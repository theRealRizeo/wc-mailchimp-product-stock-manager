<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Mailchimp API integration
 * 
 * @since 1.0.0
 */
class WCMCPROD_Core_Mailchimp {

    /**
     * MailChimp API key
     * 
     * @since 1.0.0
     * 
     * @var string
     */
    private $_api_key;

    /**
     * MailChimp data center
     * 
     * @since 1.0.0
     * 
     * @var string
     */
	private $_data_center;
	
	/**
	 * The user display name
	 * 
	 * @since 1.0.0
	 * 
	 * @var string
	 */
	private $_user;

    /**
     * The <dc> part of the URL corresponds to the data center for your account. 
     * For example, if the last part of your Mailchimp API key is us6, 
     * all API endpoints for your account are available at https://us6.api.mailchimp.com/3.0/.
     * 
     * @since 1.0.0
     * 
     * @var string
     */
    private $_endpoint = 'https://<dc>.api.mailchimp.com/3.0/';
    
    /**
	 * Constructs class with required data
	 *
	 * Hustle_Mailchimp_Api constructor.
	 * @param $api_key
	 */
	public function __construct( $api_key, $data_center ) {
		$this->_api_key     = $api_key;
		$this->_data_center = $data_center;
		$this->_endpoint    = str_replace( '<dc>', $data_center, $this->_endpoint );
		$this->_user 		= is_user_logged_in() ? wp_get_current_user()->display_name : 'admin';
	}

	/**
	 * Sends request to the endpoint url with the provided $action
	 *
	 * @param string $verb
	 * @param string $action rest action
	 * @param array $args
	 * 
	 * @since 1.0.0
	 * 
	 * @return object|WP_Error
	 */
	private function _request( $verb = "GET", $action, $args = array() ){
		$url = trailingslashit( $this->_endpoint )  . $action;

		$_args = array(
			"method" => $verb,
			"headers" =>  array(
				'Authorization' => 'apikey '. $this->_api_key,
				'Content-Type' => 'application/json;charset=utf-8'
				//'X-Trigger-Error' => 'APIKeyMissing',
			)
		);

		if( "GET" === $verb ){
			$url .= ( "?" . http_build_query( $args ) );
		}else{
			$_args['body'] = wp_json_encode( $args['body'] );
		}

		$res = wp_remote_request( $url, $_args );

		if ( !is_wp_error( $res ) && is_array( $res ) ) {
			if ( $res['response']['code'] <= 204 ) {
				return json_decode(  wp_remote_retrieve_body( $res ) );
			}
			$err = new WP_Error();
			$err->add( $res['response']['code'], $res['response']['message'], $res['body'] );
			return  $err;
		}

		return $res;
	}

	/**
	 * Get User Info for the current API KEY
	 *
	 * @param $fields
	 * @return array|mixed|object|WP_Error
	 */
	public function get_info( $fields = array() ) {
		if ( empty( $fields ) ) {
			$fields = array( 'account_id', 'account_name', 'email' );
		}
 
		return $this->_request(
			'GET',
			'',
			array(
				'fields' => implode( ',', $fields ),
			)
		);
	}

	/**
	 * Sends rest GET request
	 *
	 * @param $action
	 * @param array $args
	 * 
	 * @since 1.0.0
	 * 
	 * 
	 * @return array|mixed|object|WP_Error
	 */
	private function _get( $action, $args = array() ){
		return $this->_request( "GET", $action, $args );
	}

	/**
	 * Sends rest GET request
	 *
	 * @param $action
	 * @param array $args
	 * 
	 * @since 1.0.0
	 * 
	 * 
	 * @return array|mixed|object|WP_Error
	 */
	private function _delete( $action, $args = array() ){
		return $this->_request( "DELETE", $action, $args );
	}

	/**
	 * Sends rest POST request
	 *
	 * @param $action
	 * @param array $args
	 * 
	 * @since 1.0.0
	 * 
	 * 
	 * @return array|mixed|object|WP_Error
	 */
	private function _post( $action, $args = array()  ){
		return $this->_request( "POST", $action, $args );
	}

	 /**
	 * Sends rest PUT request
	 *
	 * @param $action
	 * @param array $args
	 * 
	 * @since 1.0.0
	 * 
	 * 
	 * @return array|mixed|object|WP_Error
	 */
	private function _put( $action, $args = array()  ){
		return $this->_request( "PUT", $action, $args );
	}

	 /**
	 * Sends rest PATCH request
	 *
	 * @param $action
	 * @param array $args
	 * 
	 * @since 1.0.0
	 * 
	 * 
	 * @return array|mixed|object|WP_Error
	 */
	private function _patch( $action, $args = array()  ){
		return $this->_request( "PATCH", $action, $args );
	}

	/**
	 * Gets all the lists
	 *
	 * @param $count - current total lists to show
	 * 
	 * @since 1.0.0
	 *
	 * @return array|mixed|object|WP_Error
	 */
	public function get_lists( $offset = 50, $count = 10 ) {
		return $this->_get( 'lists', array(
			'user' 		=> $this->_user . ':' . $this->_api_key,
			'offset' 	=> $offset,
			'count' 	=> $count,
		) );
	}

	/**
	 * Save campaign
	 * 
	 * @since 1.0.0
	 * 
	 * @return string|bool
	 */
	public function save_campaign( $list_id, $title, $subject ) {
		$admin_email 	= get_option( 'admin_email' );
		$data 			= array(
			'type'			=> 'regular',
			'recipients' 	=> array( 'list_id' => $list_id ),
			'settings'		=> array( 'subject_line' => $subject, 'title' => $title, 'reply_to' => $admin_email, 'from_name' => get_option( 'blogname' ) )
		);
		$res = $this->_post( 'campaigns', array(
			'body' =>  $data
		) );
		if ( ! is_wp_error( $res ) ) {
			return $res->id;
		} else {
			return false;
		}
	}

	/**
	 * Update campaign with content
	 * 
	 * @since 1.0.0
	 * 
	 * @return bool
	 */
	public function update_campaign( $id, $list_id, $content ) {
		$data 			= array(
			'html'			=> $content,
			'recipients' 	=> array( 'list_id' => $list_id ),
		);
		$res = $this->_put( 'campaigns/' . $id . '/content', array(
			'body' =>  $data
		) );
		if ( ! is_wp_error( $res ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Send campaign
	 */
	public function send_campaign( $id ) {
		$res = $this->_post( 'campaigns/' . $id . '/actions/send' );
		if ( ! is_wp_error( $res ) ) {
			return true;
		} else {
			return false;
		}
	}
}
?>