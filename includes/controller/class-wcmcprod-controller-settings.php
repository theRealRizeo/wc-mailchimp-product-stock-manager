<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Core controller
 * 
 * Sets up the admin settings
 */
class WCMCPROD_Controller_Settings {

	/**
	 * Singletone instance of the plugin.
	 *
	 * @since  1.0.0
	 */
	private static $instance = null;


	/**
	 * Returns singleton instance of the plugin.
	 *
	 * @since  1.0.0
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 60 );

		add_action( 'admin_post_wc_mc_product_stock_manager', array( $this, 'save_settings' ) );

		add_action( 'woocommerce_product_set_stock_status', array( $this, 'action_based_on_stock_status' ), 999, 3 );
	}

	/**
	 * Set up the admin menu
	 * 
	 * @since 1.0.0
	 */
	public function admin_menu() {
		add_submenu_page( 
			'woocommerce', 
			__( 'Stock Notifications', 'wc-mc-product-stock-manager' ),
			__( 'Stock Notifications', 'wc-mc-product-stock-manager' ),
			'manage_options',
			'wc-mc-product-stock-manager',
			array( $this, 'admin_page' )
		);
	}

	/**
	 * Render the admin page
	 * Returns html representation of the admin page
	 * 
	 * @since 1.0.0
	 */
	public function admin_page() {
		$settings = new WCMCPROD_Core_Settings();
		
		?>
		<div class="wrap">
			<h1><?php _e( 'MailChimp Product Stock Manager Settings', 'wc-mc-product-stock-manager' ); ?></h1>
			<?php
				if ( isset( $_REQUEST['success'] ) ) {
					?>
					<div class="notice notice-success is-dismissible">
						<p><?php _e( 'Settings updated', 'wc-mc-product-stock-manager' ); ?></p>
					</div>
					<?php
				}
				if ( isset( $_REQUEST['error'] ) ) {
					$error = sanitize_text_field( $_REQUEST['error'] );
					?>
					<div class="notice notice-error is-dismissible">
						<p>
							<?php
							if ( $error == 'n') {
								_e( 'An error occured. Please try again', 'wc-mc-product-stock-manager' ); 
							} else if ( $error == 'mc' ) {
								_e( 'There was an error validating your MailChimp credentials', 'wc-mc-product-stock-manager' ); 
							}
							
							?>
						</p>
					</div>
					<?php
				}
				if ( $settings->api_key ) {
					$ouf_of_stock 	= $settings->get_campaign( 'ouf_of_stock' );
					$in_stock 		= $settings->get_campaign( 'in_stock' );
					if ( empty( $ouf_of_stock ) ) {
						?>
						<div class="notice notice-warning">
							<p>
								<?php
									_e( 'Error saving out of stock campaign. Please submit the form to try again', 'wc-mc-product-stock-manager' ); 
								?>
							</p>
						</div>
						<?php
					}
					if ( empty( $in_stock ) ) {
						?>
						<div class="notice notice-warning">
							<p>
								<?php
									_e( 'Error saving in stock campaign. Please submit the form to try again', 'wc-mc-product-stock-manager' ); 
								?>
							</p>
						</div>
						<?php
					}
				}
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wc_mc_product_stock_manager" />
				<?php wp_nonce_field( 'wc_mc_product_stock_manager' ); ?>
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row"><?php _e( 'Enabled', 'wc-mc-product-stock-manager' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span><?php _e( 'Enabled', 'wc-mc-product-stock-manager' ); ?></span>
									</legend>
									<label for="enabled">
										<input name="enabled" type="checkbox" id="enabled" value="1" <?php checked( $settings->enabled, 1 ); ?> />
									</label>
								</fieldset>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="api_key"><?php _e( 'MailChimp API Key', 'wc-mc-product-stock-manager' ); ?></label>
							</th>
							<td>
								<input name="api_key" type="text" id="api_key" value="<?php echo $settings->api_key; ?>" class="regular-text" required/>
							</td>
						</tr>
						<?php
							if ( $settings->api_key ) {
								$api 		= new WCMCPROD_Core_Mailchimp( $settings->api_key, $settings->data_center );
								$drop_down 	= array();
								$response 	= $api->get_lists( 0, 100 );
								if ( is_wp_error( $response ) ) {
									_e( 'Error fetching lists, please ensure that your MailChimp API key is correct', 'wc-mc-product-stock-manager' );
								} else {
									$_lists  	= $response->lists;
									if ( is_array( $_lists ) ) {
										$drop_down = wp_list_pluck( $_lists, 'name', 'id' );
									}
									?>
									<tr>
										<th scope="row">
											<label for="list_id"><?php _e( 'MailChimp List', 'wc-mc-product-stock-manager' ); ?></label>
										</th>
										<td>
											
											<select name="list_id" id="list_id" required>
												<option value=""><?php _e( 'Select a list', 'wc-mc-product-stock-manager' ); ?></option>
												<?php 
													foreach ( $drop_down as $key => $value ) {
														?>
														<option value="<?php echo $key; ?>" <?php selected( $settings->email_list, $key ); ?>><?php echo $value; ?></option>
														<?php
													}
												?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="ouf_of_stock"><?php _e( 'Out of Stock Send Daily At', 'wc-mc-product-stock-manager' ); ?></label>
										</th>
										<td>
											
											<select name="ouf_of_stock" id="ouf_of_stock" required>
												<option value=""><?php _e( 'Select Time', 'wc-mc-product-stock-manager' ); ?></option>
												<?php
													$ouf_of_stock = $settings->get_schedule( 'ouf_of_stock', 18 );
													for ( $i = 1; $i <= 24; $i++ ) {
														?>
														<option value="<?php echo $i; ?>" <?php selected( $ouf_of_stock, $i ); ?>><?php echo date( "h.iA", strtotime( "$i:00" ) ); ?></option>
														<?php
													}
												?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="listin_stock_id"><?php _e( 'In Stock Send Daily At', 'wc-mc-product-stock-manager' ); ?></label>
										</th>
										<td>
											
											<select name="in_stock" id="in_stock" required>
												<option value=""><?php _e( 'Select Time', 'wc-mc-product-stock-manager' ); ?></option>
												<?php 
													$in_stock = $settings->get_schedule( 'in_stock', 18 );
													for ( $i = 1; $i <= 24; $i++ ) {
														?>
														<option value="<?php echo $i; ?>" <?php selected( $in_stock, $i ); ?>><?php echo date( "h.iA", strtotime( "$i:00" ) ); ?></option>
														<?php
													}
												?>
											</select>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="api_key"><?php _e( 'Email Template', 'wc-mc-product-stock-manager' ); ?></label>
										</th>
										<td>
											<?php
												$content = $settings->get_message();
												wp_editor( $content, 'email_content' );
											?>
											<p class="description">
												<?php echo sprintf( __( 'Use %s and %s to represent the state of the products and the list of the products', 'wc-mc-product-stock-manager' ), '<strong>{product_state}</strong>', '<strong>{product_list}</strong>' ); ?>
											</p>
										</td>
									</tr>
									<?php
								}
							} else {
								_e( 'Save the MailChimp API key before proceeding', 'wc-mc-product-stock-manager' );
							}
						?>
					</tbody>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function save_settings() {
		$url = admin_url( 'admin.php?page=wc-mc-product-stock-manager' );
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'wc_mc_product_stock_manager' ) ) {
			$settings 		= new WCMCPROD_Core_Settings();
			
			$api_key 		= sanitize_text_field( $_POST['api_key'] );
			$exploded 		= explode( '-', $api_key );
			$data_center 	= end( $exploded );
			$enabled		= isset( $_POST['enabled'] );
			$list_id		= isset( $_POST['list_id'] ) ? sanitize_text_field( $_POST['list_id'] ) : '';
			$content 		= isset( $_POST['email_content'] ) ? wp_kses_post( $_POST['email_content'] ) : '';

			$api 			= new WCMCPROD_Core_Mailchimp( $api_key, $data_center );
			$info 			= $api->get_info();

			if ( is_wp_error( $info ) ) {
				$url = add_query_arg( 'error', 'mc', $url );
				$settings->api_key		= false;
			} else {
				$settings->api_key 		= $api_key;
				$settings->data_center 	= $data_center;
				$settings->email_template = $content;

				$ouf_of_stock_time	= sanitize_text_field( $_POST['ouf_of_stock'] );
				$in_stock_time		= sanitize_text_field( $_POST['in_stock'] );

				$settings->set_schedule( 'in_stock', $in_stock_time );
				$settings->set_schedule( 'ouf_of_stock', $ouf_of_stock_time );
				if ( !empty( $list_id ) ) {
					$create_or_update = false;
					if ( !$settings->email_list ) {
						$settings->email_list 	= $list_id;
						$create_or_update 		= true;
					}
					if ( $settings->email_list !== $list_id ) {
						$create_or_update = true;
					}

					if ( $create_or_update ) {
						if ( empty( $settings->campaign ) ) {
							$ouf_of_stock 	= $api->save_campaign( $list_id, __( 'Products Out Of Stock', 'wc-mc-product-stock-manager' ), __( 'Products Out Of Stock', 'wc-mc-product-stock-manager' ) );
							$in_stock 		= $api->save_campaign( $list_id, __( 'Products In Stock', 'wc-mc-product-stock-manager' ), __( 'Products In Stock', 'wc-mc-product-stock-manager' ) );

							if ( $ouf_of_stock ) {
								$settings->set_campaign( 'ouf_of_stock' , $ouf_of_stock );
							}
							if ( $in_stock ) {
								$settings->set_campaign( 'in_stock' , $in_stock );
							}
						} else {
							$ouf_of_stock 	= $settings->get_campaign( 'ouf_of_stock' );
							$in_stock 		= $settings->get_campaign( 'in_stock' );
							if ( empty( $ouf_of_stock ) ) {
								$ouf_of_stock 	= $api->save_campaign( $list_id, __( 'Products Out Of Stock', 'wc-mc-product-stock-manager' ), __( 'Products Out Of Stock', 'wc-mc-product-stock-manager' ) );
								$settings->set_campaign( 'ouf_of_stock' , $ouf_of_stock );
							} else {
								$api->update_campaign( $ouf_of_stock, $list_id, $content );
							}

							if ( empty( $in_stock ) ) {
								$in_stock 	= $api->save_campaign( $list_id, __( 'Products In Stock', 'wc-mc-product-stock-manager' ), __( 'Products In Stock', 'wc-mc-product-stock-manager' ) );
								$settings->set_campaign( 'in_stock' , $in_stock );
							} else {
								$api->update_campaign( $in_stock, $list_id, $content );
							}
						}
					} else {
						$ouf_of_stock 	= $settings->get_campaign( 'ouf_of_stock' );
						$in_stock 		= $settings->get_campaign( 'in_stock' );
						if ( empty( $ouf_of_stock ) ) {
							$ouf_of_stock 	= $api->save_campaign( $list_id, __( 'Products Out Of Stock', 'wc-mc-product-stock-manager' ), __( 'Products Out Of Stock', 'wc-mc-product-stock-manager' ) );
							if ( $ouf_of_stock ) {
								$settings->set_campaign( 'ouf_of_stock' , $ouf_of_stock );
								$api->update_campaign( $ouf_of_stock, $list_id, $content );
							}
						}

						if ( empty( $in_stock ) ) {
							$in_stock 	= $api->save_campaign( $list_id, __( 'Products In Stock', 'wc-mc-product-stock-manager' ), __( 'Products In Stock', 'wc-mc-product-stock-manager' ) );
							if ( $in_stock ) {
								$settings->set_campaign( 'in_stock' , $in_stock );
								$api->update_campaign( $in_stock, $list_id, $content );
							}
						}
					}
				}
			}
			$settings->enabled = $enabled;
			$settings->save();

			$url = add_query_arg( 'success', 'true', $url );
		} else {
			$url = add_query_arg( 'error', 'n', $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Action based on stock status
	 * Update the database to send out the product data
	 * 
	 * @param int $product_id - the product id
	 * @param string $status - the product status
	 * @param WC_Product $product - the product
	 * 
	 * @since 1.0.0
	 */
	public function action_based_on_stock_status( $product_id, $status, $product ) {
		$service = new WCMCPROD_Core_Products();
		if ( !$product ) {
			$product = wc_get_product( $product_id );
		}
		if ( $status == 'instock' ) {
			$save_id = $service->get_product( $product_id );
			if ( !$save_id ) {
				$service->save_product( $product_id, $product->get_name(), $product->get_permalink(), 'in_stock' );
			} else {
				$service->update_product( $save_id, $product->get_name(), $product->get_permalink(), 'in_stock' );
			}
		} else if ( $status == 'outofstock' ) {
			$save_id = $service->get_product( $product_id );
			if ( !$save_id ) {
				$service->save_product( $product_id, $product->get_name(), $product->get_permalink(), 'out_of_stock' );
			} else {
				$service->update_product( $save_id, $product->get_name(), $product->get_permalink(), 'out_of_stock' );
			}
		} else if ( $status == 'instock' ) {
			$save_id = $service->get_product( $product_id );
			if ( $save_id ) {
				$service->delete_product( $save_id );
			}
		}
	}
}
?>