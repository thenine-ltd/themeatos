<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 29/09/2023
 * Time: 16:13
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Views\Select;

use WPCCrawler\Objects\Views\Base\AbstractView;

class SelectTranslationLanguagesView extends AbstractView {

    public function getKey(): string {
        return 'form-items.combined.select-translation-langs-with-label';
    }

    protected function createVariableNames(): ?array {
        return null;
    }

}