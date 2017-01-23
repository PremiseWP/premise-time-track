<?php
/**
 * Time card temlate
 *
 * @package Premise Time Tracker\View
 */

?><article <?php post_class( 'pwptt-time-card premise-clear-float' ); ?>>

	<div class="pwptt-time-card-intro">
		<div class="pwptt-time-card-title-wrapper">
			<a href="<?php the_permalink(); ?>" class="pwptt-time-card-permalink premise-inline-block">
				<h3 class="pwptt-time-card-title"><?php the_title(); ?></h3>
			</a>
		</div>

		<span class="pwptt-time-card-date">
			<i><?php the_time( 'm/d/y' ); ?></i>
		</span>

		<p class="pwptt-time-card-time"><?php echo (float) premise_get_value( 'pwptt_timer[time]', 'post' ); ?></p>
	</div>

	<div class="pwptt-time-card-description premise-hide-on-mobile">
		<?php the_content(); ?>
	</div>

</article><?php
