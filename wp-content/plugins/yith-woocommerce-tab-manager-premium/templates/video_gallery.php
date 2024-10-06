<?php
if ( isset( $videos ) && ! empty( $videos ) && $videos != '' ) {
	$column_w = 'ywtm_col_' . $videos['columns'];
	?>

	<div class="ywtm_video_gallery_container">
		<ul class="container_list_video container_<?php echo esc_attr( $videos['columns'] ); ?>">
			<?php foreach ( $videos['video'] as $key => $video ) : ?>
				<?php if ( $video['id'] != '' || $video['url'] != '' ) : ?>
					<li class="<?php echo esc_attr( $column_w ); ?>">
						<?php
						$video_host = $video['host'];

						$args = array(
							'id'    => $video['id'],
							'url'   => $video['url'],
							'width' => '100%',
							'echo'  => false,
						);
						if ( $video_host == 'youtube' ) {
							echo YIT_Video::youtube( $args );//phpcs:ignore WordPress.Security.EscapeOutput
						} elseif ( $video_host == 'vimeo' ) {
							echo YIT_Video::vimeo( $args );//phpcs:ignore WordPress.Security.EscapeOutput
						}
						?>
					</li>
					<?php
				endif;
endforeach;
			?>
		</ul>
	</div>
<?php } else {
	esc_html_e( 'No video found for this product', 'yith-woocommerce-tab-manager' );
} ?>
