<?php


# This is a script to pull data from a BOB GUI control panel instance and push it into a BOB GUI live instance (via a staging database).
# It runs in a shell context, not a web context.

# Requires database privileges: SELECT,INSERT,DELETE,CREATE,ALTER,DROP on votesstaging (staging) database and SELECT,INSERT,CREATE on votes (live) database


/*

ingest script
-------------

=Check for lockfile and raise error if present
=Create lockfile or raise error if not writable
=Check logfile is writable
=Make database connection
=x=Check database permissions of staging database and live database or raise error if too few/many
=Retrieve data as non-empty string or raise error
=Check dates in sync or raise error
Check the time is around 30 minutes past the hour
=Empty all data in staging instances table and delete staging vote(r|s) tables
If retrieved data is not empty
	=Check structure of data for completeness
	=Check that they are not set to open within an hour from now
	=Check that no IDs match the live.instances table
	=Add new staging instances entry
	=Create new staging vote(r|s) tables
If any future instances
	=Move staging vote(r|s) tables to live.vote(r|s) tables
	=Move forthcoming staging instances entry(ies) to live.instances
=Close database connection
=Delete lockfile

*/


/* Timings

Take the scenario of a ballot opening at midday (12:00 hrs).
Admin side is set to have a two-hour cut-off time for editing.
Therefore the ballot will become uneditable at 10:00.
Therefore it should not change from 10:00 onwards.
At 10:30 the Ingest side will request the data from Admin's 'bestow' endpoint.
Therefore by 11:00 the Admin bestow endpoint can stop presenting the data for a ballot which opens at 12:00.
So bestow should not present data whose instance opens less than one hour from the current time.

On the Ingest side, the data for this 12:00-opening vote will have been retrieved at 10:30.
When it next runs, at 11:30, data for a ballot opening at 12:00 should not be present because the Admin bestow point will have stopped presenting it, but a ballot opening at 13:00 could be there.
Therefore we can say that the bestowed data should not have instances that open less than one hour from the current time. This gives a 30 minute margin of error in practice.
At 10:31 (actually marginally after 10:30:00 but 10:31 is used for clarity), the ingesting to the staging side takes place.
At 10:32 (actually marginally after the ingest to staging), any staging vote due to open in less than two hours should be pushed live.

*/




# Class to ingest data for the BOB GUI
class bobguiIngest
{
	# State variables
	var $databaseConnection = NULL;
	var $currentTime = NULL;
	
	# Config defaults (setting both structure and default values; NULL means that the instantiator must supply a value)
	private function defaults ()
	{
		return $defaults = array (
			
			# Database credentials
			'hostname'				=> 'localhost',
			'username'				=> NULL,
			'password'				=> NULL,
			'databaseStaging'		=> 'votesstaging',
			'databaseLive'			=> 'votes',
			'instancesTableName'	=> 'instances',
			'vendor'				=> 'mysql',	// Database vendor
			
			# Organisation name
			'organisationName' => false,
			
			# E-mail address used for error reports generated by bobgui itself
			'administratorEmail' => NULL,
			
			# Instance data supply endpoint; this URL should be exempted from any public statistics report
			'instanceDataUrl' => NULL,
			'instanceDataApiKey' => NULL,
			
			# SMS error reporting
			'smsApiKey' => false,
			'smsRecipient' => false,
			
			# Log/lock files, relative to the current file
			'logFile' => dirname (__FILE__) . '/bobguiIngestLog.txt',
			'lockFile' => dirname (__FILE__) . '/lock/lockfile.txt',
			
			# The number of hours which the admin GUI side fixes the ballot from
			'ballotFixedHoursFromOpening' => 2,
			
			# Set the number of seconds representing an acceptable timestamp mismatch between the bestow data and the local time
			'timestampMismatch' => 120,	// 2 minutes drift
			
			# Expected launch time in minutes past the hour, and number of seconds either side of 30 minutes past the hour that this can run
			'launchTimeMinutes' => 30,
			'launchTimeMismatch' => 120,	// 2 minutes drift
			'maximumAcceptableScriptExecutionTime' => 300,	// 5 minutes; allows for the bestow end to be slow in bestowing
			
			# Live server URL (for use in an e-mail only)
			'liveServerUrl' => NULL,	// Not slash-terminated, e.g. 'https://www.example.com'
			
			# Document root (used for checking folder names to prevent clashes)
			'documentRoot' => '../',
		);
	}
	
	
	# Constructor (front controller)
	function __construct ($settings = array ())	// $config is an array coming from a launching file such as index.php or index.html which instantiates the class
	{
		# Set the timezone explicitly (PHP 5.3+ requires this)
		ini_set ('date.timezone', 'Europe/London');
		
		# Set high memory
		#!# Can be removed when inefficient array handling reduced
		ini_set('memory_limit', '600M');
		
		# Load external libraries
		ini_set ('include_path', dirname (__FILE__) . '/../lib/');
		require_once ('database.php');
		
		# Function to merge the arguments; note that $errors returns the errors by reference and not as a result from the method
		if (!$this->settings = $this->mergeConfiguration ($this->defaults (), $settings)) {
			$this->reportErrors ();
			return false;
		}
		
		# End if the lock file is present
		if (file_exists ($this->settings['lockFile'])) {
			$this->errors[] = 'The lock file exists so the ingest process cannot run.';
			$this->reportErrors ();
			return false;
		}
		
		# Ensure the log file exists and is writable
		if (!is_writable ($this->settings['logFile'])) {
			$this->errors[] = 'The log file is not writable.';
			$this->reportErrors ($writeLogMessage = false);
			return false;
		}
		
		# Write the lock file
		if (!@file_put_contents ($this->settings['lockFile'], 'lockfile, created at ' . date ('Y-m-d H:i:s'))) {
			$this->errors[] = 'The lock file could not be created.';
			$this->reportErrors ();
			return false;
		}
		
		# Prevent running in a non-shell context
		if (isSet ($_SERVER['SERVER_NAME'])) {
			$this->errors[] = 'The ingest process must be run in a shell context and not in a webserver context.';
			$this->reportErrors ();
			return false;
		}
		
		# Set the start time for later comparison that the script isn't taking too long
		$this->startTime = time ();
		
		# Ensure this is being run at around 30 minutes past the hour
		if (!substr_count (PHP_OS, 'WIN')) {		// #!# Scrappy test for detecting a development machine
			$expectedSecondsPastHour = $this->settings['launchTimeMinutes'] * 60;	// i.e. 30 minutes
			$minimumAcceptableSecondsPastHour = $expectedSecondsPastHour - $this->settings['launchTimeMismatch'];
			$maximumAcceptableSecondsPastHour = $expectedSecondsPastHour + $this->settings['launchTimeMismatch'];
			$currentSecondsPastHour = ((int) date ('i') * 60) + ((int) date ('s'));
			if (($currentSecondsPastHour < $minimumAcceptableSecondsPastHour) || ($currentSecondsPastHour > $maximumAcceptableSecondsPastHour)) {
				$this->errors[] = "The import was run at " . date ('r') . ", which is not within {$this->settings['launchTimeMismatch']} seconds of {$this->settings['launchTimeMinutes']} past the hour. Please check the system clock.";
				$this->reportErrors ();
				return false;
			}
		}
		
		# Open the database connection
		$this->databaseConnection = new database ($this->settings['hostname'], $this->settings['username'], $this->settings['password'], $database = false, $this->settings['vendor']);
		if (!$this->databaseConnection->connection) {
			$this->errors[] = 'The system was unable to connect to the database.';
			$this->reportErrors ();
			return false;
		}
		
		# Enable strict WHERE handling
		$this->databaseConnection->setStrictWhere ();
		
		# Assign a shortcut for the instances tables in use
		$this->dataSourceStaging = $this->settings['databaseStaging'] . '.' . $this->settings['instancesTableName'];
		$this->dataSourceLive = $this->settings['databaseLive'] . '.' . $this->settings['instancesTableName'];
		
		# Ingest the data into the staging database
		if (!$this->ingestToStaging ()) {
			return false;
		}
		
		# Move imminent staging votes to the live database
		if (!$this->moveStagingToLive ()) {
			return false;
		}
		
		# Explicitly close the database connection to prevent further execution (this is otherwise done implicitly by PHP anyway at script end)
		$this->databaseConnection->close ();
		
		# Remove the lock file; if something has failed the lockfile will stay in place to prevent further execution
		if (!@unlink ($this->settings['lockFile'])) {
			$this->errors[] = 'The lock file could not be deleted.';
			$this->reportErrors ();
			return false;
		}
		
		# End
		return true;
	}
	
	
	# Function to ingest instance data from the 'write' side and clear existing data
	function ingestToStaging ()
	{
		# Get the new instances data from the setup server
		$instanceDataUrl = $this->settings['instanceDataUrl'] . '?key=' . $this->settings['instanceDataApiKey'];
		if (!$data = file_get_contents ($instanceDataUrl)) {
			$this->errors[] = 'There was a problem obtaining the instance data.';
			$this->reportErrors ();
			return false;
		}
		$this->logMessage ('START Checkpoint 1: Instance data obtained; string length = ' . strlen ($data));
		
		# Decode the string of new data back into an array
		$newInstances = json_decode ($data, true);
		if ($newInstances === NULL) {
			$this->errors[] = 'The received response could not be decoded as valid JSON.';
			$this->reportErrors ();
			return false;
		}
		
		# Ensure the script is not taking too long
		$now = time ();
		if (($now - $this->startTime) > $this->settings['maximumAcceptableScriptExecutionTime']) {
			$this->errors[] = "The import script is taking too long to execute (as judged just after checkpoint 1).";
			$this->reportErrors ();
			return false;
		}
		
		# Check the timestamp then delete it
		if (!isSet ($newInstances['_timestamp']) || (abs ($now - (int) $newInstances['_timestamp']) > $this->settings['timestampMismatch'])) {
			$difference = abs ($now - (int) $newInstances['_timestamp']);
			$this->errors[] = "The ingest and bestow clocks are too far out of sync - by {$difference} seconds.";
			$this->reportErrors ();
			return false;
		}
		unset ($newInstances['_timestamp']);
		
		# Get the list of tables in the staging database
		$stagingTables = $this->databaseConnection->getTables ($this->settings['databaseStaging']);
		$this->logMessage ('Checkpoint 2: List of current tables in staging database found; total tables (including the instances table) = ' . count ($stagingTables));
		
		# Drop each table in the staging database (if there are any), except the instances table
		$dropTables = array ();
		foreach ($stagingTables as $index => $table) {
			if ($table != $this->settings['instancesTableName']) {
				$dropTables[] = '`' . $this->settings['databaseStaging'] . '`.`' . $table . '`';
			}
		}
		if ($dropTables) {
			$query = 'DROP TABLE ' . implode (', ', $dropTables) . ';';
			if (!$result = $this->databaseConnection->query ($query)) {
				#!# This never gets thrown, but exec can't be used instead, as that fails when there are no tables; however, this is not a problem as a check is done for the number of tables straight afterwards.
				$this->errors[] = "The staging voter+votes tables could not be deleted. The database server said:\n" . print_r ($this->databaseConnection->error (), true);
				$this->reportErrors ();
				return false;
			}
		}
		
		# Confirm a single table left, staging
		$stagingTables = $this->databaseConnection->getTables ($this->settings['databaseStaging']);
		if (!$stagingTables || (count ($stagingTables) != 1) || $stagingTables[0] != 'instances') {
			$this->errors[] = 'After attempted deletion of the staging voter+votes tables, there was not just a single staging instances table left.';
			$this->reportErrors ();
			return false;
		}
		$this->logMessage ('Checkpoint 3: staging voter+votes tables dropped');
		
		# Empty the list of current instances in the staging instances table
		$query = "TRUNCATE {$this->dataSourceStaging};";
		$result = $this->databaseConnection->execute ($query);
		if ($result === false) {
			$this->errors[] = "The list of entries in the staging instances table could not be cleared out. The database server said:\n" . print_r ($this->databaseConnection->error (), true);
			$this->reportErrors ();
			return false;
		}
		
		# Confirm an empty staging table
		$totalRecords = $this->databaseConnection->getTotal ($this->settings['databaseStaging'], $this->settings['instancesTableName']);
		if ($totalRecords != '0') {
			$this->errors[] = 'The staging instances table is not empty after attempting to clear it out.';
			$this->reportErrors ();
			return false;
		}
		$this->logMessage ("Checkpoint 4: Staging instances cleared out from the {$this->dataSourceStaging} table");
		
		# End if there are no future instances
		if (!$newInstances) {
			$this->logMessage ('END Checkpoint 5-END: No new instances, so all work is done' . "\n");
			return true;
		}
		
		# Ensure the instance data has four sections, each a non-empty array
		$expectedParts = array ('settings', 'votertable', 'votestable', 'voters');
		foreach ($newInstances as $instanceId => $instance) {
			foreach ($expectedParts as $part) {
				if (!isSet ($newInstances[$instanceId][$part]) || !is_array ($newInstances[$instanceId][$part]) || empty ($newInstances[$instanceId][$part])) {
					$this->errors[] = "The instance data is not correctly structured. The data is:\n" . print_r ($newInstances, true);
					$this->reportErrors ();
					return false;
				}
			}
			
			# No checks are done as to the validity of data within the instance, e.g. that ballotEnd > ballotStart, etc., because the ingesting process security is designed to stop existing votes being disrupted, rather than check the validity of a ballot (which is later checked by BOB at vote runtime)
			
		}
		$this->logMessage ('Checkpoint 5: New instance data confirmed correctly structured; total instances = ' . count ($newInstances));
		
		# Canonicalise the documentRoot setting
		$this->settings['documentRoot'] = realpath (dirname (__FILE__) . '/' . $this->settings['documentRoot']) . '/';
		
		# Get a list of folders in the documentRoot
		$subdirectories = array ();
		if (!is_readable ($this->settings['documentRoot']) || (!$dir = opendir ($this->settings['documentRoot']))) {
			$this->errors[] = 'Could not access the specified document root to obtain a list of subdirectories.';
			$this->reportErrors ();
			return false;
		} else {
			while ($file = readdir ($dir)) {
				if ($file != '.' && $file != '..') {
					if (is_dir ($this->settings['documentRoot'] . $file)) {
						$subdirectories[] = $file;
					}
				}
			}
			if (!$subdirectories) {
				$this->errors[] = 'No subdirectories were found in the specified document root.';
				$this->reportErrors ();
				return false;
			}
		}
		
		# Check that no incoming instance's organisation key matches an existing directory name
		foreach ($newInstances as $instanceId => $instance) {
			if (in_array ($instance['settings']['organisation'], $subdirectories)) {
				$this->errors[] = "The instance data contains a reserved organisation name ({$instance['settings']['organisation']}). The data is:\n" . print_r ($newInstances, true);
				$this->reportErrors ();
				return false;
			}
		}
		$this->logMessage ('Checkpoint 6: New instance data confirmed as not clashing with any local folder names.');
		
		# Ensure the script is not taking too long
		$now = time ();
		if (($now - $this->startTime) > $this->settings['maximumAcceptableScriptExecutionTime']) {
			$this->errors[] = "The import script is taking too long to execute (as judged just after checkpoint 5).";
			$this->reportErrors ();
			return false;
		}
		
		# Check that each instance does not open less than one hour from the current time
		$allowableOpeningTimeSecondsFromNow = ($this->settings['ballotFixedHoursFromOpening'] - 1) * 60 * 60;	// i.e. 2 hours minus 1 = 1 hour, converted to seconds
		foreach ($newInstances as $instanceId => $instance) {
			$openingTime = strtotime ($instance['settings']['ballotStart']);
			if (($openingTime - $now) < $allowableOpeningTimeSecondsFromNow) {
				$this->errors[] = "A scheduling error has occured. The instance {$instanceId}, presented by and retrieved from the Admin GUI bestow point, is set to open at {$instance['settings']['ballotStart']}, which is an opening time less than an hour from the current time. Check that the bestowing function is correctly limiting data.";
				$this->reportErrors ();
				return false;
			}
		}
		$this->logMessage ('Checkpoint 7: New instance data confirmed as not containing any new instances set to open less than one hour from the current time.');
		
		# Get the list of instance IDs in the live datasource
		$query = "SELECT id FROM {$this->dataSourceLive};";
		if (!$instancesLive = $this->databaseConnection->getData ($query, $this->dataSourceLive)) {
			$this->errors[] = "The list of live instance IDs could not be retrieved or was empty. The database server said:\n" . print_r ($this->databaseConnection->error (), true) /* . "\nThe data was:\n" . print_r ($instance['voters'], true) */;	// Data not currently shown as it could be very large!
			$this->reportErrors ();
			return false;
		}
		
		# Check that each instance does not appear in the list of live instances
		foreach ($newInstances as $instanceId => $instance) {
			if (array_key_exists ($instanceId, $instancesLive)) {
				$this->errors[] = "The list of new staging instances to be imported contained an ID ({$instanceId}) already present in the live votes.instances table.";
				$this->reportErrors ();
				return false;
			}
		}
		$this->logMessage ('Checkpoint 8: New instance data confirmed as not containing any new instances whose IDs are already in the live votes.instances table.');
		
		# Get the list of tables in the live database
		if (!$liveTables = $this->databaseConnection->getTables ($this->settings['databaseLive'])) {
			$this->errors[] = "No live tables were found.";
			$this->reportErrors ();
			return false;
		}
		$this->logMessage ('Checkpoint 9: List of current tables in live database found; total tables (including the instances table) = ' . count ($liveTables));
		
		# Check that the tables which will be created in the staging and then live databases do not already exist in the live database (which would indicate a mismatch between the live and staging databases, as well as a mismatch between the live.instances table and the list of live voter/votes tables)
		foreach ($newInstances as $instanceId => $instance) {
			$tableTypes = array ('voter', 'votes');
			foreach ($tableTypes as $table) {
				$checkFor = $instanceId . "_{$table}";
				if (in_array ($checkFor, $liveTables)) {
					$this->errors[] = "A new instance {$instanceId} was specified in the list of new staging instances to be imported, but there already exists a table in the live database called {$checkFor}. This indicates data mismatch between the live and staging databases, as well as a mismatch between the live.instances table and the list of live voter/votes tables.";
					$this->reportErrors ();
					return false;
				}
			}
		}
		$this->logMessage ('Checkpoint 10: New instance data confirmed as not containing any new instances whose IDs have the same name as voter/votes tables in the live database.');
		
		# Create each instance
		foreach ($newInstances as $instanceId => $instance) {
			
			# Create the voter and votes tables
			$tableTypes = array ('voter', 'votes');
			foreach ($tableTypes as $table) {
				
				# Create the table
				if (!$this->databaseConnection->createTable ($this->settings['databaseStaging'], $instanceId . "_{$table}", $instance["{$table}table"])) {
					$this->errors[] = "There was a problem creating the {$table} table for the {$instanceId} instance. The database server said:\n" . print_r ($this->databaseConnection->error (), true) . "\nThe data was:\n" . print_r ($instance["{$table}table"], true);
					$this->reportErrors ();
					return false;
				}
			}
			$this->logMessage ("Checkpoint 11[{$instanceId}]: Tables created for instance; instanceID = {$instanceId}");
			
			# Force specification that they have not voted
			foreach ($instance['voters'] as $key => $voter) {
				$instance['voters'][$key]['voted'] = '0';
			}
			
			# Break the voter list into smaller groups if necessary
			$maxPerGroup = 5000;
			$insertGroupNumber = 1;
			$i = 0;
			$inserts = array ();
			foreach ($instance['voters'] as $key => $value) {
				if ($i == $maxPerGroup) {
					$i = 0;
					$insertGroupNumber++;
				}
				$i++;
				$inserts[$insertGroupNumber][$key] = $value;
			}
			
			# Insert the list of voters
			$totalInsertGroups = count ($inserts);
			$totalVotersThisInstance = count ($instance['voters']);
			$insertedVotersCumulativeThisInstance = 0;
			foreach ($inserts as $groupNumber => $insert) {
				if (!$this->databaseConnection->insertMany ($this->settings['databaseStaging'], $instanceId . '_voter', $insert)) {
					$this->errors[] = "There was a problem inserting the list of voters (insert group no. {$groupNumber} of {$totalInsertGroups}). The database server said:\n" . print_r ($this->databaseConnection->error (), true) /* . "\nThe data was:\n" . print_r ($instance['voters'], true) */;	// Data not currently shown as it could be very large!
					$this->reportErrors ();
					return false;
				}
				$totalVotersThisInsert = count ($insert);
				$insertedVotersCumulativeThisInstance += $totalVotersThisInsert;
				$this->logMessage ("Checkpoint 12[{$instanceId}]: Voters for instance added (insert group no. {$groupNumber} of {$totalInsertGroups}); instanceID = {$instanceId}, total voters = {$totalVotersThisInsert}" . ($totalInsertGroups > 1 ? "/{$totalVotersThisInstance} (cumulative this instance: {$insertedVotersCumulativeThisInstance})" : ''));
			}
			
			# Add each instance to the instances table
			if (!$this->databaseConnection->insert ($this->settings['databaseStaging'], $this->settings['instancesTableName'], $instance['settings'])) {
				$this->errors[] = "There was a problem registering an instance. The database server said:\n" . print_r ($this->databaseConnection->error (), true) . "\nThe data was:\n" . print_r ($instance['settings'], true);
				$this->reportErrors ();
				return false;
			}
			$this->logMessage ("Checkpoint 13[{$instanceId}]: Instance added to the instances table; instanceID = {$instanceId}");
		}
		
		# Confirm success
		$this->logMessage ('END Checkpoint 14: New data all correctly copied from Admin GUI to staging database.');
		return true;
	}
	
	
	# Function to move imminent staging votes (i.e. within the 2nd hour before opening, i.e. between 1 and 2 hours before opening) to the live database
	function moveStagingToLive ()
	{
		# Ensure the script is not taking too long
		$now = time ();
		if (($now - $this->startTime) > $this->settings['maximumAcceptableScriptExecutionTime']) {
			$this->errors[] = "The import script is taking too long to execute (as judged just at the start of moveStagingToLive).";
			$this->reportErrors ();
			return false;
		}
		
		# Obtain the staging instances that are imminent
		$query = "SELECT * FROM {$this->dataSourceStaging} WHERE (ballotStart > DATE_ADD(FROM_UNIXTIME({$now}), INTERVAL 1 HOUR)) AND (ballotStart < DATE_ADD(FROM_UNIXTIME({$now}), INTERVAL 2 HOUR));";
		$stagingInstancesToMakeLive = $this->databaseConnection->getData ($query, $this->dataSourceStaging);
		
		# End if there are no imminent instances
		if (!$stagingInstancesToMakeLive) {
			$this->logMessage ('END Checkpoint 15-END: No imminent (1-2 hours till live) instances, so all work is done' . "\n");
			return true;
		}
		
		# The live database has already been checked to ensure that the new IDs do not clash with either entries in the live instances list or with the live vote(r|s) tables.
		# Even if that check failed, the SQL queries will then fail because of an attempted overwrite.
		
		
		# Make each staging instance live by moving it across databases
		foreach ($stagingInstancesToMakeLive as $instanceId => $instance) {
			
			# Move the tables across
			/* See: http://dev.mysql.com/doc/refman/5.1/en/rename-table.html . If ALTER TABLE rights are not available on the staging side, http://www.tech-recipes.com/rx/1487/copy-an-existing-mysql-table-to-a-new-table/ could be used instead, but that is non-atomic. */
			$tableTypes = array ('voter', 'votes');
			foreach ($tableTypes as $table) {
				$query = "RENAME TABLE `{$this->settings['databaseStaging']}`.`{$instanceId}_{$table}` TO `{$this->settings['databaseLive']}`.`{$instanceId}_{$table}`;";
				$result = $this->databaseConnection->execute ($query);
				if ($result === false) {
					$this->errors[] = "The moving of {$instanceId}_{$table} from the staging to live side failed. The database server said:\n" . print_r ($this->databaseConnection->error (), true);
					$this->reportErrors ();
					return false;
				}
			}
			$this->logMessage ("Checkpoint 15[{$instanceId}]: Voter and votes table moved from staging to live side; instanceID = {$instanceId}");
			
			# Copy the instance table entry across
			$query = "INSERT INTO `{$this->settings['databaseLive']}`.instances (SELECT * FROM `{$this->settings['databaseStaging']}`.instances AS stagingInstances WHERE stagingInstances.id = :instanceId LIMIT 1);";
			if (!$result = $this->databaseConnection->query ($query, array ('instanceId' => $instanceId))) {
				$this->errors[] = "The copying of the {$instanceId} instance configuration from the staging to live side failed. The database server said:\n" . print_r ($this->databaseConnection->error (), true);
				$this->reportErrors ();
				return false;
			}
			$this->logMessage ("Checkpoint 16[{$instanceId}]: Copied instance configuration from staging to live side; instanceID = {$instanceId}");
			
			# Delete the staging instance table entry
			$query = "DELETE FROM `{$this->settings['databaseStaging']}`.instances WHERE id = :instanceId LIMIT 1;";
			if (!$result = $this->databaseConnection->query ($query, array ('instanceId' => $instanceId))) {
				$this->errors[] = "The deleting of the {$instanceId} staging instance configuration failed. The database server said:\n" . print_r ($this->databaseConnection->error (), true);
				$this->reportErrors ();
				return false;
			}
			$this->logMessage ("Checkpoint 17[{$instanceId}]: Deleted staging instance configuration; instanceID = {$instanceId}");
			
			# Mail the admin to confirm that a vote has been pushed into live
			$subject = ($this->settings['organisationName'] ? $this->settings['organisationName'] . ' online' : 'Online') . " voting system: vote pushed live! - {$instance['title']} [{$instanceId}]";
			$message = "\n\nA new ballot instance, {$instanceId}, has been pushed into the live voting database. You can see it at:\n\n{$this->settings['liveServerUrl']}{$instance['url']}\n\n\nIt has attributes:\n\n" . print_r ($instance, true);
			$extraHeaders = 'From: ' . $this->settings['administratorEmail'];
			mail ($this->settings['administratorEmail'], $subject, $message, $extraHeaders);	// Not wordwrapped so that the instance configuration array is easier to read
			$this->logMessage ("Checkpoint 18[{$instanceId}]: E-mailed administrator with details of vote successfully made live.");
			
		}
		
		# Confirm success
		$this->logMessage ('END Checkpoint 19: Imminent instances all correctly moved from staging database to live database.' . "\n");
		return true;
	}
	
	
	# Function to log changes
	private function logMessage ($message)
	{
		# Construct the message
		$logEntry = date ('Y-m-d H:i:s') . ' ' . $message . "\n";
		
		# Log the change
		file_put_contents ($this->settings['logFile'], $logEntry, FILE_APPEND);
	}
	
	
	# Generalised support function to display errors
	protected function reportErrors ($writeLogMessage = true)
	{
		# Log the error if required
		if ($writeLogMessage) {
			$this->logMessage (' *** ERROR *** ' . implode ("\n *** ERROR *** ", $this->errors) . "\n");
		}
		
		# Build up a list of errors if there are any
		$subject = get_class ($this) . ' user runtime error';
		$message = "\n\nThe voting import system has shut down because of the following error, and will not run again until fixed.\n\n\nError details:\n\n" . implode ("\n", $this->errors);
		$extraHeaders = 'From: ' . $this->settings['administratorEmail'];
		mail ($this->settings['administratorEmail'], $subject, wordwrap ($message), $extraHeaders);
		
		# Send an SMS if required
		if ($this->settings['smsRecipient']) {
			$smsMessage = 'Voting import problem (on machine ' . php_uname ('n') . ') - importing stopped';
			file_get_contents ("https://api.clockworksms.com/http/send.aspx?key=" . $this->settings['smsApiKey'] . "&to=" . $this->settings['smsRecipient'] . "&content=" . urlencode ($smsMessage));
		}
		
		# Explicitly close the database connection to prevent further execution (this is otherwise done implicitly by PHP anyway at script end)
		if ($this->databaseConnection) {
			$this->databaseConnection->close ();
		}
	}
	
	
	# Function used by assignConfiguration to merge defaults with supplied config
	private function mergeConfiguration ($defaults, $suppliedArguments)
	{
		# Start a list of errors (so that all setup errors are shown at once)
		$errors = array ();
		
		# Merge the defaults
		$arguments = array ();
		foreach ($defaults as $argument => $defaultValue) {
			
			# Sanity check: fields marked NULL or array() in the defaults MUST be supplied in the config and must not be an empty string
			if ((is_null ($defaultValue) || $defaultValue === array ()) && (!isSet ($suppliedArguments[$argument]) || !strlen ($suppliedArguments[$argument]))) {
				$errors[] = "No '<strong>{$argument}</strong>' has been set in the configuration.";
				
			# Having passed the check, reverting to the default value if no value is specified in the supplied config
			} else {
				$arguments[$argument] = (isSet ($suppliedArguments[$argument]) ? $suppliedArguments[$argument] : $defaultValue);
			}
		}
		
		# Assign and return the errors if there are any
		if ($errors) {
			$this->errors += $errors;
			return false;
		}
		
		# Return the arguments
		return $arguments;
	}
}

?>
