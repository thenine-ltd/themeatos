<?php
/**
 * The public-specific functionality of the module.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots
 */

namespace WPTelegram\Pro\modules\bots;

use WPTelegram\Pro\modules\BaseClass;

/**
 * The public-specific functionality of the module.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots
 * @author     WP Socio
 */
class Shared extends BaseClass {

	/**
	 * The module instance.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var string $module The module instance.
	 */
	protected $module;

	/**
	 * Trigger pull_updates action.
	 *
	 * @since    1.0.0
	 */
	public function trigger_pull_updates() {

		$action = sanitize_text_field( filter_input( INPUT_GET, 'action' ) );
		$action = $action ? $action : sanitize_text_field( filter_input( INPUT_POST, 'action' ) );

		// Avoid infinite loop.
		if ( Utils::LONG_POLLING_ACTION === $action ) {
			return;
		}

		/**
		 * Whether to trigger pull updates or not. Useful if you have already setup webhook
		 */
		if ( ! apply_filters( 'wptelegram_pro_bots_trigger_pull_updates', true ) ) {
			return;
		}

		$bots = WPTG_Pro()->options()->get_path( 'bots.collection', [] );

		if ( empty( $bots ) ) {
			return;
		}

		// return if already checked for updates in long_polling_interval.
		$transient = 'wptelegram_pro_bots_triggered_pull_updates';
		if ( get_transient( $transient ) ) {
			return;
		}
		/**
		 * Send a non-blocking request to site_url
		 * to reduce the processing time of the page
		 * The update process will be completed in the background
		 */
		$args = [
			'action' => Utils::LONG_POLLING_ACTION,
		];

		$trigger_url = add_query_arg( $args, site_url() );
		$args        = [
			'timeout'  => 0.1,
			'blocking' => false,
		];
		wp_remote_get( $trigger_url, $args );

		// expiration for the transient in seconds
		// default to 5 minutes (300 seconds).
		$expiration = (int) apply_filters( 'wptelegram_pro_bots_trigger_updates_interval', 300 );

		// prevent the deadlock in some cases.
		delete_transient( $transient );

		set_transient( $transient, true, $expiration );
	}
}
