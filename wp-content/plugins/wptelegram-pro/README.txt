=== WP Telegram Pro ===
Contributors: wpsocio, irshadahmad21
Donate link: https://wptelegram.pro
Tags: telegram, notifications, posts, channel, group
Requires at least: 5.8
Requires PHP: 7.2
Tested up to: 6.2
Stable tag: 2.0.0

Integrate your WordPress site perfectly with Telegram with full control.

== Description ==
Integrate your WordPress site perfectly with Telegram with full control.

Visit [wptelegram.pro](https://wptelegram.pro) for more details.

== Changelog ==

= 2.0.0 =
- Added better support for HTML formatting.
- Added support for nested tags. You can now use <b> inside <i> and vice versa.
- Intelligently trim `{post_excerpt}` to preserve the other parts of Message Template.
- Removed support for Markdown formatting in favour of better HTML formatting
- Fixed the image not being sent "After the text" when "Send files by URL" is disabled
- Fixed the issue of messages not being sent when the markup is not valid

= 1.6.5 =
- Fixed the email attachments not being sent by default.
- Fixed PHP warning in the bot API updates handler.

= 1.6.4 =
- Added built-in support for WooCommerce variable product data
- Fixed the complex macros like {encode:{cf:field_name}} getting removed when {cf:field_name} duplicated in the message template
- Fixed logs to avoid bot token added to URL

= 1.6.3 =
- Improved message template sanitization to prevent breaking the markup
- Fixed PHP warning related to edited channel post updates
- Fixed Inline Query handler for Bots module

= 1.6.2 =
- Fixed PHP Fatal error caused by last update

= 1.6.1 =
- Added support for adding internal note to chat IDs

= 1.6.0 =
- Added the new experimental HTML converter for better formatting of email notifications
- The new can be enabled using `add_filter( 'wptelegram_pro_notify_use_experimental_text', '__return_true' );`
- Fixed escaping of special characters for instant posts
