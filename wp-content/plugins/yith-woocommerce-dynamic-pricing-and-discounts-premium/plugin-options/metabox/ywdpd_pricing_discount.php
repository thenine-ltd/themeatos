<?php
/**
 * Pricing discount metabox options
 *
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.0.0
 * @version 1.6.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWDPD_VERSION' ) ) {
	exit; // Exit if accessed directly.
}

$key                   = uniqid();
$discount_pricing_mode = ywdpd_discount_pricing_mode();
$last_priority         = ywdpd_get_last_priority( 'pricing' ) + 1;
$pricing_rules_options = YITH_WC_Dynamic_Pricing()->pricing_rules_options;

$vendor_options_rule_for                          = apply_filters( 'ywdpd_vendor_options_rule_for', '' );
$vendor_options_exclude_rule_for                  = apply_filters( 'ywdpd_vendor_options_exclude_rule_for', '' );
$vendor_options_apply_adjustment_rule_for         = apply_filters( 'ywdpd_vendor_apply_adjustment_rule_for', '' );
$vendor_options_apply_exclude_adjustment_rule_for = apply_filters( 'ywdpd_vendor_apply_exclude_adjustment_rule_for', '' );

$membership_plan_included = apply_filters( 'ywdpd_membership_plan_included_field', '' );
$membership_plan_excluded = apply_filters( 'ywdpd_membership_plan_excluded_field', '' );

$brand_options_rule_for                          = apply_filters( 'ywdpd_brand_options_rule_for', '' );
$brand_options_exclude_rule_for                  = apply_filters( 'ywdpd_brand_options_exclude_rule_for', '' );
$brand_options_apply_adjustment_rule_for         = apply_filters( 'ywdpd_brand_apply_adjustment_rule_for', '' );
$brand_options_apply_exclude_adjustment_rule_for = apply_filters( 'ywdpd_brand_apply_exclude_adjustment_rule_for', '' );

$discount_mode_options = array(
	'label' => __( 'Rule type', 'ywdpd' ),
	'desc'  => __( 'Choose which type of discount to create', 'ywdpd' ),
	'type'  => 'radio',

	'options' => $pricing_rules_options['discount_mode'],
	'std'     => 'bulk',
);

global $_REQUEST;

if ( isset( $_REQUEST['post'] ) ) {

	$post_id   = $_REQUEST['post'];
	$rule_type = get_post_meta( $post_id, '_discount_mode', true );
	if ( 'exclude_items' === $rule_type ) {
		$discount_mode_options['type'] = 'advanced-simple-text';
		$discount_mode_options['desc'] = 'Exclude items from rules';
		unset( $discount_mode_options['std'] );
	}
}

return apply_filters(
	'ywdpd_pricing_discount_metabox_options',
	array(
		'label'    => __( 'Pricing Discount Settings', 'ywdpd' ),
		'pages'    => 'ywdpd_discount', // or array( 'post-type1', 'post-type2').
		'context'  => 'normal', // ('normal', 'advanced', or 'side').
		'priority' => 'default',
		'class'    => yith_set_wrapper_class(),
		'tabs'     => array(

			'settings' => array(
				'label'  => __( 'Settings', 'ywdpd' ),
				'fields' => apply_filters(
					'ywdpd_pricing_discount_metabox',
					//GENERAL OPTIONS
					array(
						'discount_type'          => array(
							'type' => 'hidden',
							'std'  => 'pricing',
							'val'  => 'pricing',
						),
						'key'                    => array(
							'type' => 'hidden',
							'std'  => $key,
							'val'  => $key,
						),
						'active'                 => array(
							'label' => __( 'Active rule', 'ywdpd' ),
							'desc'  => __( 'Select to enable or disable this discount or pricing rule', 'ywdpd' ),
							'type'  => 'onoff',
							'std'   => 'yes',
						),
						'priority'               => array(
							'label' => __( 'Priority', 'ywdpd' ),
							'desc'  => __( 'Set the priority to assign to this rule. Priority is important to overwrite rules. 1 is the highest priority', 'ywdpd' ),
							'type'  => 'number',
							'std'   => $last_priority,
							'min'   => 1,
						),

						// @since 1.1.0
						'discount_mode'          => $discount_mode_options,
						/*GIFT PRODUCTS*/
						'gift_product_selection' => array(
							'label'    => __( 'Choose which products to offer as a gift', 'ywdpd' ),
							'type'     => 'ajax-products',
							'desc'     => __( 'Select the products to offer as gift', 'ywdpd' ),
							'multiple' => true,
							'deps'     => array(
								'ids'    => '_discount_mode',
								'values' => 'gift_products',
								'type'   => 'hideNow'
							),
							'data'     => array(
								'action'   => 'woocommerce_json_search_products_and_variations',
								'security' => wp_create_nonce( 'search-products' )
							),
							'data'     => array(
								'action'   => 'woocommerce_json_search_products_and_variations',
								'security' => wp_create_nonce( 'search-products' )
							),
						),


						'amount_gift_product_allowed'      => array(
							'label' => __( 'How many gift products the user can select?', 'ywdpd' ),
							'type'  => 'number',
							'min'   => 0,
							'step'  => 1,
							'deps'  => array(
								'ids'    => '_discount_mode',
								'values' => 'gift_products',
								'type'   => 'hideNow'
							),
							'std'   => 1,
							'desc'  => __( 'Choose how many gift products can be selected as gift by the user', 'ywdpd' ),

						),
						//End gift

						//Whole & category discount
						'simple_whole_discount'            => array(
							'label'  => __( 'Discount to apply to entire shop', 'ywdpd' ),
							'type'   => 'inline-fields',
							'desc'   => __( 'Set the discount to apply to products from this rule', 'ywdpd' ),
							'fields' => array(
								'sub_html1'      => array(
									'type' => 'html',
									'html' => _x( 'Apply', 'Apply a %discount of 20% on all products', 'ywdpd' ),
								),
								'discount_mode'  => array(
									'label'   => '',
									'type'    => 'select',
									'options' => array(
										'percentage'  => __( 'a % discount of', 'ywdpd' ),
										'price'       => __( 'a price discount of', 'ywdpd' ),
										'fixed-price' => __( 'a fixed price of', 'ywdpd' ),
									),
									'std'     => 'percentage',

								),
								'discount_value' => array(
									'label' => '',
									'type'  => 'number',
									'min'   => 1,
									'step'  => 'any',
									'std'   => 10
								),
								'sub_html3'      => array(
									'type' => 'html',
									'html' => sprintf( _x( '<span class="ywdpd_symbol">%s</span> on all products', 'Apply a %discount of 20% on all products', 'ywdpd' ), '%' )
								)
							),
							'deps'   => array(
								'ids'    => '_discount_mode',
								'values' => 'discount_whole',
								'type'   => 'hideNow'
							)

						),
						'quantity_category_discount'       => array(
							'label' => __( 'Set a category discount', 'ywdpd' ),
							'type'  => 'quantity_category_discount',
							'desc'  => __( 'Set the discount to apply to product categories from this rule', 'ywdpd' ),
							'deps'  => array(
								'ids'    => '_discount_mode',
								'values' => 'category_discount',
								'type'   => 'hideNow'
							)
						),
						//END GENERAL OPTIONS
						/***************
						 * APPLY TO
						 */
						'rule_for'                         => array(
							'label'   => __( 'Create a quantity rule for the purchase of:', 'ywdpd' ),
							'desc'    => __( 'Choose if you want to create this rule for all products or for all products of specifics categories/tags', 'ywdpd' ),
							'type'    => 'radio',
							'options' => $pricing_rules_options['rule_for'],
							'std'     => 'all_products',
							'deps'    => array(
								'ids'    => '_discount_mode',
								'values' => 'bulk,special_offer,gift_products,exclude_items',
								'type'   => 'hideNow'
							)
						),
						'rule_for_products_list'           => array(
							'label'    => __( 'Apply rule to:', 'ywdpd' ),
							'type'     => 'ajax-products',
							'desc'     => __( 'Search the product(s) to include in the rule', 'ywdpd' ),
							'deps'     => array(
								'ids'    => '_rule_for',
								'values' => 'specific_products',
								'type'   => 'hideNow'
							),
							'multiple' => true,
							'std'      => array(),
							'data'     => array(
								'action'   => 'woocommerce_json_search_products_and_variations',
								'security' => wp_create_nonce( 'search-products' )
							),
						),
						'rule_for_categories_list'         => array(
							'label'    => __( 'Apply rule to:', 'ywdpd' ),
							'type'     => 'ajax-terms',
							'data'     => array(
								'taxonomy'    => 'product_cat',
								'placeholder' => __( 'Search for a category', 'ywdpd' ),
							),
							'multiple' => true,
							'desc'     => __( 'Search the product categories to include in the rule', 'ywdpd' ),
							'deps'     => array(
								'ids'    => '_rule_for',
								'values' => 'specific_categories',
								'type'   => 'hideNow'
							),
						),
						'rule_for_tags_list'               => array(
							'label'    => __( 'Apply rule to:', 'ywdpd' ),
							'type'     => 'ajax-terms',
							'data'     => array(
								'taxonomy'    => 'product_tag',
								'placeholder' => __( 'Search for a tag', 'ywdpd' ),
							),
							'desc'     => __( 'Search the product tags to include in the rule', 'ywdpd' ),
							'multiple' => true,
							'deps'     => array(
								'ids'    => '_rule_for',
								'values' => 'specific_tag',
								'type'   => 'hideNow'
							),
						),
						'apply_to_vendors_list'            => $vendor_options_rule_for,
						'apply_to_brands_list'             => $brand_options_rule_for,
						//APPLY TO EXCLUDE
						'active_exclude'                   => array(
							'label' => __( 'Exclude products from this rule', 'ywdpd' ),
							'desc'  => __( 'Enable if you want to exclude specific products from this rule', 'ywdpd' ),
							'type'  => 'onoff',
							'std'   => 'no',
						),
						'exclude_rule_for'                 => array(
							'label'   => __( 'Exclude', 'ywdpd' ),
							'desc'    => __( 'Choose if you want to exclude some specific products or categories/tags from this rule', 'ywdpd' ),
							'type'    => 'radio',
							'options' => $pricing_rules_options['exclude_rule_for'],
							'std'     => 'specific_products',
							'deps'    => array(
								'ids'    => '_active_exclude',
								'values' => 'yes',
								'type'   => 'hideNow'
							),
						),
						'exclude_rule_for_products_list'   => array(
							'label'    => __( 'Choose which product(s) to exclude', 'ywdpd' ),
							'type'     => 'ajax-products',
							'desc'     => __( 'Search the product(s) to exclude from this rule', 'ywdpd' ),
							'deps'     => array(
								'ids'    => '_exclude_rule_for,_active_exclude',
								'values' => 'specific_products,yes',
								'type'   => 'hideNow'
							),
							'multiple' => true,
							'std'      => array(),
							'data'     => array(
								'action'   => 'woocommerce_json_search_products_and_variations',
								'security' => wp_create_nonce( 'search-products' )
							),
						),
						'exclude_rule_for_categories_list' => array(
							'label'    => __( 'Choose the product categories to exclude', 'ywdpd' ),
							'type'     => 'ajax-terms',
							'data'     => array(
								'taxonomy'    => 'product_cat',
								'placeholder' => __( 'Search for a category', 'ywdpd' ),
							),
							'multiple' => true,
							'desc'     => __( 'Search the product categories to exclude from this rule', 'ywdpd' ),
							'deps'     => array(
								'ids'    => '_exclude_rule_for,_active_exclude',
								'values' => 'specific_categories,yes',
								'type'   => 'hideNow'
							),
						),
						'exclude_rule_for_tags_list'       => array(
							'label'    => __( 'Choose which product tags to exclude', 'ywdpd' ),
							'type'     => 'ajax-terms',
							'data'     => array(
								'taxonomy'    => 'product_tag',
								'placeholder' => __( 'Search for a tag', 'ywdpd' ),
							),
							'desc'     => __( 'Search the product tags to exclude from this rule', 'ywdpd' ),
							'multiple' => true,
							'deps'     => array(
								'ids'    => '_exclude_rule_for,_active_exclude',
								'values' => 'specific_tag,yes',
								'type'   => 'hideNow'
							),
						),
						'apply_to_vendors_list_excluded'   => $vendor_options_exclude_rule_for,
						'apply_to_brands_list_excluded'    => $brand_options_exclude_rule_for,
						'gift_mode'                        => array(
							'label'   => __( 'Offer gift if', 'ywdpd' ),
							'type'    => 'radio',
							'options' => array(
								'cart_item'     => __( 'In cart there is a minimum number of items', 'ywdpd' ),
								'cart_subtotal' => __( 'In cart there are items for a minimum subtotal', 'ywdpd' )
							),
							'std'     => 'cart_item',
							'deps'    => array(
								'ids'    => '_discount_mode',
								'values' => 'gift_products',
								'type'   => 'hideNow'
							),
							'desc'    => __( 'Choose if you want to offer a gift when the cart has a minimum number of items or a minimum subtotal amount', 'ywdpd' )
						),

						'n_items_in_cart' => array(
							'label' => __( 'Offer gifts if total items in cart:', 'ywdpd' ),
							'type'  => 'gift_items_in_cart',
							'deps'  => array(
								'ids'    => '_discount_mode,_gift_mode',
								'values' => 'gift_products,cart_item',
								'type'   => 'hideNow'
							),
							'desc'  => __( 'Set how many items the user has to have in the cart in order to see the gift products', 'ywdpd' ),

						),
						'gift_subtotal'   => array(
							'label' => sprintf( _x( 'Offer gifts if subtotal is higher then (%s)', 'Offer gifts if subtotal is higher then $', 'ywdpd' ), get_woocommerce_currency_symbol() ),
							'type'  => 'number',
							'deps'  => array(
								'ids'    => '_discount_mode,_gift_mode',
								'values' => 'gift_products,cart_subtotal',
								'type'   => 'hideNow'
							),
							'min'   => 1,
							'std'   => 100,
							'desc'  => __( 'Set the minimum subtotal required to offer this gift in cart', 'ywdpd' )
						),

						'rules'                              => array(
							'label'   => __( 'Discount Rules', 'ywdpd' ),
							'desc'    => '',
							'type'    => 'quantity_discount',
							'private' => false,
							'deps'    => array(
								'ids'    => '_discount_mode',
								'values' => 'bulk',
								'type'   => 'hideNow'
							),
						),
						'so-rule'                            => array(
							'label'   => __( 'Set offer rules', 'ywdpd' ),
							'desc'    => __( 'Create the special offer rule for the products you selected', 'ywdpd' ),
							'type'    => 'special_offer_discount',
							'private' => false,
							'deps'    => array(
								'ids'    => '_discount_mode',
								'values' => 'special_offer',
								'type'   => 'hideNow'
							),
						),
						'so-repeat'                          => array(
							'label' => __( 'Repeat', 'ywdpd' ),
							'type'  => 'onoff',
							'desc'  => __( 'Enable this option to repeat the rule. For example, if you offer a 50% discount when purchasing a second product, when the user purchases four products, the rule is applied twice and the user gets a 50% discount on two of the four purchased products.', 'ywdpd' ),
							'std'   => 'no',
							'deps'  => array(
								'ids'    => '_discount_mode',
								'values' => 'special_offer',
								'type'   => 'hideNow'
							)
						),
						'quantity_based'                     => array(
							'label'   => __( 'Quantity rule will check', 'ywdpd' ),
							'desc'    => __( 'Set which parameter to check to apply the quantity rule.', 'ywdpd' ),
							'type'    => 'radio',
							'options' => array(
								'cart_line'                => __( 'Item quantity in cart line', 'ywdpd' ),
								'single_product'           => sprintf( '%s<small>%s</small>', _x( 'Single product quantity in cart - variations NOT counted', 'Single product quantity in cart - Variations of same product ARE NOT counted in this quantity', 'ywdpd' ), _x( 'Variations of same product ARE NOT counted in this quantity', 'Single product quantity in cart - Variations of same product ARE NOT counted in this quantity', 'ywdpd' ) ),
								'single_variation_product' => sprintf( '%s<small>%s</small>', _x( 'Single product quantity in cart - variations counted', 'Single product quantity in cart - Variations of same product ARE counted in this quantity', 'ywdpd' ), _x( 'Variations of same product ARE counted in this quantity', 'Single product quantity in cart - Variations of same product ARE counted in this quantity', 'ywdpd' ) ),
								'cumulative'               => __( 'Total number of products in cart', 'ywdpd' ),
							),
							'std'     => 'cart_line',
							'deps'    => array(
								'ids'    => '_discount_mode',
								'values' => 'bulk,special_offer',
								'type'   => 'hideNow'
							),
						),

						//END APPLY TO OPTIONSet the discount to apply to products from this ruleS
						//APPLY ADJUSTMENT TO OPTIONS
						'active_apply_discount_to'           => array(
							'label' => __( 'Apply discount to a different product or category', 'ywdpd' ),
							'desc'  => __( 'Enable if you want to apply a discount for other products rather than for the products selected for this quantity rule. For example: if a customer purchases 100 business cards (product A), they can get a 10% discount on flyers. (product B)', 'ywdpd' ),
							'type'  => 'onoff',
							'std'   => 'no',
							'deps'  => array(
								'ids'    => '_discount_mode',
								'values' => 'special_offer,bulk',
								'type'   => 'hideNow'
							)
						),
						'rule_apply_adjustment_discount_for' => array(
							'label'   => __( 'Apply discount to', 'ywdpd' ),
							'desc'    => __( 'Choose whether to apply the discounts on all products or specific products/product categories/tags', 'ywdpd' ),
							'type'    => 'radio',
							'options' => $pricing_rules_options['rule_apply_adjustment_discount_for'],
							'std'     => 'all_products',
							'deps'    => array(
								'ids'    => '_active_apply_discount_to',
								'values' => 'yes',
								'type'   => 'hideNow'
							)
						),
						'apply_adjustment_products_list'     => array(
							'label'    => __( 'Choose which product(s) to include', 'ywdpd' ),
							'type'     => 'ajax-products',
							'desc'     => __( 'Search the product(s) to include in this discount', 'ywdpd' ),
							'multiple' => true,
							'deps'     => array(
								'ids'    => '_active_apply_discount_to,_rule_apply_adjustment_discount_for',
								'values' => 'yes,specific_products',
								'type'   => 'hideNow'
							),
							'data'     => array(
								'action'   => 'woocommerce_json_search_products_and_variations',
								'security' => wp_create_nonce( 'search-products' )
							),
						),
						'apply_adjustment_categories_list'   => array(
							'label'    => __( 'Choose which product categories to include', 'ywdpd' ),
							'type'     => 'ajax-terms',
							'data'     => array(
								'taxonomy'    => 'product_cat',
								'placeholder' => __( 'Search for a category', 'ywdpd' ),
							),
							'multiple' => true,
							'desc'     => __( 'Search the product categories to include in the discount', 'ywdpd' ),
							'deps'     => array(
								'ids'    => '_active_apply_discount_to,_rule_apply_adjustment_discount_for',
								'values' => 'yes,specific_categories',
								'type'   => 'hideNow'
							),
						),
						'apply_adjustment_tags_list'         => array(
							'label'    => __( 'Choose which product tags to include', 'ywdpd' ),
							'type'     => 'ajax-terms',
							'data'     => array(
								'taxonomy'    => 'product_tag',
								'placeholder' => __( 'Search for a tag', 'ywdpd' ),
							),
							'desc'     => __( 'Search the product tags to include in the discount', 'ywdpd' ),
							'multiple' => true,
							'deps'     => array(
								'ids'    => '_active_apply_discount_to,_rule_apply_adjustment_discount_for',
								'values' => 'yes,specific_tag',
								'type'   => 'hideNow'
							),
						),
						'apply_adjustment_vendor_list'       => $vendor_options_apply_adjustment_rule_for,
						'apply_adjustment_brands_list'       => $brand_options_apply_adjustment_rule_for,

						'active_apply_adjustment_to_exclude'        => array(
							'label' => __( 'Exclude products from this discount', 'ywdpd' ),
							'desc'  => __( 'Enable if you want to exclude specific products from this discount', 'ywdpd' ),
							'type'  => 'onoff',
							'std'   => 'no',
							'deps'  => array(
								'ids'    => '_active_apply_discount_to',
								'values' => 'yes',
								'type'   => 'hideNow'
							)
						),
						//EXCLUDE PRODUCT FOR APPLY ADJUSTMENT TO
						'exclude_apply_adjustment_rule_for'         => array(
							'label'   => __( 'Exclude', 'ywdpd' ),
							'desc'    => __( 'Choose if you want to exclude some specific products or categories/tags from this rule', 'ywdpd' ),
							'type'    => 'radio',
							'options' => $pricing_rules_options['exclude_apply_adjustment_rule_for'],
							'std'     => 'specific_products',
							'deps'    => array(
								'ids'    => '_active_apply_adjustment_to_exclude',
								'values' => 'yes',
								'type'   => 'hideNow'
							),
						),
						'apply_adjustment_products_list_excluded'   => array(
							'label'    => __( 'Choose which product(s) to exclude', 'ywdpd' ),
							'type'     => 'ajax-products',
							'desc'     => __( 'Search the product(s) to exclude', 'ywdpd' ),
							'multiple' => true,
							'deps'     => array(
								'ids'    => '_active_apply_adjustment_to_exclude,_exclude_apply_adjustment_rule_for',
								'values' => 'yes,specific_products',
								'type'   => 'hideNow'
							),
							'data'     => array(
								'action'   => 'woocommerce_json_search_products_and_variations',
								'security' => wp_create_nonce( 'search-products' )
							),
						),
						'apply_adjustment_categories_list_excluded' => array(
							'label'    => __( 'Choose the product categories to exclude', 'ywdpd' ),
							'type'     => 'ajax-terms',
							'data'     => array(
								'taxonomy'    => 'product_cat',
								'placeholder' => __( 'Search for a category', 'ywdpd' ),
							),
							'multiple' => true,
							'desc'     => __( 'Search the product categories to exclude', 'ywdpd' ),
							'deps'     => array(
								'ids'    => '_active_apply_adjustment_to_exclude,_exclude_apply_adjustment_rule_for',
								'values' => 'yes,specific_categories',
								'type'   => 'hideNow'
							),
						),
						'apply_adjustment_tags_list_excluded'       => array(
							'label'    => __( 'Choose which product tags to exclude', 'ywdpd' ),
							'type'     => 'ajax-terms',
							'data'     => array(
								'taxonomy'    => 'product_tag',
								'placeholder' => __( 'Search for a tag', 'ywdpd' ),
							),
							'desc'     => __( 'Search the product tags to exclude', 'ywdpd' ),
							'multiple' => true,
							'deps'     => array(
								'ids'    => '_active_apply_adjustment_to_exclude,_exclude_apply_adjustment_rule_for',
								'values' => 'yes,specific_tag',
								'type'   => 'hideNow'
							),
						),
						'apply_adjustment_vendor_list_excluded'     => $vendor_options_apply_exclude_adjustment_rule_for,
						'apply_adjustment_brands_list_excluded'     => $brand_options_apply_exclude_adjustment_rule_for,

						//END EXCLUSION OPTIONS
						//END APPLY ADJUSTMENT TO OPTIONS
						//TABLE OPTIONS
						'show_table_price'                          => array(
							'label' => __( 'Show quantity & prices in a table', 'ywdpd' ),
							'desc'  => __( 'Enable if you want to show the quantity and the prices in a table in the product pages.', 'ywdpd' ),
							'type'  => 'onoff',
							'std'   => 'no',
							'deps'  => array(
								'ids'    => '_discount_mode',
								'values' => 'bulk',
								'type'   => 'hideNow'
							),
						),
						'show_in_loop'                              => array(
							'label' => __( 'Show discount in loop', 'ywdpd' ),
							'desc'  => __( 'Enable if you want to show the discounted price in the loop', 'ywdpd' ),
							'type'  => 'onoff',
							'std'   => 'yes',
							'deps'  => array(
								'ids'    => '_discount_mode',
								'values' => 'bulk,discount_whole,category_discount',
								'type'   => 'hideNow'
							),
						),
						/***************
						 * USER RULES
						 */

						'user_rules'                    => array(
							'label'   => __( 'Apply discount to', 'ywdpd' ),
							'desc'    => __( 'Choose to apply the rule to all users or only specific user roles', 'ywdpd' ),
							'type'    => 'radio',
							'options' => apply_filters( 'ywdpdp_price_rule_user_options', array(
									'everyone'       => __( 'All users', 'ywdpd' ),
									'customers_list' => __( 'Only to specific users', 'ywdpd' ),
									'role_list'      => __( 'Only to specific user roles', 'ywdpd' ),
								)
							),
							'std'     => 'everyone',
						),
						'user_rules_role_list'          => array(
							'label'    => __( 'User roles included', 'ywdpd' ),
							'desc'     => __( 'Search the user roles you want to include in this rule', 'ywdpd' ),
							'type'     => 'select',
							'class'    => 'wc-enhanced-select',
							'multiple' => true,
							'options'  => ywdpd_get_roles(),
							'std'      => array(),
							'deps'     => array(
								'ids'    => '_user_rules',
								'values' => 'role_list',
								'type'   => 'hideNow'
							),
						),
						'user_rules_customers_list'     => array(
							'label'       => __( 'Users included', 'ywdpd' ),
							'type'        => 'customers',
							'desc'        => __( 'Search the users you want to include in the rule', 'ywdpd' ),
							'placeholder' => __( 'Select users', 'ywdpd' ),
							'deps'        => array(
								'ids'    => '_user_rules',
								'values' => 'customers_list',
								'type'   => 'hideNow'
							),
						),
						'user_rules_memberships_list'   => $membership_plan_included,
						'enable_user_rule_exclude'      => array(
							'label' => __( 'Exclude users from this discount', 'ywdpd' ),
							'desc'  => __( 'Enable if you want to exclude specific users from this discount', 'ywdpd' ),
							'type'  => 'onoff',
							'std'   => 'no',
							'deps'  => array(
								'ids'    => '_user_rules',
								'values' => 'everyone,role_list,specific_membership',
								'type'   => 'hideNow'

							)
						),
						'user_rule_exclude'             => array(
							'label'   => __( 'Exclude', 'ywdpd' ),
							'desc'    => __( 'Choose if you want to exclude from this rule certain users or users with a specific role.', 'ywdpd' ),
							'type'    => 'radio',
							'options' => apply_filters( 'ywdpdp_price_rule_user_exclude_options', array(
									'specific_customers' => __( 'Specific users', 'ywdpd' ),
									'specific_roles'     => __( 'Specific user roles', 'ywdpd' )
								)
							),
							'std'     => 'specific_customers',
							'deps'    => array(
								'ids'    => '_enable_user_rule_exclude',
								'values' => 'yes',
								'type'   => 'hideNow'
							)
						),
						'user_rules_role_list_excluded' => array(
							'label'    => __( 'Choose which user role(s) to exclude', 'ywdpd' ),
							'desc'     => __( 'Search the user roles you want to exclude from this rule', 'ywdpd' ),
							'type'     => 'select',
							'class'    => 'wc-enhanced-select',
							'multiple' => true,
							'options'  => ywdpd_get_roles(),
							'std'      => array(),
							'deps'     => array(
								'ids'    => '_enable_user_rule_exclude,_user_rule_exclude',
								'values' => 'yes,specific_roles',
								'type'   => 'hideNow'
							),
						),

						'user_rules_customers_list_excluded'   => array(
							'label'       => __( 'Choose which user(s) to exclude', 'ywdpd' ),
							'type'        => 'customers',
							'desc'        => __( 'Search the users you want to excluded the rule', 'ywdpd' ),
							'placeholder' => __( 'Select user', 'ywdpd' ),
							'deps'        => array(
								'ids'    => '_enable_user_rule_exclude,_user_rule_exclude',
								'values' => 'yes,specific_customers',
								'type'   => 'hideNow'
							),
						),
						'user_rules_excluded_memberships_list' => $membership_plan_excluded,
						//END USER OPTIONS
						//OTHER SETTINGS
						'schedule_discount_mode'               => array(
							'label' => __( 'Schedule rule', 'ywdpd' ),
							'type'  => 'schedule_rules',

							'desc' => __( 'Choose whether to schedule a start and end time for this rule', 'ywdpd' ),
							'std'  => 'no'
						),
						'disable_on_sale'                      => array(
							'label' => __( "Disable this rule for 'on-sale products'", 'ywdpd' ),
							'desc'  => __( 'Enable if you want to disable this discount for  \'on-sale products\'', 'ywdpd' ),
							'type'  => 'onoff',
							'std'   => 'yes',
							'deps'  => array(
								'ids'    => '_discount_mode',
								'values' => 'bulk,special_offer,discount_whole,category_discount',
								'type'   => 'hideNow'
							),
						),
						'no_apply_with_other_rules'            => array(
							'label' => __( 'Disable other rules with lower priority', 'ywdpd' ),
							'desc'  => __( 'Enable if you want to disable other rules with lower priority applied to same products', 'ywdpd' ),
							'type'  => 'onoff',
							'std'   => 'yes',
							'deps'  => array(
								'ids'    => '_discount_mode',
								'values' => 'bulk,special_offer,exclude_items,discount_whole,category_discount',
								'type'   => 'hideNow'
							),
						),
						'disable_with_other_coupon'            => array(
							'label' => __( 'Disable when a coupon has been applied', 'ywdpd' ),
							'desc'  => __( 'Enable if you want to disable this rule if the user has applied a coupon code', 'ywdpd' ),
							'type'  => 'onoff',
							'std'   => 'no',
							'deps'  => array(
								'ids'    => '_discount_mode',
								'values' => 'bulk,special_offer,discount_whole,category_discount',
								'type'   => 'hideNow'
							),
						),
						'can_special_offer_in_popup'           => array(
							'label' => __( 'Promote offer in modal window', 'ywdpd' ),
							'type'  => 'onoff',
							'std'   => 'no',
							'desc'  => __( 'Enable to show a modal window after the user adds the product to the cart (from product page only). The modal window will display the other products linked to the offer and will only appear if there\'s at least one special offer set up for the current product.', 'ywdpd' ),
							'deps'  => array(
								'ids'    => '_discount_mode,_active_apply_discount_to',
								'values' => 'special_offer,yes',
								'type'   => 'hideNow'
							)
						),
						'text_in_modal_special_offer'          => array(
							'label' => __( 'Text to show in modal window', 'ywdpd' ),
							'desc'  => sprintf( __( 'Enter a custom text that will be shown in the modal window to describe this offer. Use %s to show the number of products dynamically.', 'ywdpd' ), "<code>{{total_to_add}}</code>" ),
							'type'  => 'text',
							'std'   =>  __( 'Get a special discount if you add {{total_to_add}} product(s) to your order.', 'ywdpd' ),
							'deps'  => array(
								'ids'    => '_discount_mode,_can_special_offer_in_popup',
								'values' => 'special_offer,yes',
								'type'   => 'hideNow'
							)
						),
						'text_in_modal_gift'                   => array(
							'label' => __( 'Text to show in modal window', 'ywdpd' ),
							'desc'  => sprintf( __( 'Enter a custom text that will be shown in the modal window to describe this offer. Use %s to show the number of products dynamically.', 'ywdpd' ), "<code>{{total_to_add}}</code>" ),
							'type'  => 'text',
							'std'   => __( 'You can add {{total_to_add}} product(s) for free!', 'ywdpd' ),
							'deps'  => array(
								'ids'    => '_discount_mode',
								'values' => 'gift_products',
								'type'   => 'hideNow'
							)
						),
						/***************
						 * NOTES
						 */
						'table_note_apply_to'                  => array(
							'label'   => __( 'Add a custom message in product with quantity rule', 'ywdpd' ),
							'desc'    => __( 'Enter a custom text to show in the product page where this quantity rule is applied', 'ywdpd' ),
							'type'    => 'textarea-editor',
							'wpautop' => false,
							'deps'    => array(
								'ids'    => '_discount_mode',
								'values' => 'bulk,special_offer,gift_products,discount_whole,category_discount',
								'type'   => 'hideNow'
							),
						),

						'table_note_adjustment_to' => array(
							'label'   => __( 'Add a custom message in products where the discount is applied', 'ywdpd' ),
							'desc'    => __( 'Enter a custom text to show in the product page where the discount is applied', 'ywdpd' ),
							'type'    => 'textarea-editor',
							'wpautop' => false,
							'deps'    => array(
								'ids'    => '_active_apply_discount_to',
								'values' => 'yes',
								'type'   => 'hideNow'
							),
						),
						'table_note'               => array(
							'label'   => __( 'Add extra notices in quantity table', 'ywdpd' ),
							'desc'    => __( 'Enter a custom text to show under the quantity table', 'ywdpd' ),
							'type'    => 'textarea-editor',
							'wpautop' => false,
							'deps'    => array(
								'ids'    => '_discount_mode',
								'values' => 'bulk',
								'type'   => 'hideNow'
							),
						),

						'table_note_gift_adjustment_to' => array(
							'label'   => __( 'Add a custom message in products offered as gift', 'ywdpd' ),
							'desc'    => __( 'Enter a custom text to show in the products offered as gift', 'ywdpd' ),
							'type'    => 'textarea-editor',
							'wpautop' => false,
							'deps'    => array(
								'ids'    => '_discount_mode',
								'values' => 'gift_products',
								'type'   => 'hideNow'
							),
						),
						'separator'                     => array(
							'type' => 'sep'
						),
						'save_rule'                     => array(
							'type' => 'save_rule',
						)
					)
				),

			),
		),
	)
);
