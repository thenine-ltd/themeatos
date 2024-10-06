<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 05/02/2023
 * Time: 17:30
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi\Enums;

/**
 * Types of the models of OpenAI
 */
abstract class ModelType {

    const GPT3  = 'GPT-3';
    const GPT4  = 'GPT-4';
    const CODEX = 'Codex';

}