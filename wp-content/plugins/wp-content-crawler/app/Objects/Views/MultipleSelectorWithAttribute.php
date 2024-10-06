<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 29/06/2023
 * Time: 12:06
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Views;

use Illuminate\Contracts\View\View;
use WPCCrawler\Objects\Enums\ShortCodeName;
use WPCCrawler\Objects\OptionsBox\Enums\OptionsBoxTab;
use WPCCrawler\Objects\OptionsBox\Enums\TabOptions\TemplatesTabOptions;
use WPCCrawler\Objects\OptionsBox\OptionsBoxConfiguration;
use WPCCrawler\Objects\Settings\SettingsImpl;
use WPCCrawler\Objects\Views\Base\AbstractViewWithLabel;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;

class MultipleSelectorWithAttribute extends AbstractViewWithLabel {

    public function getKey(): string {
        return 'form-items.combined.multiple-selector-with-attribute';
    }

    protected function createViewVariableNames(): ?array {
        return [
            ViewVariableName::URL_SELECTOR,
            ViewVariableName::DEFAULT_ATTR,
        ];
    }

    protected function onPrepareView(View $view, SettingsImpl $settings): void {
        $view
            ->with('optionsBox', OptionsBoxConfiguration::init()
                ->addTabOption(OptionsBoxTab::TEMPLATES, TemplatesTabOptions::ALLOWED_REGULAR_SHORT_CODES, [
                    ShortCodeName::WCC_ITEM,
                ])
                ->addTabOption(OptionsBoxTab::TEMPLATES, TemplatesTabOptions::ALLOW_CUSTOM_SHORT_CODES, false)
                ->get()
            );
    }
}