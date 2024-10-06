<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 05/02/2023
 * Time: 17:24
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi\Enums;

/**
 * Modes of OpenAI models
 * @see https://platform.openai.com/playground
 */
abstract class ModelMode {

    const CHAT     = 'chat';
    const COMPLETE = 'complete';
    const INSERT   = 'insert';
    const EDIT     = 'edit';

}