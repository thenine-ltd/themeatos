<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 05/07/2023
 * Time: 17:04
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Json;

use DOMElement;
use DOMNode;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Crawling\Bot\DummyBot;
use WPCCrawler\Objects\Enums\ShortCodeName;
use WPCCrawler\Objects\Html\ElementCreator;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Objects\Json\Objects\ConverterOptions;
use WPCCrawler\Objects\Json\Objects\TemplatePart;
use WPCCrawler\Utils;

/**
 * Converts a JSON value into an HTML element
 *
 * @since 1.14.0
 */
class JsonToHtmlConverter {

    const GENERATED_JSON_CONTAINER_ID = 'wpcc-generated-json';
    const ROOT_CONTAINER_CLASS = 'wpcc-converted-json';

    /** @var array<string, array|null> */
    private static $cache = [];

    /** @var array<string, string>|null */
    private static $templateCharEscapeMap = null;

    /** @var array<string, string>|null */
    private static $templateCharUnescapeMap = null;

    //

    /** @var array The data to be converted to HTML */
    private $data;

    /** @var ConverterOptions */
    private $options;

    /** @var DOMNode */
    private $rootNode;

    /** @var ElementCreator */
    private $creator;

    /** @var string A regular expression that matches a string only if it is a valid URL */
    private $urlMatchRegex = '/^https?:\/\/\S+$/i';

    /** @var bool `true` if the conversion occurred at least once. */
    private $converted = false;

    /**
     * @param string                $json A JSON string
     * @param ConverterOptions|null $options
     * @return JsonToHtmlConverter|null If the JSON string is invalid, returns `null`. Otherwise, a new
     *                                  {@link JsonToHtmlConverter} that works with the provided JSON is returned.
     * @since 1.14.0
     */
    public static function fromJson(string $json, ?ConverterOptions $options = null): ?JsonToHtmlConverter {
        $data = self::decodeJson($json);
        if ($data === null) {
            return null;
        }

        return self::fromArray($data, $options);
    }

    /**
     * @param array                 $data An array that will be converted to HTML
     * @param ConverterOptions|null $options
     * @return JsonToHtmlConverter|null A converter that works with the provided array, if it could be created.
     *                                  Otherwise, `null` is returned.
     * @since 1.14.0
     */
    public static function fromArray(array $data, ?ConverterOptions $options = null): ?JsonToHtmlConverter {
        $crawler = (new DummyBot([]))->createDummyCrawler('');
        $rootNode = $crawler->filter('body > div')->first()->getNode(0);
        if (!$rootNode) {
            $message = _wpcc('Data could not be converted to HTML, because the root node of HTML document could
                not be retrieved.');
            Informer::addInfo($message)->addAsLog();
            return null;
        }

        return new JsonToHtmlConverter($data, $rootNode, $options);
    }

    /*
     *
     */

    /**
     * This is a private constructor. Use {@link JsonToHtmlConverter::fromJson()} or
     * {@link JsonToHtmlConverter::fromArray()} method to create a new instance.
     *
     * @param array   $data     The data to be converted to HTML
     * @param DOMNode $rootNode The root node that stores the converted HTML code
     * @since 1.14.0
     */
    private function __construct(array $data, DOMNode $rootNode, ?ConverterOptions $options) {
        $this->data = $data;
        $this->options = $options ?? new ConverterOptions();
        $this->rootNode = $rootNode;
    }

    /**
     * Converts the data into HTML and injects it into the root node ({@link getRootNode()}).
     *
     * @since 1.14.0
     */
    public function convert(): void {
        if ($this->converted) {
            return;
        }

        $this->converted = true;

        $this->markRootNodeWithClassName();

        $template = $this->getOptions()->getTemplate();
        if ($template === null) {
            $this->onConvert($this->getRootNode(), $this->getData());

        } else {
            $this->onConvertWithTemplate($template, $this->getRootNode(), $this->getData());
        }

    }

    /**
     * Converts the data into HTML, injects it into the root node, and returns the HTML of the root node.
     *
     * @return string|null The HTML of the root node after the data is injected as HTML into it
     * @since 1.14.0
     */
    public function convertAndGetHtml(): ?string {
        $this->convert();
        return Utils::getDomNodeHtml($this->getRootNode());
    }

    /*
     *
     */

    /**
     * Converts the data into HTML and injects the created HTML code into the given container as its last child
     *
     * @param DOMNode $container The element into which the created HTML element will be injected as the last child
     * @since 1.14.0
     */
    protected function convertAndInjectIntoElement(DOMNode $container): void {
        $this->convert();
        (new ElementCreator())->createFromRemoteElement(
            $container,
            ElementCreator::LOCATION_INSIDE_BOTTOM,
            $this->getRootNode(),
            false
        );
    }

    /*
     *
     */

    /**
     * Renders the template by injecting the data into it, and then injects the rendered code into the given node
     *
     * @param string  $template The template, unescaped
     * @param DOMNode $parent   The parent node into which the generated HTML code will be injected
     * @param array   $data     The data to be used in the template
     * @since 1.14.0
     */
    protected function onConvertWithTemplate(string $template, DOMNode $parent, array $data): void {
        $parts = $this->createTemplateParts($template);
        $bladeTemplate = $this->createBladeTemplateFromParts($parts);
        $rendered = Utils::getBlade()->render($bladeTemplate, ['data' => $data]);
        $unescapedRender = $this->unescapeTemplate($rendered);

        // If the render resulted in an empty string, notify the user and stop.
        if ($unescapedRender === '') {
            Informer::addInfo(sprintf(
                _wpcc('HTML template resulted in an empty text. Please make sure it is correctly defined. [Template: %1$s]'),
                Str::limit($template, 500)
            ))
                ->addAsLog();
            return;
        }

        $this->getCreator()
            ->create($parent, ElementCreator::LOCATION_INSIDE_BOTTOM, $unescapedRender);
    }

    /**
     * @param DOMNode $parent The parent node into which created HTML will be inserted
     * @param array   $data   The data that will be converted into HTML
     * @since 1.14.0
     */
    protected function onConvert(DOMNode $parent, array $data): void {
        if (!$data) {
            return;
        }

        /** `true` if this data corresponds to a JSON array. Otherwise, `false`. */
        $isJsonArray = is_int(array_key_first($data));
        foreach($data as $key => $value) {
            $keyIndicator = $isJsonArray
                ? "data-index='{$key}'"
                : "class='{$key}' data-key='{$key}'";

            if (is_array($value)) {
                // Add an element that will contain this array of items
                $newParent = $this->addNode($parent, "<div {$keyIndicator}></div>");
                if (!$newParent) continue;

                $this->onConvert($newParent, $value);
                continue;
            }

            // We expect a scalar value in the rest of the loop.
            if (!is_scalar($value)) {
                continue;
            }

            $value = Utils::convertScalarToString($value);

            // First, convert HTML entities to their corresponding characters. This will turn, for example, "&#231;"
            // to "ç". Then, escape the HTML special characters so that the HTML code in de value is treated as
            // plain text.
            $value = htmlspecialchars(html_entity_decode($value));

            // Handle non-array value
            /** @noinspection PhpUnusedLocalVariableInspection */
            $elementHtml = null;
            // Check if the value is a URL. If so, create an anchor element.
            if ($this->isUrl($value)) {
                $trimmed = trim($value);
                $elementHtml = "<a {$keyIndicator} href='{$trimmed}' target='_blank' rel='nofollow noopener noreferrer'>{$value}</a>";

            } else {
                // The value is not a URL. Create a div element.
                $elementHtml = "<div {$keyIndicator}>{$value}</div>";
            }

            /** @noinspection PhpConditionAlreadyCheckedInspection */
            if ($elementHtml === null) { // @phpstan-ignore-line
                continue;
            }

            $this->addNode($parent, $elementHtml);
        }
    }

    /**
     * Adds the given HTML element definition as a {@link DOMNode} inside the given reference {@link DOMNode}, as the
     * last child.
     *
     * @param DOMNode $reference The reference node
     * @param string  $html      The HTML code of a single element that will be added into the HTML document relative to
     *                           the reference node
     * @return DOMNode|null If the given HTML code could be injected as a {@link DOMNode} successfully, it will be
     *                      returned. Otherwise, `null` is returned.
     * @since 1.14.0
     */
    protected function addNode(DOMNode $reference, string $html): ?DOMNode {
        return $this->getCreator()
            ->createOne($reference, ElementCreator::LOCATION_INSIDE_BOTTOM, $html);
    }

    /**
     * @param string $value A string that might be a URL
     * @return bool `true` if the given value is a URL
     * @since 1.14.0
     */
    protected function isUrl(string $value): bool {
        return preg_match($this->urlMatchRegex, $value) === 1;
    }

    /**
     * Adds {@link ROOT_CONTAINER_CLASS} to the root node ({@link getRootNode()})
     * @since 1.14.0
     */
    protected function markRootNodeWithClassName(): void {
        // Add a class to the root node, so that it is possible to know that the element is created by converting a JSON
        // value into HTML.
        $rootNode = $this->getRootNode();
        if (!($rootNode instanceof DOMElement)) {
            return;
        }

        $existingCls = $rootNode->getAttribute('class');
        if (mb_strpos($existingCls, self::ROOT_CONTAINER_CLASS) !== false) {
            return;
        }

        $rootNode->setAttribute('class', trim(sprintf(
            '%1$s %2$s wpcc-generated-container',
            $existingCls,
            self::ROOT_CONTAINER_CLASS
        )));
    }

    /*
     * TEMPLATE HELPERS
     */

    /**
     * @param TemplatePart[] $parts
     * @return string
     * @since 1.14.0
     */
    public function createBladeTemplateFromParts(array $parts): string {
        /** @var string[] $bladeTemplateParts */
        $bladeTemplateParts = [];
        /** @var TemplatePart[] $unclosedParts */
        $unclosedParts = [];
        $resultCount = 0;
        foreach($parts as $part) {
            $type = $part->getDirectiveType();
            if ($type === null) {
                $bladeTemplateParts[] = $part->getPart();
                continue;
            }

            // This is a directive

            // If this is a "close" directive
            if ($type === TemplatePart::DIRECTIVE_CLOSE) {
                $matchingUnclosedPart = array_pop($unclosedParts) ?? null;
                $closeDirective = $matchingUnclosedPart
                    ? $matchingUnclosedPart->getCloseBladeDirective()
                    : null;
                if ($closeDirective) {
                    $bladeTemplateParts[] = $closeDirective;
                }

                continue;
            }

            // This is a directive possibly containing a JSON path.
            $jsonPath = $part->getJsonPath();
            if ($jsonPath === null) {
                // If this is an "echo" directive, echo the item. Otherwise, the directive must have contained a JSON
                // path.
                if ($type === TemplatePart::DIRECTIVE_ECHO) {
                    $bladeTemplateParts[] = '{{ $item ?? "" }}';
                }

                continue;
            }

            // Get the JSON path by unescaping it. Also, escape the single quotes, since we will use single quotes when
            // defining the JSON path in PHP.
            $jsonPath = str_replace("'", "\'", $this->unescapeTemplate($jsonPath));
            $resultVarName = "\$result{$resultCount}";
            $resultVariable = sprintf('<?php %1$s = data_get((array) ($item ?? $data), \'%2$s\') ?? ""; ?>', $resultVarName, $jsonPath);
            $resultCount++;
            if ($type === TemplatePart::DIRECTIVE_ECHO) {
                $bladeTemplateParts[] = "{$resultVariable}{{ is_array({$resultVarName}) ? implode(' ', \Illuminate\Support\Arr::flatten({$resultVarName})) : {$resultVarName} }}";

            } else if ($type === TemplatePart::DIRECTIVE_FOREACH) {
                $unclosedParts[] = $part;
                $bladeTemplateParts[] = "{$resultVariable}@foreach((array) {$resultVarName} as \$item)";
            }
        }

        return implode('', $bladeTemplateParts);
    }

    /**
     * @param string $unescapedTemplate Unescaped template that will be converted to {@link TemplatePart}s
     * @return TemplatePart[]
     * @since 1.14.0
     */
    public function createTemplateParts(string $unescapedTemplate): array {
        // Escape the template, so that it does not have any executable PHP code in it
        $escapedTemplate = $this->escapeTemplate($unescapedTemplate);

        // Convert [wcc-item] short codes into Blade directives. First, let's find their locations.
        $pattern = sprintf('/\[\/?%1$s(?:[^]]+)?]/', preg_quote(ShortCodeName::WCC_ITEM));
        preg_match_all($pattern, $escapedTemplate, $matches, PREG_OFFSET_CAPTURE);
        if (!$matches) {
            return [];
        }

        $matchArr = $matches[0] ?? null;
        if (!is_array($matchArr)) {
            return [];
        }

        /** @var TemplatePart[] $parts */
        $parts = [];
        $mainEndIndex = 0;
        /** @var TemplatePart|null $lastPart */
        $lastPart = null;
        foreach($matchArr as $matchItem) {
            if (!is_array($matchItem)) continue;

            $part = $matchItem[0] ?? null; // @phpstan-ignore-line
            $startIndex = $matchItem[1] ?? null; // @phpstan-ignore-line
            if (!is_string($part) || !is_int($startIndex)) { // @phpstan-ignore-line
                continue;
            }

            if ($startIndex > $mainEndIndex + 1) {
                $lastPart = new TemplatePart(
                    $lastPart,
                    substr($escapedTemplate, $mainEndIndex, $startIndex - $mainEndIndex),
                    $mainEndIndex,
                    false
                );
                $parts[] = $lastPart;
            }

            $lastPart = new TemplatePart($lastPart, $part, $startIndex, true);
            $parts[] = $lastPart;
            $mainEndIndex = $lastPart->getEnd();
        }

        if ($mainEndIndex < mb_strlen($escapedTemplate) - 1) {
            $parts[] = new TemplatePart(
                $lastPart,
                substr($escapedTemplate, $mainEndIndex),
                $mainEndIndex,
                false
            );
        }

        return $parts;
    }

    /**
     * Unescapes a template, reverting changes made by the {@link escapeTemplate()} method.
     *
     * @param string $template A template
     * @return string Unescaped template
     * @since 1.14.0
     */
    public function unescapeTemplate(string $template): string {
        $unescapeMap = self::getTemplateCharUnescapeMap();
        foreach($unescapeMap as $find => $replace) {
            $template = str_replace($find, $replace, $template);
        }

        return $template;
    }

    /**
     * Escapes a template so that it does not include any executable code
     *
     * @param string $template A template
     * @return string Escaped template
     * @since 1.14.0
     */
    public function escapeTemplate(string $template): string {
        $escapeMap = self::getTemplateCharEscapeMap();
        foreach($escapeMap as $find => $replace) {
            $template = str_replace($find, $replace, $template);
        }

        return $template;
    }

    /**
     * @return array<string, string> Flipped version of the array returned by {@link getTemplateCharEscapeMap()}
     * @since 1.14.0
     */
    protected function getTemplateCharUnescapeMap(): array {
        if (self::$templateCharUnescapeMap === null) {
            self::$templateCharUnescapeMap = array_flip(self::getTemplateCharEscapeMap());
        }

        return self::$templateCharUnescapeMap;
    }

    /**
     * @return array<string, string> Keys are characters that should be escaped, the values are their unicode
     *                       equivalents.
     * @since 1.14.0
     */
    protected function getTemplateCharEscapeMap(): array {
        if (self::$templateCharEscapeMap === null) {
            self::$templateCharEscapeMap = [
                '@' => '&#64;',  // A char having a special meaning in Blade templates
                '?' => '&#63;',  // Used to define PHP code snippets (e.g. "<?php")
                '{' => '&#123;', // A char having a special meaning in Blade templates
                '}' => '&#125;', // A char having a special meaning in Blade templates
                '$' => '&#36;',  // To prevent executing PHP code
            ];
        }

        return self::$templateCharEscapeMap;
    }

    /*
     *
     */

    /**
     * @return ConverterOptions
     * @since 1.14.0
     */
    protected function getOptions(): ConverterOptions {
        return $this->options;
    }

    /*
     * PUBLIC GETTERS
     */

    /**
     * @return ElementCreator
     * @since 1.14.0
     */
    public function getCreator(): ElementCreator {
        if ($this->creator === null) {
            $this->creator = new ElementCreator();
        }
        return $this->creator;
    }

    /**
     * @return DOMNode
     * @since 1.14.0
     */
    public function getRootNode(): DOMNode {
        return $this->rootNode;
    }

    /**
     * @return array See {@link data}
     * @since 1.14.0
     */
    public function getData(): array {
        return $this->data;
    }

    /*
     * STATIC CONSTRUCTORS
     */

    /**
     * Converts many JSON strings into HTML and injects them into a {@link Crawler}
     *
     * @param Crawler               $crawler     The crawler into which the HTML versions of the given JSON strings
     *                                           will be injected. The `body` element of the crawler is preferred. If
     *                                           it does not exist, the first node of the crawler is used as the
     *                                           container.
     * @param array                 $jsonStrings The JSON strings that will be converted to HTML
     * @param ConverterOptions|null $options
     * @since 1.14.0
     */
    public static function fromJsonIntoCrawler(Crawler $crawler, array $jsonStrings, ?ConverterOptions $options = null): void {
        $container = self::getJsonContainer($crawler);
        if (!$container) {
            return;
        }

        foreach($jsonStrings as $jsonString) {
            if (!is_string($jsonString)) {
                continue;
            }

            $converter = self::fromJson($jsonString, $options);
            if (!$converter) {
                continue;
            }

            $converter->convertAndInjectIntoElement($container);
        }
    }

    /**
     * Converts an array into HTML and injects the HTML into a {@link Crawler}. **This method will create only one
     * container**. Even if an array of arrays is provided, only one container will be created.
     *
     * @param Crawler               $crawler The crawler into which the HTML version of the given data will be
     *                                       injected. The `body` element of the crawler is preferred. If it does not
     *                                       exist, the first node of the crawler is used as the container.
     * @param array                 $data    The data that will be converted into an HTML element. This is assumed as
     *                                       the array representation of one JSON string.
     * @param ConverterOptions|null $options
     * @since 1.14.0
     */
    public static function fromArrayIntoCrawler(Crawler $crawler, array $data, ?ConverterOptions $options = null): void {
        $container = self::getJsonContainer($crawler);
        if (!$container) {
            return;
        }

        $converter = self::fromArray($data, $options);
        if (!$converter) {
            return;
        }

        $converter->convertAndInjectIntoElement($container);
    }

    /**
     * Finds the JSON strings in a {@link Crawler}, converts them into HTML, and injects them as the last children of
     * the `body` element of the {@link Crawler}
     *
     * @param Crawler               $crawler The crawler that might contain JSON strings.
     * @param ConverterOptions|null $options
     * @since 1.14.0
     */
    public static function fromCrawlerIntoCrawlerAuto(Crawler $crawler, ?ConverterOptions $options = null): void {
        $jsonStrings = (new JsonFinder($crawler))
            ->find();

        self::fromJsonIntoCrawler($crawler, $jsonStrings, $options);
    }

    /*
     * STATIC HELPERS
     */

    /**
     * Decodes a JSON string into an associative array
     *
     * @param string $json  A JSON string
     * @param bool   $cache `false` if caching is disabled. Defaults to `true`.
     * @return array|null The parsed data, if it could be parsed. Otherwise, `null`.
     * @since 1.14.0
     */
    public static function decodeJson(string $json, bool $cache = true): ?array {
        $cacheKey = null;
        if ($cache) {
            $cacheKey = md5($json);
            $cachedValue = self::$cache[$cacheKey] ?? null;
            if ($cachedValue !== null) {
                return $cachedValue;
            }
        }

        $data = json_decode($json, true);

        // If the JSON could not be parsed, return null.
        if (!is_array($data)) {
            // If there is an error, add it as an information message
            $err = json_last_error_msg();
            if ($err) {
                Informer::addInfo(
                    _wpcc("JSON value could not be parsed.")
                    . ' [' . sprintf(_wpcc('Message: %1$s'), $err) . ']'
                    . ' [' . sprintf(_wpcc('JSON: %1$s'), Str::limit($json, 240)) . ']'
                )->addAsLog();
            }

            $data = null;
        }

        if ($cacheKey !== null) {
            self::$cache[$cacheKey] = $data;
        }

        return $data;
    }

    /*
     * PROTECTED STATIC METHODS
     */

    /**
     * @param Crawler $crawler The crawler whose JSON container will be retrieved
     * @return DOMNode|null The element that contains the HTML code generated from JSON
     * @since 1.14.0
     */
    protected static function getJsonContainer(Crawler $crawler): ?DOMNode {
        $body = Utils::getBodyElement($crawler);
        if (!$body) {
            return null;
        }

        // Instead of directly inserting into the `body`, create a container with ID and insert into it. By this way,
        // the found CSS selectors will be more reliable, as they won't need to find an :nth-child selector in
        // the `body`, but in the #wpcc-generated-json element.
        $containerId = self::GENERATED_JSON_CONTAINER_ID;
        $container = (new Crawler($body))
            ->filter("#{$containerId}")
            ->first()
            ->getNode(0);
        if ($container === null) {
            $container = (new ElementCreator())
                ->createOne($body, ElementCreator::LOCATION_INSIDE_BOTTOM, "<div id='{$containerId}'></div>");
        }

        return $container;
    }

}