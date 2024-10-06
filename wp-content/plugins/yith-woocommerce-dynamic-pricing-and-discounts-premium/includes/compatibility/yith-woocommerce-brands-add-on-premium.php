<?php
/**
 * Compatibility with YITH WooCommerce Brands Add-on Premium
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
 * YWDPD_Brands class to add compatibility with YITH WooCommerce Brands Add-on Premium
 *
 * @class   YWDPD_Brands
 * @package YITH WooCommerce Dynamic Pricing and Discounts
 * @since   1.1.7
 * @author  YITH
 */
if ( ! class_exists( 'YWDPD_Brands' ) ) {

	/**
	 * Class YWDPD_Brands
	 */
	class YWDPD_Brands {

		/**
		 * Single instance of the class
		 *
		 * @var YWDPD_Brands
		 */
		protected static $instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return YWDPD_Brands
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
		 * @since  1.1.7
		 * @author Emanuela Castorina
		 */
		public function __construct() {

			//PRICE RULES
			add_filter( 'yit_ywdpd_pricing_rules_options', array( $this, 'add_pricing_rule_option_rule_for' ) );
			add_filter( 'ywdpd_brand_options_rule_for', array( $this, 'add_pricing_rule_option' ) );
			add_filter( 'ywdpd_brand_options_exclude_rule_for', array( $this, 'add_pricing_rule_option' ) );
			add_filter( 'ywdpd_brand_apply_adjustment_rule_for', array( $this, 'add_pricing_rule_option' ) );
			add_filter( 'ywdpd_brand_apply_exclude_adjustment_rule_for', array( $this, 'add_pricing_rule_option' ) );

			//Valid price rule fields
			add_filter( 'ywdpd_validate_apply_to_field', array( $this, 'check_is_field_is_valid' ), 20, 3 );
			add_filter( 'ywdpd_validate_apply_to_field_excluded', array( $this, 'check_is_field_is_valid' ), 20, 3 );
			add_filter( 'ywdpd_validate_apply_adjustment_to_field', array( $this, 'check_is_field_is_valid' ), 20, 3 );
			add_filter( 'ywdpd_validate_apply_adjustment_to_field_excluded', array(
				$this,
				'check_is_field_is_valid'
			), 20, 3 );
			add_filter( 'ywdpd_is_valid_product_to_apply', array( $this, 'is_valid_brand_to_apply' ), 20, 6 );
			add_filter( 'ywdpd_is_valid_product_apply_adjustment_to', array(
				$this,
				'is_valid_brand_apply_adjustment_to'
			), 20, 3 );

			add_filter( 'ywdpd_validate_apply_to', array( $this, 'is_validate_apply_to_cart_item' ), 10, 6 );

			add_filter( 'ywdpd_get_cumulative_quantity', array( $this, 'brands_comulative_quantity' ), 20, 3 );

			//CART RULES
			add_filter( 'ywdpd_cart_rules_product_include_fields', array( $this, 'add_cart_rule_option' ) );
			add_filter( 'ywdpd_cart_rules_product_exclude_fields', array( $this, 'add_cart_rule_option' ) );
			add_filter( 'ywdpd_cart_rules_product_disable_fields', array( $this, 'add_cart_rule_option' ) );

			add_filter( 'ywdpd_valid_product_cart_condition', array( $this, 'valid_product_cart_condition' ), 20, 3 );
			add_filter( 'ywdpd_valid_product_exclude_cart_condition', array(
				$this,
				'valid_product_exclude_cart_condition'
			), 20, 3 );

			//SPECIAL OFFER IN POPUP
			add_filter( 'ywdpd_special_offer_item_type', array( $this, 'add_brands_in_item_type' ), 20, 3 );
			add_filter( 'ywdpd_get_product_taxonomy_ids_to_include', array( $this, 'add_brands_ids_in_item' ), 20, 3 );
			add_filter( 'ywdpd_item_popup_args', array( $this, 'add_brands_args_in_item' ), 20, 2 );
			add_filter( 'ywdpd_get_product_ids_to_exclude', array( $this, 'return_product_ids_to_exclude' ), 20, 3 );

		}

		/**
		 * check if the brand field is set righ
		 *
		 * @param bool $validate
		 * @param string $type
		 * @param array $rule
		 *
		 * @return bool
		 * @author YITH
		 * @since 2.0.0
		 */
		public function check_is_field_is_valid( $validate, $type, $rule ) {

			$brand_list_key          = 'ywdpd_validate_apply_to_field' === current_filter() ? 'apply_to_brands_list' : 'apply_adjustment_brands_list';
			$brand_list_key_excluded = 'ywdpd_validate_apply_to_field_excluded' === current_filter() ? 'apply_to_brands_list_excluded' : 'apply_adjustment_brands_list_excluded';

			if ( 'specific_brands' === $type ) {
				$validate = ! empty( $rule[ $brand_list_key ] );
			} elseif ( 'brand_list_excluded' === $type ) {
				$validate = ! empty( $rule[ $brand_list_key_excluded ] );
			}

			return $validate;
		}

		/**
		 * @param bool $is_valid
		 * @param array $rule
		 * @param WC_Product $product
		 * @param bool $other_variation ,
		 * @param bool $is_in_exclusion
		 *
		 * @return bool
		 */
		public function is_valid_brand_to_apply( $is_valid, $rule, $product, $other_variation, $is_in_exclusion ) {
			if ( ! $is_in_exclusion ) {
				$exclude_enabled = isset( $rule['active_exclude'] ) && yith_plugin_fw_is_true( $rule['active_exclude'] );

				$product_id = $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id();
				if ( $exclude_enabled ) {

					$exclude_type = $rule['exclude_rule_for'];

					if ( 'brand_list_excluded' === $exclude_type ) {

						$is_in_exclusion = ! $this->is_validate_apply_to( false, 'brand_list_excluded', $product_id, $rule, false );


						if ( $is_in_exclusion ) {
							$is_valid = false; // the rule isn't valid
						}
					}
				}

				if ( ! $is_in_exclusion ) {

					if ( 'specific_brands' === $rule['rule_for'] ) {
						$is_valid = $this->is_validate_apply_to( false, 'brand_list', $product_id, $rule, false );
					}
				}
			}

			return $is_valid;
		}

		/**
		 * @param bool $is_valid
		 * @param array $rule
		 * @param WC_Product $product
		 *
		 * @return bool
		 */
		public function is_valid_brand_apply_adjustment_to( $is_valid, $rule, $product ) {

			$is_excluded     = isset( $rule['active_apply_adjustment_to_exclude'] ) && yith_plugin_fw_is_true( $rule['active_apply_adjustment_to_exclude'] );
			$is_in_exclusion = false;
			$product_id      = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();

			if ( $is_excluded ) {
				$excluded_type = $rule['exclude_apply_adjustment_rule_for'];

				if ( 'brand_list_excluded' === $excluded_type ) {
					$is_in_exclusion = ! $this->valid_product_to_adjust( false, 'brand_list_excluded', $product_id, $rule, false );

					if ( $is_in_exclusion ) {
						$is_valid = false; // the rule isn't valid
					}

				}
			}

			if ( ! $is_in_exclusion ) {

				if ( isset( $rule['rule_apply_adjustment_discount_for'] ) && 'specific_brands' === $rule['rule_apply_adjustment_discount_for'] ) {
					$is_valid = $this->valid_product_to_adjust( false, 'brand_list', $product_id, $rule, false );

				}
			}


			return $is_valid;
		}


		/**
		 * @param $is_valid
		 * @param $apply_to
		 * @param $cart_item_product_id
		 * @param $rule
		 * @param $cart_item
		 *
		 * @return bool
		 */
		public function is_validate_apply_to( $is_valid, $apply_to, $cart_item_product_id, $rule, $cart_item ) {


			if ( $apply_to == 'brand_list' ) {
				$is_valid = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy( $rule['apply_to_brands_list'], $cart_item_product_id, YITH_WCBR::$brands_taxonomy );
			} elseif ( $apply_to == 'brand_list_excluded' ) {
				$is_valid = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy( $rule['apply_to_brands_list_excluded'], $cart_item_product_id, YITH_WCBR::$brands_taxonomy, false );
			}

			return $is_valid;
		}

		/**
		 * @param $is_valid
		 * @param $apply_to
		 * @param $product_id
		 * @param $rule
		 * @param $cart_item
		 * @param $is_in_exclusion
		 *
		 * @return bool
		 */
		public function is_validate_apply_to_cart_item( $is_valid, $apply_to, $product_id, $rule, $cart_item, $is_in_exclusion ) {

			$product  = wc_get_product( $product_id );
			$is_valid = $this->is_valid_brand_to_apply( $is_valid, $rule, $product, false, $is_in_exclusion );

			return $is_valid;
		}

		/**
		 * @param $is_valid
		 * @param $apply_adjustment
		 * @param $cart_item_product_id
		 * @param $rule
		 * @param $cart_item
		 *
		 * @return bool
		 */
		public function valid_product_to_adjust( $is_valid, $apply_adjustment, $cart_item_product_id, $rule, $cart_item ) {

			if ( $apply_adjustment === 'brand_list' ) {
				$is_valid = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy( $rule['apply_adjustment_brands_list'], $cart_item_product_id, YITH_WCBR::$brands_taxonomy );
			} elseif ( $apply_adjustment === 'brand_list_excluded' ) {
				$is_valid = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy( $rule['apply_adjustment_brands_list_excluded'], $cart_item_product_id, YITH_WCBR::$brands_taxonomy, false );
			}

			return $is_valid;
		}

		public function brands_comulative_quantity( $quantity, $rule_for, $rule ) {
			$is_excluded = yith_plugin_fw_is_true( $rule['active_exclude'] );

			if ( $is_excluded ) {

				$exclude_type = $rule_for['exclude_rule_for'];

				if ( 'brand_list_excluded' === $exclude_type ) {
					$quantity = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy_quantity( $rule['apply_to_brands_list_excluded'], YITH_WCBR::$brands_taxonomy, false );

				}
			} else {
				$type = $rule['rule_for'];

				if ( 'specific_brands' === $type ) {
					$quantity = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy_quantity( $rule['apply_to_brands_list'], YITH_WCBR::$brands_taxonomy );

				}
			}

			return $quantity;
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
						'id'        => 'enable_require_product_brands',
						'name'      => __( 'Require specific brands in cart', 'ywdpd' ),
						'desc'      => __( 'Enable to require products of specific brands in cart to apply the discount', 'ywdpd' ),
						'default'   => 'no',
						'type'      => 'onoff',
						'class_row' => 'product require_product',
						'class'     => 'ywdpd_enable_require_product_brands'
					),
					array(
						'id'        => 'require_product_brands_list',
						'name'      => __( 'Include a list of brands', 'ywdpd' ),
						'desc'      => __( 'Choose which brands are required in cart to apply the discount', 'ywdpd' ),
						'default'   => '',
						'type'      => 'ajax-terms',
						'data'      => array(
							'taxonomy'    => YITH_WCBR::$brands_taxonomy,
							'placeholder' => __( 'Search for brand', 'ywdpd' ),
						),
						'class_row' => 'product require_product enable_require_product_brands_list',
						'multiple'  => true,
					),
					array(
						'id'        => 'require_product_brand_list_mode',
						'name'      => __( 'Apply the discount if:', 'ywdpd' ),
						'desc'      => __( 'Choose whether to apply the discount when at least one of the specified product brand is in the cart or only when all products are in the cart', 'ywdpd' ),
						'type'      => 'radio',
						'options'   => array(
							'at_least'  => __( 'At least one selected brand is in cart', 'ywdpd' ),
							'all_brand' => __( 'All selected brands are in cart', 'ywdpd' )
						),
						'default'   => 'at_least',
						'class_row' => 'product require_product enable_require_product_brands_list',
						'class'     => 'ywdpd_require_product_tag_list_mode'
					)
				);
				$rules   = array_merge( $rules, $options );
			} elseif ( 'ywdpd_cart_rules_product_exclude_fields' === current_filter() ) {
				$options = array(
					array(
						'id'        => 'enable_exclude_product_brands',
						'name'      => __( 'Exclude specific brands from discount validation', 'ywdpd' ),
						'desc'      => __( 'Enable if you want to exclude specific brands to this cart discount validation', 'ywdpd' ),
						'default'   => 'no',
						'type'      => 'onoff',
						'class_row' => 'product exclude_product',
						'class'     => 'ywdpd_enable_exclude_product_brands'
					),
					array(
						'id'        => 'exclude_product_brands_list',
						'name'      => __( 'Exclude a list of brands', 'ywdpd' ),
						'desc'      => __( 'Choose which brands to exclude from this cart discount validation', 'ywdpd' ),
						'default'   => '',
						'type'      => 'ajax-terms',
						'data'      => array(
							'taxonomy'    => YITH_WCBR::$brands_taxonomy,
							'placeholder' => __( 'Search for brand', 'ywdpd' ),
						),
						'class_row' => 'product exclude_product enable_exclude_product_brands_list',
						'multiple'  => true,
					),
				);
				$rules   = array_merge( $rules, $options );
			} elseif ( 'ywdpd_cart_rules_product_disable_fields' === current_filter() ) {
				$options = array(
					array(
						'id'        => 'enable_disable_product_brands',
						'name'      => __( 'Disable discount when specific brands are in cart', 'ywdpd' ),
						'desc'      => __( 'Enable to disable the discount if users have specific brands in their cart', 'ywdpd' ),
						'default'   => 'no',
						'type'      => 'onoff',
						'class_row' => 'product disable_product',
						'class'     => 'ywdpd_enable_disable_product_brands'
					),
					array(
						'id'        => 'disable_product_brands_list',
						'name'      => __( 'Select a list of brands', 'ywdpd' ),
						'desc'      => __( 'Choose which brands will disable the discount', 'ywdpd' ),
						'default'   => '',
						'type'      => 'ajax-terms',
						'data'      => array(
							'taxonomy'    => YITH_WCBR::$brands_taxonomy,
							'placeholder' => __( 'Search for brand', 'ywdpd' ),
						),
						'class_row' => 'product disable_product enable_disable_product_brands_list',
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
		 * @return mixed
		 */
		public function valid_product_cart_condition( $is_valid, $condition, $conditions ) {
			$type_check = ! empty( $condition['product_type'] ) ? $condition['product_type'] : '';

			if ( $is_valid ) {
				$require_brands = isset( $condition['enable_require_product_brands'] ) ? $condition['enable_require_product_brands'] : false;
				$disable_brand  = isset( $condition['enable_disable_product_brands'] ) ? $condition['enable_disable_product_brands'] : false;

				if ( 'require_product' === $type_check && yith_plugin_fw_is_true( $require_brands ) ) {
					$brand_to_check = isset( $condition['require_product_brands_list'] ) ? $condition['require_product_brands_list'] : array();
					$is_all         = 'at_least' !== $condition['require_product_brand_list_mode'];
					if ( $require_brands && count( $brand_to_check ) > 0 ) {

						$is_valid = YITH_WC_Dynamic_Pricing_Helper()->validate_taxonomy_in_cart( $brand_to_check, YITH_WCBR::$brands_taxonomy, $is_all );

					}
				} elseif ( 'disable_product' === $type_check ) {

					$brands_to_disable = isset( $condition['disable_product_brands_list'] ) ? $condition['disable_product_brands_list'] : array();

					if ( yith_plugin_fw_is_true( $disable_brand ) && count( $brands_to_disable ) > 0 ) {

						$is_valid = YITH_WC_Dynamic_Pricing_Helper()->validate_taxonomy_in_cart( $brands_to_disable, YITH_WCBR::$brands_taxonomy, false, true );

					}
				}
			}

			return $is_valid;
		}

		/**
		 * @param bool $is_excluded
		 * @param array $conditions
		 * @param WC_Product $product
		 *
		 * @return mixed
		 */
		public function valid_product_exclude_cart_condition( $is_excluded, $conditions, $product ) {

			if ( ! $is_excluded ) {
				foreach ( $conditions as $condition ) {

					$type = ! empty( $condition['condition_for'] ) ? $condition['condition_for'] : '';
					if ( 'product' === $type ) {

						$type_check            = isset( $condition['product_type'] ) ? $condition['product_type'] : '';
						$exclude_product_brand = isset( $condition['enable_exclude_product_brands'] ) ? $condition['enable_exclude_product_brands'] : false;

						if ( 'exclude_product' === $type_check && yith_plugin_fw_is_true( $exclude_product_brand ) ) {
							$brands_excluded_list = isset( $condition['exclude_product_brands_list'] ) ? $condition['exclude_product_brands_list'] : array();

							if ( $exclude_product_brand && count( $brands_excluded_list ) > 0 ) {
								$product_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();

								$is_excluded = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy( $brands_excluded_list, $product_id, YITH_WCBR::$brands_taxonomy );
							}
						}
					}
				}
			}

			return $is_excluded;
		}

		/**
		 * @param $quantity
		 * @param $apply_to
		 * @param $rule
		 *
		 * @return mixed
		 */
		public function get_cumulative_quantity( $quantity, $apply_to, $rule ) {

			if ( $apply_to == 'brand_list' ) {
				$quantity = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy_quantity( $rule['apply_to_brands_list'], YITH_WCBR::$brands_taxonomy );
			} elseif ( $apply_to == 'brand_list_excluded' ) {
				$quantity = YITH_WC_Dynamic_Pricing_Helper()->check_taxonomy_quantity( $rule['apply_to_brand_list_excluded'], YITH_WCBR::$brands_taxonomy );
			}

			return $quantity;
		}

		/**
		 * @param $is_valid
		 * @param $type
		 * @param $brand_list
		 *
		 * @return bool
		 */
		public function validate_product_in_cart( $is_valid, $type, $brand_list ) {

			if ( $type == 'brand_list' ) {
				foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
					$brands_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_WCBR::$brands_taxonomy, array( 'fields' => 'ids' ) );
					$intersect      = array_intersect( $brands_of_item, $brand_list );
					if ( ! empty( $intersect ) ) {
						$is_valid = true;
					}
				}
			} elseif ( $type == 'brand_list_and' ) {
				foreach ( $brand_list as $brand_id ) {
					if ( YITH_WC_Dynamic_Pricing_Helper()->find_taxonomy_in_cart( $brand_id, YITH_WCBR::$brands_taxonomy ) != '' ) {
						$is_valid = true;
					} else {
						$is_valid = false;
						break;
					}
				}
			} elseif ( $type == 'brand_list_excluded' ) {
				$is_valid = true;
				foreach ( WC()->cart->cart_contents as $cart_item_key => $cart_item ) {
					$brands_of_item = wc_get_product_terms( $cart_item['product_id'], YITH_WCBR::$brands_taxonomy, array( 'fields' => 'ids' ) );
					$intersect      = array_intersect( $brands_of_item, $brand_list );
					if ( ! empty( $intersect ) ) {
						$is_valid = false;
					}
				}
			}

			return $is_valid;
		}

		/**
		 * Add pricing rules options in settings panels
		 *
		 * @param $rules
		 *
		 * @return array
		 */
		public function add_pricing_rule_option_rule_for( $rules ) {
			$new_rule = array();
			foreach ( $rules as $key => $rule ) {
				$new_rule[ $key ] = $rule;

				if ( $key === 'rule_for' || $key === 'rule_apply_adjustment_discount_for' ) {
					$new_rule[ $key ]['specific_brands'] = __( 'Specific product brands', 'ywdpd' );

				}

				if ( 'exclude_rule_for' === $key || 'exclude_apply_adjustment_rule_for' === $key ) {
					$new_rule[ $key ]['brand_list_excluded'] = __( 'Specific product brands', 'ywdpd' );
				}
			}

			return $new_rule;
		}

		/**
		 * add brand search field in the price rule metabox
		 * @return array
		 * @since 2.0.0
		 * @author YITH
		 */
		public function add_pricing_rule_option() {
			$current_filter = current_filter();
			if ( 'ywdpd_brand_options_rule_for' === $current_filter ) {
				$option = array(
					'type'     => 'ajax-terms',
					'data'     => array(
						'taxonomy'    => YITH_WCBR::$brands_taxonomy,
						'placeholder' => __( 'Search for a brand', 'ywdpd' ),
					),
					'label'    => __( 'Apply rule to:', 'ywdpd' ),
					'desc'     => __( 'Search the brand(s) to include in the rule', 'ywdpd' ),
					'deps'     => array(
						'ids'    => '_rule_for',
						'values' => 'specific_brands',
						'type'   => 'hideNow'
					),
					'multiple' => true
				);
			} elseif ( 'ywdpd_brand_options_exclude_rule_for' === $current_filter ) {
				$option = array(
					'type'     => 'ajax-terms',
					'data'     => array(
						'taxonomy'    => YITH_WCBR::$brands_taxonomy,
						'placeholder' => __( 'Search for a brand', 'ywdpd' ),
					),
					'label'    => __( 'Choose which brand(s) to exclude', 'ywdpd' ),
					'desc'     => __( 'Search the brand(s) to exclude from the rule', 'ywdpd' ),
					'deps'     => array(
						'ids'    => '_active_exclude,_exclude_rule_for',
						'values' => 'yes,brand_list_excluded',
						'type'   => 'hideNow'
					),
					'multiple' => true
				);
			} elseif ( 'ywdpd_brand_apply_adjustment_rule_for' === $current_filter ) {
				$option = array(
					'type'     => 'ajax-terms',
					'data'     => array(
						'taxonomy'    => YITH_WCBR::$brands_taxonomy,
						'placeholder' => __( 'Search for a brand', 'ywdpd' ),
					),
					'label'    => __( 'Choose which brand(s) to include', 'ywdpd' ),
					'desc'     => __( 'Search the brand(s) to include in this discount', 'ywdpd' ),
					'deps'     => array(
						'ids'    => '_active_apply_discount_to,_rule_apply_adjustment_discount_for',
						'values' => 'yes,specific_brands',
						'type'   => 'hideNow'
					),
					'multiple' => true
				);
			} else {
				$option = array(
					'type'     => 'ajax-terms',
					'data'     => array(
						'taxonomy'    => YITH_WCBR::$brands_taxonomy,
						'placeholder' => __( 'Search for a brand', 'ywdpd' ),
					),
					'label'    => __( 'Choose which brand(s) to exclude', 'ywdpd' ),
					'desc'     => __( 'Search the brand(s) to exclude from this discount', 'ywdpd' ),
					'deps'     => array(
						'ids'    => '_active_apply_adjustment_to_exclude,_exclude_apply_adjustment_rule_for',
						'values' => 'yes,brand_list_excluded',
						'type'   => 'hideNow'
					),
					'multiple' => true
				);
			}

			return $option;

		}


		/**
		 * Add localize params to javascript
		 *
		 * @param $params
		 *
		 * @return mixed
		 */
		public function add_localize_params( $params ) {
			$params['search_brand_nonce'] = wp_create_nonce( 'search-brand' );

			return $params;
		}

		/**
		 * Return the list of brands that match with the query digit
		 */
		public function json_search_brands() {

			check_ajax_referer( 'search-products', 'security' );

			ob_start();

			$term = (string) wc_clean( stripslashes( $_GET['term'] ) );

			if ( empty( $term ) ) {
				die();
			}
			global $wpdb;

			$terms = $wpdb->get_results( 'SELECT name, slug, wpt.term_id FROM ' . $wpdb->prefix . 'terms wpt, ' . $wpdb->prefix . 'term_taxonomy wptt WHERE wpt.term_id = wptt.term_id AND wptt.taxonomy = "' . YITH_WCBR::$brands_taxonomy . '" and wpt.name LIKE "%' . $term . '%" ORDER BY name ASC;' );

			$found_brands = array();

			if ( $terms ) {
				foreach ( $terms as $cat ) {
					$found_brands[ $cat->term_id ] = ( $cat->name ) ? $cat->name : 'ID: ' . $cat->slug;
				}
			}

			wp_send_json( $found_brands );
		}

		/**
		 * @param $pricing_options
		 *
		 * @return mixed
		 */
		public function add_brands_pricing_options( $pricing_options ) {

			$start        = $pricing_options['tabs']['settings']['fields'];
			$position     = array_search( 'apply_to_tags_list_excluded', array_keys( $start ) );
			$begin        = array_slice( $start, 0, $position + 1 );
			$end          = array_slice( $start, $position );
			$brands_items = array(
				'apply_to_brands_list'          => array(
					'label'       => __( 'Search for a brand', 'ywdpd' ),
					'type'        => 'brands',
					'desc'        => '',
					'placeholder' => __( 'Search for a brand', 'ywdpd' ),
					'deps'        => array(
						'ids'    => '_apply_to',
						'values' => 'brand_list',
					),
				),
				'apply_to_brands_list_excluded' => array(
					'label'       => __( 'Search for a brand', 'ywdpd' ),
					'type'        => 'brands',
					'desc'        => '',
					'placeholder' => __( 'Search for a branch', 'ywdpd' ),
					'deps'        => array(
						'ids'    => '_apply_to',
						'values' => 'brand_list_excluded',
					),
				),
			);

			$start        = $begin + $brands_items + $end;
			$position     = array_search( 'apply_adjustment_tags_list', array_keys( $start ) );
			$begin        = array_slice( $start, 0, $position + 1 );
			$end          = array_slice( $start, $position );
			$brands_items = array(
				'apply_adjustment_brands_list'          => array(
					'label'       => __( 'Search for a brand', 'ywdpd' ),
					'type'        => 'brands',
					'desc'        => '',
					'placeholder' => __( 'Search for a brand', 'ywdpd' ),
					'deps'        => array(
						'ids'    => '_apply_adjustment',
						'values' => 'brand_list',
					),
				),
				'apply_adjustment_brands_list_excluded' => array(
					'label'       => __( 'Search for a brand', 'ywdpd' ),
					'type'        => 'brands',
					'desc'        => '',
					'placeholder' => __( 'Search for a branch', 'ywdpd' ),
					'deps'        => array(
						'ids'    => '_apply_adjustment',
						'values' => 'brand_list_excluded',
					),
				),
			);

			$pricing_options['tabs']['settings']['fields'] = $begin + $brands_items + $end;

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
		public function add_brands_in_item_type( $type, $rule, $apply_special_offer_to ) {

			if ( 'specific_brands' === $apply_special_offer_to ) {
				$type = 'product_brands';
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
		public function add_brands_ids_in_item( $item_ids, $rule, $apply_special_offer_to ) {
			if ( 'specific_brands' === $apply_special_offer_to ) {
				$item_ids = isset( $rule['apply_adjustment_brands_list'] ) ? $rule['apply_adjustment_brands_list'] : array();
			}

			return $item_ids;
		}

		/**
		 * @param array $args
		 * @param string $type
		 *
		 * @return mixed
		 */
		public function add_brands_args_in_item( $args, $type ) {
			if ( 'product_brands' === $type ) {
				$args = array(
					'item_class'    => 'product_taxonomy',
					'taxonomy_name' => YITH_WCBR::$brands_taxonomy,
				);
			}

			return $args;
		}

		public function return_product_ids_to_exclude( $product_ids, $rule, $exclude_for ) {


			if ( 'brand_list_excluded' === $exclude_for ) {
				$brand_ids = isset( $rule['apply_adjustment_brands_list_excluded'] ) ? $rule['apply_adjustment_brands_list_excluded'] : array();
				if ( is_array( $brand_ids ) && count( $brand_ids ) > 0 ) {
					$tax_query = WC()->query->get_tax_query( array(
							array(
								'taxonomy' => YITH_WCBR::$brands_taxonomy,
								'terms'    => array_values( $brand_ids ),
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
 * Unique access to instance of YWDPD_Brands class
 *
 * @return YWDPD_Brands
 */
function YWDPD_Brands() {
	return YWDPD_Brands::get_instance();
}

YWDPD_Brands();
