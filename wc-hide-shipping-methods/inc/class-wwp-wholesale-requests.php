<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * Class To handle Wholesale Customer Requests
 */
if ( ! class_exists( 'WWP_Easy_Wholesale_Requests' ) ) {

	class WWP_Easy_Wholesale_Requests {

		public function __construct() {
			add_action( 'init', array( $this, 'register_requests_post_type' ) );
			add_action( 'init', array( $this, 'update_user_status' ) );
			add_filter( 'manage_wwp_requests_posts_columns', array( $this, 'register_wwp_requests_columns' ) );
			add_action( 'manage_wwp_requests_posts_custom_column', array( $this, 'custom_columns_wwp_requests' ), 15, 2 );
			add_action( 'admin_menu', array( $this, 'register_menu_for_requests' ) , 11);
			add_action( 'add_meta_boxes', array( $this, 'register_add_meta_box_requests' ) );
			add_action( 'save_post_wwp_requests', array( $this, 'save_requests_meta' ) );
			add_action( 'edit_user_profile_update', array( $this, 'wwp_edit_user_profile' ) );
			add_action( 'transition_post_status', array( $this, 'wwp_handle_post_restore' ), 10, 3 );
			add_action( 'wp_trash_post', array( $this, 'wwp_handle_post_trashed' ), 10, 1 );
			add_action( 'before_delete_post', array( $this, 'wwp_user_status_on_post_delete' ) );
		}

		public function wwp_user_status_on_post_delete( $post_id ) {
			$post = get_post( $post_id );

			if ( $post && 'wwp_requests' === $post->post_type ) {
				// Get the user ID associated with the post
				$user_id = get_post_meta( $post_id, '_user_id', true );

				if ( $user_id ) {
					// Delete the post meta
					delete_post_meta( $post_id, '_user_status' );

					// Delete the user meta
					delete_user_meta( $user_id, '_user_status' );
				}
			}
		}
	
		public function wwp_handle_post_trashed( $post_id ) {
			$post = get_post( $post_id );

			if ( $post &&'wwp_requests' === $post->post_type  ) {

				// Get the user ID associated with the post
				$user_id = get_post_meta( $post_id, '_user_id', true );

				if ( $user_id ) {
					// Get the user's current role
					$user = get_user_by( 'ID', $user_id );
					update_post_meta( $post_id, '_user_status', 'waiting' );
					update_user_meta( $user_id, '_user_status', 'waiting' );
					if ( $user ) {
						$user_roles = $user->roles; // Array of roles the user currently has

						// Get all terms in the 'wholesale_user_roles' taxonomy
						$allterms = get_terms( array(
							'taxonomy'   => 'wholesale_user_roles',  // Your custom taxonomy
							'hide_empty' => false,                    // Get all terms (including empty ones)
						) );

						$default_role = get_option( 'default_role' );
						foreach ( $allterms as $term ) {
							if ( in_array(  $term->slug, $user_roles ) ) {
								$user->set_role( $default_role );
							}
						}
					}
				}
			}
		}

		public function wwp_handle_post_restore( $new_status, $old_status, $post ) {

			if ( $post && 'trash' === $old_status && 'wwp_requests' === $post->post_type ) {
				
				if ( 'draft' == $new_status  ) {
					wp_update_post( array(
						'ID' => $post->ID,
						'post_status' => 'publish',
					) );
				}

				$user_id = get_post_meta( $post->ID, '_user_id', true );

				if ( $user_id ) {
					update_post_meta( $post->ID, '_user_status', 'waiting' );
					update_user_meta( $user_id, '_user_status', 'waiting' );
				}

				// Set user's role to WordPress default role
				$default_role = get_option( 'default_role' );
				$user = get_user_by( 'ID', $user_id );

				if ( $user && ! in_array( $default_role, $user->roles ) ) {
					$user->set_role( $default_role );
				}
			}
		}

		/**
		 * Delete user meta key and post
		 * 
		 */
		public function wwp_edit_user_profile( $user_id ) {
			
			$user = get_userdata( $user_id );
			
			if ( ! $user ) {
				return;
			}
			$old_roles = $user->roles;
			
			// New role (jo form ke through aaya hoga)
			if ( isset( $_REQUEST['role'] ) ) {
				$new_role = sanitize_text_field( $_REQUEST['role'] );
				
				if ( ! in_array( $new_role, $old_roles ) ) {
					$args = array(
						'post_type'   => 'wwp_requests', 
						'meta_query'  => array( 
							array( 
								'key' => '_user_id',
								'value' => $user_id, 
							), 
						), 
					);
					$post = get_posts( $args );
					if ( is_array( $post ) ) {
						foreach ( $post as $key => $value ) {
							wp_delete_post( $value->ID );
						}
					}
					delete_user_meta( $user_id, '_user_status' );
				}
			}
		}
		
		public function register_requests_post_type() {
			$labels = array(
				'name'               => esc_html_x( 'Requests', 'Post Type Name', 'woocommerce-wholesale-pricing' ),
				'singular_name'      => esc_html_x( 'Request', 'Post Type Singular Name', 'woocommerce-wholesale-pricing' ),
				'menu_name'          => esc_html__( 'Request', 'woocommerce-wholesale-pricing' ),
				'name_admin_bar'     => esc_html__( 'Request', 'woocommerce-wholesale-pricing' ),
				'add_new'            => esc_html__( 'Add New Request', 'woocommerce-wholesale-pricing' ),
				'add_new_item'       => esc_html__( 'Add New Request', 'woocommerce-wholesale-pricing' ),
				'new_item'           => esc_html__( 'New Request', 'woocommerce-wholesale-pricing' ),
				'edit_item'          => esc_html__( 'Edit Request', 'woocommerce-wholesale-pricing' ),
				'view_item'          => esc_html__( 'View Request', 'woocommerce-wholesale-pricing' ),
				'all_items'          => esc_html__( 'Requests List', 'woocommerce-wholesale-pricing' ),
				'search_items'       => esc_html__( 'Search Request', 'woocommerce-wholesale-pricing' ),
				'not_found'          => esc_html__( 'No Request found.', 'woocommerce-wholesale-pricing' ),
				'not_found_in_trash' => esc_html__( 'No Request found in Trash.', 'woocommerce-wholesale-pricing' ),
			);
			$args   = array(
				'labels'          => $labels,
				'description'     => esc_html__( 'Description.', 'woocommerce-wholesale-pricing' ),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => false,
				'menu_position'   => 3,
				'query_var'       => true,
				'rewrite'         => false,
				'capability_type' => 'post',
				'has_archive'     => true,
				'hierarchical'    => false,
				'supports'        => array( 'thumbnail', 'title' ),
			);
			register_post_type( 'wwp_requests', $args );
		}
		public function register_menu_for_requests() {
			
			$args = array(
				'posts_per_page' => -1,
				'post_type'      => 'wwp_requests',
				'post_status'    => 'publish',
				'meta_query'     => array(
					array(
						'key'     => '_user_status',
						'value'   => 'waiting',
						'compare' => 'EXISTS',
					),
				),
				'fields'         => 'ids',
			);

			$posts_query = new WP_Query( $args );
			$the_count   = $posts_query->post_count;
			if ( 0 !== $the_count ) {
				$the_count = '<span class="awaiting-mod">' . $the_count . '</span>';
			} else {
				$the_count = '';
			}
			add_submenu_page( 
				'wwp_wholesale',
				esc_html__( 'Wholesale User Requests', 'woocommerce-wholesale-pricing' ),
				__( 'Requests ' . $the_count, 'woocommerce-wholesale-pricing' ),
				'manage_options',
				'edit.php?post_type=wwp_requests'
			);
		}
		public function register_wwp_requests_columns( $columns ) {
			unset( $columns['author'] );
			unset( $columns['date'] );
			$columns['user_email']  = esc_html__( 'User Email', 'woocommerce-wholesale-pricing' );
			$columns['user_status'] = esc_html__( 'User Status', 'woocommerce-wholesale-pricing' );
			$columns['date']        = esc_html__( 'Date', 'woocommerce-wholesale-pricing' );
			$columns['action']      = esc_html__( 'Action', 'woocommerce-wholesale-pricing' );
			return $columns;
		}
		public function custom_columns_wwp_requests( $column, $post_id ) {
			$post = get_post( $post_id );
			switch ( $column ) {
				case 'user_status':
					$status = get_post_meta( $post_id, '_user_status', true );
					if ( 'active' === $status ) {
						echo '<p class="approved">' . esc_html__( 'Approved', 'woocommerce-wholesale-pricing' ) . '</p>';
					} elseif ( 'waiting' === $status ) {
						echo '<p class="waiting">' . esc_html__( 'Waiting', 'woocommerce-wholesale-pricing' ) . '</p>';
					} elseif ( 'rejected' === $status ) {
						echo '<p class="rejected">' . esc_html__( 'Rejected', 'woocommerce-wholesale-pricing' ) . '</p>';
					}
					break;

				case 'user_email':
					$user_id = get_post_meta( $post_id, '_user_id', true );
					if ( ! empty( $user_id ) ) {
						$user_info = get_userdata( $user_id );
						if ( $user_info ) {
							echo esc_html( $user_info->user_email );
						}
					}
					break;

				case 'action':
					if ( $post && 'publish' == $post->post_status ) {
						$user_id = get_post_meta( $post_id, '_user_id', true );
						if ( ! empty( $user_id ) ) {
							$status = get_post_meta( $post_id, '_user_status', true );
							$nonce  = wp_create_nonce( 'request_user_role_nonce' );
							?>
							<form action="">
								<a href="edit.php?post_type=wwp_requests&post_id=<?php echo esc_attr( $post_id ); ?>&user_status=active&_wpnonce=<?php echo esc_attr( $nonce ); ?>">Approve</a> | 
								<a href="edit.php?post_type=wwp_requests&post_id=<?php echo esc_attr( $post_id ); ?>&user_status=rejected&_wpnonce=<?php echo esc_attr( $nonce ); ?>">Reject</a>
							</form> 
							<?php
						}
					}
					break;
			}
		}
		public function register_add_meta_box_requests() {
			add_meta_box(
				'wholesale-pricing-pro-user-status',
				esc_html__( 'Wholesale User Request Confirmation', 'woocommerce-wholesale-pricing' ),
				array( $this, 'wholesale_user_confirmation' ),
				'wwp_requests',
				'normal',
				'high'
			);
		}
		public function wholesale_user_confirmation() {
			global $post;
			$settings      = get_option( 'wwp_wholesale_pricing_options', true );
			$status        = get_post_meta( $post->ID, '_user_status', true );
			$user_id       = get_post_meta( $post->ID, '_user_id', true );
			$rejected_note = get_user_meta( $user_id, 'rejected_note', true );
			wp_nonce_field( 'request_user_role_nonce', 'request_user_role_nonce' );
			?>
			<div class="wholesale_user_confirmation">
			<?php
			if ( ! empty( $user_id ) ) {
				$user_info = get_userdata( $user_id );
				if ( $user_info ) {
					?>
					<div class="user_info">
						<table class="form-table">
							<tbody>
							<?php
							$wp_roles = wp_roles();
							if ( ! empty( $user_info->roles[0] ) ) {
								$wp_roles_user_info = $wp_roles->role_names[ $user_info->roles[0] ];
							}
							echo '<tr class=""><td><strong>' . esc_html__( 'Wholesale roles to be assign: ', 'woocommerce-wholesale-pricing' ) . '</strong></td><td>' . esc_html( 'wwp_wholesaler' ) . '</td></tr>';
							echo '<tr><td><strong>' . esc_html__( 'User ID: ', 'woocommerce-wholesale-pricing' ) . '</strong></td><td>' . esc_html( $user_info->ID ) . '</td></tr>';
							echo '<tr><td><strong>' . esc_html__( 'User Email: ', 'woocommerce-wholesale-pricing' ) . '</strong></td><td>' . esc_html( $user_info->user_email ) . '</td></tr>';
							echo '<tr><td><strong>' . esc_html__( 'Name: ', 'woocommerce-wholesale-pricing' ) . '</strong></td><td>' . esc_html( $user_info->display_name ) . '</td></tr>';
							echo '<tr><td><strong>' . esc_html__( 'Username: ', 'woocommerce-wholesale-pricing' ) . '</strong></td><td>' . esc_html( $user_info->user_login ) . '</td></tr>';
							echo '<tr><td><strong>' . esc_html__( 'Current user role: ', 'woocommerce-wholesale-pricing' ) . '</strong></td><td>' . esc_html( $wp_roles_user_info ) . '</td></tr>';
							echo '<tr><td><a style="font-size:12px" href="' . esc_url( admin_url( "user-edit.php?user_id=$user_id" ) ) . '" >' . esc_html__( 'View Customer details', 'woocommerce-wholesale-pricing' ) . '</a></td></tr>';
							?>
							</tbody>
						</table>
					</div>
					<?php
				} else {
					echo esc_html__( 'User does not exist', 'woocommerce-wholesale-pricing' );
				}
			} else {
				echo esc_html__( 'User does not exist', 'woocommerce-wholesale-pricing' );
			}
			?>
				<table>
					<tbody>
						<tr>
							<td>
								<strong><?php esc_html_e( 'Request status', 'woocommerce-wholesale-pricing' ); ?></strong>
							</td>
							<td>
								<div class="wwp-request-wrapper">
									<input class="inp-cbx" style="display: none" id="active" type="radio" name="user_status" value="active" <?php echo ( 'active' === $status ) ? 'checked' : ''; ?> >
									<label class="cbx" for="active">
										<span>
											<svg width="12px" height="9px" viewbox="0 0 12 9">
												<polyline points="1 5 4 8 11 1"></polyline>
											</svg>
										</span>
										<span><?php esc_html_e( 'Approve', 'woocommerce-wholesale-pricing' ); ?></span>
									</label>

									<input class="inp-cbx" style="display: none" id="rejected" type="radio" name="user_status" value="rejected" <?php echo ( 'rejected' === $status ) ? 'checked' : ''; ?> >
									<label class="cbx" for="rejected">
										<span>
											<svg width="12px" height="9px" viewbox="0 0 12 9">
												<polyline points="1 5 4 8 11 1"></polyline>
											</svg>
										</span>
										<span><?php esc_html_e( 'Reject', 'woocommerce-wholesale-pricing' ); ?></span>
									</label>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
			<div id="rejected_note" style="padding-bottom: 11px;padding-top: 11px;">
				<textarea name="rejected_note" class="widefat-100" rows="4" cols="120"  placeholder="Reject Note"><?php echo esc_html_e( $rejected_note, 'woocommerce-wholesale-pricing' ); ?></textarea>
			</div>
			<input name="save" type="submit" class="wwp-button-primary" id="publish" value="<?php esc_html_e( 'Update', 'woocommerce-wholesale-pricing' ); ?>">
			<style type="">
				.post-type-wwp_requests .page-title-action {
					display: none;
				}
			</style>
			<?php
		}
		public function save_requests_meta( $post_id ) {
			// Autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			// AJAX
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}
			if ( ! isset( $_POST['request_user_role_nonce'] ) || ! wp_verify_nonce( wc_clean( $_POST['request_user_role_nonce'] ), 'request_user_role_nonce' ) ) {
				return;
			}

			if ( isset( $_POST['user_status'] ) ) {
				$status = wc_clean( $_POST['user_status'] );
				if ( isset( $_POST['user_role_set'] ) ) {
					$user_role_set = wc_clean( $_POST['user_role_set'] );
				} else {
					$user_role_set = 'wwp_wholesaler';
				}
				if ( isset( $_POST['rejected_note'] ) ) {
					$rejected_note = wc_clean( $_POST['rejected_note'] );
					update_post_meta( $post_id, '_user_status', $status );
				}
				$term_list = wp_get_post_terms( $post_id, 'wholesale_user_roles', array( 'fields' => 'all' ) );

				$allterms = get_terms( array(
					'taxonomy'   => 'wholesale_user_roles',
					'hide_empty' => false,
				) );

				$user_id = get_post_meta( $post_id, '_user_id', true );
				update_user_meta( $user_id, '_user_status', $status );
				update_user_meta( $user_id, 'rejected_note', $rejected_note );
				
				$u = new WP_User( $user_id );
				if ( 'active' === $status ) {

						$wp_roles = new WP_Roles();
						$names    = $wp_roles->get_names();

					foreach ( $names as $key => $value ) {
						$u->remove_role( $key );
					}
					$u->add_role( $user_role_set );

					foreach ( $term_list as $term_remove ) {

						wp_remove_object_terms( $post_id, $term_remove->slug, 'wholesale_user_roles', true );
					}

					wp_set_object_terms( $post_id, $user_role_set, 'wholesale_user_roles', true );

					if ( 'sent' !== get_post_meta( $post_id, '_approval_notification', true ) ) {
						/**
						 * Hook
						 * 
						 * @since 1.0.0
						 */ 
						do_action( 'wwp_wholesale_user_request_approved', $user_id );
						update_post_meta( $post_id, '_approval_notification', 'sent' );
					}
				} elseif ( 'rejected' === $status ) {
						 
						$u->remove_role( 'wwp_wholesaler' );
						 
						$u->add_role( get_option( 'default_role' ) );
						/**
						 * Hook
						 * 
						 * @since 1.0.0
						 */ 
						do_action( 'wwp_wholesale_user_rejection_notification', $user_id );
				}
			}
		}

		public function update_user_status() {
			// Autosave
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			// AJAX
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				return;
			}
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wc_clean( $_GET['_wpnonce'] ), 'request_user_role_nonce' ) ) {
				return;
			}
			$settings = get_option( 'wwp_wholesale_pricing_options', true );
			if ( isset( $_GET['post_id'] ) ) {
				$post_id = wc_clean( $_GET['post_id'] );
			}
			if ( isset( $_GET['user_status'] ) && isset( $_GET['post_type'] ) && 'wwp_requests' === $_GET['post_type'] ) {
				$status = wc_clean( $_GET['user_status'] );

				$user_role_set = 'wwp_wholesaler';

				if ( isset( $settings['default_multipe_wholesale_roles'] ) && 'multiple' === $settings['wholesale_role'] ) {
					$user_role_set = $settings['default_multipe_wholesale_roles'];
				}

				$term_list = wp_get_post_terms( $post_id, 'wholesale_user_roles', array( 'fields' => 'all' ) );

				$allterms = get_terms( array(
					'taxonomy'   => 'wholesale_user_roles',
					'hide_empty' => false,
				) );

				$user_id = get_post_meta( $post_id, '_user_id', true );
				update_user_meta( $user_id, '_user_status', $status );
				update_post_meta( $post_id, '_user_status', $status );
				$u = new WP_User( $user_id );

				if ( 'active' === $status ) {

					$wp_roles = new WP_Roles();
					$names    = $wp_roles->get_names();

					foreach ( $names as $key => $value ) {
						$u->remove_role( $key );
					}
					$u->add_role( $user_role_set );
							
					wp_remove_object_terms( $post_id, 'wwp_wholesaler', 'wholesale_user_roles', true );
	
					wp_set_object_terms( $post_id, $user_role_set, 'wholesale_user_roles', true );

					if ( 'sent' !== get_post_meta( $post_id, '_approval_notification', true ) ) {
						/**
						 * Hook
						 * 
						 * @since 1.0.0
						 */ 
						do_action( 'wwp_wholesale_user_request_approved', $user_id );
						update_post_meta( $post_id, '_approval_notification', 'sent' );
					}
				} elseif ( 'rejected' === $status ) {
					foreach ( $allterms as $key => $value ) {
						$u->remove_role( 'wwp_wholesaler' );
					}
						$u->add_role( get_option( 'default_role' ) );
						/**
						 * Hook
						 * 
						 * @since 1.0.0
						 */
						do_action( 'wwp_wholesale_user_rejection_notification', $user_id );
				}
			}
		}
	}
	new WWP_Easy_Wholesale_Requests();
}