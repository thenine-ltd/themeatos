<?php
/**
 * Cart discount class.
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
 * @class   YITH_WC_Dynamic_Discounts
 * @package YITH WooCommerce Dynamic Pricing and Discounts
 * @since   1.0.0
 * @author  YITH
 */
if ( ! class_exists( 'YITH_WC_Dynamic_Discounts' ) ) {

	/**
	 * Class YITH_WC_Dynamic_Discounts
	 */
	class YITH_WC_Dynamic_Discounts {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Dynamic_Discounts
		 */

		protected static $instance;

		/**
		 * Plugin option name
		 *
		 * @var string
		 */
		public $plugin_options = 'yit_ywdpd_options';

		/**
		 * Array with discount rules
		 *
		 * @var array
		 */
		public $discount_rules = array();


		/**
		 * Discount amount for dynami coupon
		 *
		 * @var int
		 */
		public $discount_amount = 0;

		/**
		 * Label of coupon
		 *
		 * @var string
		 */
		public $label_coupon = 'discount';

		public $current_coupon_code = '';


		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Dynamic_Discounts
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
			$label              = preg_replace( '/\s+/', '', YITH_WC_Dynamic_Options::get_coupon_label() );
			$this->label_coupon = strtolower( $label );

			add_action( 'woocommerce_removed_coupon', array( $this, 'apply_discount' ) );
			add_filter( 'woocommerce_coupon_message', array( $this, 'coupon_cart_discount_message' ), 10, 3 );
			add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'dynamic_label_coupon' ), 10, 2 );

			if ( defined( 'ICL_SITEPRESS_VERSION' ) && apply_filters( 'ywdpd_wpml_use_default_language_settings', true ) ) {
				add_filter( 'ywdpd_dynamic_discount_rules_filtered', array( $this, 'adjust_rules_for_wpml' ) );
			}
			add_action( 'wp_loaded', array( $this, 'ywdpd_set_cron' ) );
			add_action( 'ywdpd_clean_cron', array( $this, 'clear_coupons' ) );
			add_action( 'woocommerce_checkout_create_order', array( $this, 'clear_ywdpd_coupon_after_create_order' ) );
		}


		/**
		 * @param WC_Order $order
		 *
		 * @throws Exception
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		public function clear_ywdpd_coupon_after_create_order( $order ) {
			if ( version_compare( WC()->version, '3.7.0', '<' ) ) {
				$coupon_used = $order->get_used_coupons();
			} else {
				$coupon_used = $order->get_coupon_codes();
			}
			if ( $coupon_used ) {
				foreach ( $coupon_used as $coupons_code ) {
					$coupon = new WC_Coupon( $coupons_code );
					$valid  = ywdpd_coupon_is_valid( $coupon, $order );
					if ( $this->check_coupon_is_ywdpd( $coupon ) && $valid ) {
						$coupon->delete();
					}
				}
			}
		}


		/**
		 *
		 */
		public function ywdpd_set_cron() {
			if ( ! wp_next_scheduled( 'ywdpd_clean_cron' ) ) {
				$duration = apply_filters( 'ywdpd_set_cron_time', 'daily' );
				wp_schedule_event( time(), $duration, 'ywdpd_clean_cron' );
			}
		}

		/**
		 * Return pricing rules filtered and validates
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		function get_discount_rules() {
			if ( empty( $this->discount_rules ) ) {
				$this->discount_rules = $this->filter_valid_rules( $this->recover_cart_rules() );
			}

			return $this->discount_rules;
		}


		/**
		 * @return array
		 * @author Emanuela Castorina <emanuela.castorina@yithemes.com>
		 */
		function recover_cart_rules() {

			$cart_rules = ywdpd_recover_rules( 'cart' );

			return $cart_rules;
		}


		/**
		 * Filter valid cart discount rules
		 *
		 * @param $cart_rules
		 *
		 * @return array
		 */
		function filter_valid_rules( $cart_rules ) {

			$valid_rules = array();

			if ( ! $cart_rules || empty( $cart_rules ) || ! array( $cart_rules ) || $cart_rules == 'no' ) {
				return $valid_rules;
			}

			// check if cart have coupon
			$cart_have_coupon = ywdpd_check_cart_coupon();

			foreach ( $cart_rules as $key => $cart_rule ) {

				$is_valid = yith_plugin_fw_is_true( $cart_rule['active'] );

				if ( ! apply_filters( 'ywdpd_rule_is_valid', $is_valid, $cart_rule['id'], $cart_rule ) ) {
					continue;
				}

				if ( isset( $cart_rule['discount_amount'] ) && $cart_rule['discount_amount'] === '' ) {
					continue;
				} elseif ( isset( $cart_rule['discount_type'] ) && $cart_rule['discount_type'] === 'percentage' ) {
					$cart_rule['discount_amount'] = ( $cart_rule['discount_amount'] > 0 ) ? $cart_rule['discount_amount'] / 100 : $cart_rule['discount_amount'];
				}

				// DATE SCHEDULE VALIDATION
				$from          = ! empty( $cart_rule['schedule_discount_mode']['schedule_from'] ) ? $cart_rule['schedule_discount_mode']['schedule_from'] : '';
				$to            = ! empty( $cart_rule['schedule_discount_mode']['schedule_to'] ) ? $cart_rule['schedule_discount_mode']['schedule_to'] : '';
				$schedule_type = ! empty( $cart_rule['schedule_discount_mode']['schedule_type'] ) ? $cart_rule['schedule_discount_mode']['schedule_type'] : '';

				if ( 'schedule_dates' === $schedule_type && ! YITH_WC_Dynamic_Pricing_Helper()->validate_schedule( $from, $to ) ) {

					continue;
				}

				// DISCOUNT CAN BE COMBINED WITH COUPON
				$discount_combined = isset( $cart_rule['discount_combined'] ) && ! yith_plugin_fw_is_true( $cart_rule['discount_combined'] );

				if ( ! $discount_combined && $cart_have_coupon ) {

					continue;
				}

				if ( ! empty( $cart_rule['cart_discount_rules'] ) ) {

					$cart_discount_rules = maybe_unserialize( $cart_rule['cart_discount_rules'] );

					$sub_rules_valid = true;
					foreach ( $cart_discount_rules as $index => $r ) {

						$discount_type = $r['condition_for'];

						if ( ! $sub_rules_valid || ! isset( $r['condition_for'] ) ) {
							break;
						}

						$function_to_call = 'valid_' . $discount_type . '_cart_condition';

						if ( is_callable( array( YITH_WC_Dynamic_Pricing_Helper(), $function_to_call ) ) ) {

							if ( ! YITH_WC_Dynamic_Pricing_Helper()->$function_to_call( $r, $cart_discount_rules ) ) {
								$sub_rules_valid = false;
							}
						}
					}

					if ( $sub_rules_valid ) {
						$valid_rules[ $key ] = $cart_rule;
					}
				}
			}

			return apply_filters( 'ywdpd_dynamic_discount_rules_filtered', $valid_rules );
		}

		/**
		 * Apply discount to cart items
		 *
		 * @return void
		 * @throws Exception
		 */
		public function apply_discount() {

			$rules    = $this->get_discount_rules();
			$discount = $this->get_discount_amount();
			
			if ( ! empty( $rules ) && $discount >= 0 ) {

				add_filter( 'woocommerce_cart_totals_coupon_label', array( $this, 'dynamic_label_coupon' ), 10, 2 );

				add_action( 'woocommerce_cart_updated', array( $this, 'apply_coupon_cart_discount' ), 20 );
				add_filter( 'woocommerce_cart_totals_coupon_html', array( $this, 'coupon_cart_html' ), 10, 2 );

			} else {

				$coupon = $this->get_current_coupon();
				ywdpd_coupon_is_valid( $coupon, WC()->cart ) && $coupon->delete();

			}
		}

		/**
		 * @param $string
		 * @param WC_Coupon $coupon
		 *
		 * @return string
		 */
		public function dynamic_label_coupon( $string, $coupon ) {

			if ( is_null( $coupon ) || ! is_object( $coupon ) ) {
				return $string;
			}

			$coupon->get_amount();
			$is_ywdpd = $coupon->get_meta( 'ywdpd_coupon', true );

			$coupon_label = YITH_WC_Dynamic_Options::get_coupon_label() . ':';
			$coupon_label = apply_filters( 'ywdpd_dynamic_label_coupon', $coupon_label, $coupon );

			return $is_ywdpd ? esc_html( __( $coupon_label, 'ywdpd' ) ) : $string;
		}

		/**
		 * Create coupon cart discount for WooCommerce < 3.0.0
		 *
		 * @param $args
		 * @param $code
		 *
		 * @return array
		 */
		function create_coupon_cart_discount( $args, $code ) {

			if ( $code == $this->label_coupon ) {

				$args = array(
					'amount'           => $this->discount_amount,
					'apply_before_tax' => 'yes',
					'type'             => 'fixed_cart',
					'free_shipping'    => 'no',
					'individual_use'   => false,
					'usage_limit'      => 0,
				);
			}

			return $args;
		}

		/**
		 * @param $rules
		 *
		 * @return bool
		 */
		function check_single_percentage_discount( $rules ) {

			$is_single_perc = false;

			if ( ! empty( $rules ) && count( $rules ) == 1 ) {
				$rule           = reset( $rules );
				$is_single_perc = ( $rule['discount_type'] == 'percentage' );
			}

			return $is_single_perc;
		}


		/**
		 * Apply coupon cart discount to the cart
		 */
		function apply_coupon_cart_discount() {

			$rules             = $this->get_discount_rules();
			$coupon_type       = 'fixed_cart';
			$discount_subtotal = $this->get_cart_subtotal_to_discount( $rules );
			if ( $discount_subtotal == $this->get_cart_subtotal() && $this->check_single_percentage_discount( $rules ) ) {
				$coupon_type = 'percent';
				$rule        = reset( $rules );
				$discount    = $rule['discount_rule']['discount_amount'];
			} else {
				$discount = $this->get_discount_amount();
			}

			$allow_free_shipping = $this->can_allow_free_shipping( $rules );

			if ( empty( $rules ) || $discount < 0 ) {
				return;
			}

			$coupon = $this->get_current_coupon();
			$is_new = $coupon->get_amount() === '0';
			$valid  = ywdpd_coupon_is_valid( $coupon, WC()->cart );

			if ( $valid ) {

				if ( $coupon->get_discount_type() !== $coupon_type ) {
					$coupon->set_discount_type( $coupon_type );
				}

				if ( $coupon->get_amount() !== $discount ) {
					$coupon->set_amount( $discount );
				}

				if ( $coupon->get_free_shipping() !== $allow_free_shipping ) {
					$coupon->set_free_shipping( $allow_free_shipping );
				}
			} else {
				$args = array(
					'id'             => false,
					'discount_type'  => $coupon_type,
					'amount'         => $discount,
					'individual_use' => false,
					'free_shipping'  => $allow_free_shipping,
					'usage_limit'    => 0,
				);

				$coupon->add_meta_data( 'ywdpd_coupon', 1 );
				$coupon->read_manual_coupon( $coupon->get_code(), $args );
			}

			$coupon_label = $coupon->get_code();
			if ( $is_new || ! empty( $coupon->get_changes() ) ) {
				$coupon->save();
			}

			if ( ywdpd_coupon_is_valid( $coupon, WC()->cart ) && ! WC()->cart->has_discount( $coupon_label ) ) {
				WC()->cart->add_discount( $coupon_label );
			}

		}


		/**
		 * Return the coupon to apply
		 *
		 * @return WC_Coupon
		 */
		public function get_current_coupon() {

			if ( empty( $this->current_coupon_code ) ) {
				// check if in the cart
				$coupons_in_cart = WC()->cart->get_applied_coupons();

				foreach ( $coupons_in_cart as $coupon_in_cart_code ) {
					try {
						$coupon_in_cart = new WC_Coupon( $coupon_in_cart_code );
						$meta           = $coupon_in_cart->get_meta( 'ywdpd_coupon' );
						if ( ! empty( $meta ) ) {
							$this->current_coupon_code = $coupon_in_cart_code;
							break;
						}
					} catch ( WC_API_Exception $e ) {
						continue;
					}
				}
			}

			if ( empty( $this->current_coupon_code ) ) {
				if ( is_user_logged_in() ) {
					$this->current_coupon_code = apply_filters( 'ywdpd_coupon_code', $this->label_coupon . '_' . get_current_user_id(), $this->label_coupon );
				} else {
					$session = WC()->session->get( 'ywdpd_coupon_code', $this->current_coupon_code );
					if ( $session == '' ) {
						$this->current_coupon_code = apply_filters( 'ywdpd_coupon_code', uniqid( strtolower( $this->label_coupon . '_' ) ), $this->label_coupon );
						WC()->session->set( 'ywdpd_coupon_code', $this->current_coupon_code );
						WC()->session->save_data();
					} else {
						$this->current_coupon_code = $session;
					}
				}
			}

			return empty( $this->current_coupon_code ) ? false : new WC_Coupon( $this->current_coupon_code );
		}

		/**
		 * Change the label of coupon
		 *
		 * @param $string
		 * @param $coupon
		 *
		 * @return string
		 * @author  Emanuela Castorina
		 *
		 * @since   1.0.0
		 */
		public function label_coupon( $string, $coupon ) {

			// change the label if the order is generated from a quote
			if ( $coupon->code != $this->label_coupon ) {
				return $string;
			}

			return $this->label_coupon;
		}

		/**
		 * @param $value
		 * @param WC_Coupon $coupon
		 *
		 * @return string
		 */
		function coupon_cart_html( $value, $coupon ) {
			$is_ywdpd = $coupon->get_meta( 'ywdpd_coupon', true );
			if ( $is_ywdpd ) {
				$amount = WC()->cart->get_coupon_discount_amount( $coupon->get_code(), WC()->cart->display_cart_ex_tax );
				$value  = '-' . wc_price( $amount );
			}

			return $value;
		}

		/**
		 * @param $msg
		 * @param $msg_code
		 * @param WC_Coupon $coupon
		 *
		 * @return string
		 */
		function coupon_cart_discount_message( $msg, $msg_code, $coupon ) {

			$is_ywdpd = $coupon->get_meta( 'ywdpd_coupon', true );

			return $is_ywdpd ? '' : $msg;
		}


		/**
		 * Check id a YWDPD is in the list
		 *
		 * @param WC_Coupon
		 *
		 * @return bool
		 */
		public function check_coupon_is_ywdpd( $coupon ) {

			$is_ywdpd = $coupon->get_meta( 'ywdpd_coupon', true );

			return $is_ywdpd;
		}

		/**
		 * @return float|int
		 */
		function get_discount_amount() {
			$discount = 0;

			if ( ! empty( $this->discount_rules ) ) {

				foreach ( $this->discount_rules as $rule ) {

					$new_discount = 0;
					$conditions   = maybe_unserialize( $rule['cart_discount_rules'] );
					$subtotal     =  YITH_WC_Dynamic_Pricing_Helper()->get_cart_subtotal( $conditions );

					if ( $rule['discount_type'] == 'percentage' ) {
						$new_discount += $subtotal * $rule['discount_amount'];
					} elseif ( $rule['discount_type'] == 'price' ) {
						$new_discount += $rule['discount_amount'];
					} elseif ( $rule['discount_type'] == 'fixed-price' ) {
						$new_discount += ( $subtotal - $rule['discount_amount'] ) > 0 ? ( $subtotal - $rule['discount_amount'] ) : 0;
					}

					$discount += $new_discount;
				}
			}

			$this->discount_amount = $discount;

			return $discount;
		}


		/**
		 * Check if the product is excluded from the discount.
		 *
		 * @param WC_Product $product
		 *
		 * @param $rules
		 *
		 * @return bool
		 */
		function is_excluded( $product, $rules ) {

			$is_escluded = false;
			foreach ( $rules as $rule ) {

				if ( ! isset( $rule['condition_for'] ) ) {
					continue;
				}
				$type = $rule['condition_for'];

				$product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
				switch ( $rule['rules_type'] ) {
					case 'exclude_disc_products':
						$is_escluded = in_array( $product->get_id(), $rule['rules_type_exclude_disc_products'] );
						break;
					case 'exclude_disc_categories':
						$is_escluded = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy( $rule['rules_type_exclude_disc_categories'], $product_id, 'product_cat' );

						break;
					case 'exclude_disc_tag':
						$is_escluded = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy( $rule['rules_type_exclude_disc_tags'], $product_id, 'product_tag' );
						break;
					case 'exclude_disc_sale':
						$is_escluded = $product->is_on_sale();
						break;
					default:
				}

				if ( $is_escluded ) {
					return true;
				}
			}

			return false;

		}

		/**
		 * Calculate substotal to discount
		 *
		 * @param array $rules
		 *
		 * @return mixed|void
		 */
		function get_cart_subtotal_to_discount( $rules ) {
			$subtotal = 0;
			if ( ! is_null( WC()->cart ) ) {
				$subtotal = 0;

				foreach ( $rules as $rule ) {
					$conditions = maybe_unserialize( $rule['cart_discount_rules'] );

					$subtotal += YITH_WC_Dynamic_Pricing_Helper()->get_cart_subtotal( $conditions );
				}
			}

			return apply_filters( 'ywdpd_get_subtotal', $subtotal, $rules );

		}

		/**
		 * Return the cart subtotal
		 *
		 * @return float
		 */
		public function get_cart_subtotal() {
			if ( method_exists( WC()->cart, 'get_subtotal' ) ) {
				$subtotal = YITH_WC_Dynamic_Options::how_calculate_discounts() == 'tax_excluded' ? WC()->cart->get_subtotal() : WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();
			} else {
				$subtotal = YITH_WC_Dynamic_Options::how_calculate_discounts() == 'tax_excluded' ? WC()->cart->subtotal_ex_tax : WC()->cart->subtotal;
			}

			return $subtotal;
		}

		/**
		 * Clear coupons after use
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		function clear_coupons() {

			$args = array(
				'post_type'      => 'shop_coupon',
				'posts_per_page' => - 1,
				'meta_key'       => 'ywdpd_coupon',
				'meta_value'     => 1,
				'date_query'     => array(
					array(
						'column' => 'post_date_gmt',
						'before' => '1 day ago',
					),
				),
			);

			$coupons = get_posts( $args );

			if ( ! empty( $coupons ) ) {
				foreach ( $coupons as $coupon ) {
					wp_delete_post( $coupon->ID, true );
				}
			}
		}

		/**
		 * @param array $rules
		 *
		 * @return bool
		 */
		public function can_allow_free_shipping( $rules ) {

			foreach ( $rules as $rule ) {

				if ( isset( $rule['allow_free_shipping'] ) && yith_plugin_fw_is_true( $rule['allow_free_shipping'] ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * @param $cart_rules
		 *
		 * @return mixed
		 */
		public function adjust_rules_for_wpml( $cart_rules ) {
			global $sitepress;

			if ( ! is_null( $sitepress ) ) {

				foreach ( $cart_rules as $key => $rule ) {

					$conditions = ! empty( $rule['cart_discount_rules'] ) ? maybe_unserialize( $rule['cart_discount_rules'] ) : array();

					foreach ( $conditions as $i => $condition ) {
						$condition_for = $condition['condition_for'];

						if ( 'product' === $condition_for ) {

							// PRODUCT LIST IDS
							$require_product_list = ! empty( $condition['require_product_list'] ) ? $condition['require_product_list'] : array();
							if ( count( $require_product_list ) > 0 ) {
								$require_product_list                     = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $condition['require_product_list'], 'require_product_list' );
								$conditions[ $i ]['require_product_list'] = $require_product_list;
							}

							$exclude_product_list = ! empty( $condition['exclude_product_list'] ) ? $condition['exclude_product_list'] : array();
							if ( count( $exclude_product_list ) > 0 ) {
								$exclude_product_list                     = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $condition['exclude_product_list'], 'exclude_product_list' );
								$conditions[ $i ]['exclude_product_list'] = $exclude_product_list;
							}

							$disable_product_list = ! empty( $condition['disable_product_list'] ) ? $condition['disable_product_list'] : array();
							if ( count( $disable_product_list ) > 0 ) {
								$disable_product_list                     = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $condition['disable_product_list'], 'disable_product_list' );
								$conditions[ $i ]['disable_product_list'] = $disable_product_list;
							}

							// CATEGORY LIST IDS
							$require_category_list = ! empty( $condition['require_product_category_list'] ) ? $condition['require_product_category_list'] : array();
							if ( count( $require_category_list ) > 0 ) {
								$require_category_list                             = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $condition['require_product_category_list'], 'require_product_category_list' );
								$conditions[ $i ]['require_product_category_list'] = $require_category_list;
							}

							$exclude_category_list = ! empty( $condition['exclude_product_category_list'] ) ? $condition['exclude_product_category_list'] : array();
							if ( count( $exclude_category_list ) > 0 ) {
								$exclude_category_list                             = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $condition['exclude_product_category_list'], 'exclude_product_category_list' );
								$conditions[ $i ]['exclude_product_category_list'] = $exclude_category_list;
							}

							$disable_category_list = ! empty( $condition['disable_product_category_list'] ) ? $condition['disable_product_category_list'] : array();
							if ( count( $disable_category_list ) > 0 ) {
								$disable_category_list                             = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $condition['disable_product_category_list'], 'disable_product_category_list' );
								$conditions[ $i ]['disable_product_category_list'] = $disable_category_list;
							}

							// TAG LIST IDS
							$require_tag_list = ! empty( $condition['require_product_tag_list'] ) ? $condition['require_product_tag_list'] : array();
							if ( count( $require_tag_list ) > 0 ) {
								$require_tag_list                             = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $condition['require_product_tag_list'], 'require_product_tag_list' );
								$conditions[ $i ]['require_product_tag_list'] = $require_tag_list;
							}

							$exclude_tag_list = ! empty( $condition['exclude_product_tag_list'] ) ? $condition['exclude_product_tag_list'] : array();
							if ( count( $exclude_tag_list ) > 0 ) {
								$exclude_tag_list                             = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $condition['exclude_product_tag_list'], 'exclude_product_tag_list' );
								$conditions[ $i ]['exclude_product_tag_list'] = $exclude_tag_list;
							}

							$disable_tag_list = ! empty( $condition['disable_product_tag_list'] ) ? $condition['disable_product_tag_list'] : array();
							if ( count( $disable_tag_list ) > 0 ) {
								$disable_tag_list                             = YITH_WC_Dynamic_Pricing_Helper()->wpml_product_list_adjust( $condition['disable_product_tag_list'], 'disable_product_tag_list' );
								$conditions[ $i ]['disable_product_tag_list'] = $disable_tag_list;
							}
						}
					}
					$cart_rules[ $key ]['cart_discount_rules'] = $conditions;
				}
			}

			return $cart_rules;
		}
	}


}

/**
 * Unique access to instance of YITH_WC_Dynamic_Pricing class
 *
 * @return YITH_WC_Dynamic_Discounts
 */
function YITH_WC_Dynamic_Discounts() {
	return YITH_WC_Dynamic_Discounts::get_instance();
}

