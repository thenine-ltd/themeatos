<?php
/**
 * Class YITH_WCMBS_Threads_Post_Type_Admin
 *
 * Handles the Threads post type on admin side.
 *
 * @package    YITH\Membership\PostTypes
 * @author     YITH <plugins@yithemes.com>
 * @deprecated since 2.3.0
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Threads_Post_Type_Admin' ) ) {
	/**
	 * Membership threads post type admin
	 *
	 * @deprecated since 2.3.0
	 */
	class YITH_WCMBS_Threads_Post_Type_Admin extends YITH_Post_Type_Admin {
		/**
		 * The post type.
		 *
		 * @var string
		 */
		protected $post_type = 'yith-wcmbs-thread';

		public function __construct() {
			wc_deprecated_function( 'YITH_WCMBS_Threads_Post_Type_Admin::__construct()', '2.3.0' );
			parent::__construct();
		}

		/**
		 * Return false since I don't want to use the object.
		 *
		 * @return bool
		 */
		protected function use_object() {
			return false;
		}

		/**
		 * Define which columns to show on this screen.
		 *
		 * @param array $columns Existing columns.
		 *
		 * @return array
		 */
		public function define_columns( $columns ) {
			$columns['messages_count'] = '<span class="dashicons dashicons-admin-comments"></span>';
			$columns['last_sender']    = __( 'Last answer by', 'yith-woocommerce-membership' );
			$columns['actions']        = '';

			return $columns;
		}

		/**
		 * Render Actions column
		 */
		protected function render_actions_column() {
			$post = get_post( $this->post_id );
			$user = _draft_or_post_title( $post );

			$options = array(
				'delete-directly'        => true,
				// translators: %s is the name of the user related to the Thread.
				'confirm-delete-message' => sprintf( __( 'Are you sure you want to delete the thread with %s?', 'yith-woocommerce-membership' ), '<strong>' . $user . '</strong>' ) . '<br/><br/>' . __( 'This action cannot be undone and you will not be able to recover this data.', 'yith-woocommerce-membership' ),
			);

			$actions = yith_plugin_fw_get_default_post_actions( $this->post_id, $options );
			yith_plugin_fw_get_action_buttons( $actions, true );
		}

		/**
		 * Retrieve an array of parameters for blank state.
		 *
		 * @return array
		 */
		protected function get_blank_state_params() {
			return array(
				'icon_url' => YITH_WCMBS_ASSETS_URL . '/icons/messages.svg',
				'message'  => __( 'You have no message yet!', 'yith-woocommerce-membership' ) . '<br />' . __( 'Here you will see messages sent by members', 'yith-woocommerce-membership' ),
			);
		}

	}
}

return YITH_WCMBS_Threads_Post_Type_Admin::instance();
