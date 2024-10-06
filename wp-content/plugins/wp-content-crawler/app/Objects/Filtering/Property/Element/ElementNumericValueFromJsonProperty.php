<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 10/07/2023
 * Time: 08:40
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Property\Element;

use WPCCrawler\Objects\Enums\ValueType;
use WPCCrawler\Objects\Filtering\Enums\PropertyKey;
use WPCCrawler\Objects\Filtering\Property\Base\AbstractElementAttrValueFromJsonProperty;
use WPCCrawler\Objects\Filtering\Property\Objects\CalculationResult;

class ElementNumericValueFromJsonProperty extends AbstractElementAttrValueFromJsonProperty {

    public function getKey(): string {
        return PropertyKey::ELEMENT_NUMERIC_VALUE_FROM_JSON;
    }

    public function getName(): string {
        return _wpcc("JSON attribute's numeric value");
    }

    public function getDescription(): ?string {
        return _wpcc('Extracts a numeric value from JSON data retrieved from an attribute (or text) of an element');
    }

    public function getOutputDataTypes(): array {
        return [ValueType::T_NUMERIC];
    }

    protected function onCreateCalculationResult($key, $value): CalculationResult {
        return new CalculationResult($key, is_numeric($value)
            ? (float) $value
            : null
        );
    }
}