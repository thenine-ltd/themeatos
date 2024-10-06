<?php
/**
 * Text Handling functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 */

namespace WPTelegram\Pro\modules\bots\handlers;

/**
 * Text Handling functionality of the plugin.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 * @author     WP Socio
 */
class MessageTextHandler extends BaseHandler {

	/**
	 * Process the text
	 *
	 * @since   1.0.0
	 */
	public function process() {

		// the text of the message.
		$text = $this->get();

		// due to any reason :).
		if ( empty( $text ) ) {
			return;
		}

		// check if the text is a command.
		if ( $this->is_command( $text ) ) {
			/**
			 * Parse the command
			 *
			 * Back references:
			 * [1] Command name
			 * [2] Bot Username (optional)
			 * [3] Command Arguments (optional)
			 */
			$data = $this->parse_command( $text );

			reset( $data );

			$command      = next( $data );
			$bot_username = next( $data );
			$arguments    = next( $data );

			// trigger the command.
			return $this->trigger_command( $command, $arguments, $bot_username );
		}
		// non command text.
		return $this->handle_normal_text( $text );
	}

	/**
	 * Handle a non command text.
	 *
	 * @since  1.0.0
	 *
	 * @param string $text The message text.
	 */
	private function handle_normal_text( $text ) {

		$chat_id = $this->get_chat( 'id' );

		$text = sprintf( /* translators: command name */ __( 'You can press %s to get started', 'wptelegram-pro' ), '/help' );

		$response = [
			'sendMessage' => compact( 'chat_id', 'text' ),
		];

		$response = apply_filters( 'wptelegram_pro_bots_message_text_response', $response, $this );

		if ( ! empty( $response ) ) {
			$params = reset( $response );
			$method = key( $response );
			return call_user_func( [ $this->get_bot_api(), $method ], $params );
		}

		$text = $this->get();

		do_action( 'wptelegram_pro_bots_process_message_text', $text, $this->get_parent_handler(), $this );

		do_action( "wptelegram_pro_bots_process_message_text_{$text}", $this->get_parent_handler(), $this );
	}
}
