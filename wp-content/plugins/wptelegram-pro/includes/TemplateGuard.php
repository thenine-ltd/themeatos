<?php
/**
 * Template Guard
 *
 * @link       https://wptelegram.pro
 * @since      1.6.3
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */

namespace WPTelegram\Pro\includes;

/**
 * This class is used to guard the template from being broken
 * during sanitization.
 *
 * @link       https://wptelegram.pro
 * @since      1.6.3
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\includes
 */
class TemplateGuard {

	/**
	 * The map of macros to their temporary placeholders.
	 *
	 * @var array $macro_map The map of macros to their temporary placeholders.
	 */
	protected $macro_map = [];

	/**
	 * Safeguard the template macros from being broken by wp_kses().
	 *
	 * For example wp_kses() can result in malformed template
	 * For example,
	 * <a href="{cf:_field_name}">Click here</a>
	 * gets converted to
	 * <a href="_field_name}">Click here</a>
	 * due to ":" in the href being treated as a part of some protocol.
	 *
	 * @since 1.6.3
	 *
	 * @param string $template The template to safeguard.
	 *
	 * @return string The safeguarded template.
	 */
	public function safeguard_macros( $template ) {

		$this->macro_map = [];

		// Match all macros in the template.
		// For example, {cf:_field_name}, {post_title}, {post_content}, {encode:{terms:post_tag}}, etc.
		if ( preg_match_all( '/\{[^\}]+?\}+/iu', $template, $matches ) ) {

			$macros = $matches[0];

			// Sort the macros by the number of closing braces.
			// This is to ensure that the macros with more closing braces
			// are replaced first. e.g. {encode:{terms:post_tag}} should be replaced first.
			usort(
				$macros,
				function ( $a, $z ) {
					return substr_count( $z, '}' ) - substr_count( $a, '}' );
				}
			);

			$total = count( $macros );

			// Replace the macros with temporary placeholders.
			for ( $i = 0; $i < $total; $i++ ) {
				$this->macro_map[ "##MACRO{$i}##" ] = $macros[ $i ];
			}
		}

		// Replace the macros with temporary placeholders.
		$safe_template = str_replace( array_values( $this->macro_map ), array_keys( $this->macro_map ), $template );

		return $safe_template;
	}

	/**
	 * Restore the template macros.
	 *
	 * @since 1.6.3
	 *
	 * @param string $template The template to restore.
	 *
	 * @return string The restored template.
	 */
	public function restore_macros( $template ) {

		// Restore the macros with the original values.
		$restored_template = str_replace( array_keys( $this->macro_map ), array_values( $this->macro_map ), $template );

		return $restored_template;
	}
}
