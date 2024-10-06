<?php
/**
 * Import plugin settings endpoint for WordPress REST API.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.5
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes\restApi
 */

namespace WPTelegram\Pro\includes\restApi;

use WPTelegram\Pro\modules\p2tg\Main as P2TGMain;
use WPTelegram\Pro\modules\p2tg\restApi\SettingsController as P2TGSettingsController;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;

/**
 * Class to handle import settings endpoint.
 *
 * @since 1.0.5
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes\restApi
 * @author     WP Socio
 */
class ImportSettingsController extends RESTController {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	const REST_BASE = '/import-settings';

	/**
	 * Register the routes.
	 *
	 * @since 1.0.5
	 */
	public function register_routes() {

		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_BASE,
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'handle_import' ],
					'permission_callback' => [ $this, 'permissions_for_import' ],
					'args'                => self::get_test_params(),
				],
			]
		);
	}

	/**
	 * Check request permissions.
	 *
	 * @since 1.0.5
	 *
	 * @return bool
	 */
	public function permissions_for_import() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Handle the import settings request.
	 *
	 * @since 1.0.5
	 *
	 * @param WP_REST_Request $request WP REST API request.
	 */
	public function handle_import( WP_REST_Request $request ) {

		$wptelegram_ver = get_option( 'wptelegram_ver' );

		if ( ! $wptelegram_ver || ! version_compare( $wptelegram_ver, '3.0.0', '>=' ) ) {
			return new WP_REST_Response( [ 'ok' => false ], 200 );
		}

		$settings = SettingsController::get_default_settings();

		$wptelegram = json_decode( get_option( 'wptelegram', '' ), true );

		$wptelegram_p2tg   = $wptelegram['p2tg'];
		$wptelegram_notify = $wptelegram['notify'];
		$wptelegram_proxy  = $wptelegram['proxy'];

		$bot_token    = ! empty( $wptelegram['bot_token'] ) ? $wptelegram['bot_token'] : '';
		$bot_username = ! empty( $wptelegram['bot_username'] ) ? $wptelegram['bot_username'] : '';

		if ( $bot_token && $bot_username ) {
			// override the collection.
			$settings['bots']['collection'] = [
				[
					'bot_token'     => $bot_token,
					'bot_username'  => $bot_username,
					'update_method' => 'none',
				],
			];
		}

		// Update p2tg settings.
		$settings['p2tg'] = array_merge(
			$settings['p2tg'],
			[
				'active'           => ! empty( $wptelegram_p2tg['active'] ),
				'bot'              => $bot_username,
				'plugin_posts'     => ! empty( $wptelegram_p2tg['plugin_posts'] ),
				'post_edit_switch' => ! empty( $wptelegram_p2tg['post_edit_switch'] ),
				'protect_content'  => ! empty( $wptelegram_p2tg['protect_content'] ),
			]
		);

		$wptelegram_notify = self::update_parse_mode_field( $wptelegram_notify, 'HTML' );

		// Update notify settings.
		$settings['notify'] = array_merge(
			$settings['notify'],
			[
				'active'             => ! empty( $wptelegram_notify['active'] ),
				'bot'                => $bot_username,
				'catch_emails'       => [
					[
						'email'    => ! empty( $wptelegram_notify['watch_emails'] ) ? $wptelegram_notify['watch_emails'] : '',
						'chat_ids' => ! empty( $wptelegram_notify['chat_ids'] ) ? $wptelegram_notify['chat_ids'] : [],
					],
				],
				'user_notifications' => ! empty( $wptelegram_notify['user_notifications'] ),
				'message_template'   => ! empty( $wptelegram_notify['message_template'] ) ? $wptelegram_notify['message_template'] : $settings['notify']['message_template'],
				'parse_mode'         => $wptelegram_notify['parse_mode'],
			]
		);

		// Update proxy settings.
		$settings['proxy'] = array_merge(
			$settings['proxy'],
			[
				'active' => ! empty( $wptelegram_proxy['active'] ),
			],
			$wptelegram_proxy
		);

		// Update advanced settings.
		$settings['advanced'] = array_merge(
			$settings['advanced'],
			[
				'send_files_by_url' => ! empty( $wptelegram['advanced']['send_files_by_url'] ),
				'licence_key'       => (string) $request->get_param( 'licence_key' ),
			]
		);

		// Update plugin settings.
		WPTG_Pro()->options()->set_data( $settings )->update_data();

		/**
		 * Now create p2tg instance
		 */
		// channels cannot be empty if p2tg is used.
		if ( ! empty( $wptelegram_p2tg['channels'] ) ) {
			$new_instance = [
				'post_author' => get_current_user_id(),
				'post_status' => 'publish',
				'post_title'  => 'Default',
				'post_type'   => P2TGMain::CPT_NAME,
			];

			$new_instance_id = wp_insert_post( wp_slash( $new_instance ) );

			if ( $new_instance_id && ! is_wp_error( $new_instance_id ) ) {

				// Remove useless fields.
				unset( $wptelegram_p2tg['plugin_posts'], $wptelegram_p2tg['post_edit_switch'] );

				$fields = array_merge(
					P2TGSettingsController::get_default_values(),
					$wptelegram_p2tg,
					[
						'active' => true,
					]
				);

				$fields = self::update_parse_mode_field( $fields );

				$json_fields = [
					'message_template',
				];

				foreach ( $fields as $field => $value ) {
					$meta_key = P2TGMain::PREFIX . $field;
					if ( in_array( $field, $json_fields, true ) ) {
						// slashes to preserve json fields.
						$value = addslashes( wp_json_encode( $value ) );
					}
					update_post_meta( $new_instance_id, $meta_key, $value );
				}
			}
		}

		return new WP_REST_Response( [ 'ok' => true ], 200 );
	}

	/**
	 * Updates the parse_mode field in the given array.
	 *
	 * @since 1.4.0
	 *
	 * @param array  $fields  The fields array which contains 'parse_mode'.
	 * @param string $default The default value for Parse Mode.
	 *
	 * @return array Updated fields.
	 */
	public static function update_parse_mode_field( $fields, $default = 'none' ) {
		if ( empty( $fields['parse_mode'] || 'HTML' !== $fields['parse_mode'] ) ) {
			$fields['parse_mode'] = $default;
		}
		return $fields;
	}

	/**
	 * Retrieves the query params for the settings.
	 *
	 * @since 1.0.5
	 *
	 * @return array Query parameters for the settings.
	 */
	public static function get_test_params() {
		return [
			'licence_key' => [
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}
}
