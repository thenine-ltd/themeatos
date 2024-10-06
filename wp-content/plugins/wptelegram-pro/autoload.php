<?php
/**
 * Autoloader
 *
 * @link      https://wptelegram.pro
 * @since     1.4.0
 *
 * @package WPTelegram\Pro
 */

spl_autoload_register( 'wptelegram_pro_autoloader' );

/**
 * Autoloader.
 *
 * @param string $class_name The requested classname.
 * @return void
 */
function wptelegram_pro_autoloader( $class_name ) {

	$namespace = 'WPTelegram\Pro';

	if ( 0 !== strpos( $class_name, $namespace ) ) {
		return;
	}

	$class_name = str_replace( $namespace, '', $class_name );
	$class_name = str_replace( '\\', DIRECTORY_SEPARATOR, $class_name );

	$path = WPTELEGRAM_PRO_DIR . $class_name . '.php';

	include_once $path;
}
