<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 11/02/2023
 * Time: 17:56
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi\Enums;

abstract class ShortCodeAttributes {

    const DESC   = 'desc';
    const CONFIG = 'config';

    const MODE         = 'mode';
    const MODEL        = 'model';
    const MESSAGES     = 'messages';
    const PROMPT       = 'prompt';
    const STOP         = 'stop';
    const INPUT        = 'input';
    const INSTRUCTIONS = 'instructions';
    const TEMPERATURE  = 'temperature';
    const MAX_LENGTH   = 'maxLength';

    const TEST_SHORT_CODE_VALUES = 'testShortCodeValues';

}