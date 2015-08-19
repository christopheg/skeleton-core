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
		$this->template->set_template_directory(\Skeleton\Core\Application::Get()->template_path);
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
	 * Get function, returns Template object
	 */
	public static function Get() {
		if (self::$web_template === null) {
			self::$web_template = new Template();
		}

		return self::$web_template;
	}
}
