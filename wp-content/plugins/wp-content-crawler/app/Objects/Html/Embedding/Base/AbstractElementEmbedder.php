<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 22/09/2023
 * Time: 11:51
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Html\Embedding\Base;

use DOMAttr;
use DOMNamedNodeMap;
use DOMNode;
use WPCCrawler\Objects\Html\ElementCreator;

/**
 * Converts the first node to a short code that will output the first node's HTML in the front end
 */
abstract class AbstractElementEmbedder extends AbstractEmbedder {

    /**
     * @param DOMNode $node The node that is about to be converted to an element short code
     * @return bool `true` if the node should be converted to an element short code. Otherwise, `false`.
     * @since 1.14.0
     */
    abstract protected function isNodeConvertable(DOMNode $node): bool;

    /**
     * @param string|null $attributes All the attributes of the target node as a string
     * @return string|null The short code that will replace the target node
     * @since 1.14.0
     */
    abstract protected function onCreateEmbedShortCode(?string $attributes): ?string;

    protected function onEmbed(): void {
        $nodes = $this->getNodes();
        if (!$nodes) {
            return;
        }

        // We need to have the element's DOM node. Find it.
        $firstNode = $nodes[0] ?? null;
        if (!$firstNode) {
            return;
        }

        $firstNodeRef = $firstNode->getNode(0);
        if (!$firstNodeRef || !$this->isNodeConvertable($firstNodeRef)) {
            return;
        }

        // We have the element's DOM node. Create an element short code that will output the found element in the front
        // end.
        $shortCode = $this->createEmbedShortCode($firstNodeRef);
        if ($shortCode === null) {
            return;
        }

        // The short code is created. Now, inject the short code into the DOM.
        $shortCodeNode = (new ElementCreator())
            ->createOne($firstNodeRef, ElementCreator::LOCATION_BEFORE, $shortCode);
        if (!$shortCodeNode) {
            return;
        }

        // The node that contains the short code is inserted. We no longer need the HTML embed. So, remove the nodes of
        // the HTML embed.
        $this->removeNodes();
    }

    /**
     * @param DOMNode $node The node for which the embed short code will be created
     * @return string|null If the short code could be created, it is returned. Otherwise, `null` is returned.
     * @since 1.14.0
     */
    protected function createEmbedShortCode(DOMNode $node): ?string {
        // Extract the attributes of the node
        $attributesStr = $this->extractAttributes($node);
        return $this->onCreateEmbedShortCode($attributesStr);
    }

    /**
     * @param DOMNode $node A node whose attributes will be extracted
     * @return string|null The attributes of the nodes as a string, if there is at least one attribute. Otherwise,
     *                     `null`. A sample return value: 'src="https://..." width="100" height="200"'
     * @since 1.14.0
     */
    protected function extractAttributes(DOMNode $node): ?string {
        // Extract the attributes of the iframe node
        /** @var DOMNamedNodeMap|null $attributes */
        $attributes = $node->attributes;
        if (!$attributes) {
            return null;
        }

        $attrCount = $attributes->length;
        /** @var string[] $shortCodeAttrs */
        $shortCodeAttrs = [];
        for($i = 0; $i < $attrCount; $i++) {
            $attribute = $attributes->item($i);
            if (!($attribute instanceof DOMAttr)) {
                continue;
            }

            $shortCodeAttrs[] = "{$attribute->name}=\"{$attribute->value}\"";
        }

        return $shortCodeAttrs
            ? implode(' ', $shortCodeAttrs)
            : null;
    }

}