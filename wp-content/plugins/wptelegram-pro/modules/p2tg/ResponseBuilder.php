<?php
/**
 * Builds responses to be sent to Telegram.
 *
 * @link        https://wptelegram.pro
 * @since       1.0.0
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 */

namespace WPTelegram\Pro\modules\p2tg;

use WPTelegram\Pro\includes\Options;
use WPTelegram\Pro\shared\Shared;
use WPTelegram\Pro\includes\Utils as MainUtils;

/**
 * Class responsible for building message responses.
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 * @author      WP Socio
 */
class ResponseBuilder {

	/**
	 * Settings/Options
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     array       $options    Instance Options
	 */
	private $instance_options;

	/**
	 * Option Instances prepared from the settings or override options
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     array       $p2tg_instances     Option Instances
	 */
	private $p2tg_instances;

	/**
	 * ID of the current (active) instance that is being processed
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     int         $active_instance    The instance ID
	 */
	private $active_instance;

	/**
	 * The Post to Telegram rules instance.
	 *
	 * @var Rules   $rules  The rules object.
	 */
	protected $rules;

	/**
	 * The post to be handled
	 *
	 * @var WP_Post $post   Post object.
	 */
	protected $post;

	/**
	 * Whether to send the files (photo etc.) by URL
	 *
	 * @var bool    $send_files_by_url  Send files by URL
	 */
	protected $send_files_by_url = true;

	/**
	 * The post data
	 *
	 * @var PostData   $post_data  Post data.
	 */
	protected $post_data;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post           The post being processed.
	 * @param array   $p2tg_instances P2TG instances.
	 * @param Options $form_data      The data from post edit page.
	 */
	public function __construct( $post, $p2tg_instances, $form_data ) {

		$this->post           = $post;
		$this->p2tg_instances = $p2tg_instances;

		$this->post_data = new PostData( $post );

		$this->rules = new Rules( $post, $form_data );
	}

	/**
	 * Create responses from all instance.
	 *
	 * @since 1.0.0
	 *
	 * @return array[]
	 */
	public function build_responses() {

		$responses = [];

		$this->instance_options = new Options();

		foreach ( $this->p2tg_instances as $instance_id => $instance ) {

			$this->active_instance = $instance_id;

			$this->instance_options->set_data( $instance );

			// if the instance rules apply on the post.
			if ( $this->rules->instance_rules_apply( $this->instance_options ) ) {

				// create a corresponding response for the instance.
				$responses[ $instance_id ] = $this->get_instance_responses();
			}
		}

		return (array) apply_filters( 'wptelegram_pro_p2tg_responses', $responses, $this->post, $this->p2tg_instances );
	}

	/**
	 * Prepare responses from an instance
	 *
	 * @since   1.0.0
	 *
	 * @return  array[]
	 */
	private function get_instance_responses() {

		$responses = [];

		// For text message.
		$template = $this->get_message_template();
		$text     = '';

		if ( ! empty( $template ) ) {

			$text = $this->get_response_text( $template );
		}

		$this->send_files_by_url = Shared::send_files_by_url();

		$parse_mode = MainUtils::valid_parse_mode( $this->instance_options->get( 'parse_mode' ) );
		$this->instance_options->set( 'parse_mode', $parse_mode );

		// For Photo.
		$image_source = $this->get_featured_image_source();

		$responses = $this->get_default_responses( $text, $image_source );

		$files = $this->instance_options->get( 'files' );

		if ( ! empty( $files ) ) {

			$file_responses = $this->get_file_responses( $files );

			$responses = array_merge( $responses, $file_responses );
		}

		$additional_media = $this->instance_options->get( 'additional_media' );

		if ( ! empty( $additional_media ) ) {

			$media_responses = $this->get_media_responses( $additional_media );

			$responses = array_merge( $responses, $media_responses );
		}

		$send_wc_gallery = $this->instance_options->get( 'send_wc_gallery' );

		if ( $send_wc_gallery && class_exists( 'woocommerce' ) && function_exists( 'wc_get_product' ) ) {
			$gallery_responses = $this->get_wc_product_gallery_responses();

			$responses = array_merge( $responses, $gallery_responses );
		}

		$send_content_images = $this->instance_options->get( 'send_content_images' );

		if ( $send_content_images ) {
			$content_images_responses = $this->get_content_images_responses();

			$responses = array_merge( $responses, $content_images_responses );
		}

		return (array) apply_filters( 'wptelegram_pro_p2tg_instance_responses', $responses, $this->instance_options, $this->post, $this->p2tg_instances );
	}

	/**
	 * Create responses based on the text and image source.
	 *
	 * @since   1.0.0
	 *
	 * @param string $text           The text message.
	 * @param string $image_source   The source of the image - path or URL.
	 *
	 * @return  array[]
	 */
	private function get_default_responses( $text, $image_source ) {
		$parse_mode = MainUtils::valid_parse_mode( $this->instance_options->get( 'parse_mode' ) );

		$disable_web_page_preview = $this->instance_options->get( 'disable_web_page_preview' );
		$disable_notification     = $this->instance_options->get( 'disable_notification' );
		$protect_content          = $this->instance_options->get( 'protect_content' );

		$limit_to_one_message = apply_filters( 'wptelegram_pro_p2tg_limit_text_to_one_message', true, $this->post, $this->instance_options, $text, $image_source );

		$text_options = [
			'format_to' => $parse_mode,
			'id'        => 'p2tg',
			'limit'     => $limit_to_one_message ? MainUtils::get_max_text_length( 'text' ) : 0,
			'limit_by'  => 'chars',
		];

		$caption_options = array_merge( $text_options, [ 'limit' => MainUtils::get_max_text_length( 'caption' ) ] );

		// Do not fail if the replied-to message is not found.
		$allow_sending_without_reply = true;

		$method_params = [
			'sendPhoto'   => compact(
				'allow_sending_without_reply',
				'disable_notification',
				'parse_mode',
				'protect_content'
			),
			'sendMessage' => compact(
				'allow_sending_without_reply',
				'disable_notification',
				'disable_web_page_preview',
				'parse_mode',
				'protect_content'
			),
		];

		if ( ! empty( $image_source ) ) {

			$image_position = $this->instance_options->get( 'image_position' );
			$single_message = $this->instance_options->get( 'single_message' );
			$caption        = '';

			if ( $single_message ) {
				// if only caption is to be sent.
				if ( 'before' === $image_position ) {

					// remove sendMessage.
					unset( $method_params['sendMessage'] );

					$caption = MainUtils::smart_trim_excerpt( $text, $caption_options );

				} elseif ( 'after' === $image_position && '' !== $parse_mode ) {

					$text = $this->add_hidden_image_url( $text, $parse_mode );

					// Remove "sendPhoto".
					unset( $method_params['sendPhoto'] );
				}
			} elseif ( 'after' === $image_position ) {

				$method_params = array_reverse( $method_params );
			}

			if ( isset( $method_params['sendPhoto'] ) ) {

				$caption = apply_filters( 'wptelegram_pro_p2tg_post_image_caption', $caption, $this->post, $this->active_instance, $this->p2tg_instances, $text, $image_source );

				$method_params['sendPhoto']['photo']   = $image_source;
				$method_params['sendPhoto']['caption'] = $caption;
			}
		} else {
			unset( $method_params['sendPhoto'] );
		}

		$additional_text_responses = [];

		if ( isset( $method_params['sendMessage'] ) ) {

			if ( $limit_to_one_message ) {

				$text = MainUtils::smart_trim_excerpt( $text, $text_options );

			} else {
				$text_parts = MainUtils::split_content( $text, $parse_mode );
				// Extract the first piece.
				$text = array_shift( $text_parts );

				// Create additional responses for the remaining pieces.
				foreach ( $text_parts as $text_part ) {
					$additional_text_responses[] = [
						'sendMessage' => array_merge(
							$method_params['sendMessage'],
							[
								'text' => $text_part,
							]
						),
					];
				}
			}

			$method_params['sendMessage']['text'] = $text;
		}

		$method_params = apply_filters( 'wptelegram_pro_p2tg_method_params', $method_params, $this->instance_options, $this->post, $text, $image_source, $this->p2tg_instances );

		$markup_builder = new MarkupBuilder( $this->post, $this->instance_options );
		// passed by reference.
		$markup_builder->add_reply_markup( $method_params );

		$default_responses = [];

		foreach ( $method_params as $method => $params ) {
			$default_responses[] = [
				$method => $params,
			];
		}

		$default_responses = array_merge( $default_responses, $additional_text_responses );

		return apply_filters( 'wptelegram_pro_p2tg_default_responses', $default_responses, $this->instance_options, $this->post, $text, $image_source, $this->p2tg_instances );
	}

	/**
	 * Create responses based on the files included.
	 *
	 * @since 1.0.0
	 *
	 * @param array $files The array of file info.
	 *
	 * @return  array[]
	 */
	private function get_file_responses( $files ) {

		$file_responses = [];

		$caption = $this->post_data->get_field( 'post_title' );

		foreach ( $files as $id => $url ) {

			$caption = apply_filters( 'wptelegram_pro_p2tg_file_caption', $caption, $id, $url, $this->post, $this->active_instance, $this->p2tg_instances );

			$type = MainUtils::get_file_type( $id, $url );

			$file_responses[] = [
				'send' . ucfirst( $type ) => [
					$type                         => $this->send_files_by_url ? $url : get_attached_file( $id ),
					'caption'                     => $caption,
					'allow_sending_without_reply' => true,
				],
			];
		}

		return apply_filters( 'wptelegram_pro_p2tg_file_responses', $file_responses, $this->instance_options, $this->post, $files, $this->p2tg_instances );
	}

	/**
	 * Create responses based on the additional media.
	 *
	 * @since 1.2.0
	 *
	 * @param array $additional_media The array of additional media.
	 *
	 * @return  array[]
	 */
	private function get_media_responses( $additional_media ) {
		$parse_mode = MainUtils::valid_parse_mode( $this->instance_options->get( 'parse_mode' ) );

		$disable_notification = $this->instance_options->get( 'disable_notification' );
		$protect_content      = $this->instance_options->get( 'protect_content' );

		$media_responses = [];

		foreach ( $additional_media as $media_option ) {

			$is_valid_group = ! empty( $media_option['is_group'] ) && ! empty( $media_option['media'] );

			if ( $is_valid_group ) {
				$group_media = [];
				foreach ( (array) $media_option['media'] as $single_media ) {
					// phpcs:ignore
					if ( self::is_valid_media( $single_media ) && $media = $this->get_media_source( $single_media ) ) {
						$type    = $single_media['media_type'];
						$caption = $this->get_media_caption( $single_media );

						$group_media[] = compact( 'type', 'media', 'caption', 'parse_mode' );
					}
				}

				if ( ! empty( $group_media ) ) {
					$media_responses[] = [
						'sendMediaGroup' => [
							'allow_sending_without_reply' => true,
							'disable_notification'        => $disable_notification,
							'media'                       => wp_json_encode( $group_media ),
							'protect_content'             => $protect_content,
						],
					];
				}
				// phpcs:ignore
			} elseif ( self::is_valid_media( $media_option ) && $source = $this->get_media_source( $media_option ) ) {
				$caption = $this->get_media_caption( $media_option );

				$media_type = $media_option['media_type'];
				// "video" turns into "Video", "video_note" turns into "VideoNote"
				$method = str_replace( '_', '', ucwords( $media_type, '_' ) );

				$media_responses[] = [
					'send' . $method => [
						'allow_sending_without_reply' => true,
						'caption'                     => $caption,
						'disable_notification'        => $disable_notification,
						'parse_mode'                  => $parse_mode,
						'protect_content'             => $protect_content,
						$media_type                   => $source,
					],
				];
			}
		}

		return apply_filters(
			'wptelegram_pro_p2tg_media_responses',
			$media_responses,
			$this->instance_options,
			$this->post,
			$additional_media,
			$this->p2tg_instances
		);
	}

	/**
	 * Create responses based on WooCommerce product gallery.
	 *
	 * @since 1.4.9
	 *
	 * @return  array[]
	 */
	private function get_wc_product_gallery_responses() {

		$responses = [];

		$product = wc_get_product( $this->post->ID );
		if ( $product instanceof \WC_Product ) {
			$attachment_ids = $product->get_gallery_image_ids();
			$parse_mode     = MainUtils::valid_parse_mode( $this->instance_options->get( 'parse_mode' ) );

			$wc_gallery_caption = $this->instance_options->get( 'wc_gallery_caption' );
			$caption            = $this->get_media_caption( [ 'caption' => $wc_gallery_caption ] );
			$media_group        = [];
			foreach ( $attachment_ids as $attachment_id ) {
				$media = $this->send_files_by_url ? wp_get_attachment_url( $attachment_id ) : get_attached_file( $attachment_id );
				$type  = MainUtils::get_file_type( $attachment_id, $media );

				$media_group[] = compact( 'type', 'media', 'caption', 'parse_mode' );

				// Reset caption after first iteration to add it only to the first image.
				$caption = '';
			}

			$media_group = apply_filters(
				'wptelegram_pro_p2tg_wc_product_gallery_media_group',
				$media_group,
				$this->instance_options,
				$this->post,
				$this->p2tg_instances
			);

			if ( ! empty( $media_group ) ) {
				$responses[] = [
					'sendMediaGroup' => [
						'allow_sending_without_reply' => true,
						'disable_notification'        => $this->instance_options->get( 'disable_notification' ),
						'media'                       => wp_json_encode( $media_group ),
						'protect_content'             => $this->instance_options->get( 'protect_content' ),
					],
				];
			}
		}

		return apply_filters(
			'wptelegram_pro_p2tg_wc_product_gallery_responses',
			$responses,
			$this->instance_options,
			$this->post,
			$this->p2tg_instances
		);
	}

	/**
	 * Create responses based on images in the post content.
	 *
	 * @since 1.5.0
	 *
	 * @return  array[]
	 */
	private function get_content_images_responses() {

		$post_content = apply_filters( 'the_content', get_post_field( 'post_content', $this->post ) );

		// Image extensions to find in the content.
		$image_extensions = 'jpe?g|jpe|png|bmp|tiff?';

		// Regext pattern of image URLs in post content.
		$conten_images_pattern = '/https?:\/\/\S+\.(?:' . $image_extensions . ')/ius';
		$conten_images_pattern = apply_filters(
			'wptelegram_pro_p2tg_content_images_pattern',
			$conten_images_pattern,
			$this->instance_options,
			$this->post,
			$this->p2tg_instances
		);

		preg_match_all( $conten_images_pattern, $post_content, $matches );

		$image_urls = [];

		if ( ! empty( $matches[0] ) ) {
			/**
			 * It's possible that the images are repeated in different sizes like
			 * image-1536x960.jpg
			 * image-1568x980.jpg
			 * image-600x375.jpg
			 * image.jpg
			 *
			 * So we will remove the duplicates and only send the actual size image.
			 */
			$image_urls = array_map(
				function ( $url ) use ( $image_extensions ) {
					// replace any width x height values at the end before extensions.
					// "image-1536x960.jpg" becomes "image.jpg".
					return preg_replace( '/-[0-9]+?x[0-9]+?(?=\.(?:' . $image_extensions . ')$)/ius', '', $url );
				},
				$matches[0]
			);
			$image_urls = array_unique( $image_urls );
		}

		$image_sources = apply_filters(
			'wptelegram_pro_p2tg_content_image_urls',
			$image_urls,
			$this->instance_options,
			$this->post,
			$this->p2tg_instances
		);

		if ( ! $this->send_files_by_url ) {
			foreach ( $image_sources as &$image_source ) {
				$path = MainUtils::url_to_path( $image_source );
				// replace URL by path if exists.
				$image_source = $path ? $path : $image_source;
			}
		}

		$image_sources = apply_filters(
			'wptelegram_pro_p2tg_content_image_sources',
			$image_sources,
			$this->instance_options,
			$this->post,
			$this->p2tg_instances
		);

		$responses = [];
		if ( ! empty( $image_sources ) ) {
			$parse_mode             = MainUtils::valid_parse_mode( $this->instance_options->get( 'parse_mode' ) );
			$content_images_caption = $this->instance_options->get( 'content_images_caption' );
			$caption                = $this->get_media_caption( [ 'caption' => $content_images_caption ] );

			$group_media = [];
			foreach ( $image_sources as $media ) {
				$type = 'photo';

				$group_media[] = compact( 'type', 'media', 'caption', 'parse_mode' );

				// Reset caption after first iteration to add it only to the first image.
				$caption = '';
			}
			if ( ! empty( $group_media ) ) {
				$responses[] = [
					'sendMediaGroup' => [
						'allow_sending_without_reply' => true,
						'disable_notification'        => $this->instance_options->get( 'disable_notification' ),
						'media'                       => wp_json_encode( $group_media ),
						'protect_content'             => $this->instance_options->get( 'protect_content' ),
					],
				];
			}
		}

		return apply_filters(
			'wptelegram_pro_p2tg_content_images_responses',
			$responses,
			$this->instance_options,
			$this->post,
			$this->p2tg_instances
		);
	}

	/**
	 * Whether the media is valid.
	 *
	 * @since   1.2.0
	 *
	 * @param array $media The media option.
	 *
	 * @return  boolean
	 */
	public static function is_valid_media( $media ) {
		return ! empty( $media['source'] ) && ! empty( $media['media_type'] );
	}

	/**
	 * Get the media source URL.
	 *
	 * @since   1.2.0
	 *
	 * @param array $media The media option.
	 *
	 * @return string
	 */
	public function get_media_source( $media ) {
		$source = stripslashes( $this->get_response_text( $media['source'] ) );
		// if the source is a numeric value, e.g. attachment ID.
		if ( is_numeric( $source ) ) {
			$source = $this->send_files_by_url ? wp_get_attachment_url( (int) $source ) : get_attached_file( (int) $source );
		}
		return apply_filters( 'wptelegram_pro_p2tg_media_source', $source, $media, $this->post, $this->instance_options );
	}

	/**
	 * Get the media caption text.
	 *
	 * @since   1.2.0
	 *
	 * @param array $media The media option.
	 *
	 * @return string
	 */
	public function get_media_caption( $media ) {
		$caption = ! empty( $media['caption'] ) ? $this->get_response_text( $media['caption'] ) : '';

		$caption = apply_filters( 'wptelegram_pro_p2tg_media_caption', $caption, $media, $this->post, $this->instance_options );

		if ( mb_strlen( $caption, 'UTF-8' ) > 1024 ) {
			// break text after every 1024th character and preserve words.
			preg_match_all( '/.{1,1024}(?:\s|$)/su', $caption, $matches );

			return $matches[0];
		}
		return $caption;
	}

	/**
	 * Get the message template
	 *
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	private function get_message_template() {

		$template = $this->instance_options->get( 'message_template' );

		return apply_filters( 'wptelegram_pro_p2tg_message_template', $template, $this->post, $this->instance_options, $this->p2tg_instances );
	}

	/**
	 * Get the text based response.
	 *
	 * @since   1.0.0
	 *
	 * @param string $template The message template.
	 *
	 * @return  string
	 */
	private function get_response_text( $template ) {

		$parser = new TemplateParser( $this->post, $this->instance_options );

		$text = $parser->parse( $template );

		return apply_filters( 'wptelegram_pro_p2tg_response_text', $text, $template, $this->post, $this->instance_options, $this->p2tg_instances );
	}

	/**
	 * Get the featured image URL or file location
	 *
	 * @since   1.0.0
	 *
	 * @return  string
	 */
	private function get_featured_image_source() {

		$send_image = $this->instance_options->get( 'send_featured_image' );

		$source = '';

		if ( $send_image && has_post_thumbnail( $this->post->ID ) ) {

			if ( $this->send_files_by_url ) {

				// featured image url.
				$source = $this->post_data->get_field( 'featured_image_url' );

			} else {

				// featured image path.
				$source = $this->post_data->get_field( 'featured_image_path' );
			}
		}

		return apply_filters( 'wptelegram_pro_p2tg_featured_image_source', $source, $this->post, $this->instance_options, $this->send_files_by_url, $this->p2tg_instances );
	}

	/**
	 * Add hidden URL at the beginning of the text.
	 *
	 * @since 1.0.0
	 *
	 * @param string $text       The message text.
	 * @param string $parse_mode The parse mode.
	 *
	 * @return string
	 */
	private function add_hidden_image_url( $text, $parse_mode ) {

		$image_url = $this->post_data->get_field( 'featured_image_url' );

		$string = '';

		if ( 'HTML' === $parse_mode ) {
			// Add Zero Width Non Joiner as the anchor text.
			$string = '<a href="' . $image_url . '">&#8204;</a>';
		}

		// if text starts with a hashtag, add a space separator.
		$separator = preg_match( '/^#/', $text ) ? ' ' : '';

		return $string . $separator . $text;
	}
}
