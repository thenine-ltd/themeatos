<?php
/**
 * Pricing rules options
 *
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.0.0
 * @version 1.6.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWDPD_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

return apply_filters(
	'yit_ywdpd_pricing_rules_options',
	array(
		'discount_mode' => array(
			'bulk'              => __( 'Quantity Discount', 'ywdpd' ),
			'special_offer'     => __( 'Special Offer', 'ywdpd' ),
			'gift_products'     => __( 'Gift Products', 'ywdpd' ),
			'discount_whole'    => __( 'Discount on entire shop', 'ywdpd' ),
			'category_discount' => __( 'Category Discount', 'ywdpd' ),
			//	'exclude_items' => __( 'Exclude items from rules', 'ywdpd' ),
		),

		'quantity_based'                     => array(
			'cart_line'                => __( 'Item quantity in cart line', 'ywdpd' ),
			'single_product'           => __( 'Single product', 'ywdpd' ),
			'single_variation_product' => __( 'Single product variation', 'ywdpd' ),
			'cumulative'               => __( 'Sum of all products in list or category list', 'ywdpd' ),
		),
		'rule_for'                           => array(
			'all_products'        => __( 'All products', 'ywdpd' ),
			'specific_products'   => __( 'Specific products', 'ywdpd' ),
			'specific_categories' => __( 'Specific product categories', 'ywdpd' ),
			'specific_tag'        => __( 'Specific product tags', 'ywdpd' )
		),
		'exclude_rule_for'                   => array(
			'specific_products'   => __( 'Specific products', 'ywdpd' ),
			'specific_categories' => __( 'Specific product categories', 'ywdpd' ),
			'specific_tag'        => __( 'Specific product tags', 'ywdpd' )
		),
		'rule_apply_adjustment_discount_for' => array(
			'all_products'        => __( 'All products', 'ywdpd' ),
			'specific_products'   => __( 'Specific products', 'ywdpd' ),
			'specific_categories' => __( 'Specific product categories', 'ywdpd' ),
			'specific_tag'        => __( 'Specific product tags', 'ywdpd' )
		),
		'exclude_apply_adjustment_rule_for'  => array(
			'specific_products'   => __( 'Specific products', 'ywdpd' ),
			'specific_categories' => __( 'Specific product categories', 'ywdpd' ),
			'specific_tag'        => __( 'Specific product tags', 'ywdpd' )
		),
		'apply_to'                           => array(
			'all_products'             => __( 'All products', 'ywdpd' ),
			'products_list'            => __( 'Include a list of products', 'ywdpd' ),
			'products_list_excluded'   => __( 'Exclude a list of products', 'ywdpd' ),
			'categories_list'          => __( 'Include a list of categories', 'ywdpd' ),
			'categories_list_excluded' => __( 'Exclude a list of categories', 'ywdpd' ),
			'tags_list'                => __( 'Include a list of tags', 'ywdpd' ), // @since 1.1.0
			'tags_list_excluded'       => __( 'Exclude a list of tags', 'ywdpd' ), // @since 1.1.0
		),

		'apply_adjustment' => array(
			'same_product'             => __( 'Same product', 'ywdpd' ),
			'all_products'             => __( 'All products', 'ywdpd' ),
			'products_list'            => __( 'Include a list of products', 'ywdpd' ),
			'products_list_excluded'   => __( 'Exclude a list of products', 'ywdpd' ),
			'categories_list'          => __( 'Include a list of categories', 'ywdpd' ),
			'categories_list_excluded' => __( 'Exclude a list of categories', 'ywdpd' ),
			'tags_list'                => __( 'Include a list of tags', 'ywdpd' ), // @since 1.1.0
			'tags_list_excluded'       => __( 'Exclude a list of tags', 'ywdpd' ), // @since 1.1.0
		),

		'type_of_discount' => array(
			'percentage'  => __( '% discount', 'ywdpd' ),
			'price'       => __( 'Price discount', 'ywdpd' ),
			'fixed-price' => __( 'Fixed price', 'ywdpd' ),
		),

		'user_rules' => array(
			'everyone'                => __( 'Everyone', 'ywdpd' ),
			'role_list'               => __( 'Include a list of roles', 'ywdpd' ),
			'role_list_excluded'      => __( 'Exclude a list of roles', 'ywdpd' ),
			'customers_list'          => __( 'Include a list of customers', 'ywdpd' ),
			'customers_list_excluded' => __( 'Exclude a list of customers', 'ywdpd' ),
		),
	)
);
