<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 02/11/2018
 * Time: 11:31
 */

namespace WPCCrawler\Objects\Crawling\Preparers\Post;

use Illuminate\Support\Arr;
use WPCCrawler\Objects\Crawling\Preparers\Post\Base\AbstractPostBotPreparer;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Utils;

class PostContentsPreparer extends AbstractPostBotPreparer {

    /**
     * Prepare the post bot
     *
     * @return void
     */
    public function prepare() {
        $contents = $this->getValuesForSelectorSetting(SettingKey::POST_CONTENT_SELECTORS, 'html', 'content', false, true);
        if (!$contents || !is_array($contents)) {
            return;
        }

        $contents = Arr::flatten($contents, 1);
        $contents = array_values(Utils::array_msort($contents, ['start' => SORT_ASC]));
        $this->getBot()->getPostData()->setContents($contents);
    }

}
