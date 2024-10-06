<?php
/**
 * The admin-specific functionality of the module.
 *
 * @link  https://wptelegram.pro
 * @since 1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots
 */

namespace WPTelegram\Pro\modules\bots;

use WPTelegram\BotAPI\API;
use WPTelegram\Pro\modules\BaseClass;
use WPTelegram\Pro\modules\bots\handlers\UpdateHandler;

/**
 * The admin-specific functionality of the module.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots
 * @author     WP Socio
 */
class Admin extends BaseClass {

	/** Updates the DOM data related to p2tg.
	 *
	 * @param array  $data The existing DOM data.
	 * @param string $for  The domain for which the DOM data is to be rendered.
	 *
	 * @return array
	 */
	public function update_dom_data( $data, $for ) {

		if ( 'SETTINGS_PAGE' === $for ) {

			$data['uiData'] = array_merge(
				$data['uiData'],
				[
					'webhook_url'      => Utils::get_webhook_url( '%bot_token%', Utils::WEBHOOK_ACTION ),
					'allowed_updates'  => Utils::get_allowed_updates( '%bot_token%' ),
					'long_polling_url' => add_query_arg( [ 'action' => Utils::LONG_POLLING_ACTION ], site_url() ),
				]
			);
		}

		return $data;
	}

	/**
	 * Handle Webhook payload
	 *
	 * @since 1.0.0
	 */
	public function handle_webhook_update() {

		$action    = sanitize_text_field( filter_input( INPUT_GET, 'action' ) );
		$bot_token = sanitize_text_field( filter_input( INPUT_GET, 'bot_token' ) );

		// block unauthorized request.
		if ( ! $bot_token || ! $action || Utils::WEBHOOK_ACTION !== $action ) {
			return;
		}

		$update_method = Utils::UPDATE_METHOD_WEBHOOK;

		/**
		 * Fires before doing anything
		 */
		do_action( 'wptelegram_pro_bots_webhook_update_init', $bot_token );
		do_action( "wptelegram_pro_bots_webhook_update_init_{$bot_token}" );

		$bots = Utils::get_bots_by_update_method( $update_method );

		// verify bot token.
		if ( empty( $bots ) || ! array_key_exists( $bot_token, $bots ) ) {
			return;
		}

		$json   = file_get_contents( 'php://input' );
		$update = json_decode( $json, true );

		// if is not a valid update.
		if ( empty( $update ) ) {
			return;
		}

		/**
		 * Fires after receiving the update
		 *
		 * This is the best hook to be used as the entry point
		 *
		 * Place your bot specific code inside its callback function
		 *
		 * @param array  $update        The updates array.
		 * @param string $bot_token     Telegram Bot Token.
		 * @param string $update_method The update method.
		 * @param array  $bots          Array of bots.
		 */
		do_action( 'wptelegram_pro_bots_after_get_update', $update, $bot_token, $update_method, $bots );
		do_action( "wptelegram_pro_bots_after_get_update_{$bot_token}", $update, $update_method, $bots );

		$bot_api = API::get_instance( $this->module()->name(), $bot_token );

		// Pass the update to the handler.
		$this->handle_update( $update, $bot_api );

		/**
		 * Fires after doing everything
		 *
		 * This should be used to clean up
		 * your bot specific code - remove actions/filters
		 */
		do_action( 'wptelegram_pro_bots_webhook_update_finish', $update, $bot_token, $bots );

		exit( 'Done :)' );
	}

	/**
	 * Pull updates from Telegram
	 *
	 * @since   1.0.0
	 */
	public function pull_updates() {

		$action = sanitize_text_field( filter_input( INPUT_GET, 'action' ) );

		// block unwanted request.
		if ( ! $action || Utils::LONG_POLLING_ACTION !== $action ) {
			return;
		}

		$update_method = Utils::UPDATE_METHOD_LONG_POLLING;

		$bot_token = sanitize_text_field( filter_input( INPUT_GET, 'bot_token' ) );

		/**
		 * $bots = array(
		 *  $bot_token => $bot_username,
		 * );
		 */
		$bots = Utils::get_bots_by_update_method( $update_method );

		// check if bot_token is passed.
		if ( $bot_token && array_key_exists( $bot_token, $bots ) ) {

			// From $bot_token to $bot_username.
			$_bots[ $bot_token ] = $bots[ $bot_token ];

			$bots = $_bots;
		}

		/**
		 * Fires before doing anything
		 */
		do_action( 'wptelegram_pro_bots_pull_updates_init', $bots );

		if ( empty( $bots ) ) {
			exit( 'Done ' . __LINE__ );
		}

		// Create a general/common/single instance.
		$bot_api = API::get_instance( $this->module()->name() );

		foreach ( $bots as $bot_token => $username ) {

			// Set the bot token for the instance.
			$bot_api->set_bot_token( $bot_token );

			$params = $this->get_update_params( $bot_token );

			// Pull updates.
			$res = $bot_api->getUpdates( $params );

			if ( ! $bot_api->is_success( $res ) ) {

				do_action( 'wptelegram_pro_bots_pull_updates_failed', $res, $bot_token );

				// Conflict: when webhook is active.
				if ( ! is_wp_error( $res ) && 409 === $res->get_response_code() && apply_filters( 'wptelegram_pro_bots_delete_webhook', true, $bot_token ) ) {
					$bot_api->deleteWebhook();
				}

				continue;
			}

			$updates = $res->get_result();

			/**
			 * Fires after receiving the update
			 *
			 * This is the best hook to be used as the entry point
			 *
			 * Place your bot specific code inside its callback function
			 *
			 * NOTE: The $updates array is an array of updates for long_polling
			 *
			 * @param array  $updates       The updates array.
			 * @param string $bot_token     Telegram Bot Token.
			 * @param string $update_method The update method.
			 * @param array  $bots          Array of bots.
			 */
			do_action( 'wptelegram_pro_bots_after_get_update', $updates, $bot_token, $update_method, $bots );
			do_action( "wptelegram_pro_bots_after_get_update_{$bot_token}", $updates, $update_method, $bots );

			if ( ! empty( $updates ) ) {

				foreach ( $updates as $update ) {

					$this->handle_update( $update, $bot_api );
				}

				// Save the last update_id.
				$transient = "wptelegram_pro_bots_{$bot_token}_last_update_id";
				set_transient( $transient, $update['update_id'] );
			}

			/**
			 * Fires after doing everything
			 *
			 * This should be used to clean up
			 * your bot specific code - remove actions/filters
			 */
			do_action( "wptelegram_pro_bots_{$bot_token}_pull_updates_finish" );
		}

		/**
		 * Fires after doing everything
		 */
		do_action( 'wptelegram_pro_bots_pull_updates_finish', $bots );

		exit( 'Done :)' );
	}

	/**
	 * Get params for getUpdates
	 *
	 * @since   1.0.0
	 *
	 * @param string $bot_token The bot token.
	 *
	 * @return array
	 */
	private function get_update_params( $bot_token = '' ) {

		$transient = "wptelegram_pro_bots_{$bot_token}_last_update_id";
		$update_id = (int) get_transient( $transient );

		$offset = 0;
		if ( $update_id ) {
			$offset = ++$update_id;
		}

		$allowed_updates = Utils::get_allowed_updates( $bot_token, Utils::UPDATE_METHOD_LONG_POLLING );

		return compact( 'offset', 'allowed_updates' );
	}

	/**
	 * Handle update.
	 *
	 * @since   1.0.0
	 *
	 * @param array $update An update from Telegram.
	 * @param API   $bot_api Unique Telegram Bot API object.
	 */
	private function handle_update( $update, $bot_api ) {

		$transient = 'wptelegram_pro_bots_processing_update_id_' . $update['update_id'];

		/**
		 * Check if the update is locked for processing
		 * to avoid duplicates
		 */
		if ( get_transient( $transient ) ) {
			return;
		}

		/**
		 * Fires before processing and update
		 * Can be used to create log etc.
		 *
		 * @param   array              $update   An update from Telegram
		 * @param   API  $bot_api    Unique Telegram Bot API object
		 */
		do_action( 'wptelegram_pro_bots_before_process_update', $update, $bot_api );

		// Initialize the update handler class.
		$handle = new UpdateHandler( $update, 'update' );

		$handle->set_update( $update );
		$handle->set_bot_api( $bot_api );

		/**
		 * Lock the update for processing
		 * Assuming that 600 seconds (10 minutes) (by default) are enough
		 * to process an update
		 */
		$expiration = (int) apply_filters( 'wptelegram_pro_bots_update_process_duration', 600 );

		set_transient( $transient, true, $expiration );

		// process the update.
		$res = $handle->process();

		// unlock the update if processed before expiration.
		delete_transient( $transient );

		/**
		 * Fires after processing and update
		 *
		 * @param   array $update  An update from Telegram
		 * @param   API   $bot_api Unique Telegram Bot API object
		 * @param   mixed $res     Response
		 */
		do_action( 'wptelegram_pro_bots_after_process_update', $update, $bot_api, $res );

		return $res;
	}
}
