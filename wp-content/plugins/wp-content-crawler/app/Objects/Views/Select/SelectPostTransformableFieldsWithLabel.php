<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 24/02/2023
 * Time: 10:35
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Objects\Views\Select;

use Illuminate\Contracts\View\View;
use WPCCrawler\Factory;
use WPCCrawler\Objects\Settings\SettingsImpl;
use WPCCrawler\Objects\Views\Base\AbstractSelectWithLabel;

class SelectPostTransformableFieldsWithLabel extends AbstractSelectWithLabel {

    const VAR_TRANSFORMABLE_FIELDS = 'transformableFields';

    public function getKey(): string {
        return 'form-items.combined.select-post-transformable-fields-with-label';
    }

    protected function onPrepareView(View $view, SettingsImpl $settings): void {
        parent::onPrepareView($view, $settings);

        // Add the transformable field options
        $view->with(self::VAR_TRANSFORMABLE_FIELDS, Factory::postService()->getTransformableFieldsOptions($settings));
    }
}