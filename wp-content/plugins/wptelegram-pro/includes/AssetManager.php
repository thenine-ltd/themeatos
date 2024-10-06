<?php
/**
 * The assets manager of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.4.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\includes;

use DOMDocument;
use DOMXPath;
use ReflectionClass;
use WPTelegram\Pro\includes\restApi\RESTController;
use WPTelegram\Pro\includes\restApi\SettingsController;

/**
 * The assets manager of the plugin.
 *
 * Loads the plugin assets.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 * @author     WP Socio
 */
class AssetManager extends BaseClass {

	const ADMIN_MAIN_JS_HANDLE           = 'wptelegram-pro--main';
	const ADMIN_INSTANT_MESSAGES_HANDLE  = 'wptelegram-pro--im';
	const ADMIN_P2TG_METABOX_HANDLE      = 'wptelegram-pro--p2tg-metabox';
	const ADMIN_P2TG_INSTANT_POST_HANDLE = 'wptelegram-pro--p2tg-instant-post';
	const ADMIN_P2TG_GB_JS_HANDLE        = 'wptelegram-pro--p2tg-gb';
	const ADMIN_P2TG_CLASSIC_JS_HANDLE   = 'wptelegram-pro--p2tg-classic';

	/**
	 * Register the assets.
	 *
	 * @since    1.4.0
	 */
	public function register_assets() {

		$request_check = new ReflectionClass( self::class );

		$constants = $request_check->getConstants();

		$assets = $this->plugin()->assets();

		$style_deps = [
			self::ADMIN_P2TG_INSTANT_POST_HANDLE => [ 'wp-components' ],
		];

		foreach ( $constants as $handle ) {
			wp_register_script(
				$handle,
				$assets->get_asset_url( $handle ),
				$assets->get_asset_dependencies( $handle ),
				$assets->get_asset_version( $handle ),
				true
			);

			// Register styles only if they exist.
			if ( $assets->has_asset( $handle, Assets::ASSET_EXT_CSS ) ) {
				$deps = ! empty( $style_deps[ $handle ] ) ? $style_deps[ $handle ] : [];
				wp_register_style(
					$handle,
					$assets->get_asset_url( $handle, Assets::ASSET_EXT_CSS ),
					$deps,
					$assets->get_asset_version( $handle, Assets::ASSET_EXT_CSS ),
					'all'
				);
			}
		}

		wp_register_style(
			$this->plugin()->name() . '-menu',
			$assets->url( sprintf( '/css/admin-menu%s.css', wp_scripts_get_suffix() ) ),
			[],
			$this->plugin()->version(),
			'all'
		);
	}

	/**
	 * Add the data to DOM.
	 *
	 * @since 1.4.0
	 *
	 * @param string $handle The script handle to attach the data to.
	 * @param mixed  $data   The data to add.
	 * @param string $var    The JavaScript variable name to use.
	 *
	 * @return void
	 */
	public static function add_dom_data( $handle, $data, $var = 'wptelegram_pro' ) {
		wp_add_inline_script(
			$handle,
			sprintf( 'var %s = %s;', $var, wp_json_encode( $data ) ),
			'before'
		);
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.4.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_styles( $hook_suffix ) {

		wp_enqueue_style( $this->plugin()->name() . '-menu' );

		$handle = self::ADMIN_MAIN_JS_HANDLE;

		// Load only on settings page.
		if ( $this->is_settings_page( $hook_suffix ) && wp_style_is( $handle, 'registered' ) ) {
			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.4.0
	 * @param string $hook_suffix The current admin page.
	 */
	public function enqueue_admin_scripts( $hook_suffix ) {

		// Load only on settings page.
		if ( $this->is_settings_page( $hook_suffix ) ) {
			$handle = self::ADMIN_MAIN_JS_HANDLE;

			wp_enqueue_script( $handle );

			// Pass data to JS.
			$data = $this->get_dom_data();

			self::add_dom_data( $handle, $data );
		}
	}

	/**
	 * Get the common DOM data.
	 *
	 * @param string $for The domain for which the DOM data is to be rendered.
	 * possible values: 'SETTINGS_PAGE' | 'BLOCKS'.
	 *
	 * @return array
	 */
	public function get_dom_data( $for = 'SETTINGS_PAGE' ) {
		$data = [
			'pluginInfo' => [
				'title'       => $this->plugin()->title(),
				'name'        => $this->plugin()->name(),
				'version'     => $this->plugin()->version(),
				'description' => __( 'With this plugin, you can send posts to Telegram and receive notifications and do lot more :)', 'wptelegram-pro' ),
			],
			'assets'     => [
				'logoUrl'   => $this->plugin()->assets()->url( '/icons/icon-128x128.png' ),
				'tgIconUrl' => $this->plugin()->assets()->url( '/icons/tg-icon.svg' ),
			],
			'api'        => [
				'admin_url'      => admin_url(),
				'home_url'       => home_url(),
				'nonce'          => wp_create_nonce( 'wptelegram-pro' ),
				'use'            => 'SERVER', // or may be 'BROWSER'?
				'rest_namespace' => RESTController::REST_NAMESPACE,
				'wp_rest_url'    => esc_url_raw( rest_url() ),
			],
			'uiData'     => [
				'debug_info'    => $this->get_debug_info(),
				'import_config' => $this->import_settings_config(),
			],
			'i18n'       => Utils::get_jed_locale_data( 'wptelegram-pro' ),
		];

		if ( 'SETTINGS_PAGE' === $for ) {
			$data['assets'] = array_merge(
				$data['assets'],
				[
					'logoUrl'          => $this->plugin()->assets()->url( '/icons/icon-128x128.png' ),
					'tgIconUrl'        => $this->plugin()->assets()->url( '/icons/tg-icon.svg' ),
					'editProfileUrl'   => get_edit_profile_url( get_current_user_id() ),
					'p2tgLogUrl'       => Logger::get_log_url( 'p2tg' ),
					'botApiLogUrl'     => Logger::get_log_url( 'bot-api' ),
					'botUpdatesLogUrl' => Logger::get_log_url( 'bot-updates' ),
				]
			);
		}

		// Not to expose bot token to non-admins.
		if ( 'SETTINGS_PAGE' === $for && current_user_can( 'manage_options' ) ) {
			$data['savedSettings'] = SettingsController::get_default_settings();
		}

		return apply_filters( 'wptelegram_pro_assets_dom_data', $data, $for, $this->plugin() );
	}

	/**
	 * Get debug info.
	 */
	public function get_debug_info() {

		$info  = 'PHP:         ' . PHP_VERSION . PHP_EOL;
		$info .= 'WP:          ' . get_bloginfo( 'version' ) . PHP_EOL;
		$info .= 'Plugin:      ' . $this->plugin()->name() . ':v' . $this->plugin()->version() . PHP_EOL;
		$info .= 'DOMDocument: ' . ( class_exists( DOMDocument::class ) ? '✓' : '✕' ) . PHP_EOL;
		$info .= 'DOMXPath:    ' . ( class_exists( DOMXPath::class ) ? '✓' : '✕' ) . PHP_EOL;

		return $info;
	}

	/**
	 * Whether to show import settings from free version.
	 *
	 * @since    1.0.5
	 */
	public function import_settings_config() {
		// this plugin settings.
		$settings = $this->plugin()->options()->get_data();

		// free version.
		$free_version = get_option( 'wptelegram_ver', '' );

		$show_import = empty( $settings ) && $free_version;
		// Support import only for v3.0.0 or higher.
		$has_old_ver = $show_import && version_compare( $free_version, '4.0.0', '<' );

		$is_active = defined( 'WPTELEGRAM_LOADED' );

		return compact( 'show_import', 'has_old_ver', 'is_active' );
	}

	/**
	 * Whether the current page is the plugin settings page.
	 *
	 * @since 1.4.0
	 *
	 * @param string $hook_suffix The current admin page.
	 */
	public function is_settings_page( $hook_suffix ) {
		return 'toplevel_page_' . $this->plugin()->name() === $hook_suffix;
	}
}
