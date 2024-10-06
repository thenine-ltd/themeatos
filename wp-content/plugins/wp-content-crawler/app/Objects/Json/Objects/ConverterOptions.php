<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 08/07/2023
 * Time: 11:10
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Json\Objects;

use WPCCrawler\Objects\Json\JsonToHtmlConverter;

/**
 * Options for {@link JsonToHtmlConverter}
 */
class ConverterOptions {

    /** @var string|null A template that will be used to create the HTML */
    private $template;

    /**
     * @return ConverterOptions A new instance
     * @since 1.14.0
     */
    public static function newInstance(): ConverterOptions {
        return new ConverterOptions();
    }

    /**
     * @return string|null See {@link $template}
     * @since 1.14.0
     */
    public function getTemplate(): ?string {
        return $this->template;
    }

    /**
     * @param string|null $template See {@link $template}
     * @return self
     * @since 1.14.0
     */
    public function setTemplate(?string $template): self {
        $this->template = $template;
        return $this;
    }

}