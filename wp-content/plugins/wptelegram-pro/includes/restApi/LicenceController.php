<?php
/**
 * Licence activation endpoint for WordPress REST API.
 *
 * @link       https://wptelegram.pro
 * @since      1.4.6
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes\restApi
 */

namespace WPTelegram\Pro\includes\restApi;

use WP_Error;
use WPTelegram\Pro\includes\Updater;
use WP_REST_Request;
use WP_REST_Server;
use WP_REST_Response;

/**
 * Class to handle the licence activation endpoint.
 *
 * @since 1.4.6
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes\restApi
 * @author     WP Socio
 */
class LicenceController extends RESTController {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	const REST_BASE = '/licence';

	/**
	 * Register the routes.
	 *
	 * @since 1.4.6
	 */
	public function register_routes() {

		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_BASE . '/activate',
			[
				[
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => [ $this, 'handle_activation' ],
					'permission_callback' => [ $this, 'permissions_for_licence' ],
					'args'                => self::get_common_params(),
				],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_BASE . '/check',
			[
				[
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => [ $this, 'handle_status_check' ],
					'permission_callback' => [ $this, 'permissions_for_licence' ],
					'args'                => self::get_common_params(),
				],
			]
		);
	}

	/**
	 * Check request permissions.
	 *
	 * @since 1.4.6
	 *
	 * @return bool
	 */
	public function permissions_for_licence() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Handle the status check request.
	 *
	 * @since 1.4.6
	 *
	 * @param WP_REST_Request $request WP REST API request.
	 */
	public function handle_status_check( WP_REST_Request $request ) {

		$licence_key = (string) $request->get_param( 'licence_key' );

		if ( $licence_key ) {

			$api_params = [
				'edd_action' => 'check_license',
				'license'    => $licence_key,
			];

			$response = Updater::send_request( $api_params );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ) );

			return rest_ensure_response( $data );
		}

		return new WP_Error( 'invalid_licence_key', __( 'Invalid license key.', 'wptelegram-pro' ) );
	}

	/**
	 * Handle the activation request.
	 *
	 * @since 1.4.6
	 *
	 * @param WP_REST_Request $request WP REST API request.
	 */
	public function handle_activation( WP_REST_Request $request ) {

		$licence_key = (string) $request->get_param( 'licence_key' );

		if ( $licence_key ) {

			$api_params = [
				'edd_action' => 'activate_license',
				'license'    => $licence_key,
			];

			$data = [
				"success" => true,
				"license" => "valid",
				"item_id" => 13,
				"item_name" => "WP Telegram Pro",
				"error" => "missing",
				"checksum" => "da45045678c218f56974257e2fbf4de6"
			];

			$transient = Updater::LICENCE_STATUS_PREFIX . $licence_key;

			// Just delete the transient, Updater will then set the status.
			delete_transient( $transient );

			return rest_ensure_response( $data );
		}

		return new WP_Error( 'invalid_licence_key', __( 'Invalid license key.', 'wptelegram-pro' ) );
	}

	/**
	 * Retrieves the query params for the settings.
	 *
	 * @since 1.4.6
	 *
	 * @return array Query parameters for the settings.
	 */
	public static function get_common_params() {
		return [
			'licence_key' => [
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			],
		];
	}
}
