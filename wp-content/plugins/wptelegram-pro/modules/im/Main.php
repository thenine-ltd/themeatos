<?php
/**
 * The file that defines the module
 *
 * A class definition that includes attributes and functions used across the module
 *
 * @link       https://wptelegram.pro
 * @since      1.3.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\im
 */

namespace WPTelegram\Pro\modules\im;

use WPTelegram\Pro\modules\BaseModule;

/**
 * The module main class.
 *
 * @since      1.4.0
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\im
 * @author     WP Socio
 */
class Main extends BaseModule {

	/**
	 * Register all of the hooks.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected function define_necessary_hooks() {

		$admin = Admin::instance();

		add_filter( 'wptelegram_pro_assets_dom_data', [ $admin, 'update_dom_data' ], 10, 2 );

		add_action( 'admin_enqueue_scripts', [ $admin, 'enqueue_admin_styles' ], 10, 1 );

		add_action( 'admin_enqueue_scripts', [ $admin, 'enqueue_admin_scripts' ], 10, 1 );

		add_action( 'admin_menu', [ $admin, 'add_plugin_admin_menu' ], 11 );

		add_filter( 'wp_prepare_attachment_for_js', [ $admin, 'prepare_attachment_for_js' ], 10, 3 );
	}

}
