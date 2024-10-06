<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 20/09/2023
 * Time: 10:26
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Html\Embedding\PlatformEmbedders;

use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Html\Embedding\Base\AbstractPlatformEmbedder;

class TikTokEmbedder extends AbstractPlatformEmbedder {

    protected function onExtractUrl(Crawler $node): ?string {
        $url = $node->attr('cite');
        if ($url !== null) {
            return $url;
        }

        $videoId = $node->attr('data-video-id');
        return $videoId === null
            ? null
            : "https://www.tiktok.com/@handle/video/{$videoId}";
    }

    protected function getFallbackScriptShortCodeTagName(): ?string {
        return null;
    }

    /*
     *
     */

    /**
     * @param Crawler $crawler
     * @return TikTokEmbedder[]
     * @since 1.14.0
     */
    public static function fromCrawler(Crawler $crawler): array {
        $result = [];
        $crawler
            ->filter('blockquote[class*="tiktok"]')
            ->each(function(Crawler $node) use (&$result) {
                $result[] = new TikTokEmbedder([$node]);
            });

        return $result;
    }

}