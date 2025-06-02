<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( ! function_exists( 'shapeSpace_allowed_html' ) ) :
	function shapeSpace_allowed_html() {
	
		$allowed_atts                = array(
			'align'      => array(),
			'class'      => array(),
			'id'         => array(),
			'dir'        => array(),
			'lang'       => array(),
			'style'      => array(),
			'xml:lang'   => array(),
			'src'        => array(),
			'alt'        => array(),
			'href'       => array(),
			'rel'        => array(),
			'rev'        => array(),
			'target'     => array(),
			'novalidate' => array(),
			'type'       => array(),
			'name'       => array(),
			'tabindex'   => array(),
			'action'     => array(),
			'method'     => array(),
			'for'        => array(),
			'width'      => array(),
			'height'     => array(),
			'data'       => array(),
			'title'      => array(),
			'value'      => array(),
			'selected'  => array(),
			'enctype'   => array(),
			'disable'   => array(),
			'disabled'  => array(),
		);
		$allowedposttags['form']     = $allowed_atts;
		$allowedposttags['label']    = $allowed_atts;
		$allowedposttags['select']   = $allowed_atts;
		$allowedposttags['option']   = $allowed_atts;
		$allowedposttags['input']    = $allowed_atts;
		$allowedposttags['textarea'] = $allowed_atts;
		$allowedposttags['iframe']   = $allowed_atts;
		$allowedposttags['script']   = $allowed_atts;
		$allowedposttags['style']    = $allowed_atts;
		$allowedposttags['strong']   = $allowed_atts;
		$allowedposttags['small']    = $allowed_atts;
		$allowedposttags['table']    = $allowed_atts;
		$allowedposttags['span']     = $allowed_atts;
		$allowedposttags['abbr']     = $allowed_atts;
		$allowedposttags['code']     = $allowed_atts;
		$allowedposttags['pre']      = $allowed_atts;
		$allowedposttags['div']      = $allowed_atts;
		$allowedposttags['img']      = $allowed_atts;
		$allowedposttags['h1']       = $allowed_atts;
		$allowedposttags['h2']       = $allowed_atts;
		$allowedposttags['h3']       = $allowed_atts;
		$allowedposttags['h4']       = $allowed_atts;
		$allowedposttags['h5']       = $allowed_atts;
		$allowedposttags['h6']       = $allowed_atts;
		$allowedposttags['ol']       = $allowed_atts;
		$allowedposttags['ul']       = $allowed_atts;
		$allowedposttags['li']       = $allowed_atts;
		$allowedposttags['em']       = $allowed_atts;
		$allowedposttags['hr']       = $allowed_atts;
		$allowedposttags['br']       = $allowed_atts;
		$allowedposttags['tr']       = $allowed_atts;
		$allowedposttags['td']       = $allowed_atts;
		$allowedposttags['p']        = $allowed_atts;
		$allowedposttags['a']        = $allowed_atts;
		$allowedposttags['b']        = $allowed_atts;
		$allowedposttags['i']        = $allowed_atts;
		return $allowedposttags;
	}
endif;

if ( ! function_exists( 'wwp_elements' ) ) : 
	function wwp_elements( $elements ) {
		/**
		 * Filter
		 * 
		 * @since 1.7.2
		 */ 
		echo wp_kses_post( apply_filters( 'wwp_registration_form_elements', $elements ) );
	}
endif;

if ( ! function_exists( 'registration_form_class' ) ) : 
	function registration_form_class( $css ) {
		/**
		 * Filter
		 * 
		 * @since 1.7.2
		 */  
		return apply_filters( 'registration_form_class', $css );
	}
endif;

if ( ! function_exists( 'wholesale_tab_link' ) ) :
	function wholesale_tab_link( $tab = '' ) {
		
		if (!empty($tab)) {
			return admin_url( 'admin.php?page=wwp-registration-setting&tab=' ) . $tab;
		} else {
			return admin_url( 'admin.php?page=wwp-registration-setting' );
		}
	}
endif;

if ( ! function_exists( 'wholesale_tab_active' ) ) :
	function wholesale_tab_active( $active_tab = '' ) {

		// if ( !isset($_GET['wwp_wholesale_wholesale_tab_active_nonce']) || ( isset($_GET['wwp_wholesale_wholesale_tab_active_nonce']) && !wp_verify_nonce( wc_clean($_GET['wwp_wholesale_wholesale_tab_active_nonce']), 'wwp_wholesale_wholesale_tab_active_nonce') )) {
		//  return;
		// }

		$getdata = '';
		if (isset($_GET['tab'])) {
			$getdata = sanitize_text_field($_GET['tab']);
		}
		
		if ( $getdata === $active_tab ) {
			return 'nav-tab-active';
		} 
	}
endif;

if ( ! function_exists( 'wholesale_content_tab_active' ) ) :
	function wholesale_content_tab_active( $active_tab = '' ) {

		// if ( !isset($_GET['wwp_wholesale_settings_nonce']) || ( isset($_GET['wwp_wholesale_settings_nonce']) && !wp_verify_nonce( wc_clean($_GET['wwp_wholesale_settings_nonce']), 'wwp_wholesale_settings_nonce') )) {
		//  return;
		// }
		
		$getdata = '';      
		if (isset($_GET['tab'])) {
			$getdata = sanitize_text_field($_GET['tab']);
		}
		
		if ( $getdata === $active_tab ) {
			return 'block';
		} else {
			return 'none';
		}
	}
endif;

if ( ! function_exists( 'wholesale_load_form_builder' ) ) :
	function wholesale_load_form_builder() {
		
		// if ( !isset($_GET['wwp_wholesale_settings_nonce']) || ( isset($_GET['wwp_wholesale_settings_nonce']) && !wp_verify_nonce( wc_clean($_GET['wwp_wholesale_settings_nonce']), 'wwp_wholesale_settings_nonce') )) {
		// return;
		// }

		$tab = '';

		if (isset($_GET['tab'])) {
			$tab = sanitize_text_field($_GET['tab']);
		}
		
		if ( 'extra-fields' !== $tab ) { 
			return true;
		} else {
		
			return false;
		}
	}
endif;

if ( ! function_exists( 'wwp_get_get_data' ) ) :
	function wwp_get_get_data( $name = '' ) {
		if ( isset( $_GET['wwp_wholesale_registrattion_nonce'] ) && wp_verify_nonce( wc_clean( $_GET['wwp_wholesale_registrattion_nonce'] ), 'wwp_wholesale_registrattion_nonce' ) ) {
			$get = $_GET;
		}
		$get = $_GET;
		if ( isset( $get[ $name ] ) ) {
			/**
			* Hooks
			*
			* @since 3.0
			*/
			return apply_filters( 'wwp_get_get_data', wp_kses_post( $get[ $name ] ) );
		} else {
			return $_GET;
		}
	}
endif;


if ( ! function_exists( 'is_wholesaler_user' ) ) :
	function is_wholesaler_user( $user_id ) {
		if ( !empty($user_id) ) {
			$user_info = get_userdata($user_id);
			$user_role = (array) $user_info->roles;
			if (!empty($user_role) &&  in_array('wwp_wholesaler', $user_role, true) ) {
				return true;
			}
		}
		return false;
	}
endif;

if ( ! function_exists( 'wwp_global_tier_pricing_html' ) ) :
	function wwp_global_tier_pricing_html( $index ) {
		$tiers = get_option( '_wwp_wholesale_global_tier_pricing' ); ?>

		<div class="form-inline append-data">	        
			<div class="bunch_row">
				<div class="col-md-4 wrapper_my_input">
					<input type="number" name="options[tier_pricing][min][]" min="1" class="form-control form-control-sm" placeholder="Starting Quantity" 
						value="<?php echo isset( $tiers[ $index ]['min'] ) ? esc_attr( $tiers[ $index ]['min'] ) : ''; ?>" 
						id="wwp_tier_min_<?php echo esc_attr( $index ); ?>" 
						onkeyup="document.getElementById( 'wwp_tier_max_<?php echo esc_attr( $index ); ?>' ).min = parseInt(this.value) + 1;">
				</div>
				<div class="col-md-4 wrapper_my_input">
					<input type="number" name="options[tier_pricing][max][]" min="1" class="form-control form-control-sm" placeholder="Ending Quantity" 
						value="<?php echo isset( $tiers[ $index ]['max'] ) ? esc_attr( $tiers[ $index ]['max'] ) : ''; ?>" 
						id="wwp_tier_max_<?php echo esc_attr( $index ); ?>" 
						onkeyup="document.getElementById( 'wwp_tier_min_<?php echo esc_attr( $index ); ?>' ).max = parseInt(this.value) - 1;">
				</div>
				<div class="col-md-4 wrapper_my_input">
					<input type="number" name="options[tier_pricing][value][]" step="0.01" min="0" class="wwp_tire_price form-control form-control-sm" placeholder="Wholesale Price" 
						value="<?php echo isset( $tiers[ $index ]['value'] ) ? esc_attr( $tiers[ $index ]['value'] ) : ''; ?>" 
						id="wwp_tier_value_<?php echo esc_attr( $index ); ?>">
				</div>
			</div>
		</div> 
		<?php
	}
endif;


if ( ! function_exists( 'tire_get_type' ) ) :
	function tire_get_type( $id, $product, $tire_price, $regular_price, $type ) {

		if ( empty( $regular_price ) || empty( $tire_price ) ) {
			return $regular_price;
		}
		
		if ( 'fixed' === $type ) {
			$regular_price = $tire_price;
		} else {
			$regular_price = $regular_price * $tire_price / 100;
		}
		return $regular_price;
	}
endif;

if ( ! function_exists( 'wwp_get_price_including_tax' ) ) :
	function wwp_get_price_including_tax( $product, $args = array() ) {

		if ( is_admin() ) {
			return $args['price'];
		}
		
		global $woocommerce;
		if ( $woocommerce->customer->is_vat_exempt() === false && 'taxable' === get_post_meta( $product->get_id(), '_tax_status', true ) ) {
			if ( ( ! is_cart() || ! is_checkout() ) && 'excl' === get_option( 'woocommerce_tax_display_shop' ) ) {
				$price = $args['price'];
			} else {
				$price = wc_get_price_including_tax( $product, array( 'price' => $args['price'] ) );
			}
		} else {
			$price = $args['price'];
		}

		if ( class_exists( 'WCCS' ) ) {
			$wccs  = new WCCS();
			$price = $wccs->wccs_price_conveter( $price, false );
		}
		
		/**
		* Hooks
		*
		* @since 3.0
		*/
		return apply_filters( 'wwp_get_price_including_tax', $price, $product );
	}
endif;

if ( ! function_exists( 'tire_wholesale_product_price' ) ) :

	function tire_wholesale_product_price( $product_id, $product ) {
		$product_data = $product;
		
		$qty   = get_cart_qty( $product_id );
		$tiers = get_option('_wwp_wholesale_global_tier_pricing');

		if ( ! empty( $tiers ) ) {
			foreach ( $tiers as $key => $tier ) {
				if ( isset( $tier['value'] ) && ! empty( $tier['value'] ) ) {
					if ( ! is_admin() ) {
						if ( $qty >= $tier['min'] && $qty <= $tier['max'] ) {      
							return $tier['value'];
						} elseif ( $qty >= $tier['min'] && empty( $tier['max'] ) ) {
							return $tier['value'];
						}
					}
				}
			}
		}
	}

endif;

if ( ! function_exists( 'get_cart_qty' ) ) :
	function get_cart_qty( $product_id ) {
		if ( ( is_cart() || is_checkout() ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if ( $cart_item['product_id'] === $product_id ) {
					return $cart_item['quantity'];
				}
			}
		}
		/**
		* Hooks
		*
		* @since 3.0
		*/
		return apply_filters( 'wwp_cart_quantity', 1, $product_id );
	}
endif;

if ( ! function_exists( 'get_tier_min_max_qty' ) ) :

	function get_tier_min_max_qty() {

		$tiers = ( array ) get_option('_wwp_wholesale_global_tier_pricing');
		
		$min = 0;
		$max = 0;

		$min = isset( $tiers[0]['min'] ) ? $tiers[0]['min'] : 0;
		$max = isset( $tiers[ count($tiers) - 1 ]['max'] ) ? $tiers[ count($tiers) - 1 ]['max'] : 0;

		return compact( 'min', 'max' );
	}

endif;
