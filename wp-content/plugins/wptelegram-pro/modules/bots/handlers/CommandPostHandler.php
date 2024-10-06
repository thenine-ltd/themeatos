<?php
/**
 * Command post Handling functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 */

namespace WPTelegram\Pro\modules\bots\handlers;

use WP_Post;

/**
 * Handles the command post
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots\handlers
 * @author     WP Socio
 */
class CommandPostHandler extends BaseHandler {

	/**
	 * Process the command post
	 *
	 * @since  1.0.0
	 */
	public function process() {
		// the post associated with the command.
		$command_post = $this->get_current_object();

		$command_data = $this->get_command_post_data( $command_post );

		/**
		 * Fires just after a command is triggered.
		 *
		 * @param   WP_Post $command_post   The post associated with the command.
		 * @param   array   $command_data   The command post meta.
		 * @param   object  $this           Current class object.
		 */
		do_action( 'wptelegram_pro_bots_command_post_process_init', $command_post, $command_data, $this );

		$chat_id = $this->get_chat( 'id' );
		$text    = $command_data['response'];

		if ( empty( $text ) ) {
			return;
		}

		// allow more than one response.
		$responses[] = [
			'method' => 'sendMessage',
			'params' => compact( 'chat_id', 'text' ),
		];

		$responses = (array) apply_filters( 'wptelegram_pro_bots_command_post_responses', $responses, $command_post, $command_data, $this );

		if ( empty( $responses ) ) {
			return;
		}

		/**
		 * Fires just before any response is sent.
		 *
		 * @param   WP_Post $command_post   The post associated with the command.
		 * @param   array   $command_data   The command post meta.
		 * @param   array   $responses      The responses array.
		 * @param   object  $this           Current class object.
		 */
		do_action( 'wptelegram_pro_bots_command_post_before_response', $command_post, $command_data, $responses, $this );

		$res_array = [];

		foreach ( $responses as $response ) {
			$res_array[] = call_user_func( [ $this->get_bot_api(), $response['method'] ], $response['params'] );
		}

		/**
		 * Fires just after the responses are sent
		 *
		 * @param   WP_Post $command_post   The post associated with the command.
		 * @param   array   $command_data   The command post meta.
		 * @param   array   $res_array      The API responses array.
		 * @param   object  $this           Current class object.
		 */
		do_action( 'wptelegram_pro_bots_command_post_after_response', $command_post, $command_data, $res_array, $this );
		return $res_array;
	}

	/**
	 * Get the meta associated with the command post
	 *
	 * @since  1.0.0
	 *
	 * @param   WP_Post $command_post   The post associated with the command.
	 */
	public function get_command_post_data( $command_post = null ) {

		if ( is_null( $command_post ) ) {
			$command_post = $this->get_current_object();
		}
		// basic command data.
		$data_fields = [
			'name'     => '',
			'aliases'  => '',
			'desc'     => '',
			'response' => '',
		];

		$data_fields = (array) apply_filters( 'wptelegram_pro_bots_command_post_data_fields', $data_fields, $command_post );

		$command_data = [];

		// this prefix is a must for all the fields.
		$prefix = '_bot_cmd_';
		foreach ( $data_fields as $key => $value ) {
			$command_data[ $key ] = get_post_meta( $command_post->ID, $prefix . $key, true );
		}

		return apply_filters( 'wptelegram_pro_bots_command_post_data', $command_data, $command_post );
	}
}
