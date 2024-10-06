<?php
/**
 * Orders Manager premium class
 *
 * @author     YITH <plugins@yithemes.com>
 * @package    YITH\Membership\Classes
 * @class      YITH_WCMBS_Orders_Premium
 * @deprecated since 2.0.0
 * @see        YITH_WCMBS_Orders instead.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Orders_Premium' ) ) {
	/**
	 * Orders Class
	 *
	 * @class      YITH_WCMBS_Orders_Premium
	 * @since      1.2.6
	 * @package    Yithemes
	 * @deprecated since 2.0.0
	 */
	class YITH_WCMBS_Orders_Premium extends YITH_WCMBS_Orders {
		/**
		 * Constructor
		 *
		 * @access public
		 */
		protected function __construct() {
			wc_deprecated_function( 'YITH_WCMBS_Orders_Premium::__construct', '2.0.0', 'YITH_WCMBS_Orders::__construct' );
			parent::__construct();
		}
	}
}
