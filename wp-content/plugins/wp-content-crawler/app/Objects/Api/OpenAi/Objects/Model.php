<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 04/02/2023
 * Time: 12:32
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi\Objects;

use DateTime;
use Exception;
use Illuminate\Support\Str;
use WPCCrawler\Environment;
use WPCCrawler\Interfaces\Arrayable;
use WPCCrawler\Objects\Api\OpenAi\Enums\ModelMode;
use WPCCrawler\Objects\Api\OpenAi\Enums\ModelType;
use WPCCrawler\Objects\Api\OpenAi\Tokenizer\Gpt3Tokenizer;

/**
 * An OpenAI model
 */
class Model implements Arrayable {

    const DEFAULT_CONTEXT_LENGTH = 2048;

    /** @var string */
    private $id;

    /** @var DateTime|null */
    private $createdAt = null;

    /** @var int */
    private $rank;

    /**
     * @var int|null The context length of the model. The context length is the sum of prompt token count and response
     *      token count. The values can be retrieved from OpenAI Playground, by observing the maximum value of the
     *      "Maximum length" slider while selecting different models. The API does not provide this value :/
     */
    private $contextLength = null;

    /**
     * @var string[] An array of constants defined in {@link ModelMode}. Types of operations this model can be used for.
     *               The modes of the model can be retrieved from OpenAI Playground by selecting different modes and
     *               observing the available models for that mode.
     */
    private $modes = [];

    /**
     * @var string[] An array of constants defined in {@link ModelType}.
     */
    private $types = [];

    /**
     * @param string   $id      ID of the OpenAI model
     * @param int|null $created The creation epoch of the model
     * @param int      $rank    The rank of the model among the other models. This will be used when sorting the
     *                          models. The highest-rank model is shown at the top.
     * @since 1.13.0
     */
    public function __construct(string $id, ?int $created = null, int $rank = 0) {
        $this->id = $id;

        if ($created !== null) {
            try {
                $this->createdAt = new DateTime("@$created");
            } catch (Exception $e) {
                // Do nothing
            }
        }

        $this->rank = $rank;
    }

    /**
     * @return string
     * @since 1.13.0
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * @return DateTime|null
     * @since 1.13.0
     */
    public function getCreatedAt(): ?DateTime {
        return $this->createdAt;
    }

    /**
     * @return int
     * @since 1.13.0
     */
    public function getRank(): int {
        return $this->rank;
    }

    /**
     * @param int|null $contextLength See {@link Model::$contextLength}
     * @param array    $modes         See {@link Model::$modes}
     * @param array    $types         See {@link Model::$types}
     * @return self
     * @since 1.13.0
     */
    public function set(?int $contextLength, array $modes, array $types): self {
        return $this
            ->setContextLength($contextLength)
            ->setModes($modes)
            ->setTypes($types);
    }

    /**
     * @return int See {@link Model::$contextLength}. If the length is not available, minimum context length of all
     *             models is returned, which is 2048.
     * @since 1.13.0
     */
    public function getContextLength(): int {
        return $this->contextLength !== null
            ? $this->contextLength
            : self::DEFAULT_CONTEXT_LENGTH;
    }

    /**
     * @param int|null $contextLength See {@link Model::$contextLength}
     * @return self
     * @since 1.13.0
     */
    public function setContextLength(?int $contextLength): self {
        $this->contextLength = $contextLength;
        return $this;
    }

    /**
     * @return string[] See {@link Model::$modes}
     * @since 1.13.0
     */
    public function getModes(): array {
        return $this->modes;
    }

    /**
     * @param string[] $modes See {@link Model::$modes}
     * @return self
     * @since 1.13.0
     */
    public function setModes(array $modes): self {
        $this->modes = $modes;
        return $this;
    }

    /**
     * @return string[] See {@link Model::$types}
     * @since 1.13.0
     */
    public function getTypes(): array {
        return $this->types;
    }

    /**
     * @param string[] $types See {@link Model::$types}
     * @return self
     * @since 1.13.0
     */
    public function setTypes(array $types): self {
        $this->types = $types;
        return $this;
    }

    /**
     * @param string[] $texts The texts that will be sent to the API for this model
     * @return int The number of tokens that can be used for generation. If this is 0, it means there is no room for
     *             generation.
     * @since 1.13.0
     */
    public function calculateMaxTokenLength(array $texts): int {
        // From OpenAI: "The token count of your prompt plus max_tokens cannot exceed the model's context length."
        // So, (max tokens) = (context length) - (prompt token count)
        $promptTokenCount = Gpt3Tokenizer::getInstance()->getTokenCount($texts);
        return max(0, $this->getContextLength() - $promptTokenCount);
    }

    public function toArray(): array {
        return [
            'id'            => $this->getId(),
            'rank'          => $this->getRank(),
            'contextLength' => $this->getContextLength(),
            'modes'         => $this->getModes(),
            'types'         => $this->getTypes(),
            'createdAt'     => $this->getCreatedAt() !== null
                ? $this->getCreatedAt()->format(Environment::mysqlDateFormat())
                : null,
        ];
    }

    /*
     * STATIC METHODS
     */

    /**
     * Create a {@link Model} from the model data retrieved from OpenAI API
     *
     * @param array $item A model object retrieved from OpenAI API
     * @return Model|null Returns a new {@link Model} if it could be created from the provided item. Otherwise, returns
     *                    `null`
     * @since 1.13.0
     */
    public static function fromApi(array $item): ?Model {
        $id = $item["id"] ?? null;
        if ($id === null) {
            return null;
        }

        $created = $item["created"] ?? null;
        $created = is_int($created)
            ? $created
            : null;

        $rank = 0;
        if (Str::startsWith($id, 'text-'))  $rank += 10;

        if (preg_match('/^text-([a-zA-Z]+)-([0-9]+)$/', $id, $matches) === 1) {
            $modelName   = $matches[1];
            $modelNumber = (int) Str::lower($matches[2]);

            $a = mb_ord("a", 'UTF-8');
            $modelNameFirstChar = mb_ord($modelName[0], 'UTF-8');
            $alphabeticOrder = $modelNameFirstChar - $a;

            $rank += $alphabeticOrder + 1 + $modelNumber;
        }

        if (Str::contains($id, ':'))          $rank -= 1;
        if (Str::contains($id, 'beta'))       $rank -= 1;
        if (Str::contains($id, 'edit'))       $rank -= 1;
        if (Str::contains($id, 'insert'))     $rank -= 1;
        if (Str::contains($id, 'code'))       $rank -= 1;
        if (Str::contains($id, 'search'))     $rank -= 1;
        if (Str::contains($id, 'similarity')) $rank -= 1;
        if (Str::contains($id, 'query'))      $rank -= 1;

        if (Str::contains($id, 'deprecated')) $rank -= 50;

        return new Model($id, $created, $rank);
    }

}