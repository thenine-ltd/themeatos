<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 03/10/2023
 * Time: 17:53
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base;

use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Crawling\Bot\AbstractBot;
use WPCCrawler\Objects\Enums\ValueType;
use WPCCrawler\Objects\Filtering\Interfaces\NeedsBot;

/**
 * Page action commands require a bot so that they can retrieve the crawler and perform some operations on it.
 *
 * @since 1.14.0
 */
abstract class AbstractPageActionCommand extends AbstractActionCommand implements NeedsBot {

    /** @var AbstractBot|null */
    private $bot;

    /**
     * @param Crawler $crawler The crawler that stores the target page's source code
     * @since 1.14.0
     */
    abstract protected function onExecuteCommand(Crawler $crawler): void;

    public function getInputDataTypes(): array {
        return [ValueType::T_PAGE];
    }

    protected function isOutputTypeSameAsInputType(): bool {
        return true;
    }

    protected function isTestable(): bool {
        return false;
    }

    protected function shouldReassignNewValues(): bool {
        return false;
    }

    public function doesNeedSubjectValue(): bool {
        return false;
    }

    protected function onExecute($key, $subjectValue) {
        $bot = $this->getBot();
        if (!$bot) {
            return;
        }

        $crawler = $bot->getCrawler();
        if (!$crawler) {
            return;
        }

        $this->onExecuteCommand($crawler);
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