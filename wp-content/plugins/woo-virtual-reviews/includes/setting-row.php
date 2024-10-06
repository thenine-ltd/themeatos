<?php

namespace WooVR;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Setting_Row {
	protected static $instance = null;

	public function __construct() {
	}

	public static function instance() {
		return self::$instance == null ? self::$instance = new self() : self::$instance;
	}

	public static function output_fields( $options ) {
		$data = Data::instance();
		foreach ( $options as $option ) {
			if ( ! isset( $option['type'] ) ) {
				continue;
			}

			// Custom attribute handling.
			$custom_attributes = array();

			if ( ! empty( $option['custom_attributes'] ) && is_array( $option['custom_attributes'] ) ) {
				foreach ( $option['custom_attributes'] as $attribute => $attribute_value ) {
					$custom_attributes[] = esc_attr( $attribute ) . '=' . esc_attr( $attribute_value );
				}
			}
			$custom_attributes = implode( ' ', $custom_attributes );

			$type        = $option['type'];
			$id          = $option['id'] ?? $type;
			$title       = $option['title'] ?? '';
			$description = $option['desc'] ?? '';
			$multiple    = $type == 'multiselect' ? '[]' : '';

			$_id          = isset( $option['id'] ) ? str_replace( '_', '-', $id ) : '';
			$_class       = isset( $option['class'] ) ? 'wvr_params-' . $option['class'] : '';
			$_name        = isset( $option['id'] ) ? "wvr_params[{$id}]{$multiple}" : '';
			$_value       = $data->get_param( $id ) ? $data->get_param( $id ) : ( $option['value'] ?? '' );
			$_placeholder = $option['placeholder'] ?? '';

			$value        = $id ? $data->get_param( $id ) : ( $option['value'] ?? '' );

			// Switch based on type.
			switch ( $type ) {

				// Section Titles.
				case 'title':
					echo ! empty( $title ) ? '<h2>' . esc_html( $title ) . '</h2>' : '';
					if ( ! empty( $description ) ) {
						echo '<div id="' . esc_attr( sanitize_title( $id ) ) . '-description">';
						echo wp_kses_post( wpautop( wptexturize( $description ) ) );
						echo '</div>';
					}
					echo '<table class="form-table">' . "\n\n";
					break;

				// Section Ends.
				case 'sectionend':
					echo '</table>';
					break;

				// Standard text inputs and subtypes like 'number'.
				case 'text':
				case 'password':
				case 'datetime':
				case 'datetime-local':
				case 'date':
				case 'month':
				case 'time':
				case 'week':
				case 'number':
				case 'email':
				case 'url':
				case 'tel':
				case 'color':
					$_class .= $type == 'color' ? 'wvr-color-picker' : '';
					$type = $type == 'color' ? 'text' : $type;
					?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $_id ); ?>"><?php echo esc_html( $title ); ?></label>
                        </th>
                        <td class="<?php echo esc_attr( sanitize_title( $type ) ); ?>">
                            <div class="wvr_params-<?php echo esc_attr( $_id ) ?>-field">
								<?php
								printf(
									"<input type='%s' id='%s' class='%s' name='%s' value='%s'  placeholder='%s' %s>",
									esc_attr( $type ),
									esc_attr( $_id ),
									esc_attr( $_class ),
									esc_attr( $_name ),
									esc_attr( $_value ),
									esc_attr( $_placeholder ),
									esc_attr( $custom_attributes )
								);
								do_action( 'wvr_params_after_field_' . $id );
								?>

                            </div>
                            <p class="wvr_params-description">
								<?php
								if ( ! empty( $description ) ) {
									echo wp_kses_post( $description );
								}
								?>
                            </p>
                        </td>
                    </tr>
					<?php
					break;

				//Checkbox
				case 'checkbox':
					?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></label>
                        </th>
                        <td class="<?php echo esc_attr( sanitize_title( $type ) ); ?>">
                            <div class="vi-ui toggle checkbox">
								<?php
								printf(
									"<input type='%s' id='%s' class='%s' name='%s' value='1' %s %s>",
									esc_attr( $type ),
									esc_attr( $_id ),
									esc_attr( $_class ),
									esc_attr( $_name ),
									$custom_attributes,
									$_value == 1 ? 'checked' : ''
								);
								do_action( 'wvr_params_after_field_' . $id );
								?>
                                <label></label>
                            </div>
                            <p class="wvr_params-description">
								<?php
								if ( ! empty( $description ) ) {
									echo wp_kses_post( $description );
								}
								?>
                            </p>
                        </td>
                    </tr>
					<?php
					break;

				case 'select':
				case 'multiselect':
					?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></label>
                        </th>
                        <td class="<?php echo esc_attr( sanitize_title( $type ) ); ?>">
                            <div>
                                <select class="vi-ui ui selection dropdown fluid"
									<?php
									printf( 'name="%s" id="%s" class="%s" %s', esc_attr( $_name ), esc_attr( $_id ), esc_attr( $_class ), esc_attr( $custom_attributes ) );
									echo 'multiselect' === $type ? 'multiple="multiple"' : '';
									?>
                                >

									<?php foreach ( $option['options'] as $key => $page_name ) {
										$selected = is_array( $value ) ? ( in_array( $key, $value ) ? 'selected' : '' ) : ( $key == $value ? 'selected' : '' );
										echo sprintf( "<option value='%1s' %2s >%3s</option>", esc_attr( $key ), esc_attr( $selected ), esc_html( $page_name ) );
									} ?>
                                </select>
                            </div>
                            <p class="wvr_params-description">
								<?php
								if ( ! empty( $description ) ) {
									echo wp_kses_post( $description );
								}
								?>
                            </p>
                        </td>
                    </tr>
					<?php
					break;

				case 'textarea':
					?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $_id ); ?>"><?php echo esc_html( $title ); ?></label>
                        </th>
                        <td class="<?php echo esc_attr( sanitize_title( $type ) ); ?>">
                            <div class="wvr_params-<?php echo esc_attr( $_id ) ?>-field">
								<?php
								if ( is_array( $_value ) ) {
									$separator = isset( $option['separator'] ) ? $option['separator'] : "\n";
									$_value    = implode( $separator, $_value );
								}

								printf(
									"<textarea type='%s' id='%s' class='%s' name='%s'  placeholder='%s' %s>%s</textarea>",
									esc_attr( $type ),
									esc_attr( $_id ),
									esc_attr( $_class ),
									esc_attr( $_name ),
									esc_html( $_placeholder ),
									esc_attr( $custom_attributes ),
									wp_kses_post( $_value )
								);
								do_action( 'wvr_params_after_field_' . $id );
								?>

                            </div>
                            <p class="wvr_params-description">
								<?php
								if ( ! empty( $description ) ) {
									echo wp_kses_post( $description );
								}
								?>
                            </p>
                        </td>
                    </tr>
					<?php
					break;

				case 'radio':
					?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $title ); ?></label>
                        </th>
                        <td class="<?php echo esc_attr( sanitize_title( $type ) ); ?>">
                            <div>
								<?php foreach ( $option['options'] as $option ) {
									$__value  = $option['value'] ?? '';
									$has_icon = ! empty( $option['icon'] ) ? ' has-icon' : '';
									echo sprintf( "<span class='radio-element'><input type='radio' name='%s' class='%s' value='%s' %s><label class='%s'></label></span>",
										esc_attr( $_name ),
										esc_attr( $_class . $has_icon ),
										esc_attr( $__value ),
										esc_attr( $value == $__value ? 'checked' : '' ),
										esc_attr( $option['label'] ?? $option['icon'] ?? '' )
									);
								} ?>
                            </div>
                            <p class="wvr_params-description">
								<?php
								if ( ! empty( $description ) ) {
									echo wp_kses_post( $description );
								}
								?>
                            </p>
                        </td>
                    </tr>
					<?php
					break;

				case 'pro_feature':
					?>
                    <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="<?php echo esc_attr( $_id ); ?>"><?php echo esc_html( $title ); ?></label>
                        </th>
                        <td>
							<?php get_pro_button(); ?>
                        </td>
                    </tr>
					<?php
					break;

				// Default: run an action.
				default:
					do_action( 'wvr_params_admin_field_' . $type, $option );
					break;

			}
		}
	}
}

