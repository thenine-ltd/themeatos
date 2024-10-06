<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 27/09/2023
 * Time: 18:03
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\ActionCommands\PostPage;

use WPCCrawler\Objects\Crawling\Bot\AbstractBot;
use WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base\AbstractTransformActionCommand;
use WPCCrawler\Objects\Filtering\Commands\Objects\TranslationCommandService;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinition;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionList;
use WPCCrawler\Objects\Filtering\Enums\CommandKey;
use WPCCrawler\Objects\Filtering\Interfaces\NeedsBot;
use WPCCrawler\Objects\Views\Select\SelectTranslationLanguagesView;

class Translate extends AbstractTransformActionCommand implements NeedsBot {

    /** @var AbstractBot|null */
    private $bot = null;

    public function getKey(): string {
        return CommandKey::TRANSLATE;
    }

    public function getName(): string {
        return _wpcc('Translate');
    }

    protected function getFieldsInputDescription(): string {
        return _wpcc('Select the fields that will be translated. If no fields are selected, all the fields will'
            . ' be translated.');
    }

    protected function createViews(): ViewDefinitionList {
        return parent::createViews()
            ->add(new ViewDefinition(SelectTranslationLanguagesView::class));
    }

    protected function shouldUseAllFieldsByDefault(): bool {
        return true;
    }

    protected function onTransformValues(array $values): array {
        return $this->createTranslationCommandService()
            ->translateValues($values);
    }

    protected function createTranslationCommandService(): TranslationCommandService {
        return new TranslationCommandService($this);
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