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

$limit                    = empty( $db_value ) ? 1 : count( $db_value );
$qty_input_number_pattern = apply_filters( 'yith_ywdpd_quantity_pattern', 'pattern="(\d*|\*)$" title="Only number or *"' );


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
							'min_quantity'    => '1',
							'max_quantity'    => '',
							'type_discount'   => 'percentage',
							'discount_amount' => 10,
						);
					}

					$min_field_args = array(
						'type'              => 'text',
						'id'                => 'qty_from_' . $i,
						'name'              => $name . "[$i][min_quantity]",
						'value'             => $value['min_quantity'],
						'custom_attributes' => $qty_input_number_pattern,
					);
					$max_field_args = array(
						'type'              => 'text',
						'id'                => 'qty_to_' . $i,
						'name'              => $name . "[$i][max_quantity]",
						'value'             => $value['max_quantity'],
						'custom_attributes' => $qty_input_number_pattern,
					);

					$select_args = array(
						'type'     => 'select',
						'id'       => 'qty_discount_' . $i,
						'multiple' => false,
						'class'    => 'wc-enhanced-select ywdpd_qty_discount',
						'value'    => $value['type_discount'],
						'options'  => array(
							'percentage'  => __( 'a % discount of', 'ywdpd' ),
							'price'       => __( 'a price discount of', 'ywdpd' ),
							'fixed-price' => __( 'a fixed price of', 'ywdpd' ),
						),
						'name'     => $name . "[$i][type_discount]",
					);

					$amount_args = array(
						'type'  => 'text',
						'id'    => 'qty_discount_value_' . $i,
						'name'  => $name . "[$i][discount_amount]",
						'value' => $value['discount_amount'],
					);


					$min_field = yith_plugin_fw_get_field(
						$min_field_args,
						false,
						false
					);
					$max_field = yith_plugin_fw_get_field(
						$max_field_args,
						false,
						false
					);


					$discount_type_field = yith_plugin_fw_get_field(
						$select_args,
						false,
						false
					);

					$amount_fields = yith_plugin_fw_get_field(
						$amount_args,
						false,
						false
					);

					$symbol_field = sprintf( '<span class="ywdpd_symbol">%s</span>', 'percentage' == $value['type_discount'] ? '%' : get_woocommerce_currency_symbol() );
					?>
					<div class="discount-table-row" data-index="<?php echo $i; ?>">
						<?php
						/*
						 translators:
						%1$s is the numeric input field of the minimum quantity;
						%2$s is the numeric input field of the maximum quantity;
						%3$s is the type of the discount ( percentage, amount discount , fixed price )
						%4$s is the value of the discount
						%5$s is the symbol of the discount % or currency ( € )
						*/
						echo sprintf( esc_html__( '- From %1$s to %2$s apply %3$s %4$s %5$s', 'ywdpd' ), $min_field, $max_field, $discount_type_field, $amount_fields, $symbol_field ); //phpcs:ignore WordPress.Security.EscapeOutput
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
				<a href="#" id="ywdpd_new_qty_rule" class="ywdpd_new_rule"><?php esc_html_e( '+ Add rule', 'ywdpd' ); ?></a>
			</div>
		</div>

	</div>
	<div class="clear"></div>
	<span
		class="description"><?php esc_html_e( 'Create the quantity rules for the product you selected. Leave "to" field empty, to not put a max amount in this rule', 'ywdpd' ); ?></span>
</div>

<script type="text/template" id="tmpl-ywdpd-quantity-discount-row">
	<div class="discount-table-row" data-index="{{{data.index}}}">
		<?php
		$min_field_args = array(
			'type'              => 'text',
			'id'                => 'qty_from_{{{data.index}}}',
			'name'              => $name . '[{{{data.index}}}][min_quantity]',
			'value'             => 1,
			'custom_attributes' => $qty_input_number_pattern,
		);
		$max_field_args = array(
			'type'              => 'text',
			'id'                => 'qty_to_{{{data.index}}}',
			'name'              => $name . '[{{{data.index}}}][max_quantity]',
			'value'             => '',
			'custom_attributes' => $qty_input_number_pattern,
		);

		$select_args = array(
			'type'     => 'select',
			'id'       => 'qty_discount_{{{data.index}}}',
			'multiple' => false,
			'class'    => 'wc-enhanced-select ywdpd_qty_discount',
			'value'    => 'percentage',
			'options'  => array(
				'percentage'  => __( 'a % discount of', 'ywdpd' ),
				'price'       => __( 'a price discount of', 'ywdpd' ),
				'fixed-price' => __( 'a fixed price of', 'ywdpd' ),
			),
			'name'     => $name . '[{{{data.index}}}][type_discount]',
		);

		$amount_args         = array(
			'type'  => 'text',
			'id'    => 'qty_discount_value_{{{data.index}}}',
			'name'  => $name . '[{{{data.index}}}][discount_amount]',
			'value' => 10,
		);
		$min_field           = yith_plugin_fw_get_field(
			$min_field_args,
			false,
			false
		);
		$max_field           = yith_plugin_fw_get_field(
			$max_field_args,
			false,
			false
		);
		$discount_type_field = yith_plugin_fw_get_field(
			$select_args,
			false,
			false
		);
		$amount_fields       = yith_plugin_fw_get_field(
			$amount_args,
			false,
			false
		);
		$symbol_field        = sprintf( '<span class="ywdpd_symbol">%s</span>', '%' );
		/*
		 translators:
							 %1$s is the numeric input field of the minimum quantity;
							 %2$s is the numeric input field of the maximum quantity;
							 %3$s is the type of the discount ( percentage, amount discount , fixed price )
							 %4$s is the value of the discount
							 %5$s is the symbol of the discount % or currency ( € )
							*/
		echo sprintf( esc_html__( '- From %1$s to %2$s apply %3$s %4$s %5$s', 'ywdpd' ), $min_field, $max_field, $discount_type_field, $amount_fields, $symbol_field ); //phpcs:ignore WordPress.Security.EscapeOutput
		?>
		<span class="yith-icon yith-icon-trash"></span>
	</div>
</script>
