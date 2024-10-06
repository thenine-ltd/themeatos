<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 09/07/2023
 * Time: 08:59
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Property\Element;

use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Enums\ValueType;
use WPCCrawler\Objects\Filtering\Commands\Base\AbstractBaseCommand;
use WPCCrawler\Objects\Filtering\Enums\PropertyKey;
use WPCCrawler\Objects\Filtering\Property\Base\AbstractActionProperty;
use WPCCrawler\Objects\Filtering\Property\Objects\CalculationResult;
use WPCCrawler\Objects\Json\JsonToHtmlConverter;

class ElementTextAsJsonProperty extends AbstractActionProperty {

    public function getKey(): string {
        return PropertyKey::ELEMENT_TEXT_AS_JSON;
    }

    public function getName(): string {
        return _wpcc('Text as JSON');
    }

    public function getInputDataTypes(): array {
        return [ValueType::T_ELEMENT];
    }

    public function getOutputDataTypes(): array {
        return [ValueType::T_ARRAY];
    }

    public function isConditionProperty(): bool {
        return false;
    }

    protected function onCalculate($key, $source, AbstractBaseCommand $cmd): ?CalculationResult {
        if (!($source instanceof Crawler)) return null;

        $arr = JsonToHtmlConverter::decodeJson($source->text());
        if ($arr === null) {
            return null;
        }

        return new CalculationResult($key, $arr);
    }

}