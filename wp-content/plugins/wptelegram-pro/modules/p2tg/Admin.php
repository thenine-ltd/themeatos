<?php
/**
 * The admin-specific functionality of the module.
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\p2tg
 */

namespace WPTelegram\Pro\modules\p2tg;

use WPTelegram\Pro\modules\BaseClass;
use WPTelegram\Pro\includes\Options;
use WPTelegram\Pro\includes\Utils as MainUtils;
use WPTelegram\Pro\includes\Helpers;
use WPTelegram\Pro\includes\AssetManager;
use WPTelegram\Pro\modules\p2tg\restApi\InstanceController;
use WPTelegram\Pro\modules\p2tg\restApi\SettingsController;
use WPTelegram\Pro\modules\p2tg\restApi\RulesController;
use WPTelegram\Pro\modules\p2tg\restApi\InstantPostController;
use WPTelegram\BotAPI\API;

use WP_Post;

/**
 * The admin-specific functionality of the module.
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\p2tg
 * @author     WP Socio
 */
class Admin extends BaseClass {

	const OVERRIDE_METABOX_ID = 'wptelegram_pro_p2tg_override';

	/**
	 * Instance override fields for post edit page.
	 *
	 * @since 1.4.0
	 *
	 * @var array
	 */
	private static $override_fields = [
		'channels',
		'delay',
		'disable_notification',
		'files',
		'message_template',
		'send_featured_image',
	];

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 *
	 * @return void
	 */
	public function admin_enqueue_styles() {

		$handle = AssetManager::ADMIN_P2TG_INSTANT_POST_HANDLE;

		if (
			MainUtils::is_post_list_page( $this->get_override_meta_box_screens() )
			&& wp_style_is( $handle, 'registered' )
		) {
			wp_enqueue_style( $handle );
		}

		$handle = AssetManager::ADMIN_P2TG_METABOX_HANDLE;

		if (
			current_user_can( Main::instances_cap() ) &&
			MainUtils::is_post_edit_page( Main::CPT_NAME )
			&& wp_style_is( $handle, 'registered' )
		) {
			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {

		$data   = null;
		$handle = null;

		// Enqueue instance edit page assets.
		if (
			current_user_can( Main::instances_cap() ) &&
			MainUtils::is_post_edit_page( Main::CPT_NAME )
		) {
			$handle = AssetManager::ADMIN_P2TG_METABOX_HANDLE;

			wp_dequeue_script( 'autosave' );
			wp_enqueue_script( $handle );

			// Pass data to JS.
			$data = AssetManager::instance()->get_dom_data( 'P2TG_METABOX' );
		}

		$screens = $this->get_override_meta_box_screens();

		// Enqueue instant post assets for post list pages.
		if ( MainUtils::is_post_list_page( $screens ) ) {
			$handle = AssetManager::ADMIN_P2TG_INSTANT_POST_HANDLE;

			// needed for file uploads.
			wp_enqueue_media();

			wp_enqueue_script( $handle );

			$data = AssetManager::instance()->get_dom_data( 'P2TG_INSTANT_POST' );

			if ( wp_style_is( $handle, 'registered' ) ) {
				wp_enqueue_style( $handle );
			}
		}

		if ( $data && $handle ) {
			AssetManager::add_dom_data( $handle, $data );
		}

		// Load Post to Telegram js for classic editor.
		if (
			self::show_post_edit_switch() &&
			MainUtils::is_post_edit_page( $screens ) &&
			! did_action( 'enqueue_block_editor_assets' )
		) {
			$handle = AssetManager::ADMIN_P2TG_CLASSIC_JS_HANDLE;

			wp_enqueue_script( $handle );
		}
	}

	/**
	 * Enqueue assets for the Gutenberg block
	 *
	 * @since    3.0.3
	 */
	public function enqueue_block_editor_assets() {

		if ( ! self::show_post_edit_switch() ) {
			return;
		}

		$screens = $this->get_override_meta_box_screens();

		if ( MainUtils::is_post_edit_page( $screens ) ) {
			$handle = AssetManager::ADMIN_P2TG_GB_JS_HANDLE;

			wp_enqueue_script( $handle );

			// Pass data to JS.
			$data = AssetManager::instance()->get_dom_data( 'BLOCKS' );

			AssetManager::add_dom_data( $handle, $data );

			if ( wp_style_is( $handle, 'registered' ) ) {
				wp_enqueue_style( $handle );
			}
		}
	}

	/** Updates the DOM data related to p2tg.
	 *
	 * @param array  $data The existing DOM data.
	 * @param string $for  The domain for which the DOM data is to be rendered.
	 *
	 * @return array
	 */
	public function set_dom_data( $data, $for ) {

		if ( 'SETTINGS_PAGE' === $for ) {
			$data['uiData'] = array_merge(
				$data['uiData'],
				[
					'p2tgCPTUrl' => $this->module()->options()->get( 'active' ) ? admin_url( 'edit.php?post_type=' . Main::CPT_NAME ) : '',
				]
			);
		} elseif ( 'P2TG_INSTANT_POST' === $for ) {

			$data['uiData'] = [
				'instances' => self::get_instances_ui_data(),
			];
		} elseif ( 'P2TG_METABOX' === $for ) {
			global $post;

			$data['uiData'] = array_merge(
				$data['uiData'],
				[
					'bot_options'         => Helpers::get_bot_options( $this->module()->options()->get( 'bot' ) ),
					'buttons'             => $this->module()->options()->get( 'buttons' ),
					'post_types'          => $this->get_post_type_options(),
					'macros'              => $this->get_macros(),
					'rule_types'          => Rules::get_rule_types(),
					'is_wp_cron_disabled' => defined( 'DISABLE_WP_CRON' ) && constant( 'DISABLE_WP_CRON' ),
					'is_wc_active'        => class_exists( 'woocommerce' ),
					'delete_link'         => MainUtils::decode_html( urldecode_deep( get_delete_post_link( $post->ID ) ) ),
					'post'                => [
						'ID'   => (int) $post->ID,
						'type' => $post->post_type,
					],
				]
			);

			$data['savedSettings'] = SettingsController::get_instance_settings( $post->ID );

		} elseif ( 'BLOCKS' === $for ) {
			global $post;

			$saved_settings = [
				'send2tg'    => self::send2tg_default() === 'yes',
				'force_send' => self::force_send_default() === 'yes',
				'instances'  => self::get_instances_data(),
			];

			$data['savedSettings'] = $saved_settings;

			$data['uiData'] = [
				'instances' => self::get_instances_ui_data(),
			];
		}

		return $data;
	}

	/**
	 * Get instances data for post edit page.
	 *
	 * @since  1.4.0
	 *
	 * @return array
	 */
	public static function get_instances_data() {
		$post_type = MainUtils::get_current_post_type();
		$instances = Utils::get_saved_instances( $post_type );

		$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

		if ( ! empty( $post_id ) ) {
			// try to get the instances from meta.
			$meta_instances = get_post_meta( $post_id, Main::PREFIX . 'instances', true );

			if ( ! empty( $meta_instances ) ) {
				$instances = array_map( [ Utils::class, 'decode_instance_values' ], $meta_instances );
			}
		}

		$fields_to_retain = self::$override_fields;

		$delay_for_override = get_post_meta( $post_id, Main::PREFIX . 'delay_for_override', true );

		$instances = array_map(
			function ( $instance ) use ( $fields_to_retain, $delay_for_override ) {
				// Save instance ID before filtering.
				$instance_id = $instance['id'];

				// Now filter out the useless fields.
				$filtered_instance = Utils::filter_instance_fields( $instance, $fields_to_retain );

				// If we have override delay saved in meta.
				if ( isset( $delay_for_override[ $instance_id ] ) ) {
					$filtered_instance['delay'] = $delay_for_override[ $instance_id ];
				}

				// Make sure that 'files' field exists.
				if ( empty( $filtered_instance['files'] ) ) {
					$filtered_instance['files'] = [];
				}
				return $filtered_instance;
			},
			$instances
		);

		return (array) apply_filters( 'wptelegram_pro_p2tg_instances_data', $instances );
	}

	/**
	 * Get instances UI data for blocks.
	 *
	 * @since  1.4.0
	 *
	 * @param boolean $instance_active Default value to active property of the instances.
	 *
	 * @return array
	 */
	public static function get_instances_ui_data( $instance_active = false ) {
		$post_type = MainUtils::get_current_post_type();
		$instances = Utils::get_saved_instances( $post_type );

		$fields_to_retain = self::$override_fields;
		// Set active by default if single instance.
		$instance_active = count( $instances ) === 1 || $instance_active;

		$instances = array_map(
			function ( $instance ) use ( $fields_to_retain, $instance_active ) {
				// Get the title before filtering.
				$title = MainUtils::decode_html( get_the_title( $instance['id'] ) );

				$filtered_instance = Utils::filter_instance_fields( $instance, $fields_to_retain );

				// Set the title.
				$filtered_instance['title'] = $title;
				// This field should/will be overwritten by savedSettings.
				$filtered_instance['active'] = $instance_active;

				$filtered_instance['menu_order'] = get_post_field( 'menu_order', $instance['id'] );

				return $filtered_instance;
			},
			$instances
		);
		return (array) apply_filters( 'wptelegram_pro_p2tg_instances_ui_data', $instances );
	}

	/**
	 * Get the macro groups.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_macros() {
		$to_skip = [
			'product_shipping_class',
		];

		$taxonomies = get_taxonomies(
			[
				'public'   => true,
				'_builtin' => false,
			],
			'names'
		);

		$term_macros = [
			'{tags}',
			'{categories}',
			'{terms:taxonomy}',
		];

		foreach ( $taxonomies as $taxonomy ) {
			if ( in_array( $taxonomy, $to_skip, true ) ) {
				continue;
			}
			$term_macros[] = "{terms:{$taxonomy}}";
		}

		$macro_groups = [
			'post'  => [
				'label'  => __( 'Post Data', 'wptelegram-pro' ),
				'macros' => [
					'{ID}',
					'{post_title}',
					'{post_slug}',
					'{post_date}',
					'{post_date_gmt}',
					'{post_format}',
					'{post_type}',
					'{post_author}',
					'{post_excerpt}',
					'{post_content}',
					'{post_type}',
					'{short_url}',
					'{full_url}',
					'{home_url}',
				],
			],
			'terms' => [
				'label'  => __( 'Taxonomy Terms', 'wptelegram-pro' ),
				'macros' => $term_macros,
			],
			'cf'    => [
				'label'  => __( 'Custom Fields', 'wptelegram-pro' ),
				'macros' => [
					'{cf:custom_field}',
					'{acf:field_name}',
				],
			],
			'wc'    => [
				'label'  => __( 'WooCommerce Product Data', 'wptelegram-pro' ),
				'macros' => [
					'{wc:description}',
					'{wc:short_description}',
					'{wc:sku}',
					'{wc:price}',
					'{wc:regular_price}',
					'{wc:sale_price}',
					'{wc:total_sales}',
					'{wc:weight}',
					'{wc:length}',
					'{wc:width}',
					'{wc:height}',
					'{wc:average_rating}',
					'{wc:review_count}',
					'{wc:save_amount}',
					'{wc:save_percent}',
				],
			],
		];
		/* translators: 1: taxonomy, 2: {terms:taxonomy} */
		$macro_groups['terms']['info'] = sprintf( __( 'Replace %1$s in %2$s by the name of the taxonomy to insert its terms attached to the post.', 'wptelegram-pro' ), '<code>taxonomy</code>', '<code>{terms:taxonomy}</code>' ) . ' ' . sprintf( __( 'For example %1$s and %2$s in WooCommerce', 'wptelegram-pro' ), '<code>{terms:product_cat}</code>', '<code>{terms:product_tag}</code>' );

		/* translators: 1: custom field, 2: field name, 3: {cf:custom_field}, 4: {acf:field_name} */
		$macro_groups['cf']['info'] = sprintf( __( 'Replace %1$s and %2$s in %3$s and %4$s by the name of the Custom Field and ACF Field Name respectively.', 'wptelegram-pro' ), '<code>custom_field</code>', '<code>field_name</code>', '<code>{cf:custom_field}</code>', '<code>{acf:field_name}</code>' ) . ' ' . sprintf( __( 'For example %1$s, %2$s', 'wptelegram-pro' ), '<code>{cf:rtl_title}</code>', '<code>{acf:writer}</code>' );
		/**
		 * If you add your own macro_groups using this filter
		 * You should use "wptelegram_pro_p2tg_template_macro_values" filter
		 * to replace the macro with the corresponding values.
		 */
		return (array) apply_filters( 'wptelegram_pro_p2tg_template_macro_groups', $macro_groups );
	}

	/**
	 * Get the registered post types.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_post_type_options() {

		$options = [];

		$post_types = get_post_types( [ 'public' => true ], 'objects' );

		foreach ( $post_types  as $post_type ) {

			if ( 'attachment' !== $post_type->name ) {

				$options[] = [
					'value' => $post_type->name,
					'label' => "{$post_type->labels->singular_name} ({$post_type->name})",
				];
			}
		}

		return apply_filters( 'wptelegram_pro_p2tg_post_type_options', $options, $post_types );
	}

	/**
	 * Register WP REST API routes and fields.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_routes() {
		$controller = new RulesController();
		$controller->register_routes();
		$controller = new InstantPostController();
		$controller->register_routes();
		$controller = new SettingsController();
		$controller->register_fields();
	}

	/**
	 * Hooks into "rest_pre_insert_{$post->post_type}"
	 * to create a hack for did_action for filters.
	 *
	 * @since 1.4.5
	 */
	public function hook_into_rest_pre_insert() {
		$post_types = Utils::get_field_from_all_instances( 'post_types', true );

		foreach ( $post_types as $post_type ) {
			add_filter( "rest_pre_insert_{$post_type}", [ $this, 'do_rest_pre_insert_action' ], 10, 1 );
		}
	}

	/**
	 * Sets the rest_pre_insert action for post types to use in PostSender.
	 *
	 * @since 1.4.5
	 *
	 * @param \stdClass $post An object representing a single post prepared.
	 */
	public function do_rest_pre_insert_action( $post ) {

		do_action( 'wptelegram_pro_rest_pre_insert_' . $post->post_type );

		return $post;
	}

	/**
	 * Create a hidden field on the post edit page
	 * to use it for checking the requests from web
	 * in save_post callback
	 *
	 * @since    1.0.0
	 */
	public function post_edit_form_hidden_input() {
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo '<input type="hidden" id="' . esc_attr( Main::PREFIX . 'from_web' ) . '" name="' . esc_attr( Main::PREFIX . 'from_web' ) . '" value="yes" />';
	}

	/**
	 * Create a hidden field into block editor metabox section.
	 *
	 * @since 1.4.0
	 */
	public function block_editor_hidden_fields() {
		// phpcs:ignore WordPress.Security.EscapeOutput
		echo '<input type="hidden" id="' . esc_attr( Main::PREFIX . 'is_gb_metabox' ) . '" name="' . esc_attr( Main::PREFIX . 'is_gb_metabox' ) . '" value="yes" />';
	}

	/**
	 * Override metabox
	 *
	 * @since    1.0.0
	 */
	public function create_non_cmb2_override_metabox() {

		$screens = $this->get_override_meta_box_screens();

		if (
			! did_action( 'cmb2_admin_init' ) &&
			self::show_post_edit_switch() &&
			! empty( $screens )
		) {
			add_meta_box(
				self::OVERRIDE_METABOX_ID,
				sprintf( '%s (%s)', __( 'Post to Telegram', 'wptelegram-pro' ), __( 'WP Telegram Pro', 'wptelegram-pro' ) ),
				[ $this, 'render_non_cmb2_metabox' ],
				$screens,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render the non CMB2 metabox.
	 *
	 * @since 1.0.0
	 */
	public function render_non_cmb2_metabox() {
		$this->render_post_edit_switch();
	}

	/**
	 * Whether to show th epost edit switch or not.
	 *
	 * @since 1.4.0
	 *
	 * @return bool
	 */
	public static function show_post_edit_switch() {

		$bot_username = WPTG_Pro()->options()->get_path( 'p2tg.bot' );

		$show_post_edit_switch = ! empty( $bot_username );

		if ( $show_post_edit_switch ) {
			$show_post_edit_switch = WPTG_Pro()->options()->get_path( 'p2tg.post_edit_switch', true );
		}

		return (bool) apply_filters( 'wptelegram_pro_p2tg_show_post_edit_switch', $show_post_edit_switch );
	}

	/**
	 * Override metabox
	 *
	 * @since    1.0.0
	 */
	public function create_cmb2_override_metabox() {

		$screens = $this->get_override_meta_box_screens();

		if ( ! self::show_post_edit_switch() || empty( $screens ) ) {
			return;
		}

		/**
		 * Initiate the metabox
		 */
		$box  = [
			'id'           => self::OVERRIDE_METABOX_ID,
			'title'        => sprintf( '%s (%s)', __( 'Post to Telegram', 'wptelegram-pro' ), __( 'WP Telegram Pro', 'wptelegram-pro' ) ),
			'object_types' => $screens,
			'context'      => 'normal',
			'priority'     => 'high',
			'save_fields'  => false,
		];
		$cmb2 = new_cmb2_box( $box );

		$cmb2->add_field(
			[
				'name' => __( 'Override default settings', 'wptelegram-pro' ),
				'desc' => __( 'Yes', 'wptelegram-pro' ),
				'id'   => Main::PREFIX . '[override_switch]',
				'type' => 'checkbox',
			]
		);

		$saved_instances = self::get_instances_data();

		if ( empty( $saved_instances ) ) {
			$cmb2->set_prop( 'hookup', false );
			return;
		}

		$options = new Options();

		/**
		 * Add Instance fields
		 */
		foreach ( $saved_instances as $instance_id => $instance ) {

			$options->set_data( $instance );
			$this->add_instance_fields( $instance_id, $options, $cmb2 );
		}
	}

	/**
	 * Dynamically add instance fields.
	 *
	 * @since  1.0.0
	 *
	 * @param int     $instance_id Instance ID.
	 * @param Options $options the options object.
	 * @param \CMB2   $cmb2 the metabox object.
	 * @return void
	 */
	public function add_instance_fields( $instance_id, $options, &$cmb2 ) {

		static $instance_args;

		if ( is_null( $instance_args ) ) {

			$instance_args = [
				'type'       => 'checkbox',
				'show_names' => false,
				'default'    => 'on',
				'before'     => '<h3>' . __( 'Instances', 'wptelegram-pro' ) . '</h3>',
			];
		}

		$instance_args['desc'] = get_the_title( $instance_id );
		$instance_args['id']   = Main::PREFIX . "[instances][{$instance_id}][active]";

		$cmb2->add_field( $instance_args );
		unset( $instance_args['before'] );

		$channels = $options->get( 'channels', [] );
		$channels = array_combine( $channels, $channels );

		$cmb2->add_field(
			[
				'name'              => ' ',
				'desc'              => __( 'Channels', 'wptelegram-pro' ),
				'id'                => Main::PREFIX . "[instances][{$instance_id}][channels]",
				'type'              => 'multicheck',
				'options'           => $channels,
				'default'           => $channels,
				'select_all_button' => false,
			]
		);

		$cmb2->add_field(
			[
				'name'    => ' ',
				'desc'    => __( 'Disable Notifications', 'wptelegram-pro' ),
				'id'      => Main::PREFIX . "[instances][{$instance_id}][disable_notification]",
				'type'    => 'checkbox',
				'default' => $options->get( 'disable_notification', false ),
			]
		);

		$cmb2->add_field(
			[
				'name' => ' ',
				'desc' => __( 'Files to be sent after the message', 'wptelegram-pro' ),
				'id'   => Main::PREFIX . "[instances][{$instance_id}][files]",
				'type' => 'file_list',
			]
		);

		$cmb2->add_field(
			[
				'name'       => ' ',
				'desc'       => __( 'Minutes', 'wptelegram' ),
				'id'         => Main::PREFIX . "[instances][{$instance_id}][delay]",
				'after'      => '<br />' . __( 'Delay in Posting', 'wptelegram' ),
				'type'       => 'text_small',
				'default'    => $options->get( 'delay', 0 ),
				'attributes' => [
					'min'         => 0,
					'placeholder' => '0.0',
					'step'        => 'any',
					'type'        => 'number',
				],
			]
		);

		$cmb2->add_field(
			[
				'name'    => __( 'Featured Image', 'wptelegram-pro' ),
				'desc'    => __( 'Send Featured Image (if exists).', 'wptelegram-pro' ),
				'id'      => Main::PREFIX . "[instances][{$instance_id}][send_featured_image]",
				'type'    => 'checkbox',
				'default' => $options->get( 'send_featured_image', true ),
				'before'  => '<input type="hidden" name="' . Main::PREFIX . "[instances][{$instance_id}][send_featured_image]" . '" value="off" />',
			]
		);

		$cmb2->add_field(
			[
				'name'    => ' ',
				'desc'    => __( 'Structure of the message to be sent', 'wptelegram-pro' ),
				'id'      => Main::PREFIX . "[instances][{$instance_id}][message_template]",
				'type'    => 'textarea',
				'default' => $options->get( 'message_template', '' ),
			]
		);
	}

	/**
	 * Get registered post type names.
	 *
	 * @since  1.0.0
	 * @return array
	 */
	public function get_override_meta_box_screens() {

		$screens = Utils::get_field_from_all_instances( 'post_types', true );

		return (array) apply_filters( 'wptelegram_pro_p2tg_override_meta_box_screens', $screens );
	}

	/**
	 * Registers the CPT for instances.
	 *
	 * @return void
	 */
	public function register_custom_post_type() {
		$labels = [
			'name'                  => _x( 'Post to Telegram Instances', 'Post Type General Name', 'wptelegram-pro' ),
			'singular_name'         => _x( 'Post to Telegram Instance', 'Post Type Singular Name', 'wptelegram-pro' ),
			'menu_name'             => __( 'Post to Telegram Instances', 'wptelegram-pro' ),
			'name_admin_bar'        => __( 'Instance', 'wptelegram-pro' ),
			'archives'              => __( 'Instance Archives', 'wptelegram-pro' ),
			'attributes'            => __( 'Instance Attributes', 'wptelegram-pro' ),
			'parent_item_colon'     => __( 'Parent Instance:', 'wptelegram-pro' ),
			'all_items'             => __( 'All Instances', 'wptelegram-pro' ),
			'add_new_item'          => __( 'Add New Instance', 'wptelegram-pro' ),
			'add_new'               => __( 'Add New', 'wptelegram-pro' ),
			'new_item'              => __( 'New Instance', 'wptelegram-pro' ),
			'edit_item'             => __( 'Edit Instance', 'wptelegram-pro' ),
			'update_item'           => __( 'Update Instance', 'wptelegram-pro' ),
			'view_item'             => __( 'View Instance', 'wptelegram-pro' ),
			'view_items'            => __( 'View Instances', 'wptelegram-pro' ),
			'search_items'          => __( 'Search Instance', 'wptelegram-pro' ),
			'not_found'             => __( 'No Instances found', 'wptelegram-pro' ),
			'not_found_in_trash'    => __( 'No instance found in Trash', 'wptelegram-pro' ),
			'items_list'            => __( 'Instances list', 'wptelegram-pro' ),
			'items_list_navigation' => __( 'Instances list navigation', 'wptelegram-pro' ),
			'filter_items_list'     => __( 'Filter instances list', 'wptelegram-pro' ),
		];

		$capability = Main::instances_cap();

		$args = [
			'label'                 => __( 'Instance', 'wptelegram-pro' ),
			'description'           => __( 'Post to Telegram Instance', 'wptelegram-pro' ),
			'labels'                => $labels,
			'supports'              => [ 'title' ],
			'hierarchical'          => false,
			'public'                => false,
			'show_ui'               => true,
			'show_in_menu'          => false,
			'menu_position'         => 5,
			'show_in_admin_bar'     => false,
			'show_in_nav_menus'     => true,
			'can_export'            => true,
			'has_archive'           => false,
			'exclude_from_search'   => true,
			'publicly_queryable'    => false,
			'rewrite'               => false,
			'query_var'             => false,
			'capability_type'       => 'post',
			'capabilities'          => [
				'read_post'    => $capability,
				'edit_post'    => $capability,
				'delete_post'  => $capability,
				'edit_posts'   => $capability,
				'delete_posts' => $capability,
			],
			'show_in_rest'          => true,
			'rest_controller_class' => InstanceController::class,
			'register_meta_box_cb'  => [ $this, 'register_meta_box' ],
		];
		register_post_type( Main::CPT_NAME, $args );
	}

	/**
	 * Fix Parent Admin Menu Item.
	 */
	public function add_cpt_menu() {
		add_submenu_page(
			WPTG_Pro()->name(),
			__( 'Post to Telegram Instances', 'wptelegram-pro' ),
			__( 'Post to Telegram Instances', 'wptelegram-pro' ),
			Main::instances_cap(),
			'edit.php?post_type=' . Main::CPT_NAME
		);
	}

	/**
	 * Fix Parent Admin Menu Item.
	 *
	 * @param string $parent_file The parent file name.
	 * @return string
	 */
	public function cpt_parent_file( $parent_file ) {

		/* Get current screen */
		global $current_screen, $submenu_file;

		/**
		 * Add plugin page as parent file/menu if
		 * it's Post Type list Screen or Edit screen of our post type.
		 */
		if ( in_array( $current_screen->base, [ 'post', 'edit' ], true ) && Main::CPT_NAME === $current_screen->post_type ) {

			// phpcs:ignore
			$submenu_file = 'edit.php?post_type=' . Main::CPT_NAME;
			$parent_file  = WPTG_Pro()->name();
		}

		return $parent_file;
	}

	/**
	 * Set the custom columns for instance CPT list table.
	 *
	 * @param array $columns The columns.
	 * @return array
	 */
	public function set_custom_post_list_columns( $columns ) {

		$allowed_columns = [ 'cb', 'title' ];
		// Remove useless columns from shitty plugins.
		foreach ( $columns as $column => $title ) {
			if ( ! in_array( $column, $allowed_columns, true ) ) {
				unset( $columns[ $column ] );
			}
		}

		$columns = array_merge(
			$columns,
			[
				'active'           => __( 'Active', 'wptelegram-pro' ),
				'channels'         => __( 'Channel(s)', 'wptelegram-pro' ),
				'post_types'       => __( 'Post types', 'wptelegram-pro' ),
				'message_template' => __( 'Message Template', 'wptelegram-pro' ),
			]
		);

		return $columns;
	}

	/**
	 * Set the custom columns for instance CPT list table.
	 *
	 * @param array $column  The columns.
	 * @param int   $post_id The post ID.
	 * @return void
	 */
	public function render_custom_post_list_columns( $column, $post_id ) {

		switch ( $column ) {
			case 'active':
				$active = (bool) get_post_meta( $post_id, Main::PREFIX . $column, true );
				$status = $active ? __( 'Yes', 'wptelegram-pro' ) : __( 'No', 'wptelegram-pro' );
				echo esc_html( $status );
				break;
			case 'channels':
			case 'post_types':
				$value = (array) get_post_meta( $post_id, Main::PREFIX . $column, true );
				echo esc_html( implode( ', ', $value ) );
				break;
			case 'message_template':
				echo '<pre style="white-space: pre-wrap;">', esc_html( json_decode( get_post_meta( $post_id, Main::PREFIX . $column, true ) ) ),'</pre>';
				break;
		}
	}

	/**
	 * Filter the 'Months' drop-down results for CPT.
	 *
	 * @since 3.7.0
	 *
	 * @param object $months    The months drop-down query results.
	 * @param string $post_type The post type.
	 */
	public function filter_months_dropdown( $months, $post_type ) {
		if ( Main::CPT_NAME === $post_type ) {
			return [];
		}
		return $months;
	}

	/**
	 * Filter the CPT list table Bulk Actions drop-down.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $actions An array of the available bulk actions.
	 */
	public function filter_bulk_actions( $actions ) {
		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Remove quick edit option from CPT.
	 *
	 * @param array   $actions The actions array.
	 * @param WP_Post $post    Current post.
	 * @return array
	 */
	public function remove_quick_edit_button( $actions, $post ) {
		if ( Main::CPT_NAME === $post->post_type ) {
			unset( $actions['inline hide-if-no-js'] );
		}
		return $actions;
	}

	/**
	 * Registers the metabox for CPT.
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'wptelegram_pro_p2tg_cpt_mb',
			__( 'Settings', 'wptelegram-pro' ),
			'__return_null',
			Main::CPT_NAME,
			'normal',
			'high'
		);
	}

	/**
	 * Force layout to single column.
	 *
	 * @return int
	 */
	public function set_screen_layout() {
		return 1;
	}

	/**
	 * Add send to Telegram switch to post edit page
	 * when using classic editor.
	 *
	 * @since 1.0.0
	 */
	public function add_post_edit_switch() {
		if ( ! self::show_post_edit_switch() ) {
			return;
		}

		$screens = $this->get_override_meta_box_screens();
		if ( ! MainUtils::is_post_edit_page( $screens ) ) {
			return;
		}

		$is_cmb2_active = function_exists( 'cmb2_get_metabox' );
		?>
			<div class="misc-pub-section">
				<?php $this->render_post_edit_switch( $is_cmb2_active ); ?>
			</div>
		<?php
	}

	/**
	 * Renders the HTML for post edit switch.
	 *
	 * @since  1.0.0
	 *
	 * @param boolean $display_gear Whether to display link to override metabox.
	 * @return void
	 */
	public function render_post_edit_switch( $display_gear = false ) {
		?>
			<div class="wptg-pro-p2tg-post-edit-switch">
				<input type="hidden" name="<?php echo esc_attr( Main::PREFIX . '[send2tg]' ); ?>" value="no" />
				<input type="checkbox" id="<?php echo esc_attr( Main::PREFIX . 'send2tg' ); ?>" name="<?php echo esc_attr( Main::PREFIX . '[send2tg]' ); ?>" value="yes" <?php checked( self::send2tg_default(), 'yes' ); ?> />
				<label for="<?php echo esc_attr( Main::PREFIX . 'send2tg' ); ?>">
					<span style="padding-left:4px;font-weight:600;"><?php esc_html_e( 'Send to Telegram', 'wptelegram-pro' ); ?></span>
				</label>
					<?php if ( $display_gear ) : ?>
				&nbsp;<a style="text-decoration: none;" href="#<?php echo esc_attr( self::OVERRIDE_METABOX_ID ); ?>"><span class="dashicons dashicons-admin-generic"></span></a>
					<?php endif; ?>
				<div clear="both"></div>
				<input type="hidden" name="<?php echo esc_attr( Main::PREFIX . '[force_send]' ); ?>" value="no" />
				<label class="hidden" id="<?php echo esc_attr( Main::PREFIX . 'force_send-label' ); ?>" for="<?php echo esc_attr( Main::PREFIX . 'force_send' ); ?>">
					<input type="checkbox" id="<?php echo esc_attr( Main::PREFIX . 'force_send' ); ?>" name="<?php echo esc_attr( Main::PREFIX . '[force_send]' ); ?>" value="yes" <?php checked( self::force_send_default(), 'yes' ); ?> />
					<span style="padding-left:4px;">
						<?php
						esc_html_e( 'Force send', 'wptelegram-pro' );
						printf(
							/* translators: %s Rule name */
							' (' . __( 'Ignore %s Rules', 'wptelegram-pro' ), // phpcs:ignore WordPress.Security.EscapeOutput
							'<b>' . esc_html__( 'Use when', 'wptelegram-pro' ) . '</b>)'
						);
						?>
					</span>
				</label>
			</div>
		<?php
			MainUtils::nonce_field();
	}

	/**
	 * Override metabox.
	 *
	 * @since    1.4.0
	 */
	public function may_be_remove_override_metabox() {
		if ( did_action( 'enqueue_block_editor_assets' ) ) {
			// Lets remove the override metabox for block editor.
			global $post, $wp_meta_boxes;
			// Remove the metabox.
			unset( $wp_meta_boxes[ $post->post_type ]['normal']['high'][ self::OVERRIDE_METABOX_ID ] );
		}
	}

	/**
	 * Set default value for send2tg
	 *
	 * @since  1.0.0
	 */
	public static function force_send_default() {
		$default = 'no';

		$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

		if ( ! empty( $post_id ) ) {

			$force_send = get_post_meta( $post_id, Main::PREFIX . 'force_send', true );

			if ( $force_send ) {
				$default = $force_send;
			}
		}

		return (string) apply_filters( 'wptelegram_pro_p2tg_force_send_default', $default );
	}

	/**
	 * Set default value for send2tg
	 *
	 * @since  1.0.0
	 */
	public static function send2tg_default() {

		$use_when      = Utils::get_field_from_all_instances( 'use_when', true );
		$send_new      = in_array( 'new', $use_when, true );
		$send_existing = in_array( 'existing', $use_when, true );

		$default = 'yes';

		$post_id = filter_input( INPUT_GET, 'post', FILTER_SANITIZE_NUMBER_INT );

		// if we are on edit page.
		if ( ! empty( $post_id ) ) {
			$send2tg = get_post_meta( $post_id, Main::PREFIX . 'send2tg', true );
			$post    = get_post( $post_id );

			// if saved in meta e.g. for future or draft.
			if ( $send2tg && in_array( $send2tg, [ 'yes', 'no' ], true ) ) {

				$default = $send2tg;
			} elseif ( $post instanceof WP_Post ) {

				$is_new = Utils::is_post_new( $post );

				$is_new_and_dont_send_new           = $is_new && ! $send_new;
				$is_existing_and_dont_send_existing = ! $is_new && ! $send_existing;

				if ( $is_new_and_dont_send_new || $is_existing_and_dont_send_existing ) {
					$default = 'no';
				}
			}
		} elseif ( ! $send_new ) {
			$default = 'no';
		}

		return (string) apply_filters( 'wptelegram_pro_p2tg_send2tg_default', $default, $use_when, $post_id );
	}

	/**
	 * Add instant send button to the wp post list.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_instant_post_button() {

		global $typenow;
		$post_types = $this->get_override_meta_box_screens();

		if ( in_array( $typenow, $post_types, true ) ) {

			$html  = '<div class="p2tg-instant-send alignleft actions hide-if-no-js">';
			$html .= '<button type="button" class="button-primary wptelegram-pro-p2tg-instant-post-btn">';
			$html .= sprintf( '<img src="%s" />', esc_attr( WPTG_Pro()->assets()->url( '/icons/tg-icon.svg' ) ) );
			$html .= sprintf( '&nbsp;%s</button>', __( 'Send to Telegram', 'wptelegram-pro' ) );

			$html .= '</div>';

			echo $html; // phpcs:ignore
		}
	}

	/**
	 * Add instant post root element.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_instant_send_root() {
		if ( MainUtils::is_post_list_page( $this->get_override_meta_box_screens() ) ) {
			echo '<div id="wptelegram_pro_p2tg_instant_post"></div>';
		}
	}

	/**
	 * Post update messages for CPT.
	 *
	 * @param array $messages The messages array.
	 * @return array
	 */
	public function cpt_updated_messages( $messages ) {

		$messages[ Main::CPT_NAME ] = [
			1 => __( 'Instance updated.', 'wptelegram-pro' ),
			6 => __( 'Instance published.', 'wptelegram-pro' ),
		];

		return $messages;
	}

	/**
	 * Show admin notices on failure
	 *
	 * @since  1.0.0
	 */
	public function admin_notices() {

		$transient = 'wptelegram_pro_p2tg_errors';

		// phpcs:ignore
		if ( isset( $_GET[ Main::PREFIX . 'error' ] ) && $p2tg_errors = array_filter( (array) get_transient( $transient ) ) ) {

			$html = sprintf( '<b>%s (%s):</b> %s', __( 'WP Telegram Pro', 'wptelegram-pro' ), __( 'Post to Telegram', 'wptelegram-pro' ), __( 'There was some error!', 'wptelegram-pro' ) );

			$html .= '<table>';

			$html .= sprintf( '<tr><th>%s</th><th>%s</th><th>%s</th><th>%s</th></tr>', __( 'Instance', 'wptelegram-pro' ), __( 'Channel', 'wptelegram-pro' ), __( 'Error code', 'wptelegram-pro' ), __( 'Error message', 'wptelegram-pro' ) );

			foreach ( $p2tg_errors as $instance_id => $channel_errors ) {

				foreach ( $channel_errors as $channel => $errors ) {

					foreach ( $errors as $code => $message ) {

						$html .= sprintf( '<tr><td>%s</td><td><b>%s</b></td><td>%s</td><td><b>%s</b></td></tr>', get_the_title( $instance_id ), $channel, $code, $message );
					}
				}
			}

			$html .= '</table>';

			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo $html; // phpcs:ignore ?></p>
			</div>
			<?php
		}
		delete_transient( $transient );
	}

	/**
	 * Handle callback_query for button clicks
	 *
	 * @param  string $args        Arguments from callback data.
	 * @param  array  $message     The associate message.
	 * @param  API    $bot_api     Bot API instance.
	 * @param  Object $cbq_handler Callback_Query Handler object.
	 */
	public function handle_callback_query( $args, $message, $bot_api, $cbq_handler ) {

		$args = explode( '|', $args );

		// ID of the Post.
		$post_id = reset( $args );
		// Instance ID.
		$inst_id = next( $args );
		// button value.
		$button = next( $args );

		if ( ! $post_id || ! $inst_id || ! $button ) {
			return;
		}

		// Telegram user ID.
		$users_id = $cbq_handler->get_user( 'id' );

		// get the users list who have reacted to the buttons.
		$button_clicks = get_post_meta( $post_id, Main::PREFIX . 'button_clicks', true );

		$existing_user = false;

		if ( ! empty( $button_clicks ) ) {

			foreach ( $button_clicks as $button_type => $users ) {

				// if the user has already pressed a button.
				if ( in_array( $users_id, $users, true ) ) {

					$existing_user = true;

					break;
				}
			}

			if ( $existing_user ) {

				// remove the user from this list.
				foreach ( array_keys( $button_clicks[ $button_type ], $users_id, true ) as $key ) {
					unset( $button_clicks[ $button_type ][ $key ] );
				}

				if ( empty( $button_clicks[ $button_type ] ) ) {
					// remove empty buttons.
					unset( $button_clicks[ $button_type ] );
				} else {
					// destroy the keys.
					$button_clicks[ $button_type ] = array_values( $button_clicks[ $button_type ] );
				}
			}
		} else {

			// to be safe in the latest PHP versions.
			$button_clicks = [];
		}

		// if the user has not pressed same button again.
		// $button_type is defined only if $existing_user is true :) .
		if ( ! ( $existing_user && $button === $button_type ) ) {
			// add the user ID to the list.
			$button_clicks[ $button ][] = $users_id;
		}

		if ( ! add_post_meta( $post_id, Main::PREFIX . 'button_clicks', $button_clicks, true ) ) {
			update_post_meta( $post_id, Main::PREFIX . 'button_clicks', $button_clicks );
		}

		$instances = Utils::get_saved_instances( '', [ $inst_id ] );

		if ( isset( $instances[ $inst_id ] ) ) {

			$options = new Options();
			$options->set_data( $instances[ $inst_id ] );

			$inline_keyboard = MarkupBuilder::get_inline_keyboard( $post_id, $inst_id, $options );

			if ( ! empty( $inline_keyboard ) ) {

				$chat_id      = $cbq_handler->get_chat( 'id' );
				$message_id   = $message['message_id'];
				$reply_markup = wp_json_encode( compact( 'inline_keyboard' ) );

				$bot_api->editMessageReplyMarkup( compact( 'chat_id', 'message_id', 'reply_markup' ) );
			}
		}
	}
}
