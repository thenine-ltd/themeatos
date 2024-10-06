<?php
/**
 * Popup gift.
 *
 * @package YITH WooCommerce Dynamic Pricing and Discounts Premium
 * @since   1.0.0
 * @version 1.6.0
 * @author  YITH
 *
 * @var array $gift_rules_to_apply
 */

if ( ! defined( 'ABSPATH' ) || ! isset( $items_to_show ) ) {
	exit;
}
?>
<div class="ywdpd_popup <?php echo esc_attr( $popup_class ); ?>">
	<div class="ywdpd_popup_wrapper">
		<div class="ywdpd_popup_general_content">
			<span class="ywdpd_close"></span>
			<div id="ywdpd_popup_container" class="ywdpd_popup_content">
				<div class="ywdpd_step1">
					<?php foreach ( $items_to_show as $rule_key => $item_to_show ) : ?>
						<div id="ywdpd_single_rule_<?php echo esc_attr( $rule_key ); ?>"
							 class="ywdpd_single_rule_container <?php echo esc_attr( $item_to_show['type'] ); ?>"
							 data-allowed_items="<?php echo esc_attr( $item_to_show['allowed_item'] ); ?>">
							<h4 class="ywdpd_rule_title">
								<?php
								$qty_label = sprintf( '<span class="ywdpd_quantity">%s</span>', $item_to_show['allowed_item'] );
								echo wp_kses_post( str_replace( '{{total_to_add}}', $qty_label, $item_to_show['text'] ) );
								?>
							</h4>
							<div class="ywdpd_popup_stage">
								<?php
									do_action( 'ywdpd_show_popup_items', $item_to_show['items'], $rule_key, $item_to_show['type'], $item_to_show );
								?>
							</div>
						</div>
					<?php endforeach; ?>
					<div class="ywdpd_btn_container">
						<a class="button ywdpd_btn_confirm"><?php esc_html_e( 'Confirm and close window', 'ywdpd' ); ?></a>
					</div>	
				</div>
				<div class="ywdpd_step2"></div>
				<div class="ywdpd_footer">			
					<a href="" rel="nofollow"><?php esc_html_e( 'No, thanks', 'ywdpd' ); ?></a>
				</div>
			</div>
		</div>
	</div>
</div>
