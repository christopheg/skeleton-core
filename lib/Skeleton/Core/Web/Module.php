<?php
/**
 * Module management class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Core\Web;

use Skeleton\Core\Application;

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
	 * Handle the request
	 *
	 * @access public
	 */
	public function accept_request() {
		// Bootstrap the application
		$application = \Skeleton\Core\Application::get();
		$application->bootstrap($this);

		// Find the template and set it up
		$template = \Skeleton\Core\Web\Template::Get();
		$template->add_environment('module', $this);

		// Call our magic secure() method before passing on the request
		if (method_exists($this, 'secure')) {
			$allowed = $this->secure();
			if (!$allowed) {
				$module = strtolower(\Skeleton\Core\Config::$module_403);

				if (file_exists($application->module_path . '/' . $module . '.php')) {
					require $application->module_path . '/' . $module . '.php';
					$classname = 'Web_Module_' . $module;
					$module = new $classname;
					$module->accept_request();
					exit();
				} else {
					throw new \Exception('Access denied');
				}
			}
		}

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

		// Tear down the application
		$application->teardown($this);
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
	public static function get($request_parts) {
		if (Config::$application_dir === null) {
			throw new \Exception('No application_dir set. Please set Config::$application_dir');
		}

		if (file_exists(strtolower(Config::$application_dir . '/module/' . implode('/', $request_parts) . '.php'))) {
			// Does the module exist on itself?
			require_once strtolower(Config::$application_dir . '/module/' . implode('/', $request_parts) . '.php');
			$classname = 'Web_Module_' . implode('_', $request_parts);
		} elseif (file_exists(strtolower(Config::$application_dir . '/module/' . implode('/', $request_parts) . '/index.php'))) {
			// If not, is the module the user asked for actually a directory?
			require_once strtolower(Config::$application_dir . '/module/' . implode('/', $request_parts) . '/index.php');
			$classname = 'Web_Module_' . implode('_', $request_parts) . '_Index';
			$classname = str_replace('__', '_', $classname);
		} elseif (isset($request_parts[1]) AND $request_parts[1] == 'support' AND isset($request_parts[2])) {
			Web_Session::Redirect('/support?action=view&id=' . $request_parts[2]);
		} elseif (file_exists(strtolower(Config::$application_dir . '/module/default.php'))) {
			require_once Config::$application_dir . '/module/default.php';
			$classname = 'Web_Module_Default';
		} else {
			Web_Session::Redirect('/');
		}

		$module = new $classname();
		return $module;
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
