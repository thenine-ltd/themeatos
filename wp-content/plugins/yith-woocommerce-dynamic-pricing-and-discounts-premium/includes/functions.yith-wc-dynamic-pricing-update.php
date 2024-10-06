<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * map the old plugin version ( version <= 1.6.6 ) in the new plugin panel
 * @author YITH
 * @since 1.7.0
 */
function yith_ywdpd_update_db_to_2_0_0() {

	$db_version  = get_option( 'yith_ywdpd_db_version', '1.7.1' );
	$old_options = get_option( 'yit_ywdpd_options', array() );

	if (  version_compare( $db_version, '2.0.0', '<' ) ) {

		if ( count( $old_options ) > 0 ) {
			yith_ywdpd_update_plugin_option_to_2_0_0( $old_options );
		}

		yith_ywdpd_update_dynamic_rules_to_2_0_0();

		update_option( 'yith_ywdpd_db_version', '2.0.0' );
	}


}

function yith_ywdpd_update_plugin_option_to_2_0_0( $old_options ) {
	$map_old_key = array(
		'coupon_label'                     => 'ywdpd_coupon_label',
		'show_note_on_products'            => 'ywdpd_show_note_on_products',
		'show_note_on_products_place'      => 'ywdpd_show_note_on_products_place',
		'show_quantity_table'              => 'ywdpd_show_quantity_table',
		'update_price_on_qty'              => 'ywdpd_update_price_on_qty',
		'default_qty_selected'             => 'ywdpd_default_qty_selected',
		'show_quantity_table_schedule'     => 'ywdpd_show_quantity_table_schedule',
		'show_quantity_table_label'        => 'ywdpd_show_quantity_table_label',
		'quantity_table_orientation'       => 'ywdpd_quantity_table_orientation',
		'show_quantity_table_place'        => 'ywdpd_show_quantity_table_place',
		'show_minimum_price'               => 'ywdpd_show_as_default',
		'price_format'                     => 'ywdpd_price_format',
		'calculate_discounts_tax'          => 'ywdpd_calculate_discounts_tax',
		'enable_shop_manager'              => 'ywdpd_enable_shop_manager',
		'wpml_extend_to_translated_object' => 'ywdpd_wpml_extend_to_translated_object'
	);

	foreach ( $map_old_key as $old_key => $new_key ) {

		if ( isset( $old_options[ $old_key ] ) ) {
			$value = $old_options[ $old_key ];
			//manage the exception
			if ( 'show_minimum_price' === $old_key ) {
				$value = yith_plugin_fw_is_true( $value ) ? 'max' : 'min';
			}


			update_option( $new_key, $value );
		}
	}

	$table_labels = array( 'quantity' => '', 'price' => '' );
	if ( ! empty( $old_options['show_quantity_table_label_quantity'] ) ) {

		$table_labels['quantity'] = $old_options['show_quantity_table_label_quantity'];
	}

	if ( ! empty( $old_options['show_quantity_table_label_price'] ) ) {
		$table_labels['price'] = $old_options['show_quantity_table_label_price'];
	}

	update_option( 'ywdpd_quantity_table_labels', $table_labels );
}

function yith_ywdpd_update_dynamic_rules_to_2_0_0() {

	$paged = 1;
	$args  = array(
		'post_type'      => 'ywdpd_discount',
		'posts_per_page' => 20,
		'fields'         => 'ids',
		'post_status'    => 'publish',
		'meta_query'     => array(
			'relation' => 'and',
			array(
				'key'   => '_discount_type',
				'value' => 'pricing',
			),
			array(
				'key'     => '_rule_fixed',
				'compare' => 'NOT EXISTS'
			),
		),
		'paged'          => $paged
	);

	$price_rules = get_posts( $args );


	while ( count( $price_rules ) > 0 ) {

		//deactive temporarily the old rule active and save a backup in the db
		yith_ywdpd_save_support_option( $price_rules );
		$paged ++;
		$args['paged'] = $paged;
		$price_rules   = get_posts( $args );
	}
	yith_ywdpd_porting_price_rules();

	//CART RULES
	$paged              = 1;
	$args['meta_query'] = array(
		'relation' => 'and',
		array(
			'key'   => '_discount_type',
			'value' => 'cart',
		),
	array(
			'key'     => '_rule_fixed',
			'compare' => 'NOT EXISTS'
		)
	);

	$args['paged'] = $paged;

	$cart_rules = get_posts( $args );

	while ( count( $cart_rules ) > 0 ) {

		//deactive temporarily the old rule active and save a backup in the db
		yith_ywdpd_save_support_option( $cart_rules, 'cart' );
		$paged ++;
		$args['paged'] = $paged;
		$cart_rules    = get_posts( $args );
	}

	yith_ywdpd_porting_cart_rules();
}

/**
 * * @param array $rules
 */
function yith_ywdpd_save_support_option( $rules, $type = 'price' ) {

	$support_db = get_option( 'ywdpd_support_' . $type . '_rule_db', array() );

	foreach ( $rules as $rule_id ) {

		if ( ! isset( $support_db[ $rule_id ] ) ) {
			$value                  = get_post_meta( $rule_id, '_active', true );
			$rule_fixed             = get_post_meta( $rule_id, '_rule_fixed', true );
			$is_active              = true === $value || 1 === $value || '1' === $value || 'yes' === $value || 'true' === $value;
			$support_db[ $rule_id ] = $is_active;

			if ( $is_active && 'yes' !== $rule_fixed ) {

				update_post_meta( $rule_id, '_active', 'no' );
			}

			update_post_meta( $rule_id, '_version', '1.7.1' );
		}
	}

	update_option( 'ywdpd_support_' . $type . '_rule_db', $support_db );
}

function yith_ywdpd_porting_price_rules() {
	$price_rules = get_option( 'ywdpd_support_price_rule_db', array() );

	foreach ( $price_rules as $rule_id => $is_active ) {
		yith_ywdpd_porting_single_price_rule( $rule_id );

		update_post_meta( $rule_id, '_rule_fixed', 'yes' );

		if ( $is_active ) {
			update_post_meta( $rule_id, '_active', 'yes' );
		}

	}
}

/**
 * @param int $rule_id
 * @param bool $is_active
 *
 *
 * @since 2.0.0
 * @author YITH
 */
function yith_ywdpd_porting_single_price_rule( $rule_id ) {

	$apply_with_other_rule = get_post_meta( $rule_id, '_apply_with_other_rules', true );
	$apply_on_sale_rule    = get_post_meta( $rule_id, '_apply_on_sale', true );
	$apply_with_other_rule = ! yith_plugin_fw_is_true( $apply_with_other_rule ) ? 'yes' : 'no';
	$apply_on_sale_rule    = ! yith_plugin_fw_is_true( $apply_on_sale_rule ) ? 'yes' : 'no';
	$schedule_from         = get_post_meta( $rule_id, '_schedule_from', true );
	$schedule_to           = get_post_meta( $rule_id, '_schedule_to', true );
	$discount_mode         = get_post_meta( $rule_id, '_discount_mode', true );

	$new_schedule = array(
		'schedule_type' => empty( $schedule_from ) ? 'no_schedule' : 'schedule_dates',
		'schedule_from' => $schedule_from,
		'schedule_to'   => $schedule_to
	);
	$meta_to_save = array(
		'_no_apply_with_other_rules' => $apply_with_other_rule,
		'_disable_on_sale'           => $apply_on_sale_rule,
		'_schedule_discount_mode'    => $new_schedule
	);

	if ( 'special_offer' === $discount_mode ) {

		$so_rule = get_post_meta( $rule_id, 'so-rule', true );

		if ( isset( $so_rule['repeat'] ) ) {
			$meta_to_save['_so-repeat'] = 'yes';
		}
	}
	yith_ywdpd_porting_single_price_rule_apply_to( $rule_id );
	yith_ywdpd_porting_single_price_rule_adjustment_to( $rule_id );
	yith_ywdpd_porting_single_price_rule_user( $rule_id );

	foreach ( $meta_to_save as $meta_key => $meta_value ) {
		update_post_meta( $rule_id, $meta_key, $meta_value );
	}


}

/**
 * @param $rule_id
 *
 *
 */
function yith_ywdpd_porting_single_price_rule_apply_to( $rule_id ) {
	$apply_to = get_post_meta( $rule_id, '_apply_to', true );

	$exclude_condition_map_id = array(
		'categories_list_excluded',
		'tags_list_excluded',
		'products_list_excluded',
		'vendor_list_excluded',
		'brand_list_excluded'
	);
	$meta_to_save             = array();
	if ( in_array( $apply_to, $exclude_condition_map_id, true ) ) {
		$meta_to_save['_rule_for']       = 'all_products';
		$meta_to_save['_active_exclude'] = 'yes';

		switch ( $apply_to ) {
			case 'products_list_excluded':
				$meta_to_save['_exclude_rule_for']               = 'specific_products';
				$meta_to_save['_exclude_rule_for_products_list'] = get_post_meta( $rule_id, '_apply_to_products_list_excluded', true );
				break;
			case 'categories_list_excluded':
				$meta_to_save['_exclude_rule_for']                 = 'specific_categories';
				$meta_to_save['_exclude_rule_for_categories_list'] = get_post_meta( $rule_id, '_apply_to_categories_list_excluded', true );
				break;
			case 'tags_list_excluded':
				$meta_to_save['_exclude_rule_for']           = 'specific_tag';
				$meta_to_save['_exclude_rule_for_tags_list'] = get_post_meta( $rule_id, '_apply_to_tags_list_excluded', true );
				break;
			case 'vendor_list_excluded' :
				$meta_to_save['_exclude_rule_for'] = 'vendor_list_excluded';
				break;
			case 'brand_list_excluded':
				$meta_to_save['_exclude_rule_for'] = 'brand_list_excluded';
				break;
		}

	} else {

		switch ( $apply_to ) {
			case 'all_products':
				$meta_to_save['_rule_for'] = 'all_products';
				break;
			case 'products_list':
				$meta_to_save['_rule_for']               = 'specific_products';
				$meta_to_save['_rule_for_products_list'] = get_post_meta( $rule_id, '_apply_to_products_list', true );
				break;
			case 'categories_list':
				$meta_to_save['_rule_for']                 = 'specific_categories';
				$meta_to_save['_rule_for_categories_list'] = get_post_meta( $rule_id, '_apply_to_categories_list', true );
				break;
			case 'tags_list':
				$meta_to_save['_rule_for']           = 'specific_tag';
				$meta_to_save['_rule_for_tags_list'] = get_post_meta( $rule_id, '_apply_to_tags_list', true );
				break;
			case 'vendor_list' :
				$meta_to_save['_rule_for'] = 'vendor_list';
				break;
			case 'brand_list':
				$meta_to_save['_rule_for'] = 'specific_brands';
				break;

		}
	}

	foreach ( $meta_to_save as $meta_key => $meta_value ) {
		update_post_meta( $rule_id, $meta_key, $meta_value );

	}

}

/**
 * @param $rule_id
 *
 *
 */
function yith_ywdpd_porting_single_price_rule_adjustment_to( $rule_id ) {
	$apply_adjustment_to = get_post_meta( $rule_id, '_apply_adjustment', true );

	$exclude_condition_map_id = array(
		'products_list_excluded',
		'categories_list_excluded',
		'tags_list_excluded',
		'vendor_list_excluded',
		'brand_list_excluded'
	);
	$meta_to_save             = array();
	if ( in_array( $apply_adjustment_to, $exclude_condition_map_id, true ) ) {
		$meta_to_save['_rule_apply_adjustment_discount_for'] = 'all_products';
		$meta_to_save['_active_apply_adjustment_to_exclude'] = 'yes';
		$meta_to_save['_active_apply_discount_to']           = 'yes';

		switch ( $apply_adjustment_to ) {
			case 'products_list_excluded':
				$meta_to_save['_exclude_apply_adjustment_rule_for'] = 'specific_products';
				break;
			case 'categories_list_excluded':
				$meta_to_save['_exclude_apply_adjustment_rule_for'] = 'specific_categories';
				break;
			case 'tags_list_excluded':
				$meta_to_save['_exclude_apply_adjustment_rule_for'] = 'specific_tag';
				break;
			case 'vendor_list_excluded' :
				$meta_to_save['_exclude_apply_adjustment_rule_for'] = 'vendor_list_excluded';
				break;
			case 'brand_list_excluded':
				$meta_to_save['_exclude_apply_adjustment_rule_for'] = 'brand_list_excluded';
				break;
		}

	} else {

		switch ( $apply_adjustment_to ) {
			case 'all_products':
				$meta_to_save['_rule_apply_adjustment_discount_for'] = 'all_products';
				break;
			case 'products_list':
				$meta_to_save['_rule_apply_adjustment_discount_for'] = 'specific_products';
				break;
			case 'categories_list':
				$meta_to_save['_rule_apply_adjustment_discount_for'] = 'specific_categories';
				break;
			case 'tags_list':
				$meta_to_save['_rule_apply_adjustment_discount_for'] = 'specific_tag';
				break;
			case 'vendor_list' :
				$meta_to_save['_rule_apply_adjustment_discount_for'] = 'vendor_list';
				break;
			case 'brand_list':
				$meta_to_save['_rule_apply_adjustment_discount_for'] = 'brand_list';
				break;

		}
	}

	foreach ( $meta_to_save as $meta_key => $meta_value ) {

		update_post_meta( $rule_id, $meta_key, $meta_value );

	}


}

/**
 * @param $rule_id
 */
function yith_ywdpd_porting_single_price_rule_user( $rule_id ) {

	$user_meta_condition = get_post_meta( $rule_id, '_user_rules', true );

	$exclude_condition_map_id = array(
		'role_list_excluded',
		'customers_list_excluded',
		'excluded_memberships_list',
	);

	$meta_to_save = array();
	if ( in_array( $user_meta_condition, $exclude_condition_map_id, true ) ) {
		$meta_to_save['_enable_user_rule_exclude'] = 'yes';
		$meta_to_save['_user_rules']               = 'everyone';

		switch ( $user_meta_condition ) {
			case 'role_list_excluded':
				$meta_to_save['_user_rule_exclude'] = 'specific_roles';
				break;
			case 'customers_list_excluded':
				$meta_to_save['_user_rule_exclude'] = 'specific_customers';
				break;
			case 'excluded_memberships_list':
				$meta_to_save['_user_rule_exclude'] = 'specific_membership';
				break;
		}
	} else {


		switch ( $user_meta_condition ) {
			case 'memberships_list':
				$meta_to_save['_user_rules'] = 'specific_membership';
				break;
			case 'role_list':
				$role_list = get_post_meta( $rule_id, '_user_rules_role_list', array() );

				if( is_array($role_list)){
					foreach ( $role_list as $single_role){

						if( in_array('', $single_role ) ){
							$meta_to_save['_user_rules'] = 'everyone';
							break;
						}
					}

				}
				break;
		}
	}

	foreach ( $meta_to_save as $meta_key => $meta_value ) {
		update_post_meta( $rule_id, $meta_key, $meta_value );
	}
}

function yith_ywdpd_porting_cart_rules() {
	$cart_rules = get_option( 'ywdpd_support_cart_rule_db', array() );

	foreach ( $cart_rules as $rule_id => $is_active ) {
		yith_ywdpd_porting_single_cart_rule( $rule_id );

		update_post_meta( $rule_id, '_rule_fixed', 'yes' );

		if ( $is_active ) {
			update_post_meta( $rule_id, '_active', 'yes' );
		}

	}
}

/**
 * @param $rule_id
 */
function yith_ywdpd_porting_single_cart_rule( $rule_id ) {
	$schedule_from = get_post_meta( $rule_id, '_schedule_from', true );
	$schedule_to   = get_post_meta( $rule_id, '_schedule_to', true );
	$new_schedule  = array(
		'schedule_type' => empty( $schedule_from ) ? 'no_schedule' : 'schedule_dates',
		'schedule_from' => $schedule_from,
		'schedule_to'   => $schedule_to
	);
	$meta_to_save  = array(
		'_schedule_discount_mode' => $new_schedule
	);

	$cart_rules = get_post_meta( $rule_id, 'rules', true );
	$defaults   = array(
		'cart_condition_name'                  => '',
		'user_discount_to'                     => 'all',
		'customers_list'                       => array(),
		'customers_role_list'                  => array(),
		'rules_type_memberships_list'          => array(),
		'enable_exclude_users'                 => 'no',
		'customers_list_excluded'              => array(),
		'customers_role_list_excluded'         => array(),
		'rules_type_excluded_memberships_list' => array(),
		'min_order'                            => 1,
		'max_order'                            => '',
		'min_expense'                          => 1,
		'max_expense'                          => '',
		'cart_item_qty_type'                   => 'count_product_items',
		'min_product_item'                     => 1,
		'max_product_item'                     => '',
		'min_cart_item'                        => 1,
		'max_cart_item'                        => '',
		'max_subtotal'                         => '',
		'product_type'                         => 'require_product',
		'enable_require_product'               => 'yes',
		'require_product_list'                 => array(),
		'require_product_list_mode'            => 'at_least',
		'enable_require_product_categories'    => 'no',
		'require_product_category_list'        => array(),
		'require_product_cat_list_mode'        => 'at_least',
		'enable_require_product_tag'           => 'no',
		'require_product_tag_list'             => array(),
		'require_product_tag_list_mode'        => 'at_least',
		'enable_require_product_vendors'       => 'no',
		'require_product_vendors_list'         => array(),
		'enable_require_product_brands'        => 'no',
		'require_product_brands_list'          => array(),
		'require_product_brand_list_mode'      => 'at_least',
		'enable_exclude_require_product'       => 'no',
		'exclude_product_list'                 => array(),
		'enable_exclude_on_sale_product'       => 'no',
		'enable_exclude_product_categories'    => 'no',
		'exclude_product_category_list'        => 'no',
		'enable_exclude_product_tag'           => 'no',
		'exclude_product_tag_list'             => array(),
		'enable_exclude_product_vendors'       => 'no',
		'exclude_product_vendors_list'         => array(),
		'enable_exclude_product_brands'        => 'no',
		'exclude_product_brands_list'          => array(),
		'enable_disable_require_product'       => 'no',
		'disable_product_list'                 => array(),
		'enable_disable_product_categories'    => 'no',
		'disable_product_category_list'        => array(),
		'disable_exclude_product_tag'          => 'no',
		'disable_product_tag_list'             => array(),
		'enable_disable_product_brands'        => 'no',
		'disable_product_brands_list'          => array()

	);
	if ( is_array( $cart_rules ) && count( $cart_rules ) > 0 ) {
		$new_cart_rules = array();

		foreach ( $cart_rules as $single_rule ) {

			$rule_type        = isset( $single_rule['rules_type'] ) ? $single_rule['rules_type'] : '';
			$single_condition = array();

			switch ( $rule_type ) {
				case 'customers_list':
				case 'customers_list_excluded':
				case 'role_list':
				case 'role_list_excluded':
				case 'memberships_list':
				case 'excluded_memberships_list':
					$single_condition = yith_ywdpd_porting_cart_rule_user_type( $rule_type, $single_rule );
					break;
				case 'num_of_orders':
				case 'max_num_of_orders':
					$single_condition = yith_ywdpd_porting_cart_rule_amount_order_type( $rule_type, $single_rule );
					break;
				case 'amount_spent':
				case 'max_amount_spent':
					$single_condition = yith_ywdpd_porting_cart_rule_amount_spent_type( $rule_type, $single_rule );
					break;
				case 'products_list':
				case 'products_list_and':
				case 'products_list_excluded':
				case 'exclude_disc_products':
				case 'categories_list':
				case 'categories_list_and':
				case 'categories_list_excluded':
				case 'rules_type_exclude_disc_categories':
				case 'tags_list':
				case 'tags_list_and':
				case 'tags_list_excluded':
				case 'exclude_disc_tags':
				case 'vendor_list':
				case 'vendor_list_excluded':
				case 'brand_list':
				case 'brand_list_and':
				case 'brand_list_excluded':
				case 'exclude_disc_sale':
					$single_condition = yith_ywdpd_porting_cart_rule_product_type( $rule_type, $single_rule );
					break;
				case 'sum_item_quantity':
				case 'sum_item_quantity_less':
				case 'count_cart_items_at_least':
				case 'count_cart_items_less':
					$single_condition = yith_ywdpd_porting_cart_rule_cart_items_type( $rule_type, $single_rule );
					break;
				case 'subtotal_at_least':
				case 'subtotal_less':
					$single_condition = yith_ywdpd_porting_cart_rule_cart_subtotal_type( $rule_type, $single_rule );
					break;
			}
			if ( ! empty( $single_condition ) ) {

				$single_condition = array_merge( $defaults, $single_condition );

				$new_cart_rules[] = $single_condition;
			}
		}
		$meta_to_save['_cart_discount_rules'] = $new_cart_rules;
	}



	foreach ( $meta_to_save as $meta_key => $meta_value ) {
		update_post_meta( $rule_id, $meta_key, $meta_value );
	}
}

/**
 * porting user conditions for cart rules
 *
 * @param $type
 * @param $rule
 *
 * @return array
 * @author YITH
 * @since 2.0.0
 */
function yith_ywdpd_porting_cart_rule_user_type( $type, $rule ) {

	$condition = array(
		'condition_for' => 'customers'
	);
	switch ( $type ) {
		case 'customers_list':
			$customers_list                   = isset( $rule['rules_type_customers_list'] ) ? $rule['rules_type_customers_list'] : array();
			$condition['user_discount_to']    = 'specific_user_role';
			$condition['customers_list']      = $customers_list;
			$condition['cart_condition_name'] = 'Condition for Customers in list';
			break;
		case 'customers_list_excluded':
			$customer_excluded_list               = isset( $rule['rules_type_customers_list_excluded'] ) ? $rule['rules_type_customers_list_excluded'] : array();
			$condition['user_discount_to']        = 'all';
			$condition['enable_exclude_users']    = 'yes';
			$condition['customers_list_excluded'] = $customer_excluded_list;
			$condition['cart_condition_name']     = 'Condition for Customers not in list';
			break;
		case 'role_list':
			$roles_list                       = isset( $rule['rules_type_role_list'] ) ? $rule['rules_type_role_list'] : array();
			$condition['user_discount_to']    = 'specific_user_role';
			$condition['customers_role_list'] = $roles_list;
			$condition['cart_condition_name'] = 'Condition for user roles in list';
			break;
		case 'role_list_excluded':
			$roles_excluded_list                       = isset( $rule['rules_type_role_list_excluded'] ) ? $rule['rules_type_role_list_excluded'] : array();
			$condition['enable_exclude_users']         = 'yes';
			$condition['user_discount_to']             = 'all';
			$condition['customers_role_list_excluded'] = $roles_excluded_list;
			$condition['cart_condition_name']          = 'Condition for user roles not list';
			break;
		case 'memberships_list':
			$memberships_list                         = isset( $rule['rules_type_memberships_list'] ) ? $rule['rules_type_memberships_list'] : array();
			$condition['user_discount_to']            = 'specific_user_role';
			$condition['rules_type_memberships_list'] = $memberships_list;
			$condition['cart_condition_name']         = 'Condition for memberships plan in list';
			break;
		case 'excluded_memberships_list':
			$condition['user_discount_to']                     = 'all';
			$condition['enable_exclude_users']                 = 'yes';
			$memberships_excluded_list                         = isset( $rule['rules_type_excluded_memberships_list'] ) ? $rule['rules_type_excluded_memberships_list'] : array();
			$condition['rules_type_excluded_memberships_list'] = $memberships_excluded_list;
			$condition['cart_condition_name']                  = 'Condition for memberships plan not in list';
			break;
	}

	return $condition;
}

/**
 * @param $type
 * @param $rule
 *
 * @return array
 */
function yith_ywdpd_porting_cart_rule_amount_order_type( $type, $rule ) {
	$condition = array(
		'condition_for'       => 'num_of_orders',
		'cart_condition_name' => 'Condition for amount orders'
	);

	switch ( $type ) {
		case 'num_of_orders':
			$min_order              = isset( $rule['rules_type_num_of_orders'] ) ? $rule['rules_type_num_of_orders'] : 1;
			$condition['min_order'] = $min_order;
			$condition['max_order'] = '';
			break;
		case 'max_num_of_orders':
			$max_order              = isset( $rule['rules_type_max_num_of_orders'] ) ? $rule['rules_type_max_num_of_orders'] : '';
			$condition['min_order'] = 1;
			$condition['max_order'] = $max_order;
			break;
	}

	return $condition;
}

/**
 * @param $type
 * @param $rule
 *
 * @return array
 */
function yith_ywdpd_porting_cart_rule_amount_spent_type( $type, $rule ) {
	$condition = array(
		'condition_for'       => 'past_expense',
		'cart_condition_name' => 'Condition for amount spent'
	);

	switch ( $type ) {
		case 'amount_spent':
			$min_spent                = isset( $rule['rules_type_amount_spent'] ) ? $rule['rules_type_amount_spent'] : 1;
			$condition['min_expense'] = $min_spent;
			$condition['max_expense'] = '';
			break;
		case 'max_amount_spent':
			$max_spent                = isset( $rule['rules_type_max_amount_spent'] ) ? $rule['rules_type_max_amount_spent'] : '';
			$condition['min_expense'] = 1;
			$condition['max_expense'] = $max_spent;
			break;
	}

	return $condition;
}

function yith_ywdpd_porting_cart_rule_product_type( $type, $rule ) {
	$condition = array(
		'condition_for'          => 'product',
		'enable_require_product' => 'no'
	);

	switch ( $type ) {
		case 'products_list':
			$product_list                           = isset( $rule['rules_type_products_list'] ) ? $rule['rules_type_products_list'] : array();
			$condition['product_type']              = 'require_product';
			$condition['require_product_list_mode'] = 'at_least';
			$condition['require_product_list']      = $product_list;
			$condition['enable_require_product']    = 'yes';
			$condition['cart_condition_name']       = 'Condition for at least a specific product in list';
			break;
		case 'products_list_and':
			$product_list                           = isset( $rule['rules_type_products_list_and'] ) ? $rule['rules_type_products_list_and'] : array();
			$condition['product_type']              = 'require_product';
			$condition['require_product_list_mode'] = 'all_product';
			$condition['require_product_list']      = $product_list;
			$condition['cart_condition_name']       = 'Condition for all specific products in list';
			break;
		case 'products_list_excluded':
			$product_list                                = isset( $rule['rules_type_products_list_excluded'] ) ? $rule['rules_type_products_list_excluded'] : array();
			$condition['product_type']                   = 'disable_product';
			$condition['enable_disable_require_product'] = 'yes';
			$condition['disable_product_list']           = $product_list;
			$condition['cart_condition_name']            = 'Disable rule for specific products in list';
			break;
		case 'exclude_disc_products':
			$product_list                                = isset( $rule['rules_type_exclude_disc_products'] ) ? $rule['rules_type_exclude_disc_products'] : array();
			$condition['product_type']                   = 'exclude_product';
			$condition['enable_exclude_require_product'] = 'yes';
			$condition['exclude_product_list']           = $product_list;
			$condition['cart_condition_name']            = 'Exclude specific products in list to discount';
			break;
		case 'categories_list':
			$category_list                                  = isset( $rule['rules_type_categories_list'] ) ? $rule['rules_type_categories_list'] : array();
			$condition['product_type']                      = 'require_product';
			$condition['enable_require_product_categories'] = 'yes';
			$condition['require_product_category_list']     = $category_list;
			$condition['cart_condition_name']               = 'Condition for at least a specific product categories in list';
			$condition['require_product_cat_list_mode']     = 'at_least';
			break;
		case 'categories_list_and':
			$category_list                                  = isset( $rule['rules_type_categories_list_and'] ) ? $rule['rules_type_categories_list_and'] : array();
			$condition['product_type']                      = 'require_product';
			$condition['enable_require_product_categories'] = 'yes';
			$condition['require_product_category_list']     = $category_list;
			$condition['cart_condition_name']               = 'Condition for all specific product categories in list';
			$condition['require_product_cat_list_mode']     = 'all_category';
			break;
		case 'categories_list_excluded':
			$category_list                                  = isset( $rule['rules_type_categories_list_excluded'] ) ? $rule['rules_type_categories_list_excluded'] : array();
			$condition['product_type']                      = 'disable_product';
			$condition['enable_disable_product_categories'] = 'yes';
			$condition['disable_product_category_list']     = $category_list;
			$condition['cart_condition_name']               = 'Disable rule if a specific product categories in list';
			break;
		case 'rules_type_exclude_disc_categories':
			$category_list                                  = isset( $rule['rules_type_exclude_disc_categories'] ) ? $rule['rules_type_exclude_disc_categories'] : array();
			$condition['product_type']                      = 'exclude_product';
			$condition['enable_exclude_product_categories'] = 'yes';
			$condition['exclude_product_category_list']     = $category_list;
			$condition['cart_condition_name']               = 'Exclude specific product categories in list';
			break;
		case 'tags_list':
			$tag_list                                   = isset( $rule['rules_type_tags_list'] ) ? $rule['rules_type_tags_list'] : array();
			$condition['product_type']                  = 'require_product';
			$condition['enable_require_product_tag']    = 'yes';
			$condition['require_product_tag_list']      = $tag_list;
			$condition['cart_condition_name']           = 'Condition for at least a specific product tag in list';
			$condition['require_product_tag_list_mode'] = 'at_least';
			break;
		case 'tags_list_and':
			$tag_list                                   = isset( $rule['rules_type_tags_list_and'] ) ? $rule['rules_type_tags_list_and'] : array();
			$condition['product_type']                  = 'require_product';
			$condition['enable_require_product_tag']    = 'yes';
			$condition['require_product_tag_list']      = $tag_list;
			$condition['cart_condition_name']           = 'Condition for all specific product tag in list';
			$condition['require_product_tag_list_mode'] = 'all_tag';
			break;
		case 'tags_list_excluded':
			$tag_list                                 = isset( $rule['rules_type_tags_list_excluded'] ) ? $rule['rules_type_tags_list_excluded'] : array();
			$condition['product_type']                = 'disable_product';
			$condition['disable_exclude_product_tag'] = 'yes';
			$condition['disable_product_tag_list']    = $tag_list;
			$condition['cart_condition_name']         = 'Disable rule is a specific product tag in list';
			break;
		case 'exclude_disc_tags':
			$tag_list                                = isset( $rule['rules_type_exclude_disc_tags'] ) ? $rule['rules_type_exclude_disc_tags'] : array();
			$condition['product_type']               = 'exclude_product';
			$condition['enable_exclude_product_tag'] = 'yes';
			$condition['exclude_product_tag_list']   = $tag_list;
			$condition['cart_condition_name']        = 'Exclude specific product tag in list';
			break;
		case 'vendor_list':
			$vendor_list                                 = isset( $rule['rules_type_vendor_list'] ) ? $rule['rules_type_vendor_list'] : array();
			$condition['product_type']                   = 'require_product';
			$condition['enable_require_product_vendors'] = 'yes';
			$condition['require_product_vendors_list']   = $vendor_list;
			$condition['cart_condition_name']            = 'Condition for at least a vendor in list';
			break;
		case 'vendor_list_excluded':
			$vendor_list                                 = isset( $rule['rules_type_vendor_list_excluded'] ) ? $rule['rules_type_vendor_list_excluded'] : array();
			$condition['product_type']                   = 'exclude_product';
			$condition['enable_exclude_product_vendors'] = 'yes';
			$condition['exclude_product_vendors_list']   = $vendor_list;
			$condition['cart_condition_name']            = 'Exclude specific vendor in list';
			break;
		case 'brand_list':
			$brand_list                                   = isset( $rule['rules_type_brand_list'] ) ? $rule['rules_type_brand_list'] : array();
			$condition['product_type']                    = 'require_product';
			$condition['enable_require_product_brands']   = 'yes';
			$condition['require_product_brands_list']     = $brand_list;
			$condition['cart_condition_name']             = 'Condition for at least a specific product brand in list';
			$condition['require_product_brand_list_mode'] = 'at_least';
			break;
		case 'brand_list_and':
			$brand_list                                   = isset( $rule['rules_type_brand_list_and'] ) ? $rule['rules_type_brand_list_and'] : array();
			$condition['product_type']                    = 'require_product';
			$condition['enable_require_product_brands']   = 'yes';
			$condition['require_product_brands_list']     = $brand_list;
			$condition['cart_condition_name']             = 'Condition for all specific product brand in list';
			$condition['require_product_brand_list_mode'] = 'all_brand';
			break;
		case 'brand_list_excluded':
			$brand_list                                 = isset( $rule['rules_type_brand_list_excluded'] ) ? $rule['rules_type_brand_list_excluded'] : array();
			$condition['product_type']                  = 'disable_product';
			$condition['enable_disable_product_brands'] = 'yes';
			$condition['disable_product_brands_list']   = $brand_list;
			$condition['cart_condition_name']           = 'Disable rule if specific product brands are in list';
			break;
		case 'exclude_disc_sale':
			$condition['product_type']                   = 'exclude_product';
			$condition['enable_exclude_on_sale_product'] = 'yes';
			$condition['cart_condition_name']            = 'Exclude on sale products';
			break;
	}

	return $condition;
}

function yith_ywdpd_porting_cart_rule_cart_items_type( $type, $rule ) {

	$condition = array(
		'condition_for'       => 'cart_items',
		'cart_condition_name' => 'Condition for Cart items'
	);

	switch ( $type ) {
		case 'sum_item_quantity':
			$condition['cart_item_qty_type'] = 'count_product_items';
			$condition['min_product_item']   = $rule['rules_type_sum_item_quantity'];
			$condition['max_product_item']   = '';
			break;
		case 'sum_item_quantity_less':
			$condition['cart_item_qty_type'] = 'count_product_items';
			$condition['min_product_item']   = 1;
			$condition['max_product_item']   = $rule['rules_type_sum_item_quantity_less'];
			break;
		case 'count_cart_items_at_least':
			$condition['cart_item_qty_type'] = 'count_total_cart';
			$condition['min_cart_item']      = $rule['rules_type_count_cart_items_at_least'];
			$condition['max_cart_item']      = '';
			break;
		case 'count_cart_items_less':
			$condition['cart_item_qty_type'] = 'count_total_cart';
			$condition['min_cart_item']      = 1;
			$condition['max_cart_item']      = $rule['rules_type_count_cart_items_less'];
			break;
	}

	return $condition;
}

function yith_ywdpd_porting_cart_rule_cart_subtotal_type( $type, $rule ) {
	$condition = array(
		'condition_for'       => 'cart_subtotal',
		'cart_condition_name' => 'Condition for cart subtotal'
	);

	switch ( $type ) {
		case 'subtotal_at_least':
			$condition['min_subtotal'] = $rule['rules_type_subtotal_at_least'];
			$condition['max_subtotal'] = '';
			break;
		case 'subtotal_less':
			$condition['min_subtotal'] = 1;
			$condition['max_subtotal'] = $rule['rules_type_subtotal_less'];
			break;
	}

	return $condition;
}

add_action( 'admin_init', 'yith_ywdpd_update_db_to_2_0_0', 20 );
