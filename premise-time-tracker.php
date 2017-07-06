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

// TODO find proper way to bind these two
header( 'Access-Control-Allow-Origin: *' );
error_reporting(E_ERROR);

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
register_activation_hook( __FILE__, array( Premise_Time_tracker::get_instance(), 'add_user_roles' ) );


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

		include 'model/class.time-tracker-mb.php';
		include 'model/class.rest-api.php';
		include 'controller/class.user-fields.php';
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
		add_filter( 'generate_rewrite_rules', array( 'PTT_Render', 'author_rewrite_rule' ) );
		// switch the template to display ours whenever we are showing a premise time tracker page
		add_filter( 'template_include', array( PTT_Render::get_instance(), 'init' ), 99 );
		// Filter the main query when we are loading a premise time tracker taxnomy page
		add_filter( 'pre_get_posts'                    , 'ptt_filter_main_loop' );

		// Filter the terms for Freelancer profile.
		add_filter( 'get_terms_args', 'pwptt_filter_terms', 10, 2 );

		// REST API init.
		add_action( 'rest_api_init', array( 'PTT_Meta_Box', 'register_meta_fields' ) );
		add_action( 'rest_api_init', array( 'PTT_User_Fields', 'register_meta_fields' ) );

		// register endpoint for loging in from ap
		add_action( 'rest_api_init', function () {
		  register_rest_route( 'premise_time_tracker/v2', '/currentuser', array(
		    'methods' => 'GET',
		    'callback' => 'ttt_current_user',
		    // 'args' => array( 'username', 'password', 'email'),
		  ) );
		} );
		// register endpoint for loging in from ap
		add_action( 'rest_api_init', function () {
		  register_rest_route( 'premise_time_tracker/v2',
		  	'/newuser',
		  	// .'/(?P<username>[a-zA-Z0-9-_]+)'.'&(?P<password>[\w*$!-?:]+)'.'&(?P<email>([\w-\.]@[\w]\.[\w]{2,3})+)
		  	array(
		    'methods' => 'POST',
		    'callback' => 'ttt_new_user',
		  ) );
		} );

		remove_filter( 'the_content', 'wpautop' );

		add_action( 'init', array( Premise_Time_tracker::get_instance(), 'add_user_roles' ) );

		// add_action( 'rest_api_init', 'ttt_current_user' );

		// Edit the Client user profile page and insert our custom fields at the bottom.
		// for now, dont call this.
		// add_action( 'init', array( PTT_User_Fields::get_instance(), 'init' ) );

		// Use * for origin
		// add_action( 'rest_api_init', function() {

		// 	remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

		// 	add_filter( 'rest_pre_serve_request', function( $value ) {
		// 		header( 'Access-Control-Allow-Origin: *' );
		// 		header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
		// 		header( 'Access-Control-Allow-Credentials: true' );

		// 		return $value;

		// 	});
		// } );

			// add_filter( 'rest_pre_serve_request', function( $value ) {

			// 	$origin = get_http_origin();
			// 		header( 'Access-Control-Allow-Origin: *' /*. esc_url_raw( $origin )*/ );
			// 		header( 'Access-Control-Allow-Methods: POST, GET, OPTIONS, PUT, DELETE' );
			// 		header( 'Access-Control-Allow-Credentials: true' );

			// 	return $value;

			// });
		// TODO Ability to set origins from backend.
		// add_action( 'rest_api_init', function() {

		// 	remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

		// }, 9 );
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
					'author',
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
		wp_enqueue_script( 'wp-api' );
	}


	/**
	 * Add user roles
	 */
	public function add_user_roles() {
		// client
		// remove_role( 'pwptt_client' );

		// add_role(
		// 	'pwptt_client',
		// 	'Client',
		// 	array(
		// 		'read' => true,
		// 	)
		// );

		// freelancer
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


function ttt_current_user() {
	// 1. Try Logging in the user
	$request  = new WP_REST_Request( 'GET', '/wp/v2/users/me' );
	$response = rest_do_request( $request );
	if ( $response->is_error() ) {
		// not sucessful.
		// return response.
		return $response;
	}
	// Successful
	// get user from the response
	$user = $response->get_data();
	// 2. check this user has access to this blog
	$has_access = false;
	$blog_id    = get_current_blog_id();
	$user_blogs = get_blogs_of_user( $user['id'] );
	foreach ($user_blogs as $blog) {
		if ( (int) $blog_id === (int) $blog->userblog_id ) {
			$has_access = true;
		}
	}
	// if no access return error
	if ( ! $has_access ) {
		return wp_send_json_error( array(
			'message' => 'You do no have access to this organization.'
		) );
	}
	// 3. return the user and the site info
	return wp_send_json(array(
		'user' => $user,
		'site' => get_blog_details( $blog_id ),
	));
}

function ttt_new_user() {
	// return wp_send_json( $_REQUEST );
	// TODO: check HTTP_REFERER
	if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
		// TODO: sanitize data
		$username = isset( $_REQUEST['username'] ) ? $_REQUEST['username'] : null;
		$password = isset( $_REQUEST['password'] ) ? $_REQUEST['password'] : null;
		$email    = isset( $_REQUEST['email'] )    ? $_REQUEST['email']    : null;
		$blog_id  = get_current_blog_id();

		// 1. create the user
		$request = new WP_REST_Request( 'POST', '/wp/v2/users' );
		$request->set_param( 'username', $username );
		$request->set_param( 'email', $email );
		$request->set_param( 'password', $password );
		$response = rest_do_request( $request );
		if ( $response->is_error() ) {
			// not sucessful.
			// return response.
			return wp_send_json_error( $response );
		}
		// we have a user
		$user = $response->get_data();

		// 2. Let's add user to this site
		$add_to_blog = add_user_to_blog($blog_id, $user['id'], 'pwptt_freelancer');

		// 3. handle response
		if ( ! is_wp_error( $add_to_blog ) ) {
			return wp_send_json( $user );
		}
		else {
			return wp_send_json_error( array(
				'message' => 'The user was created but they could not be added to your organization.',
			) );
		}
	}
	die();
}

// function ttt_new_user() {
// 	if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
// 		var_dump('expression');
// 	}
// 	else {

// 		$user_id = wp_create_user(
// 			'createdonthefly',
// 			'$passwordcreatedonthefly',
// 			'laksjdfhaksdjfhalsdkfj@email.com'
// 		);
// 		$blog_id = wpmu_create_blog(
// 			'http://test.time.dev',
// 			'/',
// 			'$title',
// 			1
// 		);
// 		return json_encode( $user_id );
// 	}
// 	// $request = new WP_REST_Request( 'GET', '/wp/v2/users/me' );
// 	// // Set one or more request query parameters
// 	// // $request->set_param( 'per_page', 20 );
// 	// $response = rest_do_request( $request );
// 	// return $response;
// }

function ttt_get_callback() {
	return (array) wp_current_user();
}

function ttt_update_callback() {
	return null;
}