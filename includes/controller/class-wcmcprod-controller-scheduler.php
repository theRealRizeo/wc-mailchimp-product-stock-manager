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
		add_action( 'init', array( &$this, 'schedule_email_sender' ) );

		add_action( 'wc_mc_product_stock_manager_send_report', array( &$this, 'maybe_send_export' ) );
	}

	/**
	 * Set the email sender
	 * 
	 * @since 1.0.0
	 */
	public function schedule_email_sender() {
		if ( ! wp_next_scheduled( 'wc_mc_product_stock_manager_send_report' ) ) {
			wp_schedule_single_event( time(), 'wc_mc_product_stock_manager_send_report' );
		}
	}

	public function maybe_send_export( $force = false ) {
		$settings = new WCMCPROD_Core_Settings();
		if ( $settings->enabled && $settings->api_key && $settings->email_list ) {
			$list_id 			= $settings->email_list;
			$api 				= new WCMCPROD_Core_Mailchimp( $settings->api_key, $settings->data_center );
			$schedule 			= $settings->schedule; //The time of the day
			$ouf_of_stock 		= $settings->get_schedule( 'ouf_of_stock', 18 );
			$in_stock 			= $settings->get_schedule( 'in_stock', 18 );

			$last_sent_stock 	= $settings->get_last_sent( 'in_stock', null ); //last sent
			$last_sent_oos 		= $settings->get_last_sent( 'ouf_of_stock', null ); //last sent

			$next_sent_stock 	= strtotime( '+24 hours', $last_sent_stock );
			$next_sent_stock 	= date( 'Y-m-d', $next_sent_stock ) . ' ' . $in_stock;

			$next_sent_oos 		= strtotime( '+24 hours', $last_sent_oos );
			$next_sent_oos 		= date( 'Y-m-d', $next_sent_oos ) . ' ' . $ouf_of_stock;

			$is_send 			= current_time( 'timestamp' ) > strtotime( $next_sent_stock );

			$ouf_of_stock 		= $settings->get_campaign( 'ouf_of_stock' );
			$in_stock 			= $settings->get_campaign( 'in_stock' );
			if ( $force ) {
				$is_send = true;
			}
			if ( empty( $in_stock ) ) {
				$is_send = false;
			}
			$content = $settings->email_template;
			if ( $is_send ) {
				$products 	= $this->get_products( 'in_stock', __( 'In Stock', 'wc-mc-product-stock-manager' ) );
				$to_send 	= $this->replace_content( __( 'In Stock', 'wc-mc-product-stock-manager' ), $products, $content );

				$updated 	= $api->update_campaign( $in_stock, $list_id, $to_send );
				if ( $updated ) {
					$sent 	= $api->send_campaign( $in_stock );
					if ( $sent ) {
						$settings->set_last_sent( 'in_stock', current_time( 'timestamp' ) );
					}
				}
			}

			$is_send = current_time( 'timestamp' ) > strtotime( $next_sent_oos );
			if ( $force ) {
				$is_send = true;
			}

			if ( empty( $ouf_of_stock ) ) {
				$is_send = false;
			}
			if ( $is_send ) {
				$products 	= $this->get_products( 'out_of_stock', __( 'Out Of Stock', 'wc-mc-product-stock-manager' ) );
				$to_send 	= $this->replace_content( __( 'Out Of Stock', 'wc-mc-product-stock-manager' ), $products, $content );

				$updated 	= $api->update_campaign( $ouf_of_stock, $list_id, $to_send );
				if ( $updated ) {
					$sent 	= $api->send_campaign( $ouf_of_stock );
					if ( $sent ) {
						$settings->set_last_sent( 'ouf_of_stock', current_time( 'timestamp' ) );
					}
				}
				
			}
			$settings->save();
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