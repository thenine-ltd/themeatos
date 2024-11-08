<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\includes;

use WPTelegram\Pro\admin\Admin;
use WPTelegram\Pro\shared\Shared;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 * @author     WP Socio
 */
final class Main {

	/**
	 * The single instance of the class.
	 *
	 * @since 1.0.0
	 * @var   Main $instance The plugin class instance.
	 */
	protected static $instance = null;

	/**
	 * Whether the dependencies have been initiated.
	 *
	 * @since 1.4.6
	 * @var   bool $initiated Whether the dependencies have been initiated.
	 */
	private static $initiated = false;

	/**
	 * Title of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $title    Title of the plugin
	 */
	protected $title;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The plugin options
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $options    The plugin options
	 */
	protected $options;

	/**
	 * The assets handler.
	 *
	 * @since    1.4.0
	 * @access   protected
	 * @var      Assets $assets The assets handler.
	 */
	protected $assets;

	/**
	 * The utility methods
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $utils    The utility instance
	 */
	public $utils;

	/**
	 * The utility methods
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      string    $helpers    The utility instance
	 */
	public $helpers;

	/**
	 * Main class Instance.
	 *
	 * Ensures only one instance of the class is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {}

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->version     = WPTELEGRAM_PRO_VER;
		$this->plugin_name = 'wptelegram_pro';

		$this->load_dependencies();

		$this->set_locale();
	}

	/**
	 * Registers the initial hooks.
	 *
	 * @since    1.4.0
	 * @access   public
	 */
	public function init() {
		if ( self::$initiated ) {
			return;
		}

		$plugin_upgrade = Upgrade::instance();

		$modules = Modules::instance();

		// First lets do the upgrades, if needed.
		add_action( 'plugins_loaded', [ $plugin_upgrade, 'do_upgrade' ], 10 );

		// Then lets hook everything up.
		add_action( 'plugins_loaded', [ $this, 'hookup' ], 20 );
		add_action( 'plugins_loaded', [ $modules, 'load' ], 20 );

		self::$initiated = true;
	}

	/**
	 * Registers the initial hooks.
	 *
	 * @since    1.4.0
	 * @access   public
	 */
	public function hookup() {
		// If an upgrade is going on.
		if ( defined( 'WPTELEGRAM_PRO_DOING_UPGRADE' ) && WPTELEGRAM_PRO_DOING_UPGRADE ) {
			return;
		}

		$this->define_admin_hooks();
		$this->define_shared_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * Helper functions
		 */
		require_once $this->dir( '/includes/helper-functions.php' );

		/**
		 * The class responsible for loading \WPTelegram\BotAPI library
		 */
		require_once $this->dir( '/includes/bot-api/autoload-wp.php' );

		/**
		 * The class responsible for loading \WPTelegram\FormatText library
		 */
		require_once $this->dir( '/includes/format-text/autoload-wp.php' );

		// load plugin updater.
		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			require_once $this->dir( '/includes/EDD_SL_Plugin_Updater.php' );
		}
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new I18n();

		add_action( 'plugins_loaded', [ $plugin_i18n, 'load_plugin_textdomain' ] );
	}

	/**
	 * Set the plugin options
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_options() {

		$this->options = new Options( $this->name(), true );
	}

	/**
	 * Get the plugin options
	 *
	 * @since    1.0.0
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
	 * Set the assets handler.
	 *
	 * @since    1.4.0
	 * @access   private
	 */
	private function set_assets() {
		$this->assets = new Assets( $this->dir( '/assets' ), $this->url( '/assets' ) );
	}

	/**
	 * Get the plugin assets handler.
	 *
	 * @since    1.4.0
	 * @access   public
	 *
	 * @return Assets The assets instance.
	 */
	public function assets() {
		if ( ! $this->assets ) {
			$this->set_assets();
		}

		return $this->assets;
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = Admin::instance();

		add_action( 'admin_menu', [ $plugin_admin, 'add_plugin_admin_menu' ], 11 );
		add_action( 'rest_api_init', [ $plugin_admin, 'register_rest_routes' ] );
		add_filter( 'rest_request_before_callbacks', [ Utils::class, 'fitler_rest_errors' ], 10, 3 );
		add_action( 'wp_ajax_wptelegram_pro_test', [ $plugin_admin, 'ajax_handle_test' ] );
		add_action( 'after_setup_theme', [ $plugin_admin, 'initiate_logger' ] );
		add_filter( 'wptelegram_widget_delete_webhook', [ $plugin_admin, 'widget_delete_webhook' ], 10, 2 );

		add_filter( 'plugin_action_links_' . WPTELEGRAM_PRO_BASENAME, [ $plugin_admin, 'plugin_action_links' ] );
		add_filter( 'upgrader_process_complete', [ $plugin_admin, 'fire_plugin_version_upgrade' ], 10, 2 );

		$plugin_updater = Updater::instance();

		//add_action( 'init', [ $plugin_updater, 'handle_request' ] );
		//add_action( 'init', [ $plugin_updater, 'setup_plugin_update' ] );
		//add_action( 'admin_notices', [ $plugin_updater, 'licence_notice' ] );

		$asset_manager = AssetManager::instance();

		add_action( 'admin_init', [ $asset_manager, 'register_assets' ] );

		add_action( 'admin_enqueue_scripts', [ $asset_manager, 'enqueue_admin_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $asset_manager, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_shared_hooks() {

		$shared = Shared::instance();

		// better be safe by using PHP_INT_MAX to make sure
		// Some dumb people don't remove your schedule.
		add_filter( 'cron_schedules', [ $shared, 'custom_cron_schedules' ], PHP_INT_MAX - 1000, 1 ); // phpcs:ignore

		// Hook into bot API calls for file uploads.
		add_action( 'wptelegram_bot_api_remote_request_init', [ Shared::class, 'hook_into_curl_for_files' ] );
		add_action( 'wptelegram_bot_api_remote_request_finish', [ Shared::class, 'unhook_from_curl_for_files' ] );
		// Sanitise REST API params for Bot API.
		add_action( 'wptelegram_bot_api_rest_sanitize_params', [ Shared::class, 'bot_api_rest_sanitize_params' ], 10, 2 );
	}

	/**
	 * The title of the plugin.
	 *
	 * @since 1.0.0
	 * @return string The title of the plugin.
	 */
	public function title() {
		// Set here instead of constructor
		// to be able to translate it.
		if ( ! $this->title ) {
			$this->title = __( 'WP Telegram Pro', 'wptelegram' );
		}
		return $this->title;
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function version() {
		return $this->version;
	}

	/**
	 * Retrieve directory path to the plugin.
	 *
	 * @since 1.0.0
	 * @param string $path Path to append.
	 * @return string Directory with optional path appended
	 */
	public function dir( $path = '' ) {
		return WPTELEGRAM_PRO_DIR . $path;
	}

	/**
	 * Retrieve URL path to the plugin.
	 *
	 * @since 1.0.0
	 * @param string $path Path to append.
	 * @return string URL with optional path appended
	 */
	public function url( $path = '' ) {
		return WPTELEGRAM_PRO_URL . $path;
	}
}
