<?php
/**
 * Skeleton Core Application class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core;

class Application {

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
	 * Media Path
	 *
	 * @var string $media_path
	 * @access public
	 */
	public $media_path = null;

	/**
	 * Module Path
	 *
	 * @var string $module_path
	 * @access public
	 */
	public $module_path = null;

	/**
	 * Template path
	 *
	 * @var string $template_path
	 * @ccess public
	 */
	public $template_path = null;

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
	 * Constructor
	 *
	 * @access public
	 */
	public function __construct() {
	}

	/**
	 * Search module
	 *
	 * @access public
	 * @param string $request_uri
	 */
	public function route($request_uri) {
		/**
		 * Remove leading slash
		 */
		if ($request_uri[0] == '/') {
			$request_uri = substr($request_uri, 1);
		}

		if (substr($request_uri, -1) == '/') {
			$request_uri = substr($request_uri, 0, strlen($request_uri)-1);
		}

		if (!isset($this->config->base_uri)) {
			$this->config->base_uri = '/';
		}

		if (strpos( '/' . $request_uri, $this->config->base_uri) === 0) {
			$request_uri = substr($request_uri, strlen($this->config->base_uri)-1);
		}
		$request_parts = explode('/', $request_uri);

		$routes = $this->config->routes;

		/**
		 * We need to find the route that matches the most the fixed parts
		 */
		$matched_module = null;
		$best_matches_fixed_parts = 0;
		$route = '';

		foreach ($routes as $module => $uris) {
			foreach ($uris as $uri) {
				if (isset($uri[0]) AND $uri[0] == '/') {
					$uri = substr($uri, 1);
				}
				$parts = explode('/', $uri);
				$matches_fixed_parts = 0;
				$match = true;

				foreach ($parts as $key => $value) {
					if (!isset($request_parts[$key])) {
						$match = false;
						continue;
					}

					if ($value == $request_parts[$key]) {
						$matches_fixed_parts++;
						continue;
					}

					if (isset($value[0]) AND $value[0] == '$') {
						preg_match_all('/(\[(.*?)\])/', $value, $matches);
						if (!isset($matches[2][0])) {
							/**
							 *  There are no possible values for the variable
							 *  The match is valid
							 */
							 continue;
						}

						$possible_values = explode(',', $matches[2][0]);

						$variable_matches = false;
						foreach ($possible_values as $possible_value) {
							if ($request_parts[$key] == $possible_value) {
								$variable_matches = true;
							}
						}

						if (!$variable_matches) {
							$match = false;
						}

						// This is a variable, we do not increase the fixed parts
						continue;
					}
					$match = false;
				}


				if ($match and count($parts) == count($request_parts)) {
					if ($matches_fixed_parts >= $best_matches_fixed_parts) {
						$best_matches_fixed_parts = $matches_fixed_parts;
						$route = $uri;
						$matched_module = $module;
					}
				}
			}
		}

		if ($matched_module === null) {
			throw new \Exception('No matching route found');
		}

		/**
		 * We now have the correct route
		 * Now fill in the GET-parameters
		 */
		$parts = explode('/', $route);

		foreach ($parts as $key => $value) {
			if (isset($value[0]) and $value[0] == '$') {
				$value = substr($value, 1);
				if (strpos($value, '[') !== false) {
					$value = substr($value, 0, strpos($value, '['));
				}
				$_GET[$value] = $request_parts[$key];
				$_REQUEST[$value] = $request_parts[$key];
			}
		}

		$request_relative_uri = str_replace('web_module_', '', $matched_module);
		$request_relative_uri = str_replace('_', '/', $request_relative_uri);
		return \Skeleton\Core\Web\Module::get($request_relative_uri);
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
	public static function set(Application $application) {
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

		if (count($applications) > 0) {
			// Get the most specific one
			ksort($applications);
			$application = array_pop($applications);
			Application::set($application);
			return Application::get();
		}

		throw new \Exception('No application found for ' . $hostname);
	}

	/**
	 * Get all
	 *
	 * @access public
	 * @return array $applications
	 */
	public static function get_all() {
		if (Config::$application_dir === null) {
			throw new \Exception('No application_dir set. Please set Config::$application_dir');
		}
		$application_directories = scandir(Config::$application_dir);
		$application = [];
		foreach ($application_directories as $application_directory) {
			if ($application_directory[0] == '.') {
				continue;
			}

			if (file_exists(Config::$application_dir . '/' . $application_directory . '/config/Config.php')) {
				require_once Config::$application_dir . '/' . $application_directory . '/config/Config.php';
				$classname = 'Config_' . ucfirst($application_directory);
				$config = new $classname;
			} else {
				throw new \Exception('No config file in application directory. Please create "' . Config::$application_dir . '/' . $application_directory . '/config/Config.php');
			}

			$app_path = realpath(Config::$application_dir . '/' . $application_directory);
			$application = new Application();
			$application->media_path = $app_path . '/media/';
			$application->module_path = $app_path . '/module/';
			$application->template_path = $app_path . '/template/';
			$application->path = $app_path;
			$application->name = $application_directory;
			$application->config = $config;
			if (class_exists('\Skeleton\I18n\Config') AND isset(\Skeleton\I18n\Config::$language_interface)) {
				$classname = \Skeleton\I18n\Config::$language_interface;
				if (!class_exists($classname)) {
					throw new \Exception('The language interface does not exists: ' . \Skeleton\I18n\Config::$language_interface);
				}
				$application->language = $classname::get_by_name_short($config->default_language);
			}
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
		$applications = self::get_all();
		foreach ($applications as $application) {
			if ($application->name == $name) {
				return $application;
			}
		}

		throw new \Exception('Application ' . $name . ' does not exists.');
	}
}
