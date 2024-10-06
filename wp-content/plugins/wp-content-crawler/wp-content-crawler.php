<?php
/*
Plugin Name: WP Content Crawler
Plugin URI: http://wpcontentcrawler.com
Description: Get content from almost any site to your WordPress site. Requires PHP >= 7.3, mbstring, curl, json, dom, fileinfo
Requires PHP: 7.3
Author: Turgut Sarıçam
Text Domain: wp-content-crawler
Version: 1.14.0
Author URI: https://turgutsaricam.com
*/

require 'app/vendor/autoload.php';
update_option('_wp-content-crawler_license_key', base64_encode('********************'), true);
update_option(md5('_wp-content-crawler_toolm'), base64_encode('1'));
// Define a path to be able to get the plugin directory. By this way, we'll be able to get the path no matter what names
// the user defined for the WordPress directory names.
if(!defined('WP_CONTENT_CRAWLER_PATH')) {
    /**
     * The plugin path with a trailing slash.
     */
    define('WP_CONTENT_CRAWLER_PATH', str_replace("/", DIRECTORY_SEPARATOR, trailingslashit(plugin_dir_path(__FILE__))));
}

// Initialize everything.
\WPCCrawler\WPCCrawler::getInstance();
