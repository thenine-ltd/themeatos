<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 04/10/2023
 * Time: 11:15
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\Objects;

use Exception;
use WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base\AbstractActionCommand;
use WPCCrawler\Objects\Filtering\Explaining\Loggers\ActionCommandLogger;
use WPCCrawler\Objects\Filtering\Interfaces\NeedsBot;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Objects\Settings\SettingsImpl;
use WPCCrawler\Objects\Transformation\Translation\Clients\AbstractTranslateAPIClient;
use WPCCrawler\Objects\Transformation\Translation\TextTranslator;
use WPCCrawler\Objects\Transformation\Translation\TranslationService;
use WPCCrawler\WPCCrawler;

class TranslationCommandService {

    /** @var AbstractActionCommand&NeedsBot The command that translates texts to one or more languages */
    private $command;

    /**
     * @param AbstractActionCommand&NeedsBot $command See {@link command}
     * @since 1.14.0
     */
    public function __construct($command) {
        $this->command = $command;
    }

    /**
     * @param array $values The values to be translated
     * @return array If the translations are successful, the translated texts are returned. Otherwise, the original
     *               values are returned.
     * @since 1.14.0
     */
    public function translateValues(array $values): array {
        $logger = $this->getLogger();

        // Get the bot. We need it to retrieve the settings that we will use to retrieve API keys.
        $bot = $this->getCommand()->getBot();
        if (!$bot) {
            $this->maybeLogNoClientMessage();
            return $values;
        }

        // Create the API client
        $client = $this->createApiClient($bot->getSettingsImpl());
        if (!$client) {
            $this->maybeLogNoClientMessage();
            return $values;
        }

        // Get the selected languages
        $languages = $this->getLanguages();
        if (!$languages) {
            if ($logger) {
                $logger->addMessage(_wpcc('Values could not be translated, because the specified languages could not be retrieved.'));
            }

            return $values;
        }

        // Add subject items to the logger
        $this->maybeLogSubjectItems($values);

        // Translate the values to the specified target languages one by one
        $newValues = array_values($values);
        foreach($languages as $languagePair) {
            $from = $languagePair[SettingInnerKey::FROM] ?? null;
            $to   = $languagePair[SettingInnerKey::TO]   ?? null;

            // If the "from" and "to" language are not defined, do not transform the values at all.
            if (!is_string($from) || !is_string($to)) {
                return $values;
            }

            // Try to translate the new values
            $newValues = $this->translateOne($client, $newValues, $from, $to);
            if ($newValues === null) {
                return $values;
            }
        }

        // Log the new values
        $this->maybeLogModifiedSubjectItems($newValues);

        return array_combine(array_keys($values), $newValues) ?: $values;
    }

    /*
     *
     */

    /**
     * Translate values from one language to another by using a translation API client
     *
     * @param AbstractTranslateAPIClient $client The client that will translate the values
     * @param string[]                   $values The values to be translated
     * @param string                     $from   Current language of the values
     * @param string                     $to     Target language
     * @return string[]|null
     * @since 1.14.0
     */
    protected function translateOne(AbstractTranslateAPIClient $client, array $values, string $from, string $to): ?array {
        $logger = $this->getLogger();

        // Try to translate the new values
        try {
            $newValues = $client
                ->setFrom($from)
                ->setTo($to)
                ->translate($this->createTextTranslator($values));

            // Add a log message to indicate a successful translation
            if ($logger) {
                $logger->addMessage(sprintf(
                    _wpcc('Values are translated from "%1$s" to "%2$s".'), $from, $to
                ));
            }

            return $newValues;

        } catch (Exception $e) {
            // Log the exception
            $message = sprintf(_wpcc('Values could not be translated from "%1$s" to "%2$s".'), $from, $to)
                . ' - '
                . $e->getMessage();

            Informer::addInfo($message)
                ->setException($e)
                ->addAsLog();

            if ($logger) {
                $logger->addMessage($message);
            }

            // There was a problem with the translation. Return the original values.
            return null;
        }

    }

    /**
     * Creates the API client selected by the user
     *
     * @param SettingsImpl $settings Settings from which the API keys will be retrieved
     * @return AbstractTranslateAPIClient|null The client selected from the options of the command, ready to translate.
     *                                         If the client could not be created, `null` is returned.
     * @since 1.14.0
     */
    protected function createApiClient(SettingsImpl $settings): ?AbstractTranslateAPIClient {
        $selectedServiceKey = $this->getSelectedTranslationServiceKey();
        if ($selectedServiceKey === null) {
            return null;
        }

        try {
            $client = TranslationService::getInstance()
                ->createApiClientUsingSettings($settings, $selectedServiceKey);

        } catch (Exception $e) {
            // Log the exception
            Informer::addInfo($e->getMessage())->setException($e)->addAsLog();

            return null;
        }

        return $client instanceof AbstractTranslateAPIClient
            ? $client
            : null;
    }

    /**
     * @param string[] $values The values to be translated
     * @return TextTranslator A new text translator that will translate the given values
     * @since 1.14.0
     */
    protected function createTextTranslator(array $values): TextTranslator {
        // Dry-run translations when the user performs a unit test, to avoid unnecessary costs and to reduce the time it
        // takes to perform tests.
        return new TextTranslator($values, WPCCrawler::isDoingUnitTest());
    }

    /*
     * OPTION GETTERS
     */

    /**
     * @return string|null Key of the translation service selected from the command options
     * @since 1.14.0
     */
    protected function getSelectedTranslationServiceKey(): ?string {
        $serviceKey = $this->getCommand()->getStringOption(SettingKey::WPCC_SELECTED_TRANSLATION_SERVICE);
        return $serviceKey !== '-1'
            ? $serviceKey
            : null;
    }

    /**
     * @return array|null Languages defined in the command options
     * @since 1.14.0
     */
    protected function getLanguages(): ?array {
        $serviceKey = $this->getSelectedTranslationServiceKey();
        return $serviceKey !== null
            ? $this->getCommand()->getArrayOption(sprintf('%1$s%2$s', $serviceKey, TranslationService::LANGS_INPUT_NAME_SUFFIX))
            : null;
    }

    /*
     *
     */

    /**
     * If there is a logger, adds a message to indicate that the values could not be translated due to a failure in
     * creation of an API client.
     *
     * @since 1.14.0
     */
    protected function maybeLogNoClientMessage(): void {
        $logger = $this->getLogger();
        if (!$logger) {
            return;
        }

        $logger->addMessage(_wpcc('Values could not be translated, because an API client could not be created.'));
    }

    /**
     * @param array $values Subject items that will be added to the logger
     * @since 1.14.0
     */
    protected function maybeLogSubjectItems(array $values): void {
        $logger = $this->getLogger();
        if (!$logger) {
            return;
        }

        foreach($values as $value) {
            $logger->addSubjectItem((string) $value);
        }
    }

    /**
     * @param array $modifiedValues Final translation of the values
     * @since 1.14.0
     */
    protected function maybeLogModifiedSubjectItems(array $modifiedValues): void {
        $logger = $this->getLogger();
        if (!$logger) {
            return;
        }

        foreach($modifiedValues as $value) {
            $logger->addModifiedSubjectItem((string) $value);
        }
    }

    /**
     * @return ActionCommandLogger|null Logger of the {@link command}
     * @since 1.14.0
     */
    protected function getLogger(): ?ActionCommandLogger {
        return $this->getCommand()->getLogger();
    }

    /*
     * GETTERS
     */

    /**
     * @return AbstractActionCommand&NeedsBot See {@link command}
     * @since 1.14.0
     */
    public function getCommand() {
        return $this->command;
    }

}