<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 08/07/2023
 * Time: 15:27
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Json\Objects;

class TemplatePart {

    const DIRECTIVE_CLOSE = 0;
    const DIRECTIVE_ECHO = 1;
    const DIRECTIVE_FOREACH = 2;

    /** @var TemplatePart|null The template part that comes before this */
    private $prev;

    /** @var TemplatePart|null The template part that comes after this */
    private $next;

    /** @var string */
    private $part;

    /** @var int */
    private $start;

    /** @var int */
    private $end;

    /** @var bool */
    private $directive;

    /**
     * @var int|null Type of the directive. One of the constants defined in this class, whose name starts with
     *      "DIRECTIVE_", such as {@link DIRECTIVE_ECHO}.
     */
    private $directiveType;

    /** @var string|null */
    private $closeBladeDirective;

    /** @var string|null */
    private $jsonPath;

    /**
     * @param TemplatePart|null $prev      The template part that comes before this part, if it exists.
     * @param string            $part      The content of the template part
     * @param int               $start     Start index of the part within the template
     * @param bool              $directive `true` if this is a directive, which will be converted to a Blade directive
     * @since 1.14.0
     */
    public function __construct(?TemplatePart $prev, string $part, int $start, bool $directive) {
        $this->prev = $prev;
        $this->part = $part;
        $this->start = $start;
        $this->end = $start + mb_strlen($part);
        $this->directive = $directive;
        $this->directiveType = $this->extractDirectiveType();
        $this->closeBladeDirective = $this->extractCloseBladeDirective();
        $this->jsonPath = $this->extractJsonPath();

        if ($prev) {
            $prev->setNext($this);
        }
    }

    /**
     * @return TemplatePart|null
     * @since 1.14.0
     */
    public function getPrev(): ?TemplatePart {
        return $this->prev;
    }

    /**
     * @return TemplatePart|null
     * @since 1.14.0
     */
    public function getNext(): ?TemplatePart {
        return $this->next;
    }

    /**
     * @param TemplatePart|null $next
     * @return TemplatePart
     * @since 1.14.0
     */
    public function setNext(?TemplatePart $next): TemplatePart {
        $this->next = $next;
        return $this;
    }

    /**
     * @return string
     * @since 1.14.0
     */
    public function getPart(): string {
        return $this->part;
    }

    /**
     * @return int
     * @since 1.14.0
     */
    public function getStart(): int {
        return $this->start;
    }

    /**
     * @return int
     * @since 1.14.0
     */
    public function getEnd(): int {
        return $this->end;
    }

    /**
     * @return bool
     * @since 1.14.0
     */
    public function isDirective(): bool {
        return $this->directive;
    }

    /**
     * @return int|null The type of directive used in the part, such as {@link DIRECTIVE_FOREACH}. If the part is not a
     *                  directive, returns `null`.
     * @since 1.14.0
     */
    public function getDirectiveType(): ?int {
        return $this->directiveType;
    }

    /**
     * @return string|null The closing directive that will be used to close this directive in a Blade template
     * @since 1.14.0
     */
    public function getCloseBladeDirective(): ?string {
        return $this->closeBladeDirective;
    }

    /**
     * @return string|null The JSON path used in this template part, if it exists. Otherwise, `null`.
     * @since 1.14.0
     */
    public function getJsonPath(): ?string {
        return $this->jsonPath;
    }

    /*
     *
     */

    /**
     * Extracts the JSON path if this is a directive part containing a JSON path.
     *
     * @return string|null The JSON path used in this template part, if it exists. Otherwise, `null`.
     * @since 1.14.0
     */
    protected function extractJsonPath(): ?string {
        if (!$this->isDirective() || $this->getDirectiveType() === self::DIRECTIVE_CLOSE) {
            return null;
        }

        // Remove the non-path characters from the directive
        $sanitized = preg_replace('/\[wcc-item\s*|]$/', '', $this->getPart());
        if (!is_string($sanitized)) {
            return null;
        }

        $sanitized = trim($sanitized);
        if ($sanitized === '') {
            return null;
        }

        // If the path contains space characters, get the part until the first space character.
        if (strpos($sanitized, ' ') !== false) {
            $sanitized = explode(' ', $sanitized)[0];
        }

        return $sanitized;
    }

    /**
     * @return string|null The closing directive that will be used to close this directive in a Blade template
     * @since 1.14.0
     */
    protected function extractCloseBladeDirective(): ?string {
        $type = $this->getDirectiveType();
        if ($type === null) {
            return null;
        }

        if ($type === self::DIRECTIVE_FOREACH) {
            return '@endforeach <?php $item = null; ?>';
        }

        return null;
    }

    /**
     * Extracts the type of directive used in the part
     *
     * @return int|null The type of directive used in the part, such as {@link DIRECTIVE_FOREACH}. If the part is not a
     *                  directive, returns `null`.
     * @since 1.14.0
     */
    protected function extractDirectiveType(): ?int {
        if (!$this->isDirective()) {
            return null;
        }

        if (strpos($this->getPart(), '[/') === 0) {
            return self::DIRECTIVE_CLOSE;
        }

        if (strpos($this->getPart(), '*') !== false) {
            return self::DIRECTIVE_FOREACH;
        }

        return self::DIRECTIVE_ECHO;
    }
}