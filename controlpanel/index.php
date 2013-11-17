<?php

# Stub launching file for the control panel side of the BOB GUI


# Load the settings file
require_once (dirname (__FILE__) . '/../config.php');

# Control panel config items
$configControlpanel['enabled'] = $config['enabled'];
$configControlpanel['username'] = $config['controlpanelUsername'];
$configControlpanel['password'] = $config['controlpanelPassword'];
$configControlpanel['installerUsername'] = $config['installerUsername'];
$configControlpanel['installerPassword'] = $config['installerPassword'];
$configControlpanel['administratorEmail'] = $config['administratorEmail'];
$configControlpanel['organisationName'] = $config['organisationName'];
$configControlpanel['mailDomain'] = $config['mailDomain'];
$configControlpanel['emailTech'] = $config['emailTech'];
$configControlpanel['emailReturningOfficerReceipts'] = $config['emailReturningOfficerReceipts'];
$configControlpanel['liveServerUrl'] = $config['liveServerUrl'];
$configControlpanel['apiKey'] = $config['apiKey'];
$configControlpanel['disableListWhoVoted'] = $config['disableListWhoVoted'];
$configControlpanel['countingMethod'] = $config['countingMethod'];
$configControlpanel['maximumOpeningDays'] = $config['maximumOpeningDays'];
$configControlpanel['disableSurnameForenameRequirement'] = $config['disableSurnameForenameRequirement'];
$configControlpanel['disableRonAvailability'] = $config['disableRonAvailability'];
$configControlpanel['singleOrganisationMode'] = $config['singleOrganisationMode'];

# Load and run the BOB GUI with the specified settings
require_once (dirname (__FILE__) . '/bobguiAdminister.php');
new bobguiAdminister ($configControlpanel);

?>
