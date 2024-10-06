<div class="yith-ywdpd-list-table-blank-state">
	<?php if ( $icon ) : ?>
		<div class="yith-ywdpd-list-table-blank-state__icon">
			<?php include $icon; ?>
		</div>
	<?php endif; ?>
	<div class="yith-ywdpd-list-table-blank-state__message"><?php echo esc_html( $message ); ?></div>
	<div class="yith-ywdpd-list-table-blank-state__submessage"><?php echo esc_html( $submessage ); ?></div>
	<?php if ( $cta && $cta_url ) : ?>
		<div class="yith-ywdpd-list-table-blank-state__cta-wrapper">
			<a href="<?php echo esc_url( $cta_url ); ?>" class="yith-ywdpd-list-table-blank-state__cta"><?php echo esc_html( $cta ); ?></a>
		</div>
	<?php endif; ?>
</div>
