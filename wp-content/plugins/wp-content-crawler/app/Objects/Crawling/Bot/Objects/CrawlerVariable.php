<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 13/03/2023
 * Time: 13:14
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Crawling\Bot\Objects;

class CrawlerVariable {

    /** @var string Human-friendly name of the variable */
    private $name;

    /** @var string Classes that will be added to the element that stores the variable */
    private $cssClass;

    /** @var string The value of the variable */
    private $value;

    /**
     * @param string $name     See {@link $name}
     * @param string $cssClass See {@link $cssClass}
     * @param string $value    See {@link $value}
     * @since 1.13.0
     */
    public function __construct(string $name, string $cssClass, string $value) {
        $this->name     = $name;
        $this->cssClass = $cssClass;
        $this->value    = $value;
    }

    /**
     * @return string See {@link $name}
     * @since 1.13.0
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string See {@link $cssClass}
     * @since 1.13.0
     */
    public function getCssClass(): string {
        return $this->cssClass;
    }

    /**
     * @return string See {@link $value}
     * @since 1.13.0
     */
    public function getValue(): string {
        return $this->value;
    }

}