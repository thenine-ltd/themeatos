<?php
/**
 * WP Telegram Pro Utilities
 *
 * @link       https://wptelegram.pro
 * @since     1.0.0
 *
 * @package WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\includes;

use WPTelegram\Pro\includes\restApi\RESTController;
use WPTelegram\Pro\modules\p2tg\Main as P2TGMain;
use WPTelegram\FormatText\HtmlConverter;
use WPTelegram\FormatText\Converter\Utils as FormatTextUtils;
use WPTelegram\FormatText\Exceptions\ConverterException;
use WP_REST_Request;
use WP_Error;

/**
 * WP Telegram Pro Utilities
 *
 * @link       https://wptelegram.pro
 * @since     1.0.0
 *
 * @package WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */
class Utils {

	/**
	 * HTML tags allowed in Telegram messages.
	 *
	 * @var string Tags.
	 * @since 1.6.1
	 */
	const ALLOWED_HTML = [
		// Link.
		'a'          => [
			'href' => true,
		],
		// bold.
		'b'          => [],
		'strong'     => [],
		// italic.
		'em'         => [],
		'i'          => [],
		// code.
		'pre'        => [],
		'code'       => [
			'class' => true,
		],
		// underline.
		'u'          => [],
		'ins'        => [],
		// strikethrough.
		'del'        => [],
		's'          => [],
		'strike'     => [],
		// spoiler.
		'tg-spoiler' => [],
	];

	/**
	 * Pattern to match the smart excerpt tag.
	 *
	 * @var string Pattern.
	 *
	 * @since 2.0.0
	 */
	const EXCERPT_PATTERN = '/<excerpt>(.*?)<\/excerpt>/ius';

	/**
	 * Sanitize the input.
	 *
	 * @param  mixed $input  The input.
	 * @param  bool  $typefy Whether to convert strings to the appropriate data type.
	 * @since  1.0.0
	 *
	 * @return mixed
	 */
	public static function sanitize( $input, $typefy = false ) {

		if ( is_array( $input ) ) {

			foreach ( $input as $key => $value ) {

				$input[ sanitize_text_field( $key ) ] = self::sanitize( $value, $typefy );
			}
			return $input;
		}

		// These are safe types.
		if ( is_bool( $input ) || is_int( $input ) || is_float( $input ) ) {
			return $input;
		}

		// Now we will treat it as string.
		$input = sanitize_text_field( $input );

		// avoid numeric or boolean values as strings.
		if ( $typefy ) {
			return self::typefy( $input );
		}

		return $input;
	}

	/**
	 * Convert the input into the proper data type
	 *
	 * @param  mixed $input The input.
	 * @since  1.0.0
	 *
	 * @return mixed
	 */
	public static function typefy( $input ) {

		if ( is_numeric( $input ) ) {

			return floatval( $input );

		} elseif ( is_string( $input ) && preg_match( '/^(?:true|false)$/i', $input ) ) {

			return 'true' === strtolower( $input );
		}

		return $input;
	}

	/**
	 * Filter WP REST API errors.
	 *
	 * @param mixed           $response Result to send to the client. Usually a WP_REST_Response or WP_Error.
	 * @param array           $handler  Route handler used for the request.
	 * @param WP_REST_Request $request  Request used to generate the response.
	 *
	 * @since    1.4.0
	 */
	public static function fitler_rest_errors( $response, $handler, $request ) {

		$route = ltrim( $request->get_route(), '/' );

		$matches_route    = 0 === strpos( $route, RESTController::REST_NAMESPACE ) || 0 === strpos( $route, 'wp/v2/' . P2TGMain::CPT_NAME );
		$is_invalid_param = is_wp_error( $response ) && 'rest_invalid_param' === $response->get_error_code();

		if ( ! $is_invalid_param || ! $matches_route ) {
			return $response;
		}

		$data = $response->get_error_data();

		$invalid_params = [];
		if ( ! empty( $data['params'] ) ) {
			foreach ( $data['params'] as $error ) {
				preg_match( '/\A\S+/', $error, $match );
				$invalid_params[ $match[0] ] = $error;
			}
		}

		$data['params'] = $invalid_params;

		return new WP_Error(
			$response->get_error_code(),
			$response->get_error_message(),
			$data
		);
	}

	/**
	 * Sanitizes hashtag(s)
	 *
	 * Specifically, spaces are removed
	 *
	 * @since 1.0.0
	 *
	 * @param string|array $hashtag The string or array of strings to be sanitized.
	 *
	 * @return string|array The sanitized string or array of strings
	 */
	public static function sanitize_hashtag( $hashtag ) {

		$raw_hashtag = $hashtag;

		if ( is_array( $hashtag ) ) {
			foreach ( $hashtag as &$string ) {
				$string = self::strip_non_word_chars( $string );
			}
		} else {
			$hashtag = self::strip_non_word_chars( $hashtag );
		}

		return apply_filters( 'wptelegram_pro_utils_sanitize_hashtag', $hashtag, $raw_hashtag );
	}

	/**
	 * Get file type from extension
	 *
	 * @since 1.0.0
	 *
	 * @param string $id   The file ID.
	 * @param string $file The file path.
	 *
	 * @return string
	 */
	public static function get_file_type( $id, $file ) {

		$filetype = get_post_mime_type( $id );

		if ( empty( $filetype ) ) {
			$filetype = wp_check_filetype( $file );
			$filetype = $filetype['type'];
		}

		// default type.
		$type = 'document';

		if ( ! empty( $filetype ) ) {
			$filetype = explode( '/', $filetype );

			$type = reset( $filetype );

			switch ( $type ) {
				case 'video':
				case 'audio':
					break;
				case 'image':
					$type = next( $filetype ) === 'gif' ? 'animation' : 'photo';
					break;
				default:
					$type = 'document';
					break;
			}
		}

		return apply_filters( 'wptelegram_pro_utils_file_type', $type, $id, $file );
	}

	/**
	 * Strips non-word characters from the string
	 * or replaces them with underscore
	 *
	 * @since 1.0.0
	 *
	 * @param string $text The target string.
	 *
	 * @return string
	 */
	public static function strip_non_word_chars( $text ) {
		$raw_text = $text;
		// decode all HTML entities.
		$text = self::decode_html( $text );

		// remove trailing non-word characters.
		$text = preg_replace( '/(^\W+|\W+$)/u', '', $text );
		// replace one or more continuous non-word characters by _.
		$text = preg_replace( '/\W+/u', '_', $text );

		return apply_filters( 'wptelegram_pro_utils_strip_non_word_chars', $text, $raw_text );
	}

	/**
	 * Convert a key value array to value label options.
	 *
	 * @since    1.0.0
	 *
	 * @param array $data The values to be converted.
	 */
	public static function array_to_select_options( $data ) {

		$options = [];

		foreach ( $data as $value => $label ) {

			$options[] = compact( 'value', 'label' );
		}
		return $options;
	}

	/**
	 * Handles sanitization for message template
	 *
	 * @since 1.0.0
	 *
	 * @param mixed   $value       The unsanitized value from the form.
	 * @param boolean $addslashes  Whether to addslashes for database.
	 * @param boolean $json_encode Whether to json_encode.
	 *
	 * @return string Sanitized value.
	 */
	public static function sanitize_message_template( $value, $addslashes = false, $json_encode = false ) {
		if ( is_object( $value ) || is_array( $value ) ) {
			return '';
		}

		$value = (string) $value;

		$guard = new TemplateGuard();

		$value = $guard->safeguard_macros( $value );

		$filtered = wp_check_invalid_utf8( $value );

		$filtered = trim( wp_kses( $filtered, self::get_allowed_html() ) );

		// Restore the macros with the original values.
		$filtered = $guard->restore_macros( $filtered );

		if ( $json_encode ) {
			// json_encode to avoid errors when saving multi-byte emojis into database with no multi-byte support.
			$filtered = wp_json_encode( $filtered );
		}

		if ( $addslashes ) {
			// add slashes to avoid stripping of backslashes.
			$filtered = addslashes( $filtered );
		}

		return apply_filters( 'wptelegram_pro_sanitize_message_template', $filtered, func_get_args() );
	}

	/**
	 * Get a valid parse mode
	 *
	 * @since 2.0.0
	 *
	 * @param string $parse_mode Parse mode.
	 *
	 * @return string
	 */
	public static function valid_parse_mode( $parse_mode ) {

		return 'HTML' === $parse_mode ? 'HTML' : '';
	}

	/**
	 * Returns Jed-formatted localization data.
	 *
	 * @source gutenberg_get_jed_locale_data()
	 *
	 * @since 1.4.0
	 *
	 * @param  string $domain Translation domain.
	 *
	 * @return array
	 */
	public static function get_jed_locale_data( $domain ) {
		$translations = get_translations_for_domain( $domain );

		$locale = [
			'' => [
				'domain' => $domain,
				'lang'   => is_admin() ? get_user_locale() : get_locale(),
			],
		];

		if ( ! empty( $translations->headers['Plural-Forms'] ) ) {
			$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
		}

		foreach ( $translations->entries as $msgid => $entry ) {
			$locale[ $msgid ] = $entry->translations;
		}

		return $locale;
	}

	/**
	 * Create a regex from the given pattern.
	 *
	 * @since    1.4.0
	 *
	 * @param string  $pattern     The pattern to match.
	 * @param boolean $allow_empty Whether to allow an empty string.
	 * @param boolean $match_full  Whether to match the complete word.
	 * @param string  $delim       The delimiter to use.
	 *
	 * @return string
	 */
	public static function enhance_regex( $pattern, $allow_empty = false, $match_full = true, $delim = '' ) {
		if ( $allow_empty ) {
			$pattern = '(?:' . $pattern . ')?';
		}
		if ( $match_full ) {
			$pattern = '\A' . $pattern . '\Z';
		}
		if ( $delim ) {
			$pattern = $delim . $pattern . $delim;
		}
		return $pattern;
	}

	/**
	 * Generate a unique nonce field.
	 *
	 * @since  1.4.0
	 *
	 * @param boolean $echo Whether to echo the HTML.
	 * @return string
	 */
	public static function nonce_field( $echo = true ) {
		return wp_nonce_field( self::nonce(), self::nonce(), false, $echo );
	}

	/**
	 * Generate a nonce.
	 *
	 * @since  1.4.0
	 * @param string $name The name suffix.
	 * @return string unique nonce string.
	 */
	public static function nonce( $name = '_wptelegram_pro' ) {

		return 'nonce_' . $name;
	}

	/**
	 * Whether the current screen is a post edit page.
	 *
	 * @since 1.4.0
	 *
	 * @param string|string[] $post_type The post type to check.
	 *
	 * @return bool
	 */
	public static function is_post_edit_page( $post_type = null ) {

		global $pagenow, $typenow;

		$is_edit_page = 'post-new.php' === $pagenow || 'post.php' === $pagenow;

		if ( $is_edit_page ) {
			if ( $post_type ) {
				return in_array( $typenow, (array) $post_type, true );
			}
			return true;
		}
		return false;
	}

	/**
	 * Whether the current screen is a posts list page.
	 *
	 * @since 1.4.0
	 *
	 * @param string|string[] $post_type The post type to check.
	 *
	 * @return bool
	 */
	public static function is_post_list_page( $post_type = null ) {

		global $pagenow, $typenow;

		$is_list_page = 'edit.php' === $pagenow;

		if ( $is_list_page ) {
			if ( $post_type ) {
				return in_array( $typenow, (array) $post_type, true );
			}
			return true;
		}
		return false;
	}

	/**
	 * Convert underscored  or dashed strings to camelCase (medial capitals).
	 *
	 * @param string $string The string to convert.
	 * @param bool   $lcfirst Whether to convert first character to lowercase.
	 * @param bool   $separators The optional separators contains the word separator characters.
	 *
	 * @return string
	 */
	public static function snake_to_camel( $string, $lcfirst = false, $separators = ' -_' ) {
		$string = str_replace( str_split( $separators ), '', ucwords( $string, $separators ) );

		return $lcfirst ? lcfirst( $string ) : $string;
	}

	/**
	 * Gets the current post type in the WordPress Admin
	 *
	 * @since 1.4.0
	 *
	 * @return string|NULL
	 */
	public static function get_current_post_type() {

		global $post, $typenow, $pagenow, $current_screen;

		// we have a post so we can just get the post type from that.
		if ( $post && $post->post_type ) {
			return $post->post_type;
		}

		// check the global $typenow - set in admin.php.
		if ( $typenow ) {
			return $typenow;
		}

		// check the global $current_screen object - set in screen.php.
		if ( $current_screen && $current_screen->post_type ) {
			return $current_screen->post_type;
		}

		// check the post_type query string.
		$post_type = filter_input( INPUT_GET, 'post_type', FILTER_UNSAFE_RAW );

		if ( $post_type ) {
			return sanitize_key( $post_type );
		}

		// check if post ID is in query string.
		$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );
		if ( $post_id ) {
			return get_post_type( (int) $post_id );
		}

		// lastly check if the page is edit.php or post-new.php.
		if ( 'edit.php' === $pagenow || 'post-new.php' === $pagenow ) {
			return 'post';
		}

		// we do not know the post type!
		return null;
	}

	/**
	 * Converts an array of responses to be sent to Telegram to
	 * an array of methods used in responses.
	 *
	 * @since 1.0.0
	 *
	 * @param array $responses Array of responses.
	 *
	 * @return array The methods.
	 */
	public static function get_methods_from_responses( $responses ) {

		$methods = [];

		foreach ( $responses as $response ) {

			$params = reset( $response );
			$method = key( $response );

			$methods[ $method ] = true;
		}

		return $methods;
	}

	/**
	 * Decode HTML entities.
	 *
	 * @since 1.4.0
	 *
	 * @param string $text The text to decode HTML entities in.
	 *
	 * @return string The text with HTML entities decoded.
	 */
	public static function decode_html( $text ) {
		return html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Compare the two values by the given operator.
	 *
	 * @since 1.4.0
	 *
	 * @param string $value1       First value - placed on the left side of the operator.
	 * @param string $value2       Second value - placed on the right side of the operator.
	 * @param string $operator     The operator to use for comparison.
	 * @param string $split_for_in Whether to split the values for IN and NOT_IN.
	 *
	 * @return bool
	 */
	public static function compare_values( $value1, $value2, $operator = '=', $split_for_in = true ) {
		$result = false;

		// convert to string for strict comparison.
		$value1 = strtolower( strval( $value1 ) );
		$value2 = strtolower( strval( $value2 ) );

		$operator = strtoupper( $operator );

		switch ( $operator ) {
			case '=':
				$result = $value1 === $value2;
				break;

			case '!=':
				$result = $value1 !== $value2;
				break;

			case '>':
				$result = floatval( $value1 ) > floatval( $value2 );
				break;

			case '>=':
				$result = floatval( $value1 ) >= floatval( $value2 );
				break;

			case '<':
				$result = floatval( $value1 ) < floatval( $value2 );
				break;

			case '<=':
				$result = floatval( $value1 ) <= floatval( $value2 );
				break;

			case 'CONTAINS':
			case 'NOT_CONTAINS':
				$contains = false !== strpos( $value1, $value2 );
				$result   = ( $contains && 'CONTAINS' === $operator ) || ( ! $contains && 'NOT_CONTAINS' === $operator );
				break;

			case 'STARTS_WITH':
			case 'NOT_STARTS_WITH':
				$starts_with = preg_match( '/\A' . preg_quote( $value2, '/' ) . '/i', $value1 );
				$result      = ( $starts_with && 'STARTS_WITH' === $operator ) || ( ! $starts_with && 'NOT_STARTS_WITH' === $operator );
				break;

			case 'ENDS_WITH':
			case 'NOT_ENDS_WITH':
				$ends_with = preg_match( '/' . preg_quote( $value2, '/' ) . '\Z/i', $value1 );
				$result    = ( $ends_with && 'ENDS_WITH' === $operator ) || ( ! $ends_with && 'NOT_ENDS_WITH' === $operator );
				break;

			case 'IN': // usually applied for array of values.
			case 'NOT_IN':
				$array_value1 = $split_for_in ? array_map( 'trim', explode( ',', $value1 ) ) : [ $value1 ];
				// Second value is considered as the comparison value, hence it will have to be an array.
				$array_value2 = array_map( 'trim', explode( ',', $value2 ) );

				// if any value of $array_value2 is in $array_value1.
				$is_in = (bool) array_intersect( $array_value2, $array_value1 );

				$result = ( $is_in && 'IN' === $operator ) || ( ! $is_in && 'NOT_IN' === $operator );
				break;

			case 'BETWEEN':
			case 'NOT_BETWEEN':
				$float_value1 = floatval( $value1 );
				// it's assumed that comma separated values are supplied.
				$float_values2 = array_map( 'floatval', explode( ',', $value2 ) );
				if ( ! isset( $float_values2[0], $float_values2[1] ) ) {
					break;
				}
				$is_greater_than_left = $float_values2[0] < $float_value1;
				$is_less_than_right   = $float_value1 < $float_values2[1];
				// equality to remove boundary condition.
				$is_not_equal_to_either = $float_values2[0] !== $float_value1 && $float_values2[1] !== $float_value1;

				$is_between = $is_greater_than_left && $is_less_than_right && $is_not_equal_to_either;

				$result = ( $is_between && 'BETWEEN' === $operator ) || ( ! $is_between && 'NOT_BETWEEN' === $operator );
				break;
		}
		return $result;
	}

	/**
	 * Try to get the absolute path from url.
	 *
	 * @param string $url The URL to get the absolute path from.
	 *
	 * @return bool|string It returns the absolute path or false if fille doesn't exist.
	 */
	public static function url_to_path( $url ) {
		$path = wp_parse_url( $url, PHP_URL_PATH );

		if ( empty( $path ) ) {
			return false;
		}

		$file = ABSPATH . ltrim( $path, '/' );

		return file_exists( $file ) ? $file : false;
	}

	/**
	 * Generate a random UUID.
	 *
	 * @return string UUID.
	 */
	public static function uuid() {
		return wp_generate_uuid4();
	}

	/**
	 * Retrieve Telegram's supported html tags.
	 *
	 * @since 1.5.9
	 *
	 * @return string
	 */
	public static function get_supported_html_tags() {

		$supported_tags = '<' . implode( '><', array_keys( self::get_allowed_html() ) ) . '>';

		return apply_filters( 'wptelegram_pro_supported_html_tags', $supported_tags );
	}

	/**
	 * Retrieve Telegram's supported html tags.
	 *
	 * @since 1.6.1
	 *
	 * @return array
	 */
	public static function get_allowed_html() {

		return apply_filters( 'wptelegram_pro_allowed_html', self::ALLOWED_HTML );
	}

	/**
	 * The HTMLConverter instance
	 *
	 * @param string $options The options for HtmlConverter.
	 * @param string $id      The identifier of the converter options.
	 *
	 * @return HtmlConverter The HTMLConverter instance.
	 */
	public static function get_html_converter( $options = [], $id = 'default' ) {

		$defaults = [
			'format_to'          => 'text',
			'table_row_sep'      => "\n" . str_repeat( '-', 30 ) . "\n",
			'throw_on_doc_error' => true,
		];

		$options = wp_parse_args( $options, $defaults );
		$options = apply_filters( 'wptelegram_pro_html_converter_options', $options, $id );
		$options = apply_filters( "wptelegram_pro_{$id}_html_converter_options", $options );

		$converter = new HtmlConverter( $options );
		// Use this filter to add your own HTML converters.
		$converter = apply_filters( 'wptelegram_pro_html_converter', $converter, $options, $id );
		$converter = apply_filters( "wptelegram_pro_{$id}_html_converter", $converter, $options );

		return $converter;
	}

	/**
	 * Prepare the content for sending to Telegram.
	 *
	 * @param string $content The content to prepare.
	 * @param array  $options The options {
	 *     Optional. The options to prepare the content.
	 *
	 *     @type string $elipsis      The elipsis to use. Default '…'.
	 *     @type string $format_to    The format to convert to. Default 'text'.
	 *     @type string $id           The identifier of the converter options. Default 'default'.
	 *     @type int    $limit        The limit of the content. Default 55.
	 *     @type string $limit_by     The limit type. Default 'words'.
	 *     @type bool   $preserve_eol Whether to preserve new lines. Default true.
	 * }
	 *
	 * @return string The prepared content.
	 */
	public static function prepare_content( $content, $options = [] ) {

		$defaults = [
			'elipsis'         => '…',
			'format_to'       => 'text',
			'id'              => 'default',
			'limit'           => 55,
			'limit_by'        => 'words',
			'text_hyperlinks' => 'strip',
			'preserve_eol'    => true,
		];

		$options = wp_parse_args( $options, $defaults );

		$converter = self::get_html_converter( $options, $options['id'] );

		$result = trim( strip_shortcodes( $content ) );

		try {
			$result = $converter->convert( $result );
		} catch ( ConverterException $exception ) {

			// Since there was an error, we supposedly cannot format to HTML.
			$result = wp_strip_all_tags( HtmlConverter::prepareHtml( $result ) );

			$result = FormatTextUtils::decodeHtmlEntities( HtmlConverter::cleanUp( $result ) );

			// If we were supposed to format to HTML,
			// we need to ensure that special characters are escaped.
			if ( 'HTML' === $options['format_to'] ) {
				$result = FormatTextUtils::htmlSpecialChars( $result );
			}

			// override formatting.
			$options['format_to'] = 'text';

			do_action( 'wptelegram_pro_prepare_content_error', $exception, $content, $options );
		}

		// Remove new lines if not preserving them.
		if ( ! $options['preserve_eol'] ) {
			$result = preg_replace( '/[\n\r]+/u', ' ', $result );
		}

		// if there is limit set.
		if ( $options['limit'] && $options['limit'] > 0 ) {
			if ( 'HTML' === $options['format_to'] ) {
				// We are formatting to HTML, so we will use the safe trim to avoid breaking the HTML.
				$result = $converter->safeTrim( $result, $options['limit_by'], $options['limit'] );
			} else {
				$result_before_limit = $result;
				// We are formatting to text.
				$result = FormatTextUtils::limitTextBy( $result, $options['limit_by'], $options['limit'] );

				// Add the elipsis if the text is trimmed.
				if ( $result !== $result_before_limit ) {
					$result = trim( $result ) . $options['elipsis'];
				}
			}
		}

		return apply_filters( 'wptelegram_pro_prepare_content', $result, $content, $options );
	}

	/**
	 * Smartly trim the content inside <excerpt></excerpt> until the whole content is less than the limit.
	 *
	 * @param string $content The content to trim.
	 * @param array  $options The options. See prepare_content() for more info.
	 *
	 * @return string The prepared content.
	 */
	public static function smart_trim_excerpt( $content, $options = [] ) {

		if ( ! preg_match( self::EXCERPT_PATTERN, $content, $match ) ) {
			return self::prepare_content( $content, $options );
		}

		$placeholder = '{:excerpt:}';
		$delimiter   = '{::excerpt::}';

		$excerpt = $match[1];

		/**
		 * Add a placeholder for the excerpt.
		 *
		 * $content: `This is the starting content. <excerpt>This is the excerpt.</excerpt> This is the rest of the content.`.
		 *
		 * $result: `This is the starting content. {:excerpt:} This is the rest of the content.`
		 */
		$result = preg_replace( self::EXCERPT_PATTERN, $placeholder, $content );

		/**
		 * Place the excerpt at the end of the content separated by delimiter.
		 *
		 * $result: `This is the starting content. {:excerpt:} This is the rest of the content.{::excerpt::}This is the excerpt`
		 */
		$result = $result . $delimiter . $excerpt;

		/**
		 * Since the excerpt is at the end, it will be trimmed first.
		 *
		 * $result: `This is the starting content. {:excerpt:} This is the rest of the content.{::excerpt::}This is…`
		 */
		$result = self::prepare_content( $result, $options );

		/**
		 * If the delimiter is found, we will split the content to get the excerpt.
		 *
		 * $result: `This is the starting content. {:excerpt:} This is the rest of the content.{::excerpt::}This is…`
		 * OR
		 * $result: `This is the starting content. {:excerpt:} This is the rest of the…`
		 */
		if ( false !== strpos( $result, $delimiter ) ) {
			/**
			 * The first part is the trimmed content and the second part is the trimmed excerpt.
			 *
			 * $result: `This is the starting content. {:excerpt:} This is the rest of the content.`
			 *
			 * $trimmed_excerpt: `This is…`
			 */
			list( $result, $trimmed_excerpt) = array_pad( explode( $delimiter, $result ), 2, '' );

		} else {
			/**
			 * Sorry, it was all nuked.
			 *
			 * $result: `This is the starting content. {:excerpt:} This is the rest of the…`
			 */
			$trimmed_excerpt = '';
		}
		// Escape the placeholder.
		$placeholder = preg_quote( $placeholder, '/' );
		// Remove the new line if the excerpt is empty.
		$placeholder = '/' . ( $trimmed_excerpt ? $placeholder : $placeholder . '[\n\r]?' ) . '/ius';

		/**
		 * Replace the placeholder with the trimmed excerpt.
		 *
		 * $result: `This is the starting content. This is… This is the rest of the content.`
		 * OR
		 * $result: `This is the starting content. This is the rest of the…`
		 */
		$result = preg_replace( $placeholder, FormatTextUtils::preparePregReplacement( $trimmed_excerpt ), $result );

		return apply_filters( 'wptelegram_pro_smart_trim_excerpt', $result, $content, $options );
	}

	/**
	 * Split content into multiple chunks in order to send to Telegram as multiple messages.
	 *
	 * @since 2.0.0
	 *
	 * @param string $content The content to split.
	 * @param string $parse_mode The parse mode.
	 *
	 * @return string[] The split content.
	 */
	public static function split_content( $content, $parse_mode = '' ) {
		$limit = self::get_max_text_length( 'text' );

		// Remove <excerpt>...</excerpt> tags.
		$content = preg_replace( self::EXCERPT_PATTERN, '${1}', $content );

		// break text after every nth character given by the limit and preserve words.
		preg_match_all( '/.{1,' . $limit . '}(?:\s|$)/su', $content, $matches );

		$parts = $matches[0];

		// If the parse mode is HTML, we need to do some extra work
		// to make sure the HTML tags are not broken.
		if ( 'HTML' === $parse_mode ) {
			$parts = array_map( 'force_balance_tags', $parts );
		}

		return $parts;
	}

	/**
	 * Get the maximum length of the text to send to Telegram.
	 *
	 * @since 2.0.0
	 *
	 * @param string $for     The type of the text. Can be 'text' or 'caption' Default 'text'.
	 * @param int    $padding Safety padding to add to the limit. Default 20 characters.
	 *
	 * @return int The maximum length of the text to send to Telegram.
	 */
	public static function get_max_text_length( $for = 'text', $padding = 20 ) {

		$length = 'caption' === $for ? HtmlConverter::TG_CAPTION_MAX_LENGTH : HtmlConverter::TG_TEXT_MAX_LENGTH;

		// Add the safety padding.
		$length -= abs( (int) $padding );

		return (int) apply_filters( 'wptelegram_pro_max_text_length', $length, $for, $padding );
	}
}
