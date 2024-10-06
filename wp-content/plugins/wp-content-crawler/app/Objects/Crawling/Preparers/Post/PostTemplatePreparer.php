<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 02/11/2018
 * Time: 14:48
 */

namespace WPCCrawler\Objects\Crawling\Preparers\Post;

use Closure;
use DOMElement;
use WPCCrawler\Objects\Crawling\Data\PostData;
use WPCCrawler\Objects\Crawling\Preparers\Post\Base\AbstractPostBotPreparer;
use WPCCrawler\Objects\Crawling\Preparers\TransformablePreparer;
use WPCCrawler\Objects\Enums\ShortCodeName;
use WPCCrawler\Objects\File\FileService;
use WPCCrawler\Objects\File\MediaFile;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Objects\ShortCode\ShortCodeApplier;
use WPCCrawler\Objects\Traits\FindAndReplaceTrait;
use WPCCrawler\PostDetail\PostDetailsService;
use WPCCrawler\Utils;

class PostTemplatePreparer extends AbstractPostBotPreparer {

    use FindAndReplaceTrait;

    /** @var PostData */
    private $postData;

    //

    /** @var string */
    private $mainTitleShortCodeValue = '';

    /** @var string */
    private $mainListShortCodeValue = '';

    /** @var string */
    private $mainContentShortCodeValue = '';

    /** @var string */
    private $mainGalleryShortCodeValue = '';

    /** @var string */
    private $mainExcerptShortCodeValue = '';

    //

    /** @var array|null */
    private $customShortCodeValueMap = null;

    /** @var null|array */
    private $findAndReplacesForMedia = null;

    /**
     * Prepare the post bot
     *
     * @return void
     */
    public function prepare() {
        // Store the post data in the instance so that we can reach it easily.
        $this->postData = $this->bot->getPostData();

        // Prepare values of short codes
        $this->prepareMainTitleShortCodeValue();
        $this->prepareMainExcerptShortCodeValue();
        $this->prepareMainListShortCodeValue();
        $this->prepareMainContentShortCodeValue();
        $this->prepareMainGalleryShortCodeValue();

        // Define short code names and their values
        $shortCodeValueMap = [
            ShortCodeName::WCC_MAIN_TITLE   => $this->mainTitleShortCodeValue,
            ShortCodeName::WCC_MAIN_EXCERPT => $this->mainExcerptShortCodeValue,
            ShortCodeName::WCC_MAIN_CONTENT => $this->mainContentShortCodeValue,
            ShortCodeName::WCC_MAIN_LIST    => $this->mainListShortCodeValue,
            ShortCodeName::WCC_SOURCE_URL   => $this->bot->getPostUrl(),
            ShortCodeName::WCC_MAIN_GALLERY => $this->mainGalleryShortCodeValue
        ];

        // Prepare the main template using the short code values
        $this->prepareMainTemplate($shortCodeValueMap);

        // Change the main content short code's value with the prepared template
        $shortCodeValueMap[ShortCodeName::WCC_MAIN_CONTENT] = $this->postData->getTemplate();

        // Prepare templates defined in Options Box
        $this->applyOptionsBoxTemplates($shortCodeValueMap);

        // Store the value map in the post data so that the filter commands that need it can retrieve and use it to
        // apply the short codes.
        $this->bot->getPostData()
            ->setShortCodeValueMap($shortCodeValueMap + $this->getCustomShortCodeValueMap());
    }

    /*
     * TEMPLATE PREPARATION
     */

    /**
     * Replaces short codes in the templates defined in Options Boxes.
     *
     * @param array $shortCodeValueMap Already-known short code names and values
     */
    private function applyOptionsBoxTemplates(&$shortCodeValueMap): void {
        // Change file names
        $frForMedia = $this->applyShortCodesInMediaFileNames($shortCodeValueMap);

        // Prepare an array containing all short codes and their values
        $map = $shortCodeValueMap
            + ShortCodeApplier::getPredefinedShortCodeClearanceMap()
            + $this->getCustomShortCodeValueMap();

        // Make the replacements
        $fields = [
            PostData::FIELD_TITLE,
            PostData::FIELD_EXCERPT,
            PostData::FIELD_TEMPLATE,
            PostData::FIELD_SLUG,
            PostData::FIELD_CATEGORY_NAMES,
            PostData::FIELD_PREPARED_TAGS,
            PostData::FIELD_NEXT_PAGE_URL,
            PostData::FIELD_ALL_PAGE_URLS,
            PostData::FIELD_CUSTOM_META,
            PostData::FIELD_CUSTOM_TAXONOMIES,
        ];

        $shortCodeApplierRegular = $this->createShortCodeApplier($map);
        $preparer = new TransformablePreparer($this->postData, $fields, function($text) use (&$frForMedia, $shortCodeApplierRegular) {
            $text = $this->findAndReplace($frForMedia, $text);
            return $shortCodeApplierRegular->apply($text);
        });
        $preparer->prepare();

        // Media files
        $shortCodeApplierFile = $this->createShortCodeApplier($map, true);
        $this->prepareMediaTemplates($shortCodeApplierRegular, $shortCodeApplierFile, $frForMedia);

        // Prepare templates defined in options boxes of other post details implementations
        PostDetailsService::getInstance()
            ->prepareTemplates($this->getBot(), $shortCodeApplierRegular, $shortCodeApplierFile, $frForMedia);
    }

    /**
     * Applies short codes existing in the names of the media files
     *
     * @param array $shortCodeValueMap Already-known short code names and values
     * @return array Find and replace configurations that can be used to replace old file URLs with the changed ones
     * @since 1.8.0
     */
    private function applyShortCodesInMediaFileNames(&$shortCodeValueMap): array {
        $frForMedia = [];
        $applierFile    = $this->createShortCodeApplier($shortCodeValueMap, true);
        $applierRegular = $this->createShortCodeApplier($shortCodeValueMap);
        foreach($this->postData->getAllMediaFiles() as $mediaFile) {
            // Replace short codes in the name of the file
            $currentFindReplaceForMedia = FileService::getInstance()
                ->applyShortCodesToMediaFileName($mediaFile, $applierFile, $applierRegular);
            if (!$currentFindReplaceForMedia) continue;

            // Collect find-replace configurations
            $frForMedia = array_merge($frForMedia, $currentFindReplaceForMedia);
        }

        // Replace previous local URLs in the short code value map with new local URLs since the names of the files
        // have just been changed
        foreach($shortCodeValueMap as &$v) {
            $v = $this->findAndReplace($frForMedia, $v);
        }
        foreach($this->getCustomShortCodeValueMap() as &$v) {
            $v = $this->findAndReplace($frForMedia, $v);
        }

        return $frForMedia;
    }

    /**
     * @param ShortCodeApplier $applierRegular Short code applier that uses the default brackets
     * @param ShortCodeApplier $applierFile    Short code applier that uses the file-name-specific brackets
     * @param array            $frForMedia     Find-replaces for media
     * @since 1.8.0
     */
    private function prepareMediaTemplates(ShortCodeApplier $applierRegular, ShortCodeApplier $applierFile, $frForMedia): void {
        // Create a dummy crawler for the post template
        $dummyTemplateCrawler = $this->bot->createDummyCrawler($this->postData->getTemplate());

        foreach($this->postData->getAllMediaFiles() as $mediaFile) {
            // Because the name of the file can also be used in these, we need to consider short codes of the file names
            // as well.

            // NOTE: Because we apply find-replaces for media, source media URL is replaced with its local URL. Hence,
            // currently, it is not possible to show source media URL in the media's details such as title, description,
            // etc.
            $mediaFile->setMediaTitle(ShortCodeApplier::applyShortCodesForFileName($applierRegular, $applierFile, $mediaFile->getMediaTitle(), $frForMedia));
            $mediaFile->setMediaDescription(ShortCodeApplier::applyShortCodesForFileName($applierRegular, $applierFile, $mediaFile->getMediaDescription(), $frForMedia));
            $mediaFile->setMediaCaption(ShortCodeApplier::applyShortCodesForFileName($applierRegular, $applierFile, $mediaFile->getMediaCaption(), $frForMedia));
            $mediaFile->setMediaAlt(ShortCodeApplier::applyShortCodesForFileName($applierRegular, $applierFile, $mediaFile->getMediaAlt(), $frForMedia));

            // Set media alt and title in the elements having this media's local URL as their 'src' value
            $this->bot->modifyMediaElement($dummyTemplateCrawler, $mediaFile, function(MediaFile $mediaFile, DOMElement $element) {
                // If there is media alt value, set it.
                if ($mediaFile->getMediaAlt() !== '') {
                    $element->setAttribute('alt', $mediaFile->getMediaAlt());
                } else {
                    // Otherwise, make sure the element does not have 'alt' attribute.
                    $element->removeAttribute('alt');
                }

                // If there is media title value, set it
                if ($mediaFile->getMediaTitle() !== '') {
                    $element->setAttribute('title', $mediaFile->getMediaTitle());
                } else {
                    // Otherwise, make sure the element does not have 'title' attribute.
                    $element->removeAttribute('title');
                }

            });
        }

        // Set the modified template
        $this->postData->setTemplate($this->bot->getContentFromDummyCrawler($dummyTemplateCrawler));
    }

    /*
     * PREPARATION OF SHORT CODE VALUES
     */

    /**
     * Prepares main title short code's value and assigns title to {@link $postData}
     */
    private function prepareMainTitleShortCodeValue(): void {
        $templatePostTitle = $this->bot->getStringSetting(SettingKey::POST_TEMPLATE_TITLE, '[' . ShortCodeName::WCC_MAIN_TITLE . ']') ?? '';

        // If there is no template, stop.
        if($templatePostTitle === '') return;

        $applier = $this->createShortCodeApplier([
            ShortCodeName::WCC_MAIN_TITLE => $this->postData->getTitle() ?? '',
        ]);

        $templatePostTitle = $applier->apply($templatePostTitle);

        // Clear remaining predefined short codes
        $templatePostTitle = $applier->clearPredefinedShortCodes($templatePostTitle);

        $this->postData->setTitle($templatePostTitle);

        $this->mainTitleShortCodeValue = $templatePostTitle;
    }

    /**
     * Prepares main excerpt short code's value and assigns excerpt in {@link $postData}
     */
    private function prepareMainExcerptShortCodeValue(): void {
        $templatePostExcerpt = $this->bot->getStringSetting(SettingKey::POST_TEMPLATE_EXCERPT, '[' . ShortCodeName::WCC_MAIN_EXCERPT . ']') ?? '';

        $excerpt = $this->postData->getExcerpt() ?? [];

        $applier = $this->createShortCodeApplier([
            ShortCodeName::WCC_MAIN_TITLE   => $this->postData->getTitle() ?? '',
            ShortCodeName::WCC_MAIN_EXCERPT => $excerpt['data'] ?? '',
        ]);
        $templatePostExcerpt = $applier->apply($templatePostExcerpt);

        // Clear remaining predefined short codes
        $templatePostExcerpt = $applier->clearPredefinedShortCodes($templatePostExcerpt);

        $excerpt["data"] = $templatePostExcerpt;
        $this->postData->setExcerpt($excerpt);

        $this->mainExcerptShortCodeValue = $templatePostExcerpt;
    }

    /**
     * Prepares main list short code's value
     */
    private function prepareMainListShortCodeValue(): void {
        $postIsListType             = $this->bot->getSetting(SettingKey::POST_IS_LIST_TYPE);
        $templateListItem           = $this->bot->getStringSetting(SettingKey::POST_TEMPLATE_LIST_ITEM);
        $postListNumberAutoInsert   = $this->bot->getSetting(SettingKey::POST_LIST_ITEM_AUTO_NUMBER);
        $postListInsertReversed     = $this->bot->getSetting(SettingKey::POST_LIST_INSERT_REVERSED);

        if (!$postIsListType) return;

        // If there is no list template, create a default one.
        if (!$templateListItem) {
            $templateListItem = sprintf(
                '[%1$s] [%2$s]<br>[%3$s]<br>',
                ShortCodeName::WCC_LIST_ITEM_POSITION,
                ShortCodeName::WCC_LIST_ITEM_TITLE,
                ShortCodeName::WCC_LIST_ITEM_CONTENT
            );
        }

        $listItems = [];

        // Combine each element and sort them according to their position in DOM ascending
        if(empty($this->bot->combinedListData)) {
            $this->bot->combinedListData = Utils::combineArrays(
                $this->bot->combinedListData,
                $this->postData->getListNumbers(),
                $this->postData->getListTitles(),
                $this->postData->getListContents()
            );
        }

        if(empty($this->bot->combinedListData)) return;

        // Sort the list data according to the elements' start position
        $this->bot->combinedListData = Utils::array_msort($this->bot->combinedListData, ['start' => SORT_ASC]);

        // Now, match.
        $listItems[] = []; // Add an empty array to initialize
        foreach($this->bot->combinedListData as $listData) {
            $dataType = $listData["type"];
            $val = $listData["data"];

            //  If the last item of listItems has "list_content", and this data is also a "list_content", then
            // append it to the last item's list_content.
            //  If the last item of listItems has "list_number", then add a new array to the listItems with the
            // value of "list_number". If the last item does not have "list_number", then add a "list_number" to
            // the last item of listItems. Do this for each key other than "list_content".
            //  By this way, we are able to combine relevant data for each list item into one array.

            if(isset($listItems[sizeof($listItems) - 1][$dataType])) {
                if($dataType != "list_content") {
                    $listItems[] = [
                        $dataType => $val
                    ];
                } else {
                    $listItems[sizeof($listItems) - 1][$dataType] .= $val;
                }

            } else {
                $listItems[sizeof($listItems) - 1][$dataType] = $val;
            }
        }

        // Insert list items into template
        foreach ($listItems as $key => &$item) {
            $valueMap = [
                ShortCodeName::WCC_LIST_ITEM_TITLE    => $item['list_title'] ?? '',
                ShortCodeName::WCC_LIST_ITEM_CONTENT  => $item['list_content'] ?? '',
                ShortCodeName::WCC_LIST_ITEM_POSITION => $item['list_number'] ?? ($postListNumberAutoInsert ? $key + 1 : ''),
            ];
            $item["template"] = (new ShortCodeApplier($valueMap, $this->getBot()->getSettingsImpl()))
                ->apply($templateListItem);
        }

        // Combine list contents and create main list short code value
        $this->mainListShortCodeValue = '';

        // Reverse the array, if it is desired
        if($postListInsertReversed) $listItems = array_reverse($listItems);

        foreach($listItems as $mItem) {
            if(isset($mItem["template"])) {
                $this->mainListShortCodeValue .= $mItem["template"];
            }
        }

    }

    /**
     * Prepares main content short code's value
     */
    private function prepareMainContentShortCodeValue(): void {
        $findAndReplacesForCombinedContent = $this->bot->prepareFindAndReplaces([]);

        $this->mainContentShortCodeValue = '';
        $contents = $this->postData->getContents();
        if (!$contents) return;

        foreach ($contents as $content) {
            if (isset($content["data"])) {
                $this->mainContentShortCodeValue .= $content["data"];
            }
        }

        $this->mainContentShortCodeValue = $this->findAndReplace($findAndReplacesForCombinedContent, $this->mainContentShortCodeValue);
    }

    /**
     * Prepares main gallery short code's value
     */
    private function prepareMainGalleryShortCodeValue(): void {
        $templateGalleryItem = $this->bot->getStringSetting(SettingKey::POST_TEMPLATE_GALLERY_ITEM, '[' . ShortCodeName::WCC_GALLERY_ITEM_URL . ']') ?? '';

        $this->mainGalleryShortCodeValue = '';

        $mediaFiles = $this->postData->getAttachmentData();
        if(!$mediaFiles || $templateGalleryItem === '') {
            return;
        }

        // Prepare each item and append it to the main gallery template
        foreach ($mediaFiles as $mediaFile) {
            if (!$mediaFile->isGalleryImage() || empty($mediaFile->getLocalUrl())) {
                continue;
            }

            $this->mainGalleryShortCodeValue .= (new ShortCodeApplier(
                [ShortCodeName::WCC_GALLERY_ITEM_URL => $mediaFile->getLocalUrl()],
                $this->getBot()->getSettingsImpl()
            ))
                ->apply($templateGalleryItem);
        }

    }

    /**
     * Prepares the main template of the post
     *
     * @param array $shortCodeValueMap
     * @since 1.8.0
     */
    private function prepareMainTemplate($shortCodeValueMap): void {
        $templateMain                        = $this->bot->getStringSetting(SettingKey::POST_TEMPLATE_MAIN, '[' . ShortCodeName::WCC_MAIN_CONTENT . ']') ?? '';
        $findAndReplacesForTemplate          = $this->bot->getSetting(SettingKey::POST_FIND_REPLACE_TEMPLATE);
        $templateUnnecessaryElementSelectors = $this->bot->getSetting(SettingKey::TEMPLATE_UNNECESSARY_ELEMENT_SELECTORS);

        $template = $templateMain;

        // Replace all short codes with their values in the main template
        $shortCodeApplier = $this->createShortCodeApplier($shortCodeValueMap);
        $template = $shortCodeApplier->apply($template);

        // Clear the post content from unnecessary elements
        if(!empty($templateUnnecessaryElementSelectors)) {
            // Create a crawler using the HTML of the template
            $templateCrawler = $this->bot->createDummyCrawler($template);

            // Remove the elements from the crawler
            $this->bot->removeElementsFromCrawler($templateCrawler, $templateUnnecessaryElementSelectors);

            // Get the HTML of body tag for the template.
            $template = $this->bot->getContentFromDummyCrawler($templateCrawler);
        }

        // Find and replace for template
        $template = $this->findAndReplace($findAndReplacesForTemplate, $template);

        // Clear remaining predefined short codes
        $template = $shortCodeApplier->clearPredefinedShortCodes($template);

        // Set the template
        $this->postData->setTemplate($template);
    }

    /*
     * HELPERS
     */

    /**
     * @param array<string, string|string[]|Closure> $valueMap See {@link ShortCodeApplier::__construct()}
     * @param bool                                   $forFile `true` if the applier is going to be used to replace short
     *                                                        codes in a file name.
     * @return ShortCodeApplier A new applier that also applies the custom short codes
     * @since 1.13.0
     */
    private function createShortCodeApplier(array $valueMap, bool $forFile = false): ShortCodeApplier {
        $map = $valueMap + $this->getCustomShortCodeValueMap();
        $settings = $this->getBot()->getSettingsImpl();
        return $forFile
            ? new ShortCodeApplier($map, $settings, FileService::SC_OPENING_BRACKETS, FileService::SC_CLOSING_BRACKETS)
            : new ShortCodeApplier($map, $settings);
    }

    /**
     * Get an array that contains custom short code names as the keys, and the short code values as the values. E.g.
     * [short_code_name => value1, short_code_name => value2]
     *
     * @return array
     */
    private function getCustomShortCodeValueMap(): array {
        if ($this->customShortCodeValueMap === null) {
            $this->customShortCodeValueMap = $this->createCustomShortCodeValueMap();
        }

        return $this->customShortCodeValueMap;
    }

}
