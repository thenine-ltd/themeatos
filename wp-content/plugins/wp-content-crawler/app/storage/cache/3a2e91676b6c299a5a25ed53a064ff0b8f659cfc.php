<?php
use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;
?>
<button type="button" class="button wpcc-button wcc-options-box no-gap" title="<?php echo e(_wpcc("Options")); ?>"
        data-settings="<?php echo e(isset($optionsBox) && is_array($optionsBox) ? json_encode($optionsBox) : '{}'); ?>">
    <span class="dashicons dashicons-admin-settings"></span>
    <input type="hidden"
           name="<?php echo e($name . '[' . SettingInnerKey::OPTIONS_BOX . ']'); ?>"
           value="<?php echo e($value[SettingInnerKey::OPTIONS_BOX] ?? '{}'); ?>"
    >
    <div class="summary-colors"></div>
</button><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/form-items/options-box/button-options-box.blade.php ENDPATH**/ ?>