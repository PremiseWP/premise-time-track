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
		include 'controller/class.rest-users.php';
		include 'controller/class.rest-taxonomies.php';
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
		add_action( 'rest_api_init', array( PTT_Rest::get_inst(), 'register_routes') );

		// register endpoint for loging in from ap
		// add_action( 'rest_api_init', function () {
		//   register_rest_route( 'premise_time_tracker/v2',
		//   	'/newuser',
		//   	// .'/(?P<username>[a-zA-Z0-9-_]+)'.'&(?P<password>[\w*$!-?:]+)'.'&(?P<email>([\w-\.]@[\w]\.[\w]{2,3})+)
		//   	array(
		//     'methods' => 'POST',
		//     'callback' => 'ttt_new_user',
		//   ) );
		// } );
		// // register endpoint to remove taxonomies
		// add_action( 'rest_api_init', function () {
		//   register_rest_route( 'premise_time_tracker/v2',
		//   	'/remove_client_or_project',
		//   	array(
		//     'methods' => 'GET',
		//     'callback' => 'ttt_remove_client_or_project',
		//   ) );
		// } );

		// add_action( 'rest_api_init', function () {
		//   register_rest_route( 'premise_time_tracker/v2',
		//   	'/forgot_password',
		//   	array(
		//     'methods' => 'GET',
		//     'callback' => 'ttt_forgot_password',
		//   ) );
		// } );

		remove_filter( 'the_content', 'wpautop' );

		add_action( 'init', array( Premise_Time_tracker::get_instance(), 'add_user_roles' ) );

		// add_action( 'phpmailer_init', 'ttt_mailer_config', 10, 1);
		// add_action('wp_mail_failed', 'ttt_log_mailer_errors', 10, 1);


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

function ttt_forgot_password() {
	$username = isset( $_GET['username'] ) ? sanitize_text_field( $_GET['username'] ) : false;
	$email = isset( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : false;

	if ( ! $username && ! $email ) {
		return wp_send_json_error( array( 'message' => 'No params supplied.') );
	}

	$user = ($username) ? get_user_by('login', $username) : get_user_by('email', $email);

	if ( ! $user ) {
		return wp_send_json_error( array( 'message' => 'We cannot find your user.') );
	}

	if ( $username ) {
		$password = wp_generate_password();
		wp_set_password( $password, $user->ID );

		ttt_send_new_pw($user->user_email, $user->user_login, $password);
	}
	else if ( $email ) {
		ttt_send_username( $email, $user->user_login );
	}

	return true;
}

function ttt_remove_client_or_project() {
	// 1. authenticate the user
	$request  = new WP_REST_Request( 'GET', '/wp/v2/users/me' );
	$response = rest_do_request( $request );
	if ( $response->is_error() ) {
		// not sucessful.
		// return response.
		return $response;
	}

	$removed = wp_delete_term( $_GET['id'], $_GET['tax'] );

	if ( ! is_wp_error( $removed ) ) {
		return wp_send_json( array( 'success' => true ) );
	}
	return wp_send_json_error();
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

		switch ( $_REQUEST['action'] ) {
			case 'check_user' :
				$users = new WP_User_Query( array(
					'search' => '*'.sanitize_text_field($_REQUEST['s']).'*',
					'search_columns' => array( 'user_login', 'user_email' ),
					'exclude' => sanitize_text_field($_REQUEST['current_user']),
					'blog_id' => 0,
				) );
				return wp_send_json( $users->get_results() );
			break;
			case 'add_user' :
				$user_id = isset( $_REQUEST['user_id'] ) ? (int)$_REQUEST['user_id'] : null;
				$blog_id  = get_current_blog_id();

				if ( $user_id ) {
					// 1. Let's add user to this site
					$add_to_blog = add_user_to_blog($blog_id, $user_id, 'pwptt_freelancer');

					// 2. handle response
					if ( ! is_wp_error( $add_to_blog ) ) {
						$user = get_userdata( $user_id );
						ttt_send_new_user($user->user_email, $blog_id, $user->user_login );
						return wp_send_json( $add_to_blog );
					}
					else {
						return wp_send_json_error( array(
							'message' => 'The user could not be added to your organization.',
						) );
					}
				}
				else {
					return wp_send_json_error( array(
						'message' => 'No user ID supplied.',
					) );
				}
			break;
			case 'new_user' :
				// TODO: sanitize data
				$email    = isset( $_REQUEST['email'] )    ? sanitize_email( $_REQUEST['email'] )    : null;
				$username = isset( $_REQUEST['username'] ) ? sanitize_text_field( $_REQUEST['username'] ) : null;
				$password = wp_generate_password(); // isset( $_REQUEST['password'] ) ? $_REQUEST['password'] : null;
				$blog_id  = get_current_blog_id();

				// 1. create the user
				// do not send password change email
				add_filter( 'send_password_change_email', false );
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
				// revert back this filter.
				add_filter( 'send_password_change_email', true );

				// 2. Let's add user to this site
				$add_to_blog = add_user_to_blog($blog_id, $user['id'], 'pwptt_freelancer');

				// 3. handle response
				if ( ! is_wp_error( $add_to_blog ) ) {
					// 4. send email to user about with password and login info
					ttt_send_new_user($email, $blog_id, $username, $password);

					return wp_send_json( $user );
				}
				else {
					return wp_send_json_error( array(
						'message' => 'The user was created but they could not be added to your organization.',
					) );
				}
			break;
			case 'remove_user' :
				$user_id = isset( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : null;
				$blog_id  = get_current_blog_id();

				remove_user_from_blog($user_id, $blog_id);

				return wp_send_json( array( 'message' => 'done.' ) );
			break;
			default :
				// handle registrations
			break;
		}
	}
	die();
}

function ttt_send_new_user($email, $blog_id, $username ='', $password='Your current password') {
	$_blog = get_blog_details( $blog_id );
	$to = $email;
	$subject = 'You have been added to ' . $_blog->blogname;
	$headers = array('Content-Type: text/html; charset=UTF-8','From: Premise Time Tracker <no-reply@premisetimetracker.com');
	$body = "
		<!DOCTYPE html>
		<html>
		<head>
			<title>{$subject}</title>
		</head>
		<body>
			<h1>Hi {$username},</h1>
			<p>You have been added to the organization {$_blog->blogname}. To log in and begin tracking your time <a href='http://premisetimetracker.com'>click here</a>, your username and password are below.</p>
			<p>Username: {$username}<br>
				password: {$password}</p>
		</body>
		</html>
	";

	wp_mail( $to, $subject, $body, $headers );
}

function ttt_send_new_pw($email, $username ='', $password='') {
	$blog_id    = get_current_blog_id();
	$blog = get_blog_details( $blog_id );
	$url = str_replace('.api.', '.', $blog->siteurl);
	$to = $email;
	$subject = 'Password Reset';
	$headers = array('Content-Type: text/html; charset=UTF-8','From: Premise Time Tracker <no-reply@premisetimetracker.com');
	$body = "
		<!DOCTYPE html>
		<html>
		<head>
			<title>{$subject}</title>
		</head>
		<body>
			<h1>Hi {$username},</h1>
			<p>Your password has been reset and a new password has been generated for you. Please log into your account at {$url} to change your password to something you would like.</p>
			<p>New password: {$password}</p>
		</body>
		</html>
	";

	wp_mail( $to, $subject, $body, $headers );
}

function ttt_send_username($email, $username ='') {
	$blog_id    = get_current_blog_id();
	$blog = get_blog_details( $blog_id );
	$url = str_replace('.api.', '.', $blog->siteurl);
	$to = $email;
	$subject = 'Here is you username';
	$headers = array('Content-Type: text/html; charset=UTF-8','From: Premise Time Tracker <no-reply@premisetimetracker.com');
	$body = "
		<!DOCTYPE html>
		<html>
		<head>
			<title>{$subject}</title>
		</head>
		<body>
			<p>Your username is {$username}. Go to {$url} to login.</p>
		</body>
		</html>
	";

	wp_mail( $to, $subject, $body, $headers );
}

function ttt_get_callback() {
	return (array) wp_current_user();
}

function ttt_update_callback() {
	return null;
}

function ttt_mailer_config(PHPMailer $mailer){
  $mailer->IsSMTP();
  $mailer->Host = "smtp.gmail.com"; // your SMTP server
  $mailer->Port = 465;
  $mailer->SMTPDebug = 2; // write 0 if you don't want to see client/server communication in page
  $mailer->CharSet  = "utf-8";
}

function ttt_log_mailer_errors(){
  $fn = ABSPATH . '/mail.log'; // say you've got a mail.log file in your server root
  $fp = fopen($fn, 'a');
  fputs($fp, "Mailer Error: " . $mailer->ErrorInfo ."\n");
  fclose($fp);
}