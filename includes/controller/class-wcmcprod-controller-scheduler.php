<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Scheduler
 * Handles all product hooks and saves them in one place for email
 * 
 * @since 1.0.0
 */
class WCMCPROD_Controller_Scheduler {

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
		add_action( 'wc_mc_product_stock_manager_send_report', array( &$this, 'maybe_send_export' ) );
	}

	/**
	 * Send report once daily
	 */
	public function maybe_send_export( $force = false ) {
		$settings 	= new WCMCPROD_Core_Settings();
		$send 		= $force ? true : $settings->enabled;
		if ( $send && $settings->api_key && $settings->email_list ) {
			$list_id 			= $settings->email_list;
			$api 				= new WCMCPROD_Core_Mailchimp( $settings->api_key, $settings->data_center );

			$current_date		= date_i18n( get_option( 'date_format' ), current_time( 'timestamp' ) );
			$out_of_stock_title	= sprintf( __( 'Products Out Of Stock - %s', 'wc-mc-product-stock-manager' ), $current_date );
			$in_stock_title		= sprintf( __( 'Products In Stock - %s', 'wc-mc-product-stock-manager' ), $current_date );

			$sent_forced 		= true;
			$is_send 			= true;
			
			$in_stock 			= $api->save_campaign( $list_id, $in_stock_title, $in_stock_title, $settings->from_email, $settings->from_name );
			if ( $force ) {
				$is_send = true;
			}
			if ( !$in_stock ) {
				$is_send = false;
			}
			if ( $is_send ) {
				$products 	= $this->get_products( 'in_stock', __( 'In Stock', 'wc-mc-product-stock-manager' ) );
				$to_send 	= $this->replace_content( __( 'In Stock', 'wc-mc-product-stock-manager' ), $products, $settings->get_message() );

				$updated 	= $api->update_campaign( $in_stock, $to_send );
				if ( $updated ) {
					$sent 	= $api->send_campaign( $in_stock );
					if ( $sent && !$force ) {
						
					} else {
						if ( !$sent && $force ) {
							$sent_forced = false;
						}
					}
				} else {
					if ( $force ) {
						$sent_forced = false;
					}
				}
				//$api->delete_campaign( $in_stock );
			}

			$ouf_of_stock 	= $api->save_campaign( $list_id, $out_of_stock_title, $out_of_stock_title, $settings->from_email, $settings->from_name );
			$is_send 		= true;
			if ( $force ) {
				$is_send = true;
			}

			if ( !$ouf_of_stock ) {
				$is_send = false;
			}
			if ( $is_send ) {
				$products 	= $this->get_products( 'out_of_stock', __( 'Out Of Stock', 'wc-mc-product-stock-manager' ) );
				$to_send 	= $this->replace_content( __( 'Out Of Stock', 'wc-mc-product-stock-manager' ), $products, $settings->get_message() );

				$updated 	= $api->update_campaign( $ouf_of_stock, $to_send );
				if ( $updated ) {
					$sent 	= $api->send_campaign( $ouf_of_stock );
					if ( $sent && !$force ) {
						
					} else {
						if ( !$sent && $force ) {
							$sent_forced = false;
						}
					}
				}  else {
					if ( $force ) {
						$sent_forced = false;
					}
				}
				//$api->delete_campaign( $ouf_of_stock );
				
			}
			$settings->save();

			if ( $force && !$sent_forced ) {
				wp_send_json_error( __( 'Error sending test email', 'wc-mc-product-stock-manager' ) );
			}
		}
	}

	/**
	 * Replace content with place holders
	 * 
	 * @return string
	 */
	private function replace_content( $state, $list, $content ) {
		$find    = array( '{product_state}', '{product_list}' );
		$replace = array( $state, $list );
		return str_replace( $find, $replace, $content );
	}

	/**
	 * Get Products HTML
	 * 
	 * @param string $status - the product status
	 * 
	 * @since 1.0.0
	 * 
	 * @return string
	 */
	private function get_products( $status, $status_text ) {
		$service 	= new WCMCPROD_Core_Products();
		$products 	= $service->get_all_products( $status );
		if ( is_array( $products ) && !empty( $products ) ) {
			$list 	= "<ul>";
			$ids 	= array();
			foreach ( $products as $product ) {
				$list .= "<li><a href='{$product->product_url}' target='_blank'>{$product->product_name}</a></li>";
				$ids[] = $product->id;
			}
			$list .= "</ul>";
			if ( $status == 'in_stock' ) {
				$service->delete_products( $ids );
			}
			return $list;
		} else {
			return sprintf( __( 'No new products found that are %s', 'wc-mc-product-stock-manager' ), $status_text );
		}
	}
}
?>