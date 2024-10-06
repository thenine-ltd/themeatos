<?php
/**
 * Plugin settings endpoint for WordPress REST API.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes\restApi
 */

namespace WPTelegram\Pro\includes\restApi;

use WPTelegram\Pro\includes\Utils;
use WPTelegram\BotAPI\API;
use WPTelegram\Pro\modules\bots\Utils as BotsUtils;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Class to handle the settings endpoint.
 *
 * @since 1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes\restApi
 * @author     WP Socio
 */
class SettingsController extends RESTController {

	/**
	 * Pattern to match Telegram username.
	 *
	 * @var string Pattern.
	 * @since 3.0.0
	 */
	const TG_USERNAME_PATTERN = '[a-zA-Z][a-zA-Z0-9_]{3,30}[a-zA-Z0-9]';

	/**
	 * The base of this controller's route.
	 *
	 * @var string
	 */
	const REST_BASE = '/settings';

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
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'settings_permissions' ],
					'args'                => self::get_settings_params(),
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'update_settings' ],
					'permission_callback' => [ $this, 'settings_permissions' ],
					'args'                => self::get_settings_params(),
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
	public function settings_permissions() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get the default settings.
	 *
	 * @return array
	 */
	public static function get_default_values() {

		return [
			'bots'     => [
				'collection' => [
					[
						'bot_token'     => '',
						'bot_username'  => '',
						'update_method' => 'none',
					],
				],
			],
			'p2tg'     => [
				'active'           => false,
				'buttons'          => [
					[
						'value' => 'ok',
						'label' => '👌',
						'id'    => Utils::uuid(),
					],
					[
						'value' => 'like',
						'label' => '👍',
						'id'    => Utils::uuid(),
					],
					[
						'value' => 'dislike',
						'label' => '👎',
						'id'    => Utils::uuid(),
					],
					[
						'value' => 'heart',
						'label' => '❤️',
						'id'    => Utils::uuid(),
					],
					[
						'value' => 'laugh',
						'label' => '😂',
						'id'    => Utils::uuid(),
					],
					[
						'value' => 'smile',
						'label' => '😄',
						'id'    => Utils::uuid(),
					],
					[
						'value' => 'hush',
						'label' => '😯',
						'id'    => Utils::uuid(),
					],
					[
						'value' => 'angry',
						'label' => '😡',
						'id'    => Utils::uuid(),
					],
					[
						'value' => 'sad',
						'label' => '😢',
						'id'    => Utils::uuid(),
					],
					[
						'value' => 'clap',
						'label' => '👏',
						'id'    => Utils::uuid(),
					],
					[
						'label' => '🔗 ' . __( 'Visit Site', 'wptelegram-pro' ),
						'url'   => '{home_url}',
						'id'    => Utils::uuid(),
					],
					[
						'label' => '🔗 ' . __( 'View Post', 'wptelegram-pro' ),
						'url'   => '{post_url}',
						'id'    => Utils::uuid(),
					],
					[
						'label' => '📤 ' . __( 'Share', 'wptelegram-pro' ),
						'url'   => 'https://t.me/share/url?url={encode:{post_url}}&text={encode:{post_title}}',
						'id'    => Utils::uuid(),
					],
				],
				'plugin_posts'     => false,
				'post_edit_switch' => true,
			],
			'notify'   => [
				'active'           => false,
				'catch_emails'     => [
					[
						'email'    => '',
						'chat_ids' => [],
					],
				],
				'message_template' => '🔔‌<b>{email_subject}</b>🔔' . PHP_EOL . PHP_EOL . '{email_message}' . PHP_EOL . PHP_EOL . '{hashtag}',
				'parse_mode'       => 'HTML',
			],
			'proxy'    => [
				'active'       => false,
				'proxy_method' => 'cf_worker',
				'proxy_type'   => 'CURLPROXY_HTTP',
			],
			'advanced' => [
				'send_files_by_url' => true,
				'clean_uninstall'   => true,
			],
		];
	}

	/**
	 * Get the default settings.
	 *
	 * @return array
	 */
	public static function get_default_settings() {

		$settings = WPTG_Pro()->options()->get_data();

		// If we have something saved.
		if ( empty( $settings ) ) {
			$settings = self::get_default_values();
		}
		return $settings;
	}

	/**
	 * Get settings via API.
	 *
	 * @since 1.0.0
	 */
	public function get_settings() {
		return rest_ensure_response( self::get_default_settings() );
	}

	/**
	 * Update settings.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request WP REST API request.
	 */
	public function update_settings( WP_REST_Request $request ) {

		$params = array_keys( self::get_default_values() );

		$settings = [];

		foreach ( $params as $key ) {
			$settings[ $key ] = $request->get_param( $key );
		}

		WPTG_Pro()->options()->set_data( $settings )->update_data();

		// return the fresh unslashed options.
		return rest_ensure_response( $settings );
	}

	/**
	 * Retrieves the query params for the settings.
	 *
	 * @since 1.0.0
	 *
	 * @return array Query parameters for the settings.
	 */
	public static function get_settings_params() {
		$username_pattern = Utils::enhance_regex( self::TG_USERNAME_PATTERN, true );

		return [
			'bots'     => [
				'type'              => 'object',
				'sanitize_callback' => [ __CLASS__, 'sanitize_param' ],
				'validate_callback' => 'rest_validate_request_arg',
				'properties'        => [
					'collection' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'bot_token'     => [
									'type'    => 'string',
									'pattern' => Utils::enhance_regex( API::BOT_TOKEN_PATTERN, true ),
								],
								'bot_username'  => [
									'type'    => 'string',
									'pattern' => $username_pattern,
								],
								'update_method' => [
									'type' => 'string',
									'enum' => [
										BotsUtils::UPDATE_METHOD_WEBHOOK,
										BotsUtils::UPDATE_METHOD_LONG_POLLING,
										'none',
									],
								],
							],
						],
					],
				],
			],
			'p2tg'     => [
				'type'              => 'object',
				'sanitize_callback' => [ __CLASS__, 'sanitize_param' ],
				'validate_callback' => 'rest_validate_request_arg',
				'properties'        => [
					'active'           => [
						'type' => 'boolean',
					],
					'bot'              => [
						'type'    => 'string',
						'pattern' => $username_pattern,
					],
					'plugin_posts'     => [
						'type' => 'boolean',
					],
					'post_edit_switch' => [
						'type' => 'boolean',
					],
					'buttons'          => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'value' => [
									'type'    => 'string',
									'pattern' => Utils::enhance_regex( '[a-zA-Z_]{1,8}' ),
								],
								'label' => [
									'type' => 'string',
								],
								'url'   => [
									'type' => 'string',
								],
								'id'    => [
									'type' => 'string',
								],
							],
						],
					],
				],
			],
			'notify'   => [
				'type'              => 'object',
				'sanitize_callback' => [ __CLASS__, 'sanitize_param' ],
				'validate_callback' => 'rest_validate_request_arg',
				'properties'        => [
					'active'             => [
						'type' => 'boolean',
					],
					'bot'                => [
						'type'    => 'string',
						'pattern' => $username_pattern,
					],
					'catch_emails'       => [
						'type'            => 'array',
						'sanitization_cb' => [ __CLASS__, 'sanitize_catch_emails' ],
						'items'           => [
							'type'       => 'object',
							'properties' => [
								'email'    => [
									'type' => 'string',
								],
								'chat_ids' => [
									'type'  => 'array',
									'items' => [
										'type' => 'string',
									],
								],
							],
						],
					],
					'user_notifications' => [
						'type' => 'boolean',
					],
					'message_template'   => [
						'type' => 'string',
					],
					'parse_mode'         => [
						'type' => 'string',
						'enum' => [ 'none', 'HTML' ],
					],
				],
			],
			'proxy'    => [
				'type'              => 'object',
				'sanitize_callback' => [ __CLASS__, 'sanitize_param' ],
				'validate_callback' => 'rest_validate_request_arg',
				'properties'        => [
					'active'            => [
						'type' => 'boolean',
					],
					'proxy_method'      => [
						'type' => 'string',
						'enum' => [ 'cf_worker', 'google_script', 'php_proxy' ],
					],
					'cf_worker_url'     => [
						'type'   => 'string',
						'format' => 'url',
					],
					'google_script_url' => [
						'type'   => 'string',
						'format' => 'url',
					],
					'proxy_host'        => [
						'type' => 'string',
					],
					'proxy_port'        => [
						'type' => 'string',
					],
					'proxy_type'        => [
						'type' => 'string',
						'enum' => [
							'CURLPROXY_HTTP',
							'CURLPROXY_SOCKS4',
							'CURLPROXY_SOCKS4A',
							'CURLPROXY_SOCKS5',
							'CURLPROXY_SOCKS5_HOSTNAME',
						],
					],
					'proxy_username'    => [
						'type' => 'string',
					],
					'proxy_password'    => [
						'type' => 'string',
					],
				],
			],
			'advanced' => [
				'type'              => 'object',
				'sanitize_callback' => [ __CLASS__, 'sanitize_param' ],
				'validate_callback' => 'rest_validate_request_arg',
				'properties'        => [
					'send_files_by_url' => [
						'type' => 'boolean',
					],
					'enable_logs'       => [
						'type'  => 'array',
						'items' => [
							'type' => 'string',
							'enum' => [ 'bot_api_out', 'bot_api_in', 'p2tg' ],
						],
					],
					'clean_uninstall'   => [
						'type' => 'boolean',
					],
					'license_key'       => [
						'type' => 'string',
					],
				],
			],
		];
	}

	/**
	 * Sanitize the request param.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed           $value   Value of the param.
	 * @param WP_REST_Request $request WP REST API request.
	 * @param string          $param     The param key.
	 */
	public static function sanitize_param( $value, WP_REST_Request $request, $param ) {
		// First lets make the value safer.
		$safe_value = Utils::sanitize( $value );

		if ( 'notify' === $param ) {
			// Sanitize the template separately.
			$safe_value['message_template'] = Utils::sanitize_message_template( $value['message_template'] );

			// Remove useless catch email groups.
			if ( ! empty( $safe_value['catch_emails'] ) ) {
				$catch_emails = [];

				foreach ( $safe_value['catch_emails'] as $group ) {
					if ( empty( $group['email'] ) || empty( $group['chat_ids'] ) ) {
						continue;
					}

					$group['email'] = implode(
						',',
						array_filter(
							array_map( 'trim', explode( ',', $group['email'] ) )
						)
					);

					$group['chat_ids'] = array_filter( $group['chat_ids'] );

					if ( ! empty( $group['chat_ids'] ) ) {
						$catch_emails[] = $group;
					}
				}
				$safe_value['catch_emails'] = $catch_emails;
			}
		}

		// Remove useless bot instances.
		if ( 'bots' === $param && ! empty( $safe_value['collection'] ) ) {

			$collection = [];

			foreach ( $safe_value['collection'] as $group ) {
				if ( empty( $group['bot_token'] ) || empty( $group['bot_username'] ) ) {
					continue;
				}
				$collection[] = $group;
			}
			$safe_value['collection'] = $collection;
		}

		return $safe_value;
	}
}
