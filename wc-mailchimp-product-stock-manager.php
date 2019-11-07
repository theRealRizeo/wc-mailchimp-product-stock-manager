<?php
/**
 * Plugin Name:         WooCommerce MailChimp Product Stock Status
 * Description:         Send email notifications when WooCommerce products are in stock or out of stock
 * Version:             1.0.0
 * Author:              Paul Kevin
 * Author URI:          https://www.hubloy.com
 * License:             GPLv2
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:         wc-mc-product-stock-manager
 * Domain Path:         /languages/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_MC_Product_Stock_Manager' ) ) :

	/**
	 * Main plugin class
	 *
	 * Main entry point of the plugin
	 * Definess variables and constants needed to run the plugin and loads
	 * the main plugin class
	 *
	 * @since 1.0.0
	 */
	class WC_MC_Product_Stock_Manager {

		/**
		 * Current plugin version.
		 *
		 * @since 1.0.0
		 * 
		 * @var string
		 */
		public $version = '1.0.0';

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
		 * Main plugin constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->define_constants();

			//Define autoloader
			$this->auto_load();

			//Initiate plugin
			WCMCPROD_Core_Plugin::instance();
		}


		/**
		 * Define plugin constants
		 *
		 * @since 1.0.0
		 */
		protected function define_constants() {
			$upload_dir = wp_upload_dir();
			$this->define( 'WCMCPROD_VERSION', $this->version );
			$this->define( 'WCMCPROD_PLUGIN_FILE', __FILE__ );
			$this->define( 'WCMCPROD_PLUGIN', plugin_basename( __FILE__ ) );
			$this->define( 'WCMCPROD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
			$this->define( 'WCMCPROD_PLUGIN_BASE_DIR', dirname( __FILE__ ) );
			$this->define( 'WCMCPROD_PLUGIN_INCLUDES_DIR', WCMCPROD_PLUGIN_BASE_DIR . '/includes/' );
			$this->define( 'WCMCPROD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
			$this->define( 'WCMCPROD_LOG_DIR', $upload_dir['basedir'] . '/wc-mc-logs/' );
		}

		/**
		 * Define constant helper if not already set
		 *
		 * @param  string $name
		 * @param  string|bool $value
		 *
		 * @since 1.0.0
		 */
		private function define( $name, $value ) {
			if ( ! defined( $name ) ) {
				define( $name, $value );
			}
		}


		/**
		 * Load plugin 
		 * 
		 * @since 1.0.0
		 */
		private function auto_load() {
			spl_autoload_register( array( &$this, '_autoload' ) );
		}

		/**
		 * Set up the class loader
		 * 
		 * @param $class
		 */
		public function _autoload( $class ) {
			$class = trim( $class );

			if ( 'WCMCPROD_' == substr( $class, 0, 9 ) ) {
				$path_array 	= explode( '_', $class );
				array_shift( $path_array ); // Remove the 'BDRP' prefix from path.
				$alt_dir 		= array_pop( $path_array );
				$sub_path 		= implode( '/', $path_array );

				$filename 		= str_replace( '_', '-', 'class-' . $class . '.php' );
				$file_path 		= trim( strtolower( $sub_path . '/' . $filename ), '/' );
				$file_path_alt 	= trim( strtolower( $sub_path . '/' . $alt_dir . '/' . $filename ), '/' );
				$candidates 	= array();
				$candidates[] 	= WCMCPROD_PLUGIN_INCLUDES_DIR . $file_path;
				$candidates[] 	= WCMCPROD_PLUGIN_INCLUDES_DIR . $file_path_alt;

				foreach ( $candidates as $path ) {
					$current_file = basename( $path ); 
					if ( is_file( $path ) ) {
						include_once $path;
						return true;
					}
				}
			}
		}
	}

	WC_MC_Product_Stock_Manager::instance();

endif;
?>