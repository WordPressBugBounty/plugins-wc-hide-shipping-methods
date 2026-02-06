=== WC Hide Shipping Methods ===
Contributors: saadiqbal, wpexpertsio
Tags: woocommerce free shipping, hide shipping methods, hide shipping rates, force free shipping, hide other shipping methods
Requires at least: 6.5.0
Tested up to: 6.8
Stable tag: 2.0.5
WC requires at least: 3.9.4
WC tested up to: 10.0
License: GPLv3 or later License
License URI: http://www.gnu.org/licenses/gpl-3.0.html

This plugin automatically hides all other shipping methods when "Free Shipping" is available, while allowing you to retain "Local Pickup" and any additional shipping methods you select from your shipping zones.


== Description ==

WC Hide Shipping Methods is a simple and effective plugin that hides all other shipping methods when "Free Shipping" is available during the checkout process. Additionally, it provides an option to keep "Local Pickup" available alongside "Free Shipping" if desired.

Key features:
- Automatic Shipping Method Hiding: Hides all other shipping options when “Free Shipping” is available for a cleaner checkout experience.
- Local Pickup Option: Choose to keep “Local Pickup” available alongside “Free Shipping.”
- Additional Shipping Methods: Administrators can select extra shipping methods (pulled from available WooCommerce shipping zones) to display in addition to the default options.
- Seamless WooCommerce Integration: Easily managed through WooCommerce settings.
- Shipping Zone Compatibility: Dynamically pulls shipping methods from your WooCommerce shipping zones.
- Block & Classic Compatibility: Fully supports both the modern block-based checkout (Gutenberg & WooCommerce blocks) and the classic WooCommerce checkout.

== Installation ==

1. Download the plugin & install it to your `wp-content/plugins` folder (or use the Plugins menu through the WordPress Administration section).
2. Activate the plugin.
3. Navigate to **WooCommerce > Settings > Shipping > Shipping options**.
4. Select your preferred option:
   - Show "Free Shipping" only.
   - Show "Free Shipping" and "Local Pickup" only.
   - Select any additional shipping methods you would like to show alongside that.
5. Save changes and enjoy the optimized checkout experience!

== Frequently Asked Questions ==

= Q: Is this plugin compatible with WooCommerce shipping zones? =
A: Yes, the plugin is fully compatible with WooCommerce's shipping zones feature.

= Q: Is this plugin compatible with the new Local Pickup (Blocks edition)? =
A: Yes, the plugin is fully compatible with WooCommerce's new Local Pickup (Blocks edition).

= Q: Where can I go if I find an issue or want to recommend a feature? =
A: You can submit issues or feature requests on the [WPExperts Support Center](https://objectsws.atlassian.net/servicedesk/customer/portal/4).

== Screenshots ==

1. Plugin settings.
2. Checkout showing only "Free Shipping".

== Changelog ==
= 2.0.5 =
* Tested up to WordPress 6.8 and WooCommerce 10.0

= 2.0.4 =
* Back to old directory and structure

= 2.0.3 =
* Minor tweaks

= 2.0.2 =
* Fix - Minor bug fixes

= 2.0.l =
* Tested up to WordPress 6.8+

= 2.0 =
* NEW - REST API
* NEW - Tier Pricing in Global Wholesale Pricing
* NEW - Order Notification Email
* NEW - Wholesale Registration on My Account Page
* New - Wholesale Registration Page redirect
* NEW - Hide Price Option
* NEW - Disable Auto Approval Wholesale User
* NEW - Wholesale User Add Requests
* NEW - Hide Retail Price and Save Price Labels
* NEW - Disabled Coupons for Wholesale User
* NEW - Upgrade Customer to Wholesale User
* NEW - Wholesale Pricing Global Option
* NEW - New User Registration Notification
* NEW - Wholesale Registration Form

= 1.8.4 =
* WooCommerce Compatibility: Now tested up to WooCommerce 9.7.1.
* Additional Shipping Methods: Added a new multiselect setting that lets admins choose extra shipping methods (pulled dynamically from shipping zones) to display alongside “Free Shipping” and “Local Pickup.”
* Filter Priority Change: Increased the priority of shipping method filters from 10 to 99 to ensure compatibility with other plugins.

= 1.8.3 =
* Fix: Updated method_id check to account for WooCommerce's change from local_pickup to pickup_location, while maintaining backward compatibility with previous WooCommerce versions.

= 1.8.2 =
* Tested for WordPress 6.7.1
* Tested for WooCommerce 9.5.2

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
