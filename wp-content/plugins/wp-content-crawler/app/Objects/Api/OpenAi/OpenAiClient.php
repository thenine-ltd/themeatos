<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 04/02/2023
 * Time: 11:46
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use WPCCrawler\Objects\Api\OpenAi\Objects\ChatMessage;
use WPCCrawler\Objects\Api\OpenAi\Objects\Model;
use WPCCrawler\Objects\Api\OpenAi\Objects\ModelRegistry;
use WPCCrawler\Objects\Api\OpenAi\Tokenizer\Gpt3Tokenizer;
use WPCCrawler\Objects\Enums\InformationType;
use WPCCrawler\Objects\Informing\Information;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Utils;

/**
 * Used to interact with OpenAI's API
 */
class OpenAiClient {

    // TODO: Handle rate limits

    /*
        Rate limits: https://help.openai.com/en/articles/5955598-is-api-usage-subject-to-any-rate-limits
        Documentation: https://platform.openai.com/docs/api-reference/introduction
     */

    /** @var OpenAiClient|null */
    private static $testInstance = null;

    const DEFAULT_TEMPERATURE = 0.7;
    const DEFAULT_CHAT_TEMPERATURE = 1;

    /** @var string The secret key retrieved from OpenAI, used to authenticate with the API. */
    private $secretKey;

    /** @var Client|null */
    private $client = null;

    /** @var bool `true` if predefined responses must be returned instead of making a request to the API. */
    private $usePredefinedResponses = false;

    const BASE_URL = 'https://api.openai.com/v1/'; // Make sure this ends with a forward slash
    const ENDPOINT_MODELS           = 'models';
    const ENDPOINT_COMPLETIONS      = 'completions';
    const ENDPOINT_EDITS            = 'edits';
    const ENDPOINT_CHAT_COMPLETIONS = 'chat/completions';

    /**
     * **IMPORTANT:** Prefer {@link OpenAiClient::newInstance()} outside the unit tests, so that the client can be
     * replaced during unit tests.
     *
     * @param string $secretKey See {@link $secretKey}
     * @since 1.13.0
     */
    public function __construct(string $secretKey) {
        $this->secretKey = $secretKey;
    }

    /**
     * @return bool See {@link $usePredefinedResponses}
     * @since 1.13.0
     */
    public function isUsePredefinedResponses(): bool {
        return $this->usePredefinedResponses;
    }

    /**
     * @param bool $usePredefinedResponses See {@link $usePredefinedResponses}
     * @return self
     * @since 1.13.0
     */
    public function setUsePredefinedResponses(bool $usePredefinedResponses): OpenAiClient {
        $this->usePredefinedResponses = $usePredefinedResponses;
        return $this;
    }

    /**
     * @return Model[] Models available in OpenAI API
     * @see https://platform.openai.com/docs/api-reference/models/list
     * @since 1.13.0
     */
    public function getModels(): array {
        try {
            $response = $this->getClient()->get(self::ENDPOINT_MODELS);

        } catch (GuzzleException $e) {
            $this->logException($e, _wpcc("OpenAI models could not be retrieved."));
            return [];
        }

        $body = $this->getResponseBody($response);
        if ($body === null) {
            return [];
        }

        $data = $body["data"] ?? null;
        if ($data === null) {
            Informer::addInfo(_wpcc('No models are retrieved from OpenAI.'))
                ->addAsLog();
            return [];
        }

        if (!is_array($data)) {
            $data = [$data];
        }

        $models = array_filter(array_map(function ($item) {
            return Model::fromApi($item);
        }, $data));

        // Sort by rank in descending order, boost the one that has an alphabetically bigger ID when the ranks are the
        // same
        usort($models, function(Model $b, Model $a) {
            if ($a->getRank() > $b->getRank()) return 1;
            if ($a->getRank() < $b->getRank()) return -1;
            return strcmp($a->getId(), $b->getId());
        });

        return array_values($models);
    }

    /**
     * Make a "chat completion" request
     *
     * @param string        $modelName     The name of the AI model to be used for chat completion
     * @param ChatMessage[] $messages      The chat messages to be sent to the API to get a response
     * @param int|null      $maxTokens     The maximum number of tokens to generate in the completion. The token count
     *                                     of your prompt plus max_tokens cannot exceed the model's context length.
     *                                     Most models have a context length of 2048 tokens (except for the newest
     *                                     models, which support 4096). If this is `null`, this value will be
     *                                     automatically calculated and validated.
     * @param string[]|null $stopSequences Up to 4 sequences where the API will stop generating further tokens. The
     *                                     returned text will not contain the stop sequence.
     * @param float|null    $temperature   What sampling temperature to use, between 0 and 2. Higher values mean the
     *                                     model will take more risks. Try 0.9 for more creative applications, and 0
     *                                     (argmax sampling) for ones with a well-defined answer. Defaults to
     *                                     {@link DEFAULT_CHAT_TEMPERATURE}.
     * @return string|null
     * @see   https://platform.openai.com/docs/api-reference/chat
     * @since 1.13.0
     */
    public function postChatCompletion(string $modelName, array $messages, ?int $maxTokens = null,
                                       ?array $stopSequences = null, ?float $temperature = null): ?string {
        if ($this->isUsePredefinedResponses()) {
            return $this->createPredefinedResponse();
        }

        $model = $this->getModelByName($modelName);
        if (!$model) {
            return null;
        }

        $texts = [];
        $messagesPrepared = array_map(function(ChatMessage $message) use (&$texts) {
            // The role and content are used to calculate the token length by the API. So, we need to add them both.
            $texts[] = $message->getRole();
            $texts[] = $message->getContent();

            // Return the array representation of the message.
            return $message->toArray();
        }, $messages);

        $maxTokens = $maxTokens ?? $model->calculateMaxTokenLength($texts);
        if (!$this->validateMaxTokenLength($maxTokens, $model, $texts)) {
            return null;
        }

        try {
            $response = $this->getClient()->post(self::ENDPOINT_CHAT_COMPLETIONS, [
                RequestOptions::JSON => [
                    'model'       => $model->getId(),
                    'messages'    => $messagesPrepared,
                    'max_tokens'  => $maxTokens,
                    'temperature' => $temperature ?? self::DEFAULT_CHAT_TEMPERATURE,
                    'n'           => 1, // Generate only 1 completion
                    'stop'        => $this->prepareStopSequences($stopSequences, 4),
                ],
            ]);

        } catch (GuzzleException $e) {
            $messageTexts = array_map(function(ChatMessage $message) {
                return $message->getContent();
            }, $messages);

            $this->logException($e, sprintf(
                _wpcc('Chat completion cannot be retrieved. [Messages: "%1$s"]'),
                Str::limit(implode(', ', $messageTexts), 300)
            ));
            return null;
        }

        $body = $this->getResponseBody($response);
        if ($body === null) {
            return null;
        }

        $result = Utils::array_get($body, 'choices.0.message.content');
        if (!is_string($result)) {
            Informer::addInfo(_wpcc("Generated chat response could not be retrieved from OpenAI's response."))
                ->addAsLog();
            return null;
        }

        return trim($result);
    }
    
    /**
     * Make a "completion" request
     *
     * @param string        $modelName     The name of the AI model to be used for completion
     * @param string        $prompt        The prompt for text completion
     * @param int|null      $maxTokens     The maximum number of tokens to generate in the completion. The token count
     *                                     of your prompt plus max_tokens cannot exceed the model's context length.
     *                                     Most models have a context length of 2048 tokens (except for the newest
     *                                     models, which support 4096). If this is `null`, this value will be
     *                                     automatically calculated and validated.
     * @param string[]|null $stopSequences Up to 4 sequences where the API will stop generating further tokens. The
     *                                     returned text will not contain the stop sequence.
     * @param float|null    $temperature   What sampling temperature to use. Higher values mean the model will take
     *                                     more risks. Try 0.9 for more creative applications, and 0 (argmax sampling)
     *                                     for ones with a well-defined answer. Defaults to {@link DEFAULT_TEMPERATURE}.
     * @param string|null   $suffix        The suffix that comes after a completion of inserted text.
     * @return string|null
     * @see   https://platform.openai.com/docs/api-reference/completions/create
     * @since 1.13.0
     */
    public function postCompletion(string $modelName, string $prompt, ?int $maxTokens = null,
                                   ?array $stopSequences = null, ?float $temperature = null, ?string $suffix = null): ?string {
        if ($this->isUsePredefinedResponses()) {
            return $this->createPredefinedResponse();
        }

        $model = $this->getModelByName($modelName);
        if (!$model) {
            return null;
        }

        $maxTokens = $maxTokens ?? $model->calculateMaxTokenLength([$prompt]);
        if (!$this->validateMaxTokenLength($maxTokens, $model, [$prompt])) {
            return null;
        }

        try {
            $response = $this->getClient()->post(self::ENDPOINT_COMPLETIONS, [
                RequestOptions::JSON => [
                    'model'       => $model->getId(),
                    'prompt'      => $prompt,
                    'max_tokens'  => $maxTokens,
                    'temperature' => $temperature ?? self::DEFAULT_TEMPERATURE,
                    'suffix'      => $suffix,
                    'n'           => 1, // Generate only 1 completion for each prompt,
                    'stop'        => $this->prepareStopSequences($stopSequences, 4),
                ],
            ]);

        } catch (GuzzleException $e) {
            $this->logException($e, sprintf(
                _wpcc('Completion cannot be retrieved. [Prompt: "%1$s"]'),
                Str::limit($prompt, 300)
            ));
            return null;
        }

        return $this->getFirstChoiceTextFromResponse($response);
    }

    /**
     * Make an "edit" request
     *
     * @param string     $modelName        The name of the AI model to be used for edits
     * @param string     $input            The input text to use as a starting point for the edit
     * @param string     $instructions     The instruction that tells the model how to edit the input
     * @param float|null $temperature      What sampling temperature to use. Higher values mean the model will take
     *                                     more risks. Try 0.9 for more creative applications, and 0 (argmax sampling)
     *                                     for ones with a well-defined answer. Defaults to
     *                                     {@link self::DEFAULT_TEMPERATURE}.
     * @return string|null
     * @since 1.13.0
     */
    public function postEdit(string $modelName, string $input, string $instructions, ?float $temperature = null): ?string {
        if ($this->isUsePredefinedResponses()) {
            return $this->createPredefinedResponse();
        }

        $model = $this->getModelByName($modelName);
        if (!$model) {
            return null;
        }

        try {
            $response = $this->getClient()->post(self::ENDPOINT_EDITS, [
                RequestOptions::JSON => [
                    'model'       => $model->getId(),
                    'input'       => $input,
                    'instruction' => $instructions,
                    'temperature' => $temperature ?? self::DEFAULT_TEMPERATURE,
                    'n'           => 1, // Generate only 1 edit
                ],
            ]);

        } catch (GuzzleException $e) {
            $this->logException($e, sprintf(
                _wpcc('Edits cannot be retrieved. [Input: "%1$s"] [Instructions: "%2$s"]'),
                Str::limit($input, 300),
                Str::limit($instructions, 300)
            ));
            return null;
        }

        return $this->getFirstChoiceTextFromResponse($response);
    }

    /*
     * HELPER METHODS
     */

    /**
     * Prepares the stop sequences by limiting their numbers
     *
     * @param string[]|null $stopSequences The stop sequences to be prepared
     * @param int           $max           How many stop sequences at maximum can be returned
     * @return string[]|null If the given stop sequences variable is `null`, returns `null`. Otherwise, returns the
     *                                     stop sequences by limiting the quantity to the given maximum quantity.
     * @since 1.13.0
     */
    protected function prepareStopSequences(?array $stopSequences, int $max = 4): ?array {
        return $stopSequences
            ? array_slice(array_values($stopSequences), 0, $max)
            : null;
    }

    /**
     * Extract the first "choice" item's text from OpenAI API response
     *
     * @param ResponseInterface $response Response retrieved from OpenAI
     * @return string|null The text of the first "choice" item available in the response. Otherwise, `null`.
     * @since 1.13.0
     */
    protected function getFirstChoiceTextFromResponse(ResponseInterface $response): ?string {
        $body = $this->getResponseBody($response);
        if ($body === null) {
            return null;
        }

        $choices = $body["choices"] ?? null;
        if (!is_array($choices) || !$choices) {
            Informer::addInfo(_wpcc('No choices are retrieved from OpenAI response.'))
                ->addAsLog();
            return null;
        }

        $firstChoice = array_values($choices)[0];
        $result = $firstChoice["text"] ?? null;
        if ($result === null) {
            Informer::addInfo(_wpcc("Generated text could not be retrieved from OpenAI's response."))
                ->addAsLog();
            return null;
        }

        return trim($result);
    }

    /**
     * Get the body of an OpenAI response
     *
     * @param ResponseInterface $response The API response retrieved from OpenAI
     * @return array|null If the data could be retrieved, it is returned as an associative array. Otherwise, `null` is
     *                    returned.
     * @since 1.13.0
     */
    protected function getResponseBody(ResponseInterface $response): ?array {
        // Get the response text
        $responseText = $response->getBody()->getContents();

        // Parse it to JSON
        $responseJson = json_decode($responseText, true);

        // Make sure the response is parsed to JSON correctly.
        if (!is_array($responseJson)) {
            $message = _wpcc('The response retrieved from OpenAI API could not be parsed to JSON. Message: %1$s');
            $info = new Information($message, json_last_error_msg(), InformationType::ERROR);
            Informer::add($info->addAsLog());
            return null;
        }

        return $responseJson;
    }

    /**
     * @param Exception|GuzzleException $e       The exception to be logged
     * @param string                    $message A short explanation about the error, to be shown to the user.
     * @since 1.13.0
     */
    protected function logException($e, string $message): void {
        $exception = $e instanceof Exception
            ? $e
            : null;

        $info = new Information(
            $message,
            $exception
                ? $exception->getMessage()
                : '',
            InformationType::ERROR,
        );

        Informer::add($info->setException($exception)->addAsLog());
    }

    /**
     * Check if the maximum token count is valid. This method adds an information message when the token count is not
     * valid.
     *
     * @param int      $maxTokens Maximum number of tokens that the API can generate
     * @param Model    $model     The model that will generate the response
     * @param string[] $texts     The texts that will be used by the model to generate the response
     * @return bool `true` if the maximum token length is valid for the model with the given prompt. Otherwise, `false`.
     * @since 1.13.0
     */
    protected function validateMaxTokenLength(int $maxTokens, Model $model, array $texts): bool {
        if ($maxTokens > 0) {
            return true;
        }

        Informer::addInfo(sprintf(
            _wpcc('Maximum token count for model "%1$s" is %2$d with the given texts.')
                . ' ' . _wpcc('You should reduce the length of the texts to increase the maximum token count that the model can use to generate a response.')
                . ' ' . _wpcc('[Text token count: %3$d], [Model context length: %4$d], [Remaining tokens: %5$d], [Texts: "%6$s"]'),
            $model->getId(),
            $maxTokens,
            Gpt3Tokenizer::getInstance()->getTokenCount($texts),
            $model->getContextLength(),
            $maxTokens,
            Str::limit(implode(', ', $texts), 600),
        ))
            ->addAsLog();
        return false;
    }

    /**
     * Get an OpenAI model by its name (ID). This method adds an information message when the model is not found.
     *
     * @param string $modelName Name (ID) of an OpenAI model
     * @return Model|null If the model is found in {@link ModelRegistry}, it will be returned. Otherwise, `null` is
     *                    returned.
     * @since 1.13.0
     */
    protected function getModelByName(string $modelName): ?Model {
        $modelName = trim($modelName);
        $model = ModelRegistry::getInstance()->getModelByName($modelName);
        if (!$model) {
            Informer::addInfo(sprintf(_wpcc('OpenAI model "%s" is not available in the plugin.'), $modelName))
                ->addAsLog();
            return null;
        }

        return $model;
    }

    /**
     * @return Client The client that is configured to interact with OpenAI API
     * @since 1.13.0
     */
    private function getClient(): Client {
        if (!$this->client) {
            $this->client = $this->createClient();
        }

        return $this->client;
    }

    /**
     * @return Client A new client that is configured to interact with OpenAI API
     * @since 1.13.0
     */
    protected function createClient(): Client {
        return new Client([
            'base_uri' => self::BASE_URL,
            RequestOptions::HEADERS => [
                'Content-Type'  => 'application/json',
                'Authorization' => sprintf('Bearer %s', $this->getSecretKey()),
            ],
        ]);
    }

    /**
     * @return string A text that explains why a predefined response is returned
     * @since 1.13.0
     */
    protected function createPredefinedResponse(): string {
        return _wpcc('This is a predefined OpenAI GPT response. An API request is not made to avoid unnecessary costs during tests.');
    }

    /*
     * PUBLIC GETTERS
     */

    /**
     * @return string See {@link $secretKey}
     * @since 1.13.0
     */
    public function getSecretKey(): string {
        return $this->secretKey;
    }

    /*
     * STATIC METHODS
     */

    /**
     * @param string $secretKey See {@link OpenAiClient::__construct()}
     * @return OpenAiClient
     * @since 1.13.0
     */
    public static function newInstance(string $secretKey): OpenAiClient {
        return self::$testInstance ?: new OpenAiClient($secretKey);
    }

    /**
     * Set the instance that will be returned by {@link OpenAiClient::newInstance()}. This method is intended to be used
     * in unit tests, to replace the client with a mock client.
     *
     * @param OpenAiClient|null $testInstance If this is not `null`, this will be returned by
     *                                        {@link OpenAiClient::newInstance()} instead of creating a new instance. If
     *                                        this is null, {@link OpenAiClient::newInstance()} creates a new instance.
     * @since 1.13.0
     */
    public static function setTestInstance(?OpenAiClient $testInstance): void {
        self::$testInstance = $testInstance;
    }

}