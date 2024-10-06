<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 19/09/2023
 * Time: 14:42
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Html\Embedding\Base;

use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Html\ElementCreator;

abstract class AbstractPlatformEmbedder extends AbstractEmbedder {

    // Supported platforms can be found here: wp-includes/class-wp-oembed.php {@link WP_oEmbed::__construct()}

    /**
     * @return string|null Embeddable URL extracted from {@link nodes}. This URL will be given to {@link isEmbeddable()}
     *                     to make sure it is actually embeddable.
     * @since 1.14.0
     */
    abstract protected function onExtractUrl(Crawler $node): ?string;

    /**
     * @return string|null Tag name of a global WordPress short code that outputs a `script` element that injects the
     *                     JavaScript file that renders the HTML embed. This will be added to the DOM in case that the
     *                     embedding could not be done with an `embed` short code. If `null` is returned, no script
     *                     injection will be done.
     * @since 1.14.0
     */
    abstract protected function getFallbackScriptShortCodeTagName(): ?string;

    protected function onEmbed(): void {
        // Try to embed by using a short code.
        if ($this->embedWithShortCode()) {
            return;
        }

        // Embedding could not be done with a short code. Inject the fallback script via a short code.
        $this->injectFallbackScript();
    }

    /*
     *
     */

    /**
     * @return bool `true` if the media is embedded via a short code. Otherwise, `false`.
     * @since 1.14.0
     */
    protected function embedWithShortCode(): bool {
        $nodes = $this->getNodes();
        if (!$nodes) {
            return false;
        }

        $refNode = $nodes[0]->getNode(0);
        if (!$refNode) {
            return false;
        }

        // Find the embeddable URL. If there is none, stop.
        $embeddableUrl = $this->findEmbeddableUrl();
        if ($embeddableUrl === null) {
            return false;
        }

        // Insert the embed short code before the first node. If it could not be inserted, stop.
        $shortCodeNode = (new ElementCreator())
            ->createOne($refNode, ElementCreator::LOCATION_BEFORE, $this->onCreateEmbedShortCode($embeddableUrl));
        if (!$shortCodeNode) {
            return false;
        }

        // The node that contains the embeddable short code is inserted. We no longer need the HTML embed. So, remove
        // the nodes of the HTML embed.
        $this->removeNodes();

        return true;
    }

    /**
     * If there is a fallback script short code (see {@link getFallbackScriptShortCodeTagName()}), injects it into the
     * DOM.
     *
     * @return bool `true` if the script short code is added. Otherwise, `false`.
     * @since 1.14.0
     */
    protected function injectFallbackScript(): bool {
        $scriptShortCodeName = $this->getFallbackScriptShortCodeTagName();
        if ($scriptShortCodeName === null) {
            return false;
        }

        $nodes = $this->getNodes();
        if (!$nodes) {
            return false;
        }

        $refNode = $nodes[count($nodes) - 1]->getNode(0);
        if (!$refNode) {
            return false;
        }

        // Insert the script short code after the last node.
        $shortCodeNode = (new ElementCreator())
            ->createOne($refNode, ElementCreator::LOCATION_AFTER, "\n[{$scriptShortCodeName}]");
        return $shortCodeNode !== null;
    }

    /**
     * @return string|null The embeddable URL, if it is found. Otherwise, `null`.
     * @since 1.14.0
     */
    protected function findEmbeddableUrl(): ?string {
        /** @var string|null $embeddableUrl */
        $embeddableUrl = null;
        $nodes = $this->getNodes();
        foreach($nodes as $node) {
            $url = $this->onExtractUrl($node);
            if ($url === null) {
                continue;
            }

            // If the URL does not specify a protocol, use "https"
            if (Str::startsWith($url, '//')) {
                $url = "https:{$url}";
            }

            if ($this->isEmbeddable($url)) {
                $embeddableUrl = $url;
                break;
            }
        }

        return $embeddableUrl;
    }

    /**
     * @param string $url URL that will be converted to an embed short code
     * @return bool `true` if this URL is embeddable. Otherwise, `false`.
     * @since 1.14.0
     */
    protected function isEmbeddable(string $url): bool {
        // This WordPress function is private, but we use it anyway, since the alternative is creating the object here
        // ourselves, which we do not want, as there might be certain things done in the constructor that depends on
        // other events.
        $provider = _wp_oembed_get_object()
            // Do not discover the link tags in the source page, since it requires making a request and parsing the
            // response, which increases the method's execution time. If the existing provider regexes do not match the
            // URL, no need to discover it, since we cannot trust the provider anyway.
            ->get_provider($url, ['discover' => false]);
        return is_string($provider);
    }

    /*
     *
     */

    /**
     * @param string $url The embeddable URL from the platform
     * @return string The embed short code that will be added to the DOM
     * @since 1.14.0
     */
    protected function onCreateEmbedShortCode(string $url): string {
        return "[embed]{$url}[/embed]";
    }

}