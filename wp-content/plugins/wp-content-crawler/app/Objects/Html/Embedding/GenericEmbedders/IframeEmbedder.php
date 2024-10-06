<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 21/09/2023
 * Time: 18:22
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Html\Embedding\GenericEmbedders;

use DOMNode;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\GlobalShortCodes\ShortCodes\IFrameGlobalShortCode;
use WPCCrawler\Objects\Html\Embedding\Base\AbstractElementEmbedder;

/**
 * Converts all the `iframe` elements to {@link IFrameGlobalShortCode} so that the elements can be rendered in the front
 * end.
 */
class IframeEmbedder extends AbstractElementEmbedder {

    const TAG_NAME = 'iframe';

    protected function isNodeConvertable(DOMNode $node): bool {
        return Str::lower($node->nodeName) === self::TAG_NAME;
    }

    protected function onCreateEmbedShortCode(?string $attributes): ?string {
        if ($attributes === null) {
            return null;
        }

        // Create the wpcc-iframe short code with all the attributes of the iframe node
        return sprintf('[%1$s %2$s]',
            IFrameGlobalShortCode::TAG_NAME,
            $attributes
        );
    }

    /*
     *
     */

    /**
     * @param Crawler $crawler
     * @return IframeEmbedder[]
     * @since 1.14.0
     */
    public static function fromCrawler(Crawler $crawler): array {
        $result = [];
        $crawler
            ->filter(self::TAG_NAME)
            ->each(function(Crawler $node) use (&$result) {
                $result[] = new IframeEmbedder([$node]);
            });

        return $result;
    }

}