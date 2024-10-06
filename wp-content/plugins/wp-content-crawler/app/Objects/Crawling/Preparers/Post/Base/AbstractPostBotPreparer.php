<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 02/11/2018
 * Time: 11:15
 */

namespace WPCCrawler\Objects\Crawling\Preparers\Post\Base;

use WPCCrawler\Objects\Crawling\Bot\AbstractBot;
use WPCCrawler\Objects\Crawling\Bot\PostBot;
use WPCCrawler\Objects\Crawling\Preparers\Interfaces\Preparer;
use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;
use WPCCrawler\Objects\Settings\Enums\SettingKey;

abstract class AbstractPostBotPreparer implements Preparer {

    /** @var PostBot */
    protected $bot;

    /**
     * @param PostBot $postBot
     */
    public function __construct(PostBot $postBot) {
        $this->bot = $postBot;
    }

    /**
     * Prepare the post bot
     *
     * @return void
     */
    public abstract function prepare();

    /**
     * Get values for a selector setting. This applies the options box configurations as well.
     *
     * @param string            $settingName  Name of the setting from which the selector data will be retrieved
     * @param string            $defaultAttr  Attribute value that will be used if the attribute is not found in the
     *                                        settings
     * @param false|null|string $contentType  See {@link AbstractBot::extractData}
     * @param bool              $singleResult See {@link AbstractBot::extractData}
     * @param bool              $trim         See {@link AbstractBot::extractData}
     * @return mixed|null If there are no results, returns null. If $singleResult is true, returns a single result.
     *                    Otherwise, returns an array.
     * @see AbstractBot::extractValuesForSelectorSetting()
     */
    protected function getValuesForSelectorSetting(string $settingName, string $defaultAttr, $contentType = false,
                                                   bool $singleResult = false, bool $trim = true) {

        return $this->bot->extractValuesForSelectorSetting($this->bot->getCrawler(), $settingName, $defaultAttr, $contentType, $singleResult, $trim);
    }

    /**
     * Get an array that contains custom short code names as the keys, and the short code values as the values. E.g.
     * [short_code_name => value1, short_code_name => value2]
     *
     * @return array
     * @since 1.13.1
     */
    protected function createCustomShortCodeValueMap(): array {
        $postCustomShortCodeSelectors = $this->bot->getArraySetting(SettingKey::POST_CUSTOM_CONTENT_SHORTCODE_SELECTORS);
        if(!$postCustomShortCodeSelectors) {
            return [];
        }

        // Prepare defaults by assigning empty values to all custom short codes
        $defaults = [];
        foreach($postCustomShortCodeSelectors as $v) {
            $name = $v[SettingInnerKey::SHORT_CODE] ?? null;
            if (!is_string($name) || $name === '') continue;

            $defaults[$name] = '';
        }

        // If there are not any short code data in the post data, no need to continue. Return the empty values.
        $shortCodeData = $this->getBot()->getPostData()->getShortCodeData();
        if(!$shortCodeData) {
            return $defaults;
        }

        // Get custom short codes that have values
        $map = [];
        foreach($shortCodeData as $scData) {
            $name = $scData[SettingInnerKey::SHORT_CODE] ?? null;
            if (!is_string($name) || $name === '') continue;

            $map[$name] = $scData['data'] ?? '';
        }

        return $map + $defaults;
    }

    /**
     * @return PostBot
     */
    public function getBot(): PostBot {
        return $this->bot;
    }
}