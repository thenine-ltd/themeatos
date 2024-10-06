<?php
/**
 * Add extra profile fields for users in admin.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH/Membership/Admin
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly

if ( ! class_exists( 'YITH_WCMBS_Admin_Profile_Premium' ) ) {

	/**
	 * YITH_WCMBS_Admin_Profile Class
	 * @deprecated since 2.0.0
	 * @use YITH_WCMBS_Admin_Profile instead
	 */
	class YITH_WCMBS_Admin_Profile_Premium extends YITH_WCMBS_Admin_Profile {

		/**
		 * Hook in tabs.
		 * @deprecated
		 */
		protected function __construct() {
			wc_deprecated_function( 'YITH_WCMBS_Admin_Profile_Premium::__construct', '2.0.0', 'YITH_WCMBS_Admin_Profile::__construct' );
			parent::__construct();
		}
	}
}
