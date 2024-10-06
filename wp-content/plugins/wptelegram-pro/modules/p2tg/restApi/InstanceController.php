<?php
/**
 * P2tg insatnce controller.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro;
 * @subpackage WPTelegram\Pro\modules\p2tg\restApi;
 */

namespace WPTelegram\Pro\modules\p2tg\restApi;

use WP_REST_Posts_Controller;
use WP_Error;
use WP_REST_Request;

/**
 * Class to handle the p2tg instance REST requests.
 *
 * @since 1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\p2tg\restApi
 * @author     WP Socio
 */
class InstanceController extends WP_REST_Posts_Controller {

	/**
	 * Checks if a given request has access to read posts.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {

		$permission = parent::get_items_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to read posts in this post type.', 'wptelegram-pro' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to read a post.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool|WP_Error True if the request has read access for the item, WP_Error object otherwise.
	 */
	public function get_item_permissions_check( $request ) {

		$permission = parent::get_item_permissions_check( $request );
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return new WP_Error( 'rest_forbidden_context', __( 'Sorry, you are not allowed to read posts in this post type.', 'wptelegram-pro' ), [ 'status' => rest_authorization_required_code() ] );
		}

		return true;
	}
}
