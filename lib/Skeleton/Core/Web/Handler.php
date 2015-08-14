<?php
/**
 * HTTP request Handler
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

use Skeleton\I18n\Language;

namespace Skeleton\Core\Web;

use Skeleton\Core\Application;
use Skeleton\I18n\Language;

class Handler {
	/**
	 * Handle the request and send it to the correct module
	 *
	 * @access public
	 */
	public static function run() {
		/**
		 * Record the start time in microseconds
		 */
		$start = microtime(true);
		mb_internal_encoding('utf-8');

		/**
		 * Start the session
		 */
		Session::start();

		/**
		 * Hide PHP powered by
		 */
		header('X-Powered-By: Me');

		/**
		 * Parse the requested URL
		 */
		$components = parse_url($_SERVER['REQUEST_URI']);

		if (isset($components['query'])) {
			$query_string = $components['query'];
		} else {
			$query_string = '';
		}

		if (isset($components['path']) and $components['path'] !== '/') {
			$request_uri_parts = explode('/', $components['path']);
			array_shift($request_uri_parts);
		} else {
			$request_uri_parts = [];
		}

		$request_uri = '/' . implode('/', $request_uri_parts) . '/';

		 // Find out what the hostname is, if none was found, bail out
		if (!empty($_SERVER['SERVER_NAME'])) {
			$hostname = $_SERVER['SERVER_NAME'];
		} elseif (!empty($_SERVER['HTTP_HOST'])) {
			$hostname = $_SERVER['HTTP_HOST'];
		} else {
			throw new Exception('Not a web request');
		}

		/**
		 * Define the application
		 */
		try {
			$application = Application::detect($hostname, $request_uri);
		} catch (Exception $e) {
			header("HTTP/1.1 404 Not Found", true);
			echo '404 File Not Found (application)';
			return;
		}

		/**
		 * Handle the media
		 */
		Media::detect($application->request_relative_uri);

		/**
		 * Find the module to load
		 */
		try {
			// Attempt to find the module by matching defined routes
			$module = $application->route($request_uri);
		} catch (\Exception $e) {

			// So there is no route defined.

			/**
			 * 1. Try to look for the exact module
			 * 2. Take the default module
			 * 3. Load 404 module
			 */
			$filename = trim($application->request_relative_uri, '/');
			if (file_exists($application->module_path . '/' . $filename . '.php')) {
				require $application->module_path . '/' . $filename . '.php';
				$classname = 'Web_Module_' . implode('_', $request_uri_parts);
			} elseif (file_exists($application->module_path . '/' . $filename . '/' . $application->config->module_default . '.php')) {
				require $application->module_path . '/' . $filename . '/' . $application->config->module_default . '.php';

				if ($filename == '') {
					$classname = 'Web_Module_' . $application->config->module_default;
				} else {
					$classname = 'Web_Module_' . implode('_', $request_uri_parts) . '_' . $application->config->module_default;
				}
			} elseif (file_exists($application->module_path . '/' . $application->config->module_404 . '.php')) {
				require $application->module_path . '/' . $config->module_404 . '.php';
				$classname = 'Web_Module_' . $application->config->module_404;
			} else {
				header('HTTP/1.0 404 Module not found');
				exit;
			}

			$module = new $classname;
		}

		/**
		 * Set language
		 */
		// Set the language to something sensible if it isn't set yet
		if (!isset($_SESSION['language'])) {
			if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
				$languages = Language::get_all();

				foreach ($languages as $language) {
					if (strpos($_SERVER['HTTP_ACCEPT_LANGUAGE'], $language->name_short) !== false) {
						$language = $language;
						$_SESSION['language'] = $language;
					}
				}
			}

			if (!isset($_SESSION['language'])) {
				$language = Language::get_by_name_short($config->default_language);
				$_SESSION['language'] = $language;
			}
		}

		if (isset($_GET['language'])) {
			try {
				$language = Language::get_by_name_short($_GET['language']);
				$_SESSION['language'] = $language;
			} catch (Exception $e) {
				$_SESSION['language'] = Language::get_by_name_short($config->default_language);
			}
		}
		$application->language = $_SESSION['language'];

		$module->accept_request();

		// Record debug information
		$database = Database::get();
		$queries = $database->queries;
		$execution_time = microtime(true) - $start;

		Util::log_request('Request: http://' . $application->hostname . $_SERVER['REQUEST_URI'] . ' -- IP: ' . $_SERVER['REMOTE_ADDR'] . ' -- Queries: ' . $queries . ' -- Time: ' . $execution_time);
	}
}
