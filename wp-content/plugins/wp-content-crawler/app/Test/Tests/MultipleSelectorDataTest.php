<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 07/08/2023
 * Time: 17:58
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Test\Tests;

use WPCCrawler\Objects\Crawling\Bot\DummyBot;
use WPCCrawler\Test\Base\AbstractTest;
use WPCCrawler\Test\Data\TestData;

/**
 * Finds values for CSS selector configurations in provided source code. The CSS selector configurations can contain
 * a CSS selector, an attribute, and options box configuration.
 */
class MultipleSelectorDataTest extends AbstractTest {

    /**
     * @param TestData $data
     * @return array|null
     * @since 1.14.0
     */
    protected function createResults($data): ?array {
        // Get the page URL from the provided data
        $pageUrl = $data->get("pageUrl");
        if (!is_string($pageUrl)) {
            return $this->createEmptyResponse();
        }

        // Get the CSS selector configurations
        $selectorConfigs = $data->get("selectorConfigs");
        if (!is_array($selectorConfigs)) {
            return $this->createEmptyResponse();
        }

        // Create a crawler that will be used to find the CSS selector results
        $bot = new DummyBot($data->getPostSettings(), null, $data->getUseUtf8(), $data->getConvertEncodingToUtf8());
        $bot->setResponseCacheEnabled(true);

        $crawler = $bot->request($pageUrl, null, $data->getRawHtmlFindReplaces());

        $lastManipulationStep = $data->get("lastManipulationStep");
        $lastManipulationStep = is_numeric($lastManipulationStep)
            ? (int) $lastManipulationStep
            : null;
        $this->applyHtmlManipulationOptions($bot, $crawler, $lastManipulationStep, $pageUrl);

        // Find the values found via the provided CSS selector configurations
        /** @var array<int|string, string[]> $results */
        $results = [];
        foreach($selectorConfigs as $key => $selectorData) {
            if (!is_array($selectorData)) continue;

            $values = $bot->extractValuesWithSelectorData($crawler, $selectorData, 'html');
            $results[$key] = is_array($values)
                ? $values
                : (array) $values;
        }

        return $this->createResponse($results, $bot->isLatestResponseFromCache());
    }

    protected function createView() {
        // This test does not have a view
        return null;
    }

    /*
     *
     */

    protected function createEmptyResponse(): array {
        return $this->createResponse([], false);
    }

    /**
     * @param array<string, mixed> $results
     * @param bool                 $isResponseFromCache
     * @return array
     * @since 1.14.0
     */
    protected function createResponse(array $results, bool $isResponseFromCache): array {
        return [
            'results' => $results,
            'isResponseFromCache' => $isResponseFromCache,
        ];
    }
}