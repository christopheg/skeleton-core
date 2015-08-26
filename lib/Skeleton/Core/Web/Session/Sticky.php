<?php
/**
 * Sticky session store
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Web\Session;

use Skeleton\Core\Config;

class Sticky {
	private $variables = [];

	public function __construct() {
		if (isset($_SESSION[Config::$sticky_session_name])) {
			$this->variables = $_SESSION[Config::$sticky_session_name];
		}
	}

	/**
	 * Set
	 *
	 * @access public
	 * @param string $key
	 * @param string $value
	 */
	public function __set($key, $value) {
		if (!isset($_SESSION[Config::$sticky_session_name])) {
			$_SESSION[Config::$sticky_session_name] = [];
		}

		$_SESSION[Config::$sticky_session_name][$key] = $value;
	}

	/**
	 * Get
	 *
	 * @access public
	 * @param string $key
	 */
	public function __get($key) {
		if (!isset($this->variables[$key])) {
			throw new \Exception('Key "' . $key . '"" not found');
		}

		return $this->variables[$key];
	}

	/**
	 * Isset
	 *
	 * @access public
	 * @param string $key
	 */
	public function __isset($key) {
		if (!isset($this->variables[$key])) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Sticky clear
	 *
	 * @access public
	 */
	public static function clear() {
		if (!isset($_SESSION[Config::$sticky_session_name])) {
			return;
		}

		unset($_SESSION[Config::$sticky_session_name]);
	}
}
