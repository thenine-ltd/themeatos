<?php
/**
 * Cart discount field.
 *
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.0.0
 * @version 1.6.0
 * @author  YITH
 *
 * @var array $args
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $post;
extract( $args );
$db_value = get_post_meta( $post->ID, $id, true );

$cart_rules_options = YITH_WC_Dynamic_Pricing()->cart_rules_options;
$discount_type      = isset( $db_value['discount_type'] ) ? $db_value['discount_type'] : 'percentage';
$discount_amount    = isset( $db_value['discount_amount'] ) ? $db_value['discount_amount'] : 10;
?>
<div id="<?php echo esc_attr( $id ); ?>-container"
	 class="yith-plugin-fw-metabox-field-row" <?php echo yith_field_deps_data( $args ); //phpcs:ignore WordPress.Security.EscapeOutput ?>>
	<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>

	<?php
	$type_args = array(
		'id'      => $id . '_discount_type',
		'name'    => $name . '[discount_type]',
		'value'   => $discount_type,
		'options' => array(
			'percentage'  => __( 'a % discount of', 'ywdpd' ),
			'price'       => __( 'a price discount of', 'ywdpd' ),
			'fixed-price' => __( 'a fixed price of', 'ywdpd' ),
		),
		'type'    => 'select',
		'class'   => 'wc-enhanced-select ywdpd_qty_discount',
	);

	$type_field = yith_plugin_fw_get_field( $type_args, false, false );

	$value_args   = array(
		'id'    => $id . '_discount_amount',
		'name'  => $name . '[discount_amount]',
		'value' => $discount_amount,
		'type'  => 'text',
	);
	$value_fields = yith_plugin_fw_get_field( $value_args, false, false );

	$symbol_field = sprintf( '<span class="ywdpd_symbol">%s</span>', 'percentage' == $discount_type ? '%' : get_woocommerce_currency_symbol() );
	echo ( sprintf( '%1$s %2$s %3$s', $type_field, $value_fields, $symbol_field ) );//phpcs:ignore WordPress.Security.EscapeOutput
	?>
	<div class="clear"></div>
	<span class="description"><?php echo esc_html( $desc ); ?></span>
</div>
