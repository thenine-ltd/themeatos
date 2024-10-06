<?php
if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_WCPB_PREMIUM' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Implements features of FREE version of YITH WooCommerce Product Bundles
 *
 * @class   YITH_WCPB_Admin_Premium
 * @package YITH WooCommerce Product Bundles
 * @since   1.0.0
 * @author  Yithemes
 */

if ( ! class_exists( 'YITH_WCPB_Admin_Premium' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @since 1.0.0
	 */
	class YITH_WCPB_Admin_Premium extends YITH_WCPB_Admin {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WCPB_Admin_Premium
		 * @since 1.0.0
		 */
		protected static $_instance;

		private $_bundled_order_items = false;

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function __construct() {
			parent::__construct();

			add_filter( 'yith_wcpb_settings_admin_tabs', array( $this, 'settings_premium_tabs' ) );

			add_filter( 'woocommerce_reports_get_order_report_data_args', array( $this, 'woocommerce_reports_get_order_report_data_args' ) );

			/**
			 * set the price for "per item pricing" bundles so price sorting works
			 *
			 * @since 1.1.7
			 */
			add_action( 'woocommerce_process_product_meta_yith_bundle', array( $this, 'save_price_in_pip_bundles' ) );
			add_action( 'init', array( $this, 'sync_bundles' ) );

		}

		/**
		 * set the price for "per item pricing" bundles so price sorting works
		 *
		 * @param $product_id
		 *
		 * @since 1.1.7
		 */
		public function save_price_in_pip_bundles( $product_id ) {
			/** @var WC_Product_Yith_Bundle $product */
			$product = wc_get_product( $product_id );
			if ( $product && $product->is_type( 'yith_bundle' ) ) {
				if ( $product->per_items_pricing ) {
					$price = $product->get_per_item_price_tot();
					$product->set_regular_price( $price );
					$product->save();
					update_post_meta( $product->get_id(), '_price', $price );
				}
			}
		}


		/**
		 * Synchronize the bundle products
		 *
		 * @since 1.1.7
		 */
		public function sync_bundles() {
			$force_sync = isset( $_REQUEST['yith_wcpb_force_sync_bundle_products'] ) && isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'yith-wcpb-sync-pip-prices' );

			if ( $force_sync ) {
				if ( $bundle_term = get_term_by( 'slug', 'yith_bundle', 'product_type' ) ) {
					$product_ids = array_unique( (array) get_objects_in_term( $bundle_term->term_id, 'product_type' ) );
					if ( sizeof( $product_ids ) > 0 ) {
						foreach ( $product_ids as $product_id ) {
							$this->save_price_in_pip_bundles( $product_id );
						}
					}
				}

				if ( isset( $_REQUEST['yith_wcpb_redirect'] ) ) {
					wp_safe_redirect( $_REQUEST['yith_wcpb_redirect'] );
					exit;
				}
			}
		}

		/**
		 * Hide/Show product in bundle in Reports count
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Leanza Francesco <leanzafrancesco@gmail.com>
		 */
		public function woocommerce_reports_get_order_report_data_args( $args ) {
			$show = get_option( 'yith-wcpb-show-bundled-items-in-report' );
			if ( $show && $show == 'yes' ) {
				return $args;
			}

			if ( isset( $args['data']['_qty'] ) || isset( $args['data']['_line_total'] ) ) {
				global $wpdb;
				$bundled_order_items_array = $this->get_bundled_order_item_ids();
				$bundled_order_items       = is_array( $bundled_order_items_array ) ? implode( ',', $bundled_order_items_array ) : '';
				if ( ! ! $bundled_order_items ) {
					/*
					 * this NOT IN exclude products in bundle from selection
					 */
					$not_in_bundled  = "NOT IN ($bundled_order_items) AND '1' =";
					$args['where'][] = array(
						'value'    => '1',
						'key'      => 'order_items.order_item_id',
						'operator' => $not_in_bundled,
					);
				}
			}

			return $args;
		}

		public function get_bundled_order_item_ids() {
			if ( $this->_bundled_order_items === false ) {
				global $wpdb;
				$query   = "SELECT oi.order_item_id FROM {$wpdb->prefix}posts AS posts LEFT JOIN {$wpdb->prefix}woocommerce_order_items AS oi ON posts.ID = oi.order_id  INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta__b ON oi.order_item_id = order_item_meta__b.order_item_id AND order_item_meta__b.meta_key = '_bundled_by'";
				$results = $wpdb->get_results( $query );

				$this->_bundled_order_items = array();

				if ( ! ! $results ) {
					foreach ( $results as $result ) {
						if ( ! empty( $result->order_item_id ) ) {
							$this->_bundled_order_items[] = $result->order_item_id;
						}
					}
				}
			}

			return $this->_bundled_order_items;
		}


		/**
		 * Hide meta in admin order
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Leanza Francesco <leanzafrancesco@gmail.com>
		 */
		public function woocommerce_hidden_order_itemmeta( $hidden ) {
			return array_merge(
				$hidden,
				array(
					'_bundled_by',
					'_per_items_pricing',
					'_yith_bundle_cart_key',
					'_non_bundled_shipping',
					'_cartstamp',
					'_yith_wcpb_hidden',
				)
			);
		}

		/**
		 * Save Product Bandle Data
		 *
		 * @param WC_Product_YITH_Bundle $product
		 *
		 * @access public
		 * @since  1.0.0
		 * @author Leanza Francesco <leanzafrancesco@gmail.com>
		 */
		public function woocommerce_process_product_meta( $product ) {
			parent::woocommerce_process_product_meta( $product );

			if ( $product->is_type( 'yith_bundle' ) ) {
				$meta_data = array(
					'_yith_wcpb_per_item_pricing'        => isset( $_POST['_yith_wcpb_per_item_pricing'] ) && 'yes' === $_POST['_yith_wcpb_per_item_pricing'] ? 'yes' : 'no',
					'_yith_wcpb_non_bundled_shipping'    => isset( $_POST['_yith_wcpb_non_bundled_shipping'] ) && 'yes' === $_POST['_yith_wcpb_non_bundled_shipping'] ? 'yes' : 'no',
					'_yith_wcpb_show_saving_amount'      => isset( $_POST['_yith_wcpb_show_saving_amount'] ) && 'yes' === $_POST['_yith_wcpb_show_saving_amount'] ? 'yes' : 'no',
					'_yith_wcpb_bundle_advanced_options' => isset( $_POST['_yith_wcpb_bundle_advanced_options'] ) ? $_POST['_yith_wcpb_bundle_advanced_options'] : array(),
				);

				foreach ( $meta_data as $key => $value ) {
					$product->update_meta_data( $key, $value );
				}
			}
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

			if ( $product->is_type( 'yith_bundle' ) ) {
				$response['error'] = __( 'You cannot add a bundle product', 'yith-woocommerce-product-bundles' );
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
		 * Add premium tabs
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function settings_premium_tabs( $tabs ) {
			return array(
				'settings'      => __( 'General Settings', 'yith-woocommerce-product-bundles' ),
				'customization' => __( 'Customization', 'yith-woocommerce-product-bundles' ),
			);
		}
	}
}

/**
 * Unique access to instance of YITH_WCPB_Admin_Premium class
 *
 * @return YITH_WCPB_Admin_Premium
 * @deprecated since 1.2.0 use YITH_WCPB_Admin() instead
 * @since      1.0.0
 */
function YITH_WCPB_Admin_Premium() {
	return YITH_WCPB_Admin();
}
