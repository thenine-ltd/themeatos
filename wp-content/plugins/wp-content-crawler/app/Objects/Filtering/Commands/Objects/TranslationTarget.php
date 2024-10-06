<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 03/10/2023
 * Time: 18:05
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\Objects;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;

class TranslationTarget {

    /** @var Crawler The target element */
    private $element;

    /** @var string Name of the target attribute of the target element */
    private $attributeName;

    /** @var string Original value of the target attribute of the target element */
    private $originalAttributeValue;

    /**
     * @param Crawler $element                See {@link element}
     * @param string  $attributeName          See {@link attributeName}
     * @param string  $originalAttributeValue See {@link originalAttributeValue}
     * @since 1.14.0
     */
    public function __construct(Crawler $element, string $attributeName, string $originalAttributeValue) {
        $this->element = $element;
        $this->attributeName = $attributeName;
        $this->originalAttributeValue = $originalAttributeValue;
    }

    /**
     * @return Crawler See {@link element}
     * @since 1.14.0
     */
    public function getElement(): Crawler {
        return $this->element;
    }

    /**
     * @return string See {@link attributeName}
     * @since 1.14.0
     */
    public function getAttributeName(): string {
        return $this->attributeName;
    }

    /**
     * @return string See {@link originalAttributeValue}
     * @since 1.14.0
     */
    public function getOriginalAttributeValue(): string {
        return $this->originalAttributeValue;
    }

    /**
     * Updates the target element attribute's value
     *
     * @param string $newAttrValue New value of the target attribute. This is the translated attribute value.
     * @since 1.14.0
     */
    public function setNewAttrValue(string $newAttrValue): void {
        $node = $this->getElement()->getNode(0);
        if (!($node instanceof DOMElement)) {
            return;
        }

        $node->setAttribute($this->getAttributeName(), $newAttrValue);
    }

}