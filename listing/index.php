<?php

# Stub launching file for the listing side of the BOB GUI


# Load the settings file
require_once (dirname (__FILE__) . '/../config.php');

# Listing config items
$configListing['username'] = $config['listingUsername'];
$configListing['password'] = $config['listingPassword'];
$configListing['installerUsername'] = $config['installerUsername'];
$configListing['installerPassword'] = $config['installerPassword'];
$configListing['administratorEmail'] = $config['administratorEmail'];
$configListing['organisationName'] = $config['organisationName'];
$configListing['mailDomain'] = $config['mailDomain'];
$configListing['controlPanelUrl'] = $config['controlPanelUrl'];
$configListing['controlPanelOnlyUsers'] = $config['controlPanelOnlyUsers'];
$configListing['controlPanelLinkDirectly'] = $config['controlPanelLinkDirectly'];
$configListing['welcomeMessageHtml'] = $config['listingWelcomeMessageHtml'];
$configListing['singleOrganisationMode'] = $config['singleOrganisationMode'];

# Load and run the BOB GUI with the specified settings
require_once (dirname (__FILE__) . '/bobguiListing.php');
new bobguiListing ($configListing);

?>
