<?php
/**
 * Module management class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Core\Application\Web;

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
	 * Accept the request
	 *
	 * @access public
	 */
	public function accept_request() {
		/**
		 * Cleanup sticky session
		 */
		\Skeleton\Core\Web\Session\Sticky::cleanup();
		$sticky = \Skeleton\Core\Web\Session\Sticky::Get();

		// Bootstrap the application
		$application = \Skeleton\Core\Application::get();
		$application->call_event_if_exists('application', 'bootstrap', [$this]);

		// If we have the skeleton-template package installed, find the template and set it up
		if (class_exists('\Skeleton\Template\Template') === true) {
			$template = \Skeleton\Core\Web\Template::Get();
			$template->add_environment('module', $this);
			$template->add_environment('sticky_session', $sticky->get_as_array());
		}

		// Call our magic secure() method before passing on the request
		$allowed = true;
		if (method_exists($this, 'secure') === true) {
			$allowed = $this->secure();
		}

		// If the request is not allowed, make sure it gets handled properly
		if ($allowed === false) {
			$module_403 = strtolower(\Skeleton\Core\Config::$module_403);

			// Always check if it can not be handled by an event first
			if ($application->event_exists('module', 'access_denied') === true) {
				$application->call_event_if_exists('module', 'access_denied', [$this]);
			} elseif ($module_403 !== null && file_exists($application->module_path . '/' . $module_403 . '.php') === true) {
				require $application->module_path . '/' . $module_403 . '.php';
				$classname = 'Web_Module_' . $module_403;
				$module = new $classname;
				$module->accept_request();
			} else {
				throw new \Exception('Access denied');
			}
		} else {

			// Call the bootstrap method if it exists
			if (method_exists($this, 'bootstrap') === true) {
				$this->bootstrap();
			}

			// Handle request
			$this->handle_request();

			// Call the teardown method if it exists
			if (method_exists($this, 'teardown') === true) {
				$this->teardown();
			}
		}

		// Call the teardown event if it exists
		$application->call_event_if_exists('application', 'teardown', [$this]);
	}

	/**
	 * get module_path
	 *
	 * @access public
	 * @return string $path
	 */
	public function get_module_path() {
		$reflection = new \ReflectionClass($this);
		$application = Application::Get();
		$path = '/' . str_replace($application->module_path, '', $reflection->getFileName());

		return str_replace('.php', '', $path);
	}

	/**
	 * Handle the request
	 *
	 * @access public
	 */
	public function handle_request() {
		// Find out which method to call, fall back to calling display()
		if (
			isset($_REQUEST['action']) === true
			&& is_string($_REQUEST['action']) === true
			&& method_exists($this, 'display_' . $_REQUEST['action']) === true
		) {
			if (class_exists('\Skeleton\Template\Template') === true) {
				$template = \Skeleton\Core\Web\Template::Get();
				$template->assign('action', $_REQUEST['action']);
			}

			call_user_func([$this, 'display_' . $_REQUEST['action']]);
		} else {
			$this->display();
		}

		// If the module has defined a template, render it
		if ($this->template !== null && $this->template !== false) {
			$template = \Skeleton\Core\Web\Template::Get();
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
		$application = Application::get();
		$module_namespace = $application->module_namespace;

		$module_name = str_replace($module_namespace, '', '\\' . get_class($this));
		$module_name = str_replace('\\', '_', $module_name);
		return \strtolower($module_name);
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
	public static function resolve($request_relative_uri) {
		$relative_uri_parts = array_values(array_filter(explode('/', $request_relative_uri)));
		$relative_uri_parts = array_map('ucfirst', $relative_uri_parts);
		$application = \Skeleton\Core\Application::get();
		$module_namespace = $application->module_namespace;

		$classnames = [];
		$classnames[] = $module_namespace . implode('\\', $relative_uri_parts);
		$classnames[] = $module_namespace . implode('\\', $relative_uri_parts) . "\\" . ucfirst($application->config->module_default);
		$classnames[] = $module_namespace . "\\" . $application->config->module_404;

		foreach ($classnames as $classname) {
			$classname = str_replace('\\\\', '\\', $classname);
			if (class_exists($classname) === false) {
				continue;
			}

			return new $classname;
		}

		throw new \Exception('Module not found');
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
