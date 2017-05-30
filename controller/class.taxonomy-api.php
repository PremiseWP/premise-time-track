<?php
/**
 * This class adds REST API endpoints to the timer taxonomies (projects, projects).
 *
 * @package Premise Time Tracker\Controller
 */
class PTT_Taxonomy_API {

	/**
	 * Holds an instance of this class
	 *
	 * @var null
	 */
	public static $instance = null;


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
	 * Register our custom meta fields for the REST API.
	 *
	 * @link https://www.sitepoint.com/wp-api/
	 *
	 * @return void
	 */
	public function register_meta_fields() {

		$meta_keys = array( 'pwptt_project_hours' );

		foreach ( $meta_keys as $meta_key ) {
			register_rest_field( 'premise_time_tracker_project',
				$meta_key,
				array(
					'get_callback'    => array( PTT_Taxonomy_API::get_instance() , 'get_meta_field' ),
					'update_callback' => array( PTT_Taxonomy_API::get_instance() , 'update_meta_field' ),
					'schema'          => null,
				)
			);
		}
	}


	/**
	 * Get meta field to expose to the REST API.
	 *
	 * @param array           $object The object from the response.
	 * @param string          $field_name Name of field.
	 * @param WP_REST_Request $request Current request.
	 *
	 * @return mixed
	 */
	public function get_meta_field( $object, $field_name, $request ) {

		if ( 'pwptt_project_hours' === $field_name ) {

			// Return project hours dynamically.
			return $this->get_project_hours( $object['id'] );
		}

		return get_term_meta( $object['id'], $field_name, true );
	}


	/**
	 * Update meta field to exposed to the REST API.
	 *
	 * @param mixed  $value The value of the field.
	 * @param mixed  $object The object from the response or the object ID.
	 * @param string $field_name Name of field.
	 *
	 * @return mixed
	 */
	function update_meta_field( $value, $object, $field_name ) {

		$term_id = ( is_object( $object ) ? $object->ID : $object );

		return update_term_meta( $term_id, $field_name, strip_tags( $value ) );
	}


	/**
	 * Get Project total hours.
	 *
	 * @param  int $project_id Project ID.
	 *
	 * @return float  Empty if no Project, else total hours.
	 */
	public function get_project_hours( $project_id ) {

		if ( ! $project_id ) {

			return '';
		}

		$project_hours = get_term_meta( $project_id, 'pwptt_project_hours', true );

		if ( ! $project_hours &&
			'0' !== $project_hours ) {

			$project_hours = $this->calculate_project_hours( $project_id );

			$this->update_meta_field( $project_hours, $project_id, 'pwptt_project_hours' );
		}

		return $project_hours;
	}


	/**
	 * Calculate Project hours
	 *
	 * @param  int $project_id Project ID.
	 * @return float          Total hours.
	 */
	protected function calculate_project_hours( $project_id ) {
		$project_hours = 0;

		// Get each timer belonging to this project.
		$args = array(
			'posts_per_page'   => -1,
			'offset'           => 0,
				'post_type'        => 'premise_time_tracker',
			'post_status'      => 'publish',
			'tax_query' => array(
				array(
					'taxonomy' => 'premise_time_tracker_project',
					'terms' => $project_id,
				),
			),
		);

		$posts_array = get_posts( $args );

		foreach ( (array) $posts_array as $post ) {
			$project_hours += (float) $post->pwptt_hours;
		}

		return $project_hours;
	}
}

