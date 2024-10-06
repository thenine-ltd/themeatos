<?php
/**
 * General options
 *
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.0.0
 * @version 1.6.0
 * @author  YITH
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWDPD_VERSION' ) ) {
	exit; // Exit if accessed directly.
}
$settings_options = array(
	'general' => array(

		'general_settings_start'                => array(
			'name' => __( 'General Settings', 'ywdpd' ),
			'type' => 'title'
		),
		'general_enable_shop_manager'           => array(
			'id'        => 'ywdpd_enable_shop_manager',
			'name'      => __( 'Allow Shop Manager to manage dynamic pricing and discount', 'ywdpd' ),
			'desc'      => __( 'If enabled, the shop manager can edit the plugin options', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),
		'general_price_format'                  => array(
			'id'                => 'ywdpd_price_format',
			'name'              => __( 'Price format', 'ywdpd' ),
			'desc'              => sprintf( '%s <p><strong>%s</strong></p><br/>&#37;original_price&#37;,&#37;discounted_price&#37;,&#37;percentual_discount&#37;', _x( 'Enter the price format to show the original price, the percentual discount or discounted price.', '[Price Format Option description] Enter the price format to show the original price, the percentual discount or discounted price.
Placeholder available:
%original_price%,%discounted_price%,%percentual_discount%', 'ywdpd' ), _x( 'Placeholder available:', 'Enter the price format to show the original price, the percentual discount or discounted price.
Placeholder available:
%original_price%,%discounted_price%,%percentual_discount%', 'ywdpd' ) ),
			'type'              => 'yith-field',
			'yith-type'         => 'textarea',
			'custom_attributes' => 'style="resize:none;"',
			'rows'              => 2,
			'default'           => '<del>%original_price%</del> %discounted_price%',
		),
		'general_settings_end'                  => array(
			'type' => 'sectionend'
		),
		'product_page_section_start'            => array(
			'name' => __( 'Product page settings', 'ywdpd' ),
			'type' => 'title'
		),
		'product_page_show_message'             => array(
			'id'        => 'ywdpd_show_note_on_products',
			'name'      => __( 'Show the discount custom messages', 'ywdpd' ),
			'desc'      => __( 'For each rule, you can enter a custom message. You can use this option to show or hide all the custom messages on product pages.', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),
		'product_page_message_position'         => array(
			'id'        => 'ywdpd_show_note_on_products_place',
			'name'      => __( 'Message position', 'ywdpd' ),
			'desc'      => __( 'Choose where to show the discount rules messages', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'select',
			'class'     => 'wc-enhanced-select',
			'options'   => array(
				'before_add_to_cart' => __( 'Before "Add to cart" button', 'ywdpd' ),
				'after_add_to_cart'  => __( 'After "Add to cart" button', 'ywdpd' ),
				'before_excerpt'     => __( 'Before excerpt', 'ywdpd' ),
				'after_excerpt'      => __( 'After excerpt', 'ywdpd' ),
				'after_meta'         => __( 'After product meta', 'ywdpd' ),

			),
			'default'   => 'before_add_to_cart',
			'deps'      => array(
				'id'    => 'ywdpd_hide_note_on_products',
				'value' => 'no',
				'type'  => 'hide'
			),
		),
		'product_page_show_qty_table'           => array(
			'id'        => 'ywdpd_show_quantity_table',
			'name'      => __( 'Show quantity tables', 'ywdpd' ),
			'desc'      => __( 'Enable to show a quantity table in all products with a quantity discount rule. Disable to hide all tables.','ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),
		'product_page_qty_style'                => array(
			'id'        => 'ywdpd_quantity_table_orientation',
			'name'      => __( 'Quantity table layout', 'ywdpd' ),
			'desc'      => __( 'Choose the layout for quantity table', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'vertical'   => __( 'Vertical', 'ywdpd' ),
				'horizontal' => __( 'Horizontal', 'ywdpd' ),
			),
			'default'   => 'horizontal',
			'deps'      => array(
				'id'    => 'ywdpd_show_quantity_table',
				'value' => 'yes',
				'type'  => 'hide'
			),
		),
		'product_page_qty_position'             => array(
			'id'        => 'ywdpd_show_quantity_table_place',
			'name'      => __( 'Table position', 'ywdpd' ),
			'desc'      => __( 'Choose where to show the quantity table on product pages', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'select',
			'class'     => 'wc-enhanced-select',
			'options'   => array(
				'before_add_to_cart' => __( 'Before "Add to cart" button', 'ywdpd' ),
				'after_add_to_cart'  => __( 'After "Add to cart" button', 'ywdpd' ),
				'before_excerpt'     => __( 'Before excerpt', 'ywdpd' ),
				'after_excerpt'      => __( 'After excerpt', 'ywdpd' ),
				'after_meta'         => __( 'After product meta', 'ywdpd' ),

			),
			'default'   => 'before_add_to_cart',
			'deps'      => array(
				'id'    => 'ywdpd_show_quantity_table',
				'value' => 'yes',
				'type'  => 'hide'
			),
		),
		'product_page_qty_title'                => array(
			'id'        => 'ywdpd_show_quantity_table_label',
			'name'      => __( 'Quantity table title', 'ywdpd' ),
			'desc'      => __( 'Enter a text to identify the quantity table title', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'default'   => __( 'Quantity discount', 'ywdpd' ),
			'deps'      => array(
				'id'    => 'ywdpd_show_quantity_table',
				'value' => 'yes',
				'type'  => 'hide'
			),
		),
		'product_page_qty_labels'               => array(
			'id'        => 'ywdpd_quantity_table_labels',
			'name'      => __( 'Table labels', 'ywdpd' ),
			'desc'      => __( 'Enter the labels for Quantity and Price table.', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'fields'    => array(
				'quantity' => array(
					'label'             => __( 'Label for quantity', 'ywdpd' ),
					'type'              => 'text',
					'default'           => __( 'Quantity', 'ywdpd' ),
					'custom_attributes' => 'style="width:200px!important"'
				),
				'price'    => array(
					'label'             => __( 'Label for price', 'ywdpd' ),
					'type'              => 'text',
					'default'           => __( 'Price', 'ywdpd' ),
					'custom_attributes' => 'style="width:200px!important"'
				)
			),
			'deps'      => array(
				'id'    => 'ywdpd_show_quantity_table',
				'value' => 'yes',
				'type'  => 'hide'
			)
		),
		'product_page_expire_date'              => array(
			'id'        => 'ywdpd_show_quantity_table_schedule',
			'name'      => __( 'Show the end date of discount in quantity table', 'ywdpd' ),
			'desc'      => __( 'Enable to show when the discount ends in quantity table', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
			'deps'      => array(
				'id'    => 'ywdpd_show_quantity_table',
				'value' => 'yes',
				'type'  => 'hide'
			)
		),
		'product_page_show_as_default'          => array(
			'id'        => 'ywdpd_show_as_default',
			'name'      => __( 'Show as default', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'min' => __( 'The first discount rule (usually the minimum discount)', 'ywdpd' ),
				'max' => __( 'The last discount rule (usually the maximum discount)', 'ywdpd' )
			),
			'default'   => 'min',
			'desc'      => __( 'Choose whether to show the minimum or the maximum discount available as default price in product page and loop', 'ywdpd' ),
			'deps'      => array(
				'id'    => 'ywdpd_show_quantity_table',
				'value' => 'yes',
				'type'  => 'hide'
			)
		),
		'product_page_default_price_selected'   => array(
			'id'        => 'ywdpd_default_qty_selected',
			'name'      => __( 'Select default quantity in the table', 'ywdpd' ),
			'desc'      => __( 'Enable to automatically select the first quantity rule in the table', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
			'deps'      => array(
				'id'    => 'ywdpd_show_quantity_table',
				'value' => 'yes',
				'type'  => 'hide'
			)
		),
		'product_page_change_price_dynamically' => array(
			'id'        => 'ywdpd_update_price_on_qty',
			'name'      => __( 'Change product price when user changes quantity', 'ywdpd' ),
			'desc'      => __( 'Enable to show an updated price when the user changes the quantity', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
			'deps'      => array(
				'id'    => 'ywdpd_show_quantity_table',
				'value' => 'yes',
				'type'  => 'hide'
			)
		),

		'product_page_section_end'        => array(
			'type' => 'sectionend'
		),
		'cart_section_start'              => array(
			'type' => 'title',
			'name' => __( 'Cart settings', 'ywdpd' )
		),
		'cart_coupon_setting'             => array(
			'id'        => 'ywdpd_coupon_label',
			'name'      => __( 'Coupon Label', 'ywdpd' ),
			'desc'      => __( 'Enter a text to identify the coupon in cart, when a discount is applied', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'default'   => 'DISCOUNT',
		),
		'cart_coupon_calculation_setting' => array(
			'id'        => 'ywdpd_calculate_discounts_tax',
			'name'      => __( 'Calculate cart discount starting from', 'ywdpd' ),
			'desc'      => __( 'Choose to calculate the cart discount including or excluding tax on the subtotal', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'select',
			'class'     => 'wc-enhanced-select',
			'options'   => array(
				'tax_excluded' => __( 'Subtotal - tax excluded', 'ywdpd' ),
				'tax_included' => __( 'Subtotal - tax included', 'ywdpd' ),
			),
			'default'   => 'tax_excluded',
		),
		'cart_show_subtotal_mode'         => array(
			'id'        => 'ywdp_cart_special_offer_show_subtotal_mode',
			'name'      => __( 'Show special offers in cart by adapting', 'ywdpd' ),
			'desc'      => __( 'Choose how to show the discounts or special prices in cart', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'unit_price' => sprintf( __( 'The unit price %sProduct price will be adapted based on the offer applied%s', 'ywdpd' ), '<small>', '</small>' ),
				'subtotal'   => sprintf( __( 'The subtotal %sThe product price will not be adapted, but the price rule will be applied and shown to the subtotal only%s', 'ywdpd' ), '<small>', '</small>' )
			),
			'default'   => 'unit_price'
		),
		'cart_notices'                    => array(
			'id'        => 'ywdpd_enable_cart_notices',
			'name'      => __( 'Show amount discount info on cart', 'ywdpd' ),
			'desc'      => __( 'Select if you want to show a notice when a discount rule is applied', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no'
		),
		'discount_message_on_cart'        => array(
			'id'                => 'ywdpd_cart_notice_message',
			'name'              => __( 'Add a custom message in the cart totals', 'ywdpd' ),
			'desc'              => __( 'Enter a custom text to show the total discount for the customer on the cart. Please, note, this applies only to "Cart discount rules". You can use the placeholder %total_discount_percentage% to show the discount in percentage, %total_discount_price% to show the discount value', 'ywdpd' ),
			'type'              => 'yith-field',
			'yith-type'         => 'textarea',
			'deps'              => array(
				'id'    => 'ywdpd_enable_cart_notices',
				'value' => 'yes',
				'type'  => 'hide'
			),
			'default'           => __( 'Please note: you\'ve saved %total_discount_percentage% on this order today', 'ywdpd' ),
			'custom_attributes' => 'style="resize:none;"',
		),
		'cart_section_end'                => array(
			'type' => 'sectionend'
		),
		'wpml_section_start'              => array(
			'type' => 'title',
			'name' => __( 'WPML settings', 'ywdpd' )
		),
		'wpml_extension'                  => array(
			'id'        => 'ywdpd_wpml_extend_to_translated_object',
			'name'      => __( 'Extend the rules to translated contents', 'ywdpd' ),
			'desc'      => __( 'If enabled the rules will be applied also to translated products', 'ywdpd' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
			'class' =>! defined( 'ICL_SITEPRESS_VERSION') ? 'yith-disabled'  : ''
		),
		'wpml_section_end'                => array(
			'type' => 'sectionend'
		)
	)
);


return apply_filters( 'yith_ywdpd_panel_settings_options', $settings_options );
