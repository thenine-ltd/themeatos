<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 22/02/2023
 * Time: 18:08
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Views\Select;

use WPCCrawler\Objects\Views\Base\AbstractSelectWithLabel;

class SelectElementLocationWithLabel extends AbstractSelectWithLabel {

    public function getKey(): string {
        return 'form-items.combined.select-element-location-with-label';
    }

}