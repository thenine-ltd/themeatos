{{--
    Required variables:
        String $title: Title of the form item. Label name.
        String $info: Information about the form item
        String $name: Name of the form item
        String $urlSelector: CSS selector of the input that contains the test URL

    Optional variables:
        String $id: ID of the <tr> element surrounding the form items
        String $class: Class of the <tr> element surrounding the form items
        String $defaultAttr: Default attribute for the selector of the form item
        Other variables of label and multiple form item views.

--}}

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

<tr @if(isset($id)) id="{{ $id }}" @endif
    @if(isset($class)) class="{{ $class }}" @endif
    aria-label="{{ $name }}"
>
    <td>
        @include('form-items/label', [
            'for'   =>  $name,
            'title' =>  $title,
            'info'  =>  $info,
        ])
    </td>
    <td>
        @include('form-items/multiple', [
            'include'       => 'form-items/selector-custom-shortcode',
            'name'          => $name,
            'addon'         => 'dashicons dashicons-search',
            'data'          => $defaultData,
            'test'          => true,
            'addKeys'       => true,
            'addonClasses'  => 'wcc-test-selector-attribute',
            'defaultAttr'   => $attr,
        ])
        @include('partials/test-result-container')
    </td>
</tr>