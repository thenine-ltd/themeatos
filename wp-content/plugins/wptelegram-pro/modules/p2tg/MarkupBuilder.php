<?php
/**
 * Builds the reply markup to be sent to Telegram.
 *
 * @link        https://wptelegram.pro
 * @since       1.0.0
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 */

namespace WPTelegram\Pro\modules\p2tg;

use WPTelegram\Pro\includes\Options;

/**
 * Class responsible for building message reply markup.
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 * @author      WP Socio
 */
class MarkupBuilder {

	/**
	 * The prefix for meta data
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     string  The prefix for meta data
	 */
	private static $prefix = '_wptgpro_p2tg_';

	/**
	 * Settings/Options
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var     array       $options    Instance Options
	 */
	private $instance_options;

	/**
	 * The post to be handled
	 *
	 * @var WP_Post $post   Post object.
	 */
	protected $post;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since 1.0.0
	 * @param WP_Post $post             The post being processed.
	 * @param Options $instance_options The options object.
	 */
	public function __construct( $post, $instance_options ) {

		$this->post             = $post;
		$this->instance_options = $instance_options;
	}

	/**
	 * May be add reply_markup to the message.
	 *
	 * @param array $method_params Methods and Params passed by reference.
	 */
	public function add_reply_markup( &$method_params ) {

		$post_id = $this->post->ID;
		$inst_id = $this->instance_options->get( 'id' );
		$options = $this->instance_options;

		$inline_keyboard = self::get_inline_keyboard( $post_id, $inst_id, $options );

		if ( ! empty( $inline_keyboard ) ) {

			$reply_markup = wp_json_encode( compact( 'inline_keyboard' ) );

			if ( isset( $method_params['sendMessage'] ) ) {

				$method_params['sendMessage']['reply_markup'] = $reply_markup;
			} else {
				// add to the last Method.
				end( $method_params );
				$method = key( $method_params );

				$method_params[ $method ]['reply_markup'] = $reply_markup;
			}
		}
	}
	/**
	 * Get the inline keyboard.
	 *
	 * @since 1.0.0
	 *
	 * @param int     $post_id The post ID.
	 * @param int     $inst_id The instance ID.
	 * @param Options $options The instance options.
	 * @return array[]|bool
	 */
	public static function get_inline_keyboard( $post_id, $inst_id, $options ) {

		$keyboard_rows = array_filter( $options->get( 'inline_keyboard', [] ) );

		if ( empty( $keyboard_rows ) ) {
			return false;
		}

		// holds the keyboard rows and buttons.
		$inline_keyboard = [];

		$buttons = WPTG_Pro()->options()->get_path( 'p2tg.buttons', [] );
		// create an associative array with button id as key.
		$buttons = array_column( $buttons, null, 'id' );

		foreach ( $keyboard_rows as $keyboard_row ) {

			$row = [];

			foreach ( $keyboard_row as $button_id ) {

				if ( ! empty( $buttons[ $button_id ]['label'] ) ) {
					$button = $buttons[ $button_id ];

					$_button = [
						'text' => $button['label'],
					];

					if ( ! empty( $button['url'] ) ) {
						$_button['url'] = self::get_parsed_button_url( $button['url'], $post_id );
						// Allow dynamic label for URL buttons.
						$_button['text'] = self::get_parsed_button_label( $_button['text'], $post_id );

					} elseif ( ! empty( $button['value'] ) ) {
						/**
						 * Our callback data includes
						 * > p2tg (action)
						 * > post ID
						 * > Instance ID
						 * > button value
						 */
						$_button['callback_data'] = sprintf( 'p2tg@%s|%s|%s', $post_id, $inst_id, $button['value'] );

						// get button click count from database.
						$click_count = self::get_post_button_click_count( $post_id, $inst_id, $button['value'] );

						// if value is non-zero, add to the text.
						if ( $click_count ) {
							$_button['text'] = $click_count . ' ' . $_button['text'];
						}
					}
					$row[] = $_button;
				}
			}

			if ( ! empty( $row ) ) {
				$inline_keyboard[] = $row;
			}
		}
		return apply_filters( 'wptelegram_pro_p2tg_inline_keyboard', $inline_keyboard, $post_id, $inst_id, $options );
	}

	/**
	 * Parse the tags/macros in a button URL.
	 *
	 * @since 1.0.0
	 *
	 * @param string $url_template The dynamic url template.
	 * @param int    $post_id      The post ID.
	 *
	 * @return string
	 */
	public static function get_parsed_button_url( $url_template, $post_id ) {
		$parser = new TemplateParser( $post_id );

		$url = $parser->parse( $url_template );

		return apply_filters( 'wptelegram_pro_p2tg_parsed_button_url', $url, $url_template, $post_id );
	}

	/**
	 * Parse the tags/macros in a button label.
	 *
	 * @since 1.5.10
	 *
	 * @param string $label_template The dynamic label template.
	 * @param int    $post_id        The post ID.
	 *
	 * @return string
	 */
	public static function get_parsed_button_label( $label_template, $post_id ) {
		$parser = new TemplateParser( $post_id );

		$label = $parser->parse( $label_template );

		return apply_filters( 'wptelegram_pro_p2tg_parsed_button_label', $label, $label_template, $post_id );
	}

	/**
	 * Get the click count of a keyboard button for the post.
	 *
	 * @since 1.0.0
	 * @param int   $post_id The post ID.
	 * @param int   $inst_id The instance ID.
	 * @param array $button  The button details.
	 *
	 * @return int
	 */
	public static function get_post_button_click_count( $post_id, $inst_id, $button ) {

		$value = 0;

		$button_clicks = get_post_meta( $post_id, self::$prefix . 'button_clicks', true );

		if ( ! empty( $button_clicks[ $button ] ) ) {
			$value = count( $button_clicks[ $button ] );
		}

		return (int) apply_filters( 'wptelegram_pro_p2tg_post_button_click_count', $value, $button, $post_id, $inst_id );
	}
}
