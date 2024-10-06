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
    <input type="hidden" name="{{ $name }}" value="{{ $autoShow ? '1' : '0' }}">
    <div id="config-helper" data-auto-show="{{ json_encode($autoShow) }}"></div>
</div>