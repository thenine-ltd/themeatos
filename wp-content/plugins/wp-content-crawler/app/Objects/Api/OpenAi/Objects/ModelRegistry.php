<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 05/02/2023
 * Time: 17:22
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Api\OpenAi\Objects;

use WPCCrawler\Interfaces\Arrayable;
use WPCCrawler\Objects\Api\OpenAi\Enums\ModelMode;
use WPCCrawler\Objects\Api\OpenAi\Enums\ModelName;
use WPCCrawler\Objects\Api\OpenAi\Enums\ModelType;

class ModelRegistry implements Arrayable {

    /** @var ModelRegistry|null */
    private static $instance = null;

    /**
     * @return ModelRegistry The registry instance
     * @since 1.13.0
     */
    public static function getInstance(): ModelRegistry {
        if (self::$instance === null) {
            self::$instance = new ModelRegistry();
        }

        return self::$instance;
    }

    /** @var array<string, Model>|null */
    private $registry = null;

    /** This is a singleton. Use {@link ModelRegistry::getInstance()}. */
    private function __construct() { }

    /**
     * @param string $modelName One of the constants defined in {@link ModelName}
     * @return Model|null If there is a model with the given name, it is returned. Otherwise, `null` is returned.
     * @since 1.13.0
     */
    public function getModelByName(string $modelName): ?Model {
        return $this->getRegistry()[$modelName] ?? null;
    }

    /**
     * @return Model[] All the models available in the registry
     * @since 1.13.0
     */
    public function getModels(): array {
        return array_values($this->getRegistry());
    }

    /**
     * @param string $mode One of the constants defined in {@link ModelMode}
     * @return Model[] The models that have the given mode
     * @since 1.13.0
     */
    public function getModelsByMode(string $mode): array {
        return array_filter($this->getModels(), function(Model $model) use ($mode) {
            return in_array($mode, $model->getModes());
        });
    }

    /**
     * @return array<string, Model> The model registry. The keys are model names.
     * @since 1.13.0
     */
    public function getRegistry(): array {
        if ($this->registry === null) {
            $this->registry = $this->createRegistry();
        }

        return $this->registry;
    }

    public function toArray(): array {
        return array_map(function(Model $model) {
            return $model->toArray();
        }, $this->getModels());
    }

    /*
     * HELPERS
     */

    /**
     * @return array<string, Model> A new model registry. The keys are model names.
     * @since 1.13.0
     */
    protected function createRegistry(): array {
        $models = $this->createModels();

        /** @var array<string, Model> $registry */
        $registry = [];
        foreach($models as $model) {
            $registry[$model->getId()] = $model;
        }

        return $registry;
    }

    /**
     * @return Model[] New instances of all the OpenAI models available at OpenAI Playground
     * @see https://platform.openai.com/playground
     * @since 1.13.0
     */
    protected function createModels(): array {
        return [
            (new Model(ModelName::GPT_35_TURBO_INSTRUCT))
                ->set(4000, [ModelMode::COMPLETE], [ModelType::GPT3]),
            (new Model(ModelName::TEXT_DAVINCI_003))
                ->set(4000, [ModelMode::COMPLETE, ModelMode::INSERT], [ModelType::GPT3]),
            (new Model(ModelName::TEXT_CURIE_001))
                ->set(2048, [ModelMode::COMPLETE], [ModelType::GPT3]),
            (new Model(ModelName::TEXT_BABBAGE_001))
                ->set(2048, [ModelMode::COMPLETE], [ModelType::GPT3]),
            (new Model(ModelName::TEXT_ADA_001))
                ->set(2048, [ModelMode::COMPLETE], [ModelType::GPT3]),
            (new Model(ModelName::TEXT_DAVINCI_002))
                ->set(4000, [ModelMode::COMPLETE, ModelMode::INSERT], [ModelType::GPT3]),
            (new Model(ModelName::TEXT_DAVINCI_001))
                ->set(2048, [ModelMode::COMPLETE], [ModelType::GPT3]),
            (new Model(ModelName::DAVINCI_INSTRUCT_BETA))
                ->set(2048, [ModelMode::COMPLETE], [ModelType::GPT3]),
            (new Model(ModelName::DAVINCI))
                ->set(2048, [ModelMode::COMPLETE], [ModelType::GPT3]),
            (new Model(ModelName::CURIE_INSTRUCT_BETA))
                ->set(2048, [ModelMode::COMPLETE], [ModelType::GPT3]),
            (new Model(ModelName::CURIE))
                ->set(2048, [ModelMode::COMPLETE], [ModelType::GPT3]),
            (new Model(ModelName::BABBAGE))
                ->set(2048, [ModelMode::COMPLETE], [ModelType::GPT3]),
            (new Model(ModelName::ADA))
                ->set(2048, [ModelMode::COMPLETE], [ModelType::GPT3]),
            (new Model(ModelName::CODE_DAVINCI_002))
                ->set(8000, [ModelMode::COMPLETE, ModelMode::INSERT], [ModelType::CODEX]),
            (new Model(ModelName::CODE_CUSHMAN_001))
                ->set(2048, [ModelMode::COMPLETE], [ModelType::CODEX]),
            (new Model(ModelName::TEXT_DAVINCI_INSERT_002))
                ->set(4000, [ModelMode::INSERT], [ModelType::GPT3]),
            (new Model(ModelName::TEXT_DAVINCI_INSERT_001))
                ->set(4000, [ModelMode::INSERT], [ModelType::GPT3]),
            (new Model(ModelName::TEXT_DAVINCI_EDIT_001))
                ->set(-1, [ModelMode::EDIT], [ModelType::GPT3]),
            (new Model(ModelName::CODE_DAVINCI_EDIT_001))
                ->set(-1, [ModelMode::EDIT], [ModelType::CODEX]),

            (new Model(ModelName::GPT_35_TURBO))
                ->set(4000, [ModelMode::CHAT], [ModelType::GPT3]),
            (new Model(ModelName::GPT_35_TURBO_0301))
                ->set(4000, [ModelMode::CHAT], [ModelType::GPT3]),
            (new Model(ModelName::GPT_35_TURBO_16K))
                ->set(16000, [ModelMode::CHAT], [ModelType::GPT3]),
            (new Model(ModelName::GPT_4))
                ->set(8000, [ModelMode::CHAT], [ModelType::GPT4]),
            (new Model(ModelName::GPT_4_32K))
                ->set(32000, [ModelMode::CHAT], [ModelType::GPT4]),
        ];
    }

}