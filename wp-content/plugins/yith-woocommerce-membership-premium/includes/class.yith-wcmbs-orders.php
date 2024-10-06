<?php
/**
 * Orders Manager class
 *
 * @author  YITH <plugins@yithemes.com>
 * @package YITH\Membership\Classes
 * @class   YITH_WCMBS_Orders
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Orders' ) ) {
	/**
	 * Orders Class
	 *
	 * @class   YITH_WCMBS_Orders
	 * @since   1.2.6
	 */
	class YITH_WCMBS_Orders {

		use YITH_WCMBS_Singleton_Trait;

		/**
		 * Constructor
		 *
		 * @access public
		 */
		protected function __construct() {
			add_action( 'woocommerce_order_status_completed', array( $this, 'set_user_membership' ) );
			add_action( 'woocommerce_order_status_processing', array( $this, 'set_user_membership' ) );

			// Action to manage Paypal and Stripe disputes
			add_action( 'woocommerce_order_status_changed', array( $this, 'deactivate_memberships' ), 10, 3 );
		}

		/**
		 * in case of Paypal and Stripe disputes
		 * the memberships in the order will be deactivated
		 *
		 * @param int    $order_id
		 * @param string $old_status
		 * @param string $new_status
		 *
		 * @since 1.1.2
		 */
		public function deactivate_memberships( $order_id, $old_status, $new_status ) {
			if ( apply_filters( 'yith_wcmbs_deactivate_membership', false, $order_id, $old_status, $new_status ) || $new_status == 'on-hold' && in_array( $old_status, array( 'processing', 'completed' ) ) ) {
				$memberships     = YITH_WCMBS_Membership_Helper()->get_memberships_by_order( $order_id );
				$additional_note = sprintf( __( 'Reason: Order #%s status changed from %s to %s', 'yith-woocommerce-membership' ), $order_id, $old_status, $new_status );

				if ( ! empty( $memberships ) ) {
					foreach ( $memberships as $membership ) {
						if ( $membership instanceof YITH_WCMBS_Membership ) {
							$membership->update_status( 'not_active', 'change_status', $additional_note );
						}
					}
				}
			}
		}

		/**
		 * set user membership when order is completed
		 *
		 * @param int $order_id id of order
		 *
		 * @access public
		 * @since  1.0.0
		 */
		public function set_user_membership( $order_id ) {
			$memberships = YITH_WCMBS_Membership_Helper()->get_memberships_by_order( $order_id );

			if ( ! empty( $memberships ) ) {
				foreach ( $memberships as $membership ) {
					if ( $membership instanceof YITH_WCMBS_Membership && 'not_active' === $membership->status ) {
						$membership->update_status( 'resumed' );
					}
				}
			} else {

				$plans = YITH_WCMBS_Manager()->get_plans();

				$plan_product_ids    = array();
				$plans_product_array = array();
				$activated_plans     = array();

				foreach ( $plans as $plan ) {
					$member_product_ids = $plan->is_purchasable() ? $plan->get_target_products() : array();
					if ( ! ! $member_product_ids ) {
						$plan_product_ids = array_unique( array_merge( $plan_product_ids, $member_product_ids ) );
						foreach ( $member_product_ids as $member_product_id ) {
							$plans_product_array[ $member_product_id ][] = absint( $plan->get_id() );
						}
					}
				}

				$order   = wc_get_order( $order_id );
				$user_id = $order->get_user_id();

				$member = YITH_WCMBS_Members()->get_member( $user_id );

				foreach ( $order->get_items() as $order_item_id => $item ) {
					$product_id = absint( $item['product_id'] );
					$id         = ! empty( $item['variation_id'] ) ? absint( $item['variation_id'] ) : $product_id;

					// If this product is subscription, no actions! Subscription plugin will manage the membership activation.
					if ( YITH_WCMBS_Compatibility::has_plugin( 'subscription' ) ) {
						if ( function_exists( 'ywsbs_is_subscription_product' ) ? ywsbs_is_subscription_product( $id ) : YITH_WC_Subscription()->is_subscription( $id ) ) {
							continue;
						}
					}

					if ( ! apply_filters( 'yith_wcmbs_create_membership', true, $id, $order, $plan_product_ids ) ) {
						continue;
					}

					$plans_to_activate = in_array( $id, $plan_product_ids, true ) ? $plans_product_array[ $id ] : array();

					if ( $product_id !== $id && in_array( $product_id, $plan_product_ids, true ) ) {
						$plans_to_activate = array_merge( $plans_to_activate, $plans_product_array[ $product_id ] );
					}

					if ( $plans_to_activate ) {
						foreach ( $plans_to_activate as $plan_id ) {
							if ( apply_filters( 'yith_wcmbs_activate_unique_plans_only_per_order', false ) && in_array( $plan_id, $activated_plans, true ) ) {
								continue;
							}

							if ( apply_filters( 'yith_wcmbs_activate_plan_in_order', true, $plan_id, $order ) ) {
								$activated_plans[] = absint( $plan_id );
								$member->create_membership( $plan_id, $order_id, $order_item_id );
							}
						}
					}
				}
			}

		}
	}
}

if ( ! function_exists( 'yith_wcmbs_orders' ) ) {
	/**
	 * Unique access to instance of YITH_WCMBS_Admin class
	 *
	 * @return YITH_WCMBS_Orders
	 * @since   1.2.6
	 */
	function yith_wcmbs_orders() {
		return YITH_WCMBS_Orders::get_instance();
	}
}
