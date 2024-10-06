<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 27/02/2023
 * Time: 08:14
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Services;

use WPCCrawler\Objects\Enums\PageType;
use WPCCrawler\Objects\Settings\Section\SectionService;

/**
 * Stores and retrieves user preferences for the pages of the plugin
 *
 * @since 1.13.0
 */
class UserPrefsService {

    /** @var string The prefix used in the meta keys to store the user preferences */
    const PREF_KEY_PREFIX = '_wpcc_user_prefs';

    /**
     * Get the user preferences for a specific page
     *
     * @param string|null $pageType Type of the page. One of the constants defined in {@link PageType}.
     * @param int|null    $siteId   If the page is a site settings page, ID of the site.
     * @return string|null Preferences of the user for the specified page
     * @since 1.13.0
     */
    public function getUserPreferences(?string $pageType, ?int $siteId): ?string {
        // Get the user ID. We will retrieve the preferences for the current user.
        $userId = get_current_user_id();
        if ($userId <= 0) {
            return null;
        }

        // If there is a site ID, it means the sections are from the site settings page
        if ($siteId !== null) {
            $key = $this->createPrefMetaKeyForSite($userId);
            $currentPrefs = get_post_meta($siteId, $key, true);

        } else {
            $key = $this->createPrefMetaKeyForUser($pageType);
            $currentPrefs = get_user_meta($userId, $key, true);
        }

        // If the preferences do not exist, assign the defaults.
        if ($currentPrefs === null || $currentPrefs === '') {
            $currentPrefsArr = $this->maybeSetDefaults(
                $this->parsePrefs($currentPrefs),
                $pageType,
                $siteId
            );
            $currentPrefs = json_encode($currentPrefsArr);
        }

        return is_string($currentPrefs)
            ? $currentPrefs
            : null;
    }

    /**
     * Get the user preferences as an array
     *
     * @param string|null $pageType See {@link getUserPreferences()}
     * @param int|null    $siteId   See {@link getUserPreferences()}
     * @return array The user preferences, JSON-decoded
     * @since 1.13.0
     */
    public function getUserPreferencesAsArray(?string $pageType, ?int $siteId): array {
        $currentPrefs = $this->getUserPreferences($pageType, $siteId);
        return $this->parsePrefs($currentPrefs);
    }

    /**
     * Updates the user preferences for a site
     *
     * @param int   $siteId   ID of the site
     * @param array $newPrefs New user preferences
     * @since 1.13.0
     */
    public function updateUserPreferencesForSite(int $siteId, array $newPrefs): void {
        $userId = $this->getCurrentUserId();
        if ($userId === null) return;

        $key = $this->createPrefMetaKeyForSite($userId);
        update_post_meta($siteId, $key, $this->encodeUserPreferences($newPrefs));
    }

    /**
     * Updates the user preferences for a page
     *
     * @param string|null $pageType Type of the page. One of the constants defined in {@link PageType}.
     * @param array       $newPrefs New user preferences
     * @since 1.13.0
     */
    public function updateUserPreferencesForPage(?string $pageType, array $newPrefs): void {
        $userId = $this->getCurrentUserId();
        if ($userId === null) return;

        $key = $this->createPrefMetaKeyForUser($pageType);
        update_user_meta($userId, $key, $this->encodeUserPreferences($newPrefs));
    }

    /*
     * PROTECTED HELPERS
     */

    /**
     * @param array       $userPrefs Current user preferences
     * @param string|null $pageType  Type of the page. One of the constants defined in {@link PageType}.
     * @param int|null    $siteId    See {@link getUserPreferences()}
     * @return array User preferences with defaults assigned
     * @since 1.13.0
     */
    protected function maybeSetDefaults(array $userPrefs, ?string $pageType, ?int $siteId): array {
        $sectionService = new SectionService();
        if ($sectionService->shouldAssignDefaults($userPrefs, $siteId)) {
            $userPrefs = $sectionService->setDefaults($userPrefs, $siteId);
        }

        // Always update the prefs in the database, without checking if the user preferences are changed. This is
        // because we want to assign a value no matter what. By this way, the next time, because the preferences exist,
        // we will not try to set the default values. This method is called only if the user preferences do not exist.
        if ($siteId !== null) {
            $this->updateUserPreferencesForSite($siteId, $userPrefs);

        } else {
            $this->updateUserPreferencesForPage($pageType, $userPrefs);
        }

        return $userPrefs;
    }

    /**
     * Parses the user preferences in JSON-string format to an array
     *
     * @param mixed $jsonEncodedPrefs JSON-encoded preferences. If this is not a JSON string, an empty array will be
     *                                returned.
     * @return array Prefs array
     * @since 1.13.0
     */
    protected function parsePrefs($jsonEncodedPrefs): array {
        $prefsArr = is_string($jsonEncodedPrefs)
            ? json_decode($jsonEncodedPrefs, true)
            : [];
        if (!is_array($prefsArr)) {
            $prefsArr = [];
        }

        return $prefsArr;
    }

    /**
     * @param array $userPrefs User preferences as an array
     * @return string JSON-encoded user preferences
     * @since 1.13.0
     */
    protected function encodeUserPreferences(array $userPrefs): string {
        return json_encode($userPrefs) ?: '{}';
    }

    /**
     * @return int|null ID of the current user. If there is no user, returns `null`.
     * @since 1.13.0
     */
    protected function getCurrentUserId(): ?int {
        $userId = get_current_user_id();
        return $userId <= 0
            ? null
            : $userId;
    }

    /**
     * @param int $userId ID of the user whose preferences are wanted
     * @return string The meta key that stores the user's preferences for a specific site
     * @since 1.13.0
     */
    protected function createPrefMetaKeyForSite(int $userId): string {
        return sprintf('%1$s_%2$d', self::PREF_KEY_PREFIX, $userId);
    }

    /**
     * @param string|null $pageType Type of the page whose user preferences are wanted
     * @return string The meta key that stores the user preferences for the page type
     * @since 1.13.0
     */
    protected function createPrefMetaKeyForUser(?string $pageType): string {
        return sprintf('%1$s_page_%2$s', self::PREF_KEY_PREFIX, $pageType ?? 'undefined');
    }

}