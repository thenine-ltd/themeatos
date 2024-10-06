<?php

if ( ! function_exists( 'yith_wcpb_bundled_items_heading' ) ) {
	/**
	 * Print the bundle items heading
	 *
	 * @since 1.4.0
	 */
	function yith_wcpb_bundled_items_heading() {
		global $product;

		if ( $product && $product->is_type( 'yith_bundle' ) ) {
			$heading = yith_wcpb_settings()->get_option_and_translate( 'yith-wcpb-bundled-items-heading' );
			wc_get_template( 'single-product/add-to-cart/yith-bundled-items-heading.php', compact( 'product', 'heading' ), '', YITH_WCPB_TEMPLATE_PATH . '/premium/' );
		}
	}
}

add_action( 'yith_wcpb_before_add_to_cart_form', 'yith_wcpb_bundled_items_heading' );