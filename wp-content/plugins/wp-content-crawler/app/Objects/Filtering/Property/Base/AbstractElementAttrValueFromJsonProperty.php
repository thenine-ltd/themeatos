<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 10/07/2023
 * Time: 07:54
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Property\Base;

use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Enums\ValueType;
use WPCCrawler\Objects\Filtering\Commands\Base\AbstractBaseCommand;
use WPCCrawler\Objects\Filtering\Commands\CommandUtils;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinition;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionList;
use WPCCrawler\Objects\Filtering\Enums\InputName;
use WPCCrawler\Objects\Filtering\Property\Objects\CalculationResult;
use WPCCrawler\Objects\Json\JsonToHtmlConverter;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;
use WPCCrawler\Objects\Views\InputWithLabel;

abstract class AbstractElementAttrValueFromJsonProperty extends AbstractProperty {

    const DEFAULT_ATTR = 'text';

    public function getInputDataTypes(): array {
        return [ValueType::T_ELEMENT];
    }

    protected function createViews(): ?ViewDefinitionList {
        $pathDesc = (new CommandUtils())
            ->createDotPathDescription();

        return (new ViewDefinitionList())
            ->add((new ViewDefinition(InputWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Attribute name'))
                ->setVariable(ViewVariableName::INFO,  _wpcc('Name of an attribute of the element(s).')
                    . ' ' . _wpcc('You can write "text" to retrieve the texts.')
                )
                ->setVariable(ViewVariableName::NAME,  InputName::ELEMENT_ATTR)
                ->setVariable(ViewVariableName::TYPE,  'text')
                ->setVariable(ViewVariableName::PLACEHOLDER,  sprintf(
                    _wpcc('Name of attribute containing JSON... Default: %1$s'),
                    self::DEFAULT_ATTR)
                )
            )

            ->add((new ViewDefinition(InputWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('JSON path'))
                ->setVariable(ViewVariableName::INFO,  _wpcc('Enter the path to the JSON field that stores the desired value.') . $pathDesc)
                ->setVariable(ViewVariableName::NAME,  InputName::JSON_PATH)
                ->setVariable(ViewVariableName::TYPE,  'text')
                ->setVariable(ViewVariableName::PLACEHOLDER,  _wpcc('path.to.field...'))
            );
    }

    protected function onCalculate($key, $source, AbstractBaseCommand $cmd): ?CalculationResult {
        if (!($source instanceof Crawler)) return null;

        $utils = new CommandUtils();

        $targetJsonPath = $utils->getJsonPathOption($cmd);
        if ($targetJsonPath === null) return null;

        $attrName = $utils->getElementAttributeOption($cmd) ?? self::DEFAULT_ATTR;
        $jsonStr = $utils->getAttributeValue($source, $attrName);
        if ($jsonStr === null) return null;

        $arr = JsonToHtmlConverter::decodeJson($jsonStr);
        if ($arr === null) return null;

        $value = $this->extractValue($arr, $targetJsonPath);
        if ($value === null) return null;

        return $this->onCreateCalculationResult($key, $value);
    }

    /**
     * Creates the calculation result via the key and the value extracted from JSON
     *
     * @param mixed                 $key   See {@link onCalculate()}
     * @param bool|float|int|string $value The value extracted from found JSON via a JSON path specified via its option
     * @return CalculationResult The calculation result of the property
     * @since 1.14.0
     */
    abstract protected function onCreateCalculationResult($key, $value): CalculationResult;

    /*
     *
     */

    /**
     * Extracts a value from an array via its dot-notation path. If the found value is an array, its first item is
     * returned, as long as it is a scalar value.
     *
     * @param array  $data    The array from which a value will be extracted
     * @param string $dotPath Dot-notation path to the target value
     * @return bool|float|int|string|null If the value is found, it is returned. Otherwise, `null` is returned.
     * @since 1.14.0
     */
    protected function extractValue(array $data, string $dotPath) {
        $value = data_get($data, $dotPath);

        // If the value is scalar, directly return it.
        if (is_scalar($value)) {
            return $value;
        }

        // If the value is not an array, or it is an empty array, return null.
        if (!is_array($value) || !$value) {
            return null;
        }

        // The value is a non-empty array. Get the first item from it. If the first item is scalar, return it.
        // Otherwise, return null.
        $firstItem = $value[array_keys($value)[0]];
        return is_scalar($firstItem)
            ? $firstItem
            : null;
    }

}