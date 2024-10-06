<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 02/11/2018
 * Time: 11:28
 */

namespace WPCCrawler\Objects\Crawling\Preparers\Post;

use WPCCrawler\Objects\Crawling\Preparers\Post\Base\AbstractPostBotPreparer;
use WPCCrawler\Objects\Settings\Enums\SettingKey;

class PostExcerptPreparer extends AbstractPostBotPreparer {

    /**
     * Prepare the post bot
     *
     * @return void
     */
    public function prepare() {
        $excerpt = $this->getValuesForSelectorSetting(SettingKey::POST_EXCERPT_SELECTORS, 'html', 'excerpt', true, true);
        if (!is_array($excerpt)) {
            return;
        }

        $bot = $this->getBot();

        $findAndReplacesForExcerpt = $bot->prepareFindAndReplaces($bot->getArraySetting(SettingKey::POST_FIND_REPLACE_EXCERPT, null));
        $excerpt["data"] = $bot->findAndReplace($findAndReplacesForExcerpt, $excerpt["data"] ?? '');

        $bot->getPostData()->setExcerpt($excerpt);
    }
}
