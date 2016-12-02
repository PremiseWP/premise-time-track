<?php
/**
 * Template to display taxonomies for premise time tracker
 *
 * @package Premise Time Tracker\View
 */

defined( 'ABSPATH' ) or die();

get_header();

ob_start();

if ( have_posts() ) :

	// check if the user can view this
	// ! current_user_can( 'administrator' )

	/**
	 * Holds array with all the clients this user has access to. using the client slug as the key and 1 or 0 to determine access.
	 *
	 * i.e. array( "two-by-four" => "1" )
	 *
	 * @var array
	 */
	// $client_access = (array) premise_get_value( 'ptt_user_profile[client-access]', array( 'context' => 'user', 'id' => get_current_user_id() ) );

	// foreach ( $client_access as $client => $bool ) {
	// 	if ( is_tax( 'premise_time_tracker_client', $client ) ) {
	// 		var_dump('yes! - ' . $client ) . '<br>';
	// 	}
	// 	else {
	// 		var_dump('no! - ' . $client ) . '<br>';
	// 	}
	// }

	if ( ! current_user_can( 'administrator' ) ) {
		?><p class="pwptt-error-message">You do not have enough permissions to see this.</p><?
	}
	// User can view this
	else {

		$total = 0.00;

		while ( have_posts() ) : the_post();
			$time = (float) premise_get_value( 'pwptt_timer[time]', 'post' );

			$total = $total + $time;

			include 'content-ptt-time-card.php';

		endwhile;

	}

else :

	?><p class="pwptt-error-message">Sorry, it looks like there are no timers to display here.</p><?

endif;

// get the HTML from the loop
$pwptt_loop = ob_get_clean(); ?>

<section id="pwptt-taxonomy-page">

	<div class="pwptt-container">

			<h1><?php single_term_title(''); ?></h1>

			<div class="pwptt-header premise-clear-float">
					<div class="pwptt-search-wrapper">
						<?php
						// premise_field( 'text', array(
						// 	'id'          => 'pwptt-search-timesheet',
						// 	'placeholder' => '10/24/12 - 12/24/13',
						// 	'class'       => 'pwptt-search',
						// ) ); ?>
					</div>
					<div class="pwptt-total-wrapper">
						<p class="pwptt-total">
							<?php echo '' . (float) $total . ' hour(s)'; ?>
						</p>
					</div>
				</div>
			</div>

			<div id="pwptt-body" class="pwptt-body">
				<?php echo $pwptt_loop; ?>
			</div>

			<div class="pwptt-footer premise-clear-float">
				<div class="premise-align-right">
					<p class="pwptt-total">
						<?php echo '' . (float) $total . ' hour(s)'; ?>
					</p>
				</div>
				<div class="premise-row">
				</div>
			</div>

	</div>

</section>

<?php get_footer(); ?>