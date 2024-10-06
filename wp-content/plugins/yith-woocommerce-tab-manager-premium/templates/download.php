<div class="tab-download-container ywtm_content_tab">
	<?php
	if ( ! empty( $download ) ) {

		foreach ( $download as $key => $file ) :?>
			<?php
			$file_name = isset( $file['name'] ) ? wp_unslash( $file['name'] ) : __( 'No file name', 'yith-woocommerce-tab-manager' );
			$file_desc = isset( $file['desc'] ) ? wp_unslash( $file['desc'] ) : __( 'No file description', 'yith-woocommerce-tab-manager' );
			$file_url  = apply_filters( 'ywtm_change_url_path', $file['file'] );

			$file_types = apply_filters( 'ywtm_file_types_to_open_in_browser', array( 'pdf' ) );
			$file_type  = wp_check_filetype( $file_url );

			$download = true;
			if ( isset( $file_type['ext'] ) && ( in_array( strtolower( $file_type['ext'] ), $file_types ) ) ) {
				$download = false;
			}



			?>
			<div class="single_download_container">
				<div class="file_title">
					<h4><?php echo esc_html( $file_name ); ?></h4>
					<p><?php echo esc_html( $file_desc ); ?></p>
				</div>
				<div class="button_download"><a href="<?php echo esc_url( $file_url ); ?>" <?php echo $download ? 'download' : ''; ?>
												target="_blank"><?php esc_html_e( 'Download File', 'yith-woocommerce-tab-manager' ); ?></a>
				</div>
			</div>

			<?php
		endforeach;
	} else {
		echo '<span>' . esc_html__( 'No download for this product', 'yith-woocommerce-tab-manager' ) . '</span>';
	}
	?>
</div>
