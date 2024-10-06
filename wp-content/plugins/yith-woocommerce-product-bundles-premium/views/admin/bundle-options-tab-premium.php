<?php
/**
 * Admin Bundle Options TAB
 *
 * @var WC_Product_YITH_Bundle|false $bundle_product The bundle product or false (if it's not a bundle product)
 * @var WC_Product                   $product_object The product object
 */

defined( 'ABSPATH' ) || exit;

$bundle_id = $product_object->get_id();

$bundle_data = $product_object->get_meta( '_yith_wcpb_bundle_data', true, 'edit' );
$bundle_data = ! ! $bundle_data ? $bundle_data : array();

$bundled_items = $bundle_product ? $bundle_product->get_bundled_items() : array();

$per_item_pricing     = 'yes' === $product_object->get_meta( '_yith_wcpb_per_item_pricing', true, 'edit' ) ? 'yes' : 'no';
$non_bundled_shipping = 'yes' === $product_object->get_meta( '_yith_wcpb_non_bundled_shipping', true, 'edit' ) ? 'yes' : 'no';
$show_saving_amount   = 'yes' === $product_object->get_meta( '_yith_wcpb_show_saving_amount', true, 'edit' ) ? 'yes' : 'no';

$advanced_options                 = $product_object->get_meta( '_yith_wcpb_bundle_advanced_options', true, 'edit' );
$advanced_options                 = ! ! $advanced_options ? $advanced_options : array();
$default_advanced_options         = array(
	'min'          => 0,
	'max'          => 0,
	'min_distinct' => 0,
	'max_distinct' => 0,
);
$advanced_options                 = wp_parse_args( $advanced_options, $default_advanced_options );
$advanced_options                 = array_combine( array_keys( $advanced_options ), array_map( 'absint', array_values( $advanced_options ) ) );
$limit_product_selections_enabled = array_sum( array_values( $advanced_options ) ) > 0 ? 'yes' : 'no';

$items_with_qty = array();
foreach ( $bundled_items as $bundled_item ) {
	$_product_id = $bundled_item->get_product_id();
	if ( isset( $items_with_qty[ $_product_id ] ) ) {
		$items_with_qty[ $_product_id ] ++;
	} else {
		$items_with_qty[ $_product_id ] = 1;
	}
}

?>
<div id="yith_bundle_product_data" class="panel woocommerce_options_panel wc-metaboxes-wrapper yith-plugin-ui" data-items-with-qty="<?php echo esc_attr( json_encode( $items_with_qty ) ); ?>">

	<div class="yith-wcpb-bundle-options-section">

		<div class="yith-wcpb-bundle-options-section__title">
			<h3><?php esc_attr_e( 'Bundled items', 'yith-woocommerce-product-bundles' ); ?></h3>
			<span id="yith-wcpb-bundled-items-expand-collapse" class="yith-wcpb-expand-collapse">
				<a href="#" class="close_all"><?php esc_attr_e( 'Close all', 'yith-woocommerce-product-bundles' ); ?></a>
				<a href="#" class="expand_all"><?php esc_attr_e( 'Expand all', 'yith-woocommerce-product-bundles' ); ?></a>
			</span>
		</div>


		<div class="yith-wcpb-bundle-options-section__content">

			<div id="yith-wcpb-bundled-items__actions" class="yith-wcpb-bundled-items__actions">
				<div id="yith-wcpb-bundled-items__actions__hero-icon" class="yith-wcpb-bundled-items__actions__show-if-hero">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 82.5 80.23">
						<defs>
							<style>
                                .cls-1, .cls-2 {
                                    fill            : none;
                                    stroke          : currentColor;
                                    stroke-linecap  : round;
                                    stroke-linejoin : round;
                                    stroke-width    : 2.5px;
                                }

                                .cls-2 {
                                    stroke-dasharray : 0.51 6.14;
                                }
							</style>
						</defs>
						<g>
							<g>
								<polyline class="cls-1" points="72.78 46.51 72.78 63.92 42.66 78.98 10.66 63.92 10.66 46.68"/>
								<polyline class="cls-1" points="10.66 30.04 42.66 43.21 72.78 30.04"/>
								<line class="cls-1" x1="42.66" y1="43.21" x2="42.66" y2="78.98"/>
								<polyline class="cls-1" points="42.66 43.21 33.25 57.33 1.25 42.27 10.66 30.04"/>
								<line class="cls-1" x1="64.69" y1="27.34" x2="72.78" y2="30.04"/>
								<line class="cls-1" x1="10.66" y1="30.04" x2="18.75" y2="27.34"/>
								<polyline class="cls-1" points="72.78 30.04 81.25 42.27 53.02 56.39 42.66 43.21"/>
								<line class="cls-1" x1="31.24" y1="30.58" x2="31.24" y2="18.24"/>
								<line class="cls-1" x1="52.2" y1="7.7" x2="52.2" y2="26.56"/>
								<line class="cls-1" x1="45.22" y1="14.56" x2="45.22" y2="34.28"/>
								<line class="cls-1" x1="38.23" y1="11.71" x2="38.23" y2="24.23"/>
								<line class="cls-2" x1="45.22" y1="1.25" x2="45.22" y2="11.38"/>
								<line class="cls-2" x1="31.24" y1="11.94" x2="31.24" y2="3.48"/>
								<line class="cls-1" x1="59.19" y1="28.44" x2="59.19" y2="19.09"/>
								<line class="cls-2" x1="59.19" y1="12.79" x2="59.19" y2="11.14"/>
								<line class="cls-2" x1="24.25" y1="13.64" x2="24.25" y2="11.99"/>
								<line class="cls-1" x1="24.25" y1="27.59" x2="24.25" y2="20.01"/>
							</g>
						</g>
					</svg>
				</div>
				<div id="yith-wcpb-bundled-items__actions__hero-description" class="yith-wcpb-bundled-items__actions__show-if-hero">
					<?php esc_html_e( 'You are creating a bundle product!', 'yith-woocommerce-product-bundles' ); ?>
					<br/>
					<?php esc_html_e( "Now, the first step is adding some items to this bundle: after that, you should simply set the bundle options below.", 'yith-woocommerce-product-bundles' ); ?>
				</div>
				<button type="button" id="yith-wcpb-add-bundled-product" class="button button-primary"><?php esc_html_e( 'Add product to the bundle', 'yith-woocommerce-product-bundles' ); ?></button>
			</div>

			<div class="yith-wcpb-bundled-items wc-metaboxes">
				<?php
				$metabox_id = 1;
				foreach ( $bundled_items as $bundled_item ) {
					$open_closed = 'closed';
					yith_wcpb_get_view( '/admin/bundled-item.php', compact( 'metabox_id', 'bundled_item', 'open_closed' ) );
					$metabox_id ++;
				}
				?>
			</div>
		</div>
	</div>

	<div class="yith-wcpb-bundle-options-section">

		<div class="yith-wcpb-bundle-options-section__title">
			<h3><?php esc_attr_e( 'Bundle Options', 'yith-woocommerce-product-bundles' ); ?></h3>
		</div>

		<div class="yith-wcpb-bundle-options-section__content">

			<?php do_action( 'yith_wcpb_before_product_bundle_options_tab' ); ?>

			<div class="yith-wcpb-form-field">
				<label class="yith-wcpb-form-field__label"><?php esc_html_e( 'Bundle Price', 'yith-woocommerce-product-bundles' ); ?></label>
				<div class="yith-wcpb-form-field__content">
					<?php
					yith_plugin_fw_get_field(
						array(
							'type'    => 'radio',
							'name'    => '_yith_wcpb_per_item_pricing',
							'id'      => '_yith_wcpb_per_item_pricing',
							'value'   => $per_item_pricing,
							'options' => array(
								'no'  => __( 'Set a fixed price for this bundle', 'yith-woocommerce-product-bundles' ),
								'yes' => __( 'Use prices of bundled items', 'yith-woocommerce-product-bundles' ),
							),
						),
						true
					);
					?>
				</div>
				<div class='yith-wcpb-form-field__description'>
					<?php esc_html_e( 'Choose to set a fixed price for this bundle or to use the prices of individual items.', 'yith-woocommerce-product-bundles' ); ?>
				</div>
			</div>

			<div class="yith-wcpb-form-field">
				<label class="yith-wcpb-form-field__label">
					<?php
					// translators: %s is the currency symbol
					echo esc_html( sprintf( __( 'Bundle Regular Price (%s)', 'yith-woocommerce-product-bundles' ), get_woocommerce_currency_symbol() ) );
					?>
				</label>
				<div class="yith-wcpb-form-field__content">
					<?php
					yith_plugin_fw_get_field(
						array(
							'type'  => 'text',
							'name'  => '_regular_price',
							'id'    => '_yith_wcpb_bundle_regular_price',
							'class' => 'short wc_input_price yith-wcpb-short-price-field',
							'value' => wc_format_localized_price( $product_object->get_regular_price( 'edit' ) ),
						),
						true
					);
					?>
				</div>
				<div class='yith-wcpb-form-field__description'>
					<?php esc_html_e( 'Enter the price of this bundle.', 'yith-woocommerce-product-bundles' ); ?>
				</div>
			</div>

			<div class="yith-wcpb-form-field">
				<label class="yith-wcpb-form-field__label">
					<?php
					// translators: %s is the currency symbol
					echo esc_html( sprintf( __( 'Bundle Sale Price (%s)', 'yith-woocommerce-product-bundles' ), get_woocommerce_currency_symbol() ) );
					?>
				</label>
				<div class="yith-wcpb-form-field__content">
					<?php
					yith_plugin_fw_get_field(
						array(
							'type'  => 'text',
							'name'  => '_sale_price',
							'id'    => '_yith_wcpb_bundle_sale_price',
							'class' => 'short wc_input_price yith-wcpb-short-price-field',
							'value' => wc_format_localized_price( $product_object->get_sale_price( 'edit' ) ),
						),
						true
					);
					?>
				</div>
				<div class='yith-wcpb-form-field__description'>
					<?php esc_html_e( 'Enter an optional sale price to show a discount for this bundle.', 'yith-woocommerce-product-bundles' ); ?>
				</div>
			</div>

			<div class="yith-wcpb-form-field">
				<label class="yith-wcpb-form-field__label"><?php esc_html_e( 'Show saving amount', 'yith-woocommerce-product-bundles' ); ?></label>
				<div class="yith-wcpb-form-field__content">
					<?php
					yith_plugin_fw_get_field(
						array(
							'type'  => 'onoff',
							'id'    => '_yith_wcpb_show_saving_amount',
							'name'  => '_yith_wcpb_show_saving_amount',
							'value' => $show_saving_amount,
						),
						true
					);
					?>
				</div>
				<div class='yith-wcpb-form-field__description'>
					<?php esc_html_e( 'Enable if you want to show the saving amount after price in product page.', 'yith-woocommerce-product-bundles' ); ?><br/>
					<?php esc_html_e( 'Please note: to see the saving amount you need to set the regular and sale prices.', 'yith-woocommerce-product-bundles' ); ?>
				</div>
			</div>

			<div class="yith-wcpb-form-field">
				<label class="yith-wcpb-form-field__label"><?php esc_html_e( 'Bundle Shipping', 'yith-woocommerce-product-bundles' ); ?></label>
				<div class="yith-wcpb-form-field__content">
					<?php
					yith_plugin_fw_get_field(
						array(
							'type'    => 'radio',
							'name'    => '_yith_wcpb_non_bundled_shipping',
							'id'      => '_yith_wcpb_non_bundled_shipping',
							'value'   => $non_bundled_shipping,
							'options' => array(
								'yes' => __( 'Items will be shipped individually', 'yith-woocommerce-product-bundles' ),
								'no'  => __( 'Items will be bundled in a unique shipment', 'yith-woocommerce-product-bundles' ),
							),
						),
						true
					);
					?>
				</div>
				<div class='yith-wcpb-form-field__description'>
					<?php esc_html_e( 'Choose how to manage shipping for the bundled items.', 'yith-woocommerce-product-bundles' ); ?>
				</div>
			</div>


			<div class="yith-wcpb-form-field">
				<label class="yith-wcpb-form-field__label"><?php esc_html_e( 'Limit product selection for this bundle', 'yith-woocommerce-product-bundles' ); ?></label>
				<div class="yith-wcpb-form-field__content">
					<?php
					yith_plugin_fw_get_field(
						array(
							'type'  => 'onoff',
							'id'    => 'yith-wcpb-limit-product-selection',
							'value' => $limit_product_selections_enabled,
						),
						true
					);
					?>
				</div>
				<div class='yith-wcpb-form-field__description'>
					<?php esc_html_e( 'Enable if you want to set a min/max amount of bundled products that the user can select.', 'yith-woocommerce-product-bundles' ); ?>
				</div>
			</div>

			<div class="yith-wcpb-form-field">
				<label class="yith-wcpb-form-field__label"><?php esc_html_e( 'Total items in bundle', 'yith-woocommerce-product-bundles' ); ?></label>
				<div class="yith-wcpb-form-field__content">
					<?php
					$min_input = yith_plugin_fw_get_field(
						array(
							'type'  => 'number',
							'class' => 'yith-wcpb-short-inline-field',
							'name'  => '_yith_wcpb_bundle_advanced_options[min]',
							'id'    => '_yith_wcpb_bundle_advanced_options_min',
							'min'   => '0',
							'step'  => '1',
							'value' => $advanced_options['min'],
						),
						false,
						false
					);
					$max_input = yith_plugin_fw_get_field(
						array(
							'type'  => 'number',
							'class' => 'yith-wcpb-short-inline-field',
							'name'  => '_yith_wcpb_bundle_advanced_options[max]',
							'id'    => '_yith_wcpb_bundle_advanced_options_max',
							'min'   => '0',
							'step'  => '1',
							'value' => $advanced_options['max'],
						),
						false,
						false
					);

					// translators: %1$s is the numeric input field of the minimum amount; %2$s is the numeric input field of the maximum amount
					echo sprintf( esc_html__( 'Require a minimum amount of %1$s and a maximum amount of %2$s bundled items.', 'yith-woocommerce-product-bundles' ), $min_input, $max_input ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
				<div class='yith-wcpb-form-field__description'>
					<?php esc_html_e( 'Define the min and max number of bundled items that a user can select.', 'yith-woocommerce-product-bundles' ); ?>
					<br/>
					<?php esc_html_e( "Leave empty if the user does not need to select a min or max number of items, to add the bundle to the cart.", 'yith-woocommerce-product-bundles' ); ?>
				</div>
			</div>

			<div class="yith-wcpb-form-field">
				<label class="yith-wcpb-form-field__label"><?php esc_html_e( 'Different types of items in bundle', 'yith-woocommerce-product-bundles' ); ?></label>
				<div class="yith-wcpb-form-field__content">
					<?php
					$min_distinct_input = yith_plugin_fw_get_field(
						array(
							'type'  => 'number',
							'class' => 'yith-wcpb-short-inline-field',
							'name'  => '_yith_wcpb_bundle_advanced_options[min_distinct]',
							'id'    => '_yith_wcpb_bundle_advanced_options_min_distinct',
							'min'   => '0',
							'step'  => '1',
							'value' => $advanced_options['min_distinct'],
						),
						false,
						false
					);
					$max_distinct_input = yith_plugin_fw_get_field(
						array(
							'type'  => 'number',
							'class' => 'yith-wcpb-short-inline-field',
							'name'  => '_yith_wcpb_bundle_advanced_options[max_distinct]',
							'id'    => '_yith_wcpb_bundle_advanced_options_max_distinct',
							'min'   => '0',
							'step'  => '1',
							'value' => $advanced_options['max_distinct'],
						),
						false,
						false
					);

					// translators: %1$s is the numeric input field of the minimum amount; %2$s is the numeric input field of the maximum amount
					echo sprintf( esc_html__( 'Require at least %1$s and max %2$s different types of bundled items.', 'yith-woocommerce-product-bundles' ), $min_distinct_input, $max_distinct_input ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
				<div class='yith-wcpb-form-field__description'>
					<?php esc_html_e( 'Define the min and max number of different items that a user can select.', 'yith-woocommerce-product-bundles' ); ?>
					<br/>
					<?php esc_html_e( "Leave empty if the user does not need to select a min or max of different items, to add the bundle to the cart.", 'yith-woocommerce-product-bundles' ); ?>
				</div>
			</div>

			<?php do_action( 'yith_wcpb_after_product_bundle_options_tab' ); ?>
		</div>
	</div>
</div>
