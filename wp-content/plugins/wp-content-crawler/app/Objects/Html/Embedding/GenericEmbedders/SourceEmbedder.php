<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 22/09/2023
 * Time: 10:28
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Html\Embedding\GenericEmbedders;

use DOMNode;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\GlobalShortCodes\ShortCodes\ElementGlobalShortCode;
use WPCCrawler\Objects\Html\Embedding\Base\AbstractElementEmbedder;

/**
 * Converts all the `source` elements to {@link ElementGlobalShortCode} so that the elements can be rendered in the
 * front end.
 */
class SourceEmbedder extends AbstractElementEmbedder {

    const TAG_NAME = 'source';

    protected function isNodeConvertable(DOMNode $node): bool {
        return Str::lower($node->nodeName) === self::TAG_NAME;
    }

    protected function onCreateEmbedShortCode(?string $attributes): ?string {
        // A source element without any attributes is useless. So, if there are no attributes, do not create a short
        // code.
        if ($attributes === null) {
            return null;
        }

        // Create the element short code with all the attributes of the node
        return sprintf('[%1$s %2$s="%3$s" %4$s %5$s="0"]',
            ElementGlobalShortCode::TAG_NAME,
            ElementGlobalShortCode::ATTR_TAG,
            self::TAG_NAME,
            $attributes,
            ElementGlobalShortCode::ATTR_CLOSE
        );
    }

    /*
     *
     */

    /**
     * @param Crawler $crawler
     * @return SourceEmbedder[]
     * @since 1.14.0
     */
    public static function fromCrawler(Crawler $crawler): array {
        $result = [];
        $crawler
            ->filter(self::TAG_NAME)
            ->each(function(Crawler $node) use (&$result) {
                $result[] = new SourceEmbedder([$node]);
            });

        return $result;
    }
}