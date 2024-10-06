<?php
/**
 * Handles /help command
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
class HelpCommandHandler extends CommandHandler {

	/**
	 * Instantiate the command
	 */
	public function __construct() {
		$this->name        = 'help';
		$this->aliases     = [ 'listcommands' ];
		$this->description = __( 'Get the command list', 'wptelegram-pro' );
	}

	/**
	 * Process the command
	 */
	public function process() {

		$origin  = $this->get_trigger_origin();
		$bot_api = $origin->get_bot_api();

		$commands = $this->get_command_bus()->get_commands();

		$text = __( 'Command list', 'wptelegram-pro' ) . PHP_EOL . PHP_EOL;

		// remove /start command.
		unset( $commands['start'] );

		foreach ( $commands as $name => $handler ) {
			$text .= sprintf( '/%s - %s' . PHP_EOL, $handler->get_name(), $handler->get_description() );
		}

		$command_posts = []; // phpcs:ignore - WPTG_Pro()->helpers->get_command_posts( $bot_api->get_bot_token() );

		$prefix = '_bot_cmd_';
		foreach ( $command_posts as $command ) {

			$name = get_post_meta( $command->ID, $prefix . 'name', true );
			$desc = get_post_meta( $command->ID, $prefix . 'desc', true );

			// avoid if handled by a class.
			if ( ! array_key_exists( strtolower( $name ), $commands ) ) {

				$text .= sprintf( '/%s - %s' . PHP_EOL, $name, $desc );
			}
		}

		$chat_id = $origin->get_chat( 'id' );

		$response = [
			'sendMessage' => compact( 'chat_id', 'text' ),
		];

		$response = apply_filters( "wptelegram_pro_bots_{$this->name}_command_response", $response, $origin, $this );

		if ( ! empty( $response ) ) {
			$params = reset( $response );
			$method = key( $response );
			return call_user_func( [ $bot_api, $method ], $params );
		}
	}
}
