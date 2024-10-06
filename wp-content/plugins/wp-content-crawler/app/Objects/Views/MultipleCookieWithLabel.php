<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 27/06/2023
 * Time: 16:53
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Views;

use Illuminate\Contracts\View\View;
use WPCCrawler\Objects\Settings\SettingsImpl;
use WPCCrawler\Objects\Views\Base\AbstractViewWithLabel;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;

class MultipleCookieWithLabel extends AbstractViewWithLabel {

    public function getKey(): string {
        return 'form-items.combined.multiple-cookie-with-label';
    }

    protected function createViewVariableNames(): ?array {
        return null;
    }

    protected function onPrepareView(View $view, SettingsImpl $settings): void {
        $view
            ->with(ViewVariableName::HAS_EXPORT_BUTTON, true)
            ->with(ViewVariableName::HAS_IMPORT_BUTTON, true);
    }

}