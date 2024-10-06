<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<?php do_action('woocommerce_email_header', $email_heading, $email); ?>
<p><?php do_action( 'wt_decorator_email_body_content', $order , $sent_to_admin, $plain_text, $email ); ?></p>
    <?php
    $coupon_message = Wt_Smart_Coupon_Common::get_order_meta($order->get_id(), 'wt_coupon_send_to_message'); 
    if($coupon_message)
    {
        ?>
        </p><?php echo $coupon_message; ?></p>
        <?php
    }else 
    {   
        ?>
        <p><?php _e("You've got a gift!", 'wt-smart-coupons-for-woocommerce-pro'); ?></p>
        <?php
    }
    
    $coupons = Wt_Smart_Coupon_Common::get_order_meta($order->get_id(), 'wt_coupons');
    $coupons = maybe_unserialize($coupons);
    
    if(!empty($coupons))
    {
        foreach($coupons as $coupon_id)
        {       
            $coupon_title = get_the_title( $coupon_id );
            $coupon = new WC_Coupon( $coupon_title );
            $coupon_data  = Wt_Smart_Coupon_Public::get_coupon_meta_data($coupon);                  
            ?>
            <p><?php echo Wt_Smart_Coupon_Public::get_coupon_html($coupon, $coupon_data, 'email_coupon'); ?></p>
            <?php
        }
    }
    ?>
<?php do_action( 'woocommerce_email_footer', $email ); ?>