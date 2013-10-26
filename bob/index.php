<?php

## Stub launching file for BOB instances ##


# Load the settings
require_once (dirname (__FILE__) . '/../config.php');

# Fix the header and footer location
$configBob['headerLocation'] = '/style/header.html';
$configBob['footerLocation'] = '/style/footer.html';

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
