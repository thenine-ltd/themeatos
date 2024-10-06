<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 27/06/2023
 * Time: 15:50
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Filtering\Commands\ActionCommands\Request;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;
use WPCCrawler\Factory;
use WPCCrawler\Objects\Crawling\Bot\AbstractBot;
use WPCCrawler\Objects\Crawling\Bot\Objects\AppRequestOptions;
use WPCCrawler\Objects\Enums\RequestMethod;
use WPCCrawler\Objects\Enums\ValueType;
use WPCCrawler\Objects\Filtering\Commands\ActionCommands\Base\AbstractActionCommand;
use WPCCrawler\Objects\Filtering\Commands\CommandUtils;
use WPCCrawler\Objects\Filtering\Commands\Enums\CommandShortCodeName;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinition;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionFactory;
use WPCCrawler\Objects\Filtering\Commands\Views\ViewDefinitionList;
use WPCCrawler\Objects\Filtering\Enums\CommandKey;
use WPCCrawler\Objects\Filtering\Enums\InputName;
use WPCCrawler\Objects\Filtering\Interfaces\NeedsBot;
use WPCCrawler\Objects\Html\ElementCreator;
use WPCCrawler\Objects\Settings\Enums\SettingInnerKey;
use WPCCrawler\Objects\Settings\Enums\SettingKey;
use WPCCrawler\Objects\Views\Enums\ViewVariableName;
use WPCCrawler\Objects\Views\InputWithLabel;
use WPCCrawler\Objects\Views\MultipleCustomShortCodeWithLabel;
use WPCCrawler\Objects\Views\MultipleFindReplaceWithLabelForCmd;
use WPCCrawler\Objects\Views\MultipleSelectorWithAttribute;
use WPCCrawler\Objects\Views\Select\SelectRequestMethodWithLabel;
use WPCCrawler\Objects\Views\ShortCodeButtonsWithLabelForTemplateCmd;
use WPCCrawler\Objects\Views\TextAreaWithLabel;
use WPCCrawler\Utils;

class MakeRequest extends AbstractActionCommand implements NeedsBot {

    /** @var string Name of the default attribute whose value will be retrieved from the found HTML elements as a URL */
    const DEFAULT_URL_EL_ATTR = 'href';

    /**
     * @var string Name of the class that is added to the element that contains the "body" and the "head" of the
     *      response
     */
    const RESPONSE_CONTAINER_CLASS = 'wpcc-response';

    /** @var string Name of the class that is added to the element that contains the "head" of the response */
    const HEAD_CONTAINER_CLASS = 'wpcc-response-head';

    /** @var int At most how many characters of the POST body can be displayed in the log message */
    const LOG_REQUEST_BODY_LENGTH_LIMIT = 1000;

    /** @var AbstractBot|null */
    private $bot;

    public function getKey(): string {
        return CommandKey::REQUEST_MAKE;
    }

    public function getName(): string {
        return _wpcc('Make');
    }

    public function getDescription(): ?string {
        return _wpcc('Makes a request to each URL found in the target page via defined CSS selectors and appends 
            their response to the page. This is useful for manually making AJAX requests.');
    }

    public function getInputDataTypes(): array {
        return [ValueType::T_REQUEST];
    }

    protected function isOutputTypeSameAsInputType(): bool {
        return true;
    }

    public function doesNeedSubjectValue(): bool {
        return false;
    }

    protected function isTestable(): bool {
        return false;
    }

    protected function createViews(): ?ViewDefinitionList {
        $viewDefinitionFactory = ViewDefinitionFactory::getInstance();

        return (new ViewDefinitionList())
            // Selectors and attributes, used to find the URL to which a request will be made
            ->add((new ViewDefinition(MultipleSelectorWithAttribute::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Request URL selectors'))
                ->setVariable(ViewVariableName::NAME,  InputName::CSS_SELECTOR)
                ->setVariable(ViewVariableName::INFO,  _wpcc('Enter one or more selectors that will be used to find the URL to which a request will be made.'))
                ->setVariable(ViewVariableName::DEFAULT_ATTR, self::DEFAULT_URL_EL_ATTR)
            )

            // Request method
            ->add((new ViewDefinition(SelectRequestMethodWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Method'))
                ->setVariable(ViewVariableName::INFO,  _wpcc('Select the request method'))
                ->setVariable(ViewVariableName::NAME,  InputName::REQUEST_METHOD))

            // POST body short codes
            // The custom short codes are not available for the "post page filters". So, we have to let the user
            //  define their own short codes to create the post body.
            ->add((new ViewDefinition(MultipleCustomShortCodeWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('POST body custom short codes'))
                ->setVariable(ViewVariableName::INFO,  _wpcc("Define custom short codes that can be used in the
                    POST body template to create the final POST body.")
                )
                ->setVariable(ViewVariableName::NAME,  InputName::CUSTOM_SHORT_CODES)
                ->setVariable(ViewVariableName::CLAZZ, 'post-body-custom-short-codes local-short-code-inputs')
            )
            ->add((new ViewDefinition(ShortCodeButtonsWithLabelForTemplateCmd::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('POST body short codes'))
                ->setVariable(ViewVariableName::INFO,  _wpcc("Short codes that can be used in the POST body. You
                    can hover over the short codes to see what they do. You can click to the short code buttons to copy
                    the short codes. Then, you can paste the short codes into the template to include them. They will be
                    replaced with their actual values.")
                )
                ->setVariable(ViewVariableName::CLAZZ, 'post-body-short-codes')
            )
            // POST body
            ->add((new ViewDefinition(TextAreaWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('POST body template'))
                ->setVariable(ViewVariableName::INFO,  _wpcc('Define the body of the POST request'))
                ->setVariable(ViewVariableName::NAME,  InputName::POST_BODY)
            )

            // Element ID
            ->add((new ViewDefinition(InputWithLabel::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Container element ID'))
                ->setVariable(ViewVariableName::INFO,  sprintf(_wpcc('Enter the ID that will be assigned to the 
                    new element that will contain the response content. You can use this ID to interact with the 
                    response content later. An ID attribute cannot have a space character in it, according to the 
                    specifications. However, if you enter a space-separated list, it will be considered as multiple IDs.
                    In case of making multiple requests, the given ID(s) will be suffixed a number for each request. For
                    example, if the given ID is %1$s, and there are three requests, while the first element will have
                    %1$s as its ID, the second and third ones will have %2$s and %3$s as their IDs.'),
                    '<span class="highlight id">my-element my-response</span>',
                    '<span class="highlight id">my-element2 my-response2</span>',
                    '<span class="highlight id">my-element3 my-response3</span>'
                ))
                ->setVariable(ViewVariableName::NAME, InputName::ELEMENT_ID)
                ->setVariable(ViewVariableName::TYPE, 'text'))

            // Find and replace in raw response
            ->add((new ViewDefinition(MultipleFindReplaceWithLabelForCmd::class))
                ->setVariable(ViewVariableName::TITLE, _wpcc('Find and replace in raw response'))
                ->setVariable(ViewVariableName::INFO,  _wpcc('Find and replace anything in the raw response content.'))
                ->setVariable(ViewVariableName::NAME,  InputName::FIND_REPLACE)
            )
            // Extra cookies
            ->add($viewDefinitionFactory->createCookiesInput())
            // Extra request headers
            ->add($viewDefinitionFactory->createRequestHeadersInput())
        ;
    }

    protected function onExecute($key, $subjectValue) {
        $bot = $this->getBot();
        if (!$bot) return;

        $logger = $this->getLogger();

        $containerElementIds = $this->getElementIds();
        if ($containerElementIds === null) {
            if ($logger) $logger->addMessage(_wpcc('Container element ID is not specified. It must be specified.'));
            return;
        }

        $urls = $this->getUrls();
        if (!$urls) {
            if ($logger) $logger->addMessage(_wpcc('No URLs are found with the provided CSS selectors.'));
            return;
        }

        $method = $this->getRequestMethod();
        $findReplaces = $this->getArrayOption(InputName::FIND_REPLACE);

        $utils = new CommandUtils();
        $extraCookies    = $utils->getCookiesOption($this) ?? [];
        $extraReqHeaders = $utils->getRequestHeadersOption($this) ?? [];

        // Update the bot settings to include the extra cookies and request headers
        $originalSettings = $bot->getSettingsImpl()->getSettings();
        $newSettings = $originalSettings;
        $newSettings[SettingKey::COOKIES] = array_merge($newSettings[SettingKey::COOKIES] ?? [], $extraCookies);
        $newSettings[SettingKey::REQUEST_HEADERS] = array_merge($newSettings[SettingKey::REQUEST_HEADERS] ?? [], $extraReqHeaders);

        $singleKeys = Factory::postService()->getSingleMetaKeys();
        $bot->setSettings($newSettings, $singleKeys, !$bot->isSettingsPrepared());

        foreach($urls as $key => $url) {
            $this->executeForUrl($bot, $containerElementIds, $url, $key, $method, $findReplaces);
        }

        // Restore the original settings
        $bot->setSettings($originalSettings, $singleKeys, !$bot->isSettingsPrepared());
    }

    /*
     *
     */

    /**
     * Makes a request to a URL and adds its response into the bot's crawler by creating an element having the specified
     * ID and filling the element with the response's `head` and `body`.
     *
     * @param AbstractBot $bot                 A bot that will be used to make the request
     * @param string[]    $containerElementIds IDs of the element that will contain the response content
     * @param string      $url                 The URL to which a request will be made
     * @param int         $urlIndex            Index of the URL among other URLs. This will be used to create a unique
     *                                         element ID. Basically, this will be used as a suffix to the given
     *                                         container element ID.
     * @param string      $method              The request method, such as "GET" or "POST".
     * @param array|null  $findReplaces        Find and replace rules that will be applied to the raw response content
     * @return void
     * @since 1.14.0
     */
    protected function executeForUrl(AbstractBot $bot, array $containerElementIds, string $url, int $urlIndex,
                                     string $method, ?array $findReplaces): void {
        $logger = $this->getLogger();

        // If there is still no crawler, stop.
        $responseCrawler = $this->makeRequest($bot, $url, $method, $findReplaces);
        if (!$responseCrawler) {
            $this->onResponseCouldNotBeRetrieved($bot, $url);
            return;
        }

        // Find the body in the main page, so that we can create the new element inside it
        $pageCrawler = $bot->getCrawler();
        if (!$pageCrawler) {
            if ($logger) $logger->addMessage(_wpcc('Page crawler could not be retrieved.'));
            return;
        }

        $pageBody = $pageCrawler->filter('body')->first()->getNode(0);
        if (!$pageBody) {
            if ($logger) $logger->addMessage(_wpcc('"body" element could not be retrieved.'));
            return;
        }

        // Get the container element ID and create the new element
        $containerId = $this->suffixElementIds($containerElementIds, $urlIndex);

        $responseContainerClass = self::RESPONSE_CONTAINER_CLASS;
        $headContainerClass = self::HEAD_CONTAINER_CLASS;
        $created = $this->onCreateElementCreator()->create(
            $pageBody,
            ElementCreator::LOCATION_INSIDE_BOTTOM,
            "<div id='{$containerId}' class='{$responseContainerClass}'><div class='{$headContainerClass}'></div></div>"
        );
        if (!$created) {
            if ($logger) $logger->addMessage(sprintf(_wpcc('Response container element could not be created. [URL=%1$s]'), $url));
            return;
        }

        $bodyContainer = $pageCrawler->filter(sprintf('div[id="%1$s"]', $containerId))->last();
        $bodyContainerReference = $bodyContainer->getNode(0);
        $headContainerReference = $bodyContainer->filter("div.{$headContainerClass}")->first()->getNode(0);
        if (!$bodyContainerReference || !$headContainerReference) {
            if ($logger) $logger->addMessage(sprintf(_wpcc('Container element reference for response body and/or head could not be retrieved. [URL=%1$s]'), $url));
            return;
        }

        // Add the response crawler's body into the element. If there is no "body", use the main node.
        $responseBody = Utils::getBodyElement($responseCrawler);
        $responseHead = $responseCrawler->filter('head')->first()->getNode(0);
        if (!$responseBody) {
            if ($logger) $logger->addMessage(sprintf(_wpcc('Response body could not be retrieved. [URL=%1$s]'), $url));
            return;
        }

        // Import the body into the page
        $bodyImported = $this->onCreateElementCreator()
            ->createFromRemoteElement($bodyContainerReference, ElementCreator::LOCATION_INSIDE_BOTTOM, $responseBody);
        if (!$bodyImported) {
            if ($logger) $logger->addMessage(sprintf(_wpcc('Response\'s "body" could not be imported into the page. [URL=%1$s]'), $url));
            return;
        }

        // Import the head into the page
        if ($responseHead) {
            $headImported = $this->onCreateElementCreator()
                ->createFromRemoteElement($headContainerReference, ElementCreator::LOCATION_INSIDE_BOTTOM, $responseHead);
            if (!$headImported) {
                if ($logger) $logger->addMessage(sprintf(_wpcc('Response\'s "head" could not be imported into the page. [URL=%1$s]'), $url));
            }
        }

        if ($logger) {
            $logger->addMessage(sprintf(
                _wpcc('Response is imported into the page under the element with id "%1$s". [URL=%2$s]'),
                $containerId,
                $url
            ));
        }
    }

    /**
     * @param AbstractBot $bot          The bot that will be used to make the request
     * @param string      $url          The URL to which a request will be made
     * @param string      $method       The request method, one of the constants defined in {@link RequestMethod}.
     * @param array|null  $findReplaces Find and replace rules that will be applied to the raw response content
     * @return Crawler|null If the request is successful, a crawler that contains the response content is returned.
     *                      Otherwise, `null` is returned.
     * @since 1.14.0
     */
    protected function makeRequest(AbstractBot $bot, string $url, string $method, ?array $findReplaces): ?Crawler {
        // Make the request and get the response as a crawler
        $requestBody = $method === RequestMethod::POST
            ? $this->createPostBody($url)
            : null;
        $reqOptions = (new AppRequestOptions($method))
            ->setBody($requestBody);
        $responseCrawler = $bot->request($url, $reqOptions, $findReplaces);

        // If there is a logger, add a log message by including the request URL and body.
        $this->onRequestMade($url, $requestBody);

        if ($responseCrawler) {
            return $responseCrawler;
        }

        // There is no crawler, meaning that the response might not be HTML. In that case, the user will probably still
        // want the response to be added to the page. For example, the response might be JSON. So, get the raw response
        // content and create a dummy crawler with it, if possible.
        $latestResponse = $bot->getLatestResponse();
        if ($latestResponse) {
            $content = $latestResponse->getContent();
            try {
                $responseCrawler = $bot->createDummyCrawler($content);
            } catch (Exception $e) {
                // Do nothing
            }
        }

        return $responseCrawler;
    }

    /**
     * @param string $url The URL to which a POST request will be made
     * @return string The body to be sent with the POST request
     * @since 1.14.0
     */
    protected function createPostBody(string $url): string {
        $valueMap = array_merge($this->createLocalShortValueMap() ?? [], [
            CommandShortCodeName::ITEM => $url,
        ]);

        $template = $this->getStringOption(InputName::POST_BODY) ?? '';
        return $this
            ->createShortCodeApplier($valueMap)
            ->apply($template);
    }

    /**
     * @return array<string, string>|null If there are local custom short codes, they are returned as key-value pairs
     *                                    where the keys are short code names, and the values are their actual values.
     *                                    Otherwise, `null` is returned.
     * @since 1.14.0
     */
    protected function createLocalShortValueMap(): ?array {
        $localShortCodes = $this->getArrayOption(InputName::CUSTOM_SHORT_CODES);
        if (!$localShortCodes) return null;

        $bot = $this->getBot();
        if (!$bot) return null;

        /** @var array<string, string> $shortCodeValueMap */
        $shortCodeValueMap = [];
        foreach($localShortCodes as $selectorData) {
            $shortCode = $selectorData[SettingInnerKey::SHORT_CODE] ?? null;
            if (!is_string($shortCode) || $shortCode === '') {
                continue;
            }

            $isSingle = isset($selectorData[SettingInnerKey::SINGLE]);
            $results = $bot->extractValuesWithSelectorData(
                $bot->getCrawler(),
                $selectorData,
                'html',
                false,
                $isSingle
            );
            if ($results === null) {
                continue;
            }

            $result = '';

            // If the results is an array, combine all the data into a single string.
            if (is_array($results)) {
                foreach ($results as $r) {
                    if (!is_scalar($r)) continue;
                    $result .= $r;
                }

            } else {
                $result = $results;
            }

            $shortCodeValueMap[$shortCode] = $result;
        }

        return $shortCodeValueMap;
    }

    /**
     * @return ElementCreator A new {@link ElementCreator}
     * @since 1.14.0
     */
    protected function onCreateElementCreator(): ElementCreator {
        return new ElementCreator();
    }

    /**
     * @param string[] $ids      IDs of the element that will contain the response content
     * @param int      $urlIndex Index of the URL among other URLs. This will be used to create a unique element ID.
     *                           Basically, this will be used as a suffix to the given container element ID.
     * @return string ID of the container element, containing space-separated IDs
     * @since 1.14.0
     */
    protected function suffixElementIds(array $ids, int $urlIndex): string {
        // If the URL index is not 0, suffix each ID with the index. Otherwise, no need to suffix. After that, combine
        // the IDs into a single space-separated string to create the element ID.
        return implode(' ', $urlIndex === 0
            ? $ids
            : array_map(
                function(string $id) use (&$urlIndex) {
                    return $id . ($urlIndex + 1);
                },
                $ids
            )
        );
    }

    /**
     * @return string[]|null IDs that should be assigned to the container element. If there is no ID, returns `null`.
     * @since 1.14.0
     */
    protected function getElementIds(): ?array {
        $id = $this->getStringOption(InputName::ELEMENT_ID);
        if ($id === null) return null;

        // The element ID might contain spaces. In that case, treat space as a separator.
        $exploded = explode(' ', $id) ?: []; // @phpstan-ignore-line
        $ids = array_values(array_filter($exploded, function(string $id) {
            return $id !== '';
        }));

        return $ids ?: null;
    }

    /**
     * @return array<int, string>|null URLs to which the requests should be made. If there are no URLs, returns `null`.
     * @since 1.14.0
     */
    protected function getUrls(): ?array {
        $bot = $this->getBot();
        if (!$bot) return null;

        $commandUtils = new CommandUtils();

        // Get the CSS selectors
        $selectors = $commandUtils->getCssSelectorsOption($this);
        if ($selectors === null) return null;

        $urls = $bot->extractValuesWithMultipleSelectorData($bot->getCrawler(), $selectors, self::DEFAULT_URL_EL_ATTR);
        return is_array($urls)
            ? array_values(Arr::flatten($urls, 1))
            : null;
    }

    /**
     * @return string One of the constants defined in {@link RequestMethod}, e.g. {@link RequestMethod::GET}.
     * @since 1.14.0
     */
    protected function getRequestMethod(): string {
        $method = $this->getStringOption(InputName::REQUEST_METHOD) ?? RequestMethod::GET;
        return in_array($method, array_keys(RequestMethod::getRequestMethodsForSelect()))
            ? $method
            : RequestMethod::GET;
    }

    /*
     * LOGGING
     */

    /**
     * Adds a log message about the response not being able to be retrieved
     *
     * @param AbstractBot $bot The bot
     * @param string      $url The URL from which a response could not be retrieved
     * @return void
     * @since 1.14.0
     */
    protected function onResponseCouldNotBeRetrieved(AbstractBot $bot, string $url): void {
        $logger = $this->getLogger();
        if (!$logger) return;

        $message = sprintf(_wpcc('The response could not be retrieved. [URL=%1$s]'), $url);
        $e = $bot->getLatestRequestException();
        if ($e) {
            $message .= sprintf(_wpcc(' [Message=%1$s]'), $e->getMessage());
        }

        $logger->addMessage($message);
    }

    /**
     * Adds a log message about the request that is made
     *
     * @param string      $url         The URL to which a request is made
     * @param string|null $requestBody The body of the request
     * @return void
     * @since 1.14.0
     */
    protected function onRequestMade(string $url, ?string $requestBody): void {
        $logger = $this->getLogger();
        if (!$logger) return;

        $logger->addMessage(sprintf(_wpcc('Request is made to %1$s'), $url));

        if ($requestBody === null) return;

        $bodyLength = mb_strlen($requestBody);
        $limit = self::LOG_REQUEST_BODY_LENGTH_LIMIT;
        $overflowLength = $bodyLength - $limit;
        $endText = $overflowLength > 0
            ? sprintf(_wpcc('... (+%d char(s))'), $overflowLength)
            : '...';
        $logger->addMessage(sprintf(
            _wpcc('Request body: %1$s'),
            Str::limit($requestBody, $limit, $endText)
        ));
    }

    /*
     *
     */

    public function setBot(?AbstractBot $bot): void {
        $this->bot = $bot;
    }

    public function getBot(): ?AbstractBot {
        return $this->bot;
    }
}