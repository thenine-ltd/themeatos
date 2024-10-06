<?php
/**
 * Frontend class for messages
 *
 * @package YITH\Membership\Classes
 * @author  YITH <plugins@yithemes.com>
 * @version 1.0.0
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Messages_Manager_Frontend' ) ) {
	/**
	 * Messages Manager Frontend class.
	 * The class manage all the admin behaviors.
	 *
	 * @since      1.0.0
	 * @deprecated since 2.3.0
	 */
	class YITH_WCMBS_Messages_Manager_Frontend {

		/**
		 * Single instance of the class
		 *
		 * @since 1.0.0
		 * @var \YITH_WCMBS_Messages_Manager_Frontend
		 */
		private static $_instance;

		/**
		 * Returns single instance of the class
		 *
		 * @return \YITH_WCMBS_Messages_Manager_Frontend
		 * @since 1.0.0
		 */
		public static function get_instance() {
			wc_deprecated_function( 'YITH_WCMBS_Messages_Manager_Frontend::get_instance()', '2.3.0' );
			return ! is_null( self::$_instance ) ? self::$_instance : self::$_instance = new self();
		}

		/**
		 * Constructor
		 *
		 * @access public
		 * @since  1.0.0
		 */
		private function __construct() {
			wc_deprecated_function( 'YITH_WCMBS_Messages_Manager_Frontend::__construct()', '2.3.0' );
		}

		/**
		 * Get the count of messages by user id
		 *
		 * @param int $user_id The user ID.
		 *
		 * @return int
		 *
		 * @since       1.0.0
		 */
		public function get_messages_count_by_user_id( $user_id ) {
			wc_deprecated_function( 'YITH_WCMBS_Messages_Manager_Frontend::get_messages_count_by_user_id()', '2.3.0' );
			return 0;
		}

		/**
		 * Get the messages by user id
		 *
		 * @param int $user_id The user ID.
		 * @param int $offset  The offset value for messages.
		 *
		 * @since       1.0.0
		 */
		public function get_messages_by_user_id( $user_id, $offset = 0 ) {
			wc_deprecated_function( 'YITH_WCMBS_Messages_Manager_Frontend::get_messages_by_user_id()', '2.3.0' );
		}

		/**
		 * Get the thread id by user id
		 *
		 * @param int $user_id The user ID.
		 *
		 * @return int|bool
		 *
		 * @since       1.0.0
		 */
		public function get_thread_id( $user_id ) {
			wc_deprecated_function( 'YITH_WCMBS_Messages_Manager_Frontend::get_thread_id()', '2.3.0' );

			return false;
		}

		/**
		 * Send message through AJAX
		 *
		 * @return void
		 */
		public function ajax_user_send_message() {
			wc_deprecated_function( 'YITH_WCMBS_Messages_Manager_Frontend::ajax_user_send_message()', '2.3.0' );
			die();
		}


		/**
		 * Get the older messages [AJAX]
		 *
		 * @since       1.0.0
		 */
		public function ajax_get_older_messages() {
			wc_deprecated_function( 'YITH_WCMBS_Messages_Manager_Frontend::ajax_get_older_messages()', '2.3.0' );
			die();
		}
	}
}

if ( ! function_exists( 'yith_wcmbs_messages_manager_frontend' ) ) {
	/**
	 * Unique access to instance of YITH_WCMBS_Messages_Manager_Frontend class
	 *
	 * @return YITH_WCMBS_Messages_Manager_Frontend
	 * @since 1.0.0
	 */
	function yith_wcmbs_messages_manager_frontend() {
		return YITH_WCMBS_Messages_Manager_Frontend::get_instance();
	}
}
