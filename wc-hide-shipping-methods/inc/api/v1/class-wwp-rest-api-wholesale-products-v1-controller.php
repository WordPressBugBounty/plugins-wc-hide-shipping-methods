<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WWP_REST_Wholesale_Products_V1_Controller extends WC_REST_Products_Controller {

	protected $namespace                  = 'wholesale/v1';
	protected $rest_base                  = 'products';
	protected $post_type                  = 'product';
	protected $registered_wholesale_roles = array();

	public function __construct() {
		 
		$this->registered_wholesale_roles = $this->get_wholesale_role();
		
		// Inherit routes from 'wc/v3' namespace.
		$this->register_routes();
	   
		//Filter the query arguments of the request.
		//add_filter( "woocommerce_rest_{$this->post_type}_object_query", array( $this, 'query_args' ), 10, 2 );

		// Include wholesale data into the response.
		add_filter( "woocommerce_rest_prepare_{$this->post_type}_object", array( $this, 'add_wholesale_data_on_response' ), 10, 3 );

		// Fires after a single object is created or updated via the REST API.
		add_action( "woocommerce_rest_insert_{$this->post_type}_object", array( $this, 'create_update_wholesale_product' ), 10, 3 );

		// Filter the result returned.
		add_filter( 'wwp_rest_response_product_object', array( $this, 'filter_product_object' ), 10, 3 );
	}

	public function get_wholesale_role() {
		return array(
			array( 'roleName' => 'wwp_wholesaler' ),
		);
	}

	public function filter_product_object( $response, $post, $request ) {

		if ( isset( $request['fields'] ) && ! empty( $request['fields'] ) ) {
			$data    = $response->get_data();
			$newdata = array();
			foreach ( explode( ',', $request['fields'] ) as $field ) {
				$newdata[ $field ] = $data[ $field ];
			}
			$response->set_data( $newdata );
		}

		return $response;
	}

	
	public function add_wholesale_data_on_response( $response, $product, $request ) {
		/**
		* Hooks
		*
		* @since 2.4.0
		*/     
		do_action( 'wwp_before_adding_wholesale_data_on_response', $response, $product, $request );

		// Check if not wholesale endpoint.
		if ( ! $this->is_wholesale_endpoint( $request ) ) {
			return $response;
		}
		
		// Add wholesale data. Add also WWPP meta data.
		$response->data['wholesale_data'] = $this->get_wwp_meta_data( $product, $request );
		// Remove links in response.
		$links = $response->get_links();
		if ( ! empty( $links ) ) {
			foreach ( $links as $key => $link ) {
				$response->remove_link( $key );
			}
		}
		/**
		* Hooks
		*
		* @since 2.4.0
		*/      
		return apply_filters( "wwp_rest_response_{$this->post_type}_object", $response, $product, $request );
	}

	/**
	 * Custom method to get wholesale meta data.
	 *
	 * @param WC_Product      $product Product object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @since 1.12
	 * 
	 * @return array
	 */
	public function get_wwp_meta_data( $product, $request ) {
	  
		$product_id = $product->get_id();
		
		$meta_data = array(
			'wholesale_price' => array(),
		);
		 
 
			$_REQUEST['wholesale_request']['role'] = 'wwp_wholesaler';
	 
			
			$settings = get_option( 'wwp_wholesale_pricing_options', true );
		   
			$Wholesale_Price_class = new WWP_Easy_Wholesale();
			
			$r_price = get_post_meta( $product_id, '_regular_price', true );
			
		if ( empty( $r_price ) || 0 === $r_price ) {
			$r_price = 1;
		}

		if ( 'simple' === $product->get_type() ) {
			$Wprice = $Wholesale_Price_class->get_wholesale_price( $product_id );
		} else {
			$Wprice = $Wholesale_Price_class->get_variable_wholesale_price ( $product_id, wp_get_post_parent_id($product_id)   );
		}
			
		if ( ! empty( $Wprice ) && $r_price !== $Wprice) {
			 
			$actual = ( isset( $settings['retailer_label'] ) && ! empty( $settings['retailer_label'] ) ) ? esc_html( $settings['retailer_label'] ) : esc_html__( 'Actual', 'woocommerce-wholesale-pricing' );
			$save   = ( isset( $settings['save_label'] ) && ! empty( $settings['save_label'] ) ) ? esc_html( $settings['save_label'] ) : esc_html__( 'Save', 'woocommerce-wholesale-pricing' );
			$new    = ( isset( $settings['wholesaler_label'] ) && ! empty( $settings['wholesaler_label'] ) ) ? esc_html( $settings['wholesaler_label'] ) : esc_html__( 'New', 'woocommerce-wholesale-pricing' );
			$html   = '';
			 
			$saving_amount  = ( ( float ) $r_price - ( float ) $Wprice );
			$saving_amount  = number_format( (float) $saving_amount, 2, '.', '' );
			$saving_percent = ( ( float ) $r_price - ( float ) $Wprice ) / ( float ) $r_price * 100;
			$saving_percent = number_format( (float) $saving_percent, 2, '.', '' );
			 
			$html .= '<div class="wwp-wholesale-pricing-details">';
			if ( 'yes' !== $settings['retailer_disabled'] ) {
				$html .= '<p><span class="retailer-text">' . esc_html__( $actual, 'woocommerce-wholesale-pricing' ) . '</span>: <s>' . wc_price( $r_price ) . '  </s></p>';
			}
				$html .= '<p><span class="price-text">' . esc_html__( $new, 'woocommerce-wholesale-pricing' ) . '</span>: ' . wc_price( $Wprice ) . '  </p>';
			if ( 'yes' !== $settings['save_price_disabled'] ) {
				$html .= '<p><b><span class="save-price-text">' . esc_html__( $save, 'woocommerce-wholesale-pricing' ) . '</span>: ' . wc_price( $saving_amount ) . ' (' . round( $saving_percent ) . '%) </b></p>';
			}
				$html .= '</div>';

				$meta_data['wholesale_price'] =   number_format( (float) $Wprice, 2, '.', '' ); 
				
				$meta_data['price_html'] = $html ;
		}
		
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		return apply_filters( 'wwp_meta_data', array_filter( $meta_data ), $product, $request );
	}

	public static function is_wholesale_endpoint( $request ) {
		/**
		* Hooks
		*
		* @since 2.4.0
		*/     
		$wwp_is_wholesale_endpoint = apply_filters( 'wwp_is_wholesale_endpoint', is_a( $request, 'WP_REST_Request' ) && strpos( $request->get_route(), 'wholesale/v1' ) !== false ? true : false, $request );
		 
		
		return $wwp_is_wholesale_endpoint; 
	}

	public function create_update_wholesale_product( $product, $request, $create_product ) {  
		/**
		* Hooks
		*
		* @since 2.4.0
		*/     
		do_action( 'wwp_before_create_update_wholesale_product', $product, $request, $create_product );

		// Import variables into the current symbol table from an array.
		extract( $request->get_params() ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		// Get product type.
		$product_type = $product->get_type();

		// The product id.
		$product_id = $product->get_id();
		
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		$product_types = apply_filters( 'wwp_create_update_product_types', array( 'simple', 'variation', 'bundle', 'composite' ) );
		
		$data = array();
		// Check if wholesale price is set.
		if ( isset( $wholesale_price ) && in_array( $product_type, $product_types, true ) ) {
			
			// Multiple wholesale price is set.
			if ( is_array( $wholesale_price ) ) {
				if ('variation' === $product_type) {
			
					$variation_id           = $product->get_id();
					$product_parent_id      = $product->get_parent_id();
					$wholesale_amount       = $wholesale_price['wholesale_price'];
					$wholesale_min_quantity = $wholesale_price['min_qty'];
					update_post_meta($variation_id, '_wwp_wholesale_amount', esc_attr($wholesale_amount));
					update_post_meta($variation_id, '_wwp_wholesale_min_quantity', esc_attr($wholesale_min_quantity));
				} else {
					
					$woo_wholesale_amount       = isset($wholesale_price['wholesale_price']) ? $wholesale_price['wholesale_price'] : '';
					$woo_wholesale_type         = isset($wholesale_price['discount_type']) ? $wholesale_price['discount_type'] : 'fixed';
					$wwp_wholesale_min_quantity = isset($wholesale_price['min_qty']) ? ( int ) $wholesale_price['min_qty'] : '';
					$woo_wholesale_enable       = isset($wholesale_price['enable']) ? $wholesale_price['enable'] : '';
					if ( !empty($woo_wholesale_enable) ) {
						update_post_meta($product_id, '_wwp_enable_wholesale_item', esc_attr($woo_wholesale_enable));
					}
					
					if ( !empty($woo_wholesale_type) ) {
						update_post_meta($product_id, '_wwp_wholesale_type', esc_attr($woo_wholesale_type));
					}
					 
					if ( !empty($woo_wholesale_amount) ) {
						update_post_meta($product_id, '_wwp_wholesale_amount', esc_attr($woo_wholesale_amount));
					}
					 
					if ( !empty($wwp_wholesale_min_quantity) ) {
						update_post_meta($product_id, '_wwp_wholesale_min_quantity', esc_attr($wwp_wholesale_min_quantity));
					}
				}
			}
		}

		if ( 'variable' === $product_type ) {
			$woo_wholesale_type   = isset($wholesale_price['discount_type']) ? $wholesale_price['discount_type'] : 'fixed';
			$woo_wholesale_enable = isset($wholesale_price['enable']) ? $wholesale_price['enable'] : '';
			if ( !empty($woo_wholesale_enable) ) {
				update_post_meta($product_id, '_wwp_enable_wholesale_item', esc_attr($woo_wholesale_enable));
			}
			
			if ( !empty($woo_wholesale_type) ) {
				update_post_meta($product_id, '_wwp_wholesale_type', esc_attr($woo_wholesale_type));
			}

		}

		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_after_create_update_wholesale_product', $product, $request, $create_product );
	}

	public static function validate_wholesale_price( $request, $type = 'product' ) {

		$method = $request->get_method();
		switch ( $method ) {
			case 'PUT':
				$method = 'update';
				break;
			case 'POST':
				$method = 'create';
				break;
		}
	
		if ( isset( $request['wholesale_price']['min_qty'] ) ) {
			if ( ! is_numeric( $request['wholesale_price']['min_qty'] ) ) {
				return new WP_Error(
					'wholesale_rest_' . $type . '_cannot_' . $method . '_invalid_min_qty',
					sprintf( __( 'Invalid Min Quantity Value.', 'woocommerce-wholesale-pricing' ), $method ),
					array(
						'status'                  => 400,
					)
				);
			}
		}

		if ( isset( $request['wholesale_price']['discount_type'] ) && ! in_array( $request['wholesale_price']['discount_type'], array( 'fixed', 'percent' ), true ) ) {
			return new WP_Error( 'wholesale_rest_' . $request['type'] . '_product_invalid_discount_type', sprintf( __( 'Wholesale Discount Type must be fixed or percent', 'woocommerce-wholesale-pricing' ) ), array( 'status' => 400 ) );
		}

		if ( isset( $request['wholesale_price'] ) && is_array( $request['wholesale_price'] ) ) {

			$invalid_roles           = array();
			$invalid_wholesale_price = array();

			if ( isset( $request['wholesale_price']['role'] ) && !empty( $request['wholesale_price']['role'] ) ) {
				$role            = $request['wholesale_price']['role'];
				$wholesale_price = $request['wholesale_price']['wholesale_price'];
				$wholesale_role  = term_exists( $role, 'wholesale_user_roles' );
					 
				if ( 0  === $wholesale_role || null  === $wholesale_role ) {
					$invalid_roles[] = $role;
				}

				if ( ! is_numeric( $wholesale_price ) && '' !== $wholesale_price ) {
					$invalid_wholesale_price[ $role ] = $wholesale_price;
				}
			}

			if ( ! empty( $invalid_roles ) || ! empty( $invalid_wholesale_price ) ) {
				return new WP_Error(
					'wholesale_rest_' . $type . '_cannot_' . $method,
					// translators: %s: method name.
					sprintf( __( 'Unable to %s. Invalid wholesale price.', 'woocommerce-wholesale-pricing' ), $method ),
					array(
						'status'                  => 400,
						'invalid_roles'           => $invalid_roles,
						'invalid_wholesale_price' => $invalid_wholesale_price,
					)
				);
			} else {
				return true; // This is a valid parameter.
			}
		}

		// translators: %s: method name.
		return new WP_Error( 'wholesale_rest_' . $type . '_cannot_' . $method, sprintf( __( 'Unable to %s. Invalid wholesale price.', 'woocommerce-wholesale-pricing' ), $method ), array( 'status' => 400 ) );
	}

	public function get_items( $request ) {
		
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_before_product_get_items', $request );

		$wholesale_role =  'wwp_wholesaler';

		if ( empty( $wholesale_role ) && ! isset( $this->registered_wholesale_roles['roleName'] )
		&&  $this->registered_wholesale_roles['roleName'] !== $wholesale_role ) {
			return new WP_Error( 'wholesale_rest_product_cannot_view', __( 'Invalid wholesale role.', 'woocommerce-wholesale-pricing' ), array( 'status' => 400 ) );
		}

		$response = parent::get_items( $request );
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_after_product_get_items', $request, $response );

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

		if ( isset( $extra_checks['is_valid'] ) && ! $extra_checks['is_valid'] ) {
			return $extra_checks['message'];
		}
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_before_product_get_item', $request );

		$response = parent::get_item( $request );
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_after_product_get_item', $request, $response );

		return $response;
	}

	public function create_item( $request ) {
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_before_product_create_item', $request );
		if ( isset( $request['wholesale_price'] ) && 'variable' !== $request['type'] ) {

			if ( isset( $request['wholesale_price']['enable'] ) && ! in_array( $request['wholesale_price']['enable'], array( 'yes', 'no' ), true ) ) {
				return new WP_Error( 
					'wholesale_rest_' . $request['type'] . 'product_invalid_value' . $method, 
					sprintf( __( 'Wholesale Enable Value must be yes or no', 'woocommerce-wholesale-pricing' ) ), 
					array( 'status' => 400 ) 
				);
			}
			

			// Check if wholesale price is set. Make wholesale price as the basis to create wholesale product.
			$error = self::validate_wholesale_price( $request  );

			if ( is_a( $error, 'WP_Error' ) ) {
				return $error;
			}
		}

		$response = parent::create_item( $request );
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_after_product_create_item', $request, $response );

		return $response;
	}

	public function update_item( $request ) {
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_before_product_update_item', $request );

		if ( isset( $request['wholesale_price'] ) ) {

			if ( isset( $request['wholesale_price']['enable'] ) && ! in_array( $request['wholesale_price']['enable'], array( 'yes', 'no' ), true ) ) {
				return new WP_Error( 
					'wholesale_rest_' . $request['type'] . 'product_invalid_value' . $method, 
					sprintf( __( 'Wholesale Enable Value must be yes or no', 'woocommerce-wholesale-pricing' ) ), 
					array( 'status' => 400 ) 
				);
			}

			// Check if wholesale price is set. Make wholesale price as the basis to create wholesale product.
			$error = self::validate_wholesale_price( $request  );

			if ( is_a( $error, 'WP_Error' ) ) {
				return $error;

			}
		}
		
		$response = parent::update_item( $request );
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_after_product_update_item', $request, $response );

		return $response;
	}

	public function delete_item( $request ) {
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_before_product_delete_item', $request );

		$response = parent::delete_item( $request );
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_after_product_delete_item', $request, $response );

		return $response;
	}

	public function batch_items( $request, $controller = null ) {

		if ( is_null( $controller ) ) {
			$controller = $this;
		}
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_before_product_batch_update', $request );

		global $wp_rest_server;

		// Get the request params.
		$items    = array_filter( $request->get_params() );
		$query    = $request->get_query_params();
		$response = array();

		// Check batch limit.
		$limit = $controller->check_batch_limit( $items );
		if ( is_wp_error( $limit ) ) {
			return $limit;
		}

		if ( ! empty( $items['create'] ) ) {
			foreach ( $items['create'] as $item ) {
				$_item = new WP_REST_Request( 'POST' );

				// Set the route. This is needed in order to perform create, update wholesale data.
				$_item->set_route( $request->get_route() );

				// Default parameters.
				$defaults = array();
				$schema   = $controller->get_public_item_schema();
				foreach ( $schema['properties'] as $arg => $options ) {
					if ( isset( $options['default'] ) ) {
						$defaults[ $arg ] = $options['default'];
					}
				}
				$_item->set_default_params( $defaults );

				// Set request parameters.
				$_item->set_body_params( $item );

				// Set query (GET) parameters.
				$_item->set_query_params( $query );

				// Create item.
				$_response = $controller->create_item( $_item );

				if ( is_wp_error( $_response ) ) {
					$response['create'][] = array(
						'id'    => 0,
						'error' => array(
							'code'    => $_response->get_error_code(),
							'message' => $_response->get_error_message(),
							'data'    => $_response->get_error_data(),
						),
					);
				} else {
					$response['create'][] = $wp_rest_server->response_to_data( $_response, '' );
				}
			}
		}

		if ( ! empty( $items['update'] ) ) {
			foreach ( $items['update'] as $item ) {
				$_item = new WP_REST_Request( 'PUT' );

				// Set the route. This is needed in order to perform create, update wholesale data.
				$_item->set_route( $request->get_route() );

				// Set query (GET) parameters.
				$_item->set_query_params( $query );

				// Set request parameters.
				$_item->set_body_params( $item );

				// Update item.
				$_response = $controller->update_item( $_item );

				if ( is_wp_error( $_response ) ) {
					$response['update'][] = array(
						'id'    => $item['id'],
						'error' => array(
							'code'    => $_response->get_error_code(),
							'message' => $_response->get_error_message(),
							'data'    => $_response->get_error_data(),
						),
					);
				} else {
					$response['update'][] = $wp_rest_server->response_to_data( $_response, '' );
				}
			}
		}

		if ( ! empty( $items['delete'] ) ) {
			foreach ( $items['delete'] as $id ) {
				$id = (int) $id;

				if ( 0 === $id ) {
					continue;
				}

				$_item = new WP_REST_Request( 'DELETE' );

				// Set the route. This is needed in order to perform create, update wholesale data.
				$_item->set_route( $request->get_route() );

				// Set query (GET) parameters.
				$_item->set_query_params(
					array_merge(
						array(
							'id'    => $id,
							'force' => true,
						),
						$query
					)
				);

				// Delete item.
				$_response = $controller->delete_item( $_item );

				if ( is_wp_error( $_response ) ) {
					$response['delete'][] = array(
						'id'    => $id,
						'error' => array(
							'code'    => $_response->get_error_code(),
							'message' => $_response->get_error_message(),
							'data'    => $_response->get_error_data(),
						),
					);
				} else {
					$response['delete'][] = $wp_rest_server->response_to_data( $_response, '' );
				}
			}
		}
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		do_action( 'wwp_after_product_batch_update', $request, $response );

		return $response;
	}

	public function get_collection_params() {
		$wholesale_price_properties = array();

		foreach ( $this->registered_wholesale_roles as $role => $data ) {
			$wholesale_price_properties[ $role ] = array(
				// translators: %s: wholesale role name.
				'description'       => sprintf( __( 'Wholesale price for %s', 'woocommerce-wholesale-pricing' ), $data['roleName'] ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			);
		}

		$params = array(
			'wholesale_price' => array(
				'description' => __( 'The product wholesale price.', 'woocommerce-wholesale-pricing' ),
				'type'        => 'object',
				'properties'  => $wholesale_price_properties,
			),
		);

		$params = array_merge( parent::get_collection_params(), $params );
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		return apply_filters( 'wwp_rest_wholesale_product_get_collection_params', $params, $this );
	}

	public function get_fields_for_response( $request, $post_type = 'product' ) {
		$properties = parent::get_fields_for_response( $request );
		/**
		* Hooks
		*
		* @since 2.4.0
		*/
		return apply_filters( "wwp_rest_wholesale_{$post_type}_properties", $properties, $request );
	}
}
