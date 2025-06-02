<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( !class_exists('Wwp_Wholesale_Pricing_Frontend') ) {

	class Wwp_Wholesale_Pricing_Frontend {

		public function __construct() {
			add_action('wp_enqueue_scripts', array( $this, 'wwp_script_style' ));
			add_filter('woocommerce_coupons_enabled', array( $this, 'wwp_coupons_enabled' ), 10, 1 );
			$settings = get_option('wwp_wholesale_pricing_options', true);
			add_action( 'init', array( $this, 'wwp_hide_price' ));
			if ( isset( $settings['enable_upgrade'] ) && 'yes' === $settings['enable_upgrade'] ) { 
				add_action( 'init', array( $this, 'wwp_upgrade_add_rewrite' ));
				add_filter( 'query_vars', array( $this, 'wwp_upgrade_add_var' ), 10 );
				add_filter( 'woocommerce_account_menu_items', array( $this, 'wwp_upgrade_add_menu_items' ));
				add_action( 'woocommerce_account_upgrade-account_endpoint', array( $this, 'wwp_upgrade_content' ));
				add_action( 'wp_head', array( $this, 'wwp_li_icons' ));
			}

			add_filter( 'woocommerce_email_headers' , array( $this, 'email_recipeint_bcc_cc' ), 10, 4 );
			add_filter( 'woocommerce_available_payment_gateways', array( $this, 'wwp_available_payment_gateways' ), 200, 1 );
			add_filter( 'woocommerce_package_rates', array( $this, 'wwp_restrict_shipping_wholesaler' ), 10, 2 );
		}



		public function wwp_hide_price() {
			$settings = get_option( 'wwp_wholesale_pricing_options', true );
			if ( ! is_user_logged_in() && isset( $settings['price_hide'] ) && 'yes' === $settings['price_hide'] ) {
				remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10 );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 10 );
				remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
				remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
				remove_action( 'woocommerce_single_variation', 'woocommerce_single_variation_add_to_cart_button', 20 );
				add_action( 'woocommerce_single_variation', array( $this, 'wwp_woocommerce_get_variation_price_html' ));
				add_action( 'woocommerce_single_product_summary', array( $this, 'wwp_removeretail_prices' ), 10 );
				add_action( 'woocommerce_after_shop_loop_item', array( $this, 'wwp_removeretail_prices' ), 11 );
				add_filter( 'woocommerce_get_price_html', array( $this, 'wwp_woocommerce_get_price_html' ), 10, 2 );
				add_filter( 'woocommerce_is_purchasable', array( $this, 'filter_woocommerce_is_purchasable' ), 10, 2 );
			}
		}

		
		public function filter_woocommerce_is_purchasable( $_, $instance ) {
			$instance = '';
			return false;
		}

		public function wwp_removeretail_prices() {
			$settings = get_option( 'wwp_wholesale_pricing_options', true );

			if ( isset( $settings['display_link_text'] ) && ! empty( $settings['display_link_text'] ) ) {
				$link_text = $settings['display_link_text'];
			} else {
				$link_text = esc_html__( 'Login to see price', 'woocommerce-wholesale-pricing' );
			}
			echo '<a class="login-to-upgrade" href="' . esc_url( get_permalink( wc_get_page_id( 'myaccount' ) ) ) . '">' . esc_html__( $link_text, 'woocommerce-wholesale-pricing' ) . '</a>';
		}

		public function wwp_woocommerce_get_price_html( $price, $product ) {
			$price   = '';
			$product = array();
			return '';
		}
		
		public function wwp_woocommerce_get_variation_price_html() {
			return '';
		}
		
		
		public function wwp_coupons_enabled( $enabled ) {
			if ( 'yes' === get_option('wwp_wholesale_disable_coupons') && 'wwp_wholesaler' === $this->wwp_get_current_user_role()  ) {
				WC()->cart->remove_coupons();
				return false;
			}
			return $enabled;
		}   
		public function wwp_get_current_user_role() {
			if ( !is_user_logged_in() ) {
				return false;
			}
			$user      = get_userdata(get_current_user_id());
			$user_role = array_shift($user->roles);
			return $user_role;
		}
		public function wwp_script_style() {
			wp_enqueue_style( 'wwp-wholesale', WWP_PLUGIN_URL . 'assets/css/wwp-css-script.css', array(), '1.6.5', false );
			wp_enqueue_script( 'wwp-script', plugin_dir_url( __DIR__ ) . 'assets/js/script.js', array(), '1.0.0', true );

			$wwp_wholesale_pricing = array(
				'product_id'             => get_the_id(),
				'product_type'             => ( wc_get_product( get_the_id() ) instanceof WC_Product ) ? wc_get_product( get_the_id() )->get_type() : null,
			);
			wp_localize_script( 'wwp-script', 'wwpscript', $wwp_wholesale_pricing );
		}
		public function wwp_li_icons() {
			echo '<style>.woocommerce-MyAccount-navigation ul li.woocommerce-MyAccount-navigation-link--upgrade-account a::before {content: "\f1de";}</style>'; 
		}
		public function wwp_upgrade_add_rewrite() {
			global $wp_rewrite;
			add_rewrite_endpoint( 'upgrade-account', EP_ROOT | EP_PAGES  ); 
			$wp_rewrite->flush_rules();
		}
		public function wwp_upgrade_add_var( $vars ) {
			$vars[] = 'upgrade-account';
			return $vars;
		}
		public function wwp_upgrade_add_menu_items( $items ) {
			$settings = get_option('wwp_wholesale_pricing_options', true);
			 
			if (isset($settings['upgrade_tab_text']) && !empty($settings['upgrade_tab_text'])) {
				$items['upgrade-account'] = $settings['upgrade_tab_text'];
			} else {
				$items['upgrade-account'] = esc_html__('Upgrade Account', 'woocommerce-wholesale-pricing'); 
			}
			
			return $items;
		}
		public function wwp_upgrade_content() {
			$this->wwp_account_content_callback();
		}
		public function wwp_account_content_callback() {
		
			$allowed_html = array(
				'script' => array(), // Allow <script> tags
				'form' => array(
					'method' => array(),
					'action' => array(),
					'enctype' => array(),
				),
				'input' => array(
					'type' => array(),
					'name' => array(),
					'id' => array(),
					'value' => array(),
					'class' => array(),
					'required' => array(),
				),
				'label' => array(),
				'p' => array(
					'class' => array(),
				),
				'div' => array(
					'class' => array(),
					'id' => array(),
				),
				'select' => array( // Add the <select> tag
					'name' => array(),
					'id' => array(),
					'class' => array(),
					'required' => array(),
				),
				'option' => array( // Add the <option> tag
					'value' => array(),
					'selected' => array(),
				),
				// Add other tags as needed
			);
			
		
			if ( is_user_logged_in() ) {
				$settings = get_option( 'wwp_wholesale_pricing_options', true );

					$user_id   = get_current_user_id();
					$user_info = get_userdata( $user_id );
					$user_role = $user_info->roles;
					$check     = '';
				if ( ! empty( $user_role ) ) {
					foreach ( $user_role as $key => $role ) {
						if ( term_exists( $role, 'wholesale_user_roles' ) ) {
							$check = 1;
							break;
						}
					}
				}
				
				if ( 'waiting' === get_user_meta( $user_id, '_user_status', true ) ) {

					/**
					* Filter
					* 
					* @since 1.0.0
					*/
					$notice = apply_filters( 'wwp_pending_msg', __( 'Your request for upgrade account is pending.', 'woocommerce-wholesale-pricing' ) );
					wc_print_notice( esc_html__( $notice, 'woocommerce-wholesale-pricing' ), 'success' );

				} elseif ( 'rejected' === get_user_meta( $user_id, '_user_status', true ) ) {

					if ( isset( $_POST['wwp_register_upgrade'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['wwp_register_upgrade'] ), 'wwp_wholesale_registrattion_nonce' ) ) {
						$post = $_POST;
					}

					if ( ! isset( $_POST['wwp_register_upgrade'] ) ) {
						wc_print_notice( __( 'Your upgrade request is rejected.', 'woocommerce-wholesale-pricing' ), 'error' );
						$rejected_note = get_user_meta( get_current_user_id(), 'rejected_note', true );
						echo '<p class="rejected_note">' . esc_html__( $rejected_note, 'woocommerce-wholesale-pricing' ) . '</p>';
					}

					if ( isset( $settings['request_again_submit'] ) && 'yes' === $settings['request_again_submit'] ) {

						$this->wwp_registration_insert( $user_id, $check, $settings );

						
						if ( ! isset( $_POST['wwp_register_upgrade'] ) ) {

							echo wp_kses($this->wwp_registration_form(), $allowed_html);
						}
					}
				} elseif ( 'active' === get_user_meta( $user_id, '_user_status', true ) ) {

					wc_print_notice( __( 'Your request is approved.', 'woocommerce-wholesale-pricing' ), 'success' );

				} elseif ( ! term_exists( get_user_meta( $user_id, 'wholesale_role_status', true ), 'wholesale_user_roles' ) ) {

					$this->wwp_registration_insert( $user_id, $check, $settings );

				}

				if ( get_user_meta( $user_id, '_user_status', true ) ) {
					$check = 1;
				}
				
				
				if ( empty( $check ) ) {
					global $wp;
					wc_print_notice( __( 'Apply here to upgrade your account.', 'woocommerce-wholesale-pricing' ), 'notice' );

					echo wp_kses($this->wwp_registration_form(), $allowed_html);


				}
			}
		}
		
		
		/**
		 * Method email_recipeint_bcc_cc
		 *
		 * @param $recipients 
		 * @param $id 
		 * @param $obj 
		 * @param $instance 
		 *
		 * @return void
		 */
		public function email_recipeint_bcc_cc( $recipients, $id, $obj, $instance ) {


			$obj      = '';
			$instance = '';
			$id = isset($id) ? $id : '';


			if ( 'new_order' !== $id ) {                  
				return $recipients;
			}
				

			if ( ! is_wholesaler_user( get_current_user_id() ) ) {
				return $recipients;
			}
							
			$settings = get_option( 'wwp_wholesale_pricing_options', true );

			if ( isset( $settings['emailuserrole'] ) && ( 'order_email_user_role' === $settings['emailuserrole'] || 'order_email_custom' === $settings['emailuserrole'] ) ) {

				$recipient ='';

				if ( 'order_email_user_role' === $settings['emailuserrole'] && ! empty( $settings['email_user_role_value'] ) ) {
					$manager_emails = array();
					$users          = get_users( array( 'role' => $settings['email_user_role_value'] ) );
					
					
					if ( count( $users ) > 0 ) {
						foreach ( $users as $user ) {
							$manager_emails[] = sanitize_email( $user->data->user_email );
						}
						if ( count( $manager_emails ) > 0 ) {
							$recipient = implode( ',', $manager_emails );
						}
					}

				} elseif ( ! empty( $settings['order_custom_email_value'] ) && 'order_email_custom' === $settings['emailuserrole'] ) {
					$recipient .= sanitize_text_field( $settings['order_custom_email_value'] );
				}
				if ('order_email_bcc'===$settings['order_email_cc_bcc']) {
					$recipients .= 'bcc: ' . $recipient . "\r\n";
				} else {
					$recipients .= 'cc: ' . $recipient . "\r\n";
				}

			}

			return $recipients;
		}
		
			//work editing 
		public function wwp_registration_insert( $user_id, $check, $settings ) {

			if ( isset( $_POST['wwp_register_upgrade'] ) && ! wp_verify_nonce( sanitize_text_field( $_POST['wwp_register_upgrade'] ), 'wwp_wholesale_registrattion_nonce' ) ) {

				if ( ! is_wp_error( $user_id ) ) {
					// Form builder fields udate in user meta
					 
					if ( isset( $_POST['wwp_wholesaler_fname'] ) ) {
						$billing_first_name = wc_clean( $_POST['wwp_wholesaler_fname'] );
						update_user_meta( $user_id, 'billing_first_name', $billing_first_name );
					}
					if ( isset( $_POST['wwp_wholesaler_lname'] ) ) {
						$billing_last_name = wc_clean( $_POST['wwp_wholesaler_lname'] );
						update_user_meta( $user_id, 'billing_last_name', $billing_last_name );
					}
					if ( isset( $_POST['wwp_wholesaler_company'] ) ) {
						$billing_company = wc_clean( $_POST['wwp_wholesaler_company'] );
						update_user_meta( $user_id, 'billing_company', $billing_company );
					}
					if ( isset( $_POST['wwp_wholesaler_address_line_1'] ) ) {
						$billing_address_1 = wc_clean( $_POST['wwp_wholesaler_address_line_1'] );
						update_user_meta( $user_id, 'billing_address_1', $billing_address_1 );
					}
					if ( isset( $_POST['wwp_wholesaler_address_line_2'] ) ) {
						$billing_address_2 = wc_clean( $_POST['wwp_wholesaler_address_line_2'] );
						update_user_meta( $user_id, 'billing_address_2', $billing_address_2 );
					}
					if ( isset( $_POST['wwp_wholesaler_city'] ) ) {
						$billing_city = wc_clean( $_POST['wwp_wholesaler_city'] );
						update_user_meta( $user_id, 'billing_city', $billing_city );
					}
					if ( isset( $_POST['wwp_wholesaler_state'] ) ) {
						$billing_state = wc_clean( $_POST['wwp_wholesaler_state'] );
						update_user_meta( $user_id, 'billing_state', $billing_state );
					}
					if ( isset( $_POST['wwp_wholesaler_post_code'] ) ) {
						$billing_postcode = wc_clean( $_POST['wwp_wholesaler_post_code'] );
						update_user_meta( $user_id, 'billing_postcode', $billing_postcode );
					}
					if ( isset( $_POST['billing_country'] ) ) {
						$billing_country = wc_clean( $_POST['billing_country'] );
						update_user_meta( $user_id, 'billing_country', $billing_country );
					}
					if ( isset( $_POST['wwp_wholesaler_phone'] ) ) {
						$billing_phone = wc_clean( $_POST['wwp_wholesaler_phone'] );
						update_user_meta( $user_id, 'billing_phone', $billing_phone );
					}
					if ( isset( $_POST['wwp_wholesaler_tax_id'] ) ) {
						$wwp_wholesaler_tax_id = wc_clean( $_POST['wwp_wholesaler_tax_id'] );
						update_user_meta( $user_id, 'wwp_wholesaler_tax_id', $wwp_wholesaler_tax_id );
					}
					if ( isset( $_POST['wwp_custom_field_1'] ) ) {
						$custom_field = wc_clean( $_POST['wwp_custom_field_1'] );
						update_user_meta( $user_id, 'wwp_custom_field_1', $custom_field );
					}
					if ( isset( $_POST['wwp_custom_field_2'] ) ) {
						$custom_field = wc_clean( $_POST['wwp_custom_field_2'] );
						update_user_meta( $user_id, 'wwp_custom_field_2', $custom_field );
					}
					if ( isset( $_POST['wwp_custom_field_3'] ) ) {
						$custom_field = wc_clean( $_POST['wwp_custom_field_3'] );
						update_user_meta( $user_id, 'wwp_custom_field_3', $custom_field );
					}
					if ( isset( $_POST['wwp_custom_field_4'] ) ) {
						$custom_field = wc_clean( $_POST['wwp_custom_field_4'] );
						update_user_meta( $user_id, 'wwp_custom_field_4', $custom_field );
					}
					if ( isset( $_POST['wwp_custom_field_5'] ) ) {
						$custom_field = wc_clean( $_POST['wwp_custom_field_5'] );
						update_user_meta( $user_id, 'wwp_custom_field_5', $custom_field );
					}
					if ( isset( $_POST['wwp_form_data_json'] ) ) {
						$wwp_form_data_json = wc_clean( $_POST['wwp_form_data_json'] );
						update_user_meta( $user_id, 'wwp_form_data_json', $wwp_form_data_json );
					}

					if ( isset( $_POST['wwp_wholesaler_copy_billing_address'] ) ) {
						$wwp_wholesaler_copy_billing_address = wc_clean( $_POST['wwp_wholesaler_copy_billing_address'] );
					}
					if ( isset( $wwp_wholesaler_copy_billing_address ) ) {
						if ( isset( $_POST['wwp_wholesaler_fname'] ) ) {
							$billing_first_name = wc_clean( $_POST['wwp_wholesaler_fname'] );
							update_user_meta( $user_id, 'shipping_first_name', $billing_first_name );
						}
						if ( isset( $_POST['wwp_wholesaler_lname'] ) ) {
							$billing_last_name = wc_clean( $_POST['wwp_wholesaler_lname'] );
							update_user_meta( $user_id, 'shipping_last_name', $billing_last_name );
						}
						if ( isset( $_POST['wwp_wholesaler_company'] ) ) {
							$billing_company = wc_clean( $_POST['wwp_wholesaler_company'] );
							update_user_meta( $user_id, 'shipping_company', $billing_company );
						}
						if ( isset( $_POST['wwp_wholesaler_address_line_1'] ) ) {
							$billing_address_1 = wc_clean( $_POST['wwp_wholesaler_address_line_1'] );
							update_user_meta( $user_id, 'shipping_address_1', $billing_address_1 );
						}
						if ( isset( $_POST['wwp_wholesaler_address_line_2'] ) ) {
							$billing_address_2 = wc_clean( $_POST['wwp_wholesaler_address_line_2'] );
							update_user_meta( $user_id, 'shipping_address_2', $billing_address_2 );
						}
						if ( isset( $_POST['wwp_wholesaler_city'] ) ) {
							$billing_city = wc_clean( $_POST['wwp_wholesaler_city'] );
							update_user_meta( $user_id, 'shipping_city', $billing_city );
						}
						if ( isset( $_POST['wwp_wholesaler_state'] ) ) {
							$billing_state = wc_clean( $_POST['wwp_wholesaler_state'] );
							update_user_meta( $user_id, 'shipping_state', $billing_state );
						}
						if ( isset( $_POST['wwp_wholesaler_post_code'] ) ) {
							$billing_postcode = wc_clean( $_POST['wwp_wholesaler_post_code'] );
							update_user_meta( $user_id, 'shipping_postcode', $billing_postcode );
						}
						if ( isset( $_POST['billing_country'] ) ) {
							$shipping_country = wc_clean( $_POST['billing_country'] );
							update_user_meta( $user_id, 'shipping_country', $shipping_country );
						}
					} else {
						if ( isset( $_POST['wwp_wholesaler_shipping_fname'] ) ) {
							$shipping_first_name = wc_clean( $_POST['wwp_wholesaler_shipping_fname'] );
							update_user_meta( $user_id, 'shipping_first_name', $shipping_first_name );
						}
						if ( isset( $_POST['wwp_wholesaler_shipping_lname'] ) ) {
							$shipping_last_name = wc_clean( $_POST['wwp_wholesaler_shipping_lname'] );
							update_user_meta( $user_id, 'shipping_last_name', $shipping_last_name );
						}
						if ( isset( $_POST['wwp_wholesaler_shipping_company'] ) ) {
							$shipping_company = wc_clean( $_POST['wwp_wholesaler_shipping_company'] );
							update_user_meta( $user_id, 'shipping_company', $shipping_company );
						}
						if ( isset( $_POST['wwp_wholesaler_shipping_address_line_1'] ) ) {
							$shipping_address_1 = wc_clean( $_POST['wwp_wholesaler_shipping_address_line_1'] );
							update_user_meta( $user_id, 'shipping_address_1', $shipping_address_1 );
						}
						if ( isset( $_POST['wwp_wholesaler_shipping_address_line_2'] ) ) {
							$shipping_address_2 = wc_clean( $_POST['wwp_wholesaler_shipping_address_line_2'] );
							update_user_meta( $user_id, 'shipping_address_2', $shipping_address_2 );
						}
						if ( isset( $_POST['wwp_wholesaler_shipping_city'] ) ) {
							$shipping_city = wc_clean( $_POST['wwp_wholesaler_shipping_city'] );
							update_user_meta( $user_id, 'shipping_city', $shipping_city );
						}
						if ( isset( $_POST['wwp_wholesaler_shipping_state'] ) ) {
							$shipping_state = wc_clean( $_POST['wwp_wholesaler_shipping_state'] );
							update_user_meta( $user_id, 'shipping_state', $shipping_state );
						}
						if ( isset( $_POST['wwp_wholesaler_shipping_post_code'] ) ) {
							$shipping_postcode = wc_clean( $_POST['wwp_wholesaler_shipping_post_code'] );
							update_user_meta( $user_id, 'shipping_postcode', $shipping_postcode );
						}
						if ( isset( $_POST['shipping_country'] ) ) {
							$shipping_country = wc_clean( $_POST['shipping_country'] );
							update_user_meta( $user_id, 'shipping_country', $shipping_country );
						}
					}
					$id = wp_insert_post(
						array(
							'post_type'   => 'wwp_requests',
							'post_title'  => get_userdata( get_current_user_id() )->data->user_nicename . ' - ' . get_current_user_id() . ' - Upgrade Request',
							'post_status' => 'publish',
						)
					);
					if ( ! is_wp_error( $id ) ) {

						update_post_meta( $id, '_user_id', $user_id );
						if ( 'no' === $settings['disable_auto_role'] ) {
							update_post_meta( $id, '_user_status', 'active' );
							update_user_meta( $user_id, '_user_status', 'active' );
							
							wp_set_object_terms( $id, 'wwp_wholesaler', 'wholesale_user_roles', true );

							$u        = new WP_User( $user_id );
							$wp_roles = new WP_Roles();
							$names    = $wp_roles->get_names();
							foreach ( $names as $key => $value ) {
								$u->remove_role( $key );
							}
							$u->add_role( 'wwp_wholesaler' );

							if ( ! empty( $role ) ) {
								/**
								 * Hook
								 * 
								 * @since 1.0.0
								 */
								do_action( 'wwp_wholesale_user_request_approved', $user_id );
								update_post_meta( $id, '_approval_notification', 'sent' );
							}
						} else {
							update_post_meta( $id, '_user_status', 'waiting' );
							update_user_meta( $user_id, '_user_status', 'waiting' );
						}

						/**
						* Hook
						* 
						* @since 1.0.0
						*/
						do_action( 'wwp_wholesale_new_request_submitted', $user_id );
					}
					// On success
					if ( ! is_wp_error( $user_id ) ) {
						/**
						* Filter
						* 
						* @since 1.0.0
						*/
						$notice = apply_filters( 'wwp_success_msg', esc_html__( 'Your request for upgrade account is submitted.', 'woocommerce-wholesale-pricing' ) );
						wc_print_notice( esc_html__( $notice, 'woocommerce-wholesale-pricing' ), 'success' );
					} else {
						/**
						* Filter
						* 
						* @since 1.0.0
						*/
						$notice = apply_filters( 'wwp_error_msg', esc_html__( $user_id->get_error_message(), 'woocommerce-wholesale-pricing' ) );
						wc_print_notice( esc_html__( $notice, 'woocommerce-wholesale-pricing' ), 'error' );
					}
					wp_safe_redirect( wp_get_referer() );
				}
				$check = 1;
			}
		}

		public function hasYesValueExcludingKeys( $array, $groupPrefix, $excludeKeys = array( 'custommer_shipping_address', 'custommer_billing_address' ) ) {
			foreach ($array as $key => $value) {
				if (in_array($key, $excludeKeys)) {
					continue;
				}
				if (strpos($key, $groupPrefix) === 0 && 'yes' === $value ) {
					return true;
				}
			}
			return false;
		}

		public function wwp_registration_form() {
			 
			global $woocommerce;
			$countries_obj = new WC_Countries();
			$countries     = $countries_obj->__get('countries');
			
			$username      = '';
			$email         = '';
			$fname         = '';
			$lname         = '';
			$company       = '';
			$addr1         = '';
			$settings      =get_option('wwp_wholesale_pricing_options', true);
			$registrations = get_option('wwp_wholesale_registration_options');
			ob_start();
			?>
			<div class="wwp_wholesaler_registration">
			
				<form method="post" action="" enctype="multipart/form-data">
					<?php wp_nonce_field('wwp_wholesale_registrattion_nonce', 'wwp_wholesale_registrattion_nonce'); ?>
					<?php 
					if ( empty($registrations) || ( isset($registrations['custommer_billing_address']) && 'yes' === $registrations['custommer_billing_address'] && $this->hasYesValueExcludingKeys($registrations, 'enable_billing_') ) ) { 
						?>
						<h2><?php esc_html_e('Customer billing address', 'woocommerce-wholesale-pricing'); ?></h2>
						<?php 
						if ( isset($registrations['enable_billing_first_name']) && 'yes' === $registrations['enable_billing_first_name'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_fname"> <?php echo !empty($registrations['billing_first_name']) ? esc_html($registrations['billing_first_name']) : esc_html__('First Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_fname" id="wwp_wholesaler_fname" value="<?php esc_attr_e($fname); ?>" required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_last_name']) && 'yes' === $registrations['enable_billing_last_name'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_lname"><?php echo !empty($registrations['billing_last_name']) ? esc_html($registrations['billing_last_name']) : esc_html__('Last Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_lname" id="wwp_wholesaler_lname" value="<?php esc_attr_e($lname); ?>" required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_company']) && 'yes' === $registrations['enable_billing_company'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_fname"><?php echo !empty($registrations['billing_company']) ? esc_html($registrations['billing_company']) : esc_html__('Company', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_company" id="wwp_wholesaler_company" value="<?php esc_attr_e($company); ?>"  required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_address_1']) && 'yes' === $registrations['enable_billing_address_1'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_address_line_1"><?php echo !empty($registrations['billing_address_1']) ? esc_html($registrations['billing_address_1']) : esc_html__('Address line 1', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_address_line_1" id="wwp_wholesaler_address_line_1" value="<?php esc_attr_e($addr1); ?>" required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_address_2']) && 'yes' === $registrations['enable_billing_address_2'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_address_line_2"><?php echo !empty($registrations['billing_address_2']) ? esc_html($registrations['billing_address_2']) : esc_html__('Address line 2', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_address_line_2" id="wwp_wholesaler_address_line_2" required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_city']) && 'yes' === $registrations['enable_billing_city'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_city"><?php echo !empty($registrations['billing_city']) ? esc_html($registrations['billing_city']) : esc_html__('City', 'woocommerce-wholesale-pricing'); ?><span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_city" id="wwp_wholesaler_city" required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_post_code']) && 'yes' === $registrations['enable_billing_post_code'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_post_code"><?php echo !empty($registrations['billing_post_code']) ? esc_html($registrations['billing_post_code']) : esc_html__('Postcode / ZIP', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_post_code" id="wwp_wholesaler_post_code" required>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_country']) && 'yes' === $registrations['enable_billing_country'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<?php
									woocommerce_form_field(
										'billing_country', array(
											'type'       => 'select',
											'class'      => array( 'chzn-drop' ),
											'label'      => esc_html__('Select billing country', 'woocommerce-wholesale-pricing'),
											'placeholder'=> esc_html__('Enter something', 'woocommerce-wholesale-pricing'),
											'options'    => $countries,
										)
									);
							?>

			
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_state']) && 'yes' === $registrations['enable_billing_state'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_state"><?php echo !empty($registrations['billing_state']) ? esc_html($registrations['billing_state']) : esc_html__('State / County', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_state" id="wwp_wholesaler_state" required>
								<span for="wwp_wholesaler_state"><?php esc_html_e('State / County or state code', 'woocommerce-wholesale-pricing'); ?></span>
							</p>
							<?php
						}
						if ( isset($registrations['enable_billing_phone']) && 'yes' === $registrations['enable_billing_phone'] ) { 
							?>
							<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<label for="wwp_wholesaler_phone"><?php echo !empty($registrations['billing_phone']) ? esc_html($registrations['billing_phone']) : esc_html__('Phone', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_phone" id="wwp_wholesaler_phone" required>
							</p>
							<?php
						}
					}
					if ( empty($registrations) || ( isset($registrations['custommer_shipping_address']) && 'yes' === $registrations['custommer_shipping_address']  && $this->hasYesValueExcludingKeys($registrations, 'enable_shipping_') ) ) { 
						?>
						<h2><?php esc_html_e('Customer shipping address', 'woocommerce-wholesale-pricing'); ?></h2>
						<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
							<label for="wwp_wholesaler_copy_billing_address"><?php esc_html_e('Copy from billing address', 'woocommerce-wholesale-pricing'); ?></label>
							<input type="checkbox" name="wwp_wholesaler_copy_billing_address" id="wwp_wholesaler_copy_billing_address" value="yes" >
						</p>
						<div id="wholesaler_shipping_address"> 
							<?php 
							if ( isset($registrations['enable_shipping_first_name']) && 'yes' === $registrations['enable_shipping_first_name'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_lname"><?php echo !empty($registrations['shipping_first_name']) ? esc_html($registrations['shipping_first_name']) : esc_html__('First Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_fname" id="wwp_wholesaler_shipping_fname" >
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_last_name']) && 'yes' === $registrations['enable_shipping_last_name'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_fname"> <?php echo !empty($registrations['shipping_last_name']) ? esc_html($registrations['shipping_last_name']) : esc_html__('Last Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span> </label>
									<input type="text" name="wwp_wholesaler_shipping_lname" id="wwp_wholesaler_shipping_lname" >
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_company']) && 'yes' === $registrations['enable_shipping_company'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_company"><?php echo !empty($registrations['shipping_company']) ? esc_html($registrations['shipping_company']) : esc_html__('Company', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_company" id="wwp_wholesaler_shipping_company" >
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_address_1']) && 'yes' === $registrations['enable_shipping_address_1'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_address_line_1"><?php echo !empty($registrations['shipping_address_1']) ? esc_html($registrations['shipping_address_1']) : esc_html__('Address line 1', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_address_line_1" id="wwp_wholesaler_shipping_address_line_1" >
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_address_2']) && 'yes' === $registrations['enable_shipping_address_2'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_address_line_2"><?php echo !empty($registrations['shipping_address_2']) ? esc_html($registrations['shipping_address_2']) : esc_html__('Address line 2', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_address_line_2" id="wwp_wholesaler_shipping_address_line_2" >
								</p>
								<?php 
							}
							if ( isset($registrations['enable_shipping_city']) && 'yes' === $registrations['enable_shipping_city'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_city"><?php echo !empty($registrations['shipping_city']) ? esc_html($registrations['shipping_city']) : esc_html__('City', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_city" id="wwp_wholesaler_shipping_city" >
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_post_code']) && 'yes' === $registrations['enable_shipping_post_code'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_post_code"><?php echo !empty($registrations['shipping_post_code']) ? esc_html($registrations['shipping_post_code']) : esc_html__('Postcode / ZIP', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_post_code" id="wwp_wholesaler_shipping_post_code">
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_country']) && 'yes' === $registrations['enable_shipping_country'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
								<?php
									woocommerce_form_field( 'shipping_country', 
									array(
										'type'       => 'select',
										'class'      => array( 'chzn-drop' ),
										'label'      => esc_html__('Select shipping country', 'woocommerce-wholesale-pricing'),
										'placeholder'=> esc_html__('Enter something', 'woocommerce-wholesale-pricing'),
										'options'    => $countries,
									)
									);
								?>
								</p>
								<?php
							}
							if ( isset($registrations['enable_shipping_state']) && 'yes' === $registrations['enable_shipping_state'] ) { 
								?>
								<p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
									<label for="wwp_wholesaler_shipping_state"><?php echo !empty($registrations['shipping_state']) ? esc_html($registrations['shipping_state']) : esc_html__('State / County', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_state" id="wwp_wholesaler_shipping_state">
								</p>
								<?php
							} 
							?>
						</div>
						<?php
					}
					 
					?>
					<p class="woocomerce-FormRow form-row">                   
						<input type="submit" class="woocommerce-Button button" name="wwp_register_upgrade" value="<?php esc_html_e('Register', 'woocommerce-wholesale-pricing'); ?>">
					</p>
				</form>
				<script type="">
				jQuery('#wwp_wholesaler_copy_billing_address').change(
					function () {
							if (!this.checked) {
								jQuery('#wholesaler_shipping_address').fadeIn('slow');
							} else {
								jQuery('#wholesaler_shipping_address').fadeOut('slow');
							}
						}
				);
				</script>
			</div>
			<?php 
			return ob_get_clean();
		}
		

		/**
		 * Method wwp_available_payment_gateways
		 *
		 * @param $available_gateways array
		 *
		 * @return array
		 */
		public function wwp_available_payment_gateways( $available_gateways ) {
			if ( is_user_logged_in() ) {
				$user_info  = get_userdata( get_current_user_id() );
				$user_roles = (array) $user_info->roles;
				if ( empty( $user_roles ) ) {
					return $available_gateways;
				}
				if ( isset( $user_roles[0] ) ) {
					$term = get_term_by( 'slug', $user_roles[0], 'wholesale_user_roles' );

					if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
						$restricted = get_term_meta( $term->term_id, 'wwp_restricted_pmethods_wholesaler', true );
						if ( ! empty( $restricted ) && ! empty( $available_gateways ) ) {
							foreach ( $restricted as $restrict ) {
								unset( $available_gateways[ $restrict ] );
							}
						}
					}
				}
			}
			return $available_gateways;
		}
		
		/**
		 * Method wwp_restrict_shipping_wholesaler
		 *
		 * @param $rates $rates [explicite description]
		 * @param $package array
		 *
		 * @return array
		 */
		public function wwp_restrict_shipping_wholesaler( $rates, $package ) {
			$package_rate = $package;
			if ( is_user_logged_in() ) {
				if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
					return $rates;
				}
				$user_info  = get_userdata( get_current_user_id() );
				$user_roles = (array) $user_info->roles;
				if ( empty( $user_roles ) ) {
					return $rates;
				}
				// $term = get_term_by( 'slug', $user_roles[0], 'wholesale_user_roles' );
				
				if ( ! empty( $user_roles ) && isset( $user_roles[0] ) ) {
					$term = get_term_by( 'slug', $user_roles[0], 'wholesale_user_roles' );
				
					if ( ! empty( $term ) && ! is_wp_error( $term ) ) {
						$restricted = get_term_meta( $term->term_id, 'wwp_restricted_smethods_wholesaler', true );
						if ( ! empty( $restricted ) && ! empty( $rates ) ) {
							foreach ( $rates as $rate_key => $rate ) {
								if ( in_array( $rate->method_id, $restricted, true ) ) {
									unset( $rates[ $rate_key ] );
								}
							}
						}
					}
				}
			}


			return $rates;
		}
	}
	new Wwp_Wholesale_Pricing_Frontend();
}
