<?php
/**
 * Membership Data Store CPT
 *
 * @auhtor  Arcifa Giuseppe <giuseppe.arcifa@yithemes.com>
 * @package YITH\Memberships\DataStores
 */

defined( 'YITH_WCMBS' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WCMBS_Membership_Data_Store_CPT' ) ) {
	/**
	 * Membership Data Store CPT Class
	 *
	 * @since 2.0
	 */
	class YITH_WCMBS_Membership_Data_Store_CPT extends YITH_WCMBS_Simple_Data_Store_CPT {

		/**
		 * Map that relates meta keys to properties for YITH_WCMBS_Membership object
		 *
		 * @var array
		 */
		protected $meta_key_to_props = array(
			'_plan_id'       => 'plan_id',
			'_title'         => 'title',
			'_start_date'    => 'start_date',
			'_end_date'      => 'end_date',
			'_order_id'      => 'order_id',
			'_order_item_id' => 'order_item_id',
			'_user_id'       => 'user_id',
			'_status'        => 'status',
			'_paused_days'   => 'paused_days',
			'_activities'    => 'activities',
		);

		/**
		 * Map that relates meta keys to properties for the Object
		 *
		 * @var array
		 */
		protected $object_type = 'membership';

		/**
		 * Map that relates meta keys to properties for the Object
		 *
		 * @var array
		 */
		protected $object_post_type = 'ywcmbs-membership';

		/**
		 * YITH_WCBM_Badge_Data_Store_CPT construct
		 */
		public function __construct() {
			$this->messages = array(
				'invalid_data' => _x( 'Invalid Membership.', '[Generic] Error that happens when trying to read a Membership that does not exist', 'yith-woocommerce-membership' ),
			);
		}

		/**
		 * Update Object post meta
		 *
		 * @param WC_Data $object The object.
		 * @param bool    $force  Force update. Used during create.
		 *
		 * @since 2.0
		 */
		public function update_post_meta( &$object, $force = false ) {
			$props_to_update = $force ? $this->meta_key_to_props : $this->get_props_to_update( $object, $this->meta_key_to_props );

			foreach ( $props_to_update as $meta_key => $prop ) {
				$value = $object->{"get_$prop"}( 'edit' );
				$value = is_string( $value ) ? wp_unslash( $value ) : $value;
				switch ( $prop ) {
					default:
						$value = wc_clean( $value );
						break;
				}

				$updated = $this->update_or_delete_post_meta( $object, $meta_key, $value );

				if ( $updated ) {
					$this->updated_props[] = $prop;
				}
			}
		}
	}
}
