<?php

# Stub launching file for the ingest side of the BOB GUI


# Load the settings
require_once (dirname (__FILE__) . '/../config.php');

# Load and run the BOB GUI with the specified settings
require_once (dirname (__FILE__) . '/bobguiIngest.php');
new bobguiIngest ($configIngest);

?>
