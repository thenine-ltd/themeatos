<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YITH Pre-Order for WooCommerce Compatibility Class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Customize My Account Page
 * @version 1.0.0
 */

defined( 'YITH_WCMAP' ) || exit;

if ( ! class_exists( 'YITH_WCMAP_Pre_Order_Compatibility' ) ) {
	/**
	 * Class YITH_WCMAP_Pre_Order_Compatibility
	 *
	 * @since 3.0.0
	 */
	class YITH_WCMAP_Pre_Order_Compatibility extends YITH_WCMAP_Compatibility {

		/**
		 * Constructor
		 *
		 * @since 3.0.0
		 */
		public function __construct() {
			$this->endpoint_key = 'pre-orders';
			$this->endpoint     = array(
				'slug'    => 'my-pre-orders',
				'label'   => __( 'My pre-orders', 'yith-woocommerce-customize-myaccount-page' ),
				'icon'    => 'clock-o',
				'content' => '[yith_wcpo_my_pre_orders]',
			);

			// Register endpoint.
			$this->register_endpoint();
		}
	}
}
