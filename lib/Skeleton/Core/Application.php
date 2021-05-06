<?php
/**
 * Skeleton Core Application class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core;

class Exception_Unknown_Application extends \Exception {}

abstract class Application {

	/**
	 * Application
	 *
	 * @var Application $application
	 * @access private
	 */
	private static $application = null;

	/**
	 * Path
	 *
	 * @var string $path
	 * @access public
	 */
	public $path = null;

	/**
	 * Event Path
	 *
	 * @var string $event_path
	 * @access public
	 */
	public $event_path = null;

	/**
	 * Name
	 *
	 * @var string $name
	 * @access public
	 */
	public $name = null;

	/**
	 * Hostname
	 *
	 * @var string $hostname
	 * @access public
	 */
	public $hostname = null;

	/**
	 * Matched hostname
	 * This variable contains the config value for the matched hostname
	 *
	 * @var string $matched_hostname
	 * @access public
	 */
	public $matched_hostname = null;

	/**
	 * Relative URI to the application's base URI
	 *
	 * @var string $request_relative_uri
	 * @access public
	 */
	public $request_relative_uri = null;

	/**
	 * Language
	 *
	 * @access public
	 * @var Language $language
	 */
	public $language = null;

	/**
	 * Config object
	 *
	 * @access public
	 * @var Config $config
	 */
	public $config = null;

	/**
	 * Events
	 *
	 * @access public
	 * @var array $events
	 */
	public $events = [];

	/**
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct($name) {
		$this->name = $name;
		$this->get_details();
	}

	/**
	 * Get details of application
	 *
	 * @access protected
	 */
	protected function get_details() {
		$config = clone Config::get();
		$this->config = $config;
		$application_path = realpath($config->application_dir . '/' . $this->name);

		if (!file_exists($application_path)) {
			throw new \Exception('Application with name "' . $this->name . '" not found');
		}
		$this->path = $application_path;
		$this->event_path = $this->path . '/event/';

		$this->load_config();

		if (class_exists('\Skeleton\I18n\Config') AND isset(\Skeleton\I18n\Config::$language_interface)) {
			$classname = \Skeleton\I18n\Config::$language_interface;
			if (!class_exists($classname)) {
				throw new \Exception('The language interface does not exists: ' . \Skeleton\I18n\Config::$language_interface);
			}
			$this->language = $classname::get_by_name_short($this->config->default_language);
		}
	}

	/**
	 * Load the config
	 *
	 * @access private
	 */
	protected function load_config() {
		if (!file_exists($this->path . '/config')) {
			throw new \Exception('No config directory created in app ' . $this->path);
		}

		/**
		 * Set some defaults
		 */
		$this->config->application_type = '\Skeleton\Core\Application\Web';

		$this->config->read_directory($this->path . '/config');
	}

	/**
	 * Run the application
	 *
	 * @access public
	 */
	abstract public function run();

	/**
	 * Check if an event exists
	 *
	 * @access public
	 * @param string $context
	 * @param string $action
	 * @return bool $exists
	 */
	public function event_exists($context, $action) {
		// Check if the event class is already loaded
		$class = null;

		if (isset($this->events[$context])) {
			$class = $this->events[$context];
		}

		if ($class === null) {
			if (file_exists($this->event_path . '/' . ucfirst($context) . '.php')) {
				require_once $this->event_path . '/' . ucfirst($context) . '.php';
				$classname = '\\App\\' . ucfirst($this->name) . '\\Event\\' . ucfirst($context);
				$class = new $classname;
				$this->events[$context] = $class;
			}
		}

		if ($class === null) {
			return false;
		}

		if (!is_callable([$class, $action])) {
			return false;
		}

		return true;
	}

	/**
	 * Call event
	 *
	 * @access public
	 * @param string $context
	 * @param string $action
	 */
	public function call_event($context, $action, $arguments = []) {
		if (!$this->event_exists($context, $action)) {
			throw new Exception('Cannot call event, event ' . $action . ' in context ' . $context . ' does not exists');
		}

		return call_user_func_array($this->get_event_callable($context, $action), $arguments);
	}

	/**
	 * Call event if exists
	 *
	 * @access public
	 * @param string $context
	 * @param string $action
	 */
	public function call_event_if_exists($context, $action, $arguments = []) {
		if (!$this->event_exists($context, $action)) {
			return;
		}

		return call_user_func_array($this->get_event_callable($context, $action), $arguments);
	}

	/**
	 * Get a callable for an event
	 *
	 * @access public
	 * @param string $context
	 * @param string $action
	 * @return array
	 */
	public function get_event_callable(string $context, string $action) {
		return [$this->events[$context], $action];
	}

	/**
	 * Get
	 *
	 * Try to fetch the current application
	 *
	 * @access public
	 * @return Application $application
	 */
	public static function get() {
		if (self::$application === null) {
			throw new \Exception('No application set');
		}

		return self::$application;
	}

	/**
	 * Set
	 *
	 * @access public
	 * @param Application $application
	 */
	public static function set(Application $application = null) {
		self::$application = $application;
	}

	/**
	 * Detect
	 *
	 * @param string $hostname
	 * @param string $request_uri
	 * @access public
	 * @return Application $application
	 */
	public static function detect($hostname, $request_uri) {

		// If we already have a cached application, return that one
		if (self::$application !== null) {
			return Application::get();
		}

		// If multiple host headers have been set, use the last one
		if (strpos($hostname, ', ') !== false) {
			list($hostname, $discard) = array_reverse(explode(', ', $hostname));
		}

		// Find matching applications
		$applications = self::get_all();
		$matched_applications = [];

		// Match via event
		foreach ($applications as $application) {
			if (!$application->event_exists('application', 'detect')) {
				continue;
			}
			if ($application->call_event('application', 'detect', [ $hostname, $request_uri ])) {
				$matched_applications[] = $application;
			}
		}

		// Regular matches
		foreach ($applications as $application) {
			if (in_array($hostname, $application->config->hostnames)) {
				$application->matched_hostname = $hostname;
				$matched_applications[] = $application;
			}
		}

		// If we don't have any matched applications, try to match wildcards
		if (count($matched_applications) === 0) {
			foreach ($applications as $application) {
				$wildcard_hostnames = $application->config->hostnames;
				foreach ($wildcard_hostnames as $key => $wildcard_hostname) {
					if (strpos($wildcard_hostname, '*') === false) {
						unset($wildcard_hostnames[$key]);
					}
				}

				if (count($wildcard_hostnames) == 0) {
					continue;
				}

				foreach ($wildcard_hostnames as $wildcard_hostname) {
					if (fnmatch($wildcard_hostname, $hostname)) {
						$clone = clone $application;
						$clone->matched_hostname = $wildcard_hostname;
						$matched_applications[] = $clone;
					}
				}
			}
		}

		// Set required variables in the matched Application objects
		foreach ($matched_applications as $key => $application) {
			 // Set the relative request URI according to the application
			if (isset($application->config->base_uri) and ($application->config->base_uri !== '/')) {
				$application->request_relative_uri = str_replace($application->config->base_uri, '', $request_uri);
			} else {
				$application->request_relative_uri = $request_uri;
			}

			$application->hostname = $hostname;
			$matched_applications[$key] = $application;
		}

		// Now that we have matching applications, see if one matches the
		// request specifically. Otherwise, simply return the first one.
		$matched_applications_sorted = [];
		foreach ($matched_applications as $application) {
			if (isset($application->config->base_uri)) {
				// base_uri should not be empty, default to '/'
				if ($application->config->base_uri == '') {
					$application->config->base_uri = '/';
				}
				if (strpos($request_uri, $application->config->base_uri) === 0) {
					$matched_applications_sorted[strlen($application->matched_hostname)][strlen($application->config->base_uri)] = $application;
				}
			} else {
				$matched_applications_sorted[strlen($application->matched_hostname)][0] = $application;
			}
		}

		// Sort the matched array by key, so the most specific one is at the end
		ksort($matched_applications_sorted);
		$applications = array_pop($matched_applications_sorted);

		if (is_array($applications) && count($applications) > 0) {
			// Get the most specific one
			ksort($applications);
			$application = array_pop($applications);
			Application::set($application);
			return Application::get();
		}

		throw new Exception_Unknown_Application('No application found for ' . $hostname);
	}

	/**
	 * Get all
	 *
	 * @access public
	 * @return array $applications
	 */
	public static function get_all() {
		$config = Config::get();
		if (!isset($config->application_dir)) {
			throw new \Exception('No application_dir set. Please set Config::$application_dir');
		}
		$application_directories = scandir($config->application_dir);
		$application = [];
		foreach ($application_directories as $application_directory) {
			if ($application_directory[0] == '.') {
				continue;
			}

			$application = self::get_by_name($application_directory);
			$applications[] = $application;
		}
		return $applications;
	}

	/**
	 * Get application by name
	 *
	 * @access public
	 * @param string $name
	 * @return Application $application
	 */
	public static function get_by_name($name) {
		$application = new Application\Web($name);
		$config = $application->config;
		$application_type = $config->application_type;
		return new $application_type($name);
	}

	/**
	 * Create an app
	 *
	 * @access public
	 * @param string $name
	 * @return Application $application
	 */
	public static function create($name, $settings) {
		$name = strtolower($name);
		$application_path = realpath(Config::$application_dir);

		if (!file_exists($application_path)) {
			throw new \Exception('There is no application path defined. Please specificy a path in \Skeleton\Core\Config::$application_dir');
		}

		if (file_exists($application_path . '/' . $name)) {
			throw new \Exception('There is already an app with this name created');
		}

		// Create the required directories
		mkdir($application_path . '/' . $name);
		mkdir($application_path . '/' . $name . '/config');
		mkdir($application_path . '/' . $name . '/media');
		mkdir($application_path . '/' . $name . '/module');
		mkdir($application_path . '/' . $name . '/template');
		mkdir($application_path . '/' . $name . '/event');

		$root_path = dirname(__FILE__) . '/../../../';

		$config = file_get_contents($root_path . '/template/config/Config.php.tpl');
		$config = str_replace('%%APP_NAME%%', ucfirst($name), $config);

		foreach ($settings['hostnames'] as $key => $hostname) {
			$settings['hostnames'][$key] = "'" . $hostname . "'";
		}

		$config = str_replace('%%HOSTNAMES%%', '[' . implode(', ', $settings['hostnames']) . ']', $config);
		file_put_contents($application_path . '/' . $name . '/config/Config.php', $config);

		$module = file_get_contents($root_path . '/template/module/index.php.tpl');
		file_put_contents($application_path . '/' . $name . '/module/index.php', $module);

		$template = file_get_contents($root_path . '/template/template/index.twig.tpl');
		file_put_contents($application_path . '/' . $name . '/template/index.twig', $template);

		return self::get_by_name($name);
	}

}
