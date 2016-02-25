<?php 


function ptt_the_date_filter( $task = false ) {
	?>
	<p><strong>Filter by date<?php if ( $task ) echo ' or task name'; ?>:</strong></p>
	<div class="ptt-filter-by-date-container premise-row span8 premise-float-left">
		<?php 
		// From Field
		premise_field( 'text', array( 
			'class' => 'ptt-filter-by-date ptt-datepicker ptt-filter-from',
			'wrapper_class' => 'premise-float-left span4', 
			'placeholder' => 'From',
		) );
		// To Field
		premise_field( 'text', array( 
			'class' => 'ptt-filter-by-date ptt-datepicker ptt-filter-to',
			'wrapper_class' => 'premise-float-left span4', 
			'placeholder' => 'To',
		) );
		// To Field
		if ( $task ) {
			premise_field( 'text', array( 
				'class' => 'ptt-filter-by-task',
				'wrapper_class' => 'premise-float-left span4', 
				'placeholder' => 'Task Name',
			) );
		} ?>
	</div>
	<?php 
}



/**
 * display the total of all timers for a particular task
 * 
 * @return string html for total
 */
function ptt_the_total( $timers = array() ) {
	echo '<div class="ptt-filter-total-container premise-float-right premise-align-right span4">
		Total: 
		<span class="ptt-filter-total">
			' . esc_html( ptt_get_total( $timers ) ) . '
		</span>
	</div>';
}




/**
 * save the total of all timers to our object
 * 
 * @return void saves total to obkect property $total
 */
function ptt_get_total( $timers = array() ) {
	$total = 0.00;

	if ( is_array( $timers ) && ! empty( $timers ) ) {

		foreach ( $timers as $k => $timer ) {
			if ( is_array( $timer ) && isset( $timer['timer'] ) && ! empty( $timer['timer'] ) ) {
				$total = $total + (float) $timer['timer'];
			}
		}
	}

	return $total;
}



?>