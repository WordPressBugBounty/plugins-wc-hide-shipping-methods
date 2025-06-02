<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WWP_REST_Wholesale_Settings_V1_Controller')) {

	class WWP_REST_Wholesale_Settings_V1_Controller extends WC_REST_Controller {
	
		protected $namespace = 'wholesale/v1';
		protected $rest_base = 'general-discount';
		protected $wc_wholesale_prices;

		public function __construct() {
			global $wc_wholesale_prices;
			$this->wc_wholesale_prices = $wc_wholesale_prices;

			add_action('rest_api_init', array( $this, 'register_routes' ));
			add_filter('wwp_rest_response_product_object', array( $this, 'filter_product_object' ), 10, 3);
		}

		public function filter_product_object( $response, $obj, $request ) {
			if (isset($request['fields']) && !empty($request['fields'])) {
				$data    = $response->get_data();
				$newdata = array();
				foreach (explode(',', $request['fields']) as $field) {
					$newdata[ $field ] = $data[ $field ];
				}
				$response->set_data($newdata);
			}
			return $response;
		}

		public function register_routes() {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_items' ),
						'permission_callback' => array( $this, 'permissions_check' ),
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'create_item' ),
						'permission_callback' => array( $this, 'permissions_check' ),
					),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base ,
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_item' ),
						'permission_callback' => array( $this, 'permissions_check' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_item' ),
						'permission_callback' => array( $this, 'permissions_check' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_item' ),
						'permission_callback' => array( $this, 'permissions_check' ),
					),
				)
			);
		}

		public function permissions_check( $request ) {
			if (current_user_can('manage_options')) {
				return true;
			}
			return new WP_Error('wholesale_rest_role_permission_failed', __('You don\'t have permission.', 'woocommerce-wholesale-pricing'), array( 'status' => rest_authorization_required_code() ));
		}

		public function get_items( $request ) {
			$wholesale_settings = array();
		
			$wwp_enable_wholesale_item = get_option('_wwp_enable_wholesale_item', 'no');
		
			if ('yes' === $wwp_enable_wholesale_item) {
				$wholesale_settings = array(
					'wholesale_amount' => get_option('_wwp_wholesale_amount', array()),
					'wholesale_type' => get_option('_wwp_wholesale_type', array()),
					'wholesale_min_quantity' => get_option('_wwp_wholesale_min_quantity', array()),
				);
			} else {
				return new WP_Error( 'wholesale_rest_empty_global_discount', sprintf( __( 'No General discount applied.', 'woocommerce-wholesale-pricing' ) ), array( 'status' => 400 ) );
			}
		
			/**
			 * Hooks
			 *
			 * @since 1.7.0
			 */
			$wholesale_settings = apply_filters('wwp_api_fetch_wholesale_settings_filter', $wholesale_settings, $request);
		
			return new WP_REST_Response($wholesale_settings, 200);
		}
		
		

		public function get_item( $request ) {
		 
			/**
			 * Hooks
			 *
			 * @since 1.7.0
			 */     
			$wholesale_settings = apply_filters('wwp_api_fetch_wholesale_settings_filter', array(), $request);
			return new WP_REST_Response($wholesale_settings, 200);
		}
		
		public function create_item( $request ) {
		
			$response = array(
				'message' => 'discount Can not be created',
			);
			return new WP_REST_Response($response, $response_status);
		}
		
		public function update_item( $request ) {
			$params = $request->get_params();
		
			$update_options = array(
				'_wwp_enable_wholesale_item' => 'enable',
				'_wwp_wholesale_amount' => 'wholesale_amount',
				'_wwp_wholesale_type' => 'wholesale_type',
				'_wwp_wholesale_min_quantity' => 'wholesale_min_quantity',
			);

			if ( isset( $params['enable'] ) && ! in_array( $params['enable'], array( 'yes', 'no' ), true ) ) {
				return new WP_Error( 'wholesale_rest_global_discount_invalid_value', sprintf( __( 'Wholesale Enable Value must be yes or no', 'woocommerce-wholesale-pricing' ) ), array( 'status' => 400 ) );
			}

			if ( isset( $params['wholesale_type'] ) && ! in_array( $params['wholesale_type'], array( 'fixed', 'percent' ), true ) ) {
			return new WP_Error( 'wholesale_rest_global_discount_invalid_wholesale_type', sprintf( __( 'Wholesale Discount Type must be fixed or percent', 'woocommerce-wholesale-pricing' ) ), array( 'status' => 400 ) );
			}

			if ( isset( $params['wholesale_min_quantity'] ) && ! is_numeric( $params['wholesale_min_quantity'] ) ) {
				return new WP_Error( 'wholesale_rest_global_discount_invalid_min_qty', sprintf( __( 'Invalid Min Quantity Value', 'woocommerce-wholesale-pricing' ) ), array( 'status' => 400 ) );
			}

			if ( isset( $params['wholesale_min_quantity'] ) && !empty( $params['wholesale_min_quantity'] ) ) {
				$params['wholesale_min_quantity'] = ( int ) $params['wholesale_min_quantity'];
			}
		
			foreach ($update_options as $option_key => $param_key) {
				if (isset($params[ $param_key ])) {
					update_option($option_key, $params[ $param_key ]);
				}
			}
		
			$response_message = __('Successfully updated discount.', 'woocommerce-wholesale-pricing');
			$response_status  = 200;
		
			$response = array(
				'message' => $response_message,
			);
		
			return new WP_REST_Response($response, $response_status);
		}

		public function delete_item( $request ) {

			$response = array(
				'message' => 'discount Can not be Deleted',
			);
			return new WP_REST_Response($response, $response_status);
		}
	}
}
