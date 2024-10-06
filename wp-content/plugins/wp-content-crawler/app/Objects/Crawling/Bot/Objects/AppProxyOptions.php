<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 02/07/2023
 * Time: 09:13
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Crawling\Bot\Objects;

class AppProxyOptions {

    /** @var string The proxy URL */
    private $url;

    /** @var string "http", "https" or "tcp" */
    private $protocol;

    /**
     * @param string      $url      See {@link url}
     * @param string|null $protocol See {@link protocol}
     * @since 1.14.0
     */
    public function __construct(string $url, ?string $protocol = 'http') {
        $this->url      = $url;
        $this->protocol = $protocol !== null && in_array($protocol, ['http', 'https', 'tcp'])
            ? $protocol
            : 'http';

    }

    /**
     * @return string
     * @since 1.14.0
     */
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * @return string
     * @since 1.14.0
     */
    public function getProtocol(): string {
        return $this->protocol;
    }

}