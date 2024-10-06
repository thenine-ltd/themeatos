<?php
/**
 * WP Telegram Pro P2TG Utilities
 *
 * @link       https://wptelegram.pro
 * @since     1.4.0
 *
 * @package WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\p2tg;
 */

namespace WPTelegram\Pro\modules\p2tg;

use WPTelegram\Pro\modules\p2tg\restApi\SettingsController;
use WPTelegram\Pro\includes\Utils as MainUtils;
use WPTelegram\Pro\includes\Options;

/**
 * WP Telegram Pro P2TG Utilities
 *
 * @link       https://wptelegram.pro
 * @since     1.4.0
 *
 * @package WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\p2tg;
 */
class Utils {
	const JSON_STRING_FIELDS = [ 'message_template', 'wc_gallery_caption', 'content_images_caption' ];

	const JSON_ARRAY_FIELDS = [ 'additional_media', 'inline_keyboard' ];

	/**
	 * Get the post associated with the command
	 *
	 * @since   1.0.0
	 *
	 * @param   string $post_type  The post_type for filtering instances.
	 * @param   string $include    Limit the instances to the given IDs.
	 *
	 * @return  int[]
	 */
	public static function get_instance_posts( $post_type = '', $include = [] ) {

		// default args.
		$query_args = [
			'post_type'   => 'wptgpro_p2tg',
			'fields'      => 'ids', // we only need IDs.
			'numberposts' => -1,
			'orderby'     => [
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			],
		];

		$meta_query = [ // filter out useless instances.
			'relation' => 'AND',
			[
				'key'   => Main::PREFIX . 'active',
				'value' => '1',
			],
			[
				'key'     => Main::PREFIX . 'channels',
				'compare' => 'EXISTS', // of no use if empty.
			],
		];

		// if $post_type filter is supplied.
		if ( ! empty( $post_type ) ) {
			$meta_query[] = [
				'key'     => Main::PREFIX . 'post_types',
				'value'   => '"' . $post_type . '"',
				'compare' => 'LIKE', // do the magic with post type.
			];
		}

		$query_args['meta_query'] = $meta_query; // phpcs:ignore

		if ( ! empty( $include ) ) {
			$query_args['include'] = (array) $include;
		}

		$query_args = (array) apply_filters( 'wptelegram_pro_p2tg_instance_posts_query_args', $query_args );

		// get instance posts.
		$instance_posts = get_posts( $query_args );

		return (array) apply_filters( 'wptelegram_pro_p2tg_instance_posts', $instance_posts, $query_args );
	}

	/**
	 * Get Post To Telegram option instances.
	 *
	 * @param string $post_type The post_type for filtering instances.
	 * @param string $include   Limit the instances to the given IDs.
	 *
	 * @since   1.0.0
	 *
	 * @return array[]
	 */
	public static function get_saved_instances( $post_type = '', $include = [] ) {

		// get instance posts.
		$instance_posts = self::get_instance_posts( $post_type, $include );

		$saved_instances = [];

		foreach ( $instance_posts as $instance_id ) {
			$instance_settings = SettingsController::get_instance_settings( $instance_id );
			// Add the id to the instance.
			$instance_settings['id'] = $instance_id;

			$saved_instances[ $instance_id ] = $instance_settings;
		}

		// You can add the option instances dynamically.
		return (array) apply_filters( 'wptelegram_pro_p2tg_saved_instances', $saved_instances, $instance_posts, $post_type, $include );
	}

	/**
	 * Returns a list of all the JSON fields.
	 *
	 * @return array
	 */
	public static function get_json_fields() {
		return array_merge( self::JSON_STRING_FIELDS, self::JSON_ARRAY_FIELDS );
	}

	/**
	 * JSON decodes instance values.
	 *
	 * @param array $instance The instance array.
	 *
	 * @return array
	 */
	public static function decode_instance_values( $instance ) {
		foreach ( self::JSON_STRING_FIELDS as $template_field ) {
			if ( ! empty( $instance[ $template_field ] ) ) {
				$instance[ $template_field ] = MainUtils::decode_html( json_decode( $instance[ $template_field ] ) );
			}
		}

		foreach ( self::JSON_ARRAY_FIELDS as $json_array_field ) {
			if ( ! empty( $instance[ $json_array_field ] ) ) {
				$instance[ $json_array_field ] = json_decode( $instance[ $json_array_field ], true );
			}
		}

		return $instance;
	}

	/**
	 * JSON encodes instance values.
	 *
	 * @param array   $instance    The instance array.
	 * @param boolean $addslashes  Whether to addslashes for database.
	 *
	 * @return array
	 */
	public static function encode_instance_values( $instance, $addslashes = true ) {

		foreach ( self::get_json_fields() as $json_field ) {
			if ( ! empty( $instance[ $json_field ] ) ) {
				$encoded_value = wp_json_encode( $instance[ $json_field ] );
				if ( $addslashes ) {
					// add slashes to the template to avoid stripping of backslashes.
					$encoded_value = addslashes( $encoded_value );
				}
				$instance[ $json_field ] = $encoded_value;
			}
		}

		return $instance;
	}

	/**
	 * Removes instance fields, except for the given fields.
	 *
	 * @param array $instance          The instance array.
	 * @param array $fields_to_include The fields to retain.
	 *
	 * @return array
	 */
	public static function filter_instance_fields( $instance, $fields_to_include = [] ) {

		return array_intersect_key( $instance, array_flip( $fields_to_include ) );
	}

	/**
	 * Converts instance to Options object.
	 *
	 * @param array|Options $instance The instance array.
	 *
	 * @return Options
	 */
	public static function convert_instance_to_options( $instance ) {

		if ( $instance instanceof Options ) {
			return $instance;
		}

		$options = new Options();

		return $options->set_data( $instance );
	}

	/**
	 * Get a specific field value from all the P2TG instances.
	 *
	 * @since 1.4.0
	 *
	 * @param string $field_name The field to be retrieved.
	 * @param bool   $deep_merge Whether to do a deep merge for array fields.
	 *
	 * @return mixed[]
	 */
	public static function get_field_from_all_instances( $field_name, $deep_merge = false ) {

		$values = [];

		$instances = self::get_saved_instances();

		if ( ! empty( $instances ) ) {

			// Get the values from all the instances.
			$values = wp_list_pluck( $instances, $field_name );

			if ( $deep_merge ) {
				$values = call_user_func_array( 'array_merge', $values );
				$values = array_unique( $values );
			}
		}

		return $values;
	}

	/**
	 * Whether the post is new.
	 *
	 * @since   1.4.0
	 *
	 * @param   int|WP_Post $post        The post to check.
	 * @param   int         $instance_id The instance ID to check if the post has been sent via it.
	 *
	 * @return  bool
	 */
	public static function is_post_new( $post, $instance_id = null ) {

		$post = get_post( $post );

		if ( ! $post ) {
			return false;
		}

		// if the post has been published more than one day ago.
		$is_more_than_a_day_old = ( ( time() - get_post_time( 'U', true, $post->ID, false ) ) / DAY_IN_SECONDS ) > 1;

		// whether the post has already been sent to Telegram.
		$sent2tg = get_post_meta( $post->ID, Main::PREFIX . 'sent2tg', true );

		if ( $instance_id ) {
			// If the instance ID is in sent items - it's been sent via the instance.
			$sent2tg = array_key_exists( $instance_id, (array) $sent2tg );
		}

		// if the meta value is empty - it's new.
		$is_new = empty( $sent2tg ) && ! $is_more_than_a_day_old;

		return (bool) apply_filters( 'wptelegram_pro_p2tg_is_post_new', $is_new, $post );
	}
}
