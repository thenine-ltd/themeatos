<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 21/09/2023
 * Time: 09:20
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Html\Embedding\PlatformEmbedders;

use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\GlobalShortCodes\ShortCodes\PlatformScripts\InstagramScriptShortCode;
use WPCCrawler\Objects\Html\Embedding\Base\AbstractPlatformEmbedder;

class InstagramEmbedder extends AbstractPlatformEmbedder {

    protected function onExtractUrl(Crawler $node): ?string {
        return $node
            ->filter('a')
            ->last()
            ->attr('href');
    }

    protected function getFallbackScriptShortCodeTagName(): ?string {
        // WordPress does not support Instagram oEmbed, since its API requires authentication. So, we inject Instagram's
        // embed script into the DOM via short code so that HTML embed already existing in the DOM can be rendered in
        // the front end.
        return InstagramScriptShortCode::TAG_NAME;
    }

    /*
     *
     */

    /**
     * @param Crawler $crawler
     * @return InstagramEmbedder[]
     * @since 1.14.0
     */
    public static function fromCrawler(Crawler $crawler): array {
        $result = [];
        $crawler
            ->filter('blockquote.instagram-media')
            ->each(function(Crawler $node) use (&$result) {
                $result[] = new InstagramEmbedder([$node]);
            });

        return $result;
    }

}