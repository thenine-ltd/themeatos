<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 21/09/2023
 * Time: 09:04
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\GlobalShortCodes\ShortCodes\Base;

/**
 * Outputs a `script` element. The children of this class are intended to output `script` elements that render the HTML
 * embeds of social media platforms, such as Instagram. The reason for not using the `wpcc-script` short code is to make
 * the script URLs variables, so that, if the script URL of a platform changes, the scripts used in the post contents
 * do not need to be updated one by one.
 */
abstract class BasePlatformScriptShortCode extends BaseGlobalShortCode {

    /**
     * @return string Absolute URL of the JavaScript file
     * @since 1.14.0
     */
    abstract protected function getScriptUrl(): string;

    protected function parse($attributes, $content): string {
        // Create and output the element
        /** @noinspection HtmlUnknownTarget */
        return sprintf('<script async src="%1$s"></script>', $this->getScriptUrl());
    }

    protected function getDefaults(): array {
        return [];
    }

}
