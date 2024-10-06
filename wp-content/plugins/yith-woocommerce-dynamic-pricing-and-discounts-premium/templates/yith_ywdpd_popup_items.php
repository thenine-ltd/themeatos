<?php
if ( ! defined( 'ABSPATH' ) || empty( $item_ids ) ) {
	exit;
}
?>
<ul class="ywdpd_popup_items owl-carousel owl-theme <?php echo esc_attr( $list_class ); ?>">
	<?php
	foreach ( $item_ids as $item_id ) :
		if ( 'product' === $item_class ) {
			$object = wc_get_product( $item_id );

			if ( $object->is_in_stock() ) {
				$image = $object->get_image();
				$data  = array(
					'product_id'      => $item_id,
					'ywdpd_rule_id'   => $rule_key,
					'product_type'    => $object->get_type(),
					'discount_type'   => $discount['type'],
					'discount_amount' => $discount['amount'],

				);

				if ( 'special_offer' === $list_class ) {
					$data['total_to_add'] = $total_to_add;
				}
				$data      = yith_plugin_fw_html_data_to_string( $data );
				$old_price = $object->get_price();
				if ( 'percentage' === $discount['type'] ) {
					if ( 1 === $discount['amount'] ) {
						$new_price = 0;
					} else {
						$new_price = $old_price - ( $old_price * $discount['amount'] );
						$new_price = wc_get_price_to_display( $object, array( 'price' => $new_price ) );
					}
				} elseif ( 'price' === $discount['type'] ) {
					$new_price = $old_price - $discount['amount'];
					$new_price = $new_price > 0 ? wc_get_price_to_display( $object, array( 'price' => $new_price ) ) : 0;
				} else {
					$new_price = wc_get_price_to_display( $object, array( 'price' => $discount['amount'] ) );
				}


				$row          = "<div class='ywdpd_single_product' data-ywdpd_action='" . $list_class . "'>";
				$row         .= '<div class="ywdpd_product_details">';
				$row         .= "<span class='ywdpd_image_badge'><span class='ywdp_check_icon'></span></span>";
				$row         .= "<div class='ywdpd_image_container'>";
				$row         .= $image;
				$row         .= '</div>';
				$row         .= '</div>';
				$product_name = 'variation' === $object->get_type() ? $object->get_formatted_name() : $object->get_name();
				$row         .= '<h5>' . wp_kses_post( $product_name ) . '</h5>';
				$row         .= '<span class="price">' . wc_format_sale_price( wc_get_price_to_display( $object, array( 'price' => $old_price ) ), $new_price ) . '</span>';
				$row         .= '<div class="ywdpd_qty_fields_container">';

				$show_qty_field   = 'simple' === $object->get_type() || ( 'variation' === $object->get_type() && yith_dynamic_is_variation_attributes_set( $object ) );
				$add_to_cart_text = $object->add_to_cart_text();
				if ( $show_qty_field ) {
					$row         .= '<div class="ywdpd_qty_field">';
					$row         .= '<div class="ywdpd_qty_input">';
					$row         .= '<div class="ywdpd_qty_label">' . esc_html__( 'Qty in cart', 'ywdpd' ) . '</div>';
					$row         .= '<span class="ywdpd_qty"></span>';
					$row         .= '<span class="ywdpd_qty_arrows">';
					$row         .= '<span class="ywdpd_qty_remove button"><span></span></span>';
					$row         .= '<span class="ywdpd_qty_decrease button"><span></span></span>';
					$row         .= '<span class="ywdpd_qty_increase button"><span></span></span>';
					$row         .= '</span>';
					$row         .= '</div>';
					$row         .= '</div>';
					$button_class = $object->get_type();
				} else {
					$button_class = 'variable';
					if ( 'variation' === $object->get_type() ) {
						$parent_id        = $object->get_parent_id();
						$parent_product   = wc_get_product( $parent_id );
						$add_to_cart_text = $parent_product->add_to_cart_text();
					}
				}
				$row .= '</div>';
				$row .= sprintf( "<span class='single_add_to_cart_button button %s'>%s</span>", $button_class, $add_to_cart_text );
				$row .= '</div>';
			}
		} else {
			$data     = '';
			$term_obj = get_term( $item_id, $taxonomy_name );

			$term_name = ! is_wp_error( $term_obj ) ? $term_obj->name : '';
			$term_link = get_term_link( $term_obj );

			ob_start();
			woocommerce_subcategory_thumbnail( $term_obj );
			$image = ob_get_contents();
			ob_end_clean();
			$row  = $image;
			$row .= '<h5>' . wp_kses_post( $term_name ) . '</h5>';
			$row .= sprintf( '<a href="%s" class="product_taxonomy button">%s</a>', $term_link, __( 'Browse the products ', 'ywdpd' ) );
		}
		?>
		<li class="<?php echo esc_attr( $item_class ); ?> item" <?php echo $data; //phpcs:ignore WordPress.Security.EscapeOutput ?> >
			<?php echo $row; //phpcs:ignore WordPress.Security.EscapeOutput ?>
			
		</li>
	<?php endforeach; ?>
</ul>
