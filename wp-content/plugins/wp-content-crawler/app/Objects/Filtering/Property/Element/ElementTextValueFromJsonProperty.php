<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 10/07/2023
 * Time: 08:28
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Property\Element;

use WPCCrawler\Objects\Enums\ValueType;
use WPCCrawler\Objects\Filtering\Enums\PropertyKey;
use WPCCrawler\Objects\Filtering\Property\Base\AbstractElementAttrValueFromJsonProperty;
use WPCCrawler\Objects\Filtering\Property\Objects\CalculationResult;
use WPCCrawler\Utils;

class ElementTextValueFromJsonProperty extends AbstractElementAttrValueFromJsonProperty {

    public function getKey(): string {
        return PropertyKey::ELEMENT_TEXT_VALUE_FROM_JSON;
    }

    public function getName(): string {
        return _wpcc("JSON attribute's text value");
    }

    public function getDescription(): ?string {
        return _wpcc('Extracts a text value from JSON data retrieved from an attribute (or text) of an element');
    }

    public function getOutputDataTypes(): array {
        return [ValueType::T_STRING];
    }

    protected function onCreateCalculationResult($key, $value): CalculationResult {
        return new CalculationResult($key, Utils::convertScalarToString($value));
    }
}