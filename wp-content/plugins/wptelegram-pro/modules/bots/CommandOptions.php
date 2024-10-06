<?php
/**
 * Create metabox for command options
 *
 * @link       https://wptelegram.pro
 * @since      1.0.0
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots
 */

namespace WPTelegram\Pro\modules\bots;

use WPTelegram\Pro\modules\BaseClass;

/**
 * Class responsible for displaying bot command options
 *
 * @package    WPTelegram\Pro
 * @subpackage WPTelegram\Pro\modules\bots
 * @author     WP Socio
 */
class CommandOptions extends BaseClass {

	/**
	 * Get all the settings sections.
	 *
	 * @since    1.0.0
	 */
	public function render_command_option_metabox() {

		/**
		 * Initiate the metabox
		 */
		$box  = [
			'id'           => 'wptelegram_pro_bots_cmd_bot_options',
			'title'        => __( 'Bots', 'wptelegram-pro' ),
			'object_types' => [ 'wptgpro_bot_cmd' ],
			'context'      => 'side',
			'priority'     => 'low',
			'show_names'   => true,
		];
		$cmb2 = new_cmb2_box( $box );

		$prefix = '_bot_cmd_';
		$fields = [
			[
				'id'                => $prefix . 'bots',
				'type'              => 'multicheck',
				'desc'              => __( 'Select the bot(s) which can respond to this command', 'wptelegram-pro' ),
				'after'             => sprintf( '<p><span style="color:#f10e0e;">%s</span> %s</p>', __( 'Note:', 'wptelegram-pro' ), sprintf( /* translators: bot username */ __( 'If no bot is selected, the command will be associated with the primary bot (%s)', 'wptelegram-pro' ), '<b>@' . WPTG_Pro()->options()->get( 'bot_username', '' ) . '</b>' ) ),
				'options_cb'        => [ WPTG_Pro()->helpers, 'get_all_bot_tokens' ],
				'select_all_button' => false,
				'show_names'        => false,
				'column'            => [
					'position' => 2,
					'name'     => __( 'Bots', 'wptelegram-pro' ),
				],
			],
		];

		$fields = (array) apply_filters( 'wptelegram_pro_bots_command_bots_options_fields', $fields );

		foreach ( $fields as $field ) {
			$cmb2->add_field( $field );
		}

		/**
		 * Initiate the metabox
		 */
		$box  = [
			'id'           => 'wptelegram_pro_bots_cmd_general_options',
			'title'        => __( 'Command Options', 'wptelegram-pro' ),
			'object_types' => [ 'wptgpro_bot_cmd' ],
			'context'      => 'normal',
			'priority'     => 'high',
			'show_names'   => true,
		];
		$cmb2 = new_cmb2_box( $box );

		$fields = [
			[
				'name'            => __( 'Name', 'wptelegram-pro' ),
				'desc'            => __( 'Command name in English letters without spaces.', 'wptelegram-pro' ) . ' [a-z0-9_]',
				'id'              => $prefix . 'name',
				'type'            => 'text_medium',
				'sanitization_cb' => [ __CLASS__, 'sanitize_command_name' ],
				'column'          => [
					'position' => 3,
				],
				'attributes'      => [
					'required' => 'required',
				],
			],
			[
				'name'            => __( 'Aliases', 'wptelegram-pro' ) . ' ' . __( '(Optional)', 'wptelegram-pro' ),
				'desc'            => __( 'Aliases are the command equivalents', 'wptelegram-pro' ),
				'id'              => $prefix . 'aliases',
				'type'            => 'text_medium',
				'repeatable'      => true,
				'column'          => [
					'position' => 4,
					'name'     => __( 'Aliases', 'wptelegram-pro' ),
				],
				'display_cb'      => [ $this, 'aliases_display_cb' ],
				'text'            => [
					'add_row_text'            => __( 'Add Alias', 'wptelegram-pro' ),
					'remove_row_button_title' => __( 'Remove Alias', 'wptelegram-pro' ),
				],
				'sanitization_cb' => [ __CLASS__, 'sanitize_command_name' ],
			],
			[
				'name'   => __( 'Description', 'wptelegram-pro' ),
				'desc'   => __( 'To be shown in the command list', 'wptelegram-pro' ),
				'id'     => $prefix . 'desc',
				'type'   => 'text',
				'column' => [
					'position' => 5,
				],
			],
			[
				'name'            => __( 'Response', 'wptelegram-pro' ),
				'type'            => 'wysiwyg',
				'desc'            => __( 'The response to be sent', 'wptelegram-pro' ),
				'id'              => $prefix . 'response',
				'options'         => [
					'media_buttons' => false,
				],
				'sanitization_cb' => [ __CLASS__, 'sanitize_command_response' ],
			],
		];

		$fields = (array) apply_filters( 'wptelegram_pro_bots_command_general_options_fields', $fields );

		foreach ( $fields as $field ) {
			$cmb2->add_field( $field );
		}
	}

	/**
	 * Handles sanitization for the command name field.
	 *
	 * @param  mixed      $value      The unsanitized value from the form.
	 * @param  array      $field_args Array of field arguments.
	 * @param  CMB2_Field $field      The field object.
	 *
	 * @return mixed                  Sanitized value to be stored.
	 */
	public static function sanitize_command_name( $value, $field_args, $field ) {
		$value = preg_replace( '/\W/', '', $value );

		if ( is_array( $value ) ) {
			return array_map( 'strtolower', $value );
		}
		return strtolower( $value );
	}

	/**
	 * Handles sanitization for the command content field.
	 *
	 * @param  mixed      $value      The unsanitized value from the form.
	 * @param  array      $field_args Array of field arguments.
	 * @param  CMB2_Field $field      The field object.
	 *
	 * @return mixed                  Sanitized value to be stored.
	 */
	public static function sanitize_command_response( $value, $field_args, $field ) {
		return apply_filters( 'content_save_pre', $value );
	}

	/**
	 * Manually render aliases column display.
	 *
	 * @param  array      $field_args Array of field arguments.
	 * @param  CMB2_Field $field      The field object.
	 */
	public function aliases_display_cb( $field_args, $field ) {
		?>
		<div class="<?php echo $field->row_classes(); // phpcs:ignore ?>">
			<p><?php print( implode( ' | ', (array) $field->escaped_value() ) ); // phpcs:ignore ?></p>
		</div>
		<?php
	}
}
