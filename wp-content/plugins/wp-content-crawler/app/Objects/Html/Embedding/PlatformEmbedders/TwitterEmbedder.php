<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 19/09/2023
 * Time: 14:53
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Html\Embedding\PlatformEmbedders;

use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Html\Embedding\Base\AbstractPlatformEmbedder;

class TwitterEmbedder extends AbstractPlatformEmbedder {

    protected function onExtractUrl(Crawler $node): ?string {
        return $node
            ->filter('a')
            ->last()
            ->attr('href');
    }

    protected function getFallbackScriptShortCodeTagName(): ?string {
        return null;
    }

    /*
     *
     */

    /**
     * @param Crawler $crawler
     * @return TwitterEmbedder[]
     * @since 1.14.0
     */
    public static function fromCrawler(Crawler $crawler): array {
        $result = [];
        $crawler
            ->filter('blockquote[class*="twitter"]')
            ->each(function(Crawler $node) use (&$result) {
                $result[] = new TwitterEmbedder([$node]);
            });

        return $result;
    }

}