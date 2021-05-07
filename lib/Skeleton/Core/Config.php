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
	 * Config array
	 *
	 * @var array
	 * @access private
	 */
	protected $config_data = [];

	/**
	 * Config object
	 *
	 * @var Config
	 * @access private
	 */
	private static $config = null;

	/**
	 * Private (disabled) constructor
	 *
	 * @access private
	 */
	public function __construct() {
	}

	/**
	 * Get config vars as properties
	 *
	 * @param string name
	 * @return mixed
	 * @throws Exception When accessing an unknown config variable, an Exception is thrown
	 * @access public
	 */
	public function __get($name) {
		if (!isset($this->config_data[$name])) {
			throw new \Exception('Attempting to read unkown config key: '.$name);
		}
		return $this->config_data[$name];
	}

	/**
	 * Get config vars as properties
	 *
	 * @param string name
	 * @param mixed value
	 * @access public
	 */
	public function __set($name, $value) {
		$this->config_data[$name] = $value;
	}

	/**
	 * Get function, returns a Config object
	 *
	 * @return Config
	 * @access public
	 */
	public static function Get() {
		if (!isset(self::$config)) {
			try {
				self::$config = \Skeleton\Core\Application::Get()->config;
			} catch (\Exception $e) {
				return new Config();
			}
		}
		return self::$config;
	}

	/**
	 * Check if config var exists
	 *
	 * @param string key
	 * @return bool $isset
	 * @access public
	 */
	public function __isset($key) {
		if (!isset($this->config_data) OR $this->config_data === null) {
			$this->read();
		}

		if (isset($this->config_data[$key])) {
			return true;
		}

		return false;
	}

	/**
	 * Read a config file into this config
	 *
	 * @access public
	 */
	public function read_file($file) {
		if (!file_exists($file)) {
			throw new \Exception($file . ' cannot be included in config. File does not exist.');
		}

		$config_data = require $file;

		if (!is_array($config_data)) {
			return;
		}
		foreach ($config_data as $key => $value) {
			$this->$key = $value;
		}
	}

	/**
	 * Read config files from directory
	 *
	 * @access public
	 */
	public function read_directory($directory) {
		if (!file_exists($directory)) {
			throw new \Exception('Config directory does not exist');
		}

		foreach (new \DirectoryIterator($directory) as $item) {
			if (!$item->isFile()) {
				continue;
			}
			if ($item == 'environment.php') {
				continue;
			}
			$this->read_file($directory . DIRECTORY_SEPARATOR . $item);
		}

		if (file_exists($directory . DIRECTORY_SEPARATOR . 'environment.php')) {
			$this->read_file($directory . DIRECTORY_SEPARATOR . 'environment.php');
		}
	}


	/**
	 * Include a config directory
	 *
	 * @access public
	 */
	public static function include_directory($directory) {
		if (!file_exists($directory)) {
			throw new \Exception('Config directory does not exist');
		}

		$config = self::get();
		$config->read_directory($directory);
		self::$config = $config;
	}

}
