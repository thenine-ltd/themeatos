<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 23/07/17
 * Time: 11:01
 */

namespace WPCCrawler\Objects\Transformation\Translation;

use Exception;
use Illuminate\Support\Arr;
use WPCCrawler\Objects\Enums\InformationMessage;
use WPCCrawler\Objects\Enums\InformationType;
use WPCCrawler\Objects\Informing\Information;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Objects\Transformation\Translation\Clients\AbstractTranslateAPIClient;
use WPCCrawler\WPCCrawler;

/**
 * Translates a multidimensional array of texts.
 */
class TextTranslator {

    /** @var array Texts to be translated. This can be a multi-level array. */
    private $texts;

    /**
     * @var bool If true, the texts will not be translated. Instead, they will be appended dummy values to mock the
     *      translation.
     */
    private $dryRun = false;

    /**
     * @param array $texts  See {@link $texts}
     * @param bool  $dryRun See {@link $dryRun}
     */
    public function __construct($texts, $dryRun = false) {
        $this->texts  = $texts;
        $this->dryRun = $dryRun || WPCCrawler::isDoingPhpUnitTest();
    }

    /**
     * A helper method to be used to translate {@link $texts}.
     *
     * @param AbstractTranslateAPIClient $apiClient
     * @param array                      $translationOptions
     * @return array Translated texts.
     */
    public function translate(AbstractTranslateAPIClient $apiClient, $translationOptions = []) {
        // Prepare the texts
        $chunker = $apiClient->createChunker($this->texts);
        $chunks = $chunker->chunk();

        $allTranslations = [];

        // Translate each chunk and store the translations in an array.
        foreach($chunks as $chunk) {
            // If this is a dry run, just append a text to the to-be-translated values. Otherwise, actually translate.
            $translations = $this->dryRun
                ? $this->getTranslationsForDryRun($chunk, $apiClient)
                : $apiClient->translateBatch($chunk, $translationOptions);

            $allTranslations = array_merge($allTranslations, array_values($translations));
        }

        // Prepare the translations
        try {
            $allTranslations = $chunker->unchunk(Arr::flatten($allTranslations));

        } catch (Exception $e) {
            Informer::add(Information::fromInformationMessage(
                InformationMessage::TRANSLATION_ERROR,
                $e->getMessage(),
                InformationType::ERROR
            )->setException($e)->addAsLog());
        }

        return $allTranslations;
    }

    /*
     * GETTERS
     */

    /**
     * @return array
     * @since 1.14.0
     */
    public function getTexts(): array {
        return $this->texts;
    }

    /*
     * PRIVATE METHODS
     */

    /**
     * Create dummy translations for testing
     *
     * @param string[] $chunk The texts to be translated
     * @return array Dummy translations
     * @since 1.9.0
     * @since 1.14.0 Adds $apiClient parameter
     */
    private function getTranslationsForDryRun(array $chunk, AbstractTranslateAPIClient $apiClient): array {
        $mark = sprintf(_wpcc('-translate-%1$s-%2$s-'), $apiClient->getFrom(), $apiClient->getTo()) . ' ';
        /** @noinspection PhpUnnecessaryLocalVariableInspection */
        $translations = array_map(function($v) use (&$mark) {
            $needle = '>';
            $pos = strpos($v, $needle);

            if ($pos !== false) {
                return substr_replace($v, $needle . $mark, $pos, strlen($needle));

            } else {
                return $mark . $v;
            }

        }, $chunk);

        return $translations;
    }

}