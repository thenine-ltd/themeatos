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

namespace WPTelegram\Pro\modules\notify;

use WPTelegram\Pro\modules\BaseModule;

/**
 * The module main class.
 *
 * @since      1.4.0
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules
 * @author     WP Socio
 */
class Main extends BaseModule {

	const CRON_HOOK = 'wptelegram_pro_notify_cron_hook';

	/**
	 * Register all of the hooks.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected function define_necessary_hooks() {
		$admin = Admin::instance();

		add_filter( 'wptelegram_pro_assets_dom_data', [ $admin, 'update_dom_data' ], 10, 2 );
	}

	/**
	 * Register all of the hooks.
	 *
	 * @since    1.4.0
	 * @access   private
	 */
	protected function define_on_active_hooks() {

		$handler = NotifyHandler::instance();

		// BBPress integration.
		add_action( 'bbp_new_reply', [ $handler, 'integrate_bbpress' ] );
		add_action( 'bbp_new_topic', [ $handler, 'integrate_bbpress' ] );

		add_filter( 'wp_mail', [ $handler, 'handle_wp_mail' ], 99999, 1 );

		add_action( self::CRON_HOOK, [ $handler, 'run_notify_cron' ], 10, 1 );
	}
}
