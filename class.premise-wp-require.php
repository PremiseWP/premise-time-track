<?php
/**
 * This class requires premise wp
 */
class Premise_WP_Require {
    
    /**
     * holds plugins to be required
     * 
     * @var array
     */
    protected $plugins = array();


    protected $config = array(
        'id'           => '',
        'default_path' => '',              
        'menu'         => '',     
        'parent_slug'  => 'themes.php',   
        'capability'   => 'manage_options',
        'has_notices'  => true,            
        'dismissable'  => true,            
        'dismiss_msg'  => '',              
        'is_automatic' => true,           
        'message'      => '',              
    );

    
    function __construct( $id = '' ) {
        if ( is_string( $id ) ) {
            $this->config['id'] = esc_attr( $id );
            $this->config['menu'] = esc_attr( $id . '-page' );
        }
        else {
            wp_parse_args( $id, $this->config );
        }


        if ( ! class_exists( 'Premise_WP' ) )
            $this->plugins[] = premisewp();

        if ( ! empty( $this->plugins ) )
            tgmpa( $this->plugins, $this->config );
    }


    public function premisewp() {
        $premisewp = array(
            'name'               => 'Premise WP Plugin',
            'slug'               => 'Premise-WP',
            'source'             => 'https://github.com/PremiseWP/Premise-WP/archive/master.zip',
            'required'           => true, 
            'version'            => '', 
            'force_activation'   => false, 
            'force_deactivation' => false, 
            'external_url'       => '', 
            'is_callable'        => '', 
        );
        return $premisewp;
    }
}