<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 24/02/2023
 * Time: 10:19
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\ActionCommands\PostPage;

use WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base\AbstractTransformActionCommand;
use WPCCrawler\Objects\Filtering\Commands\Enums\CommandShortCodeName;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinition;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionList;
use WPCCrawler\Objects\Filtering\Enums\CommandKey;
use WPCCrawler\Objects\Filtering\Enums\InputName;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;
use WPCCrawler\Objects\Views\ShortCodeButtonsWithLabelForTemplateCmd;
use WPCCrawler\Objects\Views\TextAreaWithLabel;

class FieldTemplate extends AbstractTransformActionCommand {

    /** @var string|null */
    private $template = null;

    public function getKey(): string {
        return CommandKey::FIELD_TEMPLATE;
    }

    public function getName(): string {
        return _wpcc('Field template');
    }

    protected function getFieldsInputDescription(): string {
        return _wpcc('Select the fields that will be changed by using the template.');
    }

    protected function createViews(): ViewDefinitionList {
        return parent::createViews()
            // Add the short code buttons
            ->add((new ViewDefinition(ShortCodeButtonsWithLabelForTemplateCmd::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Short codes'))
                ->setVariable(ViewVariableName::INFO,  _wpcc("Short codes that can be used in the template. You 
                    can hover over the short codes to see what they do. You can click to the short code buttons to copy 
                    the short codes. Then, you can paste the short codes into the template to include them. They will be 
                    replaced with their actual values."))
            )
            // Add the template
            ->add((new ViewDefinition(TextareaWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Template'))
                ->setVariable(ViewVariableName::INFO,  _wpcc("Enter your template. What you define here will be 
                    used as the new value of the specified fields. The original value will be replaced with this
                    template."))
                ->setVariable(ViewVariableName::NAME, InputName::TEMPLATE)
                ->setVariable(ViewVariableName::ROWS, 4)
                ->setVariable(ViewVariableName::PLACEHOLDER, _wpcc('New value of each item of each selected field...'))
            );
    }

    protected function onExecute($key, $subjectValue) {
        $logger = $this->getLogger();
        if ($this->getTemplateOption() === '') {
            if ($logger) {
                $logger->addMessage(_wpcc('The template could not be applied, because there is no template.'));
            }
            return;
        }

        parent::onExecute($key, $subjectValue);
    }

    /**
     * @param array $values The values that should be transformed
     * @return array The transformed values
     * @since 1.13.0
     */
    protected function onTransformValues(array $values): array {
        $logger = $this->getLogger();
        return array_map(function($value) use ($logger) {
            // If the value is not scalar, return it as-is. This will probably never be the case.
            if (!is_scalar($value)) {
                return $value;
            }

            if ($logger) {
                $logger->addSubjectItem((string) $value);
            }

            $applier = $this->createShortCodeApplier([CommandShortCodeName::ITEM => $value]);
            $result = $applier->apply($this->getTemplateOption());

            if ($logger) {
                $logger->addModifiedSubjectItem($result);
            }

            return $result;
        }, $values);
    }

    /*
     * OPTION GETTERS
     */

    /**
     * @return string The template option's value. If the template option is not defined, an empty string is returned.
     * @since 1.13.0
     */
    protected function getTemplateOption(): string {
        if ($this->template === null) {
            $template = $this->getStringOption(InputName::TEMPLATE);
            $this->template = $template === null
                ? ''
                : $template;
        }

        return $this->template;
    }

}