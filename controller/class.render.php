<?php
/**
* This class handles rendering the cpt and
*/
class PTT_Render {



	public static $instance = NULL;



	public $taxonomies = array( 'premise_time_tracker_client', 'premise_time_tracker_project', 'premise_time_tracker_timesheet' );


	/**
	 * we leave the construct function empty on purpose
	 */
	function __construct() {}


	/**
	 * Instantiates our class
	 *
	 * @return void does not return anything
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}


	public function init( $template ) {
		if ( is_tax( $this->taxonomies ) ) {
			$new_template = locate_template( array( 'premise-time-tracker/taxonomy-premise-time-tracker.php' ) );
			if ( '' != $new_template ) {
				$template = $new_template;
			}
			else {
				$template = PTT_PATH . 'view/taxonomy-premise-time-tracker.php';
			}
		}

		else if ( is_singular( 'premise_time_tracker' ) ) {
			$new_template = locate_template( array( 'premise-time-tracker/single-premise-time-tracker.php' ) );
			if ( '' != $new_template ) {
				$template = $new_template;
			}
			else {
				$template = PTT_PATH . 'view/single-premise-time-tracker.php';
			}
		}

		return $template;
	}
}