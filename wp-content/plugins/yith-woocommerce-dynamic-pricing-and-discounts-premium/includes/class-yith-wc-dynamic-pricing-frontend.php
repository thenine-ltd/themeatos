<?php

/**
 * Frontend class.
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
 * Implements frontend features of YITH WooCommerce Dynamic Pricing and Discounts
 *
 * @class   YITH_WC_Dynamic_Pricing_Frontend
 * @package YITH WooCommerce Dynamic Pricing and Discounts
 * @since   1.0.0
 * @author  YITH
 */
if ( ! class_exists( 'YITH_WC_Dynamic_Pricing_Frontend' ) ) {

	/**
	 * Class YITH_WC_Dynamic_Pricing_Frontend
	 */
	class YITH_WC_Dynamic_Pricing_Frontend {


		/**
		 * Single instance of the class
		 *
		 * @var YITH_WC_Dynamic_Pricing_Frontend
		 */
		protected static $instance;

		/**
		 * Product filter.
		 *
		 * @var string
		 */
		public $get_product_filter;

		/**
		 * The pricing rules
		 *
		 * @access public
		 * @var array
		 * @since  1.0.0
		 */
		public $pricing_rules = array();

		/**
		 * Table of rules.
		 *
		 * @var array
		 */
		public $table_rules = array();

		/**
		 * Price filter list.
		 *
		 * @var array
		 */
		public $has_get_price_filter = array();

		/**
		 * Price html filter list.
		 *
		 * @var array
		 */
		public $has_get_price_html_filter = array();

		/**
		 * Cart processed flag.
		 *
		 * @var bool
		 */
		public $cart_processed = false;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Dynamic_Pricing_Frontend
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
			$posted = $_REQUEST;

			$this->get_product_filter = 'product_';

			if ( ( ! empty( $posted['add-to-cart'] ) && is_numeric( $posted['add-to-cart'] ) ) ||
				( isset( $posted['action'] ) && 'woocommerce_add_to_cart' == $posted['action'] ) ||
				( isset( $posted['wc-ajax'] ) && 'add_to_cart' == $posted['wc-ajax'] )
			) {

				add_action( 'woocommerce_add_to_cart', array( $this, 'cart_process_discounts' ), 99 );
			} else {
				if ( empty( $posted['apply_coupon'] ) || empty( $posted['coupon_code'] ) ) {

					add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'cart_process_discounts' ), 99 );
				} else {
					add_action( 'woocommerce_applied_coupon', array( $this, 'cart_process_discounts' ), 9 );
				}
			}

			add_action( 'yith_wacp_before_popup_content', array( $this, 'cart_process_discounts' ), 99 );

			// Filters to format prices.
			add_filter( 'woocommerce_get_price_html', array( &$this, 'get_price_html' ), 10, 2 );
			add_filter( 'woocommerce_get_variation_price_html', array( &$this, 'get_price_html' ), 10, 2 );
			add_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10, 2 );
			add_filter( 'woocommerce_product_variation_get_price', array( $this, 'get_price' ), 10, 2 );
			add_filter( 'woocommerce_cart_item_price', array( $this, 'replace_cart_item_price' ), 100, 3 );
			add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'replace_cart_item_subtotal' ), 100, 3 );
			add_filter( 'woocommerce_coupon_error', array( $this, 'remove_coupon_cart_message' ), 10, 3 );
			add_filter( 'woocommerce_coupon_message', array( $this, 'remove_coupon_cart_message' ), 10, 3 );

			add_shortcode( 'yith_ywdpd_quantity_table', array( $this, 'table_quantity_shortcode' ) );
			add_shortcode( 'yith_ywdpd_product_note', array( $this, 'product_note_shortcode' ) );

			add_action( 'init', array( $this, 'init' ), 10 );

			$priority = ( function_exists( 'YITH_WCCL_Frontend' ) ) ? 5 : 10;

			// custom styles and javascripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ), $priority );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_popup_scripts' ), 30 );

			if ( YITH_WC_Dynamic_Options::show_discount_info_in_cart() ) {
				add_action(
					'woocommerce_cart_totals_before_shipping',
					array(
						$this,
						'show_total_discount_message_on_cart',
					),
					99
				);
			}

			add_action( 'wp_footer', array( $this, 'show_popup' ), 25 );
			add_action( 'wp_ajax_add_special_to_cart', array( $this, 'add_special_to_cart' ) );
			add_action( 'wp_ajax_nopriv_add_special_to_cart', array( $this, 'add_special_to_cart' ) );
			add_action( 'wp_ajax_show_second_step', array( $this, 'show_second_step' ), 20 );
			add_action( 'wp_ajax_nopriv_show_second_step', array( $this, 'show_second_step' ), 20 );
			add_action( 'wp_ajax_check_variable', array( $this, 'check_variable' ) );
			add_action( 'wp_ajax_nopriv_check_variable', array( $this, 'check_variable' ) );

			add_action( 'woocommerce_add_to_cart', array( $this, 'print_popup_for_special_offer' ), 99, 6 );
		}

		/**
		 * Init function.
		 */
		public function init() {
			$this->pricing_rules = YITH_WC_Dynamic_Pricing()->get_pricing_rules();

			// Quantity table.
			$show_quantity_table = YITH_WC_Dynamic_Options::show_quantity_table();
			if ( yith_plugin_fw_is_true( $show_quantity_table ) ) {
				$this->table_quantity_init();
				add_filter(
					'woocommerce_available_variation',
					array(
						$this,
						'add_params_to_available_variation',
					),
					10,
					3
				);
			}

			// Notes on products.
			if ( YITH_WC_Dynamic_Options::show_note_on_products() ) {
				$this->note_on_products_init();
			}
		}

		/**
		 * Remove from cart only dynamic coupons
		 *
		 * @since  1.2.0
		 * @author Emanuela Castorina
		 */
		public function remove_dynamic_coupons() {
			$applied_coupons = WC()->cart->get_applied_coupons();
			foreach ( $applied_coupons as $applied_coupon ) {
				$cp   = new WC_Coupon( $applied_coupon );
				$meta = $cp->get_meta( 'ywdpd_coupon', true );
				if ( ! empty( $meta ) ) {
					WC()->cart->remove_coupon( $cp->get_code() );
				}
			}
		}

		/**
		 * Remove coupon cart message.
		 *
		 * @param string    $error Error Message.
		 * @param string    $msg_code Code message.
		 * @param WC_Coupon $coupon Coupon.
		 *
		 * @return bool
		 */
		public function remove_coupon_cart_message( $error, $msg_code, $coupon ) {

			if ( preg_match_all( '/discount_[0-9]*/', $error ) ) {
				return false;
			}

			return $error;
		}

		/**
		 * Process dynamic pricing in cart
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		public function cart_process_discounts() {
			if ( apply_filters( 'ywdpd_skip_cart_process_discounts', empty( WC()->cart->cart_contents ) || $this->cart_processed ) ) {
				return;
			}

			remove_action( 'woocommerce_applied_coupon', array( $this, 'cart_process_discounts' ), 9 );
			$reset  = apply_filters( 'ywdpd_reset_previous_discounts', current_action() == 'yith_wacp_before_popup_content' );
			$posted = $_REQUEST;
			do_action( 'ywdpd_before_cart_process_discounts' );

			$remove_item = isset( $posted['remove_item'] ) ? $posted['remove_item'] : false;
			$this->remove_dynamic_coupons();

			WC()->session->set( 'refresh_totals', true );

			$cart_sort      = array();
			$bundled_cart   = array();
			$composite_cart = array();
			$mix_match_cart = array();

			// empty old discounts and reset the available quantity.
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

				if ( $cart_item_key == $remove_item ) {
					continue;
				}

				// if the product is a bundle or a bundle item.
				if ( isset( $cart_item['bundled_by'] ) || isset( $cart_item['cartstamp'] ) ) {
					$bundled_cart[ $cart_item_key ] = WC()->cart->cart_contents[ $cart_item_key ];
				} elseif ( isset( $cart_item['mnm_config'] ) || isset( $cart_item['mnm_container'] ) ) {
					$mix_match_cart[ $cart_item_key ] = WC()->cart->cart_contents[ $cart_item_key ];
				} elseif ( isset( $cart_item['yith_wcp_component_data'] ) || isset( $cart_item['yith_wcp_child_component_data'] ) ) {
					$composite_cart[ $cart_item_key ] = WC()->cart->cart_contents[ $cart_item_key ];
				} else {
					WC()->cart->cart_contents[ $cart_item_key ]['available_quantity'] = $cart_item['quantity'];
					if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'] ) ) {
						if ( $reset && isset( WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts']['default_price'] ) ) {
							$cart_product = WC()->cart->cart_contents[ $cart_item_key ]['data'];
							$cart_product->set_price( wc_prices_include_tax() ? wc_get_price_including_tax( $cart_product ) : wc_get_price_excluding_tax( $cart_product ) );
						}

						unset( WC()->cart->cart_contents[ $cart_item_key ]['ywdpd_discounts'] );
					}
					$cart_sort[ $cart_item_key ] = WC()->cart->cart_contents[ $cart_item_key ];
				}
			}

			@uasort( $cart_sort, 'YITH_WC_Dynamic_Pricing_Helper::sort_by_price' );

			if ( ( ! class_exists( 'WC_Composite_Products' ) && ! class_exists( 'YITH_WCP' ) && ! apply_filters( 'ywdpd_skip_cart_sorting', false ) ) || apply_filters( 'ywdpd_force_cart_sorting', false ) ) {
				WC()->cart->cart_contents = $cart_sort;
			}
			remove_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10 );
			// add processed pricing rules on each cart item.
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				if ( ! YITH_WC_Dynamic_Pricing_Helper()->check_cart_item_filter_exclusion( $cart_item ) ) {
					YITH_WC_Dynamic_Pricing()->get_applied_rules_to_product( $cart_item_key, $cart_item );
				}
			}

			// apply the discount to each cart item.
			WC()->cart->calculate_totals();
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

				if ( isset( $cart_item['ywdpd_discounts'] ) && isset( $cart_item['data'] ) ) {

					YITH_WC_Dynamic_Pricing()->apply_discount( $cart_item, $cart_item_key, $reset );
				}
			}

			WC()->cart->cart_contents = array_merge( WC()->cart->cart_contents, $bundled_cart, $composite_cart, $mix_match_cart );
			WC()->cart->calculate_totals();
			if ( ! isset( $posted['remove_coupon'] ) ) {
				YITH_WC_Dynamic_Discounts()->apply_discount();
			}

			if ( isset( $posted['apply_coupon'] ) ) {
				unset( $_REQUEST['apply_coupon'] );
			}

			do_action( 'ywdpd_after_cart_process_discounts' );

			$this->cart_processed = true;
			add_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10, 2 );
			add_action( 'woocommerce_applied_coupon', array( $this, 'cart_process_discounts' ), 9 );
		}

		/**
		 * Replace the price in the cart
		 *
		 * @param float  $price Price.
		 * @param array  $cart_item Cart item.
		 * @param string $cart_item_key Cart item key.
		 *
		 * @return mixed|string
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		public function replace_cart_item_price( $price, $cart_item, $cart_item_key ) {

			do_action( 'ywdpd_before_replace_cart_item_price', $price, $cart_item, $cart_item_key );

			if ( ! isset( $cart_item['ywdpd_discounts'] ) || ! isset( $cart_item['data'] ) || ! isset( WC()->cart ) || YITH_WC_Dynamic_Pricing_Helper()->check_cart_item_filter_exclusion( $cart_item ) ) {

				return $price;
			}

			$old_price = $price;
			remove_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10 );
			foreach ( $cart_item['ywdpd_discounts'] as $discount ) {

				if ( isset( $discount['status'] ) && 'applied' == $discount['status'] ) {
					$default_price = (float) $cart_item['ywdpd_discounts']['default_price'];
					$tax_mode      = is_callable( array( WC()->cart, 'get_tax_price_display_mode' ) ) ? WC()->cart->get_tax_price_display_mode() : WC()->cart->tax_display_cart;

					if ( 'excl' === $tax_mode ) {
						$new_price = wc_get_price_excluding_tax( $cart_item['data'] );
					} else {
						$new_price = wc_get_price_including_tax( $cart_item['data'] );
					}
					if ( $default_price > $new_price ) {

						$how_show = YITH_WC_Dynamic_Options::how_show_special_offer_subtotal();
						if ( 'special_offer' === $discount['discount_mode'] && 'subtotal' == $how_show ) {
							$price = wc_price( $cart_item['ywdpd_discounts']['default_price'] );
						} else {
							$price = '<del>' . wc_price( $cart_item['ywdpd_discounts']['default_price'] ) . '</del> ' . WC()->cart->get_product_price( $cart_item['data'] );
							break;
						}
					} else {

						return $price;
					}
				}
			}

			$price = apply_filters( 'ywdpd_replace_cart_item_price', $price, $old_price, $cart_item, $cart_item_key );

			WC()->cart->calculate_totals();

			return $price;
		}

		/**
		 * replace cart item subtotal if a special offer is applied
		 *
		 * @param string $subtotal ,
		 * @param array  $cart_item ,
		 * @param string $cart_item_key
		 *
		 * @return string;
		 * @author YITH
		 * @since 2.0
		 */
		public function replace_cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
			do_action( 'ywdpd_before_replace_cart_item_subtotal', $subtotal, $cart_item, $cart_item_key );

			$how_show = YITH_WC_Dynamic_Options::how_show_special_offer_subtotal();

			if ( isset( $cart_item['ywdpd_discounts'] ) ) {
				foreach ( $cart_item['ywdpd_discounts'] as $discount ) {
					if ( isset( $discount['status'] ) && 'applied' == $discount['status'] && 'special_offer' == $discount['discount_mode'] && 'subtotal' == $how_show && isset( $discount['rule_id'] ) ) {
						/**
						 * @var WC_Product $product
						 */
						$product = $cart_item['data'];

						$new_product  = wc_get_product( $product->get_id() );
						$old_price    = $new_product->get_price( 'edit' );
						$old_subtotal = $this->get_product_subtotal( $product, $cart_item['quantity'], $old_price );
						$new_subtotal = $this->get_product_subtotal( $product, $cart_item['quantity'] );

						$rule_name = apply_filters( 'ywdpd_special_offer_name_subtotal', get_the_title( $discount['rule_id'] ) );

						if ( (float) $old_price !== (float) $product->get_price() ) {
							$subtotal = sprintf( "<div class='ywdpd_subtotal_row'><del>%s</del><span class='ywdpd_subtotal_price'><small><strong>%s</strong></small><strong>%s</strong></span></div>", $old_subtotal, $rule_name, $new_subtotal );
						}
					}
				}
			}
			do_action( 'ywdpd_after_replace_cart_item_subtotal', $subtotal, $cart_item, $cart_item_key );

			return $subtotal;
		}

		/**
		 * get the product subtotal in the cart
		 *
		 * @param WC_Product $product
		 * @param int        $quantity
		 * @param bool       $price
		 */
		public function get_product_subtotal( $product, $quantity, $price = false ) {

			if ( ! $price ) {

				$price = $product->get_price();
			}
			if ( $product->is_taxable() ) {

				if ( WC()->cart->display_prices_including_tax() ) {
					$row_price        = wc_get_price_including_tax(
						$product,
						array(
							'qty'   => $quantity,
							'price' => $price,
						)
					);
					$product_subtotal = wc_price( $row_price );

					if ( ! wc_prices_include_tax() && WC()->cart->get_subtotal_tax() > 0 ) {
						$product_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
					}
				} else {
					$row_price        = wc_get_price_excluding_tax(
						$product,
						array(
							'qty'   => $quantity,
							'price' => $price,
						)
					);
					$product_subtotal = wc_price( $row_price );

					if ( wc_prices_include_tax() && WC()->cart->get_subtotal_tax() > 0 ) {
						$product_subtotal .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
					}
				}
			} else {
				$row_price        = $price * $quantity;
				$product_subtotal = wc_price( $row_price );
			}

			return $product_subtotal;
		}

		/**
		 * Add custom params to variations
		 *
		 * @access public
		 *
		 * @param array      $args Arguments.
		 * @param WC_Product $product Product.
		 * @param WC_Product $variation Variation.
		 *
		 * @return array
		 * @since  1.1.1
		 */
		public function add_params_to_available_variation( $args, $product, $variation ) {
			if ( ! YITH_WC_Dynamic_Pricing_Helper()->is_in_exclusion_rule( array( 'product_id' => $product->get_id() ) ) ) {
				$args['table_price'] = $this->table_quantity( $variation );
			}

			return $args;
		}

		/**
		 * Show table quantity in the single product if there's a pricing rule
		 *
		 * @since  1.0.0
		 */
		public function show_note_on_products( $product = false ) {

			if ( ! $product ) {
				global $product;
			}

			/**
			 * @var WC_Product $product
			 */

			$valid_rules = $this->pricing_rules;

			if ( ! empty( $valid_rules ) && ! YITH_WC_Dynamic_Pricing_Helper()->is_in_exclusion_rule( array( 'product_id' => $product->get_id() ) ) ) {

				$find_exclusive       = false;
				$first_bulk_rule_show = false;
				foreach ( $valid_rules as $rule ) {

					$discount_mode = isset( $rule['discount_mode'] ) ? $rule['discount_mode'] : '';
					$can_process   = ( 'gift_products' === $discount_mode ) || ( 'special_offer' === $discount_mode ) || ( 'bulk' === $discount_mode && ! $first_bulk_rule_show );
					if ( $can_process ) {

						$check_exclusive = isset( $rule['no_apply_with_other_rules'] ) && yith_plugin_fw_is_true( $rule['no_apply_with_other_rules'] );
						$disable_onsale  = apply_filters( 'ywdpd_show_note_on_sale', isset( $rule['disable_on_sale'] ) && yith_plugin_fw_is_true( $rule['disable_on_sale'] ), $rule );

						if ( $check_exclusive && $find_exclusive && 'gift_products' !== $discount_mode ) {
							break;
						}

						if ( ! ( $disable_onsale && $product->is_on_sale() ) ) {

							$note_apply_to     = ! empty( $rule['table_note_apply_to'] ) ? $rule['table_note_apply_to'] : '';
							$is_valid_to_apply = YITH_WC_Dynamic_Pricing_Helper()->valid_product_to_apply( $rule, $product, true );

							if ( apply_filters( 'ywdpd_show_note_apply_to', ! empty( $note_apply_to ) && $is_valid_to_apply, $rule, $product ) ) {
								echo '<div class="show_note_on_apply_products">' . wp_kses_post( stripslashes( ywdpd_get_note( $note_apply_to ) ) ) . '</div>';
							}

							$is_apply_adjustment_enabled  = isset( $rule['active_apply_discount_to'] ) && yith_plugin_fw_is_true( $rule['active_apply_discount_to'] );
							$note_apply_adjustment_to     = ! empty( $rule['table_note_adjustment_to'] ) ? $rule['table_note_adjustment_to'] : '';
							$is_valid_apply_adjustment_to = YITH_WC_Dynamic_Pricing_Helper()->valid_product_to_adjustment( $rule, $product );
							if ( 'gift_products' === $discount_mode ) {
								$note_apply_adjustment_to = ! empty( $rule['table_note_gift_adjustment_to'] ) ? $rule['table_note_gift_adjustment_to'] : '';
							}
							if ( apply_filters( 'ywdpd_show_note_apply_adjustment_to', $is_apply_adjustment_enabled && ! empty( $note_apply_adjustment_to ) && $is_valid_apply_adjustment_to, $rule, $product ) ) {

								echo '<div class="show_note_on_apply_products">' . wp_kses_post( stripslashes( ywdpd_get_note( $note_apply_adjustment_to ) ) ) . '</div>';
							}

							if ( $check_exclusive && ( $is_valid_to_apply || $is_valid_apply_adjustment_to ) ) {
								$find_exclusive = true;

								if ( 'bulk' === $discount_mode ) {
									$first_bulk_rule_show = true; // only one qty rule can be apply in a product
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Get html price.
		 *
		 * @param float                          $price Price.
		 * @param WC_Product|WC_Product_Variable $product Product.
		 *
		 * @return mixed|string
		 */
		public function get_price_html( $price, $product ) {

			global $woocommerce_loop;

			if ( ( ( is_cart() || is_checkout() ) && is_null( $woocommerce_loop ) ) || ! YITH_WC_Dynamic_Pricing()->check_discount( $product ) || YITH_WC_Dynamic_Pricing_Helper()->is_in_exclusion_rule( array( 'product_id' => $product->get_id() ) ) ) {

				return $price;
			}

			$product_id = $product->get_id();

			if ( array_key_exists( $product_id, $this->has_get_price_html_filter ) || apply_filters( 'ywdpd_get_price_html_exclusion', false, $price, $product ) ) {
				return isset( $this->has_get_price_html_filter[ $product_id ] ) ? $this->has_get_price_html_filter[ $product_id ] : $price;
			}

			do_action( 'yith_ywdpd_get_price_and_discount_before' );

			$display_regular_price = wc_get_price_to_display(
				$product,
				array(
					'qty'   => 1,
					'price' => $product->get_price( 'edit' ),
				)
			);

			$display_regular_price = apply_filters( 'ywdpd_maybe_should_be_converted', $display_regular_price );

			$price_format = YITH_WC_Dynamic_Options::get_price_format();

			$new_price           = $price_format;
			$percentual_discount = '';
			$discount_html       = '';

			if ( $product->is_type( 'variable' ) ) {

				/**
				 * @var WC_Product_Variable $product
				 */
				$prices = array(
					$product->get_variation_price( 'min', true ),
					$product->get_variation_price( 'max', true ),
				);

				$min_variation_regular_price = $this->get_min_regular_variation_price( $product );
				$max_variation_regular_price = $this->get_max_regular_variation_price( $product );

				$min_variation_regular_price_displayed = wc_get_price_to_display(
					$product,
					array(
						$price,
						$min_variation_regular_price,
					)
				);

				remove_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10 );
				$show_minimum_price = YITH_WC_Dynamic_Options::get_default_price();
				if ( 'max' == $show_minimum_price ) {
					$discount     = $this->get_minimum_price( $product );
					$discount_max = $this->get_maximum_price( $product );
				} else {
					$discount_max = $this->get_maximum_price( $product, 1 );
					$discount     = $this->get_minimum_price( $product, 1 );
				}
				add_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10, 2 );

				if ( $prices[0] == $prices[1] && $min_variation_regular_price_displayed == $prices[0] ) {

					$display_regular_price = wc_get_price_to_display(
						$product,
						array(
							'qty'   => 1,
							'price' => $this->get_min_regular_variation_price( $product ),
						)
					);

					remove_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10 );
					$show_minimum_price = YITH_WC_Dynamic_Options::get_default_price();
					if ( 'max' == $show_minimum_price ) {
						$discount = wc_get_price_to_display(
							$product,
							array(
								'qty'   => 1,
								'price' => $this->get_minimum_price( $product ),
							)
						);
					} else {
						$discount = wc_get_price_to_display(
							$product,
							array(
								'qty'   => 1,
								'price' => $this->get_minimum_price(
									$product,
									1
								),
							)
						);
					}

					add_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10, 2 );

					$discount_html = wc_price( $discount );

					if ( $display_regular_price ) {
						$per_disc = 100 - ( (float) $discount / $display_regular_price * 100 );
						if ( $per_disc > 0 ) {
							$percentual_discount = apply_filters( 'ywdpd_percentual_discount', '-' . number_format( $per_disc, 2, '.', '' ) . '%', $per_disc );
						}
					}
				} else {

					if ( $discount != $min_variation_regular_price || $discount_max != $max_variation_regular_price ) {

						$dp_min_variation_regular_price = wc_get_price_to_display( $product, array( 'price' => $min_variation_regular_price ) );

						$dp_max_variation_regular_price = wc_get_price_to_display( $product, array( 'price' => $max_variation_regular_price ) );

						if ( $min_variation_regular_price < $max_variation_regular_price ) {
							$display_regular_price = apply_filters( 'ywdpd_change_variable_products_html_regular_price', wc_price( $dp_min_variation_regular_price ) . '-' . wc_price( $dp_max_variation_regular_price ), $dp_min_variation_regular_price, $dp_max_variation_regular_price );
						} else {
							$display_regular_price = wc_price( $dp_min_variation_regular_price );
						}

						if ( $this->has_markup_rule( $product ) ) {
							$display_regular_price = '';
						}

						$new_price = str_replace( '%original_price%', $display_regular_price, $new_price );

						$dp_discount     = wc_get_price_to_display( $product, array( 'price' => $discount ) );
						$dp_discount_max = wc_get_price_to_display( $product, array( 'price' => $discount_max ) );

						if ( $discount_max != $discount ) {
							$discount_html = apply_filters( 'ywdpd_change_variable_products_html_discount_price', wc_price( $dp_discount ) . '-' . wc_price( $dp_discount_max ), $dp_discount, $dp_discount_max );
						} else {
							$discount_html = wc_price( $dp_discount );
						}

						if ( 0 !== $min_variation_regular_price && 0.00 != $min_variation_regular_price ) {
							$per_disc = 100 - ( $discount / $min_variation_regular_price * 100 );
							if ( $per_disc > 0 ) {
								$percentual_discount = apply_filters( 'ywdpd_percentual_discount', '-' . number_format( $per_disc, 2, '.', '' ) . '%', $per_disc );
							}
						}
					} else {
						$discount  = false;
						$new_price = $price;
					}
				}
			} else {

				remove_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10 );
				remove_filter( 'woocommerce_product_variation_get_price', array( $this, 'get_price' ), 10 );
				$show_minimum_price = YITH_WC_Dynamic_Options::get_default_price();
				if ( 'max' == $show_minimum_price ) {
					$discount = $this->get_minimum_price( $product );
				} else {
					$discount = $this->get_minimum_price( $product, 1 );
				}

				add_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10, 2 );
				add_filter( 'woocommerce_product_variation_get_price', array( $this, 'get_price' ), 10, 2 );

				$discount = wc_get_price_to_display( $product, array( 'price' => $discount ) );

				$discount_html = wc_price( $discount );
			}

			do_action( 'yith_ywdpd_get_price_and_discount_after' );

			$discount = empty( $discount ) ? 0 : $discount;

			if ( $discount >= 0 && $discount != $display_regular_price ) {

				if ( empty( $percentual_discount ) && 0 != $display_regular_price ) {
					$per_disc = 100 - ( $discount / $display_regular_price * 100 );

					if ( $per_disc > 0 ) {
						$percentual_discount = apply_filters( 'ywdpd_percentual_discount', '-' . number_format( $per_disc, 2, '.', '' ) . '%', $per_disc );
					}
				}

				$new_price  = str_replace( '%original_price%', wc_price( $display_regular_price ), $new_price );
				$new_price  = str_replace( '%discounted_price%', $discount_html, $new_price );
				$new_price  = str_replace( '%percentual_discount%', $percentual_discount, $new_price );
				$new_price .= $product->get_price_suffix();
			} else {
				$show_minimum_price = YITH_WC_Dynamic_Options::get_default_price();
				if ( 'max' == $show_minimum_price ) {
					$new_price = wc_price( $discount );
				} else {
					$new_price = apply_filters( 'ywdpd_maybe_should_be_converted', $price );
				}
			}

			add_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10, 2 );

			$this->has_get_price_html_filter[ $product_id ] = $new_price;

			return apply_filters( 'yith_ywdpd_single_bulk_discount', $new_price, $product );
		}

		/**
		 * Only the first quantity table can be applied to the product
		 *
		 * @param WC_Product $product Product.
		 *
		 * @return mixed
		 */
		public function get_table_rules( $product ) {

			if ( $product instanceof WC_Product && isset( $this->table_rules[ $product->get_id() ] ) ) {
				return $this->table_rules[ $product->get_id() ];
			}

			$valid_rules = $this->pricing_rules;

			$this->table_rules[ $product->get_id() ] = array();

			if ( ! YITH_WC_Dynamic_Pricing_Helper()->is_in_exclusion_rule( array( 'product_id' => $product->get_id() ) ) ) {

				// build rules array.
				foreach ( $valid_rules as $rule ) {

					$rule_active               = yith_plugin_fw_is_true( $rule['active'] );
					$rule_adjustment_to_active = isset( $rule['active_apply_discount_to'] ) && yith_plugin_fw_is_true( $rule['active_apply_discount_to'] );
					$is_valid_product_to_apply = YITH_WC_Dynamic_Pricing_Helper()->valid_product_to_apply_bulk( $rule, $product );
					if ( $rule_adjustment_to_active ) {
						$is_valid_product_to_apply_adjustment = YITH_WC_Dynamic_Pricing_Helper()->valid_product_to_adjustment_bulk( $rule, $product, false );
					} else {
						$is_valid_product_to_apply_adjustment = true;
					}
					if ( 'bulk' === $rule['discount_mode'] && $rule_active ) {

						if ( $is_valid_product_to_apply && $is_valid_product_to_apply_adjustment ) {
							$table_rules[]                           = $rule;
							$this->table_rules[ $product->get_id() ] = $table_rules;
							break;
						}
					}
				}
			}

			return $this->table_rules[ $product->get_id() ];
		}

		/**
		 * Check if has markup rule.
		 *
		 * @param WC_Product|WC_Product_Variable $product Product.
		 *
		 * @return boolean
		 */
		public function has_markup_rule( $product ) {

			$table_rules = $this->get_table_rules( $product );

			$has_markup_rule = false;
			if ( $table_rules ) {
				foreach ( $table_rules as $rules ) {
					foreach ( $rules['rules'] as $rule ) {

						if ( $rule['type_discount'] && $rule['discount_amount'] < 0 ) {
							$has_markup_rule = true;
						}
					}
				}
			}

			return $has_markup_rule;
		}

		/**
		 * Get minimum price.
		 *
		 * @param WC_Product|WC_Product_Variable $product Product.
		 * @param string                         $min_quantity Minimum quantity.
		 *
		 * @return int|mixed
		 */
		public function get_minimum_price( $product, $min_quantity = '' ) {

			$table_rules = $this->get_table_rules( $product );

			if ( $product->is_type( 'variable' ) ) {
				$minimum_price = $product->get_variation_price( 'min' );
			} else {
				$minimum_price = $product->get_price();
			}

			$discount_price     = $minimum_price;
			$min_quantity_check = 0;
			$last_check         = true;
			if ( $table_rules ) {
				foreach ( $table_rules as $rules ) {
					$enable_adjustment_to = isset( $rules['active_apply_discount_to'] ) && yith_plugin_fw_is_true( $rules['active_apply_discount_to'] );

					$valid_product_function = 'valid_product_to_apply_bulk';
					$apply_to_valid         = true;
					if ( $enable_adjustment_to ) {
						$valid_product_function = 'valid_product_to_adjustment_bulk';
						$apply_to_valid         = YITH_WC_Dynamic_Pricing_Helper()->validate_apply_to_in_cart( $rules );
					}
					$main_rule = $rules;
					foreach ( $rules['rules'] as $rule ) {

						if ( $product->is_type( 'variable' ) ) {
							$prices = apply_filters( 'ywdpd_get_variable_prices', $product->get_variation_prices(), $product );
							$prices = isset( $prices['price'] ) ? $prices['price'] : array();

							if ( $prices ) {
								$min_price = current( $prices );
								$max_price = end( $prices );
								if ( $min_price == $max_price ) {
									// for products where only the variation is discounted.
									foreach ( $prices as $id => $p ) {
										if ( YITH_WC_Dynamic_Pricing_Helper()->$valid_product_function( $main_rule, wc_get_product( $id ) ) ) {
											$curr_discount_price = ywdpd_get_discounted_price_table( $p, $rule );
										} else {
											$curr_discount_price = $p;
										}
										$discount_price = $curr_discount_price < $discount_price ? $curr_discount_price : $discount_price;

										if ( $rule['type_discount'] && $rule['discount_amount'] < 0 ) {
											$discount_price = $curr_discount_price;
										}
									}
								} else {
									$min_key       = array_search( $min_price, $prices );
									$minimum_price = $min_price;
									if ( '' != $min_quantity && $rule['min_quantity'] != $min_quantity ) {
										continue;
									}

									if ( YITH_WC_Dynamic_Pricing_Helper()->$valid_product_function( $rules, wc_get_product( $min_key ) ) ) {
										$discount_min_price = ywdpd_get_discounted_price_table( $min_price, $rule );
									} else {
										$discount_min_price = $min_price;
									}

									$discount_price = $discount_min_price;
								}

								if ( $rule['type_discount'] && $rule['discount_amount'] < 0 ) {
									$minimum_price = $discount_price;
								}
							}
						} else {

							$price = $product->get_price();

							if ( YITH_WC_Dynamic_Pricing_Helper()->$valid_product_function( $rules, $product ) ) {
								$discount_price = ywdpd_get_discounted_price_table( $price, $rule );
							} else {
								$discount_price = $price;
							}

							if ( isset( $rule['discount_amount'] ) && $rule['discount_amount'] <= 0 && apply_filters( 'ywdpd_show_minimum_price_for_simple', true ) ) {
								$minimum_price = $discount_price < $minimum_price ? $minimum_price : $discount_price;
								$last_check    = true;
							}
						}

						if ( '' != $min_quantity && $rule['min_quantity'] == $min_quantity ) {
							$min_quantity_check = 1;
							break;
						}
					}
				}
			}

			if ( ! $last_check || ( '' != $min_quantity && ! $min_quantity_check ) ) {
				return $minimum_price;
			}

			$minimum_price = $minimum_price > $discount_price ? $discount_price : $minimum_price;

			return $minimum_price;
		}

		/**
		 * Get the maximum price.
		 *
		 * @param WC_Product|WC_Product_Variable $product Product.
		 * @param string                         $min_quantity Minimum quantity.
		 *
		 * @return int|mixed
		 */
		public function get_maximum_price( $product, $min_quantity = '' ) {

			$table_rules    = $this->get_table_rules( $product );
			$maximum_price  = $product->get_price();
			$discount_price = 0;
			if ( $product->get_type() == 'variable' ) {

				$prices        = $product->get_variation_prices();
				$prices        = isset( $prices['price'] ) ? $prices['price'] : array();
				$maximum_price = end( $prices );
			}

			if ( $table_rules ) {
				foreach ( $table_rules as $rules ) {
					foreach ( $rules['rules'] as $rule ) {
						$main_rule            = $rules;
						$enable_adjustment_to = isset( $rules['active_apply_discount_to'] ) && yith_plugin_fw_is_true( $rules['active_apply_discount_to'] );

						$valid_product_function = 'valid_product_to_apply_bulk';
						$apply_to_valid         = true;
						if ( $enable_adjustment_to ) {
							$valid_product_function = 'valid_product_to_adjustment_bulk';
							$apply_to_valid         = YITH_WC_Dynamic_Pricing_Helper()->validate_apply_to_in_cart( $rules );
						}
						if ( $product->is_type( 'variable' ) ) {
							$prices = apply_filters( 'ywdpd_get_variable_prices', $product->get_variation_prices(), $product );
							$prices = isset( $prices['price'] ) ? $prices['price'] : array();

							if ( $prices ) {
								$min_price = current( $prices );
								$max_price = end( $prices );
								if ( $min_price == $max_price ) {
									// for products where only the variation is discounted.
									foreach ( $prices as $id => $p ) {
										if ( YITH_WC_Dynamic_Pricing_Helper()->$valid_product_function( $main_rule, wc_get_product( $id ) ) ) {
											$curr_discount_price = ywdpd_get_discounted_price_table( $p, $rule );
										} else {
											$curr_discount_price = $p;
										}
										$discount_price = $curr_discount_price > $discount_price ? $curr_discount_price : $discount_price;
									}
								} else {
									$max_key       = array_search( $max_price, $prices );
									$maximum_price = $max_price;

									if ( '' != $min_quantity && $rule['min_quantity'] != $min_quantity ) {
										continue;
									}

									if ( YITH_WC_Dynamic_Pricing_Helper()->$valid_product_function( $rules, wc_get_product( $max_key ) ) ) {
										$discount_max_price = ywdpd_get_discounted_price_table( $max_price, $rule );
									} else {
										$discount_max_price = $max_price;
									}

									$discount_price = $discount_max_price > $discount_price ? $discount_max_price : $discount_price;
								}
							}
						} else {
							$discount_price = ywdpd_get_discounted_price_table( $maximum_price, $rule );
						}

						if ( '' != $min_quantity && $rule['min_quantity'] == $min_quantity ) {
							break;
						}
					}
				}
			}

			if ( $discount_price ) {
				$maximum_price = $discount_price;
			}

			return $maximum_price;
		}

		/**
		 * Get min variation price.
		 *
		 * @param WC_Product_Variable $product Product variation.
		 *
		 * @return string
		 * @since  1.1.3
		 */
		public function get_min_regular_variation_price( $product ) {

			$price = null;

			if ( $product->is_type( 'variable' ) ) {

				$prices_array = $product->get_variation_prices();

				if ( isset( $prices_array['regular_price'] ) ) {

					foreach ( $prices_array['regular_price'] as $single_price ) {

						if ( ! isset( $price ) ) {

							$price = $single_price;
						} elseif ( $price > 0 && $single_price < $price ) {

							$price = $single_price;
						}
					}
				}
			}

			return isset( $price ) ? $price : '';
		}

		/**
		 * Get max regular variation price.
		 *
		 * @param WC_Product_Variable $product Product variation.
		 *
		 * @return string
		 * @since  1.1.3
		 */
		public function get_max_regular_variation_price( $product ) {

			$price = null;

			if ( $product->is_type( 'variable' ) ) {

				$prices_array = $product->get_variation_prices();

				if ( isset( $prices_array['regular_price'] ) ) {

					foreach ( $prices_array['regular_price'] as $single_price ) {

						if ( ! isset( $price ) ) {

							$price = $single_price;
						} elseif ( $price > 0 && $single_price > $price ) {

							$price = $single_price;
						}
					}
				}
			}

			return isset( $price ) ? $price : '';
		}

		/**
		 * Get price modified.
		 *
		 * @param float      $price Price.
		 * @param WC_Product $product Product.
		 *
		 * @return mixed
		 */
		public function get_price( $price, $product ) {

			global $woocommerce_loop;
			$product_id = $product->get_id();

			if ( ( ( is_cart() || is_checkout() ) && is_null( $woocommerce_loop ) ) || ! YITH_WC_Dynamic_Pricing()->check_discount( $product ) || ! apply_filters( 'ywdpd_apply_discount', true, $price, $product ) || empty( $price ) ) {
				return $price;
			}

			if ( array_key_exists( $product_id, $this->has_get_price_filter ) || apply_filters( 'ywdpd_get_price_exclusion', false, $price, $product ) || YITH_WC_Dynamic_Pricing_Helper()->is_in_exclusion_rule( array( 'product_id' => $product_id ) ) ) {

				return apply_filters( 'yith_ywdpd_get_price_exclusion_rule', isset( $this->has_get_price_filter[ $product_id ] ) ? $this->has_get_price_filter[ $product_id ] : $price, $price, $product );
			}

			$discount = (string) YITH_WC_Dynamic_Pricing()->get_discount_price( $price, $product );

			$this->has_get_price_filter[ $product_id ] = $discount;

			return apply_filters( 'yith_ywdpd_get_price', $discount, $product );
		}

		/**
		 * Enqueue styles and scripts
		 *
		 * @access public
		 * @return void
		 * @since  1.0.0
		 */
		public function enqueue_styles_scripts() {
			$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

			wp_register_script( 'yith_ywdpd_frontend', YITH_YWDPD_ASSETS_URL . '/js/ywdpd-frontend' . $suffix . '.js', array( 'jquery' ), YITH_YWDPD_VERSION, true );
			wp_enqueue_style( 'yith_ywdpd_frontend', YITH_YWDPD_ASSETS_URL . '/css/frontend.css', false, YITH_YWDPD_VERSION );

			if ( $this->check_pricing_rules_combination() ) {

				$script = "jQuery( document.body ).on( 'updated_cart_totals', function(){
						window.location.href = window.location.href;
					});";
				wp_add_inline_script( 'wc-cart', $script );
			}

			$show_minimum_price = 'max' == YITH_WC_Dynamic_Options::get_default_price();
			$template           = YITH_WC_Dynamic_Options::get_quantity_table_layout();
			$change_qty         = YITH_WC_Dynamic_Options::update_price_on_qty_changes();
			$default_qty        = YITH_WC_Dynamic_Options::is_default_qty_selected();
			$args               = array(
				'show_minimum_price'        => yith_plugin_fw_is_true( $show_minimum_price ) ? 'yes' : 'no',
				'template'                  => apply_filters( 'ywdpd_table_orientation', $template ),
				'is_change_qty_enabled'     => $change_qty ? 'yes' : 'no',
				'is_default_qty_enabled'    => $default_qty ? 'yes' : 'no',
				'column_product_info_class' => apply_filters( 'ywdpd_column_product_info_class', '.single-product' ),
				'product_price_classes'     => apply_filters( 'ywdpd_product_price_classes', '.summary .price, .wpb_wrapper .price, .elementor-widget-woocommerce-product-price .price' ),
				'product_qty_classes'       => apply_filters( 'ywdpd_product_qty_classes', '.summary .qty, .elementor-add-to-cart .qty, .w-post-elm .qty' ),
				'variation_form_class'      => apply_filters( 'ywdpd_variation_form_class', '.summary  form.variations_form.cart' ),
				'select_minimum_quantity'   => apply_filters( 'ywdpd_minimum_quantity', false ),
			);

			wp_localize_script( 'yith_ywdpd_frontend', 'ywdpd_qty_args', $args );
			wp_enqueue_script( 'yith_ywdpd_frontend' );
		}

		/**
		 * Check if pricing rules has disabled the combination with coupons
		 *
		 * @access public
		 * @return bool
		 * @since  1.1.4
		 */
		public function check_pricing_rules_combination() {
			if ( ! WC()->cart ) {
				return false;
			}
			$cart_coupons = WC()->cart->applied_coupons;
			if ( ! empty( $cart_coupons ) && $this->pricing_rules ) {
				foreach ( $this->pricing_rules as $pricing_rule ) {
					$with_other_coupons = isset( $pricing_rule['disable_with_other_coupon'] ) && yith_plugin_fw_is_true( $pricing_rule['disable_with_other_coupon'] );
					if ( $with_other_coupons && ywdpd_check_cart_coupon() ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		 * Show table quantity in the single product if there's a pricing rule
		 *
		 * @param WC_Product|bool $product Product.
		 * @param bool            $sh Boolean.
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		public function show_table_quantity( $product = false, $sh = false ) {
			if ( ! $product ) {
				global $product;
			}

			if ( apply_filters( 'ywdpd_exclude_products_from_discount', false, $product ) ) {
				return;
			}

			$table_rules = $this->get_table_rules( $product );

			if ( $table_rules ) {
				echo ( $sh ) ? '<div class="ywdpd-table-discounts-wrapper-sh">' : '<div class="ywdpd-table-discounts-wrapper">';

				foreach ( $table_rules as $rule ) {
					$showtable = isset( $rule['show_table_price'] ) && yith_plugin_fw_is_true( $rule['show_table_price'] );
					if ( ! $showtable ) {
						continue;
					}
					$show_quantity_table_schedule = YITH_WC_Dynamic_Options::show_quantity_table_schedule();
					$table_label                  = YITH_WC_Dynamic_Options::get_quantity_columns_table_title();
					$until                        = '';
					if ( 'schedule_dates' === $rule['schedule_discount_mode']['schedule_type'] ) {
						$schedule_to = $rule['schedule_discount_mode']['schedule_to'];
						$until       = sprintf( __( 'Offer ends: %s', 'ywdpd' ), date_i18n( wc_date_format() . ' ' . wc_time_format(), strtotime( $schedule_to ) ) );
					}
					$args = array(
						'rules'          => $rule['rules'],
						'main_rule'      => $rule,
						'product'        => $product,
						'note'           => ywdpd_get_note( $rule['table_note'] ),
						'label_table'    => YITH_WC_Dynamic_Options::get_quantity_table_title(),
						'label_quantity' => $table_label['quantity'],
						'label_price'    => $table_label['price'],
						'until'          => ( yith_plugin_fw_is_true( $show_quantity_table_schedule ) ) ? $until : '',
					);

					wc_get_template( 'yith_ywdpd_table_pricing.php', $args, '', YITH_YWDPD_TEMPLATE_PATH );
				}

				add_filter( 'woocommerce_product_get_price', array( $this, 'get_price' ), 10, 2 );
				echo '</div>';
			} else {
				echo '<div class="ywdpd-table-discounts-wrapper"></div>';
			}
		}

		/**
		 * Table quantity.
		 *
		 * @param WC_Product $product Product.
		 *
		 * @return string
		 */
		public function table_quantity( $product ) {
			ob_start();
			$this->show_table_quantity( $product );

			return ob_get_clean();
		}

		/**
		 * Table Quantity Shortcode
		 *
		 * @param array $atts Attributes.
		 * @param null  $content Shortcode Content.
		 *
		 * @return mixed
		 * @internal param $product
		 */
		public function table_quantity_shortcode( $atts, $content = null ) {

			$args = shortcode_atts(
				array(
					'product' => false,
				),
				$atts
			);

			if ( ! $args['product'] ) {
				global $product;
				$the_product = $product;
			} else {
				$the_product = wc_get_product( $args['product'] );
			}

			if ( ! $the_product || apply_filters( 'ywdpd_exclude_products_from_discount', false, $the_product ) ) {
				return '';
			}

			ob_start();
			$this->show_table_quantity( $the_product, true );

			return ob_get_clean();
		}


		/**
		 * Product Note Shortcode
		 *
		 * @param array $atts Attributes.
		 * @param null  $content Shortcode Content.
		 *
		 * @return mixed
		 * @internal param $product
		 */
		public function product_note_shortcode( $atts, $content = null ) {

			$args = shortcode_atts(
				array(
					'product' => false,
				),
				$atts
			);

			if ( ! $args['product'] ) {
				global $product;
				$the_product = $product;
			} else {
				$the_product = wc_get_product( $args['product'] );
			}

			if ( ! $the_product || apply_filters( 'ywdpd_exclude_products_from_discount', false, $the_product ) ) {
				return '';
			}

			ob_start();
			$this->show_note_on_products( $the_product );

			return ob_get_clean();
		}


		/**
		 * Add action for single product page to display table pricing
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		public function table_quantity_init() {
			 // Table Pricing.
			$position                    = YITH_WC_Dynamic_Options::get_quantity_table_position();
			$priority_single_add_to_cart = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' );
			$priority_single_excerpt     = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt' );

			$custom_hook = apply_filters( 'ywdpd_table_custom_hook', array() );

			if ( ! empty( $custom_hook ) && isset( $custom_hook['hook'] ) ) {
				$hook     = $custom_hook['hook'];
				$priority = isset( $custom_hook['priority'] ) ? $custom_hook['priority'] : 10;
				add_action( $hook, array( $this, 'show_table_quantity' ), $priority );

				return;
			}

			switch ( $position ) {
				case 'before_add_to_cart':
					if ( $priority_single_add_to_cart ) {
						add_action(
							'woocommerce_single_product_summary',
							array(
								$this,
								'show_table_quantity',
							),
							$priority_single_add_to_cart - 1
						);
					} else {
						add_action( 'woocommerce_single_product_summary', array( $this, 'show_table_quantity' ), 28 );
					}
					break;
				case 'after_add_to_cart':
					if ( $priority_single_add_to_cart ) {
						add_action(
							'woocommerce_single_product_summary',
							array(
								$this,
								'show_table_quantity',
							),
							$priority_single_add_to_cart + 1
						);
					} else {
						add_action( 'woocommerce_single_product_summary', array( $this, 'show_table_quantity' ), 32 );
					}
					break;
				case 'before_excerpt':
					if ( $priority_single_excerpt ) {
						add_action(
							'woocommerce_single_product_summary',
							array(
								$this,
								'show_table_quantity',
							),
							$priority_single_excerpt - 1
						);
					} else {
						add_action( 'woocommerce_single_product_summary', array( $this, 'show_table_quantity' ), 18 );
					}
					break;
				case 'after_excerpt':
					if ( $priority_single_excerpt ) {
						add_action(
							'woocommerce_single_product_summary',
							array(
								$this,
								'show_table_quantity',
							),
							$priority_single_excerpt + 1
						);
					} else {
						add_action( 'woocommerce_single_product_summary', array( $this, 'show_table_quantity' ), 22 );
					}
					break;
				case 'after_meta':
					$priority_after_meta = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta' );
					if ( $priority_after_meta ) {
						add_action(
							'woocommerce_single_product_summary',
							array(
								$this,
								'show_table_quantity',
							),
							$priority_after_meta + 1
						);
					} else {
						add_action( 'woocommerce_single_product_summary', array( $this, 'show_table_quantity' ), 42 );
					}
					break;
				default:
					break;
			}
		}


		/**
		 * Add action for single product page to display table pricing
		 *
		 * @since  1.0.0
		 * @author Emanuela Castorina
		 */
		public function note_on_products_init() {
			// Table Pricing.
			$position                    = YITH_WC_Dynamic_Options::get_show_note_on_products_place();
			$priority_single_add_to_cart = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart' );
			$priority_single_excerpt     = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt' );

			$custom_hook = apply_filters( 'ywdpd_note_custom_hook', array() );

			if ( ! empty( $custom_hook ) && isset( $custom_hook['hook'] ) ) {
				$hook     = $custom_hook['hook'];
				$priority = isset( $custom_hook['priority'] ) ? $custom_hook['priority'] : 10;
				add_action( $hook, array( $this, 'show_note_on_products' ), $priority );

				return;
			}

			switch ( $position ) {
				case 'before_add_to_cart':
					if ( $priority_single_add_to_cart ) {
						add_action(
							'woocommerce_single_product_summary',
							array(
								$this,
								'show_note_on_products',
							),
							$priority_single_add_to_cart - 1
						);
					} else {
						add_action( 'woocommerce_single_product_summary', array( $this, 'show_note_on_products' ), 28 );
					}
					break;
				case 'after_add_to_cart':
					if ( $priority_single_add_to_cart ) {
						add_action(
							'woocommerce_single_product_summary',
							array(
								$this,
								'show_note_on_products',
							),
							$priority_single_add_to_cart + 1
						);
					} else {
						add_action( 'woocommerce_single_product_summary', array( $this, 'show_note_on_products' ), 32 );
					}
					break;
				case 'before_excerpt':
					if ( $priority_single_excerpt ) {
						add_action(
							'woocommerce_single_product_summary',
							array(
								$this,
								'show_note_on_products',
							),
							$priority_single_excerpt - 1
						);
					} else {
						add_action( 'woocommerce_single_product_summary', array( $this, 'show_note_on_products' ), 18 );
					}
					break;
				case 'after_excerpt':
					if ( $priority_single_excerpt ) {
						add_action(
							'woocommerce_single_product_summary',
							array(
								$this,
								'show_note_on_products',
							),
							$priority_single_excerpt + 1
						);
					} else {
						add_action( 'woocommerce_single_product_summary', array( $this, 'show_note_on_products' ), 22 );
					}
					break;
				case 'after_meta':
					$priority_after_meta = has_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta' );
					if ( $priority_after_meta ) {
						add_action(
							'woocommerce_single_product_summary',
							array(
								$this,
								'show_note_on_products',
							),
							$priority_after_meta + 1
						);
					} else {
						add_action( 'woocommerce_single_product_summary', array( $this, 'show_note_on_products' ), 42 );
					}
					break;
				default:
					break;
			}
		}

		public function show_total_discount_message_on_cart() {
			$message          = '';
			$original_message = YITH_WC_Dynamic_Options::get_discount_info_message();

			$coupons_applied = WC()->cart->get_applied_coupons();
			$tax_included    = 'tax_excluded' === YITH_WC_Dynamic_Options::how_calculate_discounts();
			$amount          = 0;
			foreach ( $coupons_applied as $coupon_code ) {

				$coupon = new WC_Coupon( $coupon_code );
				$meta   = $coupon->get_meta( 'ywdpd_coupon' );
				if ( ! empty( $meta ) ) {
					$amount = WC()->cart->get_coupon_discount_amount( $coupon->get_code(), $tax_included );
					break;
				}
			}

			$subtotal = WC()->cart->get_subtotal();
			if ( ! $tax_included ) {
				$subtotal += WC()->cart->get_subtotal_tax();
			}

			if ( $amount > 0 ) {

				$perc_discount  = round( $amount / $subtotal, 2 ) * 100;
				$price_discount = $subtotal - $amount;
				$message        = str_replace( '%total_discount_percentage%', $perc_discount . '%', $original_message );
				$message        = str_replace( '%total_discount_price%', $price_discount, $message );
				$message        = sprintf( '<div class="ywdpd_single_cart_notice">%s</div>', $message );
			}

			if ( ! empty( $message ) ) {
				?>
				<tr class="dynamic-discount">
					<td colspan="2" data-title="<?php _e( 'Discount', 'ywdpd' ); ?>"><?php echo $message; ?></td>
				</tr>
				<?php
			}
		}

		/**
		 * Enqueue scripts and style
		 */
		public function enqueue_popup_scripts() {
			wp_register_script( 'ywdpd_popup', YITH_YWDPD_ASSETS_URL . '/js/' . yit_load_js_file( 'ywdpd-gift-popup.js' ), array( 'jquery' ), YITH_YWDPD_VERSION, true );
			wp_register_script( 'ywdpd_owl', YITH_YWDPD_ASSETS_URL . '/js/owl/owl.carousel.min.js', array( 'jquery' ), YITH_YWDPD_VERSION, true );
			wp_register_style( 'ywdpd_owl', YITH_YWDPD_ASSETS_URL . '/css/owl/owl.carousel.min.css', array(), YITH_YWDPD_VERSION );
			wp_register_style( 'ywdpd_owl_theme', YITH_YWDPD_ASSETS_URL . '/css/owl/owl.carousel.min.css', array(), YITH_YWDPD_VERSION );

			$args = array(
				'ajax_url'             => admin_url( 'admin-ajax.php', is_ssl() ? 'https' : 'http' ),
				'actions'              => array(
					'add_gift_to_cart'    => 'add_gift_to_cart',
					'add_special_to_cart' => 'add_special_to_cart',
					'show_second_step'    => 'show_second_step',
					'check_variable'      => 'check_variable',
				),
				'i18n_qty_field_label' => esc_html__( 'Qty in cart', 'ywdpd' ),
			);
			wp_localize_script( 'ywdpd_popup', 'ywdpd_popup_args', $args );

			if ( is_cart() || is_product() ) {

				wp_enqueue_script( 'ywdpd_popup' );
				$params = array(
					'wc_ajax_url'                      => WC_AJAX::get_endpoint( '%%endpoint%%' ),
					'i18n_no_matching_variations_text' => esc_attr__( 'Sorry, no products matched your selection. Please choose a different combination.', 'woocommerce' ),
					'i18n_make_a_selection_text'       => esc_attr__( 'Please select some product options before adding this product to your cart.', 'woocommerce' ),
					'i18n_unavailable_text'            => esc_attr__( 'Sorry, this product is unavailable. Please choose a different combination.', 'woocommerce' ),
				);

				wp_enqueue_script(
					'wc-add-to-cart-variation',
					WC()->plugin_url() . 'assets/js/frontend/add-to-cart-variation.min.js',
					array(
						'jquery',
						'wp-util',
						'jquery-blockui',
					),
					WC()->version
				);

				wp_localize_script( 'wc-add-to-cart-variation', 'wc_add_to_cart_variation_params', $params );

				wp_enqueue_script( 'ywdpd_owl' );
				wp_enqueue_style( 'ywdpd_owl_theme' );
				wp_enqueue_style( 'ywdpd_owl' );
			}
		}

		/**
		 * check if can show popup in the page
		 *
		 * @auhtor YITH
		 * @since 2.1
		 */
		public function show_popup() {
			if ( is_cart() ) {

				$this->print_popup_for_gift_rules();
			}
		}

		/**
		 * show the gift rules in cart
		 *
		 * @author YITH
		 * @since 2.1
		 */
		public function print_popup_for_gift_rules() {
			$items_to_show = YITH_WC_Dynamic_Pricing_Gift_Product()->get_gift_product_to_add_popup();

			if ( count( $items_to_show ) > 0 ) {
				wc_get_template(
					'yith_ywdpd_popup.php',
					array(
						'items_to_show' => $items_to_show,
						'popup_class'   => 'cart',
					),
					YITH_YWDPD_TEMPLATE_PATH,
					YITH_YWDPD_TEMPLATE_PATH
				);
			}
		}

		/**
		 * show the special offer in product page
		 *
		 * @author YITH
		 * @since 2.1
		 */
		public function print_popup_for_special_offer( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

			if ( isset( $_REQUEST['add-to-cart'] ) ) {
				$cart_item = WC()->cart->get_cart_item( $cart_item_key );

				if ( count( $cart_item ) > 0 ) {
					$items_to_show = YITH_WC_Dynamic_Pricing_Helper()->get_valid_special_offer_to_apply( $cart_item, $cart_item_key );
					if ( count( $items_to_show ) > 0 ) {
						wc_get_template(
							'yith_ywdpd_popup.php',
							array(
								'items_to_show' => $items_to_show,
								'popup_class'   => 'product',
							),
							YITH_YWDPD_TEMPLATE_PATH,
							YITH_YWDPD_TEMPLATE_PATH
						);
					}
				}
			}
		}

		/**
		 * add the product in the cart and will be applied the special offer
		 *
		 * @auhtor YITH
		 * @throws Exception
		 * @since 2.1
		 */
		public function add_special_to_cart() {

			// phpcs:disable WordPress.Security.NonceVerification.Missing
			$posted = $_REQUEST;

			if ( isset( $posted['rules_to_apply'] ) ) {
				$rules = $posted['rules_to_apply'];
				foreach ( $rules as $rule_id => $products_to_add ) {
					foreach ( $products_to_add as $product_to_add ) {

						$qty          = ! empty( $product_to_add['quantity'] ) ? $product_to_add['quantity'] : 1;
						$product_id   = isset( $product_to_add['product_id'] ) ? $product_to_add['product_id'] : false;
						$variation_id = ! empty( $product_to_add['variation_id'] ) && $product_to_add['variation_id'] > 0 ? $product_to_add['variation_id'] : 0;
						$variations   = ! empty( $product_to_add['variations'] ) ? $product_to_add['variations'] : array();
						if ( $product_id ) {

							$product = wc_get_product( $product_id );
							if ( 'variation' === $product->get_type() && 0 === $variation_id ) {
								$product_id   = $product->get_parent_id();
								$variation_id = $product->get_id();
								$variations   = $product->get_attributes();
							}

							if ( $variation_id > 0 && count( $variations ) > 0 ) {

								$product = wc_get_product( $variation_id );
							}

							$cart_item_key = WC()->cart->add_to_cart(
								$product_id,
								$qty,
								$variation_id,
								$variations
							);

							wc_add_to_cart_message( array( $product->get_id() => $qty ) );

						}
					}
				}

				WC_AJAX::get_refreshed_fragments();
			}
		}

		/**
		 *
		 * Second step in popup, only for variable products.
		 *
		 * @auhtor YITH
		 * @since 2.1
		 */
		public function show_second_step() {
			$posted = $_REQUEST;
			if ( isset( $posted['product_id'] ) ) {

				$rule_type = isset( $posted['rule_type'] ) ? $posted['rule_type'] : 'gift_products';
				$args      = array(
					'product_id' => $posted['product_id'],
					'rule_id'    => $posted['rule_id'],
					'rule_type'  => $rule_type,
					'discount'   => array(
						'type'   => 'percentage',
						'amount' => 1,
					),
				);

				if ( 'special_offer' === $rule_type ) {

					$validated_rules = YITH_WC_Dynamic_Pricing()->validated_rules;
					$rule            = isset( $validated_rules[ $posted['rule_id'] ] ) ? $validated_rules[ $posted['rule_id'] ] : false;
					if ( $rule ) {
						$args['discount']['type']   = $rule['so-rule']['type_discount'];
						$args['discount']['amount'] = $rule['so-rule']['discount_amount'];
					}
				}
				ob_start();
				wc_get_template( 'yith_ywdpd_popup_single_product.php', $args, YITH_YWDPD_TEMPLATE_PATH, YITH_YWDPD_TEMPLATE_PATH );
				$template = ob_get_contents();
				ob_end_clean();

				wp_send_json( array( 'template' => $template ) );
			}
		}

		/**
		 * Check if a variation is already added as gift or special offer
		 *
		 * @author YITH
		 * @since 1.6.0
		 */
		public function check_variable() {
			$posted = $_REQUEST;
			if ( isset( $posted['ywdp_check_rule_id'] ) && isset( $posted['product_id'] ) ) {

				$rule_id    = $posted['ywdp_check_rule_id'];
				$product_id = $posted['product_id'];
				$find       = false;
				$product    = wc_get_product( $product_id );
				$old_price  = $product->get_price();
				if ( 'special_offer' === $posted['rule_type'] ) {
					$discount_type   = $posted['type_discount'];
					$discount_amount = $posted['amount_discount'];
					$total_to_add    = $posted['tot_to_add'];

					if ( 'percentage' === $discount_type ) {
						if ( 1 == $discount_amount ) {
							$new_price = 0;
						} else {
							$new_price = $old_price - ( $old_price * $discount_amount );
							$new_price = wc_get_price_to_display( $product, array( 'price' => $old_price * $new_price ) );
						}
					} elseif ( 'price' === $discount_type ) {
						$new_price = $old_price - $discount_amount;
						$new_price = $new_price > 0 ? wc_get_price_to_display( $product, array( 'price' => $new_price ) ) : 0;
					} else {
						$new_price = wc_get_price_to_display( $product, array( 'price' => $discount_amount ) );
					}
					$validated_rules = YITH_WC_Dynamic_Pricing()->validated_rules;
					$rule            = isset( $validated_rules[ $rule_id ] ) ? $validated_rules[ $rule_id ] : false;
					if ( $rule ) {
						$total_added = YITH_WC_Dynamic_Pricing_Helper()->get_total_product_with_special_offer( $rule );

						if ( $total_added < $total_to_add ) {
							$find = false;
						} else {
							$find = true;
						}
					}
				} else {
					$new_price = 0;
					if ( ! is_null( WC()->cart ) ) {

						foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {

							$cart_rule_id = isset( $cart_item['ywdpd_rule_id'] ) ? $cart_item['ywdpd_rule_id'] : false;

							if ( $cart_rule_id && $rule_id == $cart_rule_id && $product_id == $cart_item['variation_id'] ) {
								$find = true;
								break;
							}
						}
					}
				}

				$price_html = wc_format_sale_price( wc_get_price_to_display( $product, array( 'price' => $old_price ) ), $new_price );
				wp_send_json(
					array(
						'variation_found' => $find,
						'price'           => $price_html,
					)
				);
			}
		}
	}
}

/**
 * Unique access to instance of YITH_WC_Dynamic_Pricing_Frontend class
 *
 * @return YITH_WC_Dynamic_Pricing_Frontend
 */
function YITH_WC_Dynamic_Pricing_Frontend() {
	return YITH_WC_Dynamic_Pricing_Frontend::get_instance();
}
