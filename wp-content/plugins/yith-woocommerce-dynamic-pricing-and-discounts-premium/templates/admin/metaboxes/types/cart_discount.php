<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $post;

extract( $args );

$db_value           = get_post_meta( $post->ID, $id, true );
$limit              = empty( $db_value ) ? 1 : count( $db_value );
$cart_rules_options = YITH_WC_Dynamic_Pricing()->cart_rules_options;


?>
<div id="<?php echo esc_attr( $id ); ?>-container"
	 class="yith-plugin-fw-metabox-field-row" <?php echo yith_field_deps_data( $args ); //phpcs:ignore WordPress.Security.EscapeOutput ?> >
	<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
	<div class="yith-plugin-fw-field-wrapper">
		<div id="cart-rules">
			<?php
			for ( $i = 1; $i <= $limit; $i ++ ) :
				$rule_type_value = isset( $db_value[ $i ]['rules_type'] ) ? $db_value[ $i ]['rules_type'] : 'customers';
				$rule_type_field = array(
					'id'      => 'rules_type_' . $i,
					'name'    => $name . "[$i][rules_type]",
					'type'    => 'select',
					'class'   => 'wc-enhanced-select ywdpd_rule_type',
					'options' => array(
						'customers'     => __( 'Specific users', 'ywdpd' ),
						'products'      => __( 'Specific products', 'ywdpd' ),
						'cart_items'    => __( 'Items in cart', 'ywdpd' ),
						'cart_subtotal' => __( 'Cart subtotal', 'ywdpd' ),
					),
					'value'   => $rule_type_value,
				);
				?>
				<div class="cart-rule-row" data-index="<?php echo esc_attr( $i ); ?>">
					<div class="cart-rule-single-field">
						<div id="cart-rule-single-rule-type-container-<?php echo esc_attr( $i ); ?>" >

							<label for="rules_type_<?php echo esc_attr( $i ); ?>"><?php esc_html_e( 'Set a conditions based on', 'ywdpd' ); ?></label>
							<?php
								echo yith_plugin_fw_get_field( $rule_type_field ); //phpcs:ignore WordPress.Security.EscapeOutput
							?>
						</div>
						<div id="cart-rule-single-rule-sub-type-container-<?php echo esc_attr( $i ); ?>" >
							<?php
							foreach ( $cart_rules_options['rules_type'] as $cart_rule_key => $cart_rule_option ) :
								$hide_class  = $rule_type_value !== $cart_rule_key ? 'ywdpd_hide_row' : '';
								$options_key = array_keys( $cart_rule_option['options'] );

								?>
								<div class="cart-rule-single-rule-sub-select <?php echo esc_attr( $hide_class ); ?>"
									 data-type="<?php echo esc_attr( $cart_rule_key ); ?>">
									<label
										for="rule_type_<?php echo esc_attr( $cart_rule_key ); ?>_<?php echo esc_attr( $i ); ?>"><?php esc_html_e( 'for', 'ywdpd' ); ?></label>
									<?php

									$option_value = isset( $db_value[ $i ][ 'rule_type_for_' . $cart_rule_key ] ) ? $db_value[ $i ][ 'rule_type_for_' . $cart_rule_key ] : $options_key[0];
									$field_args   = array(
										'id'      => 'rule_type_' . $cart_rule_key . '_' . $i,
										'name'    => $name . "[$i][rule_type_for_" . $cart_rule_key . ']',
										'type'    => 'select',
										'class'   => 'wc-enhanced-select ywdpd_select_type_for',
										'options' => $cart_rule_option['options'],
										'value'   => $option_value,
									);

									echo yith_plugin_fw_get_field( $field_args ); //phpcs:ignore WordPress.Security.EscapeOutput
									?>
								</div>
								<?php
							endforeach;

							?>
						</div>
						<div id="cart-rule-single-rule-sub-sub-type-container-<?php echo esc_attr( $i ); ?>" class="ywdpdp_inline">
							<?php
							foreach ( $cart_rules_options['rules_type'] as $cart_rule_key => $cart_rule_option ) :
								$hide_class  = $rule_type_value !== $cart_rule_key ? 'ywdpd_hide_row' : '';
								$options_key = array_keys( $cart_rule_option['options'] );
								?>
								<div class="cart-rule-single-discount"
									 data-type="<?php echo esc_attr( $cart_rule_key ); ?>">

									<div class="cart-rule-sub-fields">
										<div class="cart-rule-sub-field-type">
											<?php
											foreach ( $options_key as $option_key ) :
												$hide_class = 'ywdpd_hide_row';
												yith_dynamic_pricing_cart_rule_sub_type( $option_key, $db_value, $i, $args, $hide_class );
											endforeach;
											?>
										</div>

									</div>
								</div>
								<?php
							endforeach;
							?>
						</div>
						<?php
						if ( 1 !== $i ) :
							?>
						<span class="yith-icon yith-icon-trash"></span>
							<?php
						endif;
						?>
					</div>
				</div>
				<?php
			endfor;
			?>
		</div>
		<div class="cart_new_rule">
			<a href="#" id="ywdpd_new_cart_rule" class="ywdpd_new_rule"><?php esc_html_e( '+ Add condition', 'ywdpd' ); ?></a>
		</div>
		<div class="clear"></div>
		<span class="description"><?php esc_html_e( 'Set the cart discount conditions. You can create conditions for specific users, specific products or based on cart total or cart items quantity', 'ywdpd' ); ?></span>

	</div>
</div>

<script type="text/template" id="tmpl-ywdpd-cart-discount-row">
	<div class="cart-rule-row" data-index="{{{data.index}}}">
		<div class="cart-rule-single-field">
			<div id="cart-rule-single-rule-type-container-{{{data.index}}}" >
				<label for="rules_type_{{{data.index}}}"><?php esc_html_e( 'Set a conditions based on', 'ywdpd' ); ?></label>
				<?php
				$rule_type_field = array(
					'id'      => 'rules_type_{{{data.index}}}',
					'name'    => $name . '[{{{data.index}}}][rules_type]',
					'type'    => 'select',
					'class'   => 'wc-enhanced-select ywdpd_rule_type',
					'options' => $cart_rules_options['cart_discount_rule_type'],
					'value'   => 'customers',
				);
				echo yith_plugin_fw_get_field( $rule_type_field ); //phpcs:ignore
				?>
			</div>
			<div id="cart-rule-single-rule-sub-type-container-{{{data.index}}}" >
				<?php
				foreach ( $cart_rules_options['rules_type'] as $cart_rule_key => $cart_rule_option ) :
					$hide_class  = $rule_type_value !== $cart_rule_key ? 'ywdpd_hide_row' : '';
					$options_key = array_keys( $cart_rule_option['options'] );

					?>
					<div class="cart-rule-single-rule-sub-select <?php echo esc_attr( $hide_class ); ?>"
						 data-type="<?php echo esc_attr( $cart_rule_key ); ?>">
						<label
							for="rule_type_<?php echo esc_attr( $cart_rule_key ); ?>_{{{data.index}}}"><?php esc_html_e( 'for', 'ywdpd' ); ?></label>
						<?php

						$option_value = $options_key[0];
						$field_args   = array(
							'id'      => 'rule_type_' . $cart_rule_key . '_{{{data.index}}}',
							'name'    => $name . '[{{{data.index}}}][rule_type_for_' . $cart_rule_key . ']',
							'type'    => 'select',
							'class'   => 'wc-enhanced-select ywdpd_select_type_for',
							'options' => $cart_rule_option['options'],
							'value'   => $option_value,
						);

						echo yith_plugin_fw_get_field( $field_args ); //phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</div>
					<?php
				endforeach;

				?>
			</div>
			<div id="cart-rule-single-rule-sub-sub-type-container-{{{data.index}}}" class="ywdpdp_inline">
				<?php
				foreach ( $cart_rules_options['rules_type'] as $cart_rule_key => $cart_rule_option ) :
					$hide_class  = $rule_type_value !== $cart_rule_key ? 'ywdpd_hide_row' : '';
					$options_key = array_keys( $cart_rule_option['options'] );
					?>
					<div class="cart-rule-single-discount"
						 data-type="<?php echo esc_attr( $cart_rule_key ); ?>">

						<div class="cart-rule-sub-fields">
							<div class="cart-rule-sub-field-type">
								<?php
								foreach ( $options_key as $option_key ) :
									$hide_class = 'ywdpd_hide_row';
									yith_dynamic_pricing_cart_rule_sub_type( $option_key, $db_value, '{{{data.index}}}', $args, $hide_class );
								endforeach;
								?>
							</div>

						</div>
					</div>
					<?php
				endforeach;
				?>
			</div>

			<span class="yith-icon yith-icon-trash"></span>

		</div>
	</div>
</script>
