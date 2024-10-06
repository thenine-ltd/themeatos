

<?php
    use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;

    /** @var string $urlSelector */
    $attr = isset($defaultAttr) && $defaultAttr
        ? $defaultAttr
        : 'html';
    $defaultData = [
        'urlSelector'              => $urlSelector,
        'testType'                 => \WPCCrawler\Test\Test::$TEST_TYPE_SELECTOR_ATTRIBUTE,
        SettingInnerKey::ATTRIBUTE => $attr,
    ];

    if (isset($data) && $data && is_array($data)) {
        $defaultData = array_merge($defaultData, $data);
    }
?>

<tr <?php if(isset($id)): ?> id="<?php echo e($id); ?>" <?php endif; ?>
    <?php if(isset($class)): ?> class="<?php echo e($class); ?>" <?php endif; ?>
    aria-label="<?php echo e($name); ?>"
>
    <td>
        <?php echo $__env->make('form-items/label', [
            'for'   =>  $name,
            'title' =>  $title,
            'info'  =>  $info,
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
    <td>
        <?php echo $__env->make('form-items/multiple', [
            'include'       => 'form-items/selector-custom-shortcode',
            'name'          => $name,
            'addon'         => 'dashicons dashicons-search',
            'data'          => $defaultData,
            'test'          => true,
            'addKeys'       => true,
            'addonClasses'  => 'wcc-test-selector-attribute',
            'defaultAttr'   => $attr,
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('partials/test-result-container', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
</tr><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/form-items/combined/multiple-custom-short-code-with-label.blade.php ENDPATH**/ ?>