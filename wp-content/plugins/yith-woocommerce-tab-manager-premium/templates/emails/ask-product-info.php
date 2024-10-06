<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_align = is_rtl() ? 'right' : 'left';

do_action( 'woocommerce_email_header', $email_heading, $email );
?>
	<p><?php echo esc_attr( $email_description ); ?></p>
	<div style="margin-bottom: 40px;">
	  <span>
					<?php
					$product = wc_get_product( $contact_info['product_id'] );
					if ( $product ) {
						echo $product->get_image( array( 100, 100 ) ); //phpcs:ignore WordPress.Security.EscapeOutput
						?>

						<a href="<?php echo esc_url( $product->get_permalink() ); ?>"
						   rel="nofollow"><?php echo $product->get_formatted_name(); //phpcs:ignore WordPress.Security.EscapeOutput ?></a>
						<?php
					}
					?>
	  </span>
	</div>
	<div style="font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 40px;">
		<h2><?php esc_html_e( 'Customer Details', 'yith-woocommerce-tab-manager' ); ?></h2>
		<?php
		$customer_keys = array(
			'web_address' => __( 'Web Address', 'yith-woocommerce-tab-manager' ),
			'username'    => __( 'Name', 'yith-woocommerce-tab-manager' ),
			'message'     => __( 'Message', 'yith-woocommerce-tab-manager' ),
			'user_email'  => __( 'Email', 'yith-woocommerce-tab-manager' ),
		);
		?>
		<ul>
			<?php
			foreach ( $contact_info['user_info'] as $user_key => $user_value ) {

				if ( ! empty( $user_value ) && isset( $customer_keys[ $user_key ] ) ) {
					$li = sprintf( '<li><strong>%s</strong> %s', $customer_keys[ $user_key ], $user_value );
					echo $li; //phpcs:ignore WordPress.Security.EscapeOutput
				}
			}
			?>

		</ul>
	</div>
<?php
do_action( 'woocommerce_email_footer', $email );
