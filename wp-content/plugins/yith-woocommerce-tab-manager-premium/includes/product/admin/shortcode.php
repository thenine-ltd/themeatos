<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $product_object;
$product_id = $product_object->get_id();
$tab_id     = $tab->ID;
$shortcode  = get_post_meta( $product_id, $tab_id . '_custom_shortcode', true );
?>
<div id="<?php echo esc_attr( $tab_id ); ?>_tab" class="panel woocommerce_options_panel">
	<div class="custom_tab_options" >
		<p class="form-field">
			<label for="custom_shortcode_tab"><?php esc_html_e( 'Shortcode', 'yith-woocommerce-tab-manager' ); ?></label>
			<textarea name="<?php echo esc_attr( $field_name ); ?>[shortcode]" placeholder="<?php esc_attr_e( 'Add a shortcode here', 'yith-woocommerce-tab-manager' ); ?>"><?php echo esc_attr( $shortcode ); ?></textarea>
		</p>
	 </div>
</div>
