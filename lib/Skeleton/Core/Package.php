<?php
/**
 * Skeleton Core Package class
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 */

namespace Skeleton\Core;

class Package extends Skeleton {

	/**
	 * Get all
	 *
	 * @access public
	 * @return array $packages
	 */
	public static function get_all() {
		$skeletons = parent::get_all();
		$packages = [];
		foreach ($skeletons as $skeleton) {
			if (strpos($skeleton->name, 'skeleton-package') !== 0) {
				continue;
			}
			$packages[] = $skeleton;
		}
		return $packages;
	}

}
