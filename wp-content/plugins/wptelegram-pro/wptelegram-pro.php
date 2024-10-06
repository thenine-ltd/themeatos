<?php
/**
 * The main plugin file.
 *
 * @link              https://wptelegram.pro
 * @since             1.0.0
 * @package           WPTelegram\Pro
 *
 * @wordpress-plugin
 * Plugin Name:       WP Telegram Pro
 * Plugin URI:        https://wptelegram.pro
 * Description:       Absolute WordPress Telegram integration with premium features
 * Version:           2.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * Author:            WP Socio
 * Author URI:        https://wpsocio.com
 * Text Domain:       wptelegram-pro
 * Domain Path:       /languages
 * Update URI:        false
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'WPTELEGRAM_PRO_VER', '2.0.0' );

defined( 'WPTELEGRAM_PRO_MAIN_FILE' ) || define( 'WPTELEGRAM_PRO_MAIN_FILE', __FILE__ );

defined( 'WPTELEGRAM_PRO_BASENAME' ) || define( 'WPTELEGRAM_PRO_BASENAME', plugin_basename( WPTELEGRAM_PRO_MAIN_FILE ) );

define( 'WPTELEGRAM_PRO_DIR', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

define( 'WPTELEGRAM_PRO_URL', untrailingslashit( plugins_url( '', __FILE__ ) ) );

define( 'WPTELEGRAM_PRO_MODULES_DIR', WPTELEGRAM_PRO_DIR . '/modules' );

define( 'WPTELEGRAM_PRO_MODULES_URL', WPTELEGRAM_PRO_URL . '/modules' );

// Telegram user ID meta key.
if ( ! defined( 'WPTELEGRAM_USER_ID_META_KEY' ) ) {
	// Common for all WP Telegram plugins.
	define( 'WPTELEGRAM_USER_ID_META_KEY', 'wptelegram_user_id' );
}

/**
 * Include autoloader.
 */
require WPTELEGRAM_PRO_DIR . '/autoload.php';

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wptelegram-pro-activator.php
 */
function activate_wptelegram_pro() {
	\WPTelegram\Pro\includes\Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wptelegram-pro-deactivator.php
 */
function deactivate_wptelegram_pro() {
	\WPTelegram\Pro\includes\Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wptelegram_pro' );
register_deactivation_hook( __FILE__, 'deactivate_wptelegram_pro' );

/**
 * Begins execution of the plugin and acts as the main instance of \WPTelegram\Pro\includes\Main.
 *
 * Returns the main instance of \WPTelegram\Pro\includes\Main to prevent the need to use globals.
 *
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 * @return \WPTelegram\Pro\includes\Main
 */
function WPTG_Pro() { // phpcs:ignore
	return \WPTelegram\Pro\includes\Main::instance();
}

use \WPTelegram\Pro\includes\Requirements;

if ( Requirements::satisfied() ) {
	// Fire.
	WPTG_Pro()->init();

	define( 'WPTELEGRAM_PRO_LOADED', true );
} else {
	add_filter( 'after_plugin_row_' . WPTELEGRAM_PRO_BASENAME, [ Requirements::class, 'display_requirements' ] );
}
