<?php
/**
 * The Premise Time Tracker REST API class
 *
 * @package Premise Time Tracker\Model
 */
class PTT_Rest {

	protected static $inst = NULL;

	/**
	 * leve construct empty and blank on purpose
	 */
	function __construct(){}

	/**
	 * instantiate our class
	 */
	public static function get_inst() {
		if ( self::$inst === NULL ) {
			self::$inst = new self;
		}
		return self::$inst;
	}

	public function register_routes() {
		register_rest_route( 'premise_time_tracker/v2', '/currentuser', array(
	    'methods' => 'GET',
	    'callback' => array(PTT_Rest_Users::get_inst(), 'current_user'),
	  ) );

	  register_rest_route( 'premise_time_tracker/v2', '/trydemo', array(
	    'methods' => 'POST',
	    'callback' => array(PTT_Rest_Users::get_inst(), 'try_demo'),
	  ) );

	  register_rest_route( 'premise_time_tracker/v2', '/checkuser', array(
	    'methods' => 'GET',
	    'callback' => array(PTT_Rest_Users::get_inst(), 'check_user'),
	  ) );

		register_rest_route( 'premise_time_tracker/v2',
			'/newuser',
			array(
		  'methods' => 'POST',
		  'callback' => array(PTT_Rest_Users::get_inst(), 'new_user'),
		) );

		register_rest_route( 'premise_time_tracker/v2',
			'/remove_client_or_project',
			array(
		  'methods' => 'GET',
		  'callback' => array( PTT_Rest_Taxonomies::get_inst(), 'remove' ),
		) );

		register_rest_route( 'premise_time_tracker/v2',
			'/forgot_password',
			array(
		  'methods' => 'GET',
		  'callback' => 'ttt_forgot_password',
		) );

		register_rest_route( 'premise_time_tracker/v2',
			'/register_organization',
			array(
		  'methods' => array('GET', 'POST'),
		  'callback' => array(PTT_Rest_Users::get_inst(), 'register_organization'),
		) );
	}

	/**
	 * Creates a new post via the Restful API.
	 *
	 * Requires your usename and password. Not meant for production!
	 *
	 * @return void
	 */
	public function test_api() {
		// set our headers
		// Replace USERNAME and PASSWORD with your own
		$headers = array (
			'Authorization' => 'Basic ' . base64_encode( 'USERNAME' . ':' . 'PASSWORD' ),
		);
		// set url to premise_time_tracker endpoint
		$url = rest_url( '/wp/v2/premise_time_tracker' );
		// prep data for new post
		$data = array(
			'title'       => 'created Via the API',
			'content'     => 'This is the content',
			'pwptt_hours' => '3.75', // see notes on 'model/class.time-tracker-mb.php Line 102'
			'status'      => 'publish'
		);
		// prep response
		$response = wp_remote_post( $url, array (
		    'method'  => 'POST',
		    'headers' => $headers,
		    'body'    =>  $data
		) );
	}
}