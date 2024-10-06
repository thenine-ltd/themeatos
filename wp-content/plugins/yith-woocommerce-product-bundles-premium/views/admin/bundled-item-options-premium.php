<?php
/**
 * Admin Bundle Item Options
 *
 * @var YITH_WC_Bundled_item $bundled_item The bundled product.
 * @var int                  $metabox_id   The metabox ID.
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.


$product_id      = $bundled_item->get_product_id();
$bundled_product = $bundled_item->get_product();
$bundle_product  = $bundled_item->get_bundle();
$bundle_id       = $bundle_product->get_id();
?>

<?php if ( $bundled_product && $bundled_item ) : ?>
	<div class="yith-wcpb-bundled-item-fields">
		<?php if ( $bundled_product->is_type( 'variable' ) ) : ?>
			<div class="yith-wcpb-form-field">
				<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'Filter product variations', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
				<div class="yith-wcpb-form-field__content">
					<select multiple="multiple"
							id="<?php echo esc_attr( yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_filtered_variations' ) ); ?>"
							name="<?php echo esc_attr( yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_filtered_variations', true ) ); ?>"
							style="width: 95%;"
							data-placeholder="<?php esc_attr_e( 'Choose variations&hellip;', 'yith-woocommerce-product-bundles' ); ?>" class="wc-enhanced-select">
						<?php
						$variations         = $bundled_product->get_children();
						$attribute_meta_key = $bundled_product instanceof WC_Data ? '_attributes' : '_product_attributes';
						$attributes         = maybe_unserialize( yit_get_prop( $bundled_product, $attribute_meta_key, true ) );

						$filtered_attributes = array();

						foreach ( $variations as $variation ) {
							$description    = '';
							$variation_data = get_post_meta( $variation );

							foreach ( $attributes as $attribute ) {
								if ( ! $attribute['is_variation'] ) {
									continue;
								}

								$variation_selected_value = isset( $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] ) ? $variation_data[ 'attribute_' . sanitize_title( $attribute['name'] ) ][0] : '';

								$description_name  = esc_html( wc_attribute_label( $attribute['name'] ) );
								$description_value = __( 'Any', 'woocommerce' ) . ' ' . $description_name;

								if ( $attribute['is_taxonomy'] ) {
									$post_terms = wp_get_post_terms( $bundled_product->get_id(), $attribute['name'] );

									foreach ( $post_terms as $term ) {
										if ( $variation_selected_value == $term->slug ) {
											$description_value = apply_filters( 'woocommerce_variation_option_name', esc_html( $term->name ) );
										}
									}
								} else {
									$options = array_map( 'trim', explode( WC_DELIMITER, $attribute['value'] ) );

									foreach ( $options as $option ) {
										if ( sanitize_title( $variation_selected_value ) == sanitize_title( $option ) ) {
											$description_value = esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) );
										}
									}
								}
								$description .= $description_name . ': ' . $description_value . ', ';
							}

							if ( is_array( $bundled_item->filtered_variations ) && in_array( $variation, $bundled_item->filtered_variations ) ) {
								$selected = 'selected="selected"';
							} else {
								$selected = '';
							}
							echo '<option value="' . esc_attr( $variation ) . '" ' . $selected . '>#' . esc_html( $variation ) . ' - ' . rtrim( $description, ', ' ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</select>
				</div>

				<div class="yith-wcpb-form-field__description">
					<?php esc_html_e( 'Select the variations allowed for this item.', 'yith-woocommerce-product-bundles' ); ?>
					<br/>
					<?php esc_html_e( 'Leave empty to allow all variations.', 'yith-woocommerce-product-bundles' ); ?>
				</div>
			</div>
			<div class="yith-wcpb-form-field">
				<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'Default attributes', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
				<div class="yith-wcpb-form-field__content">
					<?php
					foreach ( $attributes as $attribute ) {
						if ( ! $attribute['is_variation'] ) {
							continue;
						}

						$_value = ( isset( $bundled_item->selection_overrides[ sanitize_title( $attribute['name'] ) ] ) ) ? $bundled_item->selection_overrides[ sanitize_title( $attribute['name'] ) ] : '';

						$_options = array(
							// translators: %s is the name of an attribute; example: "No default Size"
							'' => sprintf( __( 'No default %s...', 'yith-woocommerce-product-bundles' ), wc_attribute_label( $attribute['name'] ) ),
						);

						if ( $attribute['is_taxonomy'] ) {
							$post_terms = wp_get_post_terms( $bundled_product->get_id(), $attribute['name'] );

							sort( $post_terms );
							foreach ( $post_terms as $term ) {
								$_options[ $term->slug ] = esc_html( apply_filters( 'woocommerce_variation_option_name', $term->name ) );
							}
						} else {
							$options = array_map( 'trim', explode( WC_DELIMITER, $attribute['value'] ) );
							sort( $options );

							foreach ( $options as $option ) {
								$option              = esc_attr( $option );
								$_options[ $option ] = esc_html( apply_filters( 'woocommerce_variation_option_name', $option ) );
							}
						}

						yith_plugin_fw_get_field(
							array(
								'type'    => 'select',
								'id'      => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_selection_overrides' ) . '-' . sanitize_title( $attribute['name'] ),
								'name'    => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_selection_overrides' ) . '[' . sanitize_title( $attribute['name'] ) . ']',
								'options' => $_options,
								'value'   => $_value,
							),
							true,
							false
						);
					}
					?>
				</div>

				<div class="yith-wcpb-form-field__description">
					<?php esc_html_e( 'Set the attributes to show as defaults in bundle section.', 'yith-woocommerce-product-bundles' ); ?>
				</div>
			</div>
		<?php endif; ?>
		<div class="yith-wcpb-form-field">
			<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'Product name', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
			<div class="yith-wcpb-form-field__content">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'    => 'radio',
						'class'   => 'yith-wcpb-bp-show-name',
						'id'      => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_show_name' ),
						'name'    => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_show_name' ),
						'options' => array(
							'product' => esc_html__( 'Use the default product name', 'yith-woocommerce-product-bundles' ),
							'custom'  => esc_html__( 'Enter a custom product name', 'yith-woocommerce-product-bundles' ),
							'hide'    => esc_html__( 'Leave empty - hide product name', 'yith-woocommerce-product-bundles' ),
						),
						'value'   => $bundled_item->show_name,
					),
					true
				);
				?>
			</div>

			<div class="yith-wcpb-form-field__description">
				<?php esc_html_e( 'Choose the product name to be shown.', 'yith-woocommerce-product-bundles' ); ?>
			</div>
		</div>
		<div class="yith-wcpb-form-field yith-wcpb-show-conditional" data-dep-selector="#<?php echo esc_attr( yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_show_name' ) ); ?>" data-dep-value="custom">
			<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'Enter name', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
			<div class="yith-wcpb-form-field__content">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'text',
						'class' => 'yith-wcpb-bp-name yith-wcpb-bp',
						'id'    => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_title' ),
						'name'  => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_title' ),
						'value' => $bundled_item->title,
					),
					true
				);
				?>
			</div>

			<div class="yith-wcpb-form-field__description">
				<?php esc_html_e( 'Enter a name to identify this product.', 'yith-woocommerce-product-bundles' ); ?>
			</div>
		</div>
		<div class="yith-wcpb-form-field">
			<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'Product description', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
			<div class="yith-wcpb-form-field__content">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'    => 'radio',
						'class'   => 'yith-wcpb-bp-show-description',
						'id'      => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_show_description' ),
						'name'    => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_show_description' ),
						'options' => array(
							'product' => esc_html__( 'Use the default product description', 'yith-woocommerce-product-bundles' ),
							'custom'  => esc_html__( 'Enter a custom product description', 'yith-woocommerce-product-bundles' ),
							'hide'    => esc_html__( 'Leave empty - hide product description', 'yith-woocommerce-product-bundles' ),
						),
						'value'   => $bundled_item->show_description,
					),
					true
				);
				?>
			</div>

			<div class="yith-wcpb-form-field__description">
				<?php esc_html_e( 'Choose the product description to be shown.', 'yith-woocommerce-product-bundles' ); ?>
			</div>
		</div>
		<div class="yith-wcpb-form-field yith-wcpb-show-conditional" data-dep-selector="#<?php echo esc_attr( yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_show_description' ) ); ?>" data-dep-value="custom">
			<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'Enter description', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
			<div class="yith-wcpb-form-field__content">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'textarea',
						'class' => 'yith-wcpb-bp-description yith-wcpb-bp',
						'id'    => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_description' ),
						'name'  => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_description' ),
						'value' => $bundled_item->description,
					),
					true
				);
				?>
			</div>
			<div class="yith-wcpb-form-field__description">
				<?php esc_html_e( 'Enter a description for this product.', 'yith-woocommerce-product-bundles' ); ?>
			</div>
		</div>
		<div class="yith-wcpb-form-field">
			<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'Hide product image', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
			<div class="yith-wcpb-form-field__content">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'onoff',
						'class' => 'yith-wcpb-bp-hide-bundled-thumbs yith-wcpb-bp',
						'id'    => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_hide_bundled_thumbs' ),
						'name'  => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_hide_bundled_thumbs' ),
						'value' => $bundled_item->hide_thumbnail ? 'yes' : 'no',
					),
					true
				);
				?>
			</div>

			<div class="yith-wcpb-form-field__description">
				<?php esc_html_e( 'Enable if you want to hide the product image.', 'yith-woocommerce-product-bundles' ); ?>
			</div>
		</div>

		<div class="yith-wcpb-form-field">
			<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'Apply a discount', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
			<div class="yith-wcpb-form-field__content">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'onoff',
						'class' => 'yith-wcpb-bp-apply-discount',
						'id'    => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_apply_discount' ),
						'name'  => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_apply_discount' ),
						'value' => $bundled_item->apply_discount ? 'yes' : 'no',
					),
					true
				);
				?>
			</div>

			<div class="yith-wcpb-form-field__description">
				<?php esc_html_e( 'Enable if you want to apply a discount to this product.', 'yith-woocommerce-product-bundles' ); ?>
			</div>
		</div>

		<div class="yith-wcpb-form-field yith-wcpb-show-conditional" data-dep-selector="#<?php echo esc_attr( yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_apply_discount' ) ); ?>" data-dep-value="yes">
			<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'Apply a discount of', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
			<div class="yith-wcpb-form-field__content">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'number',
						'class' => 'yith-wcpb-bp-discount yith-wcpb-short-inline-field',
						'id'    => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_discount' ),
						'name'  => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_discount' ),
						'value' => $bundled_item->discount,
						'min'   => '0',
						'max'   => '100',
						'step'  => apply_filters( 'yith_wcpb_step_discount_field', 1 ),
					),
					true,
					false
				);
				?>
				<span class="yith-wcpb-bp-discount__percentage"></span>
			</div>

			<div class="yith-wcpb-form-field__description">
				<?php esc_html_e( 'Set the discount percentage for this product.', 'yith-woocommerce-product-bundles' ); ?>
			</div>
		</div>

		<div class="yith-wcpb-form-field">
			<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'User can buy', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
			<div class="yith-wcpb-form-field__content">
				<?php
				$min_input = yith_plugin_fw_get_field(
					array(
						'type'  => 'number',
						'class' => 'yith-wcpb-short-inline-field yith-wcpb-bp-min-qty',
						'id'    => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_min_qty' ),
						'name'  => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_min_qty' ),
						'min'   => '0',
						'step'  => '1',
						'value' => $bundled_item->min_quantity,
					),
					false,
					false
				);

				$max_input = yith_plugin_fw_get_field(
					array(
						'type'  => 'number',
						'class' => 'yith-wcpb-short-inline-field yith-wcpb-bp-max-qty',
						'id'    => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_max_qty' ),
						'name'  => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_max_qty' ),
						'min'   => '0',
						'step'  => '1',
						'value' => $bundled_item->max_quantity,
					),
					false,
					false
				);

				// translators: %1$s is the numeric input field of the minimum amount; %2$s is the numeric input field of the maximum amount
				echo sprintf( esc_html__( 'a minimum quantity of %1$s and a maximum quantity of %2$s', 'yith-woocommerce-product-bundles' ), $min_input, $max_input ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>

			<div class="yith-wcpb-form-field__description">
				<?php esc_html_e( 'Set the minimum and the maximum quantity that the user can purchase.', 'yith-woocommerce-product-bundles' ); ?>
				<br/>
				<?php esc_html_e( 'Put the same value in each field to hide the quantity field.', 'yith-woocommerce-product-bundles' ); ?>
			</div>
		</div>

		<div class="yith-wcpb-form-field">
			<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'Set product as optional', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
			<div class="yith-wcpb-form-field__content">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'onoff',
						'class' => 'yith-wcpb-bp-optional yith-wcpb-bp',
						'id'    => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_optional' ),
						'name'  => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_optional' ),
						'value' => $bundled_item->optional ? 'yes' : 'no',
					),
					true
				);
				?>
			</div>

			<div class="yith-wcpb-form-field__description">
				<?php esc_html_e( 'If enabled, the user can choose to add this product to the bundle or not.', 'yith-woocommerce-product-bundles' ); ?>
			</div>
		</div>

		<div class="yith-wcpb-form-field">
			<label class="yith-wcpb-form-field__label"><?php echo esc_html_x( 'Hide product', 'Admin bundled item form field', 'yith-woocommerce-product-bundles' ); ?></label>
			<div class="yith-wcpb-form-field__content">
				<?php
				yith_plugin_fw_get_field(
					array(
						'type'  => 'onoff',
						'class' => 'yith-wcpb-bp-hide-item yith-wcpb-bp',
						'id'    => yith_wcpb_bundle_data_field_id( $metabox_id, 'bp_hide_item' ),
						'name'  => yith_wcpb_bundle_data_field_name( $metabox_id, 'bp_hide_item' ),
						'value' => $bundled_item->hidden ? 'yes' : 'no',
					),
					true
				);
				?>
			</div>

			<div class="yith-wcpb-form-field__description">
				<?php esc_html_e( 'Enable if you want to hide this product in the bundle.', 'yith-woocommerce-product-bundles' ); ?>
			</div>
		</div>
	</div> <!-- yith-wcpb-bundled-item-fields -->
<?php endif; ?>