<div class="input-group cookie {{ isset($remove) ? 'remove' : '' }} {{ isset($class) ? $class : '' }}"
     @if(isset($dataKey)) data-key="{{ $dataKey }}" @endif>
    <div class="input-container">
        @include('form-items.input-with-inner-key', [
            'innerKey'      => \WPCCrawler\Objects\Settings\Enums\SettingInnerKey::KEY,
            'placeholder'   => _wpcc('Cookie name'),
        ])

        @include('form-items.input-with-inner-key', [
            'innerKey'      => \WPCCrawler\Objects\Settings\Enums\SettingInnerKey::VALUE,
            'placeholder'   => _wpcc('Cookie content'),
        ])

        @include('form-items.input-with-inner-key', [
            'innerKey'      => \WPCCrawler\Objects\Settings\Enums\SettingInnerKey::DOMAIN,
            'placeholder'   => _wpcc("Cookie domain (optional)"),
        ])
    </div>
    @if(isset($remove) && $remove)
        @include('form-items/remove-button')
    @endif
</div>