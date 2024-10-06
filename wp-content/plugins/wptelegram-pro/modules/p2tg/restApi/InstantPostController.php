<?php
/**
 * P2Tg Instant Post endpoint for WordPress REST API.
 *
 * @link       https://wptelegram.pro
 * @since      1.4.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\p2tg\restApi
 */

namespace WPTelegram\Pro\modules\p2tg\restApi;

use WPTelegram\Pro\modules\p2tg\Main;
use WPTelegram\Pro\modules\p2tg\PostSender;
use WPTelegram\Pro\includes\restApi\RESTController;
use WPTelegram\Pro\includes\Utils;
use WP_REST_Server;
use WP_REST_Request;

/**
 * Class to handle the Instant Post endpoint.
 *
 * @since 1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\p2tg\restApi
 * @author     WP Socio
 */
class InstantPostController extends RESTController {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	const REST_BASE = '/p2tg-instant-post';

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {

		register_rest_route(
			self::REST_NAMESPACE,
			self::REST_BASE,
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'handle_request' ],
					'permission_callback' => [ $this, 'request_permissions' ],
					'args'                => self::get_params(),
				],
			]
		);
	}

	/**
	 * Check request permissions.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function request_permissions() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Handle instant post request
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request WP REST API request.
	 */
	public function handle_request( WP_REST_Request $request ) {

		$posts = $request->get_param( 'posts' );

		$ignore_all_rules = $request->get_param( 'ignore_all_rules' );

		if ( ! empty( $posts ) ) {

			$trigger = 'instant';

			$use_queue = empty( $params['use_queue'] ) ? '__return_false' : '__return_true';

			add_filter( "wptelegram_pro_p2tg_use_queue_for_{$trigger}", $use_queue );

			$post_sender = PostSender::instance();

			$results = [];

			foreach ( (array) $posts as $post_id ) {
				$post = get_post( $post_id );

				if ( $post ) {
					$result = $post_sender->send_post( $post, $trigger, [], $ignore_all_rules );

					$results[ $post_id ] = $result;
				}
			}

			return rest_ensure_response( $results );
		}

		return rest_ensure_response( false );
	}

	/**
	 * Retrieves the query params for the endpoint.
	 *
	 * @since 1.0.0
	 *
	 * @return array Query parameters for the endpoint.
	 */
	public static function get_params() {
		return [
			'posts'            => [
				'type'              => 'array',
				'items'             => [
					'type' => 'integer',
				],
				'required'          => true,
				'sanitize_callback' => [ __CLASS__, 'sanitize_param' ],
				'validate_callback' => 'rest_validate_request_arg',
			],
			Main::PREFIX       => [
				'type' => 'object',
			],
			'ignore_all_rules' => [
				'type' => 'boolean',
			],
		];
	}

	/**
	 * Sanitize the request param.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $value Value of the param.
	 */
	public static function sanitize_param( $value ) {
		// First lets make the value safer.

		return Utils::sanitize( $value );
	}
}
