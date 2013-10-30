<?php

## Stub launching file for BOB instances ##


# Load the settings file
require_once (dirname (__FILE__) . '/../config.php');

# BOB wrapper config items
$configBob['dbHostname'] = $config['dbHostname'];
$configBob['dbDatabase'] = $config['dbDatabase'];
$configBob['dbDatabaseStaging'] = $config['dbDatabaseStaging'];
$configBob['dbUsername'] = $config['dbUsername'];
$configBob['dbSetupUsername'] = $config['dbSetupUsername'];
$configBob['dbPassword'] = $config['dbPassword'];
$configBob['voterReceiptDisableable'] = $config['voterReceiptDisableable'];
$configBob['countingMethod'] = $config['countingMethod'];
$configBob['countingInstallation'] = $config['countingInstallation'];
$configBob['disableListWhoVoted'] = $config['disableListWhoVoted'];

# Fix instances table location
$configBob['dbConfigTable'] = 'instances';

# Fix the header and footer location
$configBob['headerLocation'] = '/style/header.html';
$configBob['footerLocation'] = '/style/footer.html';

# Fix the CSV count files directory
$configBob['additionalVotesCsvDirectory'] = dirname (__FILE__) . '/additionalvotescsv/';

# Get the unique name for this ballot from the query string, then remove it so that BOB gets ?admin rather than ?id=webmaster-08-09-supportteam&admin or ?id=webmaster-08-09-supportteam
if (isSet ($_GET['id'])) {
	$configBob['id'] = $_GET['id'];
	$_SERVER['QUERY_STRING'] = str_replace ('id=' . $_GET['id'] . '&', '', $_SERVER['QUERY_STRING']);
	$_SERVER['QUERY_STRING'] = str_replace ('id=' . $_GET['id'], '', $_SERVER['QUERY_STRING']);
} else {
	$configBob['id'] = false;  // Which will result in a 404 in the application itself
}

# Load and run the BOB class
#!# Hard-coded path
require_once ('../../bob/BOB.php');
new BOB ($configBob);

?>
