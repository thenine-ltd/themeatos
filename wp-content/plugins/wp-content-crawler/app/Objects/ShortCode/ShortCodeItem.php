<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 17/02/2023
 * Time: 21:46
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\ShortCode;

use Illuminate\Support\Str;
use WPCCrawler\Objects\Informing\Informer;

class ShortCodeItem {

    /** @var string The short code name without brackets */
    private $name;

    /** @var string The value of the short code. This can contain other short codes. */
    private $value;

    /** @var string The opening bracket used to define a short code */
    private $openingBracket;

    /** @var string The closing bracket used to define a short code */
    private $closingBracket;

    /** @var ShortCodeItem[] The short code items this short code item includes in its value */
    private $dependencies = [];

    /**
     * @param string $name           See {@link $name}
     * @param string $value          See {@link $value}
     * @param string $openingBracket See {@link $openingBracket}
     * @param string $closingBracket See {@link $closingBracket}
     * @since 1.13.0
     */
    public function __construct(string $name, string $value, string $openingBracket = '[', string $closingBracket = ']') {
        $this->name = $name;
        $this->value = $value;

        $this->openingBracket = $openingBracket;
        $this->closingBracket = $closingBracket;
    }

    /**
     * @return string See {@link $value}
     * @since 1.13.0
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string The short code name with brackets, such as '[name]'
     * @since 1.13.0
     */
    public function getNameWithBrackets(): string {
        return $this->getOpeningBracket() . $this->getName() . $this->getClosingBracket();
    }

    /**
     * @return string See {@link $value}
     * @since 1.13.0
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * @param string $value New value of this short code item
     * @since 1.13.0
     */
    protected function setValue(string $value): void {
        $this->value = $value;
    }

    /**
     * @return string See {@link $openingBracket}
     * @since 1.13.0
     */
    public function getOpeningBracket(): string {
        return $this->openingBracket;
    }

    /**
     * @return string See {@link $closingBracket}
     * @since 1.13.0
     */
    public function getClosingBracket(): string {
        return $this->closingBracket;
    }

    /**
     * @return ShortCodeItem[] See {@link $dependencies}
     * @since 1.13.0
     */
    public function getDependencies(): array {
        return $this->dependencies;
    }

    /**
     * Add a dependency. The dependency will only be added if it was not added previously.
     *
     * @param ShortCodeItem $item A short code that is included in this short code's value
     * @return self
     * @since 1.13.0
     */
    public function addDependency(ShortCodeItem $item): self {
        if (!in_array($item, $this->dependencies)) {
            $this->dependencies[] = $item;
        }

        return $this;
    }

    /**
     * Injects dependencies to the value of this short code item
     *
     * @param ShortCodeItem[] $parents Parents of this tree item. No need to set this. This value is set internally.
     * @since 1.13.0
     */
    public function injectDependencies(array $parents = []): self {
        $parents[] = $this;
        foreach($this->getDependencies() as $child) {
            $shortCode = $child->getNameWithBrackets();

            // If this item is among the parents, it means there is a circular dependency. To prevent this, set the new
            // value as an empty string.
            if (in_array($child, $parents)) {
                $newValue = Str::replace($shortCode, '', $this->getValue());

                // Notify the user
                Informer::addInfo(sprintf(
                    _wpcc(
                        '%1$s short code causes a circular dependency. The short code is replaced with an empty 
                        text to prevent an infinite loop. To avoid this message, please make sure the short code does
                        not reference itself. Short code dependency path: %2$s'
                    ),
                    sprintf('%1$s', $child->getNameWithBrackets()),
                    implode(' -> ', array_map(function(ShortCodeItem $item) {
                        return $item->getNameWithBrackets();
                    }, array_merge($parents, [$child])))
                ))
                    ->addAsLog();

            } else {
                // There is no circular dependency. Replace the dependency with its actual value.
                $newValue = Str::replace(
                    $shortCode,
                    $child->injectDependencies($parents)->getValue(),
                    $this->getValue()
                );
            }

            // Set the new value
            $this->setValue($newValue);
        }

        return $this;
    }

}