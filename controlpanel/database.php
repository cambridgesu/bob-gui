<?php

/*
 * Coding copyright Martin Lucas-Smith, University of Cambridge, 2003-13
 * Version 2.3.2
 * Uses prepared statements (see http://stackoverflow.com/questions/60174/best-way-to-stop-sql-injection-in-php ) where possible
 * Distributed under the terms of the GNU Public Licence - www.gnu.org/copyleft/gpl.html
 * Requires PHP 4.1+ with register_globals set to 'off'
 * Download latest from: http://download.geog.cam.ac.uk/projects/database/
 */


# Class containing basic generalised database manipulation functions for PDO
class database
{
	# Global class variables
	public $connection = NULL;
	private $preparedStatement = NULL;
	private $query = NULL;
	private $queryValues = NULL;
	private $strictWhere = false;
	private $fieldsCache = array ();
	
	
	# Function to connect to the database
	public function __construct ($hostname, $username, $password, $database = NULL, $vendor = 'mysql', $logFile = false, $userForLogging = false, $unicode = true)
	{
		# Assign the user for logging
		$this->logFile = $logFile;
		$this->userForLogging = $userForLogging;
		
		# Make attributes available for querying by calling applications
		$this->hostname = $hostname;
		$this->vendor = $vendor;
		
		# Convert localhost to 127.0.0.1
		if ($hostname == 'localhost') {
			if (version_compare (PHP_VERSION, '5.3.0', '>=')) {
				// Previously believed only to affect Windows Vista, but not the case. On PHP 5.3.x on Windows (Vista) see http://bugs.php.net/45150
				// if (substr (PHP_OS, 0, 3) == 'WIN') {
					$hostname = '127.0.0.1';
				// }
			}
		}
		
		# Connect to the database and return the status
		$dsn = "{$vendor}:host={$hostname}" . ($database ? ";dbname={$database}" : '');
		try {
			$this->connection = new PDO ($dsn, $username, $password);
		} catch (PDOException $e) {
			// error_log ("{$e} {$dsn}, {$username}, {$password}");		// Not enabled by default as $e can contain passwords which get dumped to the webserver's error log
			return false;
		}
		
		# Set transfers to UTF-8
		if ($unicode) {
			$this->execute ("SET NAMES 'utf8'");
			// # The following is a more portable version that could be used instead
			//$charset = $this->getOne ("SHOW VARIABLES LIKE 'character_set_database';");
			//$this->execute ("SET NAMES '" . $charset['Value'] . "';");
		}
	}
	
	
	# Function to disconnect from the database
	public function close ()
	{
		# Close the connection
		$this->connection = NULL;
	}
	
	
	# Function to enable whether automatically-constructed WHERE=... clauses do proper, exact comparisons, so that id="1 x" doesn't match against id value 1 in the database
	public function setStrictWhere ($boolean = true)
	{
		$this->strictWhere = $boolean;
	}
	
	
	# Function to execute a generic SQL query
	public function query ($query, $preparedStatementValues = array (), $debug = false)
	{
		return $this->queryOrExecute (__FUNCTION__, $query, $preparedStatementValues, $debug);
	}
	
	
	# Function to execute a generic SQL query
	public function execute ($query, $preparedStatementValues = array (), $debug = false)
	{
		return $this->queryOrExecute (__FUNCTION__, $query, $preparedStatementValues, $debug);
	}
	
	
	# Function used by both query and execute
	private function queryOrExecute ($mode, $query, $preparedStatementValues = array (), $debug = false)
	{
		# Global the query and any values
		$this->query = $query;
		$this->queryValues = $preparedStatementValues;
		
		# Show the query if debugging
		#!# Deprecate this
		if ($debug) {
			echo $query . "<br />";
		}
		
		# If using prepared statements, prepare then execute
		$this->preparedStatement = NULL;	// Always clear to avoid the error() function returning results of a previous statement
		if ($preparedStatementValues) {
			
			# Execute the statement (ending if there is an error in the query or parameters)
			$this->preparedStatement = $this->connection->prepare ($query);
			if (!$result = $this->preparedStatement->execute ($preparedStatementValues)) {
				return false;
			}
			
			# In execute mode, get the number of affected rows
			if ($mode == 'execute') {
				$result = $this->preparedStatement->rowCount ();
			}
			
		} else {
			
			# Execute the query and get the number of affected rows
			$function = ($mode == 'query' ? 'query' : 'exec');
			try {
				$result = $this->connection->$function ($query);
			} catch (PDOException $e) {
				if ($debug) {echo $e;}
				return false;
			}
		}
		
		# Return the result (either boolean, or the number of affected rows)
  		return $result;
	}
	
	
	# Function to get the data where only one item will be returned; this function has the same signature as getData
	# Uses prepared statement approach if a fourth parameter providing the placeholder values is supplied
	public function getOne ($query, $associative = false, $keyed = true, $preparedStatementValues = array ())
	{
		# Get the data
		$data = $this->getData ($query, $associative, $keyed, $preparedStatementValues);
		
		# Ensure that only one item is returned
		if (count ($data) > 1) {return NULL;}
		if (count ($data) !== 1) {return false;}
		
		# Return the data, taking the first item; $data[0] would fail when using $associative
		foreach ($data as $keyOrIndex => $item) {
			return $item;
		}
	}
	
	
	# Return the value of the field column from the single-result query
	public function getOneField ($query, $field, $preparedStatementValues = array ())
	{
		# Get the result or end (returning null or false)
		if (!$result = $this->getOne ($query, false, true, $preparedStatementValues)) {return $result;}
		
		# If the field doesn't exist, return false
		if (!isSet ($result[$field])) {return false;}
		
		# Return the field
		return $result[$field];
	}
	
	
	# A single row of data from the query is expected and returned; otherwise false is returned (never NULL)
	public function expectOne ($query)
	{
		# Get the data or end
		if (!$result = $this->getOne ($query)) {return false;}
    	
		# Return the result
		return $result;
	}
	
	
	# A single row of data from the query is expected and returned; otherwise false is returned
	public function expectOneField ($query, $field)
	{
		// Without any error handling this is the same as getOneField
		#!# Is this expectOneField() function needed therefore - or is this just incomplete?
		$result = $this->getOneField ($query, $field);
    	
		# Return the result
		return $result;
	}
	
	
	# Gets results from the query, returning false if there are none (never an empty array)
	public function expectData ($query)
	{
		# Get the data or end
		if (!$result = $this->getData ($query)) {return false;}
    	
		# Return the result
		return $result;
	}
	
	
	# Function to get the data where either (i) only one column per item will be returned, resulting in index => value, or (ii) two columns are returned, resulting in col1 => col2
	# Uses prepared statement approach if a third parameter providing the placeholder values is supplied
	public function getPairs ($query, $unique = false, $preparedStatementValues = array ())
	{
		# Get the data
		$data = $this->getData ($query, false, $keyed = false, $preparedStatementValues);
		
		# Convert to pairs
		$pairs = $this->toPairs ($data, $unique);
		
		# Return the data
		return $pairs;
	}
	
	
	# Helper function to convert data to pairs; assumes that the values in each item are not associative
	private function toPairs ($data, $unique = false)
	{
		# Loop through each item in the data to allocate a key/value pair
		$pairs = array ();
		foreach ($data as $key => $item) {
			
			# If more than one item, use the first two in the list as the key and value
			if (count ($item) == 1) {
				$value = $item[0];
			} else {
				$key = $item[0];
				$value = $item[1];
			}
			
			# Trim the value
			$value = trim ($value);
			
			# Add to output data
			$pairs[$key] = $value;
		}
		
		# Unique the data if necessary; note that this is unlikely to be wanted if the main keys are associative
		if ($unique) {$pairs = array_unique ($pairs);}
		
		# Return the data
		return $pairs;
	}
	
	
	# Function to get data from an SQL query and return it as an array; $associative should be false or a string "{$database}.{$table}" (which reindexes the data to the field containing the unique key) or a supplied fieldname to avoid a SHOW FULL FIELDS lookup
	# Uses prepared statement approach if a fourth parameter providing the placeholder values is supplied
	public function getData ($query, $associative = false, $keyed = true, $preparedStatementValues = array (), $onlyFields = array ())
	{
		# Global the query and any values
		$this->query = $query;
		$this->queryValues = $preparedStatementValues;
		
		# Create an empty array to hold the data
		$data = array ();
		
		# Set fetch mode
		$mode = ($keyed ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
		
		# If using prepared statements, prepare then execute
		$this->preparedStatement = NULL;	// Always clear to avoid the error() function returning results of a previous statement
		if ($preparedStatementValues) {
			
			# Execute the statement (ending if there is an error in the query or parameters)
			$this->preparedStatement = $this->connection->prepare ($query);
			#!# This sometimes gives off warnings - would be good to catch these
			if (!$this->preparedStatement->execute ($preparedStatementValues)) {
				return $data;
			}
			
			# Fetch the data
			$this->preparedStatement->setFetchMode ($mode);
			$data = $this->preparedStatement->fetchAll ();
			
		} else {
			
			# Assign the query
			if (!$statement = $this->connection->query ($query)) {
				return $data;
			}
			
			# Loop through each row and add the data to it
			$statement->setFetchMode ($mode);
			while ($row = $statement->fetch ()) {
				$data[] = $row;
			}
		}
		
		# Reassign the keys to being the unique field's name, in associative mode
		if ($associative) {
			
			# Get the unique field name, looking it up if supplied as 'database.table'; otherwise use the id directly
			if (strpos ($associative, '.') !== false) {
				list ($database, $table) = explode ('.', $associative, 2);
				$uniqueField = $this->getUniqueField ($database, $table);
			} else {
				$uniqueField = $associative;
			}
			
			# Return as non-keyed data if no unique field
			if (!$uniqueField) {
				return $data;
			}
			
			# Re-key with the field name
			$newData = array ();
			foreach ($data as $key => $attributes) {
				#!# This causes offsets if the key is not amongst the fields requested
				$newData[$attributes[$uniqueField]] = $attributes;
			}
			
			# Entirely replace the dataset; doing on a key-by-key basis doesn't work because the auto-generated keys can clash with real id key names
			$data = $newData;
		}
		
		# Filter only to specified fields if required
		if ($onlyFields) {
			foreach ($data as $index => $record) {
				foreach ($record as $key => $value) {
					if (!in_array ($key, $onlyFields)) {
						unset ($data[$index][$key]);
					}
				}
			}
		}
		
		# Return the array
		return $data;
	}
	
	
	# Function to do getData via pagination
	public function getDataViaPagination ($query, $associative = false, $keyed = true, $preparedStatementValues = array (), $onlyFields = array (), $paginationRecordsPerPage, $page = 1, $searchResultsMaximumLimit = false)
	{
		# Prepare the counting query; use a negative lookahead to match the section between SELECT ... FROM - see http://stackoverflow.com/questions/406230
		$placeholders = array (
			'/^\s*SELECT\s+(?!\s+FROM\s).+\s+FROM/misU' => 'SELECT COUNT(*) AS total FROM',
			# This works but isn't in use anywhere, so enable if/when needed with more testing '/^SELECT\s+DISTINCT\(([^)]+)\)\s+(?!\s+FROM ).+\s+FROM/' => 'SELECT COUNT(DISTINCT(\1)) AS total FROM',
		);
		$countingQuery = preg_replace (array_keys ($placeholders), array_values ($placeholders), trim ($query));
		
		# If any named placeholders are not now in the counting query, remove them from the list
		$countingPreparedStatementValues = $preparedStatementValues;
		foreach ($countingPreparedStatementValues as $key => $value) {
			if (substr_count ($query, ':' . $key) && !substr_count ($countingQuery, ':' . $key)) {
				unset ($countingPreparedStatementValues[$key]);
			}
		}
		
		# Perform a count first
		$dataCount = $this->getOne ($countingQuery, false, true, $countingPreparedStatementValues);
		$totalAvailable = $dataCount['total'];
		
		# Enforce a maximum limit if required, by overwriting the total available, which the pagination mechanism will automatically adjust to
		$actualMatchesReachedMaximum = false;
		if ($searchResultsMaximumLimit) {
			if ($totalAvailable > $searchResultsMaximumLimit) {
				$actualMatchesReachedMaximum = $totalAvailable;	// Assign the number of the actual total available, which will evaluate to true
				$totalAvailable = $searchResultsMaximumLimit;
			}
		}
		
		# Get the requested page and calculate the pagination
		require_once ('pagination.php');
		$requestedPage = (ctype_digit ($page) ? $page : 1);
		list ($totalPages, $offset, $items, $limitPerPage, $page) = pagination::getPagerData ($totalAvailable, $paginationRecordsPerPage, $requestedPage);
		
		# Now construct the main query
		$placeholders = array (
			'/;$/' => " LIMIT {$offset}, {$limitPerPage};",
		);
		$dataQuery = preg_replace (array_keys ($placeholders), array_values ($placeholders), trim ($query));
		
		# Get the data
		$data = $this->getData ($dataQuery, $associative, $keyed, $preparedStatementValues, $onlyFields);
		
		# Return the data and metadata
		return array ($data, $totalAvailable, $totalPages, $page, $actualMatchesReachedMaximum);
	}
	
	
	# Function to count the number of records
	public function getTotal ($database, $table, $restrictionSql = '')
	{
		# Check that the table exists
		$tables = $this->getTables ($database);
		if (!in_array ($table, $tables)) {return false;}
		
		# Get the total
		$query = "SELECT COUNT(*) AS total FROM `{$database}`.`{$table}` {$restrictionSql};";
		$data = $this->getOne ($query);
		
		# Return the value
		return $data['total'];
	}
	
	
	# Function to get fields
	public function getFields ($database, $table, $addSimpleType = false, $matchingRegexpNoForwardSlashes = false, $asTotal = false)
	{
		# If the raw fields list is already in the fields cache, use that to avoid a pointless SHOW FULL FIELDS lookup
		if (isSet ($this->fieldsCache[$database]) && isSet ($this->fieldsCache[$database][$table])) {
			$data = $this->fieldsCache[$database][$table];
		} else {
			
			# Cache the global query and its values, if either exist, so that they can be reinstated when this function is called by another function internally
			$cachedQuery = ($this->query ? $this->query : NULL);
			$cachedQueryValues = (!is_null ($this->queryValues) ? $this->queryValues : NULL);
			
			# Get the data
			$query = "SHOW FULL FIELDS FROM `{$database}`.`{$table}`;";
			$data = $this->getData ($query);
			
			# Restablish the catched query and its values if there is one
			if (!is_null ($cachedQuery)) {$this->query = $cachedQuery;}
			if (!is_null ($cachedQuery)) {$this->queryValues = $cachedQueryValues;}
			
			# Add the result to the fields cache, in case there is another request for getFields for this database table
			$this->fieldsCache[$database][$table] = $data;
		}
		
		# Convert the field name to be the key name
		$fields = array ();
		foreach ($data as $key => $attributes) {
			$fields[$attributes['Field']] = $attributes;
		}
		
		# Add a simple type description if required
		if ($addSimpleType) {
			foreach ($data as $key => $attributes) {
				$fields[$attributes['Field']]['_type'] = $this->simpleType ($attributes['Type']);
			}
		}
		
		# Expand ENUM field values
		foreach ($data as $key => $attributes) {
			if (preg_match ('/^enum\(\'(.+)\'\)$/i', $attributes['Type'], $matches)) {
				$fields[$attributes['Field']]['_values'] = explode ("','", $matches[1]);
			} else {
				$fields[$attributes['Field']]['_values'] = NULL;
			}
		}
		
		# Filter by regexp if required
		if ($matchingRegexpNoForwardSlashes) {
			foreach ($fields as $field => $attributes) {
				if (!preg_match ("/{$matchingRegexpNoForwardSlashes}/", $field)) {
					unset ($fields[$field]);
				}
			}
		}
		
		# If returning as a total, convert to a count
		if ($asTotal) {
			$fields = count ($fields);
		}
		
		# Return the result
		return $fields;
	}
	
	
	# Function to determine if the data is hierarchical
	public function isHierarchical ($database, $table)
	{
		# Determine if there is a parentId field and return whether it is present
		$fields = $this->getFields ($database, $table);
		return (isSet ($fields['parentId']));
	}
	
	
	# Function to create a simple type for fields
	private function simpleType ($type)
	{
		# Detect the type and give a simplified description of it
		switch (true) {
			case preg_match ('/^varchar/', $type):
				return 'string';
			case preg_match ('/text/', $type):
				return 'text';
			case preg_match ('/^(float|double|int)/', $type):
				return 'numeric';
			case preg_match ('/^(enum|set)/', $type):
				return 'list';
			case preg_match ('/^(date)/', $type):
				return 'date';
		}
		
		# Otherwise pass through the original
		return $type;
	}
	
	
	# Function to get the unique field name
	public function getUniqueField ($database, $table, $fields = false)
	{
		# Get the fields if not already supplied
		if (!$fields) {$fields = $this->getFields ($database, $table);}
		
		# Loop through to find the unique one
		foreach ($fields as $field => $attributes) {
			if ($attributes['Key'] == 'PRI') {
				return $field;
			}
		}
		
		# Otherwise return false, indicating no unique field
		return false;
	}
	
	
	# Function to get field names
	public function getFieldNames ($database, $table, $fields = false, $matchingRegexpNoForwardSlashes = false)
	{
		# Get the fields if not already supplied
		if (!$fields) {$fields = $this->getFields ($database, $table, false, $matchingRegexpNoForwardSlashes);}
		
		# Get the array keys of the fields
		return array_keys ($fields);
	}
	
	
	# Function to get field descriptions as a simple associative array
	public function getHeadings ($database, $table, $fields = false, $useFieldnameIfEmpty = true, $commentsAsHeadings = true)
	{
		# Get the fields if not already supplied
		if (!$fields) {$fields = $this->getFields ($database, $table);}
		
		# Rearrange the data
		$headings = array ();
		foreach ($fields as $field => $attributes) {
			$headings[$field] = ((((empty ($attributes['Comment']) && $useFieldnameIfEmpty)) || !$commentsAsHeadings) ? $field : $attributes['Comment']);
		}
		
		# Return the headings
		return $headings;
	}
	
	
	# Function to obtain a list of databases on the server
	public function getDatabases ($omitReserved = array ('cluster', 'information_schema', 'mysql'))
	{
		# Get the data
		$query = "SHOW DATABASES;";
		$data = $this->getData ($query);
		
		# Sort the list
		if ($data) {sort ($data);}
		
		# Rearrange
		$databases = array ();
		foreach ($data as $index => $attributes) {
			if ($omitReserved && in_array ($attributes['Database'], $omitReserved)) {continue;}
			$databases[] = $attributes['Database'];
		}
		
		# Return the data
		return $databases;
	}
	
	
	# Function to obtain a list of tables in a database
	#!# A regexp filtering option would useful and could replace some client code
	public function getTables ($database)
	{
		# Get the data
		$query = "SHOW TABLES FROM `{$database}`;";
		$data = $this->getData ($query);
		
		# Rearrange
		$tables = array ();
		foreach ($data as $index => $attributes) {
			$tables[] = $attributes["Tables_in_{$database}"];
		}
		
		# Return the data
		return $tables;
	}
	
	
	# Function to get the ID generated from the previous insert operation
	#!# Rename this for consistency
	#!# Emulate away the problem that, in the case of an insertMany, MySQL returns the *first* automatically-generated ID! - see http://dev.mysql.com/doc/refman/5.1/en/mysql-insert-id.html
	public function getLatestId ()
	{
		# Return the latest ID
		return $this->connection->lastInsertId ();
	}
	
	
	# Function to clean data
	public function escape ($uncleanData, $cleanKeys = true)
	{
		# End if no data
		if (empty ($uncleanData)) {return $uncleanData;}
		
		# If the data is an string, return it directly
		if (is_string ($uncleanData)) {
			return addslashes ($uncleanData);
		}
		
		# Loop through the data
		$data = array ();
		foreach ($uncleanData as $key => $value) {
			if ($cleanKeys) {$key = $this->escape ($key);}
			$data[$key] = $this->escape ($value);
		}
		
		# Return the data
		return $data;
	}
	
	
	# Function to deal with quotation, i.e. escaping AND adding quotation marks around the item
	/* private */ public function quote ($string)
	{
		# Strip slashes if necessary
		if (get_magic_quotes_gpc ()) {
			$string = stripslashes ($string);
		}
		
		# Special case a timestamp indication as unquoted SQL
		if ($string == 'NOW()') {
			return $string;
		}
		
		# Quote the string by calling the PDO quoting method
		$string = $this->connection->quote ($string);
		
		# Undo (unwanted automatic) backlash quoting in PDO::quote, i.e replace \\ with \ in the string; see discussion at http://www.bitpapers.com/2012/03/php-escaping-quotes.html
		$string = str_replace ('\\\\', '\\', $string);
		
		# Return the quoted string
		return $string;
	}
	
	
	# Function to construct and execute a SELECT statement
	public function select ($database, $table, $conditions = array (), $columns = array (), $associative = true, $orderBy = false, $limit = false, $keyed = true)
	{
		# Construct the WHERE clause
		$where = '';
		if ($conditions) {
			$where = array ();
			if (is_array ($conditions)) {
				foreach ($conditions as $key => $value) {
					if ($value === NULL) {		// Has to be set with a real NULL value, i.e. using $conditions['keyname'] = NULL;
						$where[] = '`' . $key . '`' . ' IS NULL';
						unset ($conditions[$key]);	// Remove the original placeholder as that will never be used, and contains an array
					} else if (is_array ($value)) {
						$i = 0;
						$conditionsThisGroup = array ();
						foreach ($value as $valueItem) {
							$valuesKey = $key . '_' . $i++;	// e.g. id_0, id_1, etc.; a numeric index is created as the values list might be associative with keys containing invalid characters
							$conditions[$valuesKey] = $valueItem;
							$conditionsThisGroup[$valuesKey] = $valueItem;
						}
						unset ($conditions[$key]);	// Remove the original placeholder as that will never be used, and contains an array
						$where[] = '`' . $key . '`' . ' IN(:' . implode (', :', array_keys ($conditionsThisGroup)) . ')';
					} else {
						$where[] = ($this->strictWhere ? 'BINARY ' : '') . '`' . $key . '`' . ' = :' . $key;
					}
				}
			} else if (is_string ($conditions)) {
				if (strlen ($conditions)) {
					$where[] = $conditions;
					$conditions = array ();	// Remove these, as there are no prepared statement values
				}
			}
			if ($where) {
				$where = ' WHERE ' . implode (' AND ', $where);
			} else {
				$where = '';
			}
		}
		
		# Construct the columns part; if the key is numeric, assume it's not a key=>value pair, but that the value is the fieldname
		$what = '*';
		if ($columns) {
			$what = array ();
			if (is_array ($columns)) {
				foreach ($columns as $key => $value) {
					if (is_numeric ($key)) {
						$what[] = $value;
					} else {
						$what[] = "{$key} AS {$value}";
					}
				}
			} else {	// Currently assumed to be a string if it's not an array
				$what[] = $columns;
			}
			$what = implode (',', $what);
		}
		
		# Construct the ordering
		$orderBy = ($orderBy ? " ORDER BY {$orderBy}" : '');
		
		# Construct the limit
		$limit = ($limit ? " LIMIT {$limit}" : '');
		
		# Prepare the statement
		$query = "SELECT {$what} FROM `{$database}`.`{$table}`{$where}{$orderBy}{$limit};\n";
		
		# Get the data
		$data = $this->getData ($query, ($associative ? "{$database}.{$table}" : false), $keyed, $conditions);
		
		# Return the data
		return $data;
	}
	
	
	# Function to select the data where only one item will be returned (as per getOne); this function has the same signature as select, except for the default on associative
	public function selectOne ($database, $table, $conditions = array (), $columns = array (), $associative_ArgumentIgnored = false, $orderBy = false)
	{
		# Get the data
		$data = $this->select ($database, $table, $conditions, $columns, false, $orderBy);
		
		# Ensure that only one item is returned
		if (count ($data) > 1) {return NULL;}
		if (count ($data) !== 1) {return false;}
		
		# Return the data
		#!# This could be unset if it's associative
		#!# http://bugs.mysql.com/36824 could result in a value slipping through that is not strictly matched - see also strictWhere
		return $data[0];
	}
	
	
	# Function to select data and return as pairs
	public function selectPairs ($database, $table, $conditions = array (), $columns = array (), $associative = true, $orderBy = false, $limit = false)
	{
		# Get the data, unkeyed (so that each record contains array(0=>value,1=>2)) (which therefore requires associative=false
		$associative = false;
		$data = $this->select ($database, $table, $conditions, $columns, $associative, $orderBy, $limit, $keyed = false);
		
		# Convert to pairs
		$pairs = $this->toPairs ($data);
		
		# Return the data
		return $pairs;
	}
	
	
	# Function to construct and execute an INSERT statement
	public function insert ($database, $table, $data, $onDuplicateKeyUpdate = false, $emptyToNull = true, $safe = false, $showErrors = false)
	{
		# Ensure the data is an array and that there is data
		if (!is_array ($data) || !$data) {return false;}
		
		# Assemble the field names
		$fields = '`' . implode ('`,`', array_keys ($data)) . '`';
		
		# Assemble the values
		$preparedValuePlaceholders = array ();
		foreach ($data as $key => $value) {
			if ($emptyToNull && ($data[$key] === '')) {$data[$key] = NULL;}	// Convert empty to NULL if required
			if ($data[$key] == 'NOW()') {	// Special handling for keywords, which are not quoted
				$preparedValuePlaceholders[] = $data[$key];	// State the value directly rather than use a placeholder
				unset ($data[$key]);
				continue;
			}
			$preparedValuePlaceholders[] = ':' . $key;
		}
		$preparedValuePlaceholders = implode (', ', $preparedValuePlaceholders);
		
		# Handle ON DUPLICATE KEY UPDATE support
		$onDuplicateKeyUpdate = $this->onDuplicateKeyUpdate ($onDuplicateKeyUpdate, $data);
		
		# Assemble the query
		$query = "INSERT INTO `{$database}`.`{$table}` ({$fields}) VALUES ({$preparedValuePlaceholders}){$onDuplicateKeyUpdate};\n";
		
		# In safe mode, only show the query
		if ($safe) {
			echo $query . "<br />";
			return true;
		}
		
		# Execute the query
		$rows = $this->execute ($query, $data, $showErrors);
		
		# Determine the result
		$result = ($rows !== false);
		
		# Log the change
		$this->logChange ($result);
		
		# Return the result
		return $result;
	}
	
	
	# Processing of ON DUPLICATE KEY UPDATE clause - see: http://dev.mysql.com/doc/refman/5.1/en/insert-on-duplicate.html
	private function onDuplicateKeyUpdate ($onDuplicateKeyUpdate, $data)
	{
		# End if not required
		if (!$onDuplicateKeyUpdate) {return '';}
		
		# If boolean true (rather than a string), compile the supplied data to a string first
		if ($onDuplicateKeyUpdate === true) {
			foreach ($data as $key => $value) {
				$clauses[] = "`{$key}`=VALUES(`{$key}`)";
			}
			$onDuplicateKeyUpdate = implode (',', $clauses);
		}
		
		# Assemble the string
		$sqlString = ' ON DUPLICATE KEY UPDATE ' . $onDuplicateKeyUpdate;
		
		# Result
		return $sqlString;
	}
	
	
	# Function to construct and execute an INSERT statement containing many items
	public function insertMany ($database, $table, $dataSet, $onDuplicateKeyUpdate = false, $emptyToNull = true, $safe = false, $showErrors = false)
	{
		# Ensure the data is an array and that there is data
		if (!is_array ($dataSet) || !$dataSet) {return false;}
		
		# Loop through each set of data
		$valuesPreparedSet = array ();
		$dataPrepared = array ();
		foreach ($dataSet as $index => $data) {
			
			# Ensure the data is an array and that there is data
			if (!is_array ($data) || !$data) {return false;}
			
			# Get the field names
			$fields = array_keys ($data);
			
			# Cache the previous field names and check for consistency, returning false if a different set of field names is found
			if (isSet ($cachedFieldList)) {
				if ($fields !== $cachedFieldList) {	// Enforce field list (including order) consistency across every record
					return false;
				}
			}
			$cachedFieldList = $fields;
			
			# Assemble the field names
			$fields = '`' . implode ('`,`', $fields) . '`';
			
			# Assemble the values
			$preparedValuePlaceholders = array ();
			foreach ($data as $key => $value) {
				if ($emptyToNull && ($data[$key] === '')) {$data[$key] = NULL;}	// Convert empty to NULL if required
				if ($data[$key] == 'NOW()') {	// Special handling for keywords, which are not quoted
					$preparedValuePlaceholders[] = $data[$key];	// State the value directly rather than use a placeholder
					unset ($data[$key]);
					continue;
				}
				$placeholder = ":{$index}_{$key}";
				$preparedValuePlaceholders[] = ' ' . $placeholder;
				$dataPrepared[$placeholder] = $data[$key];
			}
			$valuesPreparedSet[$index] = implode (',', $preparedValuePlaceholders);
		}
		
		# Handle ON DUPLICATE KEY UPDATE support
		$firstData = array_shift (array_values ($dataSet));
		$onDuplicateKeyUpdate = $this->onDuplicateKeyUpdate ($onDuplicateKeyUpdate, $firstData);
		
		# Assemble the query
		$query = "INSERT INTO `{$database}`.`{$table}` ({$fields}) VALUES (" . implode ('),(', $valuesPreparedSet) . "){$onDuplicateKeyUpdate};\n";
		
		# Prevent submission of over-long queries
		$maxLength = $this->getOne ("SHOW VARIABLES LIKE 'max_allowed_packet'");
		if (isSet ($maxLength['Value'])) {
			if (strlen ($query) > (int) $maxLength['Value']) {
				return false;
			}
		}
		
		# In safe mode, only show the query
		if ($safe) {
			echo $query . "<br />";
			return true;
		}
		
		# Execute the query
		$rows = $this->execute ($query, $dataPrepared, $showErrors);
		
		# Determine the result
		$result = ($rows !== false);
		
		# Log the change
		$this->logChange ($result);
		
		# Return the result
		return $result;
	}
	
	
	# Function to construct and execute an UPDATE statement
	public function update ($database, $table, $data, $conditions = array (), $emptyToNull = true, $safe = false)
	{
		# Ensure the data is an array and that there is data
		if (!is_array ($data) || !$data) {return false;}
		
		# Start an array of placeholder=>value data that will contain both values and conditions
		$dataUniqued = array ();
		
		# Assemble the pairs
		$preparedValueUpdates = array ();
		foreach ($data as $key => $value) {
			
			# Make the condition be that the first item is the key if nothing specified
			#!# This looks like being bogus - audit whether this can be removed or whether it is necessary for safety
			if (!$conditions) {
				$conditions[$key] = $value;	// This will only get triggered once, because it $conditions will be non-empty
			}
			
			# Add the data
			if ($emptyToNull && ($data[$key] === '')) {$data[$key] = NULL;}	// Convert empty to NULL if required
			if ($data[$key] == 'NOW()') {	// Special handling for keywords, which are not quoted
				$preparedValueUpdates[] = "`{$key}`= " . $data[$key];
				unset ($data[$key]);
				continue;
			}
			$placeholder = "data_" . $key;	// The prefix ensures namespaced uniqueness within $dataUniqued
			$preparedValueUpdates[] = "`{$key}`= :" . $placeholder;
			
			# Save the data using the new placeholder
			$dataUniqued[$placeholder] = $data[$key];
		}
		$preparedValueUpdates = implode (',', $preparedValueUpdates);
		
		# Construct the WHERE clause
		$where = '';
		if ($conditions) {
			$where = array ();
			foreach ($conditions as $key => $value) {
				$placeholder = 'conditions_' . $key;	// The prefix ensures namespaced uniqueness within $dataUniqued
				$where[] = ($this->strictWhere ? 'BINARY ' : '') . '`' . $key . '` = :' . $placeholder;
				
				# Save the data using the new placeholder
				$dataUniqued[$placeholder] = $value;
			}
			$where = ' WHERE ' . implode (' AND ', $where);
		}
		
		# Assemble the query
		$query = "UPDATE `{$database}`.`{$table}` SET {$preparedValueUpdates}{$where};\n";
		
		# In safe mode, only show the query
		if ($safe) {
			echo $query . "<br />";
			return true;
		}
		
		# Execute the query
		$rows = $this->execute ($query, $dataUniqued);
		
		# Determine the result
		$result = ($rows !== false);
		
		# Log the change
		$this->logChange ($result);
		
		# Return the result
		return $result;
	}
	
	
	#!# An update many would be useful sometimes
	
	
	# Function to delete data
	public function delete ($database, $table, $conditions, $limit = false)
	{
		# Ensure the data is an array and that there is data
		if (!is_array ($conditions) || !$conditions) {return false;}
		
		# Construct the WHERE clause
		$where = '';
		if ($conditions) {
			$where = array ();
			foreach ($conditions as $key => $value) {
				$where[] = ($this->strictWhere ? 'BINARY ' : '') . '`' . $key . '`' . ' = :' . $key;
			}
			$where = ' WHERE ' . implode (' AND ', $where);
		}
		
		# Determine any limit
		$limit = ($limit ? " LIMIT {$limit}" : '');
		
		# Assemble the query
		$query = "DELETE FROM `{$database}`.`{$table}`{$where}{$limit};\n";
		
		# Execute the query
		$result = $this->execute ($query, $conditions);
		
		# Log the change
		$this->logChange ($result);
		
		# Return the result
		return $result;
	}
	
	
	# Function to delete a set of IDs
	public function deleteIds ($database, $table, $values, $field = 'id')
	{
		# End if no items
		if (!$values || !is_array ($values)) {return false;}
		
		# Create placeholders
		$placeholders = array ();
		$placeholderValues = array ();
		$i = 0;
		foreach ($values as $key => $value) {
			$placeholderName = "p{$i}";
			$placeholders[$i] = ':' . $placeholderName;
			$placeholderValues[$placeholderName] = $value;
			$i++;
		}
		
		# Assemble the query
		$query = "DELETE FROM `{$database}`.`{$table}` WHERE " . ($this->strictWhere ? 'BINARY ' : '') . "`{$field}` IN (" . implode (', ', $placeholders) . ");";
		
		# Execute the query
		$rows = $this->execute ($query, $placeholderValues);
		
		# Log the change
		$this->logChange ($rows);
		
		# Return the number of affected rows
		return $rows;
	}
	
	
	# Function to create a table from a list of fields
	public function createTable ($database, $table, $fields, $ifNotExists = true, $type = 'InnoDB')
	{
		# Construct the list of fields
		$fieldsSql = array ();
		foreach ($fields as $fieldname => $field) {	// where $field contains the specification, either as a string like VARCHAR(255) NOT NULL, or an array containing those parts
			
			# Create a list of fields, building up a string for each equivalent to the per-field specification in a CREATE TABLE query
			if (is_array ($field)) {
				$key = $field['Field'];
				$specification  = strtoupper ($field['Type']);
				if (strlen ($field['Collation'])) {$specification .= ' collate ' . $field['Collation'];}
				if (strtoupper ($field['Null']) == 'NO') {$specification .= ' NOT NULL';}
				if (strtoupper ($field['Key']) == 'PRI') {$specification .= ' PRIMARY KEY';}
				if (strlen ($field['Default'])) {$specification .= ' DEFAULT ' . $field['Default'];}
				$field = $specification;
			}
			
			# Add the field
			$fieldsSql[] = "{$fieldname} {$field}";
		}
		
		# Compile the overall SQL; type is deliberately set to InnoDB so that rows are physically stored in the unique key order
		$query = 'CREATE TABLE' . ($ifNotExists ? ' IF NOT EXISTS' : '') . " `{$database}`.`{$table}` (" . implode (', ', $fieldsSql) . ") ENGINE={$type} CHARACTER SET utf8 COLLATE utf8_unicode_ci;";
		
		# Create the table
		if (false === $this->execute ($query)) {return false;}
		
		# Signal success
		return true;
	}
	
	
	# Function to get table metadata
	public function getTableStatus ($database, $table, $getOnly = false /*array ('Comment')*/)
	{
		# Define the query
		$query = "SHOW TABLE STATUS FROM `{$database}` LIKE '{$table}';";
		
		# Get the results
		$data = $this->getOne ($query);
		
		# If only needing certain columns, return only those
		if ($getOnly && is_array ($getOnly)) {
			foreach ($getOnly as $field) {
				if (isSet ($data[$field])) {
					$attributes[$field] = $data[$field];
				}
			}
		} else {
			$attributes = $data;
		}
		
		# Return the results
		return $attributes;
	}
	
	
	# Function to truncate a table
	public function truncate ($database, $table, $limitedPrivilegesAvailable = false)
	{
		# Determine the query
		if ($limitedPrivilegesAvailable) {
			$query = "DELETE FROM {$database}.{$table};";	// i.e. delete everything
		} else {
			$query = "TRUNCATE {$database}.{$table};";
		}
		
		# Run the query, capturing the rows changed
		$rows = $this->query ($query);
		
		# Determine the result
		$result = ($rows !== false);
		
		# Log the change
		$this->logChange ($result);
		
		# Return the result
		return $result;
	}
	
	
	# Function to set the table comment
	public function setTableComment ($database, $table, $tableComment, &$error = false)
	{
		# Ensure the string length is up to 60 characters long, as defined at: http://dev.mysql.com/doc/refman/5.1/en/create-table.html
		$maxLength = 60;	// Obviously this is currently MySQL-specific implementation
		if (strlen ($tableComment) > $maxLength) {
			$error = "The table comment must not be longer than {$maxLength} characters.";
			return false;
		}
		
		# Compile the query
		$query = "ALTER TABLE {$database}.{$table} COMMENT = '{$tableComment}';";	// Requires ALTER privilege
		
		# Run the query, capturing the rows changed
		$rows = $this->query ($query);
		
		# Determine the result
		$result = ($rows !== false);
		
		# Log the change
		$this->logChange ($result);
		
		# Return the result
		return $result;
	}
	
	
	# Function to get the table comment
	public function getTableComment ($database, $table)
	{
		# Get the table status and return the comment part
		if (!$tableStatus = $this->getTableStatus ($database, $table, array ('Comment'))) {return false;}
		return $tableStatus['Comment'];
	}
	
	
	# Function to get error information
	public function error ()
	{
		# Get the error details
		if ($this->connection) {
			if ($this->preparedStatement) {
				$error = $this->preparedStatement->errorInfo ();
			} else {
				$error = $this->connection->errorInfo ();
			}
		} else {
			$error = array ('error' => 'No database connection available');
		}
		
		# Add in the SQL statement
		$error['query'] = $this->getQuery (true);
		$error['queryEmulated'] = $this->getQuery (false);
		
		# Return the details
		return $error;
	}
	
	
	# Define a lookup function used to join fields in the format targettableId fieldname__JOIN__targetDatabase__targetTable__reserved
	#!# Caching mechanism needed for repeated fields (and fieldnames as below), one level higher in the calling structure
	public static function lookup ($databaseConnection, $fieldname, $fieldType, $simpleJoin = false, $showKeys = NULL, $orderby = false, $sort = true, $group = false, $firstOnly = false, $showFields = array (), $tableMonikerTranslations = array ())
	{
		# Determine if it's a special JOIN field
		$values = array ();
		$targetDatabase = NULL;
		$targetTable = NULL;
		$targetTableMoniker = NULL;
		if ($matches = self::convertJoin ($fieldname, $simpleJoin)) {
			
			# Load required libraries
			require_once ('application.php');
			
			# Assign the new fieldname
			$fieldname = $matches['field'];
			$targetDatabase = $matches['database'];
			$targetTable = $matches['table'];
			
			# Determine the table moniker for the target table, which is normally the same; this is useful if the client application has a table such as 'fooNames' but this maps to a nicer URL of 'foo'
			$targetTableMoniker = ($tableMonikerTranslations && isSet ($tableMonikerTranslations[$targetTable]) ? $tableMonikerTranslations[$targetTable] : $targetTable);
			
			# Get the fields of the target table
			$fields = $databaseConnection->getFieldNames ($targetDatabase, $targetTable);
			
			# Deal with ordering
			$orderbySql = '';
			if ($orderby) {
				
				# Get those fields in the orderby list that exist in the table being linked to
				$orderby = application::ensureArray ($orderby);
				$fieldsPresent = array_intersect ($orderby, $fields);
				
				# Compile the SQL
				$orderbySql = ' ORDER BY ' . implode (',', $fieldsPresent);
			}
			
			# Get the data
			#!# Enable recursive lookups
			$query = "SELECT * FROM {$targetDatabase}.{$targetTable}{$orderbySql};";
			if (!$data = $databaseConnection->getData ($query, "{$targetDatabase}.{$targetTable}")) {
				return array ($fieldname, array (), $targetDatabase, $targetTableMoniker);
			}
			
			# Sort
			if ($sort) {ksort ($data);}
			
			# Determine whether to show keys (defaults to showing keys if the field is not numeric)
			$showKey = ($showKeys === NULL ? (!strstr ($fieldType, 'int(')) : $showKeys);
			
			# Deal with grouping if required
			$grouped = false;
			if ($group) {
				
				# Determine the field to attempt to use, either a supplied fieldname or the second (first non-key) field. If the group 'name' supplied is a number, treat as an index (e.g. second key name)
				$groupField = (($group === true || is_numeric ($group)) ? application::arrayKeyName ($data, (is_numeric ($group) ? $group : 2), true) : $group);
				
				# Confirm existence of that field
				if ($groupField && in_array ($groupField, $fields)) {
					
					# Find if any group field values are unique; if so, regroup the whole dataset; if not, don't regroup
					$groupValues = array ();
					foreach ($data as $key => $rowData) {
						$groupFieldValue = $rowData[$groupField];
						if (!in_array ($groupFieldValue, $groupValues)) {
							$groupValues[$key] = $groupFieldValue;
						} else {
							
							# Regroup the data and flag this
							$data = application::regroup ($data, $groupField, false);
							$grouped = true;
							break;
						}
					}
				}
			}
			
			# Convert the data into a single key/value pair, removing repetition of the key if required
			if ($grouped) {
				foreach ($data as $groupKey => $groupData) {
					foreach ($groupData as $key => $rowData) {
						#!# Duplicated code in these two sections
						#!# This assumes the key is the first ...
						array_shift ($rowData);
						/*
						unset ($rowData[$groupField]);
						if (application::allArrayElementsEmpty ($rowData)) {
							array_unshift ($rowData, "{{$groupKey}}");
						}
						*/
						$values[$groupKey][$key]  = ($showKey ? "{$key}: " : '');
						$useFields = $rowData;
						if ($showFields) {
							require_once ('application.php');
							$useFields = application::arrayFields ($rowData, $showFields);	// Filters down to the $showFields fields only
						}
						$set = array_values ($useFields);
						$values[$groupKey][$key] .= ($firstOnly ? $set[0] : implode (' - ', $set));
					}
				}
			} else {
				foreach ($data as $key => $rowData) {
//					application::dumpData ($rowData);
					#!# This assumes the key is the first ...
					array_shift ($rowData);
					$values[$key]  = ($showKey ? "{$key}: " : '');
					$useFields = $rowData;
					if ($showFields) {
						require_once ('application.php');
						$useFields = application::arrayFields ($rowData, $showFields);	// Filters down to the $showFields fields only
					}
					$set = array_values ($useFields);
					$values[$key] .= ($firstOnly ? $set[0] : implode (' - ', $set));
				}
			}
		}
		
		# Return the field name and the lookup values
		return array ($fieldname, $values, $targetDatabase, $targetTableMoniker);
	}
	
	
	# Function to convert joins
	public static function convertJoin ($fieldname, $simpleJoin = false /* or array(currentDatabase,currentTable,array(tables)) */)
	{
		# Simple join mode, e.g. targetId joins to database=$simpleJoin[0],table=target, and the field is fixed as 'id'
		if ($simpleJoin) {
			if (preg_match ('/^([a-zA-Z0-9]+)Id$/', $fieldname, $matches)) {
				list ($currentDatabase, $currentTable, $tables) = $simpleJoin;
				
				# Determine the target table
				switch (true) {
					case ($matches[1] == 'parent'):	// Special-case: if field is 'parentId' then treat as self-join to current table
						$table = $currentTable;
						break;
					case (in_array ($matches[1] . 's', $tables)):	// Simple pluraliser, e.g. for a field 'caseId' look for a table 'cases'; if not present, it will assume 'case'
						$table = $matches[1] . 's';
						break;
					default:
						$table = $matches[1];
						break;
				}
				
				# Return the result
				return array (
					'field' => 'id',	// Fixed - nothing to do with the supplied fieldname ending 'Id'
					'database' => $currentDatabase,
					'table' => $table,
				);
			}
			
		# Otherwise use the fieldname__JOIN__table__database__reserved format
		} else {
			if (preg_match ('/^([a-zA-Z0-9]+)__JOIN__([a-zA-Z0-9]+)__([-_a-zA-Z0-9]+)__reserved$/', $fieldname, $matches)) {
				return array (
					'field' => $matches[1],
					'database' => $matches[2],
					'table' => $matches[3],
				);
			}
		}
		
		# Otherwise return false;
		return false;
	}
	
	
	# Function to substitute lookup values for their names
	public function substituteJoinedData ($dataset, $database, $table /* for targetId fieldname format, or false to use older format, i.e. fieldname__JOIN__databasename__tablename__reserved */, $targetField = true /* i.e. take id and next field; or set named field, e.g. 'name' */)
	{
		# If no data, return the value unchanged
		if (!$dataset) {return $dataset;}
		
		# Determine whether to use the simple join method, and if so assemble the simpleJoin parameter
		$simpleJoin = false;
		if ($table) {
			$tables = $this->getTables ($database);
			$simpleJoin = array ($database, $table, $tables);
		}
		
		# Get the fields in the current dataset
		$fields = array_keys (reset ($dataset));
		
		# Determine which fields are lookups
		$lookupFields = array ();
		foreach ($fields as $field) {
			if ($matches = self::convertJoin ($field, $simpleJoin)) {
				$lookupFields[$field] = $matches['table'];
			}
		}
		
		# Take no further action if no fields are lookups
		if (!$lookupFields) {return $dataset;}
		
		# Get the values in use for each of the lookup fields in the data
		$lookupValues = array ();
		foreach ($lookupFields as $field => $table) {
			foreach ($dataset as $key => $record) {
				$lookupValues[$field][] = $record[$field];
			}
			$lookupValues[$field] = array_unique ($lookupValues[$field]);
		}
		
		# If required, determine the target field which contains the looked-up data
		$targetFields = array ();
		if ($targetField === true) {
			foreach ($lookupFields as $field => $table) {
				$fields = $this->getFieldNames ($database, $table);
				$targetFields[$field] = $fields[1];	// 2nd field, i.e. the one after the key
			}
		}
		
		# Lookup the values
		$lookupResults = array ();
		foreach ($lookupValues as $field => $values) {
			$targetField = ($targetFields ? $targetFields[$field] : $targetField);
			$lookupResults[$field] = $this->selectPairs ($database, $lookupFields[$field], array ('id' => $values), array ('id', $targetField));
		}
		
		# Substitute in the values, retaining the originals where no lookup exists
		foreach ($dataset as $key => $record) {
			foreach ($lookupResults as $field => $lookups) {
				if (array_key_exists ($record[$field], $lookups)) {
					$lookedUpValue = $record[$field];
					$dataset[$key][$field] = $lookups[$lookedUpValue];
				}
			}
		}
		
		# Return the amended dataset
		return $dataset;
	}
	
	
	# Function to log a change
	#!# Ideally have some way to throw an error if the logfile is not writable
	public function logChange ($result)
	{
		# End if logging disabled
		if (!$this->logFile) {return false;}
		
		# Get the query
		$query = $this->getQuery ();
		
		# End if the file is not writable, or the containing directory is not if the file does not exist
		if (file_exists ($this->logFile)) {
			if (!is_writable ($this->logFile)) {return false;}
		} else {
			$directory = dirname ($this->logFile);
			if (!is_writable ($directory)) {return false;}
		}
		
		# Create the log entry
		$logEntry = '/* ' . ($result ? 'Success' : 'Failure') . ' ' . date ('Y-m-d H:i:s') . ' by ' . $this->userForLogging . ' */ ' . str_replace ("\r\n", '\\r\\n', $query);
		
		# Log the change
		file_put_contents ($this->logFile, $logEntry, FILE_APPEND);
	}
	
	
	# Function to notify the admin of a connection error
	public function reportError ($administratorEmail, $applicationName, $filename = false, $errorMessage = 'A database connection could not be established.')
	{
		# Tell the user
		$html = "\n<p class=\"warning\">Error: This facility is temporarily unavailable. Please check back shortly. The administrator has been notified of this problem.</p>";
		
		# Determine the filename to use
		if (!$filename) {
			$filename = getcwd () . '/' . 'errornotifiedflagfile';
		}
		
		# If there is not a flag file, write one, then report the error by e-mail
		if (!file_exists ($filename)) {
			
			# Attempt to write the notification file
			$directory = dirname ($filename);
			if (is_writable ($directory)) {
				umask (002);
				file_put_contents ($filename, date ('r'));
				$errorMessage .= "\n\nWhen the error has been corrected, you must delete the error notification flag file at\n{$filename}";
			} else {
				$errorMessage .= "\n\nAdditionally, an errornotifiedflagfile could not be written, so further e-mails like this will continue.";
			}
			
			# Add the URL
			require_once ('application.php');
			$errorMessage .= "\n\n\n---\nGenerated at URL: {$_SERVER['_PAGE_URL']}";
			
			# Mail the admin
			$mailheaders = "From: {$applicationName} <" . $administratorEmail . ">\n";
			application::utf8Mail ($administratorEmail, 'Data access error: ' . $applicationName, wordwrap ($errorMessage), $mailheaders);
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Accessor function to get the query
	public function getQuery ($showRawPreparedQuery = false)
	{
		# Return the direct query if emulation of what the prepared statement is not required
		if ($showRawPreparedQuery) {
			return $this->query;
		}
		
		# If there are no query values, return the prepared statement
		if (!$this->queryValues) {
			return $this->query;
		}
		
		# Determine whether the query uses named parameters (see http://www.php.net/pdo.prepared-statements ) rather than ?
		$usingNamedParameters = (!substr_count ($this->query, '?'));
		
		# Add colons to each (where necessary) and, where necessary, quote the values, dealing with special cases like NULL and NOW()
		$values = array ();
		foreach ($this->queryValues as $key => $value) {
			if ($usingNamedParameters) {
				$key = ':' . $key;
			}
			switch (true) {
				case ctype_digit ($value):
					$values[$key] = $value;
					break;
				case is_null ($value):
					$values[$key] = 'NULL';
					break;
				case $value == 'NOW()':
					$values[$key] = 'NOW()';
					break;
				default:
					$values[$key] = $this->quote ($value);
			}
		}
		
		# Do replacement
		if ($usingNamedParameters) {
			krsort ($values);	// Sort by key reversed, so that longer key names come first to avoid overlapping replacements
			$query = strtr ($this->query, $values);
		} else {
			$query = $this->query;
			foreach ($values as $value) {
				$query = preg_replace ('/\?/', str_replace ('\\', '\\\\', $value), $query, 1);	// Do replacement of each ? in order, using the limit=1 technique as per http://stackoverflow.com/questions/4863863 ; the str_replace must be used to replace a literal backslash \ to \\ in the replacement string
			}
		}
		
		# Return the query
		return $query;
	}
	
	
	# Function to do sort trimming of a field name, to be put in an ORDER BY clause
	public function trimSql ($fieldname)
	{
		return "TRIM( LEADING '{' FROM TRIM( LEADING '}' FROM TRIM( LEADING '(' FROM TRIM( LEADING '[' FROM TRIM( LEADING '\"' FROM TRIM( LEADING \"'\" FROM TRIM( LEADING '@' FROM TRIM( LEADING 'a ' FROM TRIM( LEADING 'an ' FROM TRIM( LEADING 'the ' FROM LOWER( `{$fieldname}` ) ) ) ) ) ) ) ) ) ) )";
	}
	
	
	# Function to execute SQL into MySQL
	public function runSql ($settings, $input, $isFile = true)
	{
		# Determine the input
		if ($isFile) {
			$input = "< \"{$input}\"";
		} else {
			// $input = "-e \"{$input}\"";
			$input = '-e "' . str_replace ('"', '\\"', $input) . '"';
		}
		
		# Compile the command
		$command = "mysql --max_allowed_packet=1000M --local-infile=1 -h {$settings['hostname']} -u {$settings['username']} --password={$settings['password']} {$settings['database']} {$input}";
		
		# Execute the command
		$output = shell_exec ($command);
		
		# Return the output
		return $output;
	}
}

?>