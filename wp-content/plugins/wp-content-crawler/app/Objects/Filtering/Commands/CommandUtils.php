<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 27/06/2023
 * Time: 21:48
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Commands;

use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Filtering\Commands\Base\AbstractBaseCommand;
use WPCCrawler\Objects\Filtering\Enums\InputName;
use WPCCrawler\Objects\Html\ElementCreator;
use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;
use WPCCrawler\Utils;

class CommandUtils {

    /**
     * Returns the value defined in {@link InputName::ELEMENT_ATTR} input of a command
     *
     * @param AbstractBaseCommand $cmd The command storing the options
     * @return string|null If there is an {@link InputName::ELEMENT_ATTR} input, its value is returned. Otherwise, null.
     * @since 1.14.0
     */
    public function getElementAttributeOption(AbstractBaseCommand $cmd): ?string {
        $value = $cmd->getStringOption(InputName::ELEMENT_ATTR);
        if ($value === null) return null;

        $value = trim($value);
        return $value === ''
            ? null
            : $value;
    }

    /**
     * Returns the value defined in {@link InputName::JSON_PATH} input of a command
     *
     * @param AbstractBaseCommand $cmd The command that stores the option
     * @return string|null The JSON path entered to its option, if it is entered. Otherwise, `null`.
     * @since 1.14.0
     */
    public function getJsonPathOption(AbstractBaseCommand $cmd): ?string {
        $path = $cmd->getStringOption(InputName::JSON_PATH);
        if ($path === null) return null;

        $path = trim($path);
        return $path === ''
            ? null
            : $path;
    }

    /**
     * Returns the values defined in {@link InputName::CSS_SELECTOR} input of a command
     *
     * @param AbstractBaseCommand $cmd The command storing the options
     * @return array|null The CSS selectors, if available. This array does not contain the items with empty CSS
     *                    selectors. If no CSS selector is available, returns null.
     * @since 1.14.0
     */
    public function getCssSelectorsOption(AbstractBaseCommand $cmd): ?array {
        $cssSelectors = $cmd->getArrayOption(InputName::CSS_SELECTOR);
        if (!$cssSelectors) return null;

        return array_filter($cssSelectors, function($data) {
            $selector = is_array($data)
                ? ($data[SettingInnerKey::SELECTOR] ?? null)
                : null;
            return $selector !== null && trim($selector) !== '';
        });
    }

    /**
     * Returns the values defined in {@link InputName::COOKIES} input of a command
     *
     * @param AbstractBaseCommand $cmd The command storing the options
     * @return array|null The cookies, if available. This array does not contain the items with empty cookie names. If
     *                    no cookies are available, returns `null`.
     * @since 1.14.0
     */
    public function getCookiesOption(AbstractBaseCommand $cmd): ?array {
        $cookies = $cmd->getArrayOption(InputName::COOKIES);
        if (!$cookies) return null;

        return $this->getKeyValueOption($cookies);
    }

    /**
     * Returns the values defined in {@link InputName::REQUEST_HEADERS} input of a command
     *
     * @param AbstractBaseCommand $cmd The command storing the options
     * @return array|null The request headers, if available. This array does not contain the items with empty header
     *                    names. If no headers are available, returns `null`.
     * @since 1.14.0
     */
    public function getRequestHeadersOption(AbstractBaseCommand $cmd): ?array {
        $headers = $cmd->getArrayOption(InputName::REQUEST_HEADERS);
        if (!$headers) return null;

        return $this->getKeyValueOption($headers);
    }

    /**
     * @param AbstractBaseCommand $cmd The command storing the options
     * @return string One of the constants defined in {@link ElementCreator}, whose name starts with "LOCATION_",
     *                such as {@link ElementCreator::LOCATION_INSIDE_BOTTOM}.
     * @since 1.14.0
     */
    public function getElementLocationOption(AbstractBaseCommand $cmd): string {
        $availableLocations = array_keys(ElementCreator::getLocationOptionsForSelect());
        $location = $cmd->getStringOption(InputName::ELEMENT_LOCATION);
        return $location === null || !in_array($location, $availableLocations)
            ? ElementCreator::LOCATION_AFTER
            : $location;
    }

    /*
     *
     */

    /**
     * Get value of an attribute of an element
     *
     * @param Crawler|null $node The node whose attribute is wanted
     * @param string|null  $attr The attribute's name
     * @param bool         $allowSpecialAttrs `true` if "html" and "text" special attributes can be assigned to $attr.
     *                                        Otherwise, `false`. Defaults to `true`.
     * @return string|null If found, the value of the attribute. Otherwise, `null`.
     * @since 1.14.0
     */
    public function getAttributeValue(?Crawler $node, ?string $attr, bool $allowSpecialAttrs = true): ?string {
        if ($node === null || $attr === null) return null;

        if ($allowSpecialAttrs) {
            if ($attr === "text") {
                return $node->text();

            } else if ($attr === "html") {
                return Utils::getNodeHTML($node);
            }
        }

        return $node->attr($attr);
    }

    /*
     *
     */

    /**
     * @return string A human-friendly text that explains what a dot notation path is, with examples.
     * @since 1.14.0
     */
    public function createDotPathDescription(): string {
        $desc = sprintf(
            _wpcc('A dot path is made up of field names, dot characters, and * (wildcard) characters. For'
                . ' example, in %1$s, %2$s path points to "%3$s" value. The wildcard character is used to select all'
                . ' the different field names. For example, in %4$s, %5$s path points to the values %6$s. To retrieve'
                . ' specific values from an array, you can use the index. For example, in %7$s, %8$s retrieves "%9$s"'
                . ' value.'),
            '<span class="highlight">{"product": {"name": "PlayStation"}}</span>',
            '<span class="highlight selector">product.name</span>',
            'PlayStation',
            '<span class="highlight">{"product": [{"name": "PlayStation"}, {"name": "Xbox"}] }</span>',
            '<span class="highlight selector">product.*.name</span>',
            '"PlayStation", "Xbox"',
            '<span class="highlight">{"product": [{"name": "PlayStation"}, {"name": "Xbox"}] }</span>',
            '<span class="highlight selector">product.1.name</span>',
            'Xbox',
        );

        return "<p>{$desc}</p>";
    }

    /*
     *
     */

    protected function getKeyValueOption(array $items): ?array {
        return array_filter($items, function($data) {
            if (!is_array($data)) return false;

            $key = $data[SettingInnerKey::KEY] ?? null;
            if ($key === null || trim($key) === '') return false;

            $value = $data[SettingInnerKey::VALUE] ?? null;
            if ($value === null) return false;

            return true;
        });
    }

}