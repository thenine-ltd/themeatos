

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


<?php echo $__env->make('partials.table-section-title', [
    'title' => _wpcc("Manipulate HTML"),
    'key'   => $sectionKey,
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>


<tr aria-label="<?php echo e($keyTestFindReplace); ?>">
    <td>
        <?php echo $__env->make('form-items/label', [
            'for'   =>  $keyTestFindReplace,
            'title' =>  _wpcc('Test code for find-and-replaces in HTML'),
            'info'  =>  _wpcc('A piece of code to be used when testing find-and-replace settings below.')
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
    <td>
        <?php echo $__env->make('form-items/textarea', [
            'name'          => $keyTestFindReplace,
            'placeholder'   =>  _wpcc('The code which will be used to test find-and-replace settings'),
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
</tr>


<tr aria-label="<?php echo e($keyFindReplaceRawHtml); ?>">
    <td>
        <?php echo $__env->make('form-items/label', [
            'for'   => $keyFindReplaceRawHtml,
            'title' => _wpcc("Find and replace in raw HTML"),
            'info'  => _wpcc('If you want some things to be replaced with some other things in <b>raw response content</b>,
                this is the place. <b>The replacements will be applied after the content of the response is retrieved</b>.
                The response content is the raw text data sent from the target web site. By using this setting, you can,
                for example, <b>fix HTML errors</b> which might cause the plugin not to be able to parse HTML properly.
                <b>Note that</b> the find-and-replace options here will be applied to raw HTML content before every test
                that requires a request to be sent under this tab.') . " " . _wpcc_trans_regex()
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
    <td>
        <?php echo $__env->make('form-items/multiple', [
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
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('partials/test-result-container', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
</tr>


<tr aria-label="<?php echo e($keyFindReplaceFirstLoad); ?>">
    <td>
        <?php echo $__env->make('form-items/label', [
            'for'   => $keyFindReplaceFirstLoad,
            'title' => _wpcc("Find and replace in HTML at first load"),
            'info'  => _wpcc('If you want some things to be replaced with some other things in <b>HTML of
                the post page at first load</b>, this is the place. <b>The replacements will be applied after
                the HTML is retrieved and replacements defined in general settings page are applied</b>.') . " " . _wpcc_trans_regex()
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
    <td>
        <?php echo $__env->make('form-items/multiple', [
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
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('partials/test-result-container', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
</tr>


<tr aria-label="<?php echo e($keyFindReplaceElementAttributes); ?>">
    <td>
        <?php echo $__env->make('form-items/label', [
            'for'   => $keyFindReplaceElementAttributes,
            'title' => _wpcc("Find and replace in element attributes"),
            'info'  => _wpcc('If you want some things to be replaced with some other things in <b>attributes of
                elements</b>, this is the place. <b>The replacements will be applied after
                the replacements at first load are applied</b>.') . " " . $cssSelectorChangeEmptyResultWarning . " " . _wpcc_trans_regex()
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
    <td>
        <?php echo $__env->make('form-items/multiple', [
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
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('partials/test-result-container', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
</tr>


<tr aria-label="<?php echo e($keyExchangeElementAttributes); ?>">
    <td>
        <?php echo $__env->make('form-items/label', [
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
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
    <td>
        <?php echo $__env->make('form-items/multiple', [
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
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('partials/test-result-container', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
</tr>


<tr aria-label="<?php echo e($keyRemoveElementAttributes); ?>">
    <td>
        <?php echo $__env->make('form-items/label', [
            'for'   => $keyRemoveElementAttributes,
            'title' => _wpcc("Remove element attributes"),
            'info'  => _wpcc('If you want to remove <b>attributes of an element</b>, this is the place. <b>The
                removals will be applied after the attribute exchanges are applied</b>.') . " " . $cssSelectorChangeEmptyResultWarning
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
    <td>
        <?php echo $__env->make('form-items/multiple', [
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
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('partials/test-result-container', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
</tr>


<tr aria-label="<?php echo e($keyFindReplaceElementHtml); ?>">
    <td>
        <?php echo $__env->make('form-items/label', [
            'for'   => $keyFindReplaceElementHtml,
            'title' => _wpcc("Find and replace in element HTML"),
            'info'  => _wpcc('If you want some things to be replaced with some other things in <b>HTML of
                elements</b>, this is the place. <b>The replacements will be applied after
                the attribute removals are applied</b>.') . " " . $cssSelectorChangeEmptyResultWarning . " " . _wpcc_trans_regex()
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
    <td>
        <?php echo $__env->make('form-items/multiple', [
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
        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        <?php echo $__env->make('partials/test-result-container', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </td>
</tr>


<?php echo $__env->make('form-items.combined.multiple-selector-with-attribute', [
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
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>


<?php echo $__env->make('form-items.combined.checkbox-with-label', [
    'name'  => $keyConvertJsonToHtmlAuto,
    'title' => _wpcc('Convert JSON to HTML automatically?'),
    'info'  => _wpcc('When this is enabled, JSON strings in the page will be automatically detected and converted into
        HTML elements, so that you can use the JSON data in other settings by selecting them via CSS selectors.')
], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/customer/www/themeatos.com/public_html/wp-content/plugins/wp-content-crawler/app/views/site-settings/partial/html-manipulation-inputs.blade.php ENDPATH**/ ?>