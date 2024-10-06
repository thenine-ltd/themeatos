<?php
/**
 * Class YITH_WCMBS_Alternative_Content_Post_Type_Admin
 *
 * Handles the Alternative Content post type on admin side.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership\PostTypes
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Alternative_Content_Post_Type_Admin' ) ) {
	/**
	 * Alternative Content post type admin
	 */
	class YITH_WCMBS_Alternative_Content_Post_Type_Admin extends YITH_Post_Type_Admin {
		/**
		 * The post type.
		 *
		 * @var string
		 */
		protected $post_type = 'ywcmbs-alt-cont';

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
			$columns['actions'] = '';

			return $columns;
		}

		/**
		 * Define bulk actions.
		 *
		 * @param array $actions Existing actions.
		 *
		 * @return array
		 */
		public function define_bulk_actions( $actions ) {
			return array(
				'delete' => _x( 'Delete', 'Alternative Content bulk action', 'yith-woocommerce-membership' ),
			);
		}

		/**
		 * Render Actions column
		 */
		protected function render_actions_column() {
			$options = array(
				'delete-directly' => true,
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
				'icon_url' => YITH_WCMBS_ASSETS_URL . '/icons/alternative-content.svg',
				'message'  => __( 'You have no alternative content block yet!', 'yith-woocommerce-membership' ),
				'cta'      => array(
					'title' => __( 'Create new alternative content block', 'yith-woocommerce-membership' ),
					'url'   => add_query_arg( array( 'post_type' => $this->post_type ), admin_url( 'post-new.php' ) ),
				),
			);
		}

	}
}

return YITH_WCMBS_Alternative_Content_Post_Type_Admin::instance();
