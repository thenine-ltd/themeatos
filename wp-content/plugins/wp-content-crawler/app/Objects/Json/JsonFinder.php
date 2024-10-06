<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 07/07/2023
 * Time: 19:12
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Json;

use Illuminate\Support\Arr;
use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Crawling\Bot\DummyBot;
use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;

/**
 * Finds JSON strings in a {@link Crawler}
 */
class JsonFinder {

    /** @var string|null See {@link getScriptMimeTypeSelector()} */
    private static $scriptMimeTypeSelector = null;

    /** @var Crawler The crawler that might contain JSON strings */
    private $crawler;

    /**
     * @param Crawler $crawler
     * @since 1.14.0
     */
    public function __construct(Crawler $crawler) {
        $this->crawler = $crawler;
    }

    /**
     * @return Crawler
     * @since 1.14.0
     */
    public function getCrawler(): Crawler {
        return $this->crawler;
    }

    /**
     * @return string[] JSON strings found in the crawler
     * @since 1.14.0
     */
    public function find(): array {
        return $this->findFromJsonScripts();
    }

    /*
     * HELPERS
     */

    /**
     * Finds the contents of scripts having a type of "application/json" and "application/ld+json"
     *
     * @return string[] The JSON strings
     * @since 1.14.0
     */
    protected function findFromJsonScripts(): array {
        $selectors = [
            [
                SettingInnerKey::SELECTOR => self::getScriptMimeTypeSelector(),
                SettingInnerKey::ATTRIBUTE => 'text',
            ]
        ];

        $jsonStrings = (new DummyBot([]))->extractValuesWithMultipleSelectorData($this->getCrawler(), $selectors, 'text');
        if (!is_array($jsonStrings)) {
            return [];
        }

        return Arr::flatten($jsonStrings);
    }

    /*
     *
     */

    /**
     * @return string A CSS selector that selects the `script` elements containing valid JSON strings
     * @since 1.14.0
     */
    protected static function getScriptMimeTypeSelector(): string {
        if (self::$scriptMimeTypeSelector === null) {
            $jsonMimeTypes = [
                'application/json',
                'application/ld+json',
                'application/hal+json',
                'application/vnd.api+json',
            ];

            self::$scriptMimeTypeSelector = implode(', ', array_map(function($mimeType) {
                return "script[type='$mimeType']";
            }, $jsonMimeTypes));
        }

        return self::$scriptMimeTypeSelector;
    }
}