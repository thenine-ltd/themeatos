<?php
/**
 * Special offer discount field.
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
$db_value = ! is_array( $db_value ) ? array() : $db_value;
$limit    = empty( $db_value ) ? 1 : count( $db_value );
?>

<div id="<?php echo esc_attr( $id ); ?>-container" <?php echo yith_field_deps_data( $args ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
	 xmlns="http://www.w3.org/1999/html">
	<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
	<div class="yith-plugin-fw-field-wrapper">
		<div class="discount-table-rules-wrapper">
			<div class="special-offers-rules">
				<div class="special-row">
					<div class="special-row-field">
						<?php
						$from_args = array(
							'type'  => 'number',
							'id'    => 'special_offer_purchase_from',
							'name'  => $name . '[purchase]',
							'value' => ! empty( $db_value['purchase'] ) ? $db_value['purchase'] : 1,
							'min'   => 1,
							'step'  => 1,
						);

						$from_fields         = yith_plugin_fw_get_field( $from_args, false, false );
						$to_args             = array(
							'type'  => 'number',
							'id'    => 'special_offer_purchase_to',
							'name'  => $name . '[receive]',
							'value' => ! empty( $db_value['receive'] ) ? $db_value['receive'] : '',
							'min'   => 1,
							'step'  => 1,
						);
						$to_fields           = yith_plugin_fw_get_field( $to_args, false, false );
						$type_discount_args  = array(
							'type'    => 'select',
							'class'   => 'wc-enhanced-select ',
							'options' => array(
								'percentage'  => __( 'a % discount of', 'ywdpd' ),
								'price'       => __( 'a price discount of', 'ywdpd' ),
								'fixed-price' => __( 'a fixed price of', 'ywdpd' ),
							),
							'id'      => 'special_offer_purchase_discount',
							'name'    => $name . '[type_discount]',
							'value'   => ! empty( $db_value['type_discount'] ) ? $db_value['type_discount'] : 'percentage',
						);
						$type_discount_field = yith_plugin_fw_get_field( $type_discount_args, false, false );

						$amount_args   = array(
							'type'  => 'text',
							'id'    => 'special_offer_purchase_discount_value',
							'name'  => $name . '[discount_amount]',
							'value' => ! empty( $db_value['discount_amount'] ) ? $db_value['discount_amount'] : 10,
						);
						$amount_field  = yith_plugin_fw_get_field( $amount_args, false, false );
						$symbol_field  = '';
						$type_discount = isset( $db_value['type_discount'] ) ? $db_value['type_discount'] : 'percentage';
						$symbol_field  = sprintf( '<span class="ywdpd_symbol">%s</span>', 'percentage' == $type_discount ? '%' : get_woocommerce_currency_symbol() );

						/*
						 translators:
							 %1$s is the numeric input field of the minimum quantity;
							 %2$s is the numeric input field of the maximum quantity;
							 %3$s is the type of the discount ( percentage, amount discount , fixed price )
							 %4$s is the value of the discount
							 %5$s is the symbol of the discount % or currency ( € )
							*/
						echo sprintf( esc_html_x( 'If user purchases %1$s item(s) gets %2$s item(s) with %3$s %4$s %5$s', 'If user purchases 3 item(s) gets 2 item(s) with a discount of 30%', 'ywdpd' ), $from_fields, $to_fields, $type_discount_field, $amount_field, $symbol_field ); //phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</div>
				</div>
			</div>
		</div>
		<div class="clear">
		</div>
		<span class="description"><?php echo esc_html( $desc ); ?></span>
	</div>
</div>
