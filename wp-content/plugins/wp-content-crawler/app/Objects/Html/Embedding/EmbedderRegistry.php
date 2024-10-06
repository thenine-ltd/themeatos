<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 19/09/2023
 * Time: 14:48
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Html\Embedding;

use WPCCrawler\Objects\Html\Embedding\Base\AbstractEmbedder;
use WPCCrawler\Objects\Html\Embedding\GenericEmbedders\IframeEmbedder;
use WPCCrawler\Objects\Html\Embedding\GenericEmbedders\SourceEmbedder;
use WPCCrawler\Objects\Html\Embedding\PlatformEmbedders\ImgurEmbedder;
use WPCCrawler\Objects\Html\Embedding\PlatformEmbedders\InstagramEmbedder;
use WPCCrawler\Objects\Html\Embedding\PlatformEmbedders\TikTokEmbedder;
use WPCCrawler\Objects\Html\Embedding\PlatformEmbedders\TumblrEmbedder;
use WPCCrawler\Objects\Html\Embedding\PlatformEmbedders\TwitterEmbedder;

class EmbedderRegistry {

    /** @var EmbedderRegistry|null */
    private static $instance = null;

    /** @var class-string<AbstractEmbedder>[]|null */
    private $registry = null;

    /**
     * @return EmbedderRegistry
     * @since 1.14.0
     */
    public static function getInstance(): EmbedderRegistry {
        if (self::$instance === null) {
            self::$instance = new EmbedderRegistry();
        }

        return self::$instance;
    }

    /*
     *
     */

    /**
     * This is a singleton. Use {@link getInstance()}.
     */
    protected function __construct() {}

    /**
     * @return class-string<AbstractEmbedder>[]
     * @since 1.14.0
     */
    public function getRegistry(): array {
        if ($this->registry === null) {
            $this->registry = $this->createRegistry();
        }

        return $this->registry;
    }

    /*
     *
     */

    /**
     * @return class-string<AbstractEmbedder>[]
     * @since 1.14.0
     */
    protected function createRegistry(): array {
        return [
            // Adding an embedder to embed an iframe seems to be unnecessary, since we can convert the iframe to
            // [wpcc-iframe] short code. Instead, add embedders for HTML embeds that use an element other than an
            // iframe, since they require custom JavaScript code to be rendered. Twitter's HTML embed is an example for
            // this, as it uses a "blockquote" to render a non-iframe element by using custom JavaScript code. On the
            // other hand, if the user does not trust the website that much to allow conversion of all the iframe
            // elements to [wpcc-iframe] short code, the WordPress-trusted platforms can still be embedded. In that
            // case, as a workaround, we can have a list of trusted domains, which should be the same as WordPress's
            // (see class-wp-oembed.php).
            ImgurEmbedder::class,     // non-iframe
            InstagramEmbedder::class, // non-iframe
            TikTokEmbedder::class,    // non-iframe
            TumblrEmbedder::class,    // non-iframe
            TwitterEmbedder::class,   // non-iframe

            // Generic embedders. Add these to the end of this array, so that the platform embedders are applied first.
            IframeEmbedder::class, // Embeds all the `iframe` elements
            SourceEmbedder::class, // Embeds all the `source` elements
        ];
    }

}