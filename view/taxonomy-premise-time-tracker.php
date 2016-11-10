<?php
/**
 * Template to display taxonomies for premise time tracker
 *
 * @package Premise Time Tracker\View
 */

get_header();

ob_start();

if ( have_posts() ) :

	$total = 0.00;

	while ( have_posts() ) : the_post();
		$time = (float) premise_get_value( 'pwptt_timer[time]', 'post' );

		$total = $total + $time;

		?><article <?php post_class( 'pwptt-time-tracker' ); ?>>

			<div class="premise-row">
				<div class="span3">
					<h3><?php the_title(); ?></h3>
				</div>
				<div class="span7">
					<?php the_content(); ?>
					<p class="premise-float-right"><i><?php the_time( 'm/d/y' ); ?></i></p>
				</div>
				<div class="span2 premise-align-right">
					<p><?php echo (float) $time . ' hour(s)'; ?></p>
				</div>
			</div>

		</article><?php
	endwhile;

else :

	?><p>Sorry, it looks like there are no timers to display here. :(</p><?php

endif;

// get the HTML from the loop
$pwptt_loop = ob_get_clean(); ?>

<section id="pwptt-taxonomy-page">

	<div class="pwptt-container">

			<div class="pwptt-header">
				<div class="premise-row">
					<div class="span3">
						<?php premise_field( 'text', array(
							'id' => 'pwptt-search',
							'placeholder' => 'search..'
						) ); ?>
					</div>
					<div class="span3">
						<?php premise_field( 'text', array(
							'name' => 'pwptt-filter[date-from]',
							'class' => 'pwptt-datepicker'
						) ); ?>
					</div>
					<div class="span3">
						<?php premise_field( 'text', array(
							'name' => 'pwptt-filter[date-to]',
							'class' => 'pwptt-datepicker'
						) ); ?>
					</div>
					<div class="span3 premise-align-right">
						<p>
							<strong><?php echo 'Total: ' . (float) $total . ' hour(s)'; ?></strong>
						</p>
					</div>
				</div>
			</div>

			<h1><?php single_term_title(''); ?></h1>

			<div class="pwptt-body">
				<?php echo $pwptt_loop; ?>
			</div>

			<div class="pwptt-footer">
				<div class="premise-row">
					<div class="span12 premise-align-right">
						<p>
							<strong><?php echo 'Total: ' . (float) $total . ' hour(s)'; ?></strong>
						</p>
					</div>
				</div>
			</div>

	</div>

</section>

<?php get_footer(); ?>