<?php
/**
 * Config class
 * Configuration for Skeleton\Core
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core;

class Config {

	/**
	 * Application directory
	 *
	 * @access public
	 * @var string $application_dir
	 */
	public static $application_dir = null;

	/**
	 * Name of the module that handles 403 errors
	 *
	 * @access public
	 * @var string $module_403
	 */
	public static $module_403 = null;

	/**
	 * Name of the session
	 *
	 * @access public
	 * @var string $session_name
	 */
	public static $session_name = 'APP';

	/**
	 * Name of the variable to store the sticky session object in
	 *
	 * @access public
	 * @var string $sticky_session_namse
	 */
	public static $sticky_session_name = 'sys_sticky_session';
}
