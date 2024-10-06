<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<?php do_action('woocommerce_email_header', $email_heading, $email); ?>

<?php do_action('wt_decorator_email_body_content', $subscription, $sent_to_admin, $plain_text, $email); ?>

<?php do_action('woocommerce_subscriptions_email_order_details', $subscription, $sent_to_admin, $plain_text, $email); ?>

<?php do_action('woocommerce_email_customer_details', $subscription, $sent_to_admin, $plain_text, $email); ?>


<?php
/**
 * Show user-defined additonal content - this is set in each email's settings.
 */
if ( isset( $additional_content ) && ! empty( $additional_content ) ) {
    echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
}

?>

<?php do_action('woocommerce_email_footer', $email); ?>