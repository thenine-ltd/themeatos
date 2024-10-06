<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 03/10/2023
 * Time: 17:32
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\ActionCommands\Page;

use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base\AbstractPageActionCommand;
use WPCCrawler\Objects\Filtering\Commands\CommandUtils;
use WPCCrawler\Objects\Filtering\Commands\Objects\TranslationCommandService;
use WPCCrawler\Objects\Filtering\Commands\Objects\TranslationTarget;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinition;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionList;
use WPCCrawler\Objects\Filtering\Enums\CommandKey;
use WPCCrawler\Objects\Filtering\Enums\InputName;
use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;
use WPCCrawler\Objects\Views\MultipleSelectorWithAttributes;
use WPCCrawler\Objects\Views\Select\SelectTranslationLanguagesView;

class PageTranslateElementAttributes extends AbstractPageActionCommand {

    public function getKey(): string {
        return CommandKey::PAGE_TRANSLATE_ELEMENT_ATTRS;
    }

    public function getName(): string {
        return _wpcc('Translate element attributes');
    }

    protected function createViews(): ?ViewDefinitionList {
        return (new ViewDefinitionList())
            // Selectors and attributes
            ->add((new ViewDefinition(MultipleSelectorWithAttributes::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Target attribute selectors'))
                ->setVariable(ViewVariableName::NAME,  InputName::CSS_SELECTOR)
                ->setVariable(ViewVariableName::INFO,  _wpcc('Enter one or more selector-attribute rules that 
                    will be used to find the target element attributes that will be translated. You can enter many
                    attribute names by using a comma as a separator. All the found attribute values will be translated.
                    For example, to translate "alt" and "title" attributes of "img" elements, you can enter "img" as
                    the CSS selector and "alt, title" into the attribute input.'))
            )

            // Language selection
            ->add(new ViewDefinition(SelectTranslationLanguagesView::class));
    }

    protected function onExecuteCommand(Crawler $crawler): void {
        // Create the targets
        $targets = $this->createTranslationTargets($crawler);
        if (!$targets) {
            return;
        }

        // Create an array that contains the texts to be sent to the translation API
        $texts = array_map(function(TranslationTarget $target) {
            return $target->getOriginalAttributeValue();
        }, $targets);

        // Translate the texts
        $translatedTexts = $this->translateTexts($texts);

        // If the number of translated texts is not equal to the number of sent texts, stop. This will probably never
        // happen.
        if (count($translatedTexts) !== count($texts)) {
            return;
        }

        // Assign the translated values of the attributes
        $this->assignTranslatedTexts($targets, $translatedTexts);
    }

    /*
     *
     */

    /**
     * Assigns the translated values of the translation targets
     *
     * @param TranslationTarget[] $targets         The translation targets
     * @param string[]            $translatedTexts The translated values of the translated targets, in the same order
     *                                             as the translation targets.
     * @since 1.14.0
     */
    protected function assignTranslatedTexts(array $targets, array $translatedTexts): void {
        $length = count($targets);
        for($i = 0; $i < $length; $i++) {
            $target = $targets[$i] ?? null;
            $translatedAttrValue = $translatedTexts[$i] ?? null;

            // If the target or the translated attribute value does not exist, stop. This will probably never happen.
            if ($target === null || $translatedAttrValue === null) {
                continue;
            }

            // Assign the translated attribute value as the new attribute value to the target
            $target->setNewAttrValue($translatedAttrValue);
        }
    }

    /**
     * @param string[] $texts Texts to be translated
     * @return string[] Translated texts, if the translation was successful. Otherwise, the given texts.
     * @since 1.14.0
     */
    protected function translateTexts(array $texts): array {
        return $this->createTranslationCommandService()
            ->translateValues($texts);
    }

    /**
     * @return TranslationCommandService A new instance of {@link TranslationService} that will be used to translate
     *                                   texts by retrieving options from this command.
     * @since 1.14.0
     */
    protected function createTranslationCommandService(): TranslationCommandService {
        return new TranslationCommandService($this);
    }

    /**
     * @param Crawler $crawler The crawler that will be used to find the targets
     * @return TranslationTarget[]|null Translation targets found via the selectors specified as the option of the
     *                                  command. If no target is found, `null` is returned.
     * @since 1.14.0
     */
    protected function createTranslationTargets(Crawler $crawler): ?array {
        $commandUtils = new CommandUtils();

        // Get the CSS selectors
        $selectors = $commandUtils->getCssSelectorsOption($this);
        if (!$selectors) {
            return null;
        }

        /** @var TranslationTarget[] $targets */
        $targets = [];
        foreach($selectors as $selectorData) {
            $newTargets = $this->createTranslationTargetsFromSelectorData($crawler, $selectorData);
            if (!$newTargets) {
                continue;
            }

            $targets = array_merge($targets, $newTargets);
        }

        return $targets ?: null;
    }

    /**
     * @param Crawler $crawler          The crawler that will be used to find the targets
     * @param array   $selectorData     Selector data that contains {@link SettingInnerKey::SELECTOR} and
     *                                  {@link SettingInnerKey::ATTRIBUTE} keys that store the CSS selector and
     *                                  comma-separated attributes, respectively.
     * @return TranslationTarget[]|null Translation targets found via the given selector data. If there is no target,
     *                                  `null` is returned.
     * @since 1.14.0
     */
    protected function createTranslationTargetsFromSelectorData(Crawler $crawler, array $selectorData): ?array {
        // Extract the selector and comma-separated attributes from the given selector data
        $selector            = $selectorData[SettingInnerKey::SELECTOR]  ?? null;
        $commaSeparatedAttrs = $selectorData[SettingInnerKey::ATTRIBUTE] ?? null;
        if (!is_string($selector) || !is_string($commaSeparatedAttrs)) {
            return null;
        }

        // Prepare the selector and the attributes
        $selector = trim($selector);
        $attributes = $this->parseCommaSeparatedAttributes($commaSeparatedAttrs);
        if ($selector === '' || !$attributes) {
            return null;
        }

        // Create a translation target for each found attribute of each found element
        /** @var TranslationTarget[] $targets */
        $targets = [];
        $crawler->filter($selector)->each(function(Crawler $node) use ($attributes, &$targets) {
            foreach($attributes as $attrName) {
                $attrValue = $node->attr($attrName);
                if ($attrValue === null || $attrValue === '') {
                    continue;
                }

                $targets[] = new TranslationTarget($node, $attrName, $attrValue);
            }
        });

        return $targets ?: null;
    }

    /**
     * @param string $commaSeparatedAttrs Comma-separated attribute names
     * @return string[]|null A string array containing each attribute name separately. If there is no attribute name,
     *                       `null` is returned.
     * @since 1.14.0
     */
    protected function parseCommaSeparatedAttributes(string $commaSeparatedAttrs): ?array {
        // Explode the attributes from the commas
        $attributes = explode(',', $commaSeparatedAttrs);

        // Trim each attribute
        $attributes = array_map(function(string $attr) {
            return trim($attr);
        }, $attributes);

        // Remove the attributes that are empty
        $attributes = array_filter($attributes, function(string $attr) {
            return $attr !== '';
        });

        return $attributes ?: null;
    }

}