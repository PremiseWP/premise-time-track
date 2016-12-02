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

			<h1><?php single_term_title(''); ?></h1>

			<div id="pwptt-loop-wrapper">
				<div class="pwptt-header premise-clear-float">
						<div class="pwptt-search-wrapper">
							<?php ptt_the_search_field(); ?>
						</div>
						<div class="pwptt-total-wrapper">
							<p class="pwptt-total">
								Total Hours: <?php echo '<span class="pwptt-total-hours">' . (float) $total . '</span>'; ?>
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
							<?php // echo '<span class="pwptt-total-hours">' . (float) $total . '</span> hour(s)'; ?>
						</p>
					</div>
					<div class="premise-row">
					</div>
				</div>

			</div>

	</div>

</section>

<?php get_footer(); ?>