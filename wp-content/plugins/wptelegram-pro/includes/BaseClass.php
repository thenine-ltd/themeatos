<?php
/**
 * The base class of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.4.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\includes;

/**
 * The base class of the plugin.
 *
 * The base class of the plugin.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 * @author     WP Socio
 */
abstract class BaseClass {

	/**
	 * Instances of the class.
	 *
	 * @since  1.4.6
	 * @access protected
	 * @var    self $instances The instances.
	 */
	protected static $instances = [];

	/**
	 * The plugin class instance.
	 *
	 * @since    1.4.0
	 * @access   private
	 * @var      Main $plugin The plugin class instance.
	 */
	private $plugin;

	/**
	 * Base class Instance.
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.4.6
	 *
	 * @return static instance.
	 */
	public static function instance() {
		if ( ! isset( self::$instances[ static::class ] ) ) {
			self::$instances[ static::class ] = new static();
		}
		return self::$instances[ static::class ];
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.4.0
	 */
	protected function __construct() {

		$this->plugin = Main::instance();
	}

	/**
	 * Get the instance of the plugin.
	 *
	 * @since     1.4.0
	 * @return    Main    The plugin class instance.
	 */
	protected function plugin() {
		return $this->plugin;
	}
}
