<?php
wp_enqueue_script( 'prettyPhoto' );
wp_enqueue_script( 'prettyPhoto-init' );
wp_enqueue_style( 'woocommerce_prettyPhoto_css' );
if ( isset( $images ) && ! empty( $images ) && ! empty( $images['gallery'] ) ) {
	if ( substr( $images['gallery'], - 1 ) == ',' ) {
		$images['gallery'] = substr( $images['gallery'], 0, - 1 );
	}

	$images_id = explode( ',', $images['gallery'] );
	$column_w  = 'ywtm_col_' . $images['columns'];

	$thumbnail_size  = apply_filters( 'yith_tab_manager_image_thumbnail_size', 'medium' );
	$image_full_size = apply_filters( 'yith_tab_manager_image_full_size', 'full' );

	?>

	<div class="ywtm_image_gallery_container ywtm_content_tab">
		<ul class="container_img  container_<?php echo esc_attr( $images['columns'] ); ?>">
			<?php foreach ( $images_id as $image ) : ?>
				<?php


				$img_src_thumbn = wp_get_attachment_image_src( $image, $thumbnail_size );
				$img_src_thumbn = is_array( $img_src_thumbn ) ? $img_src_thumbn[0] : $img_src_thumbn;
				$img_src_full   = wp_get_attachment_image_src( $image, $image_full_size );
				$img_src_full   = is_array( $img_src_full ) ? $img_src_full[0] : $img_src_full;
				?>

				<li class="<?php echo esc_attr( $column_w ); ?>"><a href="<?php echo esc_url( $img_src_full ); ?>"
													   data-rel="prettyPhoto[gallery-<?php echo esc_attr( $tab_id ); ?>]">
					<img src="<?php echo esc_attr( $img_src_thumbn ); ?>"></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php } ?>
