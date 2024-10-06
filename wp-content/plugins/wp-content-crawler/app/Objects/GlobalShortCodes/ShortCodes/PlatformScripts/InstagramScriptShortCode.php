<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 21/09/2023
 * Time: 09:06
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\GlobalShortCodes\ShortCodes\PlatformScripts;

use WPCCrawler\Objects\GlobalShortCodes\ShortCodes\Base\BasePlatformScriptShortCode;

class InstagramScriptShortCode extends BasePlatformScriptShortCode {

    const TAG_NAME = "wpcc-instagram-script";

    public function getTagName(): string {
        return self::TAG_NAME;
    }

    protected function getScriptUrl(): string {
        return "https://www.instagram.com/embed.js";
    }

}