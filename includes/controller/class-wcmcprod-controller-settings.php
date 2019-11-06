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
								$lists 		= $api->get_lists( 0, 100 );
								if ( is_wp_error( $lists ) ) {
									_e( 'Error fetching lists, please ensure that your MailChimp API key is correct', 'wc-mc-product-stock-manager' );
								} else {
									$_lists  	= $response->lists;
									if ( is_array( $_lists ) ) {
										$drop_down = wp_list_pluck( $_lists, 'name', 'id' );
									}
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
												$ouf_of_stock = $settings->get_schedule( 'ouf_of_stock' );
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
												$in_stock = $settings->get_schedule( 'in_stock' );
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
											<?php ?>
										</p>
									</td>
								</tr>
								<?php
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

			

			$api 			= new WCMCPROD_Core_Mailchimp( $api_key, $data_center );
			$info 			= $api->get_info();

			if ( is_wp_error( $info ) ) {
				$url = add_query_arg( 'error', 'mc', $url );
			} else {
				$settings->api_key 		= $api_key;
				$settings->data_center 	= $data_center;
				$settings->save();
			}

			$url = add_query_arg( 'success', 'true', $url );
		} else {
			$url = add_query_arg( 'error', 'n', $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	public function action_based_on_stock_status( $product_id, $status, $product ) {
		if ( $status == 'instock' ) {

		} else if ( $status == 'outofstock' ) {

		}
	}
}
?>