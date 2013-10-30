<?php

# Stub launching file for the ingest side of the BOB GUI


# Load the settings file
require_once (dirname (__FILE__) . '/../config.php');

# Ingest config items
$configIngest['databaseStaging']= $config['databaseStaging'];
$configIngest['databaseLive'] = $config['databaseLive'];
$configIngest['username'] = $config['ingestUsername'];
$configIngest['password'] = $config['ingestPassword'];
$configIngest['administratorEmail'] = $config['administratorEmail'];
$configIngest['organisationName'] = $config['organisationName'];
$configIngest['instanceDataUrl'] = $config['instanceDataUrl'];
$configIngest['instanceDataApiKey'] = $config['instanceDataApiKey'];
$configIngest['liveServerUrl'] = $config['liveServerUrl'];
$configIngest['smsRecipient'] = $config['smsRecipient'];
$configIngest['smsApiKey'] = $config['smsApiKey'];

# Load and run the BOB GUI with the specified settings
require_once (dirname (__FILE__) . '/bobguiIngest.php');
new bobguiIngest ($configIngest);

?>
