{{--
    Required variables:
        $keyTestUrl: Option key for "test URL" input
        $keyFactory: An instance of AbstractHtmlManipKeyFactory
--}}

<?php
    use WPCCrawler\Objects\Settings\Factory\HtmlManip\AbstractHtmlManipKeyFactory;
    use WPCCrawler\Objects\Settings\Factory\HtmlManip\CategoryHtmlManipKeyFactory;
    use WPCCrawler\Objects\Settings\Factory\HtmlManip\PostHtmlManipKeyFactory;
    use WPCCrawler\Objects\Enums\SectionKey;

    $cssSelectorChangeEmptyResultWarning = _wpcc("If you change the values that you use in the CSS selector,
        <b>the test results will be empty.</b>");

    /** @var AbstractHtmlManipKeyFactory $keyFactory */
    $keyTestFindReplace                 = $keyFactory->getTestFindReplaceKey();
    $keyFindReplaceRawHtml              = $keyFactory->getFindReplaceRawHtmlKey();
    $keyFindReplaceFirstLoad            = $keyFactory->getFindReplaceFirstLoadKey();
    $keyFindReplaceElementAttributes    = $keyFactory->getFindReplaceElementAttributesKey();
    $keyExchangeElementAttributes       = $keyFactory->getExchangeElementAttributesKey();
    $keyRemoveElementAttributes         = $keyFactory->getRemoveElementAttributesKey();
    $keyFindReplaceElementHtml          = $keyFactory->getFindReplaceElementHtmlKey();
    $keyConvertJsonToHtml               = $keyFactory->getConvertJsonToHtmlKey();
    $keyConvertJsonToHtmlAuto           = $keyFactory->getConvertJsonToHtmlAutoKey();

    $sectionKey = 'section-ss-manipulate-html';
    if ($keyFactory instanceof CategoryHtmlManipKeyFactory) {
        $sectionKey = SectionKey::SITE_SETTINGS_CATEGORY_MANIPULATE_HTML;
    } else if ($keyFactory instanceof PostHtmlManipKeyFactory) {
        $sectionKey = SectionKey::SITE_SETTINGS_POST_MANIPULATE_HTML;
    }
?>

{{-- SECTION: MANIPULATE HTML --}}
@include('partials.table-section-title', [
    'title' => _wpcc("Manipulate HTML"),
    'key'   => $sectionKey,
])

{{-- TEST FIND REPLACE FOR POST HTML AT FIRST LOAD --}}
<tr aria-label="{{ $keyTestFindReplace }}">
    <td>
        @include('form-items/label', [
            'for'   =>  $keyTestFindReplace,
            'title' =>  _wpcc('Test code for find-and-replaces in HTML'),
            'info'  =>  _wpcc('A piece of code to be used when testing find-and-replace settings below.')
        ])
    </td>
    <td>
        @include('form-items/textarea', [
            'name'          => $keyTestFindReplace,
            'placeholder'   =>  _wpcc('The code which will be used to test find-and-replace settings'),
        ])
    </td>
</tr>

{{-- FIND AND REPLACE IN RAW HTML--}}
<tr aria-label="{{ $keyFindReplaceRawHtml }}">
    <td>
        @include('form-items/label', [
            'for'   => $keyFindReplaceRawHtml,
            'title' => _wpcc("Find and replace in raw HTML"),
            'info'  => _wpcc('If you want some things to be replaced with some other things in <b>raw response content</b>,
                this is the place. <b>The replacements will be applied after the content of the response is retrieved</b>.
                The response content is the raw text data sent from the target web site. By using this setting, you can,
                for example, <b>fix HTML errors</b> which might cause the plugin not to be able to parse HTML properly.
                <b>Note that</b> the find-and-replace options here will be applied to raw HTML content before every test
                that requires a request to be sent under this tab.') . " " . _wpcc_trans_regex()
        ])
    </td>
    <td>
        @include('form-items/multiple', [
            'include'       =>  'form-items/find-replace',
            'name'          =>  $keyFindReplaceRawHtml,
            'addKeys'       =>  true,
            'remove'        =>  true,
            'addon'         =>  'dashicons dashicons-search',
            'data'          =>  [
                'urlSelector'       =>  "#" . $keyTestUrl,
                'subjectSelector'   =>  "#" . $keyTestFindReplace,
                'testType'          =>  \WPCCrawler\Test\Test::$TEST_TYPE_FIND_REPLACE_IN_RAW_HTML,
                'requiredSelectors' =>  "#{$keyTestUrl} | #{$keyTestFindReplace}", // One of them is enough
            ],
            'test'          => true,
            'addonClasses'  => 'wcc-test-find-replace-in-raw-html'
        ])
        @include('partials/test-result-container')
    </td>
</tr>

{{-- FIND AND REPLACE IN HTML AT FIRST LOAD--}}
<tr aria-label="{{ $keyFindReplaceFirstLoad }}">
    <td>
        @include('form-items/label', [
            'for'   => $keyFindReplaceFirstLoad,
            'title' => _wpcc("Find and replace in HTML at first load"),
            'info'  => _wpcc('If you want some things to be replaced with some other things in <b>HTML of
                the post page at first load</b>, this is the place. <b>The replacements will be applied after
                the HTML is retrieved and replacements defined in general settings page are applied</b>.') . " " . _wpcc_trans_regex()
        ])
    </td>
    <td>
        @include('form-items/multiple', [
            'include'       =>  'form-items/find-replace',
            'name'          =>  $keyFindReplaceFirstLoad,
            'addKeys'       =>  true,
            'remove'        =>  true,
            'addon'         =>  'dashicons dashicons-search',
            'data'          =>  [
                'urlSelector'       =>  "#" . $keyTestUrl,
                'subjectSelector'   =>  "#" . $keyTestFindReplace,
                'testType'          =>  \WPCCrawler\Test\Test::$TEST_TYPE_FIND_REPLACE_IN_HTML_AT_FIRST_LOAD,
                'requiredSelectors' =>  "#{$keyTestUrl} | #{$keyTestFindReplace}", // One of them is enough
            ],
            'test'          => true,
            'addonClasses'  => 'wcc-test-find-replace'
        ])
        @include('partials/test-result-container')
    </td>
</tr>

{{-- FIND AND REPLACE IN ELEMENT ATTRIBUTES --}}
<tr aria-label="{{ $keyFindReplaceElementAttributes }}">
    <td>
        @include('form-items/label', [
            'for'   => $keyFindReplaceElementAttributes,
            'title' => _wpcc("Find and replace in element attributes"),
            'info'  => _wpcc('If you want some things to be replaced with some other things in <b>attributes of
                elements</b>, this is the place. <b>The replacements will be applied after
                the replacements at first load are applied</b>.') . " " . $cssSelectorChangeEmptyResultWarning . " " . _wpcc_trans_regex()
        ])
    </td>
    <td>
        @include('form-items/multiple', [
            'include'       =>  'form-items/find-replace-in-element-attributes',
            'name'          =>  $keyFindReplaceElementAttributes,
            'addKeys'       =>  true,
            'remove'        =>  true,
            'addon'         =>  'dashicons dashicons-search',
            'data'          =>  [
                'urlSelector'       =>  "#" . $keyTestUrl,
                'subjectSelector'   =>  "#" . $keyTestFindReplace,
                'testType'          =>  \WPCCrawler\Test\Test::$TEST_TYPE_FIND_REPLACE_IN_ELEMENT_ATTRIBUTES,
                'requiredSelectors' =>  "#{$keyTestUrl} | #{$keyTestFindReplace}", // One of them is enough
            ],
            'test'          => true,
            'addonClasses'  => 'wcc-test-find-replace-in-element-attributes'
        ])
        @include('partials/test-result-container')
    </td>
</tr>

{{-- EXCHANGE ELEMENT ATTRIBUTES --}}
<tr aria-label="{{ $keyExchangeElementAttributes }}">
    <td>
        @include('form-items/label', [
            'for'   => $keyExchangeElementAttributes,
            'title' => _wpcc("Exchange element attributes"),
            'info'  => sprintf(_wpcc('If you want to exchange <b>the values of two attributes of an element</b>,
                this is the place. <b>If value of attribute 2 does not exist, the values will not be exchanged.</b>
                <b>The replacements will be applied after the find-and-replaces for element attributes are applied.</b>
                E.g. you can replace the values of %1$s and %2$s attributes to save lazy-loading images if the
                target %3$s element has these attributes.') . " " . $cssSelectorChangeEmptyResultWarning,
                '<span class="highlight attribute">src</span>',
                '<span class="highlight attribute">data-src</span>',
                '<span class="highlight selector">img</span>'
            )
        ])
    </td>
    <td>
        @include('form-items/multiple', [
            'include'       =>  'form-items/exchange-element-attributes',
            'name'          =>  $keyExchangeElementAttributes,
            'addKeys'       =>  true,
            'remove'        =>  true,
            'addon'         =>  'dashicons dashicons-search',
            'data'          =>  [
                'urlSelector'       =>  "#" . $keyTestUrl,
                'subjectSelector'   =>  "#" . $keyTestFindReplace,
                'testType'          =>  \WPCCrawler\Test\Test::$TEST_TYPE_EXCHANGE_ELEMENT_ATTRIBUTES,
                'requiredSelectors' =>  "#{$keyTestUrl} | #{$keyTestFindReplace}", // One of them is enough
            ],
            'test'          => true,
            'addonClasses'  => 'wcc-test-exchange-element-attributes'
        ])
        @include('partials/test-result-container')
    </td>
</tr>

{{-- REMOVE ELEMENT ATTRIBUTES --}}
<tr aria-label="{{ $keyRemoveElementAttributes }}">
    <td>
        @include('form-items/label', [
            'for'   => $keyRemoveElementAttributes,
            'title' => _wpcc("Remove element attributes"),
            'info'  => _wpcc('If you want to remove <b>attributes of an element</b>, this is the place. <b>The
                removals will be applied after the attribute exchanges are applied</b>.') . " " . $cssSelectorChangeEmptyResultWarning
        ])
    </td>
    <td>
        @include('form-items/multiple', [
            'include'       =>  'form-items/remove-element-attributes',
            'name'          =>  $keyRemoveElementAttributes,
            'addKeys'       =>  true,
            'remove'        =>  true,
            'addon'         =>  'dashicons dashicons-search',
            'data'          =>  [
                'urlSelector'       =>  "#" . $keyTestUrl,
                'subjectSelector'   =>  "#" . $keyTestFindReplace,
                'testType'          =>  \WPCCrawler\Test\Test::$TEST_TYPE_REMOVE_ELEMENT_ATTRIBUTES,
                'requiredSelectors' =>  "#{$keyTestUrl} | #{$keyTestFindReplace}", // One of them is enough
            ],
            'test'          => true,
            'addonClasses'  => 'wcc-test-remove-element-attributes'
        ])
        @include('partials/test-result-container')
    </td>
</tr>

{{-- FIND AND REPLACE IN ELEMENT HTML --}}
<tr aria-label="{{ $keyFindReplaceElementHtml }}">
    <td>
        @include('form-items/label', [
            'for'   => $keyFindReplaceElementHtml,
            'title' => _wpcc("Find and replace in element HTML"),
            'info'  => _wpcc('If you want some things to be replaced with some other things in <b>HTML of
                elements</b>, this is the place. <b>The replacements will be applied after
                the attribute removals are applied</b>.') . " " . $cssSelectorChangeEmptyResultWarning . " " . _wpcc_trans_regex()
        ])
    </td>
    <td>
        @include('form-items/multiple', [
            'include'       =>  'form-items/find-replace-in-element-html',
            'name'          =>  $keyFindReplaceElementHtml,
            'addKeys'       =>  true,
            'remove'        =>  true,
            'addon'         =>  'dashicons dashicons-search',
            'data'          =>  [
                'urlSelector'       =>  "#" . $keyTestUrl,
                'subjectSelector'   =>  "#" . $keyTestFindReplace,
                'testType'          =>  \WPCCrawler\Test\Test::$TEST_TYPE_FIND_REPLACE_IN_ELEMENT_HTML,
                'requiredSelectors' =>  "#{$keyTestUrl} | #{$keyTestFindReplace}", // One of them is enough
            ],
            'test'          => true,
            'addonClasses'  => 'wcc-test-find-replace-in-element-html'
        ])
        @include('partials/test-result-container')
    </td>
</tr>

{{-- CONVERT JSON TO HTML --}}
@include('form-items.combined.multiple-selector-with-attribute', [
    'name'          =>  $keyConvertJsonToHtml,
    'title'         =>  _wpcc('Convert JSON to HTML'),
    'info'          =>  _wpcc('CSS selectors that find JSON values to be converted into HTML elements. The found values
        must be valid JSON. If they are not valid, you can use the options box to prepare the values and make them
        valid. After the JSON values are converted to HTML elements, they will be added to the "body" element of the
        page, so that you can use the JSON data in other settings by selecting them via CSS selectors.'),
    'urlSelector'   =>  "#" . $keyTestUrl,
    'data'          =>  [
        'testType' =>  \WPCCrawler\Test\Test::$TEST_TYPE_CONVERT_JSON_TO_HTML,
        'selectorFinderBehavior' =>  \WPCCrawler\Objects\Enums\SelectorFinderBehavior::SIMILAR,
    ],
    'inputClass'    => 'css-selector',
    'showDevTools'  => true,
    'defaultAttr'   => 'text',
    'optionsBox'    => true,
])

{{-- CONVERT JSON TO HTML AUTOMATICALLY --}}
@include('form-items.combined.checkbox-with-label', [
    'name'  => $keyConvertJsonToHtmlAuto,
    'title' => _wpcc('Convert JSON to HTML automatically?'),
    'info'  => _wpcc('When this is enabled, JSON strings in the page will be automatically detected and converted into
        HTML elements, so that you can use the JSON data in other settings by selecting them via CSS selectors.')
])