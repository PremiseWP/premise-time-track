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
define( 'PTT_PATH', plugin_dir_path(__FILE__) );
define( 'PTT_URL', plugin_dir_url(__FILE__) );




/**
 * Check for required plugins
 */
require PTT_PATH . 'plugins/premise-plugin-require.php';




/**
 * Intantiate and setup Premise Boxes
 */
add_action( 'plugins_loaded', array( Premise_Time_track::get_instance(), 'setup' ) );




/**
* 
*/
class Premise_Time_track {



	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;



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
		NULL === self::$instance and self::$instance = new self;
		
		return self::$instance;
	}



	/**
	 * registers our custom post type and registers our hooks
	 */
	public function setup() {
		
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


		add_action('wp_ajax_ptt_new_timer', array( PTT_Meta_Box::get_instance(), 'ajax_new_timer' ) );
	}



	/**
	 * register and enqueue styles and scripts for the backend
	 */
	public function scripts() {

		wp_register_style( 'ptt_style', PTT_URL . 'css/premise-time-track.min.css' );
		wp_enqueue_style( 'ptt_style' );
		
		wp_register_script( 'ptt_core_js', PTT_URL . 'js/premise-time-track.min.js', array( 'jquery', 'wp-api' ) );
		wp_enqueue_script( 'ptt_core_js' );
		
		// wp_localize_script( 'ptt_core_js', 'WP_API_Settings', array( 'root' => esc_url_raw( rest_url() ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );

	}
}

?>