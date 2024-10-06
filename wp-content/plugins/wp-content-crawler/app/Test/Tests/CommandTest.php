<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 31/10/2018
 * Time: 22:24
 */

namespace WPCCrawler\Test\Tests;

use WPCCrawler\Objects\Crawling\Bot\PostBot;
use WPCCrawler\Objects\Filtering\Commands\CommandService;
use WPCCrawler\Objects\Filtering\Filter\Filter;
use WPCCrawler\Objects\Filtering\FilterDependencyProvider\Page\PageFilterDependencyProvider;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Test\Base\AbstractTest;
use WPCCrawler\Test\Data\TestData;
use WPCCrawler\Utils;

class CommandTest extends AbstractTest {

    /** @var int */
    private $subjectMaxLength = 50;

    /** @var string|null */
    private $message;

    /**
     * @param TestData $data
     * @return string[]|null
     * @since 1.11.0
     */
    protected function createResults($data): ?array {
        // Create the command by using the command data
        $cmdData = $data->get('commandData', []);
        $command = Filter::createCommandFromOptions(CommandService::getInstance(), $cmdData);

        // If the command could not be created, stop.
        if ($command === null) {
            $this->message = _wpcc('The command cannot be found.');
            return null;
        }

        $subject = $command->getTestSubject();
        if (mb_strlen($subject) > $this->subjectMaxLength) {
            $subject = mb_substr($subject, 0, $this->subjectMaxLength) . '...';
        }

        // Set the command's provider. The type of the bot is not important, since it is only used to retrieve the
        // settings currently.
        $customGeneralSettings = $data->getCustomGeneralSettings();
        $settings = $customGeneralSettings ?? [];
        if ($customGeneralSettings !== null) {
            $settings[SettingKey::DO_NOT_USE_GENERAL_SETTINGS] = true;
        }
        $command->setProvider(new PageFilterDependencyProvider(new PostBot($settings), null));

        // Make the command perform the test
        $result = $command->test();

        // Create the message
        $cmdMessage = $command->getTestMessage();
        $this->message = sprintf(
            _wpcc('Results of the test performed for %1$s command with the value %2$s'),
            '<b class="cmd-name">' . $command->getName() . '</b>',
            '<b class="cmd-subject">' . htmlspecialchars($subject) . '</b>' . ($cmdMessage ? " ({$cmdMessage})" : '')
        ) . ':';

        return $result;
    }

    protected function createView() {
        return Utils::view('partials/test-result')
            ->with("results", $this->getResults())
            ->with("message", $this->message ?: '');
    }
}