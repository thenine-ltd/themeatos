<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 19/09/2023
 * Time: 10:24
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Html\Embedding;

use WPCCrawler\Objects\Crawling\Bot\DummyBot;
use WPCCrawler\Objects\Html\Embedding\Base\AbstractEmbedder;

/**
 * Finds the embeddable content in the provided HTML and converts them to embed short code so that WordPress renders
 * them properly in the front-end. Embeddable content can be social media posts, videos, podcasts, etc. from Twitter,
 * YouTube, Spotify, and so on.
 */
class AutoEmbedder {

    /** @var string The HTML code that contains the embeddable content */
    private $html;

    /**
     * @param string $html See {@link html}
     */
    public function __construct(string $html) {
        $this->html = $html;
    }

    /**
     * @return string The HTML code whose embeddable content is converted into WordPress-registered short codes
     * @since 1.14.0
     */
    public function embed(): string {
        $bot = new DummyBot([]);
        $crawler = $bot->createDummyCrawler($this->getHtml());

        $embedderClasses = EmbedderRegistry::getInstance()->getRegistry();
        foreach($embedderClasses as $embedderClass) {
            /** @var AbstractEmbedder $embedderClass */
            $embedders = $embedderClass::fromCrawler($crawler);
            foreach($embedders as $embedder) {
                $embedder->embed();
            }
        }

        return $bot->getContentFromDummyCrawler($crawler);
    }

    /*
     * GETTERS
     */

    /**
     * @return string See {@link html}
     * @since 1.14.0
     */
    public function getHtml(): string {
        return $this->html;
    }

}