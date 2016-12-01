<?php
/**
 * Premise Time Tracker functions
 *
 * @package Premise Time Tracker\Library
 */


function ptt_search_timers() {

	if ( isset( $_POST['date_range'] ) && is_array( $_POST['date_range'] ) ) {
		$date_range = array_map( sanitize_text_field, $_POST['date_range'] );

		$_posts = new WP_Query( array(
			'posts_per_page' => -1,
			'post_type'      => 'premise_time_tracker',
			'date_query'     => array(
				'inclusive' => true,
				'after'     => ( ! empty( $date_range['from'] ) ) ? $date_range['from'] : '',
				'before'    => ( ! empty( $date_range['to'] ) )   ? $date_range['to']   : '',
			)
		) );

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

?>