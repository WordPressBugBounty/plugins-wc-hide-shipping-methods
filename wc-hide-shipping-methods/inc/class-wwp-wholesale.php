<?php
if (! defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class To Add Wholesale Functionality with WooCommerce
 */
if (!class_exists('WWP_Easy_Wholesale')) {
	class WWP_Easy_Wholesale {
		public function __construct() {
			add_filter('woocommerce_get_price_html', array( $this, 'wwp_change_product_price_display' ));
			add_filter('woocommerce_cart_item_price', array( $this, 'wwp_change_product_price_display' ));
			add_action('woocommerce_before_calculate_totals', array( $this, 'wwp_override_product_price_cart' ), 99);
			add_action('wp_footer', array( $this, 'wwp_on_variation_change' ));
			add_action('wp_ajax_wwp_variation', array( $this, 'wwp_variation_change_callback' ));
			add_action('wp_ajax_nopriv_wwp_variation', array( $this, 'wwp_variation_change_callback' ));
			add_filter('woocommerce_variable_sale_price_html', array( $this, 'wwp_variable_price_format' ), 10, 2);
			add_filter('woocommerce_variable_price_html', array( $this, 'wwp_variable_price_format' ), 10, 2);
			add_action('woocommerce_product_query', array( $this, 'wwp_wwp_wholesaler_products_only' ), 99, 1);
			add_action('init', array( $this, 'wwp_default_settings' ));
			add_filter('woocommerce_product_variation_get_price', array( $this, 'wwp_variation_price_change' ) , 200, 2 );
			add_filter('woocommerce_product_variation_get_regular_price', array( $this, 'wwp_variation_price_change' ), 200, 2 );
			add_filter( 'woocommerce_available_variation', array( $this, 'filter_woocommerce_available_variation' ), 200, 3 );
			add_action( 'woocommerce_before_add_to_cart_form', array( $this, 'simple_load_tire_priceing_table' ) );
			add_action( 'woocommerce_single_variation', array( $this, 'variation_load_tire_priceing_table' ) );
			add_filter('wwp_wholesale_product_price', array( $this, 'wwp_wholesale_product_price' ), 10, 2);
			add_filter('wwp_wholesale_product_save', array( $this, 'wwp_wholesale_product_save' ), 10, 2);
			add_action( 'woocommerce_before_mini_cart_contents', array( $this, 'woocommerce_before_mini_cart_contents' ) );
		}

		public function woocommerce_before_mini_cart_contents() {
			if ( ! defined( 'WOOCOMMERCE_CART' ) && !class_exists( 'Storefront' ) ) { 
				define( 'WOOCOMMERCE_CART', true );
			}   
			WC()->cart->calculate_totals();
			WC()->cart->set_session();
			WC()->cart->maybe_set_cart_cookies();
		}

		
		public function filter_woocommerce_available_variation( $variation_get_max_purchase_quantity, $instance, $variation ) {
			$instance  = '';
			$variation = ''; 
			if ( !$this->is_wholesaler_user(get_current_user_id()) ) {
				return $variation_get_max_purchase_quantity;
			}
			$data = get_post_meta(wp_get_post_parent_id($variation_get_max_purchase_quantity['variation_id']), '_wwp_enable_wholesale_item', true);
			
			if ( 'yes' === $data ) {
				$min_quantity = get_post_meta($variation_get_max_purchase_quantity['variation_id'], '_wwp_wholesale_min_quantity', true);

				if ( $min_quantity && 1 !== $min_quantity ) {
					/**
					 * Hooks
					 *
					 * @since 1.0.0
					 */
					$variation_get_max_purchase_quantity['availability_html'] .= apply_filters( 
						'wwp_product_minimum_quantity_text', 
						'<p style="font-size: 14px;">' . sprintf(
							/*
							 * Translators: %s: minimum quantity to apply wholesale.
							 */
							esc_html__( 'Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing' ), 
							$min_quantity
						) . '</p>', 
						$min_quantity 
					);
					return $variation_get_max_purchase_quantity; 
				}

			}
			$data =get_option('_wwp_enable_wholesale_item');
			if (  'yes' === $data  ) {
				
				$min_quantity = (int) get_option('_wwp_wholesale_min_quantity');
				if ( $min_quantity && 1 !== $min_quantity ) {
					/**
					 * Filter
					 *
					 * Translators: %s: minimum quantity to apply wholesale
					 * 
					 * @since 1.0.0
					 */
					$variation_get_max_purchase_quantity['availability_html'] .= apply_filters( 'wwp_product_minimum_quantity_text', '<p style="font-size: 14px;">' . sprintf(
						/*
						* Translators: %s: minimum quantity to apply wholesale.
						*/
						esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity ) . '</p>' , $min_quantity  );
					return $variation_get_max_purchase_quantity; 

				}
			}
			
			return $variation_get_max_purchase_quantity;    
		}
		
		public function wwp_variation_price_change( $price, $variation ) {  
			global $woocommerce;
			$variation_id     = $variation->get_id();
			$product_id       = wp_get_post_parent_id($variation_id);
			$enable_wholesale = get_post_meta($product_id, '_wwp_enable_wholesale_item', true); 
			if ( 'yes' !== get_option('_wwp_enable_wholesale_item')) {
				if ( empty($enable_wholesale) ) {
					return $price;
				}
			}
			$qty ='';
			if ( ( is_cart() || is_checkout() ) || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
				return $price;
			}

			if ($this->get_variable_wholesale_price ( $variation_id, $product_id )) {
				$price = $this->get_variable_wholesale_price ( $variation_id, $product_id );
			}   
			return $price;
		}
		public function wwp_change_product_price_display( $price ) { 
			global $post;
			$post_id = isset( $post->ID ) ? $post->ID : '';
			$product = wc_get_product($post_id);
			if ( is_cart()) {
				return $price;
			}
			if ( ( 'object' === gettype($product) ) && !$product->is_type('simple') ) {
				return $price;
			}
			if ( ( 'object' !== gettype( $product ) )) {
				return $price;
			}
			if ( !$this->is_wholesaler_user(get_current_user_id()) ) {
				return $price;
			}

			if ( !$this->is_wholesale($post->ID) ) {
				return $price;
			}
			$enable_wholesale = get_post_meta($post_id, '_wwp_enable_wholesale_item', true);
			if ( 'yes' !== get_option('_wwp_enable_wholesale_item')) {
				if ( empty($enable_wholesale) ) {
					return $price;
				}
			}
			$r_price =$product->get_price();

			if ( 0 === $r_price) {
				return '';
			}

			$wholesale_price = $this->get_wholesale_price($post_id);
			if ( !is_numeric($wholesale_price) || !is_numeric($r_price) ) {
				return $price;
			}
			$saving_amount  = ( $r_price - $wholesale_price );
			// $saving_percent = ( $r_price - $wholesale_price ) / $r_price * 100;
			$saving_percent = ( $r_price > 0 ) ? ( ( $r_price - $wholesale_price ) / $r_price * 100 ) : 0;

			$min_quantity   = get_post_meta( $post_id, '_wwp_wholesale_min_quantity', true);
			if ( empty($enable_wholesale) && 'yes' === get_option('_wwp_enable_wholesale_item') ) {
				$enable_wholesale = 'yes';
				$min_quantity     = get_option('_wwp_wholesale_min_quantity') ;
			}
			$html     = '';
			$settings = get_option('wwp_wholesale_pricing_options', true);
			$actual   = ( isset( $settings['retailer_label'] ) && !empty( $settings['retailer_label'] ) ) ? $settings['retailer_label'] : esc_html__('Actual', 'woocommerce-wholesale-pricing');
			$save     = ( isset( $settings['save_label'] ) && !empty( $settings['save_label'] ) ) ? $settings['save_label'] : esc_html__('Save', 'woocommerce-wholesale-pricing');
			$new      = ( isset( $settings['wholesaler_label'] ) && !empty( $settings['wholesaler_label']) ) ? $settings['wholesaler_label'] : esc_html__('New', 'woocommerce-wholesale-pricing');
			if ( !empty($wholesale_price) ) {
				/**
				 * Hooks
				 * 
				 * @since 1.0.0
				 */
				$html  = do_action('wwp_before_pricing');
				$html .= '<div class="wwp-wholesale-pricing-details">';
				if ( isset($settings['retailer_disabled']) &&  'yes' !== $settings['retailer_disabled'] ) {
					$html .= '<p><span class="retailer-text">' . esc_html__($actual, 'woocommerce-wholesale-pricing') . '</span>: <s>' . $price . '</s></p>';
				}
				/**
				 * Hook
				 *
				 * @since 1.0.0
				 */
				$html .= '<p><span class="price-text">' . esc_html__($new, 'woocommerce-wholesale-pricing') . '</span>: ' . apply_filters( 'wwp_wholesale_product_price', wc_price( $wholesale_price ), $post_id ) . '</p>';
				if ( isset($settings['save_price_disabled']) &&  'yes' !== $settings['save_price_disabled'] ) {
					/**
					 * Filter
					 *
					 * @since 1.0.0
					 */
					$html .= '<p><b><span class="save-price-text">' . esc_html__($save, 'woocommerce-wholesale-pricing') . '</span>: ' . apply_filters( 'wwp_wholesale_product_save', wc_price( $saving_amount ) . ' (' . round($saving_percent) . '%)', $post_id ) . '</b></p>';
				}
				if ( $min_quantity > 1 ) {
					if ('simple' === $product->get_type()) {
						/*
						 * Translators: %s: minimum quantity to apply wholesale
						 */
						$html .= '<p style="font-size: 14px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . '</p>';
					}
				}
				$html .= '</div>';
				/**
				 * Hook
				 *
				 * @since 1.0.0
				 */
				$html .= do_action('wwp_after_pricing');
			}
			return $html;
		}
		public function wwp_override_product_price_cart( $_cart ) {
			global $woocommerce;
			$items     = $woocommerce->cart->get_cart();
			$tier_qtys = get_tier_min_max_qty();
			foreach ( $_cart->cart_contents as $item ) {
				if ( $this->is_wholesale($item['product_id']) ) {
					$variation_id = $item['variation_id'];
					if ( !empty($variation_id) ) {                     
						if ( 'yes' === get_post_meta( $item['product_id'], '_wwp_enable_wholesale_item', true ) ) {
							$min_quantity = get_post_meta( $variation_id, '_wwp_wholesale_min_quantity', true );
						} else {
							$min_quantity = $this->get_wholesale_qty($item['product_id']);
						}
						if ( ( $min_quantity <= $item['quantity'] ) || ( $item['quantity'] >= $tier_qtys['min'] && $item['quantity'] <= $tier_qtys['max'] ) ) {
							if ( !empty($this->get_variable_wholesale_price($variation_id, $item['product_id'])) ) {
								$item['data']->set_price($this->get_variable_wholesale_price($variation_id, $item['product_id']));
							}
						} else {
							$item['data']->set_price( get_post_meta( $variation_id, '_price', true ) );
						}
					} else {
						if ( 'yes' === get_post_meta( $item['product_id'], '_wwp_enable_wholesale_item', true ) ) {
							$min_quantity = get_post_meta( $item['product_id'], '_wwp_wholesale_min_quantity', true );
						} else {
							$min_quantity = $this->get_wholesale_qty($item['product_id']);
						}
						if ( ( $min_quantity <= $item['quantity'] ) || ( $item['quantity'] >= $tier_qtys['min'] && $item['quantity'] <= $tier_qtys['max'] ) ) {
							if ( !empty($this->get_wholesale_price($item['product_id'])) ) {
								$item['data']->set_price($this->get_wholesale_price($item['product_id']));
							}
						} else {
							$item['data']->set_price( get_post_meta( $item['product_id'], '_price', true ) );
						}
					}
				}
			}
		}
		public function get_wholesale_qty( $product_id ) {
			$quantity         = '';
			$enable_wholesale = get_post_meta($product_id, '_wwp_enable_wholesale_item', true);
			if ( !empty($enable_wholesale) ) {
				$quantity = get_post_meta($product_id, '_wwp_wholesale_min_quantity', true);
			}
			if ( '' === $quantity ) {
				if ( 'yes' === get_option('_wwp_enable_wholesale_item') ) {
					$quantity = get_option('_wwp_wholesale_min_quantity');
				}
			}
			return $quantity;
		}
		public function is_wholesale( $post_id ) {
			$enable_wholesale = get_post_meta($post_id, '_wwp_enable_wholesale_item', true);
			if ( !empty($enable_wholesale) ) {
				return true;
			}
			if ( 'yes' === get_option('_wwp_enable_wholesale_item')) {
				return true;
			}
			return false;
		}
		public function get_wholesale_price_multi( $discount, $wprice, $post_id ) {
			if ( 'fixed' === $discount ) {
				return $wprice;
			} else {
				$product_price   = get_post_meta($post_id, '_price', true);
				$product_price   = ( isset($product_price) && is_numeric($product_price) ) ? $product_price : 0;
				$wholesale_price = $product_price * $wprice / 100;
				return $wholesale_price;
			}
		}
		public function get_wholesale_price( $post_id ) {
			$enable_wholesale = get_post_meta($post_id, '_wwp_enable_wholesale_item', true);
			$wholesale_price  = get_post_meta($post_id, '_wwp_wholesale_amount', true);
			
			if ( empty( $enable_wholesale ) ) {
				if ( 'yes' === get_option('_wwp_enable_wholesale_item')) {
					$tier_pricing = get_option('_wwp_wholesale_global_tier_pricing');
					if ( ! empty( $tier_pricing ) ) {
						$wholesale_price = tire_wholesale_product_price( $post_id, wc_get_product( $post_id ) );
						if ( empty( $wholesale_price ) ) {
							$wholesale_price = get_option('_wwp_wholesale_amount'); 
						}
					} else {
						$wholesale_price = get_option('_wwp_wholesale_amount');
					}
				}
			}

			if ( $this->is_wholesale($post_id) ) {
				$wholesale_amount_type = get_post_meta($post_id, '_wwp_wholesale_type', true);
				
				if ( empty( $enable_wholesale ) ) {
					if ( 'yes' === get_option('_wwp_enable_wholesale_item')) {
						$wholesale_amount_type = get_option('_wwp_wholesale_type');
					}
				}
				
				if ( 'fixed' === $wholesale_amount_type ) {
					return $wholesale_price;
				} else {
					$product_price = get_post_meta($post_id, '_price', true);
					$product_price = ( isset($product_price) && is_numeric($product_price) ) ? $product_price : 0;
					
					$wholesale_price = (float) $product_price * (float) $wholesale_price / 100;

					return $wholesale_price;
				}
			}
		}
		public function get_variable_wholesale_price( $variation_id, $product_id = '' ) {
			if ( empty($product_id) ) {
				$product_id = get_the_ID();
			}
			
			$wholesale_item_enable = get_post_meta($product_id, '_wwp_enable_wholesale_item', true);
			$variable_price        = get_post_meta($variation_id, '_wwp_wholesale_amount', true);
			$wholesale_amount_type = get_post_meta($product_id, '_wwp_wholesale_type', true);
		
			if ( 'yes' !== $wholesale_item_enable ) { 
			
				if ( 'yes' === get_option('_wwp_enable_wholesale_item')) {
					$wholesale_amount_type = get_option('_wwp_wholesale_type');
					$tier_pricing          = get_option('_wwp_wholesale_global_tier_pricing');
					if ( ! empty( $tier_pricing ) ) {
						$variable_price = tire_wholesale_product_price( $product_id, wc_get_product( $product_id ) );
						if ( empty( $variable_price ) ) {
							$variable_price = get_option('_wwp_wholesale_amount');
						}
					} else {
						$variable_price = get_option('_wwp_wholesale_amount');
					}
				}
			}
			
			if ( 'fixed' === $wholesale_amount_type ) {
				return $variable_price;
			} else {
				$product_price   = get_post_meta($variation_id, '_price', true);
				$product_price   = ( isset($product_price) && is_numeric($product_price) ) ? $product_price : 0;
				$variable_price  = ( isset($variable_price) && is_numeric($variable_price) ) ? $variable_price : 0;
				$wholesale_price = $product_price * $variable_price / 100;
				return $wholesale_price;
			}
		}
		public function is_wholesaler_user( $user_id ) {
			if ( !empty($user_id) ) {
				$user_info = get_userdata($user_id);
				$user_role = (array) $user_info->roles;
				if (!empty($user_role) &&  in_array('wwp_wholesaler', $user_role, true) ) {
					return true;
				}
			}
			return false;
		}
		public function wwp_on_variation_change() {
			global $post;
			$user_info = get_userdata( get_current_user_id() );    
			if ( $this->is_wholesale($post->ID) ) { ?>
				<script type="text/javascript" >
					/* Make this document ready function to work on click where you want */
					jQuery(document).ready(function($) {
						/* In front end of WordPress we have to define ajaxurl */
						var ajaxurl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';
						jQuery( "body").on( "found_variation" , ".variations_form", function( event, variation ) {
							var data = {
								'action': 'wwp_variation',
								'variation_id': variation['variation_id'],
								'variation_price': variation['price_html'],
								'wwp_variation_nonce' : '<?php echo esc_html(wp_create_nonce('wwp_variation_nonce')); ?>',
								'product_id': <?php echo esc_html(get_the_ID()); ?>
							};
							$.post(ajaxurl, data, function(response) {
								if ( '' !== response)
									jQuery('.woocommerce-variation-price').html(response);
							});
						});
					});
				</script>
				<?php
			}
		}
		public function wwp_variation_change_callback() { 
			if ( !isset($_POST['wwp_variation_nonce']) || !wp_verify_nonce( wc_clean($_POST['wwp_variation_nonce']), 'wwp_variation_nonce') ) {
				return;
			}
			$variation_id    = isset( $_POST['variation_id'] ) ? absint( $_POST['variation_id'] ) : '';
			$variation_price = isset( $_POST['variation_price'] ) ? wc_clean( $_POST['variation_price'] ) : '';
			$product_id      = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : '';
			if ( !$this->is_wholesaler_user(get_current_user_id()) ) {
				echo '';
				die();
			}
			$wholesale_variable_price = get_post_meta($variation_id, '_wwp_wholesale_amount', true);
			$variable_wholesale_price = $this->get_variable_wholesale_price($variation_id, $product_id);
			$html                     = '<s>' . esc_html($variation_price) . '</s>';
			$html                    .= '<span class="price"><span class="woocommerce-Price-amount amount">' . wc_price($variable_wholesale_price) . '</span></span>';
			$min_quantity             = get_post_meta($variation_id, '_wwp_wholesale_min_quantity', true);
			if ( $min_quantity > 1 ) {
				/*
				 * Translators: %s: minimum quantity to apply wholesale
				 */
				$html .= '<p style="font-size: 14px;">' . sprintf(esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . ' </p>';
			}
			echo wp_kses_post($html);
			die(); // this is required to terminate immediately and return a proper response
		}
		public function wwp_variable_price_format( $price, $product ) { 
			$prod_id = $product->get_id();
			if ( !$this->is_wholesaler_user(get_current_user_id()) ) {
				return $price;
			}
				
			if ( 0 === $price ) {
				return '';
			}
			
			$enable_wholesale = get_post_meta($prod_id, '_wwp_enable_wholesale_item', true);
			if ( 'yes' !== get_option('_wwp_enable_wholesale_item')) {
				if ( empty($enable_wholesale) ) {
					return $price;
				}
			}
			$product_variations           = $product->get_children();
			$wholesale_product_variations = array();
			$original_variation_price     = array();
			foreach ( $product_variations as $product_variation ) {
				$wholesale_product_variations[] = $this->get_variable_wholesale_price($product_variation, $prod_id);
				$original_variation_price[]     = get_post_meta($product_variation, '_price', true);
			}
			sort($wholesale_product_variations);
			sort($original_variation_price);
			$min_wholesale_price          = $wholesale_product_variations[0];
			$max_wholesale_price          = $wholesale_product_variations[ count($wholesale_product_variations) - 1 ];
			$min_original_variation_price = $original_variation_price[0];
			if ( empty($min_wholesale_price) ) {
				$min_wholesale_price = $min_original_variation_price;
			}
			$max_original_variation_price = $original_variation_price[ count($original_variation_price) - 1 ];
			$min_saving_amount            = ( $min_original_variation_price - $min_wholesale_price );
			if ( empty($max_wholesale_price) ) {
				$max_wholesale_price = $max_original_variation_price;
			}
			$min_saving_percent = ( $min_original_variation_price - $min_wholesale_price ) / $min_original_variation_price * 100;
			$max_saving_amount  = ( $max_original_variation_price - $max_wholesale_price );
			$max_saving_percent = ( $max_original_variation_price - $max_wholesale_price ) / $max_original_variation_price * 100;
			$min_quantity       = get_post_meta( $prod_id, '_wwp_wholesale_min_quantity', true);
			$settings           = get_option('wwp_wholesale_pricing_options', true);
			$actual             = ( isset( $settings['retailer_label'] ) && !empty( $settings['retailer_label']) ) ? esc_html( $settings['retailer_label'] ) : esc_html__('Actual', 'woocommerce-wholesale-pricing');
			$save               = ( isset( $settings['save_label'] ) && !empty( $settings['save_label']) ) ? esc_html($settings['save_label']) : esc_html__('Save', 'woocommerce-wholesale-pricing');
			$new                = ( isset( $settings['wholesaler_label'] ) && !empty( $settings['wholesaler_label']) ) ? esc_html($settings['wholesaler_label']) : esc_html__('New', 'woocommerce-wholesale-pricing');
			
			
			$actual = ( isset( $settings['retailer_label'] ) && !empty( $settings['retailer_label']) ) ? wc_clean( $settings['retailer_label']  ) : esc_html__( 'Actual', 'woocommerce-wholesale-pricing' );
			$save   = ( isset( $settings['save_label'] ) && !empty( $settings['save_label']) ) ? wc_clean( $settings['save_label']  ) : esc_html__( 'Save', 'woocommerce-wholesale-pricing' );
			$new    = ( isset( $settings['wholesaler_label'] ) && !empty( $settings['wholesaler_label']) ) ? wc_clean( $settings['wholesaler_label'] ) : esc_html__( 'New', 'woocommerce-wholesale-pricing' );
			
			$html = '<div class="wwp-wholesale-pricing-details">';
			if ( isset($settings['retailer_disabled']) && 'yes' !== $settings['retailer_disabled'] ) {
				$html .= '<p><span class="retailer-text">' . esc_attr( $actual ) . '</span>: <s>' . $price . '</s></p>';
			}
			$html .= '<p><b><span class="price-text">' . esc_attr( $new ) . '</span>: ' . wc_price( $wholesale_product_variations[0] ) . ' - ' . wc_price( $wholesale_product_variations[ count($wholesale_product_variations) - 1 ] ) . '</b></p>';
			if ( isset($settings['save_price_disabled']) && 'yes' !== $settings['save_price_disabled'] ) {
				$html .= '<p><b><span class="save-price-text">' . esc_attr( $save ) . '</span>:  (' . round( $min_saving_percent ) . '% - ' . round( $max_saving_percent ) . '%)</b></p>';
			}
			if ( $min_quantity > 1 ) {
				/*
				 * Translators: %s: minimum quantity to apply wholesale
				 */
				$html .= '<p style="font-size: 14px;">' . sprintf( esc_html__('Wholesale price will only be applied to a minimum quantity of %1$s products', 'woocommerce-wholesale-pricing'), $min_quantity) . '</p>';
			}
			$html .= '</div>';
			return $html;
		}
		public function wwp_wwp_wholesaler_products_only( $q ) {
			$settings             = get_option('wwp_wholesale_pricing_options', true);
			$wholesaler_prod_only = ( isset($settings['wholesaler_prodcut_only']) && 'yes' === $settings['wholesaler_prodcut_only'] ) ? 'yes' : 'no';
			if ( 'yes' === $wholesaler_prod_only ) {
				$user_info = get_userdata(get_current_user_id());
				if ( in_array('wwp_wholesaler', $user_info->roles, true) ) {
					$meta_query   = $q->get('meta_query');
					$meta_query[] = array(
						'key'     => '_wwp_enable_wholesale_item',
						'value'   => 'yes',
					);
					$q->set('meta_query', $meta_query);
				}
			}
		}
		public function wwp_default_settings() {
			if ( empty(get_option('wc_settings_tab_wholesale_retailer_label', true)) ) {
				update_option('wc_settings_tab_wholesale_retailer_label', esc_html__('RRP', 'woocommerce-wholesale-pricing'));
			}
			if ( empty(get_option('wc_settings_tab_wholesale_wholesaler_price_label', true)) ) {
				update_option('wc_settings_tab_wholesale_wholesaler_price_label', esc_html__('Your Price', 'woocommerce-wholesale-pricing'));
			}
			if ( empty(get_option('wc_settings_tab_wholesale_wholesaler_save_price_label', true)) ) {
				update_option('wc_settings_tab_wholesale_wholesaler_save_price_label', esc_html__('You Save', 'woocommerce-wholesale-pricing'));
			}
		}
		
		/**
		 * Method simple_load_tire_priceing_table
		 *
		 * @param $p_id int
		 *
		 * @return void
		 */
		public function simple_load_tire_priceing_table( $p_id = '' ) {

			if ( 'yes' !== get_option( '_wwp_enable_wholesale_item' ) ) {
				return;
			}

			$product_id     = ! empty( $p_id ) ? $p_id : get_the_ID();
			$original_price = get_post_meta( $product_id, '_regular_price', true );
			if ( 0 === $original_price ) {
				return;
			}
			$product = wc_get_product($product_id);
			echo esc_html($product->get_type());
			if ( $product instanceof WC_Product && $product->get_type() === 'simple' ) {
				if ( 'yes' !== get_post_meta( $product_id, '_wwp_enable_wholesale_item', true ) ) {
					$tier_pricing = get_option('_wwp_wholesale_global_tier_pricing');
					if ( ! empty( $tier_pricing ) && is_array( $tier_pricing ) ) {
						?>
						<table id="wholesale_tire_price" style="width:100%">
							<tr>
								<th><?php esc_html_e( 'Quantity', 'woocommerce-wholesale-pricing'); ?></th>
								<th><?php esc_html_e( 'Save Discount', 'woocommerce-wholesale-pricing'); ?></th>
								<th><?php esc_html_e( 'Price per unit', 'woocommerce-wholesale-pricing'); ?></th>
							</tr>
						<?php
						foreach ( $tier_pricing as $tier ) {

							if ( isset( $tier['min'], $tier['max'], $tier['value'] ) && ! empty( $tier['min'] ) && ! empty( $tier['value'] ) ) {
								
								$max_wholesale_price = tire_get_type( $product_id, null, $tier['value'], $original_price, get_option( '_wwp_wholesale_type' ) );
								$max_saving_percent  = ( $original_price - $max_wholesale_price ) / $original_price * 100;
								$max_saving_percent  = round( $max_saving_percent );
								$max_wholesale_price = wwp_get_price_including_tax( $product, array( 'price' => $max_wholesale_price ) );
								$max_wholesale_price = wc_price( $max_wholesale_price );
								?>
									<tr class="wrap_<?php esc_attr_e( $product_id ); ?> row_tire" data-id="<?php esc_attr_e( $product_id ); ?>" data-min="<?php esc_attr_e( $tier['min'] ); ?>" data-max="<?php esc_attr_e( $tier['max'] ); ?>">
										<td> <?php echo wp_kses_post( $tier['min'] . ( empty($tier['max']) ? '+' : '-' . $tier['max'] ) ); ?> </td>
										<td> <?php esc_attr_e($max_saving_percent); ?>% </td>
										<td> <?php echo wp_kses_post($max_wholesale_price); ?> </td>
									</tr>
								<?php
							}
						}
						?>
						</table>
						<?php
					}
				}
			}
		}

		public function variation_load_tire_priceing_table( $p_id = '' ) {

			if ( get_option( '_wwp_enable_wholesale_item' ) !== 'yes' ) {
				return;
			}

			$curent_veration_id = !empty($p_id) ? $p_id : get_the_ID();
			$product            = new WC_Product_Variable( $curent_veration_id );
			$variables          = $product->get_available_variations();

			if ( $product instanceof WC_Product && 'variable' === $product->get_type() ) {
				$product_tier_pricing = get_option('_wwp_wholesale_global_tier_pricing');
				if ( ! empty( $product_tier_pricing ) ) { 
					if ( ! empty( $variables ) ) {
						?>
						<table id="wholesale_tire_price" style="width:100%">
							<tr>
								<th><?php esc_html_e( 'Variations', 'woocommerce-wholesale-pricing'); ?></th>
								<th><?php esc_html_e( 'Quantity', 'woocommerce-wholesale-pricing'); ?></th>
								<th><?php esc_html_e( 'Save Discount', 'woocommerce-wholesale-pricing'); ?></th>
								<th><?php esc_html_e( 'Price per unit', 'woocommerce-wholesale-pricing'); ?></th>
							</tr>
							<?php
							foreach ( $variables as $variable ) {
								$variation_id = $variable['variation_id'];

								foreach ( $product_tier_pricing as $variation_data ) {
									if ( isset( $variation_data['value'] ) && ! empty( $variation_data['value'] ) ) {
										$max_original_variation_price = get_post_meta( $variation_id, '_regular_price', true );
										if ( 0 === $max_original_variation_price ) {
											continue;
										}
										$max_wholesale_price = tire_get_type( $variation_id, $product, $variation_data['value'], $max_original_variation_price, get_option( '_wwp_wholesale_type' ) );
										$max_saving_percent  = ( $max_original_variation_price - $max_wholesale_price ) / $max_original_variation_price * 100;
										$max_saving_percent  = round( $max_saving_percent );
										$max_wholesale_price = wwp_get_price_including_tax( $product, array( 'price' => $max_wholesale_price ) );
										$max_wholesale_price = wc_price( $max_wholesale_price );
										$product             = wc_get_product( $variation_id );
										if ( !empty($variation_data['min']) || !empty($variation_data['max']) || 0 !== $max_saving_percent) {
											?>
												<tr class="wrap_<?php esc_attr_e( $variation_id ); ?> row_tire" data-id="<?php esc_attr_e($variation_id); ?>" data-min="<?php esc_attr_e($variation_data['min']); ?>" data-max="<?php esc_attr_e($variation_data['max']); ?>">
													<td> <?php echo wp_kses_post(wc_get_formatted_variation( $product->get_variation_attributes(), true )); ?> </td>
													<td> <?php echo wp_kses_post( $variation_data['min'] . ( empty($variation_data['max']) ? '+' : '-' . $variation_data['max'] ) ); ?> </td>
													<td> <?php esc_attr_e( $max_saving_percent, 'woocommerce-wholesale-pricing' ); ?>%</td>
													<td> <?php echo wp_kses_post( $max_wholesale_price, 'woocommerce-wholesale-pricing' ); ?> </td>
												</tr>
											<?php
										}
									}
								}

							}
							?>
						</table>
						<?php
					}
				}
			}
		}

		public function wwp_wholesale_product_price( $price, $post_id ) {
			$enable_wholesale = get_post_meta($post_id, '_wwp_enable_wholesale_item', true);
			$wholesale_price  = get_post_meta($post_id, '_wwp_wholesale_amount', true);
			
			if ( empty( $enable_wholesale ) ) {
				if ( 'yes' === get_option('_wwp_enable_wholesale_item')) {
					$tier_pricing = get_option('_wwp_wholesale_global_tier_pricing');
					if ( ! empty( $tier_pricing ) ) {
						$prices = $this->get_tier_min_max_wholesale_price( $tier_pricing, $post_id );
						if ( ! empty( $prices ) ) {
							return wc_price( reset( $prices ) ) . ' - ' . wc_price( end( $prices ) );
						} else {
							$wholesale_price = get_option('_wwp_wholesale_amount');
						}
					} else {
						$wholesale_price = get_option('_wwp_wholesale_amount');
					}
				}
			}
			
			if ( $this->is_wholesale($post_id) ) {
				$wholesale_amount_type = get_post_meta($post_id, '_wwp_wholesale_type', true);
				
				if ( empty( $enable_wholesale ) ) {
					if ( 'yes' === get_option('_wwp_enable_wholesale_item')) {
						$wholesale_amount_type = get_option('_wwp_wholesale_type');
					}
				}
				
				if ( 'fixed' === $wholesale_amount_type ) {
					return wc_price( $wholesale_price );
				} else {
					$product_price   = get_post_meta($post_id, '_price', true);
					$product_price   = ( isset($product_price) && is_numeric($product_price) ) ? $product_price : 0;
					$wholesale_price = $product_price * $wholesale_price / 100;
					return wc_price( $wholesale_price );
				}
			}
			return wc_price( $price );
		}

		public function get_tier_min_max_wholesale_price( $tiers, $product_id ) {
			$prices  = array();
			$product = wc_get_product( $product_id );
			foreach ( $tiers as $tier ) {
				if ( ! empty( $tier['value'] ) ) {
					$original_price      = get_post_meta( $product_id, '_regular_price', true );
					$max_wholesale_price = tire_get_type( $product_id, null, $tier['value'], $original_price, get_option( '_wwp_wholesale_type' ) );
					$max_wholesale_price = wwp_get_price_including_tax( $product, array( 'price' => $max_wholesale_price ) );
					$prices[]            = $max_wholesale_price;
					sort($prices);
				}
			}
			return $prices;
		}

		public function wwp_wholesale_product_save( $save_text, $product_id ) {
			$discounts = array();
			if ( 'yes' !== get_post_meta( $product_id, '_wwp_enable_wholesale_item', true ) ) {
				$tier_pricing = get_option('_wwp_wholesale_global_tier_pricing');
				if ( ! empty( $tier_pricing ) ) {
					foreach ( $tier_pricing as $tier ) {
						if ( ! empty( $tier['value'] ) ) {
							$original_price      = get_post_meta( $product_id, '_regular_price', true );
							$max_wholesale_price = tire_get_type( $product_id, null, $tier['value'], $original_price, get_option( '_wwp_wholesale_type' ) );
							$max_saving_percent  = ( $original_price - $max_wholesale_price ) / $original_price * 100;
							$max_saving_percent  = round( $max_saving_percent );
							if ( ! empty( $max_saving_percent ) ) {
								$discounts[] = $max_saving_percent;
							}
						}
					}
					return '(' . max($discounts) . '%)';
				}
			}
			return $save_text;
		}
	}
	new WWP_Easy_Wholesale();
}
