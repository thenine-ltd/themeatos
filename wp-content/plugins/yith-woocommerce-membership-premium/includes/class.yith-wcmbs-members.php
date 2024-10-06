<?php
/**
 * Members Class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership\Object
 * @class   YITH_WCMBS_Members
 * @since   1.0.0
 */

defined( 'ABSPATH' ) || exit;  // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Members' ) ) {
	/**
	 * Members Class
	 *
	 * @class   YITH_WCMBS_Members
	 *
	 * @since   1.0.0
	 * @package Yithemes
	 */
	class YITH_WCMBS_Members {

		use YITH_WCMBS_Singleton_Trait;

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		protected function __construct() {

		}

		/**
		 * Get a Member obj by user_id
		 *
		 * @param $user_id int the id of the user
		 *
		 * @access public
		 * @return YITH_WCMBS_Member_Premium
		 * @since  1.0.0
		 */
		public function get_member( $user_id ) {
			$member = new YITH_WCMBS_Member( $user_id );

			return $member;
		}
	}
}

if ( ! function_exists( 'yith_wcmbs_members' ) ) {
	/**
	 * Unique access to instance of YITH_WCMBS_Members class
	 *
	 * @return YITH_WCMBS_Members
	 * @since 1.0.0
	 */
	function yith_wcmbs_members() {
		return YITH_WCMBS_Members::get_instance();
	}
}
