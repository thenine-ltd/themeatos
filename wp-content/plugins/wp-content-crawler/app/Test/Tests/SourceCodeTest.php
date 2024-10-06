<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 25/10/2018
 * Time: 17:32
 */

namespace WPCCrawler\Test\Tests;

use Illuminate\Contracts\View\View;
use WPCCrawler\Objects\Cache\ResponseCache;
use WPCCrawler\Objects\Crawling\Bot\DummyBot;
use WPCCrawler\Objects\Enums\RequestMethod;
use WPCCrawler\Objects\Html\ScriptRemover;
use WPCCrawler\Objects\Traits\FindAndReplaceTrait;
use WPCCrawler\Test\Base\AbstractTest;
use WPCCrawler\Test\Data\TestData;
use WPCCrawler\Utils;

class SourceCodeTest extends AbstractTest {

    use FindAndReplaceTrait;

    protected $responseResultsKey = 'html';

    /**
     * Conduct the test and return an array of results.
     *
     * @param TestData $data Information required for the test
     * @return array|null
     */
    protected function createResults($data): ?array {
        $keySourceCodeOptions = 'sourceCodeOptions';

        $url                        = $data->get("url");
        $enableResponseCache        = (bool) $data->get("enableServerCache");
        $invalidateUrlCache         = (bool) $data->get("invalidateUrlCache");
        $applyManipulationOptions   = $data->get("{$keySourceCodeOptions}.applyManipulationOptions");
        $removeScripts              = $data->get("{$keySourceCodeOptions}.removeScripts");
        $removeStyles               = $data->get("{$keySourceCodeOptions}.removeStyles");

        if(!is_string($url)) return null;

        // If the URL's cache must be invalidated, invalidate it before making the request.
        if ($enableResponseCache && $invalidateUrlCache) {
            ResponseCache::getInstance()->delete(RequestMethod::GET, $url);
        }

        $bot = new DummyBot($data->getPostSettings(), null, $data->getUseUtf8(), $data->getConvertEncodingToUtf8());
        $bot->setResponseCacheEnabled($enableResponseCache);

        $crawler = $bot->request(
            $url,
            null,
            $applyManipulationOptions
                ? $data->getRawHtmlFindReplaces()
                : null
        );
        $isResponseFromCache = $bot->isLatestResponseFromCache();

        if(!$crawler) return null;

        // Apply manipulation options
        if ($applyManipulationOptions) {
            // If the last manipulation step is specified, use it.
            $lastManipulationStep = $data->get("lastManipulationStep");
            $lastManipulationStep = is_numeric($lastManipulationStep)
                ? (int) $lastManipulationStep
                : null;

            $this->applyHtmlManipulationOptions($bot, $crawler, $lastManipulationStep, $url);
        }

        // Remove the scripts in the page
        if($removeScripts) {
            $crawler = (new ScriptRemover($crawler))->removeScripts()->getCrawler();
        }

        // Remove the styles
        if($removeStyles) {
            // Remove style elements
            $bot->removeElementsFromCrawler($crawler, ["style", "[rel=stylesheet]"]);

            // Remove style attributes
            $bot->removeElementAttributes($crawler, ['[style]'], 'style');
        }

        // Get the HTML to be manipulated
        $html = Utils::getNodeHTML($crawler);

        // Remove empty attributes. This is important for CSS selector finder script. It fails when there is an attribute
        // whose attribute consists of only spaces.
        $html = $this->findAndReplaceSingle(
            '<.*?[a-zA-Z-]+=["\']\s+["\'].*?>',
            '',
            $html,
            true
        );

        $parts = parse_url($url);
        $base = is_array($parts) && isset($parts['host']) 
            ? ($parts['scheme'] ?? 'http') . '://' . $parts['host']
            : null;

        // Set the base URL like this. By this way, relative URLs will be handled correctly.
        if ($base !== null) {
            /** @noinspection HtmlRequiredTitleElement */
            $html = $this->findAndReplaceSingle(
                '(<\/head>)',
                ' <base href="' . $base . '"> $1',
                $html,
                true
            );
        }

        return [
            'prepared' => $html,
            'isResponseFromCache' => $isResponseFromCache,
        ];
    }

    /**
     * Create the view of the response
     *
     * @return View|null
     */
    protected function createView() {
        return null;
    }
}