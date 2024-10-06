<?php
/**
 * Gift products class
 *
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.0.0
 * @version 1.6.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWDPD_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'YITH_WC_Dynamic_Pricing_Gift_Product' ) ) {

	/**
	 * Class YITH_WC_Dynamic_Pricing_Gift_Product
	 */
	class YITH_WC_Dynamic_Pricing_Gift_Product {

		/**
		 * Instance.
		 *
		 * @var YITH_WC_Dynamic_Pricing_Gift_Product
		 */
		protected static $instance;

		/**
		 * List of gift rules.
		 *
		 * @var array
		 */
		private $gift_rules = array();

		/**
		 * List of rules to apply.
		 *
		 * @var array
		 */
		private $gift_rules_to_apply = array();

		/**
		 * Constructor
		 */
		public function __construct() {

			add_action( 'init', array( $this, 'load_gift_rules' ), 20 );
			add_action( 'woocommerce_add_to_cart', array( $this, 'check_if_apply_rules' ), 25 );
			add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'check_if_apply_rules' ), 25 );
			add_filter( 'woocommerce_after_calculate_totals', array( $this, 'update_gift_products' ), 20, 1 );
			add_action(
				'ywdpd_before_cart_process_discounts',
				array(
					$this,
					'update_gift_products_before_cart_process_discount',
				),
				20
			);

			add_action( 'wp_loaded', array( $this, 'add_to_cart_gift_product' ), 25 );

			add_filter( 'woocommerce_add_cart_item', array( $this, 'change_price_gift_product' ), 20, 2 );
			add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'change_price_gift_product' ), 30, 2 );

			add_action( 'wp_ajax_add_gift_to_cart', array( $this, 'add_gift_to_cart' ) );
			add_action( 'wp_ajax_nopriv_add_gift_to_cart', array( $this, 'add_gift_to_cart' ) );

			add_filter( 'ywdpd_show_note_apply_to', array( $this, 'show_notes_on_product' ), 10, 3 );
			add_filter( 'ywdpd_show_note_apply_adjustment_to', array( $this, 'show_notes_on_gift_product' ), 10, 3 );
			add_filter( 'ywdpd_show_note_on_sale', array( $this, 'show_also_on_sale' ), 10, 2 );

			add_filter(
				'yith_dynamic_valid_sum_item_quantity',
				array(
					$this,
					'valid_sum_item_quantity_not_gifts',
				),
				10,
				1
			);
			add_filter(
				'yith_dynamic_valid_sum_item_quantity_less',
				array(
					$this,
					'valid_sum_item_quantity_not_gifts',
				),
				10,
				1
			);
			add_filter( 'ywdpd_get_cart_item_quantities', array( $this, 'valid_sum_item_quantity_not_gifts' ), 10, 1 );
			add_filter( 'woocommerce_cart_item_quantity', array( $this, 'hide_qty_field_for_gift' ), 30, 3 );

		}

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_WC_Dynamic_Pricing_Gift_Product
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Load the gift rules
		 */
		public function load_gift_rules() {
			$this->gift_rules = YITH_WC_Dynamic_Pricing()->get_gift_rules();
		}

		/**
		 * Check if the rule can be applied.
		 */
		public function check_if_apply_rules() {

			if ( ! is_null( WC()->cart ) && ! WC()->cart->is_empty() ) {

				$no_gift_products = $this->get_cart_products();

				if ( count( $no_gift_products ) > 0 ) {
					foreach ( $no_gift_products as $cart_item_key => $cart_item ) {
						foreach ( $this->gift_rules as $key => $rule ) {

							if ( $this->apply_to_is_valid( $rule, $cart_item ) ) {

								$this->gift_rules_to_apply[ $key ] = $rule;
							}
						}
					}
				} else {
					// remove all gift products.
					$this->remove_all_gift_product();
				}
			}
		}

		/**
		 * Remove all gift product in the cart
		 */
		public function remove_all_gift_product() {

			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

				$is_gift_product = isset( $cart_item['ywdpd_is_gift_product'] );
				if ( $is_gift_product ) {

					/**
					 * Product.
					 *
					 * @var WC_Product $product
					 */
					$product = $cart_item['data'];
					WC()->cart->remove_cart_item( $cart_item_key );
					/* translators: name of product */
					wc_add_notice( sprintf( __( 'Gift %s removed properly', 'ywdpd' ), $product->get_formatted_name() ) );
				}
			}
		}

		/**
		 * Remove gift product from cart.
		 *
		 * @param integer $rule_id Rule id.
		 */
		public function remove_gift_product( $rule_id ) {

			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

				$is_gift_product = isset( $cart_item['ywdpd_is_gift_product'] );
				$cart_rule_id    = isset( $cart_item['ywdpd_rule_id'] ) ? $cart_item['ywdpd_rule_id'] : false;

				if ( $is_gift_product && $cart_rule_id == $rule_id ) {
					$product = $cart_item['data'];
					WC()->cart->remove_cart_item( $cart_item_key );
					/* translators: name of product */
					wc_add_notice( sprintf( __( 'Gift %s removed properly', 'ywdpd' ), $product->get_formatted_name() ) );
				}
			}
		}

		/**
		 * Calculate how many gift are on cart.
		 *
		 * @param integer $rule_id Rule id.
		 *
		 * @return int|mixed
		 */
		public function get_total_gift_product_by_rule( $rule_id ) {
			$total = 0;
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

				$is_gift_product = isset( $cart_item['ywdpd_is_gift_product'] );
				$cart_rule_id    = isset( $cart_item['ywdpd_rule_id'] ) ? $cart_item['ywdpd_rule_id'] : false;

				if ( $is_gift_product && $cart_rule_id == $rule_id ) {

					$total += $cart_item['quantity'];
				}
			}

			return $total;
		}

		/**
		 * Check if on cart there's a gift
		 *
		 * @param integer $rule_id Rule id.
		 * @param int     $product_id Product id.
		 *
		 * @return bool
		 */
		public function is_gift_product_in_cart( $rule_id, $product_id ) {

			$found = false;
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

				$is_gift_product = isset( $cart_item['ywdpd_is_gift_product'] );
				$cart_rule_id    = isset( $cart_item['ywdpd_rule_id'] ) ? $cart_item['ywdpd_rule_id'] : false;
				if ( $is_gift_product && $cart_rule_id == $rule_id ) {

					$data_product_id = $cart_item['data']->get_id();

					if ( $data_product_id == $product_id ) {
						return true;
					}
				}
			}

			return $found;
		}

		/**
		 * Get the product on cart that aren't gift.
		 *
		 * @return array
		 */
		public function get_cart_products() {

			$products_in_cart = array();
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {

				$is_gift_product = isset( $cart_item['ywdpd_is_gift_product'] );

				if ( ! $is_gift_product ) {

					$products_in_cart[ $cart_item_key ] = $cart_item;
				}
			}

			return $products_in_cart;
		}

		/**
		 * Get total item on cart.
		 *
		 * @param array $rule Rule.
		 *
		 * @return int
		 */
		public function get_total_item_in_cart( $rule, $rule_type = '', $exclude_type = '' ) {

			$cart_item_total = 0;

			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				$is_gift_product = isset( $cart_item['ywdpd_is_gift_product'] );

				if ( ! $is_gift_product ) {

					switch ( $rule_type ) {
						case 'all_products':
							$cart_item_total += $cart_item['quantity'];

							break;
						case 'specific_products':
							$product_list = $rule['rule_for_products_list'];
							if ( YITH_WC_Dynamic_Pricing_Helper()->product_in_list( $cart_item, $product_list ) ) {
								$cart_item_total += $cart_item['quantity'];
							}
							break;
						case 'specific_categories':
							$cart_item_total = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy_quantity( $rule['rule_for_categories_list'], 'product_cat' );
							break;

						case 'specific_tag':
							$cart_item_total = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy_quantity( $rule['rule_for_tags_list'], 'product_tag' );
							break;

						case 'vendor_list':
							if ( ! class_exists( 'YITH_Vendors' ) ) {
								break;
							}
							$vendor_list    = array_map( 'intval', $rule['rule_for_vendors_list'] );
							$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
							$intersect      = array_intersect( $vendor_of_item, $vendor_list );
							if ( ! empty( $intersect ) ) {
								$cart_item_total += $cart_item['quantity'];
							}

							break;
					}
				}
			}

			if ( ! empty( $exclude_type ) ) {
				$cart_item_total -= $this->get_total_item_excluded_in_cart( $rule, $exclude_type );
			}

			return $cart_item_total;
		}

		public function get_total_item_excluded_in_cart( $rule, $type ) {

			$item_excluded = 0;
			foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
				$is_gift_product = isset( $cart_item['ywdpd_is_gift_product'] );

				if ( ! $is_gift_product ) {
					switch ( $type ) {
						case 'specific_products':
							$product_list = $rule['exclude_rule_for_products_list'];
							if ( YITH_WC_Dynamic_Pricing_Helper()->product_in_list( $cart_item, $product_list ) ) {
								$item_excluded += $cart_item['quantity'];
							}
							break;
						case 'specific_categories':
							$item_excluded = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy_quantity( $rule['exclude_rule_for_categories_list'], 'product_cat' );
							break;
						case 'specific_tag':
							$item_excluded = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy_quantity( $rule['exclude_rule_for_tags_list'], 'product_tag' );
							break;
						case 'vendor_list':
							if ( ! class_exists( 'YITH_Vendors' ) ) {
								break;
							}

							$vendor_list    = array_map( 'intval', $rule['exclude_rule_for_vendors_list'] );
							$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
							$intersect      = array_intersect( $vendor_of_item, $vendor_list );
							if ( empty( $intersect ) ) {
								$item_excluded = $cart_item['quantity'];
							}

							break;
					}
				}
			}

			return $item_excluded;
		}

		/**
		 * Apply the rule if it is valid.
		 *
		 * @param array $rule Rule.
		 * @param array $cart_item Cart item.
		 *
		 * @return boolean
		 */
		public function apply_to_is_valid( $rule, $cart_item ) {

			$is_excluded  = isset( $rule['active_exclude'] ) && yith_plugin_fw_is_true( $rule['active_exclude'] );
			$is_exclusion = false;
			$is_valid     = false;
			$rule_type    = '';
			$exc_type     = '';

			if ( $is_excluded ) {

				$exc_type = $rule['exclude_rule_for'];

				switch ( $exc_type ) {

					case 'specific_products':
						$product_list = $rule['exclude_rule_for_products_list'];
						if ( is_array( $product_list ) && YITH_WC_Dynamic_Pricing_Helper()->product_in_list( $cart_item, $product_list ) ) {
							$is_exclusion = true;
						}
						break;
					case 'specific_categories':
						$category_list = $rule['exclude_rule_for_categories_list'];
						if ( YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy( $category_list, $cart_item['product_id'], 'product_cat' ) ) {
							$is_exclusion = true;
						}
						break;
					case 'specific_tag':
						$tag_list = $rule['exclude_rule_for_tags_list'];
						if ( YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy( $tag_list, $cart_item['product_id'], 'product_tag' ) ) {
							$is_exclusion = true;
						}
						break;
					case 'vendor_list':
						if ( ! class_exists( 'YITH_Vendors' ) || ! isset( $rule['exclude_rule_for_vendors_list'] ) ) {
							break;
						}
						$vendor_list    = array_map( 'intval', $rule['exclude_rule_for_vendors_list'] );
						$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
						$intersect      = array_intersect( $vendor_of_item, $vendor_list );
						if ( ! empty( $intersect ) ) {
							$is_exclusion = true;
						}
						break;
				}
			}

			if ( ! $is_exclusion ) {

				$rule_type = $rule['rule_for'];

				switch ( $rule_type ) {
					case 'all_products':
						$is_valid = true;
						break;
					case 'specific_products':
						$product_list = $rule['rule_for_products_list'];

						if ( is_array( $product_list ) && YITH_WC_Dynamic_Pricing_Helper()->product_in_list( $cart_item, $product_list ) ) {
							$is_valid = true;
						}
						break;
					case 'specific_categories':
						$category_list = $rule['rule_for_categories_list'];

						if ( YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy( $category_list, $cart_item['product_id'], 'product_cat' ) ) {
							$is_valid = true;
						}
						break;
					case 'specific_tag':
						$tag_list = $rule['rule_for_tags_list'];
						if ( YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy( $tag_list, $cart_item['product_id'], 'product_tag' ) ) {
							$is_valid = true;
						}
						break;
					case 'vendor_list':
						if ( ! class_exists( 'YITH_Vendors' ) || ! isset( $rule['rule_for_vendors_list'] ) ) {
							break;
						}
						$vendor_list    = array_map( 'intval', $rule['rule_for_vendors_list'] );
						$vendor_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_Vendors()->get_taxonomy_name(), array( 'fields' => 'ids' ) );
						$intersect      = array_intersect( $vendor_of_item, $vendor_list );
						if ( ! empty( $intersect ) ) {
							$is_valid = true;
						}
						break;

				}
			}

			if ( $is_valid ) {

				$gift_mode = isset( $rule['gift_mode'] ) ? $rule['gift_mode'] : 'cart_item';

				if ( 'cart_item' === $gift_mode ) {
					$need_items_in_cart = (int) $rule['n_items_in_cart']['n_items'];
					$criteria           = $rule['n_items_in_cart']['condition'];
					$items_in_cart      = (int) $this->get_total_item_in_cart( $rule, $rule_type, $exc_type );

					switch ( $criteria ) {
						case '>':
							$is_valid = $items_in_cart > $need_items_in_cart;
							break;
						case '<':
							$is_valid = $items_in_cart < $need_items_in_cart;
							break;
						case '==':
							$is_valid = (int) $items_in_cart === (int) $need_items_in_cart;
							break;
						default:
							$is_valid = $items_in_cart !== $need_items_in_cart;
							break;

					}
				} else {
					$min_subtotal = isset( $rule['gift_subtotal'] ) ? $rule['gift_subtotal'] : 100;

					$subtotal = ! is_null( WC()->cart ) ? WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() : 0;
					$subtotal = apply_filters( 'ywdpd_gift_subtotal', $subtotal, $rule );
					if ( 0 !== $subtotal && $min_subtotal >= $subtotal ) {
						$is_valid = false;
					}
				}
			}

			return apply_filters( 'ywdpd_apply_to_is_valid', $is_valid );
		}

		/**
		 * get the gift rules.
		 */
		public function get_gift_product_to_add_popup() {
			$gift_rules_valid = array();

			if ( count( $this->gift_rules_to_apply ) > 0 ) {

				foreach ( $this->gift_rules_to_apply as $key => $rule ) {
					$items_in_cart = $this->get_total_gift_product_by_rule( $key );

					$allowed_item = $rule['amount_gift_product_allowed'];
					$rule_text    = ! empty( $rule['text_in_modal_gift'] ) ? $rule['text_in_modal_gift'] : __( 'You can add {{total_to_add}} product(s) for free!', 'ywdpd' );

					if ( $items_in_cart < $allowed_item ) {
						$product_to_gift = isset( $rule['gift_product_selection'] ) ? $rule['gift_product_selection'] : array();

						$gift_rules_valid[ $key ] = array(
							'text'          => $rule_text,
							'items_in_cart' => $items_in_cart,
							'allowed_item'  => $allowed_item - $items_in_cart,
							'items'         => array(
								'type'     => 'product_ids',
								'item_ids' => $product_to_gift,
							),
							'type'          => 'gift_products',
							'discount'      => array(
								'type'   => 'percentage',
								'amount' => 1,
							),

						);
					}
				}
			}

			return $gift_rules_valid;
		}

		/**
		 * Add to cart the gift product.
		 *
		 * @throws Exception Return an error.
		 */
		public function add_to_cart_gift_product() {

			$posted = $_REQUEST;

			if ( isset( $posted['ywdpd_add_to_cart_gift'] ) ) {

				$product = wc_get_product( $posted['ywdpd_add_to_cart_gift'] );
				$rule_id = isset( $posted['ywdpd_rule_id'] ) ? $posted['ywdpd_rule_id'] : false;

				$total_items_in_cart = $this->get_total_gift_product_by_rule( $rule_id );

				$allowed_items = $this->gift_rules_to_apply[ $rule_id ]['amount_gift_product_allowed'];

				if ( $rule_id && isset( $this->gift_rules_to_apply[ $rule_id ] ) && $total_items_in_cart + 1 <= $allowed_items ) {

					if ( $product->is_type( 'variation' ) ) {
						$product_id   = $product->get_parent_id();
						$variation_id = $product->get_id();
					} else {
						$product_id   = $product->get_id();
						$variation_id = 0;
					}

					WC()->cart->add_to_cart(
						$product_id,
						1,
						$variation_id,
						array(),
						array(
							'ywdpd_is_gift_product' => true,
							'ywdpd_rule_id'         => $rule_id,
						)
					);
				}
			}
		}

		/**
		 * Add gift to cart.
		 *
		 * @throws Exception Get the error.
		 */
		public function add_gift_to_cart() {

			$posted = $_REQUEST;

			if ( isset( $posted['rules_to_apply'] ) ) {

				$this->load_gift_rules();
				$this->check_if_apply_rules();
				$rules = $posted['rules_to_apply'];
				foreach ( $rules as $rule_id => $products_to_add ) {
					$rule_id = str_replace( 'ywdpd_single_rule_', '', $rule_id );

					if ( isset( $this->gift_rules_to_apply[ $rule_id ] ) ) {
						$total_items_in_cart = $this->get_total_gift_product_by_rule( $rule_id );
						$allowed_items       = $this->gift_rules_to_apply[ $rule_id ]['amount_gift_product_allowed'];

						foreach ( $products_to_add as $product_to_add ) {

							$qty          = ! empty( $product_to_add['quantity'] ) ? $product_to_add['quantity'] : 1;
							$product_id   = isset( $product_to_add['product_id'] ) ? $product_to_add['product_id'] : false;
							$variation_id = ! empty( $product_to_add['variation_id'] ) && $product_to_add['variation_id'] > 0 ? $product_to_add['variation_id'] : 0;
							$variations   = ! empty( $product_to_add['variations'] ) ? $product_to_add['variations'] : array();

							$product = wc_get_product( $product_id );
							if ( 'variation' === $product->get_type() && 0 === $variation_id ) {
								$product_id   = $product->get_parent_id();
								$variation_id = $product->get_id();
								$variations   = $product->get_attributes();
							}
							if ( $product_id && $total_items_in_cart + $qty <= $allowed_items ) {

								$product = wc_get_product( $product_id );

								if ( $variation_id > 0 && count( $variations ) > 0 ) {

									$product = wc_get_product( $variation_id );
								}
								$total_items_in_cart += $qty;

									$res = WC()->cart->add_to_cart(
										$product_id,
										$qty,
										$variation_id,
										$variations,
										array(
											'ywdpd_is_gift_product' => true,
											'ywdpd_rule_id' => $rule_id,
											'ywdpd_time' => time(),
										)
									);

								/* translators: name of product */
								wc_add_notice( sprintf( __( 'Gift %s added properly', 'ywdpd' ), $product->get_formatted_name() ) );
							}
						}
					}
				}
			}
		}


		/**
		 * Change gift product price.
		 *
		 * @param WC_Product $cart_item_data Cart item data.
		 * @param string     $cart_item_key Cart item key.
		 *
		 * @return mixed
		 */
		public function change_price_gift_product( $cart_item_data, $cart_item_key ) {

			if ( isset( $cart_item_data['ywdpd_is_gift_product'] ) ) {

				$cart_item_data['data']->set_price( 0 );
				// $cart_item_data['data']->set_sold_individually( true );
				$cart_item_data['data']->update_meta_data( 'has_dynamic_price', true );

			}

			return $cart_item_data;
		}


		/**
		 * Update gift product.
		 *
		 * @param bool $updated Bool.
		 *
		 * @return mixed
		 */
		public function update_gift_products( $updated ) {

			$rule_to_remove = array();
			foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {

				$rule_id = isset( $cart_item['ywdpd_rule_id'] ) ? $cart_item['ywdpd_rule_id'] : false;

				if ( $rule_id && ! in_array( $rule_id, $rule_to_remove ) ) {

					$rule = isset( $this->gift_rules_to_apply[ $rule_id ] ) ? $this->gift_rules_to_apply[ $rule_id ] : false;

					if ( ! $rule || ! $this->check_valid_single_rule( $rule ) ) {
						$rule_to_remove[] = $rule_id;
						$this->remove_gift_product( $rule_id );
					}
				}
			}

			return $updated;
		}

		/**
		 * Update gift product before process the discount.
		 */
		public function update_gift_products_before_cart_process_discount() {

			$posted = $_REQUEST;

			if ( ! empty( $posted['remove_item'] ) ) {

				$cart_item_key = $posted['remove_item'];

				$cart_item = WC()->cart->get_cart_item( $cart_item_key );

				$is_gift_product = isset( $cart_item['ywdpd_is_gift_product'] );

				if ( ! $is_gift_product ) {

					foreach ( $this->gift_rules as $key => $rule ) {

						if ( $this->apply_to_is_valid( $rule, $cart_item ) ) {

							$this->remove_gift_product( $key );
						}
					}
				}
			}
		}

		/**
		 * Check if a rule is valid
		 *
		 * @param array $rule Rule.
		 *
		 * @return bool
		 * @author YITH
		 * @since 1.6.0
		 */
		public function check_valid_single_rule( $rule ) {

			$products_in_cart = $this->get_cart_products();

			$valid = false;
			foreach ( $products_in_cart as $cart_item_key => $cart_item ) {

				if ( $this->apply_to_is_valid( $rule, $cart_item ) ) {
					return true;
				}
			}

			return $valid;
		}


		/**
		 * Check if is possibles show  notes on the product
		 *
		 * @param boolean    $show Boolean.
		 * @param array      $rule Rule.
		 * @param WC_Product $product Product.
		 *
		 * @return boolean
		 * @author YITH
		 * @since 1.6.0
		 */
		public function show_notes_on_product( $show, $rule, $product ) {
			add_filter( 'ywcdp_product_is_on_sale', '__return_false' );
			if ( 'gift_products' == $rule['discount_mode'] && YITH_WC_Dynamic_Pricing_Helper()->valid_product_to_apply( $rule, $product ) ) {
				$show = true;
			}
			remove_filter( 'ywcdp_product_is_on_sale', '__return_false' );
			return $show;
		}

		/**
		 * @param bool       $show
		 * @param array      $rule
		 * @param WC_Product $product
		 *
		 * @return bool
		 */
		public function show_notes_on_gift_product( $show, $rule, $product ) {

			if ( 'gift_products' === $rule['discount_mode'] ) {

				$show = in_array( $product->get_id(), $rule['gift_product_selection'] );

			}

			return $show;
		}

		/**
		 * Show also on sale.
		 *
		 * @param boolean $hide Boolean.
		 * @param array   $rule Rule.
		 *
		 * @return boolean
		 * @since 1.6.0
		 * @author YITH
		 */
		public function show_also_on_sale( $hide, $rule ) {
			if ( 'gift_products' == $rule['discount_mode'] ) {
				$hide = false;
			}

			return $hide;
		}

		/**
		 * @param int $num_items
		 *
		 * @return int
		 */
		public function valid_sum_item_quantity_not_gifts( $num_items ) {
			$gift_qty = 0;

			foreach ( WC()->cart->get_cart_contents() as $cart_item_key => $cart_item ) {

				if ( isset( $cart_item['ywdpd_is_gift_product'] ) ) {
					$gift_qty += 1;
				}
			}

			if ( is_array( $num_items ) ) {
				$num_items = array_sum( $num_items );
			}

			return $num_items - $gift_qty;
		}

		/**
		 *
		 */
		public function hide_qty_field_for_gift( $product_quantity, $cart_item_key, $cart_item ) {
			if ( isset( $cart_item['ywdpd_is_gift_product'] ) ) {
				$product_quantity = sprintf( '%1$s <input type="hidden" name="cart[%2$s][qty]" value="%1$s" />', $cart_item['quantity'], $cart_item_key );
			}

			return $product_quantity;
		}
	}

}

/**
 * Unique access to instance of YITH_WC_Dynamic_Pricing_Gift_Product class
 *
 * @return YITH_WC_Dynamic_Pricing_Gift_Product
 */
function YITH_WC_Dynamic_Pricing_Gift_Product() {
	return YITH_WC_Dynamic_Pricing_Gift_Product::get_instance();
}

YITH_WC_Dynamic_Pricing_Gift_Product();
