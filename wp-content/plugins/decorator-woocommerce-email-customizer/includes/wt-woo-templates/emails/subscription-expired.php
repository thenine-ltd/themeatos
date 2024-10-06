<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
?>

<?php do_action('woocommerce_email_header', $email_heading, $email); ?>

<p>
    <?php do_action( 'wt_decorator_email_body_content', $order, $sent_to_admin, $plain_text, $email ); ?>

</p>

<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" border="1">
    <thead>
        <tr>
            <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Subscription', 'xa-woocommerce-subscription'); ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Price', 'xa-woocommerce-subscription'); ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('Last Order Date', 'xa-woocommerce-subscription'); ?></th>
            <th class="td" scope="col" style="text-align:left;"><?php esc_html_e('End of Prepaid Term', 'xa-woocommerce-subscription'); ?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td class="td" width="1%" style="text-align:left; vertical-align:middle;">
                <a href="<?php echo esc_url(get_edit_post_link($subscription->get_id())); ?>">#<?php echo esc_html($subscription->get_order_number()); ?></a>
            </td>
            <td class="td" style="text-align:left; vertical-align:middle;">
                <?php echo wp_kses_post($subscription->get_formatted_order_total()); ?>
            </td>
            <td class="td" style="text-align:left; vertical-align:middle;">
                <?php
                $last_order_time_created = $subscription->get_time('last_order_date_created', 'site');
                if (!empty($last_order_time_created)) {
                    echo esc_html(date_i18n(wc_date_format(), $last_order_time_created));
                } else {
                    esc_html_e('-', 'xa-woocommerce-subscription');
                }
                ?>
            </td>
            <td class="td" style="text-align:left; vertical-align:middle;">
                <?php echo esc_html(date_i18n(wc_date_format(), $subscription->get_time('end', 'site'))); ?>
            </td>
        </tr>
    </tbody>
</table>
<br/>

<?php do_action('woocommerce_subscriptions_email_order_details', $subscription, $sent_to_admin, $plain_text, $email); ?>

<?php do_action('woocommerce_email_customer_details', $subscription, $sent_to_admin, $plain_text, $email); ?>

<?php do_action('woocommerce_email_footer', $email); ?>
