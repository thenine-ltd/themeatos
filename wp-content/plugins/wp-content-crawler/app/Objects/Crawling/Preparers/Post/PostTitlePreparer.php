<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 02/11/2018
 * Time: 11:14
 */

namespace WPCCrawler\Objects\Crawling\Preparers\Post;

use WPCCrawler\Objects\Crawling\Preparers\Post\Base\AbstractPostBotPreparer;
use WPCCrawler\Objects\Settings\Enums\SettingKey;

class PostTitlePreparer extends AbstractPostBotPreparer {

    /**
     * Prepare the post bot
     *
     * @return void
     */
    public function prepare() {
        $title = $this->getValuesForSelectorSetting(SettingKey::POST_TITLE_SELECTORS, 'text', false, true, true);
        if (!is_string($title)) {
            return;
        }

        $bot = $this->getBot();

        $findAndReplacesForTitle = $bot->prepareFindAndReplaces($bot->getArraySetting(SettingKey::POST_FIND_REPLACE_TITLE, null));
        $title = $bot->findAndReplace($findAndReplacesForTitle, $title);

        $bot->getPostData()->setTitle($title);
    }

}
