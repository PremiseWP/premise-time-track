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

	// Last week.
	$value = date( 'm/d/y', strtotime( "monday this week" ) ) . ' - ' . date( 'm/d/y', strtotime( "this sunday" ) );

	// If Timesheet or Project or Author: show all Timers.
	if ( $queried_object->taxonomy !== 'premise_time_tracker_client' ) {

		$value = '';
	}

	if ( is_author() ) {

		// Author timers page: Slug is user login & tax is 'author'.
		premise_field( 'text', array(
			'class'       => 'pwptt-search',
			'placeholder' => 'Enter a date range',
			'id'          => 'pwptt-search-timesheet',
			'data-slug'   => $queried_object->ID,
			'data-tax'    => 'author',
			'value'       => $value,
		) );
	}
	elseif ( isset( $queried_object->taxonomy ) &&
		 in_array( $queried_object->taxonomy, $ptt_taxonomies ) ) {

		premise_field( 'text', array(
			'class'       => 'pwptt-search',
			'placeholder' => 'Enter a date range',
			'id'          => 'pwptt-search-timesheet',
			'data-slug'   => $queried_object->slug,
			'data-tax'    => $queried_object->taxonomy,
			'value'       => $value,
		) );
	}
}


function ptt_get_date_query_from_date_range($range) {
	$_rng = explode( '-', $range );
	$_matches = array();
	for ($i=0; $i < count($_rng); $i++) {
		// return $_matches[] array =>
		// '0' => Month
		// '1' => Day
		// '2' => Year
		preg_match_all( '/[0-9]+/', $_rng[$i], $matches );
		$_matches[] = $matches[0];
	}
	// $_matches must have atleast 2 arrays
	if ( 1 < count($_matches) ) {
		return array(
			array(
				'after'     => array(
					'year'  => ( 2 === strlen( $_matches[0][2] ) ) ? '20'.$_matches[0][2] : $_matches[0][2], // ad 20 if year has only 2 digits
					'day' => $_matches[0][1],
					'month'   => $_matches[0][0],
				),
				'before'    => array(
					'year'  => ( 2 === strlen( $_matches[1][2] ) ) ? '20'.$_matches[1][2] : $_matches[1][2], // ad 20 if year has only 2 digits
					'day' => $_matches[1][1],
					'month'   => $_matches[1][0],
				),
				'inclusive' => true,
			),
		);
	}
	return false;
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
			'order' => 'ASC',
		);

		if ( 'author' === $data['taxonomy'] &&
			is_numeric( $data['slug'] ) ) {

			// Author timers page: Slug is user ID & tax is 'author'.
			$_query_args['author'] = $data['slug'];

		} else {

			$_query_args['tax_query'] = array(
				array(
					'taxonomy' => esc_attr( $data['taxonomy'] ),
					'field'    => 'slug',
					'terms'    => esc_attr( $data['slug'] ),
				),
			);

			add_filter( 'ptt_taxonomy_being_loaded', 'ptt_taxonomy_being_loaded' );
		}

		if ( isset( $data['date_range'] ) && is_array( $data['date_range'] ) ) {
			// sanitize the data
			$date_range = array_map( 'sanitize_text_field', $data['date_range'] );

			if ( $date_range ) {
				$_query_args['date_query'] = ptt_get_date_query_from_date_range( $date_range['from'].'-'.$date_range['to'] );
			}
		}

		if ( isset( $data['quick_change'] ) && ! empty( $data['quick_change'] ) ) {

			if ( strpos( $data['quick_change'], 'n' ) === 0 ) {

				$month_num = substr( $data['quick_change'], 1 );

				$_query_args['date_query'] = array(
					'month' => $month_num,
				);
			} elseif ( strpos( $data['quick_change'], 'W' ) === 0 ) {

				$week_num = substr( $data['quick_change'], 1 );

				$_query_args['date_query'] = array(
					'week' => $week_num,
				);
			}
		}

		if ( isset( $data['author'] ) && is_numeric( $data['author'] ) ) {
			$_query_args['author'] = $data['author'];
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
 * filter the taxonomy being loaded
 *
 * @param  string $tax empty string
 * @return string      taxonomy being loaded
 */
function ptt_taxonomy_being_loaded( $tax ) {
	return esc_attr( $_POST['taxonomy'] );
}


/**
 * outpus the quick change field
 *
 * @return string html for the quick change field
 */
function pwptt_the_quick_change_field() {
	$week = 'W' . date( 'W' );
	$month = 'n' . date( 'n' );

	$week_num = date( 'W' );
	$month_num = date( 'n' );

	if ( $_GET['week'] ) {
		$_w = trim( esc_attr( $_GET['week'] ) );
	}

	premise_field( 'select', array(
		'id'      => 'pwptt-quick-change',
		'value'   => ( $_w ) ? $_w : $week,
		'options' => array(
			'All timers' => '',
			'This month' => 'n' . $month_num,
			'Last month' => 'n' . ( $month_num - 1 ),
			'This week' => 'W' . $week_num,
			'Last week' => 'W' . ( $week_num - 1 ),
		),
	) );
}


/**
 * Outputs the author field
 *
 * @return string html for the author field
 */
function pwptt_the_author_field() {

	$authors = array(
		'All freelancers' => '',
	);


	$author_query = new WP_Query( array(
		'post_type' => 'premise_time_tracker',
		'tax_query' => array(
			array(
				'taxonomy' => get_queried_object()->taxonomy, // Current taxonomy.
				'terms' => get_queried_object()->term_id, // Current term.
			),
		),
	) );

	// Get all authors for that taxonomy.
	while ( $author_query->have_posts() ) {

		$author_query->the_post();

		$authors[ get_the_author_meta( 'display_name', get_the_author_meta( 'ID' ) ) ] = get_the_author_meta( 'ID' );
	}

	wp_reset_postdata();

	premise_field( 'select', array(
		'id'      => 'pwptt-author',
		'value'   => '',
		'options' => $authors,
	) );
}


/**
 * Filter the main query
 *
 * @param  object $wp_query the current query
 * @return object           the new query
 */
function ptt_filter_main_loop( $wp_query ) {

	if ( is_tax( PTT_Render::get_instance()->taxonomies ) ||
		( isset( $wp_query->query['post_type'] ) &&
			$wp_query->query['post_type'] === 'premise_time_tracker' ) ) {

		if ( ! current_user_can( 'edit_others_posts' ) ) {

			// Is client?
			if ( ! pwptt_is_client_profile( get_current_user_id() ) ) {
				// Deny read access to others posts to Freelancers, authors.
				$wp_query->set( 'author', get_current_user_id() );
			}
		}
	}

	if ( ! is_admin() ) {
		if ( is_tax( 'premise_time_tracker_client' ) ) {
			// start loading this week
			$date_query = array( 'week' => date('W') );

			// if there is adate range, lets change the date query
			if ( $_GET['range'] ) {
				$date_query = ptt_get_date_query_from_date_range( $_GET['range'] );
			}
			elseif ( $_GET['week'] ) {
				$_w = trim( esc_attr( $_GET['week'] ) );
				$week = ( strpos( $_w, 'n' ) === 0 ) ? 'month' : 'week';
				$date_query = array( $week => esc_attr( substr( $_w, 1 ) ) );
			}
			// var_dump($date_query);
			// Displays the default view (current week for clients).
			$wp_query->set( 'date_query', $date_query );
			$wp_query->set( 'order', 'ASC' );
		}

		if ( is_tax( 'premise_time_tracker_timesheet' ) ) {

			/*if ( ! current_user_can( 'edit_others_posts' )
				&& is_object( $wp_query->queried_object ) ) {

				$author_query = new WP_Query( array(
					'post_type' => 'premise_time_tracker',
					'author' => get_current_user_id(),
					'tax_query' => array(
						array(
							'taxonomy' => 'premise_time_tracker_timesheet',
							'field' => 'slug',
							'terms' => array( $wp_query->queried_object->term_id ),
						),
					),
				) );

				if ( ! $author_query->have_posts() ) {

					// Deny access to others Timesheets to Freelancers.
					wp_die( 'You are not allowed to view this Timesheet.' );
				}

				wp_reset_postdata();
			}*/
		}
	}


	// Client profile: limit access to its assigned client(s).
	/*if ( ! is_admin() &&
		! current_user_can( 'edit_posts' ) ) {

		$die = false;

		if ( is_tax( 'premise_time_tracker_client' ) ) {

			// Displays the default view (current week for clients).
			$wp_query->set( 'date_query', array( 'week' => date('W') - 1 ) );

			$user_clients_meta = get_user_meta( get_current_user_id(), 'pwptt_clients', true );

			$user_clients = array();

			foreach ( (array) $user_clients_meta as $slug => $yes ) {

				if ( $yes ) {
					$user_clients[] = $slug;
				}
			}

			if ( ! $user_clients ) {

				$die = true;
			}

			$queried_client = get_query_var( 'premise_time_tracker_client' );

			// Limit access to its assigned client(s).
			if ( ! in_array( $queried_client, $user_clients ) ) {

				$die = true;
			}

		} elseif ( is_tax( PTT_Render::get_instance()->taxonomies ) ) {

			$die = true;
		}

		if ( $die ) {
			// Deny access to others Timesheets to Freelancers.
			wp_die( 'You are not allowed to view this page.' );
		}
	}*/
}


/**
 * Filter terms
 * For Freelancer profile
 *
 * @param  array $args       get_terms arguments.
 * @param  array $taxonomies Taxomomies.
 *
 * @return array             Arguments.
 */
function pwptt_filter_terms( $args, $taxonomies ) {

	global $pagenow;

	// echo json_encode(var_dump(current_user_can( 'edit_others_posts' )));

	/*if ( ! current_user_can( 'edit_others_posts' )
		// && 'edit-tags.php' == $pagenow
		&& 'premise_time_tracker_timesheet' == $taxonomies[0] ) {

		// Limit Timesheets to those belonging to Freelancer.
		$args['include'] = array();

		$author_query = new WP_Query( array(
			'post_type' => 'premise_time_tracker',
			'author' => get_current_user_id(),
		) );

		// Get all timesheet terms for that author.
		while ( $author_query->have_posts() ) {

			$author_query->the_post();
			$terms = get_the_terms( $author_query->get_the_ID(), 'premise_time_tracker_timesheet' );

			foreach ( (array) $terms as $term ) {

				$args['include'][ $term->term_id ] = $term->term_id;
			}
		}

		if ( ! $args['include'] ) {
			// Return no terms.
			$args['include'][] = 9999999;
		}

		wp_reset_postdata();
	}*/

	return $args;
}


/**
 * displays the message when there are no timers
 *
 * @return string html for no timers message
 */
function pwptt_no_timers() {
	?><p class="pwptt-error-message">It looks like no time has been entered for the time period specified. Enter a different date range above in the following format M/D/YY to broaden up your search.</p><?php
}


/**
 * return the timer for a particular post. Must be ran within the loop.
 *
 * @return float i.e 1.75 (hours)
 */
function pwptt_get_timer() {
	$hours = premise_get_value( 'pwptt_hours', 'post' );

	if ( false === $hours ) {

		// FJ remove serialized Time field.
		$hours = premise_get_value( 'pwptt_timer[time]', 'post' );
	}

	$label = (1.00 < (float) $hours) ? ' hours' : ' hour';

	return (float) $hours . $label;
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




/**
 * Is Client profile?
 *
 * @link http://wordpress.stackexchange.com/questions/5047/how-to-check-if-a-user-is-in-a-specific-role
 *
 * @return boolean
 */
function pwptt_is_client_profile( $user_id ) {

	if ( ! $user_id ) {

		return false;
	}

	$user_data = get_userdata( $user_id );

	$user_roles = empty( $user_data ) ? array() : $user_data->roles;

	return in_array( 'pwptt_client', $user_roles );
}


