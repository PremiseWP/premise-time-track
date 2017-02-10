<?php
/**
 * Plugin Name: Premise Time Tracker
 * Description: Easily track time spent in tasks and assing them to a client, project, or/and timesheet. Track tasks by adding a "Timer" (new custom post type created by the plugin) per task. Clients, projects, and timesheets are toxonomies of the Timer custom post type.
 * Plugin URI:  https://github.com/PremiseWP/premise-time-track
 * Version:     2.0.0
 * Author:      Premise WP
 * Author URI:  http://premisewp.com
 * License:     GPL
 * Text Domain: pwptt-text-domain
 *
 * @package Premise Time Tracker
 */

/**
 * Plugin path
 *
 * @var constant PTT_PATH
 */
define( 'PTT_PATH', plugin_dir_path( __FILE__ ) );


/**
 * Plugin url
 *
 * @var constant PTT_URL
 */
define( 'PTT_URL',  plugin_dir_url(  __FILE__ ) );


/**
 * Intiate and setup the plugin
 *
 * @todo check for premise wp before running plugin
 */
add_action( 'plugins_loaded', array( Premise_Time_tracker::get_instance(), 'setup' ) );


/**
 * Premise Time Tracker class.
 *
 * The main class for our plugin. This class sets up the plugin, loads all required files, and registered all necessary hooks for the plugin to function properly.
 */
class Premise_Time_tracker {

	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @var object
	 */
	protected static $instance = null;


	/**
	 * Constructor. Intentionally left empty and public.
	 *
	 * @see 	setup()
	 * @since 	1.0
	 */
	public function __construct() {}


	/**
	 * Access this plugin’s working instance
	 *
	 * @since   1.0
	 * @return  object Instance for this class
	 */
	public static function get_instance() {
		null === self::$instance and self::$instance = new self;

		return self::$instance;
	}


	/**
	 * works as our construct function.
	 */
	public function setup() {
		// 1. do includes
		$this->do_includes();
		// 2. do hooks
		$this->do_hooks();
		// 3. register our CPT
		$this->register_cpt();

	}


	/**
	 * Includes all our required files
	 */
	public function do_includes() {
		include 'model/class.user-profile.php';
		include 'model/class.time-tracker-mb.php';
		include 'model/class.rest-api.php';
		include 'controller/class.render.php';
		include 'library/functions.php';
	}


	/**
	 * Registers our hooks
	 */
	public function do_hooks() {
		// Register scripts
		add_action( 'wp_enqueue_scripts'               , array( $this                        , 'scripts' ) );
		// Hook the metabox used in the post edit screen
		add_action( 'load-post.php'                    , array( PTT_Meta_Box::get_instance() , 'hook_box' ) );
		add_action( 'load-post-new.php'                , array( PTT_Meta_Box::get_instance() , 'hook_box' ) );
		// Register the ajax hook so that we can search for timers
		add_action( 'wp_ajax_ptt_search_timers'        , 'ptt_search_timers' );
		add_action( 'wp_ajax_nopriv_ptt_search_timers' , 'ptt_search_timers' );
		// switch the template to display ours whenever we are showing a premise time tracker page
		add_filter( 'template_include'                 , array( PTT_Render::get_instance()   , 'init' )                  , 99 );
		// Filter the main query when we are loading a premise time tracker taxnomy page
		add_filter( 'pre_get_posts'                    , 'ptt_filter_main_loop' );
		// REST API init.
		add_action( 'rest_api_init'                    , array( PTT_Meta_Box::get_instance() , 'register_meta_fields' ) );
		/* The following hooks are commented out for now. Will be used later to set ACL */
		// Edit the user profile page and insert our custom fields at the bottom
		// add_action( 'show_user_profile'                , array( PTT_User_Profile::get_inst() , 'custom_fields' ) );
		// add_action( 'edit_user_profile'                , array( PTT_User_Profile::get_inst() , 'custom_fields' ) );
		// Register the hook to save our fields in the user profile pages
		// add_action( 'personal_options_update'          , array( PTT_User_Profile::get_inst() , 'save_custom_fields' ) );
		// add_action( 'edit_user_profile_update'         , array( PTT_User_Profile::get_inst() , 'save_custom_fields' ) );
	}


	/**
	 * Registers the custom post type and its taxonomies if PremiseCPT class exists
	 */
	public function register_cpt() {
		if ( class_exists( 'PremiseCPT' ) ) {
			// register our CPT
			$time_track_cpt = new PremiseCPT( array(
				'post_type_name' => 'premise_time_tracker',
				'singular'       => 'Timer',
				'plural'         => 'Timers',
				'slug'           => 'time-tracker'
			),
			array(
				'public'       => true,
				'show_in_rest' => true,
				'rest_base'    => 'premise_time_tracker',
				'show_ui'      => true,
				'supports'     => array(
					'title',
					'editor',
					'custom-fields',
				),
				'menu_icon'    => 'dashicons-clock',
			) );
			// register our client taxnomy
			$time_track_cpt->register_taxonomy( array(
				'taxonomy_name' => 'premise_time_tracker_client',
				'singular'      => 'Client',
				'plural'        => 'Clients',
				'slug'          => 'time-tracker-client',
			),
			array(
				'hierarchical' => true,
				'show_in_rest' => true,
			) );
			// register our project taxnomy
			$time_track_cpt->register_taxonomy( array(
				'taxonomy_name' => 'premise_time_tracker_project',
				'singular'      => 'Project',
				'plural'        => 'Projects',
				'slug'          => 'time-tracker-project',
			),
			array(
				'hierarchical' => false,
				'show_in_rest' => true,
			) );
			// register our timesheets taxnomy
			$time_track_cpt->register_taxonomy( array(
				'taxonomy_name' => 'premise_time_tracker_timesheet',
				'singular'      => 'Timesheet',
				'plural'        => 'Timesheets',
				'slug'          => 'time-tracker-timesheet',
			),
			array(
				'hierarchical' => false,
				'show_in_rest' => true,
			) );
		}
	}


	/**
	 * Register and enqueue styles and scripts for the front end.
	 */
	public function scripts() {
		if ( ! is_admin() ) {
			// register
			wp_register_style(  'pwptt_css', PTT_URL . 'css/premise-time-track.min.css' );
			wp_register_script( 'pwptt_js' , PTT_URL . 'js/premise-time-track.min.js' );
			// enqueue
			wp_enqueue_style(  'pwptt_css' );
			wp_enqueue_script( 'pwptt_js' );

			// Localize.
			$localized = array( 'wpajaxurl' => admin_url( 'admin-ajax.php' ) );

			// Allows pwptt_js file to access 'pwptt_localized'.
			wp_localize_script( 'pwptt_js', 'pwptt_localized', $localized );

		}
		else {
			wp_enqueue_script( 'wp-api');
		}
	}
}
