<?php
	/*
		Plugin Name: Ongraph Mpress
		Author: Dinesh
		Author URI: https://www.ongrpah.com/
	*/

	if ( ! defined( 'ABSPATH' ) ) {
	    exit;
	}

	// Include necessary classes
	include_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
	include_once plugin_dir_path( __FILE__ ) . 'includes/class-admin.php';
	include_once plugin_dir_path( __FILE__ ) . 'includes/class-db.php';

	class OngMpressPlugin {

	    public function __construct() {
	        // Instantiate classes
	        $this->activator = new ActiveMpressPlugin();
	        $this->admin = new DashboardFunction();
	        $this->db = new PluginDatabase();

	        // Hook into activation
	        register_activation_hook( __FILE__, [ $this->activator, 'activate' ] );

	        // Hook into admin notices
	        add_action( 'admin_notices', [ $this->admin, 'admin_notice' ] );

	        // Hook into admin menu
	        // add_action( 'admin_menu', [ $this->admin, 'add_menu' ] );

	        // Hook into admin initialization
	        add_action( 'admin_init', [ $this->admin, 'settings_init' ] );
	    }
	}

	// Instantiate the plugin class
	new OngMpressPlugin();
?>
