<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $post;
extract( $args );
?>
<div id="<?php echo esc_attr( $id ); ?>-container"
	 class="yith-plugin-fw-metabox-field-row" <?php echo yith_field_deps_data( $args ); //phpcs:ignore WordPress.Security.EscapeOutput ?> >
	<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
	<div class="yith-plugin-fw-field-wrapper yith-plugin-fw-field-wrapper-advanced-simple-text"><?php echo esc_html( $desc ); ?></div>
	<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="yit_metaboxes[_discount_mode]" value="exclude_items">
</div>

