<?php
/**
 * Admin premium class
 *
 * @author     YITH <plugins@yithemes.com>
 * @package    YITH\Membership\Classes
 * @deprecated since 2.0.0
 * @see        YITH_WCMBS_Admin instead.
 */

! defined( 'YITH_WCMBS' ) && exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Admin_Premium' ) ) {
	/**
	 * Admin class.
	 * The class manage all the admin behaviors.
	 *
	 * @deprecated 1.4.0 | use YITH_WCMBS_Admin instead
	 */
	class YITH_WCMBS_Admin_Premium extends YITH_WCMBS_Admin {
		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		protected function __construct() {
			wc_deprecated_function( 'YITH_WCMBS_Admin_Premium::__construct', '2.0.0', 'YITH_WCMBS_Admin::__construct' );
			parent::__construct();
		}
	}
}
