<?php
/**
 * Customer Membership Expiring email
 *
 * @author        YITH <plugins@yithemes.com>
 * @package       YITH WooCommerce Membership Premium
 * @version       1.1.2
 *
 * @var string                   $email_heading
 * @var YITH_WCMBS_Expiring_Mail $email
 * @var string                   $custom_message
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php echo wp_kses_post( wpautop( wptexturize( $custom_message ) ) ); ?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>