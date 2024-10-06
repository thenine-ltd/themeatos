<?php
/**
 * Provides logging capabilities for debugging purposes.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\includes;

use ReflectionClass;
use WPTelegram\BotAPI\Response;
use WPTelegram\BotAPI\API;
use WP_Post;
use WPTelegram\Pro\modules\p2tg\RequestCheck;
use WPTelegram\Pro\modules\p2tg\Main as P2TGMain;

/**
 * Provides logging capabilities for debugging purposes.
 */
class Logger extends BaseClass {

	/**
	 * Enabled Log types
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     array       $active_logs    The enabled logs
	 */
	private static $active_logs;

	/**
	 * Whether already hooked or not.
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     array       $hooked_up  The enabled logs
	 */
	private static $hooked_up = false;

	/**
	 * Information about the processed post
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     array       $p2tg_post_info     The Post info
	 */
	private $p2tg_post_info;

	/**
	 * Set the active logs.
	 *
	 * @param array $active_logs The logs that are activated.
	 *
	 * @return self
	 */
	public function set_active_logs( $active_logs ) {

		self::$active_logs = (array) $active_logs;

		return $this;
	}

	/**
	 * Get the active logs
	 */
	public function get_active_logs() {

		return (array) apply_filters( 'wptelegram_pro_logger_active_logs', self::$active_logs );
	}

	/**
	 * Hook into WP Telegram to create logs.
	 */
	public function hookup() {

		add_action( 'init', [ $this, 'view_log' ] );

		// avoid hooking in multiple times.
		if ( ! self::$hooked_up && ! empty( self::$active_logs ) ) {

			$this->hook_it_up();

			self::$hooked_up = true;
		}
	}

	/**
	 * Hook into WP Telegram to create logs.
	 */
	protected function hook_it_up() {

		foreach ( $this->get_active_logs() as $log_type ) {

			$method = [ $this, "hookup_for_{$log_type}" ];

			if ( is_callable( $method ) ) {
				call_user_func( $method );
			}
		}
	}

	/**
	 * Get the URL for a log type.
	 *
	 * @param string $type Log type.
	 * @return string
	 */
	public static function get_log_url( $type ) {

		$url = add_query_arg(
			[
				'action' => 'wptelegram_pro_view_log',
				'hash'   => wp_hash( 'log' ),
				'type'   => $type,
			],
			site_url()
		);

		return apply_filters( 'wptelegram_pro_logger_log_url', $url, $type );
	}

	/**
	 * View logs
	 */
	public function view_log() {

		// phpcs:ignore
		if ( isset( $_GET['action'], $_GET['hash'], $_GET['type'] ) && 'wptelegram_pro_view_log' === $_GET['action'] && isset( $_GET['hash'] ) ) {
			$hash = sanitize_text_field( wp_unslash( $_GET['hash'] ) ); // phpcs:ignore
			$type = sanitize_text_field( wp_unslash( $_GET['type'] ) ); // phpcs:ignore

			if ( ! empty( $hash ) && ! empty( $type ) ) {
				global $wp_filesystem;

				$file_path = self::get_log_file_path( $type, $hash );

				if ( $wp_filesystem->exists( $file_path ) ) {
					$contents = $wp_filesystem->get_contents( $file_path );
				} else {
					$contents = 'Log file not found!';
				}

				header( 'Content-Type: text/plain' );

				exit( $contents ); // phpcs:ignore
			}
		}
	}

	/**
	 * Hook for \WPTelegram\BotAPI log.
	 */
	protected function hookup_for_bot_api_out() {

		add_action( 'wptelegram_bot_api_debug', [ $this, 'add_bot_api_debug' ], 10, 2 );
	}

	/**
	 * Hook for incoming bot updates log.
	 */
	protected function hookup_for_bot_api_in() {

		add_action( 'wptelegram_pro_bots_webhook_update_init', [ $this, 'add_webhook_update_init' ], 10, 1 );
		add_action( 'wptelegram_pro_bots_after_get_update', [ $this, 'add_bots_after_update' ], 10, 4 );
	}

	/**
	 * Hook for Post to Telegram log
	 */
	protected function hookup_for_p2tg() {

		add_action( 'wptelegram_pro_p2tg_before_send_post', [ $this, 'add_before_send' ], 999, 5 );

		add_action( 'wptelegram_pro_p2tg_set_form_data', [ $this, 'set_form_data' ], 999, 2 );

		add_action( 'wptelegram_pro_p2tg_post_sv_check_failed', [ $this, 'add_sv_check' ], 999, 2 );

		add_action( 'wptelegram_pro_p2tg_before_process', [ $this, 'add_before_process' ], 999, 2 );

		add_action( 'wptelegram_pro_p2tg_set_instance_for_delay', [ $this, 'add_set_instance_for_delay' ], 999, 3 );

		add_filter( 'wptelegram_pro_p2tg_responses', [ $this, 'add_get_responses' ], 999, 3 );

		add_filter( 'wptelegram_pro_p2tg_post_date_rules_apply', [ $this, 'add_date_rules_apply' ], 999, 3 );

		add_filter( 'wptelegram_pro_p2tg_post_type_rules_apply', [ $this, 'add_post_type_rules_apply' ], 999, 3 );

		add_filter( 'wptelegram_pro_p2tg_dynamic_rules_apply', [ $this, 'add_dynamic_rules_apply' ], 999, 3 );

		add_filter( 'wptelegram_pro_p2tg_featured_image_source', [ $this, 'add_featured_image_source' ], 999, 4 );

		add_action( 'wptelegram_pro_p2tg_after_process', [ $this, 'add_after_process' ], 10, 3 );

		add_action( 'wptelegram_pro_p2tg_send_post_finish', [ $this, 'add_post_finish' ], 999, 5 );

		add_action( 'wptelegram_pro_p2tg_after_send_post', [ $this, 'add_after_send' ], 999, 3 );

		add_action( 'wptelegram_pro_prepare_content_error', [ $this, 'prepare_content_error' ], 10, 3 );
	}

	/**
	 * Get the current request type.
	 *
	 * @param WP_Post $post    The post being handled.
	 */
	private function get_request_type( $post ) {

		$request_check = new ReflectionClass( RequestCheck::class );

		$constants = $request_check->getConstants();

		foreach ( $constants as $constant => $value ) {
			if ( RequestCheck::is( $value, $post ) ) {
				return $constant;
			}
		}
	}

	/**
	 * Get the key from post.
	 *
	 * @param WP_Post $post The post being handled.
	 */
	public function get_key( $post ) {
		return $post->post_type . '-' . $post->ID . '-' . $post->post_status;
	}

	/**
	 * Handle the debug action.
	 *
	 * @param Response $response  The API response.
	 * @param API      $tg_api    The post being handled.
	 */
	public function add_bot_api_debug( $response, $tg_api ) {

		$res = $tg_api->get_last_response();

		$params = $tg_api->get_request()->get_params();

		if ( isset( $params['reply_markup'] ) ) {
			// Make sure that the mark up is easy to read.
			$params['reply_markup'] = json_decode( $params['reply_markup'], true );
		}

		// add the method and request params.
		$text = 'Method: ' . $tg_api->get_request()->get_api_method() . PHP_EOL . 'Params: ' . $this->json_encode( $params, 'bot-api' ) . PHP_EOL . '--------------------------------' . PHP_EOL;

		// add the response.
		if ( is_wp_error( $res ) ) {
			$text .= 'WP_Error: ' . $res->get_error_code() . ' ' . $res->get_error_message() . PHP_EOL;

			$base_url = $tg_api->get_client()->get_base_url();
			// redact the worker name if present.
			$base_url = preg_replace( '/(?<=https:\/\/)[^\.]+?(?=\.)/', '***', $base_url );

			$text .= 'URL: ' . $base_url;
		} else {
			$text .= 'Response: ' . $this->json_encode( json_decode( $res->get_body(), true ), 'bot-api' );
		}

		$this->write_log( 'bot-api', $text );
	}

	/**
	 * Add the incoming bot updates init hook.
	 *
	 * @param string $bot_token     Bot token.
	 *
	 * @return void
	 */
	public function add_webhook_update_init( $bot_token ) {

		$text = 'webhook-init: ' . substr( $bot_token, 0, 10 );

		$this->write_log( 'bot-updates', $text );
	}

	/**
	 * Add the incoming bot updates after hook.
	 *
	 * @param array  $update        The update object.
	 * @param string $bot_token     Bot token.
	 * @param string $update_method The update method.
	 * @param array  $bots          Bot collection.
	 *
	 * @return void
	 */
	public function add_bots_after_update( $update, $bot_token, $update_method, $bots ) {

		if ( ! empty( $update ) ) {
			$text = $bots[ $bot_token ] . ' ' . $this->json_encode( $update, 'bot-updates' );

			$this->write_log( 'bot-updates', $text );
		}
	}

	/**
	 * Handle p2tg before post send action.
	 *
	 * @param mixed   $result   The action result.
	 * @param WP_Post $post     The post being handled.
	 * @param string  $trigger  The source trigger.
	 * @param array   $inc_inst Included instances.
	 * @param bool    $force    Whether to force send.
	 */
	public function add_before_send( $result, $post, $trigger, $inc_inst, $force ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		$this->p2tg_post_info[ $key ]['before_send'] = [
			'trigger'      => $trigger,
			'inc_inst'     => $inc_inst,
			'force'        => $force,
			'request_type' => $this->get_request_type( $post ),
		];
	}

	/**
	 * Add form data to the log.
	 *
	 * @param mixed   $form_data The action result.
	 * @param WP_Post $post      The post being handled.
	 */
	public function set_form_data( $form_data, $post ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		// Remove message_template to make the logs clean.
		if ( ! empty( $form_data['instances'] ) && is_array( $form_data['instances'] ) ) {
			$form_data['instances'] = array_map(
				function ( $instance ) {
					unset( $instance['message_template'] );
					return $instance;
				},
				$form_data['instances']
			);
		}

		$this->p2tg_post_info[ $key ]['form_data'] = $form_data;
	}

	/**
	 * Add security and validity info.
	 *
	 * @param int     $validity The request validity.
	 * @param WP_Post $post     The post being handled.
	 */
	public function add_sv_check( $validity, $post ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		$this->p2tg_post_info[ $key ]['sv'] = $validity;
	}

	/**
	 * Add before process info.
	 *
	 * @param WP_Post $post      The post being handled.
	 * @param array   $instances The instances collection.
	 */
	public function add_before_process( $post, $instances ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		$this->p2tg_post_info[ $key ]['before_process'] = [
			'instances' => array_keys( $instances ),
		];
	}

	/**
	 * Add before process info.
	 *
	 * @param float   $delay       Delay in posting.
	 * @param int     $instance_id Instance ID.
	 * @param WP_Post $post        The post being handled.
	 */
	public function add_set_instance_for_delay( $delay, $instance_id, $post ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		$this->p2tg_post_info[ $key ][ $instance_id ]['delay'] = $delay;
	}

	/**
	 * Add before process info.
	 *
	 * @param array   $responses The responses collection.
	 * @param WP_Post $post      The post being handled.
	 * @param array   $instances The instances collection.
	 */
	public function add_get_responses( $responses, $post, $instances ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		$this->p2tg_post_info[ $key ]['responses'] = [
			'responses' => $this->shorten_responses( $responses ),
			'instances' => array_keys( $instances ),
		];

		return $responses;
	}

	/**
	 * Add after process info.
	 *
	 * @param WP_Post $post      The post being handled.
	 * @param array   $instances The instances collection.
	 * @param array   $responses The responses collection.
	 */
	public function add_after_process( $post, $instances, $responses ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		$this->p2tg_post_info[ $key ]['after_process'] = [
			'instances' => array_keys( $instances ),
			'responses' => $this->shorten_responses( $responses ),
		];
	}

	/**
	 * Shorten responses.
	 *
	 * @param array $responses The responses.
	 * @return array
	 */
	public function shorten_responses( $responses ) {
		if ( empty( $responses ) ) {
			return $responses;
		}

		foreach ( $responses as $instance_id => $inst_responses ) {
			$responses[ $instance_id ] = array_map(
				function( $response ) {
					reset( $response );
					return key( $response );
				},
				$inst_responses
			);
		}

		return $responses;
	}

	/**
	 * Add date_rules_apply info
	 *
	 * @param boolean $rules_apply The post being handled.
	 * @param Options $options     The post being handled.
	 * @param WP_Post $post        The post being handled.
	 */
	public function add_date_rules_apply( $rules_apply, $options, $post ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		$this->p2tg_post_info[ $key ]['rules'][ $options->get( 'id' ) ]['date_rules'] = [
			'apply'   => $rules_apply,
			'sent2tg' => get_post_meta( $post->ID, P2TGMain::PREFIX . 'sent2tg', true ),
		];

		return $rules_apply;
	}

	/**
	 * Add post_type_rules_apply info
	 *
	 * @param boolean $rules_apply The post being handled.
	 * @param Options $options     The post being handled.
	 * @param WP_Post $post        The post being handled.
	 */
	public function add_post_type_rules_apply( $rules_apply, $options, $post ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		$this->p2tg_post_info[ $key ]['rules'][ $options->get( 'id' ) ]['post_type_rules'] = $rules_apply;

		return $rules_apply;
	}

	/**
	 * Add dynamic_rules_apply info
	 *
	 * @param boolean $rules_apply The post being handled.
	 * @param Options $options     The post being handled.
	 * @param WP_Post $post        The post being handled.
	 */
	public function add_dynamic_rules_apply( $rules_apply, $options, $post ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		$this->p2tg_post_info[ $key ]['rules'][ $options->get( 'id' ) ]['dynamic_rules'] = $rules_apply;

		return $rules_apply;
	}

	/**
	 * Add rules_apply info
	 *
	 * @param string  $source            The featured image source.
	 * @param WP_Post $post              The post being handled.
	 * @param Options $options           The post being handled.
	 * @param boolean $send_files_by_url The featured image source.
	 */
	public function add_featured_image_source( $source, $post, $options, $send_files_by_url ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		$this->p2tg_post_info[ $key ]['image_source'][ $options->get( 'id' ) ] = [
			'send_image'  => $options->get( 'send_featured_image' ),
			'has_image'   => has_post_thumbnail( $post->ID ),
			'send_by_url' => $send_files_by_url,
			'source'      => $source,
		];

		return $source;
	}

	/**
	 * Add post send finish info.
	 *
	 * @param WP_Post $post            The post being handled.
	 * @param string  $trigger         The source trigger.
	 * @param boolean $ok              The featured image source.
	 * @param array   $inc_inst        Included instances.
	 * @param array   $processed_posts The featured image source.
	 */
	public function add_post_finish( $post, $trigger, $ok, $inc_inst, $processed_posts ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		$this->p2tg_post_info[ $key ]['finish'] = [
			'ok'        => $ok,
			'inc_inst'  => $inc_inst,
			'processed' => $processed_posts,
		];
	}

	/**
	 * Add after send post info.
	 *
	 * @param mixed   $result  The action result.
	 * @param WP_Post $post    The post being handled.
	 * @param string  $trigger The source trigger.
	 */
	public function add_after_send( $result, $post, $trigger ) {

		// create a an entry from post ID and its status.
		$key = $this->get_key( $post );

		if ( is_array( $result ) ) {
			$result = array_map(
				function( $instances ) {
					if ( is_array( $instances ) ) {
						return array_map(
							function( $responses ) {
								if ( is_array( $responses ) ) {
									return array_map(
										function( $response ) {
											if ( $response instanceof Response ) {
												return $response->get_decoded_body();
											}
											return $response;
										},
										$responses
									);
								}
								return $responses;
							},
							$instances
						);
					}
					return $instances;
				},
				$result
			);
		}

		$this->p2tg_post_info[ $key ]['after_send'] = [
			'result' => $result,
		];

		$text = $this->json_encode( [ $key => $this->p2tg_post_info[ $key ] ], 'p2tg' );

		$this->write_log( 'p2tg', $text );

		unset( $this->p2tg_post_info[ $key ] );
	}

	/**
	 * Convert the data to JSON with filter.
	 *
	 * @param array  $data The data to encode.
	 * @param string $type The log type.
	 *
	 * @return string
	 */
	private function json_encode( $data, $type = '' ) {
		$params = [ $data ];
		if ( apply_filters( 'wptelegram_pro_logger_pretty_json', false, $type ) ) {
			$params[] = JSON_PRETTY_PRINT;
		}

		return call_user_func_array( 'json_encode', $params );
	}

	/**
	 * Handle prepare content error.
	 *
	 * @param ConverterException $exception The exception thrown.
	 * @param string             $content The content that was being prepared.
	 * @param array              $options The options passed to prepare_content.
	 *
	 * @return void
	 */
	public function prepare_content_error( $exception, $content, $options ) {
		$text  = 'Error: ' . $exception . PHP_EOL;
		$text .= 'Options: ' . wp_json_encode( $options ) . PHP_EOL;
		$text .= 'Content: ' . $content;

		$this->write_log( 'converter', $text );
	}

	/**
	 * Write the log to file.
	 *
	 * @param string $type The log type.
	 * @param string $text The content.
	 */
	public function write_log( $type, $text ) {

		$bot_token_regex = '/' . \WPTelegram\BotAPI\API::BOT_TOKEN_PATTERN . '/';

		$text = preg_replace( $bot_token_regex, '**********', $text );

		$file_path = self::get_log_file_path( $type );

		global $wp_filesystem;

		$contents = '[' . current_time( 'mysql' ) . ']' . PHP_EOL . $text . PHP_EOL . PHP_EOL;

		// Default to 512 kb.
		$max_filesize = apply_filters( 'wptelegram_pro_logger_max_filesize', ( 1024 ** 2 ) / 2, $type, $file_path );

		// Make sure that the file size remains less than $max_filesize.
		if ( $wp_filesystem->exists( $file_path ) && $wp_filesystem->size( $file_path ) < $max_filesize ) {
			// Append the existing content.
			$contents = $wp_filesystem->get_contents( $file_path ) . $contents;
		}

		$wp_filesystem->put_contents( $file_path, $contents );
	}

	/**
	 * Get log file path.
	 *
	 * @since 1.4.0
	 *
	 * @param string $type Log type.
	 * @param string $hash The hash to use in file name.
	 *
	 * @return string
	 */
	public static function get_log_file_path( $type, $hash = '' ) {

		require_once ABSPATH . 'wp-admin/includes/file.php';
		WP_Filesystem();

		global $wp_filesystem;

		$file_name = self::get_log_file_name( $type, $hash );

		$file_path = $wp_filesystem->wp_content_dir() . $file_name;

		return apply_filters( 'wptelegram_pro_logger_log_file_path', $file_path, $type );
	}

	/**
	 * Get log file name.
	 *
	 * @since 1.4.0
	 *
	 * @param string $type Log type.
	 * @param string $hash The hash to use in file name.
	 *
	 * @return string
	 */
	public static function get_log_file_name( $type, $hash = '' ) {

		$hash = $hash ? $hash : wp_hash( 'log' );

		$file_name = "wptelegram-pro-{$type}-{$hash}.log";

		return apply_filters( 'wptelegram_pro_logger_log_file_name', $file_name, $type, $hash );
	}
}
