<?php
/**
 * Time card temlate
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
} else {

	$is_iframe = false;
}
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

		<p class="pwptt-time-card-time"><?php echo pwptt_get_timer(); ?></p>
	</div>

	<div class="pwptt-time-card-description premise-hide-on-mobile">
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

</article><?php
