<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Core database set up
 * 
 * @since 1.0.0
 */
class WCMCPROD_Core_Database {

	const PRODUCTS	= 'products';

	/**
	 * Current tables
	 */
	private static $tables = array();

	/**
	 * Get all the used table names
	 *
	 * @since 1.0
	 * @return array
	 */
	private static function table_names( $db = false ) {
		if ( ! $db ) {
			global $wpdb;
			$db = $wpdb;
		}

		return array(
			self::PRODUCTS => $db->prefix . 'wc_mc_product_stock'
		);
	}

	/**
	 * Get Table Name
	 *
	 * @since 1.0
	 * @param string $name - the name of the table
	 *
	 * @return string/bool
	 */
	public static function get_table_name( $name ) {
		if ( empty( self::$tables ) ) {
			self::$tables = self::table_names();
		}
		return isset( self::$tables[ $name ] ) ? self::$tables[ $name ] : false;
	}

	/**
	 * Create tables
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		global $wpdb;

		$wpdb->hide_errors();

		$max_index_length = 191;
		$charset_collate  = $wpdb->get_charset_collate();

		// Product table
		$table_name = self::get_table_name( self::PRODUCTS );
		if ( $table_name ) {
			$sql = "CREATE TABLE {$table_name} (
				`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				`product_id` VARCHAR($max_index_length) NULL,
				`product_name` TEXT NOT NULL,
				`product_url` TEXT NOT NULL,
				`status` enum('in_stock','out_of_stock') DEFAULT 'in_stock',
				`processed` enum('sent','pending') DEFAULT 'pending',
				`date_created` datetime NOT NULL default '0000-00-00 00:00:00',
				`date_updated` datetime NOT NULL default '0000-00-00 00:00:00',
				PRIMARY KEY (`id`),
				KEY `wc_mc_product_id` (`product_id` ASC),
				KEY `wc_mc_product_id_status` (`product_id` ASC, `status` ASC),
				KEY `wc_mc_product_id_processed` (`product_id` ASC, `processed` ASC),
				KEY `wc_mc_product_id_status_processed` (`product_id` ASC, `status` ASC, `processed` ASC))
				$charset_collate;";
			dbDelta( $sql );
		}

	}
}
?>