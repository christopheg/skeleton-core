<?php
/**
 * HTTP request Handler
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Web;

use Skeleton\Core\Hook;
use Skeleton\Core\Application;
use Skeleton\I18n\Language;
use Skeleton\Database\Database;

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
			throw new \Exception('Not a web request');
		}

		/**
		 * Define the application
		 */
		try {
			$application = Application::detect($hostname, $request_uri);
		} catch (Exception $e) {
			HTTP\Status::code_404('application');
		}

		/**
		 * Handle the media
		 */
		Media::detect($application->request_relative_uri);

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
				$language = Language::get_by_name_short($application->config->default_language);
				$_SESSION['language'] = $language;
			}
		}

		if (isset($_GET['language'])) {
			try {
				$language = Language::get_by_name_short($_GET['language']);
				$_SESSION['language'] = $language;
			} catch (Exception $e) {
				$_SESSION['language'] = Language::get_by_name_short($application->config->default_language);
			}
		}

		$application->language = $_SESSION['language'];

		/**
		 * Find the module to load
		 *
		 * FIXME: this nested try/catch is not the prettiest of things
		 */
		$module = null;
		try {
			// Attempt to find the module by matching defined routes
			$module = $application->route($request_uri);
		} catch (\Exception $e) {
			try {
				// Attempt to find a module by matching paths
				$module = Module::get($application->request_relative_uri);
			} catch (\Exception $e) {
				if (Hook::exists('module_not_found')) {
					Hook::call('module_not_found');
				} else {
					HTTP\Status::code_404('module');
				}
			}
		}

		if ($module !== null) {
			$module->accept_request();
		}
	}
}
