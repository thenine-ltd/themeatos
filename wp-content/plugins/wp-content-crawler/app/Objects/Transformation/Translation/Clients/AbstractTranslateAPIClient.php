<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 30/12/2018
 * Time: 12:27
 *
 * @since 1.8.0
 */

namespace WPCCrawler\Objects\Transformation\Translation\Clients;

use Exception;
use WPCCrawler\Objects\Transformation\Base\AbstractTransformAPIClient;
use WPCCrawler\Objects\Transformation\Translation\TextTranslator;

abstract class AbstractTranslateAPIClient extends AbstractTransformAPIClient {

    /** @var string|null The language of the content to be translated */
    private $from = null;

    /** @var string|null The target language */
    private $to = null;

    /**
     * Translate texts using the settings. This method might override the options given in the constructor.
     *
     * @param TextTranslator $textTranslator
     * @return array See {@link TextTranslator::translate()}
     * @uses TextTranslator::translate()
     * @since 1.9.0
     * @throws Exception When the settings do not have the required options.
     */
    public abstract function translate(TextTranslator $textTranslator);

    /**
     * Translate an array of strings.
     *
     * @param array $texts   A flat sequential array of texts
     * @param array $options Translation options
     * @return array Translated texts in the same order as $texts. If an error occurs, returns an empty array.
     * @since 1.8.0
     */
    public abstract function translateBatch(array $texts, $options = []);

    /**
     * Get languages
     *
     * @param array $options A key-value pair array that defines the options for to-be-retrieved languages
     * @return array An array structured as [ ["code" => "lang1code", "name" => "Lang 1 Name], ["code" => "lang2code", "name" => "Lang 2 Name"], ... ]
     *               In case of error, returns an empty array.
     * @since 1.8.0
     */
    public abstract function localizedLanguages($options = []);

    /*
     * PUBLIC HELPERS
     */

    /**
     * Prepares languages array as key-value pairs.
     *
     * @param array $options See {@link localizedLanguages()}
     * @return array A key-value pair where keys are language codes and the values are their names.
     * @since 1.8.0
     */
    public function getLocalizedLanguagesAsAssocArray($options = []) {
        $languages = $this->localizedLanguages($options);

        $prepared = [];
        foreach($languages as $lang) $prepared[$lang["code"]] = $lang["name"];

        return $prepared;
    }

    /*
     * PROTECTED HELPERS
     */

    /**
     * @param string $from Original 'from' value that will be used to define 'from' value in translate API request
     * @return string Sanitized value that can be safely sent to the APIs
     * @since 1.8.0
     */
    protected function sanitizeFrom($from) {
        return $from == 'detect' ? '' : $from;
    }

    /*
     * GETTERS AND SETTERS
     */

    /**
     * @return string|null See {@link from}
     * @since 1.14.0
     */
    public function getFrom(): ?string {
        return $this->from;
    }

    /**
     * @param string|null $from See {@link from}
     * @return self
     * @since 1.14.0
     */
    public function setFrom(?string $from): AbstractTranslateAPIClient {
        $this->from = $from;
        return $this;
    }

    /**
     * @return string|null See {@link to}
     * @since 1.14.0
     */
    public function getTo(): ?string {
        return $this->to;
    }

    /**
     * @param string|null $to See {@link to}
     * @return self
     * @since 1.14.0
     */
    public function setTo(?string $to): AbstractTranslateAPIClient {
        $this->to = $to;
        return $this;
    }
}