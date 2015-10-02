<?php
/**
 * Session class
 *
 * @author Gerry Demaret <gerry@tigron.be>
 * @author Christophe Gosiau <christophe@tigron.be>
 */

namespace Skeleton\Core\Web;

class Session {

	/**
	 * Sticky session variables
	 *
	 * @access private
	 * @var array $sticky
	 */
	private static $sticky = null;

	/**
	 * Start the Session
	 *
	 * @access public
	 */
	public static function start() {
		session_name(\Skeleton\Core\Config::$session_name);
		session_start();
	}

	/**
	 * Redirect to
	 *
	 * @access public
	 * @param string $url
	 * @param bool $rewrite
	 */
	public static function redirect($url, $rewrite = true) {
		if ($rewrite) {
			// TODO: I don't actually know why there is a try/catch around this?
			try {
				$url = \Skeleton\Core\Util::rewrite_reverse($url);
			} catch (\Exception $e) { }
		}

		header('Location: '.$url);
		echo 'Redirecting to : '.$url;
		exit;
	}

	/**
	 * Destroy
	 *
	 * @access public
	 */
	public static function destroy() {
		session_destroy();
	}

	/**
	 * Set a sticky session variable
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $value
	 */
	public static function set_sticky($key, $value) {
		if (self::$sticky === null) {
			self::$sticky = new \Skeleton\Core\Web\Session\Sticky();
		}

		self::$sticky->$key = $value;
	}
}
