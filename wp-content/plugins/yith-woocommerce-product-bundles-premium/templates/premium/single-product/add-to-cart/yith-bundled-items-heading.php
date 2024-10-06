<?php
/**
 * @var WC_Product_Yith_Bundle $product The bundle product.
 * @var string                 $heading The heading
 */
?>
<?php if ( $heading ): ?>
	<div class="yith-wcpb-bundled-items-heading"><?php echo wp_kses_post( $heading ); ?></div>
<?php endif; ?>