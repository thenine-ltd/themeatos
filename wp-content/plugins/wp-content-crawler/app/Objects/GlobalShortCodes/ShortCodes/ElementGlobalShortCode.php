<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 22/09/2023
 * Time: 09:03
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\GlobalShortCodes\ShortCodes;

use Illuminate\Support\Arr;
use WPCCrawler\Objects\GlobalShortCodes\ShortCodes\Base\BaseGlobalShortCode;

/**
 * Makes it possible to output any HTML code. Structure: <b>[wpcc-element _tag="video" ...]...[/wpcc-element]</b>
 * The tag of the element to be rendered is specified in `_tag` attribute. The attributes non-specific to the short code
 * are added to the created element. If there is a content, it is added as the inner HTML of the element. The content
 * can be made up of [wpcc-element] short codes, as well. However, it is **NOT** possible to render a DOM tree
 * correctly, since WordPress's short code parser cannot handle nested short codes.
 */
class ElementGlobalShortCode extends BaseGlobalShortCode {

    const TAG_NAME = "wpcc-element";

    /** @var string Defines the tag name of the element to be created */
    const ATTR_TAG = '_tag';

    /** @var string If this is "0", there will be no closing tag for the created element. */
    const ATTR_CLOSE = '_close';

    public function getTagName(): string {
        return self::TAG_NAME;
    }

    protected function parse($attributes, $content): ?string {
        // Get the tag name of the element that will be created. If there is none, stop, since we cannot create an
        // element whose tag name is unknown.
        $tagName = Arr::pull($attributes, self::ATTR_TAG);
        $close   = Arr::pull($attributes, self::ATTR_CLOSE) !== '0';
        if (!is_string($tagName)) {
            return null;
        }

        $tagName = trim($tagName);
        if ($tagName === '') {
            return null;
        }

        // Create the attributes of the HTML element
        $elementAttrs = [];
        foreach($attributes as $attrName => $attrValue) {
            $elementAttrs[] = "{$attrName}=\"{$attrValue}\"";
        }

        // Combine the attributes and add a space before the combined string, to make the formatting of the output
        // pretty.
        $elementAttrsStr = $elementAttrs
            ? ' ' . implode(' ', $elementAttrs)
            : '';

        // Create the content. The content might have short codes as well. So, apply the short codes in the content.
        $content = do_shortcode($content ?? '');

        // Output the element
        return $close
            ? sprintf('<%1$s%2$s>%3$s</%1$s>', $tagName, $elementAttrsStr, $content)
            : sprintf('<%1$s%2$s />', $tagName, $elementAttrsStr);
    }

    protected function getDefaults() {
        return null;
    }
}