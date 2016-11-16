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
	private $namespace = null;

	/**
	 * @var string $include_paths
	 */
	private $include_paths = [];

	/**
	 * @var string $namespaces
	 */
	private $namespaces = [];

	/**
	 * @var string $namespace_separator
	 */
	private $namespace_separator = '\\';

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
	public function get_namespace_separator() {
		return $this->namespace_separator;
	}

	/**
	 * Adds an include path for all class files in the namespace of this
	 * class loader.
	 *
	 * @param string $include_path
	 */
	public function add_include_path($include_path) {
		$this->include_paths[] = $include_path;
	}

	/**
	 * Gets the loaded include paths.
	 *
	 * @return string $include_path
	 */
	public function get_include_paths() {
		return $this->include_paths;
	}

	public function add_namespace($namespace, $path) {
		$this->namespaces[$namespace] = $path;
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
		$file_path = str_replace(' ', '/', ucwords(str_replace('_', ' ', str_replace('\\', ' ', strtolower($class_name))))) . '.php';

		foreach ($this->namespaces as $namespace => $namespace_path) {
			$path = $namespace_path . '/' . substr($file_path, strpos('/', $file_path));

			if (file_exists($path)) {
				require_once $path;
			}
		}

		foreach ($this->include_paths as $include_path) {
			$path = $include_path . '/' . $file_path;

			if (file_exists($path)) {
				require_once $path;
			}
		}
	}
}
