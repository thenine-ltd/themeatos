<?php
/**
 * The base class of the module.
 *
 * @link       https://wptelegram.pro
 * @since      1.4.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\modules;

/**
 * The base class of the module.
 *
 * The base class of the module.
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
	 * The module class instance.
	 *
	 * @since    1.4.0
	 * @access   private
	 * @var      BaseModule $module The module class instance.
	 */
	private $module;

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
		// static::class can be something like "WPTelegram\Core\modules\p2tg\Admin".
		// $relative_path becomes "p2tg\Admin".
		$relative_path = ltrim( str_replace( __NAMESPACE__, '', static::class ), '\\' );

		// extract module name from ["p2tg", "Admin"].
		list( $module_name ) = explode( '\\', $relative_path );

		$main = __NAMESPACE__ . "\\{$module_name}\Main";

		if ( ! isset( self::$instances[ static::class ] ) ) {
			self::$instances[ static::class ] = new static( $main::instance() );
		}
		return self::$instances[ static::class ];
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.4.0
	 * @param BaseModule $module The module class instance.
	 */
	protected function __construct( $module ) {
		$this->module = $module;
	}

	/**
	 * Get the instance of the module.
	 *
	 * @since     1.4.0
	 * @return    BaseModule    The module class instance.
	 */
	protected function module() {
		return $this->module;
	}
}
