<?php
/**
 * Frontend class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership
 * @version 1.1.1
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly

if ( ! class_exists( 'YITH_WCMBS_Frontend_Premium' ) ) {
	/**
	 * Frontend class.
	 * The class manage all the Frontend behaviors.
	 *
	 * @since      1.0.0
	 * @deprecated 1.4.0 | use YITH_WCMBS_Frontend instead
	 */
	class YITH_WCMBS_Frontend_Premium extends YITH_WCMBS_Frontend {

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
			parent::__construct();
		}
	}
}
