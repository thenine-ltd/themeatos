<?php
/**
 * Class YITH_WCMBS_Membership_Post_Type_Admin
 *
 * Handles the Membership post type on admin side.
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership\PostTypes
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Membership_Post_Type_Admin' ) ) {
	/**
	 * Membership post type admin
	 */
	class YITH_WCMBS_Membership_Post_Type_Admin extends YITH_Post_Type_Admin {
		/**
		 * The post type.
		 *
		 * @var string
		 */
		protected $post_type = 'ywcmbs-membership';

		/**
		 * The classic object.
		 *
		 * @var YITH_WCMBS_Membership
		 */
		protected $object;

		/**
		 * Define which columns to show on this screen.
		 *
		 * @param array $columns Existing columns.
		 *
		 * @return array
		 */
		public function define_columns( $columns ) {
			$date_column = $columns['date'];
			unset( $columns['cb'] );
			unset( $columns['date'] );
			unset( $columns['title'] );

			$new_columns = array(
				'ID'         => __( 'ID', 'yith-woocommerce-membership' ),
				'status'     => __( 'Status', 'yith-woocommerce-membership' ),
				'user'       => __( 'User', 'yith-woocommerce-membership' ),
				'order'      => __( 'Order', 'yith-woocommerce-membership' ),
				'start_date' => __( 'Starting Date', 'yith-woocommerce-membership' ),
				'end_date'   => __( 'Expiration Date', 'yith-woocommerce-membership' ),
				'date'       => $date_column,
				'actions'    => '',
			);

			return $new_columns + $columns;
		}

		/**
		 * Add sortable columns.
		 *
		 * @param string[] $sortable_columns The list of sortable columns.
		 *
		 * @return string[]
		 */
		public function define_sortable_columns( $sortable_columns ) {
			$sortable_columns['start_date'] = 'start_date';
			$sortable_columns['end_date']   = 'end_date';

			return $sortable_columns;
		}

		/**
		 * Render columns
		 *
		 * @param string $column  The column name.
		 * @param int    $post_id The post ID.
		 */
		public function render_columns( $column, $post_id ) {
			parent::render_columns( $column, $post_id );

			if ( $this->object ) {
				do_action( 'yith_wcmbs_membership_render_custom_columns', $column, $post_id, $this->object );
			}
		}

		/**
		 * Render ID column
		 */
		protected function render_id_column() {
			if ( $this->object ) {
				echo sprintf( '<strong>%s</strong> %s', absint( $this->object->get_id() ), esc_html( $this->object->get_plan_title() ) );
			}
		}

		/**
		 * Render status column
		 */
		protected function render_status_column() {
			if ( $this->object ) {
				echo '<span class="yith-wcmbs-membership-status ' . esc_attr( $this->object->status ) . '">' . esc_html( $this->object->get_status_text() ) . '</span>';
			}
		}

		/**
		 * Render user column
		 */
		protected function render_user_column() {
			if ( $this->object ) {
				$user_id = $this->object->get_user_id();
				$user    = get_user_by( 'id', $user_id );
				if ( $user ) {
					$edit_link = get_edit_user_link( $user_id );
					echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $user->user_login ) . '</a>';
				}
			}
		}

		/**
		 * Render order column
		 */
		protected function render_order_column() {
			if ( $this->object ) {
				$order_id = $this->object->order_id;
				if ( $order_id > 0 ) {
					$order   = wc_get_order( $order_id );
					$user_id = $order ? $order->get_user_id() : false;
					if ( $user_id ) {
						$user_info = get_userdata( $user_id );
					}

					if ( ! empty( $user_info ) ) {
						$username = '<a href="user-edit.php?user_id=' . absint( $user_info->ID ) . '">';

						if ( $user_info->first_name || $user_info->last_name ) {
							$username .= esc_html(
								sprintf(
									_x( '%1$s %2$s', 'full name', 'woocommerce' ),
									ucfirst( $user_info->first_name ),
									ucfirst( $user_info->last_name )
								)
							);
						} else {
							$username .= esc_html( ucfirst( $user_info->display_name ) );
						}

						$username .= '</a>';

						$username = apply_filters(
							'yith_wcmbs_username_anchor_membership_list_table',
							$username,
							$user_info
						);

					} elseif ( $order instanceof WC_Order ) {
						$billing_first_name = $order->get_billing_first_name();
						$billing_last_name  = $order->get_billing_last_name();
						$username           = trim(
							sprintf(
								_x( '%1$s %2$s', 'full name', 'woocommerce' ),
								$billing_first_name,
								$billing_last_name
							)
						);
					} else {
						$username = __( 'Guest', 'woocommerce' );
					};
					if ( $order ) {
						echo wp_kses_post(
							sprintf(
								_x( '%1$s by %2$s', 'Order number by X', 'woocommerce' ),
								'<a href="' . admin_url( 'post.php?post=' . absint( $order_id ) . '&action=edit' ) . '" class="row-title"><strong>#' . esc_attr( $order->get_order_number() ) . '</strong></a>',
								$username
							)
						);
					} else {
						echo wp_kses_post(
							sprintf(
								__( '%s [Order not found]', 'yith-woocommerce-membership' ),
								'<strong>#' . absint( $order_id ) . '</strong>'
							)
						);
					}

					$billing_email = $order ? $order->get_billing_email() : '';
					if ( $billing_email ) {
						echo '&ensp;'; // added space to prevent issues on copy and paste.
						echo '<small class="meta email"><a href="' . esc_url( 'mailto:' . $billing_email ) . '">' . esc_html( $billing_email ) . '</a></small>';
					}
				} else {
					esc_html_e( 'created by Admin', 'yith-woocommerce-membership' );
				}
			}
		}

		/**
		 * Render start date column
		 */
		protected function render_start_date_column() {
			if ( $this->object ) {
				echo esc_html( $this->object->get_formatted_date( 'start_date' ) );
			}
		}

		/**
		 * Render end date column
		 */
		protected function render_end_date_column() {
			if ( $this->object ) {
				echo esc_html( $this->object->get_formatted_date( 'end_date' ) );
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
				'icon_url' => YITH_WCMBS_ASSETS_URL . '/icons/membership.svg',
				'message'  => __( 'You have no membership yet!', 'yith-woocommerce-membership' ),
			);
		}

		/**
		 * Pre-fetch any data for the row each column has access to it, by loading $this->object.
		 *
		 * @param int $post_id Post ID being shown.
		 */
		protected function prepare_row_data( $post_id ) {
			if ( empty( $this->object ) || $this->object->get_id() !== $post_id ) {
				$this->object = yith_wcmbs_get_membership( $post_id );
			}
		}

	}
}

return YITH_WCMBS_Membership_Post_Type_Admin::instance();
