<?php
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Objects\Settings\SettingService;

/** @var array $settings */
$name = SettingKey::CONFIG_HELPER_AUTO_SHOW;

/** @var $autoShow `true` when the dialog must be auto-shown */
$autoShow = SettingService::isAutoShowConfigHelper()
    && !empty($settings[$name])
    && ($settings[$name][0] ?? null) === '1';
?>

<div class="config-helper-wrapper">
    <input type="hidden" name="<?php echo e($name); ?>" value="<?php echo e($autoShow ? '1' : '0'); ?>">
    <div id="config-helper" data-auto-show="<?php echo e(json_encode($autoShow)); ?>"></div>
</div><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/form-items/config-helper-container.blade.php ENDPATH**/ ?>