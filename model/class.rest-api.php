<?php
/**
 * The Premise Time Tracker REST API class
 *
 * @package Premise Time Tracker\Model
 */
class PTT_Rest {

	/**
	 * leve construct empty and blank on purpose
	 */
	function __construct(){}


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