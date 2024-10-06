<?php
/**
 * WP Telegram Pro bots Utilities
 *
 * @link       https://wptelegram.pro
 * @since     1.4.0
 *
 * @package WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots;
 */

namespace WPTelegram\Pro\modules\bots;

use WP_List_Util;

/**
 * WP Telegram Pro bots Utilities
 *
 * @link       https://wptelegram.pro
 * @since     1.4.0
 *
 * @package WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots;
 */
class Utils {

	const WEBHOOK_ACTION = 'wptelegram_pro_bots_webhook';

	const LONG_POLLING_ACTION = 'wptelegram_pro_bots_pull_updates';

	const UPDATE_METHOD_WEBHOOK = 'webhook';

	const UPDATE_METHOD_LONG_POLLING = 'long_polling';

	/**
	 * Get the webhook url
	 *
	 * @since  1.4.0
	 *
	 * @param string $bot_token Telegram bot token.
	 * @param string $action    The URL query param action value.
	 *
	 * @return string
	 */
	public static function get_webhook_url( $bot_token, $action ) {

		$args = [
			'bot_token' => $bot_token,
			'action'    => $action,
		];

		/**
		 * Add bot_token to the URL to make it secure
		 * because only admins know the bot token
		 */
		$webhook_url = add_query_arg( $args, site_url() );

		return apply_filters( 'wptelegram_pro_bots_webhook_url', $webhook_url, $args );
	}

	/**
	 * Get allowed update types for a bot.
	 *
	 * @since 1.4.0
	 *
	 * @param string $bot_token Telegram bot token.
	 * @param string $update_method string The update method - long_polling or webhook.
	 *
	 * @return string
	 */
	public static function get_allowed_updates( $bot_token, $update_method = self::UPDATE_METHOD_WEBHOOK ) {

		/**
		 * For example
		 * $allowed_updates = [
		 *   'message',
		 *   'inline_query',
		 *   'chosen_inline_result',
		 * ];
		*/
		$allowed_updates = []; // All updates by default.

		// Allow all updates by default.
		return (array) apply_filters( 'wptelegram_pro_bots_allowed_updates', $allowed_updates, $bot_token, $update_method );
	}

	/**
	 * Get all bots by update_method
	 *
	 * @since 1.4.0
	 *
	 * @param string $update_method string The update method - long_polling or webhook.
	 *
	 * @return array The array of bots
	 */
	public static function get_bots_by_update_method( $update_method = self::UPDATE_METHOD_WEBHOOK ) {
		$bots = [];

		$saved_bots = WPTG_Pro()->options()->get_path( 'bots.collection', [] );

		foreach ( $saved_bots as $bot ) {

			if ( $bot['update_method'] === $update_method ) {
				$bots[ $bot['bot_token'] ] = "@{$bot['bot_username']}";
			}
		}

		return apply_filters( 'wptelegram_pro_bots_by_update_method', $bots, $update_method );
	}

	/**
	 * Get the Bot Token from its username.
	 *
	 * @since 1.4.0
	 *
	 * @param  string $bot_username The bot username.
	 *
	 * @return string Telegram Bot Token
	 */
	public static function get_bot_token_from_username( $bot_username ) {

		$bot = self::get_bot_by( 'bot_username', $bot_username );

		$bot_token = ! empty( $bot['bot_token'] ) ? $bot['bot_token'] : '';

		return apply_filters( 'wptelegram_pro_bot_token_from_username', $bot_token, $bot_username );
	}

	/**
	 * Get the Bot Username from its token.
	 *
	 * @since 1.4.0
	 *
	 * @param  string $bot_token The bot token.
	 *
	 * @return string Telegram Bot Username
	 */
	public static function get_bot_username_from_token( $bot_token ) {

		$bot = self::get_bot_by( 'bot_token', $bot_token );

		$bot_username = ! empty( $bot['bot_username'] ) ? $bot['bot_username'] : '';

		return apply_filters( 'wptelegram_pro_bots_bot_username_from_token', $bot_username, $bot_token );
	}

	/**
	 * Get the Bot by field.
	 *
	 * @since 1.6.3
	 *
	 * @param  string $field The bot field. Can be 'bot_token' or 'bot_username'.
	 * @param  string $value The value of the field.
	 * @param  mixed  $default The default value to return if the bot is not found.
	 *
	 * @return array|null The bot collection item.
	 */
	public static function get_bot_by( $field, $value, $default = null ) {

		if ( ! $field ) {
			return $default;
		}

		$collection = WPTG_Pro()->options()->get_path( 'bots.collection', [] );

		$list = new WP_List_Util( $collection );

		$bots = $list->filter( [ $field => $value ] );

		$bot = reset( $bots );

		return apply_filters( 'wptelegram_pro_bots_get_bot_by', $bot, $field, $value );
	}
}
