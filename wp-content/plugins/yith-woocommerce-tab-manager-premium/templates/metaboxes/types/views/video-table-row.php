<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$fields_name  = array(
	'video_name' => 'video_name',
	'video_type' => 'host',
	'video_id'   => 'id',
	'video_url'  => 'url',
);
$metabox_name = 'yit_metaboxes[' . $field_id . '][video_info]';

?>
<tr>
	<td class="sort"></td>
	<?php foreach ( $fields_name as $key => $field_name ) : ?>

		<td class="<?php echo esc_attr( $key ); ?>">
			<?php $field_value = isset( $value[ $field_name ] ) ? $value[ $field_name ] : ''; ?>
			<?php if ( 'video_type' === $key ) : ?>
				<select name="<?php echo esc_attr( $metabox_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $field_name ); ?>]">
					<option value="youtube" <?php selected( 'youtube', esc_attr( $field_value ) ); ?>><?php esc_html_e( 'YouTube', 'yith-woocommerce-tab-manager' ); ?></option>
					<option value="vimeo" <?php selected( 'vimeo', esc_attr( $field_value ) ); ?>><?php esc_html_e( 'Vimeo', 'yith-woocommerce-tab-manager' ); ?></option>
				</select>
			<?php else : ?>

			<input type="text" name="<?php echo esc_attr( $metabox_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $field_name ); ?>]" value="<?php echo esc_attr( $field_value ); ?>"/>
			<?php endif; ?>
		</td>

	<?php endforeach; ?>
	<td width="1%"><a href="#" class="delete"><?php esc_html_e( 'Remove', 'yith-woocommerce-tab-manager' ); ?></a></td>
</tr>
