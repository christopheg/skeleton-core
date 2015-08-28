<?php
/**
 * Module management class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Core\Web;

use Skeleton\Core\Application;
use Skeleton\Core\Hook;

abstract class Module {

	/**
	 * Login required
	 *
	 * @var $login_required
	 */
	protected $login_required = true;

	/**
	 * Template
	 *
	 * @var $template
	 */
	protected $template = null;

	/**
	 * Accept the request
	 *
	 * @access public
	 */
	public function accept_request() {
		// Bootstrap the application
		$application = \Skeleton\Core\Application::get();

		// Call the bootstrap hook if it exists
		Hook::call_if_exists('bootstrap', [$this]);

		// Find the template and set it up
		$template = \Skeleton\Core\Web\Template::Get();
		$template->add_environment('module', $this);

		// Call our magic secure() method before passing on the request
		$allowed = true;
		if (method_exists($this, 'secure')) {
			$allowed = $this->secure();
		}

		// If the request is not allowed, make sure it gets handled properly
		if ($allowed === false) {
			$module_403 = strtolower(\Skeleton\Core\Config::$module_403);

			// Always check if it can not be handled by a hook first
			if (Hook::exists('module_access_denied')) {
				Hook::call('module_access_denied', [$this]);
			} elseif ($module_403 !== null and file_exists($application->module_path . '/' . $module_403 . '.php')) {
				require $application->module_path . '/' . $module_403 . '.php';
				$classname = 'Web_Module_' . $module;
				$module = new $classname;
				$module->accept_request();
			} else {
				throw new \Exception('Access denied');
			}
		} else {
			$this->handle_request();
		}

		// Call the teardown hook if it exists
		Hook::call_if_exists('teardown', [$this]);
	}

	/**
	 * Handle the request
	 *
	 * @access public
	 */
	public function handle_request() {
		$template = \Skeleton\Core\Web\Template::Get();

		// Find out which method to call, fall back to calling displa()
		if (isset($_REQUEST['action']) AND method_exists($this, 'display_' . $_REQUEST['action'])) {
			$template->assign('action', $_REQUEST['action']);
			call_user_func([$this, 'display_'.$_REQUEST['action']]);
		} else {
			$this->display();
		}

		// If the module has defined a template, render it
		if ($this->template !== null and $this->template !== false) {
			$template->display($this->template);
		}
	}

	/**
	 * Is login required?
	 *
	 * @access public
	 */
	public function is_login_required() {
		return $this->login_required;
	}

	/**
	 * Get the classname of the current module
	 *
	 * @access public
	 */
	public function get_name() {
		if (strpos(get_class($this), 'Web_Module_') !== false) {
			return strtolower(substr(get_class($this),strlen('Web_Module_')));
		}

		return strtolower(get_class($this));
	}

	/**
	 * Display the function
	 *
	 * @access public
	 */
	public abstract function display();

	/**
	 * Get the requested module
	 *
	 * @param string Module name
	 * @access public
	 * @return Web_Module Requested module
	 * @throws Exception
	 */
	public static function get($request_relative_uri) {
		$application = \Skeleton\Core\Application::get();

		$relative_uri_parts = array_values(array_filter(explode('/', $request_relative_uri)));

		$filename = trim($request_relative_uri, '/');
		if (file_exists($application->module_path . '/' . $filename . '.php')) {
			require $application->module_path . '/' . $filename . '.php';
			$classname = 'Web_Module_' . implode('_', $relative_uri_parts);
		} elseif (file_exists($application->module_path . '/' . $filename . '/' . $application->config->module_default . '.php')) {
			require $application->module_path . '/' . $filename . '/' . $application->config->module_default . '.php';

			if ($filename == '') {
				$classname = 'Web_Module_' . $application->config->module_default;
			} else {
				$classname = 'Web_Module_' . implode('_', $relative_uri_parts) . '_' . $application->config->module_default;
			}
		} elseif (file_exists($application->module_path . '/' . $application->config->module_404 . '.php')) {
			require $application->module_path . '/' . $application->config->module_404 . '.php';
			$classname = 'Web_Module_' . $application->config->module_404;
		} else {
			throw new \Exception('Module not found');
		}

		return new $classname;
	}

	/**
	 * Check if a module exists
	 *
	 * @param string Name of the module
	 * @access public
	 * @return bool
	 */
	public static function exists($name) {
		if (Config::$application_dir === null) {
			throw new \Exception('No application_dir set. Please set Config::$application_dir');
		}

		return (file_exists(Config::$application_dir . '/module/' . $name . '.php'));
	}
}
