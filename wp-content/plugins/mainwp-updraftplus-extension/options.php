<?php

if ( ! defined( 'ABSPATH' ) ) {
		die( 'No direct access allowed' ); }

class MainWP_Updraft_Plus_Options {
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


	public static function admin_page_url() {
			return admin_url( 'options-general.php' );
	}

	public static function googledrive_page_url() {
			return admin_url( 'admin.php?page=Extensions-Mainwp-Updraftplus-Extension&action=updraftmethod-googledrive-auth' );
	}

	public static function admin_page() {
		return 'options-general.php';
	}

	public static function get_updraft_option( $option, $default = null ) {
			global $mainwp_updraft_globals;
			$value = (isset( $mainwp_updraft_globals['all_saved_settings'] ) && isset( $mainwp_updraft_globals['all_saved_settings'][ $option ] )) ? $mainwp_updraft_globals['all_saved_settings'][ $option ] : $default;
			return $value;
	}

	public static function update_updraft_option( $option, $value, $site_id = null ) {
			global $mainwp_updraft_globals;
			if ( null === $site_id ) {
				$site_id = isset( $mainwp_updraft_globals['site_id'] ) ? $mainwp_updraft_globals['site_id'] : 0; }

			MainWP_Updraftplus_Backups::update_updraftplus_settings( array( $option => $value ), $site_id );
			//update_option($option, $value);
	}

	public static function delete_updraft_option( $option ) {
			global $mainwp_updraft_globals;
			$site_id = isset( $mainwp_updraft_globals['site_id'] ) ? $mainwp_updraft_globals['site_id'] : 0;
			MainWP_Updraftplus_Backups::delete_updraftplus_settings( $option, $site_id );
			//delete_option($option);
	}

	public static function hourminute( $pot ) {
		if ( preg_match( '/^([0-2]?[0-9]):([0-5][0-9])$/', $pot, $matches ) ) {
				return sprintf( '%02d:%s', $matches[1], $matches[2] ); }
		if ( '' == $pot ) {
				return date( 'H:i', time() + 300 ); }
			return '00:00';
	}

	public static function weekday( $pot ) {
			$pot = absint( $pot );
			return ($pot > 6) ? 0 : $pot;
	}
}
