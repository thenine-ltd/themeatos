<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 10/02/2023
 * Time: 15:06
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi\ShortCode;

use WPCCrawler\Objects\Api\OpenAi\Enums\ModelMode;
use WPCCrawler\Objects\Api\OpenAi\Enums\ShortCodeAttributes;
use WPCCrawler\Objects\Api\OpenAi\OpenAiClient;
use WPCCrawler\Objects\Enums\ShortCodeName;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Objects\ShortCode\AbstractCustomShortCodeApplier;
use WPCCrawler\Objects\ShortCode\ShortCodeApplier;

class OpenAiGptShortCodeApplier extends AbstractCustomShortCodeApplier {

    /** @var OpenAiClient */
    private $client;

    /**
     * @param OpenAiClient     $client The client that will be used to make requests to the API
     * @param ShortCodeApplier $applier See {@link AbstractCustomShortCodeApplier::__construct()}
     * @since 1.13.0
     */
    public function __construct(OpenAiClient $client, ShortCodeApplier $applier) {
        parent::__construct($applier);
        $this->client = $client;
    }

    public function getShortCodeName(): string {
        return ShortCodeName::OPENAI_GPT;
    }

    /**
     * @param string $template The template that might contain one or more [openai-gpt] short codes
     * @return string The content whose [openai-gpt] short codes are replaced
     * @since 1.13.0
     */
    public function onApply(string $template): string {
        return $this->doApplyWithCallback($template, [$this, 'applySingle']);
    }

    protected function onInjectValues(string $template): string {
        return $this->doApplyWithCallback($template, [$this, 'injectValuesSingle']);
    }

    /**
     * @return OpenAiClient
     * @since 1.13.0
     */
    public function getClient(): OpenAiClient {
        return $this->client;
    }

    /*
     * HELPERS
     */

    /**
     * @param array $match Regular expression match array
     * @return string The short code's actual value
     * @since 1.13.0
     */
    protected function applySingle(array $match): string {
        $shortCode = $this->createShortCodeFromMatch($match);
        return $shortCode === null
            ? ''
            : $shortCode->apply();
    }

    /**
     * @param array $match Regular expression match array
     * @return string The short code rewritten by injecting other short codes via the applier retrieved via
     *                {@link getApplier()}.
     * @since 1.13.0
     */
    protected function injectValuesSingle(array $match): string {
        $shortCode = $this->createShortCodeFromMatch($match);
        return $shortCode === null
            ? ''
            : $shortCode->injectValues()->toString();
    }

    /**
     * @param array $match Regular expression match array
     * @return OpenAiGptShortCode|null The short code if it could be created. Otherwise, `null`.
     * @since 1.13.0
     */
    public function createShortCodeFromMatch(array $match): ?OpenAiGptShortCode {
        $shortCode = $match[0] ?? '';
        $attributes = shortcode_parse_atts($match[3] ?? '');
        if (is_string($attributes)) {
            $attributes = [$attributes];
        }

        $config = $this->getConfig($attributes);

        // Get each attribute
        $mode         = $this->getString( $config, ShortCodeAttributes::MODE);
        $model        = $this->getString( $config, ShortCodeAttributes::MODEL);
        $messages     = $this->getArray(  $config, ShortCodeAttributes::MESSAGES);
        $prompt       = $this->getString( $config, ShortCodeAttributes::PROMPT);
        $stop         = $this->getArray(  $config, ShortCodeAttributes::STOP);
        $input        = $this->getString( $config, ShortCodeAttributes::INPUT);
        $instructions = $this->getString( $config, ShortCodeAttributes::INSTRUCTIONS);
        $temperature  = $this->getNumeric($config, ShortCodeAttributes::TEMPERATURE);
        $maxLength    = $this->getNumeric($config, ShortCodeAttributes::MAX_LENGTH);

        // Make sure that the required parameters are defined
        if ($mode === null || $model === null
            || ($mode === ModelMode::CHAT && $messages === null)
            || (($mode === ModelMode::COMPLETE || $mode === ModelMode::INSERT) && $prompt === null)
            || ($mode === ModelMode::EDIT && ($input === null || $instructions === null))
        ) {
            Informer::addInfo(sprintf(
                _wpcc('Because one or more of the required attributes are missing, the short code cannot be applied. Short code: %1$s'),
                $shortCode,
            ))->addAsLog();
            return null;
        }

        return (new OpenAiGptShortCode($this->getClient(), $this->getApplier(), $mode, $model))
            ->setMessagesFromArray($messages)
            ->setPrompt($prompt)
            ->setStop($stop)
            ->setInput($input)
            ->setInstructions($instructions)
            ->setTemperature($temperature)
            ->setMaxLength($maxLength);
    }

    /**
     * @param array $attributes Short code attributes
     * @return array The configuration of the short code retrieved from the attributes
     * @since 1.13.0
     */
    protected function getConfig(array $attributes): array {
        $configRaw = $attributes[ShortCodeAttributes::CONFIG] ?? null;
        if (!is_string($configRaw)) {
            return [];
        }

        $configJson = base64_decode($configRaw);
        if (!is_string($configJson)) {
            return [];
        }

        $config = json_decode($configJson, true);
        return is_array($config)
            ? $config
            : [];
    }

    /**
     * @param array  $config Short code config
     * @param string $name   Name of the attribute whose value will be returned
     * @return array|null If the value exists, and it is an array, the value is returned. Otherwise, `null` is returned.
     * @since 1.13.0
     */
    protected function getArray(array $config, string $name): ?array {
        $value = $config[$name] ?? null;
        return is_array($value)
            ? $value
            : null;
    }

    /**
     * @param array  $config Short code config
     * @param string $name   Name of the attribute whose value will be returned
     * @return float|int|string|null If the value exists, and it is a numeric value, the value is returned. Otherwise,
     *                               `null` is returned.
     * @since 1.13.0
     */
    protected function getNumeric(array $config, string $name) {
        $value = $this->getScalar($config, $name);
        return is_numeric($value)
            ? $value
            : null;
    }

    /**
     * @param array  $config Short code config
     * @param string $name   Name of the attribute whose value will be returned
     * @return string|null If the value exists, and it is a scalar, the value is returned as a string. Otherwise, `null`
     *                       is returned.
     * @since 1.13.0
     */
    protected function getString(array $config, string $name): ?string {
        $value = $this->getScalar($config, $name);
        return $value === null
            ? null
            : (string) $value;
    }

    /**
     * @param array  $config Short code config
     * @param string $name   Name of the attribute whose value will be returned
     * @return scalar|null If the value exists, and it is a scalar, the value is returned. Otherwise, `null` is
     *                     returned.
     * @since 1.13.0
     */
    protected function getScalar(array $config, string $name) {
        $value = $config[$name] ?? null;
        return is_scalar($value)
            ? $value
            : null;
    }

}