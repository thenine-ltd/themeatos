<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 22/11/2020
 * Time: 20:13
 *
 * @since 1.11.0
 */

namespace WPCCrawler\Objects\Html;


use DOMNode;

/**
 * Unwraps the children of a container element to the container's parent and removes the container. For example, if the
 * tag element in "<parent><tag>some <b>content</b></tag></parent>" is unwrapped, it becomes
 * "<parent>some <b>content</b></parent>". PHPStorm renders the HTML in the example, making it incomprehensible. See the
 * not-rendered PHPDoc to understand the example.
 *
 * @since 1.11.0
 */
class ElementUnwrapper {

    /**
     * @param DOMNode|null $container The container element whose contents should be unwrapped
     * @since 1.11.0
     */
    public function unwrap(?DOMNode $container): void {
        if (!$container) return;

        // Get the parent node of the container. We will insert the container's child to the container's parent. If the
        // parent does not exist, we cannot do this.
        /** @var DOMNode|null $parentNode */
        $parentNode = $container->parentNode;
        if (!$parentNode) return;

        // Collect the child nodes in another array first. We cannot directly iterate over the container's child nodes,
        // because we will move the child nodes during iteration.
        /** @var DOMNode[] $childNodes */
        $childNodes = [];
        foreach($container->childNodes as $childNode) {
            /** @var DOMNode $childNode */
            $childNodes[] = $childNode;
        }

        // Move the children of the container into their grandparent
        foreach($childNodes as $childNode) {
            // Move node the parent of the container, just before the container.
            $parentNode->insertBefore($childNode, $container);
        }

        // Remove the container
        $parentNode->removeChild($container);
    }

}