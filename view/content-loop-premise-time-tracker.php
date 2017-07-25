<?php
/**
 * Time card temlate
 *
 * @package Premise Time Tracker\View
 */

// No header & edit button if viewed from Chrome extension / iframe.
if ( isset( $_REQUEST['iframe'] )
	&& $_REQUEST['iframe'] ) {

	$is_iframe = true;

} else {

	$is_iframe = false;
}

global $post;

$tax_to_get = is_tax('premise_time_tracker_client') ? 'project' : 'client';
$terms_array = wp_get_post_terms( $post->ID, 'premise_time_tracker_'.$tax_to_get );
$project_or_client = ! empty( $terms_array ) ? esc_attr( $terms_array[0]->name ) : '';

?>
<article <?php post_class( 'pwptt-time-card premise-clear-float' ); ?>>

	<div class="pwptt-time-card-intro">
		<div class="pwptt-time-card-title-wrapper">
			<div class="pwp-float-right">
				<h4 class="pwptt-time-card-project"><?php echo $project_or_client; ?></h4>
			</div>
			<h3 class="pwptt-time-card-title"><?php the_title(); ?></h3>
		</div>

		<span class="pwptt-time-card-date">
			<i><?php the_time( 'l' ); ?> - <?php the_time( 'm/d/y' ); ?></i>
		</span>

		<p class="pwptt-time-card-time"><?php echo pwptt_get_timer(); ?></p>
	</div>

	<div class="pwptt-time-card-description premise-hide-on-mobile pwp-clear-float">
		<?php the_content(); ?>
	</div>
	<?php if ( $is_iframe /*&& ! pwptt_is_client_profile( get_current_user_id() )*/ ) : ?>
		<div class="pwptt-iframe-edit">
			<a href="?step=ptt-form&amp;ptt-id=<?php the_ID(); ?>"
				title="Edit">
				<i class="fa fa-pencil"></i>
			</a>
		</div>
	<?php endif; ?>

</article><?php
