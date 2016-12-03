<?php
/**
 * Premise Time Tracker functions
 *
 * @package Premise Time Tracker\Library
 */


/**
 * display the search field on taxonomy (archive) pages
 *
 * @return string html for search field
 */
function ptt_the_search_field() {
	$queried_object = get_queried_object();
	$ptt_taxonomies = array(
		'premise_time_tracker_client',
		'premise_time_tracker_project',
		'premise_time_tracker_timesheet'
	);

	if ( isset( $queried_object->taxonomy ) &&
		 in_array( $queried_object->taxonomy, $ptt_taxonomies ) ) {

		premise_field( 'text', array(
			'class'       => 'pwptt-search',
			'placeholder' => 'Enter a date range',
			'id'          => 'pwptt-search-timesheet',
			'data-slug'   => $queried_object->slug,
			'data-tax'    => $queried_object->taxonomy,
			'value'       => date( 'm/d/y', strtotime( "monday last week" ) ) . ' - ' . date( 'm/d/y', strtotime( "last sunday" ) ),
		) );
	}
}


/**
 * search for items for a specific client project or timesheet within a date range
 *
 * @return string html for timers
 */
function ptt_search_timers() {

	$data = $_POST;

	// if ( ( isset( $data['taxonomy'] ) && ! empty( $data['taxonomy'] ) )
	// 	&& ( isset( $data['slug'] ) && ! empty( $data['slug'] ) )
	// 	&& ( isset( $data['date_range'] ) && is_array( $data['date_range'] ) ) ) {


	// }


	if ( ( isset( $data['taxonomy'] ) && ! empty( $data['taxonomy'] ) ) &&
		 ( isset( $data['slug'] ) && ! empty( $data['slug'] ) ) ) {

		$_query_args = array(
			'posts_per_page' => -1,
			'post_type'      => 'premise_time_tracker',
			'tax_query'     => array(
				'taxonomy' => esc_attr( $data['taxonomy'] ),
				'field'    => 'slug',
				'terms'    => esc_attr( $data['slug'] ),
			),
		);

		if ( isset( $data['date_range'] ) && is_array( $data['date_range'] ) ) {
			// sanitize the data
			$date_range = array_map( sanitize_text_field, $data['date_range'] );

			if ( $date_range ) {
				$_query_args['date_query'] = array(
					'inclusive' => true,
					'after'     => ( ! empty( $date_range['from'] ) ) ? $date_range['from'] : '',
					'before'    => ( ! empty( $date_range['to'] ) )   ? $date_range['to']   : '',
				);
			}
		}

		if ( isset( $data['current_week'] ) && (bool) $data['current_week'] ) {
			$_query_args['date_query'] = array(
				'week' => date('W')
			);
		}
var_dump($_query_args);
		$_posts = new WP_Query( $_query_args );


		if ( $_posts->have_posts() ) {
			while( $_posts->have_posts() ) { $_posts->the_post();
				$time = (float) premise_get_value( 'pwptt_timer[time]', 'post' );

				$total = $total + $time;

				include PTT_PATH . '/view/content-ptt-time-card.php';
			}
		}
		else {
			echo '<p>No posts where found.</p>';
		}
	}

	die();
}


/**
 * displays the default view (current week)
 *
 * @param  object $wp_query the current query
 * @return object           the new query
 */
function ptt_filter_main_loop( $wp_query ) {
	if ( ! is_admin() ) {
		if ( is_tax( 'premise_time_tracker_client' ) ||
			 is_tax( 'premise_time_tracker_project' ) ||
			 is_tax( 'premise_time_tracker_timesheet' ) ) {

			$wp_query->set( 'date_query', array( 'week' => date('W') - 1 ) );
		}
	}
}

?>