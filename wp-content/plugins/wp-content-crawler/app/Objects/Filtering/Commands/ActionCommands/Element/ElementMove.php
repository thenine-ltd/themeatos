<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 10/07/2023
 * Time: 00:45
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\ActionCommands\Element;

use DOMElement;
use DOMNode;
use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base\AbstractBotActionCommand;
use WPCCrawler\Objects\Filtering\Commands\CommandUtils;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinition;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionFactory;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionList;
use WPCCrawler\Objects\Filtering\Enums\CommandKey;
use WPCCrawler\Objects\Filtering\Enums\InputName;
use WPCCrawler\Objects\Html\ElementCreator;
use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;
use WPCCrawler\Objects\Views\MultipleSelectorWithLabel;

class ElementMove extends AbstractBotActionCommand {

    public function getKey(): string {
        return CommandKey::ELEMENT_MOVE;
    }

    public function getName(): string {
        return _wpcc('Move');
    }

    public function getDescription(): ?string {
        return _wpcc('Moves the target element relative to the specified reference element');
    }

    protected function createViews(): ViewDefinitionList {
        return (new ViewDefinitionList())
            ->add((new ViewDefinition(MultipleSelectorWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Reference selectors'))
                ->setVariable(ViewVariableName::NAME,  InputName::REFERENCE_SELECTOR)
                ->setVariable(ViewVariableName::INFO,  _wpcc('Enter one or more selectors that will be used to'
                    . ' find the reference element. If multiple elements are found, the first one will be used.'))
            )

            // Element location
            ->add(ViewDefinitionFactory::getInstance()->createElementLocationSelect())
        ;
    }

    protected function onExecuteCommand($node): void {
        if (!($node instanceof Crawler)) return;

        $target = $node->getNode(0);
        if (!($target instanceof DOMElement)) return;

        $logger = $this->getLogger();

        $refNode = $this->getReferenceElement();
        if (!$refNode) {
            if ($logger) $logger->addMessage(_wpcc('Reference element could not be found.'));
            return;
        }

        // Move the found element to its new location
        (new ElementCreator())->moveNewElements(
            $refNode,
            (new CommandUtils())->getElementLocationOption($this),
            [$target]
        );
    }

    /*
     *
     */

    /**
     * @return DOMNode|null The element relative to which the target element(s) will be moved
     * @since 1.14.0
     */
    public function getReferenceElement(): ?DOMNode {
        $bot = $this->getBot();
        if (!$bot) return null;

        $crawler = $bot->getCrawler();
        if (!$crawler) return null;

        $selectors = $this->getReferenceSelectors();
        if (!$selectors) return null;

        foreach($selectors as $selectorData) {
            $selector = $selectorData[SettingInnerKey::SELECTOR] ?? null;
            if ($selector === null) continue;

            // If a reference node is found, return it directly, since we need only one reference node.
            $refNode = $crawler->filter($selector)->first()->getNode(0);
            if ($refNode) {
                return $refNode;
            }
        }

        return null;
    }

    /*
     *
     */

    /**
     * @return array|null CSS selectors that select the container element(s)
     * @since 1.14.0
     */
    protected function getReferenceSelectors(): ?array {
        return $this->getArrayOption(InputName::REFERENCE_SELECTOR);
    }
}