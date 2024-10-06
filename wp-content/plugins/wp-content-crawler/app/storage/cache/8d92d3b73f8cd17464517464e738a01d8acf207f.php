

<?php if(isset($message)): ?>
    <?php echo $message; ?>

<?php endif; ?>

<?php echo $__env->make('partials.info-list', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/partials/information-message.blade.php ENDPATH**/ ?>