<?php
/**
 * Mamanger Premium Class
 *
 * @deprecated  since 2.0 | Use YITH_WCMBS_Manager instead.
 */

defined( 'ABSPATH' ) || exit;  // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Manager_Premium' ) ) {
	/**
	 * Manager Class
	 *
	 * @class       YITH_WCMBS_Manager_Premium
	 * @since       1.0.0
	 * @deprecated  since 2.0 | Use YITH_WCMBS_Manager instead.
	 * @package     YITH/Membership/Classes
	 */
	class YITH_WCMBS_Manager_Premium extends YITH_WCMBS_Manager {

		/**
		 * Single instance of the class
		 *
		 * @since      1.0.0
		 * @var YITH_WCMBS_Manager_Premium
		 * @deprecated 1.4.0 | use YITH_WCMBS_Manager instead
		 */
		protected static $instance;

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		protected function __construct() {
			wc_deprecated_function( 'YITH_WCMBS_Manager_Premium::__construct', '2.0.0', 'YITH_WCMBS_Manager::__construct' );
			parent::__construct();
		}
	}
}
