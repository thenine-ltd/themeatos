<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\admin
 */

namespace WPTelegram\Pro\admin;

use WPTelegram\Pro\includes\restApi\SettingsController;
use WPTelegram\Pro\includes\restApi\ImportSettingsController;
use WPTelegram\Pro\includes\BaseClass;
use WPTelegram\Pro\includes\Logger;
use WPTelegram\Pro\includes\restApi\LicenceController;
use WPTelegram\Pro\modules\bots\Utils as BotsUtils;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\admin
 * @author     WP Socio
 */
class Admin extends BaseClass {

	/**
	 * Whether to show import settings from free version.
	 *
	 * @since    1.0.5
	 */
	public function show_import_settings() {
		// this plugin settings.
		$settings = $this->plugin()->options()->get_data();

		if ( ! empty( $settings ) ) {
			return false;
		}

		// free version settings.
		$settings = get_option( 'wptelegram', [] );

		return ! empty( $settings );
	}

	/**
	 * Initiate logger
	 *
	 * @since    1.0.0
	 */
	public function initiate_logger() {

		$active_logs = WPTG_Pro()->options()->get_path( 'advanced.enable_logs', [] );

		Logger::instance()->set_active_logs( $active_logs )->hookup();
	}

	/**
	 * Register WP REST API routes.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_routes() {
		$controllers = [
			SettingsController::class,
			ImportSettingsController::class,
			LicenceController::class,
		];

		foreach ( $controllers as $class ) {
			$controller = new $class();
			$controller->register_routes();
		}
	}

	/**
	 * Register the admin menu.
	 *
	 * @since 1.0.0
	 */
	public function add_plugin_admin_menu() {

		add_menu_page(
			esc_html( $this->plugin()->title() ),
			esc_html( $this->plugin()->title() ),
			'manage_options',
			$this->plugin()->name(),
			[ $this, 'display_plugin_admin_page' ],
			'none',
			80
		);
	}

	/**
	 * Render the settings page for this plugin.
	 *
	 * @since 1.0.0
	 */
	public function display_plugin_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
			<div id="wptelegram-pro-settings"></div>
		<?php
	}

	/**
	 * Add action links to the plugin page.
	 *
	 * @since  1.4.6
	 *
	 * @param array $links The links for the plugin.
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			menu_page_url( $this->plugin()->name(), false ),
			esc_html__( 'Settings', 'wptelegram-pro' )
		);

		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Fires up plugin version upgrade by sending a non-blocking request to home page
	 * immediately after the plugin is upgraded to a new version.
	 *
	 * @since  1.4.8
	 *
	 * @param mixed $upgrader WP Upgrader instance.
	 * @param array $args     Array of bulk item update data.
	 */
	public function fire_plugin_version_upgrade( $upgrader, $args ) {
		if ( 'update' === $args['action'] && 'plugin' === $args['type'] && ! empty( $args['plugins'] ) ) {
			foreach ( (array) $args['plugins'] as $basename ) {
				if ( WPTELEGRAM_PRO_BASENAME === $basename ) {
					wp_remote_get(
						site_url(),
						[
							'timeout'   => 0.01,
							'blocking'  => false,
							'sslverify' => false,
						]
					);
					break;
				}
			}
		}
	}

	/**
	 * Prevent deletion of webhook for widget plugin.
	 *
	 * @since 1.4.0
	 *
	 * @param boolean $delete_webhook Whether to delete webhook.
	 * @param string  $bot_token      The bot token.
	 *
	 * @return boolean
	 */
	public function widget_delete_webhook( $delete_webhook, $bot_token ) {
		$bot_with_webhook = BotsUtils::get_bots_by_update_method( BotsUtils::UPDATE_METHOD_WEBHOOK );

		// if webhook is set as update method.
		if ( array_key_exists( $bot_token, $bot_with_webhook ) ) {
			$delete_webhook = false;
		}

		return $delete_webhook;
	}
}
