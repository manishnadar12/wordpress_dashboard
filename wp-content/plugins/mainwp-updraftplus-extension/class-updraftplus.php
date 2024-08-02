<?php

class MainWP_UpdraftPlus {
		/*
		 * Plugin: UpdraftPlus - Backup/Restore
		 * PluginURI: http://updraftplus.com
		 * Description: Backup and restore: take backups locally, or backup to Amazon S3, Dropbox, Google Drive, Rackspace, (S)FTP, WebDAV & email, on automatic schedules.
		 * Author: UpdraftPlus.Com, DavidAnderson
		 * Version: 1.9.60
		 * Donate link: http://david.dw-perspective.org.uk/donate
		 * License: GPLv3 or later
		 * Text Domain: updraftplus
		 * Domain Path: /languages
		 * Author URI: http://updraftplus.com
		 */

	private $jobdata;
	public $errors = array();
	public $nonce;
	// Choices will be shown in the admin menu in the order used here
	public $backup_methods = array(
		'updraftvault' => 'UpdraftPlus Vault', // new
		'dropbox' => 'Dropbox',
		's3' => 'Amazon S3',
		'cloudfiles' => 'Rackspace Cloud Files',
		'googledrive' => 'Google Drive',
		'onedrive' => 'Microsoft OneDrive',
		'ftp' => 'FTP',
		'azure' => 'Microsoft Azure', // new
		'sftp' => 'SFTP / SCP',
		'googlecloud' => 'Google Cloud', // new
        'backblaze'    => 'Backblaze', // new
		'webdav' => 'WebDAV',
		's3generic' => 'S3-Compatible (Generic)',
		'openstack' => 'OpenStack (Swift)',
		'dreamobjects' => 'DreamObjects',
		'email' => 'Email'
	);

	public function __construct() {
		global $mainwp_updraft_globals;

		add_action('plugins_loaded', array($this, 'load_translations'));

		if ( empty( $mainwp_updraft_globals ) ) {
			$site_id = MainWP_Updraftplus_Backups::get_site_id_managesites_updraftplus();
			$mainwp_updraft_globals['site_id'] = $site_id;
			$all_settings = array();
			if ( MainWP_Updraftplus_Backups::is_managesites_updraftplus() ) {
				if ( $site_id ) {
					$updraft_site = MainWP_Updraftplus_BackupsDB::get_instance()->get_setting_by( 'site_id', $site_id );
					if ( $updraft_site ) {
						$all_settings = unserialize( base64_decode( $updraft_site->settings ) );
					} else {
						$all_settings = get_site_option( 'mainwp_updraftplus_generalSettings' );
						if (is_array($all_settings)) {
							$update = array(
								'site_id' => $site_id,
								'settings' => base64_encode( serialize( $all_settings ) )
							);
							MainWP_Updraftplus_BackupsDB::get_instance()->update_setting( $update );
						}
					}
				}
			} else {
					$all_settings = get_site_option( 'mainwp_updraftplus_generalSettings' );
			}

            $mainwp_updraft_globals['all_saved_settings'] = $all_settings;
            $mainwp_updraft_globals['site_id'] = $site_id;
		}

			# Create admin page
			add_action( 'init', array( $this, 'handle_url_actions' ) );
	}

	public function load_translations() {
		// Tell WordPress where to find the translations
		load_plugin_textdomain('mainwp-updraftplus-extension', false, basename(dirname(__FILE__)).'/languages/');
	}

	public function get_backup_history( $timestamp = false ) {
			$backup_history = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_backup_history' );
			// In fact, it looks like the line below actually *introduces* a race condition
			//by doing a raw DB query to get the most up-to-date data from this option we slightly narrow the window for the multiple-cron race condition
		//      global $wpdb;
		//      $backup_history = @unserialize($wpdb->get_var($wpdb->prepare("SELECT option_value from $wpdb->options WHERE option_name='updraft_backup_history'")));
		if ( is_array( $backup_history ) ) {
				krsort( $backup_history ); //reverse sort so earliest backup is last on the array. Then we can array_pop.
		} else {
				$backup_history = array();
		}
		if ( ! $timestamp ) {
				return $backup_history; }
			return (isset( $backup_history[ $timestamp ] )) ? $backup_history[ $timestamp ] : array();
	}

	public function strip_dirslash( $string ) {
			return preg_replace( '#/+(,|$)#', '$1', $string );
	}

		// This important function returns a list of file entities that can potentially be backed up (subject to users settings), and optionally further meta-data about them
	public function get_backupable_file_entities( $include_others = true, $full_info = false ) {

			$wp_upload_dir = wp_upload_dir();

		if ( $full_info ) {
				$arr = array(
					'plugins' => array( 'path' => WP_PLUGIN_DIR, 'description' => __( 'Plugins', 'mainwp-updraftplus-extension' ) ),
					'themes' => array( 'path' => WP_CONTENT_DIR . '/themes', 'description' => __( 'Themes', 'mainwp-updraftplus-extension' ) ),
					'uploads' => array( 'path' => $wp_upload_dir['basedir'], 'description' => __( 'Uploads', 'mainwp-updraftplus-extension' ) ),
				);
		} else {
				$arr = array(
					'plugins' => WP_PLUGIN_DIR,
					'themes' => WP_CONTENT_DIR . '/themes',
					'uploads' => $wp_upload_dir['basedir'],
				);
		}

			$arr = apply_filters( 'mainwp_updraft_backupable_file_entities', $arr, $full_info );

			// We then add 'others' on to the end
		if ( $include_others ) {
			if ( $full_info ) {
					$arr['others'] = array( 'path' => WP_CONTENT_DIR, 'description' => __( 'Others', 'mainwp-updraftplus-extension' ) );
			} else {
						$arr['others'] = WP_CONTENT_DIR;
			}
		}

			// Entries that should be added after 'others'
			$arr = apply_filters( 'mainwp_updraft_backupable_file_entities_final', $arr, $full_info );

			return $arr;
	}

	public function jobdata_get( $key, $default = null ) {
		if ( ! is_array( $this->jobdata ) ) {
				$this->jobdata = get_site_option( 'updraft_jobdata_' . $this->nonce, array() );
			if ( ! is_array( $this->jobdata ) ) {
					return $default; }
		}
			return (isset( $this->jobdata[ $key ] )) ? $this->jobdata[ $key ] : $default;
	}

	public function jobdata_set( $key, $value ) {
		//      if (!is_array($this->jobdata)) {
		//          $this->jobdata = get_site_option("mainwp_updraft_jobdata_".$this->nonce);
		//          if (!is_array($this->jobdata)) $this->jobdata = array();
		//      }
		//      $this->jobdata[$key] = $value;
		//      update_site_option("updraft_jobdata_".$this->nonce, $this->jobdata);
	}

	public function get_job_option( $opt ) {
			// These are meant to be read-only
		//      if (empty($this->jobdata['option_cache']) || !is_array($this->jobdata['option_cache'])) {
		//          if (!is_array($this->jobdata))
		//                            $this->jobdata = get_site_option("updraft_jobdata_".$this->nonce, array());
		//
		//          $this->jobdata['option_cache'] = array();
		//      }
		//      return (isset($this->jobdata['option_cache'][$opt])) ? $this->jobdata['option_cache'][$opt] : MainWP_Updraft_Plus_Options::get_updraft_option($opt);
	}

	public function get_table_prefix( $allow_override = false ) {
			global $wpdb;
		if ( is_multisite() && ! defined( 'MULTISITE' ) ) {
				# In this case (which should only be possible on installs upgraded from pre WP 3.0 WPMU), $wpdb->get_blog_prefix() cannot be made to return the right thing. $wpdb->base_prefix is not explicitly marked as public, so we prefer to use get_blog_prefix if we can, for future compatibility.
				$prefix = $wpdb->base_prefix;
		} else {
				$prefix = $wpdb->get_blog_prefix( 0 );
		}
			return ($allow_override) ? apply_filters( 'mainwp_updraftplus_get_table_prefix', $prefix ) : $prefix;
	}

	public function remove_empties( $list ) {
		if ( ! is_array( $list ) ) {
				return $list; }
		foreach ( $list as $ind => $entry ) {
			if ( empty( $entry ) ) {
					unset( $list[ $ind ] ); }
		}
			return $list;
	}

	public function schedule_backup( $interval ) {
			return $interval;
	}

	public function schedule_backup_database( $interval ) {
			return $interval;
	}

	public function retain_range( $input ) {
			$input = (int) $input;
			return ($input > 0 && $input < 3650) ? $input : 1;
	}

	public function replace_http_with_webdav( $input ) {
		if ( ! empty( $input['url'] ) && 'http' == substr( $input['url'], 0, 4 ) ) {
				$input['url'] = 'webdav' . substr( $input['url'], 4 ); }
			return $input;
	}

	public function just_one_email( $input, $required = false ) {
			$x = $this->just_one( $input, 'saveemails', (empty( $input ) && false === $required) ? '' : get_bloginfo( 'admin_email' ) );
		if ( is_array( $x ) ) {
			foreach ( $x as $ind => $val ) {
				if ( empty( $val ) ) {
						unset( $x[ $ind ] ); }
			}
			if ( empty( $x ) ) {
					$x = ''; }
		}
			return $x;
	}

	public function just_one( $input, $filter = 'savestorage', $rinput = false ) {
			$oinput = $input;
		if ( false === $rinput ) {
				$rinput = (is_array( $input )) ? array_pop( $input ) : $input; }
		if ( is_string( $rinput ) && false !== strpos( $rinput, ',' ) ) {
				$rinput = substr( $rinput, 0, strpos( $rinput, ',' ) ); }
			return apply_filters( 'mainwp_updraftplus_' . $filter, $rinput, $oinput );
	}

		// Acts as a WordPress options filter
	public function googledrive_checkchange( $google ) {
			$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_googledrive' );
		if ( ! is_array( $google ) ) {
				return $opts; }
			$old_client_id = (empty( $opts['clientid'] )) ? '' : $opts['clientid'];
		if ( ! empty( $opts['token'] ) && $old_client_id != $google['clientid'] ) {
				require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/googledrive.php' );
				add_action( 'http_request_args', array( $this, 'modify_http_options' ) );
				MainWP_Updraft_Plus_BackupModule_googledrive::gdrive_auth_revoke( false );
				remove_action( 'http_request_args', array( $this, 'modify_http_options' ) );
				$google['token'] = '';
				unset( $opts['ownername'] );
		}
		foreach ( $google as $key => $value ) {
				// Trim spaces - I got support requests from users who didn't spot the spaces they introduced when copy/pasting
				$opts[ $key ] = ('clientid' == $key || 'secret' == $key) ? trim( $value ) : $value;
		}
		if ( isset( $opts['folder'] ) ) {
				$opts['folder'] = apply_filters( 'mainwp_updraftplus_options_googledrive_foldername', 'UpdraftPlus', $opts['folder'] );
				unset( $opts['parentid'] );
		}
			return $opts;
	}

	public function ftp_sanitise( $ftp ) {
		if ( is_array( $ftp ) && ! empty( $ftp['host'] ) && preg_match( '#ftp(es|s)?://(.*)#i', $ftp['host'], $matches ) ) {
				$ftp['host'] = untrailingslashit( $matches[2] );
		}
			return $ftp;
	}

	public function log( $line, $level = 'notice', $uniq_id = false, $skip_dblog = false ) {

	}

	public function s3_sanitise( $s3 ) {
		if ( is_array( $s3 ) && ! empty( $s3['path'] ) && '/' == substr( $s3['path'], 0, 1 ) ) {
				$s3['path'] = substr( $s3['path'], 1 );
		}
			return $s3;
	}

		// Acts as a WordPress options filter
	public function bitcasa_checkchange( $bitcasa ) {
			$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_bitcasa' );
		if ( ! is_array( $opts ) ) {
				$opts = array(); }
		if ( ! is_array( $bitcasa ) ) {
				return $opts; }
			$old_client_id = (empty( $opts['clientid'] )) ? '' : $opts['clientid'];
		if ( ! empty( $opts['token'] ) && $old_client_id != $bitcasa['clientid'] ) {
				unset( $opts['token'] );
				unset( $opts['ownername'] );
		}
		foreach ( $bitcasa as $key => $value ) {
				$opts[ $key ] = $value;
		}
			return $opts;
	}


		// Acts as a WordPress options filter
	public function dropbox_checkchange( $dropbox ) {
			$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_dropbox' );
		if ( ! is_array( $opts ) ) {
				$opts = array(); }
		if ( ! is_array( $dropbox ) ) {
				return $opts; }
		foreach ( $dropbox as $key => $value ) {
				$opts[ $key ] = $value;
		}
		if ( preg_match( '#^https?://(www.)dropbox\.com/home/Apps/UpdraftPlus([^/]*)/(.*)$#i', $opts['folder'], $matches ) ) {
				$opts['folder'] = $matches[3]; }
			return $opts;
	}

		// Handle actions passed on to method plugins; e.g. Google OAuth 2.0 - ?action=updraftmethod-googledrive-auth&page=updraftplus
		// Nov 2013: Google's new cloud console, for reasons as yet unknown, only allows you to enter a redirect_uri with a single URL parameter... thus, we put page second, and re-add it if necessary. Apr 2014: Bitcasa already do this, so perhaps it is part of the OAuth2 standard or best practice somewhere.
		// Also handle action=downloadlog
	public function handle_url_actions() {

	}

	public function really_is_writable( $dir ) {

			return true;
	}

	public function detect_safe_mode() {
			return MainWP_Updraft_Plus_Options::get_updraft_option( 'mainwp_updraft_detect_safe_mode' );
	}

		// Returns without any trailing slash
	public function backups_dir_location() {
			global $mainwp_updraft_globals;
			$dir = (isset( $mainwp_updraft_globals['all_saved_settings'] ) && isset( $mainwp_updraft_globals['all_saved_settings']['updraft_dir'] )) ? $mainwp_updraft_globals['all_saved_settings']['updraft_dir'] : 'updraft';
			return $dir;
	}

    // save data for reload and syncing process
	public function save_reload_data( $information, $siteid ) {
			$update = array(
				'updraft_backup_disabled' => isset( $information['updraft_backup_disabled'] ) ? $information['updraft_backup_disabled'] : 0,
				'nextsched_files_gmt' => isset( $information['nextsched_files_gmt'] ) ? $information['nextsched_files_gmt'] : 0,
				'nextsched_files_timezone' => isset( $information['nextsched_files_timezone'] ) ? $information['nextsched_files_timezone'] : '',
				'nextsched_database_gmt' => isset( $information['nextsched_database_gmt'] ) ? $information['nextsched_database_gmt'] : 0,
				'nextsched_database_timezone' => isset( $information['nextsched_database_timezone'] ) ? $information['nextsched_database_timezone'] : '',
				'nextsched_current_timegmt' => isset( $information['nextsched_current_timegmt'] ) ? $information['nextsched_current_timegmt'] : 0,
				'nextsched_current_timezone' => isset( $information['nextsched_current_timezone'] ) ? $information['nextsched_current_timezone'] : '',
				'mainwp_updraft_backup_history_count' => $information['updraft_count_backups'],
				'updraft_lastbackup_html' => $information['updraft_lastbackup_html']
			);

            if (isset($information['updraft_historystatus'])) {
                $update['mainwp_updraft_backup_history_html'] = $information['updraft_historystatus'];
            }
            
			MainWP_Updraftplus_BackupsDB::get_instance()->update_setting_fields_by( 'site_id', $siteid, $update );
	}
}
