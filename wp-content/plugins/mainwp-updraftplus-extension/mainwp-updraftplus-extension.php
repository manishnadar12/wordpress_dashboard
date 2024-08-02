<?php
/*
  Plugin Name: MainWP UpdraftPlus Extension
  Plugin URI: https://mainwp.com
  Description: MainWP UpdraftPlus Extension combines the power of your MainWP Dashboard with the popular WordPress UpdraftPlus Plugin. It allows you to quickly back up your child sites.
  Version: 4.0.2
  Author: MainWP
  Author URI: https://mainwp.com
  Documentation URI: https://mainwp.com/help/category/mainwp-extensions/updraftplus-backups/
 */

if ( ! defined( 'MAINWP_UDP_PLUGIN_FILE' ) ) {
	define( 'MAINWP_UDP_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct access allowed' );
}

define( 'MAINWP_UPDRAFT_PLUS_DIR', dirname( __FILE__ ) );
define( 'MAINWP_UPDRAFT_PLUS_URL', plugins_url( '', __FILE__ ) );

define( 'MAINWP_UPDRAFT_DEFAULT_OTHERS_EXCLUDE', 'upgrade,cache,updraft,backup*,*backups,mysql.sql' );
define( 'MAINWP_UPDRAFT_DEFAULT_UPLOADS_EXCLUDE', 'backup*,*backups,backwpup*,wp-clone' );

if ( ! defined( 'MAINWP_UPDRAFTPLUS_SPLIT_MIN' ) ) {
	define( 'MAINWP_UPDRAFTPLUS_SPLIT_MIN', 25 );
}

if ( ! defined( 'MAINWP_UPDRAFTPLUS_PER_PAGE' ) ) {
	define( 'MAINWP_UPDRAFTPLUS_PER_PAGE', 25 );
}

class MainWP_Updraftplus_Backups_Extension {

	public $plugin_slug;
	public static $isPremium = null;
	public $updraft_sites_data = null;
	private $dev_version = 2.0;

    private static $instance = null;

    static function get_instance() {
		if ( null === MainWP_Updraftplus_Backups_Extension::$instance ) {
			MainWP_Updraftplus_Backups_Extension::$instance = new MainWP_Updraftplus_Backups_Extension();
		}
		return MainWP_Updraftplus_Backups_Extension::$instance;
	}

	public function __construct() {
		$this->plugin_slug = plugin_basename( __FILE__ );

		add_filter( 'plugin_row_meta', array( &$this, 'plugin_row_meta' ), 10, 2 );
		add_action( 'admin_init', array( &$this, 'admin_init' ) );
		add_filter( 'mainwp-getsubpages-sites', array( &$this, 'managesites_subpage' ), 10, 1 );

        $add_managesites_column = false;
		$primary_backup = get_option( 'mainwp_primaryBackup', null );
		if ( 'updraftplus' == $primary_backup ) {
            add_filter( 'mainwp-managesites-getbackuplink', array( $this, 'managesites_backup_link' ), 10, 2 );
            add_filter( 'mainwp-getcustompage-backups', array( $this, 'add_page_backups' ), 10, 1 );
            add_filter( 'mainwp-getprimarybackup-activated', array( $this, 'primary_backups_activated' ), 10, 1 );
            add_action( 'mainwp-sitestable-prepared-items', array( &$this, 'prepared_items' ), 10 , 2 );
            $add_managesites_column = true;
		} else if (empty($primary_backup)) {
            $add_managesites_column = true;
        }

        if ($add_managesites_column) {
            add_filter( 'mainwp_managesites_column_url', array( &$this, 'managesites_column_url' ), 10, 2 );
        }

		add_filter( 'mainwp-sync-others-data', array( $this, 'sync_others_data' ), 10, 2 );
		add_action( 'mainwp-site-synced', array( $this, 'synced_site' ), 10, 2 );

		add_filter( 'mainwp-getprimarybackup-methods', array( $this, 'primary_backups_method' ), 10, 1 );
		add_filter( 'mainwp-sync-extensions-options', array( &$this, 'mainwp_sync_extensions_options' ), 10, 1 );
		add_action( 'mainwp_applypluginsettings_mainwp-updraftplus-extension', array( MainWP_Updraftplus_Backups::get_instance(), 'mainwp_apply_plugin_settings' ) );

		$this->options_init();

		MainWP_Updraftplus_Backups::get_instance()->init_updraft();

//		if ( ('updraftplus' == $primary_backup) && isset( $_GET['page'] ) && 'managesites' == $_GET['page'] && !isset( $_GET['id']) && !isset( $_GET['do']) && !isset( $_GET['dashboard'])) {
//			// load data to reduce db query
//			if ( $this->updraft_sites_data === null ) {
//				$this->updraft_sites_data = MainWP_Updraftplus_Backups_Plugin::get_instance()->get_updraft_info();
//			}
//		}
	}

    public function prepared_items( $websites = array(), $site_ids = array()) {

        if ( is_array( $site_ids ) && $site_ids ) {
            if ( $this->updraft_sites_data === null ) {
                $this->updraft_sites_data = MainWP_Updraftplus_Backups_Plugin::get_instance()->get_updraft_info( $site_ids );
			}
        }

    }

    public function options_init() {
		if ( isset( $_POST['submit'] ) ) {
			if ( isset( $_POST['mainwp_premium_updraft_site_id'] ) ) {
				$is_premium = (isset( $_POST['mwp_updraft_is_premium'] ) && 'yes' == $_POST['mwp_updraft_is_premium']) ? 1 : 0;
				if ( empty( $_POST['mainwp_premium_updraft_site_id'] ) ) {
					update_option( 'mainwp_updraft_general_is_premium', $is_premium );
				} else {
					$update = array(
						'site_id' => $_POST['mainwp_premium_updraft_site_id'],
						'is_premium' => $is_premium,
					);
					MainWP_Updraftplus_BackupsDB::get_instance()->update_setting( $update );
				}
				self::$isPremium = $is_premium;
			}

			if ( isset( $_POST['mainwp_updraft_addons_site_id'] ) ) {
				if ( isset( $_POST['mainwp_updraftplus-addons_options'] ) ) {
					$opts = $_POST['mainwp_updraftplus-addons_options'];
					$email = trim( $opts['email'] );
					$passwd = trim( $opts['password'] );
					if ( empty( $email ) || empty( $passwd ) ) {
						$email = $passwd = '';
					}
					$value = array(
						'email' => $email,
						'password' => $passwd,
					);
					$site_id = ! empty( $_POST['mainwp_updraft_addons_site_id'] ) ? $_POST['mainwp_updraft_addons_site_id'] : 0;
					MainWP_Updraftplus_Backups::update_updraftplus_settings( array( 'addons_options' => $value ), $site_id );

					if ( $site_id ) {
						update_option( 'mainwp_updraft_individual_addons_connect', 1 );
					} else {
						update_option( 'mainwp_updraft_general_addons_connect', 1 );
					}
				}
			}
		}
	}


	static function is_updraft_premium() {
		if ( null === self::$isPremium ) {
			if ( MainWP_Updraftplus_Backups::is_managesites_updraftplus() ) {
				$sid = MainWP_Updraftplus_Backups::get_site_id_managesites_updraftplus();
				$data = MainWP_Updraftplus_BackupsDB::get_instance()->get_setting_by( 'site_id', $sid );
				if ( $data ) {
					self::$isPremium = $data->is_premium;
				}
			} else {
				self::$isPremium = get_option( 'mainwp_updraft_general_is_premium', 0 );
			}
		}
		return self::$isPremium;
	}

	public function sync_others_data( $data, $pWebsite = null ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$primary_backup = get_option( 'mainwp_primaryBackup', null );
		if ($primary_backup == 'updraftplus') {
			$data['syncUpdraftData'] = $this->dev_version;
		}
		$data['sync_Updraftvault_quota_text'] = 1;
		return $data;
	}

	public function synced_site( $pWebsite, $information = array() ) {
		if ( is_array( $information ) ) {
			if ( isset( $information['syncUpdraftData'] ) ) {
				$data = $information['syncUpdraftData'];
				if ( is_array( $data ) )  {
					if ( isset( $data['nextsched_current_timegmt'] ) ) {
						global $mainwp_updraftplus;
						$mainwp_updraftplus->save_reload_data( $data, $pWebsite->id );
                        // to improved performance
                        $update = array(
                            'site_id' => $pWebsite->id,
                            'lastbackup_gmttime' => $data['updraft_lastbackup_gmttime'],
                        );
                        MainWP_Updraftplus_BackupsDB::get_instance()->update_setting( $update );
					}
					if ( isset( $data['updraftvault_quota_text'] ) ) {
						$html = preg_replace( '/ - <a href="#" id="updraftvault_recountquota"\>[^<]+<\/a>/', '', $data['updraftvault_quota_text'] );
						MainWP_Updraft_Plus_Options::update_updraft_option( 'updraftvault_quota_text', $html, $pWebsite->id);
					}
				}
				unset( $information['syncUpdraftData'] );
			}

			if ( isset($information['sync_Updraftvault_quota_text']) ) {
				$html = preg_replace( '/ - <a href="#" id="updraftvault_recountquota"\>[^<]+<\/a>/', '', $information['sync_Updraftvault_quota_text'] );
				MainWP_Updraft_Plus_Options::update_updraft_option( 'updraftvault_quota_text', $html, $pWebsite->id);
				unset( $information['sync_Updraftvault_quota_text'] );
			}
		}
	}

	public function primary_backups_activated( $input ) {
		return 'updraftplus';
	}

	public function primary_backups_method( $methods ) {
		$methods[] = array( 'value' => 'updraftplus', 'title' => 'MainWP UpdraftPlus Extension' );
		return $methods;
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {
		if ( $this->plugin_slug != $plugin_file ) {
			return $plugin_meta;
		}

		$slug = basename($plugin_file, ".php");
		$api_data = get_option( $slug. '_APIManAdder');
		if (!is_array($api_data) || !isset($api_data['activated_key']) || $api_data['activated_key'] != 'Activated' || !isset($api_data['api_key']) || empty($api_data['api_key']) ) {
			return $plugin_meta;
		}


		$plugin_meta[] = '<a href="?do=checkUpgrade" title="Check for updates.">Check for updates now</a>';
		return $plugin_meta;
	}

	function managesites_subpage( $subPage ) {
		$subPage[] = array(
			'title' => __( 'UpdraftPlus Backups', 'mainwp' ),
			'slug' => 'Updraftplus',
			'sitetab' => true,
			'menu_hidden' => true,
			'callback' => array( 'MainWP_Updraftplus_Backups', 'render' ),
		);
		return $subPage;
	}

	public function managesites_column_url( $actions, $websiteid ) {
            $actions['Updraftplus'] = sprintf( '<a href="admin.php?page=ManageSitesUpdraftplus&id=%1$s">' . __( 'Updraftplus Backup/Restore', 'mainwp' ) . '</a>', $websiteid );
            return $actions;
	}

	public function managesites_backup_link( $input, $site_id ) {

		if ( $site_id ) {
			$lastbackup = 0;
			if ( is_array( $this->updraft_sites_data ) && isset( $this->updraft_sites_data[ $site_id ] ) && isset( $this->updraft_sites_data[ $site_id ]['lastbackup_gmttime'] ) ) {
				$lastbackup = $this->updraft_sites_data[ $site_id ]['lastbackup_gmttime'];
			}
			$output = '';
			if ( ! empty( $lastbackup ) ) {
				$output = MainWP_Updraftplus_Backups_Utility::format_timestamp( MainWP_Updraftplus_Backups_Utility::get_timestamp( $lastbackup ) ) . '<br />';
			} else {
				$output = '<span class="mainwp-red">Never</span><br/>';
			}

			if ( mainwp_current_user_can( 'dashboard', 'execute_backups' ) ) {
				$output .= sprintf( '<a href="admin.php?page=ManageSitesUpdraftplus&id=%s">' . __( 'Backup Now', 'mainwp' ) . '</a>', $site_id );
			}
			return $output;
		} else {
			return $input;
		}
	}

	public function add_page_backups( $input = null ) {
		return array( 'title' => __( 'Existing Backups', 'mainwp' ), 'slug' => 'Updraftplus', 'managesites_slug' => 'Updraftplus', 'callback' => array( $this, 'render_redicting' ) );
	}

	public function render_redicting() {
		?>
		<div id="mainwp_background-box">
			<div style="font-size: 30px; text-align: center; margin-top: 5em;"><?php _e( 'You will be redirected to the page immediately.', 'mainwp' ); ?></div>
			<script type="text/javascript">
				window.location = "admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=backups";
			</script>
		</div>
		<?php
	}

    public function admin_init() {
		wp_enqueue_style( 'mainwp-updraftplus-extension', MAINWP_UPDRAFT_PLUS_URL . '/css/mainwp-updraftplus-backups.css' );
		wp_enqueue_script( 'mainwp-updraftplus-extension', MAINWP_UPDRAFT_PLUS_URL . '/js/mainwp-updraftplus-backups.js', array(), $this->dev_version );

		MainWP_Updraftplus_Backups::get_instance()->admin_init();
		MainWP_Updraftplus_Backups_Plugin::get_instance()->admin_init();
		MainWP_Updraftplus_Backups_Next_Scheduled::get_instance()->admin_init();

		global $mainwp_updraftplus_admin;
		if ( ! is_a( $mainwp_updraftplus_admin, 'MainWP_Updraft_Plus_Admin' ) ) {
			require_once MAINWP_UPDRAFT_PLUS_DIR . '/admin.php';
			$mainwp_updraftplus_admin = new MainWP_Updraft_Plus_Admin();
		}
	}

	function mainwp_sync_extensions_options($values = array()) {
		$values['mainwp-updraftplus-extension'] = array(
			'plugin_name' => 'UpdraftPlus - Backup/Restore',
			'plugin_slug' => 'updraftplus/updraftplus.php'
		);
		return $values;
	}
}

class Mainwp_Updraftplus_Backups_Extension_Activator {

	protected $mainwpMainActivated = false;
	protected $childEnabled = false;
	protected $childKey = false;
	protected $childFile;
	protected $plugin_handle = 'mainwp-updraftplus-extension';
	protected $product_id = 'MainWP UpdraftPlus Extension';
	protected $software_version = '4.0.2';

	public function __construct() {

		$this->childFile = __FILE__;

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
		MainWP_Updraftplus_BackupsDB::get_instance()->install();
	}

    function autoload( $class_name ) {
        $allowedLoadingTypes = array( 'class' );
        $class_name = str_replace( '_', '-', strtolower( $class_name ) );
        foreach ( $allowedLoadingTypes as $allowedLoadingType ) {
            $class_file = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . $allowedLoadingType . DIRECTORY_SEPARATOR . $class_name . '.' . $allowedLoadingType . '.php';
            if ( file_exists( $class_file ) ) {
                require_once( $class_file );
            }
        }
    }

	function get_this_extension( $pArray ) {
		$pArray[] = array( 'plugin' => __FILE__, 'api' => $this->plugin_handle, 'mainwp' => true, 'callback' => array( &$this, 'settings' ), 'apiManager' => true );
		return $pArray;
	}

	function error_str( $error = 'conflict' ) {
		if ( 'conflict' == $error ) {
			return 'MainWP UpdraftPlus Extension conflict with installed plugin <strong>' . self::$conflict_name . '</strong>. Please deactivate plugin ' . self::$conflict_name . ' first.';
		}
	}

	function settings() {
		do_action( 'mainwp-pageheader-extensions', __FILE__ );

		if ( $this->childEnabled ) {
			MainWP_Updraftplus_Backups::render();
		} else {
			?><div class="mainwp_info-box-yellow"><strong><?php _e( 'The Extension has to be enabled to change the settings.' ); ?></strong></div><?php
		}

		do_action( 'mainwp-pagefooter-extensions', __FILE__ );
	}

	function activate_this_plugin() {
		$this->mainwpMainActivated = apply_filters( 'mainwp-activated-check', $this->mainwpMainActivated );
		$this->childEnabled = apply_filters( 'mainwp-extension-enabled-check', __FILE__ );
		$this->childKey = $this->childEnabled['key'];
		if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'extension', 'mainwp-updraftplus-extension' ) ) {
			return;
		}
		MainWP_Updraftplus_Backups_Extension::get_instance();
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
			echo '<div class="error"><p>MainWP UpdraftPlus Extension ' . __( 'requires <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> to be activated in order to work. Please install and activate <a href="http://mainwp.com/" target="_blank">MainWP Dashboard Plugin</a> first.' ) . '</p></div>';
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

global $mainWPUpdraftPlusBackupsExtensionActivator;
$mainWPUpdraftPlusBackupsExtensionActivator = new Mainwp_Updraftplus_Backups_Extension_Activator();
