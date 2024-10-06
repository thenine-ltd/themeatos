{{--
    Required variables:
        string $title       Title of the form item. Label name.
        string $info        Information about the form item
        string $name        Name of the form item
        string $urlSelector CSS selector for the URL input

    Optional variables:
        string $id          ID of the <tr> element surrounding the form items

        Other variables of label and multiple form item views.
--}}

<?php
/** @var string $urlSelector */

use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;

$defaultData = [
    'urlSelector'              => $urlSelector,
    'testType'                 => \WPCCrawler\Test\Test::$TEST_TYPE_SELECTOR_ATTRIBUTE,
    'ignoreAttrInput'          => true,
    SettingInnerKey::ATTRIBUTE => 'html'
];

if (isset($data) && $data && is_array($data)) {
    $defaultData = array_merge($defaultData, $data);
}
?>

<tr @if(isset($id)) id="{{ $id }}" @endif
    @if(isset($class)) class="{{ $class }}" @endif
    aria-label="{{ $name }}"
>
    <td>
        @include('form-items/label', [
            'for'   => $name,
            'title' => $title,
            'info'  => $info,
        ])
    </td>
    <td>
        @include('form-items.multiple', [
            'include'       => 'form-items.selector-with-attributes',
            'name'          => $name,
            'addon'         => 'dashicons dashicons-search',
            'data'          => $defaultData,
            'test'          => true,
            'addKeys'       => true,
            'addonClasses'  => isset($addonClasses) && $addonClasses ? $addonClasses : 'wcc-test-selector-attributes',
        ])
        @include('partials/test-result-container')
    </td>
</tr>
