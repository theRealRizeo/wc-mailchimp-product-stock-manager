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

		add_action( 'wp_ajax_wc_mc_product_stock_manager_test', array( $this, 'test_sending' ) );

		add_action( 'wp_ajax_wc_mc_product_stock_manager_sync_products', array( $this, 'sync_products' ) );

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
				if ( $settings->api_key && !empty( $settings->from_email ) && !empty( $settings->from_name ) && !empty( $settings->email_list ) ) {
					?>
					<a href="#" class="button button-primary wc_mc_product_stock_manager_test"><?php _e( 'Send Test Emails', 'wc-mc-product-stock-manager' ); ?></a>
					<?php
				}
			?>
			<a href="#" class="button button-primary wc_mc_product_stock_manager_sync"><?php _e( 'Sync Out Of Stock Products', 'wc-mc-product-stock-manager' ); ?></a>
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
							<th scope="row"><?php _e( 'Debug', 'wc-mc-product-stock-manager' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">
										<span><?php _e( 'Debug', 'wc-mc-product-stock-manager' ); ?></span>
									</legend>
									<label for="debug">
										<input name="debug" type="checkbox" id="debug" value="1" <?php checked( $settings->debug, 1 ); ?> />
									</label>
								</fieldset>
								<p class="description">
									<?php _e( 'Use this to log responses from the api. This logs only the sending process', 'wc-mc-product-stock-manager' ); ?>
								</p>
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
											<label for="schedule_run"><?php _e( 'Next Run Time', 'wc-mc-product-stock-manager' ); ?></label>
										</th>
										<td>
											<?php
												$timestamp 		= wp_next_scheduled( 'wc_mc_product_stock_manager_send_report' );
												echo date_i18n( get_option('date_format') . ' ' . get_option('time_format') . ' (P)', $timestamp );
											?>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="from_email"><?php _e( 'MailChimp Campaign Reply To Email', 'wc-mc-product-stock-manager' ); ?></label>
										</th>
										<td>
											<input name="from_email" type="text" id="from_email" value="<?php echo $settings->from_email; ?>" class="regular-text" required/>
											<p class="description">
												<?php _e( 'Reply to email', 'wc-mc-product-stock-manager' ); ?>
											</p>
										</td>
									</tr>
									<tr>
										<th scope="row">
											<label for="from_name"><?php _e( 'MailChimp Campaign From Name', 'wc-mc-product-stock-manager' ); ?></label>
										</th>
										<td>
											<input name="from_name" type="text" id="from_name" value="<?php echo $settings->from_name; ?>" class="regular-text" required/>
											<p class="from_name">
												<?php _e( 'Name shown when sending', 'wc-mc-product-stock-manager' ); ?>
											</p>
										</td>
									</tr>
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
											<label for="api_key"><?php _e( 'Email Template', 'wc-mc-product-stock-manager' ); ?></label>
										</th>
										<td>
											<?php
												$content 	= $settings->get_message();
												$settings  = array( 'media_buttons' => false );
												wp_editor( $content, 'email_content', $settings );
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
		<script type="text/javascript">
			jQuery(function($) {
				$('body').on('click', 'a.wc_mc_product_stock_manager_test', function(e){
					e.preventDefault();
					var $button = $(this),
						$btn_txt = $button.text(),
						$nonce = '<?php echo wp_create_nonce( 'wc_mc_product_stock_manager_test' ); ?>';

					$button.attr('disabled', 'disabled');
					$button.html('<span class="spinner is-active"></span>');
					$.post(
						window.ajaxurl,
						{ 'action' : 'wc_mc_product_stock_manager_test', '_wpnonce' : $nonce }
					).done( function( response ) {
						$button.removeAttr('disabled');
						$button.html($btn_txt);
						alert( response.data );
					}).fail(function(xhr, status, error) {
						$button.removeAttr('disabled');
						$button.html($btn_txt);
						alert( 'An error occured' );
					});
				});
				$('body').on('click', 'a.wc_mc_product_stock_manager_sync', function(e){
					e.preventDefault();
					var $button = $(this),
						$btn_txt = $button.text(),
						$nonce = '<?php echo wp_create_nonce( 'wc_mc_product_stock_manager_sync_products' ); ?>';

					$button.attr('disabled', 'disabled');
					$button.html('<span class="spinner is-active"></span>');
					$.post(
						window.ajaxurl,
						{ 'action' : 'wc_mc_product_stock_manager_sync_products', '_wpnonce' : $nonce }
					).done( function( response ) {
						$button.removeAttr('disabled');
						$button.html($btn_txt);
						alert( response.data );
					}).fail(function(xhr, status, error) {
						$button.removeAttr('disabled');
						$button.html($btn_txt);
						alert( 'An error occured' );
					});
				});
				
			});
		</script>
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
			$debug			= isset( $_POST['debug'] );
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
				$from_email			= sanitize_text_field( $_POST['from_email'] );
				$from_name			= sanitize_text_field( $_POST['from_name'] );

				$settings->from_email 	= $from_email;
				$settings->from_name 	= $from_name;
				if ( !empty( $list_id ) ) {
					if ( !$settings->email_list ) {
						$settings->email_list = $list_id;
					}
				}
			}
			$settings->enabled 	= $enabled;
			$settings->debug	= $debug;
			$settings->save();

			$url = add_query_arg( 'success', 'true', $url );
		} else {
			$url = add_query_arg( 'error', 'n', $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Test Sending
	 */
	public function test_sending() {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'wc_mc_product_stock_manager_test' ) ) {
			do_action( 'wc_mc_product_stock_manager_send_report', true );
			wp_send_json_success( __( 'Email sent via MailChimp', 'wc-mc-product-stock-manager' ) );
		} else {
			wp_send_json_error( __( 'Error scheduling a test. Please refresh the page and try again', 'wc-mc-product-stock-manager' ) );
		}
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
		} else if ( $status == 'onbackorder' ) {
			$save_id = $service->get_product( $product_id );
			if ( $save_id ) {
				$service->delete_product( $save_id );
			}
		}
	}

	/**
	 * Sync products out of stock
	 * 
	 * Called on first run
	 */
	public function sync_products() {
		if ( wp_verify_nonce( $_POST['_wpnonce'], 'wc_mc_product_stock_manager_sync_products' ) ) {
			$args = array(
				'post_type' => 'product',
				'posts_per_page' => -1,
				'post_status' => 'publish',
				'meta_query' => array(
				array(
					'key' => '_stock_status',
					'value' => 'outofstock',
					'compare' => '='
				)
				),
				'fields' => 'ids',
			);
			$service 		= new WCMCPROD_Core_Products();
			$product_ids 	= get_posts( $args ); 

			foreach ( $product_ids as $product_id ) {
				$product = wc_get_product( $product_id );
				$save_id = $service->get_product( $product_id );
				if ( !$save_id ) {
					$service->save_product( $product_id, $product->get_name(), $product->get_permalink(), 'out_of_stock' );
				} else {
					$service->update_product( $save_id, $product->get_name(), $product->get_permalink(), 'out_of_stock' );
				}
			}

			wp_send_json_success( sprintf( __( ' %d products synced', 'wc-mc-product-stock-manager' ), count( $product_ids ) ) );
		} else {
			wp_send_json_error( __( 'Not allowed', 'wc-mc-product-stock-manager' ) );
		}
	}
}
?>