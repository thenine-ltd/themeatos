<?php

$content                = wpautop( htmlspecialchars_decode( str_replace( '\\', '', $content ) ) );
$use_the_content_filter = apply_filters( 'ywtm_use_default_the_content_filter', true );

if ( $use_the_content_filter ) {
	$content = apply_filters( 'the_content', $content );
} else {
	$content = apply_filters( 'ywtm_the_content', $content );
}
?>

<div class="tab-editor-container ywtm_content_tab"> <?php echo do_shortcode( $content ); ?></div>
