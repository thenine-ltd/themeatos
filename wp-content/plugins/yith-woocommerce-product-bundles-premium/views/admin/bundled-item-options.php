<?php
/**
 * Admin Bundle Item Options
 *
 * @var YITH_WC_Bundled_item $bundled_item The bundled product.
 * @var int                  $metabox_id   The metabox ID.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

$bp_quantity = $bundled_item->get_quantity();
?>
<div class="options_group">
	<p class="form-field">
		<label><?php echo esc_html( _x( 'Quantity', 'Admin: quantity of the bundled product.', 'yith-woocommerce-product-bundles' ) ); ?></label>
		<input type="number" size="4" value="<?php echo esc_attr( $bp_quantity ); ?>" name="_yith_wcpb_bundle_data[<?php echo esc_attr( $metabox_id ); ?>][bp_quantity]"
				class="yith-wcpb-bp-quantity short">
	</p>
</div>
