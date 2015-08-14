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
		/**
		 * Initialize sticky sessions
		 */
		Session_Sticky::clear(get_class($this));
		$session = Session_Sticky::Get();
		$session->module = get_class($this);

		$application = \Skeleton\Core\Application::get();

		// Bootstrap the application
		$application->bootstrap($this);

		/**
		 * Determine the template
		 */
		$template = \Skeleton\Core\Web\Template::Get();
		$template->assign('session', $_SESSION);
		$template->assign('MODULE', get_class($this));

		if (method_exists($this, 'secure')) {
			$allowed = $this->secure();
			if (!$allowed) {
				if (file_exists($application->module_path . '/' . $config->module_403 . '.php')) {
					require $application->module_path . '/' . $config->module_403 . '.php';
					$classname = 'Web_Module_' . $config->module_403;
					$module_403 = new $classname;
					$module_403->accept_request();
					exit();
				}
			}
		}

		if (isset($_REQUEST['action']) AND method_exists($this, 'display_' . $_REQUEST['action'])) {
			$template->assign('action', $_REQUEST['action']);
			call_user_func([$this, 'display_'.$_REQUEST['action']]);
		} else {
			$this->display();
		}

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
		if (file_exists(strtolower(WEB_PATH . '/module/' . implode('/', $request_parts) . '.php'))) {
			// Does the module exist on itself?
			require_once strtolower(WEB_PATH . '/module/' . implode('/', $request_parts) . '.php');
			$classname = 'Web_Module_' . implode('_', $request_parts);
		} elseif (file_exists(strtolower(WEB_PATH . '/module/' . implode('/', $request_parts) . '/index.php'))) {
			// If not, is the module the user asked for actually a directory?
			require_once strtolower(WEB_PATH . '/module/' . implode('/', $request_parts) . '/index.php');
			$classname = 'Web_Module_' . implode('_', $request_parts) . '_Index';
			$classname = str_replace('__', '_', $classname);
		} elseif (isset($request_parts[1]) AND $request_parts[1] == 'support' AND isset($request_parts[2])) {
			Web_Session::Redirect('/support?action=view&id=' . $request_parts[2]);
		} elseif (file_exists(strtolower(WEB_PATH . '/module/default.php'))) {
			require_once WEB_PATH . '/module/default.php';
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
		return (file_exists(WEB_PATH . '/module/' . $name . '.php'));
	}
}
