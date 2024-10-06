<?php
/**
 * Update Handling functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 */

namespace WPTelegram\Pro\modules\bots\handlers;

use WPTelegram\Pro\includes\Utils;

/**
 * The Update Handling functionality of the plugin.
 *
 * @package    WPTelegram\pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 * @author     WP Socio
 */
class UpdateHandler extends BaseHandler {

	/**
	 * Get the known bot API types.
	 *
	 * @return array
	 */
	final public static function known_types() {
		return [
			'message',
			'edited_message',
			'channel_post',
			'edited_channel_post',
			'inline_query',
			'chosen_inline_result',
			'callback_query',
			'shipping_query',
			'pre_checkout_query',
		];
	}

	/**
	 * Process the update
	 *
	 * @since  1.0.0
	 */
	public function process() {

		$update_type = $this->get_type( self::known_types() );

		$camel_update_type = Utils::snake_to_camel( $update_type );

		// The class name to handle the update object.
		$handler = $camel_update_type ? __NAMESPACE__ . "\\{$camel_update_type}Handler" : null;

		$handler = apply_filters( 'wptelegram_pro_bots_update_type_handler', $handler, $update_type, $this );

		$handler = apply_filters( "wptelegram_pro_bots_update_{$update_type}_handler", $handler, $this );

		$res = false;

		$process_update = apply_filters( "wptelegram_pro_bots_process_update_{$update_type}", true, $this );

		if ( $handler && $process_update && class_exists( $handler ) ) {

			// Every handler should extend the base object.
			if ( is_subclass_of( $handler, BaseHandler::class ) ) {
				// get the object from type.
				$object = $this->get( $update_type );

				$object = apply_filters( "wptelegram_pro_bots_update_{$update_type}", $object, $this );

				// pass the update object to the handler.
				$handle = new $handler( $object, $update_type, $this );

				do_action( 'wptelegram_pro_bots_before_process_update_type', $update_type, $object, $this );

				do_action( "wptelegram_pro_bots_before_process_update_{$update_type}", $object, $this );

				// process the update object.
				$res = $handle->process();

				do_action( "wptelegram_pro_bots_after_process_update_{$update_type}", $object, $res, $this );

				do_action( 'wptelegram_pro_bots_after_process_update_type', $update_type, $object, $res, $this );

			} else {
				trigger_error( sprintf( '%1$s class should extend %2$s class', $handler, BaseHandler::class ) ); // phpcs:ignore
			}
		} else {

			// when no handler is defined.
			do_action( 'wptelegram_pro_bots_process_update_unknown_handler', $update_type, $handler, $this );

			do_action( "wptelegram_pro_bots_process_update_{$update_type}_unknown_handler", $handler, $this );
		}

		do_action( 'wptelegram_pro_bots_process_update', $update_type, $this->get_update(), $this->get_bot_api(), $this );

		do_action( "wptelegram_pro_bots_process_update_{$update_type}", $this->get_update(), $this->get_bot_api(), $this );

		return $res;
	}
}
