<?php
/**
 * Skeleton PSR-0 compliant autoloader
 *
 * Based on the SplClassLoader example by Jonathan H. Wage and others
 * https://gist.github.com/jwage/221634
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core;

class Autoloader {

	/**
	 * @var string $file_extension
	 */
	 private $file_extension = '.php';

	/**
	 * @var string $namespace
	 */
	private $namespace;

	/**
	 * @var string $include_path
	 */
	private $include_path;

	/**
	 * @var string $namespace_separator
	 */
	private $namespace_separator = '\\';

	/**
	 * Creates a new Autoloader that loads classes of the specified namespace.
	 *
	 * @param string $namespace The namespace to use.
	 * @param string $include_path The path to search for includes
	 */
	public function __construct($namespace = null, $include_path = null) {
		$this->namespace = $namespace;
		$this->include_path = $include_path;
	}

	/**
	 * Sets the namespace separator used by classes in the namespace of this
	 * class loader.
	 *
	 * @param string $sep The separator to use.
	 */
	public function set_namespace_separator($sep) {
		$this->namespace_separator = $sep;
	}

	/**
	 * Gets the namespace seperator used by classes in the namespace of this
	 * class loader.
	 *
	 * @return void
	 */
	public function set_namespace_separator() {
		return $this->namespace_separator;
	}

	/**
	 * Sets the base include path for all class files in the namespace of this
	 * class loader.
	 *
	 * @param string $include_path
	 */
	public function set_include_path($include_path) {
		$this->include_path = $include_path;
	}

	/**
	 * Gets the base include path for all class files in the namespace of this
	 * class loader.
	 *
	 * @return string $include_path
	 */
	public function get_include_path() {
		return $this->include_path;
	}

	/**
	 * Sets the file extension of class files in the namespace of this class loader.
	 *
	 * @param string $file_extension
	 */
	public function set_file_extension($file_extension) {
		$this->file_extension = $file_extension;
	}

	/**
	 * Gets the file extension of class files in the namespace of this class loader.
	 *
	 * @return string $file_extension
	 */
	public function get_file_extension() {
		return $this->file_extension;
	}

	/**
	 * Installs this class loader on the SPL autoload stack.
	 */
	public function register() {
		spl_autoload_register([$this, 'load_class']);
	}

	/**
	 * Uninstalls this class loader from the SPL autoloader stack.
	 */
	public function unregister() {
		spl_autoload_unregister([$this, 'load_class']);
	}

	/**
	 * Loads the given class or interface.
	 *
	 * @param string $class_name The name of the class to load.
	 * @return void
	 */
	public function load_class($class_name) {
		if (null === $this->namespace || $this->namespace.$this->namespace_separator === substr($class_name, 0, strlen($this->namespace.$this->namespace_separator))) {
			$file_name = '';
			$namespace = '';

			if (false !== ($last_namespace_position = strripos($class_name, $this->namespace_separator))) {
				$namespace = substr($class_name, 0, $last_namespace_position);
				$class_name = substr($class_name, $last_namespace_position + 1);
				$file_name = str_replace($this->namespace_separator, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
			}

			$file_name .= str_replace('_', DIRECTORY_SEPARATOR, $class_name) . $this->file_extension;

			require ($this->include_path !== null ? $this->include_path . DIRECTORY_SEPARATOR : '') . $file_name;
		}
	}
}
