<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 22/02/2023
 * Time: 21:32
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Views;

use WPCCrawler\Objects\Views\Base\AbstractViewWithLabel;

/**
 * Creates a view that contains a label and a list of short codes that can be used when defining templates for
 * "create element" command.
 *
 * @since 1.13.0
 */
class ShortCodeButtonsWithLabelForCreateElementCmd extends AbstractViewWithLabel {

    public function getKey(): string {
        return 'form-items.combined.short-code-buttons-with-label-for-create-element-cmd';
    }

    protected function createViewVariableNames(): ?array {
        return null;
    }
}