<?php
/**
 * Product Bundle Class
 *
 * @author  Yithemes
 * @package YITH WooCommerce Product Bundles
 * @version 1.0.0
 */


if ( ! defined( 'YITH_WCPB' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'YITH_WC_Bundled_Item' ) ) {
	/**
	 * Product Bundle Item Object
	 *
	 * @since  1.0.0
	 * @author Leanza Francesco <leanzafrancesco@gmail.com>
	 */
	class YITH_WC_Bundled_Item {

		public $item_id;

		/**
		 * product id of a item
		 *
		 * @var int
		 * @since 1.0.0
		 */
		public $product_id;

		/**
		 * product object of a item
		 *
		 * @var WC_Product|false
		 * @since 1.0.0
		 */
		public $product = false;

		/**
		 * @var WC_Product_Yith_Bundle
		 */
		public $parent;

		private $quantity;

		public $hide_thumbnail;
		public $hidden;
		public $min_quantity;
		public $max_quantity;
		public $show_name;
		public $product_title       = null;
		public $title;
		public $show_description;
		public $description;
		public $product_description = null;
		public $optional;
		public $apply_discount;
		public $discount;

		public $product_attributes;
		public $product_variations;
		public $selected_product_attributes;
		public $selection_overrides;

		public $min_price;
		public $max_price;

		public $filtered_variations;

		public $wpml_translated = false;


		/**
		 * __construct
		 *
		 * @access public
		 *
		 * @param WC_Product_Yith_Bundle $parent
		 * @param int                    $item_id
		 * @param array|bool             $item_data
		 */
		public function __construct( $parent, $item_id, $item_data = false ) {
			do_action( 'yith_wcpb_before_bundled_item_construct', $parent, $item_id );
			$this->parent = $parent;

			if ( false === $item_data ) {
				$item_data = $parent->bundle_data[ $item_id ];
			}

			$this->item_id    = $item_id;
			$this->product_id = $item_data['product_id'];

			$this->quantity            = isset( $item_data['bp_quantity'] ) ? $item_data['bp_quantity'] : 1;
			$this->quantity            = isset( $item_data['bp_min_qty'] ) ? $item_data['bp_min_qty'] : $this->quantity;
			$this->hide_thumbnail      = isset( $item_data['bp_hide_bundled_thumbs'] ) && in_array( $item_data['bp_hide_bundled_thumbs'], array( 'on', 'yes' ), true );
			$this->hidden              = isset( $item_data['bp_hide_item'] ) && in_array( $item_data['bp_hide_item'], array( 'on', 'yes' ), true );
			$this->min_quantity        = isset( $item_data['bp_min_qty'] ) ? $item_data['bp_min_qty'] : $this->quantity;
			$this->max_quantity        = isset( $item_data['bp_max_qty'] ) ? $item_data['bp_max_qty'] : $this->quantity;
			$this->optional            = isset( $item_data['bp_optional'] ) && in_array( $item_data['bp_optional'], array( 'on', 'yes' ), true );
			$this->apply_discount      = isset( $item_data['bp_apply_discount'] ) && $item_data['bp_apply_discount'] === 'yes';
			$this->discount            = isset( $item_data['bp_discount'] ) ? floatval( $item_data['bp_discount'] ) : 0;
			$this->filtered_variations = isset( $item_data['bp_filtered_variations'] ) ? $item_data['bp_filtered_variations'] : array();
			$this->selection_overrides = isset( $item_data['bp_selection_overrides'] ) ? $item_data['bp_selection_overrides'] : array();

			$bundled_product = wc_get_product( $this->product_id );
			if ( ! $bundled_product || $bundled_product->is_type( 'yith_bundle' ) ) {
				return;
			}

			$this->filtered_variations = array_map( array( YITH_WCPB()->compatibility->wpml, 'get_wpml_product_id_current_language' ), $this->filtered_variations );

			$this->product             = $bundled_product;
			$this->product_title       = $bundled_product->get_name();
			$this->product_description = $bundled_product->get_short_description();
			$this->title               = isset( $item_data['bp_title'] ) ? $item_data['bp_title'] : '';
			$this->description         = isset( $item_data['bp_description'] ) ? $item_data['bp_description'] : '';
			$this->show_name           = isset( $item_data['bp_show_name'] ) ? $item_data['bp_show_name'] : 'product';
			$this->show_description    = isset( $item_data['bp_show_description'] ) ? $item_data['bp_show_description'] : 'product';

			/* ==== WPML start ==== */
			$parent_wpml_id = $this->get_wpml_product_id_current_language();
			if ( $this->product_id != $parent_wpml_id ) {
				$this->wpml_translated     = true;
				$this->product_title       = get_the_title( $parent_wpml_id );
				$this->product_description = get_the_excerpt( $parent_wpml_id );
			}
			/* ==== WPML end ==== */

			list ( $min_price, $max_price ) = $this->get_min_max_prices();
			$this->min_price = $min_price;
			$this->max_price = $max_price;

			$this->legacy_init_before_1_4_0();

			do_action( 'yith_wcpb_after_bundled_item_construct', $this );
		}

		public function get_min_max_prices() {
			if ( ! $this->product->is_type( 'variable' ) ) {
				// SIMPLE
				$min_price = $this->product->get_regular_price();
				$max_price = $this->product->get_regular_price();
			} else {
				// VARIABLE
				if ( $this->filtered_variations ) {
					$regular_prices = array();
					foreach ( $this->filtered_variations as $id ) {
						if ( $_variation = wc_get_product( $id ) ) {
							$regular_prices[] = $_variation->get_regular_price();
						}
					}
					asort( $regular_prices );
				} else {
					$prices         = $this->product->get_variation_prices( true );
					$regular_prices = $prices['regular_price'];
				}

				$min_price = current( $regular_prices );
				$max_price = end( $regular_prices );
			}

			return array( $min_price, $max_price );
		}

		/**
		 * return the product id
		 *
		 * @return int
		 * @since 1.2.8
		 */
		public function get_product_id() {
			return $this->product_id;
		}

		/**
		 * return true if the bundled item is hidden
		 *
		 * @return bool
		 * @since 1.2.8
		 */
		public function is_hidden() {
			return ! ! apply_filters( 'yith_wcpb_bundled_item_is_hidden', $this->hidden, $this );
		}

		public function get_wpml_product_id_current_language() {
			global $sitepress;
			$id = $this->product_id;
			if ( isset( $sitepress ) ) {
				if ( function_exists( 'icl_object_id' ) ) {
					$id = icl_object_id( $id, 'product', true );
				} elseif ( function_exists( 'wpml_object_id_filter' ) ) {
					$id = wpml_object_id_filter( $id, 'product', true );
				}
			}

			return $id;
		}

		/**
		 * Return true if this->product is setted
		 *
		 * @return  boolean
		 */
		public function exists() {
			return ! empty( $this->product );
		}

		/**
		 * Return this->product [or false if it not exist]
		 *
		 * @return  WC_Product|false
		 */
		public function get_product() {
			return ! empty( $this->product ) ? $this->product : false;
		}

		/**
		 * Return this->quantity [or 0 if it's not setted]
		 *
		 * @return  int
		 */
		public function get_quantity() {
			return ! empty( $this->quantity ) ? $this->quantity : 0;
		}

		/**
		 * Return true if min_quantity < max_quantity
		 *
		 * @return  bool
		 */
		public function has_quantity_to_choose() {
			return ( $this->min_quantity < $this->max_quantity ) ? true : false;
		}

		/**
		 * Return true if is optional
		 *
		 * @return  bool
		 */
		public function is_optional() {
			return ! ! apply_filters( 'yith_wcpb_bundled_item_is_optional', $this->optional, $this );

		}

		/**
		 * Return true if is variable
		 *
		 * @return  bool
		 */
		function has_variables() {
			return $this->product->is_type( 'variable' );
		}


		/**
		 * Returns this product's available variations array.
		 *
		 * @param bool $price_zero
		 *
		 * @return array
		 */
		public function get_product_variations( $price_zero = false ) {
			if ( ! empty( $this->product_variations ) ) {
				return $this->product_variations;
			}

			if ( $this->product->is_type( 'variable' ) ) {
				do_action( 'yith_wcpb_get_product_variations_before', $this );
				do_action( 'woocommerce_before_init_bundled_item', $this );
				add_filter( 'woocommerce_get_children', array( $this, 'bundled_item_children' ), 10, 2 );
				add_filter( 'woocommerce_show_variation_price', '__return_true', 98 );

				if ( $price_zero ) {
					add_filter( 'woocommerce_get_variation_price_html', array( $this, 'price_zero' ), 99, 2 );
					add_filter( 'woocommerce_get_price_html', array( $this, 'price_zero' ), 99, 2 );
				} else {
					add_filter( 'woocommerce_get_variation_price_html', array( $this, 'get_price_html' ), 99, 2 );
					add_filter( 'woocommerce_get_price_html', array( $this, 'get_price_html' ), 99, 2 );
				}

				$parent_wpml_id = $this->get_wpml_product_id_current_language();
				if ( $this->product_id != $parent_wpml_id ) {
					$wpml_parent_product     = wc_get_product( $parent_wpml_id );
					$bundled_item_variations = $wpml_parent_product->get_available_variations();
				} else {
					$bundled_item_variations = $this->product->get_available_variations();
				}
				if ( $price_zero ) {
					remove_filter( 'woocommerce_get_variation_price_html', array( $this, 'price_zero' ), 99 );
					remove_filter( 'woocommerce_get_price_html', array( $this, 'price_zero' ), 99 );
				} else {
					remove_filter( 'woocommerce_get_variation_price_html', array( $this, 'get_price_html' ), 99 );
					remove_filter( 'woocommerce_get_price_html', array( $this, 'get_price_html' ), 99 );
				}

				remove_filter( 'woocommerce_show_variation_price', '__return_true', 98 );
				remove_filter( 'woocommerce_get_children', array( $this, 'bundled_item_children' ), 10 );

				// add only active variations
				foreach ( $bundled_item_variations as $variation_data ) {
					if ( ! empty( $variation_data ) ) {
						$show_price                                   = ! $this->parent->per_items_pricing || $this->discount > 0;
						$variation_data['display_regular_price_html'] = $show_price ? wc_price( $variation_data['display_regular_price'] ) : '';
						$this->product_variations[]                   = $variation_data;
					}
				}

				do_action( 'yith_wcpb_get_product_variations_after', $this );

				return $this->product_variations;
			}

			return false;
		}

		public function price_zero( $price_html, $product ) {
			return '';
		}

		public function bundled_item_children( $children, $bundled_product ) {
			if ( empty( $this->filtered_variations ) || ! is_array( $this->filtered_variations ) ) {
				return $children;
			} else {
				$filtered_children = array();

				foreach ( $children as $variation_id ) {
					// Remove if filtered
					if ( in_array( $variation_id, $this->filtered_variations ) ) {
						$filtered_children[] = $variation_id;
					}
				}

				return $filtered_children;
			}
		}

		/**
		 * @param string     $price_html
		 * @param WC_Product $product
		 *
		 * @return string
		 */
		public function get_price_html( $price_html, $product ) {
			if ( ! $product->is_type( 'variation' ) ) {
				return $price_html;
			}

			$regular_price = $product->get_regular_price();
			$discount      = apply_filters( 'yith_wcpb_bundled_item_calculated_discount', $regular_price * $this->discount / 100, $this->discount, $regular_price, $product->get_id(), array() );
			$price         = $regular_price - $discount;
			$price         = yith_wcpb_get_price_to_display( $product, $price );
			$price         = yith_wcpb_round_bundled_item_price( $price );

			return apply_filters( 'yith_wcpb_bundled_item_get_price_html', wc_price( $price ), $price, $product );
		}

		/**
		 * Returns the variation attributes array if this product is variable.
		 *
		 * @return array
		 */
		public function get_product_variation_attributes() {

			if ( ! empty( $this->product_attributes ) ) {
				return $this->product_attributes;
			}

			if ( $this->product->is_type( 'variable' ) ) {
				$parent_wpml_id = $this->get_wpml_product_id_current_language();
				if ( $this->product_id != $parent_wpml_id ) {
					$wpml_parent_product      = wc_get_product( $parent_wpml_id );
					$this->product_attributes = $wpml_parent_product->get_variation_attributes();
				} else {
					$this->product_attributes = $this->product->get_variation_attributes();
				}

				return $this->product_attributes;
			}

			return false;

		}

		function get_selected_product_variation_attributes() {
			if ( ! empty( $this->selected_product_attributes ) ) {
				return $this->selected_product_attributes;
			}

			if ( $this->product->is_type( 'variable' ) ) {
				$selected_product_attributes = array();
				if ( ! empty( $this->selection_overrides ) ) {
					$selected_product_attributes = $this->selection_overrides;
				} else {
					$selected_product_attributes = (array) maybe_unserialize( yit_get_prop( $this->product, '_default_attributes', true, 'edit' ) );
				}

				$this->selected_product_attributes = apply_filters( 'woocommerce_product_default_attributes', $selected_product_attributes, $this->product );

				return $this->selected_product_attributes;
			}

			return false;
		}

		/**
		 * Retrieve the parent bundle product
		 *
		 * @return WC_Product_YITH_Bundle
		 * @since 1.4.0
		 */
		public function get_bundle() {
			return $this->parent;
		}

		/**
		 * Retrieve the name to be shown based on settings
		 *
		 * @return string
		 * @since 1.4.0
		 */
		public function get_name_to_be_shown() {
			switch ( $this->show_name ) {
				case 'product':
					$name = ! is_null( $this->product_title ) ? $this->product_title : $this->title;
					break;
				case 'hide':
					$name = '';
					break;
				case 'custom':
				default:
					$name = $this->title;
					$name = call_user_func( '__', $name, 'yith-woocommerce-product-bundles' );
					break;
			}

			return apply_filters( 'yith_wcpb_bundled_item_get_name_to_be_shown', $name, $this );
		}

		/**
		 * Retrieve the description to be shown based on settings
		 *
		 * @return string
		 * @since 1.4.0
		 */
		public function get_description_to_be_shown() {
			switch ( $this->show_description ) {
				case 'product':
					$description = ! is_null( $this->product_description ) ? $this->product_description : $this->description;
					break;
				case 'hide':
					$description = '';
					break;
				case 'custom':
				default:
					$description = $this->description;
					$description = call_user_func( '__', $description, 'yith-woocommerce-product-bundles' );
					break;
			}

			return apply_filters( 'yith_wcpb_bundled_item_get_description_to_be_shown', $description, $this );
		}

		/**
		 * Legavy method to init new values for old bundle products
		 *
		 * @return void
		 * @since 1.4.0
		 */
		private function legacy_init_before_1_4_0() {
			$bundle = $this->get_bundle();
			if ( $bundle && $bundle->is_type( 'yith_bundle' ) && version_compare( $bundle->get_bundle_product_version(), '1.4.0', '<' ) ) {
				$this->apply_discount = $this->discount > 0;

				if ( ! empty( $bundle->bundle_data[ $this->item_id ]['bp_title'] ) ) {
					$title = str_replace( ' ', '', $bundle->bundle_data[ $this->item_id ]['bp_title'] );
					if ( ! $title ) {
						$this->show_name = 'hide';
					} else {
						$this->show_name = 'custom';
					}
				}

				if ( ! empty( $bundle->bundle_data[ $this->item_id ]['bp_description'] ) ) {
					$description = str_replace( ' ', '', $bundle->bundle_data[ $this->item_id ]['bp_description'] );
					if ( ! $description ) {
						$this->show_description = 'hide';
					} else {
						$this->show_description = 'custom';
					}
				}
			}
		}

	}
}

