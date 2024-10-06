<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 22/02/2023
 * Time: 18:10
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\ActionCommands\Element;

use DOMNode;
use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base\AbstractBotActionCommand;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinition;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionFactory;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionList;
use WPCCrawler\Objects\Filtering\Enums\CommandKey;
use WPCCrawler\Objects\Filtering\Enums\InputName;
use WPCCrawler\Objects\Html\ElementCreator;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;
use WPCCrawler\Objects\Views\ShortCodeButtonsWithLabelForCreateElementCmd;
use WPCCrawler\Objects\Views\TextAreaWithLabel;

/**
 * Used to create new elements and insert them relative to other elements inside the crawler
 *
 * @since 1.13.0
 */
class ElementCreate extends AbstractBotActionCommand {

    public function getKey(): string {
        return CommandKey::ELEMENT_CREATE;
    }

    public function getName(): string {
        return _wpcc('Create');
    }

    public function getDescription(): ?string {
        return _wpcc('Create and insert a new HTML element relative to target elements specified by their 
            selectors. If there are multiple target elements, a new HTML element will be created and inserted for each 
            of them.');
    }

    protected function createViews(): ViewDefinitionList {
        $factory = ViewDefinitionFactory::getInstance();
        return (new ViewDefinitionList())
            // Element location
            ->add($factory->createElementLocationSelect())

            // Add the short code buttons to the top of the template
            ->add((new ViewDefinition(ShortCodeButtonsWithLabelForCreateElementCmd::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Short codes'))
                ->setVariable(ViewVariableName::INFO,  _wpcc("Short codes that can be used in the code of the new
                    HTML element. You can hover over the short codes to see what they do. You can click to the short 
                    code buttons to copy the short codes. Then, you can paste the short codes into the new HTML
                    element's code to include them. They will be replaced with their actual values."))
            )
            // HTML element template
            ->add((new ViewDefinition(TextareaWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc("New element's HTML code"))
                ->setVariable(ViewVariableName::INFO,  _wpcc("Define your HTML element. The code you enter here 
                    must be a valid HTML code. If it is not valid, you might get unexpected results. Otherwise, your
                    element will be created and inserted into the specified location."))
                ->setVariable(ViewVariableName::PLACEHOLDER, _wpcc('HTML code of the element...'))
                ->setVariable(ViewVariableName::NAME, InputName::TEMPLATE)
                ->setVariable(ViewVariableName::ROWS, 8))
        ;
    }

    protected function onExecuteCommand($node): void {
        if (!($node instanceof Crawler)) return;

        $referenceElement = $node->getNode(0);
        if (!$referenceElement) return;

        // Get the parent element. If there is no parent element, stop and notify the user. We cannot insert an element
        // if there is no parent element.
        // Probably there will never be a case where the parent element does not exist. Even if the target element is
        // "html", its parent element is the document. So, it does not seem to be possible to test this.
        /** @var DOMNode|null $parentElement */
        $parentElement = $referenceElement->parentNode;
        if (!$parentElement) {
            // Notify the user if there is a logger
            $logger = $this->getLogger();
            if ($logger) {
                $logger->addMessage(_wpcc('New HTML element could not be inserted, because the target element 
                    does not have a parent element. The target element must have a parent element.'));
            }

            return;
        }

        $creator = new ElementCreator();
        $creator->create($referenceElement, $this->getLocation(), $this->createHtmlCode());
    }

    /**
     * @return string|null The final HTML code, created from the template
     * @since 1.13.0
     */
    protected function createHtmlCode(): ?string {
        $template = $this->getNewElementTemplate();
        if ($template === '') return null;

        // Get the short code value map
        $shortCodeValueMap = $this->getShortCodeValueMap();

        // If the value map does not exist, return the template without applying the short codes. In this case, we
        // expect that the user creates an element that will be selected via CSS selectors later to be used in other
        // places of the post, such as the content. Then, when the content is being prepared, the short codes will be
        // applied there.
        if ($shortCodeValueMap === null) {
            return $template;
        }

        // Apply the short codes to the template to create the final HTML code
        $applier = $this->createShortCodeApplier($shortCodeValueMap);
        return $applier->apply($template);
    }

    /*
     * OPTION GETTERS
     */

    /**
     * @return string The location option's value
     * @since 1.13.0
     */
    protected function getLocation(): string {
        $availableLocations = array_keys(ElementCreator::getLocationOptionsForSelect());
        $location = $this->getStringOption(InputName::ELEMENT_LOCATION);
        return $location === null || !in_array($location, $availableLocations)
            ? ElementCreator::LOCATION_AFTER
            : $location;
    }

    /**
     * @return string Template of the new HTML element. This might contain short codes.
     * @since 1.13.0
     */
    protected function getNewElementTemplate(): string {
        return $this->getStringOption(InputName::TEMPLATE) ?? '';
    }

}