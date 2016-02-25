<?php 




/**
* 
*/
class PTT_Reports_Page {


	protected static $instance = null;



	public static function get_instance() {
		null === self::$instance and self::$instance = new self;

		return self::$instance;
	}
	
	function __construct() {}



	public function setup() {
		$this->tasks = get_posts( array( 'post_type' => 'premise_time_track' ) );

		
			// var_dump($timers);

		$this->the_page();
	}




	public function the_page() {
		?>
		<div class="wrap">
			<?php $this->the_report(); ?>
		</div>
		<?php 
	}




	public function the_report() {
		?>
		<div class="ptt-report premise-clear-float">
			<a href="javascript:;" class="ptt-new-timesheet premise-float-right">
				<i class="fa fa-clock-o"></i>
				New Timesheet
			</a>

			<!-- <a href="javascript:;" class="ptt-new-timesheet premise-float-right">
				<i class="fa fa-th"></i>
				Print as CSV
			</a> -->
			<h3>See All Your Timers</h3>
			<div class="premise-clear-float">
				<?php ptt_the_date_filter( true ); ?> 
				<?php ptt_the_total( $this->get_all_timers() ); ?>
			</div>
			<div class="ptt-table ptt-report-header">
				<div class="ptt-table-tr">
					<div class="ptt-table-td premise-align-center">
						<strong>date</strong>
					</div>
					<div class="ptt-table-td premise-align-center ptt-report-cell-description">
						<strong>Description</strong>
					</div>
					<div class="ptt-table-td premise-align-center">
						<strong>Start</strong>
					</div>
					<div class="ptt-table-td premise-align-center">
						<strong>Stop</strong>
					</div>
					<div class="ptt-table-td premise-align-center">
						<strong>Timer</strong>
					</div>
				</div>
			</div>
			<div class="ptt-report-body ptt-filterable-by-date">
				<?php 
				foreach ( $this->tasks as $k => $task ) {
					// get timers
					$timers = (array) premise_get_value( 'ptt_meta[timers]', array( 'context' => 'post', 'id' => $task->ID ) );
					?>
					<div class="ptt-report-task">
						<a href="<?php echo get_edit_post_link( $task->ID ); ?>">
							<h3 class="ptt-task-name">
								<?php echo esc_html( $task->post_title );  ?>
							</h3>
						</a>
						<?php if ( ! $this->empty_timers( $timers ) ) { ?>
							<div class="ptt-table">
								<?php 
								foreach ( $timers as $k => $row ) {
									if ( ( isset( $row['date'] ) && ! empty( $row['date'] ) ) || 
											( isset( $row['start'] ) && ! empty( $row['start'] ) ) || 
												( isset( $row['stop'] ) && ! empty( $row['stop'] ) ) || 
													( isset( $row['timer'] ) && '' !== $row['timer'] ) ) {
									?>
										<div class="ptt-table-tr ptt-fields-wrapper">
											<div class="ptt-table-td ptt-report-cell-date">
												<div class="ptt-cell-value ptt-cell-value-date premise-align-center">
													<?php echo isset( $row['date'] ) ? $row['date'] : ''; ?>
													<input  type="hidden" 
															value="<?php echo isset( $row['date'] ) ? $row['date'] : ''; ?>"
															class="ptt-datepicker hasdatepicker">
												</div>
											</div>
											<div class="ptt-table-td ptt-report-cell-description">
												<div class="ptt-cell-value ptt-cell-value-description premise-align-left">
													<?php echo isset( $row['description'] ) ? $row['description'] : ''; ?>
													<input  type="hidden" 
															value="<?php echo isset( $row['description'] ) ? $row['description'] : ''; ?>"
															class="ptt-datepicker hasdatepicker">
												</div>
											</div>
											<div class="ptt-table-td ptt-report-cell-start">
												<div class="ptt-cell-value ptt-cell-value-start premise-align-center">
													<?php echo isset( $row['start'] ) ? $row['start'] : ''; ?>
												</div>
											</div>
											<div class="ptt-table-td ptt-report-cell-stop">
												<div class="ptt-cell-value ptt-cell-value-stop premise-align-center">
													<?php echo isset( $row['stop'] ) ? $row['stop'] : ''; ?>
												</div>
											</div>
											<div class="ptt-table-td ptt-report-cell-timer">
												<div class="ptt-cell-value ptt-cell-value-timer premise-align-center">
													<?php echo isset( $row['timer'] ) ? $row['timer'] : ''; ?>
													<input  type="hidden" 
															value="<?php echo isset( $row['timer'] ) ? $row['timer'] : ''; ?>"
															class="ptt-timer-field">
												</div>
											</div>
										</div>
									<?php 
									}
								}
								?>
							</div>
							<?php 
						}
						else {
							?>
							<div class="ptt-message ptt-error">
								<span>We found no timers saved for this task.</span>
							</div>
							<?php 
						}
						?>
					</div>
					<?php 
				}
				?>
			</div>
			<div class="premise-clear-float">
				<?php ptt_the_total( $this->get_all_timers() ); ?>
			</div>
		</div>
		<?php 
	}



	public function get_all_timers() {
		$timers = array();
		foreach( (array) $this->tasks as $t ) {
			$timer_arr = premise_get_value( 'ptt_meta[timers]', array( 'context' => 'post', 'id' => $t->ID ) );
			
			foreach ( (array) $timer_arr as $timer ) 
				array_push( $timers, $timer );
		}
		return $timers;
	}



	public function empty_timers( $timers = array() ) {
		$response = true;
		foreach( $timers as $k => $t ) { 
			if ( ( isset( $t['date'] ) && ! empty( $t['date'] ) ) || 
					( isset( $t['start'] ) && ! empty( $t['start'] ) ) || 
						( isset( $t['stop'] ) && ! empty( $t['stop'] ) ) || 
							( isset( $t['timer'] ) && '' !== $t['timer'] ) ) {
				$response = false;
			}
		}
		return (boolean) $response;
	}
}


?>