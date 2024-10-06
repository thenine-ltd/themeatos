

<?php
    /** @var string $name */
    /** @var array $options */
?>
<div class="input-group <?php echo e(isset($remove) ? 'remove' : ''); ?>"
     <?php if(isset($dataKey)): ?> data-key="<?php echo e($dataKey); ?>" <?php endif; ?>
>
    <div class="input-container">
        <?php echo $__env->make('form-items.select-element', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>

    <?php if(isset($remove) && $remove): ?>
        <?php echo $__env->make('form-items.remove-button', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php endif; ?>
</div><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/form-items/select.blade.php ENDPATH**/ ?>