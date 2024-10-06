<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 18/02/2023
 * Time: 08:31
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\ShortCode;

use Closure;
use WPCCrawler\Objects\Informing\Informer;

abstract class AbstractCustomShortCodeApplier {

    /** @var ShortCodeApplier The main short code applier that can be used to apply short codes to inner templates */
    private $applier;

    /** @var string|null */
    private $shortCodeExistenceRegex = null;

    /**
     * @param ShortCodeApplier $applier See {@link $applier}
     * @since 1.13.0
     */
    public function __construct(ShortCodeApplier $applier) {
        $this->applier = $applier;
    }

    /**
     * @param string $template The template that might contain the custom short code
     * @return string The text with the custom short code applied
     * @since 1.13.0
     */
    public function apply(string $template): string {
        // If the template does not have this short code, return the template back without doing any extra work.
        if (!$this->hasShortCode($template)) {
            return $template;
        }

        return $this->onApply($template);
    }

    /**
     * Injects the values of other short codes into the templates of the short code, but does not apply the short code.
     *
     * @param string $template The template that might contain the custom short code
     * @return string The template whose short code templates are injected
     * @since 1.13.0
     */
    public function injectValues(string $template): string {
        // If the template does not have this short code, return the template back without doing any extra work.
        if (!$this->hasShortCode($template)) {
            return $template;
        }

        return $this->onInjectValues($template);
    }

    /**
     * @return string Name of the short code, without brackets
     * @since 1.13.0
     */
    abstract public function getShortCodeName(): string;

    /**
     * Applies the custom short code to the given template. If the template contains another template that needs short
     * codes to be applied, you can use {@link getApplier()} to receive the main applier.
     *
     * @param string $template The template that might contain the custom short code
     * @return string The text with the custom short code applied
     * @since 1.13.0
     */
    abstract protected function onApply(string $template): string;

    /**
     * Injects the values of other short codes into the templates of the short code, but does not apply the short code.
     *
     * @param string $template The template that might contain the custom short code
     * @return string The template whose short code templates are injected
     * @since 1.13.0
     */
    abstract protected function onInjectValues(string $template): string;

    /**
     * Clears the short codes from the given template
     *
     * @param string $template The template that contains the short codes
     * @return string The template whose short codes are removed
     * @since 1.13.0
     */
    public function clear(string $template): string {
        return $this->doApplyWithCallback($template, function() {
            return '';
        });
    }

    /**
     * @param string  $template The template that contains the short codes
     * @param Closure $replacer See {@link doApplyWithCallback()}
     * @return string The template whose short codes are replaced
     * @since 1.13.0
     */
    public function replace(string $template, Closure $replacer): string {
        return $this->doApplyWithCallback($template, $replacer);
    }

    /**
     * @return ShortCodeApplier See {@link $applier}
     * @since 1.13.0
     */
    public function getApplier(): ShortCodeApplier {
        return $this->applier;
    }

    /*
     * PROTECTED METHODS
     */

    /**
     * @param string   $template The template that contains the short codes
     * @param callable $callback A function that will replace each found short code in the template
     * @return string The template with whose short codes are replaced
     * @since 1.13.0
     */
    protected function doApplyWithCallback(string $template, callable $callback): string {
        $pattern = get_shortcode_regex([$this->getShortCodeName()]);
        $newTemplate = preg_replace_callback("/$pattern/", $callback, $template);

        // This will never happen. This is done to please the code quality checker.
        if (!is_string($newTemplate)) {
            Informer::addInfo(sprintf(
                _wpcc('%1$s short code could not be applied.'),
                '[' . $this->getShortCodeName() . ']'
            ))->addAsLog();

            // Return the original content
            return $template;
        }

        return $newTemplate;
    }

    /**
     * @param string $template Text that might contain one or more short codes
     * @return bool `true` if the template contains at least one short code
     * @since 1.13.0
     */
    protected function hasShortCode(string $template): bool {
        return preg_match($this->getShortCodeExistenceRegex(), $template) === 1;
    }

    /**
     * @return string A regular expression that is used to test if a text contains the short code
     * @since 1.13.0
     */
    protected function getShortCodeExistenceRegex(): string {
        if ($this->shortCodeExistenceRegex === null) {
            $this->shortCodeExistenceRegex = '/'
                . preg_quote($this->getApplier()->getOpeningBracket())
                . '\s*'
                . preg_quote($this->getShortCodeName())
                . '/';
        }

        return $this->shortCodeExistenceRegex;
    }

}