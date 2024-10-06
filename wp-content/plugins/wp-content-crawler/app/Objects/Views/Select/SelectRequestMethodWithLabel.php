<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 27/06/2023
 * Time: 16:20
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Views\Select;

use WPCCrawler\Objects\Views\Base\AbstractSelectWithLabel;

class SelectRequestMethodWithLabel extends AbstractSelectWithLabel {

    public function getKey(): string {
        return 'form-items.combined.select-request-method-with-label';
    }

}