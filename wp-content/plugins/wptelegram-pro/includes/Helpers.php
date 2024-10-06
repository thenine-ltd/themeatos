<?php
/**
 * WP Telegram Pro Helpers
 *
 * @link       https://wptelegram.pro
 * @since     1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\includes;

use WPTelegram\BotAPI\Client;

/**
 * WP Telegram Pro Helpers
 *
 * @link       https://wptelegram.pro
 * @since     1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */
class Helpers {

	/**
	 * Get the bot options.
	 *
	 * @since  1.3.0
	 *
	 * @param string $module_bot The bot selected for a given module.
	 * @return array
	 */
	public static function get_bot_options( $module_bot = '' ) {

		$bots = WPTG_Pro()->options()->get_path( 'bots.collection', [] );

		$bot_options = [
			[
				'value' => '',
				'label' => '···',
			],
		];

		foreach ( $bots as $bot ) {

			$options = [
				'value'         => $bot['bot_username'],
				'label'         => "@{$bot['bot_username']}",
				'bot_token'     => $bot['bot_token'], // required for tests.
				'bot_username'  => $bot['bot_username'],
				'update_method' => $bot['update_method'],
			];

			if ( $bot['bot_username'] === $module_bot ) {
				$options['is_module_bot'] = 'true'; // avoid error for React non-boolean attribute.
			}

			$bot_options[] = $options;
		}

		return apply_filters( 'wptelegram_pro_bot_options', $bot_options, $bots );
	}

	/**
	 * Modify cURL handle
	 * The method is not used by default
	 * but can be used to modify
	 * the behavior of cURL requests
	 *
	 * @since 1.0.0
	 *
	 * @param \CurlHandle $handle  The cURL handle (passed by reference).
	 * @param array       $r       The HTTP request arguments.
	 * @param string      $url     The request URL.
	 *
	 * @return void
	 */
	public static function modify_http_api_curl_for_files( &$handle, $r, $url ) {

		$telegram_api_client = new Client();

		$bot_api_url = $telegram_api_client->get_base_url();

		// If it's a request to Telegram API base URL.
		$to_telegram = 0 === strpos( $url, $bot_api_url );

		$by_wptelegram = ! empty( $r['headers']['wptelegram_bot'] );

		// if the request is sent to Telegram by WP Telegram.
		if ( $to_telegram && $by_wptelegram ) {

			$types = [ 'animation', 'photo', 'audio', 'video', 'document' ];

			foreach ( $types as $type ) {

				if ( ! empty( $r['body'][ $type ] ) && file_exists( $r['body'][ $type ] ) ) {

					$r['body'][ $type ] = curl_file_create( $r['body'][ $type ] ); // phpcs:ignore
					curl_setopt( $handle, CURLOPT_POSTFIELDS, $r['body'] ); // phpcs:ignore
					break;
				}
			}

			if ( ! empty( $r['body']['media'] ) ) {
				$media_items = json_decode( $r['body']['media'], true );
				if ( json_last_error() === JSON_ERROR_NONE ) {
					$num     = 1;
					$updated = false;
					foreach ( $media_items as &$media_item ) {
						if ( ! empty( $media_item['media'] ) && file_exists( $media_item['media'] ) ) {
							$updated = true;
							// create a unique key for the mdeia item.
							$key = 'wpmedia' . ( $num++ );
							// create a file handle for the media.
							$r['body'][ $key ] = curl_file_create( $media_item['media'] ); // phpcs:ignore
							// set the key in POST body.
							$media_item['media'] = 'attach://' . $key;
						}
					}
					if ( $updated ) {
						$r['body']['media'] = wp_json_encode( $media_items );
						curl_setopt( $handle, CURLOPT_POSTFIELDS, $r['body'] ); // phpcs:ignore
					}
				}
			}
		}
	}

	/**
	 * Bypass wpautop() from the given filter
	 * to preserve newlines.
	 *
	 * @since 1.0.0
	 *
	 * @param string $tag The name of the filter hook like "the_content".
	 */
	public static function bypass_wpautop_for( $tag ) {
		$priority = has_filter( $tag, 'wpautop' );
		if ( false !== $priority ) {
			remove_filter( $tag, 'wpautop', $priority );
			add_filter( $tag, [ __CLASS__, 'restore_wpautop_hook' ], $priority + 1 );
		}
	}

	/**
	 * Re-add wp_autop() to the given filter.
	 *
	 * @access public
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The post content running through this filter.
	 * @return string The unmodified content.
	 */
	public static function restore_wpautop_hook( $content ) {
		$tag = current_filter();

		$current_priority = has_filter( $tag, [ __CLASS__, 'restore_wpautop_hook' ] );

		add_filter( $tag, 'wpautop', $current_priority - 1 );
		remove_filter( $tag, [ __CLASS__, 'restore_wpautop_hook' ], $current_priority );

		return $content;
	}
}
