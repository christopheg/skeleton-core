<?php
/**
 * Hooks
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

 namespace Skeleton\Core;

class Hook {
	/**
	 * List of available Hooks
	 */
	private static $definable_hooks = [
		'bootstrap',
		'teardown',
		'handle_error',
		'module_access_denied',
		'module_not_found',
	];

	/**
	 * Check if a hook exists in the current application
	 *
	 * @access public
	 * @param string $name
	 * @return bool
	 */
	public static function exists($name) {
		$application = Application::get();

		if (!in_array($name, self::$definable_hooks)) {
			return false;
		}

		if (file_exists($application->path . '/config/Hook.php')) {
			require_once $application->path . '/config/Hook.php';
			$classname = 'Hook_' . ucfirst($application->name);

			if (method_exists($classname, $name)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Call a hook
	 *
	 * @access public
	 * @param string $name
	 * @param array $arguments
	 * @return bool
	 */
	public static function call($name, $arguments = []) {
		if (self::exists($name)) {
			$application = Application::get();
			$classname = 'Hook_' . ucfirst($application->name);
			call_user_func_array([$classname, $name], $arguments);
		} else {
			throw new \Exception('Hook is not defined');
		}
	}

	/**
	 * Call a hook if it exists, fail silently if not
	 *
	 * @access public
	 * @param string $name
	 * @param array $arguments
	 * @return bool
	 */
	public static function call_if_exists($name, $arguments = []) {
		if (self::exists($name)) {
			$application = Application::get();
			$classname = 'Hook_' . ucfirst($application->name);
			call_user_func_array([$classname, $name], $arguments);
		}
	}
}
