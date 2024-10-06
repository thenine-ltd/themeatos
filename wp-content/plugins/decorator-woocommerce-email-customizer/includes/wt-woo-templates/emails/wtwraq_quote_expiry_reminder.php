<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'woocommerce_email_header', $email_heading, $email );
?>

<p>
    <?php do_action( 'wt_decorator_email_body_content', $quote, $sent_to_admin, $plain_text, $email ); ?>
</p>
<h2>
    <?php
    if ( 'yes' === $quote->get_meta( '_wtwraq_is_guest_request' ) ) {
        printf( '<a>%1$s</a> <time>(%2$s)</time>', esc_html__( 'Quote #', 'wt-woo-request-quote' ), $quote->get_id(), esc_html( get_date_from_gmt( wtwraq_get_date( $quote, '_wtwraq_quote_sent_date' ), get_option( 'date_format' ) ) ) );
    } else {
        printf( '<a href="%1$s">%2$s</a> <time>(%3$s)</time>', esc_url( wc_get_endpoint_url( 'view-quote', $quote->get_id(), get_permalink( get_option( 'woocommerce_myaccount_page_id' ) ) ) ), esc_html__( 'Quote #' . $quote->get_id(), 'wt-woo-request-quote' ), esc_html( get_date_from_gmt( wtwraq_get_date( $quote, '_wtwraq_quote_sent_date' ), get_option( 'date_format' ) ) ) );
    }
    ?>
</h2>

<?php
do_action( 'wtwraq_email_quote_details', $quote, $sent_to_admin, $plain_text, $email );

if ( $additional_content ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

do_action( 'woocommerce_email_footer', $email );
?>