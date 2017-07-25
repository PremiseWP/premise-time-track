<?php
/**
*
*/
class PTT_Rest_Taxonomies extends PTT_Rest {

	protected static $inst = NULL;

	protected $client = NULL;

	protected $project = NULL;

	/**
	 * leave empty and public on purpose
	 */
	function __construct() {}

	/**
	 * instantiate our class
	 */
	public static function get_inst() {
		if ( self::$inst === NULL ) {
			self::$inst = new self;
		}
		return self::$inst;
	}


	public function remove() {
		$id = isset( $_GET['id'] ) ? sanitize_text_field( $_GET['id'] ) : false;
		$tax = isset( $_GET['tax'] ) ? sanitize_text_field( $_GET['tax'] ) : false;

		if ( ! $id || ! $tax ) {
			return wp_send_json_error( array(
				'message' => 'No sufficent params supplied',
			) );
		}
		// 1. authenticate the user
		$request  = new WP_REST_Request( 'GET', '/wp/v2/users/me' );
		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			// not sucessful.
			// return response.
			return $response;
		}

		$removed = wp_delete_term( $id, $tax );

		if ( ! is_wp_error( $removed ) ) {
			return wp_send_json( array( 'success' => true ) );
		}
		return wp_send_json_error();
	}
}
?>