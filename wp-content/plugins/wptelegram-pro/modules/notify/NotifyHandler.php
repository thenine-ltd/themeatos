<?php
/**
 * Notifications Handling functionality of the plugin.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\notify
 */

namespace WPTelegram\Pro\modules\notify;

use WPTelegram\Pro\modules\bots\Utils as BotsUtils;
use WPTelegram\Pro\includes\Utils;
use WPTelegram\Pro\shared\Shared;
use WPTelegram\Pro\modules\BaseClass;
use WPTelegram\BotAPI\API;
use WP_User;

/**
 * The Notifications Handling functionality of the plugin.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\notify
 * @author     WP Socio
 */
class NotifyHandler extends BaseClass {

	const CHATS2EMAILS_OPTION = 'wptelegram_pro_notify_chats2emails';
	const RESPONSES_OPTION    = 'wptelegram_pro_notify_responses';

	/**
	 * Bot Token to be used for Telegram API calls
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    string Telegram Bot Token.
	 */
	private $bot_token;

	/**
	 * Arguments from wp_mail.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array wp_mail args.
	 */
	private $wp_mail_args;

	/**
	 * The wp_mail email addresses - to, cc, bcc
	 * extracted from $wp_mail_args.
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array   The email recipients.
	 */
	private $email_recipients;

	/**
	 * Array of email headers containing chat_ids and emails
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array The chats to emails mapping.
	 */
	private $chats2emails;

	/**
	 * Prepared Responses to be sent to Telegram
	 *
	 * @since  1.0.0
	 * @access private
	 * @var    array   The prepared responses.
	 */
	private $responses;

	/**
	 * Set up the basics.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args A compacted array of wp_mail() arguments,
	 * including the "to" email, subject, message, headers, and attachments values.
	 */
	private function init( $args ) {

		$this->wp_mail_args = $args;

		$this->bot_token = BotsUtils::get_bot_token_from_username( $this->module()->options()->get( 'bot' ) );

		do_action( 'wptelegram_pro_notify_init', $args );
	}

	/**
	 * Filters the wp_mail() arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args A compacted array of wp_mail() arguments,
	 * including the "to" email, subject, message, headers, and attachments values.
	 */
	public function handle_wp_mail( $args ) {

		$this->init( $args );

		if ( empty( $this->bot_token ) ) {
			return $this->wp_mail_args;
		}

		$this->set_email_recipients();

		if ( empty( $this->email_recipients ) ) {
			return $this->wp_mail_args;
		}

		// final switch for plugin devs
		// pass a false value to abort.
		$send_notification = apply_filters(
			'wptelegram_pro_notify_send_notification',
			true,
			$this->wp_mail_args,
			$this->email_recipients,
			$this->module()->options()
		);
		if ( ! $send_notification ) {
			return $this->wp_mail_args;
		}

		$this->chats2emails = [];

		// used to avoid duplicate notifications.
		$chat_id_bucket = [];

		$catch_emails = $this->get_catch_emails();
		$user_notify  = $this->module()->options()->get( 'user_notifications' );

		$added_catch_any = false;

		// process only "to" addresses by default.
		add_filter( 'wptelegram_pro_notify_process_to', '__return_true', 10 );

		foreach ( $this->email_recipients as $address_header => $addresses ) {
			$process_header = apply_filters(
				"wptelegram_pro_notify_process_{$address_header}",
				false,
				$this->wp_mail_args,
				$this->email_recipients,
				$this->module()->options()
			);

			if ( $process_header ) {

				$this->chats2emails[ $address_header ] = [];

				if ( ! is_array( $addresses ) ) {
					$addresses = explode( ',', $addresses );
				}
				$addresses = array_map( 'trim', (array) $addresses );

				foreach ( $addresses as $recipient ) {

					// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>".
					if ( preg_match( '/(.*)<(.+)>/u', $recipient, $matches ) && count( $matches ) === 3 ) {
						$email = $matches[2];
					} else {
						$email = $recipient;
					}

					// for comparisons.
					$email = strtolower( $email );

					// add the chat ids that receive all the notifications.
					if ( ! $added_catch_any && isset( $catch_emails['any'] ) ) {

						foreach ( $catch_emails['any'] as $chat_id ) {

							// avoid duplicates.
							if ( ! in_array( $chat_id, $chat_id_bucket, true ) ) {

								$this->chats2emails[ $address_header ][ $chat_id ] = $email;

								// add to the bucket.
								$chat_id_bucket[] = $chat_id;
							}
						}

						$added_catch_any = true;
					}

					// if is to be caught.
					if ( ! empty( $catch_emails[ $email ] ) ) {

						foreach ( $catch_emails[ $email ] as $chat_id ) {

							// avoid duplicates.
							if ( ! in_array( $chat_id, $chat_id_bucket, true ) ) {

								$this->chats2emails[ $address_header ][ $chat_id ] = $email;

								// add to the bucket.
								$chat_id_bucket[] = $chat_id;
							}
						}
					}

					// user notifications.
					if ( $user_notify ) {
						$chat_id = $this->get_user_chat_id( $email );
						if ( $chat_id && ! in_array( $chat_id, $chat_id_bucket, true ) ) {

							$this->chats2emails[ $address_header ][ $chat_id ] = $email;
							// add to the bucket.
							$chat_id_bucket[] = $chat_id;
						}
					}
				}
			}
		}

		$this->chats2emails = (array) apply_filters(
			'wptelegram_pro_notify_chats2emails',
			$this->chats2emails,
			$this->wp_mail_args,
			$this->email_recipients,
			$this->module()->options()
		);

		$is_empty = true;
		foreach ( $this->chats2emails as $address_header => $chat_ids ) {
			$is_empty = empty( $chat_ids );
			if ( ! $is_empty ) {
				break;
			}
		}

		if ( ! $is_empty ) {

			$this->prepare_default_responses();

			if ( ! empty( $this->responses ) ) {

				if ( $this->needs_scheduling() ) {

					$this->schedule();
				} else {

					$this->send_responses();
				}
			}
		}

		do_action( 'wptelegram_pro_notify_finish', $args, $this->chats2emails, $this->module()->options() );

		return $this->wp_mail_args;
	}

	/**
	 * Schedule the messages
	 *
	 * @since 1.0.0
	 */
	private function schedule() {

		$chats2emails = get_option( self::CHATS2EMAILS_OPTION, [] );
		$responses    = get_option( self::RESPONSES_OPTION, [] );

		$chats2emails[] = $this->chats2emails;

		end( $chats2emails );

		$key = key( $chats2emails );

		$responses[ $key ] = $this->responses;

		update_option( self::CHATS2EMAILS_OPTION, $chats2emails );
		update_option( self::RESPONSES_OPTION, $responses );

		if ( ! wp_next_scheduled( Main::CRON_HOOK ) ) {
			wp_schedule_event( time(), Shared::INTERVAL_TWO_MINUTELY, Main::CRON_HOOK, [ $this->wp_mail_args ] );
		}
	}

	/**
	 * Execute the scheduled event
	 *
	 * @since 1.0.0
	 *
	 * @param array $args The args from cron hook.
	 */
	public function run_notify_cron( $args ) {

		$this->init( $args );

		$unschedule = false;

		if ( empty( $this->bot_token ) ) {

			$unschedule = true;

		} else {

			$all_chats2emails = get_option( self::CHATS2EMAILS_OPTION, [] );
			$all_responses    = get_option( self::RESPONSES_OPTION, [] );

			if ( empty( $all_chats2emails ) || empty( $all_responses ) ) {

				$unschedule = true;
			} else {

				$this->chats2emails = [];
				$this->responses    = [];

				$chats2emails = reset( $all_chats2emails );

				$key = key( $all_chats2emails );

				$this->responses = $all_responses[ $key ];

				$count = 0;

				foreach ( $chats2emails as $address_header => $chat_emails ) {

					foreach ( $chat_emails as $chat_id => $email ) {

						if ( $count >= 20 ) {
							break 2;
						}

						$this->chats2emails[ $address_header ][ $chat_id ] = $email;

						unset( $chats2emails[ $address_header ][ $chat_id ] );

						++$count;
					}

					if ( empty( $chats2emails[ $address_header ] ) ) {
						unset( $chats2emails[ $address_header ] );
					}
				}

				if ( empty( $chats2emails ) ) {

					unset( $all_chats2emails[ $key ] );

					unset( $all_responses[ $key ] );

				} else {

					$all_chats2emails[ $key ] = $chats2emails;
				}
			}
		}

		if ( ! empty( $this->chats2emails ) ) {

			$this->send_responses();
		}

		if ( $unschedule || empty( $all_chats2emails ) || empty( $all_responses ) ) {

			$timestamp = wp_next_scheduled( Main::CRON_HOOK );
			wp_unschedule_event( $timestamp, Main::CRON_HOOK );

			delete_option( self::CHATS2EMAILS_OPTION );
			delete_option( self::RESPONSES_OPTION );

		} else {

			update_option( self::CHATS2EMAILS_OPTION, $all_chats2emails );
			update_option( self::RESPONSES_OPTION, $all_responses );
		}

		do_action( 'wptelegram_pro_notify_cron_finish', $args, $this->chats2emails, $this->module()->options() );
	}

	/**
	 * Whether scheduling is needed
	 * to avoid getting limited by Telegram Bot API
	 * Keeping it safe to set the maximum limit to 20
	 *
	 * @link https://core.telegram.org/bots/faq#my-bot-is-hitting-limits-how-do-i-avoid-this
	 *
	 * @since 1.0.0
	 */
	private function needs_scheduling() {

		$count = 0;

		foreach ( $this->chats2emails as $address_header ) {
			$count += count( $address_header );
		}

		return ( $count > 20 );
	}

	/**
	 * Extract the email addresses from wp_mail args.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function set_email_recipients() {

		$cc  = [];
		$bcc = [];

		$to = $this->wp_mail_args['to'];

		if ( ! is_array( $to ) ) {
			$to = explode( ',', $to );
		}

		if ( ! empty( $this->wp_mail_args['headers'] ) ) {

			$headers = $this->wp_mail_args['headers'];

			if ( ! is_array( $headers ) ) {

				$headers = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
			}

			// If it's actually got contents.
			if ( ! empty( $headers ) ) {
				// Iterate through the raw headers.
				foreach ( (array) $headers as $header ) {

					// strictly follow the rules.
					if ( strpos( $header, ':' ) === false ) {
						continue;
					}
					// Explode them out.
					list( $name, $content ) = explode( ':', trim( $header ), 2 );

					// Cleanup crew.
					$name    = trim( $name );
					$content = trim( $content );

					switch ( strtolower( $name ) ) {
						case 'cc':
							$cc = array_merge( (array) $cc, explode( ',', $content ) );
							break;
						case 'bcc':
							$bcc = array_merge( (array) $bcc, explode( ',', $content ) );
							break;
					}
				}
			}
		}

		$email_recipients = compact( 'to', 'cc', 'bcc' );
		// remove the empty values from each array.
		foreach ( $email_recipients as $address_header => $addresses ) {
			$email_recipients[ $address_header ] = array_filter( $addresses );
		}

		$this->email_recipients = (array) apply_filters(
			'wptelegram_pro_notify_email_recipients',
			$email_recipients,
			$this->wp_mail_args,
			$this->module()->options()
		);
	}

	/**
	 * Prepare the text to be sent to Telegram.
	 *
	 * @since 1.0.0
	 *
	 * @access private
	 */
	private function prepare_default_responses() {

		$this->responses = [];

		$template = $this->get_message_template();

		$text = $this->get_response_text( $template );

		if ( ! empty( $text ) ) {

			$options = $this->get_prepare_content_options( Utils::get_max_text_length( 'text' ) );

			$this->responses = [
				[
					'sendMessage' => [
						'text'                     => Utils::prepare_content( $text, $options ),
						'parse_mode'               => $options['format_to'],
						'disable_web_page_preview' => true,
					],
				],
			];

			$send_attachments = $this->module()->options()->get( 'send_attachments' );

			if ( $send_attachments && ! empty( $this->wp_mail_args['attachments'] ) ) {
				$file_responses = $this->get_file_responses( $this->wp_mail_args['attachments'] );

				// Disable sending files by URL to ensure that the attachments are uploaded.
				add_filter( 'wptelegram_pro_send_files_by_url', '__return_false' );

				$this->responses = array_merge( $this->responses, $file_responses );
			}
		}

		$this->responses = apply_filters(
			'wptelegram_pro_notify_default_responses',
			$this->responses,
			$this->wp_mail_args,
			$this->chats2emails,
			$this->module()->options()
		);
	}

	/**
	 * Create responses based on the files included
	 *
	 * @since 1.0.0
	 *
	 * @param array $files The attachment files.
	 *
	 * @return  array
	 */
	private function get_file_responses( $files ) {

		$file_responses = [];

		foreach ( $files as $path ) {

			$caption = apply_filters(
				'wptelegram_pro_notify_file_caption',
				basename( $path ),
				$path,
				$this->wp_mail_args,
				$this->module()->options()
			);

			$type = Utils::get_file_type( '', $path );

			$file_responses[] = [
				'send' . ucfirst( $type ) => [
					$type     => $path,
					'caption' => $caption,
				],
			];
		}

		return apply_filters(
			'wptelegram_pro_notify_file_responses',
			$file_responses,
			$files,
			$this->module()->options(),
			$this->wp_mail_args
		);
	}

	/**
	 * Get the message template
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_message_template() {

		$template = $this->module()->options()->get( 'message_template', '' );

		$template = stripslashes( $template );

		return apply_filters(
			'wptelegram_pro_notify_message_template',
			$template,
			$this->wp_mail_args,
			$this->chats2emails,
			$this->module()->options()
		);
	}

	/**
	 * Get the text based response.
	 *
	 * @since 1.0.0
	 *
	 * @param string $template The message template.
	 *
	 * @return string
	 */
	private function get_response_text( $template ) {

		$macro_keys = [ 'email_subject', 'email_message' ];
		// Use this filter to add your own macros.
		$macro_keys = (array) apply_filters(
			'wptelegram_pro_notify_macro_keys',
			$macro_keys,
			$this->wp_mail_args,
			$this->chats2emails,
			$this->module()->options()
		);

		$macro_values = [];

		foreach ( $macro_keys as $macro_key ) {

			$macro = '{' . $macro_key . '}';

			// get the value only if it's in the template.
			if ( false !== strpos( $template, $macro ) ) {

				$macro_values[ $macro ] = $this->get_macro_value( $macro_key );
			}
		}

		/**
		 * Use this filter to replace your own macros
		 * with the corresponding values
		 */
		$macro_values = (array) apply_filters(
			'wptelegram_pro_notify_macro_values',
			$macro_values,
			$this->wp_mail_args,
			$this->chats2emails,
			$this->module()->options()
		);

		$text = str_replace( array_keys( $macro_values ), array_values( $macro_values ), $template );

		return apply_filters(
			'wptelegram_pro_notify_response_text',
			$text,
			$template,
			$this->wp_mail_args,
			$this->chats2emails,
			$this->module()->options()
		);
	}

	/**
	 * Get the text for the given macro.
	 *
	 * @param string $macro The macro to get the text for.
	 *
	 * @return string The text for the given macro.
	 */
	private function get_macro_value( $macro ) {

		$value = '';

		$options = $this->get_prepare_content_options();

		switch ( $macro ) {
			case 'email_message':
				$value = $this->prepare_email_message( $this->wp_mail_args['message'], $this->wp_mail_args['headers'] );
				$value = Utils::prepare_content( $value, $options );
				break;

			case 'email_subject':
				$value = wp_strip_all_tags( $this->wp_mail_args['subject'], true );
				$value = Utils::prepare_content( $value, $options );
				break;
		}

		$value = apply_filters( 'wptelegram_pro_notify_macro_value', $value, $macro, $this->wp_mail_args, $this->module()->options() );

		return apply_filters( "wptelegram_pro_notify_macro_{$macro}_value", $value, $this->wp_mail_args, $this->module()->options() );
	}

	/**
	 * Get the options for prepare_content
	 *
	 * @since 2.0.0
	 *
	 * @param int $limit The limit.
	 *
	 * @return array
	 */
	private function get_prepare_content_options( $limit = 0 ) {
		$parse_mode = Utils::valid_parse_mode( $this->module()->options()->get( 'parse_mode', 'HTML' ) );

		$options = [
			'format_to'       => $parse_mode,
			'id'              => 'notify',
			'limit'           => $limit,
			'limit_by'        => 'chars',
			'text_hyperlinks' => 'retain',
			'images_in_links' => [
				'title_or_alt'    => 'retain',
				'lone_image_link' => 'retain',
			],
		];

		return apply_filters( 'wptelegram_pro_notify_prepare_content_options', $options, $limit, $this->wp_mail_args, $this->chats2emails, $this->module()->options() );
	}

	/**
	 * Prepare the email message.
	 *
	 * The function:
	 * 1. Converts the quoted-printable message to an 8 bit string
	 *    if "Content-Transfer-Encoding" is "quoted-printable"
	 *
	 * @since 2.0.0
	 *
	 * @param string       $message The email message.
	 * @param string|array $headers The email headers.
	 *
	 * @return string
	 */
	private function prepare_email_message( $message, $headers ) {

		$headers_str = is_array( $headers ) ? implode( "\n", $headers ) : $headers;

		if ( preg_match( '/Content-Transfer-Encoding:\s*?quoted-printable/i', $headers_str ) ) {
			$message = quoted_printable_decode( $message );
		}

		return apply_filters( 'wptelegram_pro_notify_prepare_email_message', $message, $headers, $this->wp_mail_args, $this->module()->options() );
	}

	/**
	 * Send the messages
	 *
	 * @since 1.0.0
	 */
	public function send_responses() {

		$tg_api = new API( $this->bot_token );

		do_action(
			'wptelegram_pro_notify_before_send_responses',
			$this->wp_mail_args,
			$this->chats2emails,
			$this->module()->options(),
			$this->responses,
			$this
		);

		$methods = Utils::get_methods_from_responses( $this->responses );

		// loop through instance destination channels.
		foreach ( $this->chats2emails as $address_header => $chat_emails ) {

			foreach ( $chat_emails as $chat_id => $email ) {

				// loop through the prepared responses.
				foreach ( $this->responses as $response ) {

					$params = reset( $response );
					$method = key( $response );

					// Remove note added to the chat id after "|".
					$chat_id = preg_replace( '/\s*\|.*?$/u', '', $chat_id );

					list( $params['chat_id'], $params['message_thread_id'] ) = array_pad( explode( ':', $chat_id ), 2, '' );

					if ( ! $params['message_thread_id'] ) {
						unset( $params['message_thread_id'] );
					}

					$params = apply_filters(
						'wptelegram_pro_notify_api_method_params',
						$params,
						$method,
						$this->wp_mail_args,
						$this->chats2emails,
						$this->module()->options()
					);

					$api_res = call_user_func( [ $tg_api, $method ], $params );

					do_action(
						'wptelegram_pro_notify_api_response',
						$api_res,
						$tg_api,
						$response,
						$email,
						$this->wp_mail_args,
						$this->chats2emails,
						$this->module()->options()
					);
				}
			}
		}

		do_action(
			'wptelegram_pro_notify_after_send_responses',
			$this->wp_mail_args,
			$this->chats2emails,
			$this->module()->options(),
			$this->responses,
			$this
		);
	}

	/**
	 * Get Telegram Chat ID from email address.
	 *
	 * @since 1.0.0
	 *
	 * @param string $email Email ID of the user.
	 * @return string
	 */
	private function get_user_chat_id( $email ) {
		$chat_id = 0;

		$user = get_user_by( 'email', $email );

		if ( $user instanceof WP_User ) {

			$chat_id = $user->{WPTELEGRAM_USER_ID_META_KEY};
		}
		return (string) apply_filters( 'wptelegram_pro_notify_user_chat_id', $chat_id, $email, $this->wp_mail_args );
	}

	/**
	 * Get the email to chat id mapping.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private function get_catch_emails() {

		$catch_emails_group = $this->module()->options()->get( 'catch_emails', [] );

		$catch_emails = [];

		foreach ( $catch_emails_group as $group ) {

			if ( empty( $group['email'] ) || empty( $group['chat_ids'] ) ) {
				continue;
			}

			$chat_ids = array_map( 'trim', $group['chat_ids'] );

			$emails = array_map( 'trim', explode( ',', strtolower( $group['email'] ) ) );

			foreach ( $emails as $email ) {

				// if already found chat_id for the email.
				if ( ! empty( $catch_emails[ $email ] ) ) {
					$chat_ids = array_merge( $catch_emails[ $email ], $chat_ids );
				}

				$catch_emails[ $email ] = $chat_ids;
			}
		}

		return apply_filters(
			'wptelegram_pro_notify_catch_emails',
			$catch_emails,
			$this->wp_mail_args,
			$this->email_recipients,
			$this->module()->options()
		);
	}

	/**
	 * BBPress integration.
	 *
	 * @since 1.0.0
	 */
	public function integrate_bbpress() {

		// open the gate for bbpress :) .
		add_action( 'bbp_pre_notify_subscribers', [ __CLASS__, 'start_bbpress_integration' ] );
		add_action( 'bbp_pre_notify_forum_subscribers', [ __CLASS__, 'start_bbpress_integration' ] );

		// close the gate for bbpress :) .
		add_action( 'bbp_post_notify_subscribers', [ __CLASS__, 'stop_bbpress_integration' ] );
		add_action( 'bbp_post_notify_forum_subscribers', [ __CLASS__, 'stop_bbpress_integration' ] );
	}

	/**
	 * Start bbpress integration.
	 *
	 * @since 1.0.0
	 */
	public static function start_bbpress_integration() {
		// process "bcc" addresses.
		add_filter( 'wptelegram_pro_notify_process_bcc', '__return_true', 13, 1 );
	}

	/**
	 * Stop bbpress integration.
	 *
	 * @since 1.0.0
	 */
	public static function stop_bbpress_integration() {
		remove_filter( 'wptelegram_pro_notify_process_bcc', '__return_false', 13, 1 );
	}
}
