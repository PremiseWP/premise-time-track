<?php
/**
 * This class adds the Client profile fields to the User page.
 *
 * @package Premise Time Tracker\Controller
 */
class PTT_Client_Fields {

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
		if ( $this->is_client_profile() ) {
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

			$user = wp_get_current_user();

			return $user->ID;

		} else*/
		if ( 'user-edit.php' === $pagenow &&
			isset( $_REQUEST['user_id'] ) &&
			(string) (int) $_REQUEST['user_id'] === $_REQUEST['user_id'] ) {

			return (int) $_REQUEST['user_id'];
		}
	}

	/**
	 * Is Client profile?
	 *
	 * @link http://wordpress.stackexchange.com/questions/5047/how-to-check-if-a-user-is-in-a-specific-role
	 *
	 * @return boolean
	 */
	protected function is_client_profile() {

		$user_id = $this->get_user_id();

		if ( ! $user_id ) {

			return false;
		}

		$user_data = get_userdata( $user_id );

		$user_roles = empty( $user_data ) ? array() : $user_data->roles;

		return in_array( 'pwptt_client', $user_roles );
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
}

