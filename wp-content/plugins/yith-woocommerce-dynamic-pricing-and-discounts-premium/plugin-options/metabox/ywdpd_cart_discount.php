<?php
/**
 * Cart discount metabox options
 *
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.0.0
 * @version 1.6.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWDPD_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

$woo_doc_url           = sprintf( '<a href="%1$s" title="%2$s">%2$s</a>', 'https://docs.woocommerce.com/document/free-shipping/', __( 'WooCommerce Documentation', 'ywdpd' ) );
$discount_pricing_mode = ywdpd_discount_pricing_mode();
$last_priority         = ywdpd_get_last_priority( 'cart' ) + 1;
$key                   = uniqid();
$cart_rules_options    = YITH_WC_Dynamic_Pricing()->cart_rules_options;
$general_options       = array(
	array(
		'id'        => 'cart_condition_name',
		'type'      => 'text',
		'class_row' => 'ywdpd_general_rule',
		'name'      => __( 'Condition name', 'ywdpd' ),
		'desc'      => __( 'Set a name for this cart rule condition', 'ywdpd' )
	),
	array(
		'id'        => 'condition_for',
		'name'      => __( 'Create a condition based on:', 'ywdpd' ),
		'type'      => 'select',
		'class'     => 'wc-enhanced-select ywdpd_condition_for',
		'class_row' => 'ywdpd_general_rule',
		'options'   => array(
			'customers'     => __( 'Users', 'ywdpd' ),
			'num_of_orders' => __( 'Number of orders', 'ywdpd' ),
			'past_expense'  => __( 'Total amount spent', 'ywdpd' ),
			'product'       => __( 'Products', 'ywdpd' ),
			'cart_items'    => __( 'Cart items', 'ywdpd' ),
			'cart_subtotal' => __( 'Cart subtotal', 'ywdpd' )
		),
		'default'   => 'customers',
		'desc'      => __( 'Choose the condition type', 'ywdpd' )
	)
);
$user_options_include  = apply_filters( 'ywdpd_cart_rules_user_include_fields', array(
		array(
			'id'        => 'customers_list',
			'name'      => __( 'Apply discount to these users', 'ywdpd' ),
			'type'      => 'ajax-customers',
			'data'      => array(
				'placeholder' => esc_attr( __( 'Search for a customer', 'ywdpd' ) ),
				'allow_clear' => true,
			),
			'multiple'  => true,
			'desc'      => __( 'Select for which users to apply this discount', 'ywdpd' ),
			'class_row' => "customers specific_user_role",
			'default'   => ''
		),
		array(
			'id'        => 'customers_role_list',
			'name'      => __( 'Apply discount to these user roles', 'ywdpd' ),
			'type'      => 'select',
			'class'     => 'wc-enhanced-select',
			'data'      => array(
				'placeholder' => esc_attr( __( 'Search for a role', 'ywdpd' ) ),
				'allow_clear' => true,
			),
			'multiple'  => true,
			'options'   => ywdpd_get_roles(),
			'desc'      => __( 'Select for which user roles to apply this discount', 'ywdpd' ),
			'class_row' => "customers specific_user_role",
			'default'   => array()

		),
	)
);
$user_options_exclude  = apply_filters( 'ywdpd_cart_rules_user_exclude_fields', array(
		array(
			'id'        => 'customers_list_excluded',
			'name'      => __( 'Users excluded', 'ywdpd' ),
			'type'      => 'ajax-customers',
			'data'      => array(
				'placeholder' => esc_attr( __( 'Search for a customer', 'ywdpd' ) ),
				'allow_clear' => true
			),
			'multiple'  => true,
			'desc'      => __( 'Choose which users to exclude from this discount', 'ywdpd' ),
			'class_row' => "customers all customers_list_excluded",
			'default'   => ''
		),
		array(
			'id'        => 'customers_role_list_excluded',
			'name'      => __( 'Users roles excluded', 'ywdpd' ),
			'type'      => 'select',
			'class'     => 'wc-enhanced-select',
			'data'      => array(
				'placeholder' => esc_attr( __( 'Search for a role', 'ywdpd' ) ),
				'allow_clear' => true
			),
			'multiple'  => true,
			'options'   => ywdpd_get_roles(),
			'desc'      => __( 'Choose which user roles to exclude from this discount', 'ywdpd' ),
			'class_row' => "customers all customers_list_excluded",
			'default'   => array()
		),
	)
);
$user_options          = array_merge( array(
	array(
		'id'        => 'user_discount_to',
		'name'      => __( 'Apply discount to:', 'ywdpd' ),
		'type'      => 'radio',
		'class'     => 'user_discount_to',
		'options'   => array(
			'all'                => __( 'All users', 'ywdpd' ),
			'specific_user_role' => __( 'Specific users or user roles', 'ywdpd' )
		),
		'default'   => 'all',
		'desc'      => __( 'Choose to apply the discount to all user ( you can exclude users later ) or only to specific users/user roles', 'ywdpd' ),
		'class_row' => "customers sub_not_hide"
	)
),
	$user_options_include,
	array(
		array(
			'id'        => 'enable_exclude_users',
			'name'      => __( 'Exclude users from this discount', 'ywdpd' ),
			'type'      => 'onoff',
			'desc'      => __( 'Choose whether to exclude specific users or user roles from this discount', 'ywdpd' ),
			'default'   => 'no',
			'class_row' => "customers all subsub_not_hide",
			'class'     => 'ywdpd_enable_exclude_users'
		),
	),
	$user_options_exclude
);


$num_of_order_options    = array(

	array(
		'id'        => 'min_order',
		'name'      => __( 'Minimum number of orders', 'ywdpd' ),
		'type'      => 'number',
		'desc'      => __( 'Set the minimum number of orders required to apply this discount', 'ywdpd' ),
		'min'       => 0,
		'step'      => 1,
		'default'   => 1,
		'class_row' => "num_of_orders"
	),
	array(
		'id'        => 'max_order',
		'name'      => __( 'Maximum number of orders', 'ywdpd' ),
		'type'      => 'number',
		'desc'      => __( 'Set the maximum number of orders allowed to apply this discount', 'ywdpd' ),
		'min'       => 1,
		'step'      => 1,
		'class_row' => "num_of_orders"
	)
);
$past_expense_options    = array(
	array(
		'id'        => 'min_expense',
		'name'      => sprintf( _x( 'Minimum past expense (%s)','Minimum past expense ($)', 'ywdpd' ), get_woocommerce_currency_symbol() ),
		'type'      => 'number',
		'desc'      => __( 'Set the minimum expense required to apply this discount', 'ywdpd' ),
		'min'       => 1,
		'step'      => 0.1,
		'default'   => 1,
		'class_row' => "past_expense"
	),
	array(
		'id'        => 'max_expense',
		'name'      => sprintf( _x( 'Maximum past expense (%s)', 'Maximum past expense ($)', 'ywdpd' ), get_woocommerce_currency_symbol() ),
		'type'      => 'number',
		'desc'      => __( 'Set the maximum expense allowed to apply this discount', 'ywdpd' ),
		'min'       => 1,
		'step'      => 0.1,
		'class_row' => "past_expense"
	)
);
$cart_items_options      = array(

	array(
		'id'        => 'cart_item_qty_type',
		'type'      => 'radio',
		'options'   => array(
			'count_product_items' => __( 'Product items - unit of same product in the cart', 'ywdpd' ),
			'count_total_cart'    => __( 'Cart items - total of products in the cart', 'ywdpd' )
		),
		'class_row' => 'cart_items',
		'class'     => 'ywdpd_cart_item_qty_type',
		'default'   => 'count_product_items',
		'name'      => __( 'Check quantity of', 'ywdpd' ),
		'desc'      => __( 'Choose to link the condition to the number of product or total items in the cart', 'ywdpd' )
	),
	array(
		'id'        => 'min_product_item',
		'type'      => 'number',
		'name'      => __( 'Minimum quantity of product items', 'ywdpd' ),
		'desc'      => __( 'Set the minimum quantity of a same product in cart required to apply the discount', 'ywdpd' ),
		'min'       => 1,
		'default'   => 1,
		'step'      => 1,
		'class_row' => 'cart_items count_product_items'
	),
	array(
		'id'        => 'max_product_item',
		'type'      => 'number',
		'name'      => __( 'Maximum quantity of product items', 'ywdpd' ),
		'desc'      => __( 'Set the maximum quantity of a same product in cart allowed to apply the discount', 'ywdpd' ),
		'min'       => 1,
		'step'      => 1,
		'class_row' => 'cart_items count_product_items'
	),
	array(
		'id'        => 'min_cart_item',
		'type'      => 'number',
		'name'      => __( 'Minimum quantity of items in cart', 'ywdpd' ),
		'desc'      => __( 'Set the minimum quantity of items in cart required to apply the discount', 'ywdpd' ),
		'min'       => 1,
		'default'   => 1,
		'step'      => 1,
		'class_row' => 'cart_items count_total_cart'
	),
	array(
		'id'        => 'max_cart_item',
		'type'      => 'number',
		'name'      => __( 'Maximum quantity of items in cart', 'ywdpd' ),
		'desc'      => __( 'Set the maximum quantity of items in cart allowed to apply the discount', 'ywdpd' ),
		'min'       => 1,
		'step'      => 1,
		'class_row' => 'cart_items count_total_cart'
	)
);
$cart_subtotal_option    = array(

	array(
		'id'        => 'min_subtotal',
		'type'      => 'number',
		'name'      => sprintf( _x( 'Minimum cart subtotal (%s)', 'Minimum cart subtotal (€)', 'ywdpd' ), get_woocommerce_currency_symbol() ),
		'desc'      => __( 'Set the minimum cart subtotal required to apply the discount', 'ywdpd' ),
		'min'       => 1,
		'step'      => 0.1,
		'default'   => 1,
		'class_row' => 'cart_subtotal'
	),
	array(
		'id'        => 'max_subtotal',
		'type'      => 'number',
		'name'      => sprintf( _x( 'Maximum cart subtotal (%s)', 'Maximum cart subtotal (€)', 'ywdpd' ), get_woocommerce_currency_symbol() ),
		'desc'      => __( 'Set the maximum cart subtotal allowed to apply the discount', 'ywdpd' ),
		'min'       => 1,
		'step'      => 0.1,
		'class_row' => 'cart_subtotal'
	)
);
$product_require_options = apply_filters( 'ywdpd_cart_rules_product_include_fields', array(
	array(
		'id'        => 'enable_require_product',
		'name'      => __( 'Require specific products in the cart', 'ywdpd' ),
		'desc'      => __( 'Enable to require specific products in cart to apply the discount', 'ywdpd' ),
		'default'   => 'yes',
		'type'      => 'onoff',
		'class_row' => 'product require_product',
		'class'     => 'ywdpd_enable_require_product'
	),
	array(
		'id'        => 'require_product_list',
		'name'      => __( 'Select product', 'ywdpd' ),
		'desc'      => __( 'Choose which products are required in the cart to apply the discount', 'ywdpd' ),
		'default'   => '',
		'type'      => 'ajax-products',
		'data'      => array(
			'placeholder' => __( 'Search for products', 'ywdpd' ),
			'action'      => 'woocommerce_json_search_products_and_variations',
			'security'    => wp_create_nonce( 'search-products' )
		),
		'class_row' => 'product require_product enable_require_product_list',
		'multiple'  => true,
	),
	array(
		'id'        => 'require_product_list_mode',
		'name'      => __( 'Apply the discount if:', 'ywdpd' ),
		'desc'      => __( 'Choose to apply the discount when at least one of the specified product is in the cart or only when all products are in the cart', 'ywdpd' ),
		'type'      => 'radio',
		'options'   => array(
			'at_least'    => __( 'At least one selected product is in the cart', 'ywdpd' ),
			'all_product' => __( 'All selected products are in the cart', 'ywdpd' )
		),
		'default'   => 'at_least',
		'class_row' => 'product require_product enable_require_product_list',
		'class'     => 'ywdpd_require_product_list_mode'
	),
	array(
		'id'        => 'enable_require_product_categories',
		'name'      => __( 'Require specific product categories in cart', 'ywdpd' ),
		'desc'      => __( 'Enable to require products of specific categories in cart to apply the discount', 'ywdpd' ),
		'default'   => 'no',
		'type'      => 'onoff',
		'class_row' => 'product require_product',
		'class'     => 'ywdpd_enable_require_product_categories'
	),
	array(
		'id'        => 'require_product_category_list',
		'name'      => __( 'Select product category', 'ywdpd' ),
		'desc'      => __( 'Choose which product categories are required in the cart to apply the discount', 'ywdpd' ),
		'default'   => '',
		'type'      => 'ajax-terms',
		'data'      => array(
			'taxonomy'    => 'product_cat',
			'placeholder' => __( 'Search for product category', 'ywdpd' ),
		),
		'class_row' => 'product require_product enable_require_product_category_list',
		'multiple'  => true,
	),
	array(
		'id'        => 'require_product_cat_list_mode',
		'name'      => __( 'Apply the discount if:', 'ywdpd' ),
		'desc'      => __( 'Choose to apply the discount when at least one of the specified product category is in the cart or only when all products are in the cart', 'ywdpd' ),
		'type'      => 'radio',
		'options'   => array(
			'at_least'     => __( 'At least one of the selected product categories is in the cart', 'ywdpd' ),
			'all_category' => __( 'All selected product categories are in the cart', 'ywdpd' )
		),
		'default'   => 'at_least',
		'class_row' => 'product require_product enable_require_product_category_list',
		'class'     => 'ywdpd_require_product_cat_list_mode'
	),
	array(
		'id'        => 'enable_require_product_tag',
		'name'      => __( 'Require specific product tags in the cart', 'ywdpd' ),
		'desc'      => __( 'Enable to require products of a specific tag in the cart to apply the discount', 'ywdpd' ),
		'default'   => 'no',
		'type'      => 'onoff',
		'class_row' => 'product require_product',
		'class'     => 'ywdpd_enable_require_product_tag'
	),
	array(
		'id'        => 'require_product_tag_list',
		'name'      => __( 'Select product tag', 'ywdpd' ),
		'desc'      => __( 'Choose which product tag is required', 'ywdpd' ),
		'default'   => '',
		'type'      => 'ajax-terms',
		'data'      => array(
			'taxonomy'    => 'product_tag',
			'placeholder' => __( 'Search for product tag', 'ywdpd' ),
		),
		'class_row' => 'product require_product enable_require_product_tag_list',
		'multiple'  => true,
	),
	array(
		'id'        => 'require_product_tag_list_mode',
		'name'      => __( 'Apply the discount if:', 'ywdpd' ),
		'desc'      => __( 'Choose to apply the discount when at least one of the specified product tag is added to the cart or only when all products are in the cart', 'ywdpd' ),
		'type'      => 'radio',
		'options'   => array(
			'at_least' => __( 'At least one selected product tag is in cart', 'ywdpd' ),
			'all_tag'  => __( 'All selected product tags are in cart', 'ywdpd' )
		),
		'default'   => 'at_least',
		'class_row' => 'product require_product enable_require_product_tag_list',
		'class'     => 'ywdpd_require_product_tag_list_mode'
	),
) );
$product_exclude_options = apply_filters( 'ywdpd_cart_rules_product_exclude_fields', array(
	array(
		'id'        => 'enable_exclude_require_product',
		'name'      => __( 'Exclude specific products in cart', 'ywdpd' ),
		'desc'      => __( 'Enable to exclude specific products from discount conditions', 'ywdpd' ),
		'default'   => 'no',
		'type'      => 'onoff',
		'class_row' => 'product exclude_product',
		'class'     => 'ywdpd_enable_exclude_require_product'
	),
	array(
		'id'        => 'exclude_product_list',
		'name'      => __( 'Select product', 'ywdpd' ),
		'desc'      => __( 'Choose which products to exclude from this cart condition validation', 'ywdpd' ),
		'default'   => '',
		'type'      => 'ajax-products',
		'data'      => array(
			'placeholder' => __( 'Search for products', 'ywdpd' ),
			'action'      => 'woocommerce_json_search_products_and_variations',
			'security'    => wp_create_nonce( 'search-products' )
		),
		'class_row' => 'product exclude_product enable_exclude_product_list',
		'multiple'  => true,
	),
	array(
		'id'        => 'enable_exclude_on_sale_product',
		'name'      => __( 'Exclude \'on-sale products\'', 'ywdpd' ),
		'desc'      => __( 'Enable if you want to exclude \'on-sale products\' from discount conditions', 'ywdpd' ),
		'default'   => 'no',
		'type'      => 'onoff',
		'class_row' => 'product exclude_product',
		'class'     => 'ywdpd_enable_exclude_on_sale_product'
	),
	array(
		'id'        => 'enable_exclude_product_categories',
		'name'      => __( 'Exclude specific product categories from discount validation', 'ywdpd' ),
		'desc'      => __( 'Enable if you want to exclude products of specific categories from discount validation', 'ywdpd' ),
		'default'   => 'no',
		'type'      => 'onoff',
		'class_row' => 'product exclude_product',
		'class'     => 'ywdpd_enable_exclude_product_categories'
	),
	array(
		'id'        => 'exclude_product_category_list',
		'name'      => __( 'Select product category', 'ywdpd' ),
		'desc'      => __( 'Choose which product categories exclude from condition validation', 'ywdpd' ),
		'default'   => '',
		'type'      => 'ajax-terms',
		'data'      => array(
			'taxonomy'    => 'product_cat',
			'placeholder' => __( 'Search for product category', 'ywdpd' ),
		),
		'class_row' => 'product exclude_product enable_exclude_product_category_list',
		'multiple'  => true,
	),
	array(
		'id'        => 'enable_exclude_product_tag',
		'name'      => __( 'Exclude specific product tag from discount validation', 'ywdpd' ),
		'desc'      => __( 'Enable if you want to exclude products of specific tags from discount validation', 'ywdpd' ),
		'default'   => 'no',
		'type'      => 'onoff',
		'class_row' => 'product exclude_product',
		'class'     => 'ywdpd_enable_exclude_product_tag'
	),
	array(
		'id'        => 'exclude_product_tag_list',
		'name'      => __( 'Select product tag', 'ywdpd' ),
		'desc'      => __( 'Choose which product tags exclude from condition validation', 'ywdpd' ),
		'default'   => '',
		'type'      => 'ajax-terms',
		'data'      => array(
			'taxonomy'    => 'product_tag',
			'placeholder' => __( 'Search for product tag', 'ywdpd' ),
		),
		'class_row' => 'product exclude_product enable_exclude_product_tag_list',
		'multiple'  => true,
	)
) );

$product_disable_options = apply_filters( 'ywdpd_cart_rules_product_disable_fields', array(
	//Product not selected
	array(
		'id'        => 'enable_disable_require_product',
		'name'      => __( 'Disable discount when specific products are in cart', 'ywdpd' ),
		'desc'      => __( 'Enable to disable the discount if users has specific products in his cart', 'ywdpd' ),
		'default'   => 'no',
		'type'      => 'onoff',
		'class_row' => 'product disable_product',
		'class'     => 'ywdpd_enable_disable_product'
	),
	array(
		'id'        => 'disable_product_list',
		'name'      => __( 'Select product', 'ywdpd' ),
		'desc'      => __( 'Choose which products will disable the discount', 'ywdpd' ),
		'default'   => '',
		'type'      => 'ajax-products',
		'data'      => array(
			'placeholder' => __( 'Search for products', 'ywdpd' ),
			'action'      => 'woocommerce_json_search_products_and_variations',
			'security'    => wp_create_nonce( 'search-products' )
		),
		'class_row' => 'product disable_product enable_disable_product_list',
		'multiple'  => true,
	),
	array(
		'id'        => 'enable_disable_product_categories',
		'name'      => __( 'Disable discount when product(s) of specific categories are in the cart', 'ywdpd' ),
		'desc'      => __( 'Enable to disable the discount if user has products of specific categories in his cart', 'ywdpd' ),
		'default'   => 'no',
		'type'      => 'onoff',
		'class_row' => 'product disable_product',
		'class'     => 'ywdpd_enable_disable_product_categories'
	),
	array(
		'id'        => 'disable_product_category_list',
		'name'      => __( 'Select product category', 'ywdpd' ),
		'desc'      => __( 'Choose which product categories will disable the discount', 'ywdpd' ),
		'default'   => '',
		'type'      => 'ajax-terms',
		'data'      => array(
			'taxonomy'    => 'product_cat',
			'placeholder' => __( 'Search for product category', 'ywdpd' ),
		),
		'class_row' => 'product disable_product enable_disable_product_category_list',
		'multiple'  => true,
	),
	array(
		'id'        => 'disable_exclude_product_tag',
		'name'      => __( 'Disable discount when product(s) with specific tag are in the cart', 'ywdpd' ),
		'desc'      => __( 'Enable to disable the discount if the user has products of specific tags in his cart', 'ywdpd' ),
		'default'   => 'no',
		'type'      => 'onoff',
		'class_row' => 'product disable_product',
		'class'     => 'ywdpd_enable_disable_product_tag'
	),
	array(
		'id'        => 'disable_product_tag_list',
		'name'      => __( 'Select product tag', 'ywdpd' ),
		'desc'      => __( 'Choose which product tags will disable the discount', 'ywdpd' ),
		'default'   => '',
		'type'      => 'ajax-terms',
		'data'      => array(
			'taxonomy'    => 'product_tag',
			'placeholder' => __( 'Search for product tag', 'ywdpd' ),
		),
		'class_row' => 'product disable_product enable_disable_product_tag_list',
		'multiple'  => true,
	)
) );

$product_options = array_merge( array(
	array(
		'id'        => 'product_type',
		'name'      => __( 'Condition type', 'ywdpd' ),
		'type'      => 'radio',
		'options'   => array(
			'require_product' => sprintf( __( 'Require specific products in cart to apply the discount. %sDiscount will be applied only if the user has the specified product(s) in his cart%s', 'ywdpd' ), '<small>', '</small>' ),
			'exclude_product' => sprintf( __( 'Exclude specific products from cart condition validation. %sExcluded products will not be considered for achieving the conditions%s', 'ywdpd' ), '<small>', '</small>' ),
			'disable_product' => sprintf( __( 'Disable discount when there is a specific  product in cart. %sDiscount will be not applied if the user has specified product(s) in his cart%s', 'ywdpd' ), '<small>', '</small>' ),
		),
		'desc'      => __( 'Choose which kind of condition to create based on products', 'ywdpd' ),
		'class_row' => 'product',
		'class'     => 'ywdpd_product_type',
		'default'   => 'require_product'
	),
),
	$product_require_options,
	$product_exclude_options,
	$product_disable_options

);


return array(
	'label'    => __( 'Pricing Discount Settings', 'ywdpd' ),
	'pages'    => 'ywdpd_discount', // or array( 'post-type1', 'post-type2').
	'context'  => 'normal', // ('normal', 'advanced', or 'side').
	'priority' => 'default',
	'class'    => yith_set_wrapper_class(),
	'tabs'     => array(
		'settings' => array(
			'label'  => __( 'Settings', 'ywdpd' ),
			'fields' => apply_filters(
				'ywdpd_cart_discount_metabox',
				array(
					'discount_type' => array(
						'type' => 'hidden',
						'std'  => 'cart',
						'val'  => 'cart',
					),
					'key'           => array(
						'type' => 'hidden',
						'std'  => $key,
						'val'  => $key,
					),
					'active'        => array(
						'label' => __( 'Active', 'ywdpd' ),
						'desc'  => __( 'Select to enable or disable this rule', 'ywdpd' ),
						'type'  => 'onoff',
						'std'   => 'yes',
					),
					'priority'      => array(
						'label' => __( 'Priority', 'ywdpd' ),
						'desc'  => __( 'Set the priority to assign to this rule. Priority is important to overwrite rules. 1 is the highest priority', 'ywdpd' ),
						'type'  => 'number',
						'min'   => 1,
						'std'   => $last_priority,
					),
					/***************
					 * DISCOUNT TABLES
					 */

					'schedule_discount_mode' => array(
						'label' => __( 'Schedule offer', 'ywdpd' ),
						'type'  => 'schedule_rules',
						'desc'  => __( 'Choose to schedule a start and end time for this rule or enable it now', 'ywdpd' ),
						'std'   => 'no'
					),
					'discount_combined'      => array(
						'label' => __( 'Disable when a coupon has been applied', 'ywdpd' ),
						'desc'  => __( 'Enable if you want to disable this rule if the user has applied a coupon code', 'ywdpd' ),
						'type'  => 'onoff',
						'std'   => 'no',
					),

					'allow_free_shipping' => array(
						'label' => __( 'Allow free shipping', 'ywdpd' ),
						'type'  => 'onoff',
						'std'   => 'no',
						'desc'  => sprintf(
							_x( "Enable to offer 'free shipping' when this rule applies. For this to work, make sure you have set up a free-shipping method in WooCommerce > Settings > Shipping . After creating a shipping method, set 'Free shipping requires...' to 'A valid free shipping coupon'. For more information on setting up free shipping check the %s","Enable to offer 'free shipping' when this rule applies. For this to work, make sure you have set up a free-shipping method in WooCommerce > Settings > Shipping . After creating a shipping method, set 'Free shipping requires...' to 'A valid free shipping coupon'. For more information on setting up free shipping check the WooCommerce Documentation", 'ywdpd' ),
							$woo_doc_url
						)
					),
					'cart_discount_rules' => array(
						'id'               => '_cart_discount_rules',
						'label'            => __( 'Discount conditions', 'ywdpd' ),
						'type'             => 'toggle-element',
						'add_button'       => __( 'Add condition', 'ywdpd' ),
						'title'            => '%%cart_condition_name%%',
						'yith-display-row' => false,
						'elements'         => array_merge( $general_options, $user_options, $num_of_order_options, $past_expense_options, $cart_items_options, $cart_subtotal_option, $product_options ),

						'save_button'   => array(
							'id'   => 'ywdpd_save_condition',
							'name' => __( 'Save condition', 'ywdpd' )
						),
						'delete_button' => array(
							'id'   => 'ywdpd_delete_condition',
							'name' => __( 'Delete condition', 'ywdpd' ),

						)
					),
					'discount_rule'       => array(
						'label' => __( 'When conditions are met apply', 'ywdpd' ),
						'type'  => 'cart_discount_type',
						'desc'  => __( 'Set the discount to apply when all conditions are met', 'ywdpd' )
					),
					'separator'           => array(
						'type' => 'sep'
					),
					'save_rule'           => array(
						'type' => 'save_rule',
					)
				)
			),

		),
	),
);
