<?php
use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;
?>
<button type="button" class="button wpcc-button wcc-options-box no-gap" title="{{ _wpcc("Options") }}"
        data-settings="{{ isset($optionsBox) && is_array($optionsBox) ? json_encode($optionsBox) : '{}' }}">
    <span class="dashicons dashicons-admin-settings"></span>
    <input type="hidden"
           name="{{ $name . '[' . SettingInnerKey::OPTIONS_BOX . ']' }}"
           value="{{ $value[SettingInnerKey::OPTIONS_BOX] ?? '{}' }}"
    >
    <div class="summary-colors"></div>
</button>