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
function pwptt_the_search_field() {
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

	if ( ( isset( $data['taxonomy'] ) && ! empty( $data['taxonomy'] ) ) &&
		 ( isset( $data['slug'] ) && ! empty( $data['slug'] ) ) ) {

		$_query_args = array(
			'posts_per_page' => -1,
			'post_type'      => 'premise_time_tracker',
			'tax_query'      => array(
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

		if ( isset( $data['quick_change'] ) && is_numeric( $data['quick_change'] ) ) {
			$_query_args['date_query'] = array(
				'week' => $data['quick_change']
			);
		}

		$_posts = new WP_Query( $_query_args );

		if ( $_posts->have_posts() ) {
			while( $_posts->have_posts() ) {
				$_posts->the_post();
				PTT_Render::the_timer_card();
			}
		}
		else {
			pwptt_no_timers();
		}
	}

	die();
}


/**
 * outpus the quick change field
 *
 * @return string html for the quick change field
 */
function pwptt_the_quick_change_field() {
	$week_num = date('W');
	premise_field( 'select', array(
		'id'      => 'pwptt-quick-change',
		'value'   => $week_num - 1,
		'options' => array(
			'this week' => $week_num,
			'last week' => $week_num - 1,
		),
	) );
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


/**
 * displays the message when there are no timers
 *
 * @return string html for no timers message
 */
function pwptt_no_timers() {
	?><p class="pwptt-error-message">It looks like no time has been entered for the time period specified. Enter a different date range above in the following format M/D/YY to broaden up your search.</p><?
}


/**
 * return the timer for a particular post. Must be ran within the loop.
 *
 * @return float i.e 1.75 (hours)
 */
function pwptt_get_timer() {
	return (float) premise_get_value( 'pwptt_timer[time]', 'post' );
}


/**
 * returns the loop for the timers html as a string
 *
 * @return string the loop of timers in html
 */
function pwptt_get_loop() {
	ob_start();

	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			PTT_Render::the_timer_card();
		}
	}
	else {
		pwptt_no_timers();
	}

	// get the HTML from the loop
	return ob_get_clean();
}


/**
 * outputs the total for the timers in the loop. Must be called after pwptt_get_loop().
 *
 * @return string the total hours with needed class to be updated via JS
 */
function pwptt_the_total() {
	echo '<span class="pwptt-total-hours">' . (float) PTT_Render::$total . '</span>';
}


/**
 * output the disclaimer for our timers loop
 *
 * @return string html for disclaimer
 */
function pwptt_the_disclaimer() {
	echo '<p class="pwptt-disclaimer"><i>Timers may not appear on current time and can be added at later dates. Keep this in mind and always verify with the freelancer if there are no timers entered for a specific time period. To avoid conflicts, it helps to set a due date when hours need to be entered. This way the freelancer can commit to having all their hours entered by the time the employer needs them.</i></p>';
}

?>