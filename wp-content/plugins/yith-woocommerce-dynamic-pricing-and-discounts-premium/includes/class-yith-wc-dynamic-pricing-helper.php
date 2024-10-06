<?php
/**
 * Helper function for YITH WooCommerce Dynamic Pricing and Discounts
 *
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.0.0
 * @version 1.6.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWDPD_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Helper function for YITH WooCommerce Dynamic Pricing and Discounts
 *
 * @class   YITH_WC_Dynamic_Pricing
 * @package YITH WooCommerce Dynamic Pricing and Discounts
 * @since   1.0.0
 * @author  YITH
 */
if ( ! class_exists( 'YITH_WC_Dynamic_Pricing_Helper' ) ) {

	/**
	 * Class YITH_WC_Dynamic_Pricing_Helper
	 */
	class YITH_WC_Dynamic_Pricing_Helper {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Dynamic_Pricing_Helper
		 */
		protected static $instance;
		/**
		 * Product counters.
		 *
		 * @var array
		 */
		public $product_counters = array();
		/**
		 * Variation counters.
		 *
		 * @var array
		 */
		public $variation_counters = array();
		/**
		 * Categories counter.
		 *
		 * @var array
		 */
		public $categories_counter = array();
		/**
		 * Categories on cart.
		 *
		 * @var array
		 */
		public $cart_categories = array();
		/**
		 * Tags counters.
		 *
		 * @var array
		 */
		public $tags_counter = array();
		/**
		 * Tags on cart.
		 *
		 * @var array
		 */
		public $cart_tags = array();
		/**
		 * Discount to apply.
		 *
		 * @var array
		 */
		public $discounts_to_apply = array();
		/**
		 * Product to apply.
		 *
		 * @var array
		 */
		private $valid_product_to_apply = array();
		/**
		 * Gift product to apply.
		 *
		 * @var array
		 */
		private $valid_gift_product_to_apply = array();
		/**
		 * Valid product to adjustment.
		 *
		 * @var array
		 */
		private $valid_product_to_adjustment = array();

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Dynamic_Pricing_Helper
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
			add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'load_counters' ), 98 );
			add_action( 'yith_wacp_before_popup_content', array( $this, 'load_counters' ), 5 );
			add_action( 'init', array( $this, 'register_post_type' ) );
		}

		/**
		 * Register discount post type.
		 *
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		public function register_post_type() {

			$name          = isset( $_GET['ywdpd_discount_type'] ) && 'cart' === $_GET['ywdpd_discount_type'] ? _x( 'Cart Rules', 'Post Type General Name', 'ywdpd' ) : _x( 'Discounts & Price Rules', 'Post Type General Name', 'ywdpd' );
			$singular_name = isset( $_GET['ywdpd_discount_type'] ) && 'cart' === $_GET['ywdpd_discount_type'] ? _x( 'Cart Rule', 'Post Type General Name', 'ywdpd' ) : _x( 'Discounts & Price Rule', 'Post Type General Name', 'ywdpd' );
			$labels        = array(
				'name'               => $name,
				'singular_name'      => $singular_name,
				'menu_name'          => $singular_name,
				'parent_item_colon'  => __( 'Parent Item:', 'ywdpd' ),
				'all_items'          => __( 'All Discount Rules', 'ywdpd' ),
				'view_item'          => __( 'View Discount Rules', 'ywdpd' ),
				'add_new_item'       => __( '+ Add Rule', 'ywdpd' ),
				'add_new'            => __( '+ Add Rule', 'ywdpd' ),
				'edit_item'          => $singular_name,
				'update_item'        => __( 'Update Discount Rule', 'ywdpd' ),
				'search_items'       => __( 'Search Discount Rule', 'ywdpd' ),
				'not_found'          => __( 'Not found', 'ywdpd' ),
				'not_found_in_trash' => __( 'Not found in Trash', 'ywdpd' ),
			);
			$args          = array(
				'label'               => __( 'ywdpd_discount', 'ywdpd' ),
				'labels'              => $labels,
				'supports'            => array( 'title' ),
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'exclude_from_search' => true,
				'capability_type'     => 'post',
			);

			register_post_type( 'ywdpd_discount', $args );

		}


		/**
		 * Load all the counters
		 *
		 * @return void
		 */
		public function load_counters() {
			if ( empty( WC()->cart->cart_contents ) ) {
				return;
			}

			$this->reset_counters();

			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				/**
				 * @var WC_Product $product
				 */
				$product = $cart_item['data'];
				if ( 'variation' === $product->get_type() ) {
					$product_id   = $product->get_parent_id();
					$variation_id = $product->get_id();
				} else {
					$product_id   = $product->get_id();
					$variation_id = false;
				}
				$quantity = $cart_item['quantity'];

				if ( $variation_id ) {
					$this->product_counters[ $product_id ] = isset( $this->product_counters[ $product_id ] ) ?
						$this->product_counters[ $product_id ] + $quantity : $quantity;

					$this->variation_counters[ $variation_id ] = isset( $this->variation_counters[ $variation_id ] ) ?
						$this->variation_counters[ $variation_id ] + $quantity : $quantity;
				} else {
					$this->product_counters[ $product_id ] = isset( $this->product_counters[ $product_id ] ) ?
						$this->product_counters[ $product_id ] + $quantity : $quantity;
				}

				$categories = wp_get_post_terms( $product_id, 'product_cat' );
				foreach ( $categories as $category ) {
					$this->categories_counter[ $category->term_id ] = isset( $this->categories_counter[ $category->term_id ] ) ?
						$this->categories_counter[ $category->term_id ] + $quantity : $quantity;

					$this->cart_categories[] = $category->term_id;
				}

				$tags = wp_get_post_terms( $product_id, 'product_tag' );
				foreach ( $tags as $tag ) {
					$this->tags_counter[ $tag->term_id ] = isset( $this->tags_counter[ $tag->term_id ] ) ?
						$this->tags_counter[ $tag->term_id ] + $quantity : $quantity;

					$this->cart_tags[] = $tag->term_id;
				}
			}
		}

		/**
		 * Reset all counters
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		private function reset_counters() {
			$this->categories_counter = array();
			$this->cart_categories    = array();
			$this->tags_counter       = array();
			$this->cart_tags          = array();
			$this->product_counters   = array();
			$this->variation_counters = array();
		}

		/**
		 * Get all user role list for select field
		 *
		 * @access public
		 * @return array
		 */
		public function get_roles() {
			global $wp_roles;
			$roles = array();

			foreach ( $wp_roles->get_names() as $key => $role ) {
				$roles[ $key ] = translate_user_role( $role );
			}

			return array_merge(
				array(
					''      => __( 'All', 'ywdpd' ),
					'guest' => __(
						'Guest',
						'ywdpd'
					),
				),
				$roles
			);
		}

		/**
		 * Validate date
		 *
		 * @access public
		 *
		 * @param string $from From date.
		 * @param string $to To date.
		 *
		 * @return mixed
		 */
		public function validate_schedule( $from, $to ) {

			if ( '' === $from && '' === $to ) {
				return true;
			}

			try {

				$return     = true;
				$timezone   = get_option( 'timezone_string' );
				$zone       = '' !== $timezone ? new DateTimeZone( $timezone ) : '';
				$gmt_offset = get_option( 'gmt_offset' );
				$ve         = $gmt_offset > 0 ? '+' : '-';

				if ( '' !== $zone ) {
					$today_dt = new DateTime( 'now', $zone );
				} else {
					$today_dt = new DateTime( '@' . strtotime( 'now ' . $ve . absint( $gmt_offset ) . ' HOURS' ) );
				}

				if ( '' !== $from ) {

					if ( '' !== $zone ) {
						$from_dt = new DateTime( $from, $zone );
					} else {
						$from_dt = new DateTime( '@' . strtotime( $from . ' ' . $ve . absint( $gmt_offset ) . ' HOURS' ) );
					}

					if ( $today_dt < $from_dt ) {
						$return = false;
					}
				}

				if ( $return && '' !== $to ) {

					if ( '' !== $zone ) {
						$to_dt = new DateTime( $to, $zone );
					} else {
						$to_dt = new DateTime( '@' . strtotime( $to . ' ' . $ve . absint( $gmt_offset ) . ' HOURS' ) );
					}

					if ( $today_dt > $to_dt ) {
						$return = false;
					}
				}
			} catch ( Exception $e ) {
				return false;
			}

			return apply_filters( 'ywsbs_validate_schedule', $return, $from, $to );

		}

		/**
		 * Validate user
		 *
		 * @access public
		 *
		 * @param array $rule
		 *
		 * @return bool
		 */
		public function validate_user( $rule ) {

			$to_return = false;

			$type               = $rule['user_rules'];
			$is_exclude_enabled = isset( $rule['enable_user_rule_exclude'] ) && yith_plugin_fw_is_true( $rule['enable_user_rule_exclude'] );
			$is_in_exclusion    = false;
			$user               = is_user_logged_in() ? wp_get_current_user() : false;
			$roles              = $user ? $user->roles : array( 'guest' );

			if ( $is_exclude_enabled && 'customers_list' !== $type ) {

				$exc_type = ! empty( $rule['user_rule_exclude'] ) ? $rule['user_rule_exclude'] : '';

				if ( 'specific_customers' === $exc_type ) {
					$user_list = ! empty( $rule['user_rules_customers_list_excluded'] ) ? $rule['user_rules_customers_list_excluded'] : array();

					if ( $user instanceof WP_User ) {
						if ( in_array( $user->ID, $user_list ) ) {
							$is_in_exclusion = true;
						}
					}
				} elseif ( 'specific_roles' === $exc_type ) {
					$user_role_list = ! empty( $rule['user_rules_role_list_excluded'] ) ? $rule['user_rules_role_list_excluded'] : array();
					if ( ! empty( $user_role_list ) ) {
						$intersect = array_intersect( $roles, $user_role_list );

						if ( count( $intersect ) > 0 ) {
							$is_in_exclusion = true;
						}
					}
				}
			}

			if ( ! $is_in_exclusion ) {

				if ( 'everyone' == $type ) {
					$to_return = true;
				} else {

					if ( 'customers_list' === $type ) {
						$user_list = ! empty( $rule['user_rules_customers_list'] ) ? $rule['user_rules_customers_list'] : array();
						if ( $user instanceof WP_User ) {
							if ( in_array( $user->ID, $user_list ) ) {
								$to_return = true;
							}
						} else {
							$to_return = false;
						}
					} elseif ( 'role_list' === $type ) {
						$user_role_list = ! empty( $rule['user_rules_role_list'] ) ? $rule['user_rules_role_list'] : array();
						if ( ! empty( $user_role_list ) ) {
							$intersect = array_intersect( $roles, $user_role_list );

							if ( count( $intersect ) > 0 ) {
								$to_return = true;
							}
						}
					}
				}
			}

			return apply_filters( 'yit_ywdpd_validate_user', $to_return, $type, $rule );
		}

		public function validate_apply_to_field( $type, $rule ) {

			$validate = false;
			if ( 'all_products' === $type ) {
				$validate = true;
			} elseif ( 'specific_products' === $type ) {
				$validate = ! empty( $rule['rule_for_products_list'] );
			} elseif ( 'specific_categories' === $type ) {
				$validate = ! empty( $rule['rule_for_categories_list'] );
			} elseif ( 'specific_tag' == $type ) {
				$validate = ! empty( $rule['rule_for_tags_list'] );
			} elseif ( 'vendor_list' == $type ) {
				$validate = ! empty( $rule['apply_to_vendors_list'] );
			}

			return apply_filters( 'ywdpd_validate_apply_to_field', $validate, $type, $rule );
		}

		public function validate_apply_adjustment_to_field( $type, $rule ) {

			$validate = false;
			if ( 'all_products' === $type ) {
				$validate = true;
			} elseif ( 'specific_products' === $type ) {
				$validate = ! empty( $rule['apply_adjustment_products_list'] );
			} elseif ( 'specific_categories' === $type ) {
				$validate = ! empty( $rule['apply_adjustment_categories_list'] );
			} elseif ( 'specific_tag' == $type ) {
				$validate = ! empty( $rule['apply_adjustment_tags_list'] );
			} elseif ( 'vendor_list' == $type ) {
				$validate = ! empty( $rule['apply_adjustment_vendor_list'] );
			}

			return apply_filters( 'ywdpd_validate_apply_adjustment_to_field', $validate, $type, $rule );
		}

		public function validate_apply_to_field_excluded_products( $type, $rule ) {

			$validate = false;
			if ( 'specific_products' === $type ) {
				$validate = ! empty( $rule['exclude_rule_for_products_list'] );
			} elseif ( 'specific_categories' === $type ) {
				$validate = ! empty( $rule['exclude_rule_for_categories_list'] );
			} elseif ( 'specific_tag' === $type ) {
				$validate = ! empty( $rule['exclude_rule_for_tags_list'] );
			} elseif ( 'vendor_list_excluded' == $type ) {
				$validate = ! empty( $rule['apply_to_vendors_list_excluded'] );
			}

			return apply_filters( 'ywdpd_validate_apply_to_field_excluded', $validate, $type, $rule );

		}

		public function validate_apply_adjustment_to_field_excluded_products( $type, $rule ) {

			$validate = false;
			if ( 'specific_products' === $type ) {
				$validate = ! empty( $rule['apply_adjustment_products_list_excluded'] );
			} elseif ( 'specific_categories' === $type ) {
				$validate = ! empty( $rule['apply_adjustment_categories_list_excluded'] );
			} elseif ( 'specific_tag' === $type ) {
				$validate = ! empty( $rule['apply_adjustment_tags_list_excluded'] );
			} elseif ( 'vendor_list_excluded' == $type ) {
				$validate = ! empty( $rule['apply_adjustment_vendor_list_excluded'] );
			}

			return apply_filters( 'ywdpd_validate_apply_adjustment_to_field_excluded', $validate, $type, $rule );

		}

		public function get_cart_item_key_adjustment_to( $rule ) {

			$cart_item_keys = array();
			if ( ! is_null( WC()->cart ) && ! WC()->cart->is_empty() ) {

				foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {

					$valid_to_apply = $this->valid_product_to_adjust( $rule, $cart_item );

					if ( $valid_to_apply ) {
						$cart_item_keys[] = $cart_item_key;
					}
				}
			}

			return $cart_item_keys;
		}

		/**
		 * Check if the cart item has the bulk applied.
		 *
		 * @param string $cart_item_key Cart item key.
		 *
		 * @return bool
		 */
		public function has_a_bulk_applied( $cart_item_key ) {

			if ( ! isset( WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'] ) ) {
				return false;
			}

			$ywdpd_discounts = WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'];
			foreach ( $ywdpd_discounts as $ywdpd_discount ) {
				if ( isset( $ywdpd_discount['discount_mode'] ) && 'bulk' === $ywdpd_discount['discount_mode'] ) {
					return true;
				}
			}

			return false;

		}

		/**
		 * Validate product apply_to
		 *
		 * @access public
		 *
		 * @param string $key_rule Key rule.
		 * @param array  $rule Rule.
		 * @param string $cart_item_key Cart item key.
		 * @param array  $cart_item Cart item.
		 *
		 * @return mixed
		 */
		public function validate_apply_to( $key_rule, $rule, $cart_item_key, $cart_item ) {

			$is_valid = $this->validate_apply_to_cart_item( $rule, $cart_item_key, $cart_item );

			if ( $is_valid ) {

				// If the apply for is valid, check if is set the apply_adjustment_to option
				$is_active_apply_adjustment = isset( $rule['active_apply_discount_to'] ) && yith_plugin_fw_is_true( $rule['active_apply_discount_to'] );
				$is_valid_also_for_adjust   = $this->valid_product_to_adjust( $rule, $cart_item );

				$discount = array();

				$quantity = $this->check_quantity( $rule, $cart_item );

				$product_id = ( ! empty( $cart_item['variation_id'] ) && $cart_item['variation_id'] > 0 ) ? $cart_item['variation_id'] : $cart_item['product_id'];

				$product = wc_get_product( $product_id );

				if ( ! $product ) {
					return false;
				}

				$discount['key'] = $key_rule;

				$discount['status']    = 'processing';
				$discount['exclusive'] = isset( $rule['no_apply_with_other_rules'] ) && yith_plugin_fw_is_true( $rule['no_apply_with_other_rules'] );

				$discount['onsale'] = isset( $rule['disable_on_sale'] ) && ! yith_plugin_fw_is_true( $rule['disable_on_sale'] );

				remove_filter(
					'woocommerce_product_get_price',
					array(
						YITH_WC_Dynamic_Pricing_Frontend(),
						'get_price',
					)
				);
				remove_filter(
					'woocommerce_product_variation_get_price',
					array(
						YITH_WC_Dynamic_Pricing_Frontend(),
						'get_price',
					)
				);

				$tax_mode                  = is_callable(
					array(
						WC()->cart,
						'get_tax_price_display_mode',
					)
				) ? WC()->cart->get_tax_price_display_mode() : WC()->cart->tax_display_cart;
				$discount['default_price'] = ( $tax_mode === 'excl' ) ? yit_get_price_excluding_tax( $product ) : yit_get_price_including_tax( $product );

				add_filter(
					'woocommerce_product_get_price',
					array(
						YITH_WC_Dynamic_Pricing_Frontend(),
						'get_price',
					),
					10,
					2
				);

				$discount = apply_filters( 'ywdpd_validate_apply_to_discount', $discount, $product, $key_rule, $rule, $cart_item_key, $cart_item );

				if ( 'bulk' === $rule['discount_mode'] ) {
					$discount['discount_mode'] = 'bulk';
					foreach ( $rule['rules'] as $index => $r ) {

						if ( ( $quantity >= $r['min_quantity'] && '*' === $r['max_quantity'] ) || ( $quantity <= $r['max_quantity'] && $quantity >= $r['min_quantity'] ) ) {
							$discount['discount_amount'] = array(
								'type'   => $r['type_discount'],
								'amount' => $r['discount_amount'],
							);
							$discount['rule_id']         = $rule['id'];
							break;
						}
					}
				} elseif ( 'special_offer' === $rule['discount_mode'] ) {

					$discount = array_merge( $discount, $this->get_special_offer_discount( $rule, $cart_item ) );

				}

				if ( ! isset( $discount['discount_amount'] ) ) {
					return false;
				}

				// check if the rule can be applied to current cart item.
				if ( ! $is_active_apply_adjustment || ( $is_active_apply_adjustment && $is_valid_also_for_adjust ) ) {
					WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'][ $key_rule ]                                    = $discount;
					WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'][ $key_rule ]['discount_amount']['same_product'] = 1;
				}

				if ( $is_active_apply_adjustment ) {
					foreach ( WC()->cart->cart_contents as $cart_item_key_adj => $cart_item_adj ) {

						$this->process_rule_adjustment( $rule, $key_rule, $cart_item_key_adj, $cart_item_adj, $discount );
					}
				}
			}

			return $is_valid;

		}

		/**
		 * return the array with the special offer details
		 *
		 * @auhtor YITH
		 * @sice 2.1
		 *
		 * @param array $rule
		 * @param array $cart_item
		 *
		 * @return array
		 */
		public function get_special_offer_discount( $rule, $cart_item ) {
			$discount                                 = array();
			$discount['discount_mode']                = 'special_offer';
			$num_valid_product_to_apply_in_cart_clean = $this->num_valid_product_to_apply_in_cart( $rule, $cart_item, true );
			$num_valid_product_to_apply_in_cart_mix   = $this->num_valid_product_to_apply_in_cart( $rule, $cart_item, false );

			if ( isset( $rule['so-rule']['repeat'] ) ) {
				$repetitions = floor( ( $num_valid_product_to_apply_in_cart_clean + $num_valid_product_to_apply_in_cart_mix ) / $rule['so-rule']['purchase'] );
			} else {
				$repetitions = 1;
			}

			$rcq = $num_valid_product_to_apply_in_cart_clean; // remaining clean quantity.
			$rmq = $num_valid_product_to_apply_in_cart_mix; // remaining mixed quantity.

			$tot_apply_to        = $rmq + $rcq;
			$discount['rule_id'] = $rule['id'];

			$tt = 0;
			if ( $rcq || $rmq ) {

				for ( $x = 1; $x <= $repetitions; $x ++ ) {
					if ( $tot_apply_to - $rule['so-rule']['purchase'] >= 0 ) {
						$tot_apply_to -= $rule['so-rule']['purchase'];
						$tt           += isset( $rule['so-rule']['receive'] ) ? intval( $rule['so-rule']['receive'] ) : 0;
					}
				}
			}

			$discount['discount_amount'] = array(
				'type'           => $rule['so-rule']['type_discount'],
				'amount'         => $rule['so-rule']['discount_amount'],
				'purchase'       => $rule['so-rule']['purchase'],
				'receive'        => $rule['so-rule']['receive'],
				'quantity_based' => $rule['quantity_based'],
				'total_target'   => $tt,
				'same_product'   => 0,
			);

			return $discount;

		}

		public function validate_apply_to_in_cart( $rule ) {

			$is_valid = false;

			if ( ! is_null( WC()->cart ) && ! WC()->cart->is_empty() ) {

				foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {
					if ( $this->validate_apply_to_cart_item( $rule, $cart_item_key, $cart_item ) ) {
						return true;
					}
				}
			}

			return $is_valid;
		}

		public function validate_apply_to_cart_item( $rule, $cart_item_key, $cart_item ) {

			if ( $this->is_in_exclusion_rule( $cart_item ) || ( $this->has_a_bulk_applied( $cart_item_key ) && 'bulk' === $rule['discount_mode'] ) ) {
				return false;
			}

			$exclude_enabled = isset( $rule['active_exclude'] ) && yith_plugin_fw_is_true( $rule['active_exclude'] );
			$is_in_exclusion = false;
			$is_valid        = false;

			if ( $exclude_enabled ) {
				$exclude_type = $rule['exclude_rule_for'];

				switch ( $exclude_type ) {

					case 'specific_products':
						$product_list = $rule['exclude_rule_for_products_list'];
						if ( is_array( $product_list ) && $this->product_in_list( $cart_item, $product_list ) ) {
							$is_in_exclusion = true;
						}
						break;
					case 'specific_categories':
						$categories_list = $rule['exclude_rule_for_categories_list'];
						if ( $this->check_taxonomy( $categories_list, $cart_item['product_id'], 'product_cat' ) ) {
							$is_in_exclusion = true;
						}
						break;
					case 'specific_tag':
						$tags_list = $rule['exclude_rule_for_tags_list'];
						if ( $this->check_taxonomy( $tags_list, $cart_item['product_id'], 'product_tag' ) ) {
							$is_in_exclusion = true;
						}
						break;
					case 'vendor_list_excluded':
						if ( ! class_exists( 'YITH_Vendors' ) || ! isset( $rule['apply_to_vendors_list_excluded'] ) ) {
							break;
						}
						$vendor_list    = array_map( 'intval', $rule['apply_to_vendors_list_excluded'] );
						$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
						$intersect      = array_intersect( $vendor_of_item, $vendor_list );
						if ( empty( $intersect ) ) {
							$is_in_exclusion = true;
						}
						break;

				}
			}

			if ( ! $is_in_exclusion ) {

				switch ( $rule['rule_for'] ) {

					case 'all_products':
						$is_valid = true;
						break;
					case 'specific_products':
						$product_list = $rule['rule_for_products_list'];

						if ( is_array( $product_list ) && $this->product_in_list( $cart_item, $product_list ) ) {
							$is_valid = true;
						}
						break;
					case 'specific_categories':
						$categories_list = $rule['rule_for_categories_list'];
						if ( $this->check_taxonomy( $categories_list, $cart_item['product_id'], 'product_cat' ) ) {

							$is_valid = true;
						}
						break;
					case 'specific_tag':
						$tags_list = $rule['rule_for_tags_list'];
						if ( $this->check_taxonomy( $tags_list, $cart_item['product_id'], 'product_tag' ) ) {

							$is_valid = true;
						}
						break;
					case 'vendor_list':
						if ( ! class_exists( 'YITH_Vendors' ) || ! isset( $rule['apply_to_vendors_list'] ) ) {
							break;
						}
						$vendor_list    = array_map( 'intval', $rule['apply_to_vendors_list'] );
						$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
						$intersect      = array_intersect( $vendor_of_item, $vendor_list );
						if ( ! empty( $intersect ) ) {
							$is_valid = true;
						}
						break;
					default:
						$is_valid = apply_filters( 'ywdpd_validate_apply_to', $is_valid, $rule['rule_for'], $cart_item['product_id'], $rule, $cart_item, false );

						break;
				}
			}

			return $is_valid;
		}

		/**
		 * Check if the product in cart_item is in a exclusion rule
		 *
		 * @param array $cart_item Cart item.
		 *
		 * @return bool
		 */
		public function is_in_exclusion_rule( $cart_item ) {

			$exclusion_rules = YITH_WC_Dynamic_Pricing()->get_exclusion_rules();
			$excluded        = false;
			$is_valid        = false;

			foreach ( $exclusion_rules as $rule ) {

				$exclude_enabled = isset( $rule['active_exclude'] ) && yith_plugin_fw_is_true( $rule['active_exclude'] );
				$is_in_exclusion = false;
				if ( $exclude_enabled ) {
					$exclude_type = $rule['exclude_rule_for'];

					switch ( $exclude_type ) {

						case 'specific_products':
							$product_list = $rule['exclude_rule_for_products_list'];
							if ( is_array( $product_list ) && $this->product_in_list( $cart_item, $product_list ) ) {
								$is_in_exclusion = true;
							}
							break;
						case 'specific_categories':
							$categories_list = $rule['exclude_rule_for_categories_list'];
							if ( $this->check_taxonomy( $categories_list, $cart_item['product_id'], 'product_cat' ) ) {
								$is_in_exclusion = true;
							}
							break;
						case 'specific_tag':
							$tags_list = $rule['exclude_rule_for_tags_list'];
							if ( $this->check_taxonomy( $tags_list, $cart_item['product_id'], 'product_cat' ) ) {
								$is_in_exclusion = true;
							}
							break;

					}
				}

				if ( ! $is_in_exclusion ) {

					switch ( $rule['rule_for'] ) {

						case 'all_products':
							$is_valid = true;
							break;
						case 'specific_products':
							$product_list = $rule['rule_for_products_list'];
							if ( is_array( $product_list ) && $this->product_in_list( $cart_item, $product_list ) ) {
								$is_valid = true;
							}
							break;
						case 'specific_categories':
							$categories_list = $rule['rule_for_categories_list'];
							if ( $this->check_taxonomy( $categories_list, $cart_item['product_id'], 'product_cat' ) ) {

								$is_valid = true;
							}
							break;
						case 'specific_tag':
							$tags_list = $rule['rule_for_tags_list'];
							if ( $this->check_taxonomy( $tags_list, $cart_item['product_id'], 'product_cat' ) ) {

								$is_valid = true;
							}
							break;
						default:
							$is_valid = apply_filters( 'ywdpd_is_in_exclusion_rule', $is_valid, $rule['apply_to'], $cart_item['product_id'], $rule, $cart_item );

							break;
					}
				}

				if ( $is_valid ) {
					return true;
				}
			}

			return $excluded;
		}

		/**
		 * Assign the discount to a cart item if is a valid product to adjust
		 *
		 * @param array  $rule Rule.
		 * @param string $key_rule Key rule.
		 * @param string $cart_item_key Cart item key.
		 * @param array  $cart_item Cart item.
		 * @param mixed  $discount Discount.
		 *
		 * @return mixed
		 */
		public function process_rule_adjustment( $rule, $key_rule, $cart_item_key, $cart_item, $discount ) {
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'][ $key_rule ] ) ) {
				return;
			}

			if ( $this->valid_product_to_adjust( $rule, $cart_item ) ) {
				WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'][ $key_rule ] = $discount;
			}

			return;
		}

		/**
		 * Check the quantity of a cart_item based on the rule quantity_based
		 *
		 * @param array $rule Rule.
		 * @param array $cart_item Cart item.
		 *
		 * @return int|mixed
		 */
		public function check_quantity( $rule, $cart_item ) {

			$quantity = $cart_item['quantity'];

			if ( 'bulk' === $rule['discount_mode'] || 'special_offer' === $rule['discount_mode'] ) {
				switch ( $rule['quantity_based'] ) {
					case 'cart_line':
						break;
					case 'single_product':
						if ( isset( $this->product_counters[ $cart_item['product_id'] ] ) ) {
							$quantity = $this->product_counters[ $cart_item['product_id'] ];
						}
						break;
					case 'single_variation_product':
						if ( isset( $cart_item['variation_id'] ) && 0 < $cart_item['variation_id'] && isset( $this->variation_counters[ $cart_item['variation_id'] ] ) ) {
							$quantity = $this->variation_counters[ $cart_item['variation_id'] ];
						}
						break;
					case 'cumulative':
						$quantity = $this->get_cumulative_quantity( $rule );

						break;
					default:
				}
			}

			return $quantity;
		}

		/**
		 * Get the cumulative quantity in the cart contents
		 *
		 * @param array $rule Rule.
		 *
		 * @return int
		 */
		public function get_cumulative_quantity( $rule ) {

			// get cumulative quantity
			$quantity = $this->get_cumulative_quantity_for_apply_to( $rule );

			return apply_filters( 'ywdpd_get_cumulative_quantity', $quantity, $rule['rule_for'], $rule );
		}

		public function get_cumulative_quantity_for_adjustment_to( $rule ) {

			$is_excluded = yith_plugin_fw_is_true( $rule['active_apply_adjustment_to_exclude'] );
			$quantity    = 0;
			if ( $is_excluded ) {

				$type = $rule['exclude_apply_adjustment_rule_for'];

				switch ( $type ) {

					case 'specific_products':
						$product_list = $rule['apply_adjustment_products_list_excluded'];
						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

							if ( ! $this->product_in_list( $cart_item, $product_list ) ) {
								$quantity += $cart_item['quantity'];
							}
						}
						break;
					case 'specific_categories':
						$categories_list = $rule['apply_adjustment_categories_list_excluded'];
						$quantity        = $this->check_taxonomy_quantity( $categories_list, 'product_cat', false );
						break;
					case 'specific_tag':
						$tag_list = $rule['apply_adjustment_tags_list_excluded'];
						$quantity = $this->check_taxonomy_quantity( $tag_list, 'product_tag', false );
						break;
					case 'vendor_list_excluded':
						if ( ! class_exists( 'YITH_Vendors' ) ) {
							break;
						}
						$vendor_list = array_map( 'intval', $rule['apply_to_vendors_list_excluded'] );
						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
							$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
							$intersect      = array_intersect( $vendor_of_item, $vendor_list );
							if ( empty( $intersect ) ) {
								$quantity += $cart_item['quantity'];
							}
						}
						break;
				}
			} else {

				$type = $rule['rule_apply_adjustment_discount_for'];
				switch ( $type ) {
					case 'all_products':
						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
							$quantity += $cart_item['quantity'];
						}
						break;
					case 'specific_products':
						$product_list = $rule['apply_adjustment_products_list'];
						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

							if ( $this->product_in_list( $cart_item, $product_list ) ) {
								$quantity += $cart_item['quantity'];
							}
						}
						break;
					case 'specific_categories':
						$categories_list = $rule['apply_adjustment_categories_list'];
						$quantity        = $this->check_taxonomy_quantity( $categories_list, 'product_cat', true );
						break;
					case 'specific_tag':
						$tag_list = $rule['apply_adjustment_tags_list'];
						$quantity = $this->check_taxonomy_quantity( $tag_list, 'product_tag', true );
						break;
					case 'vendor_list':
						if ( ! class_exists( 'YITH_Vendors' ) ) {
							break;
						}
						$vendor_list = array_map( 'intval', $rule['apply_to_vendors_list'] );

						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
							$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
							$intersect      = array_intersect( $vendor_of_item, $vendor_list );
							if ( ! empty( $intersect ) ) {
								$quantity += $cart_item['quantity'];
							}
						}
						break;
				}
			}

			return $quantity;
		}

		public function get_cumulative_quantity_for_apply_to( $rule ) {

			$is_excluded = isset( $rule['active_exclude'] ) && yith_plugin_fw_is_true( $rule['active_exclude'] );
			$quantity    = 0;
			if ( $is_excluded ) {

				$type = $rule['exclude_rule_for'];

				switch ( $type ) {

					case 'specific_products':
						$product_list = $rule['exclude_rule_for_products_list'];
						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

							if ( ! $this->product_in_list( $cart_item, $product_list ) ) {
								$quantity += $cart_item['quantity'];
							}
						}
						break;
					case 'specific_categories':
						$categories_list = $rule['exclude_rule_for_categories_list'];
						$quantity        = $this->check_taxonomy_quantity( $categories_list, 'product_cat', false );
						break;
					case 'specific_tag':
						$tag_list = $rule['exclude_rule_for_tags_list'];
						$quantity = $this->check_taxonomy_quantity( $tag_list, 'product_tag', false );
						break;
					case 'vendor_list_excluded':
						if ( ! class_exists( 'YITH_Vendors' ) ) {
							break;
						}
						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
							$vendor_list    = array_map( 'intval', $rule['apply_to_vendors_list_excluded'] );
							$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
							$intersect      = array_intersect( $vendor_of_item, $vendor_list );
							if ( ! empty( $intersect ) ) {
								$quantity += $cart_item['quantity'];
							}
						}
						break;
				}
			} else {

				$type = $rule['rule_for'];
				switch ( $type ) {
					case 'all_products':
						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
							$quantity += $cart_item['quantity'];
						}
						break;
					case 'specific_products':
						$product_list = $rule['rule_for_products_list'];
						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

							if ( $this->product_in_list( $cart_item, $product_list ) ) {
								$quantity += $cart_item['quantity'];
							}
						}
						break;
					case 'specific_categories':
						$categories_list = $rule['rule_for_categories_list'];
						$quantity        = $this->check_taxonomy_quantity( $categories_list, 'product_cat', true );
						break;
					case 'specific_tag':
						$tag_list = $rule['rule_for_tags_list'];
						$quantity = $this->check_taxonomy_quantity( $tag_list, 'product_tag', true );
						break;
					case 'vendor_list':
						if ( ! class_exists( 'YITH_Vendors' ) ) {
							break;
						}
						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
							$vendor_list    = array_map( 'intval', $rule['apply_to_vendors_list'] );
							$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
							$intersect      = array_intersect( $vendor_of_item, $vendor_list );
							if ( ! empty( $intersect ) ) {
								$quantity += $cart_item['quantity'];
							}
						}
						break;
				}
			}

			return $quantity;
		}

		/**
		 * Check if the product in cart item is a valid product to adjust the rule
		 *
		 * @param array $rule Rule.
		 * @param array $cart_item Cart item.
		 *
		 * @return bool
		 */
		public function valid_product_to_adjust( $rule, $cart_item ) {
			$is_valid          = false;
			$is_in_exclusion   = false;
			$exclusion_enabled = isset( $rule['active_apply_adjustment_to_exclude'] ) && yith_plugin_fw_is_true( $rule['active_apply_adjustment_to_exclude'] );

			if ( $exclusion_enabled ) {
				$exclude_type = $rule['exclude_apply_adjustment_rule_for'];

				switch ( $exclude_type ) {

					case 'specific_products':
						$product_list = $rule['apply_adjustment_products_list_excluded'];
						if ( is_array( $product_list ) && $this->product_in_list( $cart_item, $product_list ) ) {
							$is_in_exclusion = true;
						}
						break;
					case 'specific_categories':
						$categories_list = $rule['apply_adjustment_categories_list_excluded'];
						if ( $this->check_taxonomy( $categories_list, $cart_item['product_id'], 'product_cat' ) ) {
							$is_in_exclusion = true;
						}
						break;
					case 'specific_tag':
						$tags_list = $rule['apply_adjustment_tags_list_excluded'];
						if ( $this->check_taxonomy( $tags_list, $cart_item['product_id'], 'product_tag' ) ) {
							$is_in_exclusion = true;
						}
						break;
					case 'vendor_list_excluded':
						if ( ! class_exists( 'YITH_Vendors' ) ) {
							break;
						}
						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
							$vendor_list    = array_map( 'intval', $rule['apply_adjustment_vendor_list_excluded'] );
							$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
							$intersect      = array_intersect( $vendor_of_item, $vendor_list );
							if ( ! empty( $intersect ) ) {
								$is_in_exclusion = true;
							}
						}
						break;

				}
			}

			if ( ! $is_in_exclusion ) {
				$apply_adjustment_type = isset( $rule['rule_apply_adjustment_discount_for'] ) ? $rule['rule_apply_adjustment_discount_for'] : 'all_products';

				switch ( $apply_adjustment_type ) {
					case 'all_products':
						$is_valid = true;
						break;
					case 'specific_products':
						$product_list = $rule['apply_adjustment_products_list'];

						if ( is_array( $product_list ) && $this->product_in_list( $cart_item, $product_list ) ) {
							$is_valid = true;
						}
						break;
					case 'specific_categories':
						$categories_list = $rule['apply_adjustment_categories_list'];
						if ( $this->check_taxonomy( $categories_list, $cart_item['product_id'], 'product_cat' ) ) {
							$is_valid = true;
						}
						break;
					case 'specific_tag':
						$tags_list = $rule['apply_adjustment_tags_list'];
						if ( $this->check_taxonomy( $tags_list, $cart_item['product_id'], 'product_tag' ) ) {
							$is_valid = true;
						}
						break;
					case 'vendor_list':
						if ( ! class_exists( 'YITH_Vendors' ) ) {
							break;
						}
						foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
							$vendor_list    = array_map( 'intval', $rule['apply_adjustment_vendor_list'] );
							$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
							$intersect      = array_intersect( $vendor_of_item, $vendor_list );
							if ( ! empty( $intersect ) ) {
								$is_valid = true;
							}
						}
						break;

				}
			}

			return apply_filters( 'ywdpd_is_valid_product_apply_adjustment_to', $is_valid, $rule, $cart_item['data'] );
		}

		/**
		 * Check valid product to adjustment.
		 *
		 * @param array      $rule Rule.
		 * @param WC_Product $product Product.
		 * @param bool       $other_variations Variations.
		 *
		 * @return bool
		 */
		public function valid_product_to_adjustment( $rule, $product, $other_variations = false ) {

			$key_check = $rule['key'] . '_' . $product->get_id() . '_' . ( $other_variations ? 1 : 0 );

			if ( isset( $this->valid_product_to_adjustment[ $key_check ] ) ) {
				return $this->valid_product_to_adjustment[ $key_check ];
			}

			$is_valid = false;

			$even_onsale = isset( $rule['disable_on_sale'] ) && ! yith_plugin_fw_is_true( $rule['disable_on_sale'] );
			$sale_price  = yit_get_prop( $product, 'sale_price', true, 'edit' );
			$main_id     = $product->is_type( 'variation' ) ? yit_get_base_product_id( $product ) : $product->get_id();
			$search_in   = array( $main_id, $product->get_id() );
			$sale_price  = apply_filters( 'ywcdp_product_is_on_sale', '' !== $sale_price, $product, $rule );

			if ( $sale_price && ! $even_onsale ) {
				return false;
			}

			if ( $other_variations ) {
				if ( $product->is_type( 'variation' ) ) {
					$parent    = wc_get_product( yit_get_base_product_id( $product ) );
					$search_in = array_merge( $parent->get_children(), $search_in );
				} elseif ( $product->is_type( 'variable' ) ) {
					$search_in = array_merge( $product->get_children(), $search_in );
				}
			}

			$is_excluded     = isset( $rule['active_apply_adjustment_to_exclude'] ) && yith_plugin_fw_is_true( $rule['active_apply_adjustment_to_exclude'] );
			$is_in_exclusion = false;

			if ( $is_excluded ) {
				$excluded_type = $rule['exclude_apply_adjustment_rule_for'];

				switch ( $excluded_type ) {

					case 'specific_products':
						if ( isset( $rule['apply_adjustment_products_list_excluded'] ) ) {
							$product_list = $rule['apply_adjustment_products_list_excluded'];
							$intersect    = array_intersect( $search_in, $product_list );
							if ( ! empty( $intersect ) ) {
								$is_in_exclusion = true;
							}
						}
						break;
					case 'specific_categories':
						if ( isset( $rule['apply_adjustment_categories_list_excluded'] ) ) {
							$categories_list    = $rule['apply_adjustment_categories_list_excluded'];
							$get_by             = is_numeric( $categories_list[0] ) ? 'ids' : 'slugs';
							$categories_of_item = wc_get_product_terms( $main_id, 'product_cat', array( 'fields' => $get_by ) );
							$categories_of_item = apply_filters( 'ywdpd_dynamic_exclude_category_list', $categories_of_item, $main_id, $rule );
							$intersect          = array_intersect( $categories_of_item, $categories_list );
							if ( ! empty( $intersect ) ) {
								$is_in_exclusion = true;
							}
						}
						break;
					case 'specific_tag':
						if ( isset( $rule['apply_adjustment_tags_list_excluded'] ) ) {
							$tags_list    = $rule['apply_adjustment_tags_list_excluded'];
							$get_by       = is_numeric( $tags_list[0] ) ? 'ids' : 'slugs';
							$tags_of_item = wc_get_product_terms( $main_id, 'product_tag', array( 'fields' => $get_by ) );
							$intersect    = array_intersect( $tags_of_item, $tags_list );
							if ( ! empty( $intersect ) ) {
								$is_in_exclusion = true;
							}
						}

						break;
					case 'vendor_list_excluded':
						if ( ! class_exists( 'YITH_Vendors' ) || ! isset( $rule['apply_adjustment_vendor_list_excluded'] ) ) {
							break;
						}
						$vendor_list    = array_map( 'intval', $rule['apply_adjustment_vendor_list_excluded'] );
						$vendor_of_item = wc_get_product_terms( $main_id, YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
						$intersect      = array_intersect( $vendor_of_item, $vendor_list );
						if ( ! empty( $intersect ) ) {
							$is_in_exclusion = true;
						}
						break;
				}
			}

			if ( ! $is_in_exclusion ) {
				$rule_type = isset( $rule['rule_apply_adjustment_discount_for'] ) ? $rule['rule_apply_adjustment_discount_for'] : 'all_products';

				switch ( $rule_type ) {

					case 'all_products':
						$is_valid = true;
						break;
					case 'specific_products':
						if ( isset( $rule['apply_adjustment_products_list'] ) ) {
							$product_list = $rule['apply_adjustment_products_list'];
							$intersect    = array_intersect( $search_in, $product_list );
							if ( ! empty( $intersect ) ) {
								$is_valid = true;
							}
						}
						break;
					case 'specific_categories':
						if ( isset( $rule['apply_adjustment_categories_list'] ) ) {
							$categories_list    = $rule['apply_adjustment_categories_list'];
							$get_by             = is_numeric( $categories_list[0] ) ? 'ids' : 'slugs';
							$categories_of_item = wc_get_product_terms( $main_id, 'product_cat', array( 'fields' => $get_by ) );
							$categories_of_item = apply_filters( 'ywdpd_dynamic_exclude_category_list', $categories_of_item, $main_id, $rule );
							$intersect          = array_intersect( $categories_of_item, $categories_list );
							if ( ! empty( $intersect ) ) {
								$is_valid = true;
							}
						}
						break;
					case 'specific_tag':
						if ( isset( $rule['apply_adjustment_tags_list'] ) ) {
							$tags_list    = $rule['apply_adjustment_tags_list'];
							$get_by       = is_numeric( $tags_list[0] ) ? 'ids' : 'slugs';
							$tags_of_item = wc_get_product_terms( $main_id, 'product_tag', array( 'fields' => $get_by ) );
							$intersect    = array_intersect( $tags_of_item, $tags_list );
							if ( ! empty( $intersect ) ) {
								$is_valid = true;
							}
						}

						break;
					case 'vendor_list':
						if ( ! class_exists( 'YITH_Vendors' ) || ! isset( $rule['apply_adjustment_vendor_list'] ) ) {
							break;
						}
						$vendor_list    = array_map( 'intval', $rule['apply_adjustment_vendor_list'] );
						$vendor_of_item = wc_get_product_terms( $main_id, YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
						$intersect      = array_intersect( $vendor_of_item, $vendor_list );
						if ( ! empty( $intersect ) ) {
							$is_valid = true;
						}
						break;
				}
			}

			$this->valid_product_to_adjustment[ $key_check ] = apply_filters( 'ywdpd_is_valid_product_apply_adjustment_to', $is_valid, $rule, $product );

			return $this->valid_product_to_adjustment[ $key_check ];
		}

		/**
		 * Check if the product is a valid product to apply the rule
		 *
		 * @param array                          $rule Rule.
		 * @param WC_Product|WC_Product_Variable $product Product.
		 * @param bool                           $other_variations Variations.
		 *
		 * @return bool
		 */
		public function valid_product_to_apply( $rule, $product, $other_variations = false ) {

			if ( ! $product ) {
				return false;
			}

			$key_check = $rule['key'] . '_' . $product->get_id() . '_' . ( $other_variations ? 1 : 0 );

			if ( isset( $this->valid_product_to_apply[ $key_check ] ) ) {
				return $this->valid_product_to_apply[ $key_check ];
			}

			$is_valid = false;

			$even_onsale = isset( $rule['disable_on_sale'] ) && ! yith_plugin_fw_is_true( $rule['disable_on_sale'] );
			$sale_price  = $product->get_sale_price( 'edit' );
			$main_id     = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
			$search_in   = array( $main_id, $product->get_id() );
			$sale_price  = apply_filters( 'ywcdp_product_is_on_sale', '' !== $sale_price, $product, $rule );

			if ( $sale_price && ! $even_onsale ) {
				return false;
			}

			if ( $other_variations && apply_filters( 'yith_ywdpd_valid_product_to_apply_other_variations', true, $rule, $product, $other_variations ) ) {
				if ( $product->is_type( 'variation' ) ) {
					$parent = wc_get_product( $product->get_parent_id() );
					if ( $parent instanceof WC_Product_Variable ) {
						$search_in = array_merge( $parent->get_children(), $search_in );
					}
				} elseif ( $product->is_type( 'variable' ) ) {
					$search_in = array_merge( $product->get_children(), $search_in );
				}
			}

			// Check if the rule can't applied to specific products/categories/tag

			$exclude_enabled = isset( $rule['active_exclude'] ) && yith_plugin_fw_is_true( $rule['active_exclude'] );
			$is_in_exclusion = false;

			if ( $exclude_enabled ) {
				$exclude_type = $rule['exclude_rule_for'];

				switch ( $exclude_type ) {

					case 'specific_products':
						$product_list = $rule['exclude_rule_for_products_list'];
						if ( is_array( $product_list ) && count( array_intersect( $search_in, $product_list ) ) > 0 ) {
							$is_in_exclusion = true;
						}
						break;
					case 'specific_categories':
						$categories_list    = $rule['exclude_rule_for_categories_list'];
						$get_by             = is_numeric( $categories_list[0] ) ? 'ids' : 'slugs';
						$categories_of_item = wc_get_product_terms( $main_id, 'product_cat', array( 'fields' => $get_by ) );
						$categories_of_item = apply_filters( 'ywdpd_dynamic_category_list', $categories_of_item, $main_id, $rule );
						$intersect          = array_intersect( $categories_of_item, $categories_list );
						if ( ! empty( $intersect ) ) {
							$is_in_exclusion = true;
						}
						break;
					case 'specific_tag':
						$tags_list    = $rule['exclude_rule_for_tags_list'];
						$get_by       = is_numeric( $tags_list[0] ) ? 'ids' : 'slugs';
						$tags_of_item = wc_get_product_terms( $main_id, 'product_tag', array( 'fields' => $get_by ) );
						$intersect    = array_intersect( $tags_of_item, $tags_list );
						if ( ! empty( $intersect ) ) {
							$is_in_exclusion = true;
						}
						break;
					case 'vendor_list_excluded':
						if ( ! class_exists( 'YITH_Vendors' ) || ! isset( $rule['apply_to_vendors_list_excluded'] ) ) {
							break;
						}
						$vendor_list    = array_map( 'intval', $rule['apply_to_vendors_list_excluded'] );
						$vendor_of_item = wc_get_product_terms( $main_id, YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
						$intersect      = array_intersect( $vendor_of_item, $vendor_list );
						if ( ! empty( $intersect ) ) {
							$is_in_exclusion = true;
						}
				}
			}

			if ( ! $is_in_exclusion ) {

				switch ( $rule['rule_for'] ) {

					case 'all_products':
						$is_valid = true;
						break;
					case 'specific_products':
						$product_list = $rule['rule_for_products_list'];

						if ( is_array( $product_list ) && count( array_intersect( $search_in, $product_list ) ) > 0 ) {
							$is_valid = true;
						}
						break;
					case 'specific_categories':
						$categories_list    = $rule['rule_for_categories_list'];
						$get_by             = is_numeric( $categories_list[0] ) ? 'ids' : 'slugs';
						$categories_of_item = wc_get_product_terms( $main_id, 'product_cat', array( 'fields' => $get_by ) );
						$categories_of_item = apply_filters( 'ywdpd_dynamic_category_list', $categories_of_item, $main_id, $rule );
						$intersect          = array_intersect( $categories_of_item, $categories_list );
						if ( ! empty( $intersect ) ) {
							$is_valid = true;
						}
						break;
					case 'specific_tag':
						$tags_list    = $rule['rule_for_tags_list'];
						$get_by       = is_numeric( $tags_list[0] ) ? 'ids' : 'slugs';
						$tags_of_item = wc_get_product_terms( $main_id, 'product_tag', array( 'fields' => $get_by ) );
						$intersect    = array_intersect( $tags_of_item, $tags_list );
						if ( ! empty( $intersect ) ) {
							$is_valid = true;
						}
						break;
					case 'vendor_list':
						if ( ! class_exists( 'YITH_Vendors' ) || ! isset( $rule['apply_to_vendors_list'] ) ) {
							break;
						}
						$vendor_list    = array_map( 'intval', $rule['apply_to_vendors_list'] );
						$vendor_of_item = wc_get_product_terms( $main_id, YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
						$intersect      = array_intersect( $vendor_of_item, $vendor_list );
						if ( ! empty( $intersect ) ) {
							$is_valid = true;
						}
						break;
				}
			}

			$this->valid_product_to_apply[ $key_check ] = apply_filters( 'ywdpd_is_valid_product_to_apply', $is_valid, $rule, $product, $other_variations, $is_in_exclusion );

			return $this->valid_product_to_apply[ $key_check ];
		}

		/**
		 * Check if the product is a valid product to apply the bulk rule
		 *
		 * @param array      $rule Rule.
		 * @param WC_Product $product Product.
		 * @param bool       $other_variations Variations.
		 *
		 * @return bool
		 */
		public function valid_product_to_apply_bulk( $rule, $product, $other_variations = false ) {

			return isset( $rule['discount_mode'] ) && $rule['discount_mode'] == 'bulk' && $this->valid_product_to_apply( $rule, $product, $other_variations );
		}

		/**
		 * Check if the product is a valid product to adjustment the bulk rule
		 *
		 * @param array      $rule Rule.
		 * @param WC_Product $product Product.
		 * @param bool       $other_variations Variations.
		 *
		 * @return bool
		 */
		public function valid_product_to_adjustment_bulk( $rule, $product, $other_variations = false ) {
			return isset( $rule['discount_mode'] ) && $rule['discount_mode'] == 'bulk' && $this->valid_product_to_adjustment( $rule, $product, $other_variations );
		}


		/**
		 * check if the customers condition is valid
		 *
		 * @param array $condition
		 * @param array $conditions
		 *
		 * @return bool
		 * @author  YITH
		 * @since 2.0
		 */
		public function valid_customers_cart_condition( $condition, $conditions ) {
			$is_valid = true;

			$apply_for_all = isset( $condition['user_discount_to'] ) ? $condition['user_discount_to'] : 'all';

			$user_logged_in = is_user_logged_in();
			$user           = $user_logged_in ? wp_get_current_user() : false;
			$user_roles     = $user ? $user->roles : array( 'guest' );
			if ( 'all' === $apply_for_all ) {

				$is_customers_excluded = yith_plugin_fw_is_true( $condition['enable_exclude_users'] );

				if ( $is_customers_excluded ) {

					$customers_excluded = ! empty( $condition['customers_list_excluded'] ) ? $condition['customers_list_excluded'] : '';
					$roles_excluded     = ! empty( $condition['customers_role_list_excluded'] ) ? $condition['customers_role_list_excluded'] : '';

					if ( $user_logged_in ) {
						if ( ! empty( $customers_excluded ) ) {
							if ( in_array( $user->ID, $customers_excluded ) ) {
								$is_valid = false;
							}
						}
					}

					if ( ! empty( $roles_excluded ) && is_array( $roles_excluded ) ) {
						$intersect = array_intersect( $user_roles, $roles_excluded );
						if ( ! empty( $roles_excluded ) && 0 < count( $intersect ) ) {
							$is_valid = false;
						}
					}
				}
			} else {

				$customers_list = ! empty( $condition['customers_list'] ) ? $condition['customers_list'] : '';
				$role_list      = ! empty( $condition['customers_role_list'] ) ? $condition['customers_role_list'] : '';

				if ( $user_logged_in ) {
					if ( ! empty( $customers_list ) ) {
						if ( ! in_array( $user->ID, $customers_list ) ) {
							$is_valid = false;
						}
					}
				} else {
					$is_valid = false;
				}

				if ( ! empty( $role_list ) && is_array( $role_list ) ) {
					$intersect = array_intersect( $user_roles, $role_list );

					if ( 0 === count( $intersect ) ) {
						$is_valid = false;
					}
				}
			}

			return apply_filters( 'ywdpd_customers_condition_in_cart_is_valid', $is_valid, $condition, $conditions );
		}

		/**
		 * check if the num of order condition is valid
		 *
		 * @param array $condition
		 * @param array $conditions
		 *
		 * @return  bool
		 * @since 2.0
		 * @author YITH
		 */
		public function valid_num_of_orders_cart_condition( $condition, $conditions ) {

			$is_valid = true;

			if ( is_user_logged_in() ) {

				$user_id      = get_current_user_id();
				$num_of_order = wc_get_customer_order_count( $user_id );
				$min_order    = ! empty( $condition['min_order'] ) ? $condition['min_order'] : 0;
				$max_order    = ! empty( $condition['max_order'] ) ? $condition['max_order'] : '';

				if ( ( $num_of_order < $min_order ) || ( ! empty( $max_order ) && $num_of_order >= $max_order ) ) {

					$is_valid = false;
				}
			}

			return $is_valid;
		}

		/**
		 * check the past expense condition
		 *
		 * @param array $condition
		 * @param array $conditions
		 *
		 * @return  bool
		 * @since 2.0
		 *
		 * @author YITH
		 */
		public function valid_past_expense_cart_condition( $condition, $conditions ) {

			$is_valid = true;

			if ( is_user_logged_in() ) {

				$user_id      = get_current_user_id();
				$past_expense = wc_get_customer_total_spent( $user_id );
				$min_expense  = ! empty( $condition['min_expense'] ) ? $condition['min_expense'] : 1;
				$max_expense  = ! empty( $condition['max_expense'] ) ? $condition['max_expense'] : '';

				if ( ( $past_expense < $min_expense ) || ( ! empty( $max_expense ) && $past_expense > $max_expense ) ) {

					$is_valid = false;
				}
			}

			return $is_valid;
		}

		/**
		 * check if the cart condition is valid
		 *
		 * @param array $condition
		 * @param array $conditions
		 *
		 * @return bool
		 * @since 2.0
		 *
		 * @author YITH
		 */
		public function valid_cart_items_cart_condition( $condition, $conditions ) {
			$is_valid = true;

			$quantity_check = ! empty( $condition['cart_item_qty_type'] ) ? $condition['cart_item_qty_type'] : 'count_product_items';

			if ( 'count_product_items' === $quantity_check ) {
				$min_items = ! empty( $condition['min_product_item'] ) ? $condition['min_product_item'] : 1;
				$max_items = ! empty( $condition['max_product_item'] ) ? $condition['max_product_item'] : '';

				$is_valid = $this->check_cart_item_quantity( $min_items, $max_items );
			} else {

				$num_items = 0;

				if ( ! is_null( WC()->cart ) && ! WC()->cart->is_empty() ) {

					foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {

						if ( ! $this->product_is_excluded_from_conditions( $cart_item['data'], $conditions ) ) {
							$num_items += $cart_item['quantity'];
						}
					}
				}
				$num_items = apply_filters( 'ywdpd_get_cart_item_quantities', $num_items );

				$min_items = ! empty( $condition['min_cart_item'] ) ? $condition['min_cart_item'] : 1;
				$max_items = ! empty( $condition['max_cart_item'] ) ? $condition['max_cart_item'] : '';

				if ( ( $num_items < $min_items ) || ( ! empty( $max_items ) && $num_items > $max_items ) ) {
					$is_valid = false;
				}
			}

			return $is_valid;
		}

		/**
		 * check if the subtotal condition is satisfied
		 *
		 * @param array $condition
		 * @param array $conditions
		 *
		 * @return bool
		 * @since 2.0
		 *
		 * @author YITH
		 */
		public function valid_cart_subtotal_cart_condition( $condition, $conditions ) {

			$is_valid = true;

			$min_subtotal = ! empty( $condition['min_subtotal'] ) ? $condition['min_subtotal'] : 1;
			$max_subtotal = ! empty( $condition['max_subtotal'] ) ? $condition['max_subtotal'] : '';
			$subtotal     = $this->get_cart_subtotal( $conditions );

			if ( $subtotal < $min_subtotal || ( ! empty( $max_subtotal ) && $subtotal > $max_subtotal ) ) {
				$is_valid = false;
			}

			return $is_valid;
		}

		/**
		 * check if the current product condition is valid
		 *
		 * @param array $condition
		 * @param array $conditions
		 *
		 * @return bool
		 */
		public function valid_product_cart_condition( $condition, $conditions ) {

			$is_valid = true;

			$type_check = ! empty( $condition['product_type'] ) ? $condition['product_type'] : '';

			if ( 'require_product' === $type_check ) {

				$require_product     = yith_plugin_fw_is_true( $condition['enable_require_product'] );
				$require_product_cat = yith_plugin_fw_is_true( $condition['enable_require_product_categories'] );
				$require_product_tag = yith_plugin_fw_is_true( $condition['enable_require_product_tag'] );

				$is_valid = false;
				if ( $require_product ) {
					$product_list = ! empty( $condition['require_product_list'] ) ? $condition['require_product_list'] : array();
					$is_all       = 'at_least' !== $condition['require_product_list_mode'];

					$is_valid = $this->validate_taxonomy_in_cart( $product_list, 'product', $is_all );
				}
				if ( ! $is_valid && $require_product_cat ) {
					$cat_list = ! empty( $condition['require_product_category_list'] ) ? $condition['require_product_category_list'] : array();
					$is_all   = 'at_least' !== $condition['require_product_cat_list_mode'];
					$is_valid = $this->validate_taxonomy_in_cart( $cat_list, 'product_cat', $is_all );

				}

				if ( ! $is_valid && $require_product_tag ) {
					$tag_list = ! empty( $condition['require_product_tag_list'] ) ? $condition['require_product_tag_list'] : array();
					$is_all   = 'at_least' !== $condition['require_product_tag_list_mode'];

					$is_valid = $this->validate_taxonomy_in_cart( $tag_list, 'product_tag', $is_all );
				}
			} elseif ( 'disable_product' === $type_check ) {

				$disable_product     = yith_plugin_fw_is_true( $condition['enable_disable_require_product'] );
				$disable_product_cat = yith_plugin_fw_is_true( $condition['enable_disable_product_categories'] );
				$disable_product_tag = yith_plugin_fw_is_true( $condition['disable_exclude_product_tag'] );
				$is_disable          = false;
				if ( $disable_product ) {
					$disable_product_list = ! empty( $condition['disable_product_list'] ) ? $condition['disable_product_list'] : array();
					$is_disable           = ! $this->validate_taxonomy_in_cart( $disable_product_list, 'product', false, true );
				}

				if ( ! $is_disable && $disable_product_cat ) {
					$disable_product_cat_list = ! empty( $condition['disable_product_cat_list'] ) ? $condition['disable_product_cat_list'] : array();
					$is_disable               = ! $this->validate_taxonomy_in_cart( $disable_product_cat_list, 'product_cat', false, true );
				}
				if ( ! $is_disable && $disable_product_tag ) {
					$disable_product_tag_list = ! empty( $condition['disable_product_tag_list'] ) ? $condition['disable_product_tag_list'] : array();
					$is_disable               = ! $this->validate_taxonomy_in_cart( $disable_product_tag_list, 'product_tag', false, true );
				}

				$is_valid = ! $is_disable;
			}

			return apply_filters( 'ywdpd_valid_product_cart_condition', $is_valid, $condition, $conditions );

		}

		/**
		 * check if each cart item quantity is valid
		 *
		 * @param int $min_qty
		 * @param int $max_qty
		 *
		 * @return bool
		 * @since 2.0
		 * @author YITH
		 */
		public function check_cart_item_quantity( $min_qty, $max_qty ) {
			$is_valid = true;

			if ( ! is_null( WC()->cart ) && ! WC()->cart->is_empty() ) {

				foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {

					$qty = $cart_item['quantity'];

					if ( ( $qty < $min_qty ) || ( ! empty( $max_qty ) && $qty > $max_qty ) ) {
						return false;
					}
				}
			}

			return $is_valid;
		}

		/**
		 * get the right cart subtotal
		 *
		 * @param array $conditions
		 *
		 * @return float
		 */
		public function 
		get_cart_subtotal( $conditions ) {
			$subtotal = 0;

			if ( ! empty( WC()->cart ) && ! WC()->cart->is_empty() ) {

				$tax_excluded = YITH_WC_Dynamic_Options::how_calculate_discounts() == 'tax_excluded';

				foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {

					$product = $cart_item['data'];
					if ( ! $this->product_is_excluded_from_conditions( $product, $conditions ) ) {
						if ( !empty( $product->get_meta('custom_price',true) ) ) {
							$custom_price = $product->get_meta('custom_price', true );
							if( $tax_excluded ) {
								$subtotal += wc_get_price_excluding_tax( $product , array( 'price' => $custom_price, 'qty' => $cart_item['quantity'] ) );
							} else {
								$subtotal += wc_get_price_including_tax( $product , array( 'price' => $custom_price, 'qty' => $cart_item['quantity'] ) );

							}
					}else{
						$subtotal += $tax_excluded ? $cart_item['line_subtotal'] : $cart_item['line_subtotal'] + $cart_item['line_subtotal_tax'];

					}
				}
			}

			return $subtotal;
		}
	}

		/**
		 * @param WC_Product $product
		 * @param array      $conditions
		 *
		 * @return bool
		 */
		public function product_is_excluded_from_conditions( $product, $conditions ) {

			$is_excluded = false;

			if ( ! is_array( $conditions ) ) {
				$conditions = array();
			}

			$product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
			foreach ( $conditions as $condition ) {

				$type = ! empty( $condition['condition_for'] ) ? $condition['condition_for'] : '';
				if ( 'product' === $type ) {

					$type_check = isset( $condition['product_type'] ) ? $condition['product_type'] : '';

					if ( 'exclude_product' === $type_check ) {
						$exclude_product     = yith_plugin_fw_is_true( $condition['enable_exclude_require_product'] );
						$exclude_onsale      = yith_plugin_fw_is_true( $condition['enable_exclude_on_sale_product'] );
						$exclude_product_cat = yith_plugin_fw_is_true( $condition['enable_exclude_product_categories'] );
						$exclude_product_tag = yith_plugin_fw_is_true( $condition['enable_exclude_product_tag'] );

						if ( $exclude_product ) {

							$exclude_product_list = ! empty( $condition['exclude_product_list'] ) ? $condition['exclude_product_list'] : array();

							$is_excluded = in_array( $product->get_id(), $exclude_product_list );
						}

						if ( $exclude_onsale && $product->is_on_sale() ) {
							$is_excluded = true;
						}

						if ( ! $is_excluded && $exclude_product_cat ) {
							$exclude_product_cat_list = ! empty( $condition['exclude_product_category_list'] ) ? $condition['exclude_product_category_list'] : array();
							$is_excluded              = $this->check_taxonomy( $exclude_product_cat_list, $product_id, 'product_cat' );
						}

						if ( ! $is_excluded && $exclude_product_tag ) {
							$exclude_product_tag_list = ! empty( $condition['exclude_product_tag_list'] ) ? $condition['exclude_product_tag_list'] : array();
							$is_excluded              = $this->check_taxonomy( $exclude_product_tag_list, $product_id, 'product_tag' );
						}

						if ( $is_excluded ) {
							break;
						}
					}
				}
			}

			return apply_filters( 'ywdpd_valid_product_exclude_cart_condition', $is_excluded, $conditions, $product );
		}


		public function validate_taxonomy_in_cart( $list, $taxonomy_name, $all = false, $disable = false ) {
			$is_valid = false;
			if ( count( $list ) > 0 ) {
				$get_by        = is_numeric( $list[0] ) ? 'ids' : 'slugs';
				$list_of_items = array();

				foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {
					if ( 'product' === $taxonomy_name ) {
						$product_id      = ! empty( $cart_item['variation_id'] ) && $cart_item['variation_id'] > 0 ? $cart_item['variation_id'] : $cart_item['product_id'];
						$list_of_items[] = $product_id;
					} else {

						$new_list_of_items = wc_get_product_terms( $cart_item['product_id'], $taxonomy_name, array( 'fields' => $get_by ) );
						$list_of_items     = array_merge( $list_of_items, $new_list_of_items );
					}
				}

				$list_of_items = array_unique( $list_of_items );

				if ( $all ) {
					$is_valid = count( array_diff( $list, $list_of_items ) ) === 0;

				} else {
					$is_valid = count( array_intersect( $list, $list_of_items ) ) > 0;
				}

				if ( $disable ) {
					$is_valid = ! $is_valid;
				}
			}

			return $is_valid;

		}

		/**
		 * Validate product in cart
		 *
		 * @param string $type Product list condition.
		 * @param array  $product_list Product list.
		 *
		 * @return bool
		 */
		public function validate_product_in_cart( $type, $product_list ) {
			$is_valid = false;

			if ( ! $product_list || ! is_array( $product_list ) ) {
				return $is_valid;
			}

			$get_by    = is_numeric( $product_list[0] ) ? 'ids' : 'slugs';
			$search_by = is_numeric( $product_list[0] ) ? 'id' : 'slug';

			switch ( $type ) {
				case 'products_list':
					foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
						if ( $this->product_in_list( $cart_item, $product_list ) ) {
							$is_valid = true;
						}
					}
					break;
				case 'products_list_and':
					foreach ( $product_list as $pl ) {
						if ( $this->find_product_in_cart( $pl ) !== '' ) {
							$is_valid = true;
						} else {
							$is_valid = false;
							break;
						}
					}
					break;
				case 'products_list_excluded':
					foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
						if ( ! $this->product_in_list( $cart_item, $product_list ) ) {
							$is_valid = true;
						} else {
							$is_valid = false;
							break;
						}
					}
					break;
				case 'categories_list':
					$categories_list = $product_list;
					foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
						$categories_of_item = wc_get_product_terms( $cart_item['product_id'], 'product_cat', array( 'fields' => $get_by ) );
						$intersect          = array_intersect( $categories_of_item, $categories_list );
						if ( ! empty( $intersect ) ) {
							$is_valid = true;
						}
					}

					break;
				case 'categories_list_and':
					$categories_list = $product_list;

					foreach ( $categories_list as $category_id ) {
						$term = get_term_by( $search_by, $category_id, 'product_cat' );
						if ( is_a( $term, 'WP_Term' ) && $this->find_taxonomy_in_cart( $term->term_id, 'product_cat' ) !== '' ) {
							$is_valid = true;
						} else {
							$is_valid = false;
							break;
						}
					}
					break;
				case 'categories_list_excluded':
					$is_valid        = true;
					$categories_list = $product_list;
					foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
						$categories_of_item = wc_get_product_terms( $cart_item['product_id'], 'product_cat', array( 'fields' => $get_by ) );
						$intersect          = array_intersect( $categories_of_item, $categories_list );
						if ( ! empty( $intersect ) ) {
							$is_valid = false;
						}
					}

					break;
				case 'tags_list':
					$tags_list = $product_list;
					foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
						$tags_of_item = wc_get_product_terms( $cart_item['product_id'], 'product_tag', array( 'fields' => $get_by ) );
						$intersect    = array_intersect( $tags_of_item, $tags_list );
						if ( ! empty( $intersect ) ) {
							$is_valid = true;
						}
					}
					break;
				case 'tags_list_and':
					$tags_list = $product_list;
					foreach ( $tags_list as $tag_id ) {
						$term = get_term_by( $search_by, $tag_id, 'product_tag' );
						if ( is_a( $term, 'WP_Term' ) && $this->find_taxonomy_in_cart( $term->term_id, 'product_tag' ) !== '' ) {
							$is_valid = true;
						} else {
							$is_valid = false;
							break;
						}
					}
					break;
				case 'tags_list_excluded':
					$is_valid  = true;
					$tags_list = $product_list;
					foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
						$tags_of_item = wc_get_product_terms( $cart_item['product_id'], 'product_tag', array( 'fields' => $get_by ) );
						$intersect    = array_intersect( $tags_of_item, $tags_list );
						if ( ! empty( $intersect ) ) {
							$is_valid = false;
						}
					}
					break;
				default:
					$is_valid = apply_filters( 'ywdpd_validate_product_in_cart', $is_valid, $type, $product_list );
			}

			return $is_valid;
		}

		/**
		 * Check if in the cart there a taxonomy
		 *
		 * @param integer $taxonomy_id Taxonomy id.
		 * @param string  $taxonomy Taxonomy.
		 *
		 * @return int|string
		 */
		public function find_taxonomy_in_cart( $taxonomy_id, $taxonomy ) {
			$is_in_cart = '';
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				$taxonomy_of_item = wc_get_product_terms( $cart_item['product_id'], $taxonomy, array( 'fields' => 'ids' ) );

				if ( ! empty( $taxonomy_of_item ) && in_array( $taxonomy_id, $taxonomy_of_item ) ) {
					$is_in_cart = $cart_item_key;
				}
			}

			return $is_in_cart;

		}

		/**
		 * Check if a product is in cart
		 *
		 * @param integer $product_id Product id.
		 *
		 * @return int|string
		 */
		public function find_product_in_cart( $product_id ) {
			$is_in_cart = '';

			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				if ( ( isset( $cart_item['variation_id'] ) && $product_id == $cart_item['variation_id'] ) || $product_id == $cart_item['product_id'] ) {
					$is_in_cart = $cart_item_key;
				}
			}

			return $is_in_cart;
		}

		/**
		 * Return the number of valid product to adjust
		 *
		 * @param array $rule Rule.
		 * @param array $cart_item Cart Item.
		 * @param bool  $clean Bool.
		 *
		 * @return int|string
		 * @since 1.1.0
		 */
		public function num_valid_product_to_adjust_in_cart( $rule, $cart_item, $clean = false ) {
			$num        = 0;
			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$product    = wc_get_product( $product_id );

			if ( in_array( $rule['quantity_based'], array( 'cart_line', 'single_variation_product' ) )
				 || ( 'single_product' === $rule['quantity_based'] && ! $product->is_type( 'variation' ) )
			) {
				if ( $clean ) {
					if ( $this->valid_product_to_apply( $rule, $cart_item['data'], true ) && ! $this->valid_product_to_adjust( $rule, $cart_item ) ) {
						$num = $cart_item['available_quantity'];
					}
				} else {
					if ( $this->valid_product_to_apply( $rule, $cart_item['data'], true ) && $this->valid_product_to_adjust( $rule, $cart_item ) ) {
						$num = isset( $cart_item['available_quantity'] ) ? $cart_item['available_quantity'] : 1;
					}
				}
			} elseif ( 'single_product' === $rule['quantity_based'] && $product->is_type( 'variation' ) ) {
				$parent_id = method_exists( $product, 'get_parent_id' ) ? $product->get_parent_id() : $product->post->ID;

				foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_it ) {
					if ( $cart_it['variation_id'] && $parent_id === $cart_it['product_id'] ) {
						if ( $clean ) {
							if ( $this->valid_product_to_adjust( $rule, $cart_it ) && ! $this->valid_product_to_apply( $rule, $cart_it['data'], true ) ) {
								$num += $cart_it['quantity'];
							}
						} else {
							if ( $this->valid_product_to_adjust( $rule, $cart_it ) && $this->valid_product_to_apply( $rule, $cart_it['data'], true ) ) {
								$num += $cart_it['quantity'];
							}
						}
					}
				}
			} else {
				foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_it ) {
					if ( $clean ) {
						if ( $this->valid_product_to_adjust( $rule, $cart_it ) && ! $this->valid_product_to_apply( $rule, $cart_it['data'], true ) ) {
							$num += $cart_it['quantity'];
						}
					} else {
						if ( $this->valid_product_to_adjust( $rule, $cart_it ) && $this->valid_product_to_apply( $rule, $cart_it['data'], true ) ) {
							$num += $cart_it['quantity'];
						}
					}
				}
			}

			return $num;
		}

		/**
		 * Return the number of valid product to adjust
		 *
		 * @param array $rule Rule.
		 * @param array $cart_item Cart Item.
		 * @param bool  $clean Bool.
		 *
		 * @return int|string
		 * @since 1.1.0
		 */
		public function num_valid_product_to_apply_in_cart( $rule, $cart_item, $clean = false ) {
			$num        = 0;
			$product_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			$product    = wc_get_product( $product_id );

			if ( in_array( $rule['quantity_based'], array( 'cart_line', 'single_variation_product' ) )
				 || ( 'single_product' === $rule['quantity_based'] && ! $product->is_type( 'variation' ) )
			) {
				if ( $clean ) {
					if ( $this->valid_product_to_apply( $rule, $cart_item['data'], true ) && ! $this->valid_product_to_adjust( $rule, $cart_item ) ) {
						$num = $cart_item['available_quantity'];
					}
				} else {
					if ( $this->valid_product_to_apply( $rule, $cart_item['data'], true ) && $this->valid_product_to_adjust( $rule, $cart_item ) ) {
						$num = isset( $cart_item['available_quantity'] ) ? $cart_item['available_quantity'] : 1;
					}
				}
			} elseif ( 'single_product' === $rule['quantity_based'] && $product->is_type( 'variation' ) ) {

				$parent_id = yit_get_base_product_id( $product );

				foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_it ) {

					if ( $cart_it['variation_id'] && $parent_id === $cart_it['product_id'] ) {
						if ( $clean ) {
							if ( $this->valid_product_to_apply( $rule, $cart_it['data'], true ) && ! $this->valid_product_to_adjust( $rule, $cart_it ) ) {
								$num += $cart_it['quantity'];
							}
						} else {
							if ( $this->valid_product_to_apply( $rule, $cart_it['data'], true ) && $this->valid_product_to_adjust( $rule, $cart_it ) ) {
								$num += $cart_it['quantity'];
							}
						}
					}
				}
			} else {
				foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_it ) {
					if ( $clean ) {
						if ( $this->valid_product_to_apply( $rule, $cart_it['data'], true ) && ! $this->valid_product_to_adjust( $rule, $cart_it ) ) {
							$num += $cart_it['available_quantity'];
						}
					} else {
						if ( $this->valid_product_to_apply( $rule, $cart_it['data'], true ) && $this->valid_product_to_adjust( $rule, $cart_it ) ) {
							$num += $cart_it['available_quantity'];
						}
					}
				}
			}

			return $num;
		}


		/**
		 * Check if the product of the cart item is in a list
		 *
		 * @param array $cart_item Cart Item.
		 * @param array $product_list Product list.
		 *
		 * @return bool
		 * @since 1.1.0
		 */
		public function product_in_list( $cart_item, $product_list ) {
			return ( ( isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] && in_array( $cart_item['variation_id'], $product_list ) ) || in_array( $cart_item['product_id'], $product_list ) );
		}

		/**
		 * Sorting.
		 *
		 * @param array $cart_item_a Cart item.
		 * @param array $cart_item_b Cart item.
		 *
		 * @return bool
		 */
		public static function sort_by_price( $cart_item_a, $cart_item_b ) {
			return $cart_item_a['data']->get_price() > $cart_item_b['data']->get_price();
		}

		/**
		 * Sorting descendant.
		 *
		 * @param array $cart_item_a Cart item.
		 * @param array $cart_item_b Cart item.
		 *
		 * @return bool
		 */
		public static function sort_by_price_desc( $cart_item_a, $cart_item_b ) {
			return $cart_item_a['data']->get_price() < $cart_item_b['data']->get_price();
		}

		/**
		 * Check taxonomy.
		 *
		 * @param array  $list List.
		 * @param string $item Product id.
		 * @param string $taxonomy_name Taxonomy.
		 * @param bool   $in Bool.
		 *
		 * @return bool
		 */
		public function check_taxonomy( $list, $item, $taxonomy_name, $in = true ) {
			$excluded = false;
			$product  = wc_get_product( $item );
			$item     = $product->get_parent_id() ? $product->get_parent_id() : $item;

			$get_by       = is_numeric( $list[0] ) ? 'ids' : 'slugs';
			$list_of_item = wc_get_product_terms( $item, $taxonomy_name, array( 'fields' => $get_by ) );
			$intersect    = array_intersect( $list_of_item, $list );
			if ( ! empty( $intersect ) ) {
				$excluded = true;
			}

			return $in ? $excluded : ! $excluded;
		}

		/**
		 * Check the quantity products of a specific taxonomy.
		 *
		 * @param array  $list List.
		 * @param string $taxonomy_name Taxonomy
		 * @param bool   $in Bool.
		 *
		 * @return int
		 */
		public function check_taxonomy_quantity( $list, $taxonomy_name, $in = true ) {

			$quantity = 0;
			$get_by   = is_numeric( $list[0] ) ? 'ids' : 'slugs';

			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				$list_of_item = wc_get_product_terms( $cart_item['product_id'], $taxonomy_name, array( 'fields' => $get_by ) );
				$intersect    = array_intersect( $list_of_item, $list );

				$check = $in ? ! empty( $intersect ) : empty( $intersect );
				if ( $check ) {
					$quantity += $cart_item['quantity'];
				}
			}

			return $quantity;
		}

		/**
		 * Check cart item exclusion.
		 *
		 * @param array $cart_item Cart item.
		 *
		 * @return mixed
		 */
		public function check_cart_item_filter_exclusion( $cart_item ) {
			if ( isset( $cart_item['product_id'] ) ) {
				$product = wc_get_product( $cart_item['product_id'] );

				return apply_filters( 'ywdpd_exclude_products_from_discount', false, $product );
			}
		}


		/**
		 * WPML adjust.
		 *
		 * @param array  $list List.
		 * @param string $type_of_list Type of list.
		 *
		 * @return mixed
		 */
		public function wpml_product_list_adjust( $list, $type_of_list ) {
			if ( $list ) {
				$trans_products = $list;
				$object_type    = false;
				switch ( $type_of_list ) {
					case 'all_products':
					case 'specific_products':
					case 'require_product_list':
					case 'exclude_product_list':
					case 'disable_product_list':
						$object_type = 'product';
						break;
					case 'specific_categories':
					case 'require_product_category_list':
					case 'exclude_product_category_list':
					case 'disable_product_category_list':
						$object_type = 'product_cat';
						break;
					case 'specific_tag':
					case 'require_product_tag_list':
					case 'exclude_product_tag_list':
					case 'disable_product_tag_list':
						$object_type = 'product_tag';
						break;
					default:
						apply_filters( 'wpml_product_list_adjust_type_of_list', $object_type, $type_of_list );
				}

				if ( $object_type ) {
					foreach ( $list as $object_id ) {
						$p = wpml_object_id_filter( $object_id, $object_type, true, wpml_get_current_language() );

						if ( ! in_array( $p, $trans_products ) ) {
							$trans_products[] = $p;
						}
					}
					$list = $list + $trans_products;
				}
			}

			return $list;
		}

		/**
		 * check if in the cart there are special offer that can be applied in popup
		 *
		 * @param array  $cart_item ,
		 * @param string $cart_item_key
		 *
		 * @return array
		 * @author YITH
		 * @since 2.1
		 */
		public function get_valid_special_offer_to_apply( $cart_item, $cart_item_key ) {

			$special_offers = array();

			if ( ! is_null( WC()->cart ) && ! WC()->cart->is_empty() ) {

				foreach ( YITH_WC_Dynamic_Pricing()->validated_rules as $key_rule => $rule ) {
					if ( isset( $rule['discount_mode'] ) && 'special_offer' === $rule['discount_mode'] ) {
						$can_in_popup = isset( $rule['can_special_offer_in_popup'] ) && yith_plugin_fw_is_true( $rule['can_special_offer_in_popup'] );
						if ( $can_in_popup ) {

							$is_valid = $this->validate_apply_to_cart_item( $rule, $cart_item_key, $cart_item );

							if ( $is_valid ) {

								$discount = $this->get_special_offer_discount( $rule, $cart_item );

								$total_to_add = isset( $discount['discount_amount']['total_target'] ) ? $discount['discount_amount']['total_target'] : 0;

								$is_active_apply_to_another_product = isset( $rule['active_apply_discount_to'] ) && yith_plugin_fw_is_true( $rule['active_apply_discount_to'] );

								if ( $is_active_apply_to_another_product ) {

									$total_added = $this->get_total_product_with_special_offer( $rule );

									if ( $total_to_add - $total_added > 0 ) {
										if ( ! in_array( $rule['key'], $special_offers, true ) ) {
											$rule_text = isset( $rule['text_in_modal_special_offer'] ) ? $rule['text_in_modal_special_offer'] : __( 'Get a special discount if you add {{total_to_add}} product(s) to your order.', 'ywdpd' );

											$special_offer                  = array(
												'text'     => $rule_text,
												'items_in_cart' => $total_added,
												'total_to_add' => $total_to_add,
												'allowed_item' => $total_to_add - $total_added,
												'items'    => $this->get_special_offer_items_to_show_in_popup( $rule ),
												'type'     => 'special_offer',
												'discount' => array(
													'type' => $discount['discount_amount']['type'],
													'amount' => $discount['discount_amount']['amount'],
												),
											);
											$special_offers[ $rule['key'] ] = $special_offer;
										}
									}
								}
							}
						}
					}
				}
			}

			return $special_offers;
		}

		/**
		 * get how many products have this special offer applied in the cart
		 *
		 * @auhtor YITH
		 *
		 * @param array $rule
		 *
		 * @return int
		 * @since 2.1
		 */
		public function get_total_product_with_special_offer( $rule ) {
			$total = 0;

			if ( ! is_null( WC()->cart ) && ! WC()->cart->is_empty() ) {
				$rule_to_check = $rule['id'];

				foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {
					if ( isset( $cart_item['ywdpd_discounts'][ $rule['key'] ] ) ) {
						$discount = $cart_item['ywdpd_discounts'][ $rule['key'] ];
						$rule_id  = $discount['rule_id'];
						if ( $rule_to_check === $rule_id && 'applied' === $discount['status'] ) {
							$total += $cart_item['quantity'];
						}
					}
				}
			}

			return $total;
		}

		/**
		 * Get the ids to show in the popup
		 *
		 * @auhtor YITH
		 *
		 * @param array $rule
		 *
		 * @return array
		 * @since 2.1
		 */
		public function get_special_offer_items_to_show_in_popup( $rule ) {
			$items = array(
				'type'     => '',
				'item_ids' => array(),
			);

			$apply_special_offer_to = isset( $rule['rule_apply_adjustment_discount_for'] ) ? $rule['rule_apply_adjustment_discount_for'] : 'all_products';

			if ( 'all_products' === $apply_special_offer_to ) {
				$args              = array(
					'limit'   => 10,
					'status'  => 'publish',
					'type'    => array( 'simple', 'variable' ),
					'orderby' => 'rand',
					'return'  => 'ids',
				);
				$is_exclude_active = isset( $rule['active_apply_adjustment_to_exclude'] ) && yith_plugin_fw_is_true( $rule['active_apply_adjustment_to_exclude'] );

				if ( $is_exclude_active ) {
					$exclude_for = isset( $rule['exclude_apply_adjustment_rule_for'] ) ? $rule['exclude_apply_adjustment_rule_for'] : 'specific_products';

					switch ( $exclude_for ) {
						case 'specific_products':
							$product_to_exclude = $rule['apply_adjustment_products_list_excluded'];
							break;
						case 'specific_categories':
							$category_ids = isset( $rule['apply_adjustment_categories_list_excluded'] ) ? $rule['apply_adjustment_categories_list_excluded'] : array();
							if ( is_array( $category_ids ) && count( $category_ids ) > 0 ) {
								$tax_query = WC()->query->get_tax_query(
									array(
										array(
											'taxonomy' => 'product_cat',
											'terms'    => array_values( $category_ids ),
											'operator' => 'IN',
										),
									)
								);

								$product_to_exclude = get_posts(
									array(
										'numberposts' => - 1,
										'post_type'   => array( 'product' ),
										'post_status' => 'publish',
										'tax_query'   => $tax_query,
										'fields'      => 'ids',
									)
								);
							}
							break;
						case 'specific_tag':
							$tag_ids = isset( $rule['apply_adjustment_tags_list_excluded'] ) ? $rule['apply_adjustment_tags_list_excluded'] : array();
							if ( is_array( $tag_ids ) && count( $tag_ids ) > 0 ) {
								$tax_query = WC()->query->get_tax_query(
									array(
										array(
											'taxonomy' => 'product_tag',
											'terms'    => array_values( $tag_ids ),
											'operator' => 'IN',
										),
									)
								);

								$product_to_exclude = get_posts(
									array(
										'numberposts' => - 1,
										'post_type'   => array( 'product' ),
										'post_status' => 'publish',
										'tax_query'   => $tax_query,
										'fields'      => 'ids',
									)
								);
							}
							break;
						default:
							$product_to_exclude = apply_filters( 'ywdpd_get_product_ids_to_exclude', array(), $rule, $exclude_for );

							break;
					}

					if ( is_array( $product_to_exclude ) && count( $product_to_exclude ) > 0 ) {
						$args['exclude'] = $product_to_exclude;
					}
				}
				$items['type']     = 'product_ids';
				$items['item_ids'] = wc_get_products( $args );
			} else {

				switch ( $apply_special_offer_to ) {
					case 'specific_products':
						$items['type']               = 'product_ids';
						$product_taxonomy_to_include = isset( $rule['apply_adjustment_products_list'] ) ? $rule['apply_adjustment_products_list'] : array();
						break;
					case 'specific_categories':
						$items['type']               = 'product_categories';
						$product_taxonomy_to_include = isset( $rule['apply_adjustment_categories_list'] ) ? $rule['apply_adjustment_categories_list'] : array();
						break;
					case 'specific_tag':
						$items['type']               = 'product_tag';
						$product_taxonomy_to_include = isset( $rule['apply_adjustment_tags_list'] ) ? $rule['apply_adjustment_tags_list'] : array();
						break;
					default:
						$items['type']               = apply_filters( 'ywdpd_special_offer_item_type', '', $rule, $apply_special_offer_to );
						$product_taxonomy_to_include = apply_filters( 'ywdpd_get_product_taxonomy_ids_to_include', array(), $rule, $apply_special_offer_to );
						break;
				}

				if ( is_array( $product_taxonomy_to_include ) && count( $product_taxonomy_to_include ) > 0 ) {
					$items['item_ids'] = $product_taxonomy_to_include;
				}
			}

			return $items;
		}

	}
}


/**
 * Unique access to instance of YITH_WC_Dynamic_Pricing_Helper class
 *
 * @return YITH_WC_Dynamic_Pricing_Helper
 */
function YITH_WC_Dynamic_Pricing_Helper() {
	return YITH_WC_Dynamic_Pricing_Helper::get_instance();
}

YITH_WC_Dynamic_Pricing_Helper();
