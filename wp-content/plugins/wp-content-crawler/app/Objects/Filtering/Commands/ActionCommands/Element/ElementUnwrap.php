<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 26/02/2023
 * Time: 10:40
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\ActionCommands\Element;

use DOMElement;
use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base\AbstractBotActionCommand;
use WPCCrawler\Objects\Filtering\Enums\CommandKey;
use WPCCrawler\Objects\Html\ElementUnwrapper;

class ElementUnwrap extends AbstractBotActionCommand {

    public function getKey(): string {
        return CommandKey::ELEMENT_UNWRAP;
    }

    public function getName(): string {
        return _wpcc('Unwrap');
    }

    public function getDescription(): ?string {
        return _wpcc("Removes an element's tags, while keeping its content");
    }

    protected function onExecuteCommand($node): void {
        if (!($node instanceof Crawler)) return;

        $child = $node->getNode(0);
        if (!($child instanceof DOMElement)) return;

        (new ElementUnwrapper())->unwrap($child);
    }
}