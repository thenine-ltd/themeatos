<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 12/02/2023
 * Time: 18:01
 *
 * @since 1.13.0
 */

namespace WPCCrawler\Test\Tests;

use Exception;
use Illuminate\Contracts\View\View;
use WPCCrawler\Factory;
use WPCCrawler\Objects\Api\OpenAi\OpenAiClient;
use WPCCrawler\Objects\Informing\Informer;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Objects\Settings\SettingsImpl;
use WPCCrawler\Objects\ShortCode\ShortCodeApplier;
use WPCCrawler\Test\Base\AbstractTest;
use WPCCrawler\Test\Data\TestData;
use WPCCrawler\Utils;

class OpenAiGptShortCodeTest extends AbstractTest {

    /** @var array<string, string>|null */
    private $shortCodes = null;

    protected function createResults($data): ?array {
        // Get the form item values
        $values = $data->getFormItemValues();
        if (!$values || !is_array($values)) return null;

        // Get the short code string from the values
        $parentKey = 'openai_gpt_short_code';
        $key = 'short_code';
        $values = isset($values[$parentKey])
            ? Utils::array_get($values, "$parentKey.values")
            : $values;
        if (!is_array($values)) {
            $values = [];
        }

        $shortCodeStr = Utils::array_get($values, $key);
        if (!is_string($shortCodeStr) || $shortCodeStr === '') {
            Informer::addInfo(_wpcc('Short code could not be retrieved from the request data.'));
            return null;
        }

        // Get the short codes that are used in the templates
        $shortCodes = Utils::array_get($values, 'short_codes');
        if (!is_array($shortCodes)) {
            $shortCodes = [];
        }

        // Store the short codes in the instance so that we can add them to the view
        $this->shortCodes = $shortCodes;

        // Get the secret key
        $secretKey = $this->getOpenAiSecretKey($data);
        if ($secretKey === null) {
            Informer::addInfo(_wpcc('No OpenAI API secret key is provided. The secret key is required.'));
            return null;
        }

        $applier = new ShortCodeApplier(
            $shortCodes,
            new SettingsImpl([
                SettingKey::DO_NOT_USE_GENERAL_SETTINGS => true,
                SettingKey::WPCC_API_OPENAI_SECRET_KEY => $secretKey,
            ], Factory::postService()->getSingleMetaKeys())
        );

        // Invalidate the test instance so that a client that will actually talk to the API is used
        OpenAiClient::setTestInstance(null);
        return [
            $applier->apply($shortCodeStr),
        ];
    }

    /**
     * Create the view of the response
     *
     * @return View
     * @throws Exception
     */
    protected function createView(): View {
        return Utils::view('partials/test-result')
            ->with("results", $this->getResults())
            ->with("message", _wpcc('Test results for the created OpenAI GPT short code:'))
            ->with("extra", [
                'shortCodes' => $this->shortCodes,
            ]);
    }

    /*
     * PRIVATE HELPERS
     */

    /**
     * @param TestData $data The test data
     * @return string|null The OpenAI secret key. If it is not found, or it is empty, `null` is returned.
     * @since 1.13.0
     */
    private function getOpenAiSecretKey(TestData $data): ?string {
        // Get the secret key provided in the data
        $secretKey = $data->get(SettingKey::WPCC_API_OPENAI_SECRET_KEY);

        // If the secret key is retrieved from the custom general settings in a site settings page while the custom
        // general settings is disabled, ignore it.
        if (is_string($secretKey) && $secretKey !== ''
            && $data->isSiteSettingsPage() && $data->getCustomGeneralSettings() === null
        ) {
            $secretKey = null;
        }

        // If it does not exist
        if (!is_string($secretKey) || $secretKey === '') {
            // If this is the general settings page, it is required to send the secret key with the data. So, stop.
            if ($data->isGeneralSettingsPage()) {
                return null;
            }

            // This is not the general settings page. Try to get the secret key from the general settings.
            $settings = new SettingsImpl([], Factory::postService()->getSingleMetaKeys());
            $secretKey = $settings->getSetting(SettingKey::WPCC_API_OPENAI_SECRET_KEY);
        }

        // If the secret key is still not found, return null.
        if (!is_string($secretKey) || $secretKey === '') {
            return null;
        }

        // The secret key is found
        return $secretKey;
    }

}