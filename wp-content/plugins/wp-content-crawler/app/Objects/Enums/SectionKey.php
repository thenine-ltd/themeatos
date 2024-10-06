<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 28/02/2023
 * Time: 13:26
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Enums;

abstract class SectionKey {

    // SITE SETTINGS PAGE KEYS
    // Main tab
    const SITE_SETTINGS_MAIN_CUSTOMIZATIONS = 'section-ss-main-customizations';
    const SITE_SETTINGS_MAIN_REQUEST        = 'section-ss-main-request';
    const SITE_SETTINGS_MAIN_SETTINGS_PAGE  = 'section-ss-main-settings-page';

    // Category tab
    const SITE_SETTINGS_CATEGORY_NEXT_PAGE            = 'section-ss-category-next-page';
    const SITE_SETTINGS_CATEGORY_FEATURED_IMAGES      = 'section-ss-category-featured-images';
    const SITE_SETTINGS_CATEGORY_MANIPULATE_HTML      = 'section-ss-category-manipulate-html';
    const SITE_SETTINGS_CATEGORY_UNNECESSARY_ELEMENTS = 'section-ss-category-unnecessary-elements';
    const SITE_SETTINGS_CATEGORY_FILTERS              = 'section-ss-category-filters';
    const SITE_SETTINGS_CATEGORY_NOTIFICATIONS        = 'section-ss-category-notifications';

    // Post tab
    const SITE_SETTINGS_POST_CATEGORY             = 'section-ss-post-category';
    const SITE_SETTINGS_POST_WOOCOMMERCE          = 'section-ss-post-woocommerce';
    const SITE_SETTINGS_POST_DATE                 = 'section-ss-post-date';
    const SITE_SETTINGS_POST_META                 = 'section-ss-post-meta';
    const SITE_SETTINGS_POST_FEATURED_IMAGE       = 'section-ss-post-featured-image';
    const SITE_SETTINGS_POST_IMAGES               = 'section-ss-post-images';
    const SITE_SETTINGS_POST_CUSTOM_SHORT_CODES   = 'section-ss-post-custom-short-codes';
    const SITE_SETTINGS_POST_LIST_TYPE_POSTS      = 'section-ss-post-list-type-posts';
    const SITE_SETTINGS_POST_PAGINATION           = 'section-ss-post-pagination';
    const SITE_SETTINGS_POST_POST_META            = 'section-ss-post-post-meta';
    const SITE_SETTINGS_POST_TAXONOMIES           = 'section-ss-post-taxonomies';
    const SITE_SETTINGS_POST_MANIPULATE_HTML      = 'section-ss-post-manipulate-html';
    const SITE_SETTINGS_POST_UNNECESSARY_ELEMENTS = 'section-ss-post-unnecessary-elements';
    const SITE_SETTINGS_POST_FILTERS              = 'section-ss-post-filters';
    const SITE_SETTINGS_POST_NOTIFICATIONS        = 'section-ss-post-notifications';
    const SITE_SETTINGS_POST_OTHER                = 'section-ss-post-other';

    // Templates tab
    const SITE_SETTINGS_TEMPLATES_ITEM_TEMPLATES       = 'section-ss-templates-item-templates';
    const SITE_SETTINGS_TEMPLATES_QUICK_FIXES          = 'section-ss-templates-quick-fixes';
    const SITE_SETTINGS_TEMPLATES_UNNECESSARY_ELEMENTS = 'section-ss-templates-unnecessary-elements';
    const SITE_SETTINGS_TEMPLATES_MANIPULATE_HTML      = 'section-ss-templates-manipulate-html';

    // GENERAL SETTINGS PAGE KEYS
    // Scheduling tab
    const GENERAL_SETTINGS_SCHEDULING_RECRAWLING = 'section-gs-scheduling-recrawling';
    const GENERAL_SETTINGS_SCHEDULING_DELETING   = 'section-gs-scheduling-deleting';

    // Post tab
    const GENERAL_SETTINGS_POST_MEDIA       = 'section-gs-post-media';
    const GENERAL_SETTINGS_POST_SHORT_CODES = 'section-gs-post-short-codes';

    // Translation tab
    const GENERAL_SETTINGS_TRANSLATION_AMAZON_TRANSLATE          = 'section-gs-translation-amazon_translate';
    const GENERAL_SETTINGS_TRANSLATION_DEEPL_TRANSLATE           = 'section-gs-translation-deepl_translate';
    const GENERAL_SETTINGS_TRANSLATION_GOOGLE_TRANSLATE          = 'section-gs-translation-google_translate';
    const GENERAL_SETTINGS_TRANSLATION_MICROSOFT_TRANSLATOR_TEXT = 'section-gs-translation-microsoft_translator_text';
    const GENERAL_SETTINGS_TRANSLATION_YANDEX_TRANSLATE          = 'section-gs-translation-yandex_translate';

    // Spinning tab
    const GENERAL_SETTINGS_SPINNING_SPIN_REWRITER = 'section-gs-spinning-spin_rewriter';
    const GENERAL_SETTINGS_SPINNING_TURKCE_SPIN   = 'section-gs-spinning-turkce_spin';

    // APIs tab
    const GENERAL_SETTINGS_APIS_OPENAI = 'section-gs-apis-openai';

    // Advanced tab
    const GENERAL_SETTINGS_ADVANCED_PROXIES = 'section-gs-advanced-proxies';
    const GENERAL_SETTINGS_ADVANCED_OTHER   = 'section-gs-advanced-other';

}