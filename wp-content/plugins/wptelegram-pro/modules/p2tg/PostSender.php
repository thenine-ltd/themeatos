<?php
/**
 * Post Handling functionality of the plugin.
 *
 * @link        https://wptelegram.pro
 * @since       1.0.0
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 */

namespace WPTelegram\Pro\modules\p2tg;

use WPTelegram\Pro\modules\BaseClass;
use WPTelegram\Pro\includes\Options;
use WPTelegram\BotAPI\API;
use WPTelegram\BotAPI\Response;
use WPTelegram\Pro\includes\Utils as MainUtils;
use WPTelegram\Pro\modules\bots\Utils as BotsUtils;
use WP_Post;
use WP_Error;

/**
 * The Post Handling functionality of the plugin.
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 * @author      WP Socio
 */
class PostSender extends BaseClass {

	/**
	 * Bot Token to be used for Telegram API calls
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     string  Telegram Bot Token.
	 */
	private $bot_token;

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
	 * Responses prepared from all instances
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     array       $instance_responses
	 */
	private $responses;

	/**
	 * The data from post edit page.
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var Options $form_data The submitted form data.
	 */
	private $form_data;

	/**
	 * Instance delay handler
	 *
	 * @since   1.0.0
	 * @access  private
	 *
	 * @var InstanceDelayHandler $delay_handler The delay handler instance.
	 */
	private $delay_handler;

	/**
	 * The Telegram API
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     API $bot_api Telegram API Object
	 */
	private $bot_api;

	/**
	 * WP_Error
	 *
	 * @var WP_Error
	 */
	protected $wp_error;

	/**
	 * The post to be handled
	 *
	 * @var WP_Post $post   Post object.
	 */
	protected $post;

	/**
	 * The posts processed in the current request
	 * to be used to avoid double posting.
	 *
	 * @var array $processed_posts The posts that have been processed.
	 */
	protected static $processed_posts = [];

	/**
	 * The send post trigger.
	 *
	 * @since  1.0.0
	 * @access protected
	 * @var string $trigger The trigger name.
	 */
	protected $trigger;

	/**
	 * Whether the post is from Gutenberg REST Request.
	 *
	 * @var bool $is_gutenberg_post Is Gutenberg post.
	 */
	public static $is_gutenberg_post;

	/**
	 * Set up the basics.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post          The post being proessed.
	 * @param string  $trigger       The source of the trigger.
	 * @param array   $inc_instances The included instances.
	 *
	 * @return void
	 */
	public function init( $post, $trigger, $inc_instances ) {

		$this->post = $post;

		$this->form_data = new Options();

		$this->delay_handler = new InstanceDelayHandler( $post, $trigger );

		$this->bot_token = BotsUtils::get_bot_token_from_username( $this->module()->options()->get( 'bot' ) );

		$this->trigger = $trigger;

		$this->set_form_data();

		do_action( 'wptelegram_pro_p2tg_send_post_init', $this->post, $trigger, $inc_instances );
	}

	/**
	 * Sets the form submission data.
	 *
	 * @since 3.0.0
	 */
	public function set_form_data() {
		// Default data.
		$this->form_data = [
			'send2tg'         => null,
			'force_send'      => null,
			'override_switch' => false,
			'instances'       => [],
		];

		// Form data matters only if post edit switch is enabled.
		// or if it's an instant post.
		if ( ! Admin::show_post_edit_switch() && 'instant' !== $this->trigger ) {
			return;
		}

		$data_source = null;

		// phpcs:disable WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput
		if ( RequestCheck::is( RequestCheck::REST_REQUEST ) ) {
			$raw_body = file_get_contents( 'php://input' );

			if ( ! empty( $raw_body ) ) {
				$body = json_decode( $raw_body, true );
				// It has come from Gutenberg.
				if ( ! empty( $body[ Main::PREFIX ] ) ) {
					$data_source = $body[ Main::PREFIX ];

					$form_data = MainUtils::sanitize( $data_source );

					$form_data['send2tg']    = $form_data['send2tg'] ? 'yes' : 'no';
					$form_data['force_send'] = $form_data['force_send'] ? 'yes' : 'no';

					$this->form_data = $form_data;
				}
			}
		} elseif ( ! empty( $_POST[ Main::PREFIX ] ) ) {
			$data_source = $_POST[ Main::PREFIX ];

			$form_data = MainUtils::sanitize( wp_unslash( $_POST[ Main::PREFIX ] ) );
			// set the boolean value.
			if ( isset( $form_data['override_switch'] ) ) {
				$form_data['override_switch'] = 'on' === $form_data['override_switch'];
			}

			$this->form_data = array_merge( $this->form_data, $form_data );
		}

		// Override the default options.
		if ( $this->defaults_overridden() && ! empty( $this->form_data['instances'] ) ) {
			foreach ( $this->form_data['instances'] as $instance_id => &$instance ) {

				$instance['active'] = ! empty( $instance['active'] );

				// if not active or no destination channel is selected.
				if ( ! $instance['active'] || empty( $instance['channels'] ) ) {

					// remove the instance.
					unset( $this->form_data['instances'][ $instance_id ] );

					continue;
				}

				// if the template is set.
				if ( isset( $instance['message_template'] ) ) {

					// Sanitize the template separately.
					$message_template = $data_source['instances'][ $instance_id ]['message_template'];

					// If it's not a REST request, unslash it.
					if ( ! RequestCheck::is( RequestCheck::REST_REQUEST ) ) {
						$message_template = wp_slash( $message_template );
					}
					$message_template = MainUtils::sanitize_message_template( $message_template );

					// override the template.
					$instance['message_template'] = $message_template;
				}

				// if files are included.
				if ( isset( $instance['files'] ) ) {

					// Remove empty values values.
					$instance['files'] = array_filter( (array) $instance['files'] );
				}

				// if send featured image.
				if ( isset( $instance['send_featured_image'] ) ) {
					$instance['send_featured_image'] = $instance['send_featured_image'] && 'off' !== $instance['send_featured_image'];
				}
			}
		}
		// phpcs:disable WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput

		do_action( 'wptelegram_pro_p2tg_set_form_data', $this->form_data, $this->post );
	}

	/**
	 * Handle Insert Post Hook.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $post_id The post ID.
	 * @param WP_Post $post    The post Object.
	 */
	public function wp_insert_post( $post_id, $post ) {

		if ( self::$is_gutenberg_post ) {
			return;
		}

		$this->send_post( $post, __FUNCTION__ );
	}

	/**
	 * Handle Scheduled Post.
	 *
	 * @since   1.0.0
	 *
	 * @param WP_Post $post The post Object.
	 */
	public function future_to_publish( $post ) {

		$send_scheduled_posts = (bool) apply_filters( 'wptelegram_pro_p2tg_send_scheduled_posts', true, $post );

		if ( $send_scheduled_posts ) {

			$this->send_post( $post, __FUNCTION__ );
		}
	}

	/**
	 * Handle the delayed instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $post_id     The post ID from WP Cron.
	 * @param string $instance_id The instance ID.
	 */
	public function delayed_instance( $post_id, $instance_id ) {

		$post = get_post( $post_id );

		if ( $post ) {

			$trigger = __FUNCTION__ . '_' . $instance_id;

			$this->send_post( $post, $trigger, [ $instance_id ] );
		}
	}

	/**
	 * Handle the post published via WP REST API.
	 *
	 * @since 1.4.0
	 *
	 * @param WP_Post $post The post to handle.
	 */
	public function rest_after_insert( $post ) {

		$this->send_post( $post, __FUNCTION__ );
	}

	/**
	 * Make sure the global $post and its data is set.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post    The post to be handled.
	 * @param string  $trigger The name of the source trigger hook.
	 */
	private function may_be_setup_postdata( $post, $trigger ) {
		$previous_post = null;

		// Make sure the global $post and its data is set.
		if ( false !== strpos( $trigger, 'delayed_instance' ) ) {

			if ( ! empty( $GLOBALS['post'] ) ) {
				$previous_post = $GLOBALS['post'];
			}
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$GLOBALS['post'] = $post;

			setup_postdata( $post );
		}

		return $previous_post;
	}

	/**
	 * Make sure the global $post and its data is reset.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post|null $previous_post The post to be handled.
	 * @param string       $trigger       The name of the source trigger hook.
	 */
	private function may_be_reset_postdata( $previous_post, $trigger ) {

		if ( false !== strpos( $trigger, 'delayed_instance' ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride
			$GLOBALS['post'] = $previous_post;

			if ( $previous_post ) {
				setup_postdata( $previous_post );
			}
		}
	}

	/**
	 * May be send the post to Telegram
	 * if P2TG instances are found and their rules apply.
	 *
	 * @since   1.0.0
	 *
	 * @param WP_Post $post     The post to be handled.
	 * @param string  $trigger  The name of the source trigger hook.
	 * @param array   $inc_inst The array of IDs of P2TG Instances to be included/processed.
	 * @param bool    $force    Whether to bypass the custom rules.
	 *
	 * @return mixed
	 */
	public function send_post( $post, $trigger = 'instant', $inc_inst = [], $force = false ) {

		if ( empty( $post ) ) {
			return __LINE__;
		}

		$previous_post = $this->may_be_setup_postdata( $post, $trigger );

		$result = __LINE__;

		do_action( 'wptelegram_pro_p2tg_before_send_post', $result, $post, $trigger, $inc_inst, $force );

		$result = $this->send_the_post( $post, $trigger, $inc_inst, $force );

		do_action( 'wptelegram_pro_p2tg_after_send_post', $result, $post, $trigger, $inc_inst, $force );

		$this->may_be_reset_postdata( $previous_post, $trigger );

		return $result;
	}

	/**
	 * May be send the post to Telegram
	 * if P2TG instances are found and their rules apply.
	 *
	 * This method is not intended to be used directly, although it can be.
	 * Use wptelegram_pro_p2tg_send_post() instead.
	 * Relying on this method is not safe as it may change in future
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post     The post to be handled.
	 * @param string  $trigger  The name of the source trigger hook.
	 * @param array   $inc_inst The array of IDs of P2TG Instances to be included/processed.
	 * @param bool    $force    Whether to bypass the custom rules.
	 *
	 * @return mixed
	 */
	public function send_the_post( $post, $trigger, $inc_inst, $force ) {

		$this->init( $post, $trigger, $inc_inst );

		if ( empty( $this->bot_token ) ) {
			return __LINE__;
		}

		// if already processed in the current request.
		if ( in_array( $post->ID, self::$processed_posts, true ) ) {
			return __LINE__;
		}

		$ok = true;
		// for logging.
		$result = __LINE__;

		// if not doing "rest_after_insert_{$post_type}" action.
		$is_rest_pre_insert = RequestCheck::is( RequestCheck::REST_PRE_INSERT, $this->post );

		if ( $is_rest_pre_insert ) {

			// come back later.
			add_action( 'rest_after_insert_' . $this->post->post_type, [ $this, 'rest_after_insert' ], 10, 1 );

			$ok = false;

			$result .= ':' . __LINE__;
		}

		if ( $ok ) {
			$validity = $this->security_and_validity_check();
			/**
			 * If the security check failed
			 * returned int (the line number) or boolean (false).
			 */
			if ( true !== $validity ) {

				$ok = false;

				$result .= ':' . __LINE__;

				/**
				 * Fires after the security check fails
				 * Can be used to determine which condition actually failed
				 * by checking for the integer value of $validity - the line number.
				 *
				 * @since 1.0.0
				 *
				 * @param WP_Post  $post     The current post.
				 * @param int|bool $validity The validity status.
				 */
				do_action( 'wptelegram_pro_p2tg_post_sv_check_failed', $validity, $this->post, $trigger );
			}
		}

		if ( $ok ) {

			$this->set_p2tg_instances( $inc_inst );

			if ( 'no' === $this->form_data['send2tg'] || empty( $this->p2tg_instances ) ) {
				$ok = false;

				$result .= ':' . __LINE__;
			}
		}

		if ( 'no' === $this->form_data['send2tg'] && $this->is_valid_status() ) {
			$this->delay_handler->clear_scheduled_hooks();

			$result .= ':' . __LINE__;
		}

		// If draft, pending, future etc.
		$valid_non_live = $this->is_status_of_type( 'non_live' );

		if ( $valid_non_live && ! $is_rest_pre_insert ) {

			$this->may_be_save_instances_to_meta();

			$ok = false;

			$result .= ':' . __LINE__;

		} else {
			// House keeping.
			$this->may_be_clean_up();

			$result .= ':' . __LINE__;
		}

		// If some rules should be bypassed.
		if ( $ok && $force ) {
			$this->bypass_rules( $force );
		}

		if ( $ok ) {

			// Everything looks good :) .
			$result = $this->process();

			// Add the post ID to the processed array.
			self::$processed_posts[] = $post->ID;
		}

		do_action( 'wptelegram_pro_p2tg_send_post_finish', $this->post, $trigger, $ok, $inc_inst, self::$processed_posts );

		return $result;
	}

	/**
	 * The post statuses that are valid/allowed.
	 *
	 * @since 1.0.0
	 *
	 * @return array[]
	 */
	public function get_valid_post_statuses() {
		$valid_statuses = [
			'live'     => [ // The ones that are live/visible.
				'publish',
				'private',
			],
			'non_live' => [ // The that are not yet live for the audience.
				'future',
				'draft',
				'pending',
			],
		];
		return (array) apply_filters( 'wptelegram_pro_p2tg_valid_post_statuses', $valid_statuses, $this->post );
	}

	/**
	 * If it's a valid status that the should be handled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function is_valid_status() {

		$valid_statuses = call_user_func_array( 'array_merge', array_values( $this->get_valid_post_statuses() ) );

		return in_array( $this->post->post_status, $valid_statuses, true );
	}

	/**
	 * If it's a live/non_live status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $type The type of status.
	 *
	 * @return bool
	 */
	public function is_status_of_type( $type ) {

		$valid_statuses = $this->get_valid_post_statuses();

		return in_array( $this->post->post_status, $valid_statuses[ $type ], true );
	}

	/**
	 * Save instances meta for the scheduled post
	 *
	 * @since   1.0.0
	 */
	private function may_be_save_instances_to_meta() {

		// If options need to be saved.
		if ( $this->defaults_overridden() && 'no' !== $this->form_data['send2tg'] ) {
			self::save_data_to_meta( $this->post, $this->p2tg_instances, $this->form_data );
		}
		self::save_form_data_to_meta( $this->post, $this->form_data );
	}

	/**
	 * Save instance and form data to post meta.
	 *
	 * This method is used from outside this class to avoid $this.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post    The post being processed.
	 * @param array   $p2tg_instances The instances to be processed.
	 * @param Options $form_data      The data from post edit page.
	 */
	public static function save_data_to_meta( $post, $p2tg_instances, $form_data ) {

		$p2tg_instances = array_map( [ Utils::class, 'encode_instance_values' ], $p2tg_instances );

		if ( ! add_post_meta( $post->ID, Main::PREFIX . 'instances', $p2tg_instances, true ) ) {
			update_post_meta( $post->ID, Main::PREFIX . 'instances', $p2tg_instances );
		}
		self::save_form_data_to_meta( $post, $form_data );
	}

	/**
	 * Save form data to post meta.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_Post $post    The post being processed.
	 * @param Options $form_data The data from post edit page.
	 */
	public static function save_form_data_to_meta( $post, $form_data ) {
		// if it's a future post and override switch is used.
		if ( $form_data['send2tg'] ) {
			if ( ! add_post_meta( $post->ID, Main::PREFIX . 'send2tg', $form_data['send2tg'], true ) ) {
				update_post_meta( $post->ID, Main::PREFIX . 'send2tg', $form_data['send2tg'] );
			}
		}

		// if it's a future post and override switch is used.
		if ( $form_data['force_send'] ) {
			if ( ! add_post_meta( $post->ID, Main::PREFIX . 'force_send', $form_data['force_send'], true ) ) {
				update_post_meta( $post->ID, Main::PREFIX . 'force_send', $form_data['force_send'] );
			}
		}
	}

	/**
	 * Add the required filters to bypass some rules.
	 *
	 * @since 1.0.0
	 *
	 * @param bool $force Whether to bypass the dynamic rules.
	 */
	private function bypass_rules( $force = false ) {

		// Override the default saved option.
		add_filter( 'wptelegram_pro_p2tg_bypass_post_date_rules', '__return_true' );

		// If forced to bypass the custom rules.
		if ( $force ) {
			add_filter( 'wptelegram_pro_p2tg_bypass_dynamic_rules', '__return_true' );
		}
	}

	/**
	 * Security checks
	 *
	 * This function was actually a requirement
	 * to check which condition actually failed
	 *
	 * @since   1.0.0
	 *
	 * @return bool|int
	 */
	private function security_and_validity_check() {

		if ( 'no' === $this->form_data['send2tg'] ) {
			return __LINE__;
		}

		// If it's the block editor metabox submission.
		if ( RequestCheck::is( RequestCheck::GB_METABOX ) ) {
			return __LINE__;
		}

		$send_if_importing = (bool) apply_filters( 'wptelegram_pro_p2tg_send_if_importing', false, $this->post );

		// if importing.
		if ( RequestCheck::is( RequestCheck::WP_IMPORTING ) && ! $send_if_importing ) {
			return __LINE__;
		}

		$send_if_bulk_edit = (bool) apply_filters( 'wptelegram_pro_p2tg_send_if_bulk_edit', false, $this->post );

		// if bulk edit.
		if ( RequestCheck::is( RequestCheck::BULK_EDIT ) && ! $send_if_bulk_edit ) {
			return __LINE__;
		}

		$send_if_quick_edit = (bool) apply_filters( 'wptelegram_pro_p2tg_send_if_quick_edit', false, $this->post );

		// if quick edit.
		if ( RequestCheck::is( RequestCheck::QUICK_EDIT ) && ! $send_if_quick_edit ) {
			return __LINE__;
		}

		$send_if_password_protected = (bool) apply_filters( 'wptelegram_pro_p2tg_send_if_password_protected', false, $this->post );

		// if password protected post.
		if ( '' !== $this->post->post_password && ! $send_if_password_protected ) {
			return __LINE__;
		}

		// Is the post created via wp-admin.
		if ( RequestCheck::is( RequestCheck::FROM_WEB ) && Admin::show_post_edit_switch() ) {

			$nonce = MainUtils::nonce();

			// Check for nonce.
			if ( ! isset( $_POST[ $nonce ] ) ) {
				return __LINE__;
			}

			// Verify nonce.
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ $nonce ] ) ), $nonce ) ) {
				return __LINE__;
			}
		}

		// if it's an AUTOSAVE.
		if ( RequestCheck::is( RequestCheck::DOING_AUTOSAVE ) ) {
			return false; // __LINE__ not required
		}

		// if it's a post revision.
		if ( RequestCheck::is( RequestCheck::POST_REVISION ) ) {
			return false; // __LINE__ not required
		}

		// If not a valid status.
		if ( ! $this->is_valid_status() ) {
			return __LINE__;
		}

		// if the post is published/updated using WP CRON.
		$is_cron = RequestCheck::is( RequestCheck::DOING_CRON );

		// if the post is published/updated via WP-CLI.
		$is_cli = RequestCheck::is( RequestCheck::WP_CLI );

		// if the post is published/updated by some plugin.
		$plugin_posts = $this->module()->options()->get( 'plugin_posts', false );

		// No need to check for user permissions when WP-CLI or Cron.
		if ( ! $is_cli && ! $is_cron && ! $plugin_posts ) {

			$user_has_permission = false;
			// Allow custom code to control authentication.
			// Especially for front-end submissions.
			$user_has_permission = (bool) apply_filters( 'wptelegram_pro_p2tg_current_user_has_permission', $user_has_permission, $this->post );

			if ( ! $user_has_permission ) {

				if ( 'page' === $this->post->post_type && ! current_user_can( 'edit_page', $this->post->ID ) ) {
					// if user has not permissions to edit pages.
					return __LINE__;
				} elseif ( ! current_user_can( 'edit_post', $this->post->ID ) ) {
					// if user has not permissions to edit posts.
					return __LINE__;
				}
			}
		}

		// final control in your hands.
		// pass a false value to avoid posting.
		if ( ! apply_filters( 'wptelegram_pro_p2tg_filter_post', $this->post ) ) {
			return __LINE__;
		}

		return true;
	}

	/**
	 * Whether the default options have been overridden
	 * on the post edit page
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public function defaults_overridden() {

		return ! empty( $this->form_data['override_switch'] );
	}

	/**
	 * Clean up the meta table etc.
	 *
	 * @since 1.0.0
	 */
	public function may_be_clean_up() {
		$is_gb_metabox = RequestCheck::is( RequestCheck::GB_METABOX );

		$is_initial_rest_request = RequestCheck::is( RequestCheck::REST_PRE_INSERT, $this->post );

		if ( $this->is_status_of_type( 'live' ) && ! $is_gb_metabox && ! $is_initial_rest_request ) {
			$meta_fields = [
				'instances',
				'send2tg',
				'force_send',
				'delay_for_override',
			];
			foreach ( $meta_fields as $meta_field ) {
				delete_post_meta( $this->post->ID, Main::PREFIX . $meta_field );
			}
		}
	}

	/**
	 * Set the instances.
	 *
	 * @since 1.0.0
	 *
	 * @param array $inc_inst Array of instance IDs to include.
	 * @return void
	 */
	public function set_p2tg_instances( $inc_inst ) {

		$this->p2tg_instances = $this->get_p2tg_instances( $inc_inst );

		if ( ! $this->form_data['send2tg'] ) {
			$send2tg = get_post_meta( $this->post->ID, Main::PREFIX . 'send2tg', true );
			if ( $send2tg ) {
				$this->form_data['send2tg'] = $send2tg;
			}
		}

		if ( ! $this->form_data['force_send'] ) {
			$force_send = get_post_meta( $this->post->ID, Main::PREFIX . 'force_send', true );
			if ( $force_send ) {
				$this->form_data['force_send'] = $force_send;
			}
		}
		// clean the database.
		delete_post_meta( $this->post->ID, Main::PREFIX . 'instances' );
		delete_post_meta( $this->post->ID, Main::PREFIX . 'send2tg' );
		delete_post_meta( $this->post->ID, Main::PREFIX . 'force_send' );
	}

	/**
	 * Fetch the instances from the settings
	 * and user override options.
	 *
	 * @since 1.0.0
	 *
	 * @param array $include Array of instance IDs to include.
	 * @return  array[]
	 */
	public function get_p2tg_instances( $include = [] ) {

		// try to get the instances from meta.
		$saved_instances = get_post_meta( $this->post->ID, Main::PREFIX . 'instances', true );

		// if there is nothing.
		if ( empty( $saved_instances ) ) {
			$saved_instances = Utils::get_saved_instances( $this->post->post_type, $include );
		} else {
			$saved_instances = array_map( [ Utils::class, 'decode_instance_values' ], $saved_instances );
		}

		$p2tg_instances = $saved_instances;

		if ( ! empty( $p2tg_instances ) && $this->defaults_overridden() ) {
			// Override the default options.
			foreach ( $p2tg_instances as $instance_id => &$instance ) {

				// If the instance is not found in the override options.
				if ( empty( $this->form_data['instances'][ $instance_id ] ) ) {

					// remove the instance.
					unset( $p2tg_instances[ $instance_id ] );

					continue;
				}

				$instance_data = $this->form_data['instances'][ $instance_id ];

				// if the channels are set.
				if ( isset( $instance_data['channels'] ) ) {
					$instance['channels'] = $instance_data['channels'];
				}

				// if the template is set.
				if ( isset( $instance_data['message_template'] ) ) {
					$instance['message_template'] = $instance_data['message_template'];
				}

				// if files included.
				if ( ! empty( $instance_data['files'] ) ) {
					$instance['files'] = $instance_data['files'];
				} else {
					$instance['files'] = [];
				}

				// if delay overridden.
				if ( isset( $instance_data['delay'] ) ) {
					$instance['delay'] = floatval( $instance_data['delay'] );
				}

				// if disable_notification overridden.
				if ( isset( $instance_data['disable_notification'] ) ) {
					$instance['disable_notification'] = ! empty( $instance_data['disable_notification'] );
				}

				// if send_featured_image overridden.
				if ( isset( $instance_data['send_featured_image'] ) ) {
					$instance['send_featured_image'] = ! empty( $instance_data['send_featured_image'] );
				}
			}
		}

		return (array) apply_filters( 'wptelegram_pro_p2tg_instances', $p2tg_instances, $saved_instances, $include, $this->post );
	}

	/**
	 * Process
	 *
	 * @since   1.0.0
	 *
	 * @return mixed
	 */
	private function process() {

		do_action( 'wptelegram_pro_p2tg_before_process', $this->post, $this->p2tg_instances );

		// Pass by reference.
		$this->delay_handler->process_instances_for_delay( $this->p2tg_instances, $this->form_data );

		// For logs.
		$result = __LINE__;

		if ( ! empty( $this->p2tg_instances ) ) {
			$response_builder = new ResponseBuilder( $this->post, $this->p2tg_instances, $this->form_data );

			$this->responses = $response_builder->build_responses();

			$result = $this->send_responses();
		}

		do_action( 'wptelegram_pro_p2tg_after_process', $this->post, $this->p2tg_instances, $this->responses );

		return $result;
	}

	/**
	 * Send the responses.
	 *
	 * @return array[]
	 */
	private function send_responses() {

		// Remove query variable, if present.
		remove_query_arg( Main::PREFIX . 'error' );
		// Remove error transient.
		delete_transient( 'wptelegram_pro_p2tg_errors' );

		$this->bot_api = new API( $this->bot_token );

		$this->instance_options = new Options();

		$api_responses = [];

		do_action( 'wptelegram_pro_p2tg_before_send_responses', $this->responses, $api_responses, $this->p2tg_instances, $this->post, $this->bot_api );

		foreach ( $this->responses as $instance_id => $responses ) {

			$this->instance_options->set_data( $this->p2tg_instances[ $instance_id ] );

			$instance_bot = BotsUtils::get_bot_token_from_username( $this->instance_options->get( 'bot' ) );
			$instance_bot = $instance_bot ? $instance_bot : $this->bot_token;

			if ( $instance_bot ) {
				$this->bot_api->set_bot_token( $instance_bot );
			}

			$channels = $this->instance_options->get( 'channels', [] );

			$message_as_reply = (bool) apply_filters( 'wptelegram_pro_p2tg_send_message_as_reply', true, $this->instance_options, $this->post, $this->p2tg_instances, $this->responses );

			// loop through instance destination channels.
			foreach ( $channels as $channel ) {
				/**
				 * The api response.
				 *
				 * @var Response
				 */
				$res = false;

				// loop through the prepared responses.
				foreach ( $responses as $response ) {

					$params = reset( $response );
					$method = key( $response );

					// Remove note added to the chat id after "|".
					$channel = preg_replace( '/\s*\|.*?$/u', '', $channel );

					list( $params['chat_id'], $params['message_thread_id'] ) = array_pad( explode( ':', $channel ), 2, '' );

					if ( ! $params['message_thread_id'] ) {
						unset( $params['message_thread_id'] );
					}

					if ( $message_as_reply && $this->bot_api->is_success( $res ) ) {

						$result = $res->get_result();
						// send next message in reply to the previous one.
						$params['reply_to_message_id'] = ! empty( $result['message_id'] ) ? $result['message_id'] : null;
					}

					/**
					 * Filters the params for the Telegram API method
					 * It can be used to modify the behavior in a number of ways
					 * You can use it to change the text based on the channel/chat
					 *
					 * @since   1.0.0
					 */
					$params = apply_filters( 'wptelegram_pro_p2tg_api_method_params', $params, $method, $this->instance_options, $this->post, $this->p2tg_instances, $this->responses );

					$res = call_user_func( [ $this->bot_api, $method ], $params );
					$api_responses[ $instance_id ][ $channel ][] = $res;

					do_action( 'wptelegram_pro_p2tg_api_response', $res, $this->instance_options, $this->post, $this->p2tg_instances, $this->responses );

					if ( is_wp_error( $res ) ) {
						$this->handle_wp_error( $res, $instance_id, $channel );
					}
				}
			}
			// Restore the module bot token.
			$this->bot_api->set_bot_token( $this->bot_token );
		}

		// update post meta if the message was successful.
		$this->update_post_meta( $api_responses );

		do_action( 'wptelegram_pro_p2tg_after_send_responses', $this->responses, $api_responses, $this->post, $this->p2tg_instances, $this->bot_api );

		return $api_responses;
	}

	/**
	 * Update post meta if the message was successful.
	 *
	 * @since   1.0.0
	 *
	 * @param array $api_responses API responses from Telegram.
	 * @return void
	 */
	private function update_post_meta( $api_responses ) {

		// Whether the post has already been sent to Telegram.
		$sent2tg = get_post_meta( $this->post->ID, Main::PREFIX . 'sent2tg', true );

		$sent2tg = empty( $sent2tg ) ? [] : $sent2tg;

		$current_time = current_time( 'mysql' );

		foreach ( $api_responses as $instance_id => $instance_responses ) {

			foreach ( $instance_responses as $responses ) {

				foreach ( $responses as $res ) {
					// if any of the responses is successful.
					if ( $this->bot_api->is_success( $res ) ) {

						$sent2tg[ $instance_id ] = $current_time;
					}
				}
			}
		}
		if ( ! add_post_meta( $this->post->ID, Main::PREFIX . 'sent2tg', $sent2tg, true ) ) {

			update_post_meta( $this->post->ID, Main::PREFIX . 'sent2tg', $sent2tg );
		}
	}

	/**
	 * Handle WP_Error of wp_remote_post()
	 *
	 * @since   1.0.0
	 *
	 * @param WP_Error $wp_error The error object.
	 * @param int      $instance_id ID of the current instance.
	 * @param string   $channel The channel chat ID.
	 *
	 * @return void
	 */
	private function handle_wp_error( $wp_error, $instance_id, $channel ) {

		$transient = 'wptelegram_pro_p2tg_errors';

		$p2tg_errors = array_filter( (array) get_transient( $transient ) );

		$p2tg_errors[ $instance_id ][ $channel ][ $wp_error->get_error_code() ] = $wp_error->get_error_message();

		set_transient( $transient, $p2tg_errors, 60 );

		add_filter( 'redirect_post_location', [ $this, 'add_admin_notice_query_var' ], 99 );
	}

	/**
	 * Add query variable upon error.
	 *
	 * @since   1.0.0
	 *
	 * @param string $location URL.
	 *
	 * @return string
	 */
	public function add_admin_notice_query_var( $location ) {

		remove_filter( 'redirect_post_location', [ $this, __FUNCTION__ ], 99 );

		return add_query_arg( [ Main::PREFIX . 'error' => true ], $location );
	}
}
