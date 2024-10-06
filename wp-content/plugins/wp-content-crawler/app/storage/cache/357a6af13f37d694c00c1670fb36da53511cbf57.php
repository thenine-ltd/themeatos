
<?php echo $__env->make('partials.table-section-title', [
    'title' => _wpcc("WooCommerce"),
    'key'   => \WPCCrawler\Objects\Enums\SectionKey::SITE_SETTINGS_POST_WOOCOMMERCE,
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

<tr id="woocommerce-options-container">
    <td colspan="2">
        <?php echo $__env->make('post-detail.woocommerce.site-settings.container', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
</tr><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/post-detail/woocommerce/site-settings/main.blade.php ENDPATH**/ ?>