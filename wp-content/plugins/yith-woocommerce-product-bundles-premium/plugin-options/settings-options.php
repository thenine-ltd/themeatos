<?php

$price_sync_url = wp_nonce_url( add_query_arg( array(
												   'yith_wcpb_force_sync_bundle_products' => '1',
												   'yith_wcpb_redirect'                   => urlencode( admin_url( 'admin.php?page=yith_wcpb_panel' ) ),
											   ), admin_url() ), 'yith-wcpb-sync-pip-prices' );

$quick_view_url  = "https://yithemes.com/themes/plugins/yith-woocommerce-quick-view/";
$quick_view_name = "YITH WooCommerce Quick View";

$settings = array(

	'settings' => array(

		'general-options' => array(
			'title' => _x( 'General Settings', 'Panel Section Title', 'yith-woocommerce-product-bundles' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'yith-wcpb-general-options',
		),

		'show-bundled-items-in-report' => array(
			'id'        => 'yith-wcpb-show-bundled-items-in-report',
			'name'      => __( 'Show bundled items in WooCommerce Reports', 'yith-woocommerce-product-bundles' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __( 'Enable to show also the bundled items in WooCommerce Reports.', 'yith-woocommerce-product-bundles' ),
			'default'   => 'no',
		),

		'hide-bundled-items-in-cart' => array(
			'id'        => 'yith-wcpb-hide-bundled-items-in-cart',
			'name'      => __( 'Hide bundled items in cart and checkout', 'yith-woocommerce-product-bundles' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __( 'Enable to hide the bundled items in cart and checkout.', 'yith-woocommerce-product-bundles' ),
			'default'   => 'no',
		),

		'show-bundled-item-prices' => array(
			'id'        => 'yith-wcpb-show-bundled-item-prices',
			'name'      => __( 'Show bundled item prices in cart and checkout', 'yith-woocommerce-product-bundles' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => implode( '<br />', array(
				__( 'Enable to show bundled item prices in cart and checkout.', 'yith-woocommerce-product-bundles' ),
				__( 'Please note: this option is available only for bundle products with the "Use prices of bundled items" option enabled.', 'yith-woocommerce-product-bundles' ),
			) ),
			'default'   => 'no',
		),

		'pip-bundle-order-pricing' => array(
			'id'        => 'yith-wcpb-pip-bundle-order-pricing',
			'name'      => __( 'In the order, show', 'yith-woocommerce-product-bundles' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'price-in-bundle'        => __( 'Price of bundle', 'yith-woocommerce-product-bundles' ),
				'price-in-bundled-items' => __( 'Price of bundled items', 'yith-woocommerce-product-bundles' ),
			),
			'desc'      => implode( '<br />', array(
				__( 'Choose which price to show in order details.', 'yith-woocommerce-product-bundles' ),
				__( 'Please note: this option is available only for bundle products with the "Use prices of bundled items" option enabled.', 'yith-woocommerce-product-bundles' ),
			) ),
			'default'   => 'price-in-bundle',
		),

		'out-of-stock-bundle' => array(
			'id'        => 'yith-wcpb-out-of-stock-bundle',
			'name'      => __( 'If a bundled product is out of stock', 'yith-woocommerce-product-bundles' ),
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'hide'             => __( 'Hide the bundle', 'yith-woocommerce-product-bundles' ),
				'set-out-of-stock' => __( 'Set the bundle as Out of Stock', 'yith-woocommerce-product-bundles' ),
				'show'             => __( 'Show the bundle, but users will not be able to buy it', 'yith-woocommerce-product-bundles' ),
			),
			'desc'      => __( 'Choose how to manage bundles when an item in the bundle is out of stock.', 'yith-woocommerce-product-bundles' ),
			'default'   => yith_wcpb_settings()->get_default( 'yith-wcpb-out-of-stock-bundle' ),
		),

		'photoswipe-for-bundled-images' => array(
			'id'        => 'yith-wcpb-photoswipe-for-bundled-images',
			'name'      => __( 'Activate PhotoSwipe for bundled images', 'yith-woocommerce-product-bundles' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'desc'      => __( 'Enable to use PhotoSwipe to open bundled image gallery. It requires PhotoSwipe is enabled.', 'yith-woocommerce-product-bundles' ),
			'default'   => 'yes',
		),

		'general-options-end' => array(
			'type' => 'sectionend',
		),

		'quick-view-integration-options' => array(
			// translators: %s is the name of the plugin
			'title' => sprintf( _x( '%s integration', 'Panel Section Title', 'yith-woocommerce-product-bundles' ), $quick_view_name ),
			'type'  => 'title',
			'desc'  => defined( 'YITH_WCQV' ) ? '' : implode( ' ', array(
				// translators: %s is the name of the plugin
				sprintf( __( 'In order to use this integration you have to install and activate %s.', 'yith-woocommerce-product-bundles' ), $quick_view_name ),
				"<a href='{$quick_view_url}'>" . _x( 'Learn more', 'Learn more link for plugin integrations', 'yith-woocommerce-product-bundles' ) . "</a>",
			) ),
			'id'    => 'yith-wcpb-quick-view-integration-options',
		),

		'quick-view-for-bundled-items' => array(
			'id'        => 'yith-wcpb-quick-view-for-bundled-items',
			'name'      => __( 'Open bundled items in quick view', 'yith-woocommerce-product-bundles' ),
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
			'class'     => defined( 'YITH_WCQV' ) ? '' : 'yith-disabled',
		),

		'quick-view-integration-options-end' => array(
			'type' => 'sectionend',
		),

		'tools-options' => array(
			'title' => _x( 'Tools', 'Panel Section Title', 'yith-woocommerce-product-bundles' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'yith-wcpb-tools-options',
		),

		'pip-bundle-force-price-sync' => array(
			'name'             => __( 'Sync prices for bundle products', 'yith-woocommerce-product-bundles' ),
			'type'             => 'yith-field',
			'yith-type'        => 'html',
			'yith-display-row' => true,
			'html'             => "<a href='$price_sync_url' class='yith-update-button'>" . __( 'Sync prices', 'yith-woocommerce-product-bundles' ) . "</a>",
			'desc'             => implode( '<br />', array(
				__( 'Sync the bundle prices when the "Use prices of bundled items" is enabled', 'yith-woocommerce-product-bundles' ),
				__( 'Use it ONLY if you are encountering issues with sorting prices in the shop.', 'yith-woocommerce-product-bundles' ),
			) ),
		),

		'tools-options-end' => array(
			'type' => 'sectionend',
		),

	),
);

return apply_filters( 'yith_wcpb_panel_settings_options', $settings );