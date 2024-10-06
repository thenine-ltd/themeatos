<?php
/**
 * The Member Premium Class
 *
 * @class   YITH_WCMBS_Member_Premium
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership
 * @since   1.0.0
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Member_Premium' ) ) {
	/**
	 * Member Class
	 *
	 * @class   YITH_WCMBS_Member_Premium
	 */
	class YITH_WCMBS_Member_Premium extends YITH_WCMBS_Member {

		/**
		 * Constructor
		 *
		 * @param int $user_id the id of the user
		 *
		 * @access public
		 * @return mixed array|bool
		 * @since  1.0.0
		 */
		public function __construct( $user_id ) {
			parent::__construct( $user_id );
		}
	}
}
