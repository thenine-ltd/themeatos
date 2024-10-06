

<?php
use WPCCrawler\Factory
?>

<?php echo $__env->make('form-items.combined.short-code-buttons-with-label', [
    // The buttons of the short codes that cannot be used under the category tab are hidden via CSS.
    'buttons' => Factory::postService()->getEditorButtonsMain(),
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/form-items/combined/short-code-buttons-with-label-for-create-element-cmd.blade.php ENDPATH**/ ?>