<?php
/**
 * Created by PhpStorm.
 * User: tsaricam
 * Date: 27/06/2023
 * Time: 16:37
 *
 * @since 1.14.0
 */

namespace WPCCrawler\Objects\Enums;

use WPCCrawler\Objects\Filtering\Enums\InputName;

abstract class RequestMethod {

    /** @var array<string, string|array>|null */
    private static $requestMethods = null;

    const GET    = 'GET';
    const POST   = 'POST';
    const HEAD   = 'HEAD';
    const PUT    = 'PUT';
    const DELETE = 'DELETE';
    const PATCH  = 'PATCH';

    /**
     * @return array<string, string|array> Available request methods. The keys are the method names, while the values
     *                       are their human-friendly texts.
     * @since 1.14.0
     */
    public static function getRequestMethodsForSelect(): array {
        if (self::$requestMethods === null) {
            self::$requestMethods = [
                RequestMethod::GET    => RequestMethod::GET,
                RequestMethod::POST   => [
                    'name' => RequestMethod::POST,
                    'dependants' => json_encode([
                        sprintf('tr[aria-label=\'%1$s\']', InputName::POST_BODY),
                        'tr.post-body-short-codes',
                        'tr.post-body-custom-short-codes',
                    ]),
                    'container' => '{"closest": "table"}',
                ],
                RequestMethod::HEAD   => RequestMethod::HEAD,
                RequestMethod::PUT    => RequestMethod::PUT,
                RequestMethod::DELETE => RequestMethod::DELETE,
                RequestMethod::PATCH  => RequestMethod::PATCH,
            ];
        }

        return self::$requestMethods;
    }

}