<?php
/**
 * Message Handling functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 */

namespace WPTelegram\Pro\modules\bots\handlers;

use WPTelegram\Pro\includes\Utils;

/**
 * Handles the Message object of the update
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 * @author     WP Socio
 */
class MessageHandler extends BaseHandler {

	/**
	 * Get the known types in message object.
	 *
	 * @return array
	 */
	final public static function known_types() {
		return [
			'text',
			'audio',
			'document',
			'photo',
			'sticker',
			'video',
			'voice',
			'video_note',
			'contact',
			'location',
			'venue',
			'new_chat_members',
			'left_chat_member',
			'new_chat_title',
			'new_chat_photo',
			'delete_chat_photo',
			'group_chat_created',
			'supergroup_chat_created',
			'channel_chat_created',
			'migrate_to_chat_id',
			'migrate_from_chat_id',
			'pinned_message',
			'invoice',
			'successful_payment',
		];
	}

	/**
	 * Get the message caption
	 *
	 * @since   1.0.0
	 *
	 * @return string
	 */
	public function get_caption() {
		return $this->get( 'caption', '' );
	}

	/**
	 * Get the message or caption entities
	 *
	 * @since   1.0.0
	 *
	 * @return array
	 */
	public function get_entities() {
		$type = $this->get_type( self::known_types() );
		$key  = ( 'text' === $type ) ? 'entities' : 'caption_entities';
		return $this->get( $key, [] );
	}

	/**
	 * Process the message
	 *
	 * @since  1.0.0
	 */
	public function process() {

		$message_type = $this->get_type( self::known_types() );

		$camel_message_type = Utils::snake_to_camel( $message_type );

		// The class name to handle the message object.
		$handler = $camel_message_type ? __NAMESPACE__ . "\\Message{$camel_message_type}Handler" : null;

		$handler = apply_filters( 'wptelegram_pro_bots_message_type_handler', $handler, $message_type, $this );

		$handler = apply_filters( "wptelegram_pro_bots_message_{$message_type}_handler", $handler, $this );

		$res = false;

		if ( $handler && class_exists( $handler ) ) {
			// Every handler should extend the base object.
			if ( is_subclass_of( $handler, BaseHandler::class ) ) {
				// get the object from type.
				$object = $this->get( $message_type );

				$object = apply_filters( "wptelegram_pro_bots_message_{$message_type}", $object, $this );

				// pass the update object to the handler.
				$handle = new $handler( $object, $message_type, $this );

				do_action( "wptelegram_pro_bots_before_process_message_{$message_type}", $object, $this );

				// process the update object.
				$res = $handle->process();

				do_action( "wptelegram_pro_bots_after_process_message_{$message_type}", $object, $res, $this );

			} else {
				trigger_error( sprintf( '%1$s class should extend %2$s class' , $handler, BaseHandler::class ) ); // phpcs:ignore
			}
		} else {

			// when no handler is defined.
			do_action( 'wptelegram_pro_bots_process_message_unknown_handler', $message_type, $handler, $this );

			do_action( "wptelegram_pro_bots_process_message_{$message_type}_unknown_handler", $handler, $this );
		}

		do_action( 'wptelegram_pro_bots_process_message', $message_type, $this->get_current_object(), $this->get_bot_api(), $this );

		do_action( "wptelegram_pro_bots_process_message_{$message_type}", $this->get_current_object(), $this->get_bot_api(), $this );

		return $res;
	}
}
