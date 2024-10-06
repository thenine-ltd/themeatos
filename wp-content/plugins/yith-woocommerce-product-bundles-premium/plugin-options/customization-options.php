<?php
$settings = array(

	'customization' => array(

		'customization-options' => array(
			'title' => __( 'Customization', 'yith-woocommerce-product-bundles' ),
			'type'  => 'title',
			'desc'  => '',
			'id'    => 'yith-wcpb-customization-options',
		),

		'pip-bundle-pricing' => array(
			'id'        => 'yith-wcpb-pip-bundle-pricing',
			'name'      => __( 'Bundle price style in Shop', 'yith-woocommerce-product-bundles' ),
			'type'      => 'yith-field',
			'yith-type' => 'select-images',
			'options'   => array(
				'min-max'                => array(
					'label' => __( 'Min - Max', 'yith-woocommerce-product-bundles' ),
					'image' => YITH_WCPB_ASSETS_URL . '/images/pip-price-min-max.jpg',
				),
				'min'                    => array(
					'label' => __( 'Min only', 'yith-woocommerce-product-bundles' ),
					'image' => YITH_WCPB_ASSETS_URL . '/images/pip-price-min.jpg',
				),
				'from-min'               => array(
					'label' => __( 'Min only higher than', 'yith-woocommerce-product-bundles' ),
					'image' => YITH_WCPB_ASSETS_URL . '/images/pip-price-from-min.jpg',
				),
				'regular-and-discounted' => array(
					'label' => __( 'Regular and discounted', 'yith-woocommerce-product-bundles' ),
					'image' => YITH_WCPB_ASSETS_URL . '/images/pip-price-regular-and-discounted.jpg',
				),
			),
			'desc'      => implode( '<br />', array(
				__( 'Choose the price view for bundle products in shop pages.', 'yith-woocommerce-product-bundles' ),
				__( 'Please note: this option is available only for bundle products with the "Use prices of bundled items" option enabled.', 'yith-woocommerce-product-bundles' ),
			) ),
			'default'   => 'from-min',
		),

		'bundled-items-heading' => array(
			'id'        => 'yith-wcpb-bundled-items-heading',
			'name'      => __( "Bundles' title", 'yith-woocommerce-product-bundles' ),
			'type'      => 'yith-field',
			'yith-type' => 'text',
			'desc'      => __( 'Enter an optional title to be shown in your bundles, before the list of bundled items.', 'yith-woocommerce-product-bundles' ),
			'default'   => '',
		),

		'add-to-cart-label' => array(
			'id'                => 'yith-wcpb-add-to-cart-label',
			'name'              => __( 'Add to cart label', 'yith-woocommerce-product-bundles' ),
			'type'              => 'yith-field',
			'yith-type'         => 'text',
			'desc'              => __( 'Enter the label for the add to cart button.', 'yith-woocommerce-product-bundles' ),
			'default'           => '',
			'custom_attributes' => ' placeholder="' . yith_wcpb_settings()->get_empty_value( 'yith-wcpb-add-to-cart-label' ) . '" ',
		),

		'add-item-to-bundle-label' => array(
			'id'                => 'yith-wcpb-add-item-to-bundle-label',
			'name'              => __( '"Add item to bundle" label', 'yith-woocommerce-product-bundles' ),
			'type'              => 'yith-field',
			'yith-type'         => 'text',
			'desc'              => __( 'Enter the text for the checkbox to add optional items to bundle.', 'yith-woocommerce-product-bundles' ),
			'default'           => '',
			'custom_attributes' => ' placeholder="' . yith_wcpb_settings()->get_empty_value( 'yith-wcpb-add-item-to-bundle-label' ) . '" ',
		),

		'add-item-to-bundle-for-label' => array(
			'id'                => 'yith-wcpb-add-item-to-bundle-for-label',
			'name'              => __( '"Add item to bundle for ..." label', 'yith-woocommerce-product-bundles' ),
			'type'              => 'yith-field',
			'yith-type'         => 'text',
			'desc'              => implode( '<br />', array(
				__( 'Enter the text for the checkbox to add optional items to bundle, with the item price.', 'yith-woocommerce-product-bundles' ),
				// translators: %s is the placeholder for the '%s' string
				sprintf( __( 'Use the %s placeholder to show the bundled item price.', 'yith-woocommerce-product-bundles' ), '<code>%s</code>' ),
			) ),
			'default'           => '',
			'custom_attributes' => ' placeholder="' . yith_wcpb_settings()->get_empty_value( 'yith-wcpb-add-item-to-bundle-for-label' ) . '" ',
		),

		'customization-options-end' => array(
			'type' => 'sectionend',
		),

	),
);

return apply_filters( 'yith_wcpb_panel_customization_options', $settings );