<?php
/**
 * This class adds the Client profile fields to the User page.
 *
 * @package Premise Time Tracker\Controller
 */
class PTT_Client_Fields {

	/**
	 * Constructor
	 *
	 * @see PWP_User_Fields class
	 */
	function __construct() {

		$option_names = array();

		$args = array(
			'title' => 'Premise Time Tracker Options',
			'description' => 'Assign Client Access:',
			'fields' => $client_fields,
		);

		$client_fields = $this->get_client_fields();

		foreach ( (array) $client_fields as $client_field ) {

			$option_names[] = $client_field['name'];
		}

		new PWP_User_Fields( $args, $option_names );
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

		foreach ( (array) $clients as $client ) {

			$fields[] = array(
				'type'    => 'checkbox',
				'label'   => $term->name,
				'name'  => 'ptt_user_profile[client-access][' . $term->slug . ']',
				//'value' => $this->profile['client-access'][$term->slug],
			);
		}

		return $fields;
	}
}

