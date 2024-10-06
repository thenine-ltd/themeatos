<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 16/10/2023
 * Time: 19:40
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Html;

use DOMNode;

class DomNodeFlattener {

    /** @var DOMNode */
    private $node;

    /**
     * @param DOMNode $node The node to be flattened
     * @since 1.14.0
     */
    public function __construct(DOMNode $node) {
        $this->node = $node;
    }

    /**
     * Flattens the node's tree iteratively (not recursively, to avoid PHP's "max function nesting level")
     *
     * @return DOMNode[] All the nodes inside the reference node. This array is ordered depth-first.
     * @since 1.14.0
     */
    public function flatten(): array {
        /** @var DOMNode[] $result */
        $result = [];

        $stack = [$this->getNode()];
        while($stack) {
            // Extract the first item from the stack as the current node
            $current = array_shift($stack);
            if (!($current instanceof DOMNode)) {
                break;
            }

            // Add the current node to the result
            $result[] = $current;

            // If the current node has children, add them to the stack. DOMNode::$childNodes is never null, according to
            // PHP's documentation. However, during tests run on PHP 7.3, due to $childNodes variable being null, a
            // fatal error occurred, although it did not occur in the same environment before (wtf?). So, to be on the
            // safe side, we make sure there are children, and DOMNode::$childNodes variable is not null.
            $children = $current->hasChildNodes() && $current->childNodes !== null
                ? iterator_to_array($current->childNodes, false)
                : [];
            $stack = array_merge($children, $stack);
        }

        return $result;
    }

    /*
     *
     */

    /**
     * @return DOMNode
     * @since 1.14.0
     */
    public function getNode(): DOMNode {
        return $this->node;
    }

}