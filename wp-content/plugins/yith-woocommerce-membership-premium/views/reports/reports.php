<?php
/*
 * Template for Reports Page
 *
 */
?>
<div id="yith-wcmbs-reports-tab" class="wrap yith-wcmbs-with-menu">
	<ul class="yith-wcmbs-menu">
		<li data-show="#yith-wcmbs-membership-reports"><?php esc_html_e( 'Memberships', 'yith-woocommerce-membership' ) ?></li>
		<li data-show="#yith-wcmbs-download-reports"><?php esc_html_e( 'Downloads', 'yith-woocommerce-membership' ) ?></li>
	</ul>

	<div id="poststuff" class="yith-wcmbs-clearfix">
		<div id="yith-wcmbs-membership-reports">
			<?php do_action( 'yith_wcmbs_membership_reports' ); ?>
		</div>
		<div id="yith-wcmbs-download-reports" style="display:none;">
			<?php do_action( 'yith_wcmbs_download_reports' ); ?>
		</div>
	</div>

</div>
