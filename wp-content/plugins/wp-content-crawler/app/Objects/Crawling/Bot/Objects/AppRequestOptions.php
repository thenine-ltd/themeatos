<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 02/07/2023
 * Time: 09:12
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Crawling\Bot\Objects;

use WPCCrawler\Objects\Enums\RequestMethod;

class AppRequestOptions {

    /** @var string */
    private $method;

    /** @var AppProxyOptions|null Details of a proxy that will be used when making a request */
    private $proxy;

    /** @var string|null The request's raw body data */
    private $body;

    /**
     * @param string $method HTTP request method, e.g. GET, POST, HEAD, PUT, DELETE. One of the constants defined in
     *                       {@link RequestMethod} class.
     * @since 1.14.0
     */
    public function __construct(string $method = RequestMethod::GET) {
        $this->method = $method;
    }

    /**
     * @return string
     * @since 1.14.0
     */
    public function getMethod(): string {
        return $this->method;
    }

    /**
     * @return string|null See {@link body}
     * @since 1.14.0
     */
    public function getBody(): ?string {
        return $this->body;
    }

    /**
     * @param string|null $body See {@link body}
     * @return AppRequestOptions
     * @since 1.14.0
     */
    public function setBody(?string $body): AppRequestOptions {
        $this->body = $body;
        return $this;
    }

    /**
     * @return AppProxyOptions|null
     * @since 1.14.0
     */
    public function getProxy(): ?AppProxyOptions {
        return $this->proxy;
    }

    /**
     * @param AppProxyOptions|null $proxy
     * @return AppRequestOptions
     * @since 1.14.0
     */
    public function setProxy(?AppProxyOptions $proxy): AppRequestOptions {
        $this->proxy = $proxy;
        return $this;
    }

}