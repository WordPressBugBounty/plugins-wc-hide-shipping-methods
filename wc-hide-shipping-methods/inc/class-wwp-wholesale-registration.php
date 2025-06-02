<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

/**
 * Class Woo_Wholesale_Registration
 */
if ( !class_exists('Wwp_Wholesale_Pricing_Registration') ) {

	class Wwp_Wholesale_Pricing_Registration {
		public $errors = array();
		public function __construct() {
			add_shortcode('wwp_registration_form', array( $this, 'wwp_registration_form' ));
			add_action( 'woocommerce_register_form_end', array( $this, 'wwp_wholesale_registeration' ) );
			$this->registratio_process();
		}
		public function registratio_process() { 

			if ( isset($_POST['wwp_register']) ) {
				if ( isset($_POST['wwp_wholesale_registrattion_nonce']) || wp_verify_nonce( wc_clean($_POST['wwp_wholesale_registrattion_nonce']), 'wwp_wholesale_registrattion_nonce') ) {
					$this->errors = $this->wwp_register_wholesaler();
				 
				}
			}
		}
		public function wwp_registration_form() {

			$data = $_REQUEST;

			if ( !is_admin() && is_user_logged_in() ) {
				return esc_html__('You are already registered!', 'woocommerce-wholesale-pricing');
			}
	
			global $woocommerce;
			$countries_obj   = new WC_Countries();
			$countries       = $countries_obj->__get('countries');
			$default_country = $countries_obj->get_base_country();
			$errors          =array();
			ob_start();
			if ( isset($data['wwp_register']) ) {
				$errors = $this->errors;
			}
			$username = '';
			$email    = '';
			$fname    = '';
			$lname    = '';
			$company  = '';
			$addr1    = '';
			if ( !empty($errors) ) {
				echo '<ul class="woocommerce-error" role="alert">';
				foreach ( $errors as $key => $error ) {
					echo '<li>' . wp_kses_post($error) . '</li>';
				}
				echo '</ul>';
			}
			$username                               = isset( $data['wwp_wholesaler_username'] ) ? wc_clean($data['wwp_wholesaler_username']) : '';
			$email                                  = isset( $data['wwp_wholesaler_email'] ) ? wc_clean($data['wwp_wholesaler_email']) : '';
			$fname                                  = isset( $data['wwp_wholesaler_fname'] ) ? wc_clean($data['wwp_wholesaler_fname']) : '';
			$lname                                  = isset( $data['wwp_wholesaler_lname'] ) ? wc_clean($data['wwp_wholesaler_lname']) : '';
			$company                                = isset( $data['wwp_wholesaler_company'] ) ? wc_clean($data['wwp_wholesaler_company']) : '';
			$addr1                                  = isset( $data['wwp_wholesaler_address_line_1'] ) ? wc_clean($data['wwp_wholesaler_address_line_1']) : '';
			$wwp_wholesaler_address_line_2          = isset( $data['wwp_wholesaler_address_line_2'] ) ? wc_clean($data['wwp_wholesaler_address_line_2']) : '';
			$wwp_wholesaler_city                    = isset( $data['wwp_wholesaler_city'] ) ? wc_clean($data['wwp_wholesaler_city']) : '';
			$wwp_wholesaler_post_code               = isset( $data['wwp_wholesaler_post_code'] ) ? wc_clean($data['wwp_wholesaler_post_code']) : '';
			$billing_country                        = isset( $data['billing_country'] ) ? wc_clean($data['billing_country']) : '';
			$wwp_wholesaler_state                   = isset( $data['wwp_wholesaler_state'] ) ? wc_clean($data['wwp_wholesaler_state']) : '';
			$wwp_wholesaler_phone                   = isset( $data['wwp_wholesaler_phone'] ) ? wc_clean($data['wwp_wholesaler_phone']) : '';
			$wwp_wholesaler_shipping_fname          = isset( $data['wwp_wholesaler_shipping_fname'] ) ? wc_clean($data['wwp_wholesaler_shipping_fname']) : '';
			$wwp_wholesaler_shipping_lname          = isset( $data['wwp_wholesaler_shipping_lname'] ) ? wc_clean($data['wwp_wholesaler_shipping_lname']) : '';               
			$wwp_wholesaler_shipping_company        = isset( $data['wwp_wholesaler_shipping_company'] ) ? wc_clean($data['wwp_wholesaler_shipping_company']) : '';             
			$wwp_wholesaler_shipping_address_line_1 = isset( $data['wwp_wholesaler_shipping_address_line_1'] ) ? wc_clean($data['wwp_wholesaler_shipping_address_line_1']) : '';                
			$wwp_wholesaler_shipping_address_line_2 = isset( $data['wwp_wholesaler_shipping_address_line_2'] ) ? wc_clean($data['wwp_wholesaler_shipping_address_line_2']) : '';                
			$wwp_wholesaler_shipping_city           = isset( $data['wwp_wholesaler_shipping_city'] ) ? wc_clean($data['wwp_wholesaler_shipping_city']) : '';              
			$wwp_wholesaler_shipping_post_code      = isset( $data['wwp_wholesaler_shipping_post_code'] ) ? wc_clean($data['wwp_wholesaler_shipping_post_code']) : '';               
			$wwp_wholesaler_shipping_post_code      = isset( $data['wwp_wholesaler_shipping_post_code'] ) ? wc_clean($data['wwp_wholesaler_shipping_post_code']) : '';               
			$wwp_wholesaler_shipping_state          = isset( $data['wwp_wholesaler_shipping_state'] ) ? wc_clean($data['wwp_wholesaler_shipping_state']) : '';
			
			$wwp_wholesaler_tax_id = isset( $data['wwp_wholesaler_tax_id'] ) ? wc_clean($data['wwp_wholesaler_tax_id']) : '';
			if (isset( $data['billing_country'] )) {
				$default_country = wc_clean($data['billing_country']);
			}
			if (isset( $data['shipping_country'] )) {
				$default_country = wc_clean($data['shipping_country']);
			}
			
			$settings      =get_option('wwp_wholesale_pricing_options', true);
			$registrations = get_option('wwp_wholesale_registration_options');

			?>
			<div class="wwp_wholesaler_registration">
				<h2><?php esc_html_e('Registration', 'woocommerce-wholesale-pricing'); ?></h2>
				<form method="POST" action="">
					
					<?php
					/**
					 * Hook
					 *
					 * @since 1.0.0
					 */ 
					do_action( 'wwp_wholesaler_registration_fields_start', $registrations, $settings );
					// wp_nonce_field('wwp_wholesale_registrattion_nonce', 'wwp_wholesale_registrattion_nonce'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					$nonce = wp_create_nonce('wwp_wholesale_registrattion_nonce');
					?>
					<input type="hidden" name="wwp_wholesale_registrattion_nonce" value="<?php echo esc_attr($nonce); ?>">
					<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
						<label for="wwp_wholesaler_username"><?php esc_html_e('Username', 'woocommerce-wholesale-pricing'); ?><span class="required">*</span></label>
						<input type="text" name="wwp_wholesaler_username" id="wwp_wholesaler_username" value="<?php esc_attr_e($username); ?>" required>
					</<?php wwp_elements('p'); ?>>
					
					<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
						<label for="wwp_wholesaler_email"><?php esc_html_e('Email', 'woocommerce-wholesale-pricing'); ?><span class="required">*</span></label>
						<input type="email" name="wwp_wholesaler_email" id="wwp_wholesaler_email" value="<?php esc_attr_e($email); ?>" required>
					</<?php wwp_elements('p'); ?>>
					
					<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
						<label for="wwp_wholesaler_password"><?php esc_html_e('Password', 'woocommerce-wholesale-pricing'); ?><span class="required">*</span></label>
						<input type="password" name="wwp_wholesaler_password" id="wwp_wholesaler_password"  minlength="8" required>  
					</<?php wwp_elements('p'); ?>>
					
					<?php 
					if ( empty($registrations) || ( isset($registrations['custommer_billing_address']) && 'yes' === $registrations['custommer_billing_address'] ) ) { 
						?>
						<h2><?php esc_html_e('Customer billing address', 'woocommerce-wholesale-pricing'); ?></h2>
						<?php 
						if ( isset($registrations['enable_billing_first_name']) && 'yes' === $registrations['enable_billing_first_name'] ) {  
							?>
							<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
								<label for="wwp_wholesaler_fname"> <?php echo !empty($registrations['billing_first_name']) ? esc_html__(__($registrations['billing_first_name']), 'woocommerce-wholesale-pricing') : esc_html__('First Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_fname" id="wwp_wholesaler_fname" value="<?php esc_attr_e($fname); ?>" required>
							</<?php wwp_elements('p'); ?>>

							<?php
						}
						if ( isset($registrations['enable_billing_last_name']) && 'yes' === $registrations['enable_billing_last_name'] ) { 
							?>
							<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
								<label for="wwp_wholesaler_lname"><?php echo !empty($registrations['billing_last_name']) ? esc_html__(__($registrations['billing_last_name']), 'woocommerce-wholesale-pricing') : esc_html__('Last Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_lname" id="wwp_wholesaler_lname" value="<?php esc_attr_e($lname); ?>" required>
							</<?php wwp_elements('p'); ?>>
							
							<?php
						}
						if ( isset($registrations['enable_billing_company']) && 'yes' === $registrations['enable_billing_company'] ) { 
							?>
							<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
								<label for="wwp_wholesaler_company"><?php echo !empty($registrations['billing_company']) ? esc_html__(__($registrations['billing_company']), 'woocommerce-wholesale-pricing') : esc_html__('Company', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_company" id="wwp_wholesaler_company" value="<?php esc_attr_e($company); ?>"  required>
							</<?php wwp_elements('p'); ?>>
							
							<?php
						}
						if ( isset($registrations['enable_billing_address_1']) && 'yes' === $registrations['enable_billing_address_1'] ) { 
							?>
							<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
								<label for="wwp_wholesaler_address_line_1"><?php echo !empty($registrations['billing_address_1']) ? esc_html__(__($registrations['billing_address_1']), 'woocommerce-wholesale-pricing') : esc_html__('Address line 1', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_address_line_1" id="wwp_wholesaler_address_line_1" value="<?php esc_attr_e($addr1); ?>" required>
							</<?php wwp_elements('p'); ?>>
							
							<?php
						}
						if ( isset($registrations['enable_billing_address_2']) && 'yes' === $registrations['enable_billing_address_2'] ) { 
							?>
							<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
								<label for="wwp_wholesaler_address_line_2"><?php echo !empty($registrations['billing_address_2']) ? esc_html__(__($registrations['billing_address_2']), 'woocommerce-wholesale-pricing') : esc_html__('Address line 2 (optional)', 'woocommerce-wholesale-pricing'); ?></label>
								<input type="text" name="wwp_wholesaler_address_line_2" id="wwp_wholesaler_address_line_2" value="<?php esc_attr_e($wwp_wholesaler_address_line_2); ?>">
							</<?php wwp_elements('p'); ?>>
							
							<?php
						}
						if ( isset($registrations['enable_billing_city']) && 'yes' === $registrations['enable_billing_city'] ) { 
							?>
							<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
								<label for="wwp_wholesaler_city"><?php echo !empty($registrations['billing_city']) ? esc_html__(__($registrations['billing_city']), 'woocommerce-wholesale-pricing') : esc_html__('City', 'woocommerce-wholesale-pricing'); ?><span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_city" id="wwp_wholesaler_city" value="<?php esc_attr_e($wwp_wholesaler_city); ?>" required>
							</<?php wwp_elements('p'); ?>>
							
							<?php
						}
						if ( isset($registrations['enable_billing_post_code']) && 'yes' === $registrations['enable_billing_post_code'] ) { 
							?>
							<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
								<label for="wwp_wholesaler_post_code"><?php echo !empty($registrations['billing_post_code']) ? esc_html__(__($registrations['billing_post_code']), 'woocommerce-wholesale-pricing') : esc_html__('Postcode / ZIP', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_post_code" id="wwp_wholesaler_post_code" value="<?php esc_attr_e($wwp_wholesaler_post_code); ?>" required>
							</<?php wwp_elements('p'); ?>>
							
							<?php
						}
						if ( isset($registrations['enable_billing_country']) && 'yes' === $registrations['enable_billing_country'] ) { 
							?>
							<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
							<?php
								woocommerce_form_field(
									'billing_country', array(
										'type'       => 'select',
										'class'      => array( 'chzn-drop' ),
										'label'      => esc_html__('Select billing country', 'woocommerce-wholesale-pricing'),
										'placeholder'=> esc_html__('Enter something', 'woocommerce-wholesale-pricing'),
										'default'    => $default_country,
										'options'    => $countries,
										'required'   => true,
									)
								);
							?>
							</<?php wwp_elements('p'); ?>>
							
							<?php
						}
						if ( isset($registrations['enable_billing_state']) && 'yes' === $registrations['enable_billing_state'] ) { 
							?>
							<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
								<label for="wwp_wholesaler_state"><?php echo !empty($registrations['billing_state']) ? esc_html__(__($registrations['billing_state']), 'woocommerce-wholesale-pricing') : esc_html__('State / County or state code', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_state" id="wwp_wholesaler_state" value="<?php esc_attr_e($wwp_wholesaler_state); ?>" required>
							<!--	<<?php wwp_elements('p'); ?> for="wwp_wholesaler_state"><?php esc_html_e('State / County or state code', 'woocommerce-wholesale-pricing'); ?></<?php wwp_elements('p'); ?>>--->
							</<?php wwp_elements('p'); ?>>
							
							<?php
						}
						if ( isset($registrations['enable_billing_phone']) && 'yes' === $registrations['enable_billing_phone'] ) { 
							?>
							<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
								<label for="wwp_wholesaler_phone"><?php echo !empty($registrations['billing_phone']) ? esc_html__(__($registrations['billing_phone']), 'woocommerce-wholesale-pricing') : esc_html__('Phone', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
								<input type="text" name="wwp_wholesaler_phone" id="wwp_wholesaler_phone" value="<?php esc_attr_e($wwp_wholesaler_phone); ?>"  required>
							</<?php wwp_elements('p'); ?>>
							
							<?php
						}
					}
					if ( empty($registrations) || ( isset($registrations['custommer_shipping_address']) && 'yes' === $registrations['custommer_shipping_address'] ) ) { 
						?>
						<h2><?php esc_html_e('Customer shipping address', 'woocommerce-wholesale-pricing'); ?></h2>
						<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
							<label for="wwp_wholesaler_copy_billing_address"><?php esc_html_e('Copy from billing address', 'woocommerce-wholesale-pricing'); ?></label>
							<input type="checkbox" name="wwp_wholesaler_copy_billing_address" id="wwp_wholesaler_copy_billing_address" value="yes" >
						</<?php wwp_elements('p'); ?>>
						<div id="wholesaler_shipping_address"> 
							<?php 
							if ( isset($registrations['enable_shipping_first_name']) && 'yes' === $registrations['enable_shipping_first_name'] ) { 
								?>
								<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
									<label for="wwp_wholesaler_shipping_lname"><?php echo !empty($registrations['shipping_first_name']) ? esc_html__(__($registrations['shipping_first_name']), 'woocommerce-wholesale-pricing') : esc_html__('First Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_fname" id="wwp_wholesaler_shipping_fname"value="<?php esc_attr_e($wwp_wholesaler_shipping_fname); ?>"  >
								</<?php wwp_elements('p'); ?>>
								
								<?php
							}
							if ( isset($registrations['enable_shipping_last_name']) && 'yes' === $registrations['enable_shipping_last_name'] ) { 
								?>
								<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
									<label for="wwp_wholesaler_shipping_fname"> <?php echo !empty($registrations['shipping_last_name']) ? esc_html__(__($registrations['shipping_last_name']), 'woocommerce-wholesale-pricing') : esc_html__('Last Name', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span> </label>
									<input type="text" name="wwp_wholesaler_shipping_lname" id="wwp_wholesaler_shipping_lname"value="<?php esc_attr_e($wwp_wholesaler_shipping_lname); ?>" >
								</<?php wwp_elements('p'); ?>>
								
								<?php
							}
							if ( isset($registrations['enable_shipping_company']) && 'yes' === $registrations['enable_shipping_company'] ) { 
								?>
								<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
									<label for="wwp_wholesaler_shipping_company"><?php echo !empty($registrations['shipping_company']) ? esc_html__(__($registrations['shipping_company']), 'woocommerce-wholesale-pricing') : esc_html__('Company', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_company" id="wwp_wholesaler_shipping_company" value="<?php esc_attr_e($wwp_wholesaler_shipping_company); ?>">
								</<?php wwp_elements('p'); ?>>
								
								<?php
							}
							if ( isset($registrations['enable_shipping_address_1']) && 'yes' === $registrations['enable_shipping_address_1'] ) { 
								?>
								<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
									<label for="wwp_wholesaler_shipping_address_line_1"><?php echo !empty($registrations['shipping_address_1']) ? esc_html__(__($registrations['shipping_address_1']), 'woocommerce-wholesale-pricing') : esc_html__('Address line 1', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_address_line_1" id="wwp_wholesaler_shipping_address_line_1" value="<?php esc_attr_e($wwp_wholesaler_shipping_address_line_1); ?>" >
								</<?php wwp_elements('p'); ?>>
								
								<?php
							}
							if ( isset($registrations['enable_shipping_address_2']) && 'yes' === $registrations['enable_shipping_address_2'] ) { 
								?>
								<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
									<label for="wwp_wholesaler_shipping_address_line_2"><?php echo !empty($registrations['shipping_address_2']) ? esc_html__(__($registrations['shipping_address_2']), 'woocommerce-wholesale-pricing') : esc_html__('Address line 2 (optional)', 'woocommerce-wholesale-pricing'); ?></label>
									<input type="text" name="wwp_wholesaler_shipping_address_line_2" id="wwp_wholesaler_shipping_address_line_2" value="<?php esc_attr_e($wwp_wholesaler_shipping_address_line_2); ?>">
								</<?php wwp_elements('p'); ?>>
								
								<?php 
							}
							if ( isset($registrations['enable_shipping_city']) && 'yes' === $registrations['enable_shipping_city'] ) { 
								?>
								<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
									<label for="wwp_wholesaler_shipping_city"><?php echo !empty($registrations['shipping_city']) ? esc_html__(__($registrations['shipping_city']), 'woocommerce-wholesale-pricing') : esc_html__('City', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_city" id="wwp_wholesaler_shipping_city" value="<?php esc_attr_e($wwp_wholesaler_shipping_city); ?>">
								</<?php wwp_elements('p'); ?>>
								
								<?php
							}
							if ( isset($registrations['enable_shipping_post_code']) && 'yes' === $registrations['enable_shipping_post_code'] ) { 
								?>
								<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
									<label for="wwp_wholesaler_shipping_post_code"><?php echo !empty($registrations['shipping_post_code']) ? esc_html__(__($registrations['shipping_post_code']), 'woocommerce-wholesale-pricing') : esc_html__('Postcode / ZIP', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_post_code" id="wwp_wholesaler_shipping_post_code" value="<?php esc_attr_e($wwp_wholesaler_shipping_post_code); ?>">
								</<?php wwp_elements('p'); ?>>
								
								<?php
							}
							if ( isset($registrations['enable_shipping_country']) && 'yes' === $registrations['enable_shipping_country'] ) { 
								?>
								<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
								<?php
									woocommerce_form_field( 'shipping_country', 
									array(
										'type'       => 'select',
										'class'      => array( 'chzn-drop' ),
										'label'      => esc_html__('Select shipping country', 'woocommerce-wholesale-pricing'),
										'placeholder'=> esc_html__('Enter something', 'woocommerce-wholesale-pricing'),
										'default'    => $default_country,
										'options'    => $countries,
										'required'   => true,
									)
									);
								?>
								</<?php wwp_elements('p'); ?>>
								
								<?php
							}
							if ( isset($registrations['enable_shipping_state']) && 'yes' === $registrations['enable_shipping_state'] ) { 
								?>
								<<?php wwp_elements('p'); ?> class="<?php echo wp_kses_post( registration_form_class(' woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide wwp_form_css_row ') ); ?>">
									<label for="wwp_wholesaler_shipping_state"><?php echo !empty($registrations['shipping_state']) ? esc_html__(__($registrations['shipping_state']), 'woocommerce-wholesale-pricing') : esc_html__('State / County', 'woocommerce-wholesale-pricing'); ?> <span class="required">*</span></label>
									<input type="text" name="wwp_wholesaler_shipping_state" id="wwp_wholesaler_shipping_state" value="<?php esc_attr_e($wwp_wholesaler_shipping_state); ?>">
								</<?php wwp_elements('p'); ?>>
								
								<?php 
							} 
							?>
						</div>
						<?php
					} 
					?>
					<<?php wwp_elements('p'); ?> class="woocomerce-FormRow form-row"> 
						<input type="submit" class="woocommerce-Button button" id="register" name="wwp_register" value="<?php esc_html_e('Register', 'woocommerce-wholesale-pricing'); ?>">
					</<?php wwp_elements('p'); ?>>
					
					<?php
					/**
					 * Hook
					 *
					 * @since 1.0.0
					 */
					do_action( 'wwp_wholesaler_registration_fields_end', $registrations, $settings );
					?>
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
		public function wwp_register_wholesaler() { 
			
			$data = $_REQUEST;

			$errors   =array();
			$settings =get_option('wwp_wholesale_pricing_options', true);            
			$role     = get_option( 'default_role' );
			if ( isset( $settings['disable_auto_role'] ) && 'no' === $settings['disable_auto_role'] ) {
				$role = 'wwp_wholesaler';
			}
			$userdata = array(
				'first_name'  => isset( $data['wwp_wholesaler_fname'] ) ? wc_clean($data['wwp_wholesaler_fname']) : '',
				'last_name'  => isset( $data['wwp_wholesaler_lname'] ) ? wc_clean($data['wwp_wholesaler_lname']) : '',
				'user_login'  => isset( $data['wwp_wholesaler_username'] ) ? wc_clean($data['wwp_wholesaler_username']) : '',
				'user_email'  => isset( $data['wwp_wholesaler_email'] ) ? wc_clean($data['wwp_wholesaler_email']) : '',
				'user_pass'   => isset( $data['wwp_wholesaler_password'] ) ? wc_clean($data['wwp_wholesaler_password']) : '',  
				'role'        => $role,
			);
			$user_id  = wp_insert_user($userdata);
			if ( !is_wp_error($user_id) ) {

				if ( isset($data['wwp_wholesaler_fname']) ) {
					$billing_first_name = wc_clean($data['wwp_wholesaler_fname']);
					update_user_meta($user_id, 'billing_first_name', $billing_first_name);
				}
				if ( isset($data['wwp_wholesaler_lname']) ) {
					$billing_last_name = wc_clean($data['wwp_wholesaler_lname']);
					update_user_meta($user_id, 'billing_last_name', $billing_last_name);
				}
				if ( isset($data['wwp_wholesaler_company']) ) {
					$billing_company = wc_clean($data['wwp_wholesaler_company']);
					update_user_meta($user_id, 'billing_company', $billing_company);
				}
				if ( isset($data['wwp_wholesaler_address_line_1']) ) {
					$billing_address_1 = wc_clean($data['wwp_wholesaler_address_line_1']);
					update_user_meta($user_id, 'billing_address_1', $billing_address_1);
				}
				if ( isset($data['wwp_wholesaler_address_line_2']) ) {
					$billing_address_2 = wc_clean($data['wwp_wholesaler_address_line_2']);
					update_user_meta($user_id, 'billing_address_2', $billing_address_2);
				}
				if ( isset($data['wwp_wholesaler_city']) ) {
					$billing_city = wc_clean($data['wwp_wholesaler_city']);
					update_user_meta($user_id, 'billing_city', $billing_city);
				}
				if ( isset($data['wwp_wholesaler_state']) ) {
					$billing_state = wc_clean($data['wwp_wholesaler_state']);
					update_user_meta($user_id, 'billing_state', $billing_state);
				}
				if ( isset($data['wwp_wholesaler_post_code']) ) {
					$billing_postcode = wc_clean($data['wwp_wholesaler_post_code']);
					update_user_meta($user_id, 'billing_postcode', $billing_postcode);
				}
				if ( isset($data['billing_country']) ) {
					$billing_country = wc_clean($data['billing_country']);
					update_user_meta($user_id, 'billing_country', $billing_country);
				}
				if ( isset($data['wwp_wholesaler_phone']) ) {
					$billing_phone = wc_clean($data['wwp_wholesaler_phone']);
					update_user_meta($user_id, 'billing_phone', $billing_phone);
				}
				if ( isset($data['wwp_wholesaler_copy_billing_address']) ) {
					$wwp_wholesaler_copy_billing_address = wc_clean($data['wwp_wholesaler_copy_billing_address']);
				}
				if ( isset($wwp_wholesaler_copy_billing_address) ) {
					if ( isset($data['wwp_wholesaler_fname']) ) {
						$billing_first_name = wc_clean($data['wwp_wholesaler_fname']);
						update_user_meta($user_id, 'shipping_first_name', $billing_first_name);
					}
					if ( isset($data['wwp_wholesaler_lname']) ) {
						$billing_last_name = wc_clean($data['wwp_wholesaler_lname']);
						update_user_meta($user_id, 'shipping_last_name', $billing_last_name);
					}
					if ( isset($data['wwp_wholesaler_company']) ) {
						$billing_company = wc_clean($data['wwp_wholesaler_company']);
						update_user_meta($user_id, 'shipping_company', $billing_company);
					}
					if ( isset($data['wwp_wholesaler_address_line_1']) ) {
						$billing_address_1 = wc_clean($data['wwp_wholesaler_address_line_1']);
						update_user_meta($user_id, 'shipping_address_1', $billing_address_1);
					}
					if ( isset($data['wwp_wholesaler_address_line_2']) ) {
						$billing_address_2 = wc_clean($data['wwp_wholesaler_address_line_2']);
						update_user_meta($user_id, 'shipping_address_2', $billing_address_2);
					}
					if ( isset($data['wwp_wholesaler_city']) ) {
						$billing_city = wc_clean($data['wwp_wholesaler_city']);
						update_user_meta($user_id, 'shipping_city', $billing_city);
					}
					if ( isset($data['wwp_wholesaler_state']) ) {
						$billing_state = wc_clean($data['wwp_wholesaler_state']);
						update_user_meta($user_id, 'shipping_state', $billing_state);
					}
					if ( isset($data['wwp_wholesaler_post_code']) ) {
						$billing_postcode = wc_clean($data['wwp_wholesaler_post_code']);
						update_user_meta($user_id, 'shipping_postcode', $billing_postcode);
					}
					if ( isset($data['billing_country']) ) {
						$shipping_country = wc_clean($data['billing_country']);
						update_user_meta($user_id, 'shipping_country', $shipping_country);
					}
				} else {
					if ( isset($data['wwp_wholesaler_shipping_fname']) ) {
						$shipping_first_name = wc_clean($data['wwp_wholesaler_shipping_fname']);
						update_user_meta($user_id, 'shipping_first_name', $shipping_first_name);
					}
					if ( isset($data['wwp_wholesaler_shipping_lname']) ) {
						$shipping_last_name = wc_clean($data['wwp_wholesaler_shipping_lname']);
						update_user_meta($user_id, 'shipping_last_name', $shipping_last_name);
					}
					if ( isset($data['wwp_wholesaler_shipping_company']) ) {
						$shipping_company = wc_clean($data['wwp_wholesaler_shipping_company']);
						update_user_meta($user_id, 'shipping_company', $shipping_company);
					}
					if ( isset($data['wwp_wholesaler_shipping_address_line_1']) ) {
						$shipping_address_1 = wc_clean($data['wwp_wholesaler_shipping_address_line_1']);
						update_user_meta($user_id, 'shipping_address_1', $shipping_address_1);
					}
					if ( isset($data['wwp_wholesaler_shipping_address_line_2']) ) {
						$shipping_address_2 = wc_clean($data['wwp_wholesaler_shipping_address_line_2']);
						update_user_meta($user_id, 'shipping_address_2', $shipping_address_2);
					}
					if ( isset($data['wwp_wholesaler_shipping_city']) ) {
						$shipping_city = wc_clean($data['wwp_wholesaler_shipping_city']);
						update_user_meta($user_id, 'shipping_city', $shipping_city);
					}
					if ( isset($data['wwp_wholesaler_shipping_state']) ) {
						$shipping_state = wc_clean($data['wwp_wholesaler_shipping_state']);
						update_user_meta($user_id, 'shipping_state', $shipping_state);
					}
					if ( isset($data['wwp_wholesaler_shipping_post_code']) ) {
						$shipping_postcode = wc_clean($data['wwp_wholesaler_shipping_post_code']);
						update_user_meta($user_id, 'shipping_postcode', $shipping_postcode);
					}
					if ( isset($data['shipping_country']) ) {
						$shipping_country = wc_clean($data['shipping_country']);
						update_user_meta($user_id, 'shipping_country', $shipping_country);
					}
				}

				$id = wp_insert_post(
					array(
						'post_type'   => 'wwp_requests',
						'post_title'  => isset( $data['wwp_wholesaler_username'] ) ? wc_clean( $data['wwp_wholesaler_username'] ) . ' - ' . esc_attr( $user_id ) : '',
						'post_status' => 'publish',
					)
				);
			 
				//On success
				if ( !is_wp_error($user_id) ) {
				 
					update_post_meta( $id, '_user_id', $user_id );
					if ( isset( $settings['disable_auto_role'] ) && 'no' === $settings['disable_auto_role'] ) {
					
						update_post_meta( $id, '_user_status', 'active' );
						update_user_meta( $user_id, '_user_status', 'active' );
						wp_set_object_terms( $id, 'wwp_wholesaler', 'wholesale_user_roles', true );
						
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
					do_action('wwp_wholesale_new_registered_request', $user_id);

					/**
					 * Filter
					 *
					 * @since 1.0.0
					 */
					$notice = apply_filters('wwp_success_msg', esc_html__('You are Registered Successfully', 'woocommerce-wholesale-pricing'));
					wc_add_notice(esc_html__($notice, 'woocommerce-wholesale-pricing'), 'success');
					$_POST = array();
				} else {
					/**
					 * Filter
					 *
					 * @since 1.0.0
					 */
					$notice = apply_filters('wwp_error_msg', esc_html__($user_id->get_error_message(), 'woocommerce-wholesale-pricing'));
					wc_add_notice(esc_html__($notice, 'woocommerce-wholesale-pricing'), 'error');
				}
				
	
				if ( isset( $settings['register_redirect'] ) && ! empty( get_permalink( $settings['register_redirect'] ) ) && ! is_wp_error( $user_id ) ) {
					$location = get_permalink( $settings['register_redirect'] );
					header( "Location: $location", true );
					exit;
				}
			} else {
				$errors[] = $user_id->get_error_message();
			}
			return $errors;
		}
		
		/**
		 * Method wwp_wholesale_registeration
		 *
		 * @return void
		 */
		public function wwp_wholesale_registeration() {
			$settings = get_option( 'wwp_wholesale_pricing_options', true );
			if ( isset( $settings['enable_registration_page'] ) && 'yes' === $settings['enable_registration_page'] ) {
				$page_id   = $settings['registration_page'];
				$page_link = get_permalink( $page_id );
				
				/**
				* Hooks
				*
				* @since 3.0
				*/
				echo '<a href="' . esc_url( $page_link ) . '">' . esc_html( apply_filters( 'wwp_wholesale_registration_text', esc_html__( 'Or Want to Register as a Wholesaler?', 'woocommerce-wholesale-pricing' ) ) ) . '</a>';
			}
		}
	}
	new Wwp_Wholesale_Pricing_Registration();
}
