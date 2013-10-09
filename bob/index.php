<?php

## Stub launching file for BOB instances ##


# Deny direct running of this stub file, i.e. deny /bobstub.php?id=webmaster-09-10-testeleven
if (substr ($_SERVER['REQUEST_URI'], 0, strlen ($_SERVER['SCRIPT_NAME'])) == $_SERVER['SCRIPT_NAME']) {
	$_GET['action'] = 'page404';
	#!# Hard-coded path
	require_once ('./index.php');
	return false;
}

# Get the unique name for this ballot from the query string, then remove it so that BOB gets ?admin rather than ?id=webmaster-08-09-supportteam&admin or ?id=webmaster-08-09-supportteam
if (isSet ($_GET['id'])) {
	$config['id'] = $_GET['id'];
	$_SERVER['QUERY_STRING'] = str_replace ('id=' . $_GET['id'] . '&', '', $_SERVER['QUERY_STRING']);
	$_SERVER['QUERY_STRING'] = str_replace ('id=' . $_GET['id'], '', $_SERVER['QUERY_STRING']);
} else {
	$config['id'] = false;  // Which will result in a 404 in the application itself
}

# Database connection details
$config['dbHostname'] = 'localhost';
$config['dbDatabase'] = 'votes';
$config['dbDatabaseStaging'] = 'votesstaging';
$config['dbUsername'] = 'testvote';
$config['dbSetupUsername'] = 'testvotesetup';
$config['dbPassword'] = 'your_password_goes_here';

# Database table containing the config which the dbSetupUsername has SELECT rights on
$config['dbConfigTable'] = 'instances';

# Counting installation config; must end /openstv/ (slash-terminated)
$config['countingInstallation'] = '%documentroot/../openstv/openstv/';

# Location in the URL space of optional header and footer file; must start with /
$config['headerLocation'] = '/style/header.html';
$config['footerLocation'] = '/style/footer.html';


## End of config; now run the system ##

# Load and run the BOB class
#!# Hard-coded path
require_once ('../../bob/BOB.php');
new BOB ($config);

?>
