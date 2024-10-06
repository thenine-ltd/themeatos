<?php
/**
 * Admin Post Type Discount class.
 *
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.0.0
 * @version 1.6.0
 * @author  YITH
 *
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWDPD_VERSION' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Customize the List table for Discount Post Type.
 *
 * @class   YITH_WC_Dynamic_Discount_Post_Type_Admin
 * @package YITH WooCommerce Dynamic Pricing and Discounts
 * @since   1.0.0
 * @author  YITH
 */
if ( ! class_exists( 'YITH_WC_Dynamic_Discount_Post_Type_Admin' ) ) {
	/**
	 * Class YITH_WC_Dynamic_Pricing_Admin
	 */
	class YITH_WC_Dynamic_Discount_Post_Type_Admin {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Dynamic_Discount_Post_Type_Admin
		 */
		protected static $instance;

		/**
		 * Post type name
		 *
		 * @var string
		 */
		public $post_type_name = '';

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Dynamic_Discount_Post_Type_Admin
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		public function __construct() {

			$this->post_type_name = YITH_WC_Dynamic_Pricing_Admin()->post_type_name;

			add_filter( 'pre_get_posts', array( $this, 'filter_post_by_discount_type' ), 10, 1 );
			add_filter( 'disable_months_dropdown', array( $this, 'remove_months_dropdown' ), 10, 2 );

			add_filter( 'views_edit-' . $this->post_type_name, '__return_null' );
			add_action( 'bulk_actions-edit-' . $this->post_type_name, array( $this, 'custom_bulk_action' ), 10 );
			add_filter( 'handle_bulk_actions-edit-' . $this->post_type_name, array(
				$this,
				'bulk_action_handler'
			), 10, 3 );

			add_action( 'yith_plugin_fw_panel_active_tab_class', array(
				$this,
				'custom_panel_active_tab_class'
			), 10, 3 );
			add_action( 'yith_plugin_fw_panel_url', array( $this, 'custom_tab_url' ), 10, 5 );

			add_filter( 'manage_' . $this->post_type_name . '_posts_columns', array( $this, 'manage_list_columns' ) );
			add_action( 'manage_' . $this->post_type_name . '_posts_custom_column', array(
				$this,
				'render_list_columns'
			), 10, 2 );

			add_filter( 'post_row_actions', array( $this, 'manage_row_actions' ), 10, 2 );

			add_filter( 'admin_body_class', array( $this, 'add_admin_body_class' ), 10 );

			add_action( 'manage_posts_extra_tablenav', array( $this, 'maybe_render_blank_state' ) );
		}


		/**
		 * Add the discount type inside the body class
		 *
		 * @param string $body_class Body Class.
		 *
		 * @return string
		 */
		public function add_admin_body_class( $body_class ) {
			$screen    = get_current_screen();
			$screen_id = $screen ? $screen->id : '';

			if ( 'edit-' . $this->post_type_name === $screen_id && isset( $_GET['ywdpd_discount_type'] ) ) { //phpcs:ignore
				$body_class .= ' ywdpd-discount-type-' . $_GET['ywdpd_discount_type']; //phpcs:ignore
			}

			return $body_class;
		}


		/**
		 * Manage the columns
		 *
		 * @param array $columns Columns.
		 *
		 * @return array
		 */
		public function manage_list_columns( $columns ) {

			unset( $columns['date'] );
			unset( $columns['title'] );

			$new_columns['cb'] = $columns['cb'];
			unset( $columns['cb'] );

			$new_columns['name']          = __( 'Rule Name', 'ywdpd' );
			$new_columns['discount_mode'] = __( 'Discount Mode', 'ywdpd' );
			$new_columns['status']        = __( 'Status', 'ywdpd' );
			$new_columns['priority']      = __( 'Priority', 'ywdpd' );

			$new_columns = array_merge( $new_columns, $columns );

			return $new_columns;
		}

		/**
		 * Manage the row actions in the Discount List
		 *
		 * @param array $actions Action list.
		 * @param WP_Post $post WP_Post.
		 *
		 * @return array
		 */
		public function manage_row_actions( $actions, $post ) {
			if ( get_post_type( $post ) === $this->post_type_name ) {

				if ( isset( $actions['inline hide-if-no-js'] ) ) {
					unset( $actions['inline hide-if-no-js'] );
				}

				if ( isset( $actions['trash'] ) ) {
					unset( $actions['trash'] );
				}

				$show_duplicate_link = add_query_arg(
					array(
						'post_type' => $this->post_type_name,
						'action'    => 'duplicate_discount',
						'post'      => $post->ID,
					),
					admin_url( 'post.php' )
				);

				$show_duplicate_link  = wp_nonce_url( $show_duplicate_link, 'ywdpd-duplicate-rule_' . $post->ID );
				$actions['duplicate'] = sprintf( '<a href="%s">%s</a>', esc_url( $show_duplicate_link ), __( 'Duplicate', 'ywdpd' ) );

				$actions['delete'] = sprintf(
					'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
					get_delete_post_link( $post->ID, '', true ),
					esc_attr( __( 'Delete', 'ywdpd' ) ),
					__( 'Delete', 'ywdpd' )
				);

			}

			return $actions;
		}

		/**
		 * Render the columns
		 *
		 * @param array $column Column.
		 * @param int $post_id Post id.
		 */
		public function render_list_columns( $column, $post_id ) {
			$post = get_post( $post_id );
			switch ( $column ) {
				case 'name':
					$mode_option   = get_post_meta( $post_id, '_discount_mode', true );
					$warning_message = '';
					if( 'exclude_items' === $mode_option ){
						$warning_message = '<span class="ywdpd-alert-rule alert-rule"></span>';
					}
					echo '<strong>' . $post->post_title . '</strong>'.$warning_message;
					break;
				case 'discount_mode':
					$modes         = ywdpd_discount_pricing_mode();
					$mode_option   = get_post_meta( $post_id, '_discount_mode', true );
					$discount_mode = isset( $modes[ $mode_option ] ) ? $modes[ $mode_option ] : '';
					echo '<strong>' . esc_html( $discount_mode ) . '</strong>';
					break;
				case 'status':
					$status = get_post_meta( $post_id, '_active', 1 );

					echo "<div class='yith-plugin-ui'>";
					echo yith_plugin_fw_get_field(
						array(
							'type'  => 'onoff',
							'class' => 'ywdpd-toggle-enabled',
							'value' => yith_plugin_fw_is_true( $status ) ? 'yes' : 'no',
							'data'  => array(
								'discount-id' => $post_id,
								'security'    => wp_create_nonce( 'discount-status-toggle-enabled' ),
							),
						)
					);
					echo '</div>';

					break;
				case 'priority':
					echo '<span class="yith-icon yith-icon-drag"></span><small>' . _x( 'Arrange order', 'Arrange the discount rule priority', 'ywdpd' ) . '</small>';
					break;
			}
		}

		/**
		 * Handle the custom bulk action.
		 *
		 * @param string $redirect_to Redirect URL.
		 * @param string $do_action Selected bulk action.
		 * @param array $post_ids Post ids.
		 *
		 * @return mixed
		 */
		public function bulk_action_handler( $redirect_to, $do_action, $post_ids ) {
			if ( 'activate' !== $do_action && 'deactivate' !== $do_action ) {
				return $redirect_to;
			}

			foreach ( $post_ids as $discount_id ) {

				$post_type_object = get_post_type_object( $this->post_type_name );

				if ( current_user_can( $post_type_object->cap->delete_post, $discount_id ) ) {
					switch ( $do_action ) {
						case 'activate':
							update_post_meta( $discount_id, '_active', 1 );
							break;
						case 'deactivate':
							update_post_meta( $discount_id, '_active', false );
							break;
						default:
					}
				}
			}

			return $redirect_to;
		}

		/**
		 * Add custom bulk actions.
		 *
		 * @param array $bulk_actions Bulk action list.
		 *
		 * @return array
		 */
		public function custom_bulk_action( $bulk_actions ) {

			unset( $bulk_actions['trash'] );
			$bulk_actions['activate']   = __( 'Activate', 'ywdpd' );
			$bulk_actions['deactivate'] = __( 'Deactivate', 'ywdpd' );
			$bulk_actions['delete']     = __( 'Delete', 'ywdpd' );

			return $bulk_actions;
		}

		/**
		 * Remove months dropdown on discount list table.
		 *
		 * @param bool $result Hide or show the filter.
		 * @param string $post_type Post type.
		 *
		 * @return bool
		 */
		public function remove_months_dropdown( $result, $post_type ) {

			if ( $this->post_type_name === $post_type ) {
				$result = true;
			}

			return $result;
		}

		/**
		 * Filter the discount type.
		 *
		 * @param WP_Query $query WP_Query.
		 *
		 * @return WP_Query
		 */
		public function filter_post_by_discount_type( $query ) {

			if ( $query->is_main_query() && isset( $query->query['post_type'] ) && $this->post_type_name === $query->query['post_type'] ) {
				$meta_query = ! ! $query->get( 'meta_query' ) ? $query->get( 'meta_query' ) : array();

				if ( isset( $_GET['ywdpd_discount_type'] ) && ! empty( $_GET['ywdpd_discount_type'] ) ) {
					$meta_query[] = array(
						'relation' => 'OR',
						array(
							'key'   => '_discount_type',
							'value' => $_GET['ywdpd_discount_type'],
						),
					);

					$query->set( 'orderby', 'meta_value_num' );
					$query->set( 'meta_key', '_priority' );
					$query->set( 'order', 'ASC' );
					$query->set( 'meta_query', $meta_query );
				}
			}

			return $query;
		}

		/**
		 * Change the active class.
		 *
		 * @param string $active_class Active class.
		 * @param string $current_tab Current tab.
		 * @param string $tab Tab.
		 *
		 * @return string
		 */
		public function custom_panel_active_tab_class( $active_class, $current_tab, $tab ) {

			if ( isset( $_GET['ywdpd_discount_type'] ) ) {
				$classes = explode( ' ', $active_class );

				if ( ! empty( $classes ) ) {
					$index = array_search( 'nav-tab-active', $classes );
					if ( $index !== - 1 ) {
						unset( $classes[ $index ] );
					}
				}

				if ( $tab === $_GET['ywdpd_discount_type'] ) {
					$classes[] = ( $tab === $_GET['ywdpd_discount_type'] ) ? ' nav-tab-active' : '';
				}

				$active_class = implode( ' ', $classes );
			}

			return $active_class;
		}

		/**
		 * Change the url of pricing and cart rule to specify the type of discount rule.
		 *
		 * @param string $url URL of tab.
		 * @param string $page Panel page.
		 * @param string $tab Current tab.
		 * @param string $sub_tab Current sub tab.
		 * @param string $parent_page Parent page.
		 *
		 * @return string
		 */
		public function custom_tab_url( $url, $page, $tab, $sub_tab, $parent_page ) {
			if ( YITH_WC_Dynamic_Pricing_Admin()->get_panel_page() == $page && in_array( $tab, array(
					'pricing',
					'cart'
				) ) ) {
				$url = add_query_arg( array( 'ywdpd_discount_type' => $tab ), $url );
			}

			return $url;
		}

		/**
		 * show icon if price and cart rule list are empty
		 *
		 * @param $which
		 */
		public function maybe_render_blank_state( $which ) {
			global $post_type;

			if ( 'ywdpd_discount' === $post_type && 'bottom' == $which ) {

				$discount_type = isset( $_GET['ywdpd_discount_type'] ) ? $_GET['ywdpd_discount_type'] : 'pricing';

				if ( 'pricing' === $discount_type ) {
					$attrs = array(

						'icon'       => YITH_YWDPD_DIR . '/assets/icons/price-rule.svg',
						'message'    => __( 'You have no price rule yet.', 'ywdpd' ),
						'submessage' => __( 'Create now your first discount or special price to make your customers happy!', 'ywdpd' ),
						'cta'        => __( 'Create rule', 'ywdpd' ),
						'cta_url'    => add_query_arg( array(
							'post_type'           => 'ywdpd_discount',
							'ywdpd_discount_type' => 'pricing'
						), admin_url( 'post-new.php' ) )
					);
				} else {
					$attrs = array(
						'icon'       => YITH_YWDPD_DIR . '/assets/icons/cart-rule.svg',
						'message'    => __( 'You have no cart rule yet.', 'ywdpd' ),
						'submessage' => __( 'Create now your first cart discount to make your customers happy!', 'ywdpd' ),
						'cta'        => __( 'Create rule', 'ywdpd' ),
						'cta_url'    => add_query_arg( array(
							'post_type'           => 'ywdpd_discount',
							'ywdpd_discount_type' => 'cart'
						), admin_url( 'post-new.php' ) )
					);
				}

				$posts_args = array(
					'numberposts'  => - 1,
					'post_type'    => 'ywdpd_discount',
					'post_status'  => 'publish',
					'meta_key'     => '_discount_type',
					'meta_value'   => $discount_type,
					'meta_compare' => '='
				);

				$posts = get_posts( $posts_args );

				if ( 0 === count( $posts ) ) {

					wc_get_template( '/admin/dynamic-list-table-blank-state.php', $attrs, '', YITH_YWDPD_TEMPLATE_PATH );
					echo '<style type="text/css">#posts-filter .wp-list-table, #posts-filter .tablenav.top, .tablenav.bottom .actions, .wrap .subsubsub  { display: none; } #posts-filter .tablenav.bottom { height: auto; } </style>';
				}

			}
		}
	}

}


/**
 * Unique access to instance of YITH_WC_Dynamic_Discount_Post_Type_Admin class
 *
 * @return YITH_WC_Dynamic_Discount_Post_Type_Admin
 */
function YITH_WC_Dynamic_Discount_Post_Type_Admin() {
	return YITH_WC_Dynamic_Discount_Post_Type_Admin::get_instance();
}
