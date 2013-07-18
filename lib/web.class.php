<?php

define ("DEFAULT_PAGE", "homepage");

class Web {

	// Dir
	public static $dir;

	// Title
	public static $settings = array();

	// DB handler
	public static $db;

	// DEBUG MOD
	public static $debug = false;

	// Active page
	protected $page = array();

	// Theme handler
	protected $theme;

	// FOR NOW: hash with error messages
	public static $errors = array();

	// Modules
	protected $modules = array (
		'head' => '',
		'content' => '',
		'absolute_path' => ''
	);

	/* WEB inicialization
     * @param $_config configuration data
    */ 
	public function __construct($_config) {

		// Set debug mode
		self::$debug = $_config['web']['debug'];

		// Set active page
		$act_page = (!empty($_GET['page'])) ?  $_GET['page'] : DEFAULT_PAGE;

		// Establish db connection
		self::$db = new Database($_config['db']['server'], $_config['db']['dbname'], $_config['db']['username'], $_config['db']['password'], $_config['db']['charset'], $_config['db']['prefix']);

		// Configure website from database data
		$this->loadWebConfig($_config['web']['settings']);

		// Get page from db
		$this->page = $this->loadPage($act_page);

		// Inicialize theme
		$this->theme = $this->webThemeInit();

		// Inicialize modules
		$this->webModulesInit();

		// DEBUG: show errors
		(self::$debug ) ? var_dump(self::$errors) : null;

	}

	/* Load webconfig from database, if empty, use configuration file settings
	 * @param $config configuration data array - specific for admin and web
	*/
	protected function loadWebConfig($settings, $admin = false) {

		// Set table where pages are
		$settingsTable = (!$admin) ? "settings" : "admin_settings";

		// Select web settings from database	
		try {
		
			self::$db->query("SELECT title, description, keywords, author, copyright, theme FROM ".database::$prefix.$settingsTable);
		
			// If no row was selected, use config settings
			if (!(self::$settings = self::$db->single()))
				self::$settings = $settings;

			// If specific settings is empty in DB, load from config
			foreach($settings as $key => $value) {
				if (empty(self::$settings[$key]))
					self::$settings[$key] = $settings[$key];
			}

		}

		// If db error, use configuration file settings
		catch (PDOException $e) {
			self::$errors['db'] = $e->getMessage();
			self::$settings = $settings;
		}

		// DEBUG OUTPUT
		(self::$debug ) ? var_dump(self::$settings) : null;
	}

	/* Load page data from database
	 * in case of missing page use default missing page data
	 * @param $page active page
	 * @return hash with page info
	*/
	protected function loadPage($page, $admin = false) {

		// Set table where pages are
		$pageTable = (!$admin) ? "page" : "admin_page";

		try {
			// Load page data from DB
			self::$db->query("SELECT id, name, title, theme FROM ".database::$prefix . $pageTable ." WHERE name = :pagename");
			self::$db->bind(":pagename", $page);

			$results = self::$db->single();

			if (empty($results))
				return $this->missingPage($page, $admin);
		
		}

		// If db error, use configuration file settings
		catch (PDOException $e) {
			self::$errors['db'] = $e->getMessage();
			// Generate missing page
			return $this->missingPage($page, $admin);
		}

		(self::$debug ) ? var_dump(self::$db->single()) : null;

		return $results;
	}

	/* Setup missing page
	 * TODO: GENERATE MISSING PAGE
	 * @param $page page name
	 * @param $admin admin handler
	*/
	protected function missingPage($page, $admin) {

		echo "sdasdsad";
		$missingPage['id'] = -1;
		$missingPage['theme'] = '404_notfound';

		return $missingPage;

	}

	/* Init modules on webpage
	*/
	protected function webModulesInit() {
		
		// Loop inicializing modules
		foreach($this->modules as $key => $value) {
			$this->modules[$key] = $this->theme->initModule($key, $this->page);	
		}
	}

	/* Show website output
	 * @return string with output data
	 */
	public function showWebsite() {
		return $this->theme->getThemeData();
	}

	/* Instanciate webtheme
	 * -- TODO: DEFENSIVE PROGRAMMING
     * @param $webconfig webconfiguration data
    */ 
	private function webThemeInit() {

		// DEBUG OUTPUT
		(self::$debug ) ? var_dump($this->page) : null;

		// Instanciate theme
		return new Theme(false, (!empty($this->page['theme'])) ? $this->page['theme'] : null);

	}
}


?>