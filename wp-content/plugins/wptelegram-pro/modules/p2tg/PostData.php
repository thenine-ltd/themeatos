<?php
/**
 * Post data provider.
 *
 * @link        https://wptelegram.pro
 * @since       1.0.0
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 */

namespace WPTelegram\Pro\modules\p2tg;

use WPTelegram\Pro\includes\Utils;
use WP_Post;

/**
 * The Post data provider.
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 * @author      WP Socio
 */
class PostData {

	/**
	 * The post to be handled
	 *
	 * @var WP_Post $post   Post object.
	 */
	protected $post;

	/**
	 * The post data
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @var     array       $data   The array containing the post data
	 */
	protected $data;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.0.0
	 * @param   WP_Post $post   Post object.
	 */
	public function __construct( $post ) {

		$this->data = [];

		$this->set_post( $post );
	}

	/**
	 * Set the post
	 *
	 * @since    1.0.0
	 * @param   WP_Post $post   Post object.
	 */
	public function set_post( $post ) {

		$this->post = get_post( $post );
	}

	/**
	 * Retrieves a field from post data
	 * And updates the data if not found
	 *
	 * @since 1.0.0
	 *
	 * @param  string $field    The field to be retrieved.
	 * @param  string $options   Optional options to be used for some fields.
	 *
	 * @return mixed Field value
	 */
	public function get_field( $field, $options = [] ) {

		// if the data already exists for the field.
		if ( ! array_key_exists( $field, $this->data ) ) {
			$this->data[ $field ] = $this->get_field_value( $field, $options );
		}

		$value = apply_filters( 'wptelegram_pro_p2tg_post_data_field', $this->data[ $field ], $field, $this->post );

		return apply_filters( "wptelegram_pro_p2tg_post_data_{$field}", $value, $this->post );
	}

	/**
	 * Retrieves a field value from post without modifying $this->data
	 *
	 * @since 1.0.0
	 *
	 * @param  string $field    The field to be retrieved.
	 * @param  string $options   Optional options to be used for some fields.
	 *
	 * @return mixed Field value
	 */
	public function get_field_value( $field, $options = [] ) {

		$value = '';

		switch ( $field ) {

			case 'id':
			case 'ID':
			case 'post_id':
				$value = $this->post->ID;
				break;

			/* Post Title */
			case 'title':
			case 'post_title':
				$value = get_the_title( $this->post );
				break;

			/* Post slug */
			case 'slug':
			case 'post_slug':
			case 'post_name':
				$value = $this->post->post_name;
				break;

			/* The post's local publication time */
			case 'post_date':
				$value = get_the_date( '', $this->post->ID );
				break;

			/* The post's GMT publication time */
			case 'post_date_gmt':
				$value = date_i18n( get_option( 'date_format' ), strtotime( $this->post->post_date_gmt ) );
				break;

			/* The post type label (singular), like Post or Page. */
			case 'post_type_label':
				$post_type = get_post_type_object( $this->post->post_type );
				// Return singular name or the slug.
				$value = ! empty( $post_type->labels->singular_name ) ? $post_type->labels->singular_name : $this->post->post_type;
				break;

				/* The post type slug, like 'post' or 'page'. */
			case 'post_type':
				$value = $this->post->post_type;
				break;

			/* The post's post_format */
			case 'post_format':
				$value = get_post_format( $this->post );
				break;

			/* Post Author name */
			case 'author':
			case 'post_author':
				$value = get_the_author_meta( 'display_name', $this->post->post_author );
				break;

			/* Post Author username*/
			case 'author_username':
				$value = get_the_author_meta( 'user_login', $this->post->post_author );
				break;

			/* Post Excerpt */
			case 'excerpt':
			case 'post_excerpt':
				$excerpt_source = isset( $options['excerpt_source'] ) ? $options['excerpt_source'] : 'post_content';
				$excerpt_length = isset( $options['excerpt_length'] ) ? $options['excerpt_length'] : 55;
				$preserve_eol   = isset( $options['excerpt_preserve_eol'] ) && $options['excerpt_preserve_eol'];
				$parse_mode     = isset( $options['parse_mode'] ) ? $options['parse_mode'] : 'text';

				if ( 'before_more' === $excerpt_source ) {
					$parts   = get_extended( apply_filters( 'the_content', get_post_field( 'post_content', $this->post ) ) );
					$excerpt = $parts['main'];
				} else {
					$field  = 'post_content' === $excerpt_source ? 'post_content' : 'post_excerpt';
					$filter = 'post_content' === $excerpt_source ? 'the_content' : 'the_excerpt';

					$excerpt = get_post_field( $field, $this->post );

					self::remove_autoembed_filter();

					// apply the_content or the_excerpt.
					$excerpt = apply_filters( $filter, $excerpt );

					self::restore_autoembed_filter();

				}

				// Remove shortcodes.
				$excerpt = trim( strip_shortcodes( $excerpt ) );

				$value = Utils::prepare_content(
					$excerpt,
					[
						'format_to'    => $parse_mode,
						'id'           => 'p2tg',
						'limit'        => $excerpt_length,
						'limit_by'     => 'words',
						'preserve_eol' => $preserve_eol,
					]
				);

				$plain_excerpt = apply_filters( 'wptelegram_pro_p2tg_post_data_plain_excerpt', false, $value, $excerpt, $this->post, $options );

				if ( $plain_excerpt ) {
					$value = trim( wp_strip_all_tags( $value ) );
				}

				// If the excerpt is not empty.
				if ( $value ) {
					// Add custom tags for smart trimming.
					$value = '<excerpt>' . $value . '</excerpt>';
				}
				break;

			/* Post Content */
			case 'content':
			case 'post_content':
				$parse_mode = isset( $options['parse_mode'] ) ? $options['parse_mode'] : 'text';

				$content = get_post_field( 'post_content', $this->post );
				$content = preg_replace( '@<br[^>]*?/?>@si', "\n", $content );

				self::remove_autoembed_filter();
				$content = apply_filters( 'the_content', $content );
				self::restore_autoembed_filter();

				// Remove shortcodes.
				$content = trim( strip_shortcodes( $content ) );

				$value = Utils::prepare_content(
					$content,
					[
						'format_to' => $parse_mode,
						'id'        => 'p2tg',
						'limit'     => 0,
					]
				);
				break;

			case 'short_url':
				$value = wp_get_shortlink( $this->post->ID );
				break;

			case 'home_url':
				$value = home_url();
				break;

			case 'post_url':
			case 'full_url':
				$value = urldecode_deep( get_permalink( $this->post->ID ) );
				break;

			case 'featured_image_url':
				// post thumbnail ID.
				$thumbnail_id = get_post_thumbnail_id( $this->post->ID );

				$value = wp_get_attachment_url( $thumbnail_id );
				break;

			case 'featured_image_path':
				// post thumbnail ID.
				$thumbnail_id = get_post_thumbnail_id( $this->post->ID );

				$value = get_attached_file( $thumbnail_id );
				break;

			default:
				// if it's something special.
				if ( preg_match( '/^(terms(?:lugs)?|a?cf|wc):/i', $field, $match ) ) {

					$_field = preg_replace( '/^' . $match[1] . ':/i', '', $field );

					switch ( $match[1] ) {

						case 'terms':
						case 'termslugs':
							$taxonomy = $_field;

							if ( taxonomy_exists( $taxonomy ) ) {

								$terms = get_the_terms( $this->post->ID, $taxonomy );
								if ( is_wp_error( $terms ) || empty( $terms ) ) {
									// we have nothing to work upon.
									break;
								}

								$field_to_pluck = 'termslugs' === $match[1] ? 'slug' : 'name';

								$values = wp_list_pluck( $terms, $field_to_pluck );

								if ( 'terms' === $match[1] ) {

									$cats_as_tags = ! empty( $options['cats_as_tags'] );

									$cats_as_tags = apply_filters(
										"wptelegram_pro_p2tg_post_data_send_{$taxonomy}_as_tags",
										$cats_as_tags,
										$this->post,
										$options
									);

									if ( ! $cats_as_tags && is_taxonomy_hierarchical( $taxonomy ) ) {

										$value = implode( ' | ', $values );

									} else {

										$values = Utils::sanitize_hashtag( $values );
										$value  = '#' . implode( ' #', $values );
									}
								} else {
									$value = implode( ',', $values );
								}
							}
							break;

						case 'cf':
							$value = get_post_meta( $this->post->ID, $_field, true );
							break;

						case 'acf':
							if ( function_exists( 'get_field' ) ) {
								$value = get_field( $_field, $this->post->ID );
							}
							break;

						case 'wc':
							$wc_exists = class_exists( 'woocommerce' ) && function_exists( 'wc_get_product' );
							if ( $wc_exists ) {
								$product   = wc_get_product( $this->post->ID );
								$wc_exists = $product instanceof \WC_Product;

								if ( ! $wc_exists ) {
									break;
								}

								// If the product is variable.
								if ( $product instanceof \WC_Product_Variable ) {

									// Get all the product variations.
									$variations = $product->get_available_variations();

									if ( ! empty( $variations ) ) {
										// First variation ID.
										$variation_id = $variations[0]['variation_id'];

										$product = new \WC_Product_Variation( $variation_id );
									}
								}

								$method = [ $product, 'get_' . strtolower( $_field ) ];
								if ( is_callable( $method ) ) {

									$value = wp_strip_all_tags( call_user_func( $method ) );

									break;
								}

								// If the product is not on sale.
								if ( ! $product->is_on_sale() ) {
									break;
								}

								// Get the price values.
								$sale_price    = $product->get_sale_price();
								$regular_price = $product->get_regular_price();

								// Let us ensure that we have both the prices.
								if ( ! $sale_price || ! $regular_price ) {
									break;
								}

								if ( 'save_percent' === $_field ) {

									$value = round(
										( ( $regular_price - $sale_price ) / $regular_price ) * 100,
										2 // Number of decimal places to round the percent to.
									);
								} elseif ( 'save_amount' === $_field ) {

									$value = $regular_price - $sale_price;
								}
							}
							break;
					}
				}
				break;
		}

		$value = apply_filters( 'wptelegram_pro_p2tg_post_data_field_value', $value, $field, $this->post, $options );

		$remove_multi_eol = apply_filters( 'wptelegram_pro_p2tg_post_data_remove_multi_eol', true, $this->post );

		if ( $remove_multi_eol ) {
			// remove multiple newlines.
			$value = preg_replace( '/\n[\n\r\s]*\n[\n\r\s]*\n/u', "\n\n", $value );
		}

		return (string) apply_filters( "wptelegram_pro_p2tg_post_data_{$field}_value", $value, $this->post, $options, $field );
	}

	/**
	 * Removes the autoembed filter from the_content
	 *
	 * @since 1.4.0
	 */
	public static function remove_autoembed_filter() {
		remove_filter( 'the_content', [ $GLOBALS['wp_embed'], 'autoembed' ], 8 );
	}

	/**
	 * Restores the autoembed filter to the_content
	 *
	 * @since 1.4.0
	 */
	public static function restore_autoembed_filter() {
		add_filter( 'the_content', [ $GLOBALS['wp_embed'], 'autoembed' ], 8 );
	}
}
