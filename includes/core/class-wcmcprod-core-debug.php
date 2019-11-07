<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Core debug
 * 
 * @since 1.0.0
 */
class WCMCPROD_Core_Debug {


	/**
	 * The single instance of the class
	 *
	 * @since 1.0.0
	 */
	protected static $_instance = null;
	 
	 
	/**
	 * Get the instance
	 * 
	 * @since 1.0.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Debug constructor
	 * Set up log directory for debug purposes
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( !is_dir( WCMCPROD_LOG_DIR ) ) {
			wp_mkdir_p( WCMCPROD_LOG_DIR );
		}

		if ( ! is_file( WCMCPROD_LOG_DIR . 'index.php' ) ) {
			//create a blank index file
			file_put_contents( WCMCPROD_LOG_DIR . 'index.php', '' );
		}
	}

	/**
	 * Set up log directory
	 * 
	 * @since 1.0.0
	 */
	public static function init_directory() {
		if ( !is_dir( WCMCPROD_LOG_DIR ) ) {
			wp_mkdir_p( WCMCPROD_LOG_DIR );
		}

		if ( ! is_file( WCMCPROD_LOG_DIR . 'index.php' ) ) {
			//create a blank index file
			file_put_contents( WCMCPROD_LOG_DIR . 'index.php', '' );
		}
	}

	/**
	 * Returns the debugging status. False means no debug output is made.
	 *
	 * @since  1.0.0
	 *
	 * @return bool
	 */
	public function is_enabled() {
        $settings 	= new WCMCPROD_Core_Settings();
		return $settings->debug;
	}

	/**
	 * Do logging
	 *
	 * @param mixed <dynamic> Each param will be dumped
	 */
	public function log( $message ) {
		if ( $this->is_enabled() ) {
			$log_time	= date( "Y-m-d\tH:i:s\t" );
			$log_file 	= date( "Y-m-d" );
			$log_file 	= WCMCPROD_LOG_DIR . '/' . $log_file . '_wc_mailchimp.log';
			foreach ( func_get_args() as $param ) {
				if ( is_scalar( $param ) ) {
					$dump = $param;
				} else {
					$dump = var_export( $param, true );
				}
				error_log( $log_time . $dump . "\n", 3, $log_file );
			}
		}
	}
}

?>