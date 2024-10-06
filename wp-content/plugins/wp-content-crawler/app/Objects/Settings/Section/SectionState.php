<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 26/02/2023
 * Time: 23:31
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Settings\Section;

use WPCCrawler\Interfaces\Arrayable;
use WPCCrawler\Objects\Enums\SectionKey;
use WPCCrawler\Objects\Enums\TabKey;

class SectionState implements Arrayable {

    const KEY_TAB_ID     = 'tabId';
    const KEY_SECTION_ID = 'sectionId';
    const KEY_COLLAPSED  = 'collapsed';

    /** @var string ID of the tab that contains the section, one of the constants defined in {@link TabKey} */
    private $tabId;

    /** @var string ID of the section, one of the constants defined in {@link SectionKey} */
    private $sectionId;

    /** @var bool `true` if the section is collapsed. Otherwise, `false`. */
    private $collapsed;

    /**
     * @param string $tabId     See {@link $tabId}
     * @param string $sectionId See {@link $sectionId}
     * @param bool   $collapsed See {@link $collapsed}
     * @since 1.13.0
     */
    public function __construct(string $tabId, string $sectionId, bool $collapsed) {
        $this->tabId     = $tabId;
        $this->sectionId = $sectionId;
        $this->collapsed = $collapsed;
    }

    /**
     * @return string See {@link $tabId}
     * @since 1.13.0
     */
    public function getTabId(): string {
        return $this->tabId;
    }

    /**
     * @return string See {@link $sectionId}
     * @since 1.13.0
     */
    public function getSectionId(): string {
        return $this->sectionId;
    }

    /**
     * @return bool See {@link $collapsed}
     * @since 1.13.0
     */
    public function isCollapsed(): bool {
        return $this->collapsed;
    }

    /**
     * @return string Unique identifier for the section in its page
     * @since 1.13.0
     */
    public function getKey(): string {
        return sprintf('%1$s_%2$s', $this->getTabId(), $this->getSectionId());
    }

    public function toArray(): array {
        return [
            self::KEY_TAB_ID     => $this->getTabId(),
            self::KEY_SECTION_ID => $this->getSectionId(),
            self::KEY_COLLAPSED  => $this->isCollapsed()
                ? 1
                : 0,
        ];
    }

    /*
     * STATIC HELPERS
     */

    /**
     * @param array $state Raw state retrieved from the UI or the database
     * @return SectionState|null If the state information could be retrieved from the given array, a new
     *                           {@link SectionState} is returned. Otherwise, `null` is returned.
     * @since 1.13.0
     */
    public static function fromArray(array $state): ?SectionState {
        $tabId        = $state[self::KEY_TAB_ID]     ?? null;
        $sectionId    = $state[self::KEY_SECTION_ID] ?? null;
        $rawCollapsed = $state[self::KEY_COLLAPSED]  ?? null;
        if (!is_string($tabId) || !is_string($sectionId)) {
            return null;
        }

        $collapsed = $rawCollapsed === 'true' || $rawCollapsed === '1' || $rawCollapsed === 1;
        return new SectionState($tabId, $sectionId, $collapsed);
    }
}