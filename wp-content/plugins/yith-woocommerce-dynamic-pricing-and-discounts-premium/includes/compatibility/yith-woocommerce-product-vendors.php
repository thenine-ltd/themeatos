<?php
/**
 * Compatibility with YITH WooCommerce Multivendor
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
 * YWDPD_Multivendor class to add compatibility with YITH WooCommerce Multivendor
 *
 * @class   YWDPD_Multivendor
 * @package YITH WooCommerce Dynamic Pricing and Discounts
 * @since   1.0.0
 * @author  YITH
 */
if ( ! class_exists( 'YWDPD_Multivendor' ) ) {

	/**
	 * Class YWDPD_Multivendor
	 */
	class YWDPD_Multivendor {

		/**
		 * Single instance of the class
		 *
		 * @var YWDPD_Multivendor
		 */
		protected static $instance;


		/**
		 * Returns single instance of the class
		 *
		 * @return YWDPD_Multivendor
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
		 * Initialize class and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		public function __construct() {


			//PRICE OPTIONS
			add_filter( 'ywdpd_vendor_options_rule_for', array( $this, 'add_pricing_rule_option' ) );
			add_filter( 'ywdpd_vendor_options_exclude_rule_for', array( $this, 'add_pricing_rule_option' ) );
			add_filter( 'ywdpd_vendor_apply_adjustment_rule_for', array( $this, 'add_pricing_rule_option' ) );
			add_filter( 'ywdpd_vendor_apply_exclude_adjustment_rule_for', array( $this, 'add_pricing_rule_option' ) );
			add_filter( 'yit_ywdpd_pricing_rules_options', array( $this, 'add_pricing_option_rule_for' ) );



			add_filter( 'ywdpd_cart_rules_product_include_fields', array( $this, 'add_cart_rule_option' ) );
			add_filter( 'ywdpd_cart_rules_product_exclude_fields', array( $this, 'add_cart_rule_option' ) );


			// CART RULES
			add_filter( 'ywdpd_valid_product_cart_condition', array(
				$this,
				'yith_vendor_valid_require_vendor_condition'
			), 20, 3 );
			add_filter( 'ywdpd_valid_product_exclude_cart_condition', array(
				$this,
				'yith_vendor_valid_exclude_vendor_condition'
			), 20, 3 );
			add_filter( 'yith_ywdpd_admin_localize', array( $this, 'add_localize_params' ) );


			// panel type category search
			add_action( 'wp_ajax_ywdpd_vendor_search', array( $this, 'json_search_vendors' ) );
			add_action( 'wp_ajax_nopriv_ywdpd_vendor_search', array( $this, 'json_search_vendors' ) );

			//SPECIAL OFFER IN POPUP
			add_filter( 'ywdpd_special_offer_item_type', array( $this, 'add_vendor_in_item_type' ), 20, 3 );
			add_filter( 'ywdpd_get_product_taxonomy_ids_to_include', array( $this, 'add_vendor_ids_in_item' ), 20, 3 );
			add_filter( 'ywdpd_item_popup_args', array( $this, 'add_vendor_args_in_item' ), 20, 2 );
			add_filter( 'ywdpd_get_product_ids_to_exclude', array( $this, 'return_product_ids_to_exclude' ), 20, 3 );

		}


		/**
		 * @param array $rules
		 *
		 * @return array
		 */
		public function add_pricing_option_rule_for( $rules ) {
			$new_rule = array();
			foreach ( $rules as $key => $rule ) {
				$new_rule[ $key ] = $rule;

				if ( $key == 'rule_for' || $key == 'rule_apply_adjustment_discount_for' ) {
					$new_rule[ $key ]['vendor_list'] = __( 'Specific vendors', 'ywdpd' );

				}

				if ( 'exclude_rule_for' == $key || 'exclude_apply_adjustment_rule_for' == $key ) {
					$new_rule[ $key ]['vendor_list_excluded'] = __( 'Specific vendors', 'ywdpd' );
				}
			}

			return $new_rule;
		}

		/**
		 * Add pricing rules options in settings panels
		 *
		 * @return array
		 */
		public function add_pricing_rule_option() {

			$current_filter = current_filter();
			if ( 'ywdpd_vendor_options_rule_for' === $current_filter ) {
				$option = array(
					'type'     => 'ajax-terms',
					'data'     => array(
						'taxonomy'    => YITH_Vendors()->get_taxonomy_name(),
						'placeholder' => __( 'Search for a vendor', 'ywdpd' ),
					),
					'label'    => __( 'Apply rule to:', 'ywdpd' ),
					'desc'     => __( 'Search the vendor(s) to include in the rule', 'ywdpd' ),
					'deps'     => array(
						'ids'    => '_rule_for',
						'values' => 'vendor_list',
						'type'   => 'hideNow'
					),
					'multiple' => true
				);
			} elseif ( 'ywdpd_vendor_options_exclude_rule_for' === $current_filter ) {
				$option = array(
					'type'     => 'ajax-terms',
					'data'     => array(
						'taxonomy'    => YITH_Vendors()->get_taxonomy_name(),
						'placeholder' => __( 'Search for a vendor', 'ywdpd' ),
					),
					'label'    => __( 'Choose which vendor(s) to exclude', 'ywdpd' ),
					'desc'     => __( 'Search the vendor(s) to exclude from the rule', 'ywdpd' ),
					'deps'     => array(
						'ids'    => '_active_exclude,_exclude_rule_for',
						'values' => 'yes,vendor_list_excluded',
						'type'   => 'hideNow'
					),
					'multiple' => true
				);
			} elseif ( 'ywdpd_vendor_apply_adjustment_rule_for' === $current_filter ) {
				$option = array(
					'type'     => 'ajax-terms',
					'data'     => array(
						'taxonomy'    => YITH_Vendors()->get_taxonomy_name(),
						'placeholder' => __( 'Search for a vendor', 'ywdpd' ),
					),
					'label'    => __( 'Choose which vendor(s) to include', 'ywdpd' ),
					'desc'     => __( 'Search the vendor(s) to include in this discount', 'ywdpd' ),
					'deps'     => array(
						'ids'    => '_active_apply_discount_to,_rule_apply_adjustment_discount_for',
						'values' => 'yes,vendor_list',
						'type'   => 'hideNow'
					),
					'multiple' => true
				);
			} else {
				$option = array(
					'type'        => 'ajax-terms',
					'data'        => array(
						'taxonomy'    => YITH_Vendors()->get_taxonomy_name(),
						'placeholder' => __( 'Search for a vendor', 'ywdpd' ),
					),
					'label'       => __( 'Choose which vendor(s) to exclude', 'ywdpd' ),
					'placeholder' => __( 'Search for a vendor', 'ywdpd' ),
					'desc'        => __( 'Search the vendor(s) to exclude from this discount', 'ywdpd' ),
					'deps'        => array(
						'ids'    => '_active_apply_adjustment_to_exclude,_exclude_apply_adjustment_rule_for',
						'values' => 'yes,vendor_list_excluded',
						'type'   => 'hideNow'
					),
					'multiple'    => true
				);
			}

			return $option;
		}


		/**
		 * Add pricing rules options in settings panels
		 *
		 * @param $rules
		 *
		 * @return array
		 */
		public function add_cart_rule_option( $rules ) {

			if ( 'ywdpd_cart_rules_product_include_fields' === current_filter() ) {

				$options = array(
					array(
						'id'        => 'enable_require_product_vendors',
						'name'      => __( 'Require specific vendors in cart', 'ywdpd' ),
						'desc'      => __( 'Enable to require products of specific vendors in cart to apply the discount', 'ywdpd' ),
						'default'   => 'no',
						'type'      => 'onoff',
						'class_row' => 'product require_product',
						'class'     => 'ywdpd_enable_require_product_vendors'
					),
					array(
						'id'        => 'require_product_vendors_list',
						'name'      => __( 'Include a list of vendors', 'ywdpd' ),
						'desc'      => __( 'Choose which vendors are required in cart to apply the discount', 'ywdpd' ),
						'default'   => '',
						'type'      => 'ajax-terms',
						'data'      => array(
							'taxonomy'    => YITH_Vendors()->get_taxonomy_name(),
							'placeholder' => __( 'Search for vendor', 'ywdpd' ),
						),
						'class_row' => 'product require_product enable_require_product_vendors_list',
						'multiple'  => true,
					),
				);
				$rules   = array_merge( $rules, $options );
			} elseif ( 'ywdpd_cart_rules_product_exclude_fields' === current_filter() ) {
				$options = array(
					array(
						'id'        => 'enable_exclude_product_vendors',
						'name'      => __( 'Exclude specific vendors from discount validation', 'ywdpd' ),
						'desc'      => __( 'Enable if you want to exclude specific vendors to this cart discount validation', 'ywdpd' ),
						'default'   => 'no',
						'type'      => 'onoff',
						'class_row' => 'product exclude_product',
						'class'     => 'ywdpd_enable_exclude_product_vendors'
					),
					array(
						'id'        => 'exclude_product_vendors_list',
						'name'      => __( 'Exclude a list of vendors', 'ywdpd' ),
						'desc'      => __( 'Choose which product vendors to exclude from this cart validation', 'ywdpd' ),
						'default'   => '',
						'type'      => 'ajax-terms',
						'data'      => array(
							'taxonomy'    => YITH_Vendors()->get_taxonomy_name(),
							'placeholder' => __( 'Search for vendor', 'ywdpd' ),
						),
						'class_row' => 'product exclude_product enable_exclude_product_vendors_list',
						'multiple'  => true,
					),
				);
				$rules   = array_merge( $rules, $options );
			}

			return $rules;
		}


		/**
		 * @param bool $is_valid
		 * @param array $condition
		 * @param array $conditions
		 *
		 * @return bool
		 */
		public function yith_vendor_valid_require_vendor_condition( $is_valid, $condition, $conditions ) {

			$type_check = ! empty( $condition['product_type'] ) ? $condition['product_type'] : '';
			if ( 'require_product' === $type_check ) {
				$require_product_vendor = isset( $condition['enable_require_product_vendors'] ) && yith_plugin_fw_is_true( $condition['enable_require_product_vendors'] );
				$vendor_to_check        = isset( $condition['require_product_vendors_list'] ) ? $condition['require_product_vendors_list'] : array();

				if ( $require_product_vendor && count( $vendor_to_check ) > 0 ) {

					if ( ! is_null( WC()->cart ) && ! WC()->cart->is_empty() ) {
						$vendor_in_cart = array();

						foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {

							$vendor = yith_get_vendor( $cart_item['data'], 'product' );

							if ( $vendor->is_valid() && ! in_array( $vendor->id, $vendor_in_cart, true ) ) {
								$vendor_in_cart[] = $vendor->id;
							}
						}
					}

					$intersect = array_intersect( $vendor_to_check, $vendor_in_cart );

					$is_valid = count( $intersect ) > 0;
				}
			}

			return $is_valid;
		}

		/**
		 * @param bool $is_excluded
		 * @param array $conditions
		 * @param WC_Product
		 *
		 * @return bool
		 */
		public function yith_vendor_valid_exclude_vendor_condition( $is_excluded, $conditions, $product ) {

			foreach ( $conditions as $condition ) {

				$type = ! empty( $condition['condition_for'] ) ? $condition['condition_for'] : '';
				if ( 'product' === $type ) {

					$type_check = isset( $condition['product_type'] ) ? $condition['product_type'] : '';

					if ( 'exclude_product' === $type_check ) {
						$exclude_product_vendor = yith_plugin_fw_is_true( $condition['enable_exclude_product_vendors'] );
						$vendor_id_to_exclude   = isset( $condition['exclude_product_vendors_list'] ) ? $condition['exclude_product_vendors_list'] : array();

						if ( $exclude_product_vendor && count( $vendor_id_to_exclude ) > 0 ) {

							$vendor = yith_get_vendor( $product, 'product' );

							if ( $vendor->is_valid() && in_array( $vendor->id, $vendor_id_to_exclude ) ) {
								$is_excluded = true;
								break;
							}
						}

					}
				}
			}

			return $is_excluded;
		}

		/**
		 * Add localize params to javascript
		 *
		 * @param $params
		 *
		 * @return mixed
		 */
		public function add_localize_params( $params ) {
			$params['search_vendor_nonce'] = wp_create_nonce( 'search-vendor' );

			return $params;
		}


		public function json_search_vendors() {

			check_ajax_referer( 'search-products', 'security' );

			ob_start();

			$term = (string) wc_clean( stripslashes( $_GET['term'] ) );

			if ( empty( $term ) ) {
				die();
			}
			global $wpdb;
			$terms = $wpdb->get_results( 'SELECT name, slug, wpt.term_id FROM ' . $wpdb->prefix . 'terms wpt, ' . $wpdb->prefix . 'term_taxonomy wptt WHERE wpt.term_id = wptt.term_id AND wptt.taxonomy = "' . YITH_Vendors()->get_taxonomy_name() . '" and wpt.name LIKE "%' . $term . '%" ORDER BY name ASC;' );

			$found_vendors = array();

			if ( $terms ) {
				foreach ( $terms as $cat ) {
					$found_vendors[ $cat->term_id ] = ( $cat->name ) ? $cat->name : 'ID: ' . $cat->slug;
				}
			}

			wp_send_json( $found_vendors );
		}

		/**
		 * @param $pricing_options
		 *
		 * @return mixed
		 */
		public function add_vendor_pricing_options( $pricing_options ) {

			$start         = $pricing_options['tabs']['settings']['fields'];
			$position      = array_search( 'rule_for_tags_list', array_keys( $start ) );
			$begin         = array_slice( $start, 0, $position + 1 );
			$end           = array_slice( $start, $position );
			$vendor_items1 = array(
				'apply_to_vendors_list' => array(
					'label'       => __( 'Search for a vendor', 'ywdpd' ),
					'type'        => 'vendors',
					'desc'        => '',
					'placeholder' => __( 'Search for a vendor', 'ywdpd' ),
					'deps'        => array(
						'ids'    => '_rule_for',
						'values' => 'vendor_list',
					),
				),
			);
			$start         = $begin + $vendor_items1 + $end;
			$position      = array_search( 'exclude_rule_for_tags_list', array_keys( $start ) );
			$begin         = array_slice( $start, 0, $position + 1 );
			$end           = array_slice( $start, $position );

			$vendor_items2 = array(
				'apply_to_vendors_list_excluded' => array(
					'label'       => __( 'Search for a vendor', 'ywdpd' ),
					'type'        => 'vendors',
					'desc'        => '',
					'placeholder' => __( 'Search for a vendor', 'ywdpd' ),
					'deps'        => array(
						'ids'    => '_active_exclude,_exclude_rule_for',
						'values' => 'yes,vendor_list_excluded',
					),
				),
			);
			$start         = $begin + $vendor_items2 + $end;

			$position                                      = array_search( 'apply_adjustment_tags_list', array_keys( $start ) );
			$begin                                         = array_slice( $start, 0, $position + 1 );
			$end                                           = array_slice( $start, $position );
			$vendor_items                                  = array(
				'apply_adjustment_vendor_list' => array(
					'label'       => __( 'Search for a vendor', 'ywdpd' ),
					'type'        => 'vendors',
					'desc'        => '',
					'placeholder' => __( 'Search for a vendor', 'ywdpd' ),
					'deps'        => array(
						'ids'    => '_active_apply_discount_to,_rule_apply_adjustment_discount_for',
						'values' => 'yes,vendor_list',
					),
				),
			);
			$start                                         = $begin + $vendor_items + $end;
			$position                                      = array_search( 'apply_adjustment_tags_list_excluded', array_keys( $start ) );
			$begin                                         = array_slice( $start, 0, $position + 1 );
			$end                                           = array_slice( $start, $position );
			$vendor_items3                                 = array(
				'apply_adjustment_vendor_list_excluded' => array(
					'label'       => __( 'Search for a vendor', 'ywdpd' ),
					'type'        => 'vendors',
					'desc'        => '',
					'placeholder' => __( 'Search for a vendor', 'ywdpd' ),
					'deps'        => array(
						'ids'    => '_active_apply_adjustment_to_exclude,_exclude_apply_adjustment_rule_for',
						'values' => 'yes,vendor_list_excluded',
					),
				),
			);
			$begin                                         = $begin + $vendor_items3 + $end;
			$pricing_options['tabs']['settings']['fields'] = $begin;

			return $pricing_options;

		}

		/**
		 * @auhtor YITH
		 *
		 * @param string $type
		 * @param array $rule
		 * @param string $apply_special_offer_to
		 *
		 * @return string
		 * @since 2.1
		 */
		public function add_vendor_in_item_type( $type, $rule, $apply_special_offer_to ) {

			if ( 'vendor_list' === $apply_special_offer_to ) {
				$type = 'product_vendor';
			}

			return $type;
		}

		/**
		 * @param array $item_ids
		 * @param array $rule
		 * @param string $apply_special_offer_to
		 *
		 * @return mixed
		 */
		public function add_vendor_ids_in_item( $item_ids, $rule, $apply_special_offer_to ) {
			if ( 'vendor_list' === $apply_special_offer_to ) {
				$item_ids = isset( $rule['apply_adjustment_vendor_list'] ) ? $rule['apply_adjustment_vendor_list'] : array();

			}

			return $item_ids;
		}

		/**
		 * @param array $args
		 * @param string $type
		 *
		 * @return mixed
		 */
		public function add_vendor_args_in_item( $args, $type ) {
			if ( 'product_vendor' === $type ) {
				$args = array(
					'item_class'    => 'product_taxonomy',
					'taxonomy_name' => YITH_Vendor::$taxonomy,
				);
			}

			return $args;
		}

		public function return_product_ids_to_exclude( $product_ids, $rule, $exclude_for ) {
		
			if ( 'vendor_list_excluded' === $exclude_for ) {
				$vendor_ids = isset( $rule['apply_adjustment_vendor_list_excluded'] ) ? $rule['apply_adjustment_vendor_list_excluded'] : array();
				if ( is_array( $vendor_ids ) && count( $vendor_ids ) > 0 ) {
					$tax_query = WC()->query->get_tax_query( array(
							array(
								'taxonomy' => YITH_Vendor::$taxonomy,
								'terms'    => array_values( $vendor_ids ),
								'operator' => 'IN',
							)
						)
					);

					$product_ids = get_posts( array(
							'numberposts' => - 1,
							'post_type'   => array( 'product' ),
							'post_status' => 'publish',
							'tax_query'   => $tax_query,
							'fields'      => 'ids'
						)
					);
				}
			}

			return $product_ids;
		}

	}

}

/**
 * Unique access to instance of YWDPD_Multivendor class
 *
 * @return YWDPD_Multivendor
 */
function YWDPD_Multivendor() {
	return YWDPD_Multivendor::get_instance();
}

YWDPD_Multivendor();
