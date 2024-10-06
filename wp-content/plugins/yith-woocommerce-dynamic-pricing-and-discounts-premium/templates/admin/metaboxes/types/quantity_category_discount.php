<?php
/**
 * Quantity discount field.
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
$limit    = empty( $db_value ) ? 1 : count( $db_value );

?>

<div id="<?php echo esc_attr( $id ); ?>-container" <?php echo yith_field_deps_data( $args ); //phpcs:ignore WordPress.Security.EscapeOutput ?>
	 class="yith-plugin-fw-metabox-field-row">

	<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
	<div class="yith-plugin-fw-field-wrapper">
		<div class="discount-table-rules-wrapper">
			<div class="discount-rules">
				<?php

				for ( $i = 1; $i <= $limit; $i ++ ) :

					if ( isset( $db_value[ $i ] ) ) {
						$value = $db_value[ $i ];
					} else {
						$value = array(
							'min_quantity'  => 10,
							'max_quantity'  => '',
							'type_discount' => 'price',
							'product_cat'   => '',
						);
					}

					$type_discount_args  = array(
						'type'    => 'select',
						'class'   => 'wc-enhanced-select ywdpd_qty_discount',
						'options' => array(
							'percentage'  => __( 'a % discount of', 'ywdpd' ),
							'price'       => __( 'a price discount of', 'ywdpd' ),
							'fixed-price' => __( 'a fixed price of', 'ywdpd' ),
						),
						'id'      => 'qty_cat_discount_' . $i,
						'name'    => $name . "[$i][type_discount]",
						'value'   => ! empty( $value['type_discount'] ) ? $value['type_discount'] : 'percentage',
					);
					$type_discount_field = yith_plugin_fw_get_field( $type_discount_args, false, false );

					$amount_args = array(
						'id'    => "qty_cat_discount_value_$i",
						'name'  => $name . "[$i][discount_amount]",
						'type'  => 'text',
						'value' => isset( $value['discount_amount'] ) ? esc_attr( $value['discount_amount'] ) : 10,
					);

					$amount_field      = yith_plugin_fw_get_field( $amount_args, false, false );
					$symbol_field      = sprintf( '<span class="ywdpd_symbol">%s</span>', 'percentage' == $value['type_discount'] ? '%' : get_woocommerce_currency_symbol() );
					$product_args      = array(
						'id'       => 'qty_cat_discount_category_' . $i,
						'type'     => 'ajax-terms',
						'name'     => $name . '[' . $i . '][product_cat]',
						'data'     => array(
							'taxonomy' => 'product_cat',
						),
						'value'    => isset( $value['product_cat'] ) ? $value['product_cat'] : '',
						'multiple' => false,
					);
					$product_cat_field = yith_plugin_fw_get_field( $product_args, false, false );
					?>
					<div class="discount-table-row" data-index="<?php echo esc_attr( $i ); ?>">
						<?php
						/*
						 translators:
						   %1$s is the type of the discount ( percentage, amount discount , fixed price )
						   %2$s is the value of the discount
						 %3$s is the symbol of the discount % or currency ( € )
						 %4$s is a product category field
						*/
						echo sprintf( esc_html__( 'Apply %1$s %2$s %3$s on all products of %4$s', 'ywdpd' ), $type_discount_field, $amount_field, $symbol_field, $product_cat_field ); //phpcs:ignore WordPress.Security.EscapeOutput
						?>
						<?php
						if ( 1 !== $i ) :
							?>
							<span class="yith-icon yith-icon-trash"></span>
							<?php
						endif;
						?>

					</div>
					<?php
				endfor;
				?>
			</div>
			<div class="discount_new_rule">
				<a href="#" id="ywdpd_new_qty_cat_rule" class="ywdpd_new_rule"><?php esc_html_e( '+ Add rule', 'ywdpd' ); ?></a>
			</div>
		</div>

	</div>
	<div class="clear"></div>
	<span
		class="description"><?php echo esc_html( $desc ); ?></span>
</div>

<script type="text/template" id="tmpl-ywdpd-quantity-category-discount-row">
	<div class="discount-table-row" data-index="{{{data.index}}}">
		<?php
		$type_discount_args  = array(
			'type'    => 'select',
			'class'   => 'wc-enhanced-select ywdpd_qty_discount',
			'options' => array(
				'percentage'  => __( 'a % discount of', 'ywdpd' ),
				'price'       => __( 'a price discount of', 'ywdpd' ),
				'fixed-price' => __( 'a fixed price of', 'ywdpd' ),
			),
			'id'      => 'qty_cat_discount_{{{data.index}}}',
			'name'    => $name . '[{{{data.index}}}][type_discount]',
			'value'   => 'percentage',
		);
		$type_discount_field = yith_plugin_fw_get_field( $type_discount_args, false, false ); //phpcs:ignore WordPress.Security.EscapeOutput

		$amount_args = array(
			'id'    => 'qty_cat_discount_value_{{{data.index}}}',
			'name'  => $name . '[{{{data.index}}}][discount_amount]',
			'type'  => 'text',
			'value' => 10,
		);

		$amount_field      = yith_plugin_fw_get_field( $amount_args, false, false );
		$symbol_field      = sprintf( '<span class="ywdpd_symbol">%s</span>', '%' );
		$product_args      = array(
			'id'       => 'qty_cat_discount_category_{{{data.index}}}',
			'type'     => 'ajax-terms',
			'name'     => $name . '[{{{data.index}}}][product_cat]',
			'data'     => array(
				'taxonomy' => 'product_cat',
			),
			'value'    => '',
			'multiple' => false,
		);
		$product_cat_field = yith_plugin_fw_get_field( $product_args, false, false ); //phpcs:ignore WordPress.Security.EscapeOutput
		/*
		 translators:
						   %1$s is the type of the discount ( percentage, amount discount , fixed price )
						   %2$s is the value of the discount
						 %3$s is the symbol of the discount % or currency ( € )
						 %4$s is a product category field
						*/
		echo sprintf( esc_html__( 'Apply %1$s %2$s %3$s on all products of %4$s', 'ywdpd' ), $type_discount_field, $amount_field, $symbol_field, $product_cat_field ); //phpcs:ignore WordPress.Security.EscapeOutput
		?>
		<span class="yith-icon yith-icon-trash"></span>
	</div>
</script>
