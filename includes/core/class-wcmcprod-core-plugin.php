<?php
/**
 * Main plugin loader
 * Loads and set up the plugin
 * 
 * @since 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Core plugin
 */
class WCMCPROD_Core_Plugin {

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

	/**
	 * Main plugin loader
	 * 
	 * Loads all required plugin files and set up admin pages
	 */
	public function __construct() {
        // Plugin activation Hook.
		register_activation_hook(
			WCMCPROD_PLUGIN_FILE,
			array( $this, 'plugin_activation' )
		);

		//Plugin deactivation hook
		register_deactivation_hook(
			WCMCPROD_PLUGIN_FILE,
			array( $this, 'plugin_deactivation' )
		);

		add_action(
			'setup_theme',
			array( $this, 'setup_controller' )
		);
    }

    /**
	 * Plugin activation hook
	 * Called during activation mainly to set up DB tables
	 * 
	 * @since 1.0.0
	 */
	function plugin_activation() {
		//Create database
		WCMCPROD_Core_Database::init();

		//Create debug directory
		WCMCPROD_Core_Debug::init_directory();
	}

	/**
	 * Plugin deactivation hook
	 * Called during deactivation to remove things still in use
	 * 
	 * @since 1.0.0
	 */
	function plugin_deactivation() {
		//unset schedule
		if ( wp_next_scheduled( 'wc_mc_product_stock_manager_send_report' ) ) {
			wp_clear_scheduled_hook( 'wc_mc_product_stock_manager_send_report' );
		}
	}

	/**
	 * Set up controller
	 * 
	 * @since 1.0.0
	 */
	public function setup_controller() {
		WCMCPROD_Controller_Settings::instance();
		WCMCPROD_Controller_Scheduler::instance();
	}
}
?>