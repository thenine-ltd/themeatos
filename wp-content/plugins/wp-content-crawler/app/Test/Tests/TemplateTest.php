<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 28/10/2018
 * Time: 21:14
 */

namespace WPCCrawler\Test\Tests;

use Exception;
use Illuminate\Contracts\View\View;
use WPCCrawler\Objects\Enums\ShortCodeName;
use WPCCrawler\Objects\OptionsBox\Boxes\Def\DefaultOptionsBoxApplier;
use WPCCrawler\Objects\Settings\SettingsImpl;
use WPCCrawler\Objects\ShortCode\ShortCodeApplier;
use WPCCrawler\Test\Base\AbstractTest;
use WPCCrawler\Test\Data\TestData;
use WPCCrawler\Utils;

class TemplateTest extends AbstractTest {

    /** @var string|null */
    private $message;

    /**
     * Conduct the test and return an array of results.
     *
     * @param TestData $data Information required for the test
     * @return array
     */
    protected function createResults($data): array {
        // Here, form item values must be an array
        if(!$data->getFormItemValues() || !is_array($data->getFormItemValues())) return [];

        // Get the template
        $template = Utils::array_get($data->getFormItemValues(), 'template');
        if (!is_string($template)) return [];

        $results = [];

        if ($data->getTestData()) {
            // If the data comes from the options box, we cannot apply all options box settings. So, let's apply some
            // of them.
            if ($data->isFromOptionsBox()) {
                $data->applyOptionsBoxSettingsToTestData(function($applier) {
                    /** @var DefaultOptionsBoxApplier $applier */

                    // Since this is a template test, do not apply all template options coming from the options box.
                    // There might be a number of template options. We want to test only the template that the user
                    // wants to test.
                    $applier->setApplyTemplateOptions(false);
                });
            }

            $settings = new SettingsImpl($data->getPostSettings());
            foreach($data->getTestData() ?: [] as $val) {
                $shortCodeApplier = new ShortCodeApplier([ShortCodeName::WCC_ITEM => $val], $settings);
                $results[] = $shortCodeApplier->apply($template);
            }
        }

        $this->message = sprintf(
            _wpcc('%2$s Test results for %1$s:'),
            "<span class='highlight template'>" . (mb_strlen($template) > 200 ? mb_substr($template, 0, 199) . '...' : $template) . "</span>",
            sprintf(
                _wpcc('Only %1$s short code is replaced for testing purposes.'),
                "<span class='highlight short-code'>[" . ShortCodeName::WCC_ITEM . "]</span>"
            )
        );

        return $results;
    }

    /**
     * Create the view of the response
     *
     * @return View|null
     * @throws Exception
     */
    protected function createView() {
        return Utils::view('partials/test-result')
            ->with("results", $this->getResults())
            ->with("message", $this->message ?: '');
    }
}