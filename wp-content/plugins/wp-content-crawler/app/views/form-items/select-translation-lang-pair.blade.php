<?php

use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;

?>

<div class="input-group select-language-pair {{ isset($remove) ? 'remove' : '' }}"
     @if(isset($dataKey)) data-key="{{ $dataKey }}" @endif
>
    <div class="input-container">
        @include('form-items.select-element', [
            'name'        => sprintf('%1$s[%2$s]', $name, SettingInnerKey::FROM),
            'selectTitle' => _wpcc('From language'),
            'options'     => $languagesFrom,
        ])

        {{-- A "right arrow" icon to indicate the direction of translation --}}
        <i class="fas fa-arrow-right"></i>

        @include('form-items.select-element', [
            'name'        => sprintf('%1$s[%2$s]', $name, SettingInnerKey::TO),
            'selectTitle' => _wpcc('To language'),
            'options'     => $languagesTo,
        ])
    </div>

    @if(isset($remove) && $remove)
        @include('form-items.remove-button')
    @endif
</div>