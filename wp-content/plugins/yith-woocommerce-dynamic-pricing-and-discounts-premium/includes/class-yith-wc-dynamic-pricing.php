<?php
/**
 * Main class.
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
 * Implements features of YITH WooCommerce Dynamic Pricing and Discounts
 *
 * @class   YITH_WC_Dynamic_Pricing
 * @package YITH WooCommerce Dynamic Pricing and Discounts
 * @since   1.0.0
 * @author  YITH
 */
if ( ! class_exists( 'YITH_WC_Dynamic_Pricing' ) ) {

	/**
	 * Class YITH_WC_Dynamic_Pricing
	 */
	class YITH_WC_Dynamic_Pricing {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Dynamic_Pricing
		 */
		protected static $instance;

		/**
		 * The name for the plugin options
		 *
		 * @access public
		 * @var string
		 * @since  1.0.0
		 */
		public $plugin_options = 'yit_ywdpd_options';

		/**
		 * Validated rules
		 *
		 * @var array
		 */
		public $validated_rules = array();

		/**
		 * Exclusion rules
		 *
		 * @var array
		 */
		public $exclusion_rules = array();

		/**
		 * Adjust rules
		 *
		 * @var array
		 */
		public $adjust_rules = array();

		/**
		 * Adjust counter
		 *
		 * @var array
		 */
		public $adjust_counter = array();

		/**
		 * Pricing rules options
		 *
		 * @var array
		 */
		public $pricing_rules_options = array();

		/**
		 * Cart rules options
		 *
		 * @var array
		 */
		public $cart_rules_options = array();

		/**
		 * Check discount
		 *
		 * @var array
		 */
		protected $check_discount = array();

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Dynamic_Pricing
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
		 */
		public function __construct() {

			$this->pricing_rules_options = include YITH_YWDPD_DIR . 'plugin-options/pricing-rules-options.php';
			$this->cart_rules_options    = include YITH_YWDPD_DIR . 'plugin-options/cart-rules-options.php';

			/* plugin */
			add_action( 'plugins_loaded', array( $this, 'plugin_fw_loader' ), 15 );

			if ( YITH_WC_Dynamic_Options::can_wpml_extend_to_translated_object() ) {
				add_filter( 'ywdpd_pricing_rules_filtered', array( $this, 'adjust_rules_for_wpml' ) );
			}

			add_filter( 'ywdpd_change_dynamic_price', array( $this, 'calculate_role_price_for_fix_dynamic_price' ), 10, 3 );

			add_action( 'yith_dynamic_pricing_after_apply_discounts', array( $this, 'change_default_price' ), 20, 1 );

			if ( defined( 'ELEMENTOR_VERSION' ) ) {
				require_once YITH_YWDPD_INC . '/compatibility/elementor/class.yith-wc-dynamic-elementor.php';
			}

			add_filter( 'ywdpd_round_total_price', array( $this, 'deactivate_round_price' ), 999, 1 );

		}

		/**
		 * Adjust rules for WPML
		 *
		 * @param array $rules Rules.
		 *
		 * @return mixed
		 */
		public function adjust_rules_for_wpml( $rules ) {
			global $sitepress;
			$has_wpml = ! empty( $sitepress ) ? true : false;

			if ( ! $has_wpml ) {
				return $rules;
			}

			foreach ( $rules as $key => $rule ) {

				$apply_to_type = $rule['rule_for'];

				if ( 'all_products' !== $apply_to_type ) {

					switch ( $apply_to_type ) {
						case 'specific_products':
							$rule['rule_for_products_list'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['rule_for_products_list'], $apply_to_type );
							break;
						case 'specific_categories':
							$rule['rule_for_categories_list'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['rule_for_categories_list'], $apply_to_type );
							break;
						case 'specific_tag':
							$rule['rule_for_tags_list'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['rule_for_tags_list'], $apply_to_type );
							break;

					}
				}

				$is_active_exclude     = isset( $rule['active_exclude'] ) && yith_plugin_fw_is_true( $rule['active_exclude'] );
				$apply_to_exclude_type = isset( $rule['exclude_rule_for'] ) ? $rule['exclude_rule_for'] : '';
				if ( $is_active_exclude ) {
					switch ( $apply_to_exclude_type ) {
						case 'specific_products':
							$rule['exclude_rule_for_products_list'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['exclude_rule_for_products_list'], $apply_to_exclude_type );
							break;
						case 'specific_categories':
							$rule['exclude_rule_for_categories_list'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['exclude_rule_for_categories_list'], $apply_to_exclude_type );
							break;
						case 'specific_tag':
							$rule['exclude_rule_for_tags_list'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['exclude_rule_for_tags_list'], $apply_to_exclude_type );
							break;
					}
				}

				$is_active_adjustment_to  = isset( $rule['active_apply_discount_to'] ) && yith_plugin_fw_is_true( $rule['active_apply_discount_to'] );
				$apply_adjustment_to_type = isset( $rule['rule_apply_adjustment_discount_for'] ) ? $rule['rule_apply_adjustment_discount_for'] : '';
				if ( $is_active_adjustment_to && 'all_products' !== $apply_adjustment_to_type ) {

					switch ( $apply_adjustment_to_type ) {
						case 'specific_products':
							$rule['apply_adjustment_products_list'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['apply_adjustment_products_list'], $apply_adjustment_to_type );
							break;
						case 'specific_categories':
							$rule['apply_adjustment_categories_list'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['apply_adjustment_categories_list'], $apply_adjustment_to_type );
							break;
						case 'specific_tag':
							$rule['apply_adjustment_tags_list'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['apply_adjustment_tags_list'], $apply_adjustment_to_type );
							break;

					}
				}

				$is_active_exclude_adjustment_to  = isset( $rule['active_apply_adjustment_to_exclude'] ) && yith_plugin_fw_is_true( $rule['active_apply_adjustment_to_exclude'] );
				$apply_exclude_adjustment_to_type = isset( $rule['exclude_apply_adjustment_rule_for'] ) ? $rule['exclude_apply_adjustment_rule_for'] : '';
				if ( $is_active_exclude_adjustment_to ) {
					switch ( $apply_exclude_adjustment_to_type ) {
						case 'specific_products':
							$rule['apply_adjustment_products_list_excluded'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['apply_adjustment_products_list_excluded'], $apply_exclude_adjustment_to_type );
							break;
						case 'specific_categories':
							$rule['apply_adjustment_categories_list_excluded'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['apply_adjustment_categories_list_excluded'], $apply_exclude_adjustment_to_type );
							break;
						case 'specific_tag':
							$rule['apply_adjustment_tags_list_excluded'] = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $rule['apply_adjustment_tags_list_excluded'], $apply_exclude_adjustment_to_type );
							break;

					}
				}

				$rules[ $key ] = $rule;
			}

			return $rules;
		}

		/**
		 * Return pricing rules filtered and validates
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		public function get_pricing_rules() {

			$pricing_rules = (array) $this->filter_valid_rules( $this->recover_pricing_rules() );

			return $pricing_rules;
		}

		/**
		 * Return te gift rules
		 *
		 * @return array
		 */
		public function get_gift_rules() {

			$all_rules = $this->get_pricing_rules();

			$gift_rules = array();

			if ( ! empty( $all_rules ) ) {

				foreach ( $all_rules as $key => $rule ) {

					if ( isset( $rule['discount_mode'] ) && 'gift_products' === $rule['discount_mode'] ) {
						$gift_rules[ $key ] = $rule;
					}
				}
			}

			return $gift_rules;
		}

		/**
		 * Recover pricing rules
		 *
		 * @return array
		 */
		public function recover_pricing_rules() {

			$pricing_rules = ywdpd_recover_rules( 'pricing' );

			return $pricing_rules;
		}

		/**
		 * Return pricing rules validates
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @param array $pricing_rules Pricing rules.
		 *
		 * @return array
		 * @since  1.0.0
		 */
		public function filter_valid_rules( $pricing_rules ) {

			if ( is_array( $pricing_rules ) && count( $pricing_rules ) > 0 ) {

				foreach ( $pricing_rules as $key => $rule ) {

					$is_valid = yith_plugin_fw_is_true( $rule['active'] );

					if ( ! apply_filters( 'ywdpd_rule_is_valid', $is_valid, $rule['id'], $rule ) ) {

						continue;
					}

					$from          = ! empty( $rule['schedule_discount_mode']['schedule_from'] ) ? $rule['schedule_discount_mode']['schedule_from'] : '';
					$to            = ! empty( $rule['schedule_discount_mode']['schedule_to'] ) ? $rule['schedule_discount_mode']['schedule_to'] : '';
					$schedule_type = ! empty( $rule['schedule_discount_mode']['schedule_type'] ) ? $rule['schedule_discount_mode']['schedule_type'] : '';

					if ( 'schedule_dates' === $schedule_type && ! YITH_WC_Dynamic_Pricing_Helper()->validate_schedule( $from, $to ) ) {

						continue;
					}

					if ( ! YITH_WC_Dynamic_Pricing_Helper()->validate_user( $rule ) ) {
						continue;
					}

					$discount_mode = $rule['discount_mode'];

					if ( 'discount_whole' === $discount_mode ) {
						$rule['discount_mode']            = 'bulk';
						$rule['rules']                    = array(
							array(
								'min_quantity'    => 1,
								'max_quantity'    => '*',
								'discount_amount' => $rule['simple_whole_discount']['discount_value'],
								'type_discount'   => $rule['simple_whole_discount']['discount_mode'],
							),
						);
						$rule['rule_for']                 = 'all_products';
						$rule['active_apply_discount_to'] = 'no';
						$rule['show_table_price']         = 'no';

					}

					// PRODUCTS VALIDATION (APPLY TO) check if the list of products or categories is empty.
					if ( ! isset( $rule['rule_for'] ) || ! YITH_WC_Dynamic_Pricing_Helper()->validate_apply_to_field( $rule['rule_for'], $rule ) ) {
						continue;
					}

					if ( 'gift_products' === $discount_mode && empty( $rule['gift_product_selection'] ) ) {
						continue;
					}

					// Check if the rule has exceptions.
					$active_exclude = isset( $rule['active_exclude'] ) && yith_plugin_fw_is_true( $rule['active_exclude'] );
					$excluded_type  = isset( $rule['exclude_rule_for'] ) ? $rule['exclude_rule_for'] : false;

					if ( $active_exclude && ! YITH_WC_Dynamic_Pricing_Helper()->validate_apply_to_field_excluded_products( $excluded_type, $rule ) ) {
						continue;
					}

					// Check if the Apply Adjustment to is enabled.

					$active_apply_adjustment = isset( $rule['active_apply_discount_to'] ) && yith_plugin_fw_is_true( $rule['active_apply_discount_to'] );
					$apply_adjustment_type   = isset( $rule['rule_apply_adjustment_discount_for'] ) ? $rule['rule_apply_adjustment_discount_for'] : '';
					if ( $active_apply_adjustment && ! YITH_WC_Dynamic_Pricing_Helper()->validate_apply_adjustment_to_field( $apply_adjustment_type, $rule ) ) {
						continue;
					}

					$active_excluded_apply_adjustment = isset( $rule['active_apply_adjustment_to_exclude'] ) && yith_plugin_fw_is_true( $rule['active_apply_adjustment_to_exclude'] );
					$excluded_type                    = isset( $rule['exclude_apply_adjustment_rule_for'] ) ? $rule['exclude_apply_adjustment_rule_for'] : false;

					if ( $active_excluded_apply_adjustment && ! YITH_WC_Dynamic_Pricing_Helper()->validate_apply_adjustment_to_field_excluded_products( $excluded_type, $rule ) ) {
						continue;
					}

					// if it's all ok is the rule is configured properly.
					// DISCOUNT RULES VALIDATION.
					if ( isset( $rule['discount_mode'] ) && $rule['discount_mode'] == 'bulk' ) {
						if ( isset( $rule['rules'] ) ) {
							foreach ( $rule['rules'] as $index => $discount_rule ) {

								if ( $discount_rule['min_quantity'] == '' || $discount_rule['min_quantity'] == 0 ) {
									$rule['rules'][ $index ]['min_quantity'] = 1;
								}

								if ( $discount_rule['max_quantity'] == '' ) {
									$rule['rules'][ $index ]['max_quantity'] = '*';
								}

								if ( isset( $discount_rule['type_discount'] ) && $discount_rule['type_discount'] == 'percentage' && $discount_rule['discount_amount'] > 0 ) {
									$rule['rules'][ $index ]['discount_amount'] = floatval( $discount_rule['discount_amount'] ) / 100;
								}
							}
						}
					} elseif ( isset( $rule['discount_mode'] ) && $rule['discount_mode'] == 'special_offer' ) {
						$special_offer = $rule['so-rule'];

						if ( $special_offer['purchase'] == '' || $special_offer['purchase'] == 0 ) {
							$rule['so-rule']['purchase'] = 1;
						}

						if ( $special_offer['receive'] == '' ) {
							$rule['so-rule']['receive'] = '*';
						}

						if ( $special_offer['type_discount'] == 'percentage' && $special_offer['discount_amount'] > 0 ) {
							$rule['so-rule']['discount_amount'] = $special_offer['discount_amount'] / 100;
						}

						if ( isset( $rule['so-repeat'] ) && yith_plugin_fw_is_true( $rule['so-repeat'] ) ) {

							$rule['so-rule']['repeat'] = true;
						}
					}

					if ( 'category_discount' === $rule['discount_mode'] ) {

						$sb_qty_rules = $rule['quantity_category_discount'];

						$i = 0;
						foreach ( $sb_qty_rules as $sb_qty_rule ) {

							if ( 'percentage' == $sb_qty_rule['type_discount'] ) {
								$sb_qty_rule['discount_amount'] = $sb_qty_rule['discount_amount'] / 100;
							}
							$rule['id']            = 'qty_cat_rule_' . $i;
							$rule['key']           = $key . '_' . $i;
							$rule['discount_mode'] = 'bulk';
							$rule['rules']         = array(
								array(
									'min_quantity'    => 1,
									'max_quantity'    => '*',
									'discount_amount' => $sb_qty_rule['discount_amount'],
									'type_discount'   => $sb_qty_rule['type_discount'],
								),

							);
							$rule['rule_for']                         = 'specific_categories';
							$rule['rule_for_categories_list']         = isset( $sb_qty_rule['product_cat'] ) ? array( $sb_qty_rule['product_cat'] ) : array();
							$rule['active_apply_discount_to']         = 'no';
							$rule['show_table_price']                 = 'no';
							$this->validated_rules[ $key . '_' . $i ] = $rule;

							$i ++;
						}
					} else {
						$this->validated_rules[ $key ] = $rule;
					}
				}
			}
			$this->validated_rules = apply_filters( 'ywdpd_pricing_rules_filtered', $this->validated_rules );

			return $this->validated_rules;
		}

		/**
		 * Add applied rules to single cart item
		 *
		 * @param string $cart_item_key Item key.
		 * @param array  $cart_item Cart Item.
		 *
		 * @return bool
		 *
		 * @since  1.0.0
		 */
		public function get_applied_rules_to_product( $cart_item_key, $cart_item ) {

			$exclude = apply_filters( 'ywdpd_get_applied_rules_to_product_exclude', empty( $cart_item ), $cart_item );

			if ( $exclude ) {
				return false;
			}

			foreach ( $this->validated_rules as $key_rule => $rule ) {

				// DISCOUNT CAN BE COMBINED WITH COUPON
				$with_other_coupons = isset( $rule['disable_with_other_coupon'] ) && yith_plugin_fw_is_true( $rule['disable_with_other_coupon'] );
				if ( $with_other_coupons && ywdpd_check_cart_coupon() ) {
					continue;
				}

				if ( ! YITH_WC_Dynamic_Pricing_Helper()->validate_apply_to( $key_rule, $rule, $cart_item_key, $cart_item ) ) {

					continue;
				}
			}
		}

		/**
		 * Add applied rules to single cart item
		 *
		 * @since  1.0.0
		 */
		public function get_exclusion_rules() {
			if ( ! empty( $this->exclusion_rules ) ) {
				return $this->exclusion_rules;
			}

			$exclusion_rules = array();

			foreach ( $this->validated_rules as $rule ) {
				if ( $rule['discount_mode'] == 'exclude_items' ) {
					$exclusion_rules[] = $rule;
				}
			}

			$this->exclusion_rules = $exclusion_rules;

			return $this->exclusion_rules;
		}

		/**
		 * Check discount
		 *
		 * @param WC_Product $product Product.
		 *
		 * @return bool
		 */
		public function check_discount( $product ) {

			if ( apply_filters( 'ywdpd_exclude_products_from_discount', false, $product ) ) {
				return false;
			}
			/*
			elseif ( isset( $this->check_discount[ $product->get_id() ] ) ) {
				return $this->check_discount[ $product->get_id() ];
			}
			**/

			$return          = false;
			$is_single_check = apply_filters( 'ywdpd_check_if_single_page', is_single() );

			foreach ( $this->validated_rules as $rule ) {
				$enable_adjustment_to = isset( $rule['active_apply_discount_to'] ) && yith_plugin_fw_is_true( $rule['active_apply_discount_to'] );

				$valid_product_function = 'valid_product_to_apply_bulk';
				$apply_to_valid         = true;
				if ( $enable_adjustment_to ) {
					$valid_product_function = 'valid_product_to_adjustment_bulk';
					$apply_to_valid         = YITH_WC_Dynamic_Pricing_Helper()->validate_apply_to_in_cart( $rule );
				}

				if ( $apply_to_valid && YITH_WC_Dynamic_Pricing_Helper()->$valid_product_function( $rule, $product, false ) && ( $is_single_check || ( isset( $rule['show_in_loop'] ) && yith_plugin_fw_is_true( $rule['show_in_loop'] ) ) ) ) {

					$return = true;
				}
			}

			$this->check_discount[ $product->get_id() ] = $return;

			return $return;
		}

		/**
		 * Return the discounted price
		 *
		 * @param float $default_price Default price.
		 * @param WC_Product $product Product.
		 *
		 * @return float
		 */
		public function get_discount_price( $default_price, $product ) {

			if ( empty( $default_price ) ) {
				return $default_price;
			}

			$current_difference = 0;

			$discount_price = floatval( $default_price );

			foreach ( $this->validated_rules as $rule ) {

				$even_onsale = isset( $rule['disable_on_sale'] ) && ! yith_plugin_fw_is_true( $rule['disable_on_sale'] );
				$sale_price  = $product->get_sale_price( 'edit' );

				if ( $sale_price != '' && $sale_price != $default_price && ! $even_onsale ) {
					continue;
				}
				$enable_adjustment_to = isset( $rule['active_apply_discount_to'] ) && yith_plugin_fw_is_true( $rule['active_apply_discount_to'] );

				$valid_product_function = 'valid_product_to_apply_bulk';
				$apply_to_valid         = true;

				if ( $enable_adjustment_to ) {

					$valid_product_function = 'valid_product_to_adjustment_bulk';
					$apply_to_valid         = YITH_WC_Dynamic_Pricing_Helper()->validate_apply_to_in_cart( $rule );
				}

				if ( $apply_to_valid && YITH_WC_Dynamic_Pricing_Helper()->$valid_product_function( $rule, $product, false ) && ( is_single() || ( isset( $rule['show_in_loop'] ) && yith_plugin_fw_is_true( $rule['show_in_loop'] ) && ! $enable_adjustment_to ) ) ) {

					$is_exclusive = ( isset( $rule['no_apply_with_other_rules'] ) && yith_plugin_fw_is_true( $rule['no_apply_with_other_rules'] ) );

					if ( $rule && isset( $rule['rules'] ) && $rule['rules'] ) {
						foreach ( $rule['rules'] as $qty_rule ) {
							if ( $qty_rule['min_quantity'] == 1 && is_numeric( $qty_rule['discount_amount'] ) ) {
								switch ( $qty_rule['type_discount'] ) {
									case 'percentage':
										$current_difference = $discount_price * $qty_rule['discount_amount'];
										break;
									case 'price':
										$current_difference = $qty_rule['discount_amount'];
										break;
									case 'fixed-price':
										$current_difference = $discount_price - $qty_rule['discount_amount'];
										break;
									default:
								}
							}
						}

						$discount_price = ( ( $discount_price - $current_difference ) < 0 ) ? 0 : ( $discount_price - $current_difference );

					}
					break;
					/*
					if( $is_exclusive ) {
						 break;
					 }*/
				}
			}

			return apply_filters( 'yith_ywdpd_get_discount_price', $discount_price );
		}

		/**
		 * Return all adjustments to single cart item
		 *
		 * @param array     $cart_item Cart item.
		 * @param     string  $cart_item_key Cart item key.
		 *
		 * @param bool          $reset
		 *
		 * @since  1.0.0
		 */
		public function apply_discount( $cart_item, $cart_item_key, $reset = false ) {

			$this->adjust_counter = $reset ? array() : $this->adjust_counter;

			$discounts = $cart_item['ywdpd_discounts'];

			$product_id    = ( isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] > 0 ) ? $cart_item['variation_id'] : $cart_item['product_id'];
			$product       = wc_get_product( $product_id );
			$has_exclusive = $this->has_exclusive( $discounts );
			$back          = false;
			remove_filter(
				'woocommerce_product_get_price',
				array(
					YITH_WC_Dynamic_Pricing_Frontend(),
					'get_price',
				),
				10
			);
			$default_price = $cart_item['data']->get_price();
			$price         = $current_price = $default_price;
			$difference    = 0;

			foreach ( $discounts as $discount ) {

				if ( ! isset( $discount['discount_amount'] ) || ! isset( $discount['discount_mode'] ) ) {
					continue;
				}

				$dm  = $discount['discount_amount'];
				$key = $discount['key'];

				if ( $dm == 'exclude' ) {
					$price      = $current_price = $default_price;
					$difference = 0;
				}

				if ( ! $discount['onsale'] && ( ( $product->get_sale_price() !== '' and $product->get_sale_price() > 0 ) && $product->get_sale_price() !== $product->get_regular_price() ) ) {

					continue;
				}

				// check if the discount has an exclusive rule
				if ( $has_exclusive && ! $discount['exclusive'] ) {

					continue;
				}

				$current_difference = apply_filters( 'ywdpd_apply_discount_current_difference', 0, $discount, $dm, $cart_item, $cart_item_key, $price );
				if ( $discount['discount_mode'] == 'bulk' && isset( $dm['type'] ) ) {
					switch ( $dm['type'] ) {
						case 'percentage':
							$current_difference = $price * $dm['amount'];
							$back               = true;
							break;
						case 'price':
							$current_difference = $dm['amount'];
							break;
						case 'fixed-price':
							$amount             = apply_filters( 'ywdpd_maybe_should_be_converted', $dm['amount'] );
							$current_difference = $price - $dm['amount'];
							break;
						default:
					}
				} elseif ( $discount['discount_mode'] == 'special_offer' && isset( $dm['type'] ) ) {

					// calculate new price
					$parent_id = $product->get_parent_id();

					if ( $dm['same_product'] && ( in_array(
						$dm['quantity_based'],
						array(
							'cart_line',
							'single_variation_product',
						)
					) ) ) {
						$adj_counter = $this->adjust_counter[ $key ] = $dm['total_target'];
					} elseif ( $dm['same_product'] && ( $dm['quantity_based'] == 'single_product' && $product->is_type( 'variation' ) ) ) {
						$adj_counter = $this->adjust_counter[ $key . $parent_id ] = isset( $this->adjust_counter[ $key . $parent_id ] ) ? $this->adjust_counter[ $key . $parent_id ] : $dm['total_target'];
					} else {
						$adj_counter = $this->adjust_counter[ $key ] = isset( $this->adjust_counter[ $key ] ) ? $this->adjust_counter[ $key ] : $dm['total_target'];

					}

					$a = ( $adj_counter > $cart_item['quantity'] ) ? $cart_item['quantity'] : $adj_counter;

					$full_price_quantity = $cart_item['available_quantity'] - $a;

					$discount_quantity = $a;
					$normal_line_total = $cart_item['quantity'] * $price;

					switch ( $dm['type'] ) {
						case 'percentage':
							$difference_s       = $price - $price * floatval( $dm['amount'] );
							$line_total         = ( $discount_quantity * $difference_s ) + ( $full_price_quantity * $price );
							$current_difference = ( $normal_line_total - $line_total ) / $cart_item['quantity'];
							$current_difference = $current_difference >= 0 ? $current_difference : 0;

							break;

						case 'price':
							$difference_s       = floatval( $price ) - floatval( $dm['amount'] );
							$difference_s       = $difference_s >= 0 ? $difference_s : 0;
							$line_total         = ( $discount_quantity * $difference_s ) + ( $full_price_quantity * $price );
							$current_difference = ( $normal_line_total - $line_total ) / $cart_item['quantity'];
							$current_difference = $current_difference >= 0 ? $current_difference : 0;
							break;
						case 'fixed-price':
							$difference_s       = apply_filters( 'ywdpd_maybe_should_be_converted', $dm['amount'] );
							$line_total         = ( $discount_quantity * $difference_s ) + ( $full_price_quantity * $price );
							$current_difference = ( $normal_line_total - $line_total ) / $cart_item['quantity'];
							$current_difference = $current_difference >= 0 ? $current_difference : 0;
							break;
						default:
					}

					if ( $dm['same_product'] && $dm['quantity_based'] == 'single_product' && $product->is_type( 'variation' ) ) {
						if ( $dm['total_target'] >= $cart_item['quantity'] ) {
							$this->adjust_counter[ $key . $parent_id ]                        = $this->adjust_counter[ $key . $parent_id ] - $cart_item['quantity'];
							WC()->cart->cart_contents[ $cart_item_key ]['available_quantity'] = 0;
						} else {
							WC()->cart->cart_contents[ $cart_item_key ]['available_quantity'] = $cart_item['quantity'] - $adj_counter;
							$this->adjust_counter[ $key . $parent_id ]                        = 0;
						}
					} else {

						if ( $dm['total_target'] > $cart_item['quantity'] ) {
							$this->adjust_counter[ $key ]                                    -= $cart_item['quantity'];
							WC()->cart->cart_contents[ $cart_item_key ]['available_quantity'] = 0;
						} else {
							WC()->cart->cart_contents[ $cart_item_key ]['available_quantity'] = $cart_item['quantity'] - $adj_counter;
							$this->adjust_counter[ $key ]                                     = 0;
						}
					}
				}

				$difference += $current_difference;

				$price = ( ( $default_price - $difference ) < 0 ) ? 0 : ( $default_price - $difference );

				if ( apply_filters( 'ywdpd_round_total_price', true ) ) {
					$price = round( $price, wc_get_price_decimals() );
				}
				WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'][ $discount['key'] ]['status']           = 'applied';
				WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'][ $discount['key'] ]['discount_applied'] = $current_difference;
				WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'][ $discount['key'] ]['current_price']    = $price;
				WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'][ $discount['key'] ]['discount_type']    = $dm['type'];

				$price = apply_filters( 'ywdpd_change_dynamic_price', $price, $cart_item_key, $discount );

				// check if the discount has an exclusive rule

				if ( $has_exclusive && $discount['exclusive'] && $difference != 0 ) {

					break;
				}
			}
			remove_filter(
				'woocommerce_product_get_price',
				array(
					YITH_WC_Dynamic_Pricing_Frontend(),
					'get_price',
				),
				10
			);
			$tax_mode = is_callable(
				array(
					WC()->cart,
					'get_tax_price_display_mode',
				)
			) ? WC()->cart->get_tax_price_display_mode() : WC()->cart->tax_display_cart;

			WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts']['default_price'] = ( $tax_mode == 'excl' ) ? yit_get_price_excluding_tax( $product ) : yit_get_price_including_tax( $product );
			add_filter(
				'woocommerce_product_get_price',
				array(
					YITH_WC_Dynamic_Pricing_Frontend(),
					'get_price',
				),
				10,
				2
			);

			if ( class_exists( 'WOOCS' ) && $back ) {
				global $WOOCS; //phpcs:ignore
				if ( $WOOCS->current_currency != $WOOCS->default_currency and $WOOCS->is_multiple_allowed ) { //phpcs:ignore
					$currencies = $WOOCS->get_currencies(); //phpcs:ignore
					$price      = $price / $currencies[ $WOOCS->current_currency ]['rate']; //phpcs:ignore
				}
			}

			WC()->cart->cart_contents[ $cart_item_key ]['data']->set_price( $price );
			$product = WC()->cart->cart_contents[ $cart_item_key ]['data'];
			yit_set_prop( $product, 'has_dynamic_price', true );
			yit_set_prop( $product, 'custom_price', $price );
			do_action( 'yith_dynamic_pricing_after_apply_discounts', $cart_item_key );
		}

		/**
		 * Check if is exclusive
		 *
		 * @param array $discounts Discount list.
		 *
		 * @return bool
		 */
		public function has_exclusive( $discounts ) {
			foreach ( $discounts as $discount ) {
				if ( isset( $discount['exclusive'] ) && $discount['exclusive'] == 1 ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if a product has specific categories
		 *
		 * @param int $product_id Product to check.
		 * @param array $categories List of categories.
		 * @param mixed $min_amount Minimum amount.
		 *
		 * @return bool
		 * @since  1.0.0
		 *
		 * @deprecated
		 */
		public function product_categories_validation( $product_id, $categories, $min_amount ) {
			$categories_cart         = YITH_WC_Dynamic_Pricing_Helper()->cart_categories;
			$intersect_cart_category = array_intersect( $categories, $categories_cart );

			$return = false;

			if ( is_array( $intersect_cart_category ) ) {
				$categories_counter         = YITH_WC_Dynamic_Pricing_Helper()->categories_counter;
				$categories_of_item         = wc_get_product_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
				$intersect_product_category = array_intersect( $categories_of_item, $categories );

				if ( is_array( $intersect_product_category ) ) {
					$tot = 0;
					foreach ( $categories as $cat ) {
						$tot += $categories_counter[ $cat ];
					}

					if ( $tot >= $min_amount ) {
						$return = true;
					}
				}
			}

			return $return;

		}

		/**
		 * Check if a product has specific tags
		 *
		 * @param int $product_id Product to check.
		 * @param array $tags Tag list.
		 * @param mixed $min_amount Minimum amount.
		 *
		 * @return bool
		 * @since  1.1.0
		 *
		 * @deprecated
		 */
		public function product_tags_validation( $product_id, $tags, $min_amount ) {
			$tags_cart          = YITH_WC_Dynamic_Pricing_Helper()->cart_tags;
			$intersect_cart_tag = array_intersect( $tags, $tags_cart );

			$return = false;

			if ( is_array( $intersect_cart_tag ) ) {
				$tags_counter          = YITH_WC_Dynamic_Pricing_Helper()->tags_counter;
				$tags_of_item          = wc_get_product_terms( $product_id, 'product_tag', array( 'fields' => 'ids' ) );
				$intersect_product_tag = array_intersect( $tags_of_item, $tags );

				if ( is_array( $intersect_product_tag ) ) {
					$tot = 0;
					foreach ( $tags as $tag ) {
						$tot += $tags_counter[ $tag ];
					}

					if ( $tot >= $min_amount ) {
						$return = true;
					}
				}
			}

			return $return;

		}

		/**
		 * Load YIT Plugin Framework
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function plugin_fw_loader() {
			if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
				global $plugin_fw_data;
				if ( ! empty( $plugin_fw_data ) ) {
					$plugin_fw_file = array_shift( $plugin_fw_data );
					require_once $plugin_fw_file;
				}
			}
		}

		/**
		 * Get options from db
		 *
		 * @access  public
		 *
		 * @param string $option Option to get.
		 * @param bool   $value Default value.
		 *
		 * @return mixed
		 * @since   1.0.0
		 */
		public function get_option( $option, $value = false ) {
			// get all options
			$options = get_option( $this->plugin_options );

			if ( isset( $options[ $option ] ) ) {
				return $options[ $option ];
			}

			return $value;
		}

		/**
		 * Calculate role price.
		 *
		 * @param float  $price Price.
		 * @param string $cart_item_key Cart item key.
		 * @param array  $discount Discount.
		 *
		 * @return mixed
		 */
		public function calculate_role_price_for_fix_dynamic_price( $price, $cart_item_key, $discount ) {

			if ( function_exists( 'YITH_Role_Based_Prices_Product' ) ) {
				if ( ! is_null( WC()->cart ) && isset( WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'][ $discount['key'] ] ) ) {

					$dynamic_type = WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'][ $discount['key'] ]['discount_type'];
					/**
					 * @var WC_Product $product
					 */
					$product = WC()->cart->cart_contents[ $cart_item_key ]['data'];

					$user_role = YITH_Role_Based_Prices_Product()->user_role['role'];

					if ( 'fixed-price' == $dynamic_type ) {

						yit_set_prop( $product, 'dynamic_fixed_price', $price );

						add_filter( 'ywcrbp_product_price_choose', array( $this, 'change_base_price' ), 10, 2 );

						YITH_Role_Based_Prices_Product()->load_global_rules();

						$global_rules = YITH_Role_Based_Type()->get_price_rule_by_user_role( $user_role, false );
						$new_price    = ywcrbp_calculate_product_price_role( $product, $global_rules, $user_role, $price );

						if ( 'no_price' !== $new_price ) {

							$price = $new_price;
							WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'][ $discount['key'] ]['current_price'] = $price;
						}
						remove_filter( 'ywcrbp_product_price_choose', array( $this, 'change_base_price' ), 10 );

					}
				}
			}

			return $price;
		}

		/**
		 * Change base price.
		 *
		 * @param float      $price Price.
		 * @param WC_Product $product Product.
		 *
		 * @return float|mixed
		 */
		public function change_base_price( $price, $product ) {

			$dynamic_price = yit_get_prop( $product, 'dynamic_fixed_price', true );

			if ( $dynamic_price ) {
				$price = $dynamic_price;

			}

			return $price;
		}

		/**
		 * Change default price.
		 *
		 * @param string $cart_item_key Cart item key.
		 */
		public function change_default_price( $cart_item_key ) {

			$product = WC()->cart->cart_contents[ $cart_item_key ]['data'];

			$dynamic_price = yit_get_prop( $product, 'dynamic_fixed_price', true );
			if ( $dynamic_price ) {
				WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts']['default_price'] = $dynamic_price;
			}
		}

		/**
		 * Deactivate round price
		 *
		 * @param bool $active .
		 *
		 * @return bool
		 */
		public function deactivate_round_price( $active ) {
			$how_show = YITH_WC_Dynamic_Options::how_show_special_offer_subtotal();

			if ( 'subtotal' == $how_show ) {
				$active = false;
			}

			return $active;
		}

	}
}

/**
 * Unique access to instance of YITH_WC_Dynamic_Pricing class
 *
 * @return YITH_WC_Dynamic_Pricing
 */
function YITH_WC_Dynamic_Pricing() { //phpcs:ignore
	return YITH_WC_Dynamic_Pricing::get_instance();
}

