<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $post;

extract( $args );

$value = get_post_meta( $post->ID, $id, true );

$radio_args = array(
	'type'    => 'radio',
	'id'      => 'schedule_mode',
	'name'    => $name . '[schedule_type]',
	'options' => array(
		'no_schedule'    => __( 'Enable the rule now and end it manually', 'ywdpd' ),
		'schedule_dates' => __( 'Schedule a start and end time', 'ywdpd' ),
	),
	'value'   => isset( $value['schedule_type'] ) ? $value['schedule_type'] : 'no_schedule',
);

$date_from = isset( $value['schedule_from'] ) ? $value['schedule_from'] : '';
$date_to   = isset( $value['schedule_to'] ) ? $value['schedule_to'] : '';
?>
<div id="<?php echo esc_attr( $id ); ?>-container" 
					<?php
					echo yith_field_deps_data( $args ); //phpcs:ignore WordPress.Security.EscapeOutput
					?>
													 class="yith-plugin-fw-metabox-field-row">
	<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
	<div class="yith-plugin-fw-field-wrapper">
		<?php
		echo yith_plugin_fw_get_field( $radio_args ); //phpcs:ignore WordPress.Security.EscapeOutput
		?>
		<div class="yith-plugin-fw-field-schedule">
			<span class="from_datepicker">
				<label for="_schedule_from"><?php esc_html_e( 'From', 'ywdpd' ); ?></label>
				<input type="text" id="_schedule_from" autocomplete="off" class="yith-plugin-fw-text-input datepicker" value="<?php echo esc_attr( $date_from ); ?>" name="<?php echo esc_attr( $name ); ?>[schedule_from]" />
				<span class="yith-icon yith-icon-calendar2"></span>
			</span>

			<span class="to_datepicker">
				<label for="_schedule_to"><?php esc_html_e( 'To', 'ywdpd' ); ?></label>
				<input type="text" id="_schedule_to" autocomplete="off" class="yith-plugin-fw-text-input datepicker" value="<?php echo esc_attr( $date_to ); ?>" name="<?php echo esc_attr( $name ); ?>[schedule_to]" />
				<span class="yith-icon yith-icon-calendar2"></span>
			</span>

		</div>
	</div>
	<div class="clear"></div>
	<span class="description"><?php echo esc_html( $desc ); ?></span>
</div>
