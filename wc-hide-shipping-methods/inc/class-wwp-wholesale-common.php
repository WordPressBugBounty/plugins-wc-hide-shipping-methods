<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( !class_exists('WWP_Wholesale_Pricing_Common') ) {

	class WWP_Wholesale_Pricing_Common {

		public function __construct() {
			
			// new user register
			add_action('wwp_wholesale_new_registered_request', array( $this, 'wwp_wholesale_new_registered_request' ), 10, 1);
		}
		
		public function wwp_wholesale_new_registered_request( $user_id ) { 
			if ( 'yes' === get_option('wwp_wholesale_user_registration_notification') ) {
				
				$subject = get_option('wwp_wholesale_registration_notification_subject');
				$subject = !empty( $subject ) ? $subject : esc_html__('New Request Received.', 'woocommerce-wholesale-pricing');
				$subject = stripslashes(html_entity_decode($subject, ENT_QUOTES, 'UTF-8' ));
				$body    = get_option('wwp_wholesale_registration_notification_body');
				$user    = get_user_by( 'ID', $user_id );
				if ( !is_wp_error($user) ) {
					$sendor   = esc_html(get_option('blogname')) . ' <' . esc_html(get_option('admin_email')) . '>';
					$headers  = 'From: ' . $sendor . PHP_EOL;
					$headers .= 'MIME-Version: 1.0' . PHP_EOL; 
					$headers .= 'Content-Type: text/html; charset=UTF-8';
					$body     =str_replace('{email}', $user->user_email, $body);
					$body     =str_replace('{first_name}', $user->first_name, $body);
					$body     =str_replace('{last_name}', $user->last_name, $body);
					$body     =str_replace('{username}', $user->user_login, $body);
					$body     =str_replace('{date}', gmdate( 'Y-m-d', strtotime( $user->user_registered ) ), $body);
					$body     =str_replace('{time}', gmdate( 'H:i:s', strtotime( $user->user_registered ) ), $body);
					 
					wp_mail($user->user_email, $subject, $body, $headers, '');
				}
			}
		}
	}
	new WWP_Wholesale_Pricing_Common();
}
