<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 20/08/2023
 * Time: 17:33
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Test\Enums;

abstract class ManipulationStep {

    const NONE                            = -1;
    const INITIAL_REPLACEMENTS            = 0;
    const FIND_REPLACE_ELEMENT_ATTRIBUTES = 1;
    const EXCHANGE_ELEMENT_ATTRIBUTES     = 2;
    const REMOVE_ELEMENT_ATTRIBUTES       = 3;
    const FIND_REPLACE_ELEMENT_HTML       = 4;
    const CONVERT_JSON_TO_HTML            = 5;
    const REMOVE_ELEMENTS_FROM_CRAWLER    = 6;

}