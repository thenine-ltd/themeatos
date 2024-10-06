<?php
/**
 * Post to Telegram message template parser.
 *
 * @link        https://wptelegram.pro
 * @since       1.4.0
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 */

namespace WPTelegram\Pro\modules\p2tg;

use WPTelegram\Pro\includes\Options;
use WPTelegram\Pro\includes\Helpers;
use WPTelegram\Pro\includes\Utils as MainUtils;
use WP_Post;

/**
 * Post to Telegram message template parser.
 *
 * @package     WPTelegram\Pro
 * @subpackage  WPTelegram\Pro\modules\p2tg
 * @author      WP Socio
 */
class TemplateParser {

	/**
	 * The post to be handled
	 *
	 * @var WP_Post $post   Post object.
	 */
	protected $post;

	/**
	 * The post data
	 *
	 * @since   1.4.0
	 * @access  protected
	 * @var     PostData $post_data The post data.
	 */
	protected $post_data;

	/**
	 * The options for parsing the template.
	 *
	 * @var Options $options The options object.
	 */
	public $options;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.4.0
	 *
	 * @param int|WP_Post $post    Post object or ID.
	 * @param Options     $options The options for parsing the template.
	 */
	public function __construct( $post, $options = null ) {

		$this->set_post( $post );
		$this->set_options( $options );
	}

	/**
	 * Set the post
	 *
	 * @since    1.4.0
	 * @param   int|WP_Post $post   Post object or ID.
	 */
	public function set_post( $post ) {
		$this->post = get_post( $post );

		$this->reset_data();

		return $this;
	}

	/**
	 * Set the post
	 *
	 * @since 1.4.0
	 *
	 * @param Options $options The options for parsing the template.
	 */
	public function set_options( $options ) {
		if ( ! $options ) {
			// set to an empty object by default.
			$options = new Options();
		}
		$this->options = $options;

		return $this;
	}

	/**
	 * Reset the existing post data.
	 */
	public function reset_data() {
		$this->post_data = new PostData( $this->post );

		return $this;
	}

	/**
	 * Returns the valid parse mode.
	 */
	public function get_parse_mode() {
		return MainUtils::valid_parse_mode( $this->options->get( 'parse_mode' ) );
	}

	/**
	 * Parses the given template and converts it to the text.
	 *
	 * @since 1.4.0
	 *
	 * @param  string $template The template to parse.
	 *
	 * @return string The parsed value.
	 */
	public function parse( $template ) {

		$macro_values = $this->parse_macros( $template );

		$template = $this->normalize_template( $template );

		// lets replace the conditional macros.
		$template = $this->process_template_logic( $template, $macro_values );

		$text = str_replace( array_keys( $macro_values ), array_values( $macro_values ), $template );

		$text = $this->encode_values( $text );

		return apply_filters( 'wptelegram_pro_p2tg_parsed_template', $text, $template, $this->post, $this->options );
	}

	/**
	 * Parses the given template to encode the values if needed.
	 *
	 * @since 1.4.0
	 *
	 * @param  string $template The template to parse.
	 *
	 * @return string The parsed value.
	 */
	public function encode_values( $template ) {
		$pattern = '#\{encode:([^\}]+?)\}#iu';

		$encoded = preg_replace_callback(
			$pattern,
			function ( $match ) {
				return rawurlencode( $match[1] );
			},
			$template
		);

		return apply_filters( 'wptelegram_pro_p2tg_template_encoded_values', $encoded, $template );
	}

	/**
	 * Parses the given template to set the correct values to be parsed.
	 *
	 * @since 1.4.2
	 *
	 * @param  string $template The template to parse.
	 *
	 * @return string The normalized value.
	 */
	public function normalize_template( $template ) {

		$raw_template = $template;

		// replace {tags} and {categories} with taxonomy names.
		$replace = [ '{terms:post_tag}', '{terms:category}' ];

		// Use {tags} and {categories} for WooCommerce products.
		if ( class_exists( 'woocommerce' ) && 'product' === $this->post->post_type ) {

			$replace = [ '{terms:product_tag}', '{terms:product_cat}' ];
		}

		// Modify the template.
		$template = str_replace( [ '{tags}', '{categories}' ], $replace, $template );

		return apply_filters( 'wptelegram_pro_p2tg_normalized_template', $template, $raw_template, $this->post, $this->options );
	}

	/**
	 * Parses the given template for all possible macros and returns the macro data.
	 *
	 * @since 1.4.0
	 *
	 * @param  string $template The template to parse.
	 *
	 * @return array The parsed macro values.
	 */
	public function parse_macros( $template ) {

		// Remove wpautop() from the `the_content` filter
		// to preserve newlines.
		Helpers::bypass_wpautop_for( 'the_content' );

		$excerpt_source       = $this->options->get( 'excerpt_source' );
		$excerpt_length       = (int) $this->options->get( 'excerpt_length' );
		$excerpt_preserve_eol = $this->options->get( 'excerpt_preserve_eol' );
		$cats_as_tags         = $this->options->get( 'cats_as_tags' );
		$parse_mode           = MainUtils::valid_parse_mode( $this->options->get( 'parse_mode' ) );

		$template = $this->normalize_template( $template );

		$macro_keys = [
			'ID',
			'full_url',
			'home_url',
			'post_author',
			'post_content',
			'post_date',
			'post_date_gmt',
			'post_excerpt',
			'post_format',
			'post_id',
			'post_title',
			'post_slug',
			'post_type',
			'post_type_label',
			'post_url',
			'short_url',
		];

		// for post excerpt.
		$params = compact(
			'excerpt_source',
			'excerpt_length',
			'excerpt_preserve_eol',
			'cats_as_tags',
			'parse_mode'
		);

		$macro_values = [];

		foreach ( $macro_keys as $macro_key ) {
			$key = '{' . $macro_key . '}';

			// get the value only if it's in the template.
			if ( false !== strpos( $template, $key ) ) {

				$macro_values[ $key ] = $this->post_data->get_field( $macro_key, $params );
				if ( 'post_author' === $macro_key ) {
					$key = str_replace( 'post_author:', 'author_username:', $key );
					// store the values for slugs to be used in conditional logic.
					$macro_values[ $key ] = $this->post_data->get_field( 'author_username' );
				}
			}
		}

		// if it's something unusual :) .
		if ( preg_match_all( '/(?<=\{)(terms|a?cf|wc):([^\}]+?)(?=\})/iu', $template, $matches ) ) {

			foreach ( $matches[0] as $field ) {
				$key = '{' . $field . '}';

				$macro_values[ $key ] = $this->post_data->get_field( $field, $params );
				if ( false !== strpos( $key, 'terms:' ) ) {
					$search  = 'terms:';
					$replace = 'termslugs:';
					$key     = str_replace( $search, $replace, $key );
					$field   = str_replace( $search, $replace, $field );
					// store the values for slugs to be used in conditional logic.
					$macro_values[ $key ] = $this->post_data->get_field( $field );
				}
			}
		}

		/**
		 * Use this filter to replace your own macros
		 * with the corresponding values
		 */
		$macro_values = (array) apply_filters( 'wptelegram_pro_p2tg_template_macro_values', $macro_values, $this->post, $this->options );

		// Prepare macro values for further processing.
		$macro_values = array_map( [ $this, 'prepare_macro_value' ], $macro_values );

		return $macro_values;
	}

	/**
	 * Prepare macro value for further processing.
	 *
	 * @since 1.4.0
	 *
	 * @param string $macro_value The value for a macro.
	 *
	 * @return string
	 */
	public function prepare_macro_value( $macro_value ) {
		// Remove unwanted slashes.
		return stripslashes( $macro_value );
	}

	/**
	 * Resolve the conditional macros in the template.
	 *
	 * @since 1.4.0
	 *
	 * @param string $template     The message template being processed.
	 * @param array  $macro_values The values for all macros.
	 *
	 * @return string
	 */
	private function process_template_logic( $template, $macro_values ) {

		$raw_template = $template;
		// decode &lt; etc.
		$template = MainUtils::decode_html( $template );

		$operators = '!=|=|>=|>|<=|<|CONTAINS|NOT_CONTAINS|IN|NOT_IN|BETWEEN|NOT_BETWEEN|STARTS_WITH|NOT_STARTS_WITH|ENDS_WITH|NOT_ENDS_WITH';

		$pattern = '/\[if\s*?                          # Conditional block starts
			(?P<macro>\{[^\}]+?\})                     # Conditional expression, a macro
			(?:                                        # non-capturing comparison block
				\s*(?P<operator>' . $operators . ')\s* # Comparison operator, with optional spaces around
				(?P<value>[^\]]+?)                     # Non empty comparison value
			)?                                         # Make comparison block optional
		\]                                             # Conditional block ends
		\[                                             # Consequence block starts
			(?P<consequence>[^\]]*?)                   # Consequence expression
		\]                                             # Consequence block ends
		(?:                                            # non-capturing alternative block
			\[                                         # Alternative block starts
				(?P<alternative>[^\]]*?)               # Alternative expression
			\]                                         # Alternative block ends
		)?                                             # Make alternative block optional
		/ix';

		preg_match_all( $pattern, $template, $matches, PREG_SET_ORDER );

		// loop through the conditional expressions.
		foreach ( $matches as $match ) {
			// If it's an advanced logic.
			if ( ! empty( $match['operator'] ) && isset( $match['value'] ) ) {
				$replace = $this->process_conditional_macro( $macro_values, $match );
			} else { // It's the basic logic.
				// if expression is false, take from alternative.
				$key = empty( $macro_values[ $match['macro'] ] ) ? 'alternative' : 'consequence';

				$replace = isset( $match[ $key ] ) ? str_replace( array_keys( $macro_values ), array_values( $macro_values ), $match[ $key ] ) : '';
			}

			$template = str_replace( $match[0], $replace, $template );
		}

		// remove the ugly empty lines.
		$template = preg_replace( '/(?:\A|[\n\r]).*?\{remove_line\}.*/u', '', $template );

		return apply_filters(
			'wptelegram_pro_p2tg_process_template_logic',
			$template,
			$macro_values,
			$raw_template,
			$this->post,
			$this->options
		);
	}

	/**
	 * Process the conditional macro in message template.
	 *
	 * @since 1.4.0
	 *
	 * @param array $macro_values The values for each macro.
	 * @param array $condition    The array containing the rules to apply.
	 *
	 * @return string
	 */
	private function process_conditional_macro( $macro_values, $condition ) {
		// convert macros.
		$macro = str_replace(
			[ 'terms:', 'post_author:' ],
			[ 'termslugs:', 'author_username:' ],
			$condition['macro']
		);
		// there should be a value for the given macro.
		$macro_value = $macro_values[ $macro ];
		// if condition value is passed as some macro, lets replace that with its value.
		$condition_value = str_replace( array_keys( $macro_values ), array_values( $macro_values ), $condition['value'] );

		$condition_applies = MainUtils::compare_values(
			$macro_value,
			$condition_value,
			$condition['operator'],
			strpos( $macro, 'termslugs:' ) !== false
		);

		// if condition applies, take from consequence.
		$key = $condition_applies ? 'consequence' : 'alternative';

		$replace = isset( $condition[ $key ] ) ? str_replace( array_keys( $macro_values ), array_values( $macro_values ), $condition[ $key ] ) : '';

		return apply_filters(
			'wptelegram_pro_p2tg_process_conditional_macro',
			$replace,
			$macro_values,
			$condition,
			$this->post,
			$this->options
		);
	}
}
