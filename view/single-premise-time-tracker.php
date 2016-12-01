<?php
/**
 * Template to display a single timer
 *
 * @package Premise Time Tracker\View
 */

get_header(); ?>

<section id="pwptt-single-timer">

	<div class="pwptt-container">

		<?php if ( have_posts() ) : while( have_posts() ) : the_post(); ?>

			<article <?php post_class( 'pwptt-timer-post' ); ?>>

				<h1><?php the_title(); ?></h1>

				<div class="pwptt-timer-meta premise-clear-float">
					<span class="pwptt-timer-date">
						<i><?php the_time( 'm/d/y' ); ?></i>
					</span>

					<span class="pwptt-timer-time premise-float-right premise-align-right">
						<?php echo (float) premise_get_value( 'pwptt_timer[time]', 'post' ) . ' hour(s)'; ?>
					</span>
				</div>

				<div class="pwptt-timer-description">
					<?php the_content(); ?>
				</div>
			</article>

		<?php endwhile; else:
			echo '<p>Sorry the timer was not found.</p>';
		endif; ?>

	</div>

</section>

<?php get_footer(); ?>