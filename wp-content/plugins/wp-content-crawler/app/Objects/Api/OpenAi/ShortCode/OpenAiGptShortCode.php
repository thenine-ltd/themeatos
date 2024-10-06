<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 10/02/2023
 * Time: 14:45
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi\ShortCode;

use Illuminate\Support\Str;
use WPCCrawler\Objects\Api\OpenAi\Enums\ModelMode;
use WPCCrawler\Objects\Api\OpenAi\Enums\ModelName;
use WPCCrawler\Objects\Api\OpenAi\Enums\ShortCodeAttributes;
use WPCCrawler\Objects\Api\OpenAi\Objects\ChatMessage;
use WPCCrawler\Objects\Api\OpenAi\OpenAiClient;
use WPCCrawler\Objects\Enums\ShortCodeName;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Objects\ShortCode\ShortCodeApplier;

class OpenAiGptShortCode {

    /** @var string The value to be used to indicate a model where to insert text */
    const INSERT_REFERENCE = '[insert]';

    /** @var array<string, string>|null */
    private static $textEscapeMap = null;

    /** @var OpenAiClient */
    private $client;

    /** @var ShortCodeApplier */
    private $applier;

    /** @var string One of the constants defined in {@link ModelMode} */
    private $mode;

    /** @var string One of the constants defined in {@link ModelName} */
    private $modelName;

    /** @var ChatMessage[]|null */
    private $messages = null;

    /** @var string|null The prompt that will be sent to the API */
    private $prompt = null;

    /** @var string[]|null The stop sequences */
    private $stop = null;

    /** @var string|null The input that will be sent to the API with the instructions */
    private $input = null;

    /** @var string|null Instructions that will be sent to the API, only available for specific modes */
    private $instructions = null;

    /** @var float|null The temperature parameter of the API */
    private $temperature = null;

    /** @var int|null The maximum length parameter of the API */
    private $maxLength = null;

    /**
     * @var array<string, string>|null The keys are short code names without brackets. The values are the actual values
     *      of the short codes. This is not used when applying the short code. This exists, because the UI adds the
     *      test short code values to the config so that the user can restore the entire configuration when importing
     *      a short code. When running the tests, we need to create the expected short code that must be the same as the
     *      one created in the UI.
     */
    private $testShortCodeValues = null;

    /**
     * @param OpenAiClient     $client    The client that will be used to make requests
     * @param ShortCodeApplier $applier   Used to apply the short codes that might exist in the text fields such as
     *                                    prompt, input, and instructions. The short codes found in the text fields
     *                                    will be replaced with their values before sending them to the API.
     * @param string           $mode      See {@link $mode}
     * @param string           $modelName See {@link $modelName}
     * @since 1.13.0
     */
    public function __construct(OpenAiClient $client, ShortCodeApplier $applier, string $mode, string $modelName) {
        $this->client = $client;
        $this->applier = $applier;
        $this
            ->setMode($mode)
            ->setModelName($modelName);
    }

    /**
     * @return OpenAiClient
     * @since 1.13.0
     */
    public function getClient(): OpenAiClient {
        return $this->client;
    }

    /**
     * @return ShortCodeApplier
     * @since 1.13.0
     */
    public function getApplier(): ShortCodeApplier {
        return $this->applier;
    }

    /**
     * @return string See {@link $mode}
     * @since 1.13.0
     */
    public function getMode(): string {
        return $this->mode;
    }

    /**
     * @param string $mode See {@link $mode}
     * @return self
     * @since 1.13.0
     */
    public function setMode(string $mode): self {
        $this->mode = $mode;
        return $this;
    }

    /**
     * @return string See {@link $modelName}
     * @since 1.13.0
     */
    public function getModelName(): string {
        return $this->modelName;
    }

    /**
     * @param string $modelName See {@link $modelName}
     * @return self
     * @since 1.13.0
     */
    public function setModelName(string $modelName): self {
        $this->modelName = $modelName;
        return $this;
    }

    /**
     * @param bool $prepareTemplate `true` if the message templates' short codes should be replaced with their values.
     *                              Otherwise, `false`.
     * @return ChatMessage[]|null
     * @since 1.13.0
     */
    public function getMessages(bool $prepareTemplate = false): ?array {
        if ($this->messages === null || !$prepareTemplate) {
            return $this->messages;
        }

        /** @var ChatMessage[] $newMessages */
        $newMessages = [];
        foreach($this->messages as $message) {
            $newMessages[] = new ChatMessage(
                $message->getRole(),
                $this->prepareTemplateText($message->getContent())
            );
        }

        return $newMessages;
    }

    /**
     * @param ChatMessage[]|null $messages The chat messages. This must NOT contain escaped special short code
     *                                     characters. If there are any escaped special short code characters, they
     *                                     will remain as-is. Inner short codes can still contain escaped special short
     *                                     code characters.
     * @return self
     * @since 1.13.0
     */
    public function setMessages(?array $messages): self {
        $this->messages = $messages;
        return $this;
    }

    /**
     * Set messages from an array of array representations of {@link ChatMessage}s
     *
     * @param array[]|null $messages An array of array representations of {@link ChatMessage}s. This must NOT contain
     *                               escaped special short code characters. If there are any escaped special short code
     *                               characters, they will remain as-is. Inner short codes can still contain escaped
     *                               special short code characters.
     * @return self
     * @since 1.13.0
     */
    public function setMessagesFromArray(?array $messages): self {
        if ($messages === null) {
            return $this->setMessages(null);
        }

        /** @var ChatMessage[] $chatMessages */
        $chatMessages = [];
        foreach($messages as $messageArr) {
            $message = ChatMessage::fromArray($messageArr);
            if (!$message) continue;

            $chatMessages[] = $message;
        }

        return $this->setMessages($chatMessages);
    }

    /**
     * @param bool $prepareTemplate `true` if the prompt template's short codes should be replaced with their values.
     *                              Otherwise, `false`.
     * @return string|null See {@link $prompt}
     * @since 1.13.0
     */
    public function getPrompt(bool $prepareTemplate = false): ?string {
        if ($this->prompt === null) {
            return null;
        }

        return $prepareTemplate
            ? $this->prepareTemplateText($this->prompt)
            : $this->prompt;
    }

    /**
     * @param string|null $prompt See {@link $prompt}
     * @return self
     * @since 1.13.0
     */
    public function setPrompt(?string $prompt): self {
        $this->prompt = $prompt;
        return $this;
    }

    /**
     * @return string[]|null See {@link $stop}
     * @since 1.13.0
     */
    public function getStop(bool $prepareTemplate = false): ?array {
        if (!$this->stop) {
            return null;
        }

        if (!$prepareTemplate) {
            return $this->stop;
        }

        return array_map(function(string $v) {
            return $this->prepareTemplateText($v);
        }, $this->stop);
    }

    /**
     * @param string[]|null $stop See {@link $stop}
     * @return self
     * @since 1.13.0
     */
    public function setStop(?array $stop): self {
        $this->stop = $stop;
        return $this;
    }

    /**
     * @param bool $prepareTemplate `true` if the input template's short codes should be replaced with their values.
     *                              Otherwise, `false`.
     * @return string|null See {@link $input}
     * @since 1.13.0
     */
    public function getInput(bool $prepareTemplate = false): ?string {
        if ($this->input === null) {
            return null;
        }

        return $prepareTemplate
            ? $this->prepareTemplateText($this->input)
            : $this->input;
    }

    /**
     * @param string|null $input See {@link $input}
     * @return self
     * @since 1.13.0
     */
    public function setInput(?string $input): self {
        $this->input = $input;
        return $this;
    }

    /**
     * @param bool $prepareTemplate `true` if the instruction template's short codes should be replaced with their
     *                              values. Otherwise, `false`.
     * @return string|null See {@link $instructions}
     * @since 1.13.0
     */
    public function getInstructions(bool $prepareTemplate = false): ?string {
        if ($this->instructions === null) {
            return null;
        }

        return $prepareTemplate
            ? $this->prepareTemplateText($this->instructions)
            : $this->instructions;
    }

    /**
     * @param string|null $instructions See {@link $instructions}
     * @return self
     * @since 1.13.0
     */
    public function setInstructions(?string $instructions): self {
        $this->instructions = $instructions;
        return $this;
    }

    /**
     * @return float|null See {@link $temperature}
     * @since 1.13.0
     */
    public function getTemperature(): ?float {
        return $this->temperature;
    }

    /**
     * @param string|float|null $temperature See {@link $temperature}. If this is a string, it must be numeric.
     * @return self
     * @since 1.13.0
     */
    public function setTemperature($temperature): self {
        if ($temperature === null || is_float($temperature)) {
            $this->temperature = $temperature;
            return $this;
        }

        if (!is_numeric($temperature)) {
            $this->temperature = null;

            Informer::addInfo(sprintf(
                _wpcc('Temperature parameter of %1$s short code must be numeric.')
                    . ' ' . _wpcc('The non-numeric value "%2$s" is ignored.'),
                $this->getShortCodeNameWithBrackets(),
                $temperature
            ))->addAsLog();
            return $this;
        }

        $this->temperature = (float) $temperature;
        return $this;
    }

    /**
     * @return int|null See {@link $maxLength}
     * @since 1.13.0
     */
    public function getMaxLength(): ?int {
        return $this->maxLength;
    }

    /**
     * @param string|int|float|null $maxLength See {@link $maxLength}. If this is a string, it must be numeric.
     * @return self
     * @since 1.13.0
     */
    public function setMaxLength($maxLength): self {
        if ($maxLength === null || is_int($maxLength)) {
            $this->maxLength = $maxLength;
            return $this;
        }

        if (!is_numeric($maxLength)) {
            $this->maxLength = null;

            Informer::addInfo(sprintf(
                _wpcc('Maximum length parameter of %1$s short code must be numeric.')
                    . ' ' . _wpcc('The non-numeric value "%2$s" is ignored.'),
                $this->getShortCodeNameWithBrackets(),
                $maxLength
            ))->addAsLog();
            return $this;
        }

        $this->maxLength = (int) $maxLength;
        return $this;
    }

    /**
     * @return array<string, string>|null See {@link $testShortCodeValues}
     * @since 1.13.0
     */
    public function getTestShortCodeValues(): ?array {
        return $this->testShortCodeValues;
    }

    /**
     * @param array<string, string>|null $testShortCodeValues See {@link $testShortCodeValues}
     * @return self
     * @since 1.13.0
     */
    public function setTestShortCodeValues(?array $testShortCodeValues): self {
        $this->testShortCodeValues = $testShortCodeValues;
        return $this;
    }

    /**
     * @return string String representation of the short code, such as `[openai-gpt mode="complete"]`
     * @since 1.13.0
     */
    public function toString(): string {
        /** @var array<string, string|array> $config */
        $config = [];
        /** @var array<string, string> $config */
        $customDesc = [];
        $config[ShortCodeAttributes::MODE]  = $this->getMode();
        $config[ShortCodeAttributes::MODEL] = $this->getModelName();

        $messages = $this->getMessages();
        if ($messages) {
            $messagesArr = [];
            $messageDescArr = [];
            foreach($messages as $message) {
                $messagesArr[] = $message->toArray();
                $messageDescArr[] = sprintf('(%1$s:%2$s)', $message->getRole(), $message->getContent());
            }
            $config[ShortCodeAttributes::MESSAGES] = $messagesArr;
            $customDesc[ShortCodeAttributes::MESSAGES] = sprintf('(%1$s)', implode('|', $messageDescArr));
        }

        $prompt = $this->getPrompt();
        if ($prompt !== null) {
            $config[ShortCodeAttributes::PROMPT] = $prompt;
        }

        $stop = $this->getStop();
        if ($stop) {
            $config[ShortCodeAttributes::STOP] = $stop;
            $customDesc[ShortCodeAttributes::STOP] = sprintf('(%1$s)', implode('|', $stop));
        }

        $input = $this->getInput();
        if ($input !== null) {
            $config[ShortCodeAttributes::INPUT] = $input;
        }

        $instructions = $this->getInstructions();
        if ($instructions !== null) {
            $config[ShortCodeAttributes::INSTRUCTIONS] = $instructions;
        }

        $temperature = $this->getTemperature();
        if ($temperature !== null) {
            $config[ShortCodeAttributes::TEMPERATURE] = $temperature;
        }

        $maxLength = $this->getMaxLength();
        if ($maxLength !== null) {
            $config[ShortCodeAttributes::MAX_LENGTH] = $maxLength;
        }

        $testShortCodeValues = $this->getTestShortCodeValues();
        if ($testShortCodeValues) {
            $config[ShortCodeAttributes::TEST_SHORT_CODE_VALUES] = $testShortCodeValues;
        }

        //

        $allDesc = array_merge($config, $customDesc);
        $descPrepared = [];
        foreach($allDesc as $key => $value) {
            if (!is_scalar($value)) continue;
            $descPrepared[] = sprintf('(%1$s:%2$s)', $key, $value);
        }

        $attributes = [
            ShortCodeAttributes::DESC   => $this->escapeShortCodeText(implode('|', $descPrepared)),
            ShortCodeAttributes::CONFIG => base64_encode(json_encode($config) ?: ''),
        ];

        $attributesPrepared = [];
        foreach($attributes as $key => $value) {
            $attributesPrepared[] = sprintf('%1$s="%2$s"', $key, $value);
        }

        return sprintf('[%1$s %2$s]',
            ShortCodeName::OPENAI_GPT,
            implode(' ', $attributesPrepared)
        );
    }

    /*
     *
     */

    /**
     * @return string The actual value of the short code, retrieved from OpenAI according to the specified values
     * @since 1.13.0
     */
    public function apply(): string {
        $mode = $this->getMode();

        if ($mode === ModelMode::CHAT) {
            return $this->applyChatCompletion();

        } else if ($mode === ModelMode::COMPLETE) {
            return $this->applyCompletion();

        } else if ($mode === ModelMode::INSERT) {
            return $this->applyInsert();

        } else if ($mode === ModelMode::EDIT) {
            return $this->applyEdit();
        }

        Informer::addInfo(sprintf(
            _wpcc('Specified mode "%1$s" does not exist for %2$s short code. The short code is ignored.'),
            $mode,
            $this->getShortCodeNameWithBrackets(),
        ));
        return '';
    }

    /**
     * Replaces the short codes used in the templates with their values
     *
     * @return self
     * @since 1.13.0
     */
    public function injectValues(): self {
        return $this
            ->setInput($this->getInput(true))
            ->setInstructions($this->getInstructions(true))
            ->setMessages($this->getMessages(true))
            ->setPrompt($this->getPrompt(true))
            ->setStop($this->getStop(true));
    }

    /*
     * PROTECTED HELPERS
     */

    /**
     * Applies "chat" mode with the specified short code parameters
     *
     * @return string Result of the chat completion operation
     * @since 1.13.0
     */
    protected function applyChatCompletion(): string {
        $result = $this->getClient()->postChatCompletion(
            $this->getModelName(),
            $this->getMessages(true) ?? [],
            $this->getMaxLength(),
            $this->getStop(true),
            $this->getTemperature()
        );

        return $result === null
            ? ''
            : $result;
    }

    /**
     * Applies "complete" mode with the specified short code parameters
     *
     * @return string Result of the completion operation
     * @since 1.13.0
     */
    protected function applyCompletion(): string {
        $result = $this->getClient()->postCompletion(
            $this->getModelName(),
            $this->getPrompt(true) ?? '',
            $this->getMaxLength(),
            $this->getStop(true),
            $this->getTemperature()
        );

        return $result === null
            ? ''
            : $result;
    }

    /**
     * Applies "insert" mode with the specified short code parameters
     *
     * @return string Result of the insertion operation
     * @since 1.13.0
     */
    protected function applyInsert(): string {
        $insertReference = self::INSERT_REFERENCE;
        $prompt = $this->getPrompt(true) ?? '';

        // Split the prompt from the insert reference
        $parts = explode($insertReference, $prompt);

        // There must be exactly 1 insert reference in the prompt.
        $referenceCount = count($parts) - 1;
        if ($referenceCount !== 1) {
            Informer::addInfo(sprintf(
                _wpcc('The prompt must contain exactly one %1$s for insertion. It contains %2$d of them. [Prompt: "%3$s"]'),
                $insertReference,
                $referenceCount,
                Str::limit($prompt, 300)
            ))->addAsLog();
            return '';
        }

        $actualPrompt = $parts[0] ?? null;
        $suffix       = $parts[1] ?? null;

        // This will never happen. We do this to be on the safe side.
        if (!is_string($actualPrompt) || !is_string($suffix)) {
            Informer::addInfo(sprintf(
                _wpcc('Suffix could not be extracted from the prompt for the insertion request. [Prompt: "%1$s"]'),
                Str::limit($prompt, 300)
            ))
                ->addAsLog();
            return '';
        }

        $result = $this->getClient()->postCompletion(
            $this->getModelName(),
            $actualPrompt,
            $this->getMaxLength(),
            $this->getStop(true),
            $this->getTemperature(),
            $suffix,
        );

        return $result === null
            ? ''
            : $result;
    }

    /**
     * Applies "edit" mode with the specified short code parameters
     *
     * @return string Result of the edit operation
     * @since 1.13.0
     */
    protected function applyEdit(): string {
        $result = $this->getClient()->postEdit(
            $this->getModelName(),
            $this->getInput(true) ?? '',
            $this->getInstructions(true) ?? '',
            $this->getTemperature()
        );

        return $result === null
            ? ''
            : $result;
    }

    /*
     *
     */

    /**
     * Escapes the special characters in a text so that it does not break the short code parser
     *
     * @param string $text The text whose special characters will be escaped
     * @return string The text with its special characters escaped
     * @since 1.13.0
     */
    protected function escapeShortCodeText(string $text): string {
        foreach(self::getTextEscapeMap() as $char => $escaped) {
            $text = Str::replace($char, $escaped, $text);
        }

        return $text;
    }

    /**
     * @param string $text The template text to be prepared. This text can contain other short codes.
     * @return string The text whose short codes are replaced with their values
     * @since 1.13.0
     */
    protected function prepareTemplateText(string $text): string {
        return $this->getApplier()->apply($text);
    }

    /**
     * @return string Returns "[openai-gpt]"
     * @since 1.13.0
     */
    protected function getShortCodeNameWithBrackets(): string {
        return '[' . ShortCodeName::OPENAI_GPT . ']';
    }

    /*
     * PROTECTED STATIC HELPERS
     */

    /**
     * @return array<string, string> The keys are characters. The values are the escaped characters.
     * @since 1.13.0
     */
    protected static function getTextEscapeMap(): array {
        if (self::$textEscapeMap === null) {
            self::$textEscapeMap = [
                '"' => '&#34;',
                '[' => '&#91;',
                ']' => '&#93;',
                '/' => '&#47;',
            ];
        }

        return self::$textEscapeMap;
    }

}