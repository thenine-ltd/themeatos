<?php
/**
 * Members Premium Class
 *
 * @author      YITH <plugins@yithemes.com>
 * @package     YITH\Membership\Classes
 * @class       YITH_WCMBS_Members_Premium
 *
 * @deprecated  since 2.0 | use YITH_WCMBS_Frontend instead
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly

if ( ! class_exists( 'YITH_WCMBS_Members_Premium' ) ) {
	/**
	 * Members Class
	 *
	 * @class       YITH_WCMBS_Members_Premium
	 *
	 * @since       1.0.0
	 * @deprecated  since 2.0 | Use YITH_WCMBS_Members instead
	 */
	class YITH_WCMBS_Members_Premium extends YITH_WCMBS_Members {

		/**
		 * Single instance of the class
		 *
		 * @var YITH_WCMBS_Frontend_Premium
		 */
		protected static $instance;

		/**
		 * Constructor
		 */
		protected function __construct() {
			wc_deprecated_function( 'YITH_WCMBS_Members_Premium::__construct', '2.0.0', 'YITH_WCMBS_Members::__construct' );
			parent::__construct();
		}
	}
}
