<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Main Plugin settings
 * 
 * @since 1.0.0
 */
class WCMCPROD_Core_Settings {

	/**
	 * Enabled status
	 * 
	 * @since 1.0.0
	 * 
	 * @var bool
	 */
	public $enabled = false;

    /**
     * Mailchimp API Key
     * 
     * @since 1.0.0
     * 
     * @var string
     */
    public $api_key;

    /**
     * Mailchimp Data Center
     * 
     * @since 1.0.0
     * 
     * @var string
     */
    public $data_center;

    /**
     * Mailchimp Email List id
     * 
     * @since 1.0.0
     * 
     * @var string
     */
    public $email_list;

    /**
     * Mailchimp Campaign ID
     * 
     * @since 1.0.0
     * 
     * @var string
     */
    public $campaign_id;

    /**
     * Schedule to send out the email
     * 
     * @since 1.0.0
     * 
     * @var array
     */
	public $schedule = array();
	

    /**
     * HTML content of email
     * This is synched to the campaign
     * 
     * @since 1.0.0
     * 
     * @var string
     */
    public $email_template;

    /**
     * Last send date of schedule
     * 
     * @since 1.0.0
     * 
     * @var array
     */
    public $last_sent = array();

    /**
	 * Main constructor
	 *
	 * Load and assign options
	 */
	public function __construct() {
		$this->_load();
    }

    /**
	 * Plugin option key
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function option_key() {
		return 'wc_mc_product_stock_manager_settings';
	}
    
    /**
	 * Load model
	 *
	 * @since 1.0.0
	 */
	private function _load() {
		$option_key = $this->option_key();
		$settings   = get_option( $option_key );
		$this->_import( $settings );
	}

	/**
	 * Import data to option
	 *
	 * @param array $data
	 */
	private function _import( $data ) {
		if ( $data ) {
			foreach ( $data as $key => $value ) {
				if ( $value ) {
					$value = maybe_unserialize( $value );
				}

				if ( null !== $value ) {
					$this->set_field( $key, $value );
				}
			}
		}
	}

	/**
	 * Set field value, bypassing the __set validation.
	 *
	 * Used for loading from db.
	 *
	 * @since  1.0.0
	 *
	 * @param string $field
	 * @param mixed  $value
	 */
	public function set_field( $field, $value ) {
		// Don't deserialize values of "private" fields.
		if ( '_' !== $field[0] ) {

			// Only set values of existing fields, don't create a new field.
			if ( property_exists( $this, $field ) ) {
				$this->$field = $value;
			}
		}
    }
    
    /**
	 * Save Settings
	 *
	 * @since  1.0.0
	 */
	public function save() {
		$settings = array(
			'enabled'			=> $this->enabled,
			'api_key'           => $this->api_key,
			'data_center'       => $this->data_center,
            'email_list'        => $this->email_list,
            'campaign_id'       => $this->campaign_id,
            'schedule'          => $this->schedule,
            'email_template'    => $this->email_template,
            'last_sent'         => $this->last_sent
		);
		update_option( $this->option_key(), $settings );
	}

	/**
	 * Reads the options from options table
	 *
	 * @since  1.0.0
	 */
	public function refresh() {
		$this->_load();
	}

	/**
	 * Get Schedule
	 */
	public function get_schedule( $key, $default = '' ) {
		if ( isset( $this->schedule[ $key ] ) ) {
			return $this->schedule[ $key ];
		}
		return $default;
	}

	/**
	 * Get last sent
	 */
	public function get_last_sent( $key, $default = '' ) {
		if ( isset( $this->last_sent[ $key ] ) ) {
			return $this->last_sent[ $key ];
		}
		return $default;
	}

	public function get_message() {
		if ( empty( $this->email_template ) ) {
			return "
				Hello,<br/>
				The following products are {product_state} : <br/>
				{product_list} <br/>
			";
		}
		return $this->email_template;
	}
}
?>