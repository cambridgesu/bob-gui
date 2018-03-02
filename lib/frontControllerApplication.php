<?php


#!# Add flag to add an 'admins can create users' flag option


# Front Controller pattern application
# Version 1.9.10
class frontControllerApplication
{
	# Define global defaults
	private function globalDefaults ()
	{
		# Specify available arguments as defaults or as NULL (to represent a required argument)
		return array (
			'applicationName'								=> application::unCamelCase (get_class ($this)),
			'enabled'										=> true,		// Whether this application is enabled
			'authentication' 								=> false,		// Whether all pages require authentication
			'dataDisableAuth'								=> false,		// Whether to disable auth on the data function (only relevant when using authentication=true); this can cause logout due to fast cookie transfer
			'externalAuth'									=> false,		// Allow external authentication/authorisation
			'internalAuth'									=> false,		// Allow internal authentication/authorisation
			'internalAuthSalt'								=> '%_salt',	// Salt used for internalAuth; should be set if using internalAuth
			'internalAuthPasswordRequiresLettersAndNumbers'	=> true,	// Whether the internal auth password requires both letters and numbers
			'authLinkVisibility'							=> true,		// Whether the auth link is visible (true/false or regexp for matching REMOTE_ADDR)
			'minimumPasswordLength'							=> 4,			// Minimum password length when using externalAuth
			'h1'											=> false,		// NB an empty string will remove <h1>..</h1> altogether
			'headerLocation'								=> false,		// GUI header, if local loading needed
			'footerLocation'								=> false,		// GUI footer, if local loading needed
			'guiLocationAbsolute'							=> false,
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
			'nativeTypes'									=> false,	// Whether to enable native types in the database (e.g. INT columns return values as int); a future release will change this to true
			'installerUsername'								=> 'root',	// Username for database installer account
			'installerPassword'								=> false,	// Password for database installer account; if not defined, the user will be prompted for it with a GUI form
			'jQuery'										=> false,	// Whether to load jQuery
			'peopleDatabase'								=> 'people',
			'table'											=> NULL,
			'administrators'								=> false,	// Administrators table e.g. 'administrators' or 'facility.administrators', or an array of usernames
			'settingsTable'									=> 'settings',	// Settings table (must be in the main database) e.g. 'settings' or false to disable (only needed a table of that name is present for a different purpose)
			'settingsTableExplodeTextarea'					=> false,	// Whether to split textarea columns in a settings table into an array of values - true/false, or an array of fieldnames which should have this applied to
			'profiles'										=> false,	// Use of the profiles system (true/false or table, e.g. 'profiles'; true will use 'profiles'
			'tablePrefix'									=> false,	// Prefix which will be added to any table/administrators/settingsTable/profiles settings
			'logfile'										=> './logfile.txt',
			'webmaster'										=> (isSet ($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : NULL),
			'administratorEmail'							=> (isSet ($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : NULL),
			'webmasterContactAddress'						=> (isSet ($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : NULL),
			'feedbackRecipient'								=> (isSet ($_SERVER['SERVER_ADMIN']) ? $_SERVER['SERVER_ADMIN'] : NULL),	#!# This ought to be the value of administratorEmail by default
			'useCamUniLookup'								=> true,
			'directoryIndex'								=> 'index.html',					# The directory index, used for local file retrieval
			'userAgent'										=> 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0)',	# The user-agent string used for external retrieval
			'emailDomain'									=> 'cam.ac.uk',
			'ravenGetPasswordUrl'							=> 'https://jackdaw.cam.ac.uk/get-raven-password/',
			'ravenResetPasswordUrl'							=> 'https://jackdaw.cam.ac.uk/get-raven-password/',
			'ravenCentralLogoutUrl'							=> 'https://raven.cam.ac.uk/auth/logout.html',
			'authFileGroup'									=> false,		// Whether to write an auth file containing the administrators, and if so, what group name (or true, which will allocate 'administrators')
			'page404'										=> 'sitetech/404.html',	// Or false to use internal handler
			'useAdmin'										=> true,
			'revealAdminFunctions'							=> false,	// Whether to show admins-only tabs etc to non-administrators
			'useFeedback'									=> true,
			'disableTabs'									=> false,
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
			'cronUsername'									=> false,	// HTTP username required for cron jobs
			'apiUsername'									=> false,	// HTTP username required for API calls
			'apiJsonPretty'									=> true,	// Whether to use pretty printing for JSON output
			'applicationStylesheet'							=> '/styles.css',	// Where / represents the root of the repository containing the application
			'dataDirectory'									=> '/data/',	// Where / represents the root of the repository containing user data files
			'itemCaseSensitive'								=> false,	// Whether an $item value fed to an action is case-sensitive; if not, it is converted to lower-case; #!# In future this will default to true
			'corsDomains'									=> array (),	// Domains enabled for CORS headers
			'importsSectionsMode'							=> false,	// Whether imports consist of a set of sections that all combine into one table and can be imported separately
			'importLog'										=> false,	// Import log file (false or filename; %applicationRoot is supported), which will create importlog.txt in baseUrl
			'useTemplating'									=> false,	// Whether to enable templating
			'templatesDirectory'							=> '%applicationRoot/app/views/',
			'exportsDirectory'								=> '%applicationRoot/exports/',
		);
	}
	
	
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
			'tab' => 'My profile',
			'icon' => 'user',
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
			'tab' => 'Data editing',
			'icon' => 'pencil',
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
			'url' => 'login/',
			'usetab' => 'home',
		),
		'logoutinternal' => array (
			'description' => 'Logout',
			'url' => 'login/logout/',
			'usetab' => 'home',
		),
		'register' => array (
			'description' => 'Create a new account',
			'url' => 'login/register/',
			'usetab' => 'home',
		),
		'resetpassword' => array (
			'description' => 'Reset a forgotten password',
			'url' => 'login/resetpassword/',
			'usetab' => 'home',
		),
		'accountdetails' => array (
			'description' => 'Change login account details',
			'url' => 'login/accountdetails/',
			'usetab' => 'home',
			'authentication' => true,
		),
		'loggedout' => array (
			'description' => 'Logged out',
			'url' => 'loggedout.html',
			'usetab' => 'home',
		),
		'cron' => array (
			'description' => 'Cron hook for non-interactive processes',
			'url' => 'cron/',
			'export' => true,
		),
		'templates' => array (
			'description' => 'Templates',
			'url' => 'templates/',
			'parent' => 'admin',
			'subtab' => 'Templates',
			'icon' => 'tag',
			'administrator' => true,
		),
		'api' => array (
			'description' => 'API (HTTP)',
			'url' => 'api/%s',
			'export' => true,
		),
		'apidocumentation' => array (
			'description' => 'API (HTTP)',
			'url' => 'api/',
			'administrator' => true,
		),
		'data' => array (	// Used for e.g. AJAX calls, etc.
			'description' => 'Data point',
			'url' => 'data.html',
			'export' => true,
		),
		
	);
	
	# Define defaults; these can be extended by adding definitions in a defaults () method
	var $defaults = array ();
	
	# User status (an optional way of adding (...) after the username in the login corner
	private $userStatus = false;
	
	# Internal auth
	var $internalAuthClass = NULL;
	
	# Define common text
	protected $cross = '<img src="/images/icons/cross.png" alt="Cross" class="icon" />';
	protected $tick = '<img src="/images/icons/tick.png" alt="Tick" class="icon" />';
	
	# Tab forcing
	var $tabForced = false;
	
	# Templating
	public $template = array ();
	public $templateFunctions = array ();	// NB Functions must be within the class, not static, and marked public
	
	
	# Constructor
	public function __construct ($settings = array (), $disableAutoGui = false)
	{
		# Load required libraries
		require_once ('application.php');
		
		# Define the location of the stub launching file and the image store
		$this->baseUrl = application::getBaseUrl ();
		$this->imageStoreRoot = $_SERVER['DOCUMENT_ROOT'] . $this->baseUrl . '/images/';
		
		# Determine the application (repository) directory; see: http://stackoverflow.com/questions/32937389/
		$classHierarchy = array_reverse (array_values (class_parents ($this)));		// Classes in order, starting with frontControllerApplication, but not including the last in the chain
		$classHierarchy[] = get_class ($this);		// The last in the chain
		$mainApplicationClass = $classHierarchy[1];		// i.e. the direct child of frontControllerApplication
		$reflector = new ReflectionClass ($mainApplicationClass);
		$this->applicationRoot = dirname ($reflector->getFileName ());
		
		# Obtain the defaults
		$this->defaults = $this->assignDefaults ($settings);
		
		# Function to merge the arguments; note that $errors returns the errors by reference and not as a result from the method
		#!# Ideally the start and end div would surround these items before $this->action is determined, but that would break external type handling
		if (!$this->settings = application::assignArguments ($errors, $settings, $this->defaults, get_class ($this), NULL, $handleErrors = true)) {return false;}
		
		# End if not enabled
		if (!$this->settings['enabled']) {
			$this->page404 ();
			return false;
		}
		
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
		
		# Obtain the house style files (header and footer)
		$houseStyleParts = array ('header', 'footer');
		foreach ($houseStyleParts as $houseStylePart) {
			${$houseStylePart} = false;     // i.e. create $header and $footer
			if ($this->settings[$houseStylePart . 'Location']) {	// i.e. headerLocation and footerLocation
				$file = ($this->settings['guiLocationAbsolute'] ? '' : $_SERVER['DOCUMENT_ROOT']) . $this->settings[$houseStylePart . 'Location'];
				if (is_readable ($file)) {
					${$houseStylePart} = file_get_contents ($file);
				}
			}
		}
		
		# Show header if required
		echo $header;
		
		# Set a lockfile location
		$this->lockfile = $_SERVER['DOCUMENT_ROOT'] . $this->baseUrl . '/lockfile.txt';
		
		# Set import log location if enabled
		$this->importLog = false;
		if (is_string ($this->settings['importLog'])) {
			$this->importLog = str_replace ('%applicationRoot', $this->applicationRoot, $this->settings['importLog']);
		}
		
		# Define the data URL, e.g. for use with ultimateForm::<widget>::autocomplete
		$this->dataUrl = "{$_SERVER['_SITE_URL']}{$this->baseUrl}/data.html";
		
		# Define the footer message which goes at the end of any e-mails sent
		$this->footerMessage = "\n\n\n---\nIf you have any questions or need assistance with this facility, please check the help/feedback pages on the website at:\n{$_SERVER['_SITE_URL']}{$this->baseUrl}/";
		
		# Ensure the version of PHP is supported
		if (version_compare (PHP_VERSION, $this->settings['minimumPhpVersion'], '<')) {
			echo $this->throwError (3, "PHP version needs to be at least: {$this->settings['minimumPhpVersion']}");
			echo $footer;
			return false;
		}
		
		# Get the username if set - the security model hands trust up to Apache/Raven
		$this->user = (isSet ($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : NULL);
		if ($this->settings['internalAuth']) {$this->user = false;}		// The user comes from a database connection so the "new database" call (which supplies $this->user) cannot know the user by this point; this ordering avoids having to create two database connections (one for this call and one for the userAccount class)
		if ($this->settings['user']) {$this->user = $this->settings['user'];}
		
		# If required, make connections to the database server and ensure the tables exist
		if ($this->settings['useDatabase']) {
			require_once ('database.php');
			$this->databaseConnection = new database ($this->settings['hostname'], $this->settings['username'], $this->settings['password'], $this->settings['database'], $this->settings['vendor'], $this->settings['logfile'], $this->user, $this->settings['nativeTypes']);
			if (!$this->databaseConnection->connection) {
				echo $this->databaseConnection->reportError ($this->settings['administratorEmail'], $this->settings['applicationName']);
				echo $footer;
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
		$this->userEmail = false;
		if ($this->settings['internalAuth']) {
			$this->loadInternalAuth ();
			$this->user = $this->internalAuthClass->getUserId ();
			$this->userEmail = $this->internalAuthClass->getUserEmail ();
			$this->userVisibleIdentifier = $this->internalAuthClass->getUserEmail ();
			#!# This appears above the tabs
			echo $this->internalAuthClass->getHtml ();	// Basically will only appear if the user gets logged out for security reasons
		}
		
		# Setup the database if required
		if ($this->settings['useDatabase']) {
			if (method_exists ($this, 'databaseStructure')) {
				if (!$this->databaseSetup ($html)) {
					echo $html;
					echo $footer;
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
		
		# Assign the item (basically to deal with the common scenario of a function needing an ID parameter
		$this->item = (isSet ($_GET['item']) ? ($this->settings['itemCaseSensitive'] ? $_GET['item'] : strtolower ($_GET['item'])) : false);
		
		# Additional processing, before actions processing phase, if required
		if (method_exists ($this, 'mainPreActions')) {
			if ($this->mainPreActions () === false) {
				if ($this->settings['div']) {echo "\n<div id=\"{$this->settings['div']}\">\n";}
				$endDiv = ($this->settings['div'] ? "\n</div>" : '');
				echo $endDiv;
				echo $footer;
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
		
		# Compatibility fix to pump a script-supplied argument into the query string
		if (isSet ($_SERVER['argv']) && isSet ($_SERVER['argv'][1]) && preg_match ('/^action=/', $_SERVER['argv'][1])) {
			$this->action = preg_replace ('/^action=/', '', $_SERVER['argv'][1]);
		}
		
		# Determine whether the action is an export type, i.e. has no house style or loaded outside the system
		$this->exportType = ($disableAutoGui || (isSet ($this->actions[$this->action]['export']) && ($this->actions[$this->action]['export'])));
		if ($this->exportType) {$this->settings['div'] = false;}
		
		# Load jQuery if required
		if (!$this->exportType) {
			if ($this->settings['jQuery']) {
				echo "\n\n\n<!-- jQuery -->\n" . '<script type="text/javascript" src="//code.jquery.com/jquery.min.js"></script>' . "\n\n";
			}
		}
		
		# Load any stylesheet if supplied
		if (!$this->exportType) {
			$stylesheet = $this->applicationRoot . $this->settings['applicationStylesheet'];
			if (is_readable ($stylesheet)) {
				$styles = file_get_contents ($stylesheet);
				echo "\n\n" . '<style type="text/css">' . "\n\t" . str_replace ("\n", "\n\t", trim ($styles)) . "\n</style>\n";
			}
		}
		
		# Determine the data directory
		if ($this->settings['dataDirectory']) {
			$dataDirectory = $this->applicationRoot . $this->settings['dataDirectory'];
			if (is_dir ($dataDirectory) && is_readable ($dataDirectory)) {
				$this->dataDirectory = $dataDirectory;
			}
		}
		
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
		$functions = array ('editing', 'profile', 'import', 'templates', 'apidocumentation', 'feedback', 'help', 'admin');
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
		if (isSet ($this->actions[$this->action]) && array_key_exists ('description', $this->actions[$this->action]) && $this->actions[$this->action]['description'] && !substr_count ($this->actions[$this->action]['description'], '%') && (!isSet ($this->actions[$this->action]['heading']) || $this->actions[$this->action]['heading'])) {
			$headerHtml .= "\n<h2>{$this->actions[$this->action]['description']}</h2>";
		}
		
		# Redirect to the page requested if necessary
		if (!$this->login ()) {
			echo $endDiv;
			echo $footer;
			return false;
		}
		
		# Determine whether the user can see auth (login/logout) links
		$authLinkVisibility = $this->settings['authLinkVisibility'];
		$authLimited = (is_string ($this->settings['authLinkVisibility']));
		if ($authLimited) {
			$delimiter = '@';
			$authLinkVisibility = (preg_match ($delimiter . addcslashes ($this->settings['authLinkVisibility'], $delimiter) . $delimiter, gethostbyaddr ($_SERVER['REMOTE_ADDR'])));
		}
		
		# Show login status
		#!# Should have urlencode also?
		$location = htmlspecialchars ($_SERVER['REQUEST_URI']);	// Note that this will not maintain any #anchor, because the server doesn't see any hash: http://stackoverflow.com/questions/940905
		$this->ravenUser = !substr_count ($this->user, '@');
		$loginUrl = (isSet ($_SERVER['SINGLE_SIGN_ON_ENABLED']) && $_SERVER['SINGLE_SIGN_ON_ENABLED'] ? '/login/' : $this->baseUrl . '/login.html');
		$logoutUrl = (isSet ($_SERVER['SINGLE_SIGN_ON_ENABLED']) && $_SERVER['SINGLE_SIGN_ON_ENABLED'] ? '/logout/' : $this->baseUrl . '/logout.html');
		$loginTextLink = "You are not currently <a href=\"{$loginUrl}?{$location}\" rel=\"nofollow\">logged in</a>";
		if (!$this->ravenUser) {$logoutUrl = $this->baseUrl . '/logoutexternal.html';}
		if ($this->settings['externalAuth']) {$loginTextLink = "You are not currently logged in using [<a href=\"{$loginUrl}?{$location}\" rel=\"nofollow\">Raven</a>] or [<a href=\"{$this->baseUrl}/loginexternal.html?{$location}\" rel=\"nofollow\">Friends login</a>]";}
		if ($this->settings['internalAuth']) {
			$logoutUrl = $this->baseUrl . '/' . $this->actions['logoutinternal']['url'];
			$loginTextLink = "You are not currently <a href=\"{$this->baseUrl}/{$this->actions['logininternal']['url']}?{$location}\" rel=\"nofollow\">logged in</a>";
		}
		if ($authLinkVisibility) {
			$headerHtml = '<p class="loggedinas noprint"' . ($authLimited ? ' title="[The login system is not visible to all users]"' : '') . '>' . ($this->user ? 'You are logged in as: <strong>' . $this->userVisibleIdentifier . ($this->userIsAdministrator ? ' (ADMIN)' : ($this->userStatus ? " ({$this->userStatus})" : '')) . '</strong> [<a href="' . $logoutUrl . "?{$location}\" class=\"logout\" rel=\"nofollow\">log out</a>]" : $loginTextLink) . '</p>' . $headerHtml;
		}
		
		# Show the header/tabs
		if (!$this->exportType) {
			echo $headerHtml;
		}
		
		# Require authentication for actions that require this
		$authRequiredByAction = (isSet ($this->actions[$this->action]['authentication']) && $this->actions[$this->action]['authentication']);
		$authRequiredGlobally = $this->settings['authentication'];
		if (isSet ($this->actions[$this->action]['authentication']) && !$this->actions[$this->action]['authentication']) {
			$authRequiredGlobally = false;	// Override global authentication setting if explicitly set to false for the local action
		}
		if (!$this->user && ($authRequiredByAction || $authRequiredGlobally)) {
			$pagesNeverRequiringAuthentication = array ('register', 'resetpassword', );
			if ($this->settings['dataDisableAuth']) {$pagesNeverRequiringAuthentication[] = 'data';}
			if ($this->settings['apiUsername']) {$pagesNeverRequiringAuthentication[] = 'api';}
			if (!in_array ($this->action, $pagesNeverRequiringAuthentication)) {
				if ($this->settings['authentication']) {echo "\n<p>Welcome.</p>";}
				$loginTextLink = "<a href=\"{$loginUrl}?{$location}\" tabindex=\"1\">log in (using Raven)</a>";
				if ($this->settings['externalAuth']) {$loginTextLink = "log in using [<a href=\"{$loginUrl}?{$location}\">Raven</a>] or [<a href=\"{$this->baseUrl}/loginexternal.html?{$location}\">Friends login</a>]";}
				if ($this->settings['internalAuth']) {$loginTextLink = "<a href=\"{$this->baseUrl}/{$this->actions['logininternal']['url']}?{$location}\">log in</a> (or <a href=\"{$this->baseUrl}/{$this->actions['register']['url']}\">create an account</a>)";}
				echo "\n<p><strong>Please " . $loginTextLink . " so that you can " . ($this->actions[$this->action]['description'] ? htmlspecialchars (strtolower (strip_tags ($this->actions[$this->action]['description']))) : 'use this facility') . '.</strong></p>';
				if (!$this->settings['internalAuth']) {
					echo "\n<p>(<a href=\"{$this->baseUrl}/help.html\">Information on Raven accounts</a> is available.)</p>";
				}
				echo $endDiv;
				echo $footer;
				return false;
			}
		}
		
		# Check administrator credentials if necessary
		if (isSet ($this->actions[$this->action]['administrator']) && ($this->actions[$this->action]['administrator'])) {
			if ($this->restrictedAdministrator) {
				echo "\n<p><strong>You need to be logged on as a full, unrestricted administrator to access this section.</p>";
				echo $endDiv;
				echo $footer;
				return false;
			} else {
				if (!$this->userIsAdministrator) {
					echo "\n<p><strong>You need to be logged on as an administrator to access this section.</strong></p>";
					echo $endDiv;
					echo $footer;
					return false;
				}
			}
		}
		
		# Check restricted administrator credentials if necessary
		if (isSet ($this->actions[$this->action]['restrictedAdministrator']) && ($this->actions[$this->action]['restrictedAdministrator'])) {
			if (!$this->userIsAdministrator && !$this->restrictedAdministrator) {
				echo "\n<p><strong>You need to be logged on as an restricted administrator to access this section.</strong></p>";
				echo $endDiv;
				echo $footer;
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
					echo "\n<p><strong>Please log in so that you can access this facility.</strong></p>";
				}
				echo $endDiv;
				echo $footer;
				return false;
			}
		}
		
		# Get the user's details
		$this->userName = false;
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
				echo $footer;
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
		
		# Initialise templating if required
		$this->templateHandle = $this->initialiseTemplating ();
		
		# Perform the action
		if (!$disableAutoGui) {
			$this->performAction ($this->doAction, $this->item);
		}
		
		# End with a div if not an export type
		if (!$this->exportType) {
			echo $endDiv;
			echo $footer;
		}
		
		# Run the shutdown (actually post-action) function if one has been defined
		if (method_exists ($this, 'shutdown')) {
			$this->shutdown ();
		}
	}
	
	
	# Function to perform the action
	private function performAction ($action, $item)
	{
		# Perform the action
		$this->$action ($item);
	}
	
	
	# Function to define defaults
	private function assignDefaults ($settings)
	{
		# Get the global defaults
		$globalDefaults = $this->globalDefaults ();
		
		# Merge application defaults with the standard application defaults, with preference: constructor settings, application defaults, frontController application defaults
		$defaults = array_merge ($globalDefaults, $this->defaults ($settings), $settings);
		
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
	
	
	# Function to deal with errors
	#!# Some applications using frontControllerApplication have their own throwError with a different signature - need to create a functional superset
	public function throwError ($number, $diagnosisDetails = '')
	{
		# Define an array of errors
		$applicationErrors = array (
			0 => 'This facility is temporarily unavailable. Please check back shortly.',
			// 1 => 'The webserver was unable to access user authorisation credentials, so we regret this facility is unavailable at this time.',
			// 2 => 'There was a problem initialising the database structure on first-run. Possibly the administrator/root password was wrong.',
			3 => 'The server software does not support this application.',
		);
		
		# Define the default error message if the specified error number does not exist
		$errorMessage = (isSet ($applicationErrors[$number]) ? $applicationErrors[$number] : "A strange yet unknown error (#$number) has occurred.");
		
		# Show the error message
		$userErrors[] = 'Error: ' . $errorMessage . ' The administrator has been notified of this problem.';
		$html = application::showUserErrors ($userErrors);
		
		# Assemble the administrator's error message
		if ($diagnosisDetails != '') {$errorMessage .= "\n\nFurther information available: " . $diagnosisDetails;}
		
		# Mail the admininistrator
		$subject = '[' . ucfirst ($this->settings['applicationName']) . '] error';
		$message = 'The ' . $this->settings['applicationName'] . " has an application error: please investigate. Diagnostic details are given below.\n\nApplication error $number:\n" . $errorMessage;
		application::sendAdministrativeAlert ($this->settings['administratorEmail'], $this->settings['applicationName'], $subject, $message);
		
		# Return the HTML
		return $html;
	}
	
	
	# Setter function to set the userStatus text
	public function setUserStatus ($string)
	{
		$this->userStatus = $string;
	}
	
	
	# Skeleton function to get local actions; normally overridden
	public function defaults ()
	{
		return $this->defaults;
	}
	
	
	# Function to define defaults
	private function assignActions ()
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
		if (!$this->settings['useTemplating']) {unset ($actions['templates']);}
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
	public function actions ()
	{
		return $this->actions;
	}
	
	
	# Function to show tabs of the actions
	#!# Currently this mixes modification of the action registry with display of the tabs
	private function showTabs ($current, $class = 'tabs')
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
			if (array_key_exists ('enableIf', $attributes)) {	// array_key_exists used, to enable NULL to be considered false
				if (!$attributes['enableIf']) {
					unset ($this->actions[$action]);
					if ($this->action == $action) {$this->action = 'home';}
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
				$tabs[$action] .= "<a href=\"{$url}\"" . (array_key_exists ('description', $this->actions[$action]) ? ' title="' . trim (strip_tags ($attributes['description'])) . '"' : '') . (isSet ($attributes['linkId']) ? " id=\"{$attributes['linkId']}\"" : '') . ">";
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
		
		# If not showing the tabs, cancel the HTML
		if ($this->settings['disableTabs']) {
			$html = '';
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to set up the database
	private function databaseSetup (&$html)
	{
		# Get the tables, or end if already present
		if ($tables = $this->databaseConnection->getTables ($this->settings['database'])) {return true;}
		
		# If a database password is supplied, use this rather than running the setup routine interactively
		if ($this->settings['installerPassword']) {
			if (!$installerDatabaseConnection = $this->installerDatabaseConnection ($this->settings['installerPassword'], $databaseError)) {
				$html  = "\n<p>The database setup process did not complete. You may need to set this up manually. The database error was:</p>";
				$html .= "\n<p><pre>" . wordwrap (htmlspecialchars ($databaseError)) . '</pre></p>';
				return false;
			}
		} else {
			
			# End if on the login page
//			if ($this->action == 'login') {return true;}
			
			# If using internalAuth, this has to be temporarily switched to HTTP auth, to avoid the chicken-and-egg situation of not having an account to set up the tables, but there not being a user table
//			if ($this->settings['internalAuth']) {$this->settings['internalAuth'] = false;}
			
			# Start the HTML
			$html  = "\n<h2>Set up database</h2>";
			
			# Ensure the user is logged in
//			$location = htmlspecialchars ($_SERVER['REQUEST_URI']);	// Note that this will not maintain any #anchor, because the server doesn't see any hash: http://stackoverflow.com/questions/940905
//			$loginTextLink = "You are not currently logged in</a>";
			$html .= "\n<p>The database is not yet set up. The site administrator needs to " . /* ($this->user ? */ "enter the database system password below." /* : "<a href=\"{$this->baseUrl}/login.html?{$location}\">log in</a> first.") */ . '</p>';
//			if (!$this->user) {return false;}
			
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
					if (!$installerDatabaseConnection = $this->installerDatabaseConnection ($unfinalisedData['password'], $errorMessage)) {
						$form->registerProblem ('wrong', $errorMessage, 'password');
					}
				}
			}
			if (!$result = $form->process ($html)) {return false;}
		}
		
		# Get the database structure, either an SQL statement or a simple array of queries
		$databaseStructure = $this->databaseStructure ();
		
		# Ensure the SQL is an array of queries
		if (!is_array ($databaseStructure)) {
			$databaseStructure = array ($databaseStructure);
		}
		
		# Attach internalAuth structure if required
		if ($this->settings['internalAuth']) {
			$databaseStructure[] = $this->internalAuthClass->databaseStructure ();
		}
		
		# Execute each query, and show failure error message if something went wrong
		$i = 0;
		foreach ($databaseStructure as $query) {
			$i++;
			if (!$result = $installerDatabaseConnection->query ($query)) {
				$html  = "\n<p>The database setup process did not complete" . (count ($databaseStructure) > 1 ? ", failing at query #{$i}" : '') . ". You may need to set this up manually. The database error was:</p>";
				$databaseError = $installerDatabaseConnection->error ();
				$html .= "\n<p><pre>" . wordwrap (htmlspecialchars ($databaseError[2])) . '</pre></p>';
				return false;
			}
		}
		
		# Redirect
		$redirectTo = $_SERVER['_SITE_URL'] . $this->baseUrl . ($this->settings['internalAuth'] ? '/login/register/' : '/');	// Sadly, these have to be hard-coded as the action loading phase hasn't yet happened
		application::sendHeader (302, $redirectTo);
		
		# Confirm success (in case the header redirection failed)
		return true;
	}
	
	
	# Function to connect to the database with the installer account
	private function installerDatabaseConnection ($password, &$errorMessage = '')
	{
		# Attempt the connection
		$installerDatabaseConnection = new database ($this->settings['hostname'], $this->settings['installerUsername'], $password, $this->settings['database'], $this->settings['vendor'], $this->settings['logfile'], $this->user, $this->settings['nativeTypes']);
		
		# End if no connection, defining the error message
		if (!$installerDatabaseConnection->connection) {
			$errorMessage = "Could not connect using that password for " . htmlspecialchars ($this->settings['hostname']);
			return false;
		}
		
		# Return the connection resource
		return $installerDatabaseConnection;
	}
	
	
	# Function to create an icon
	public function icon ($icon)
	{
		# NB The icon location is absolute at present; could add setting in future if required
		return '<img src="' . '/images/icons/' . $icon . '.png" alt="" class="icon" /> ';
	}
	
	
	# Function to show tabs of the actions
	private function showSubTabs ($current)
	{
		# End if not in a subtabbed section
		if (!$this->parentAction && !$this->isParentAction) {return;}
		
		# Determine the parent to use
		$parent = ($this->isParentAction ? $current : $this->parentAction);
		
		# Merge in the child actions
		$actions = $this->getChildActions ($parent, true, true);
		
		# Compile the HTML, adding a heading
		$html  = "\n<h4 id=\"tabsheading\">" . (isSet ($this->actions[$parent]['subheading']) ? $this->actions[$parent]['subheading'] : $this->actions[$parent]['description']) . '</h4>';
		$html .= $this->actionsListHtml ($actions, $useDescriptionAsText = false, $this->settings['tabUlClass'] . ' subtabs', $current);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to get the child actions for a function
	public function getChildActions ($parent, $includeParent = false, $subtabsOnly = false)
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
	public function facilityIsOpen (&$html, $openingExtraMessage = false, $closingExtraMessage = false)
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
	public function actionsListHtml ($actions, $useDescriptionAsText = false, $ulClass = false, $current = false)
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
	private function getAdministrators ()
	{
		# Return an empty array if the application does not use a table of administrators
		if (!$this->settings['administrators']) {return array ();}
		
		# If the setting is an array the return that
		if (is_array ($this->settings['administrators'])) {
			foreach ($this->settings['administrators'] as $administrator) {
				$administrators[$administrator] = $administrator;	// Administrators have to be in the key
			}
			return $administrators;
		}
		
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
			$administrators[$username]['email']  = ((isSet ($administrator['email']) && (!empty ($administrator['email']))) ? $administrator['email'] : $username);
			if (!substr_count ($administrators[$username]['email'], '@')) {
				$administrators[$username]['email'] .= (((!isSet ($administrator['userType'])) || ($administrator['userType'] != 'External')) ? "@{$this->settings['emailDomain']}" : '');
			}
		}
		
		# Return the array
		return $administrators;
	}
	
	
	# Function to return a list of administrators receiving e-mail
	public function getAdministratorsReceivingEmail ($asStringImplode = ', ', $exceptEmails = array ())
	{
		# Create a list
		$recipients = array ();
		foreach ($this->administrators as $username => $administrator) {
			if (isSet ($administrator['receiveEmail'])) {	// If this field is present, only include those receiving e-mail
				if ($administrator['receiveEmail'] == 'Yes') {
					$recipients[$username] = $administrator['email'];
				}
			} else {
				$recipients[$username] = $administrator['email'];
			}
		}
		
		# Filter out unwanted if required
		if ($exceptEmails) {
			if (is_string ($exceptEmails)) {
				$exceptEmails = array ($exceptEmails);
			}
			foreach ($exceptEmails as $index => $exceptEmail) {
				if (!substr_count ($exceptEmail, '@')) {
					$exceptEmails[$index] = $exceptEmail . '@cam.ac.uk';
				}
			}
			$recipients = array_diff ($recipients, $exceptEmails);
		}
		
		# Implode to string
		if ($asStringImplode) {
			$recipients = implode ($asStringImplode, $recipients);
		}
		
		# Return the list
		return $recipients;
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
	public function userIsAdministrator ()
	{
		# Return NULL if no user
		if (!$this->userVisibleIdentifier || !$this->administrators) {return NULL;}
		
		# Return boolean whether the user is in the list
		return (array_key_exists ($this->userVisibleIdentifier, $this->administrators));
	}
	
	
	# Login function
	private function login ($method = 'login')
	{
		# Start the HTML
		$html = '';
		
		# Ensure there is a username, by forcing a query string with "action=login" in to be redirected to the login method noted
		#!# Throw error 1 if on the login page and no username is provided by the server
		$delimiter = '/';
		if (ini_get ('output_buffering') && preg_match ($delimiter . '^action=' . preg_quote ($method, $delimiter) . $delimiter, $_SERVER['QUERY_STRING'])) {
			
			# For internal login, return whether valid credentials have been supplied, and if not show a form
			if ($this->settings['internalAuth']) {
				$method = 'logininternal';
				$html .= $this->logininternal ($result /* passed back by reference */);
				if (!$result) {
					echo $html;
					return false;
				}
			}
			
			# Redirect back
			#!# Support output_buffering being off by providing a link
			$location = $this->baseUrl . '/';
			if (substr_count ($_SERVER['QUERY_STRING'], "action={$method}&/")) {
				$location = '/' . str_replace ("action={$method}&/", '', $_SERVER['QUERY_STRING']);
			}
			#!# This isn't actually needed by the logininternal implementation, as that handles redirects itself
			header ('Location: ' . $_SERVER['_SITE_URL'] . $location);
			return false;
		}
		
		# End
		return true;
	}
	
	
	# Login function
	private function loginexternal ()
	{
		# Pass on
		return $this->login (__FUNCTION__);
	}
	
	
	# Logout message
	private function logoutexternal ()
	{
		# Construct the HTML
		$html = "\n" . '<p>To log out, please close all instances of your web browser.</p>';
		
		# Show the HTML
		echo $html;
	}
	
	
	# Login function, only available if internalAuth is enabled
	private function logininternal (&$status = false)
	{
		# Run the validation and return the supplied e-mail
		$this->user = $this->internalAuthClass->login ($showStatus = true);
		
		# Assemble the HTML
		$html  = "\n<h2>" . $this->actions['logininternal']['description'] . '</h2>';
		$html .= $this->internalAuthClass->getHtml ();
		
		# Set the status
		$status = ($this->user);
		
		# Return the HTML
		return $html;
	}
	
	
	# Logout message, only available if internalAuth is enabled
	private function logoutinternal ()
	{
		# Log out and confirm this status
		$this->internalAuthClass->logout ();
		
		# Assemble the HTML
		$html  = $this->internalAuthClass->getHtml ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Register page
	private function register ()
	{
		# Log out and confirm this status
		$this->internalAuthClass->register ();
		
		# Assemble the HTML
		$html  = $this->internalAuthClass->getHtml ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Reset password page
	private function resetpassword ()
	{
		# Log out and confirm this status
		$this->internalAuthClass->resetpassword ();
		
		# Assemble the HTML
		$html  = $this->internalAuthClass->getHtml ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# Login account details page
	private function accountdetails ()
	{
		# Log out and confirm this status
		$this->internalAuthClass->accountdetails ();
		
		# Assemble the HTML
		$html  = $this->internalAuthClass->getHtml ();
		
		# Show the HTML
		echo $html;
	}
	
	
	# API documentation page
	public function apidocumentation ($introductionHtml = '')
	{
		# Create a list of API calls
		$apiCalls = $this->getApiCalls (true);
		
		# Ensure that apiCalls have been defined
		if (!$apiCalls) {
			$this->page404 ();
			return false;
		}
		
		# Start the HTML
		$html = "\n<p>This page details the API calls available.</p>";
		
		# Add introduction if any
		if (method_exists ($this, 'apidocumentationIntroduction')) {
			$html .= $this->apidocumentationIntroduction ();
		}
		
		# Add drop-down list
		$list = array ();
		foreach ($apiCalls as $apiCall => $documentationMethod) {
			$list[$apiCall] = "<a href=\"#{$apiCall}\">{$apiCall}</a>";
		}
		$html .= application::htmlUl ($list);
		
		# Add documentation for each API call
		foreach ($apiCalls as $apiCall => $documentationMethod) {
			
			# Add heading
			$html .= "\n<h2 class=\"apidocumentation\" id=\"{$apiCall}\"><a href=\"#{$apiCall}\">#</a> {$apiCall}</h2>";
			
			# State if no documentation
			if (!method_exists ($this, $documentationMethod)) {
				$html .= "\n<p><em>No documentation available yet.</em></p>";
				continue;
			}
			
			# Add documentation
			$html .= $this->{$documentationMethod} ();
		}
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to get the list of API calls
	private function getApiCalls ($documentationMode = false)
	{
		# Get the API calls defined by the application class
		$classMethods = get_class_methods ($this);
		$apiCalls = array ();
		foreach ($classMethods as $classMethod) {
			if (preg_match ('/^apiCall_([a-zA-Z]+)$/', $classMethod, $matches)) {
				$apiCall = $matches[1];
				if ($documentationMode) {
					$classMethod = str_replace ('apiCall_', 'apiCallDocumentation_', $classMethod);
				}
				$apiCalls[$apiCall] = $classMethod;		// e.g. foobar => apiCall_foobar
			}
		}
		
		# Return the list
		return $apiCalls;
	}
	
	
	# API (HTTP); needs to be extended
	#!# Make private once overriding callers have been migrated
	public function api ()
	{
		# Get the list of API calls
		$apiCalls = $this->getApiCalls ();
		
		# Ensure that apiCalls have been defined
		if (!$apiCalls) {
			$this->page404 ();
			return false;
		}
		
		# Initialise the API
		if ($method = $this->loadApi ($apiCalls, $error)) {
			
			# Extract any ID
			$id = (isSet ($_GET['id']) ? $_GET['id'] : NULL);
			
			# Obtain the data (which may be empty) from the API calls function; error is returned by reference
			$function = $apiCalls[$method];
			$data = $this->{$function} ($id, $error);	// i.e. uses class method defined as apiCall_foobar ($id, &$error = '')
		}
		
		# If an error occured, set the error as the output
		if ($error) {
			$data = array ('error' => $error);
		}
		
		# Compatibility for PHP<5.4 (while some servers still on PHP5.3)
		if (!defined ('JSON_UNESCAPED_SLASHES')) {define ('JSON_UNESCAPED_SLASHES', 64);}
		if (!defined ('JSON_PRETTY_PRINT')) {define ('JSON_PRETTY_PRINT', 128);}
		if (!defined ('JSON_UNESCAPED_UNICODE')) {define ('JSON_UNESCAPED_UNICODE', 256);}
		
		# Determine if a JSON-P callback is required
		$jsonpCallback = false;
		if (isSet ($_GET['callback'])) {
			$jsonpCallback = (strlen ($_GET['callback']) ? $_GET['callback'] : false);
			unset ($_GET['callback']);      // Avoid leakage into the application environment
		}
		
		# Encode the JSON
		$flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
		if ($this->settings['apiJsonPretty']) {
			$flags = JSON_PRETTY_PRINT | $flags;
		}
		$json = json_encode ($data, $flags);	// Enable pretty-print; see: http://www.vinaysahni.com/best-practices-for-a-pragmatic-restful-api#pretty-print-gzip
		
		# If a callback is specified, convert to JSON-P; See http://www.php.net/json-encode#95667 and https://stackoverflow.com/questions/1678214
		if ($jsonpCallback) {
			$json = $jsonpCallback . '(' . $json . ');';
		}
		
		# Send the data
		header ('Content-type: application/json; charset=UTF-8');
		echo $json;
	}
	
	
	# API loader function
	private function loadApi ($apiCalls, &$error = '')
	{
		# End if not enabled
		if (!$this->settings['apiUsername']) {
			$error = 'Application error: The API is not enabled.';
			return false;
		}
		
		# Determine if access is open
		$openAccess = ($this->settings['apiUsername'] === true);
		if (!$openAccess) {
			
			# Obtain the HTTP-supplied username and validate it
			if (!$httpAuthUsername = $this->requestHttpAuthUsername ()) {
				$error = 'A HTTP-supplied username has not been supplied.';	// Probably will never be shown, as a dialog box should be shown instead
				return false;
			}
			
			# Check the username matches
			if ($httpAuthUsername != $this->settings['apiUsername']) {
				$error = "The HTTP-supplied username ({$httpAuthUsername}) is not correct.";
				return false;
			}
		}
		
		# Ensure a method is supplied
		if (!isSet ($_GET['method']) || !strlen ($_GET['method'])) {
			$error = 'No API method was supplied.';
			return false;
		}
		
		# Extract the method
		$method = $_GET['method'];
		if (substr_count ($method, '.')) {
			$method = lcfirst (implode (array_map ('ucfirst', explode ('.', $method))));	// e.g. foo.bar => fooBar
		}
		
		# Ensure the method is supported
		if (!isSet ($apiCalls[$method])) {
			$error = 'Invalid API resource specified.';
			return false;
		}
		
		# Unset the action and method from _GET, as callers should not need this
		unset ($_GET['action']);
		unset ($_GET['method']);
		
		# Return the method to run
		return $method;
	}
	
	
	# Cron hook function for non-interactive processes; add using e.g.:
	# 0 * * * * wget -q -O - http://theusername:@example.com/baseUrl/cron/
	private function cron ()
	{
		# Ensure that a cronJobs function has been defined
		if (!method_exists ($this, 'cronJobs')) {
			echo "Application error: A cronJobs protected method has not been defined.";
			return false;
		}
		
		# End if not enabled
		if (!$this->settings['cronUsername']) {
			echo 'Application error: The cron system is not enabled.';
			return false;
		}
		
		# Ensure that certain characters have not been used as the username in the settings; see: http://stackoverflow.com/a/703341
		if (preg_match ('/[%:@$]/', $this->settings['cronUsername'])) {
			echo "Application error: The cron username setting is not correct.";
			return false;
		}
		
		# Obtain the HTTP-supplied username and validate it
		if (!$httpAuthUsername = $this->requestHttpAuthUsername ()) {
			echo 'A HTTP-supplied username has not been supplied.';	// Probably will never be shown, as a dialog box should be shown instead
			return false;
		}
		
		# Check the username matches
		if ($httpAuthUsername != $this->settings['cronUsername']) {
			echo "The HTTP-supplied username ({$httpAuthUsername}) is not correct.";
			return false;
		}
		
		# Run the cron jobs
		$this->cronJobs ();
	}
	
	
	# Function to trigger HTTP auth (by the application)
	private function requestHttpAuthUsername ()
	{
		# Obtain PHP-triggered HTTP Auth (mod_php)
		if (isset ($_SERVER['PHP_AUTH_USER'])) {
			return $_SERVER['PHP_AUTH_USER'];
		}
		
		# Obtain PHP-triggered HTTP Basic Auth (other servers) username if supplied; the password is ignored; see: http://evertpot.com/223/
		if (isset ($_SERVER['HTTP_AUTHENTICATION'])) {
			if (strpos (strtolower ($_SERVER['HTTP_AUTHENTICATION']), 'basic') === 0) {
				list ($username, $passwordIrrelevant) = explode (':', base64_decode (substr ($_SERVER['HTTP_AUTHORIZATION'], 6)));
				return $username;
			}
		}
		
		# Trigger HTTP Basic Auth
		header ('WWW-Authenticate: Basic realm="Specify the username, and anything as the password."');
		header ('HTTP/1.0 401 Unauthorized');
		
		# Return false
		return false;
	}
	
	
	# Function to provide an 'Are you sure?' form
	public function areYouSure ($message, $confirmation, &$html)
	{
		# Start the HTML
		$html = '';
		
		# Create the form
		require_once ('ultimateForm.php');
		$form = new form (array (
			'formCompleteText' => false,
			'nullText' => false,
			'div' => 'graybox',
			'displayRestrictions' => false,
			'requiredFieldIndicator' => false,
		));
		$form->heading ('p', $message);
		$form->checkboxes (array (
			'name'				=> 'confirmation',
			'title'				=> 'Confirm',
			'values'			=> array ($confirmation),
			'required'			=> true,	// Ensures that a submission must be ticked for the form to be successful
		));
		
		# Process the form
		$result = $form->process ($html);
		
		# Return status
		return $result;
	}
	
	
	# Function to provide a general-purpose importing user interface
	public function importUi ($baseFilenames, $importTypes = array ('full' => 'FULL import'), $fileCreationInstructionsHtml, $fileExtension = 'xml', $echoHtml = true)
	{
		# Allow long-running processes
		ini_set ('max_execution_time', 0);
		
		# Start the HTML
		$html = '';
		
		# Ensure that the import routine has been defined in the client program
		if (!method_exists ($this, 'doImport')) {
			$html .= "\n" . '<p class="warning">Importing is not enabled.</p>';
			echo $html;
			return;
		}
		
		# Add support for application root in the exports directory setting
		$this->settings['exportsDirectory'] = str_replace ('%applicationRoot', $this->applicationRoot, $this->settings['exportsDirectory']);
		
		# Define the directory
		$this->exportsDirectory = $this->settings['exportsDirectory'];
		
		# Determine the dated filenames for each expected files
		$today = date ('Ymd');
		$expectedFiles = array ();
		foreach ($baseFilenames as $baseFilename) {
			$expectedFiles[$baseFilename] = $baseFilename . $today . '.' . $fileExtension;
		}
		
		# Start the HTML with instructions
		$html .= "\n" . '<p>Imports can be done using this form.</p>';
		$html .= "\n" . '<ol class="spaced">';
		$html .= "\n\t" . '<li><p><strong>Create the exports</strong> as follows:</p>';
		$html .= "\n\t\t" . '<div class="graybox">';
		$html .= "\n\t\t\t" . $fileCreationInstructionsHtml;
		$html .= "\n\t\t" . '</div></li>';
		$html .= "\n\t" . "<li><p><strong>Upload " . ($this->settings['importsSectionsMode'] ? 'each available export file' : 'the export files') . "</strong> to this website, using this form. Note that this can take several minutes, so please be patient.</p>";
		$html .= $this->importUploadFilesForm ($expectedFiles);
		$html .= "\n\t" . '</li>';
		$html .= "\n\t" . '<li><p><strong>Run the import</strong> using the form below. This will reset the data in this system.</p>';
		$html .= "\n\t\t" . '<div class="graybox">';
		$html .= $this->importControl ($expectedFiles, $importTypes, $fileExtension);
		$html .= "\n\t\t" . '</div></li>';
		$html .= "\n\t" . '</li>';
		$html .= "\n" . '</ol>';
		
		# Show log file if present
		$html .= $this->importLogHtml ();
		
		# Show the HTML if required
		if ($echoHtml) {
			echo $html;
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to show the import log
	public function importLogHtml ($title = 'Import log')
	{
		# End if no import logging
		if (!$this->importLog) {return false;}
		
		# End if no file
		if (!is_file ($this->importLog)) {return false;}
		
		# Assemble the HTML
		$html  = "\n<hr />";
		$html .= "\n<h3>{$title}:</h3>";
		$html .= "\n<pre>";
		$html .= file_get_contents ($this->importLog);
		$html .= "\n</pre>";
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to create an upload form for importing
	private function importUploadFilesForm ($expectedFiles)
	{
		# Start the HTML
		$html = '';
		
		# Create the form
		$form = new form (array (
			'submitButtonText' => 'Upload!',
			'name' => 'upload',
			'div' => false,
			'displayRestrictions' => false,
			'formCompleteText' => false,
			'requiredFieldIndicator' => false,
			'submitButtonAccesskey' => false,
		));
		
		$i = 0;
		foreach ($expectedFiles as $basename => $filename) {
			$form->upload (array (
				'name'					=> 'file' . $i++,
				'title'					=> $basename . ' file',
				'directory'				=> $this->exportsDirectory,
				// 'output'				=> array ('processing' => 'compiled'),
				'required'				=> (!$this->settings['importsSectionsMode']),
				'enableVersionControl'	=> true,
				'forcedFileName'		=> pathinfo ($filename, PATHINFO_FILENAME),
				'allowedExtensions'		=> array (pathinfo ($filename, PATHINFO_EXTENSION)),
				'lowercaseExtension'	=> true,
			));
		}
		
		# Process the form and confirm success
		if ($result = $form->process ($html)) {
			$html = "\n" . "<p>{$this->tick} The " . (count ($expectedFiles) == 1 ? 'file' : 'files') . ' should now be listed in the import box below.</p>';
		}
		
		# Surround with a box
		$html = "\n<div class=\"graybox\">\n" . $html . "\n</div>";
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to control the actual import
	private function importControl ($expectedFiles, $importTypes, $fileExtension)
	{
		# Start the HTML
		$html = '';
		
		# Determine the export file sets, or end
		if (!$exportFiles = $this->importAvailableFileSets ($expectedFiles)) {
			$html .= "\n<div class=\"graybox\">";
			if (count ($expectedFiles) > 1) {
				$html .= "\n\t<p class=\"warning\">No export file sets were found. Please follow the instructions above to create a set.</p>";
			} else {
				$html .= "\n\t<p class=\"warning\">No export files were found. Please follow the instructions above to create one.</p>";
			}
			$html .= "\n</div>";
			return $html;
		}
		
		# Ensure another import is not running
		if ($importHtml = $this->importInProgress ()) {
			$html .= $importHtml;
			return $html;
		}
		
		# Create the form
		if (!$result = $this->importRunForm ($exportFiles, $importTypes, $html)) {
			return $html;
		}
		
		/*
		# State that the import is now running
		$html = "\n<p>Import now running (can take 5-10 minutes)&hellip;</p>";
		
		# Flush the HTML so far
		echo $html;
		ob_flush ();
		flush ();
		*/
		
		# Determine the chosen files
		$files = array ();
		if ($this->settings['importsSectionsMode']) {
			preg_match ('/^(.+)([0-9]{8}\.(.+))$/', $result['date'], $matches);
			$basename = $matches[1];
			$files[$basename] = $this->exportsDirectory . $result['date'];
		} else {
			foreach ($expectedFiles as $basename => $filename) {
				$files[$basename] = $this->exportsDirectory . $basename . $result['date'] . '.' . $fileExtension;
			}
		}
		
		# Write the lockfile
		file_put_contents ($this->lockfile, $result['importtype'] . ' ' . $_SERVER['REMOTE_USER'] . ' ' . date ('Y-m-d H:i:s'));
		
		# Start a timer
		$startTime = time ();
		
		# Start the log
		$this->logger ("Starting {$result['importtype']} import (started by {$this->user})", $reset = true);
		
		# Run the import
		$done = $this->doImport ($files, $result['importtype'], $html /* amended by reference */);
		
		# Determine duration
		$finishTime = time ();
		$seconds = $finishTime - $startTime;
		if ($seconds > (60*60)) {
			$duration = round (($seconds / (60*60)), 2) . ' hours';
		} else if ($seconds > 60) {
			$duration = round (($seconds / 60), 1) . ' minutes';
		} else {
			$duration = round ($seconds, 1) . ' seconds';
		}
		
		# Show how long the import took
		if ($done) {
			$html .= "\n<p>{$this->tick} The import took: " . $duration . '.</p>';
		}
		
		# Log end
		$this->logger ("Import finished (took {$duration})");
		
		# Remove the lockfile
		unlink ($this->lockfile);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to get the latest export file sets
	private function importAvailableFileSets ($expectedFiles, &$errorHtml = '')
	{
		# Ensure the directory is readable
		if (!is_readable ($this->exportsDirectory)) {
			$errorHtml = "\n<p class=\"warning\">The directory {$this->exportsDirectory} could not be accessed. Please check the file permissions.</p>";
			return false;
		}
		
		# Obtain an array of all files in the directory or end
		require_once ('directories.php');
		if (!$files = directories::listFiles ($this->exportsDirectory, array (), true, $skipUnreadableFiles = true, $skipZeroLengthFiles = true)) {
			$errorHtml = "\n<p class=\"warning\">There are no files uploaded yet.</p>";
			return false;
		}
		
		# Group by date
		$groups = array ();
		foreach ($files as $filename => $attributes) {
			#!# $expectedFiles currently assumed not to have special preg characters
			if (preg_match ('/^(' . implode ('|', array_keys ($expectedFiles)) . ')([0-9]{8}).([a-z]+)$/', $filename, $matches)) {
				$date = $matches[2];
				$type = $matches[1];
				$groups[$date][$type] = $filename;
			}
		}
		
		# Sort most-recent first
		krsort ($groups);
		
		# In imports sections mode, keep grouped by date as a hierarchical select control, but in the visible string, show the date string and the type
		$listing = array ();
		if ($this->settings['importsSectionsMode']) {
			foreach ($groups as $date => $files) {
				$dateGroupLabel = $this->importDateString ($date);
				asort ($files);
				foreach ($files as $type => $file) {
					$listing[$dateGroupLabel][$file] = $type . " ({$dateGroupLabel})";
				}
			}
			
		# Filter to those having complete groups only, resulting in array(date=>dateString, date=>dateString, ...)
		} else {
			foreach ($groups as $date => $files) {
				if (!array_diff (array_keys ($expectedFiles), array_keys ($files))) {
					$listing[$date] = $this->importDateString ($date);
				}
			}
		}
		
		# Return the file path
		return $listing;
	}
	
	
	# Function to get the export date as a string
	private function importDateString ($date)
	{
		# Determine the filename
		$date = date_create_from_format ('Ymd', $date);
		$string = date_format ($date, 'jS F Y');
		
		# Return the string
		return $string;
	}
	
	
	# Function to create the run import form
	private function importRunForm ($exportFiles, $importTypes, &$html)
	{
		# Determine the most recent file if required
		if ($this->settings['importsSectionsMode']) {
			$mostRecent = false;
		} else {
			$exportFilesKeys = array_keys ($exportFiles);
			$mostRecent = reset ($exportFilesKeys);
		}
		
		# Create the form
		$form = new form (array (
			'submitButtonText' => 'Begin import!',
			'div' => 'ultimateform horizontalonly',
			'name' => 'import',
		));
		$form->select (array ( 
		    'name'		=> 'date', 
		    'title'		=> ($this->settings['importsSectionsMode'] ? 'Select export file' : 'Select export files dated'),
		    'values'	=> $exportFiles,
		    'required'	=> true,
			'default'	=> $mostRecent,
		));
		if (count ($importTypes) != 1) {
			$form->select (array (
			    'name'		=> 'importtype', 
			    'title'		=> 'Import type', 
			    'values'	=> $importTypes,
			    'required'	=> true,
				'default'	=> 'full',
			));
		}
		
		# End if not submitted
		$result = $form->process ($html);
		
		# If only one import type, return that
		if ($result) {
			if (count ($importTypes) == 1) {
				reset ($importTypes);
				$result['importtype'] = key ($importTypes);
			}
		}
		
		# Return the status
		return $result;
	}
	
	
	# Function to show import status
	public function importInProgress ($detectStaleLockfileHours = 24, $blockUi = true)
	{
		# Return false if no lockfile
		if (!file_exists ($this->lockfile)) {return false;}
		
		# Get the username and timestamp from the lockfile
		$lockfileText = trim (file_get_contents ($this->lockfile));
		list ($type, $username, $timestamp) = explode (' ', $lockfileText, 3);
		
		# Assemble the HTML
		$html  = "\n<div class=\"graybox\">";
		$html .= "\n\t<p class=\"warning\">A '<em>{$type}</em>' import (which was started by {$username} at {$timestamp}) is currently running" . ($blockUi ? '; please try again later' : '') . '.</p>';
		if ($blockUi) {
			$html .= "\n\t<p class=\"warning\">This page will automatically refresh to show when the import is finished.</p>";
		}
		if (!$blockUi) {
			$html .= "\n\t<p class=\"warning\">Be aware that data shown is likely to be in an inconsistent state, and system performance may be slow, until this import completes.</p>";
		}
		$html .= "\n</div>";
		
		# Refresh the page regularly
		if ($blockUi) {
			$html .= "\n<meta http-equiv=\"refresh\" content=\"10;URL=" . htmlspecialchars ($_SERVER['_PAGE_URL']) . "\">";
		}
		
		# Detect a stale lockfile
		if ($detectStaleLockfileHours) {
			$startTime = strtotime ($timestamp);
			$now = time ();
			$maximumPeriod = $detectStaleLockfileHours * 60 * 60;
			if (($now - $startTime) > $maximumPeriod) {
				$adminMessage = "A stale lockfile, created at {$timestamp}, was detected.\n\n{$this->lockfile}";
				$this->reportError ($adminMessage, $publicMessage = false);
			}
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Data point
	public function data ()
	{
		echo '<p>This URL can be assigned a function data() for transmission of data.</p>';
	}
	
	
	# Logout message
	private function loggedout ()
	{
		echo '
		<p>You have logged out of Raven for this site.</p>
		<p>If you have finished browsing, then you should completely exit your web browser. This is the best way to prevent others from accessing your personal information and visiting web sites using your identity.</p>
		<p>If for any reason you can\'t exit your browser you should first log-out of all other personalised sites that you have accessed and then <a href="' . $this->settings['ravenCentralLogoutUrl'] . '" target="_blank">logout from the central authentication service</a>.</p>';
	}
	
	
	# Function to provide a help page
	public function help ()
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
	public function getName ($user)
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
	public function admin ()
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
	private function history ()
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
	
	
	# Function to provide a logger
	public function logger ($message, $reset = false)
	{
		# End if import log not enabled
		if (!$this->importLog) {return;}
		
		# Construct the string
		$string = date ('Y-m-d H:i:s') . ': ' . $message . "\r\n";
		
		# Append to the logfile (or start fresh if resetting)
		file_put_contents ($this->importLog, $string, ($reset ? 0 : FILE_APPEND));
	}
	
	
	# Function to enable memory profiling; see: https://github.com/arnaud-lb/php-memory-profiler
	public function profileMemoryStart ()
	{
		# End if not enabled
		if (!function_exists ('memprof_enable')) {return false;}
		
		# Enable memory profiling
		memprof_enable ();
	}
	
	
	# Function to enable memory profiling
	public function profileMemoryEnd ($filenameBase = 'memory.heap' /* from baseUrl; will also have image file extension added after */)
	{
		# Available only to admins
		if (!$this->userIsAdministrator) {return false;}
		
		# End if not enabled
		if (!function_exists ('memprof_dump_pprof')) {return false;}
		
		# Create the memory dump
	    $time = microtime (true);
		$location = $this->baseUrl . '/' . $filenameBase;
		$file = $_SERVER['DOCUMENT_ROOT'] . $location;
	    $fh = fopen ($file, 'w');
	    memprof_dump_pprof ($fh);
	    fclose ($fh);
		
		# Create an image; see: http://stackoverflow.com/questions/13699297/how-to-use-pprof-in-go-program
		$imageLocation = $location . '.gif';
		$image = $_SERVER['DOCUMENT_ROOT'] . $imageLocation;
		$command = "google-pprof --gif {$file} | cat > {$image}";
		shell_exec ($command);
		
		# Remove the raw memory dump file
		unlink ($file);
	    
		# Construct an HTML image tag, linking to the documentation
		$html  = "\n<h3>Memory usage:</h3>";
		$html .= "\n<a href=\"https://gperftools.github.io/gperftools/heapprofile.html\" target=\"_blank\"><img src=\"{$imageLocation}\"></a>";
		
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
			'div' => 'ultimateform settings',
			'reappear' => true,
			'formCompleteText' => false,
			'displayRestrictions' => false,
			'unsavedDataProtection' => true,
			'jQuery' => !$this->settings['jQuery'],	// Do not load if already loaded
			'cols' => 80,
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
	#!# This is a poor API
	public function feedback ($id_ignored = NULL, $error_ignored = NULL, $echoHtml = true)
	{
		# Start the HTML
		$html = "<p>We welcome your feedback on this facility. If you have any suggestions, questions or comments - whether positive or negative - we'd like to hear from you. Please use the form below to send us your feedback.</p>";
		
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
			'disallow'      => 'https?:/',
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
		
#!# Temporary anti-spam measure, 140729, mvl22
if ($unfinalisedData = $form->getUnfinalisedData ()) {
	if (preg_match ('/^([a-z]+)@gmail.com$/', $unfinalisedData['contacts'])) {      // E-mails always coming from [a-z]+@gmail.com
		if (preg_match ("~http://([^\s]+)$~i", trim ($unfinalisedData['message']))) {   // Message always ends with a link
			$form->registerProblem ('antispam', 'Please remove web addresses from your submission.');
		}
	}
}

		# Set the processing options
		$form->setOutputEmail ($this->settings['feedbackRecipient'], $this->settings['administratorEmail'], "{$this->settings['applicationName']} contact form", NULL, $replyToField = 'contacts');
		$form->setOutputScreen ();
		
		# Process the form
		$result = $form->process ($html);
		
		# Show the HTML if required
		if ($echoHtml) {
			echo $html;
		}
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to provide cookie-based login internally
	private function loadInternalAuth ()
	{
		# Assemble the settings to use
		$internalAuthSettings = array (
			'saltLegacyHashes'					=> $this->settings['internalAuthSalt'],
			'baseUrl'							=> $this->baseUrl,
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
	public function administrators ($null = NULL, $boxClass = 'graybox', $showFields = array ('active' => 'Active?', 'receiveEmail' => 'Receive e-mail?', 'email' => 'E-mail', 'privilege' => 'privilege', 'name' => 'name', 'forename' => 'forename', 'surname' => 'surname', ))
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
			$result['email'] = (isSet ($result['email']) ? $result['email'] : $result[$usernameField] . (substr_count ($result[$usernameField], '@') ? '' : "@{$this->settings['emailDomain']}"));
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
			
			# Rewrite the auth file
			$this->rewriteAuthFile (array_keys ($this->administrators));
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
					
					# Rewrite the auth file
					$this->rewriteAuthFile (array_keys ($this->administrators));
					
				} else {
					$html .= "\n<p class=\"warning\">There was a problem deleting the administrator. (Probably 'delete' privileges are not enabled for this table. Please contact the main administrator of the system.</p>";
				}
			}
		}
		$html .= "\n" . '</div>';
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to rewrite the auth file
	private function rewriteAuthFile ($administrators)
	{
		# End if not required
		if (!$this->settings['authFileGroup']) {return;}
		
		# Determine the group name
		if ($this->settings['authFileGroup'] === true) {
			$this->settings['authFileGroup'] = 'administrators';
		}
		
		# Add any additional users to the administrators list
		#!# Changes to the list will only take effect when a full admin is added/removed
		if (isSet ($this->authFileGroupAdditionalUsers)) {
			$administrators = array_merge ($administrators, $this->authFileGroupAdditionalUsers);
			$administrators = array_unique ($administrators);
		}
		
		# Determine the filename
		$filename = $_SERVER['DOCUMENT_ROOT'] . $this->baseUrl . '/.ht-users';
		
		# Assemble the file contents
		$groupAuth = $this->settings['authFileGroup'] . ': ' . implode (' ', $administrators);
		
		# Write the file, replacing whatever is there currently
		#!# Error handling needed
		file_put_contents ($filename, $groupAuth);
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
	
	
	# CORS sending headers
	public function corsHeaders ()
	{
		# Send allowed headers for authorised sites; see: http://stackoverflow.com/a/1850482 and http://caniuse.com/#search=CORS
		if (isSet ($_SERVER['HTTP_ORIGIN'])) {	// NB HTTP_ORIGIN is HTML5-specific so not yet available in all browsers
			if (in_array ($_SERVER['HTTP_ORIGIN'], $this->settings['corsDomains'])) {
				header ('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
			}
		}
	}
	
	
	# 404 page
	#!# Needs to have a customised message mode
	public function page404 ($includePureContentHeaderFooter = false)
	{
		# Start the HTML
		$html = '';
		
		# Send correct HTTP header
		application::sendHeader (404);
		
		# End here
		#!# Currently this is visible within the tabs
		if ($this->settings['page404']) {
			if ($includePureContentHeaderFooter) {
				include ('pureContentWrapper.php');
			}
			include ($this->settings['page404']);
			if ($includePureContentHeaderFooter) {
				include ('sitetech/appended.html');
			}
		} else {
			$html .= "\n<h2>Page not found</h2>";
			$html .= "\n<p>Sorry, that page was not found. Please check the URL or use the menu to navigate elsewhere.</p>";
			echo $html;
		}
		
		return false;
	}
	
	
	# Home page, expected to be overridden
	public function home ()
	{
		$html  = "\n<p>Welcome</p>";
		
		# Show the HTML
		echo $html;
	}
	
	
	# Admin editing section, substantially delegated to the sinenomine editing component
	# Needs adding to httpd.conf, where $applicationBaseUrl is not slash-terminated
	#	Use MacroSinenomineEmbeddedWholeDb "$applicationBaseUrl" "/data" "editing"
	#!# Need to add support for 'allow' to make easier to allow only specific tables
	public function editing ($attributes = array (), $deny = false /* or supply an array */, $sinenomineExtraSettings = array ())
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
		
		# Merge in other settings, with the supplied values taking priority
		$settings = array_merge ($settings, $sinenomineExtraSettings);
		
		# Load and run the database editing
		require_once ('sinenomine.php');
		$sinenomine = new sinenomine ($settings, $this->databaseConnection);
		
		# Set constraints
		#!# This is a poor API - would be better to require to supply nested
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
	
	
	# Function to create filtering controls
	public function filteringControls ($fields, $path, &$html = '')
	{
		# Redirect to clear non-submitted values, to keep the URLs as simple as possible
		#!# This would be useful to have in ultimateForm natively, as that would remove the need to specify external GET fields (action/runpage)
		$get = $_GET;
		unset ($get['action']);
		unset ($get['item']);
		$redirect = false;
		foreach ($get as $key => $value) {
			if (!strlen ($value)) {
				unset ($get[$key]);
				$redirect = true;
			}
		}
		if ($redirect) {
			$url = $_SERVER['_SITE_URL'] . $path . ($get ? '?' . http_build_query ($get) : '');
			$html = application::sendHeader (302, $url, true);
			return $html;
		}
		
		# Create a form
		require_once ('ultimateForm.php');
		$form = new form (array (
			'formCompleteText' => false,
			'div' => 'ultimateform graybox',
			'id' => 'filters',
			'display' => 'template',
			'displayTemplate' => "{[[PROBLEMS]]} <p>Filter to only: &nbsp; {" . implode ('} &nbsp;{', array_keys ($fields)) . "} &nbsp;{[[SUBMIT]]} &nbsp; <span class=\"clear\">or <a href=\"{$path}\">clear filters</a></span></p>",
			'name' => false,
			'get' => true,
			'reappear' => true,
			'submitButtonAccesskey' => false,
			'submitButtonText' => 'Apply filters',
			'requiredFieldIndicator' => false,
			'nullText' => false,
			'autosubmit' => true,
		));
		foreach ($fields as $fieldname => $field) {
			$field['name'] = $fieldname;
			if (!isSet ($field['placeholder'])) {
				$field['placeholder'] = $field['title'];
			}
			if (isSet ($field['values'])) {
				$field['nullText'] = $field['title'];
				$form->select ($field);
			} else {
				$form->search ($field);
			}
		}
		$form->process ($html);
		
		# Determine the filters, giving a result suitable for use in a WHERE clause
		$conditions = application::arrayFields ($get, array_keys ($fields));
		
		# Convert conditions to LIKE %...%
		foreach ($conditions as $field => $value) {
			if (isSet ($fields[$field]['like']) && $fields[$field]['like']) {
				if (strlen ($value)) {
					$conditions[$field] = '%' . $value . '%';
				}
			}
		}
		
		# Return the filter result
		return $conditions;
	}
	
	
	# Function to initialise templating
	private function initialiseTemplating ()
	{
		# End if not enabled
		if (!$this->settings['useTemplating']) {return NULL;}
		
		# Add support for application root in the template setting
		$this->settings['templatesDirectory'] = str_replace ('%applicationRoot', $this->applicationRoot, $this->settings['templatesDirectory']);
		
		# Load templating
		require_once ('smarty/libs/Smarty.class.php');
		$templateHandle = new Smarty ();
		// $templateHandle->caching = 0;
		// $templateHandle->force_compile = true;
		$templateHandle->setTemplateDir ($this->settings['templatesDirectory']);
		$templateHandle->setCompileDir ($this->applicationRoot . '/tmp/templates_c/');
		$tplDirectory = $this->applicationRoot . "/tmp/templates_tpl/";
		if (!is_dir ($tplDirectory)) {
			mkdir ($tplDirectory, 0775, true);
		}
		$templateHandle->assign ('templates_tpl', $tplDirectory);
		
		# Register plugin functions
		foreach ($this->templateFunctions as $function) {
			$templateHandle->registerPlugin ('modifier', $function, array ($this, $function));
		}
		
		# Return the handle
		return $templateHandle;
	}
	
	
	# Function to provide templatisation
	public function templatise ($templateFile = false /* Normally assigned automatically based on the action */)
	{
		# Assign each provided placeholder
		foreach ($this->template as $placeholder => $fragmentHtml) {
			$this->templateHandle->assign ($placeholder, $fragmentHtml);
		}
		
		# Default to the action's template
		if (!$templateFile) {
			$templateFile = $this->action . '.tpl';
		}
		
		# Compile the HTML
		$html = $this->templateHandle->fetch ($templateFile);
		
		# Return the HTML
		return $html;
	}
	
	
	# Function to list and edit templates
	private function templates ($template)
	{
		# Start the HTML
		$html = '';
		
		# Ensure the directory is writable
		$this->settings['templatesDirectory'];
		if (!is_writable ($this->settings['templatesDirectory'])) {
			$html .= "\n<p class=\"error\">The application is not set up correctly - the template directory at <tt>{$this->settings['templatesDirectory']}</tt> is not writable.</p>";
			echo $html;
			return;
		}
		
		# Get the list of templates or end
		#!# Add support for nested directories
		require_once ('directories.php');
		if (!$templateFiles = directories::listFiles ($this->settings['templatesDirectory'], array ('tpl'), $directoryIsFromRoot = true)) {
			$html .= "\n<p>There are no templates.</p>";
			echo $html;
			return;
		}
		
		# Arrange the data as path => file
		$templates = array ();
		foreach ($templateFiles as $filename => $attributes) {
			$name = $attributes['name'];	// Without extension, e.g. item.tpl will return item
			$templates[$name] = $this->settings['templatesDirectory'] . $filename;
		}
		
		# If a template has been selected for editing, end if not present in the registry of templates
		if ($template) {
			if (!isSet ($templates[$template])) {
				$this->page404 ();
				return false;
			}
		}
		
		# Create a listing if no template selected
		if (!$template) {
			$list = array ();
			foreach ($templates as $name => $filename) {
				$list[] = "<a href=\"{$name}.html\">{$name}</a>";
			}
			$html .= "\n<p>Click on a template below to edit it.</p>";
			$html .= application::htmlUl ($list);
			echo $html;
			return;
		}
		
		# Confirm the template file
		$templateFile = $templates[$template];
		
		# Display a flash message if set
		#!# Flash message support needs to be added to ultimateForm natively, as this is a common use-case
		$successMessage = 'The template has been updated, and the previous version has been archived.';
		if ($flashValue = application::getFlashMessage ('submission', $this->baseUrl . '/')) {
			$message = "\n" . "<p>{$this->tick} <strong>" . $successMessage . '</strong></p>';
			$html .= "\n<div class=\"graybox flashmessage\">" . $message . '</div>';
		}
		
		# Create the form
		$form = new form (array (
			'formCompleteText' => false,
			'reappear'	=> true,
			'display' => 'paragraphs',
			'autofocus' => true,
			'unsavedDataProtection' => true,
			'whiteSpaceTrimSurrounding' => false,
		));
		$form->heading ('', '<div class="graybox">');
		$form->heading ('p', "Here you can edit the <em>{$template}</em> template.");
		$form->heading ('p', 'The template language is <strong>Smarty</strong>. A <a href="http://www.smarty.net/docs/en/language.basic.syntax.tpl" target="_blank">basic syntax guide</a> and a <a href="http://www.smarty.net/docs/en/" target="_blank">full syntax reference guide</a> are available on the Smarty website.');
		$form->heading ('', '</div>');
		$form->textarea (array (
			'name'		=> 'template',
			'title'		=> 'Template',
			'required'	=> true,
			'rows'		=> 35,
			'cols'		=> 115,
			'default'	=> file_get_contents ($templateFile),
			'wrap'		=> 'off',
		));
		
		# Validate the parser syntax
		if ($unfinalisedData = $form->getUnfinalisedData ()) {
			if ($unfinalisedData['template']) {
				try {
					$this->templateHandle->fetch ('eval:'. $unfinalisedData['template']);
				} catch (SmartyException $e) {
					// var_dump ($e);
					$form->registerProblem ('compilefailure', 'The template engine reported a syntax error on line ' . $e->line . ': <strong><tt>' . $e->desc . '</tt></strong>.');
				}
			}
		}
		
		# Process the form
		if (!$result = $form->process ($html)) {
			echo $html;
			return;
		}
		
		# Archive the original file
		$archiveFile = $templateFile . '.' . date ('Ymd-His') . '.' . $this->user;
		rename ($templateFile, $archiveFile);
		
		# Save the new file
		file_put_contents ($templateFile, $result['template']);
		
		# Set a flash message
		$function = __FUNCTION__;
		$redirectTo = "{$_SERVER['_SITE_URL']}{$this->baseUrl}/{$this->actions[$function]['url']}{$template}.html";
		$redirectMessage = "\n{$this->tick}" . ' <strong>' . $successMessage . '</strong></p>';
		application::setFlashMessage ('submission', '1', $redirectTo, $redirectMessage, $this->baseUrl . '/');
		
		# Confirm success, resetting the HTML, and show the submission
		$html = application::sendHeader (302, $redirectTo, true);
		
		# Show the HTML
		echo $html;
	}
	
	
	# Function to generate a readable stacktracks
	public function stackTrace ($shortenPaths = true)
	{
		# Obtain the string
		$e = new Exception ();
		$trace = explode ("\n", $e->getTraceAsString ());	// Unicode-compliance fixed in: https://bugs.php.net/61362
		
		# Reverse array to make steps line up chronologically
		$trace = array_reverse ($trace);
		array_shift ($trace); // remove {main}
		array_pop ($trace); // remove call to this method
		
		# Determine path replacements, to have shorter
		$applicationPaths = array (
			$this->applicationRoot . '/'				=> '',
			$_SERVER['DOCUMENT_ROOT'] . $this->baseUrl	=> '',
		);
		
		# Construct the result string
		$lines = array ();
		$i = 0;
		foreach ($trace as $line) {
			if ($shortenPaths) {
				$line = strtr ($line, $applicationPaths);
			}
			$line = preg_replace ('/^(.+): (.+)$/', "\\1:\n\t\\2", $line);
			$lines[] = $i++  . ')' . substr ($line, strpos ($line, ' ')); // replace '#someNum' with '$i)', set the right ordering
		}
		$result = "\n" . implode ("\n", $lines);
		
		# Surround as print_r
		$result = application::dumpData ($result, false, $return = true);
		
		# Echo the result
		echo $result;
	}
	
	
	# Function to send administrative alerts
	public function reportError ($adminMessage, $publicMessage = 'Apologies, but a problem with the setup of this system was found. The webmaster has been made aware of this problem and will correct the misconfiguration as soon as possible. Please kindly check back later.', $databaseModeData = false, $class = 'warning')
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
