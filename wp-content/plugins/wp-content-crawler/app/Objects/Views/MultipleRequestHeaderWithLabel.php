<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 27/06/2023
 * Time: 17:05
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Views;

use WPCCrawler\Objects\Views\Base\AbstractViewWithLabel;

class MultipleRequestHeaderWithLabel extends AbstractViewWithLabel {

    public function getKey(): string {
        return 'form-items.combined.multiple-request-header-with-label';
    }

    protected function createViewVariableNames(): ?array {
        return null;
    }

}