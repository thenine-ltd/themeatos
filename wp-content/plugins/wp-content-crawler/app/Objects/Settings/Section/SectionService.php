<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 26/02/2023
 * Time: 21:39
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Settings\Section;

use WPCCrawler\Objects\Enums\SectionKey;
use WPCCrawler\Objects\Enums\TabKey;
use WPCCrawler\Objects\Settings\SettingService;
use WPCCrawler\Services\UserPrefsService;
use WPCCrawler\Utils;

/**
 * Handles things related to the sections of settings in the UI, such as saving and retrieving the expansion states of
 * the sections.
 *
 * @since 1.13.0
 */
class SectionService {

    const PREF_KEY = 'sectionStates';

    const KEY_PAGE_TYPE = 'pageType';
    const KEY_SITE_ID   = 'siteId';
    const KEY_STATES    = 'states';

    /**
     * Handles the AJAX request made to update the section states
     *
     * @param array $data The section states
     * @return string JSON response
     * @since 1.13.0
     */
    public function handleSaveSectionStateRequest(array $data): string {
        if (SettingService::isSavingSectionStateDisabled()) {
            return $this->createResponse(false);
        }

        $pageTypeRaw = Utils::array_get($data, self::KEY_PAGE_TYPE);
        $pageType = is_string($pageTypeRaw)
            ? $pageTypeRaw
            : null;

        $siteIdRaw = Utils::array_get($data, self::KEY_SITE_ID);
        $siteId = is_numeric($siteIdRaw)
            ? (int) $siteIdRaw
            : -1;
        $siteId = $siteId <= 0
            ? null
            : $siteId;

        $statesRaw = Utils::array_get($data, self::KEY_STATES);

        $success = is_array($statesRaw) && $this->saveSectionStates($pageType, $siteId, $statesRaw);
        return $this->createResponse($success);
    }

    /**
     * Assigns the default section states for the site settings
     *
     * @param array    $currentPrefs Current user preferences
     * @param int|null $siteId       ID of the site for which the default section states will be assigned
     * @return array User preferences with defaults related to the section states assigned
     * @since 1.13.0
     */
    public function setDefaults(array $currentPrefs, ?int $siteId): array {
        // If there is no site ID, do not change the preferences. This is because only the site settings page's sections
        // have default values.
        if ($siteId === null) {
            return $currentPrefs;
        }

        $currentPrefs[self::PREF_KEY] = array_map(function(SectionState $state) {
            return $state->toArray();
        }, $this->createDefaultSiteSectionStates());

        return $currentPrefs;
    }

    /**
     * @param array    $currentPrefs Current user preferences
     * @param int|null $siteId       ID of the site that will be checked if it needs the default states
     * @return bool `true` if the current preferences need the default states
     * @since 1.13.0
     */
    public function shouldAssignDefaults(array $currentPrefs, ?int $siteId): bool {
        if ($siteId === null) {
            return false;
        }

        $sectionStates = $currentPrefs[self::PREF_KEY] ?? null;
        if ($sectionStates !== null || SettingService::isDefaultSiteSectionStatesDisabled()) {
            return false;
        }

        return true;
    }

    /**
     * @return SectionState[] Default states of the sections in the site settings page
     * @since 1.13.0
     */
    public function createDefaultSiteSectionStates(): array {
        return [
            // Main tab
            new SectionState(TabKey::SITE_SETTINGS_TAB_MAIN, SectionKey::SITE_SETTINGS_MAIN_CUSTOMIZATIONS, true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_MAIN, SectionKey::SITE_SETTINGS_MAIN_REQUEST,        true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_MAIN, SectionKey::SITE_SETTINGS_MAIN_SETTINGS_PAGE,  true),

            // Category tab
            new SectionState(TabKey::SITE_SETTINGS_TAB_CATEGORY, SectionKey::SITE_SETTINGS_CATEGORY_NEXT_PAGE,            true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_CATEGORY, SectionKey::SITE_SETTINGS_CATEGORY_FEATURED_IMAGES,      true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_CATEGORY, SectionKey::SITE_SETTINGS_CATEGORY_MANIPULATE_HTML,      true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_CATEGORY, SectionKey::SITE_SETTINGS_CATEGORY_UNNECESSARY_ELEMENTS, true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_CATEGORY, SectionKey::SITE_SETTINGS_CATEGORY_FILTERS,              true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_CATEGORY, SectionKey::SITE_SETTINGS_CATEGORY_NOTIFICATIONS,        true),

            // Post tab
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_CATEGORY,             true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_DATE,                 true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_META,                 true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_FEATURED_IMAGE,       true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_IMAGES,               true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_CUSTOM_SHORT_CODES,   true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_LIST_TYPE_POSTS,      true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_PAGINATION,           true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_POST_META,            true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_TAXONOMIES,           true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_MANIPULATE_HTML,      true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_UNNECESSARY_ELEMENTS, true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_FILTERS,              true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_NOTIFICATIONS,        true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_POST, SectionKey::SITE_SETTINGS_POST_OTHER,                true),

            // Templates tab
            new SectionState(TabKey::SITE_SETTINGS_TAB_TEMPLATES, SectionKey::SITE_SETTINGS_TEMPLATES_ITEM_TEMPLATES,       true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_TEMPLATES, SectionKey::SITE_SETTINGS_TEMPLATES_UNNECESSARY_ELEMENTS, true),
            new SectionState(TabKey::SITE_SETTINGS_TAB_TEMPLATES, SectionKey::SITE_SETTINGS_TEMPLATES_MANIPULATE_HTML,      true),
        ];
    }

    /*
     * PROTECTED HELPERS
     */

    /**
     * @param bool $success `true` if the section state is saved. Otherwise, `false`.
     * @return string JSON-encoded response that can be sent to the client
     * @since 1.13.0
     */
    protected function createResponse(bool $success): string {
        return json_encode([
            'success' => $success
        ]) ?: '{}';
    }

    /**
     * @param int|null $siteId    ID of the site whose state changes will be saved
     * @param array    $rawStates The section states
     * @return bool `true` if the state changes have been saved successfully. Otherwise, `false`.
     * @since 1.13.0
     */
    protected function saveSectionStates(?string $pageType, ?int $siteId, array $rawStates): bool {
        // Get the user ID. We will store the section states for the current user, so that each user can have different
        // section states.
        $userId = get_current_user_id();
        if ($userId <= 0) {
            return false;
        }

        $stateMap = $this->createStateMap($rawStates);
        $prefService = new UserPrefsService();

        // If there is a site ID, it means the sections are from the site settings page. Because there can be multiple
        // sites, we will store the state in a post meta of the site.
        if ($siteId !== null) {
            $currentPrefs = $prefService->getUserPreferencesAsArray(null, $siteId);
            $prefService->updateUserPreferencesForSite($siteId, $this->syncSectionStates($currentPrefs, $stateMap));

        } else {
            // The states are from a page that cannot be more than one. In this case, we will store the states as a
            // user meta.
            $currentPrefs = $prefService->getUserPreferencesAsArray($pageType, null);
            $prefService->updateUserPreferencesForPage($pageType, $this->syncSectionStates($currentPrefs, $stateMap));
        }

        return true;
    }

    /**
     * @param array                $currentPrefs The current preferences of the user, retrieved from the database.
     * @param array<string, array> $stateMap     The new states of certain sections. See {@link createStateMap()}
     * @return array The new preferences of the user, including the new states.
     * @since 1.13.0
     */
    protected function syncSectionStates(array $currentPrefs, array $stateMap): array {
        $currentStatesPref = $currentPrefs[self::PREF_KEY] ?? [];
        if (!is_array($currentStatesPref)) {
            $currentStatesPref = [];
        }

        // Sync the new states. If there was a state for a section, it will be overridden.
        $newStatesPref = array_merge(
            $this->createStateMap($currentStatesPref),
            $stateMap
        );

        // Assign the new states without their keys, to not store unnecessary data.
        $currentPrefs[self::PREF_KEY] = array_values($newStatesPref);
        return $currentPrefs;
    }

    /**
     * @param array $rawStates The states retrieved from the UI
     * @return array<string, array> Prepared states. The keys are identifiers of the states, unique in their page. The
     *                              values are the states.
     * @since 1.13.0
     */
    protected function createStateMap(array $rawStates): array {
        $result = [];
        foreach($rawStates as $rawState) {
            $state = SectionState::fromArray($rawState);
            if (!$state) continue;

            $result[$state->getKey()] = $state->toArray();
        }

        return $result;
    }

}