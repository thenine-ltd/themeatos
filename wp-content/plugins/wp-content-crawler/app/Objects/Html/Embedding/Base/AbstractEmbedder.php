<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 21/09/2023
 * Time: 18:25
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Html\Embedding\Base;

use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Crawling\Bot\DummyBot;

abstract class AbstractEmbedder {

    /**
     * @var Crawler[] Elements that store a third-party platform's HTML embed. When a WordPress short code is used,
     *                these elements will be removed. So, this array should contain all the nodes related to one HTML
     *                embed.
     */
    private $nodes;

    /**
     * @param Crawler[] $nodes See {@link nodes}
     * @since 1.14.0
     */
    protected function __construct(array $nodes) {
        $this->nodes = array_values($nodes);
    }

    /**
     * Converts the {@link nodes} to a WordPress-registered embed short code or adds a WordPress-registered short code
     * to make the HTML embed renderable in the front end, if possible. Otherwise, it does nothing.
     *
     * @since 1.14.0
     */
    public function embed(): void {
        $this->onEmbed();
    }

    /**
     * Converts the HTML embed to a WordPress-suitable embed, which is an embed code that is rendered by WordPress to
     * show the actual HTML embed in the front end.
     *
     * @since 1.14.0
     */
    abstract protected function onEmbed(): void;

    /*
     *
     */

    /**
     * Removes the {@link nodes} from the DOM
     *
     * @since 1.14.0
     */
    protected function removeNodes(): void {
        $dummyBot = new DummyBot([]);

        $nodes = $this->getNodes();
        foreach($nodes as $node) {
            $dummyBot->removeNode($node);
        }
    }

    /*
     * GETTERS
     */

    /**
     * @return Crawler[] See {@link nodes}
     * @since 1.14.0
     */
    protected function getNodes(): array {
        return $this->nodes;
    }

    /*
     * STATIC METHODS
     */

    /**
     * @param Crawler $crawler The crawler that contains none or many HTML embeds
     * @return AbstractEmbedder[] An embedder for each HTML embed found in the crawler
     * @since 1.14.0
     */
    abstract public static function fromCrawler(Crawler $crawler): array;

}