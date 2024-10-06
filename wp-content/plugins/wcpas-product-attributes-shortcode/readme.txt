=== Product Attributes Shortcode ===
Contributors: ninetyninew
Tags: product attributes, product attribute, product terms, product term, shortcode
Donate link: https://ko-fi.com/ninetyninew
Stable tag: 2.0.1
Tested up to: 6.6.1
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Shortcode to display a linked list of terms from a product attribute, such as all brand links from a brands attribute.

== Description ==

Provides a shortcode to display a linked list of terms from a product attribute, such as all brand links from a brands attribute.

This extension is intended to be used where you want to display all terms from a product attribute, e.g. to display a list of all brands in the footer of your website. It is not intended to be used to display a list of terms associated with a specific product.

= Features =

- Displays a list of all terms from a specific product attribute
- Choose if these links filter products or go to term archives
- List of terms does not reduce as products are filtered
- Various shortcode attributes to modify the display/functionality
- Elements include classes and IDs for custom CSS styling

= Usage =

Use the following shortcode replacing the `x` with your attribute name:

`[wcpas_product_attributes attribute="x"]`

If your attribute has spaces in the name then replace these with hyphens in the shortcode, if you have other characters in your attribute name see the related FAQ below.

There are various other shortcode attributes which can be used, if these are omitted the defaults are used, the available shortcode attributes are:

- `archive_links` - `1` to enable archive links e.g. `/brand/sega/`, `0` to disable e.g. `/shop/?filter_brand=sega`, default is `0`
- `categorize` - `first_character` to categorize the terms list by first character, default is empty
- `current_attribute_link` - `1` to still display a link if the current page, `0` to disable, default is `1`
- `hide_empty` - `1` to hide empty terms, `0` to disable, default is `1`
- `links_target` - Use a HTML link target e.g. `_blank` to open links in a new window, default is empty
- `min_price` - Use a minimum price number for links to include a minimum price filter, requires `archive_links="0"`, default is empty
- `max_price` - Use a maximum price number for links to include a maximum price filter, requires `archive_links="0"`, default is empty
- `order` - `asc` or `desc`, default is `asc`
- `orderby` - Any [orderby](https://developer.wordpress.org/reference/classes/wp_term_query/__construct/#parameters) parameter, default is name
- `show_counts` - `1` to enable display of term counts, `0` to disable, default is `0`
- `show_descriptions` - `1` to enable display of term descriptions, `0` to disable, default is `0`

Example shortcode using multiple shortcode attributes:

`[wcpas_product_attributes attribute="brand" archive_links="0" categorize="first_character" current_attribute_link="0" hide_empty="0" links_target="_blank" min_price="50.00" max_price="500.00" order="desc" orderby="id" show_counts="1" show_descriptions="1"]`

= Donate =

If this product has helped you, please consider [making a donation](https://ko-fi.com/ninetyninew).

== Screenshots ==

1. Shortcode display
2. Shortcode display with categorize option used
2. Shortcode display with various additional options used
4. Shortcode added via block

== Frequently Asked Questions ==

= Nothing is displaying? =

Ensure you have included the shortcode correctly and that the attribute you are using has products assigned. If nothing is still displaying the attribute name you have entered may not be in the correct format, see the related FAQ for more information.

= What attribute name/format do I use? =

If your attribute is a single word you just need to enter that. If your attribute has spaces in the name, replace these with hyphens in the shortcode attribute. For more complex attribute names e.g. with special characters you can determine what the attribute name is by editing the attribute in Products > Attributes, in the URL you will see something like `/edit-tags.php?taxonomy=pa_example-attribute&post_type=product`, the part after the `pa_` and before the `&` is your attribute name, in this example it is `example-attribute`.

= Why aren't my list of terms linked? =

The list will not include links on the terms if you are using `archive_links="1"` and the attribute used does not have archives enabled on the attribute which is set when editing the attribute in Products > Attributes .

= My list of terms are linked but the links do not work? =

Links are filter based which use your shop page (unless you are using `archive_links="1"`), if they are not working you may have not configured a shop page in WooCommerce, this is created during installation of WooCommerce, if it has been removed it will need recreating.

= Minimum/maximum price is not working? =

These are filter based and therefore require `archive_links="0"`.

= Can I split the list of terms up? =

Use the `categorize` shortcode attribute as described in the usage information above.

= Can I use it in a page, post, widget, block, etc? =

You can use it anywhere you can use a shortcode.

= Can I use it in a PHP template? =

Yes, use the `do_shortcode` function of WordPress to echo the shortcode.

= How can I display it as a horizontal list rather than a bullet list? =

The output is based on a `<ul>` element, however you can use some custom CSS to make the `<ul>` list display horizontally, and optionally with a separating character, such as a comma. See this [custom CSS example](https://jsfiddle.net/c6mg5kyv/). If using the `categorize` shortcode attribute the linked example will need some additional custom CSS rules due to the additional category markup it includes.

= How do I style it? =

We intentionally do not add any default styling to keep the extension as simple/lightweight as possible and to reduce the need to override any default CSS styles. If you wish to add your own styling, you will need to apply some custom CSS styling, each shortcode used outputs a parent `<ul>` element containing child elements. All elements have classes and IDs for custom CSS styling purposes.

== Installation ==

Before using this product, please ensure you review and accept our [terms and conditions](https://99w.co.uk/#terms-conditions) and [privacy policy](https://99w.co.uk/#privacy-policy).

Before using this product on a production website you should thoroughly test it on a staging/development environment, including all aspects of your website and potential data volumes, even if not directly related to the functionality the product provides.

The same process should also be completed when updating any aspect of your website in future, such as performing installations/updates, making changes to any configuration, custom web development, etc.

Always refer to the changelog before updating.

= Installation =

Please see [this documentation](https://wordpress.org/support/article/managing-plugins/#installing-plugins-1).

= Updates =

Please see [this documentation](https://wordpress.org/documentation/article/manage-plugins/#updating-plugins).

= Minimum Requirements =

* PHP 7.4.0
* WooCommerce 8.5.0
* WordPress 6.3.0

= BETA Functionality =

We may occasionally include BETA functionality, this is highlighted with a `(BETA)` label. Functionality with this label should be used with caution and is only recommended to be tested on a staging/development environment. The functionality is included so users can test the functionality/provide feedback before it becomes stable, at which point the `(BETA)` label will be removed. Note that there may be occasions where BETA functionality is determined unsuitable for use and removed entirely.

= Caching =

If you are using any form of caching then it is recommended that the cache lifespan/expiry should be set to 10 hours or less. This is recommended by most major caching solutions to avoid potential issues with WordPress nonces.

= Screen Sizes =

- Frontend: Where elements may be displayed on the frontend they will fit within the screen width
- Backend: Where interfaces may be displayed it is recommended to use a desktop computer with a resolution of 1920x1080 or higher, for lower resolutions any interfaces will attempt to fit within the screen width but some elements may be close together and/or larger than the screen width

= Translation =

We generally recommend [Loco Translate](https://wordpress.org/plugins/loco-translate/) to translate and/or adapt text strings within this product.

= Works With =

Where we have explicitly stated this product works with another product, this should only be assumed accurate if you are using the version of the other product which was the latest at the time the latest version of this product was released. This is because, while usually unlikely, the other product may have changed functionality which effects this product.

== Changelog ==

= 2.0.1 - 2024-08-23 =

* Add: .pot to languages folder
* Update: PHP requires at least 7.4.0
* Update: WooCommerce requires at least 8.5.0
* Update: WooCommerce tested up to 9.2.2
* Update: WordPress requires at least 6.3.0
* Update: WordPress tested up to 6.6.1

= 2.0.0 - 2024-07-18 =

* Add: categorize shortcode attribute
* Add: Requires WooCommerce dependency header
* Update: Readme.txt with categorize shortcode attribute information and other updates
* Update: WooCommerce tested up to 9.1.2
* Update: WordPress tested up to 6.6.0

= 1.9.3 - 2024-07-09 =

* Update: composer.json and composer.lock to woocommerce/woocommerce-sniffs 1.0.0
* Update: Installation and updates information in readme.txt
* Update: phpcs.xml codesniffs
* Update: WooCommerce tested up to 9.0.2
* Update: WordPress tested up to 6.5.5

= 1.9.2 - 2024-04-10 =

* Add: Translation information in readme.txt
* Update: Horizontal list information and example in readme.txt
* Update: Intended use cases in readme.txt
* Update: WooCommerce tested up to 8.7.0
* Update: WordPress tested up to 6.5.2

= 1.9.1 - 2024-03-08 =

* Add: BETA functionality information to readme.txt
* Add: Caching information to readme.txt
* Add: Donation information to readme.txt
* Add: Works with information to readme.txt
* Update: Screen sizes information in readme.txt
* Update: WooCommerce tested up to 8.6.1
* Update: WordPress tested up to 6.4.3

= 1.9.0 - 2024-01-16 =

* Add: WooCommerce Cart/Checkout blocks compatibility
* Update: Changelog consistency

= 1.8.0 - 2023-12-18 =

* Add: current_attribute_link shortcode attribute
* Add: Class of wcpas-product-attribute-current to current attribute li element
* Update: Changelog keys
* Update: Code consistency
* Update: Development assets
* Update: PHP requires at least 7.3.0
* Update: WooCommerce requires at least 7.9.0
* Update: WooCommerce tested up to 8.4.0
* Update: WordPress requires at least 6.1.0
* Update: WordPress tested up to 6.4.2

= 1.7.0 - 2023-09-19 =

* Add: links_target shortcode attribute
* Add: show_descriptions shortcode attribute
* Add: Additional classes/IDs to markup for targeted custom CSS styling purposes
* Update: WooCommerce tested up to 8.1.1
* Update: WordPress tested up to 6.3.1

= 1.6.0 - 2023-08-02 =

* Add: High Performance Order Storage (HPOS) compatibility if WooCommerce version is 8.0.0 or higher, note that this version includes several changes for HPOS compatibility, it is recommended you perform this update on a staging/development environment before updating the extension on a production website regardless of whether HPOS enabled, HPOS and the compatibility in this extension are very new, use with caution
* Update: Development assets
* Update: PHP requires at least 7.2.0
* Update: WooCommerce requires at least 7.3.0
* Update: WooCommerce tested up to 7.9.0
* Update: WordPress requires at least 5.9.0
* Update: WordPress tested up to 6.2.2

= 1.5.0 - 2022-10-22 =

* Add: If attribute is not entered lower case it will still display the list
* Update: Filter links now use add_query_arg
* Update: Code refactoring
* Update: PHP requires at least 7.0.0
* Update: WooCommerce requires at least 5.0.0
* Update: WordPress requires at least 5.4.0
* Update: WordPress tested up to 6.0.3

= 1.4.0 - 2022-04-23 =

* Add: Escaping of URLs, attributes and overall attributes list markup
* Update: WordPress tested up to 5.9.3

= 1.3.0 - 2022-03-25 =

* Add: wcpas_product_attributes_translation function
* Add: WooCommerce not installed/activated notice
* Update: WordPress tested up to 5.9.2
* Fix: Translations may not load due to load_plugin_textdomain not hooked on init

= 1.2.0 - 2021-07-16 =

* Add: min_price shortcode attribute (archive_links must be false to use)
* Add: max_price shortcode attribute (archive_links must be false to use)

= 1.1.0 - 2021-05-12 =

* Add: archive_links shortcode attribute
* Update: Default link used is a filter based term link

= 1.0.0 - 2021-05-11 =

* New: Initial release