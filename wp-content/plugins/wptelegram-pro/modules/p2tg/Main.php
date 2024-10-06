<?php
/**
 * The file that defines the module
 *
 * A class definition that includes attributes and functions used across the module
 *
 * @link       https://wptelegram.pro
 * @since      1.4.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules
 */

namespace WPTelegram\Pro\modules\p2tg;

use WPTelegram\Pro\modules\BaseModule;

/**
 * The module main class.
 *
 * @since      1.4.0
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules
 * @author     WP Socio
 */
class Main extends BaseModule {

	const CPT_NAME = 'wptgpro_p2tg';
	const PREFIX   = '_wptgpro_p2tg_';

	/**
	 * Register all of the hooks.
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected function define_necessary_hooks() {
		$admin = Admin::instance();

		add_filter( 'wptelegram_pro_assets_dom_data', [ $admin, 'set_dom_data' ], 10, 2 );
	}

	/**
	 * Register all of the hooks.
	 *
	 * @since    1.4.0
	 * @access   private
	 */
	protected function define_on_active_hooks() {

		$admin = Admin::instance();

		$cpt_name = self::CPT_NAME;

		add_action( 'admin_enqueue_scripts', [ $admin, 'admin_enqueue_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $admin, 'admin_enqueue_styles' ] );
		add_action( 'enqueue_block_editor_assets', [ $admin, 'enqueue_block_editor_assets' ] );

		add_action( 'post_submitbox_misc_actions', [ $admin, 'add_post_edit_switch' ] );
		add_action( 'edit_form_top', [ $admin, 'post_edit_form_hidden_input' ] );
		add_action( 'block_editor_meta_box_hidden_fields', [ $admin, 'block_editor_hidden_fields' ] );
		add_action( 'cmb2_admin_init', [ $admin, 'create_cmb2_override_metabox' ] );
		add_action( 'add_meta_boxes', [ $admin, 'may_be_remove_override_metabox' ], 100 );

		add_action( 'init', [ $admin, 'register_custom_post_type' ] );
		add_action( 'rest_api_init', [ $admin, 'register_rest_routes' ] );
		add_action( 'rest_api_init', [ $admin, 'hook_into_rest_pre_insert' ] );

		add_filter( "get_user_option_screen_layout_{$cpt_name}", [ $admin, 'set_screen_layout' ] );
		add_filter( "manage_{$cpt_name}_posts_columns", [ $admin, 'set_custom_post_list_columns' ], 999999, 1 );
		add_action( "manage_{$cpt_name}_posts_custom_column", [ $admin, 'render_custom_post_list_columns' ], 10, 2 );
		add_filter( 'months_dropdown_results', [ $admin, 'filter_months_dropdown' ], 10, 2 );
		add_filter( "bulk_actions-edit-{$cpt_name}", [ $admin, 'filter_bulk_actions' ], 10, 1 );
		add_action( 'post_updated_messages', [ $admin, 'cpt_updated_messages' ], 10, 1 );
		add_filter( 'post_row_actions', [ $admin, 'remove_quick_edit_button' ], 10, 2 );
		add_action( 'admin_menu', [ $admin, 'add_cpt_menu' ], 11 );
		add_filter( 'parent_file', [ $admin, 'cpt_parent_file' ] );

		add_action( 'manage_posts_extra_tablenav', [ $admin, 'add_instant_post_button' ], 10, 1 );
		add_action( 'admin_footer', [ $admin, 'add_instant_send_root' ], 10, 1 );

		add_action( 'admin_notices', [ $admin, 'admin_notices' ] );
		add_action( 'wptelegram_pro_bots_process_callback_query_p2tg', [ $admin, 'handle_callback_query' ], 10, 4 );

		$duplicator = DuplicateInstance::instance();

		$action = DuplicateInstance::ACTION;
		add_action( "admin_action_{$action}", [ $duplicator, 'duplicate_instance_action' ] );

		add_filter( 'post_row_actions', [ $duplicator, 'duplicate_link' ], 10, 2 );

		$post_sender = PostSender::instance();

		add_action( 'wp_insert_post', [ $post_sender, 'wp_insert_post' ], 20, 2 );

		// delay event handler.
		add_action( 'wptelegram_pro_p2tg_delayed_instance', [ $post_sender, 'delayed_instance' ], 10, 2 );

		// Trigger handler.
		add_action( 'wptelegram_pro_p2tg_send_post', [ $post_sender, 'send_post' ], 10, 4 );

		// Cron hook.
		add_action( 'wptelegram_pro_p2tg_process_queue', [ $post_sender, 'process_p2tg_queue' ], 10, 1 );
	}

	/**
	 * Returns the capability required for modifying the instances.
	 *
	 * @return string The user capability.
	 */
	public static function instances_cap() {
		return apply_filters( 'wptelegram_pro_p2tg_instances_cap', 'manage_options' );
	}
}
