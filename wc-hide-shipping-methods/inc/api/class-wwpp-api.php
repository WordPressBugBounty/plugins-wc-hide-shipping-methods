<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WWP_REST_API' ) ) {

	/**
	 * Model that houses the logic of WWPP API.
	 *
	 * @since 1.12
	 */
	class WWP_REST_API {

		/**
		 * Class Properties.
		 */

		/**
		 * Property that holds the single main instance of WWP_REST_API.
		 *
		 * @since 1.12.0
		 *
		 * @var WWP_REST_API
		 */
		private static $instance;

		/**
		 * Property that holds WWP API Products Controllers.
		 *
		 * @since 1.12.0
		 * 
		 * @var WWP_REST_API_Wholesale_Products_Controller
		 */
		public $wwp_rest_api_wholesale_products_controller;

		/**
		 * Property that holds WWP API Variations Controllers.
		 *
		 * @since 1.12.0
		 * 
		 * @var WWP_REST_API_Wholesale_Variations_Controller
		 */
		public $wwp_rest_api_wholesale_variations_controller;

		/**
		 * Property that holds WWP API Wholesale Roles Controllers.
		 *
		 * @since 1.12.0
		 * 
		 * @var WWP_REST_API_Wholesale_Roles_Controller
		 */
		public $wwp_rest_api_wholesale_roles_controller;

		
		/**
		 * WWP_REST_Wholesale_Settings_V1_Controller
		 *
		 * @var mixed
		 */
		public $WWP_REST_Wholesale_Settings_V1_Controller;
		/**
		 * Class Methods.
		 */

		/**
		 * WWP_REST_API constructor.
		 *
		 * @since 1.12.0
		 *
		 */
		public function __construct() {
			add_action( 'rest_api_init', array( $this, 'load_api_wwp_controllers' ), 5 );
			add_action( 'woocommerce_rest_is_request_to_rest_api', array( $this, 'authenticate_user' ) );
		}

		/**
		 * Load WWP API.
		 *
		 * @since 1.16.0
		 * 
		 */
		public function load_wwp_api() {
			// API controllers using wholesale/v1 namespace.
			add_action( 'rest_api_init', array( $this, 'load_api_wwp_controllers' ), 5 );

			// Authenticate users if api keys are provided.
			add_action( 'woocommerce_rest_is_request_to_rest_api', array( $this, 'authenticate_user' ) );
		}

		/**
		 * Ensure that only one instance of WWP_REST_API is loaded or can be loaded (Singleton Pattern).
		 *
		 * @since 1.12.0
		 *
		 * @param array $dependencies Array of instance objects of all dependencies of WWP_REST_API model.
		 * @return WWP_REST_API
		 */
		public static function instance( $dependencies = array() ) {
			if ( ! self::$instance instanceof self ) {
				self::$instance = new self( $dependencies );
			}

			return self::$instance;
		}

		/**
		 * WWPP API controllers under wwpp/v1 namespace.
		 *
		 * @since 1.12
		 * 
		 */
		public function load_api_wwp_controllers() {
			include_once WWP_PLUGIN_PATH . 'inc/class-wwp-wholesale.php';
			require_once WWP_PLUGIN_PATH . 'inc/api/v1/class-wwp-rest-api-wholesale-products-v1-controller.php';
			require_once WWP_PLUGIN_PATH . 'inc/api/v1/class-wwp-rest-api-wholesale-products-variations-v1-controller.php';
			require_once WWP_PLUGIN_PATH . 'inc/api/v1/class-wwp-rest-api-wholesale-settings-v1-controller.php';

			$this->wwp_rest_api_wholesale_products_controller   = new WWP_REST_Wholesale_Products_V1_Controller();
			$this->wwp_rest_api_wholesale_variations_controller = new WWP_REST_Wholesale_Product_Variations_V1_Controller();
			$this->WWP_REST_Wholesale_Settings_V1_Controller    = new WWP_REST_Wholesale_Settings_V1_Controller();
		}

		/**
		 * Authenticate if user if using WWPP rest base if api keys are provided.
		 *
		 * @since 1.12
		 *
		 * @param bool $rest_request Whether the request is a REST API request.
		 * @return boolean
		 */
		public function authenticate_user( $rest_request ) {
			$rest_prefix = trailingslashit( rest_get_url_prefix() );
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

			// Authenticate if the request is using the wholesale/v1 endpoint.
			if ( false !== strpos( $request_uri, $rest_prefix . 'wholesale/' ) ) {
				return true;
			}

			return $rest_request;
		}
	}

	// Instantiate the class to initialize the plugin.
	$wwp_rest_api_instance = new WWP_REST_API();

}
