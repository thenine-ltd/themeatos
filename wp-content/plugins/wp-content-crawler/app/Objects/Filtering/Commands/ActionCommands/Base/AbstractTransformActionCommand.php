<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 27/09/2023
 * Time: 18:17
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base;

use WPCCrawler\Exceptions\MethodNotExistException;
use WPCCrawler\Objects\Enums\ValueType;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinition;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionList;
use WPCCrawler\Objects\Filtering\Enums\InputName;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Objects\Transformation\Base\AbstractTransformationService;
use WPCCrawler\Objects\Transformation\Interfaces\Transformable;
use WPCCrawler\Objects\Value\ValueExtractor;
use WPCCrawler\Objects\Value\ValueSetter;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;
use WPCCrawler\Objects\Views\Select\SelectPostTransformableFieldsWithLabel;

abstract class AbstractTransformActionCommand extends AbstractActionCommand {

    /** @var string[]|null */
    private $fields = null;

    public function getInputDataTypes(): array {
        return [ValueType::T_POST_PAGE];
    }

    protected function isOutputTypeSameAsInputType(): bool {
        return true;
    }

    public function doesNeedSubjectValue(): bool {
        return false;
    }

    protected function isTestable(): bool {
        return false;
    }

    protected function createViews(): ViewDefinitionList {
        return (new ViewDefinitionList())
            // Add the transformable fields
            ->add((new ViewDefinition(SelectPostTransformableFieldsWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Fields'))
                ->setVariable(ViewVariableName::INFO,  $this->getFieldsInputDescription())
                ->setVariable(ViewVariableName::NAME,  InputName::TRANSFORMABLE_FIELDS)
            );
    }

    abstract protected function getFieldsInputDescription(): string;

    /**
     * @param array $values The values that should be transformed
     * @return array The transformed values
     * @since 1.14.0
     */
    abstract protected function onTransformValues(array $values): array;

    /**
     * @return bool `true` if all the fields must be transformed when no field is specified in the options. Otherwise,
     *              `false`.
     * @since 1.14.0
     */
    protected function shouldUseAllFieldsByDefault(): bool {
        return false;
    }

    protected function onExecute($key, $subjectValue) {
        $logger = $this->getLogger();

        if (!$this->getTransformableFields() && !$this->shouldUseAllFieldsByDefault()) {
            if ($logger) {
                $logger->addMessage(_wpcc('The command could not be executed, because there are not any selected fields.'));
            }
            return;
        }

        $provider = $this->getProvider();
        if (!$provider) {
            if ($logger) {
                $logger->addMessage(_wpcc('The command could not be executed, because there is no dependency provider.'));
            }
            return;
        }

        // Transform the post data
        foreach($provider->getDataSourceMap() as $identifier => $data) {
            if (!($data instanceof Transformable)) {
                continue;
            }

            $this->transform(
                $data,
                $this->getTransformableFieldsForIdentifier($identifier)
            );
        }

    }

    /**
     * Transform specific fields of a transformable
     *
     * @param Transformable $data   The data to be transformed
     * @param string[]      $fields The fields of the data, without an identifier
     * @since 1.13.0
     */
    protected function transform(Transformable $data, array $fields): void {
        if (!$fields && $this->shouldUseAllFieldsByDefault()) {
            $fields = array_keys($data->getTransformableFields()->toAssociativeArray());
        }

        // If there are no fields, stop.
        if (!$fields) {
            return;
        }

        $map = [];
        foreach ($fields as $field) {
            $map[$field] = '';
        }

        // Get the texts to transform
        $values = null;
        try {
            $values = (new ValueExtractor())->fillAndFlatten($data, $map);
        } catch (MethodNotExistException $e) {
            Informer::addError($e->getMessage())->setException($e)->addAsLog();
        }

        // If there is nothing to transform, stop.
        if (!$values) {
            return;
        }

        // Transform the values
        $transformedValues = $this->onTransformValues($values);

        // Put the transformed values to their original places
        $setter = new ValueSetter();
        try {
            $setter->set($data, $transformedValues);
        } catch (MethodNotExistException $e) {
            Informer::addError($e->getMessage())->setException($e)->addAsLog();
        }
    }

    /**
     * @param string $identifier Prefix that is used to specify the type of transformable that the field belongs to.
     * @return string[] The fields that has the given identifier, with the identifier removed.
     * @since 1.14.0
     */
    protected function getTransformableFieldsForIdentifier(string $identifier): array {
        return AbstractTransformationService::getTransformableFieldsFromSelect(
            $this->getTransformableFields(),
            $identifier
        );
    }

    /*
     * OPTION GETTERS
     */

    /**
     * @return string[] The transformable fields specified in the options
     * @since 1.14.0
     */
    protected function getTransformableFields(): array {
        if ($this->fields === null) {
            $fields = $this->getArrayOption(InputName::TRANSFORMABLE_FIELDS) ?? [];
            $this->fields = array_filter($fields, function($field) {
                return is_string($field);
            });
        }

        return $this->fields;
    }
}