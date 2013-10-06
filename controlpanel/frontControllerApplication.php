<?php


#!# Add flag to add an 'admins can create users' flag option


# Front Controller pattern application
# Version 1.6.10
class frontControllerApplication
{
 	# Define available actions; these should be extended by adding definitions in an overriden assignActions ()
	var $actions = array ();
	var $globalActions = array (
		'home' => array (
			'description' => false,
			'url' => '',
			'tab' => 'Home',
		),
		'help' => array (
			'description' => 'Help and documentation',
			'url' => 'help.html',
			'tab' => 'Help',
		),
		'page404' => array (
			'description' => 'Error 404: page not found',
		),
		'profile' => array (
			'description' => 'Update your profile',
			'url' => 'profile/',
			'tab' => '<img src="/images/icons/user.png" alt="" class="icon" /> My profile',
			'authentication' => true,
		),
		'feedback' => array (
			'description' => 'Feedback/contact form',
			'url' => 'feedback.html',
			'tab' => 'Feedback',
		),
		'editing' => array (
			'description' => false,
			'url' => 'data/',
			'tab' => '<img src="/images/icons/pencil.png" alt="" class="icon" /> Data editing',
			'administrator' => true,
		),
		'admin' => array (
			'description' => 'Administrative options for authorised administrators',
			'url' => 'admin.html',
			'tab' => 'Admin',
			'administrator' => true,
		),
		'administrators' => array (
			'description' => 'Add/remove/list administrators',
			'url' => 'administrators.html',
			'administrator' => true,
			'parent' => 'admin',
			'subtab' => 'Administrators',
			'restrictedAdministrator' => true,
		),
		'history' => array (
			'description' => 'History of changes made',
			'url' => 'history.html',
			'administrator' => true,
			'parent' => 'admin',
			'subtab' => 'History',
			'restrictedAdministrator' => true,
		),
		'settings' => array (
			'description' => 'Settings',
			'url' => 'settings.html',
			'administrator' => true,
			'parent' => 'admin',
			'subtab' => 'Settings',
		),
		'login' => array (
			'description' => 'Login',
			'url' => 'login.html',
			'usetab' => 'home',
		),
		'loginexternal' => array (
			'description' => 'Friends login',
			'url' => 'loginexternal.html',
			'usetab' => 'home',
		),
		'logoutexternal' => array (
			'description' => 'Friends logout',
			'url' => 'logoutexternal.html',
			'usetab' => 'home',
		),
		'logininternal' => array (
			'description' => 'Login',
			'url' => 'logininternal.html',
			'usetab' => 'home',
		),
		'logoutinternal' => array (
			'description' => 'Logout',
			'url' => 'logoutinternal.html',
			'usetab' => 'home',
		),
		'register' => array (
			'description' => 'Create a new account',
			'url' => 'register.html',
			'usetab' => 'home',
		),
		'resetpassword' => array (
			'description' => 'Reset a forgotten password',
			'url' => 'resetpassword.html',
			'usetab' => 'home',
		),
		'loggedout' => array (
			'description' => 'Logged out',
			'url' => 'loggedout.html',
			'usetab' => 'home',
		),
		'data' => array (	// Used for e.g. AJAX calls, etc.
			'description' => 'Data point',
			'url' => 'data.html',
			'export' => true,
		),
		
	);
	
	# Define defaults; these can be extended by adding definitions in a defaults () method
	var $defaults = array ();
	var $globalDefaults = array ();
	
	# User status (an optional way of adding (...) after the username in the login corner
	private $userStatus = false;
	
	# Internal auth
	var $internalAuthClass = NULL;
	
	# Tab forcing
	var $tabForced = false;
	
	
	# Constructor
	function __construct ($settings = array (), $disableAutoGui = false)
	{
		# Load required libraries
		require_once ('application.php');
		
		# Define the location of the stub launching file and the image store
		$this->baseUrl = application::getBaseUrl ();
		$this->imageStoreRoot = $_SERVER['DOCUMENT_ROOT'] . $this->baseUrl . '/images/';
		
		# Obtain the defaults
		$this->defaults = $this->assignDefaults ($settings);
		
		# Define an array of errors
		#!# Move application::throwError() into this class as it shouldn't be in the general application class
		$this->applicationErrors = array (
			0 => 'This facility is temporarily unavailable. Please check back shortly.',
			1 => 'The webserver was unable to access user authorisation credentials, so we regret this facility is unavailable at this time.',
			2 => 'There was a problem initialising the database structure on first-run. Possibly the administrator/root password was wrong.',
			3 => 'The server software does not support this application.',
		);
		
		# Function to merge the arguments; note that $errors returns the errors by reference and not as a result from the method
		#!# Ideally the start and end div would surround these items before $this->action is determined, but that would break external type handling
		if (!$this->settings = application::assignArguments ($errors, $settings, $this->defaults, get_class ($this), NULL, $handleErrors = true)) {return false;}
		
		# Deal with table prefixes
		if ($this->settings['tablePrefix']) {
			if ($this->settings['table']) {$this->settings['table'] = $this->settings['tablePrefix'] . $this->settings['table'];}
		}
		
		# If a password setting is supplied, and it appears to be a full path, assume it is a file to be read, not the password string itself
		if ($this->settings['password']) {
			if (substr ($this->settings['password'], 0, 1) == '/') {
				if (is_readable ($this->settings['password'])) {
					$this->settings['password'] = trim (file_get_contents ($this->settings['password']));
				}
			}
		}
		
		# Load camUniData if required
		if ($this->settings['useCamUniLookup']) {
			require_once ('camUniData.php');
		}
		
		# Load the form if required
		if ($this->settings['form']) {
			require_once ($this->settings['form'] === 'dev' ? 'ultimateForm-dev.php' : 'ultimateForm.php');
		}
		
		# Load jQuery if required
		if ($this->settings['jQuery']) {
			echo "\n\n\n<!-- jQuery -->\n" . '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>' . "\n\n";
		}
		
		# Define the data URL, e.g. for use with ultimateForm::<widget>::autocomplete
		$this->dataUrl = "{$_SERVER['_SITE_URL']}{$this->baseUrl}/data.html";
		
		# Define the footer message which goes at the end of any e-mails sent
		$this->footerMessage = "\n\n\n---\nIf you have any questions or need assistance with this facility, please check the help/feedback pages on the website at:\n{$_SERVER['_SITE_URL']}{$this->baseUrl}/";
		
		# Instantiate an application
		$this->application = new application ($this->settings['applicationName'], $this->applicationErrors, $this->settings['administratorEmail']);
		
		# Ensure the version of PHP is supported
		if (version_compare (PHP_VERSION, $this->settings['minimumPhpVersion'], '<')) {
			return $this->application->throwError (3, "PHP version needs to be at least: {$this->settings['minimumPhpVersion']}");
		}
		
		# Get the username if set - the security model hands trust up to Apache/Raven
		$this->user = (isSet ($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : NULL);
		if ($this->settings['internalAuth']) {$this->user = false;}		// The user comes from a database connection so the "new database" call (which supplies $this->user) cannot know the user by this point; this ordering avoids having to create two database connections (one for this call and one for the userAccount class)
		if ($this->settings['user']) {$this->user = $this->settings['user'];}
		
		# If required, make connections to the database server and ensure the tables exist
		if ($this->settings['useDatabase']) {
			require_once ('database.php');
			$this->databaseConnection = new database ($this->settings['hostname'], $this->settings['username'], $this->settings['password'], $this->settings['database'], $this->settings['vendor'], $this->settings['logfile'], $this->user);
			if (!$this->databaseConnection->connection) {
				echo $this->databaseConnection->reportError ($this->settings['administratorEmail'], $this->settings['applicationName']);
				return false;
			}
			
			# Enable strict WHERE mode if required
			$this->databaseConnection->setStrictWhere ($this->settings['databaseStrictWhere']);
			
			# Assign a shortcut for the database table in use
			$this->dataSource = $this->settings['database'] . '.' . $this->settings['table'];
		}
		
		# Assign a shortcut for printing the home URL as http://servername... or www.servername...
		$this->homeUrlVisible = (!substr ($_SERVER['SERVER_NAME'], 0, -3) != 'www' ? 'http://' : '') . $_SERVER['SERVER_NAME'] . $_SERVER['PHP_SELF'];
		
		# Define the user_agent string for downloading pages (some sites may refuse '-' or 'PHP' etc.)
		ini_set ('user_agent', $this->settings['userAgent']);
		
		# Set PHP parameters
		ini_set ('error_reporting', 2047);
		
		# Get the action
		$this->action = (isSet ($_GET['action']) ? $_GET['action'] : 'home');
		
		# If dataDisableAuth is needed, and action=data, do not load internal auth, as we do not want cookie transmission of any sort
		if ($this->settings['dataDisableAuth']) {
			if ($this->action == 'data') {
				$this->settings['internalAuth'] = false;
			}
		}
		
		# Deal with internal auth (often not used)
		$this->userVisibleIdentifier = $this->user;
		if ($this->settings['internalAuth']) {
			$this->loadInternalAuth ();
			$this->user = $this->internalAuthClass->getUserId ();
			$this->userVisibleIdentifier = $this->internalAuthClass->getUserEmail ();
			#!# This appears above the tabs
			echo $this->internalAuthClass->getHtml ();	// Basically will only appear if the user gets logged out for security reasons
		}
		
		# Setup the database if required
		if ($this->settings['useDatabase']) {
			if (method_exists ($this, 'databaseStructure')) {
				if (!$this->databaseSetup ($html)) {
					echo $html;
					return true;
				}
			}
		}
		
		# Get the administrators and determine if the user is an administrator
		#!# Should disable system or force entry if no administrators
		$this->administrators = $this->getAdministrators ();
		$this->userIsAdministrator = $this->userIsAdministrator ();
		
		# Get the settings from the settings table, if required
		$this->addSettingsTableConfig ();
		
		# Get the profile from the profiles table, if required
		$this->profile = $this->getProfile ();
		
		# Determine the administrator privilege level if the database table supports this
		$this->restrictedAdministrator = NULL;
		if ($this->userIsAdministrator) {
			$this->restrictedAdministrator = ((isSet ($this->administrators[$this->user]['privilege']) && ($this->administrators[$this->user]['privilege'] == 'Restricted administrator')) ? true : NULL);
		}
		
		# Additional processing, before actions processing phase, if required
		if (method_exists ($this, 'mainPreActions')) {
			if ($this->mainPreActions () === false) {
				echo $endDiv;
				return false;
			}
		}
		
		# Get the available actions
		$this->actions = $this->assignActions ();
		
/*
		# Remove administrator actions if not an administrator
		if (!$this->userIsAdministrator) {
			foreach ($this->actions as $action => $attributes) {
				if (isSet ($attributes['administrator']) && ($attributes['administrator'])) {
					unset ($this->actions[$action]);
				}
			}
		}
*/
		
		# Assign the item (basically to deal with the common scenario of a function needing an ID parameter
		#!# strtolower is potentially unhelpful here
		$this->item = (isSet ($_GET['item']) ? strtolower ($_GET['item']) : false);
		
		# Compatibility fix to pump a script-supplied argument into the query string
		if (isSet ($_SERVER['argv']) && isSet ($_SERVER['argv'][1]) && preg_match ('/^action=/', $_SERVER['argv'][1])) {
			$this->action = preg_replace ('/^action=/', '', $_SERVER['argv'][1]);
		}
		
		# Determine whether the action is an export type, i.e. has no house style or loaded outside the system
		$this->exportType = ($disableAutoGui || (isSet ($this->actions[$this->action]['export']) && ($this->actions[$this->action]['export'])));
		if ($this->exportType) {$this->settings['div'] = false;}
		
		# Start a div if required to hold the application and define the ending div
		if ($this->settings['div']) {echo "\n<div id=\"{$this->settings['div']}\">\n";}
		$endDiv = ($this->settings['div'] ? "\n</div>" : '');
		
		# Determine if this action has parent action, and if so, what it is
		$this->parentAction = (isSet ($this->actions[$this->action]['parent']) ? $this->actions[$this->action]['parent'] : false);
		
		# Determine if this action is a parent action
		$this->isParentAction = false;
		foreach ($this->actions as $action => $attributes) {
			if (isSet ($attributes['parent']) && $attributes['parent'] == $this->action) {
				$this->isParentAction = true;
			}
		}
		
		# Move feedback and admin to the end
		$functions = array ('editing', 'profile', 'feedback', 'help', 'admin');
		foreach ($functions as $function) {
			if (isSet ($this->actions[$function])) {
				$temp{$function} = $this->actions[$function];
				unset ($this->actions[$function]);
				$this->actions[$function] = $temp{$function};
			}
		}
		
		# Default to home if no valid action selected
		#!# Should show 404 - this will happen if a query string is set up but the action itself isn't registered
		if (!$this->action || !array_key_exists ($this->action, $this->actions)) {
			$this->action = 'home';
		}
		
		# Determine if a header logo (assumed to be relative to baseUrl) is to be used, and if so assemble the HTML
		$headerLogo = false;
		if ($this->settings['headerLogo']) {
			$location = $this->baseUrl . $this->settings['headerLogo'];
			$headerLogoFile = $_SERVER['DOCUMENT_ROOT'] . $location;
			if (is_readable ($headerLogoFile)) {
				list ($width, $height, $type, $attributes) = getimagesize ($headerLogoFile);
				$headerLogo = '<img alt="' . ucfirst (htmlspecialchars ($this->settings['applicationName'])) . '" title="' . ucfirst (htmlspecialchars ($this->settings['applicationName'])) . '" src="' . $location . '" ' . $attributes . ' />';
				$headerLogo = "<a href=\"{$this->baseUrl}/\">" . $headerLogo . '</a>';	// Clickable
			}
		}
		
		# Show the header
		$headerHtml  = "\n" . ($this->settings['h1'] === '' ? '' : ($this->settings['h1'] ? $this->settings['h1'] : '<h1>' . ($headerLogo ? $headerLogo : ucfirst (htmlspecialchars ($this->settings['applicationName']))) . '</h1>'));
		
		# Show the tabs, any subtabs, and the action name
		$selectedTab = ($this->tabForced ? $this->tabForced : $this->action);
		$headerHtml .= $this->showTabs ($selectedTab, $this->settings['tabUlClass']);
		if (method_exists ($this, 'guiSearchBox')) {
			$headerHtml .= "\n<div id=\"cornersearch\">";
			$headerHtml .= $this->guiSearchBox ();
			$headerHtml .= "\n</div>";
		}
		$headerHtml .= $this->showSubTabs ($this->action);
		if (array_key_exists ('description', $this->actions[$this->action]) && $this->actions[$this->action]['description'] && !substr_count ($this->actions[$this->action]['description'], '%') && (!isSet ($this->actions[$this->action]['heading']) || $this->actions[$this->action]['heading'])) {$headerHtml .= "\n<h2>{$this->actions[$this->action]['description']}</h2>";}
		
		# Redirect to the page requested if necessary
		if (!$this->login ()) {
			echo $endDiv;
			return false;
		}
		
		# Show login status
		#!# Should have urlencode also?
		$location = htmlspecialchars ($_SERVER['REQUEST_URI']);	// Note that this will not maintain any #anchor, because the server doesn't see any hash: http://stackoverflow.com/questions/940905
		$this->ravenUser = !substr_count ($this->user, '@');
		$logoutUrl = 'logout.html';
		$loginTextLink = "You are not currently <a href=\"{$this->baseUrl}/login.html?{$location}\">logged in</a>";
		if (!$this->ravenUser) {$logoutUrl = 'logoutexternal.html';}
		if ($this->settings['externalAuth']) {$loginTextLink = "You are not currently logged in using [<a href=\"{$this->baseUrl}/login.html?{$location}\">Raven</a>] or [<a href=\"{$this->baseUrl}/loginexternal.html?{$location}\">Friends login</a>]";}
		if ($this->settings['internalAuth']) {
			$logoutUrl = 'logoutinternal.html';
			$loginTextLink = "You are not currently <a href=\"{$this->baseUrl}/logininternal.html?{$location}\">logged in</a>";
		}
		$headerHtml = '<p class="loggedinas noprint">' . ($this->user ? 'You are logged in as: <strong>' . $this->userVisibleIdentifier . ($this->userIsAdministrator ? ' (ADMIN)' : ($this->userStatus ? " ({$this->userStatus})" : '')) . "</strong> [<a href=\"{$this->baseUrl}/" . $logoutUrl . "\" class=\"logout\">log out</a>]" : $loginTextLink) . '</p>' . $headerHtml;
		
		# Show the header/tabs
		if (!$this->exportType) {
			echo $headerHtml;
		}
		
		# Require authentication for actions that require this
		$authRequiredByAction = (isSet ($this->actions[$this->action]['authentication']) && $this->actions[$this->action]['authentication']);
		if (!$this->user && ($authRequiredByAction || $this->settings['authentication'])) {
			$pagesNeverRequiringAuthentication = array ('register', 'resetpassword', );
			if ($this->settings['dataDisableAuth']) {$pagesNeverRequiringAuthentication[] = 'data';}
			if (!in_array ($this->action, $pagesNeverRequiringAuthentication)) {
				if ($this->settings['authentication']) {echo "\n<p>Welcome.</p>";}
				$loginTextLink = "<a href=\"{$this->baseUrl}/login.html?{$location}\">log in (using Raven)</a>";
				if ($this->settings['externalAuth']) {$loginTextLink = "log in using [<a href=\"{$this->baseUrl}/login.html?{$location}\">Raven</a>] or [<a href=\"{$this->baseUrl}/loginexternal.html?{$location}\">Friends login</a>]";}
				if ($this->settings['internalAuth']) {$loginTextLink = "<a href=\"{$this->baseUrl}/logininternal.html?{$location}\">log in</a> (or <a href=\"{$this->baseUrl}/register.html\">create an account</a>)";}
				echo "\n<p><strong>You need to " . $loginTextLink . " before you can " . ($this->actions[$this->action]['description'] ? htmlspecialchars (strtolower (strip_tags ($this->actions[$this->action]['description']))) : 'use this facility') . '.</strong></p>';
				if (!$this->settings['internalAuth']) {
					echo "\n<p>(<a href=\"{$this->baseUrl}/help.html\">Information on Raven accounts</a> is available.)</p>";
				}
				echo $endDiv;
				return false;
			}
		}
		
		# Check administrator credentials if necessary
		if (isSet ($this->actions[$this->action]['administrator']) && ($this->actions[$this->action]['administrator'])) {
			if ($this->restrictedAdministrator) {
				echo "\n<p><strong>You need to be logged on as a full, unrestricted administrator to access this section.</p>";
				echo $endDiv;
				return false;
			} else {
				if (!$this->userIsAdministrator) {
					echo "\n<p><strong>You need to be logged on as an administrator to access this section.</strong></p>";
					echo $endDiv;
					return false;
				}
			}
		}
		
		# Check restricted administrator credentials if necessary
		if (isSet ($this->actions[$this->action]['restrictedAdministrator']) && ($this->actions[$this->action]['restrictedAdministrator'])) {
			if (!$this->userIsAdministrator && !$this->restrictedAdministrator) {
				echo "\n<p><strong>You need to be logged on as an restricted administrator to access this section.</strong></p>";
				echo $endDiv;
				return false;
			}
		}
		
		# Check specific privilege credentials if necessary
		if (isSet ($this->actions[$this->action]['privilege']) && ($this->actions[$this->action]['privilege'])) {
			$privilegeProperty = $this->actions[$this->action]['privilege'];
			if (!$this->userIsAdministrator && !$this->$privilegeProperty) {	// Assumes that restrictedAdministrator is not enough
				if ($this->user) {
					echo "\n<p><strong>You do not have the required privilege to access this section.</strong></p>";
				} else {
					echo "\n<p><strong>You need to log in before you can access this facility.</strong></p>";
				}
				echo $endDiv;
				return false;
			}
		}
		
		# Get the user's details
		$this->userName = false;
		$this->userEmail = false;
		$this->userPhone = false;
		if ($this->settings['useCamUniLookup']) {
			if ($this->user) {
				if ($person = camUniData::getLookupData ($this->user)) {
					$this->userName = $person['name'];
					$this->userEmail = ($person['email'] ? $person['email'] : $this->user . '@' . $this->settings['emailDomain']);
					$this->userPhone = $person['telephone'];
				}
			}
		}
		
		# Create a shortcut for the current year
		$this->year = date ('Y');
		
		# Additional processing if required
		if (method_exists ($this, 'main')) {
			if ($this->main () === false) {
				echo $endDiv;
				return false;
			}
		}
		
		# Show debugging information if required
		if ($this->settings['debug']) {
			application::dumpData ($_GET);
		}
		
		# Determine the action to use - the 'method' keyword is used to work around name clashes with reserved PHP keywords, e.g. clone.html -> clone -> clonearticle (as 'clone' is a PHP keyword so cannot be used as a method name)
		$this->doAction = (isSet ($this->actions[$this->action]['method']) ? $this->actions[$this->action]['method'] : $this->action);
		
		# Send no-cache headers if required
		if (isSet ($this->actions[$this->action]['nocache']) && $this->actions[$this->action]['nocache']) {
			header ('Cache-Control: no-cache, must-revalidate');	// HTTP/1.1
			header ('Expires: Sat, 26 Jul 1997 05:00:00 GMT');		// Date in the past
		}
		
		# Perform the action
		if (!$disableAutoGui) {
			$this->performAction ($this->doAction, $this->item);
		}
		
		# End with a div if not an export type
		if (!$this->exportType) {
			echo $endDiv;
		}
		
		# Run the shutdown (actually post-action) function if one has been defined
		if (method_exists ($this, 'shutdown')) {
			$this->shutdown ();
		}
	}
	
	
	# Function to perform the action
	function performAction ($action, $item)
	{
		# Perform the action
		$this->$action ($item);
	}
	
	
	# Function to define defaults
	function assignDefaults ($settings)
	{
		# Specify available arguments as defaults or as NULL (to represent a required argument)
		$this->globalDefaults = array (
			'applicationName'								=> application::unCamelCase (get_class ($this)),
			'authentication' 								=> false,		// Whether all pages require authentication
			'dataDisableAuth'								=> false,		// Whether to disable auth on the data function (only relevant when using authentication=true); this can cause logout due to fast cookie transfer
			'externalAuth'									=> false,		// Allow external authentication/authorisation
			'internalAuth'									=> false,		// Allow internal authentication/authorisation
			'internalAuthSalt'								=> '%_salt',	// Salt used for internalAuth; should be set if using internalAuth
			'internalAuthPasswordRequiresLettersAndNumbers'	=> true,	// Whether the internal auth password requires both letters and numbers
			'minimumPasswordLength'							=> 4,			// Minimum password length when using externalAuth
			'h1'											=> false,		// NB an empty string will remove <h1>..</h1> altogether
			'headerLogo'									=> false,		// Image for a header instead of the application name
			'useDatabase'									=> true,
			'credentials'									=> false,	// Filename of credentials file, which results in hostname/username/password/database being ignored
			'hostname'										=> 'localhost',
			'username'										=> NULL,
			'password'										=> NULL,
			#!# Consider a 'passwordFile' option that just contains the password, with other credentials specified normally and the username assumed to be the class name
			'database'										=> NULL,
			'databaseStrictWhere'							=> false,	// Whether automatically-constructed WHERE=... clauses do proper, exact comparisons, so that id="1 x" doesn't match against id value 1 in the database
			'vendor'										=> 'mysql',	// Database vendor
			'jQuery'										=> false,	// Whether to load jQuery
			'peopleDatabase'								=> 'people',
			'table'											=> NULL,
			'administrators'								=> false,	// Administrators table e.g. 'administrators' or 'facility.administrators'
			'settingsTable'									=> 'settings',	// Settings table (must be in the main database) e.g. 'settings' or false to disable (only needed a table of that name is present for a different purpose)
			'settingsTableExplodeTextarea'					=> false,	// Whether to split textarea columns in a settings table into an array of values - true/false, or an array of fieldnames which should have this applied to
			'profiles'										=> false,	// Use of the profiles system (true/false or table, e.g. 'profiles'; true will use 'profiles'
			'tablePrefix'									=> false,	// Prefix which will be added to any table/administrators/settingsTable/profiles settings
			'logfile'										=> './logfile.txt',
			'webmaster'										=> (isSet ($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : NULL),
			'administratorEmail'							=> (isSet ($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : NULL),
			'webmasterContactAddress'						=> (isSet ($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : NULL),
			'feedbackRecipient'								=> (isSet ($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : NULL),
			'useCamUniLookup'								=> true,
			'directoryIndex'								=> 'index.html',					# The directory index, used for local file retrieval
			'userAgent'										=> 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)',	# The user-agent string used for external retrieval
			'emailDomain'									=> 'cam.ac.uk',
			'ravenGetPasswordUrl'							=> 'https://jackdaw.cam.ac.uk/get-raven-password/',
			'ravenResetPasswordUrl'							=> 'https://jackdaw.cam.ac.uk/get-raven-password/',
			'ravenCentralLogoutUrl'							=> 'https://raven.cam.ac.uk/auth/logout.html',
			'page404'										=> 'sitetech/404.html',	// Or false to use internal handler
			'useAdmin'										=> true,
			'revealAdminFunctions'							=> false,	// Whether to show admins-only tabs etc to non-administrators
			'useFeedback'									=> true,
			'helpTab'										=> false,
			'useEditing'									=> false,	// Whether to enable editing as a main tab
			'debug'											=> false,	# Whether to switch on debugging info
			'minimumPhpVersion'								=> '5.1.0',	// PDO supported in 5.1 and above
			'showChanges'									=> 25,		// Number of most recent changes to show in log file
			'user'											=> false,	// Become this user
			'form'											=> true,	// Whether to load ultimateForm
			'opening'										=> false,
			'closing'										=> false,
			'div'											=> false,	// Whether to create a surrounding div with this id
			'crsidRegexp'									=> '^[a-zA-Z][a-zA-Z0-9]{1,7}$',
			'tabUlClass'									=> 'tabs',	// The class used for the ul tag for the tabs
			'tabDivId'										=> false,	// Whether to surround the tabs with a div of this id (or false to disable)
			'umaskPermissions'								=> 0022,	// Permissions for umask calls; the default here is standard Unix
			'mkdirPermissions'								=> 0755,	// Permissions for mkdir calls; the default here is standard Unix
			'chmodPermissions'								=> 0644,	// Permissions for chmod calls; the default here is standard Unix
			'editingPagination'								=> 250,		// Pagination when editing the embedded record editor
		);
		
		# Merge application defaults with the standard application defaults, with preference: constructor settings, application defaults, frontController application defaults
		$defaults = array_merge ($this->globalDefaults, $this->defaults ($settings), $settings);
		
		# Remove database settings if not being used
		if (isSet ($defaults['useDatabase']) && !$defaults['useDatabase']) {
			$defaults['hostname'] = $defaults['username'] = $defaults['password'] = $defaults['database'] = $defaults['table'] = false;
		}
		
		# Deal with credentials behaviour
		if ($defaults['credentials']) {
			
			# Check that the authentication credentials can be read and then read them
			if (is_readable ($defaults['credentials'])) {
				include ($defaults['credentials']);
				
				# Merge the defaults in
				if (isSet ($credentials)) {
					$defaults = array_merge ($defaults, $credentials);
				}
			}
		}
		
		# Return the defaults
		return $defaults;
	}
	
	
	# Setter function to set the userStatus text
	function setUserStatus ($string)
	{
		$this->userStatus = $string;
	}
	
	
	# Skeleton function to get local actions
	function defaults ()
	{
		return $this->defaults;
	}
	
	
	# Function to define defaults
	function assignActions ()
	{
		# Merge application actions with the standard application actions
		if (method_exists ($this, 'actions')) {
			$localActions = $this->actions ();
			$actions = $this->globalActions;	// This is loaded after localActions, so that localActions can amend the global actions directly if wanted
			$actions = array_merge ($actions, $localActions);
		} else {
			$actions = $this->globalActions;
		}
		
		# Remove admin/feedback/editing/settings if required
		#!# The admin system should just be automatically enabled if the table is present, like settings is
		if (!$this->settings['useAdmin']) {unset ($actions['admin']);}
		if (!$this->settings['useFeedback']) {unset ($actions['feedback']);}
		if (!$this->settings['useEditing']) {unset ($actions['editing']);}
		if (!$this->enableProfileTab) {unset ($actions['profile']);}
		if (!$this->enableSettingsSubtab) {unset ($actions['settings']);}
		
		# Remove tabs if necessary
		if (!$this->settings['helpTab']) {unset ($actions['help']['tab']);}
		
		# Remove external login if necessary
		if (!$this->settings['externalAuth']) {
			unset ($actions['loginexternal']);
			unset ($actions['logoutexternal']);
		}
		
		# If using internal login, remove the standard login/logout
		if ($this->settings['internalAuth']) {
			unset ($actions['login']);
			unset ($actions['logout']);
		} else {
			
			# If not using internal login, remove the internal login functions
			unset ($actions['logininternal']);
			unset ($actions['logoutinternal']);
			unset ($actions['register']);
			unset ($actions['reset']);
		}
		
		# Return the actions
		return $actions;
	}
	
	
	# Skeleton function to define locally-defined actions; normally overridden
	function actions ()
	{
		return $this->actions;
	}
	
	
	# Function to show tabs of the actions
	function showTabs ($current, $class = 'tabs')
	{
		# Switch tab context
		if (isSet ($this->actions[$current]['usetab'])) {
			$current = $this->actions[$current]['usetab'];
		}
		
		# Create the tabs
		foreach ($this->actions as $action => $attributes) {
			
			# Skip if it's an admin function and admin functions should be hidden
			if (isSet ($attributes['administrator']) && ($attributes['administrator'])) {
				if (!$this->userIsAdministrator) {
					if (!$this->settings['revealAdminFunctions']) {
						continue;
					}
				}
			}
			
			# Skip if it's a restricted admin function and admin functions should be hidden
			if (isSet ($attributes['restrictedAdministrator']) && ($attributes['restrictedAdministrator'])) {
				if (!$this->userIsAdministrator && !$this->restrictedAdministrator) {
					if (!$this->settings['revealAdminFunctions']) {
						continue;
					}
				}
			}
			
			# Skip if the user doesn't have a specifically-defined privilege
			if (isSet ($attributes['privilege']) && ($attributes['privilege'])) {
				$privilegeProperty = $attributes['privilege'];
				if (!$this->userIsAdministrator && !$this->$privilegeProperty) {	// Assumes that restrictedAdministrator is not enough
					continue;
				}
			}
			
			# Disable (remove) an action if required; this is basically a convenience flag to avoid having to do unset() after an array definition
			if (isSet ($attributes['enableIf'])) {
				if (!$attributes['enableIf']) {
					unset ($this->actions[$action]);
					continue;
				}
			}
			
			# Skip if there is no tab attribute
			if (!isSet ($attributes['tab'])) {continue;}
			
			# Determine if the tab should be marked current (i.e. current page is in this section
			$isCurrent = (($action == $current) || ($action == $this->parentAction));
			
			# Make up the URL if not supplied
			if (!isSet ($attributes['url'])) {$this->actions[$action]['url'] = "{$action}.html";}
			
			# Assemble the URL, adding the base URL in the usual case of not being an absolute URL
			$url = ((substr ($this->actions[$action]['url'], 0, 1) == '/') ? '' : $this->baseUrl . '/') . $this->actions[$action]['url'];
			
			# Add the tab
			$tabs[$action]  = "<li class=\"{$action}" . ($isCurrent ? ' selected' : '') . "\">";
			if ($this->actions[$action]['url'] !== false) {
				$tabs[$action] .= "<a href=\"{$url}\"" . (array_key_exists ('description', $this->actions[$this->action]) ? ' title="' . trim (strip_tags ($attributes['description'])) . '"' : '') . (isSet ($attributes['linkId']) ? " id=\"{$attributes['linkId']}\"" : '') . ">";
			}
			if (isSet ($this->actions[$action]['icon'])) {
				$tabs[$action] .= $this->icon ($this->actions[$action]['icon']);
			}
			$tabs[$action] .= $attributes['tab'];
			if ($this->actions[$action]['url'] !== false) {
				$tabs[$action] .= '</a>';
			}
			$tabs[$action] .= '</li>';
		}
		
		# Compile the HTML
		$html = "\n\n" . "<ul class=\"{$class}\">" . "\n\t" . implode ("\n\t", $tabs) . "\n</ul>\n";
		
		# Add on a surrounding div if required
		if ($this->settings['tabDivId']) {
			$html = "\n" . "<div id=\"{$this->settings['tabDivId']}\">" . "\n" . $html . "\n</div>";
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to set up the database
	private function databaseSetup (&$html)
	{
		# Get the tables, or end if already present
		if ($tables = $this->databaseConnection->getTables ($this->settings['database'])) {return true;}
		
		# End if on the login page
//		if ($this->action == 'login') {return true;}
		
		# If using internalAuth, this has to be temporarily switched to HTTP auth, to avoid the chicken-and-egg situation of not having an account to set up the tables, but there not being a user table
//		if ($this->settings['internalAuth']) {$this->settings['internalAuth'] = false;}
		
		# Start the HTML
		$html  = "\n<h2>Set up database</h2>";
		
		# Ensure the user is logged in

//		$location = htmlspecialchars ($_SERVER['REQUEST_URI']);	// Note that this will not maintain any #anchor, because the server doesn't see any hash: http://stackoverflow.com/questions/940905
//		$loginTextLink = "You are not currently logged in</a>";
		$html .= "\n<p>The database is not yet set up. The site administrator needs to " . /* ($this->user ? */ "enter the database system password below." /* : "<a href=\"{$this->baseUrl}/login.html?{$location}\">log in</a> first.") */ . '</p>';
//		if (!$this->user) {return false;}
		
		# Request the root database credentials
		$form = new form (array (
			'formCompleteText' => false,
			'autofocus' => true,
		));
		$form->password (array (
			'name'			=> 'password',
			'title'			=> 'Database root password',
			'required'		=> true,
		));
		if ($unfinalisedData = $form->getUnfinalisedData ()) {
			if ($unfinalisedData['password']) {
				$rootDatabaseConnection = new database ($this->settings['hostname'], 'root', $unfinalisedData['password'], $this->settings['database'], $this->settings['vendor'], $this->settings['logfile'], $this->user);
				if (!$rootDatabaseConnection->connection) {
					$form->registerProblem ('wrong', "Could not connect using that password for " . htmlspecialchars ($this->settings['hostname']), 'password');
				}
			}
		}
		if (!$result = $form->process ($html)) {return false;}
		
		# Get the database structure
		$sql = $this->databaseStructure ();
		
		# Attach internalAuth structure if required
		if ($this->settings['internalAuth']) {
			$sql .= $this->internalAuthClass->databaseStructure ();
		}
		
		# Execute the SQL
		$result = $rootDatabaseConnection->query ($sql);
		
		# Show failure error message if something went wrong
		if (!$result) {
			$html  = "\n<p>The process did not complete. You may need to set this up manually. The database error was:</p>";
			$databaseError = $rootDatabaseConnection->error ();
			$html .= "\n<p><pre>" . wordwrap (htmlspecialchars ($databaseError[2])) . '</pre></p>';
			return false;
		}
		
		# Redirect
		$redirectTo = $_SERVER['_SITE_URL'] . $this->baseUrl . ($this->settings['internalAuth'] ? '/register.html' : '/');	// Sadly, these have to be hard-coded as the action loading phase hasn't yet happened
		application::sendHeader (302, $redirectTo);
	}
	
	
	# Function to create an icon
	public function icon ($icon)
	{
		# NB The icon location is absolute at present; could add setting in future if required
		return '<img src="' . '/images/icons/' . $icon . '.png" alt="" class="icon" /> ';
	}
	
	
	# Function to show tabs of the actions
	function showSubTabs ($current)
	{
		# End if not in a subtabbed section
		if (!$this->parentAction && !$this->isParentAction) {return;}
		
		# Determine the parent to use
		$parent = ($this->isParentAction ? $current : $this->parentAction);
		
		# Merge in the child actions
		$actions = $this->getChildActions ($parent, true, true);
		
		# Compile the HTML, adding a heading
		$html  = "\n<h4 id=\"tabsheading\">" . (isSet ($this->actions[$parent]['subheading']) ? $this->actions[$parent]['subheading'] : $this->actions[$parent]['description']) . '</h4>';
		$html .= $this->actionsListHtml ($actions, $useDescriptionAsText = false, 'tabs subtabs', $current);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to get the child actions for a function
	function getChildActions ($parent, $includeParent = false, $subtabsOnly = false)
	{
		# End if not in a subtabbed section
		if (!$parent) {return array ();}
		
		# Add the parent action as the first item if necessary
		if ($includeParent) {
			$children[$parent] = $this->actions[$parent];
			$children[$parent]['subtab'] = (isSet ($children[$parent]['tab']) ? $children[$parent]['tab'] . ': home' : 'Home');
		}
		
		# Find actions in the current section that have a subtab requirement
		foreach ($this->actions as $action => $attributes) {
			if (isSet ($attributes['parent']) && ($attributes['parent'] == $parent)) {
				
				# If required, skip if there is no subtab attribute
				if ($subtabsOnly && !isSet ($attributes['subtab'])) {continue;}
				
				# Allocate to the list
				$children[$action] = $this->actions[$action];
			}
		}
		
		# Return the children
		return $children;
	}
	
	
	# Function to determine whether this facility is open
	function facilityIsOpen (&$html, $openingExtraMessage = false, $closingExtraMessage = false)
	{
		# Check that the opening time has passed
		if ($this->settings['opening']) {
			if (time () < strtotime ($this->settings['opening'])) {
				$html .= '<p class="warning">This facility is not yet open. Please return at a later date.</p>' . $openingExtraMessage;
				return false;
			}
		}
		
		# Check that the closing time has passed
		if ($this->settings['closing']) {
			if (time () > strtotime ($this->settings['closing'])) {
				$html .= '<p class="warning">This facility has now closed.</p>' . $closingExtraMessage;
				return false;
			}
		}
		
		# Otherwise return true
		return true;
	}
	
	
	# Function to create an HTML list of actions
	function actionsListHtml ($actions, $useDescriptionAsText = false, $ulClass = false, $current = false)
	{
		# Return an empty string if no actions
		if (!$actions) {return '';}
		
		# Create the tabs
		$items = array ();
		foreach ($actions as $action => $attributes) {
			
			# Skip if's an admin function and admin functions should be hidden
			if (isSet ($attributes['administrator']) && ($attributes['administrator'])) {
				if (!$this->userIsAdministrator) {
					if (!$this->settings['revealAdminFunctions']) {
						continue;
					}
				}
			}
			
			# Make up the URL if not supplied
			if (!isSet ($attributes['url'])) {$attributes['url'] = "{$action}.html";}
			
			# Skip if it's an ID-based article but there is no item
			if (!$this->item && substr_count ($attributes['url'], '%id')) {continue;}
			
			# Determine the text
			$text = ($useDescriptionAsText ? $attributes['description'] : $attributes['subtab']);
			
			# Convert the URL to insert an item number if relevant
			$attributes['url'] = str_replace ('%id', $this->item, $attributes['url']);
			
			# Add an icon if required
			$icon = (isSet ($attributes['icon']) ? $this->icon ($attributes['icon']) : '');
			
			#!# subtab is hard-coded at present
			$items[$action] = "<a href=\"{$this->baseUrl}/{$attributes['url']}\" title=\"{$attributes['description']}\">{$icon}{$text}</a>";
		}
		
		# Compile the HTML
		$html = application::htmlUl ($items, 0, $ulClass, $ignoreEmpty = true, $sanitise = false, $nl2br = false, $liClass = false, $current);
		
		# Return the list
		return $html;
	}
	
	
	# Function to create a portal table showing the sub-applications
	public function applicationTable ()
	{
		# Determine the applications
		$applications = array ();
		foreach ($this->actions as $action => $attributes) {
			if (isSet ($attributes['applicationImage'])) {
				$applications[] = $action;
			}
		}
		
		# Create as a table
		$table = array ();
		foreach ($applications as $application) {
			$link = $this->baseUrl . '/' . $this->actions[$application]['url'];
			$table[$application] = array (
				'image' => "<a href=\"{$link}\"><img src=\"{$this->baseUrl}{$this->actions[$application]['applicationImage']}\" /></a>",
				'text' => "<h2><a href=\"{$link}\">" . $this->actions[$application]['description'] . '</a></h2>' . "\n" . $this->actions[$application]['aboutHtml'],
			);
		}
		
		# Compile the HTML
		$html = application::htmlTable ($table, array (), 'portal largeimages imageborders', $keyAsFirstColumn = false, false, $allowHtml = true, false, $addCellClasses = false, $addRowKeyClasses = false, array (), false, $showHeadings = false);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to get an array of administrators
	function getAdministrators ()
	{
		# Return an empty array if the application does not use a table of administrators
		if (!$this->settings['administrators']) {return array ();}
		
		# If the setting is an array the return that
		if (is_array ($this->settings['administrators'])) {return $this->settings['administrators'];}
		
		# True means assign the default table name 'administrators'
		if ($this->settings['administrators'] === true) {
			$this->settings['administrators'] = 'administrators';
		}
		
		# Add table name prefix if required
		if ($this->settings['tablePrefix']) {
			$this->settings['administrators'] = $this->settings['tablePrefix'] . $this->settings['administrators'];
		}
		
		# Convert table to database.table
		$administrators = $this->settings['administrators'];
		if (!substr_count ($this->settings['administrators'], '.')) {
			$administrators = "{$this->settings['database']}.{$this->settings['administrators']}";
		}
		
		# Get the fieldnames
		$fields = $this->databaseConnection->getFieldnames ($this->settings['database'], $this->settings['administrators']);
		
		# Get the list of administrators
		$query = "SELECT * FROM {$administrators}" . (in_array ('active', $fields) ? " WHERE (active = 'Y' OR active = 'Yes')" : '') . ';';
		if (!$administrators = $this->databaseConnection->getData ($query, $administrators)) {
			return false;
		}
		
		# Allocate their e-mail addresses
		foreach ($administrators as $username => $administrator) {
			$administrators[$username]['email'] = ((isSet ($administrator['email']) && (!empty ($administrator['email']))) ? $administrator['email'] : $username . (((!isSet ($administrator['userType'])) || ($administrator['userType'] != 'External')) ? "@{$this->settings['emailDomain']}" : ''));
		}
		
		# Return the array
		return $administrators;
	}
	
	
	# Function to get settings table config
	private function addSettingsTableConfig ()
	{
		# Assume there is no such table
		$this->enableSettingsSubtab = false;
		
		# End if the application does not have database support
		if (!$this->settings['useDatabase']) {return false;}
		
		# End if the application does not use a database of additional settings
		if (!$this->settings['settingsTable']) {return false;}
		
		# Add table name prefix if required
		if ($this->settings['tablePrefix']) {
			$this->settings['settingsTable'] = $this->settings['tablePrefix'] . $this->settings['settingsTable'];
		}
		
		# Ensure the settings table exists
		$tables = $this->databaseConnection->getTables ($this->settings['database']);
		if (!in_array ($this->settings['settingsTable'], $tables)) {return false;}
		
		# Enable the settings subtab
		$this->enableSettingsSubtab = true;
		
		# Get the settings
		if (!$settingsFromTable = $this->databaseConnection->selectOne ($this->settings['database'], $this->settings['settingsTable'], array ('id' => 1))) {return false;}
		
		# If the setting is a textarea (but not HTML), explode the options into a list
		if ($this->settings['settingsTableExplodeTextarea']) {
			$fieldStructure = $this->databaseConnection->getFields ($this->settings['database'], $this->settings['settingsTable']);
			foreach ($fieldStructure as $fieldname => $field) {
				if (is_array ($this->settings['settingsTableExplodeTextarea']) && !in_array ($fieldname, $this->settings['settingsTableExplodeTextarea'])) {continue;}	// Skip if a list is supplied and the field is not in it
				if ($field['Type'] == 'text') {
					if (!preg_match ('/(html|richtext)/i', $fieldname)) {	// Exclude fields that look like richtext (HTML); this should match the defintion in the dataBinding function in ultimateForm.php, so that the developer can be sure that if a richtext field appears in the settings page, that it won't get exploded
						$settingsFromTable[$fieldname] = preg_split ("/\s*\r?\n\t*\s*/", trim ($settingsFromTable[$fieldname]));
					}
				}
			}
		}
		
		# Merge in the settings, ignoring the id, and overwriting anything currently present
		foreach ($settingsFromTable as $key => $value) {
			if ($key == 'id') {continue;}
			$this->settings[$key] = $value;
		}
	}
	
	
	# Function to get the user profile, if that system is in use
	private function getProfile ()
	{
		# Assume there is no such table
		$this->enableProfileTab = false;
		
		# End if the application does not have database support
		if (!$this->settings['useDatabase']) {return false;}
		
		# End if the application does not use a table of profiles
		if (!$this->settings['profiles']) {return false;}
		
		# If set to boolean true, use the default of 'profiles'
		if ($this->settings['profiles'] === true) {$this->settings['profiles'] = 'profiles';}
		
		# Add table name prefix if required
		if ($this->settings['tablePrefix']) {
			$this->settings['profiles'] = $this->settings['tablePrefix'] . $this->settings['profiles'];
		}
		
		# Ensure the profiles table exists
		$tables = $this->databaseConnection->getTables ($this->settings['database']);
		if (!in_array ($this->settings['profiles'], $tables)) {return false;}
		
		# Enable the profiles subtab
		$this->enableProfileTab = true;
		
		# Get the profile
		if (!$profile = $this->databaseConnection->selectOne ($this->settings['database'], $this->settings['profiles'], array ('id' => $this->user))) {return false;}
		
		# Return the confirmed profile
		return $profile;
	}
	
	
	# Function to determine if the user is an administrator
	function userIsAdministrator ()
	{
		# Return NULL if no user
		if (!$this->userVisibleIdentifier || !$this->administrators) {return NULL;}
		
		# Return boolean whether the user is in the list
		return (array_key_exists ($this->userVisibleIdentifier, $this->administrators));
	}
	
	
	# Login function
	function login ($method = 'login')
	{
		# Ensure there is a username, by forcing a query string with "action=login" in to be redirected to the login method noted
		#!# Throw error 1 if on the login page and no username is provided by the server
		$delimiter = '/';
		if (ini_get ('output_buffering') && preg_match ($delimiter . '^action=' . preg_quote ($method, $delimiter) . $delimiter, $_SERVER['QUERY_STRING'])) {
			
			# For internal login, return whether valid credentials have been supplied, and if not show a form
			if ($this->settings['internalAuth']) {
				$method = 'logininternal';
				if (!$result = $this->logininternal ()) {
					return false;
				}
			}
			
			# Redirect back
			#!# Support output_buffering being off by providing a link
			$location = $this->baseUrl . '/';
			if (substr_count ($_SERVER['QUERY_STRING'], "action={$method}&/")) {
				$location = '/' . str_replace ("action={$method}&/", '', $_SERVER['QUERY_STRING']);
			}
			header ('Location: ' . $_SERVER['_SITE_URL'] . $location);
			return false;
		}
		
		# End
		return true;
	}
	
	
	# Login function
	function loginexternal ()
	{
		# Pass on
		return $this->login (__FUNCTION__);
	}
	
	
	# Logout message
	function logoutexternal ()
	{
		echo "\n" . '<p>To log out, please close all instances of your web browser.</p>';
	}
	
	
	# Login function, only available if internalAuth is enabled
	function logininternal ()
	{
		# Run the validation and return the supplied e-mail
		$this->user = $this->internalAuthClass->login ($showStatus = true);
		
		# Assemble the HTML
		$html  = "\n<h2>" . $this->actions['logininternal']['description'] . '</h2>';
		$html .= $this->internalAuthClass->getHtml ();
		
		# Show the HTML
		echo $html;
		
		# Return the status
		return ($this->user);
	}
	
	
	# Logout message, only available if internalAuth is enabled
	function logoutinternal ()
	{
		# Log out and confirm this status
		$this->internalAuthClass->logout ();
		
		# Assemble the HTML
		$html  = $this->internalAuthClass->getHtml ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Register page
	function register ()
	{
		# Log out and confirm this status
		$this->internalAuthClass->register ();
		
		# Assemble the HTML
		$html  = $this->internalAuthClass->getHtml ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Reset password page
	function resetpassword ()
	{
		# Log out and confirm this status
		$this->internalAuthClass->resetpassword ();
		
		# Assemble the HTML
		$html  = $this->internalAuthClass->getHtml ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Data point
	function data ()
	{
		echo '<p>This URL can be assigned a function data() for transmission of data.</p>';
	}
	
	
	# Logout message
	function loggedout ()
	{
		echo '
		<p>You have logged out of Raven for this site.</p>
		<p>If you have finished browsing, then you should completely exit your web browser. This is the best way to prevent others from accessing your personal information and visiting web sites using your identity. If for any reason you can\'t exit your browser you should first log-out of all other personalized sites that you have accessed and then <a href="' . $this->settings['ravenCentralLogoutUrl'] . '" target="_blank">logout from the central authentication service</a>.</p>';
	}
	
	
	# Function to provide a help page
	function help ()
	{
		# Construct the help text
		$html  = "\n" . '<h3 id="updating">User accounts - Raven authentication</h3>';
		$html .= "\n" . '<p>To make changes, a Raven password is required for security. You can <a href="' . $this->settings['ravenGetPasswordUrl'] . '" target="_blank">obtain your Raven password</a> from the University Computing Service immediately if you do not yet have it.</p>';
		$html .= "\n" . '<p>If you have <strong>forgotten</strong> your Raven password, you will need to <a href="' . $this->settings['ravenResetPasswordUrl'] . '" target="_blank">request a new one</a> from the central University Computing Service.</p>';
		$html .= "\n" . '<h3 id="security">Security</h3>';
		$html .= "\n" . "<p>Various security and auditing mechanisms are in place. " . ($_SERVER['_SERVER_PROTOCOL_TYPE'] == 'http' ? "Submissions are sent using HTTP as the server does not currently have an SSL certificate, although the Raven authentication stage is transmitted using HTTPS." : 'Submissions are encrypted using HTTPS.') . " Please <a href=\"{$this->baseUrl}/feedback.html\">contact us</a> if you have any questions on security.</p>";
		$html .= "\n" . '<p>Attempts to add Javascript or HTML tags to submitted data will fail.</p>';
		$html .= "\n" . '<h3 id="lookup">How do we pre-fill your name in some webforms?</h3>';
		$html .= "\n" . '<p>If you are logged in via Raven, we use the University\'s <a href="http://www.lookup.cam.ac.uk/" target="_blank">lookup service</a> to obtain then pre-fill your name as a time-saving courtesy.</p>';
		$html .= "\n" . '<h3 id="dataprotection">Data protection</h3>';
		$html .= "\n" . '<p>All data is stored in accordance with the Data Protection Act, and data submitted through this system will not be passed on to third parties.</p>';
		$html .= "\n" . '<h3 id="contacts">Any further questions?</h3>';
		$html .= "\n" . "<p>We very much hope you find this new facility user-friendly and self-explanatory. However, if you still have questions, please do not hesitate to <a href=\"{$this->baseUrl}/feedback.html\">contact us</a>.</p>";
		
		# Show the HTML
		echo $html;
	}
	
	
	
	# Function to get the real name of a user using the University's lookup service
	function getName ($user)
	{
		# Attempt to get the data
		if ($this->settings['useCamUniLookup']) {
			if ($userLookupData = camUniData::getLookupData ($user)) {
				return $userLookupData['name'];
			}
		}
		
		# Fall back
		return "user {$user}";
	}
	
	
	# Administrator options
	function admin ()
	{
		# Create the HTML
		$html  = "\n<p>This section contains various functions available to administrators only.</p>";
		
		# Determine the tasks
		$actions = $this->getChildActions (__FUNCTION__, false, false);
		
		# Compile the HTML, adding a heading
		$html = $this->actionsListHtml ($actions, true);
		
		# Show the HTML
		echo $html;
	}
	
	
	# Show recent changes
	function history ()
	{
		# End if there is no log file
		if (!file_exists ($this->settings['logfile'])) {
			echo "\n" . '<p>There is no log file, so changes cannot be listed.</p>';
			return false;
		}
		
		# Get the log file contents
		$changes = array ();
		if ($logfile = file_get_contents ($this->settings['logfile'])) {
			
			# Split into individual changes, with most recent first
			$changes = array_reverse (explode ("\n", trim ($logfile)), true);
		}
		
		# Ensure changes are found
		if (!$changes) {
			echo "\n<p class=\"warning\">There was some problem reading the logfile.</p>";
			return false;
		}
		
		# Loop through each change
		$i = 0;
		$changesHtml = array ();
		foreach ($changes as $index => $change) {
			if (++$i == $this->settings['showChanges']) {break;}
			$delimiter = '!';
			if (preg_match ($delimiter . "/\* (Success|Failure) (.{19}) by ([a-zA-Z0-9]+) \*/ (UPDATE|INSERT INTO) ([^.]+)\.([^ ]+) (.*)" . $delimiter, $change, $parts)) {
				$nameMatch = array ();
				// preg_match ($delimiter . ($parts[4] == 'UPDATE' ? "WHERE id='([a-z]+)';$" : "VALUES \('([a-z]+)',") . $delimiter, trim ($parts[7]), $nameMatch);
				$changesHtml[] = "\n<h3 class=\"spaced\">[" . ($index + 1) . '] ' . ($parts[1] == 'Success' ? 'Successful' : 'Failed') . ' ' . ($parts[4] == 'UPDATE' ? 'update' : 'new submission') . (isSet ($nameMatch[1]) ? " made to <span class=\"warning\"><a href=\"{$this->baseUrl}/{$nameMatch[1]}/\">{$nameMatch[1]}</a></span>" : '') . ' by<br />' . $parts[3] . ' at ' . $parts[2] . ":</h3>\n<p>{$parts[4]} {$parts[5]}.{$parts[6]} " . htmlspecialchars ($parts[7]) . '</p>';
			}
		}
		
		# Start the HTML
		$html  = "\n<div class=\"basicbox\">";
		$html .= "\n<p>There have been <strong>" . count ($changes) . "</strong> updates to the database.<br />Only <strong>the most recent {$this->settings['showChanges']} changes</strong> are shown below.</p>";
		$html .= "\n<p>IMPORTANT NOTE: This does not include changes manually to the database directly (e.g. using PhpMyAdmin) - it only covers changes submitted via the webforms in this system itself.</p>";
		$html .= "\n</div>";
		$html .= "\n" . implode ("\n\n", $changesHtml);
		
		# Show the HTML
		echo $html;
	}
	
	
	# Settings form
	public function settings ($dataBindingSettingsOverrides = array ())
	{
		# Start the HTML
		$html = '';
		
		# Ensure settings are all strings - some may have been exploded
		$settings = $this->settings;
		if ($this->settings['settingsTableExplodeTextarea']) {
			foreach ($settings as $key => $value) {
				if (is_array ($value)) {
					$settings[$key] = implode ("\n", $value);
				}
			}
		}
		
		# Define default dataBinding settings
		$dataBindingSettings = array (
			'database' => $this->settings['database'],
			'table' => $this->settings['settingsTable'],
			'intelligence' => true,
			'data' => $settings,
			'attributes' => array (),
		);
		
		# Merge in any overriding settings
		if ($dataBindingSettingsOverrides) {
			$dataBindingSettings = array_merge ($dataBindingSettings, $dataBindingSettingsOverrides);
		}
		
		# Databind a form
		$form = new form (array (
			'databaseConnection'	=> $this->databaseConnection,
			'reappear' => true,
			'formCompleteText' => false,
			'displayRestrictions' => false,
			'unsavedDataProtection' => true,
			'jQuery' => !$this->settings['jQuery'],	// Do not load if already loaded
		));
		$form->dataBinding ($dataBindingSettings);
		
		# Add getUnfinalised post-processing if such a function is defined in the calling class
		if (method_exists ($this, 'settingsGetUnfinalised')) {
			$this->settingsGetUnfinalised ($form);	// Needs to be received by reference
		}
		
		# Process the form
		if ($result = $form->process ($html)) {
			
			# Add in fixed data
			$result['id'] = 1;
			
			# Insert/update the data
			$this->databaseConnection->insert ($this->settings['database'], $this->settings['settingsTable'], $result, $onDuplicateKeyUpdate = true);
			
			# Confirm success
			$html = "\n<p><img src=\"/images/icons/tick.png\" class=\"icon\" alt=\"\" /> The settings have been updated.</p>" . $html;
		}
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to create a button to update the user's profile
	public function profileUpdateButton ($div = 'graybox')
	{
		# If no user, do not show the button
		if (!$this->user) {return false;}
		
		# If the user has a profile already, not needed
		if ($this->profile) {return false;}
		
		# Assemble the HTML
		$html = "\n<p>Please <a href=\"{$this->baseUrl}/profile/\">" . '<img src="/images/icons/user.png" alt="" class="icon" /> create your profile</a> so that we can personalise this system for you.</p>';
		
		# Surround with a div if required
		if ($div) {
			$html = "\n<br /><br /><div class=\"{$div}\">{$html}</div><br /><br />";
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Profile page
	public function profile ($dataBindingSettingsOverrides = array ())
	{
		# Start the HTML
		$html = '';
		
		# Introductory text
		$html .= "\n<p>Here you can " . ($this->profile ? 'update' : 'create') . ' your profile.</p>';
		
		# Define default dataBinding settings
		$dataBindingSettings = array (
			'database' => $this->settings['database'],
			'table' => $this->settings['profiles'],
			'intelligence' => true,
			'data' => $this->profile,
			'simpleJoin' => true,
			#!# Currently assumes the join table has a name field of name as its visible values
			'lookupFunctionParameters' => array ($showKeys = false, 'name', false, false, $firstOnly = true),
			'attributes' => array (
				'id' => array ('default' => $this->user, 'editable' => false, ),
			),
		);
		
		# Merge in any overriding settings
		if ($dataBindingSettingsOverrides) {
			$dataBindingSettings = array_merge ($dataBindingSettings, $dataBindingSettingsOverrides);
		}
		
		# Databind a form
		$form = new form (array (
			'databaseConnection'	=> $this->databaseConnection,
			'reappear' => true,
			'formCompleteText' => false,
			'nullText' => false,
			'display' => 'paragraphs',
			'div' => 'graybox',
			'displayRestrictions' => false,
		));
		$form->dataBinding ($dataBindingSettings);
		
		# Add additional validation if required
		if (method_exists ($this, 'profileUnfinalisedData')) {
			if ($unfinalisedData = $form->getUnfinalisedData ()) {
				$this->profileUnfinalisedData ($unfinalisedData, $form);	// $form received by reference
			}
		}
		
		# Process the form
		if ($result = $form->process ($html)) {
			
			# Set fixed data
			$result['id'] = $this->user;
			
			# Insert/update the data
			$action = ($this->profile ? 'update' : 'insert');
			$argument4 = ($this->profile ? array ('id' => $this->user) : false);
			$this->databaseConnection->{$action} ($this->settings['database'], $this->settings['profiles'], $result);
			
			# Confirm success
			$html = "\n<p><img src=\"/images/icons/tick.png\" class=\"icon\" alt=\"\" /> The settings have been updated.</p>" . $html;
		}
		
		# Show the HTML
		echo $html;
	}
	
	
	# Feedback form
	function feedback ()
	{
		# Show the form
		echo "<p>We welcome your feedback on this facility. If you have any suggestions, questions or comments - whether positive or negative - we'd like to hear from you. Please use the form below to send us your feedback.</p>";
		
		# Create a new form
		$form = new form (array (
			'displayRestrictions' => false,
			'formCompleteText' => "Many thanks for your input - we'll be in touch shortly if applicable.",
			'antispam'	=> true,
		));
		
		# Widgets
		$form->textarea (array (
			'name'		=> 'message',
			'title'		=> 'Message',
			'required'	=> true,
			'cols'		=> 55,
			'default' 	=> (isSet ($_GET['message']) ? $_GET['message'] : false),
		));
		$form->input (array (
			'name'		=> 'name',
			'title'		=> 'Your name',
			'required'	=> true,
			'default'	=> ($this->settings['useCamUniLookup'] && $this->user && ($userLookupData = camUniData::getLookupData ($this->user)) ? $userLookupData['name'] : ''),
		));
		$form->email (array (
			'name'		=> 'contacts',
			'title'		=> 'E-mail',
			'required'	=> true,
			'default'	=> ($this->userVisibleIdentifier ? $this->userVisibleIdentifier . ($this->settings['internalAuth'] ? '' : '@' . $this->settings['emailDomain']) : ''),	// internalAuth will result in e-mail addresses not usernames
			'editable'	=> (!$this->user),
		));
		
		# Set the processing options
		$form->setOutputEmail ($this->settings['feedbackRecipient'], $this->settings['administratorEmail'], "{$this->settings['applicationName']} contact form", NULL, $replyToField = 'contacts');
		$form->setOutputScreen ();
		
		# Process the form
		$result = $form->process ();
	}
	
	
	# Function to provide cookie-based login internally
	function loadInternalAuth ()
	{
		# Assemble the settings to use
		$internalAuthSettings = array (
			'salt'								=> $this->settings['internalAuthSalt'],
			'baseUrl'							=> $this->baseUrl,
			'loginUrl'							=> '/logininternal.html',
			'logoutUrl'							=> '/logoutinternal.html',
			'database'							=> $this->settings['database'],
			'applicationName'					=> $this->settings['applicationName'],
			'administratorEmail'				=> $this->settings['administratorEmail'],
			'passwordRequiresLettersAndNumbers'	=> $this->settings['internalAuthPasswordRequiresLettersAndNumbers'],
		);
		
		# Load the user account system
		require_once ('userAccount.php');
		$this->internalAuthClass = new userAccount ($internalAuthSettings, $this->databaseConnection);
	}
	
	
	# Function to determine the administrator username field
	private function administratorUsernameField ()
	{
		# Determine the name of the username field
		#!# Use of $this->settings['administrators'] as table name here needs auditing
		$fields = $this->databaseConnection->getFieldnames ($this->settings['database'], $this->settings['administrators']);
		$possibleUsernameFields = array ('username', 'crsid', "username__JOIN__{$this->settings['peopleDatabase']}__people__reserved");
		foreach ($possibleUsernameFields as $field) {
			if (in_array ($field, $fields)) {
				return $field;
			}
		}
		
		# Return the default if not found
		#!# This is not useful, since it should already have been found
		return $possibleUsernameFields[0];
	}
	
	
	# Function to show administrators
	function administrators ($null = NULL, $boxClass = 'graybox', $showFields = array ('active' => 'Active?', 'email' => 'E-mail', 'privilege' => 'privilege', 'name' => 'name', 'forename' => 'forename', 'surname' => 'surname', ))
	{
		# Start the HTML
		$html  = '';
		
		# Get the username field
		$administratorUsernameField = $this->administratorUsernameField ();
		
		# Add an administrator form
		$html .= $this->administratorsAdd ($boxClass, $administratorUsernameField);
		
		# Delete an administrator form
		$html .= $this->administratorsDelete ($boxClass, $administratorUsernameField);
		
		# Show current administrators
		$html .= $this->administratorsShow ($boxClass, $administratorUsernameField, $showFields);
		
		# Show the HTML
		echo $html;
	}
	
	
	# Helper function to add an administrator
	private function administratorsAdd ($boxClass, $usernameField)
	{
		# Start the HTML
		$html  = '';
		
		# Compile the HTML
		if (true) {
			$authSystemName = 'Raven';
			if ($this->settings['internalAuth']) {
				$authSystemName = 'Password';
			}
			$html .= "\n<div class=\"{$boxClass}\">";
			$html .= "\n<h3 id=\"add" . strtolower ($authSystemName) . "\">Add an administrator ({$authSystemName} login)</h3>";
			$form = new form (array (
				'name' => 'add' . strtolower ($authSystemName),
				'submitTo' => '#add' . strtolower ($authSystemName),
				'formCompleteText' => false,
				'div' => false,
				'databaseConnection'	=> $this->databaseConnection,
				'displayRestrictions' => false,
				'requiredFieldIndicator' => false,
			));
			$form->dataBinding (array (
				'database' => $this->settings['database'],
				#!# This could cause problems
				'table' => $this->settings['administrators'],
				'includeOnly' => array ($usernameField, 'forename', 'surname', 'name', 'email', 'privilege'),
				'attributes' => array (
					$usernameField => array ('current' => array_keys ($this->administrators), 'autocomplete' => ($this->settings['useCamUniLookup'] ? camUniData::autocompleteNamesUrlSource () : false), ),
					'email' => array ('type' => 'email', ),
			)));
			
			# Process the form
			if ($result = $form->process ($html)) {
				
				# Add and inform the new administrator
				$html .= $this->processNewAdministrator ($result, $usernameField, $authSystemName);
			}
			$html .= "\n" . '</div>';
		}
		
		# Add an external administrator form, if using the external auth option
		#!# Refactor to use dataBinding by combining with the above code
		if ($this->settings['externalAuth']) {
			$authSystemName = 'Friends';
			$html .= "\n<div class=\"{$boxClass}\">";
			$html .= "\n<h3 id=\"add" . strtolower ($authSystemName) . "\">Add an administrator ({$authSystemName} login)</h3>";
			$form = new form (array (
				'name' => 'add' . strtolower ($authSystemName),
				'submitTo' => '#add' . strtolower ($authSystemName),
				'formCompleteText' => false,
				'div' => false,
				'databaseConnection'	=> $this->databaseConnection,
				'displayRestrictions' => false,
				'requiredFieldIndicator' => false,
			));
			$form->email (array (
				'name'			=> 'email',
				'title'			=> 'E-mail address',
				'required'		=> true,
				'current'		=> array_keys ($this->administrators),
				'description'	=> '(This will be used as the login username)',
			));
			$form->input (array (
				'name'			=> 'forename',
				'title'			=> 'Forename',
				'required'		=> true,
			));
			$form->input (array (
				'name'			=> 'surname',
				'title'			=> 'Surname',
				'required'		=> true,
			));
			$form->password (array (
				'name'			=> 'password',
				'title'			=> 'Password',
				'required'		=> true,
				'generate'		=> true,
				'minlength'		=> 4,
			));
			$form->select (array (
				'name'			=> 'privilege',
				'title'			=> 'Administrator level',
				'values'		=> array ('Administrator', 'Restricted administrator'),
				'default'		=> 'Administrator',
				'required'		=> true,
			));
			if ($result = $form->process ($html)) {
				
				# Encrypt the password
				$result['password'] = crypt ($result['password']);
				
				# Add in fixed data
				$result[$usernameField] = $result['email'];
				$result['userType'] = 'External';
				
				# Add and inform the new administrator
				$html .= $this->processNewAdministrator ($result, $usernameField, $authSystemName);
			}
			$html .= "\n" . '</div>';
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Helper function to process a new administrator
	private function processNewAdministrator ($result, $usernameField, $authSystemName)
	{
		# Start the HTML
		$html  = '';
		
		# Insert the data
		if ($this->databaseConnection->insert ($this->settings['database'], $this->settings['administrators'], $result)) {
			
			# Deal with variance in the fieldnames
			$result['email'] = (isSet ($result['email']) ? $result['email'] : $result[$usernameField] . "@{$this->settings['emailDomain']}");
			$result['privilege'] = (isSet ($result['privilege']) ? $result['privilege'] : 'Administrator');
			$result['forename'] = (isSet ($result['forename']) ? $result['forename'] : $result[$usernameField]);
			$result['password'] = (isSet ($result['password']) ? $result['password'] : "[Your {$authSystemName} password]");
			$result['userType'] = (isSet ($result['userType']) ? $result['userType'] : 'Raven');
			
			# Confirm success and reload the list
			$html .= "\n<p>" . htmlspecialchars ($result[$usernameField]) . ' has been added as an ' . ($result['userType'] == 'External' ? 'external ' : '') . strtolower ($result['privilege']) . '. <a href="">Reset page.</a></p>';
			$this->administrators = $this->getAdministrators ();
			
			# E-mail the new user
			$applicationName = ucfirst (strip_tags ($this->settings['h1'] ? $this->settings['h1'] : $this->settings['applicationName']));
			$message = "\nDear {$result['forename']},\n\nI have given you administrative rights for this facility.\n\nYou can log in using the following credentials:\n\nLogin at:    {$_SERVER['_SITE_URL']}{$this->baseUrl}/\nLogin type:  {$authSystemName} login\nUsername:    {$result[$usernameField]}\nPassword:    {$result['password']}\n\n\nPlease let me know if you have any questions.";
			application::utf8Mail ($result['email'], $applicationName, wordwrap ($message), "From: {$this->userEmail}");
			$html .= "\n<p class=\"success\">An e-mail giving the login details has been sent to the new user.</p>";
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Helper function to delete an administrator
	private function administratorsDelete ($boxClass, $usernameField)
	{
		# Compile the HTML
		$html  = "\n<div class=\"{$boxClass}\">";
		$html .= "\n<h3 id=\"remove\">Remove an administrator</h3>";
		$administrators = $this->administrators;
		//unset ($administrators[$this->user]);	// Remove current user - you can't delete yourself
		if (!$administrators) {
			$html .= "<p>There are no other administrators.</p>";
		} else {
			$form = new form (array (
				'name' => 'remove',
				'submitTo' => '#remove',
				'formCompleteText' => false,
				'div' => false,
				'requiredFieldIndicator' => false,
			));
			$form->select (array (
				'name'	=> $usernameField,
				'title'	=> 'Select administrator to remove',
				'required' => true,
				'values' => array_keys ($administrators),
			));
			$form->input (array (
				'name'			=> 'confirm',
				'title'			=> ($this->settings['externalAuth'] ? 'Type username/e-mail to confirm' : 'Type username to confirm'),
				'required'		=> true,
			));
			$form->validation ('same', array ($usernameField, 'confirm'));
			if ($result = $form->process ($html)) {
				if ($this->databaseConnection->delete ($this->settings['database'], $this->settings['administrators'], array ($usernameField => $result[$usernameField]))) {
					$html .= "\n<p>" . htmlspecialchars ($result[$usernameField]) . " is no longer as an administrator. <a href=\"\">Reset page.</a></p>";
					$this->administrators = $this->getAdministrators ();
				} else {
					$html .= "\n<p class=\"warning\">There was a problem deleting the administrator. (Probably 'delete' privileges are not enabled for this table. Please contact the main administrator of the system.</p>";
				}
			}
		}
		$html .= "\n" . '</div>';
		
		# Return the HTML
		return $html;
	}
	
	
	
	# Helper function to show the current administrators
	private function administratorsShow ($boxClass, $usernameField, $showFields)
	{
		# Compile the HTML
		$html  = "\n<div class=\"{$boxClass}\">";
		$html .= "\n<h3 id=\"list\">List current administrators</h3>";
		if (!$this->administrators) {
			$html .= "\n<p>There are no administrators set up yet.</p>";
		} else {
			$html .= "\n<p>The following are administrators of this system and can make changes to the data in it:</p>";
			$onlyFields = array_merge (array ($usernameField), array_keys ($showFields));
			if ($this->settings['externalAuth']) {$onlyFields[] = 'userType';}
			$tableHeadingSubstitutions = $showFields;
			$tableHeadingSubstitutions[$usernameField] = 'Username';
			$html .= application::htmlTable ($this->administrators, $tableHeadingSubstitutions, $class = 'lines', $showKey = false, $uppercaseHeadings = true, false, false, false, false, $onlyFields);
		}
		$html .= "\n" . '</div>';
		
		# Return the HTML
		return $html;
	}
	
	
	# 404 page
	#!# Needs to have a customised message mode
	function page404 ($includePureContentHeaderFooter = false)
	{
		# End here
		#!# Currently this is visible within the tabs
		application::sendHeader (404);
		if ($this->settings['page404']) {
			if ($includePureContentHeaderFooter) {
				include ('pureContentWrapper.php');
			}
			include ($this->settings['page404']);
			if ($includePureContentHeaderFooter) {
				include ('sitetech/appended.html');
			}
		} else {
			echo "\n<h2>Page not found</h2>";
			echo "\n<p>Sorry, that page was not found. Please check the URL or use the menu to navigate elsewhere.</p>";
		}
		return false;
	}
	
	
	# Home page
	function home ()
	{
		$html  = "<p>Welcome</p>";
		
		# Show the HTML
		echo $html;
	}
	
	
	# Admin editing section, substantially delegated to the sinenomine editing component
	# Needs adding to httpd.conf, where $applicationBaseUrl is not slash-terminated
	#	Use MacroSinenomineEmbeddedWholeDb "$applicationBaseUrl" "/data" "editing"
	public function editing ($attributes = array (), $deny = false /* or supply an array */)
	{
		# If there are no deny table entries, and an array (empty/full) has not been supplied, deny the administrators table by default
		if (!$deny && !is_array ($deny)) {
			$deny = array ();
			$deny[$this->settings['database']] = array (
				'administrators',
			);
		}
		
		# Start the HTML
		$html  = '';
		
		# Get the username field
		$administratorUsernameField = $this->administratorUsernameField ();
		
		# Define the settings
		$settings = array (
			'database' => $this->settings['database'],
			'table' => false,
			'administratorEmail' => $this->settings['administratorEmail'],
			'userIsAdministrator' => $this->userIsAdministrator,
			'application' => __CLASS__,
			'baseUrl' => $this->baseUrl . '/data',
			'attributes' => (isSet ($attributes) ? $attributes : array ()),
			'exclude' => (isSet ($exclude) ? $exclude : false),
			'displayErrorDebugging' => false,
			'lookupFunctionParameters' => array (true), // Ensures that the abstract ID is shown
			'validation' => (isSet ($validation) ? $validation : false),
			'pagination' => $this->settings['editingPagination'],
			'showMetadata' => false,
			'hideTableIntroduction' => true,
			#!# Inconsistent
			'fieldFiltering' => "{$this->settings['database']}.{$this->settings['administrators']}.{$administratorUsernameField}.{$this->user}.state",
			'deny' => $deny,
			'denyAdministratorOverride' => false,
			'tableCommentsInSelectionList' => true,
		);
		
		# Load and run the database editing
		require_once ('sinenomine.php');
		$sinenomine = new sinenomine ($settings, $this->databaseConnection);
		
		# Set constraints
		if ($attributes) {
			foreach ($attributes as $attribute) {
				$sinenomine->attributes ($attribute[0], $attribute[1], $attribute[2], $attribute[3]);
			}
		}
		
		# Process
		$sinenomine->process ();
		$html .= $sinenomine->getContentCss (true);
		$html .= $sinenomine->getHtml ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Editing of a single table, substantially delegated to the sinenomine editing component
	# Needs adding to httpd.conf, where $applicationBaseUrl is not slash-terminated
	#	Use MacroSinenomineEmbeddedTable "$applicationBaseUrl" "$editingUrl" "$applicationAction"
	public function editingTable ($table, $dataBindingAttributes = array (), $formDiv = 'graybox lines', $tableUrlMoniker = false, $sinenomineExtraSettings = array ())
	{
		# Start the HTML
		$html  = '';
		
		# Get the username field
		$administratorUsernameField = $this->administratorUsernameField ();
		
		# Define the sinenomine settings, with any additional settings taking priority
		$settings = array (
			'database' => $this->settings['database'],
			'table' => $table,
			'tableUrlMoniker' => $tableUrlMoniker,
			'administratorEmail' => $this->settings['administratorEmail'],
			'userIsAdministrator' => $this->userIsAdministrator,
			'application' => __CLASS__,
			'baseUrl' => $this->baseUrl,
			'pagination' => $this->settings['editingPagination'],
			'showMetadata' => false,
			'hideTableIntroduction' => true,
			'fieldFiltering' => "{$this->settings['database']}.{$this->settings['administrators']}.{$administratorUsernameField}.{$this->user}.editingState" . ucfirst ($table),
			'formDiv' => $formDiv,
		);
		
		# Add additional settings if specified, overwriting any specified above
		$settings = array_merge ($settings, $sinenomineExtraSettings);
		
		# Load and run the database editing
		require_once ('sinenomine.php');
		$sinenomine = new sinenomine ($settings, $this->databaseConnection);
		
		# Set constraints
		if ($dataBindingAttributes) {
			foreach ($dataBindingAttributes as $field => $attributeArray) {
				$sinenomine->attributes ($this->settings['database'], $table, $field, $attributeArray);
			}
		}
		
		# Process
		$sinenomine->process ();
		$html .= $sinenomine->getContentCss (true);
		$html .= $sinenomine->getHtml ();
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to send administrative alerts
	function reportError ($adminMessage, $publicMessage = 'Apologies, but a problem with the setup of this system was found. The webmaster has been made aware of this problem and will correct the misconfiguration as soon as possible. Please kindly check back later.', $databaseModeData = false, $class = 'warning')
	{
		# Add on database error information if present
		if ($this->settings['useDatabase'] && ($databaseModeData !== false)) {
			$adminMessage .= "\n\nThe database said:\n" . application::dumpData ($this->databaseConnection->error (), true);
		}
		
		# E-mail the error to the administrator (unless the user is an administrator)
		if (!$this->userIsAdministrator) {
			$mailheaders = 'From: ' . $this->settings['applicationName'] . ' <' . $this->settings['administratorEmail'] . '>';
			application::utf8Mail ($this->settings['administratorEmail'], 'Error in ' . $this->settings['applicationName'], wordwrap ($adminMessage), $mailheaders);
		}
		
		# Start the HTML
		$html  = '';
		
		# Create the visible text of an error
		if ($publicMessage) {
			$html .= "\n<p class=\"{$class}\">" . htmlspecialchars ($publicMessage) . '</p>';
		}
		
		# Add on debugging information if the user is an administrator
		if ($this->userIsAdministrator) {
			$html .= "\n<div class=\"graybox\">";
			$html .= "\n<p class=\"warning\">Admin debug information:</p>";
			$html .= "\n<pre>" . htmlspecialchars ($adminMessage) . '</pre>';
			$html .= "\n</div>";
		}
		
		# Return the error as HTML
		return $html;
	}
}

?>