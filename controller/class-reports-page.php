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
		echo '<div class="wrap">';

		$this->the_report();

		echo '</div>';
	}




	public function the_report() {
		?>
		<div class="ptt-report">
			<h3>See All Your Timers</h3>
			<div class="ptt-table ptt-report-header">
				<div class="ptt-table-tr">
					<div class="ptt-table-td premise-align-center">
						<strong>date</strong>
					</div>
					<div class="ptt-table-td premise-align-center">
						<strong>start</strong>
					</div>
					<div class="ptt-table-td premise-align-center">
						<strong>stop</strong>
					</div>
					<div class="ptt-table-td premise-align-center">
						<strong>timer</strong>
					</div>
				</div>
			</div>
			<div class="ptt-report-body">
				<?php 
				foreach ( $this->tasks as $k => $task ) {
					
					$timers = (array) premise_get_value( 'ptt_meta[timers]', array( 'context' => 'post', 'id' => $task->ID ) );
					?>
					<div class="ptt-report-task">
						<h3><?php echo $task->post_title;  ?></h3>
						<div class="ptt-table">
							<?php 
							foreach ( $timers as $k => $row ) {
								if ( ( isset( $row['date'] ) && empty( $row['date'] ) ) && 
										( isset( $row['start'] ) && empty( $row['start'] ) ) && 
											( isset( $row['stop'] ) && empty( $row['stop'] ) ) && 
												( isset( $row['timer'] ) && empty( $row['timer'] ) ) ) {
									return false;
								}
								?>
									<div class="ptt-table-tr">
										<div class="ptt-table-td">
											<div class="ptt-cell-value premise-align-center">
												<?php echo isset( $row['date'] ) ? $row['date'] : ''; ?>
											</div>
										</div>
										<div class="ptt-table-td">
											<div class="ptt-cell-value premise-align-center">
												<?php echo isset( $row['start'] ) ? $row['start'] : ''; ?>
											</div>
										</div>
										<div class="ptt-table-td">
											<div class="ptt-cell-value premise-align-center">
												<?php echo isset( $row['stop'] ) ? $row['stop'] : ''; ?>
											</div>
										</div>
										<div class="ptt-table-td">
											<div class="ptt-cell-value premise-align-center">
												<?php echo isset( $row['timer'] ) ? $row['timer'] : ''; ?>
											</div>
										</div>
									</div>
								<?php 
							}
							?>
						</div>
					</div>
					<?php 
				}
				?>
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
}


?>