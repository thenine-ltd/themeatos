<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 10/07/2023
 * Time: 01:29
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\ActionCommands\Arrays;

use WPCCrawler\Objects\Arrays\ArraySanitizer;
use WPCCrawler\Objects\Crawling\Bot\AbstractBot;
use WPCCrawler\Objects\Enums\ValueType;
use WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base\AbstractActionCommand;
use WPCCrawler\Objects\Filtering\Commands\CommandUtils;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinition;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionList;
use WPCCrawler\Objects\Filtering\Enums\CommandKey;
use WPCCrawler\Objects\Filtering\Enums\InputName;
use WPCCrawler\Objects\Filtering\Interfaces\NeedsBot;
use WPCCrawler\Objects\Json\JsonToHtmlConverter;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;
use WPCCrawler\Objects\Views\MultipleInputWithLabel;

class ConvertToHtmlAuto extends AbstractActionCommand implements NeedsBot {

    /** @var AbstractBot|null */
    private $bot;

    public function getKey(): string {
        return CommandKey::ARRAY_CONVERT_TO_HTML_AUTO;
    }

    public function getName(): string {
        return _wpcc('Convert to HTML automatically');
    }

    public function getInputDataTypes(): array {
        return [ValueType::T_ARRAY];
    }

    protected function isOutputTypeSameAsInputType(): bool {
        return true;
    }

    protected function shouldReassignNewValues(): bool {
        return false;
    }

    protected function isTestable(): bool {
        return false;
    }

    protected function createViews(): ?ViewDefinitionList {
        $pathDesc = (new CommandUtils())
            ->createDotPathDescription();

        return (new ViewDefinitionList())
            // Only field paths
            ->add((new ViewDefinition(MultipleInputWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Only field paths'))
                ->setVariable(ViewVariableName::INFO,  _wpcc('Enter the paths of fields that should be the'
                    . ' only fields in the data. The unspecified paths will be considered as unnecessary and removed'
                    . ' before converting the data to HTML.') . $pathDesc)
                ->setVariable(ViewVariableName::NAME,        InputName::ONLY_PATH)
                ->setVariable(ViewVariableName::PLACEHOLDER, _wpcc('path.to.*.field'))
                ->setVariable(ViewVariableName::TYPE,        'text')
            )

            // Unnecessary field paths
            ->add((new ViewDefinition(MultipleInputWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Unnecessary field paths'))
                ->setVariable(ViewVariableName::INFO,  _wpcc('Enter the paths of fields that should be'
                    . ' removed from the data before converting the data to HTML.') . $pathDesc)
                ->setVariable(ViewVariableName::NAME,        InputName::UNNECESSARY_PATH)
                ->setVariable(ViewVariableName::PLACEHOLDER, _wpcc('path.*.to.unnecessary.field'))
                ->setVariable(ViewVariableName::TYPE,        'text')
            )
        ;
    }

    protected function onExecute($key, $subjectValue) {
        if (!is_array($subjectValue)) return;

        $bot = $this->getBot();
        if (!$bot) return;

        $crawler = $bot->getCrawler();
        if (!$crawler) return;

        JsonToHtmlConverter::fromArrayIntoCrawler(
            $crawler,
            $this->applyOnlyPaths($this->removeUnnecessaryFields($subjectValue))
        );
    }

    /*
     *
     */

    /**
     * @param array $data The data whose unnecessary fields will be removed
     * @return array The data after its unnecessary fields are removed
     * @since 1.14.0
     */
    protected function removeUnnecessaryFields(array $data): array {
        $unnecessaryPaths = $this->getUnnecessaryFieldPaths();
        foreach($unnecessaryPaths as $path) {
            $value = data_get($data, $path);

            // If the value is already null, do not try to set it again. Otherwise, a non-existing key will be created.
            if ($value === null) {
                continue;
            }

            data_set($data, $path, null);
        }

        return $data;
    }

    /**
     * Retrieves the paths specified in the "only paths" setting from the data
     *
     * @param array $data The data from which the "only paths" will be retrieved
     * @return array The data that contains only the values of the specified paths
     * @since 1.14.0
     */
    protected function applyOnlyPaths(array $data): array {
        $onlyPaths = $this->getOnlyFieldPaths();
        if (!$onlyPaths) {
            return $data;
        }

        return (new ArraySanitizer($data, $onlyPaths))
            ->sanitize();
    }

    /*
     *
     */

    /**
     * @return string[] Dot-notation paths of the unnecessary fields
     * @since 1.14.0
     */
    protected function getOnlyFieldPaths(): array {
        return $this->getStringArrayOptionValue(InputName::ONLY_PATH);
    }

    /**
     * @return string[] Dot-notation paths of the unnecessary fields
     * @since 1.14.0
     */
    protected function getUnnecessaryFieldPaths(): array {
        return $this->getStringArrayOptionValue(InputName::UNNECESSARY_PATH);
    }

    /**
     * @param string $inputName Input name of the option
     * @return string[] The option value
     * @since 1.14.0
     */
    protected function getStringArrayOptionValue(string $inputName): array {
        $value = $this->getArrayOption($inputName);
        if (!$value) return [];

        return array_filter(array_map(function($rule) {
            if (!is_string($rule)) return null;

            $trimmed = trim($rule);
            return $trimmed !== ''
                ? $trimmed
                : null;
        }, $value));
    }

    /*
     *
     */

    public function setBot(?AbstractBot $bot): void {
        $this->bot = $bot;
    }

    public function getBot(): ?AbstractBot {
        return $this->bot;
    }
}