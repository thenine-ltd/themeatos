<?php
/**
 * Handles /start command
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\commands
 */

namespace WPTelegram\Pro\modules\bots\commands;

/**
 * Handles /help command
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\commands
 * @author     WP Socio
 */
class StartCommandHandler extends CommandHandler {

	/**
	 * Instantiate the command
	 */
	public function __construct() {
		$this->name        = 'start';
		$this->aliases     = [ 'hi', 'hello' ];
		$this->description = __( 'Welcome', 'wptelegram-pro' );
	}

	/**
	 * Process the command
	 */
	public function process() {

		$origin = $this->get_trigger_origin();

		$chat_id = $origin->get_chat( 'id' );

		$fname = $origin->get_user( 'first_name' );

		$text = sprintf( /* translators: User's first name */ __( 'Welcome %s!', 'wptelegram-pro' ), $fname );

		$text .= PHP_EOL . sprintf( /* translators: Command name */ __( 'You can press %s to get started', 'wptelegram-pro' ), '/help' );

		$response = [
			'sendMessage' => compact( 'chat_id', 'text' ),
		];

		$response = apply_filters( "wptelegram_pro_bots_{$this->name}_command_response", $response, $origin, $this );

		if ( ! empty( $response ) ) {
			$params = reset( $response );
			$method = key( $response );
			return call_user_func( [ $origin->get_bot_api(), $method ], $params );
		}
	}
}
