<?php
/**
 * Plugin settings endpoint for WordPress REST API.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro;
 * @subpackage WPTelegram\Pro\modules\p2tg\restApi;
 */

namespace WPTelegram\Pro\modules\p2tg\restApi;

use WPTelegram\Pro\includes\restApi\RESTController;
use WPTelegram\Pro\includes\restApi\SettingsController as MainSettingsController;
use WPTelegram\Pro\includes\Utils as MainUtils;
use WPTelegram\Pro\modules\p2tg\Main;
use WP_Post;
use WPTelegram\Pro\modules\p2tg\Utils;

/**
 * Class to handle the settings endpoint.
 *
 * @since 1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\p2tg\restApi
 * @author     WP Socio
 */
class SettingsController extends RESTController {

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	const FIELD_NAME = 'p2tg_options';

	/**
	 * Register the fields for ptg instance CPT.
	 *
	 * @since 1.0.0
	 */
	public function register_fields() {

		register_rest_field(
			Main::CPT_NAME,
			self::FIELD_NAME,
			[
				'get_callback'    => [ $this, 'get_options' ],
				'update_callback' => [ $this, 'update_options' ],
				'schema'          => [
					'type'        => 'object',
					'properties'  => self::get_fields(),
					'arg_options' => [
						'sanitize_callback' => [ __CLASS__, 'sanitize_param' ],
						'validate_callback' => 'rest_validate_request_arg',
					],
				],
			]
		);
	}

	/**
	 * Get the default values for settings.
	 *
	 * @return array
	 */
	public static function get_default_values() {
		return [
			// Basics.
			'active'                   => true,
			'bot'                      => '',
			// Destination.
			'channels'                 => [],
			// Rules.
			'use_when'                 => [ 'new' ],
			'post_types'               => [ 'post' ],
			'rules'                    => [],
			// Message.
			'message_template'         => '{post_title}' . PHP_EOL . PHP_EOL . '{post_excerpt}' . PHP_EOL . PHP_EOL . '{full_url}',
			'excerpt_length'           => 55,
			'excerpt_preserve_eol'     => false,
			'excerpt_source'           => 'post_content',
			// Image.
			'send_featured_image'      => true,
			'image_position'           => 'before',
			'single_message'           => false,
			// Formatting.
			'cats_as_tags'             => false,
			'parse_mode'               => 'none',
			'disable_web_page_preview' => false,
			// Media.
			'additional_media'         => [],
			'send_wc_gallery'          => false,
			'wc_gallery_caption'       => '',
			'send_content_images'      => false,
			'content_images_caption'   => '',
			// Inline button.
			'inline_keyboard'          => [],
			// Misc.
			'delay'                    => 0,
			'disable_notification'     => false,
			'protect_content'          => false,
		];
	}

	/**
	 * Get the settings for an instance.
	 *
	 * @param int $instance_id The insatance post ID.
	 *
	 * @return array
	 */
	public static function get_instance_settings( $instance_id ) {

		// get the saved `channels`.
		$channels = get_post_meta( $instance_id, Main::PREFIX . 'channels', true );
		// since `channels` always has a truthy value, the assumption is safe.
		$is_new_instance = empty( $channels );

		$default_values = self::get_default_values();

		if ( $is_new_instance ) {
			return $default_values;
		}

		$settings = [];

		foreach ( self::get_fields() as $field => $schema ) {
			$value = get_post_meta( $instance_id, Main::PREFIX . $field, true );

			switch ( $schema['type'] ) {
				case 'boolean':
					$value = (bool) $value;
					break;
				case 'number':
					$value = (float) $value;
					break;
				case 'integer':
					$value = (int) $value;
					break;
			}
			$settings[ $field ] = $value;
		}

		$settings = Utils::decode_instance_values( $settings );

		return array_merge( $default_values, $settings );
	}

	/**
	 * Retrieve the options for an instance.
	 *
	 * @since 1.0.0
	 *
	 * @param array $object  Data object.
	 */
	public function get_options( $object ) {
		return self::get_instance_settings( $object['id'] );
	}

	/**
	 * Update the instance options.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed   $options The field value.
	 * @param WP_Post $post    Data object.
	 */
	public function update_options( $options, $post ) {

		foreach ( array_keys( self::get_fields() ) as $field ) {
			if ( array_key_exists( $field, $options ) ) {
				update_post_meta( $post->ID, Main::PREFIX . $field, $options[ $field ] );
			}
		}
	}

	/**
	 * Retrieves the fields to be registered.
	 *
	 * @since 1.0.0
	 *
	 * @return array The fields to be registered.
	 */
	public static function get_fields() {
		return [
			'active'                   => [
				'type' => 'boolean',
			],
			'bot'                      => [
				'type'    => 'string',
				'pattern' => MainUtils::enhance_regex( MainSettingsController::TG_USERNAME_PATTERN, true ),
			],
			'channels'                 => [
				'type'  => 'array',
				'items' => [
					'type' => 'string',
				],
			],
			'use_when'                 => [
				'type'  => 'array',
				'items' => [
					'type' => 'string',
					'enum' => [ 'new', 'existing' ],
				],
			],
			'post_types'               => [
				'type'  => 'array',
				'items' => [
					'type' => 'string',
				],
			],
			'rules'                    => [
				'type'  => 'array',
				'items' => [
					'type'  => 'array',
					'items' => [
						'type'       => 'object',
						'properties' => [
							'param'        => [
								'type' => 'string',
							],
							'custom_param' => [
								'type' => 'string',
							],
							'operator'     => [
								'type' => 'string',
							],
							'values'       => [
								'type'  => 'array',
								'items' => [
									'type'       => 'object',
									'properties' => [
										'value' => [
											'type' => 'string',
										],
										'label' => [
											'type' => 'string',
										],
									],
								],
							],
						],
					],
				],
			],
			'message_template'         => [
				'type' => 'string',
			],
			'excerpt_source'           => [
				'type' => 'string',
				'enum' => [ 'post_content', 'before_more', 'post_excerpt' ],
			],
			'excerpt_length'           => [
				'type'    => 'integer',
				'minimum' => 1,
				'maximum' => 300,
			],
			'excerpt_preserve_eol'     => [
				'type' => 'boolean',
			],
			'send_featured_image'      => [
				'type' => 'boolean',
			],
			'image_position'           => [
				'type' => 'string',
				'enum' => [ 'before', 'after' ],
			],
			'single_message'           => [
				'type' => 'boolean',
			],
			'cats_as_tags'             => [
				'type' => 'boolean',
			],
			'parse_mode'               => [
				'type' => 'string',
				'enum' => [ 'none', 'HTML' ],
			],
			'disable_web_page_preview' => [
				'type' => 'boolean',
			],
			'additional_media'         => [
				'type'  => 'array',
				'items' => [
					'type'       => 'object',
					'properties' => [
						'is_group'   => [
							'type' => 'boolean',
						],
						'source'     => [
							'type' => 'string',
						],
						'caption'    => [
							'type' => 'string',
						],
						'media_type' => [
							'type' => 'string',
							'enum' => [
								'animation',
								'audio',
								'document',
								'photo',
								'video',
							],
						],
						'media'      => [
							'type'  => 'array',
							'items' => [
								'type'       => 'object',
								'properties' => [
									'source'     => [
										'type' => 'string',
									],
									'caption'    => [
										'type' => 'string',
									],
									'media_type' => [
										'type' => 'string',
										'enum' => [
											'photo',
											'video',
										],
									],
								],
							],
						],
					],
				],
			],
			'send_wc_gallery'          => [
				'type' => 'boolean',
			],
			'wc_gallery_caption'       => [
				'type' => 'string',
			],
			'send_content_images'      => [
				'type' => 'boolean',
			],
			'content_images_caption'   => [
				'type' => 'string',
			],
			'delay'                    => [
				'type'    => 'number',
				'minimum' => 0,
			],
			'disable_notification'     => [
				'type' => 'boolean',
			],
			'protect_content'          => [
				'type' => 'boolean',
			],
			'inline_keyboard'          => [
				'type'  => 'array',
				'items' => [
					'type'  => 'array',
					'items' => [
						'type' => 'string',
					],
				],
			],
		];
	}

	/**
	 * Sanitize the request param.
	 *
	 * @since 1.4.0
	 *
	 * @param mixed $value Value of the param.
	 */
	public static function sanitize_param( $value ) {
		// First lets make the value safer.
		$safe_value = MainUtils::sanitize( $value );

		// Remove useless channels.
		if ( ! empty( $safe_value['channels'] ) ) {
			$safe_value['channels'] = array_values( array_filter( $safe_value['channels'] ) );
		}

		// Sanitize the templates separately.
		foreach ( [ 'message_template', 'wc_gallery_caption', 'content_images_caption' ] as $template_field ) {
			if ( isset( $safe_value[ $template_field ] ) ) {
				$safe_value[ $template_field ] = MainUtils::sanitize_message_template( $value[ $template_field ], true, true );
			}
		}

		// Remove useless rules.
		if ( ! empty( $safe_value['rules'] ) ) {
			$safe_value['rules'] = self::clean_up_rules( $safe_value['rules'] );
		}

		// Ensure that media caption is sanitized separately.
		if ( ! empty( $safe_value['additional_media'] ) ) {
			$additional_media = [];
			foreach ( $safe_value['additional_media'] as $iem_key => $item ) {
				// if it's a single media.
				if ( ! empty( $item['source'] ) ) {
					if ( ! empty( $item['caption'] ) ) {
						$item['caption'] = MainUtils::sanitize_message_template(
							// get the value from actual input.
							$value['additional_media'][ $iem_key ]['caption']
						);
					}
					$additional_media[] = $item;
				} elseif ( ! empty( $item['media'] ) ) {
					$media_group = [];
					// loop through each group item.
					foreach ( (array) $item['media'] as $media_key => $media ) {
						// Source should be present.
						if ( empty( $media['source'] ) ) {
							continue;
						}

						if ( ! empty( $media['caption'] ) ) {
							// do not json_encode here, it will be done below.
							$media['caption'] = MainUtils::sanitize_message_template(
								// get the value from actual input.
								$value['additional_media'][ $iem_key ]['media'][ $media_key ]['caption']
							);
						}
						$media_group[] = $media;
					}
					// If we have got something.
					if ( ! empty( $media_group ) ) {
						$additional_media[] = [
							'media'    => $media_group,
							'is_group' => true,
						];
					}
				}
			}
			$safe_value['additional_media'] = $additional_media;
		}

		if ( ! empty( $safe_value['inline_keyboard'] ) ) {
			$safe_value['inline_keyboard'] = self::clean_up_keyboard( $safe_value['inline_keyboard'] );
		}

		foreach ( [ 'additional_media', 'inline_keyboard' ] as $json_array_field ) {
			if ( isset( $safe_value[ $json_array_field ] ) ) {
				$safe_value[ $json_array_field ] = addslashes( wp_json_encode( $safe_value[ $json_array_field ] ) );
			}
		}

		return $safe_value;
	}

	/**
	 * Removes non-existent buttons.
	 *
	 * @param array $keyboard The keyboard array.
	 *
	 * @return array
	 */
	public static function clean_up_keyboard( $keyboard ) {

		$buttons = WPTG_Pro()->options()->get_path( 'p2tg.buttons', [] );
		// create an associative array with button id as key.
		$buttons = array_column( $buttons, null, 'id' );

		// Remove empty rows.
		$keyboard = array_values( array_filter( (array) $keyboard ) );

		$inline_keyboard = [];
		foreach ( $keyboard as $keyboard_row ) {
			$row = [];
			foreach ( $keyboard_row as $button_id ) {
				if ( ! empty( $buttons[ $button_id ] ) ) {
					$row[] = $button_id;
				}
			}
			if ( ! empty( $row ) ) {
				$inline_keyboard[] = $row;
			}
		}
		return $inline_keyboard;
	}

	/**
	 * Removes useless rules.
	 *
	 * @param array $rule_groups The rules array.
	 *
	 * @return array
	 */
	public static function clean_up_rules( $rule_groups ) {
		$rules = [];
		foreach ( (array) $rule_groups as  $rule_group ) {
			$group = [];

			if ( is_array( $rule_group ) ) {
				foreach ( $rule_group as $rule ) {
					// remove empty values.
					$rule = array_filter( (array) $rule );
					// Whether we have a value for param or custom param.
					$has_empty_param = empty( $rule['param'] ) || ( 'custom' === $rule['param'] && empty( $rule['custom_param'] ) );
					if ( $has_empty_param || empty( $rule['operator'] ) || empty( $rule['values'] ) ) {
						continue;
					}
					// If it's a custom rule.
					if ( 'custom' === $rule['param'] ) {
						// Use only the first value.
						$rule['values'] = [ reset( $rule['values'] ) ];
					}
					$group[] = $rule;
				}
			}
			if ( ! empty( $group ) ) {
				$rules[] = $group;
			}
		}
		return $rules;
	}
}
