<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
/**
 * Class Woo_Wholesale_User_Roles
 */
if (!class_exists('WWP_Wholesale_User_Roles')) {

	class WWP_Wholesale_User_Roles {

		public function __construct() {
			add_action( 'init', array( $this, 'wwp_add_default_role' ), 3 );
			add_action( 'init', array( $this, 'register_taxonomy_for_users' ) );
			add_filter('wholesale_user_roles_row_actions', array( $this, 'wholesale_user_roles_row_actions' ) );
			add_filter('manage_edit-wholesale_user_roles_columns', array( $this, 'wholesale_user_roles_columns' ), 99, 1);
			add_action( 'wholesale_user_roles_edit_form_fields', array( $this, 'wwp_edit_new_field' ), 10, 1 );
			add_action( 'edited_wholesale_user_roles', array( $this, 'wwp_save_new_field' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts_user_roles' ) );
			add_filter( 'wp_update_term_data', array( $this, 'wp_update_term_data_wholesale_user_roles' ), 99, 3);
			add_filter( 'bulk_actions-edit-wholesale_user_roles', array( $this, 'wwp_remove_bulk_actions' ), 99 );
			add_action( 'admin_menu', array( $this, 'register_menu_for_user_roles' ) );
		}

		public function register_menu_for_user_roles() {
			add_filter( 'parent_file', array( $this, 'wwp_menu_highlight_wholesale_user_roles' ), 10 );
		}

		public function wwp_menu_highlight_wholesale_user_roles( $parent_file ) {
			global $current_screen;
			if ( 'wholesale_user_roles' == $current_screen->taxonomy ) {
				$parent_file = 'wwp_wholesale';
			}
			return $parent_file;
		}

		public function wwp_remove_bulk_actions( $actions ) {
			return array();
		}

		public function wwp_add_default_role() {
			add_role('wwp_wholesaler', esc_html__('Wholesaler - Wholesaler Role', 'woocommerce-wholesale-pricing'), array(
				'read' => true,
				'level_0' => true,
			));
		}
		
		/**
		 * Method register_taxonomy_for_users
		 *
		 * @return void
		 */
		public function register_taxonomy_for_users() {
			$labels = array(
				'label'                      => esc_html__( 'Wholesale Roles', 'woocommerce-wholesale-pricing' ),
				'name'                       => esc_html__( 'Wholesale User Roles', 'woocommerce-wholesale-pricing' ),
				'singular_name'              => esc_html__( 'Wholesale Role', 'woocommerce-wholesale-pricing' ),
				'search_items'               => esc_html__( 'Search User Roles', 'woocommerce-wholesale-pricing' ),
				'popular_items'              => esc_html__( 'Popular User Roles', 'woocommerce-wholesale-pricing' ),
				'all_items'                  => esc_html__( 'All User Roles', 'woocommerce-wholesale-pricing' ),
				'parent_item'                => null,
				'parent_item_colon'          => null,
				'edit_item'                  => esc_html__( 'Edit User Role', 'woocommerce-wholesale-pricing' ),
				'update_item'                => esc_html__( 'Update User Role', 'woocommerce-wholesale-pricing' ),
				'add_new_item'               => esc_html__( 'Add New User Role', 'woocommerce-wholesale-pricing' ),
				'new_item_name'              => esc_html__( 'New User Role Name', 'woocommerce-wholesale-pricing' ),
				'separate_items_with_commas' => esc_html__( 'Separate topics with commas', 'woocommerce-wholesale-pricing' ),
				'add_or_remove_items'        => esc_html__( 'Add or remove topics', 'woocommerce-wholesale-pricing' ),
				'choose_from_most_used'      => esc_html__( 'Choose from the most used topics', 'woocommerce-wholesale-pricing' ),
				'menu_name'                  => esc_html__( 'Wholesale Roles', 'woocommerce-wholesale-pricing' ),
			);
			$args   = array(
				'hierarchical'          => false,
				'labels'                => $labels,
				'show_ui'               => true,
				'show_in_menu'          => 'wwp_wholesale',
				'show_admin_column'     => true,
				'update_count_callback' => '_update_post_term_count',
				'query_var'             => true,
			);
			register_taxonomy( 'wholesale_user_roles', array( 'wwp_requests' ), $args );
			$term = term_exists( 'wwp_wholesaler', 'wholesale_user_roles' );
			
			if ( null === $term || false === $term ) {
				wp_insert_term( 'Wholesaler - Wholesaler Role', 'wholesale_user_roles', array( 'slug' => 'wwp_wholesaler' ) );
			}
		}
		


		/**
		 * Method wholesale_user_roles_row_actions
		 *
		 * @param $columns array
		 *
		 * @return array
		 */
		public function wholesale_user_roles_row_actions( $actions ) {
			unset($actions['delete']);
			unset($actions['view']);
			unset($actions['inline hide-if-no-js']);
			return $actions;
		}
		
		/**
		 * Method wholesale_user_roles_columns
		 *
		 * @param $columns array
		 *
		 * @return array
		 */
		public function wholesale_user_roles_columns( $columns ) {
			unset( $columns['posts'] );   
			return $columns;
		}
		
		public function wwp_edit_new_field( $term ) {
			$term_id = $term->term_id;
			wp_nonce_field( 'wwp_wholesale_user_roles', 'wwp_wholesale_user_roles' ); ?>
			<tr class="form-field term-gateways-wrap">
				<th><label for="wwp_restricted_pmethods_wholesaler">
					<?php esc_html_e( 'Disable Payment Methods', 'woocommerce-wholesale-pricing' ); ?></label>
				</th>
				<td>
					<?php
						$value              = get_term_meta( $term_id, 'wwp_restricted_pmethods_wholesaler', true );
						$available_gateways = WC()->payment_gateways->payment_gateways();
					?>
					<select name="wwp_restricted_pmethods_wholesaler[]" id="wwp_restricted_pmethods_wholesaler" class="regular-text select2 wc-enhanced-select" multiple>
					<?php
					if ( ! empty( $available_gateways ) ) {
						foreach ( $available_gateways as $key => $method ) {
							$selected = '';
							if ( ! empty( $value ) && in_array( $key, $value, true ) ) {
								$selected = 'selected="selected"';
							}
							echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>' . esc_attr( $method->title ) . '</option>';
						}
					}
					?>
					</select>
					<p><?php esc_html_e( 'Select payment methods to restrict for wholesale users.', 'woocommerce-wholesale-pricing' ); ?></p>
				</td>
			</tr>

			<tr class="form-field term-shipping-wrap">
				<th><label for="wwp_restricted_smethods_wholesaler">
					<?php esc_html_e( 'Disable Shipping Methods', 'woocommerce-wholesale-pricing' ); ?></label>
				</th>
				<td>
					<?php
						$value            = get_term_meta( $term_id, 'wwp_restricted_smethods_wholesaler', true );
						$shipping_methods = WC()->shipping->get_shipping_methods();
					?>
					<select name="wwp_restricted_smethods_wholesaler[]" id="wwp_restricted_smethods_wholesaler" class="regular-text select2 wc-enhanced-select" multiple>
					<?php
					if ( ! empty( $shipping_methods ) ) {
						foreach ( $shipping_methods as $key => $method ) {
							$selected = '';
							if ( ! empty( $value ) && in_array( $key, $value, true ) ) {
								$selected = 'selected="selected"';
							}
							echo '<option value="' . esc_attr( $key ) . '" ' . esc_attr( $selected ) . '>' . esc_attr( $method->method_title ) . '</option>';
						}
					}
					?>
					</select>
					<p><?php esc_html_e( 'Select shipping methods to restrict for wholesale users.', 'woocommerce-wholesale-pricing' ); ?></p>
				</td>
			</tr>

			<?php
		}

		public function wwp_save_new_field( $term_id ) {
			if ( ! isset( $_POST['wwp_wholesale_user_roles'] ) || ! wp_verify_nonce( wc_clean( $_POST['wwp_wholesale_user_roles'] ), 'wwp_wholesale_user_roles' ) ) {
				wp_die( esc_html__( 'Security check', 'wholesale-for-woocommerce' ) );
			}

			$term = get_term( $term_id, 'wholesale_user_roles' );

			if ( 'wwp_wholesaler' !== $term->slug ) {
				return;
			}

			if ( isset( $_POST['wwp_restricted_pmethods_wholesaler'] ) ) {
				update_term_meta( $term_id, 'wwp_restricted_pmethods_wholesaler', wc_clean( $_POST['wwp_restricted_pmethods_wholesaler'] ) );
			} else {
				update_term_meta( $term_id, 'wwp_restricted_pmethods_wholesaler', '' );
			}
			if ( isset( $_POST['wwp_restricted_smethods_wholesaler'] ) ) {
				update_term_meta( $term_id, 'wwp_restricted_smethods_wholesaler', wc_clean( $_POST['wwp_restricted_smethods_wholesaler'] ) );
			} else {
				update_term_meta( $term_id, 'wwp_restricted_smethods_wholesaler', '' );
			}
		}

		public function admin_enqueue_scripts_user_roles() {
			$screen = get_current_screen();
			if ( 'edit-wholesale_user_roles' === $screen->id ) {
				wp_enqueue_style( 'wwp-bootstrap-select2', WWP_PLUGIN_URL . 'assets/css/bootstrap.select2.min.css', array(), WWPL_VERSION );
				wp_enqueue_script( 'wc-enhanced-select' );
			}
		}

		public function wp_update_term_data_wholesale_user_roles( $data, $term_id, $taxonomy ) {
			if ('wholesale_user_roles' === $taxonomy ) {
				$data['name'] = 'Wholesaler - Wholesaler Role';
				$data['slug'] = 'wwp_wholesaler';
			}

			return $data;
		}
	}
	new WWP_Wholesale_User_Roles();
}
