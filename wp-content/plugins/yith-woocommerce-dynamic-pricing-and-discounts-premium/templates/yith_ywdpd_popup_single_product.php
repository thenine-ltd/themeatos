<?php

/**
 * Popup gift single product.
 *
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.0.0
 * @version 1.6.0
 * @author  YITH
 *
 * @var integer $rule_id
 * @var integer $product_id
 */

if ( ! defined( 'ABSPATH' ) && ! isset( $product_id ) ) {
	exit;
}

$single_product = wc_get_product( $product_id );

?>
<div class="single-product">
	<div class="ywdpd_step2_header">
		<span class="ywdpd_back"></span>
		<h4 class="ywdpd_rule_title"><?php esc_html_e( 'Select a variation', 'ywdpd' ); ?></h4>
	</div>
	<div id="product-<?php echo esc_attr( $product_id ); ?>" class="ywdpdp_single_product product <?php echo esc_attr( $single_product->get_type() ); ?>">
		<div class="ywdpd_single_product_left">
			<?php
			echo $single_product->get_image(  ); //phpcs:ignore
			echo "<h5>".$single_product->get_name()."</h5>"; //phpcs:ignore
			?>
			<span class="price" style="display: none;">
			</span>
		</div>
		<div class="ywdpd_single_product_right">
			<?php
			if ( 'variable' === $single_product->get_type() ) {

				global $product;

				$product = $single_product;
				woocommerce_variable_add_to_cart();
			} else {
				/**
				 * The product is a variation.
				 *
				 * @var WC_Product_Variation $single_product
				 */
				$attributes = $single_product->get_attributes();
				$parent_id  = $single_product->get_parent_id();
				/**
				 * The product is the variable product.
				 *
				 * @var WC_Product_Variable $parent_product
				 */
				$parent_product       = wc_get_product( $parent_id );
				$variation_attributes = $parent_product->get_variation_attributes();
				$attribute_keys       = array_keys( $attributes );

				?>
			<table class="variations" cellspacing="0">
				<tbody>
					<?php foreach ( $attributes as $attribute_name => $value ) : ?>
						<tr>
							<td class="label"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo wc_attribute_label( $attribute_name ); // WPCS: XSS ok. ?></label></td>
							<td class="value">
								<?php
								if ( ! empty( $value ) ) {
									echo '<span>' . esc_html( $value ) . '</span>';
									?>
									<input type="hidden" id="<?php echo esc_attr( $attribute_name ); ?>" name="attribute_<?php echo esc_attr( $attribute_name ); ?>" value="<?php echo esc_attr( $value ); ?>">
									<?php
								} else {
									wc_dropdown_variation_attribute_options(
										array(
											'options'   => '',
											'attribute' => $attribute_name,
											'product'   => $parent_product,
											'class'     => 'wc-enhanced-select',
										)
									);
									echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : '';

								}
								?>
								 
							</td>
						</tr>
						<?php endforeach; ?>
				</tbody>
			</table>
			<input type="hidden" name="product_id" value="<?php echo esc_attr( $parent_id ); ?>">
			<input type="hidden" class="variation_id" value="<?php echo esc_attr( $product_id ); ?>">
				<?php
			}
			?>
				<div class="ywdpd_button_add_to_gift">
					<?php
						$add_to_cart_button = __( 'Save options', 'ywdpd' );
					?>
					<button
						class="ywdpd_add_to_gift button single_add_to_cart_button disabled"><?php echo esc_html( $add_to_cart_button ); ?></button>
					<input type="hidden" class="ywdpd_rule_id" value="<?php echo esc_attr( $rule_id ); ?>">
					<input type="hidden" class="ywdpd_rule_type" value="<?php echo esc_attr( $rule_type ); ?>">
				</div>
		
		</div>
	</div>
</div>
