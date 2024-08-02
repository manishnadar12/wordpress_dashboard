<?php
/*
Plugin Name: MainWP Rocket Extension
Plugin URI: https://mainwp.com
Description: MainWP Rocket Extension combines the power of your MainWP Dashboard with the popular WP Rocket Plugin. It allows you to mange WP Rocket settings and quickly Clear and Preload cache on your child sites.
Version: 4.0.1
Author: MainWP
Author URI: https://mainwp.com
Documentation URI: https://mainwp.com/help/category/mainwp-extensions/rocket/
Icon URI:
*/

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}

if ( ! defined( 'MAINWP_WP_ROCKET_PLUGIN_FILE' ) ) {
	define( 'MAINWP_WP_ROCKET_PLUGIN_FILE', __FILE__ );
}

if (!defined('MAINWP_WP_ROCKET_PATH'))
    define( 'MAINWP_WP_ROCKET_PATH', realpath( plugin_dir_path( MAINWP_WP_ROCKET_PLUGIN_FILE ) ) . '/' );

if (!defined('MAINWP_WP_ROCKET_URL'))
    define( 'MAINWP_WP_ROCKET_URL', plugins_url( '', __FILE__ ) );

if (!defined('MAINWP_WP_ROCKET_SLUG'))
    define( 'MAINWP_WP_ROCKET_SLUG' , 'mainwp_wp_rocket_settings' );

if (!defined('MAINWP_ROCKET_GENERAL_SETTINGS'))
    define( 'MAINWP_ROCKET_GENERAL_SETTINGS'       , 'mainwp_rocket_general_settings' );

class MainWP_Rocket_Extension {
	public $plugin_slug;
	public $wprocket_sites = null;
  public $version = '3.1';

	const REQUIRES_MAINWP_VERSION = '2.0.22';
	const REQUIRES_MAINWP_CHILD_VERSION = '2.0.22';

	public function __construct() {

		$this->plugin_slug = plugin_basename( __FILE__ );

		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'init', array( &$this, 'init' ) );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'mainwp-getsubpages-sites', array( &$this, 'managesites_subpage' ), 10, 1 );
		add_action( 'admin_notices', array( &$this, 'error_notice' ) );
		MainWP_Rocket_DB::get_instance()->install();
    MainWP_Rocket_Based::get_instance();
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {

		if ( $this->plugin_slug != $plugin_file ) { return $plugin_meta; }

		$slug = basename($plugin_file, ".php");
		$api_data = get_option( $slug. '_APIManAdder');
		if ( !is_array( $api_data ) || !isset( $api_data['activated_key'] ) || $api_data['activated_key'] != 'Activated' || !isset( $api_data['api_key'] ) || empty( $api_data['api_key'] ) ) {
			return $plugin_meta;
		}

		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';
		return $plugin_meta;
	}

	function managesites_subpage( $subPage ) {
		$subPage[] = array(
		'title' 				=> __( 'WP Rocket','mainwp-rocket-extension' ),
		'slug' 					=> 'WPRocket',
		'sitetab' 			=> true,
		'menu_hidden' 	=> true,
		'callback' 			=> array( 'MainWP_Rocket', 'render' ),
		);
		return $subPage;
	}

	public function init() {

	}

	public function admin_init() {
		wp_enqueue_style( 'mainwp-rocket-extension', MAINWP_WP_ROCKET_URL . '/css/mainwp-rocket.css' );
		wp_enqueue_script( 'mainwp-rocket-extension', MAINWP_WP_ROCKET_URL . '/js/mainwp-rocket.js', array( 'jquery' ), $this->version );
		wp_localize_script( 'mainwp-rocket-extension', 'mainwp_rocket_loc', array( 'nonce' => wp_create_nonce( 'mainwp_rocket_nonce' ) ) );

		MainWP_Rocket::get_instance()->admin_init();
		MainWP_Rocket_Plugin::get_instance()->admin_init();
	}

	function error_notice() {
		if ( self::is_mainwp_pages() && version_compare( self::REQUIRES_MAINWP_VERSION, get_option( 'mainwp_plugin_version' ), '>' ) ) {
			echo '<div class="ui red message">' . sprintf( __( 'MainWP Rocket Extension requires MainWP Dashboard plugin version %s to be installed on your dashboard site. Please update MainWP Dashboard plugin!', 'mainwp-rocket-extension' ), self::REQUIRES_MAINWP_VERSION ) . '</div>';
		}
	}

	public static function is_mainwp_pages() {
		$current_screen = get_current_screen();
		if ( $current_screen->parent_base == 'mainwp_tab' ) {
			return true;
		}
		return false;
	}
}

class MainWP_Rocket_Extension_Activator {
	protected $mainwpMainActivated = false;
	protected $childEnabled = false;
	protected $childKey = false;
	protected $childFile;
	protected $plugin_handle = 'mainwp-rocket-extension';
	protected $product_id = 'MainWP Rocket Extension';
	protected $software_version = '4.0.1';

	public function __construct() {

		$this->childFile = __FILE__;
    $this->includes();

    spl_autoload_register( array( $this, 'autoload' ) );
    register_activation_hook( __FILE__, array($this, 'activate') );
    register_deactivation_hook( __FILE__, array($this, 'deactivate') );

		add_filter( 'mainwp-getextensions', array( &$this, 'get_this_extension' ) );
		$this->mainwpMainActivated = apply_filters( 'mainwp-activated-check', false );

		if ( $this->mainwpMainActivated !== false ) {
			$this->activate_this_plugin();
		} else {
			add_action( 'mainwp-activated', array( &$this, 'activate_this_plugin' ) );
		}

		add_action( 'admin_notices', array( &$this, 'mainwp_error_notice' ) );
	}

	function autoload( $class_name ) {
    $allowedLoadingTypes = array( 'class' );
    $class_name = str_replace( '_', '-', strtolower( $class_name ) );
    foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
	    $class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.class.php';
	    if ( file_exists( $class_file ) ) {
        require_once( $class_file );
	    }
    }
  }

  function includes() {
    require_once('includes/functions.php');
  }

	function get_this_extension( $pArray ) {
		$pArray[] = array(
			'plugin' 		 => __FILE__,
			'api' 			 => $this->plugin_handle,
			'mainwp' 		 => true,
			'callback' 	 => array( &$this, 'settings' ),
			'apiManager' => true
		);
		return $pArray;
	}

	function settings() {
		do_action( 'mainwp-pageheader-extensions', __FILE__ );
		MainWP_Rocket::render();
		do_action( 'mainwp-pagefooter-extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp-activated-check', $this->mainwpMainActivated );
		$this->childEnabled = apply_filters( 'mainwp-extension-enabled-check', __FILE__ );
		$this->childKey = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' )&& ! mainwp_current_user_can( 'extension', 'mainwp-rocket-extension' ) ) {
			return;
		}
		new MainWP_Rocket_Extension();
	}

	public function get_child_key() {
		return $this->childKey;
	}

	public function get_child_file() {
		return $this->childFile;
	}

	function mainwp_error_notice() {
		global $current_screen;
		if ( $current_screen->parent_base == 'plugins' && $this->mainwpMainActivated == false ) {
			echo '<div class="error"><p>MainWP Rocket Extension ' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
		}
	}

	public function activate() {
		$options = array(
            'product_id' => $this->product_id,
			'software_version' => $this->software_version,
		);
        do_action( 'mainwp_activate_extention', $this->plugin_handle , $options );
	}

	public function deactivate() {
		do_action( 'mainwp_deactivate_extention', $this->plugin_handle );
	}
}

global $mainWPRocketExtensionActivator;
$mainWPRocketExtensionActivator = new MainWP_Rocket_Extension_Activator();
