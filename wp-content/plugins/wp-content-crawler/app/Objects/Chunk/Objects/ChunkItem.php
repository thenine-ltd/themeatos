<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 06/02/2023
 * Time: 13:12
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Chunk\Objects;

use Illuminate\Support\Str;

class ChunkItem {

    /** @var string A regex pattern that matches whitespace characters. Retrieved from {@link trim()} function. */
    const WHITESPACE_PATTERN = '[ \t\n\r\0\x0B]*';

    /** @var string */
    private $key;

    /** @var string */
    private $value;

    /** @var int */
    private $length;

    /** @var string|null */
    private $leadingWhitespace = null;

    /** @var string|null */
    private $trailingWhitespace = null;

    /**
     * @param string $key
     * @param string $value
     * @param int    $length
     * @since 1.13.0
     */
    public function __construct(string $key, string $value, int $length) {
        $this->key = $key;
        $this->value = $value;
        $this->length = $length;
    }

    /**
     * @return string
     * @since 1.13.0
     */
    public function getKey(): string {
        return $this->key;
    }

    /**
     * @return string
     * @since 1.13.0
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * @param string $value              The new value
     * @param bool   $restoreWhitespaces `true` if the new value's whitespaces should be made the same as the previous
     *                                   value's whitespaces. Defaults to `true`.
     * @since 1.13.0
     */
    public function setValue(string $value, bool $restoreWhitespaces = true): void {
        $this->setWhitespaces();

        $this->value = $value;

        if ($restoreWhitespaces) {
            $this->maybeRestoreWhitespaces();
        }
    }

    /**
     * @return int
     * @since 1.13.0
     */
    public function getLength(): int {
        return $this->length;
    }

    /*
     * PROTECTED METHODS
     */

    /**
     * Assigns the leading and trailing whitespaces to the value
     * @since 1.13.0
     */
    protected function maybeRestoreWhitespaces(): void {
        $leadingWs = $this->getLeadingWhitespace();
        $trailingWs = $this->getTrailingWhitespace();

        if ($leadingWs !== null && !Str::startsWith($this->value, $leadingWs)) {
            $this->value = $leadingWs . ltrim($this->value);
        }

        if ($trailingWs !== null && !Str::endsWith($this->value, $trailingWs)) {
            $this->value = rtrim($this->value) . $trailingWs;
        }
    }

    /**
     * Assigns the values of {@link ChunkItem::$leadingWhitespace} and {@link ChunkItem::$trailingWhitespace}
     * @since 1.13.0
     */
    protected function setWhitespaces(): void {
        if (preg_match(sprintf('/^%s/', self::WHITESPACE_PATTERN), $this->getValue(), $leadingWsMatches) === 1) {
            $this->leadingWhitespace = $leadingWsMatches[0] ?? null;
        }

        if (preg_match(sprintf('/%s$/', self::WHITESPACE_PATTERN), $this->getValue(), $trailingWsMatches) === 1) {
            $this->trailingWhitespace = $trailingWsMatches[0] ?? null;
        }
    }

    /**
     * @return string|null
     * @since 1.13.0
     */
    protected function getLeadingWhitespace(): ?string {
        return $this->leadingWhitespace;
    }

    /**
     * @return string|null
     * @since 1.13.0
     */
    protected function getTrailingWhitespace(): ?string {
        return $this->trailingWhitespace;
    }
}