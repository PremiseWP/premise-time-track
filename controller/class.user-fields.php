<?php
/**
 * This class adds the Client profile fields to the User page.
 *
 * @package Premise Time Tracker\Controller
 * @todo  show meta profile in REST for ALL users so we know how to limit info!
 */
class PTT_User_Fields {

	/**
	 * Holds an instance of this class
	 *
	 * @var null
	 */
	public static $instance = NULL;


	/**
	 * Holds the client fields saved for each user
	 *
	 * @var array
	 */
	protected $user_clients = array();


	/**
	 * We leave the construct function empty on purpose
	 */
	function __construct() {}


	/**
	 * Instantiates our class
	 *
	 * @return object instance of this class
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}


	/**
	 * Constructor
	 *
	 */
	public function init() {
		if ( pwptt_is_client_profile( $this->get_user_id() ) ) {
			$this->build_client_fields();
		}
	}


	/**
	 * Get User ID, the edited one.
	 *
	 * @return int User ID.
	 */
	protected function get_user_id() {

		if ( ! is_admin() ) {

			return 0;
		}

		global $pagenow;

		/*if ( 'profile.php' === $pagenow ) {

			return get_current_user_id();

		} else*/
		if ( 'user-edit.php' === $pagenow &&
			isset( $_REQUEST['user_id'] ) &&
			(string) (int) $_REQUEST['user_id'] === $_REQUEST['user_id'] ) {

			return (int) $_REQUEST['user_id'];
		}

		return 0;
	}

	/**
	 * Build client fields.
	 *
	 * @see PWP_User_Fields class
	 */
	protected function build_client_fields() {
		$option_names = array();

		$client_fields = $this->get_client_fields();

		$args = array(
			'title' => 'Premise Time Tracker Options',
			'description' => 'Assign Client Access:',
			'fields' => $client_fields,
		);

		foreach ( (array) $client_fields as $client_field ) {

			$option_names[] = $client_field['name'];
		}

		new PWP_User_Fields( $args, 'pwptt_clients' );
	}


	/**
	 * Get client fields.
	 *
	 * @return array Client fields.
	 */
	function get_client_fields() {

		$clients = get_terms( array(
			'taxonomy'   => 'premise_time_tracker_client',
			'hide_empty' => false,
			'orderby'    => 'name',
		) );

		$fields = array();

		if ( empty( $clients ) || is_wp_error( $clients ) ) {

			return $fields;
		}

		$this->user_clients = get_user_meta( $this->get_user_id(), 'pwptt_clients', true );

		foreach ( (array) $clients as $client ) {

			$value = ! isset( $this->user_clients[ $client->slug ] ) ? '' :
				$this->user_clients[ $client->slug ];

			$fields[] = array(
				'type'  => 'hidden',
				'name'  => 'pwptt_clients[' . $client->slug . ']',
				'value' => '0',
			);

			$fields[] = array(
				'type'  => 'checkbox',
				'label' => $client->name,
				'name'  => 'pwptt_clients[' . $client->slug . ']',
				'value' => $value,
			);
		}

		return $fields;
	}


	/**
	 * Register our custom meta fields for the REST API.
	 *
	 * @link https://www.sitepoint.com/wp-api/
	 *
	 * @return void
	 */
	public function register_meta_fields() {

		$meta_keys = array( 'pwptt_clients', 'pwptt_profile_level' );

		foreach ( $meta_keys as $meta_key ) {
			register_rest_field( 'user',
				$meta_key,
				array(
					'get_callback'    => array( PTT_User_Fields::get_instance() , 'get_meta_field' ),
					'update_callback' => array( PTT_User_Fields::get_instance() , 'update_meta_field' ),
					'schema'          => null,
				)
			);
		}
	}


	/**
	 * Get meta field to expose to the REST API.
	 *
	 * @param array           $object The object from the response
	 * @param string          $field_name Name of field
	 * @param WP_REST_Request $request Current request
	 *
	 * @return mixed
	 */
	public function get_meta_field( $object, $field_name, $request ) {

		if ( 'pwptt_profile_level' === $field_name ) {

			// Return profile level dynamically.
			return $this->get_profile_level( $object['id'] );
		}

		return get_user_meta( $object['id'], $field_name, true );
	}


	/**
	 * Update meta field to exposed to the REST API.
	 *
	 * @param mixed  $value The value of the field
	 * @param object $object The object from the response
	 * @param string $field_name Name of field
	 *
	 * @return mixed
	 */
	function update_meta_field( $value, $object, $field_name ) {

		if ( 'pwptt_profile_level' === $field_name ) {

			// No update.
			return;
		}

		if ( ! $value ) {
			return;
		}

		return update_user_meta( $object->ID, $field_name, strip_tags( $value ) );
	}


	/**
	 * Get User profile level.
	 *
	 * @param  int    $user_id User ID.
	 *
	 * @return string Empty if no User, else administrator|freelancer|client depending on capabilities.
	 */
	public function get_profile_level( $user_id ) {

		if ( ! $user_id ) {

			return '';
		}

		$user_data = get_userdata( $user_id );

		if ( empty( $user_data ) ) {

			return '';
		}

		$profile_level = 'administrator';

		$allcaps = $user_data->allcaps;

		if ( ! isset( $allcaps['edit_others_posts'] ) ||
			! $allcaps['edit_others_posts'] ) {

			$profile_level = 'freelancer';
		}

		if ( ! isset( $allcaps['edit_posts'] )
			|| ! $allcaps['edit_posts'] ) {

			$profile_level = 'client';
		}

		return $profile_level;
	}
}

