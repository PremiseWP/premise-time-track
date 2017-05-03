<?php
/**
 * Template to display authors for premise time tracker
 *
 * @package Premise Time Tracker\View
 */

// No header & edit button if viewed from Chrome extension / iframe.
if ( isset( $_REQUEST['iframe'] )
	&& $_REQUEST['iframe'] ) {

	$is_iframe = true;

	wp_head();
} else {

	$is_iframe = false;
	get_header();
}

// call the loop before-hand so we can have the right total later.
$pwp_loop = pwptt_get_loop(); ?>

<section id="pwptt-author-page" class="pwptt-page <?php if ( $is_iframe ) echo 'iframe'; ?>">

	<div class="pwptt-container">

		<h1><?php the_author(); ?></h1>

		<div id="pwptt-loop-wrapper">
			<div class="pwptt-header premise-clear-float">
					<div class="pwptt-search-wrapper">
						<?php pwptt_the_search_field(); ?>
					</div>
					<div class="pwptt-quick-change-wrapper">
						<?php pwptt_the_quick_change_field(); ?>
					</div>
					<div class="pwptt-total-wrapper">
						<p class="pwptt-total">
							Total<span class="premise-hide-on-mobile">&nbsp;hours</span>:
							<?php pwptt_the_total(); ?>
						</p>
					</div>
				</div>
			</div>

			<div id="pwptt-body" class="pwptt-body">
				<?php echo $pwp_loop; ?>
			</div>

			<div class="pwptt-footer premise-clear-float">
				<?php pwptt_the_disclaimer(); ?>
			</div>

		</div>

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
