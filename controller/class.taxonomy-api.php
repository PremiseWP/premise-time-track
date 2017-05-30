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
		if ( ! $project_id ) {
			return 0;
		}

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


	/**
	 * Use postID when the post is updated via the REST API.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/rest_delete_this-post_type/
	 * @link https://developer.wordpress.org/reference/hooks/rest_insert_this-post_type/
	 *
	 * @param  WP_Post $post                    Post.
	 * @param  WP_REST_Request|WP_REST_Response $post_after
	 * @param  WP_REST_Request|bool             $post_before
	 */
	public function update_project_hours_rest( $post, $request_or_response = null, $request_or_creating = null ) {
		$this->update_project_hours( $post->ID );
	}


	/**
	 * Use post ID when the post changes
	 *
	 * @link https://developer.wordpress.org/reference/hooks/deleted_post/
	 * @link https://developer.wordpress.org/reference/hooks/post_updated/
	 *
	 * @param  integer $post_id    Post ID.
	 * @param  WP_Post $post_after
	 * @param  WP_Post $post_before
	 */
	public function update_project_hours_post( $post_id, $post_after = null, $post_before = null ) {

		$this->update_project_hours( $post_id );
	}


	/**
	 * Use value of post meta pwptt_hours when the post meta changes
	 *
	 * @param  integer $meta_id    ID of the meta data field.
	 * @param  integer $post_id    Post ID.
	 * @param  string $meta_key    Name of meta field.
	 * @param  string $meta_value  Value of meta field.
	 */
	public function update_project_hours_meta( $meta_id, $post_id, $meta_key = '', $meta_value = '' ) {

		if ( $meta_key !== 'pwptt_hours' ) {
			return false;
		}

		$this->update_project_hours( $post_id, true );
	}


	/**
	 * Update project hours when post or meta updated.
	 *
	 * @param  integer $post_id    Post ID.
	 */
	protected function update_project_hours( $post_id, $is_meta = false ) {

		if ( $is_meta ) {
			$terms = wp_get_post_terms( $post_id, 'premise_time_tracker_project' );

		} else {
			// Update all projects.
			$terms = get_terms( 'premise_time_tracker_project', array(
				'hide_empty' => false,
			) );
		}

		foreach ( (array) $terms as $project ) {
			$project_hours = $this->calculate_project_hours( $project->term_id );

			$this->update_meta_field( $project_hours, $project->term_id, 'pwptt_project_hours' );
		}
	}
}
