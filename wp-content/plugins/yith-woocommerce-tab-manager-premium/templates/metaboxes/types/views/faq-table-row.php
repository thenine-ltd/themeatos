<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
$fields_name = array(
	'faq_name' => 'question',
	'faq_desc' => 'answer',


);
$metabox_name = 'yit_metaboxes[' . $field_id . ']';

?>
<tr>
	<td class="sort"></td>
	<?php foreach ( $fields_name as $key => $field_name ) : ?>

		<td class="<?php echo esc_attr( $key ); ?>">
			<?php $field_value = isset( $value[ $field_name ] ) ? $value[ $field_name ] : ''; ?>

			<?php
			if ( 'faq_name' === $key ) {
				$rows = 5;
			} else {
				$rows = 10;
			}
			?>
			<textarea  rows="<?php echo esc_attr( $rows ); ?>" name="<?php echo esc_attr( $metabox_name ); ?>[<?php echo esc_attr( $i ); ?>][<?php echo esc_attr( $field_name ); ?>]"><?php echo esc_attr( $field_value ); ?></textarea>

		</td>

	<?php endforeach; ?>
	<td width="1%"><a href="#" class="delete"><?php esc_html_e( 'Remove', 'yith-woocommerce-tab-manager' ); ?></a></td>
</tr>
