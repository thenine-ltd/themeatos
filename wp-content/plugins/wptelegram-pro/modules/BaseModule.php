<?php
/**
 * The file that defines the module
 *
 * A class definition that includes attributes and functions used across the module
 *
 * @link       https://wptelegram.pro
 * @since      1.4.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules
 */

namespace WPTelegram\Pro\modules;

use WPTelegram\Pro\includes\Options;

/**
 * The module pro class.
 *
 * @since      1.4.0
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules
 * @author     WP Socio
 */
abstract class BaseModule {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.4.0
	 * @var   static $instances The instance.
	 */
	protected static $instances = [];

	/**
	 * List of modules which have been initiated.
	 *
	 * @since 1.4.6
	 * @var   array $initiated List of modules which have been initiated.
	 */
	private static $initiated = [];

	/**
	 * The module options
	 *
	 * @since    1.4.0
	 * @access   protected
	 * @var      Options    $options    The module options.
	 */
	protected $options;

	/**
	 * The module name
	 *
	 * @since    1.4.0
	 * @access   protected
	 * @var      string    $module_name    The module name.
	 */
	protected $module_name;

	/**
	 * Main class Instance.
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.4.0
	 *
	 * @return static instance.
	 */
	public static function instance() {
		// static::class can be something like "WPTelegram\Pro\modules\p2tg\Main".
		// $relative_path becomes "p2tg\Main".
		$relative_path = ltrim( str_replace( __NAMESPACE__, '', static::class ), '\\' );

		// extract module name from ["p2tg", "Main"].
		list( $module_name ) = explode( '\\', $relative_path );

		if ( ! isset( self::$instances[ $module_name ] ) ) {
			self::$instances[ $module_name ] = new static( $module_name );
		}
		return self::$instances[ $module_name ];
	}

	/**
	 * Define the pro functionality of the module.
	 *
	 * @param string $module_name The module name.
	 *
	 * @since    1.4.0
	 */
	protected function __construct( $module_name ) {

		$this->module_name = $module_name;
	}

	/**
	 * Registers the initial hooks.
	 *
	 * @since    1.4.6
	 * @access   public
	 */
	public function init() {
		if ( ! empty( self::$initiated[ $this->module_name ] ) ) {
			return;
		}

		$this->define_necessary_hooks();

		if ( $this->options()->get( 'active' ) ) {
			$this->define_on_active_hooks();
		}

		self::$initiated[ $this->module_name ] = true;
	}

	/**
	 * Set the plugin options
	 *
	 * @since    1.4.0
	 * @access   private
	 */
	protected function set_options() {
		$data = WPTG_Pro()->options()->get( $this->module_name );

		$this->options = new Options();

		$this->options->set_data( (array) $data );
	}


	/**
	 * Get the plugin options
	 *
	 * @since    1.4.0
	 * @access   public
	 *
	 * @return Options
	 */
	public function options() {
		if ( ! $this->options ) {
			$this->set_options();
		}
		return $this->options;
	}

	/**
	 * The name of the module.
	 *
	 * @since     1.4.0
	 * @return    string    The name of the module.
	 */
	public function name() {
		return $this->module_name;
	}

	/**
	 * Register all of the hooks.
	 *
	 * @since    1.4.0
	 * @access   private
	 */
	protected function define_necessary_hooks() {}

	/**
	 * Register all of the hooks.
	 *
	 * @since    1.4.0
	 * @access   private
	 */
	protected function define_on_active_hooks() {}
}
