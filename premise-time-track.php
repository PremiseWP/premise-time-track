<?php
/**
 * Plugin Name: Premise Time Track
 * Description: Create and track different timers. Perfect to manage freelancers.
 * Plugin URI:
 * Version:     2.0.0
 * Author:      Premise WP
 * Author URI:  http://premisewp.com
 * License:     GPL
 * Text Domain: pwptt-text-domain
 *
 * @package Premise Time Track
 */

/**
 * Define constants for plugin's url and path
 */
define( 'PTT_PATH', plugin_dir_path( __FILE__ ) );
define( 'PTT_URL',  plugin_dir_url(  __FILE__ ) );


/**
 * Intantiate and setup Premise Boxes
 *
 * @todo check for premise wp before running plugin
 */
add_action( 'plugins_loaded', array( Premise_Time_track::get_instance(), 'setup' ) );


/**
 * Premise Time Track class.
 */
class Premise_Time_track {



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
		include 'library/functions.php';
		include 'controller/class-time-track-cpt.php';
		include 'controller/class.render.php';
		include 'model/class.user-profile.php';
	}


	/**
	 * Registers our hooks
	 */
	public function do_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );

		add_action( 'load-post.php',     array( PTT_Meta_Box::get_instance(), 'hook_box' ) );
		add_action( 'load-post-new.php', array( PTT_Meta_Box::get_instance(), 'hook_box' ) );

		add_action( 'wp_ajax_ptt_search_timers', 'ptt_search_timers' );
		add_action( 'wp_ajax_nopriv_ptt_search_timers', 'ptt_search_timers' );

		add_filter( 'template_include',  array( PTT_Render::get_instance(), 'init' ), 99 );

		add_action( 'show_user_profile', array( PTT_User_Profile::get_inst(), 'custom_fields' ) );
		add_action( 'edit_user_profile', array( PTT_User_Profile::get_inst(), 'custom_fields' ) );

		// add_action( 'personal_options_update', 'tm_save_profile_fields' );
		// add_action( 'edit_user_profile_update', 'tm_save_profile_fields' );
	}


	/**
	 * Register the cpt if premise wp exists
	 */
	public function register_cpt() {
		if ( class_exists( 'PremiseCPT' ) ) {

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
				),
				'menu_icon'    => 'dashicons-clock',
			) );

			$time_track_cpt->register_taxonomy( array(
				'taxonomy_name' => 'premise_time_tracker_client',
				'singular'      => 'Client',
				'plural'        => 'Clients',
				'slug'          => 'time-traker-client',
			),
			array(
				'hierarchical' => true,
			) );

			$time_track_cpt->register_taxonomy( array(
				'taxonomy_name' => 'premise_time_tracker_project',
				'singular'      => 'Project',
				'plural'        => 'Projects',
				'slug'          => 'time-traker-project',
			),
			array(
				'hierarchical' => false,
			) );

			$time_track_cpt->register_taxonomy( array(
				'taxonomy_name' => 'premise_time_tracker_timesheet',
				'singular'      => 'Timesheet',
				'plural'        => 'Timesheets',
				'slug'          => 'timesheet',
			),
			array(
				'hierarchical' => false,
			) );
		}
	}



	/**
	 * Register and enqueue styles and scripts for the backend.
	 */
	public function scripts() {
		// register
		wp_register_style(  'pwptt_css', PTT_URL . 'css/premise-time-track.min.css' );
		wp_register_script( 'pwptt_js' , PTT_URL . 'js/premise-time-track.min.js' );
		// enqueue
		wp_enqueue_style(  'pwptt_css' );
		wp_enqueue_script( 'pwptt_js' );
	}
}
