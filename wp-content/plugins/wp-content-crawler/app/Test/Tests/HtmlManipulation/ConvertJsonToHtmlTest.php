<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 07/07/2023
 * Time: 12:15
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Test\Tests\HtmlManipulation;

use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Objects\Json\JsonToHtmlConverter;
use WPCCrawler\Objects\OptionsBox\OptionsBoxService;
use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;
use WPCCrawler\Test\Base\AbstractHtmlManipulationTest;
use WPCCrawler\Test\Data\TestData;
use WPCCrawler\Test\Enums\ManipulationStep;
use WPCCrawler\Utils;

class ConvertJsonToHtmlTest extends AbstractHtmlManipulationTest {

    /** @var string|null */
    private $url;

    /** @var string|null */
    private $content;

    /** @var string|null */
    private $selector;

    /** @var string|null */
    private $attr;

    /** @var string|null */
    private $optionsBox;

    protected function getLastHtmlManipulationStep(): ?int {
        return ManipulationStep::FIND_REPLACE_ELEMENT_HTML;
    }

    protected function defineVariables() {
        $formItemValues = $this->getData()->getFormItemValues();
        if (!is_array($formItemValues)) $formItemValues = [];

        $this->url        = $this->getData()->get("url");
        $this->content    = $this->getData()->get("subject");
        $this->selector   = Utils::array_get($formItemValues, SettingInnerKey::SELECTOR);
        $this->attr       = Utils::array_get($formItemValues, SettingInnerKey::ATTRIBUTE);
        $this->optionsBox = Utils::array_get($formItemValues, SettingInnerKey::OPTIONS_BOX);

        if (!is_string($this->attr) || $this->attr === '') {
            $this->attr = 'text';
        }
    }

    protected function getMessageLastPart(): string {
        return sprintf('%1$s %2$s',
            $this->selector ? "<span class='highlight selector'>" . $this->selector . "</span>" : '',
            $this->attr ? "<span class='highlight attribute'>" . $this->attr . "</span>" : ''
        );
    }

    protected function manipulate($crawler, $bot): Crawler {
        return $crawler;
    }

    protected function shouldApplyOptionsBoxOptions(): bool {
        return false;
    }

    protected function onCreateResults(TestData $data): ?array {
        $jsonStrings = $this->createHtmlManipulationResults(
            $this->url,
            $this->content,
            $this->selector,
            $this->getMessageLastPart(),
            $this->attr
        );

        // Apply the options box settings
        $optionsBoxApplier = OptionsBoxService::getInstance()->createApplierFromRawData($this->optionsBox);
        if ($optionsBoxApplier) {
            $jsonStrings = $optionsBoxApplier->applyToArray($jsonStrings);
        }

        $htmlResults = [];
        foreach($jsonStrings as $jsonString) {
            if (!is_string($jsonString)) continue;

            $htmlResult = null;
            $converter = JsonToHtmlConverter::fromJson($jsonString);
            if ($converter) {
                $htmlResult = $converter->convertAndGetHtml();
            }

            $htmlResults[] = $htmlResult;
        }

        return $htmlResults;
    }
}