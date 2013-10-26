<?php

# Stub launching file for the control panel side of the BOB GUI


# Load the settings
require_once (dirname (__FILE__) . '/../config.php');

# Load and run the BOB GUI with the specified settings
require_once (dirname (__FILE__) . '/bobguiAdminister.php');
new bobguiAdminister ($configControlpanel);

?>
