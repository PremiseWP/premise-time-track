<?php
/**
* This class decides wchich template to show when viewing a post in wordpress when the Premise Time Tracker plugin is active. Before deciding which template to use, or even whether to interrupt Wordpress' normal flow for making this decision, this class checks to make sure that Wordpress is about to load a Premise Time Tracker post or taxonomy. We only interrupt Wordpress' norma flow if said scenario is true.
*
* @package Premise Time Tracker\Controller
*/
class PTT_Render {

	/**
	 * Holds an instance of this class
	 *
	 * @var null
	 */
	public static $instance = NULL;


	/**
	 * holds array of taxonomies used by our premise_timer_tracker custom post type
	 *
	 * @var array
	 */
	public $taxonomies = array( 'premise_time_tracker_client', 'premise_time_tracker_project', 'premise_time_tracker_timesheet' );


	/**
	 * holds the path to the taxonomie view temlate
	 *
	 * @var string
	 */
	public static $tax_view_path = PTT_PATH . 'view/taxonomy-premise-time-tracker.php';


	/**
	 * Holds path for time card template
	 *
	 * @var string
	 */
	public static $time_card_path = PTT_PATH . '/view/content-loop-premise-time-tracker.php';


	/**
	 * Holds the path to the single timer template
	 *
	 * @var string
	 */
	public static $timer_view_path = PTT_PATH . 'view/single-premise-time-tracker.php';


	/**
	 * Holds the total hours on the group of timers queried
	 *
	 * @var float
	 */
	public static $total = 0.00;


	/**
	 * we leave the construct function empty on purpose
	 */
	function __construct() {}


	/**
	 * Instantiates our class
	 *
	 * @return object instance of this class
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}


	/**
	 * Decide whether to interrupt Wordpress and show one of our templates instead or not. If we decide to interrupt Wordpress this function allows the theme author to take control and override us.
	 *
	 * @return string           the new template we are telling Wordpress to load. (may be different or the same as the original).
	 */
	public function init( $template ) {
		// No admin bar if viewed from Chrome extension / iframe.
		if ( isset( $_GET['iframe'] )
			&& $_GET['iframe'] ) {

			add_filter( 'show_admin_bar', '__return_false' );
		}

		// if Wordpress is about to load one of our taxonomies
		if ( is_tax( $this->taxonomies ) ) {
			$new_template = locate_template( array( 'premise-time-tracker/taxonomy-premise-time-tracker.php' ) );
			$template     = ( '' != $new_template ) ? $new_template : self::$tax_view_path;
		}
		// if Wordpress is about to load one of our timers
		else if ( is_singular( 'premise_time_tracker' ) ) {
			$new_template = locate_template( array( 'premise-time-tracker/single-premise-time-tracker.php' ) );
			$template     = ( '' != $new_template ) ? $new_template : self::$timer_view_path;
		}
		return $template;
	}


	/**
	 * get the timer card template and update the total. must be called within a loop
	 *
	 * @return string html for timer card
	 */
	public static function the_timer_card() {
		self::$total += pwptt_get_timer();
		$new_template = locate_template( array( 'premise-time-tracker/content-loop-premise-time-tracker.php' ) );
		include ( '' != $new_template ) ? $new_template : self::$time_card_path;
	}
}
