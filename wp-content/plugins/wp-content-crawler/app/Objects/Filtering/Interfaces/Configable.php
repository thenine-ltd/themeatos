<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 07/09/2023
 * Time: 11:34
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Interfaces;

interface Configable {

    /**
     * @return array The configuration
     * @since 1.14.0
     */
    public function toConfig(): array;

}