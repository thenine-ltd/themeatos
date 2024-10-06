# Changelog

All notable changes to this project are documented in this file.

## Unreleased

## [2.0.0 - 2023-05-22](https://github.com/wpsocio/wptelegram-pro/releases/tag/v2.0.0)

### Enhancements

- Added better support for HTML formatting.
- Added support for nested tags. You can now use <b> inside <i> and vice versa.
- Intelligently trim `{post_excerpt}` to preserve the other parts of Message Template.

### Breaking changes
- Removed support for Markdown formatting in favour of better HTML formatting

### Bug fixes

- Fixed the image not being sent "After the text" when "Send files by URL" is disabled
- Fixed the issue of messages not being sent when the markup is not valid

## [1.6.5 - 2023-05-20](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.6.5)

### Bug fixes

- Fixed the email attachments not being sent by default.
- Fixed PHP warning in the bot API updates handler.

## [1.6.4 - 2023-04-23](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.6.4)

### Enhancements

- Added built-in support for WooCommerce variable product data

### Bug fixes

- Fixed the complex macros like {encode:{cf:field_name}} getting removed when {cf:field_name} duplicated in the message template
- Fixed logs to avoid bot token added to URL

## [1.6.3 - 2023-03-25](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.6.3)

### Enhancements

- Improved message template sanitization to prevent breaking the markup

### Bug fixes

- Fixed PHP warning related to edited channel post updates
- Fixed Inline Query handler for Bots module

## [1.6.2 - 2023-02-19](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.6.2)

### Bug fixes

- Fixed PHP Fatal error caused by last update

## [1.6.1 - 2023-02-18](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.6.1)

### Enhancements

- Added support for adding internal note to chat IDs

## [1.6.0 - 2023-01-31](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.6.0)

### Enhancements

- Added the new experimental HTML converter for better formatting of email notifications
- The new can be enabled using `add_filter( 'wptelegram_pro_notify_use_experimental_text', '__return_true' );`

### Bug fixes

- Fixed escaping of special characters for instant posts

## [1.5.16 - 2023-01-5](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.16)

### Bug fixes

- Fixed PHP warning when not using message thread ID

## [1.5.15 - 2022-12-8](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.15)

### Improvements

- Improved the default value for Send to Telegram option on post edit page

### Enhancements

- Added support for sending messages to topics with groups

### Bug fixes

- Fixed messages not sent when replied-to message is not found

## [1.5.14 - 2022-11-19](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.14)

### Bug fixes

- Fixed PHP warnings in logger

## [1.5.13 - 2022-11-1](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.13)

### Bug fixes

- Fixed warnings in PHP 8.x

## [1.5.12 - 2022-10-31](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.12)

### Improvements

- Improved logging for better understanding of errors

### Bug fixes

- Fixed PHP warning related to edited message updates

## [1.5.11 - 2022-09-10](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.11)

### Enhancements

- Added custom filter for WooCommerce gallery media group
- Added support for sorting of Post to Telegram instances

## [1.5.10 - 2022-07-22](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.10)

### Enhancements

- Post to Telegram: Added support for dynamic template for URL button labels.

### Bug fixes

- Fixed deprecation warnings for PHP 8.1

## [1.5.9 - 2022-06-12](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.9)

### Enhancements

- Added support for <tg-spoiler> tag
- Added and improved filters for HTML and Markdown support
- Improved logging options to prevent users from mistakes

## [1.5.8 - 2022-03-26](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.8)

### Bug fixes

- Fixed Post to Telegram rule search bug

## [1.5.7 - 2022-02-2](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.7)

### Bug fixes

- Fixed Instant Messages not sent

## [1.5.6 - 2022-01-5](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.6)

### Bug fixes

- Fixed broken upgrades for Post to Telegram buttons
- Fixed fatal error caused on some web hosts

## [1.5.5 - 2021-12-31](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.5)

### Enhancements

- Added "Protect content" option to Post to Telegram.

## [1.5.4 - 2021-12-11](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.4)

### Bug fixes

- Fixed inline buttons not loading
- Fixed featured image not shown after the text when Send files by URL is OFF

## [1.5.3 - 2021-11-26](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.3)

### Bug fixes

- Fixed PHP warnings

## [1.5.2 - 2021-11-7](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.2)

### Enhancements

- Inline keyboard buttons are now editable.

### Bug fixes

- Fixed Additional Media and WC Gallery not sent when Send files by URL is disabled

## [1.5.1 - 2021-10-23](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.1)

### Enhancements

- Added override option for P2TG Send Featured Image.

## [1.5.0 - 2021-10-17](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.5.0)

### Enhancements

- Added support for sending images from post content 🎉
- Added caption support for WooCommerce product gallery images 🎉

### Bug fixes

- Fixed instant posts not sent when Post edit switch if OFF
- Fixed posts not sent when Formatting is None

## [1.4.10 - 2021-08-1](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.4.10)

### Bug fixes

- Fixed the issue of data not shown on Post to Telegram instance edit page

## [1.4.9 - 2021-07-24](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.4.9)

### Enhancements

- Added WooCommerce product gallery support 🎉

## [1.4.8 - 2021-07-5](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.4.8)

### Bug fixes

- Fixed admin page not shown just after upgrade
- Fixed multiple empty lines in post content and excerpt

## [1.4.7 - 2021-06-14](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.4.7)

### Bug fixes

- Fixed licence check requests

### Enhancements

- Added `{post_slug}` macro

## [1.4.6 - 2021-06-10](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.4.6)

### Bug fixes

- Fixed licence activation when migrating servers
- Fixed parent category rule bug in Post to Telegram
- Fixed proxy import from free version

## [1.4.5 - 2021-05-7](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.4.5)

### Bug fixes

- Fixed the issue of posts not sent when using multiple instances
- Fixed WooCommerce REST API products not sent to Telegram
- Fixed the delay in override settings preventing publish post

## [1.4.4 - 2021-05-4](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.4.4)

### Bug fixes

- Fixed the Share button URL encoding

## [1.4.3 - 2021-05-3](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.4.3)

### Bug fixes

- Fixed upgrade bug in additional media and inline keyboard when empty
- Fixed upgrade bug in Post to Telegram rules when empty
- Fixed the issue of settings not saved due to trailing slash redirects

## [1.4.2 - 2021-05-2](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.4.2)

### Bug fixes

- Fixed the error with `{tags}` and `{categories}`

## [1.4.1 - 2021-05-2](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.4.1)

### Bug fixes

- Fixed crashing of instance editor

## [1.4.0 - 2021-05-2](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.4.0)

### Enhancements

- Switched to PHP namespaces
- Added support for custom rules for Post to Telegram 🎉
- Added support for Audio and Document in Additional Media groups
- Added instance settings to Instant Post options on post list page
- Better support for block editor override settings
- Improved logging for better diagnosis
- UI Improvements

### Bug fixes

- Fixed wrongly encoded caption when duplicating instances
- Fixed import settings from the free version
- Fixed override settings for classic editor
- Fixed the YouTube links being stripped out from the content.

## [1.3.0 - 2021-01-31](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.3.0)

### Enhancements

- Added Instant Messages module 🎉
- Added support for using macros in template logic comparison values
- Enhanced Post to Telegram template logic by adding `STARTS_WITH` and `ENDS_WITH` operators
- Improved the logic for deciding new and existing posts

### Bug fixes

- Fixed the bug - bot token not being updated for modules when changed

## [1.2.1 - 2020-10-5](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.2.1)

### Bug fixes

- Fixed the issue of P2TG instance active status not being saved.

## [1.2.0 - 2020-10-3](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.2.0)

### Enhancements

- Added Additional Media support for Post to Telegram

### Bug fixes

- Fixed the issue of empty template not being saved

## [1.1.3 - 2020-09-2](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.1.3)

### Bug fixes

- Removed space at the beginning when using Single Message

## [1.1.2 - 2020-08-29](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.1.2)

### Bug fixes

- Fixed escaping of Markdown when nested
- Fixed categories as hashtags
- Fixed hastags at the beginning when using Single Message

## [1.1.1 - 2020-08-26](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.1.1)

### Bug fixes

- Fixed escaping of Markdown special characters in post data

## [1.1.0 - 2020-08-25](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.1.0)

### Enhancements

- Added advanced template logic.
- Added support for MarkdownV2.

### Bug fixes

- Fixed validation for instances after import

## [1.0.5 - 2020-08-20](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.0.5)

### Enhancements

- Added one click import settings from free version
- Added filter to disable long polling

### Bug fixes

- Fixed manifest file not found issue
- Fixed block editor assets

## [1.0.4 - 2020-08-15](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.0.4)

### Bug fixes

- Fixed syntax error for PHP 5.6
- Fixed wrong post data when importing in bulk
- Fixed admin menu icon
- Fix plugin update issue

## [1.0.3 - 2020-08-7](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.0.3)

### Bug fixes

- Delayed loading of modules to fix conflict with other plugins
- Fixed Send to Telegram override for scheduled posts
- Fixed saving of empty values for "Use when"
- Fixed webhook issue when same bot is used for Telegram widget

## [1.0.2 - 2020-08-3](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.0.2)

### Enhancements

- Added intro video

### Bug fixes

- Fixed instances meta conflict
- Fixed Admin menu icon fonts
- Fixed the instance not loading issue when the bot is deleted
- Fixed instance rules for multiple delays
- Fixed p2tg template escape characters

## [1.0.1 - 2020-08-1](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.0.1)

### Bug fixes

- Disables message update processing by default
- Fixed messed up message templare and keyboard for duplicate instances
- Fixed the bug in messed up instance delay
- Fixed inline buttons for delayed instances

## [1.0.0 - 2020-08-1](https://github.com/wpsocio/wptelegram-pro/releases/tag/v1.0.0)

- Initial Release.
