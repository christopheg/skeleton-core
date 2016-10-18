<?php
/**
 * Singleton, you can get an instance with Web_Template::Get()
 *
 * Embeds the Template object
 *
 * @author Christophe Gosiau <christophe@tigron.be>
 * @author Gerry Demaret <gerry@tigron.be>
 * @author David Vandemaele <david@tigron.be>
 */

namespace Skeleton\Core\Web;

class Template {

	/**
	 * Unique
	 *
	 * @var int $unique
	 */
	private $unique = 0;

	/**
	 * Template
	 *
	 * @access private
	 * @var Template $template
	 */
	private $template = null;

	/**
	 * Template
	 *
	 * @var Web_Template $template
	 * @access private
	 */
	private static $web_template = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->template = new \Skeleton\Template\Template();
		$application = \Skeleton\Core\Application::Get();

		if (file_exists($application->template_path)) {
			$this->template->add_template_directory($application->template_path, $application->name);
		}
		$packages = \Skeleton\Core\Package::get_all();
		foreach ($packages as $package) {
			if (file_exists($package->template_path)) {
				$this->template->add_template_directory($package->template_path, $package->name);
			}
		}
	}

	/**
	 * Assign a variable to the template
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $value
	 */
	public function assign($key, $value) {
		$this->template->assign($key, $value);
	}

	/**
	 * Add a variable to the environment
	 *
	 * @access public
	 * @param string $key
	 * @param mixed $value
	 */
	public function add_environment($key, $value) {
		$this->template->add_environment($key, $value);
	}

	/**
	 * Display a template
	 *
	 * @access public
	 * @param string $template
	 */
	public function display($template) {
		echo \Skeleton\Core\Util::rewrite_reverse_html($this->template->render($template));
	}

	/**
	 * Render a template
	 *
	 * @access public
	 * @param string $template
	 * @return string $rendered_template
	 */
	public function render($template) {
		return \Skeleton\Core\Util::rewrite_reverse_html($this->template->render($template));
	}

	/**
	 * Get function, returns Template object
	 */
	public static function Get() {
		if (self::$web_template === null) {
			self::$web_template = new Template();
		}

		return self::$web_template;
	}
}
