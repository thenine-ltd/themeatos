<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 17/02/2023
 * Time: 21:26
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\ShortCode;

use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use WPCCrawler\Factory;
use WPCCrawler\Objects\Api\OpenAi\OpenAiClient;
use WPCCrawler\Objects\Api\OpenAi\ShortCode\OpenAiGptShortCodeApplier;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Objects\Settings\SettingsImpl;
use WPCCrawler\Objects\Traits\FindAndReplaceTrait;

class ShortCodeApplier {

    use FindAndReplaceTrait;

    /**
     * @var string Prefix for internal short codes. This is all lower-case, because the short code name is made
     *      lower-case automatically in file names. We do not want to miss those short codes just because the case
     *      differs.
     */
    const INTERNAL_SHORT_CODE_PREFIX = 'wpccinternal';

    /**
     * @var array<string, string>|null A map whose keys are the names of the predefined short codes such as
     *      wcc-main-title, wcc-main-excerpt, etc. The values are empty strings. Use
     *      {@link getPredefinedShortCodeClearanceMap()} to retrieve the value.
     */
    private static $predefinedShortCodeClearanceMap = null;

    /**
     * @var array<string, string> Keys are the names of the internal short codes without brackets. The values are their
     * actual values.
     */
    private static $internalShortCodes = [];

    /** @var int The index of the last created internal short code */
    private static $lastInternalShortCodeIndex = 0;

    /**
     * @var array<string, scalar|scalar[]|Closure> Keys are short code names without brackets. The values are the actual
     *      values of the short codes. If the value is a {@link Closure}, it must return a scalar value.
     */
    private $valueMap;

    /** @var SettingsImpl|null The site settings. This will be used to retrieve API keys. */
    private $settings;

    /** @var string The opening bracket used to define a short code */
    private $openingBracket;

    /** @var string The closing bracket used to define a short code */
    private $closingBracket;

    /**
     * @var array<string, string>|null The {@link $map} that is prepared by replacing the short codes existing in the
     *      templates with their actual values. In other words, this is the map with the dependencies injected.
     */
    private $preparedMap = null;

    /**
     * @var bool `true` if the custom short code appliers must not be used when applying the short codes. Otherwise,
     *      `false`.
     */
    private $customAppliersDisabled = false;

    /**
     * @var bool `true` if the short codes of the custom appliers must be rewritten by injecting the short code values
     *           into the templates inside the custom short codes.
     */
    private $injectValuesToCustomAppliers = false;

    /**
     * @var Closure|null A function that can modify the template. The signature is: <b>fn(string $template): string</b>
     *                   This is called before returning the final template in {@link apply()}. If the custom appliers
     *                   use this applier to apply the templates used in their short codes, this callback will be called
     *                   at those times as well.
     */
    private $onModifyTemplateCallback = null;

    /**
     * @param array<string, scalar|scalar[]|Closure> $valueMap       See {@link $map}
     * @param SettingsImpl|null                      $settings       See {@link $settings}
     * @param string                                 $openingBracket See {@link $openingBracket}
     * @param string                                 $closingBracket See {@link $closingBracket}
     * @since 1.13.0
     */
    public function __construct(array $valueMap, ?SettingsImpl $settings = null, string $openingBracket = '[',
                                string $closingBracket = ']') {
        $this->valueMap = $valueMap;
        $this->settings = $settings;

        $this->openingBracket = $openingBracket;
        $this->closingBracket = $closingBracket;
    }

    /**
     * @return bool See {@link $customAppliersDisabled}
     * @since 1.13.0
     */
    public function isCustomAppliersDisabled(): bool {
        return $this->customAppliersDisabled;
    }

    /**
     * @param bool $customAppliersDisabled See {@link $customAppliersDisabled}
     * @return self
     * @since 1.13.0
     */
    public function setCustomAppliersDisabled(bool $customAppliersDisabled): ShortCodeApplier {
        $this->customAppliersDisabled = $customAppliersDisabled;
        return $this;
    }

    /**
     * @return bool See {@link $injectValuesToCustomAppliers}
     * @since 1.13.0
     */
    public function isInjectValuesToCustomAppliers(): bool {
        return $this->injectValuesToCustomAppliers;
    }

    /**
     * @param bool $injectValuesToCustomAppliers See {@link $injectValuesToCustomAppliers}
     * @return self
     * @since 1.13.0
     */
    public function setInjectValuesToCustomAppliers(bool $injectValuesToCustomAppliers): self {
        $this->injectValuesToCustomAppliers = $injectValuesToCustomAppliers;
        return $this;
    }

    /**
     * @return Closure|null See {@link $onModifyTemplateCallback}
     * @since 1.13.0
     */
    public function getOnModifyTemplateCallback(): ?Closure {
        return $this->onModifyTemplateCallback;
    }

    /**
     * @param Closure|null $onModifyTemplateCallback See {@link $onModifyTemplateCallback}
     * @return self
     * @since 1.13.0
     */
    public function setOnModifyTemplateCallback(?Closure $onModifyTemplateCallback): ShortCodeApplier {
        $this->onModifyTemplateCallback = $onModifyTemplateCallback;
        return $this;
    }

    /**
     * @param string $template The template that might contain short codes
     * @return string The template with the short codes replaced with their actual values
     * @since 1.13.0
     */
    public function apply(string $template): string {
        $newTemplate = $this->applyValueMap($this->getPreparedMap(), $template);

        // Apply other appliers as well, if they are enabled.
        $newTemplate = $this->maybeApplyCustomAppliers($newTemplate);

        // Rewrite the short codes of the custom appliers, if it is enabled.
        $newTemplate = $this->maybeRewriteCustomApplierShortCodes($newTemplate);

        // If there is a modifier, call it, so that it can modify the final template.
        $modifier = $this->getOnModifyTemplateCallback();
        if ($modifier instanceof Closure) {
            $newTemplate = $modifier($newTemplate);
        }

        return $newTemplate;
    }

    /**
     * Apply the short codes to multiple templates. The templates can also be arrays. This method recursively applies
     * the short codes to all the values of the given array.
     *
     * @param string[]|string[][] $templates The templates whose short codes should be replaced with their values
     * @return array The given template array, with the short codes replaced with their values
     * @since 1.13.0
     */
    public function applyAll(array $templates): array {
        $result = [];
        foreach($templates as $key => $template) {
            if (is_array($template)) {
                $result[$key] = $this->applyAll($template);
                continue;
            }

            $result[$key] = $this->apply($template);
        }

        return $result;
    }

    /**
     * Clears the generative short codes from the given template
     *
     * @param string $template The template that might contain custom short codes.
     * @return string The template without the generative short codes
     * @since 1.13.0
     */
    public function clearGenerativeShortCodes(string $template): string {
        $customAppliers = $this->createCustomAppliers();
        foreach($customAppliers as $customApplier) {
            $template = $customApplier->clear($template);
        }

        return $template;
    }

    /**
     * @param string $template The template that might contain short codes
     * @return string The template whose short codes are replaced with internal short codes
     * @since 1.13.0
     */
    public function replaceWithInternalShortCodes(string $template): string {
        $customAppliers = $this->createCustomAppliers();
        foreach($customAppliers as $customApplier) {
            $template = $customApplier->replace($template, function(array $match) {
                $newIndex = self::$lastInternalShortCodeIndex + 1;

                // Create an internal short code name
                $internalShortCodeName = self::INTERNAL_SHORT_CODE_PREFIX . $newIndex;

                // Store the original
                self::$internalShortCodes[$internalShortCodeName] = $match[0] ?? '';

                // Update the index
                self::$lastInternalShortCodeIndex = $newIndex;

                // Return the internal short code
                return $this->getOpeningBracket() . $internalShortCodeName . $this->getClosingBracket();
            });
        }

        return $template;
    }

    /**
     * @param string $internalTemplate A template that contains internal short codes
     * @return string The template whose internal short codes are replaced with their original values
     * @since 1.13.0
     */
    public function restoreOriginalTemplateFromInternal(string $internalTemplate): string {
        $originalTemplate = $internalTemplate;
        foreach(self::$internalShortCodes as $shortCodeName => $value) {
            $originalTemplate = self::replaceShortCode(
                $originalTemplate,
                $shortCodeName,
                $value,
                $this->getOpeningBracket(),
                $this->getClosingBracket()
            );
        }

        return $originalTemplate;
    }

    /**
     * Replaces the predefined short codes existing in a template with empty strings
     *
     * @param string $template The template that might contain predefined short codes whose values are not assigned
     * @return string The template with the predefined short codes in it replaced with empty strings
     * @since 1.13.0
     */
    public function clearPredefinedShortCodes(string $template): string {
        return $this->applyValueMap(self::getPredefinedShortCodeClearanceMap(), $template);
    }

    /**
     * @return array<string, string> See {@link $preparedMap}
     * @since 1.13.0
     */
    public function getPreparedMap(): array {
        if ($this->preparedMap === null) {
            $this->preparedMap = $this->createPreparedMap();
        }

        return $this->preparedMap;
    }

    /**
     * @return array<string, scalar|scalar[]|Closure> See {@link $valueMap}
     * @since 1.13.0
     */
    public function getValueMap(): array {
        return $this->valueMap;
    }

    /**
     * @return SettingsImpl See {@link $settings}
     * @since 1.13.0
     */
    public function getSettings(): SettingsImpl {
        if ($this->settings === null) {
            $this->settings = new SettingsImpl([]);
        }

        return $this->settings;
    }

    /**
     * @return string See {@link $openingBracket}
     * @since 1.13.0
     */
    public function getOpeningBracket(): string {
        return $this->openingBracket;
    }

    /**
     * @return string See {@link $closingBracket}
     * @since 1.13.0
     */
    public function getClosingBracket(): string {
        return $this->closingBracket;
    }

    /*
     * PROTECTED HELPERS
     */

    /**
     * Applies the custom short code appliers to a template
     *
     * @param string $template The template whose short codes will be replaced with their values by using the custom
     *                         appliers
     * @return string The template whose short codes are replaced, if the custom appliers are not disabled. Otherwise,
     *                the given template is returned without any change.
     * @since 1.13.0
     */
    protected function maybeApplyCustomAppliers(string $template): string {
        if ($this->isCustomAppliersDisabled()) {
            return $template;
        }

        // Create the other appliers and apply them as well
        $newTemplate = $template;
        $customAppliers = $this->createCustomAppliers();
        foreach($customAppliers as $customApplier) {
            $newTemplate = $customApplier->apply($newTemplate);
        }

        return $newTemplate;
    }

    /**
     * Rewrites the short codes of the custom appliers by replacing the short codes used in their templates with their
     * values. The custom appliers will not be applied. Only the short codes that are available in the value map and
     * used in the custom appliers will be injected.
     *
     * @param string $template The template whose short codes will be replaced with their rewritten values by using the
     *                         custom appliers.
     * @return string The template whose custom applier short codes are replaced, if it is enabled (see
     *                {@link setInjectValuesToCustomAppliers()}). Otherwise, the given template is returned without any
     *                change.
     * @since 1.13.0
     */
    protected function maybeRewriteCustomApplierShortCodes(string $template): string {
        if (!$this->isInjectValuesToCustomAppliers()) {
            return $template;
        }

        // Create the custom appliers and make them inject the short codes into the template
        $newTemplate = $template;
        $customAppliers = $this->createCustomAppliers();
        foreach($customAppliers as $customApplier) {
            $newTemplate = $customApplier->injectValues($newTemplate);
        }

        return $newTemplate;
    }

    /**
     * @param array<string, string> $valueMap The keys are short code names without brackets. The values are the actual
     *                                        values of the short codes.
     * @param string                $template The template that might contain the short codes defined in the value map
     * @return string The template with the short codes in it replaced with their actual values
     * @since 1.13.0
     */
    protected function applyValueMap(array $valueMap, string $template): string {
        $openingBracket = $this->getOpeningBracket();
        $closingBracket = $this->getClosingBracket();

        // Replace the short codes existing in the template with their values
        $newTemplate = $template;
        foreach($valueMap as $shortCodeName => $value) {
            $newTemplate = self::replaceShortCode(
                $newTemplate,
                $shortCodeName,
                $value,
                $openingBracket,
                $closingBracket
            );
        }

        return $newTemplate;
    }

    /**
     * @return AbstractCustomShortCodeApplier[] The custom short code appliers, freshly created
     * @since 1.13.0
     */
    protected function createCustomAppliers(): array {
        // Get the settings needed to create the appliers
        $openAiSecretKey = $this->getSettings()->getSetting(SettingKey::WPCC_API_OPENAI_SECRET_KEY);
        if (!is_string($openAiSecretKey)) {
            $openAiSecretKey = '';
        }

        return [
            new OpenAiGptShortCodeApplier($this->createOpenAiClient($openAiSecretKey), $this),
        ];
    }

    /**
     * @param string $openAiSecretKey The secret key to be used to connect to OpenAI API
     * @return OpenAiClient A new client
     * @since 1.13.0
     */
    protected function createOpenAiClient(string $openAiSecretKey): OpenAiClient {
        return OpenAiClient::newInstance($openAiSecretKey);
    }

    /**
     * @return array<string, string> See {@link $prepardMap}
     * @since 1.13.0
     */
    protected function createPreparedMap(): array {
        $valueMap = $this->getValueMap();

        // Create an object for each short code so that we can create a dependency tree
        /** @var array<string, ShortCodeItem> $items */
        $items = [];
        foreach($valueMap as $name => $value) {
            $actualValue = $value instanceof Closure
                ? $value()
                : $value;

            // If the value is an array, make it a string.
            if (is_array($actualValue)) {
                $actualValue = implode('', array_filter(Arr::flatten($actualValue), function($v) {
                    return is_scalar($v);
                }));
            }

            $items[$name] = new ShortCodeItem(
                $name,
                is_scalar($actualValue)
                    ? (string) $actualValue
                    : '',
                $this->getOpeningBracket(),
                $this->getClosingBracket()
            );
        }

        // Create the dependency tree
        foreach($items as $item) {
            foreach($valueMap as $candidateName => $v) {
                $candidateNameWithBrackets = $this->getOpeningBracket() . $candidateName . $this->getClosingBracket();
                if (!Str::contains($item->getValue(), $candidateNameWithBrackets)) {
                    continue;
                }

                $dependency = $items[$candidateName] ?? null;
                if (!$dependency) continue;

                $item->addDependency($dependency);
            }
        }

        /** @var array<string, string> $preparedMap */
        $preparedMap = [];
        foreach($items as $item) {
            $preparedMap[$item->getName()] = $item->injectDependencies()->getValue();
        }

        return $preparedMap;
    }

    /*
     * STATIC METHODS
     */

    /**
     * @return array<string, string> See {@link $predefinedShortCodeClearanceMap}
     */
    public static function getPredefinedShortCodeClearanceMap(): array {
        if(self::$predefinedShortCodeClearanceMap === null) {
            $names = Factory::postService()->getPredefinedShortCodes();
            $map = [];
            foreach($names as $name) {
                $map[$name] = '';
            }

            self::$predefinedShortCodeClearanceMap = $map;
        }

        return self::$predefinedShortCodeClearanceMap;
    }

    /**
     * Applies short codes in a template considering the file-name-related short codes as well. Also, it applies
     * find-replace rules to the template before applying the short codes.
     *
     * @param ShortCodeApplier $regular      A regular short code applier
     * @param ShortCodeApplier $file         A short code applier that will be used to replace file-name-specific short
     *                                       codes
     * @param string           $template     The template that contains short codes
     * @param array            $findReplaces The find-replace rules that will be applied before applying the short
     *                                       codes
     * @return string
     * @since 1.13.0
     */
    public static function applyShortCodesForFileName(ShortCodeApplier $regular, ShortCodeApplier $file,
                                                      string $template, array $findReplaces): string {
        $template = $regular->findAndReplace($findReplaces, $template);
        $template = $regular->apply($template);
        return $file->apply($template);
    }

    /**
     * Applies short codes in templates considering the file-name-related short codes as well. Also, it applies
     * find-replace rules to the templates before applying the short codes.
     *
     * @param ShortCodeApplier $regular      A regular short code applier
     * @param ShortCodeApplier $file         A short code applier that will be used to replace file-name-specific short
     *                                       codes
     * @param array            $templates    The templates that contain short codes
     * @param array            $findReplaces The find-replace rules that will be applied before applying the short
     *                                       codes
     * @return array
     * @since 1.13.0
     */
    public static function applyShortCodesForFileNameAll(ShortCodeApplier $regular, ShortCodeApplier $file,
                                                         array $templates, array $findReplaces): array {
        $templates = $regular->applyFindAndReplaces($findReplaces, $templates);
        $templates = $regular->applyAll($templates);
        return $file->applyAll($templates);
    }

    /**
     * Replaces a simple short code in a template with a value. The short code cannot have any attributes. Examples:
     * `[my-short-code]`, `{another-short-code}`, `(yet-another-short-code)`
     *
     * @param string      $template       The template including the short code
     * @param string      $shortCodeName  The name of the short code to be replaced, without brackets
     * @param string|null $value          The string to put in place of the short code
     * @param string      $openingBracket Opening bracket for the short code. Default: `[`
     * @param string      $closingBracket Closing bracket for the short code. Default: `]`
     */
    public static function replaceShortCode(string $template, string $shortCodeName, ?string $value,
                                            string $openingBracket = '[', string $closingBracket = ']'): string {
        return str_replace(
            $openingBracket . $shortCodeName . $closingBracket,
            $value ?? '',
            $template
        );
    }

    /**
     * Invalidates the internal short codes
     *
     * @since 1.13.0
     */
    public static function invalidateInternalShortCodes(): void {
        self::$internalShortCodes = [];
        self::$lastInternalShortCodeIndex = 0;
    }

}