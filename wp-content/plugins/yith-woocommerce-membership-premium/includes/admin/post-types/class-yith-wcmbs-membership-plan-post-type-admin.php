<?php
/**
 * Class YITH_WCMBS_Membership_Plan_Post_Type_Admin
 *
 * Handles the Membership Plan post type on admin side.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership\PostTypes
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Membership_Plan_Post_Type_Admin' ) ) {
	/**
	 * Membership post type admin
	 */
	class YITH_WCMBS_Membership_Plan_Post_Type_Admin extends YITH_Post_Type_Admin {
		/**
		 * The post type.
		 *
		 * @var string
		 */
		protected $post_type = 'yith-wcmbs-plan';

		/**
		 * The classic object.
		 *
		 * @var YITH_WCMBS_Plan
		 */
		protected $object;

		/**
		 * Adjust which columns are displayed by default.
		 *
		 * @param array  $hidden Current hidden columns.
		 * @param object $screen Current screen.
		 *
		 * @return array
		 */
		public function default_hidden_columns( $hidden, $screen ) {
			$hidden[] = 'date';

			return parent::default_hidden_columns( $hidden, $screen );
		}

		/**
		 * Define which columns to show on this screen.
		 *
		 * @param array $columns Existing columns.
		 *
		 * @return array
		 */
		public function define_columns( $columns ) {
			$new_columns = array(
				'shortcode' => __( 'Membership Item Shortcode', 'yith-woocommerce-membership' ),
			);

			if ( isset( $columns['date'] ) ) {
				$new_columns['date'] = $columns['date'];
				unset( $columns['date'] );
			}

			$new_columns['actions'] = '';

			return $columns + $new_columns;
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
				'delete' => _x( 'Delete', 'Membership Plan bulk action', 'yith-woocommerce-membership' ),
			);
		}

		/**
		 * Render Shortcode column
		 */
		public function render_shortcode_column() {
			if ( $this->object ) {
				$field = array(
					'type'  => 'copy-to-clipboard',
					'value' => '[membership_items plan=' . absint( $this->object->get_id() ) . ']',
				);

				yith_plugin_fw_get_field( $field, true );
			}
		}

		/**
		 * Render Actions column
		 */
		protected function render_actions_column() {
			$options = array(
				'delete-directly' => true,
			);

			$actions = yith_plugin_fw_get_default_post_actions( $this->object->get_id(), $options );

			yith_plugin_fw_get_action_buttons( $actions, true );
		}

		/**
		 * Retrieve an array of parameters for blank state.
		 *
		 * @return array
		 */
		protected function get_blank_state_params() {
			return array(
				'icon_url' => YITH_WCMBS_ASSETS_URL . '/icons/plans.svg',
				'message'  => __( 'You have no membership plan yet!', 'yith-woocommerce-membership' ),
				'cta'      => array(
					'title' => __( 'Create a new membership plan', 'yith-woocommerce-membership' ),
					'url'   => add_query_arg( array( 'post_type' => YITH_WCMBS_Post_Types::$plan ), admin_url( 'post-new.php' ) ),
				),
			);
		}

		/**
		 * Has the months dropdown enabled?
		 *
		 * @return bool
		 */
		protected function has_months_dropdown_enabled() {
			return false;
		}

		/**
		 * Pre-fetch any data for the row each column has access to it, by loading $this->object.
		 *
		 * @param int $post_id Post ID being shown.
		 */
		protected function prepare_row_data( $post_id ) {
			if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
				$this->object = yith_wcmbs_get_plan( $post_id );
			}
		}

	}
}

return YITH_WCMBS_Membership_Plan_Post_Type_Admin::instance();
