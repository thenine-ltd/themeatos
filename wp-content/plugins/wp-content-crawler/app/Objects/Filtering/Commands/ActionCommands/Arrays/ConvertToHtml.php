<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 09/07/2023
 * Time: 11:09
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\ActionCommands\Arrays;

use WPCCrawler\Objects\Crawling\Bot\AbstractBot;
use WPCCrawler\Objects\Enums\ShortCodeName;
use WPCCrawler\Objects\Enums\ValueType;
use WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base\AbstractActionCommand;
use WPCCrawler\Objects\Filtering\Commands\CommandUtils;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinition;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionList;
use WPCCrawler\Objects\Filtering\Enums\CommandKey;
use WPCCrawler\Objects\Filtering\Enums\InputName;
use WPCCrawler\Objects\Filtering\Interfaces\NeedsBot;
use WPCCrawler\Objects\Json\JsonToHtmlConverter;
use WPCCrawler\Objects\Json\Objects\ConverterOptions;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;
use WPCCrawler\Objects\Views\TextAreaWithLabel;

class ConvertToHtml extends AbstractActionCommand implements NeedsBot {

    /** @var AbstractBot|null */
    private $bot;

    public function getKey(): string {
        return CommandKey::ARRAY_CONVERT_TO_HTML;
    }

    public function getName(): string {
        return _wpcc('Convert to HTML');
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
        return (new ViewDefinitionList())
            ->add((new ViewDefinition(TextAreaWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('HTML template'))
                ->setVariable(ViewVariableName::INFO,  $this->createTemplateSettingInfo())
                ->setVariable(ViewVariableName::NAME,  InputName::TEMPLATE)
                ->setVariable(ViewVariableName::ROWS,  10)
            )
        ;
    }

    protected function onExecute($key, $subjectValue) {
        if (!is_array($subjectValue)) return;

        $bot = $this->getBot();
        if (!$bot) return;

        $crawler = $bot->getCrawler();
        if (!$crawler) return;

        $template = $this->getTemplate();
        if ($template === null) return;

        JsonToHtmlConverter::fromArrayIntoCrawler(
            $crawler,
            $subjectValue,
            ConverterOptions::newInstance()
                ->setTemplate($template)
        );
    }

    /*
     *
     */

    /**
     * @return string|null The HTML template to be used to convert data to HTML code
     * @since 1.14.0
     */
    protected function getTemplate(): ?string {
        return $this->getStringOption(InputName::TEMPLATE);
    }

    /*
     *
     */

    /**
     * @return string The information that will be shown for the template setting
     * @since 1.14.0
     */
    protected function createTemplateSettingInfo(): string {
        $transVarDef = _wpcc('Field value retrieval');
        $transLoopDef = _wpcc('Loop definition');
        $transNestedLoopDef = _wpcc('Nested loops');

        $itemScName = ShortCodeName::WCC_ITEM;
        $templateDesc = <<<HTML
<div class="sample-container">
    <div class="sample-title">{$transVarDef}</div>
    <code>[{$itemScName} path.to.field]</code>
</div>
<div class="sample-container">
    <div class="sample-title">{$transLoopDef}</div>
    <code>[{$itemScName} path.to.array.*]
    &lt;span>[{$itemScName}]&lt;/span>
    &lt;span>[{$itemScName} path.to.field.in.item]&lt;/span>
[/{$itemScName}]</code>
</div>
<div class="sample-container">
    <div class="sample-title">{$transNestedLoopDef}</div>
    <code>[{$itemScName} path.to.array.*]
    [{$itemScName} path.to.field.in.item.*]
        &lt;span>[wcc-item]&lt;/span>
    [/{$itemScName}]
[/{$itemScName}]</code>
</div>
HTML;

        $pathDesc = (new CommandUtils())
            ->createDotPathDescription();

        return _wpcc('Define the template to be used to convert data to HTML code.')
            . ' ' . _wpcc('You can use HTML code anywhere in the template.')
            . ' ' . _wpcc('You can use variables and loops as shown below.')
            . ' ' . _wpcc('In the loops, the item short code retrieves the values from the current item of'
                . ' the loop. The JSON paths can contain multiple wildcards (* characters).')
            . $templateDesc . $pathDesc;
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