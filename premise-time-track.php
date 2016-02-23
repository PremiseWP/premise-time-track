<?php
/**
 * Plugin Name: Premise Time Track
 * Description: 
 * Plugin URI:	
 * Version:     1.0.0
 * Author:      Premise WP
 * Author URI:  http://premisewp.com
 * License:     GPL
 * Text Domain: ptt-text-domain
 *
 * @package PTT
 */

/**
 * Define constants for plugin's url and path
 */
define( 'PTT_PATH', plugin_dir_path( __FILE__ ) );
define( 'PTT_URL', plugin_dir_url( __FILE__ ) );




/**
 * Check for required plugins
 */
require PTT_PATH . 'plugins/premise-plugin-require.php';




/**
 * Intantiate and setup Premise Boxes
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
	 * Labels for custom post type
	 * 
	 * @var array
	 */
	public $labels = array(
		'post_type_name' => 'premise_time_track',
	    'singular' => 'Timer',
	    'plural' => 'Timers',
	    'slug' => 'premise_time_track'
	);




	/**
	 * options for custom post type
	 * 
	 * @var array
	 */
	public $options = array(
		'public' => false, 
		'show_in_rest' => true,
		'rest_base' => 'premise_time_track', 
		'show_ui' => true, 
		'supports' => array( 
			'title', 
			'editor', 
		),
	);




	public $tax_labels = array(
		'taxonomy_name' => 'premise_time_track_tasklist',
	    'singular' => 'Task List',
	    'plural' => 'Task Lists',
	    'slug' => 'premise_time_track_tasklist'
	);
	



	public $tax_options = array(
		'hirearchical' => true,
	);




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
	 * Require Premise WP
	 * Registers our custom post type and registers our hooks
	 */
	public function setup() {

		// Require Premise WP.
		if ( ! class_exists( 'Premise_WP' ) ) {

			// Require Premise WP plugin with the help of TGM Plugin Activation.
			require_once PTT_PATH . 'plugins/class-tgm-plugin-activation.php';

			add_action( 'tgmpa_register', array( $this, 'ptt_register_required_plugins' ) );
		}

		if ( class_exists( 'PremiseCPT' ) ) {

			$time_track_cpt = new PremiseCPT( $this->labels, $this->options );
			$time_track_cpt->register_taxonomy( $this->tax_labels, $this->tax_options);
			include 'controller/class-time-track-cpt.php';
		}

		$this->do_hooks();
	}



	/**
	 * Register our hooks
	 */
	public function do_hooks() {

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

		if ( class_exists( 'PTT_Meta_Box' ) ) {
			add_action( 'wp_ajax_ptt_new_timer', array( PTT_Meta_Box::get_instance(), 'ajax_new_timer' ) );
		}
	}



	/**
	 * Register and enqueue styles and scripts for the backend.
	 */
	public function scripts() {

		wp_register_style( 'ptt_style', PTT_URL . 'css/premise-time-track.min.css' );
		wp_enqueue_style( 'ptt_style' );

		// wp_register_script( 'ptt_core_js', PTT_URL . 'js/premise-time-track.min.js', array( 'jquery', 'wp-api' ) );
		wp_register_script( 'ptt_core_js', PTT_URL . 'js/premise-time-track.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'ptt_core_js' );

		// JQuery UI (datepicker).
		wp_register_script(	'jquery-ui', PTT_URL . '/js/lib/jquery-ui.min.js', array( 'jquery' ), '4.2.3' );
		wp_enqueue_script( 'jquery-ui' );

		// wp_localize_script( 'ptt_core_js', 'WP_API_Settings', array( 'root' => esc_url_raw( rest_url() ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
	}





	/**
	 * Register the required plugins for this theme.
	 *
	 * We register one plugin:
	 * - Premise-WP from a GitHub repository
	 *
	 * @link https://github.com/PremiseWP/Premise-WP
	 */
	function ptt_register_required_plugins() {
		/*
		 * Array of plugin arrays. Required keys are name and slug.
		 * If the source is NOT from the .org repo, then source is also required.
		 */
		$plugins = array(

			// Include Premise-WP plugin.
			array(
				'name'               => 'Premise-WP', // The plugin name.
				'slug'               => 'Premise-WP', // The plugin slug (typically the folder name).
				'source'             => 'https://github.com/PremiseWP/Premise-WP/archive/master.zip', // The plugin source.
				'required'           => true, // If false, the plugin is only 'recommended' instead of required.
				'version'            => '1.2', // E.g. 1.0.0. If set, the active plugin must be this version or higher. If the plugin version is higher than the plugin version installed, the user will be notified to update the plugin.
				'force_activation'   => false, // If true, plugin is activated upon theme activation and cannot be deactivated until theme switch.
				// 'force_deactivation' => false, // If true, plugin is deactivated upon theme switch, useful for theme-specific plugins.
				// 'external_url'       => '', // If set, overrides default API URL and points to an external URL.
				// 'is_callable'        => '', // If set, this callable will be be checked for availability to determine if a plugin is active.
			),
		);

		/*
		 * Array of configuration settings.
		 */
		$config = array(
			'id'           => 'ptabs-tgmpa',         // Unique ID for hashing notices for multiple instances of TGMPA.
			'default_path' => '',                      // Default absolute path to bundled plugins.
			'menu'         => 'tgmpa-install-plugins', // Menu slug.
			'parent_slug'  => 'plugins.php',            // Parent menu slug.
			'capability'   => 'install_plugins',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
			'has_notices'  => true,                    // Show admin notices or not.
			'dismissable'  => false,                    // If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
			'is_automatic' => true,                   // Automatically activate plugins after installation or not.
			'message'      => '',                      // Message to output right before the plugins table.
		);

		tgmpa( $plugins, $config );
	}
}
