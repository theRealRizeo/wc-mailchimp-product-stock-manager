<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Products class manager
 * 
 * @since 1.0.0
 */
class WCMCPROD_Core_Products {

    /**
     * The table name
     */
    private $table_name;

    public function __construct() {
        $this->table_name = WCMCPROD_Core_Database::get_table_name( WCMCPROD_Core_Database::PRODUCTS );
    }

    /**
     * Get products by status
     * 
     * @param string $status - in_stock or out_of_stock
     * 
     * @since 1.0.0
     * 
     * @return string
     */
    public function get_products( $status ) {
        global $wpdb;
        $sql        = "SELECT `id`, `product_id`, `product_name`, `product_url` FROM {$this->table_name} WHERE `status` = %s AND `processed` = 'pending'";
        $products   = $wpdb->get_results( $wpdb->prepare( $sql, $status ) );
        return $products;
    }

    /**
     * Update ids to processed
     * 
     * @param array $ids 
     * 
     * @since 1.0.0
     */
    public function update_processed( $ids = array() ) {
        global $wpdb;
        $str = implode( ",", $ids );
        $sql = "UPDATE {$this->table_name} SET `processed` = 'sent' WHERE `id` IN( $str )";
        $wpdb->query( $sql );
    }

    /**
     * Save Product
     */
    public function save_product( $product_id, $name, $url, $status ) {
        global $wpdb;
        $insert_id = $wpdb->insert( $this->table_name, array(
            'product_id'    => $product_id,
            'product_name'  => $name,
            'product_url'   => $url,
			'status'    	=> $status,
			'processed'    	=> 'pending',
            'date_created'  => date_i18n( 'Y-m-d H:i:s' )
		) );
		if ( $insert_id ) {
			return true;
		}
		return false;
	}

	/**
	 * Get product insert by id
	 * 
	 * @param int $product_id - the product id
	 * 
	 * @since 1.0.0
	 * 
	 * @return int|bool
	 */
	public function get_product( $product_id ) {
		global $wpdb;
		$sql 		= "SELECT `id` FROM {$this->table_name} WHERE `product_id` = %d";
		$product 	= $wpdb->get_row( $wpdb->prepare( $sql, $product_id ) );
		if ( $product ) {
			return $product;
		}
		return false;
	}

	/**
	 * Update product
	 */
	public function update_product( $id, $name, $url, $status ){
		global $wpdb;
		$wpdb->update( $this->table_name, array(
			'product_name'  => $name,
            'product_url'   => $url,
			'status'    	=> $status,
			'processed'    	=> 'pending',
			'date_updated'  => date_i18n( 'Y-m-d H:i:s' )
		), array( 'id' => $id ) );
	}
}
?>