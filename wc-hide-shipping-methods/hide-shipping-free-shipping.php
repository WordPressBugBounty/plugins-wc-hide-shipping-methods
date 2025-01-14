<?php
/**
 * Plugin Name: WC Hide Shipping Methods
 * Plugin URI: https://wordpress.org/plugins/wc-hide-shipping-methods/
 * Documentation URI: https://wccare.co/
 * Description: Hides other shipping methods when "Free shipping" is available.
 * Author: Rynaldo Stoltz
 * Author URI: https://wccare.co/
 * Version: 1.8.3
 * Text Domain: wc-hide-shipping-methods
 * Domain Path: /languages
 * License: GPLv3 or later License
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least: 3.9.4
 * WC tested up to: 9.5.2
 * Requires at least: 6.5
 * Requires PHP: 7.4
 *
 * @package WC_Hide_Shipping_Methods
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * WC_Hide_Shipping_Methods class.
 *
 * Handles the hiding of shipping methods based on the settings in WooCommerce.
 */
class WC_Hide_Shipping_Methods
{
    /**
     * Constructor to initialize the class.
     */
    public function __construct()
    {
        // Check if WooCommerce is active, if not, show an admin notice.
        add_action('admin_notices', [$this, 'check_woocommerce_active']);

        // Add WooCommerce settings and declare compatibility.
        add_filter('woocommerce_get_settings_shipping', [$this, 'add_settings'], 10, 2);
        add_action('before_woocommerce_init', [$this, 'declare_woocommerce_compatibility']);

        // Register activation hook.
        register_activation_hook(__FILE__, [$this, 'update_default_option']);

        // Add plugin action links.
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'plugin_action_links']);

        // Apply filters for hiding shipping methods.
        $this->apply_shipping_method_filters();
    }

    /**
     * Checks if WooCommerce is active and shows a warning if it is not.
     */
    public function check_woocommerce_active()
    {
        if (!is_plugin_active('woocommerce/woocommerce.php')) {
            $class   = 'error';
            $message = sprintf(
                __(
                    '<strong>WC Hide Shipping Methods is inactive.</strong> The <a href="%s" target="_blank">WooCommerce plugin</a> must be active for this plugin to work.',
                    'wc-hide-shipping-methods'
                ),
                esc_url('https://wordpress.org/plugins/woocommerce/')
            );
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), wp_kses_post($message));
        }
    }

    /**
     * Adds custom settings to WooCommerce shipping settings.
     *
     * @param array $settings WooCommerce shipping settings.
     * @return array Updated WooCommerce shipping settings.
     */
    public function add_settings($settings)
    {
        $settings[] = [
            'title' => __('Shipping Method Visibility', 'wc-hide-shipping-methods'),
            'type'  => 'title',
            'id'    => 'wc_hide_shipping',
        ];

        $settings[] = [
            'title'    => __('Free Shipping: ', 'wc-hide-shipping-methods'),
            'desc'     => '',
            'id'       => 'wc_hide_shipping_options',
            'type'     => 'radio',
            'desc_tip' => true,
            'options'  => [
                'hide_all'          => __('Show "Free Shipping" only (if available). Hide all the other methods', 'wc-hide-shipping-methods'),
                'hide_except_local' => __('Show "Free Shipping" and "Local Pickup" only (if available). Hide all the other methods.', 'wc-hide-shipping-methods'),
            ],
        ];

        $settings[] = [
            'type' => 'sectionend',
            'id'   => 'wc_hide_shipping',
        ];

        return $settings;
    }

    /**
     * Apply filters based on the selected shipping method option.
     */
    private function apply_shipping_method_filters()
    {
        $option = get_option('wc_hide_shipping_options', 'hide_all'); // Default to 'hide_all' if option is not set.

        if ('hide_all' === $option) {
            add_filter('woocommerce_package_rates', [$this, 'hide_shipping_when_free_is_available'], 10, 2);
        } elseif ('hide_except_local' === $option) {
            add_filter('woocommerce_package_rates', [$this, 'hide_shipping_when_free_is_available_keep_local'], 10, 2);
        }
    }

    /**
     * Hide all other shipping methods when free shipping is available.
     *
     * @param array $rates Array of available shipping rates.
     * @return array Filtered array of shipping rates.
     */
    public function hide_shipping_when_free_is_available($rates)
    {
        $free = [];
        foreach ($rates as $rate_id => $rate) {
            if ('free_shipping' === $rate->method_id) {
                $free[$rate_id] = $rate;
            }
        }
        return !empty($free) ? $free : $rates;
    }

    /**
     * Hide all other shipping methods except Local Pickup when free shipping is available.
     *
     * @param array $rates Array of available shipping rates.
     * @param array $package The package array being shipped.
     * @return array Filtered array of shipping rates.
     */
    public function hide_shipping_when_free_is_available_keep_local($rates, $package)
    {
        $new_rates = [];
        foreach ($rates as $rate_id => $rate) {
            if ('free_shipping' === $rate->method_id) {
                $new_rates[$rate_id] = $rate;
            }
        }

        if (!empty($new_rates)) {
            foreach ($rates as $rate_id => $rate) {
                // Check for both 'pickup_location' and 'local_pickup' for backward compatibility.
                if ('pickup_location' === $rate->method_id || 'local_pickup' === $rate->method_id) {
                    $new_rates[$rate_id] = $rate;
                }
            }
            return $new_rates;
        }

        return $rates;
    }

    /**
     * Update the default option when the plugin is activated.
     */
    public function update_default_option()
    {
        update_option('wc_hide_shipping_options', 'hide_all');
    }

    /**
     * Declare plugin compatibility with WooCommerce HPOS and Cart & Checkout Blocks.
     */
    public function declare_woocommerce_compatibility()
    {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }

    /**
     * Adds a settings link to the plugins page.
     *
     * @param array $links Array of action links.
     * @return array Modified array of action links.
     */
    public function plugin_action_links($links)
    {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=wc-settings&tab=shipping&section=options')) . '">' . __('Settings', 'wc-hide-shipping-methods') . '</a>';
        array_unshift($links, $settings_link); // Add the settings link to the beginning of the array.
        return $links;
    }
}

// Initialize the plugin.
new WC_Hide_Shipping_Methods();
