<?php
/**
 * Plugin Name: Wholesale For WooCommerce
 * Plugin URI: https://wpexperts.io/
 * Description: Wholesale for WooCommerce Lite gives you an ability to display wholesale price on all products of your WooCommerce store. Add wholesale pricing on your existing products and display how much your customers are savings.<a href="https://woocommerce.com/products/wholesale-for-woocommerce/?aff=2878" target="_blank"> UPGRADE TO WHOLESALE FOR WOOCOMMERCE PRO </a>to get premium features - Assign and manage wholesale user roles, control product and price visibility and more. 
 * Version: 2.0
 * Author: WPExperts
 * Author URI: https://wpexperts.io/
 * Developer: WPExperts
 * Developer URI: https://wpexperts.io/
 * Text Domain: woocommerce-wholesale-pricing
 * 
 * WC requires at least: 5.0
 * WC tested up to: 9.8.1
 * Tested up to: 6.7
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */
if (!defined('ABSPATH')) {
	exit();
}

if ( file_exists( WP_PLUGIN_DIR . '/woo-wholesale-pricing/wholesale-for-woocommerce-free.php' ) ) {
	deactivate_plugins( '/woo-wholesale-pricing/wholesale-for-woocommerce-free.php' );
}

if ( file_exists( WP_PLUGIN_DIR . '/wc-hide-shipping-methods/hide-shipping-free-shipping.php' ) ) {
	deactivate_plugins( '/wc-hide-shipping-methods/hide-shipping-free-shipping.php' );
}

if (!defined('WWP_PLUGIN_URL')) {
	define('WWP_PLUGIN_URL', plugin_dir_url(__FILE__));
}
if (!defined('WWP_PLUGIN_PATH')) {
	define('WWP_PLUGIN_PATH', plugin_dir_path(__FILE__));
}
if (!defined('WWP_PLUGIN_DIRECTORY_NAME')) {
	define('WWP_PLUGIN_DIRECTORY_NAME', __DIR__);
}

if (!defined('WWPL_VERSION')) {
	define('WWPL_VERSION', '2.0');
}


if (!class_exists('Wwp_Wholesale_Pricing')) {

	class Wwp_Wholesale_Pricing {

		public function __construct() {
			self::freemius_integration();
			add_action( 'before_woocommerce_init', array( $this, 'woo_hpos_incompatibility' ) );
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'wwwp_lite_add_action_links' ) );
			
			/**
			 * Hooks
			 *
			 * @since 1.0.0
			 */
			if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')), true)) {
				self::init();
			} else {
				add_action('admin_notices', array( __CLASS__, 'wholesale_admin_notice_error' ));
			}
		}


		public function wwwp_lite_add_action_links( $actions ) {
			$deactivate = $actions['deactivate'];
			unset( $actions['deactivate'] );

			$wwp_links = array(
				'<a href="https://wpexperts.io/products/wholesale-for-woocommerce-pro/?utm_source=plugin_page&utm_medium=wholesale-for-woo"> <b>' . esc_html__( 'Get Premium', 'woocommerce-wholesale-pricing' ) . '</b></a>',
				'<a href="' . admin_url( 'admin.php?page=wwp_wholesale' ) . '">Settings</a>',
				$deactivate,
			);
			$actions   = array_merge( $actions, $wwp_links );
			return $actions;
		}

		public function woo_hpos_incompatibility() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
			}
		}

		public static function init() {
			if (function_exists('load_plugin_textdomain')) {
				load_plugin_textdomain('woocommerce-wholesale-pricing', false, dirname(plugin_basename(__FILE__)) . '/languages/');
			}
			include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-general-functions.php';
			require_once WWP_PLUGIN_PATH . '/inc/class-wwp-wholesale-requests.php';
			include_once WWP_PLUGIN_PATH . 'inc/api/class-wwpp-api.php';
			require_once WWP_PLUGIN_PATH . '/inc/class-wwp-wholesale-user-roles.php';
			if (is_admin()) {
				include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-backend.php';
				
			} else {
				include_once WWP_PLUGIN_PATH . '/inc/class-wwp-wholesale-common.php';
				include_once WWP_PLUGIN_PATH . '/inc/class-wwp-wholesale-frontend.php';
				add_action('init', array( __CLASS__, 'include_wholesale_functionality' ));
			}

			include_once WWP_PLUGIN_PATH . 'inc/compatibilty/hide-shipping-free-shipping.php';
		}

		public static function include_wholesale_functionality() {
			if (is_user_logged_in()) {
				$user_info = get_userdata(get_current_user_id());
				$user_role = (array) $user_info->roles;
				if ( !empty($user_role) && in_array('wwp_wholesaler', $user_role, true) ) {
					include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale.php';
					include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale-functions.php';
				}               
			}
			include_once WWP_PLUGIN_PATH . '/inc/class-wwp-wholesale-registration.php';
		}

		public static function wholesale_admin_notice_error() {
			$class   = 'notice notice-error';
			$message = esc_html__('The plugin Wholesale For WooCommerce requires Woocommerce to be installed and activated, in order to work', 'woocommerce-wholesale-pricing');
			printf('<div class="%1$s"><p>%2$s</p></div>', esc_html($class), esc_html($message)); 
		}

		public static function freemius_integration() {
			if ( ! function_exists( 'wwl_fs' ) ) {
				// Create a helper function for easy SDK access.
				function wwl_fs() {
					global $wwl_fs;
			
					if ( ! isset( $wwl_fs ) ) {
						// Include Freemius SDK.
						require_once __DIR__ . '/freemius/start.php';
			
						$wwl_fs = fs_dynamic_init( array(
							'id'                  => '8990',
							'slug'                => 'wc-wholesale-lite',
							'type'                => 'plugin',
							'public_key'          => 'pk_466c8d5547c4b56a548fb5ac668a5',
							'is_premium'          => false,
							'has_addons'          => false,
							'has_paid_plans'      => false,
							'menu'                => array(
								'slug'           => 'wwp_wholesale',
								'first-path'     => 'admin.php?page=wwp_wholesale',
								'account'        => false,
								'contact'        => false,
								'support'        => false,
							),
						) );
					}
			
					return $wwl_fs;
				}
			
				// Init Freemius.
				wwl_fs();
				/**
				 * Signal that SDK was initiated.
				 * 
				 * @since 1.0.0
				 */
				do_action( 'wwl_fs_loaded' );
			}
		}
	}   
	new Wwp_Wholesale_Pricing();
}
