<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 03/02/2023
 * Time: 16:55
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Chunk\LengthStrategy;

class UrlEncodedByteLengthStrategy extends ByteLengthStrategy {

    /** @var UrlEncodedByteLengthStrategy|null */
    private static $instance;

    /**
     * @return UrlEncodedByteLengthStrategy
     * @since 1.13.0
     */
    public static function getInstance(): UrlEncodedByteLengthStrategy {
        if (self::$instance === null) {
            self::$instance = new UrlEncodedByteLengthStrategy();
        }

        return self::$instance;
    }

    /**
     * Get length of a text
     *
     * @param string $text Text whose length is wanted
     * @return int The length of the text
     * @since 1.13.0
     */
    public function getLengthFor(string $text): int {
        return parent::getLengthFor(urlencode($text));
    }

}