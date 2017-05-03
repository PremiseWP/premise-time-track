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
define( 'PTT_URL',  plugin_dir_url( __FILE__ ) );


/**
 * When activating plugin, create Freelancer & Client roles.
 */
register_activation_hook( __FILE__, array( Premise_Time_tracker::get_instance(), 'add_freelancer_role' ) );
register_activation_hook( __FILE__, array( Premise_Time_tracker::get_instance(), 'add_client_role' ) );


/**
 * Intiate and setup the plugin
 *
 * @todo check for premise wp before running plugin
 * TODO: require PremiseWP, Oauth server, REST api...
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
		// 2. register our CPT
		$this->register_cpt();
		// 3. do hooks
		$this->do_hooks();

	}


	/**
	 * Includes all our required files
	 */
	public function do_includes() {
		// Require Premise WP.
		if ( ! class_exists( 'Premise_WP' )
			|| ! class_exists( 'WP_REST_Controller' )
			|| ! function_exists( 'rest_oauth1_init' ) ) {

			// Require Premise WP plugin with the help of TGM Plugin Activation.
			require_once PTT_PATH . 'includes/class-tgm-plugin-activation.php';

			add_action( 'tgmpa_register', array( $this, 'register_required_plugins' ) );

			return;
		}

		include 'controller/class.user-fields.php';
		include 'model/class.time-tracker-mb.php';
		include 'model/class.rest-api.php';
		include 'controller/class.render.php';
		include 'library/functions.php';
	}


	/**
	 * Registers our hooks
	 */
	public function do_hooks() {

		if ( ! class_exists( 'PTT_Meta_Box' ) ) {
			return;
		}

		// Register scripts
		add_action( 'wp_enqueue_scripts'               , array( $this                        , 'scripts' ) );
		// Hook the metabox used in the post edit screen
		add_action( 'load-post.php'                    , array( PTT_Meta_Box::get_instance() , 'hook_box' ) );
		add_action( 'load-post-new.php'                , array( PTT_Meta_Box::get_instance() , 'hook_box' ) );
		// Register the ajax hook so that we can search for timers
		add_action( 'wp_ajax_ptt_search_timers'        , 'ptt_search_timers' );
		add_action( 'wp_ajax_nopriv_ptt_search_timers' , 'ptt_search_timers' );
		// Add author rewrite rule for our CPT.
		add_filter( 'generate_rewrite_rules'           , array( PTT_Render::get_instance()   , 'author_rewrite_rule' ) );
		// switch the template to display ours whenever we are showing a premise time tracker page
		add_filter( 'template_include'                 , array( PTT_Render::get_instance()   , 'init' )                  , 99 );
		// Filter the main query when we are loading a premise time tracker taxnomy page
		add_filter( 'pre_get_posts'                    , 'ptt_filter_main_loop' );

		// Filter the terms for Freelancer profile.
		add_filter( 'get_terms_args', 'pwptt_filter_terms', 10, 2 );

		// REST API init.
		add_action( 'rest_api_init'                    , array( PTT_Meta_Box::get_instance() , 'register_meta_fields' ) );
		add_action( 'rest_api_init'                    , array( PTT_User_Fields::get_instance() , 'register_meta_fields' ) );

		// Edit the Client user profile page and insert our custom fields at the bottom.
		add_action( 'init'                             , array( PTT_User_Fields::get_instance(), 'init' ) );
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
		}
			wp_enqueue_script( 'wp-api' );
	}


	/**
	 * Add our Freelancer role.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_role/
	 *
	 * @link https://codex.wordpress.org/Roles_and_Capabilities#Author
	 * Author – somebody who can publish and manage their own posts.
	 */
	public function add_freelancer_role() {

		remove_role( 'pwptt_freelancer' );

		add_role(
			'pwptt_freelancer',
			'Freelancer',
			array(
				'edit_published_posts' => true,
				'upload_files' => true,
				'publish_posts' => true,
				'delete_published_posts' => true,
				'edit_posts' => true,
				'delete_posts' => true,
				'read' => true,
				// Needed for Freelancers to add Client / Project / Timesheet to Timer in REST.
				'manage_categories' => true,
			)
		);
	}




	/**
	 * Add our Client role.
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_role/
	 *
	 * @link https://codex.wordpress.org/Roles_and_Capabilities#Subscriber
	 * Subscriber – somebody who can only manage their profile.
	 */
	public function add_client_role() {

		remove_role( 'pwptt_client' );

		add_role(
			'pwptt_client',
			'Client',
			array(
				'read' => true,
			)
		);
	}




	/**
	 * Register the required plugins for this theme.
	 *
	 * We register 2 plugins:
	 * - Premise-WP from a GitHub repository
	 * - WP REST API from Wordpress
	 *
	 * @link https://github.com/PremiseWP/Premise-WP
	 */
	public function register_required_plugins() {
		/*
		 * Array of plugin arrays. Required keys are name and slug.
		 * If the source is NOT from the .org repo, then source is also required.
		 */
		$plugins = array(

			array(
				'name'             => 'Premise-WP',
				'slug'             => 'Premise-WP',
				'source'           => 'https://github.com/PremiseWP/Premise-WP/archive/master.zip',
				'required'         => true,
				'force_activation' => false,
			),
			array(
				'name'             => 'Wordpress REST API',
				'slug'             => 'rest-api',
				'source'           => 'https://wordpress.org/plugins/rest-api/',
				'required'         => true,
				'force_activation' => false,
			),
			array(
				'name'             => 'WordPress REST API - OAuth 1.0a Server',
				'slug'             => 'rest-api-oauth1',
				'source'           => 'https://wordpress.org/plugins/rest-api-oauth1/',
				'required'         => true,
				'force_activation' => false,
			),
		);

		/*
		 * Array of configuration settings.
		 */
		$config = array(
			'id'           => 'ptt-tgmpa',
			'default_path' => '',
			'menu'         => 'tgmpa-install-plugins',
			'parent_slug'  => 'plugins.php',
			'capability'   => 'install_plugins',
			'has_notices'  => true,
			'dismissable'  => false,
			'dismiss_msg'  => '',
			'is_automatic' => true,
			'message'      => '',
		);

		tgmpa( $plugins, $config );
	}
}
