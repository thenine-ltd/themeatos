<?php
/**
 * Template for bundles
 *
 * @var WC_Product_YITH_Bundle $product The Bundle Product.
 * @package YITH WooCommerce Product Bundles
 */

defined( 'ABSPATH' ) || exit;

$quick_view_for_bundled_items = 'yes' === get_option( 'yith-wcpb-quick-view-for-bundled-items', 'no' );

$bundled_items     = $product->get_bundled_items();
$per_items_pricing = $product->per_items_pricing;

do_action( 'yith_wcpb_before_bundle_items_list' );

$display_table = false;
if ( $bundled_items ) {
	foreach ( $bundled_items as $bundled_item ) {
		if ( ! $bundled_item->is_hidden() ) {
			$display_table = true;
			break;
		}
	}
}
$style = ! $display_table ? "style='display:none'" : '';

if ( $bundled_items ) : ?>
	<div class='yith-wcpb-product-bundled-items' <?php echo esc_attr( $style ); ?>>

		<?php
		foreach ( $bundled_items as $bundled_item ) :
			/**
			 * The current bundled item.
			 *
			 * @var YITH_WC_Bundled_Item $bundled_item The current bundled item.
			 */
			$bundled_product_id = $bundled_item->get_wpml_product_id_current_language();
			$bundled_product = wc_get_product( $bundled_product_id );
			$bundled_post = get_post( yit_get_base_product_id( $bundled_product ) );
			$quantity = absint( $bundled_item->get_quantity() );
			$hide_thumbnail = $bundled_item->hide_thumbnail;
			$hidden = $bundled_item->is_hidden();
			$bundled_item_id = $bundled_item->item_id;
			$step_qty = absint( apply_filters( 'yith_wcpb_bundled_item_quantity_input_step', 1, $bundled_product, $bundled_item ) );
			$min_qty = absint( apply_filters( 'yith_wcpb_bundled_item_quantity_input_min', $bundled_item->min_quantity, $bundled_product, $bundled_item ) );
			$max_qty = absint( apply_filters( 'yith_wcpb_bundled_item_quantity_input_max', $bundled_item->max_quantity, $bundled_product, $bundled_item ) );
			$item_id = $bundled_item->item_id;
			$title = $bundled_item->get_name_to_be_shown();
			$description = $bundled_item->get_description_to_be_shown();
			$optional = $bundled_item->optional;

			if ( $bundled_item->has_variables() ) {
				$my_price_max = yith_wcpb_get_price_to_display( $bundled_product, $bundled_product->get_variation_regular_price( 'min' ) );
			} else {
				$my_price_max = yith_wcpb_get_price_to_display( $bundled_product, $bundled_product->get_regular_price() );
			}

			$my_price_max = (float) apply_filters( 'yith_wcpb_bundled_item_displayed_price', $my_price_max, $bundled_product, $bundled_item );
			$my_discount  = apply_filters( 'yith_wcpb_bundled_item_calculated_discount', $my_price_max * (float) $bundled_item->discount / 100, $bundled_item->discount, $my_price_max, $bundled_item->get_product_id(), array() );
			$my_price     = $my_price_max - $my_discount;

			$my_price = yith_wcpb_round_bundled_item_price( $my_price );

			if ( $hidden ) {
				if ( $bundled_item->has_variables() ) {
					$default_selection     = $bundled_item->get_selected_product_variation_attributes();
					$default_selection_tmp = array();
					foreach ( $default_selection as $key => $value ) {
						$default_selection_tmp[ 'attribute_' . $key ] = YITH_WCPB()->compatibility->wpml->get_wpml_term_slug_current_language( $value, $key );
						$default_selection[ $key ]                    = YITH_WCPB()->compatibility->wpml->get_wpml_term_slug_current_language( $value, $key );
					}

					$data_store   = WC_Data_Store::load( 'product' );
					$variation_id = $data_store->find_matching_product_variation( $bundled_product, $default_selection_tmp );

					if ( ! ! $variation_id ) {
						$input_name = "yith_bundle_variation_id_$bundled_item_id";
						echo "<input type='hidden' name='" . esc_attr( $input_name ) . "' value='" . esc_attr( $variation_id ) . "' class='variation_id' data-item-id='" . esc_attr( $bundled_item_id ) . "'/>";

						$b_attributes = $attributes[ $bundled_item_id ];
						foreach ( $b_attributes as $name => $options ) {
							$input_name = 'yith_bundle_attribute_' . sanitize_title( $name ) . '_' . $bundled_item_id;
							$value      = isset( $default_selection[ $name ] ) ? $default_selection[ $name ] : 0;

							echo "<input type='hidden' name='" . esc_attr( $input_name ) . "' value='" . esc_attr( $value ) . "' />";
						}
					}
				}
				continue;
			}

			// free -> premium.
			if ( ( ! isset( $min_qty ) && ! isset( $max_qty ) ) || $min_qty < 0 || $max_qty < 1 ) {
				$min_qty = $quantity;
				$max_qty = $quantity;
			}

			$initial_quantity = absint( apply_filters( 'yith_wcpb_bundle_items_list_bundle_quantity', max( 1, $min_qty ), $min_qty, $max_qty, $bundled_item, $product ) );

			$quantity_lbl = '';
			if ( $min_qty === $max_qty && 1 !== $min_qty ) {
				$quantity_lbl = esc_html( $min_qty ) . ' x ';
			}

			$item_title = esc_html( $quantity_lbl . $title );
			$item_title = apply_filters( 'yith_wcpb_bundle_items_list_bundle_title', $item_title, $bundled_item, $min_qty, $max_qty, $title );

			$bundled_item_classes = apply_filters( 'yith_wcpb_bundled_item_classes', array( 'product', 'yith-wcpb-product-bundled-item' ), $bundled_item, $product );
			$bundled_item_classes = implode( ' ', $bundled_item_classes );
			?>
			<div class="<?php echo esc_attr( $bundled_item_classes ); ?>"
					data-is-purchasable="<?php echo esc_attr( $bundled_product->is_purchasable() ? '1' : '0' ); ?>"
					data-min-quantity="<?php echo esc_attr( $min_qty ); ?>"
					data-max-quantity="<?php echo esc_attr( $max_qty ); ?>">
				<div class="yith-wcpb-product-bundled-item-image">
					<?php
					if ( ! $hide_thumbnail ) {
						$post_thumbnail_id = $bundled_product->get_image_id();
						if ( $post_thumbnail_id ) {
							echo wc_get_gallery_image_html( $post_thumbnail_id, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							echo '<div class="woocommerce-product-gallery__image--placeholder">';
							echo sprintf( '<img src="%s" alt="%s" class="wp-post-image" />', esc_url( wc_placeholder_img_src() ), esc_html__( 'Awaiting product image', 'woocommerce' ) );
							echo '</div>';
						}
					}

					?>
				</div>
				<div class="yith-wcpb-product-bundled-item-data">

					<h3 class="yith-wcpb-product-bundled-item-data__title">
						<?php if ( $bundled_product->is_visible() ) : ?>
							<a href="<?php echo esc_url( $bundled_product->get_permalink() ); ?>" class="<?php echo esc_attr( $quick_view_for_bundled_items ? 'yith-wcqv-button' : '' ); ?>" data-product_id="<?php echo esc_attr( $bundled_product->get_id() ); ?>">
								<?php echo $item_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						<?php else : ?>
							<?php echo $item_title; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php endif; ?>
					</h3>

					<?php do_action( 'yith_wcpb_after_bundled_item_title', $bundled_item ); ?>

					<div class="yith-wcpb-product-bundled-item-data__price">
						<?php
						if ( apply_filters( 'yith_wcpb_show_bundled_items_prices', true, $bundled_item, $product ) ) {
							if ( ! $bundled_item->has_variables() || apply_filters( 'yith_wcpb_bundled_item_show_default_price_for_variables', true ) ) {
								$my_price_max_html_data = $my_price_max > 0 ? htmlspecialchars( wc_price( $my_price_max ) ) : '';
								$my_price_html_data     = $my_price > 0 ? htmlspecialchars( wc_price( $my_price ) ) : '';

								if ( ! $per_items_pricing ) {
									?>
									<div class="price" data-default-del="<?php echo esc_attr( $my_price_max_html_data ); ?>" data-default-ins="">
										<del><span class="amount"><?php echo wc_price( $my_price_max ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></del>
									</div>
									<?php
								} else {
									if ( $my_price_max > $my_price ) {
										?>
										<div class="price" data-default-del="<?php echo esc_attr( $my_price_max_html_data ); ?>" data-default-ins="<?php echo esc_attr( $my_price_html_data ); ?>">
											<del><span class="amount"><?php echo wc_price( $my_price_max ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></del>
											<ins><span class="amount"><?php echo wc_price( $my_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></ins>
										</div>
										<?php
									} else {
										?>
										<div class="price" data-default-del="" data-default-ins="<?php echo esc_attr( $my_price_html_data ); ?>">
											<ins><span class="amount"><?php echo wc_price( $my_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span></ins>
										</div>
										<?php
									}
								}
							}
						}
						?>
					</div>

					<div class="yith-wcpb-product-bundled-item-data__description"><?php echo wp_kses_post( do_shortcode( $description ) ); ?></div>

					<?php if ( $optional ) : ?>
						<?php
						$_id = "yith_bundle_{$bundled_product_id}optional_{$item_id}";
						?>
						<div class="yith-wcpb-product-bundled-item-data__optional yith-wcpb-bundled-optional-wrapper">
							<input type="checkbox" name="yith_bundle_optional_<?php echo esc_attr( $item_id ); ?>"
									id="<?php echo esc_attr( $_id ); ?>"
									class="yith-wcpb-bundled-optional" data-item-id="<?php echo esc_attr( $item_id ); ?>">
							<?php if ( ! $per_items_pricing || $bundled_item->has_variables() || ! apply_filters( 'yith_wcpb_show_bundled_items_prices', true, $bundled_item, $product ) ) : ?>
								<label for="<?php echo esc_attr( $_id ); ?>"><?php echo wp_kses_post( apply_filters( 'yith_wcpb_add_label', yith_wcpb_settings()->get_option( 'yith-wcpb-add-item-to-bundle-label' ) ) ); ?></label>
							<?php else : ?>
								<label for="<?php echo esc_attr( $_id ); ?>">
									<?php
									// translators: %s is the price of the product.
									echo wp_kses_post( sprintf( apply_filters( 'yith_wcpb_add_for_label', yith_wcpb_settings()->get_option_and_translate( 'yith-wcpb-add-item-to-bundle-for-label' ) ), wc_price( $my_price ) ) );
									?>
								</label>
							<?php endif; ?>
						</div>
					<?php endif; ?>

					<?php
					if ( $bundled_item->has_variables() ) :
						$b_attributes = $attributes[ $bundled_item_id ];
						$attribute_keys = array_keys( $b_attributes );
						?>
						<div class="yith-wcpb-product-bundled-item-data__variations_form bundled_item_cart_content variations_form"
								data-optional="<?php echo esc_attr( $bundled_item->is_optional() ? 1 : 0 ); ?>"
								data-type="<?php echo esc_attr( $bundled_product->get_type() ); ?>"
								data-product_variations="<?php echo esc_attr( json_encode( $available_variations[ $bundled_item_id ] ) ); ?>"
								data-bundled_item_id="<?php echo esc_attr( $bundled_item->item_id ); ?>"
								data-product_id="<?php echo esc_attr( $bundled_product->get_id() ); ?>"
								data-bundle_id="<?php echo esc_attr( $product->get_id() ); ?>">

							<input name="yith_bundle_variation_id_<?php echo esc_attr( $bundled_item_id ); ?>" class="variation_id"
									value="" type="hidden" data-item-id="<?php echo esc_attr( $bundled_item_id ); ?>">

							<table class="variations" cellspacing="0">
								<tbody>
								<?php foreach ( $b_attributes as $name => $options ) : ?>
									<tr>
										<td class="label"><label for="<?php echo esc_attr( sanitize_title( $name ) ); ?>"><?php echo esc_html( wc_attribute_label( $name ) ); ?></label></td>
										<td class="value">
											<?php
											$identifier = 'yith_bundle_attribute_' . sanitize_title( $name ) . '_' . $bundled_item_id;

											if ( isset( $_REQUEST[ $identifier ] ) ) {
												$selected_value = $_REQUEST[ $identifier ];
											} elseif ( isset( $selected_attributes[ $bundled_item_id ][ sanitize_title( $name ) ] ) ) {
												$selected_value = $selected_attributes[ $bundled_item_id ][ sanitize_title( $name ) ];
											} else {
												$selected_value = '';
											}

											if ( taxonomy_exists( $name ) && ! ! $selected_value ) {
												$selected_value = YITH_WCPB()->compatibility->wpml->get_wpml_term_slug_current_language( $selected_value, $name );
											}

											yith_wcpb_wc_dropdown_variation_attribute_options(
												array(
													'id'        => esc_attr( $identifier ),
													'name'      => $identifier,
													'class'     => 'yith-wcpb-select-for-variables',
													'options'   => $options,
													'attribute' => $name,
													'product'   => $bundled_product,
													'selected'  => $selected_value,
												)
											);
											echo end( $attribute_keys ) === $name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ) : '';
											?>
										</td>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>

							<div class="single_variation_wrap bundled_item_wrap" style="display:none;">
								<div class="single_variation bundled_item_cart_details"></div>
							</div>

						</div>
					<?php endif; ?>

					<div class="yith-wcpb-product-bundled-item-data__quantity">
						<?php if ( $min_qty < $max_qty ) : ?>
							<input step="<?php echo esc_attr( $step_qty ); ?>" min="<?php echo esc_attr( $min_qty ); ?>" max="<?php echo esc_attr( $max_qty ); ?>"
									name="yith_bundle_quantity_<?php echo esc_attr( $item_id ); ?>" value="<?php echo esc_attr( $initial_quantity ); ?>"
									title="Qty"
									data-item-id="<?php echo esc_attr( $item_id ); ?>"
									class="yith-wcpb-bundled-quantity" size="4" type="number">
							<div class="yith-wcpb-bundled-quantity__invalid-notice">
								<?php

								echo wp_kses_post(
									sprintf(
									// translators: 1. the minimum amount; 2. the maximum amount.
										__( 'You can choose a minimum quantity of %1$s and a maximum quantity of %2$s for this product!', 'yith-woocommerce-product-bundles' ),
										'<span class="yith-wcpb-bundled-quantity__invalid-notice__min-qty">' . $min_qty . '</span>',
										'<span class="yith-wcpb-bundled-quantity__invalid-notice__max-qty">' . $max_qty . '</span>'
									)
								);
								?>
							</div>
						<?php else : ?>
							<input name="yith_bundle_quantity_<?php echo esc_attr( $item_id ); ?>" value="<?php echo esc_attr( $initial_quantity ); ?>"
									type="hidden" class="yith-wcpb-bundled-quantity"
									data-item-id="<?php echo esc_attr( $item_id ); ?>">
						<?php endif; ?>
					</div>

					<?php do_action( 'yith_wcpb_after_bundled_item_quantity_input', $bundled_item, $min_qty, $max_qty ); ?>

					<?php if ( ! $bundled_item->has_variables() ) : ?>
						<div class="yith-wcpb-product-bundled-item-data__availability yith-wcpb-product-bundled-item-availability not-variation">
							<?php echo apply_filters( 'yith_wcpb_bundled_item_stock_html', wc_get_stock_html( $bundled_product ), $bundled_product, $bundled_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
<?php endif; ?>

<?php do_action( 'yith_wcpb_after_bundle_items_list' ); ?>
