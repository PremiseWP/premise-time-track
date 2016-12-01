<?php
/**
 * User Profile Model
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
		$terms = get_terms( 'premise_time_tracker_client' );
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ){
		 echo '<ul>';
		 foreach ( $terms as $term ) {
		   echo '<li>' . premise_field( 'checkbox', array(
		   	'name'  => '',
		   	'label' => $term->name,
		   	'value_att' => $term->ID,
		   ) ) . '</li>';
		 }
		 echo '</ul>';
		}
	}


	/**
	 * display the custom fields for the user profile
	 *
	 * @param  objcet $user the user object fort he uer profile being viewed
	 * @return string       html for the table of fields.
	 */
	public function custom_fields( $user ) {
		if ( ! is_object( $user ) )
			return false;

		?><h3>Premise Time Tracker Options</h3>
		<table class="form-table">
		<tr>
			<th><label for="birth-date-day">Assign Client Access</label></th>
			<td>
				<?php $this->list_clients(); ?>
			</td>
		</tr>
		</table><?
	}
}