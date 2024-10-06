<?php
/**
 * Callback_Query Handling functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 */

namespace WPTelegram\Pro\modules\bots\handlers;

/**
 * Handles the Callback_Query object of the update
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 * @author     WP Socio
 */
class CallbackQueryHandler extends BaseHandler {

	/**
	 * Process the callback query
	 *
	 * @since  1.0.0
	 */
	public function process() {

		$callback_query = $this->get();

		// callback_query_id.
		$callback_query_id = $callback_query['id'];

		$params = apply_filters( 'wptelegram_pro_bots_answer_callback_query_params', compact( 'callback_query_id' ), $callback_query, $this );

		$this->get_bot_api()->answerCallbackQuery( $params );

		// Sender.
		$user = $this->get( 'from' );
		// Message with the callback button that originated the query.
		$message = $this->get( 'message' );
		// Data associated with the callback button.
		$data = $this->get( 'data' );

		/**
		 * It is assumed that callback_data will be of the form
		 * action@arguments, @ used as a delimiter
		 *
		 * Arguments part can further be divided by using a delimiter
		 *
		 * Example: rate@post|5, edit@user|123, delete@post|123|field
		 */
		$data = explode( '@', $data );

		$action = reset( $data );

		$arguments = next( $data );

		do_action( 'wptelegram_pro_bots_process_callback_query', $action, $arguments, $message, $this->get_bot_api(), $this );

		do_action( "wptelegram_pro_bots_process_callback_query_{$action}", $arguments, $message, $this->get_bot_api(), $this );
	}
}
