<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\shared
 */

namespace WPTelegram\Pro\shared;

use WPTelegram\Pro\includes\BaseClass;
use WPTelegram\Pro\includes\Helpers;
use WPTelegram\Pro\includes\Utils;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\shared
 * @author     WP Socio
 */
class Shared extends BaseClass {

	const INTERVAL_ONE_MINUTELY  = 'wptelegram_one_minutely';
	const INTERVAL_TWO_MINUTELY  = 'wptelegram_two_minutely';
	const INTERVAL_FIVE_MINUTELY = 'wptelegram_five_minutely';
	const INTERVAL_TEN_MINUTELY  = 'wptelegram_ten_minutely';

	/**
	 * Add custom schedules
	 *
	 * @since   1.0.0
	 *
	 * @param array $schedules The array of cron schedules.
	 * @return array
	 */
	public function custom_cron_schedules( $schedules ) {

		return array_merge(
			$schedules,
			[
				self::INTERVAL_ONE_MINUTELY  => [
					'interval' => MINUTE_IN_SECONDS, // Intervals in seconds.
					'display'  => __( 'Every Minute', 'wptelegram-pro' ),
				],
				self::INTERVAL_TWO_MINUTELY  => [
					'interval' => 2 * MINUTE_IN_SECONDS, // Intervals in seconds.
					'display'  => __( 'Every 2 Minutes', 'wptelegram-pro' ),
				],
				self::INTERVAL_FIVE_MINUTELY => [
					'interval' => 5 * MINUTE_IN_SECONDS, // Intervals in seconds.
					'display'  => __( 'Every 5 Minutes', 'wptelegram-pro' ),
				],
				self::INTERVAL_TEN_MINUTELY  => [
					'interval' => 10 * MINUTE_IN_SECONDS, // Intervals in seconds.
					'display'  => __( 'Every 10 Minutes', 'wptelegram-pro' ),
				],
			]
		);
	}

	/**
	 * Whether to send files by URL.
	 *
	 * @since 1.4.0
	 */
	public static function send_files_by_url() {
		$send_files_by_url = WPTG_Pro()->options()->get_path( 'advanced.send_files_by_url', true );

		return (bool) apply_filters( 'wptelegram_pro_send_files_by_url', $send_files_by_url );
	}

	/**
	 * May be hook into curl for uploading files.
	 *
	 * @since 1.4.0
	 */
	public static function hook_into_curl_for_files() {
		// if modify curl for WP Telegram Pro.
		if ( ! self::send_files_by_url() ) {
			// modify curl.
			add_action( 'http_api_curl', [ Helpers::class, 'modify_http_api_curl_for_files' ], 10, 3 );
		}
	}

	/**
	 * Unhook from curl for uploading files.
	 *
	 * @since 1.4.0
	 */
	public static function unhook_from_curl_for_files() {
		// remove cURL modification.
		remove_action( 'http_api_curl', [ Helpers::class, 'modify_http_api_curl_for_files' ], 10, 3 );
	}

	/**
	 * Sanitise REST API params for Bot API.
	 *
	 * @since 1.4.0
	 *
	 * @param mixed $safe_value Sanitized value.
	 * @param mixed $raw_value  Unsanitized (raw) value.
	 */
	public static function bot_api_rest_sanitize_params( $safe_value, $raw_value ) {
		foreach ( [ 'text', 'caption' ] as $key ) {
			if ( isset( $safe_value[ $key ] ) ) {
				$safe_value[ $key ] = Utils::sanitize_message_template( $raw_value[ $key ] );
			}
		}
		return $safe_value;
	}
}
