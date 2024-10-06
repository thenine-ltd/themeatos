<?php
/**
 * Created by PhpStorm.
 * User: turgutsaricam
 * Date: 8.10.2019
 * Time: 10:55
 *
 * @since 1.9.0
 */

namespace WPCCrawler\Objects\Enums;

class UrlType extends EnumBase {

    const QUEUE   = "url_type_queue";
    const DELETED = "url_type_deleted";
    const SAVED   = "url_type_saved";
    const UPDATED = "url_type_updated";
    const OTHER   = "url_type_other";
    const ALL     = "url_type_all";

}
