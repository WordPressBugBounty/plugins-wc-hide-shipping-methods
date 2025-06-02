<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
/**
 * Class To Add Wholesale Functionality with WooCommerce
 */
if ( !class_exists('Wwp_Wholesale_Functions') ) {

	class Wwp_Wholesale_Functions {

		public function __construct() {
			add_filter('woocommerce_package_rates', array( $this, 'wwp_apply_free_shipping_if_valid_coupon' ), 100);
		}
		public function wwp_apply_free_shipping_if_valid_coupon( $rates ) {
			global $woocommerce;
			$free = array();
			foreach ( $woocommerce->cart->applied_coupons as $coupon_title ) {
				// Query to get the coupon by title
				$query = new WP_Query(array(
					'post_type'  => 'shop_coupon',
					'title'      => $coupon_title,
					'posts_per_page' => 1,
				));
		
				if ($query->have_posts()) {
					$coupon     = $query->post;
					$coupon_obj = new WC_Coupon($coupon->ID);
					if ($coupon_obj->get_free_shipping()) {
						foreach ($rates as $rate_id => $rate) {
							if ('flat_rate' === $rate->method_id) {
								$rate->label      = esc_html__('Free Shipping', 'woocommerce-wholesale-pricing');
								$rate->cost       = 0.00;
								$free[ $rate_id ] = $rate;
								break;
							}
						}
					}
				}
			}
			return !empty($free) ? $free : $rates;
		}
	}
	new Wwp_Wholesale_Functions();
}
