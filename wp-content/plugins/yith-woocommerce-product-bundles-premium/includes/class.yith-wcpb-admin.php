<?php
/**
 * Admin class
 *
 * @author  Yithemes
 * @package YITH WooCommerce Product Bundles
 * @version 1.0.0
 */

if ( ! defined( 'YITH_WCPB' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WCPB_Admin' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since    1.0.0
	 * @author   Leanza Francesco <leanzafrancesco@gmail.com>
	 */
	class YITH_WCPB_Admin {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WCPB_Admin
		 * @since 1.0.0
		 */
		protected static $_instance;

		/**
		 * Plugin version
		 *
		 * @var string
		 * @since 1.0.0
		 */
		public $version = YITH_WCPB_VERSION;

		/**
		 * @var YIT_Plugin_Panel_WooCommerce Panel Object
		 */
		protected $_panel;

		/**
		 * @var string Premium version landing link
		 */
		protected $_premium_landing = 'https://yithemes.com/themes/plugins/yith-woocommerce-product-bundles';

		/**
		 * @var string panel page
		 */
		protected $_panel_page = 'yith_wcpb_panel';

		/**
		 * @var string
		 */
		public $doc_url = 'https://docs.yithemes.com/yith-woocommerce-product-bundles/';


		public $templates = array();


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WCPB_Admin|YITH_WCPB_Admin_Premium
		 * @since 1.0.0
		 */
		public static function get_instance() {
			$self = __CLASS__ . ( class_exists( __CLASS__ . '_Premium' ) ? '_Premium' : '' );

			return ! is_null( $self::$_instance ) ? $self::$_instance : $self::$_instance = new $self();
		}

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {

			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );

			// Add action links
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_WCPB_DIR . '/' . basename( YITH_WCPB_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 3 );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 20 );

			add_filter( 'woocommerce_product_data_tabs', array( $this, 'woocommerce_product_data_tabs' ) );
			add_action( 'woocommerce_product_data_panels', array( $this, 'woocommerce_product_data_panels' ) );

			add_action( 'wp_ajax_yith_wcpb_select_product_box', array( $this, 'select_product_box' ) );
			add_action( 'wp_ajax_yith_wcpb_select_product_box_filtered', array( $this, 'select_product_box_filtered' ) );
			add_action( 'wp_ajax_yith_wcpb_add_product_in_bundle', array( $this, 'add_product_in_bundle' ) );

			add_action( 'woocommerce_admin_process_product_object', array( $this, 'woocommerce_process_product_meta' ) );

			add_action( 'yith_wcpb_admin_bundled_item_options', array( $this, 'print_bundled_item_options' ), 10, 2 );
			add_filter( 'product_type_selector', array( $this, 'product_type_selector' ) );

			// Admin ORDER
			add_filter( 'woocommerce_admin_html_order_item_class', array( $this, 'woocommerce_admin_html_order_item_class' ), 10, 2 );
			add_filter( 'woocommerce_admin_order_item_class', array( $this, 'woocommerce_admin_html_order_item_class' ), 10, 2 );
			add_filter( 'woocommerce_admin_order_item_count', array( $this, 'woocommerce_admin_order_item_count' ), 10, 2 );
			add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'woocommerce_hidden_order_itemmeta' ) );

			add_action( 'wp_ajax_woocommerce_add_order_item', array( $this, 'prevent_adding_bundle_products_in_orders' ), 5 );

			// Premium Tabs
			add_action( 'yith_wcpb_premium_tab', array( $this, 'show_premium_tab' ) );

			add_action( 'yith_wcpb_how_to_tab', array( $this, 'show_how_to_tab' ) );
		}

		/**
		 * Hide bundled_by meta in admin order
		 *
		 * @param array $hidden
		 *
		 * @access public
		 * @return array
		 * @author Leanza Francesco <leanzafrancesco@gmail.com>
		 * @since  1.0.0
		 */
		public function woocommerce_hidden_order_itemmeta( $hidden ) {
			return array_merge( $hidden, array( '_bundled_by', '_cartstamp' ) );
		}

		/**
		 * add CSS class in admin order bundled items
		 *
		 * @param string $class
		 * @param array  $item
		 *
		 * @access public
		 * @return string
		 * @author Leanza Francesco <leanzafrancesco@gmail.com>
		 * @since  1.0.0
		 */
		public function woocommerce_admin_html_order_item_class( $class, $item ) {
			if ( isset( $item['bundled_by'] ) ) {
				return $class . ' yith-wcpb-admin-bundled-item';
			}

			return $class;
		}

		/**
		 * Filter item count in admin orders page
		 *
		 * @param int      $count
		 * @param WC_Order $order
		 *
		 * @access public
		 * @return int|string
		 * @author Leanza Francesco <leanzafrancesco@gmail.com>
		 * @since  1.0.0
		 */
		public function woocommerce_admin_order_item_count( $count, $order ) {
			$counter = 0;
			foreach ( $order->get_items() as $item ) {
				if ( isset( $item['bundled_by'] ) ) {
					$counter += $item['qty'];
				}
			}
			if ( $counter > 0 ) {
				$non_bundled_count = $count - $counter;

				// translators: 1: number of items; 2: number of elements included in the bundle
				return sprintf( _n( '%1$s item [%2$s bundled elements]', '%1$s items [%2$s bundled elements]', $non_bundled_count, 'yith-woocommerce-product-bundles' ), $non_bundled_count, $counter );
			}

			return $count;
		}

		/**
		 * add Product Bundle type in product type selector [in product wc-metabox]
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Leanza Francesco <leanzafrancesco@gmail.com>
		 */
		public function product_type_selector( $types ) {
			$types['yith_bundle'] = _x( 'Product Bundle', 'Admin: type of product', 'yith-woocommerce-product-bundles' );

			return $types;
		}

		/**
		 *
		 */
		/**
		 * Print the Bundled Item Options
		 *
		 * @param WC_Product|bool                   $bundled_product The Bundled Product.
		 * @param array                             $item_data       The item data.
		 * @param int                               $metabox_id      The metabox ID.
		 * @param int                               $bundle_id       The Bundle ID.
		 * @param WC_Product_YITH_Bundle|WC_Product $bundle_product  The Bundle Product or the Product object.
		 *
		 * @return void
		 * @since 1.4.0
		 */
		public function print_bundled_item_options( $bundled_item, $metabox_id ) {
			yith_wcpb_get_view( '/admin/bundled-item-options.php', compact( 'bundled_item', 'metabox_id' ) );
		}

		public function select_product_box() {
			yith_wcpb_get_view( '/admin/select-product-box.php' );
			die();
		}

		public function select_product_box_filtered() {
			yith_wcpb_get_view( '/admin/select-product-box-products.php' );
			die();
		}

		/**
		 * Ajax Called in bundle_options_metabox.js
		 * return the empty form for the item
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Leanza Francesco <leanzafrancesco@gmail.com>
		 */
		public function add_product_in_bundle() {
			$metabox_id = intval( $_POST['id'] );
			$bundle_id  = absint( $_POST['bundle_id'] );
			$product_id = absint( $_POST['product_id'] );
			$product    = wc_get_product( $product_id );
			$response   = array();

			if ( $product instanceof WC_Product && ! $product->is_type( 'simple' ) ) {
				$response['error'] = __( 'You can add only simple products with the FREE version of YITH WooCommerce Product Bundles', 'yith-woocommerce-product-bundles' );
			} else {
				$bundle_product = wc_get_product( $bundle_id );
				$bundled_item   = new YITH_WC_Bundled_Item( $bundle_product, $metabox_id, compact( 'product_id' ) );
				ob_start();
				yith_wcpb_get_view( '/admin/bundled-item.php', compact( 'metabox_id', 'bundled_item' ) );
				$response['html'] = ob_get_clean();
			}
			wp_send_json( $response );
		}

		/**
		 * add Bundle Options Tab [in product wc-metabox]
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Leanza Francesco <leanzafrancesco@gmail.com>
		 */
		public function woocommerce_product_data_tabs( $product_data_tabs ) {
			$product_data_tabs['yith_bundle_options'] = array(
				'label'  => __( 'Bundle Options', 'yith-woocommerce-product-bundles' ),
				'target' => 'yith_bundle_product_data',
				'class'  => array( 'show_if_yith_bundle' ),
			);

			$product_data_tabs['inventory']['class'] = array_merge( $product_data_tabs['inventory']['class'], array( 'show_if_yith_bundle' ) );

			return $product_data_tabs;
		}

		/**
		 * add panel for Bundle Options Tab [in product wc-metabox]
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Leanza Francesco <leanzafrancesco@gmail.com>
		 */
		public function woocommerce_product_data_panels() {
			/** @var WC_Product $product_object */
			global $product_object;

			$bundle_product = $product_object->is_type( 'yith_bundle' ) ? $product_object : false;

			yith_wcpb_get_view( '/admin/bundle-options-tab.php', compact( 'bundle_product', 'product_object' ) );
		}

		/**
		 * Set the product meta before saving the product
		 *
		 * @param WC_Product_YITH_Bundle $product
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Leanza Francesco <leanzafrancesco@gmail.com>
		 */
		public function woocommerce_process_product_meta( $product ) {
			if ( $product->is_type( 'yith_bundle' ) ) {

				$bundle_data = ! empty( $_POST['_yith_wcpb_bundle_data'] ) && is_array( $_POST['_yith_wcpb_bundle_data'] ) ? wp_unslash( $_POST['_yith_wcpb_bundle_data'] ) : array();
				if ( $bundle_data ) {
					$indexed_bundle_data = array();
					$loop                = 1;
					foreach ( $bundle_data as $single_bundle_data ) {
						if ( isset( $single_bundle_data['bp_title'] ) ) {
							$single_bundle_data['bp_title'] = wp_kses_post( $single_bundle_data['bp_title'] );
						}

						if ( isset( $single_bundle_data['bp_description'] ) ) {
							$single_bundle_data['bp_description'] = wp_kses_post( $single_bundle_data['bp_description'] );
						}

						$indexed_bundle_data[ $loop ] = $single_bundle_data;
						$loop ++;
					}
				}

				$product->update_meta_data( '_yith_wcpb_bundle_data', $bundle_data );
				$product->update_meta_data( '_yith_bundle_product_version', YITH_WCPB()->get_bundle_product_version() );
			}
		}

		/**
		 * Action Links
		 * add the action links to plugin admin page
		 *
		 * @param $links | links plugin array
		 *
		 * @return   mixed Array
		 * @return mixed
		 * @use      plugin_action_links_{$plugin_file_name}
		 * @author   Leanza Francesco <leanzafrancesco@gmail.com>
		 * @since    1.0
		 */
		public function action_links( $links ) {
			return yith_add_action_links( $links, $this->_panel_page, defined( 'YITH_WCPB_PREMIUM' ), YITH_WCPB_SLUG );
		}

		/**
		 * plugin_row_meta
		 * add the action links to plugin admin page
		 *
		 * @param $row_meta_args
		 * @param $plugin_meta
		 * @param $plugin_file
		 *
		 * @return   array
		 * @since    1.0
		 * @use      plugin_row_meta
		 */
		public function plugin_row_meta( $row_meta_args, $plugin_meta, $plugin_file ) {
			$init = defined( 'YITH_WCPB_FREE_INIT' ) ? YITH_WCPB_FREE_INIT : YITH_WCPB_INIT;

			if ( $init === $plugin_file ) {
				$row_meta_args['slug']       = YITH_WCPB_SLUG;
				$row_meta_args['is_premium'] = defined( 'YITH_WCPB_PREMIUM' );
			}

			return $row_meta_args;
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @return   void
		 * @since    1.0
		 * @author   Leanza Francesco <leanzafrancesco@gmail.com>
		 * @use      /Yit_Plugin_Panel class
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->_panel ) ) {
				return;
			}

			$admin_tabs_free = array(
				'how-to'  => __( 'How to', 'yith-woocommerce-product-bundles' ),
				'premium' => __( 'Premium Version', 'yith-woocommerce-product-bundles' ),
			);

			$admin_tabs = apply_filters( 'yith_wcpb_settings_admin_tabs', $admin_tabs_free );

			$args = array(
				'create_menu_page' => true,
				'parent_slug'      => '',
				'class'            => yith_set_wrapper_class(),
				'page_title'       => 'WooCommerce Product Bundles',
				'menu_title'       => 'Product Bundles',
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => 'yit_plugin_panel',
				'page'             => $this->_panel_page,
				'admin-tabs'       => $admin_tabs,
				'options-path'     => YITH_WCPB_DIR . '/plugin-options',
			);

			/* === Fixed: not updated theme  === */
			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once 'plugin-fw/lib/yit-plugin-panel-wc.php';
			}

			$this->_panel = new YIT_Plugin_Panel_WooCommerce( $args );
		}

		/**
		 * don't allow adding bundle to orders through "Add products" box in orders
		 *
		 * @since 1.2.21
		 */
		public function prevent_adding_bundle_products_in_orders() {
			if ( isset( $_POST['data'] ) ) {
				$items_to_add = array_filter( wp_unslash( (array) $_POST['data'] ) );

				$bundle_titles = array();
				foreach ( $items_to_add as $item ) {
					if ( ! isset( $item['id'], $item['qty'] ) || empty( $item['id'] ) ) {
						continue;
					}
					$product_id = absint( $item['id'] );
					$product    = wc_get_product( $product_id );
					if ( $product && $product->is_type( 'yith_bundle' ) ) {
						$bundle_titles[] = $product->get_formatted_name();
					}
				}

				if ( $bundle_titles ) {
					// translators: %s is a comma-separated list of bundle products.
					wp_send_json_error( array( 'error' => sprintf( __( 'You are trying to add the following Bundle products to the order: %s. You cannot add Bundle products to orders through this box since this type of products needs to follow the normal WooCommerce "Add-to-cart > Cart > Checkout > Order" process.', 'yith-woocommerce-product-bundles' ), implode( ', ', $bundle_titles ) ) ) );
				}
			}
		}

		public function admin_enqueue_scripts() {
			$screen     = get_current_screen();
			$metabox_js = defined( 'YITH_WCPB_PREMIUM' ) ? 'bundle_options_metabox_premium.js' : 'bundle_options_metabox.js';

			wp_enqueue_style( 'yith-wcpb-admin-styles', YITH_WCPB_ASSETS_URL . '/css/admin.css', array(), YITH_WCPB_VERSION );
			wp_register_style( 'yith-wcpb-popup', YITH_WCPB_ASSETS_URL . '/css/yith-wcpb-popup.css', array(), YITH_WCPB_VERSION );

			wp_register_script( 'yith-wcpb-popup', yit_load_js_file( YITH_WCPB_ASSETS_URL . '/js/yith-wcpb-popup.js' ), array( 'jquery' ), YITH_WCPB_VERSION, true );
			wp_register_script( 'yith_wcpb_bundle_options_metabox', yit_load_js_file( YITH_WCPB_ASSETS_URL . '/js/' . $metabox_js ), array( 'jquery', 'jquery-ui-sortable', 'yith-wcpb-popup' ), YITH_WCPB_VERSION, true );

			if ( 'product' == $screen->id ) {
				wp_enqueue_style( 'yith-wcpb-popup' );
				wp_enqueue_style( 'yith-plugin-fw-fields' );

				wp_enqueue_script( 'yith-plugin-fw-fields' );
				wp_enqueue_script( 'yith_wcpb_bundle_options_metabox' );


				// TODO: to remove. It was kept for backward compatibility
				wp_localize_script(
					'yith_wcpb_bundle_options_metabox',
					'ajax_object',
					array(
						'free_not_simple'     => __( 'You can add only simple products with the FREE version of YITH WooCommerce Product Bundles', 'yith-woocommerce-product-bundles' ),
						'yith_bundle_product' => __( 'You cannot add a bundle product', 'yith-woocommerce-product-bundles' ),
						'minimum_characters'  => apply_filters( 'yith_wcpb_minimum_characters_ajax_search', 3 ),
					)
				);


				wp_localize_script(
					'yith_wcpb_bundle_options_metabox',
					'yith_bundle_opts',
					array(
						'i18n'               => array(
							'addedLabelSingular' => _n( '1 item added', '%s items added', 1, 'yith-woocommerce-product-bundles' ),
							'addedLabelPlural'   => _n( '1 item added', '%s items added', 2, 'yith-woocommerce-product-bundles' ),
						),
						'minimum_characters' => apply_filters( 'yith_wcpb_minimum_characters_ajax_search', 3 ),
					)
				);
			}
		}

		/**
		 * Show premium landing tab
		 *
		 * @return   void
		 * @since    1.0
		 * @author   Leanza Francesco <leanzafrancesco@gmail.com>
		 */
		public function show_premium_tab() {
			$landing = YITH_WCPB_TEMPLATE_PATH . '/premium.php';
			file_exists( $landing ) && require $landing;
		}

		/**
		 * Show premium landing tab
		 *
		 * @return   void
		 * @since    1.0
		 * @author   Leanza Francesco <leanzafrancesco@gmail.com>
		 */
		public function show_how_to_tab() {
			$landing = YITH_WCPB_TEMPLATE_PATH . '/how-to.php';
			file_exists( $landing ) && require $landing;
		}

		/**
		 * Get the premium landing uri
		 *
		 * @return  string The premium landing link
		 * @author  Andrea Grillo <andrea.grillo@yithemes.com>
		 * @since   1.0.0
		 */
		public function get_premium_landing_uri() {
			return $this->_premium_landing;
		}
	}
}

/**
 * Unique access to instance of YITH_WCPB_Admin class
 *
 * @return YITH_WCPB_Admin|YITH_WCPB_Admin_Premium
 * @since 1.0.0
 */
function YITH_WCPB_Admin() {
	return YITH_WCPB_Admin::get_instance();
}
