<?php
/**
 * The file that defines the module
 *
 * A class definition that includes attributes and functions used across the module
 *
 * @link  https://wptelegram.pro
 * @since 1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots
 */

namespace WPTelegram\Pro\modules\bots;

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

	/**
	 * Register all of the hooks.
	 *
	 * @since    1.4.0
	 * @access   protected
	 */
	protected function define_necessary_hooks() {

		$admin = Admin::instance();

		// To be used for webhook updates.
		add_action( 'init', [ $admin, 'handle_webhook_update' ] );

		// To be used for pulling updates (long polling).
		add_action( 'init', [ $admin, 'pull_updates' ] );

		add_filter( 'wptelegram_pro_assets_dom_data', [ $admin, 'update_dom_data' ], 10, 2 );

		$public = Shared::instance();

		add_action( 'init', [ $public, 'trigger_pull_updates' ] );
		// Disable message and channel post processing by default.
		add_action( 'wptelegram_pro_bots_process_update_message', '__return_false' );
		add_action( 'wptelegram_pro_bots_process_update_edited_message', '__return_false' );
		add_action( 'wptelegram_pro_bots_process_update_channel_post', '__return_false' );
		add_action( 'wptelegram_pro_bots_process_update_edited_channel_post', '__return_false' );
	}
}
