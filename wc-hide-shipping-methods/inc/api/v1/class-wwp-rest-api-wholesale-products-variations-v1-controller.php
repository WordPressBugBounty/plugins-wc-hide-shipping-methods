<?php
// Exit if accessed directly.
if (!defined('ABSPATH')) {
	exit;
}

if (!class_exists('WWP_REST_Wholesale_Product_Variations_V1_Controller')) {

	class WWP_REST_Wholesale_Product_Variations_V1_Controller extends WC_REST_Product_Variations_Controller {

		protected $namespace = 'wholesale/v1';
		protected $rest_base = 'products/(?P<product_id>[\d]+)/variations';
		protected $post_type = 'product_variation';
		protected $wwp_rest_wholesale_products_v1_controller;
		protected $registered_wholesale_roles;

		public function __construct() {
			  
			$this->wwp_rest_wholesale_products_v1_controller = new WWP_REST_Wholesale_Products_V1_Controller();
			$this->registered_wholesale_roles                = $this->wwp_rest_wholesale_products_v1_controller->get_wholesale_role();
			// Inherit routes from 'wc/v3' namespace.
			$this->register_routes();
			// Include wholesale data into the response.
			add_filter("woocommerce_rest_prepare_{$this->post_type}_object", array( $this->wwp_rest_wholesale_products_v1_controller, 'add_wholesale_data_on_response' ), 10, 3);

			// Fires after a single object is created or updated via the REST API.
			add_action("woocommerce_rest_insert_{$this->post_type}_object", array( $this->wwp_rest_wholesale_products_v1_controller, 'create_update_wholesale_product' ), 10, 3);

			// Insert '_have_wholesale_price' and '_variations_with_wholesale_price' meta after inserting variation.
			add_action('wwp_after_variation_create_item', array( $this, 'update_variable_wholesale_price_meta_flag' ), 10, 2);

			// After Deleting Variation delete parent meta _variations_with_wholesale_price.
			add_action('wwp_after_variation_delete_item', array( $this, 'update_variable_wholesale_price_meta_flag' ), 10, 2);

			// Filter the result returned.
			add_filter('wwp_rest_response_product_object', array( $this, 'filter_product_object' ), 10, 3);
		}

		public static function is_wholesale_endpoint( $request ) {
			/**
			* Hooks
			*
			* @since 2.4.0
			*/
			$wwp_is_wholesale_endpoint = apply_filters('wwp_is_wholesale_endpoint', is_a($request, 'WP_REST_Request') && strpos($request->get_route(), 'wholesale/v1') !== false ? true : false, $request);
			return $wwp_is_wholesale_endpoint;
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


		public function update_variable_wholesale_price_meta_flag( $request, $response ) {

			if ( is_a( $response, 'WP_Error' ) ) {
				return $response;
			}

			global $wc_wholesale_prices;
			$method = $request->get_method();
			if (isset($request['product_id'])) {
				$product_parent_id = intval($request['product_id']);
				$variation_id      = $response->data['id'];
				// Variation is Deleted.
				if ('DELETE' === $method) {
					$product    = wc_get_product($product_parent_id);
					$variations = $product->get_available_variations();
					// If all variations are removed then set stock status to outofstock.
					if (empty($variations)) {
						update_post_meta($product_parent_id, '_stock_status', 'outofstock');
					}
				}
			}
		}

		public function get_items( $request ) {
			/**
			* Hooks
			*
			* @since 2.4.0
			*/      
			$extra_checks = apply_filters(
				"wwp_before_get_items_{$this->post_type}_extra_check",
				array(
					'is_valid' => true,
					'message'  => '',
				),
				$request
			);

			// Extra check for wholesale visibility.
			if (isset($extra_checks['is_valid']) && !$extra_checks['is_valid']) {
				return $extra_checks['message'];
			}
			/**
			* Hooks
			*
			* @since 2.4.0
			*/
			do_action('wwp_before_variation_get_items', $request);

			$product_id = (int) ( isset($request['product_id']) ? $request['product_id'] : '' );

			$product = wc_get_product( $product_id );

			if ( ! $product instanceof WC_Product_Variable ) {
				return new WP_Error( 'wholesale_rest_invalid_product', sprintf( __( 'Invalid Product ID', 'woocommerce-wholesale-pricing' ) ), array( 'status' => 400 ) );
			}

			$response = parent::get_items($request);
			/**
			* Hooks
			*
			* @since 2.4.0
			*/
			do_action('wwp_after_variation_get_items', $request, $response);

			return $response;
		}

		public function get_item( $request ) {
			/**
			* Hooks
			*
			* @since 2.4.0
			*/      
			$extra_checks = apply_filters(
				"wwp_before_get_item_{$this->post_type}_extra_check",
				array(
					'is_valid' => true,
					'message'  => '',
				),
				$request
			);

			// Extra check for wholesale visibility.
			if (isset($extra_checks['is_valid']) && !$extra_checks['is_valid']) {
				return $extra_checks['message'];
			}
			/**
			* Hooks
			*
			* @since 2.4.0
			*/          
			do_action('wwp_before_variation_get_item', $request);

			$product_id = (int) ( isset($request['product_id']) ? $request['product_id'] : '' );
			
			$product = wc_get_product( $product_id );

			if ( ! $product instanceof WC_Product_Variable ) {
				return new WP_Error( 'wholesale_rest_invalid_product', sprintf( __( 'Invalid Product ID', 'woocommerce-wholesale-pricing' ) ), array( 'status' => 400 ) );
			}

			$response = parent::get_item($request);
			/**
			* Hooks
			*
			* @since 2.4.0
			*/
			do_action('wwp_after_variation_get_item', $request, $response);

			return $response;
		}

		public function create_item( $request ) { 
			/**
			* Hooks
			*
			* @since 2.4.0
			*/      
			do_action('wwp_before_variation_create_item', $request);

			if (isset($request['wholesale_price'])) {

				// Check if wholesale price is set. Make wholesale price as the basis to create wholesale product.
				$error = $this->wwp_rest_wholesale_products_v1_controller->validate_wholesale_price($request, 'variation');

				if (is_a($error, 'WP_Error')) {
					return $error;
				}
			}

			$response = parent::create_item($request);
			/**
			* Hooks
			*
			* @since 2.4.0
			*/          
			do_action('wwp_after_variation_create_item', $request, $response);

			return $response;
		}

		public function update_item( $request ) {
			/**
			* Hooks
			*
			* @since 2.4.0
			*/      
			do_action('wwp_before_variation_update_item', $request);

			if (isset($request['wholesale_price'])) {

				// Check if wholesale price is set. Make wholesale price as the basis to create wholesale product.
				$error = $this->wwp_rest_wholesale_products_v1_controller->validate_wholesale_price($request, 'variation');

				if (is_a($error, 'WP_Error')) {
					return $error;
				}
			}

			$response = parent::update_item($request);
			/**
			* Hooks
			*
			* @since 2.4.0
			*/
			do_action('wwp_after_variation_update_item', $request, $response);

			return $response;
		}

		public function delete_item( $request ) {
			/**
			* Hooks
			*
			* @since 2.4.0
			*/      
			do_action('wwp_before_variation_delete_item', $request);
			$_REQUEST['request'] = $request;
			// Force Delete Variation.
			$request->set_param('force', true);

			$response = parent::delete_item($request);
			/**
			* Hooks
			*
			* @since 2.4.0
			*/
			do_action('wwp_after_variation_delete_item', $request, $response);

			return $response;
		}

		public function batch_items( $request ) {
			$items       = array_filter($request->get_params());
			$params      = $request->get_url_params();
			$query       = $request->get_query_params();
			$product_id  = $params['product_id'];
			$body_params = array();
			
			foreach (array( 'update', 'create', 'delete' ) as $batch_type) {
				if (!empty($items[ $batch_type ])) {
					$injected_items = array();
					foreach ($items[ $batch_type ] as $item) {
						$injected_items[] = is_array($item) ? array_merge(
							array(
								'product_id' => $product_id,
							),
							$item
						) : $item;
					}
					$body_params[ $batch_type ] = $injected_items;
				}
			}

			$route   = $request->get_route();
			$request = new WP_REST_Request($request->get_method());
			$request->set_body_params($body_params);
			$request->set_query_params($query);

			// Set the route. This is needed for create and update to get the correct $base in get_item_schema.
			$request->set_route($route);

			$responses = parent::batch_items($request);

			$codes  = wp_list_pluck($responses, 'code');
			$status = $codes && !in_array('rest_no_route', $codes, true) ? 200 : 400;

			return new WP_REST_Response($responses, $status);
		}
	}
}
