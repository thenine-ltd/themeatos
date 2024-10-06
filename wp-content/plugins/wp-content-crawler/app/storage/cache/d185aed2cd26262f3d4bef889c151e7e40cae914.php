

<?php
/** @var string $name */
/** @var array<string, array|string> $options */
?>

<select name="<?php echo e($name); ?>"
        id="<?php echo e($name); ?>"
        tabindex="0"
        <?php if(isset($selectTitle)): ?> title="<?php echo e($selectTitle); ?>" <?php endif; ?>
        <?php if(isset($disabled)): ?> disabled <?php endif; ?>
>
    <?php $selectedKey = isset($settings[$name]) ? (isset($isOption) && $isOption ? $settings[$name] : $settings[$name][0]) : false; ?>
    <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $optionData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            /** @var string|array $optionData */
            // If the option data is an array
            $isArr = is_array($optionData);
            if ($isArr) {
                // Get the option name and the dependants if there exists any
                $optionName = \WPCCrawler\Utils::array_get($optionData, 'name');
                $dependants = \WPCCrawler\Utils::array_get($optionData, 'dependants');
                $container  = \WPCCrawler\Utils::array_get($optionData, 'container');
            } else {
                // Otherwise, option data is the name of the option and there is no dependant.
                $optionName = $optionData;
                $dependants = null;
                $container = null;
            }
        ?>

        <option value="<?php echo e($key); ?>" data-order="<?php echo e($loop->index); ?>"
                <?php if($selectedKey && $key == $selectedKey): ?> selected="selected" <?php endif; ?>
                <?php if($dependants): ?> data-dependants="<?php echo e($dependants); ?>" <?php endif; ?>
                <?php if($container): ?> data-container="<?php echo e($container); ?>" <?php endif; ?>
        ><?php echo e($optionName); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>


<?php if($sortableSelect ?? false): ?>
    <?php echo $__env->make('partials.select-sorter', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php endif; ?><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/form-items/select-element.blade.php ENDPATH**/ ?>