<?php
/**
 * Plugin Name: WC Hide Shipping Methods
 * Plugin URI: https://wordpress.org/plugins/wc-hide-shipping-methods/
 * Description: Hides other shipping methods when "Free shipping" is available.
 * Author: WPExperts
 * Author URI: https://wpexperts.io
 * Version: 2.0.4
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
}

// Initialize the plugin.
new WC_Hide_Shipping_Methods();
