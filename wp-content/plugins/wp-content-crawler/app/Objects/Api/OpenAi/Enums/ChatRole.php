<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 02/03/2023
 * Time: 11:00
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi\Enums;

/**
 * Roles available for a OpenAI chat message
 */
abstract class ChatRole {

    const SYSTEM    = 'system';
    const USER      = 'user';
    const ASSISTANT = 'assistant';

}