{{--
    Adds the user preferences

    Required variables:
        string   $pageType  One of the constants defined in PageType class
        int|null $siteId    ID of the site, if this is for a site settings page.
--}}

<?php

use WPCCrawler\Services\UserPrefsService;

$prefs = (new UserPrefsService())->getUserPreferences($pageType ?? null, $siteId ?? null) ?? '{}';

?>

<div id="user-prefs" class="hidden" data-prefs='{!! $prefs !!}'></div>