<?php
/**
 * Template to display taxonomies for premise time tracker
 *
 * @package Premise Time Tracker\View
 */

get_header(); ?>

<section id="pwptt-taxonomy-page">

	<div class="pwptt-container">

		<?php if ( have_posts() ) : ?>

			<?php while ( have_posts() ) : the_post();
				$time = premise_get_value( 'pwptt_timer[time]', 'post' ); ?>

				<article <?php post_class( 'pwptt-time-tracker' ); ?>>

					<div class="premise-row">
						<div class="span3"><?php the_title(); ?></div>
						<div class="span7"><?php the_content(); ?></div>
						<div class="span2 premise-align-right"><?php echo $time . ' hour(s)'; ?></div>
					</div>

				</article>

			<?php endwhile; ?>

		<?php else : ?>



		<?php endif; ?>

	</div>

</section>

<?php get_footer(); ?>