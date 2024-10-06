<div class="tab-faqs-container ywtm_content_tab">
	<?php
	if ( ! empty( $faqs ) ) {
		foreach ( $faqs as $key => $faq ) :?>
		<div class="tab-faq-wrapper">
			<div class="tab-faq-title">
				<span class="tab-faq-icon closed"></span>
				<h4><?php echo esc_html( wp_unslash( $faq['question'] ) ); ?></h4>
			</div>
			<div class="tab-faq-item">
				<div class="tab-faq-item-content">
				   <p><span class="tab-faq-answ"><?php echo esc_html_x( 'Answer:', 'Part of Answer section of FAQ tab', 'yith-woocommerce-tab-manager' ); ?></span> <?php echo wp_unslash( $faq['answer'] ); ?></p>
				</div>
			</div>
		</div>
			<?php
	endforeach;
	} else {
		echo '<span>' . esc_html__( 'No FAQ for this product', 'yith-woocommerce-tab-manager' ) . '</span>';
	}
	?>
</div>
