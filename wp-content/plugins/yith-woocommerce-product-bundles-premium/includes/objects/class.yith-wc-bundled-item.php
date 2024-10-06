<?php
/**
 * Product Bundle Class
 *
 * @author Yithemes
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
	 * @since 1.0.0
	 * @author Leanza Francesco <leanzafrancesco@gmail.com>
	 */
	class YITH_WC_Bundled_Item {

		public $item_id;

		public $product_id;
		public $product;

		private $quantity;

		/**
		 * @var WC_Product_Yith_Bundle
		 */
		public $parent;

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
			$this->parent = $parent;

			if ( false === $item_data ) {
				$item_data = $parent->bundle_data[ $item_id ];
            }
            
			$this->item_id    = $item_id;
			$this->product_id = $item_data['product_id'];
			$this->product_id = YITH_WCPB()->compatibility->wpml->wpml_object_id( $this->product_id, 'product', true );

			$this->quantity = isset( $item_data['bp_quantity'] ) ? $item_data['bp_quantity'] : 1;

			$bundled_product = wc_get_product( $this->product_id );

			// if exist the product with $this->product_id
			if ( $bundled_product ) {
				$this->product = $bundled_product;
			}
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
		 * @return  WC_Product
		 */
		public function get_product() {
			return ! empty( $this->product ) ? $this->product : false;
		}

		/**
		 * return the product id
		 *
		 * @return int
		 */
		public function get_product_id() {
			return $this->product_id;
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
		 * Retrieve the parent bundle product
		 *
		 * @return WC_Product_YITH_Bundle
		 * @since 1.4.0
		 */
		public function get_bundle() {
			return $this->parent;
		}
	}
}
