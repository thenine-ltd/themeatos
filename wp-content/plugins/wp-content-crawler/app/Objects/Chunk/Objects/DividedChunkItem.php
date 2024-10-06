<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 06/02/2023
 * Time: 13:15
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Chunk\Objects;

/**
 * A chunk item that is one of the parts of a long text
 */
class DividedChunkItem extends ChunkItem {

    /** @var string */
    private $elementId;

    /** @var string */
    private $parentDotKey;

    /**
     * @param string $key
     * @param string $value
     * @param int    $length
     * @param string $elementId
     * @param string $parentDotKey
     * @since 1.13.0
     */
    public function __construct(string $key, string $value, int $length, string $elementId, string $parentDotKey) {
        parent::__construct($key, $value, $length);
        $this->elementId = $elementId;
        $this->parentDotKey = $parentDotKey;
    }

    /**
     * @return string
     * @since 1.13.0
     */
    public function getElementId(): string {
        return $this->elementId;
    }

    /**
     * @return string
     * @since 1.13.0
     */
    public function getParentDotKey(): string {
        return $this->parentDotKey;
    }

}