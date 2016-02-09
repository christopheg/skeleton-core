<?php
/**
 * Skeleton Core Package class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core;

class Package {

	/**
	 * Name
	 *
	 * @access public
	 * @var string $name
	 */
	public $name = null;

	/**
	 * Path
	 *
	 * @access public
	 * @var string $path
	 */
	public $path = null;

	/**
	 * Template path
	 *
	 * @access public
	 * @var string $path
	 */
	public $template_path = null;

	/**
	 * Asset dir
	 *
	 * @access public
	 * @var string $path
	 */
	public $asset_path = null;

	/**
	 * Get all
	 *
	 * @access public
	 * @return array $packages
	 */
	public static function get_all() {
		/**
		 * Search for other Skeleton packages installed
		 */
		$composer_dir = realpath(__DIR__ . '/../../../../../');
		$installed = file_get_contents($composer_dir . '/composer/installed.json');
		$installed = json_decode($installed);

		$packages = [];
		foreach ($installed as $install) {
			$package = $install->name;
			list($vendor, $name) = explode('/', $package);
			if ($vendor != 'tigron') {
				continue;
			}
			if (strpos($name, 'skeleton-package') !== 0) {
				continue;
			}

			$package = new self();
			$package->name = $name;
			$package->path = $composer_dir . '/tigron/' . $name;
			$package->template_path = $composer_dir . '/tigron/' . $name . '/template';
			$package->asset_path = $composer_dir . '/tigron/' . $name . '/media';

			$packages[] = $package;
		}
		return $packages;
	}

}
