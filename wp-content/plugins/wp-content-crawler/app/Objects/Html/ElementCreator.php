<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 13/03/2023
 * Time: 12:30
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Html;

use DOMDocument;
use DOMNode;
use WPCCrawler\Objects\Crawling\Bot\DummyBot;

/**
 * Used to create HTML elements and insert them into a {@link DOMDocument}
 */
class ElementCreator {

    const LOCATION_AFTER         = 'after';
    const LOCATION_BEFORE        = 'before';
    const LOCATION_INSIDE_TOP    = 'inside_top';
    const LOCATION_INSIDE_BOTTOM = 'inside_bottom';

    /**
     * Creates elements and inserts them into the document relative to an element
     *
     * @param DOMNode     $reference The reference element
     * @param string      $location  One of the constants defined in this class whose name starts with `LOCATION_`, such
     *                               as {@link ElementCreator::LOCATION_AFTER}. The location is relative to the
     *                               reference element.
     * @param string|null $htmlCode  The HTML code of the element(s). If this is `null`, no element will be created.
     * @return bool `true` if the elements are created and inserted to the specified location. Otherwise, `false`.
     * @since 1.13.0
     */
    public function create(DOMNode $reference, string $location, ?string $htmlCode): bool {
        if ($htmlCode === null) {
            return false;
        }

        /** @var DOMDocument|null $document */
        $document = $reference->ownerDocument;
        if (!$document) {
            return false;
        }

        // Create the new elements. If there is no element created, stop.
        $newNodes = $this->createNewElements($document, $htmlCode);
        if (!$newNodes) {
            return false;
        }

        // Move the new elements to the specified location
        return $this->moveNewElements($reference, $location, $newNodes);
    }

    /**
     * Creates one HTML element.
     *
     * @param DOMNode     $reference The reference element
     * @param string      $location  One of the constants defined in this class whose name starts with `LOCATION_`,
     *                               such
     *                               as {@link ElementCreator::LOCATION_AFTER}. The location is relative to the
     *                               reference element.
     * @param string|null $htmlCode  The HTML code of one single element. If this is `null`, or it contains more than
     *                               one element, no element will be created.
     * @return DOMNode|null {@link DOMNode} of the created element, if it could be created. Otherwise, `null`.
     * @since 1.14.0
     */
    public function createOne(DOMNode $reference, string $location, ?string $htmlCode): ?DOMNode {
        if ($htmlCode === null) {
            return null;
        }

        /** @var DOMDocument|null $document */
        $document = $reference->ownerDocument;
        if (!$document) {
            return null;
        }

        // Create the new elements. If there is no element created, stop.
        $newNodes = $this->createNewElements($document, $htmlCode, true);
        if (!$newNodes || count($newNodes) !== 1) {
            return null;
        }

        $newNode = array_values($newNodes)[0];
        $success = $this->moveNewElements($reference, $location, [$newNode]);
        return $success
            ? $newNode
            : null;
    }

    /**
     * Imports children of an element from another document and inserts them into the document relative to an element
     *
     * @param DOMNode      $reference          The reference element
     * @param string       $location           One of the constants defined in this class whose name starts with
     *                                         `LOCATION_`, such as {@link ElementCreator::LOCATION_AFTER}. The
     *                                         location is relative to the reference element.
     * @param DOMNode|null $remoteElement      An element from another document. The children of this element will be
     *                                         added to the specified location.
     * @param bool         $importOnlyChildren `true` if only the children of the given remote element will be
     *                                         imported. `false` if the remote element itself will be imported.
     *                                         Defaults to `true`.
     * @return bool `true` if the elements are inserted to the specified location. Otherwise, `false`.
     * @since 1.14.0
     */
    public function createFromRemoteElement(DOMNode $reference, string $location, ?DOMNode $remoteElement,
                                            bool $importOnlyChildren = true): bool {
        if ($remoteElement === null) {
            return false;
        }

        /** @var DOMDocument|null $document */
        $document = $reference->ownerDocument;
        if (!$document) {
            return false;
        }

        // Create the new elements.
        if ($importOnlyChildren) {
            $newNodes = $this->importChildren($document, $remoteElement);

        } else {
            $newNode = $document->importNode($remoteElement, true);
            $newNodes = $newNode // @phpstan-ignore-line
                ? [$newNode]
                : null;
        }

        // If there is no element created, stop.
        if (!$newNodes) {
            return false;
        }

        // Move the new elements to the specified location
        return $this->moveNewElements($reference, $location, $newNodes);
    }

    /*
     *
     */

    /**
     * @param DOMDocument $document The document
     * @param bool        $single   See {@link importChildren()}
     * @return DOMNode[]|null The new HTML elements created from the given HTML code. If the elements could not be
     *                        created, `null` is returned.
     * @since 1.13.0
     */
    protected function createNewElements(DOMDocument $document, string $htmlCode, bool $single = false): ?array {
        // The HTML code might contain multiple elements. Create all the elements.
        $bot = new DummyBot([]);
        $container = $bot->createDummyCrawler($htmlCode)
            ->filter('body > div')
            ->first()
            ->getNode(0);

        return $this->importChildren($document, $container, $single);
    }

    /**
     * Imports nodes from an element of another document into a specific document
     *
     * @param DOMDocument  $document  The document
     * @param DOMNode|null $container The element from another document, whose children will be imported into the given
     *                                document
     * @param bool         $single    `true` if only the first child must be imported. Otherwise, `false`. Defaults to
     *                                `false`.
     * @return DOMNode[]|null The new nodes imported from the given container into the given document. If there are no
     *                        imported nodes, `null` is returned.
     * @since 1.14.0
     */
    protected function importChildren(DOMDocument $document, ?DOMNode $container, bool $single = false): ?array {
        if (!$container || !$container->hasChildNodes()) {
            return null;
        }

        /** @var DOMNode[] $childNodes */
        $childNodes = [];
        foreach($container->childNodes as $childNode) {
            if (!($childNode instanceof DOMNode)) continue;

            // Import the child into the document
            $newChildNode = $document->importNode($childNode, true);
            if (!($newChildNode instanceof DOMNode)) continue;

            $childNodes[] = $newChildNode;

            if ($single) {
                break;
            }
        }

        return $childNodes;
    }

    /**
     * @param DOMNode   $reference The reference element
     * @param DOMNode[] $nodes     The new HTML elements that are already imported to the document of the reference
     *                             element
     * @param string    $location  One of the constants defined in this class whose name starts with `LOCATION_`, such
     *                             as {@link ElementCreator::LOCATION_AFTER}
     * @return bool `true` if the elements are moved successfully. Otherwise, `false`.
     * @since 1.13.0
     */
    public function moveNewElements(DOMNode $reference, string $location, array $nodes): bool {
        $parent = $reference->parentNode;
        if (!$parent) {
            return false;
        }

        if ($location === self::LOCATION_AFTER) {
            $this->insertAfter($parent, $reference, $nodes);

        } else if ($location === self::LOCATION_BEFORE) {
            $this->insertBefore($parent, $reference, $nodes);

        } else if ($location === self::LOCATION_INSIDE_BOTTOM) {
            $this->insertInsideBottom($reference, $nodes);

        } else if ($location === self::LOCATION_INSIDE_TOP) {
            $this->insertInsideTop($reference, $nodes);
        }

        return true;
    }

    /*
     * ELEMENT MOVERS
     */

    /**
     * @param DOMNode   $parent    The parent of the reference element
     * @param DOMNode   $reference The reference element
     * @param DOMNode[] $children  The child nodes that will be inserted after the reference element, as siblings
     * @since 1.13.0
     */
    protected function insertAfter(DOMNode $parent, DOMNode $reference, array $children): void {
        /** @var DOMNode|null $nextSibling */
        $nextSibling = $reference->nextSibling;

        foreach($children as $child) {
            if ($nextSibling) {
                $parent->insertBefore($child, $nextSibling);
            } else {
                $parent->insertBefore($child);
            }
        }
    }

    /**
     * @param DOMNode   $parent    The parent of the reference element
     * @param DOMNode   $reference The reference element
     * @param DOMNode[] $children  The child nodes that will be inserted before the reference element, as siblings
     * @since 1.13.0
     */
    protected function insertBefore(DOMNode $parent, DOMNode $reference, array $children): void {
        foreach($children as $child) {
            $parent->insertBefore($child, $reference);
        }
    }

    /**
     * @param DOMNode   $reference The reference element
     * @param DOMNode[] $children  The child nodes that will be appended to the children of the reference element
     * @since 1.13.0
     */
    protected function insertInsideBottom(DOMNode $reference, array $children): void {
        foreach($children as $child) {
            $reference->insertBefore($child);
        }
    }

    /**
     * @param DOMNode   $reference The reference element
     * @param DOMNode[] $children  The child nodes that will be prepended to the children of the reference element
     * @since 1.13.0
     */
    protected function insertInsideTop(DOMNode $reference, array $children): void {
        /** @var DOMNode|null $firstChild */
        $firstChild = $reference->firstChild;

        foreach($children as $child) {
            if ($firstChild) {
                $reference->insertBefore($child, $firstChild);

            } else {
                $reference->insertBefore($child);
            }
        }
    }

    /*
     * STATIC METHODS
     */

    /**
     * @return array<string, string> Locations options that contain the location of the created element relative to the
     *                               specified element
     * @since 1.13.0
     */
    public static function getLocationOptionsForSelect(): array {
        return [
            self::LOCATION_AFTER         => _wpcc('After'),
            self::LOCATION_BEFORE        => _wpcc('Before'),
            self::LOCATION_INSIDE_BOTTOM => _wpcc('Inside Bottom'),
            self::LOCATION_INSIDE_TOP    => _wpcc('Inside Top'),
        ];
    }

}