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

	$total = 0.00;

	while ( have_posts() ) : the_post();
		$time = (float) premise_get_value( 'pwptt_timer[time]', 'post' );

		$total = $total + $time;

		include 'content-ptt-time-card.php';

	endwhile;

else :

	?><p class="pwptt-error-message">Sorry, it looks like there are no timers to display here.</p><?

endif;

// get the HTML from the loop
$pwptt_loop = ob_get_clean(); ?>

<section id="pwptt-taxonomy-page">

	<div class="pwptt-container">

			<h1><?php single_term_title( '' ); ?></h1>

			<div id="pwptt-loop-wrapper">
				<div class="pwptt-header premise-clear-float">
						<p><i>viewing last week | <a href="#" class="pwptt-show-this-week">view this week</a>.</i></p>
						<div class="pwptt-search-wrapper">
							<?php ptt_the_search_field(); ?>
						</div>
						<div class="pwptt-total-wrapper">
							<p class="pwptt-total">
								Total<span class="premise-hide-on-mobile">&nbsp;hours</span>: <?php echo '<span class="pwptt-total-hours">' . (float) $total . '</span>'; ?>
							</p>
						</div>
					</div>
				</div>

				<div id="pwptt-body" class="pwptt-body">
					<?php echo $pwptt_loop; ?>
				</div>

				<div class="pwptt-footer premise-clear-float">
					<p><a href="#" class="pwptt-show-this-week">view this week</a></p>
				</div>

			</div>

	</div>

</section>

<?php get_footer(); ?>