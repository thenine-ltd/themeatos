<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 04/10/2023
 * Time: 08:39
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Views;

use WPCCrawler\Objects\Views\Base\AbstractViewWithLabel;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;

class MultipleSelectorWithAttributes extends AbstractViewWithLabel {

    public function getKey(): string {
        return 'form-items.combined.multiple-selector-with-attributes';
    }

    protected function createViewVariableNames(): ?array {
        return [
            ViewVariableName::URL_SELECTOR,
        ];
    }

}