<?php
/**
 * Plugin Name: WC Hide Shipping Methods
 * Plugin URI: https://wordpress.org/plugins/wc-hide-shipping-methods/
 * Description: Hides other shipping methods when "Free shipping" is available.
 * Author: WPExperts
 * Author URI: https://wpexperts.io
 * Version: 2.0.5
 * Text Domain: wc-hide-shipping-methods
 * Domain Path: /languages
 * License: GPLv3 or later License
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least: 3.9.4
 * WC tested up to: 9.9
 * Requires at least: 6.5
 * Requires PHP: 7.4
 *
 * @package WC_Hide_Shipping_Methods
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Custom function to output a multiselect field for WooCommerce settings.
 *
 * @param array $value Field arguments.
 */
if ( ! function_exists( 'woocommerce_admin_field_multiselect' ) ) {
    function woocommerce_admin_field_multiselect( $value ) {
        // Get the saved option.
        $option_value = get_option( $value['id'], $value['default'] );
        if ( ! is_array( $option_value ) ) {
            $option_value = explode( ',', $option_value );
        }
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
            </th>
            <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ); ?>">
                <select multiple="multiple" style="<?php echo esc_attr( $value['css'] ); ?>" class="<?php echo esc_attr( $value['class'] ); ?>" id="<?php echo esc_attr( $value['id'] ); ?>" name="<?php echo esc_attr( $value['id'] ); ?>[]">
                    <?php
                    foreach ( $value['options'] as $key => $label ) {
                        $selected = in_array( $key, (array) $option_value, true ) ? 'selected="selected"' : '';
                        echo '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $label ) . '</option>';
                    }
                    ?>
                </select>
                <?php echo isset( $value['desc'] ) ? '<p class="description">' . wp_kses_post( $value['desc'] ) . '</p>' : ''; ?>
            </td>
        </tr>
        <?php
    }
}

/**
 * WC_Hide_Shipping_Methods class.
 *
 * Handles the hiding of shipping methods based on the settings in WooCommerce.
 */
class WC_Hide_Shipping_Methods {

    /**
     * Constructor to initialize the class.
     */
    public function __construct() {
        // Check if WooCommerce is active, if not, show an admin notice.
        add_action( 'admin_notices', [ $this, 'check_woocommerce_active' ] );

        // Add WooCommerce settings and declare compatibility.
        add_filter( 'woocommerce_get_settings_shipping', [ $this, 'add_settings' ], 10, 2 );
        add_action( 'before_woocommerce_init', [ $this, 'declare_woocommerce_compatibility' ] );

        // Register activation hook.
        register_activation_hook( __FILE__, [ $this, 'update_default_option' ] );

        // Add plugin action links.
        add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'plugin_action_links' ] );
        
        if( ! defined( 'SMTP_EL_VERSION' ) ) {
            add_filter( 'woocommerce_settings_tabs_array', array( $this, 'smtp_email_logs_tab' ), 99 );
            add_action( 'woocommerce_settings_tabs_smtp_email_logs', array( $this, 'smtp_email_logs_settings' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        }

        // Apply filters for hiding shipping methods with a higher priority.
        $this->apply_shipping_method_filters();
    }

    /**
     * Checks if WooCommerce is active and shows a warning if it is not.
     */
    public function check_woocommerce_active() {
        if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            $class   = 'error';
            $message = sprintf(
                __(
                    '<strong>WC Hide Shipping Methods is inactive.</strong> The <a href="%s" target="_blank">WooCommerce plugin</a> must be active for this plugin to work.',
                    'wc-hide-shipping-methods'
                ),
                esc_url( 'https://wordpress.org/plugins/woocommerce/' )
            );
            printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
        }
    }

    /**
     * Adds custom settings to WooCommerce shipping settings.
     *
     * @param array $settings WooCommerce shipping settings.
     * @return array Updated WooCommerce shipping settings.
     */
    public function add_settings( $settings ) {
        $settings[] = [
            'title' => __( 'Shipping Method Visibility', 'wc-hide-shipping-methods' ),
            'type'  => 'title',
            'id'    => 'wc_hide_shipping',
        ];

        $settings[] = [
            'title'    => __( 'Free Shipping: ', 'wc-hide-shipping-methods' ),
            'desc'     => '',
            'id'       => 'wc_hide_shipping_options',
            'type'     => 'radio',
            'desc_tip' => true,
            'options'  => [
                'hide_all'          => __( 'Show "Free Shipping" only (if available). Hide all the other methods', 'wc-hide-shipping-methods' ),
                'hide_except_local' => __( 'Show "Free Shipping" and "Local Pickup" only (if available). Hide all the other methods.', 'wc-hide-shipping-methods' ),
            ],
        ];

        // ------------------------------------------------------------------
        // NEW: Additional methods to display (Pulled from available shipping zones)
        // ------------------------------------------------------------------
        $settings[] = [
            'title'             => __( 'Additional methods to display alongside the above setting', 'wc-hide-shipping-methods' ),
            'desc'              => __( 'Select any additional shipping methods you want to display alongside free shipping (and local pickup, if applicable).', 'wc-hide-shipping-methods' ),
            'id'                => 'wc_hide_shipping_additional_methods',
            'type'              => 'multiselect', // Our custom multiselect field.
            'class'             => 'wc-enhanced-select',
            'css'               => 'width: 400px;',
            'options'           => $this->get_available_shipping_methods(),
            'default'           => [],
        ];
        // ------------------------------------------------------------------

        $settings[] = [
            'type' => 'sectionend',
            'id'   => 'wc_hide_shipping',
        ];

        // ------------------------------------------------------------------
        // NEW: Support & notice section
        // ------------------------------------------------------------------
        $settings[] = [
            'title' => __( 'Support & Other Plugins', 'wc-hide-shipping-methods' ),
            'type'  => 'title',
            'id'    => 'wc_hide_shipping_notice',
            'desc'  => '<div style="border: 1px solid #ddd; border-radius: 4px; background-color: #fff; padding: 20px; margin: 10px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                        <p style="font-size: 15px; margin: 0 0 15px;">For support with this plugin, please visit <a href="https://orcawp.com" target="_blank" style="color: #0073aa; text-decoration: none;">Orca</a>. You may also be interested in some of our other plugins:</p>
                                <strong><a href="https://orcawp.com/product/advanced-per-product-shipping-for-woocommerce/" target="_blank" style="color: #0073aa; text-decoration: none;">Advanced Per Product Shipping for WooCommerce</a></strong>: Add shipping fees for individual products or entire product categories, and even restrict it to specific WooCommerce shipping zones or target custom zip/postcodes.</br>
                                <strong><a href="https://orcawp.com/product/shipping-importer-and-exporter-for-woocommerce/" target="_blank" style="color: #0073aa; text-decoration: none;">Shipping Importer and Exporter for WooCommerce</a></strong>: Easily export and import your shipping zones, methods, locations, rates, and settings with just a few clicks. 
                        </ul>
                    </div>',
        ];
        $settings[] = [
            'type' => 'sectionend',
            'id'   => 'wc_hide_shipping_notice',
        ];

        return $settings;
    }

    /**
     * Helper method to dynamically pull available shipping methods from WooCommerce shipping zones.
     *
     * @return array Available shipping methods with keys in the format "method_id:instance_id" (if applicable) and values as titles.
     */
    public function get_available_shipping_methods() {
        $available = [];

        if ( ! class_exists( 'WC_Shipping_Zones' ) ) {
            return $available;
        }

        // Get default zone (zone id 0) which covers methods not assigned to a specific zone.
        $default_zone    = new WC_Shipping_Zone( 0 );
        $default_methods = $default_zone->get_shipping_methods();
        foreach ( $default_methods as $method ) {
            $title = method_exists( $method, 'get_title' ) ? $method->get_title() : $method->get_method_title();
            $key   = $method->id;
            if ( isset( $method->instance_id ) && $method->instance_id ) {
                $key .= ':' . $method->instance_id;
            }
            $available[ $key ] = $title . ' (' . __( 'Default Zone', 'wc-hide-shipping-methods' ) . ')';
        }

        // Get all configured zones.
        $zones = WC_Shipping_Zones::get_zones();
        if ( is_array( $zones ) && ! empty( $zones ) ) {
            foreach ( $zones as $zone_data ) {
                $zone         = new WC_Shipping_Zone( $zone_data['id'] );
                $zone_methods = $zone->get_shipping_methods();
                foreach ( $zone_methods as $method ) {
                    $title = method_exists( $method, 'get_title' ) ? $method->get_title() : $method->get_method_title();
                    $key   = $method->id;
                    if ( isset( $method->instance_id ) && $method->instance_id ) {
                        $key .= ':' . $method->instance_id;
                    }
                    $available[ $key ] = $title . ' (' . $zone->get_zone_name() . ')';
                }
            }
        }

        return $available;
    }

    /**
     * Apply filters based on the selected shipping method option.
     */
    private function apply_shipping_method_filters() {
        $option = get_option( 'wc_hide_shipping_options', 'hide_all' ); // Default to 'hide_all' if option is not set.

        // Use a higher priority (99) so that our filter runs later.
        if ( 'hide_all' === $option ) {
            add_filter( 'woocommerce_package_rates', [ $this, 'hide_shipping_when_free_is_available' ], 99, 2 );
        } elseif ( 'hide_except_local' === $option ) {
            add_filter( 'woocommerce_package_rates', [ $this, 'hide_shipping_when_free_is_available_keep_local' ], 99, 2 );
        }
    }

    /**
     * Hide all other shipping methods when free shipping is available.
     *
     * @param array $rates Array of available shipping rates.
     * @return array Filtered array of shipping rates.
     */
    public function hide_shipping_when_free_is_available( $rates ) {
        $free       = [];
        // Ensure the additional methods value is treated as an array.
        $additional = (array) get_option( 'wc_hide_shipping_additional_methods', [] );

        foreach ( $rates as $rate_id => $rate ) {
            if ( 'free_shipping' === $rate->method_id ) {
                $free[ $rate_id ] = $rate;
            } elseif ( in_array( $rate->method_id . ( isset( $rate->instance_id ) && $rate->instance_id ? ':' . $rate->instance_id : '' ), $additional, true ) ) {
                $free[ $rate_id ] = $rate;
            }
        }
        return ! empty( $free ) ? $free : $rates;
    }

    /**
     * Hide all other shipping methods except Local Pickup when free shipping is available.
     *
     * @param array $rates Array of available shipping rates.
     * @param array $package The package array being shipped.
     * @return array Filtered array of shipping rates.
     */
    public function hide_shipping_when_free_is_available_keep_local( $rates, $package ) {
        $new_rates  = [];
        // Ensure the additional methods value is treated as an array.
        $additional = (array) get_option( 'wc_hide_shipping_additional_methods', [] );

        foreach ( $rates as $rate_id => $rate ) {
            if ( 'free_shipping' === $rate->method_id ) {
                $new_rates[ $rate_id ] = $rate;
            }
        }

        if ( ! empty( $new_rates ) ) {
            foreach ( $rates as $rate_id => $rate ) {
                if ( 'pickup_location' === $rate->method_id ||
                     'local_pickup' === $rate->method_id ||
                     in_array( $rate->method_id . ( isset( $rate->instance_id ) && $rate->instance_id ? ':' . $rate->instance_id : '' ), $additional, true )
                ) {
                    $new_rates[ $rate_id ] = $rate;
                }
            }
            return $new_rates;
        }

        return $rates;
    }

    /**
     * Update the default option when the plugin is activated.
     */
    public function update_default_option() {
        update_option( 'wc_hide_shipping_options', 'hide_all' );
    }

    /**
     * Declare plugin compatibility with WooCommerce HPOS and Cart & Checkout Blocks.
     */
    public function declare_woocommerce_compatibility() {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
        }
    }

    /**
     * Adds a settings link to the plugins page.
     *
     * @param array $links Array of action links.
     * @return array Modified array of action links.
     */
    public function plugin_action_links( $links ) {
        $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=options' ) ) . '">' . __( 'Settings', 'wc-hide-shipping-methods' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }

    public function smtp_email_logs_tab( $tabs ) {
        $tabs['smtp_email_logs'] = __( 'SMTP', 'wc-hide-shipping-methods' );

        return $tabs;
    }

    public function smtp_email_logs_settings() {
        ?>
        <style>
        .smtp-providers-list {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 12px;
            margin-bottom: 32px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .smtp-providers-list h2 {
            font-size: 1.5em;
            margin-bottom: 8px;
            color: #1a1a1a;
        }
        .smtp-provider-row {
            display: flex;
            align-items: center;
            background: #fff;
            border-radius: 10px;
            margin-bottom: 14px;
            padding: 20px 26px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.06);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        .smtp-provider-logo {
            width: 38px;
            height: 38px;
            margin-right: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .smtp-provider-title {
            font-size: 1.15em;
            font-weight: 700;
            margin-right: 14px;
            color: #1a1a1a;
        }
        .smtp-provider-desc {
            color: #6c757d;
            font-size: 1em;
            flex: 1;
            line-height: 1.4;
        }
        .smtp-provider-status {
            background: linear-gradient(135deg, #ffeaea 0%, #ffe0e0 100%);
            color: #d32f2f;
            font-size: 0.9em;
            padding: 4px 12px;
            border-radius: 8px;
            margin-right: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .smtp-provider-switch {
            margin-right: 14px;
            display: flex;
            align-items: center;
        }
        .smtp-toggle {
            position: relative;
            width: 52px;
            height: 26px;
            background: #e0e0e0;
            border-radius: 13px;
            margin-right: 10px;
            transition: background 0.3s;
            cursor: pointer;
            border: none;
        }
        .smtp-toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .smtp-toggle-slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: left 0.3s;
        }
        .smtp-toggle input:checked + .smtp-toggle-slider {
            left: 29px;
            background: #4caf50;
        }
        .smtp-toggle-label {
            font-size: 0.9em;
            color: #9e9e9e;
            font-weight: 700;
            margin-left: 2px;
        }
        .smtp-provider-edit {
            color: #4f46e5;
            font-size: 1.3em;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .smtp-provider-edit:hover {
            transform: scale(1.1);
        }
        .smtp-provider-row:hover {
            box-shadow: 0 4px 16px rgba(79, 70, 229, 0.15);
            border-color: #4f46e5;
            transform: translateY(-2px);
        }
        .smtp-blur {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 9998;
            backdrop-filter: blur(4px);
            display: none;
            animation: fadeIn 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .smtp-popup {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(175deg, #DAD7FF 0%, #FFFFFF 100%);
            border-radius: 16px;
            box-shadow: 0 12px 48px rgba(90, 80, 180, 0.10);
            z-index: 9999;
            padding: 48px 40px 40px 40px;
            min-width: 480px;
            max-width: 92vw;
            display: none;
            animation: popupSlideIn 0.3s ease-out;
        }
        @keyframes popupSlideIn {
            from { 
                opacity: 0;
                transform: translate(-50%, -45%);
            }
            to { 
                opacity: 1;
                transform: translate(-50%, -50%);
            }
        }
        .smtp-popup h2 {
            margin-top: 0;
            font-size: 1.9em;
            color: #101517;
        }
        .smtp-popup p {
            font-size: 1.1em;
            color: #101517;
        }
        .smtp-popup .smtp-popup-close {
            position: absolute;
            top: 16px;
            right: 20px;
            font-size: 2.2em;
            color: #7c6fc7;
            cursor: pointer;
            transition: transform 0.2s, color 0.2s;
        }
        .smtp-popup .smtp-popup-close:hover {
            transform: scale(1.1);
            color: #3a2e7a;
        }
        .smtp-popup .smtp-popup-btn {
            background: #873eff;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 18px 42px;
            font-size: 1.2em;
            font-weight: 500;
            margin-top: 24px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(90, 80, 180, 0.10);
            letter-spacing: 0.5px;
        }
        .smtp-popup .smtp-popup-btn:hover {
            background: #5007aa;
            color: #fff;
            box-shadow: 0 6px 20px rgba(90, 80, 180, 0.18);
        }
        .smtp-popup-gradient {
            background: #FFFFFF;
            color: #3a2e7a;
            text-align: center;
        }
        .smtp-popup-logo-container {
            background: linear-gradient(135deg, #edeaff 0%, #f8f9fa 100%);
            width: 96px;
            height: 96px;
            border-radius: 50%;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(90, 80, 180, 0.10), 0 0 0 6px rgba(218,215,255,0.18);
        }
        .smtp-popup-logo-container img {
            width: 60px;
            height: 60px;
        }
        .smtp-popup-heading {
            margin-top: 0;
            font-size: 2.2em;
            font-weight: 600;
            letter-spacing: -1.5px;
            color: #3a2e7a;
            text-shadow: 0 2px 12px rgba(218,215,255,0.18);
        }
        .smtp-popup-tagline {
            font-size: 1.15em;
            opacity: 0.95;
            margin: 16px auto 36px;
            max-width: 460px;
            line-height: 1.5;
            color: #4b3e8a;
        }
        .smtp-popup-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
            margin: 36px 0 50px 0;
            justify-items: stretch;
        }
        .smtp-popup-card {
            background: #ffffff;
            backdrop-filter: blur(10px);
            border-radius: 14px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0px 2px 8px rgba(0,0,0,.16);
            transition: all 0.3s;
            border: 1px solid rgba(218,215,255,0.3);
        }
        .smtp-popup-card-icon {
            background: linear-gradient(135deg, #edeaff 0%, #f8f9fa 100%);
            padding: 10px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(90, 80, 180, 0.10);
            flex-shrink: 0;
        }
        .smtp-popup-card-icon img {
            width: 25px;
            display: block;
        }
        .smtp-popup-card-text {
            font-size: 1.12em;
            font-weight: 700;
            text-align: left;
            color: #720EEC;
            text-shadow: 0 1px 4px rgba(218, 215, 255, 0.18);
        }
        .smtp-popup-link {
            text-decoration: none;
        }
        </style>
        <div class="smtp-providers-list">
            <h2>SMTP Providers</h2>
            <p style="color:#666; font-size:1.08em; margin-bottom:18px;">⚠️ Don’t risk losing sales! WooCommerce emails often fail to deliver or go to spam by default. Make sure your order emails reach the inbox — activate reliable email delivery today.</p>
            <div class="smtp-provider-row smtp-provider-office365" onclick="showSmtpProPopup()">
                <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/office365.png" class="smtp-provider-logo" alt="Microsoft 365">
                <span class="smtp-provider-title">Microsoft 365</span>
                <span class="smtp-provider-desc">Send emails using Microsoft 365 with licensed accounts for trusted and secure delivery.</span>
                <span class="smtp-provider-status">INACTIVE</span>
                <span class="smtp-provider-switch">
                    <label class="smtp-toggle">
                        <input type="checkbox" disabled>
                        <span class="smtp-toggle-slider"></span>
                    </label>
                    <span class="smtp-toggle-label">OFF</span>
                </span>
                <span class="smtp-provider-edit">&#9998;</span>
            </div>
            <div class="smtp-provider-row smtp-provider-gmail" onclick="showSmtpProPopup()">
                <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/gmail.png" class="smtp-provider-logo" alt="Gmail">
                <span class="smtp-provider-title">Gmail</span>
                <span class="smtp-provider-desc">Secure Gmail API email delivery with a fast setup and high reliability.</span>
                <span class="smtp-provider-status">INACTIVE</span>
                <span class="smtp-provider-switch">
                    <label class="smtp-toggle">
                        <input type="checkbox" disabled>
                        <span class="smtp-toggle-slider"></span>
                    </label>
                    <span class="smtp-toggle-label">OFF</span>
                </span>
                <span class="smtp-provider-edit">&#9998;</span>
            </div>
            <div class="smtp-provider-row smtp-provider-brevo" onclick="showSmtpProPopup()">
                <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/brevo.png" class="smtp-provider-logo" alt="Brevo">
                <span class="smtp-provider-title">Brevo</span>
                <span class="smtp-provider-desc">Send emails daily via Brevo SMTP, ideal for lower-volume email delivery.</span>
                <span class="smtp-provider-status">INACTIVE</span>
                <span class="smtp-provider-switch">
                    <label class="smtp-toggle">
                        <input type="checkbox" disabled>
                        <span class="smtp-toggle-slider"></span>
                    </label>
                    <span class="smtp-toggle-label">OFF</span>
                </span>
                <span class="smtp-provider-edit">&#9998;</span>
            </div>
            <div class="smtp-provider-row smtp-provider-smtp" onclick="showSmtpProPopup()">
                <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/smtp.png" class="smtp-provider-logo" alt="SMTP">
                <span class="smtp-provider-title">Other SMTP</span>
                <span class="smtp-provider-desc">Connect any SMTP service for a flexible setup and reliable email sending.</span>
                <span class="smtp-provider-status">INACTIVE</span>
                <span class="smtp-provider-switch">
                    <label class="smtp-toggle">
                        <input type="checkbox" disabled>
                        <span class="smtp-toggle-slider"></span>
                    </label>
                    <span class="smtp-toggle-label">OFF</span>
                </span>
                <span class="smtp-provider-edit">&#9998;</span>
            </div>
        </div>
        <div class="smtp-blur" id="smtpBlur"></div>
        <div class="smtp-popup smtp-popup-gradient" id="smtpPopup">
            <span class="smtp-popup-close" onclick="closeSmtpProPopup()">&times;</span>
            <div class="smtp-popup-logo-container">
                <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/logo.webp" alt="SMTP Pro">
            </div>
            <h2 class="smtp-popup-heading">SMTP & Email Logs for WooCommerce</h2>
            <p class="smtp-popup-tagline">Unlock professional email delivery and never miss a customer order again!</p>
            <div class="smtp-popup-grid">
                <div class="smtp-popup-card">
                    <div class="smtp-popup-card-icon">
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/office365.png">
                    </div>
                    <span class="smtp-popup-card-text">Microsoft SMTP</span>
                </div>
                <div class="smtp-popup-card">
                    <div class="smtp-popup-card-icon">
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/gmail.png">
                    </div>
                    <span class="smtp-popup-card-text">Gmail SMTP</span>
                </div>
                <div class="smtp-popup-card">
                    <div class="smtp-popup-card-icon">
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/brevo.png">
                    </div>
                    <span class="smtp-popup-card-text">Brevo SMTP</span>
                </div>
                <div class="smtp-popup-card">
                    <div class="smtp-popup-card-icon">
                        <img src="<?php echo plugin_dir_url(__FILE__); ?>assets/images/smtp.png">
                    </div>
                    <span class="smtp-popup-card-text">Other SMTP</span>
                </div>
            </div>
            <a href="https://woocommerce.com/products/smtp-and-email-logs/?utm_source=plugin&utm_medium=wc_hide_shipping_methods&utm_campaign=settings_tab" target="_blank" class="smtp-popup-link smtp-popup-btn">Get Started Now</a>
        </div>
        <script>
        function showSmtpProPopup() {
            document.getElementById('smtpBlur').style.display = 'block';
            document.getElementById('smtpPopup').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }
        function closeSmtpProPopup() {
            document.getElementById('smtpBlur').style.display = 'none';
            document.getElementById('smtpPopup').style.display = 'none';
            document.body.style.overflow = '';
        }
        document.getElementById('smtpBlur').addEventListener('click', closeSmtpProPopup);
        </script>
        <?php
    }

    /**
     * Enqueue admin scripts and add JavaScript alert
     */
    public function enqueue_admin_scripts( $hook ) {
        // Only load on WooCommerce settings pages
        if ( 'woocommerce_page_wc-settings' === $hook ) {
            ?>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    // Find the SMTP tab by its text content
                    var tabs = document.querySelectorAll('.woo-nav-tab-wrapper .nav-tab');
                    tabs.forEach(function(tab) {
                        if (tab.textContent.trim() === 'SMTP') {
                        // Create the badge element
                        var badge = document.createElement('span');
                        badge.textContent = 'Premium';
                        badge.style.background = '#873eff';
                        badge.style.color = '#fff';
                        badge.style.fontSize = '11px';
                        badge.style.padding = '2px 6px';
                        badge.style.marginLeft = '6px';
                        badge.style.borderRadius = '8px';
                        badge.style.verticalAlign = 'middle';
                        badge.style.fontWeight = 'bold';
                        // Add the badge after the tab
                        tab.appendChild(badge);
                        }
                    });
                });
            </script>
            <?php
        }
    }
}

// Initialize the plugin.
new WC_Hide_Shipping_Methods();
