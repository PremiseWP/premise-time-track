<?php
/**
 * Template to display a single timer
 *
 * @package Premise Time Tracker\View
 */

// No header if viewed from Chrome extension / iframe.
if ( isset( $_GET['iframe'] )
	&& $_GET['iframe'] ) {

	$is_iframe = true;

	$rest_client_url = '';

	if ( isset( $_GET['rest-client-url'] )
		&& $_GET['rest-client-url'] ) {

		$rest_client_url = $_GET['rest-client-url'];
	}

	wp_head();
} else {

	$is_iframe = false;
	get_header();
} ?>

<section id="pwptt-single-timer" <?php if ( $is_iframe ) echo 'class="iframe"'; ?>>

	<div class="pwptt-container">

		<?php if ( have_posts() ) : while( have_posts() ) : the_post(); ?>

			<article <?php post_class( 'pwptt-timer-post' ); ?>>

				<h1><?php the_title(); ?></h1>

				<div class="pwptt-timer-meta premise-clear-float">
					<span class="pwptt-timer-date">
						<i><?php the_time( 'm/d/y' ); ?></i>
					</span>

					<span class="pwptt-timer-time premise-float-right premise-align-right">
						<?php echo pwptt_get_timer() . ' hour(s)'; ?>
					</span>
				</div>

				<div class="pwptt-timer-description">
					<?php the_content(); ?>
				</div>

				<?php if ( $is_iframe && $rest_client_url ) : ?>
					<div class="pwptt-chrome-extension-edit">
						<a href="<?php echo esc_url( $rest_client_url ); ?>?step=ptt-form&amp;ptt-id=<?php the_ID(); ?>"
							title="Edit">
							<i class="fa fa-pencil"></i>
						</a>
					</div>
				<?php endif; ?>
			</article>

		<?php endwhile; else:
			echo '<p class="pwptt-error-message">Sorry, the timer was not found.</p>';
		endif; ?>

	</div>

</section>

<?php // No footer if viewed from Chrome extension / iframe.
if ( isset( $_GET['iframe'] )
	&& $_GET['iframe'] ) {
	wp_footer(); ?>
</body>
</html>
<?php
} else {

	get_footer();
} ?>
