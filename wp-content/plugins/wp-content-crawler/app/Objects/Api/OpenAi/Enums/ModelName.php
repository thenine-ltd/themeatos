<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 05/02/2023
 * Time: 21:13
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi\Enums;

abstract class ModelName {

    const TEXT_DAVINCI_003 = 'text-davinci-003';
    const TEXT_CURIE_001 = 'text-curie-001';
    const TEXT_BABBAGE_001 = 'text-babbage-001';
    const TEXT_ADA_001 = 'text-ada-001';
    const TEXT_DAVINCI_002 = 'text-davinci-002';
    const TEXT_DAVINCI_001 = 'text-davinci-001';
    const DAVINCI_INSTRUCT_BETA = 'davinci-instruct-beta';
    const DAVINCI = 'davinci';
    const CURIE_INSTRUCT_BETA = 'curie-instruct-beta';
    const CURIE = 'curie';
    const BABBAGE = 'babbage';
    const ADA = 'ada';
    const CODE_DAVINCI_002 = 'code-davinci-002';
    const CODE_CUSHMAN_001 = 'code-cushman-001';
    const TEXT_DAVINCI_INSERT_002 = 'text-davinci-insert-002';
    const TEXT_DAVINCI_INSERT_001 = 'text-davinci-insert-001';
    const TEXT_DAVINCI_EDIT_001 = 'text-davinci-edit-001';
    const CODE_DAVINCI_EDIT_001 = 'code-davinci-edit-001';

    const GPT_35_TURBO          = 'gpt-3.5-turbo';
    const GPT_35_TURBO_16K      = 'gpt-3.5-turbo-16k';
    const GPT_35_TURBO_0301     = 'gpt-3.5-turbo-0301';
    const GPT_35_TURBO_INSTRUCT = 'gpt-3.5-turbo-instruct';
    const GPT_4                 = 'gpt-4';
    const GPT_4_32K             = 'gpt-4-32k';

}