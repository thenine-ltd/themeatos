<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 02/02/2023
 * Time: 18:16
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Transformation\Translation\Clients;

use DeepL\DeepLException;
use DeepL\Language;
use DeepL\TextResult;
use DeepL\TranslateTextOptions;
use DeepL\Translator;
use Exception;
use Illuminate\Support\Arr;
use WPCCrawler\Objects\Chunk\Enum\ChunkType;
use WPCCrawler\Objects\Chunk\TransformationChunker;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Objects\Settings\SettingsImpl;
use WPCCrawler\Objects\Transformation\Translation\TextTranslator;

class DeeplTranslateAPIClient extends AbstractTranslateAPIClient {

    /*
        Some notes:
            * Library: https://github.com/DeepLcom/deepl-php
            * Limits can be found here: https://www.deepl.com/docs-api/translate-text/
            * Request body size must not exceed 128 KiB (i.e. 128*1024 bytes).
     */

    /** @var string */
    const OPTION_KEY_SOURCE = 'source';
    /** @var string */
    const OPTION_KEY_TARGET = 'target';

    const FORMALITY_DEFAULT     = 'default';
    const FORMALITY_MORE        = 'more';
    const FORMALITY_LESS        = 'less';
    const FORMALITY_PREFER_MORE = 'prefer_more';
    const FORMALITY_PREFER_LESS = 'prefer_less';

    /** @var string */
    private $settingKeyFrom = SettingKey::WPCC_TRANSLATION_DEEPL_TRANSLATE_FROM;
    /** @var string */
    private $settingKeyTo = SettingKey::WPCC_TRANSLATION_DEEPL_TRANSLATE_TO;
    /** @var string */
    private $settingKeyApiKey = SettingKey::WPCC_TRANSLATION_DEEPL_TRANSLATE_API_KEY;
    /** @var string */
    private $settingKeyFormality = SettingKey::WPCC_TRANSLATION_DEEPL_TRANSLATE_FORMALITY;

    /** @var Translator|null */
    private $client = null;

    /** @var string|null API key */
    private $apiKey = null;

    /** @var string|null 'Formality" option's value */
    private $formality = null;

    public function init() {
        // Nothing to do here.
    }

    public function setOptionsUsingSettings(SettingsImpl $settings) {
        $apiKey = $settings->getStringSetting($this->settingKeyApiKey);
        if($apiKey === null || $apiKey === '') {
            throw new Exception(_wpcc("You must provide a valid API key for DeepL API to work properly."));
        }

        $this->apiKey = $apiKey;
        $this
            ->setFrom($settings->getStringSetting($this->settingKeyFrom) ?? '')
            ->setTo($settings->getStringSetting($this->settingKeyTo) ?? '');

        $this->formality = $settings->getStringSetting($this->settingKeyFormality);
        if ($this->formality === '') {
            $this->formality = null;
        }
    }

    public function createChunker(array $texts): TransformationChunker {
        // The request body limit is 128 KiB. The body can contain field names, quotes, and other options as well. So,
        // assign a value considering that.
        $byteLimit = 100 * 1024;
        return new TransformationChunker(
            // The client sends the data as URL-encoded. So, the byte length must be calculated for the URL-encoded
            // texts.
            ChunkType::T_URL_ENCODED_BYTES,
            $texts,
            $byteLimit,
            $byteLimit,
            80
        );
    }

    public function translate(TextTranslator $textTranslator): array {
        $from = $this->getFrom();
        $to   = $this->getTo();

        if (!$from || !$to) {
            throw new Exception("From and to languages must be set.");
        }

        return $textTranslator->translate($this, [
            // Define source and target languages. The keys for these are internal. They won't be sent to the API.
            self::OPTION_KEY_SOURCE => $this->sanitizeFrom($from),
            self::OPTION_KEY_TARGET => $to,

            // Define other DeepL options.
            TranslateTextOptions::FORMALITY => $this->formality,
        ]);
    }

    public function translateBatch(array $texts, $options = []) {
        $client = $this->getClient();
        if (!$client) {
            Informer::addError(_wpcc('DeepL client could not be retrieved.'));
            return [];
        }

        // Get source and target languages from the options array and then remove them from the array so that the
        // options array contains only the DeepL options.
        $sourceLang = Arr::pull($options, self::OPTION_KEY_SOURCE, null);
        $targetLang = Arr::pull($options, self::OPTION_KEY_TARGET, null);

        $sourceLang = is_string($sourceLang)
            ? $sourceLang
            : null;
        $targetLang = is_string($targetLang)
            ? $targetLang
            : '';

        if ($sourceLang === '') {
            $sourceLang = null;
        }

        try {
            $results = $client->translateText($texts, $sourceLang, $targetLang, $options + [
                // Tell the API we are sending HTML
                TranslateTextOptions::TAG_HANDLING => 'html',
            ]);
        } catch (DeepLException $e) {
            Informer::addError($e->getMessage())->setException($e)->addAsLog();
            return [];
        }

        if (!$results) {
            return [];
        }

        if ($results instanceof TextResult) {
            $results = [$results];
        }

        return array_map(function(TextResult $result) {
            return $result->text;
        }, $results);
    }

    /**
     * @throws Exception
     */
    public function localizedLanguages($options = []): array {
        $client = $this->getClient();
        if (!$client) {
            throw new Exception(_wpcc('DeepL client could not be retrieved.'));
        }

        $languages = array_merge(
            $client->getSourceLanguages(),
            $client->getTargetLanguages(),
        );
        usort($languages, function(Language $a, Language $b) {
            return strcmp($a->name, $b->name);
        });

        $result = [];
        foreach($languages as $language) {
            $code = $language->code;
            $name = $language->name;
            if (isset($result[$code])) continue;

            $result[$code] = [
                "code" => $code,
                "name" => $name,
            ];
        }

        return array_values($result);
    }

    public function getTestResultMessage(SettingsImpl $settings): string {
        return sprintf(
            _wpcc('<b>From:</b> %1$s, <b>To:</b> %2$s, <b>API Key:</b> %3$s'),
            $settings->getStringSetting($this->settingKeyFrom) ?? '',
            $settings->getStringSetting($this->settingKeyTo) ?? '',
            $settings->getStringSetting($this->settingKeyApiKey) ?? '',
        );
    }

    /*
     * HELPERS
     */

    /**
     * @return Translator|null
     * @since 1.13.0
     */
    protected function getClient(): ?Translator {
        if ($this->client === null) {
            $key = $this->apiKey;
            try {
                $this->client = is_string($key) && $key !== ''
                    ? new Translator($key)
                    : null;

            } catch (DeepLException $e) {
                $this->client = null;
                Informer::addError($e->getMessage())->setException($e)->addAsLog();
            }
        }

        return $this->client;
    }

    /*
     * STATIC HELPERS
     */

    /**
     * @return array<string, string> Options that can be used in a "select" element to display the available values for
     *                               the "formality" option of DeepL
     * @since 1.13.0
     */
    public static function getFormalityOptions(): array {
        return [
            '' => _wpcc('Select formality'),
            self::FORMALITY_DEFAULT     => self::FORMALITY_DEFAULT,
            self::FORMALITY_MORE        => self::FORMALITY_MORE,
            self::FORMALITY_LESS        => self::FORMALITY_LESS,
            self::FORMALITY_PREFER_MORE => self::FORMALITY_PREFER_MORE,
            self::FORMALITY_PREFER_LESS => self::FORMALITY_PREFER_LESS,
        ];
    }

}