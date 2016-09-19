<?php 
/**
 * Display the options neede for the Time Track CPT
 *
 * @package PTT
 * @subpackage controller
 */



/**
 * Register our hooks to load our class when posts are being edited
 */
add_action( 'load-post.php',     array( PTT_Meta_Box::get_instance(), 'hook_box' ) );
add_action( 'load-post-new.php', array( PTT_Meta_Box::get_instance(), 'hook_box' ) );




/**
* The premise time track meta box class
*
* Prints our meta boxes on the premise time track custom post types
*/
class PTT_Meta_Box {
	
	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;




	/**
	 * holds nonce action
	 * 
	 * @var string
	 */
	protected $nonce = 'ptt_meta_box';




	/**
	 * holds timers count
	 *
	 * Useful when looping through timer to 
	 * easily know how many timers have already been saved
	 * 
	 * @var integer
	 */
	public $count = 0;



	/**
	 * Holds an array of all the timer saved
	 * 
	 * @var array
	 */
	public $timers = array();




	/**
	 * Constructor. Intentionally left empty and public.
	 *
	 * @see 	pboxes_setup()
	 * @since 	1.0
	 */
	public function __construct() {}

	



	/**
	 * Access this plugin’s working instance
	 *
	 * @since   1.0
	 * @return  object of this class
	 */
	public static function get_instance() {
		NULL === self::$instance and self::$instance = new self;
		
		return self::$instance;
	}




	/**
	 * Hooks our meta boxes
	 * 
	 * @return Does not return any values
	 */
	public function hook_box() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post',      array( $this, 'save'         ) );
	}




	/**
	 * creates our meta boxes
	 *
	 * Only creates the meta boxes if 
	 * we are in the correct post type
	 * 
	 * @param string $post_type current post type
	 */
	public function add_meta_box( $post_type ) {
		if ( 'premise_time_track' == $post_type ) {
			add_meta_box( 'premise_time_track', 'Timer', array( $this, 'render_timer' ), $post_type, 'side', 'low' );
			add_meta_box( 'premise_time_track_history', 'Time History', array( $this, 'render_history' ), $post_type, 'advanced', 'high' );
		}
	}




	/**
	 * The timer meta box
	 * 
	 * @return string html for timer meta box
	 */
	public function render_timer() {
		$this->reset();

		wp_nonce_field( $this->nonce, 'ptt_nonce_field' ); ?>
		<div class="ptt-timer">
			<?php 
			$this->the_buttons();
			
			$this->working_timer();
			
			$this->controls(); ?>
		</div> <?php 
	}




	/**
	 * otputs the timer buttons
	 * 
	 * @return string html for timer buttons
	 */
	protected function the_buttons() {
		$options = array(
			// The buttons (start | stop)
			array(
				'type' => 'button',
				'value' => 'Start',
				'id' => 'ptt_start',
				'class' => 'ptt-start-btn',
				'wrapper_class' => 'ptt-left',
			),
			array(
				'type' => 'button',
				'value' => 'Stop',
				'id' => 'ptt_stop',
				'class' => 'ptt-stop-btn',
				'wrapper_class' => 'ptt-right',
			),
		);

		premise_field_section( $options );
	}




	/**
	 * otputs the fields
	 * 
	 * @param  integer $count integer to override the objects count
	 * @return string         html for fields
	 */
	protected function the_fields( $count = '' ) {
		$i = is_int( $count ) ? $count : $this->count;

		$the_fields = array(
			// Hidden fields
			array(
				'type' => 'hidden',
				'name' => 'ptt_meta[timers]['.$i.'][timestamp_start]', 
				'context' => 'post',	
			),
			array(
				'type' => 'hidden',
				'name' => 'ptt_meta[timers]['.$i.'][timestamp_stop]', 
				'context' => 'post',	
			),
			// the fields
			array(
				'type' => 'text',
				'name' => 'ptt_meta[timers]['.$i.'][date]', 
				'label' => 'Date', 
				'context' => 'post',
				'class' => 'ptt-datepicker',
			),
			array(
				'type' => 'text',
				'name' => 'ptt_meta[timers]['.$i.'][start]', 
				'class' => 'ptt-time-field ptt-start',
				'label' => 'From', 
				'context' => 'post', 
				'wrapper_class' => 'ptt-left',
				'maxlength' => '5', 
			),
			array(
				'type' => 'text',
				'name' => 'ptt_meta[timers]['.$i.'][stop]', 
				'class' => 'ptt-time-field ptt-stop',
				'label' => 'To', 
				'context' => 'post', 
				'wrapper_class' => 'ptt-right',
				'maxlength' => '5', 
			),
			array(
				'type' => 'number',
				'name' => 'ptt_meta[timers]['.$i.'][timer]', 
				'label' => 'Timer', 
				'context' => 'post',
				'class' => 'ptt-timer-field',
				'min' => '0.00',
				'step' => '0.25',
			),
			array(
				'type' => 'textarea',
				'name' => 'ptt_meta[timers]['.$i.'][description]', 
				'label' => 'Description', 
				'context' => 'post',
				'rows' => '1',
				'wrapper_class' => 'ptt-description-field', 
			), 
		);

		premise_field_section( $the_fields );
	}




	/**
	 * otputs the controls
	 * 
	 * @return string htmla for controls
	 */
	protected function controls() {
		?>
		<div class="ptt-timer-controls">
			<a href="javascript:;" class="ptt-new-timer">
				<i class="fa fa-plus"></i> New Timer
			</a>
		</div>
		<?php 
	}




	/**
	 * outputs the working timer
	 *
	 * The working timer is the timer that is currently active.
	 * Once the user creates a new timer and saves it, the new one 
	 * becomes the working timer and the old one is moved to history.
	 *
	 * @see self::render_history() this function loads the history of timers
	 * 
	 * @return string html for the working timer
	 */
	public function working_timer() {
		$_count = ( 0 < $this->count ) ? $this->count -1 : $this->count;
		echo '<div class="ptt-fields-wrapper ptt-timer-fields">';
			$this->the_fields( $_count );
		echo '</div>';
		
	}




	/**
	 * Ajax a new timer
	 *
	 * This function allows us to get a new set of timer fields
	 * from the server.
	 * 
	 * @param integer $_POST['count'] the count to override current count
	 * @return string html for timer fields
	 */
	public function ajax_new_timer() {
		$this->count = is_numeric( $_POST['count'] ) ? $_POST['count']+1 : $this->count+1;
		$this->the_fields();
		die();
	}




	/**
	 * The history meta box
	 *
	 * This meta box displays the timer history.
	 * i.e. The timers that have been saved so far, 
	 * minus the last one. The last timer is always
	 * the working timer and stays in the timer meta box
	 * 
	 * @return string html for meta box
	 */
	public function render_history() {
		?>
		<!-- The History Header -->
		<div class="ptt-time-history-header premise-clear-float">
			<?php ptt_the_date_filter();
			$this->the_total(); ?>
		</div>
		<!-- The History -->
		<div class="ptt-time-history ptt-filterable-by-date">
			<?php $this->the_history(); ?>
		</div>
		<!-- The History Footer -->
		<div class="ptt-time-history-footer premise-clear-float">
			<?php $this->the_total(); ?>
		</div>
		<?php 
	}



	/**
	 * displays the hitory
	 * 
	 * @return string the history fields for the history meta box
	 */
	public function the_history() {
		if ( 0 < $this->count ) {
			// array_pop( $this->timers );
			$i = 0;
			foreach( $this->timers as $timer ) {
				?>
				<div class="ptt-fields-wrapper ptt-time-history-<?php echo $i+1; ?>">
					<a href="javascript:;" class="ptt-delete-time-history"><i class="fa fa-trash-o"></i></a>
					<?php $this->the_fields( $i ); ?>
				</div>
				<?php 
				$i++;
			}
		}
	}



	/**
	 * displays the fields for date filtering through timers
	 * 
	 * @return string html for date filter fields
	 */
	public function the_date_filter() {
		?>
		<p><strong>Filter By Date:</strong></p>
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
			) ); ?>
		</div>
		<?php 
	}



	/**
	 * display the total of all timers for a particular task
	 * 
	 * @return string html for total
	 */
	public function the_total() {
		echo '<div class="ptt-filter-total-container premise-float-right premise-align-right span4">
			Total: 
			<span class="ptt-filter-total">
				' . esc_html( $this->total ) . '
			</span>
		</div>';
	}




	/**
	 * save the total of all timers to our object
	 * 
	 * @return void saves total to obkect property $total
	 */
	public function get_total() {
		$total = 0.00;

		if ( is_array( $this->timers ) && ! empty( $this->timers ) ) {

			foreach ( $this->timers as $k => $timer ) {
				if ( is_array( $timer ) && isset( $timer['timer'] ) && ! empty( $timer['timer'] ) ) {
					$total = $total + (float) $timer['timer'];
				}
			}
		}

		$this->total = $total;
	}




	/**
	 * Reset the object
	 * 
	 * @return void resets timers and count
	 */
	protected function reset() {
		$this->timers = premise_get_value( 'ptt_meta[timers]', 'post' ) ? premise_get_value( 'ptt_meta[timers]', 'post' ) : array();
        $this->count = ! empty( $this->timers ) ? count( $this->timers ) : $this->count;
        $this->get_total();
	}




	/**
	 * Reorganize the ptt_meta timers array to ensure the order does not break
	 * 
	 * @return mixed return an array if there is something to save
	 */
	public function reset_ptt_meta() {
		if ( isset( $_POST['ptt_meta']['timers'] ) && is_array( $_POST['ptt_meta']['timers'] ) ) {
			
			$_timers = $_POST['ptt_meta']['timers'];
			$timers_organized = array();

			// Remove the first timer 
			// For some reason the last timer comes up
			// first in the array
			$fst_elmnt = array_shift( $_timers );
			
			$i = 0;
			foreach ( $_timers as $k => $timer ) {
				$timers_organized[$i] = $timer;
				$i++;
			}
			
			// Place the first element as the last element 
			// where it belongs
			$timers_organized[] = $fst_elmnt;

			$_POST['ptt_meta']['timers'] = $timers_organized;
		}
		return $_POST['ptt_meta'];
	}




	/**
	 * Save the ptt_meta if it is safe
	 * 
	 * @param  integer $post_id the post id
	 * @return mixed          returns the post id on failure.
	 */
	public function save( $post_id ) {

		// Check if our nonce is set.
        if ( ! isset( $_POST['ptt_nonce_field'] ) ) {
            return $post_id;
        }
 
        $nonce = $_POST['ptt_nonce_field'];
 
        // Verify that the nonce is valid.
        if ( ! wp_verify_nonce( $nonce, $this->nonce ) ) {
            return $post_id;
        }
 
        /*
         * If this is an autosave, our form has not been submitted,
         * so we don't want to do anything.
         */
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
 
        // Check the user's permissions.
        if ( 'premise_time_track' !== $_POST['post_type'] ) {
            return $post_id;
        }
 
        /* OK, it's safe for us to save the data now. */
 
        // Sanitize the user input.
        $mydata = $this->reset_ptt_meta();
 
        // Update the meta field.
        update_post_meta( $post_id, 'ptt_meta', $mydata );
	}



	
}
?>