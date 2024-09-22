=== WC Hide Shipping Methods ===

Contributors: rynald0s, riaanknoetze
Tags: woocommerce free shipping, hide shipping methods, hide shipping rates, force free shipping, hide other shipping methods
Requires at least: 6.5.0
Tested up to: 6.5
Stable tag: 1.8.1
WC requires at least: 3.9.4
WC tested up to: 8.1
License: GPLv3 or later License
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin hides all other shipping methods when "Free Shipping" is available, but you can keep "Local Pickup" alongside it.


== Description ==

WC Hide Shipping Methods is a simple and effective plugin that hides all other shipping methods when "Free Shipping" is available during the checkout process. Additionally, it provides an option to keep "Local Pickup" available alongside "Free Shipping" if desired.

Key features:
- Automatically hides all other shipping methods when "Free Shipping" is available.
- Option to keep "Local Pickup" available with "Free Shipping".
- Easy integration with WooCommerce settings.
- Compatible with WooCommerce shipping zones.
- Compatible with both classic and modern block-based checkout methods (i.e. Gutenberg & WooCommerce blocks).

== Installation ==

1. Download the plugin & install it to your `wp-content/plugins` folder (or use the Plugins menu through the WordPress Administration section).
2. Activate the plugin.
3. Navigate to **WooCommerce > Settings > Shipping > Shipping options**.
4. Select your preferred option:
   - Show "Free Shipping" only.
   - Show "Free Shipping" and "Local Pickup" only.
5. Save changes and enjoy the optimized checkout experience!

== Frequently Asked Questions ==

= Q: Is this plugin compatible with WooCommerce shipping zones? =
A: Yes, the plugin is fully compatible with WooCommerce's shipping zones feature.

= Q: Where can I go if I find an issue or want to recommend a feature? =
A: You can submit issues or feature requests on the [Public GitHub Repository](https://github.com/riaanknoetze/wc-hide-shipping-methods-free/issues).

== Screenshots ==

1. Plugin settings.
2. Checkout showing only "Free Shipping".

== Changelog ==

= 1.8.1 =
* Updated banner and screenshots.
* Fixed SVN and stable tag issues.
* Revised the readme.txt file.

= 1.8 =
* New - Refactor code to use classes [#2](https://github.com/RiaanKnoetze/wc-hide-shipping-methods/issues/2)
* New - Add admin dependency notice when WooCommerce is disabled [#8](https://github.com/RiaanKnoetze/wc-hide-shipping-methods/issues/8)
* New - Add plugin action links for easy access to settings page [#1](https://github.com/RiaanKnoetze/wc-hide-shipping-methods/issues/1)
* Fix - Display all free shipping methods instead of just the first matched one [#3](https://github.com/RiaanKnoetze/wc-hide-shipping-methods/issues/3)
* Tweak - Add fallback values when checking plugin options [#2](https://github.com/RiaanKnoetze/wc-hide-shipping-methods/issues/7)
* Tweak - Add Local Pickup compatibility notice [#9](https://github.com/RiaanKnoetze/wc-hide-shipping-methods/issues/9)

= 1.7 =
* New - Add HPOS compatibility
* New - Add Cart/Checkout block compatibility

= 1.6 =
* New - Refactored code with guard clauses for better readability.
* New - Updated plugin and contributor information.
* New - Added PHP compatibility notices
* New - Added WooCommerce version compatibility notices
* New - Added localizations and translations
* Tweak - Updated readme file
* Tweak - Shortened function names for improved code clarity.

= 1.5 = 
* Tested for WordPress 5.8.1.
* Tested for WooCommerce 5.6.0.
* Updated readme.txt file.
* Updated version numbers.

= 1.4 = 
* Tested for WordPress 5.4.2.
* Tested for WooCommerce 4.2.0.
* Updated readme.txt file.
* Updated version numbers.
* Updated author URI.

= 1.3 = 
* Tested for WordPress 4.7.4.
* Tested for WooCommerce 3.0.4.
* Updated readme.txt file.
* Updated version number.

= 1.2 = 
* Updated assets.
* Updated readme.txt file.

= 1.1 = 
* Resolved "2019 years ago" issue.

= 1.0 =  
* Initial release.

== Upgrade Notice ==

= 1.6 =
* This version includes code refactoring, shortened function names, and updated contributor information. It's recommended to update for a better development experience and compatibility.

= 1.5 = 
* Tested with the latest versions of WordPress and WooCommerce. Recommended update for compatibility.

= 1.4 = 
* Tested with WordPress 5.4.2 and WooCommerce 4.2.0. Compatibility update.

= 1.3 = 
* Compatibility tested with WordPress 4.7.4 and WooCommerce 3.0.4.

= 1.2 = 
* Updated assets and documentation.

= 1.1 = 
* Resolved "2019 years ago" issue. Important update.

= 1.0 =
* Initial release.
