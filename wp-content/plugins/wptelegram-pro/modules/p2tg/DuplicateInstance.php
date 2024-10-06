<?php
/**
 * Instance duplication functionality of the plugin.
 *
 * @link        https://wptelegram.pro
 * @since       1.0.0
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 */

namespace WPTelegram\Pro\modules\p2tg;

use WPTelegram\Pro\modules\BaseClass;
use WPTelegram\Pro\modules\p2tg\restApi\SettingsController;

/**
 * The Instance duplication functionality of the plugin.
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 * @author      WP Socio
 */
class DuplicateInstance extends BaseClass {

	const ACTION = 'duplicate_inst';

	/**
	 * The nonce name prefix.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var string $action The duplicate action name.
	 */
	public static $nonce_prefix = 'duplicate-inst_';

	/**
	 * Show the "Duplicate" link in instance list.
	 *
	 * @param array   $actions Array of actions.
	 * @param WP_Post $post Post object.
	 * @return array
	 */
	public function duplicate_link( $actions, $post ) {
		if ( ! current_user_can( Main::instances_cap() ) ) {
			return $actions;
		}

		if ( Main::CPT_NAME !== $post->post_type || 'trash' === $post->post_status ) {
			return $actions;
		}

		$duplicate_link = admin_url( sprintf( 'admin.php?action=%1$s&post=%2$d', self::ACTION, $post->ID ) );
		$duplicate_link = wp_nonce_url( $duplicate_link, self::$nonce_prefix . $post->ID );

		$actions[ self::ACTION ] = sprintf(
			'<a href="%1$s" aria-label="%2$s" rel="permalink">%3$s</a>',
			$duplicate_link,
			esc_attr__( 'Duplicate this instance', 'wptelegram-pro' ),
			esc_html__( 'Duplicate', 'wptelegram-pro' )
		);

		return $actions;
	}

	/**
	 * Duplicate a instance action.
	 */
	public function duplicate_instance_action() {
		if ( empty( $_REQUEST['post'] ) ) {
			wp_die( esc_html__( 'No instance to duplicate has been supplied!', 'wptelegram-pro' ) );
		}

		$instance_id = isset( $_REQUEST['post'] ) ? absint( $_REQUEST['post'] ) : '';

		check_admin_referer( self::$nonce_prefix . $instance_id );

		$instance = get_post( $instance_id );

		if ( ! $instance ) {
			/* translators: %s: instance id */
			wp_die( sprintf( esc_html__( 'Instance creation failed, could not find original instance: %s', 'wptelegram-pro' ), esc_html( $instance_id ) ) );
		}

		$this->duplicate_instance( $instance );

		// Redirect to the instance list screen.
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . Main::CPT_NAME ) );
		exit;
	}

	/**
	 * Function to create the duplicate of the instance.
	 *
	 * @param WP_Post $instance The instance to duplicate.
	 * @return WP_Post The duplicate.
	 */
	public function duplicate_instance( $instance ) {
		$new_instance = [
			'post_author' => $instance->post_author,
			'post_status' => $instance->post_status,
			'post_title'  => $instance->post_title . ' - ' . __( 'Duplicate', 'wptelegram-pro' ),
			'post_type'   => Main::CPT_NAME,
		];

		$new_instance_id = wp_insert_post( wp_slash( $new_instance ) );

		if ( $new_instance_id && ! is_wp_error( $new_instance_id ) ) {

			$json_fields = Utils::get_json_fields();

			$old_instance_id = $instance->ID;
			// Duplicate all the meta fields.
			$fields = array_keys( SettingsController::get_fields() );
			foreach ( $fields as $field ) {
				$meta_key   = Main::PREFIX . $field;
				$meta_value = get_post_meta( $old_instance_id, $meta_key, true );
				// slashes to preserve json fields.
				if ( in_array( $field, $json_fields, true ) ) {
					$meta_value = addslashes( $meta_value );
				}
				update_post_meta( $new_instance_id, $meta_key, $meta_value );
			}
		}

		return $new_instance_id;
	}
}
