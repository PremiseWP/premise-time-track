<?php
/**
 * User Profile Model. Adds custom fields to the user profile and saves it on profile update.
 *
 * @package Premise Time Tracker\Model
 */
class PTT_User_Profile {

	/**
	 * holds instance of this class
	 *
	 * @var null
	 */
	protected static $inst = NULL;


	/**
	 * holds the profile fields saved for each user
	 *
	 * @var array
	 */
	protected $profile = array();


	/**
	 * instantiate this class and return instance
	 *
	 * @return object instance of this class
	 */
	public static function get_inst() {
		if ( self::$inst === NULL )
			self::$inst = new self;

		return self::$inst;
	}


	/**
	 * left blank and public on purpose
	 */
	function __construct() {}


	/**
	 * list clients as checkboxes
	 *
	 * @return string html for client checkboxes
	 */
	public function list_clients() {

		$terms = get_terms( array(
			'taxonomy'   => 'premise_time_tracker_client',
			'hide_empty' => false,
			'orderby'    => 'name',
		) );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
			echo '<ul>';
				foreach ( $terms as $term ) {
					echo '<li>';
						premise_field( 'checkbox', array(
							'name'  => 'ptt_user_profile[client-access]['.$term->slug.']',
							'label' => $term->name,
							'value' => $this->profile['client-access'][$term->slug],
						) );
					echo '</li>';
				}
			echo '</ul>';
		}
	}


	/**
	 * display the custom fields for the user profile
	 *
	 * @param  objcet $user the user object for the uer profile being viewed
	 * @return string       html for the table of fields.
	 */
	public function custom_fields( $user ) {
		if ( ! is_object( $user ) )
			return false;

		// set the profile fields for this user
		$this->profile = get_user_meta( $user->ID, 'ptt_user_profile', true );

		// display the fields table
		?><h3>Premise Time Tracker Options</h3>
		<table class="form-table">
			<tr>
				<th>Assign Client Access:</th>
				<td><?php $this->list_clients(); ?></td>
			</tr>
		</table><?
	}


	/**
	 * saves the user custom fields.
	 *
	 * @param  int  $user_id user id for user being saved
	 * @return void          does not return anything
	 */
	public function save_custom_fields( $user_id ) {
		update_user_meta( $user_id, 'ptt_user_profile', $_POST['ptt_user_profile'] );
	}
}