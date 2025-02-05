<?php
/*
  UpdraftPlus Addon: moredatabase:Multiple database backup options
  Description: Provides the ability to encrypt database backups, and to back up external databases
  Version: 1.1
  Shop: /shop/moredatabase/
  Latest Change: 1.9.32
 */

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

$mainwp_updraft_plus_addon_moredatabase = new MainWP_Updraft_Plus_Addon_MoreDatabase;

class MainWP_Updraft_Plus_Addon_MoreDatabase {

	public function __construct() {
			add_filter( 'mainwp_updraft_backup_databases', array( $this, 'backup_databases' ) );
			add_filter( 'mainwp_updraft_database_encryption_config', array( $this, 'database_encryption_config' ) );
			add_filter( 'mainwp_updraft_encrypt_file', array( $this, 'encrypt_file' ), 10, 5 );
			add_filter( 'mainwp_updraft_database_moredbs_config', array( $this, 'database_moredbs_config' ) );
			# This runs earlier than default, to allow users who were over-riding already with a filter to continue doing so
			add_filter( 'mainwp_updraftplus_get_table_prefix', array( $this, 'get_table_prefix' ), 9 );
			add_action( 'mainwp_updraft_extradb_testconnection', array( $this, 'extradb_testconnection' ) );
			add_action( 'mainwp_updraftplus_restore_form_db', array( $this, 'restore_form_db' ), 9 );
	}

	public function restore_form_db() {

			echo '<div class="updraft_restore_crypteddb" style="display:none;">' . __( 'Database decryption phrase', 'mainwp-updraftplus-extension' ) . ': ';

			$updraft_encryptionphrase = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_encryptionphrase' );

			echo '<input type="' . apply_filters( 'mainwp_updraftplus_admin_secret_field_type', 'text' ) . '" name="mwp_updraft_encryptionphrase" id="updraft_encryptionphrase" value="' . esc_attr( $updraft_encryptionphrase ) . '" size="54" /></div><br>';
	}

	public function get_table_prefix( $prefix ) {
		if ( MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_backupdb_nonwp' ) ) {
				global $mainwp_updraftplus;
				$mainwp_updraftplus->log( 'All tables found will be backed up (indicated by backupdb_nonwp option)' );
				return '';
		}
			return $prefix;
	}

	public function extradb_testconnection() {

		if ( empty( $_POST['user'] ) ) {
				$ret['m'] = die( json_encode( array( 'r' => $_POST['row'], 'm' => '<p>' . sprintf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ) . '</p>', __( 'user', 'mainwp-updraftplus-extension' ) ) ) ) ); }

		if ( empty( $_POST['host'] ) ) {
				$ret['m'] = die( json_encode( array( 'r' => $_POST['row'], 'm' => '<p>' . sprintf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ) . '</p>', __( 'host', 'mainwp-updraftplus-extension' ) ) ) ) ); }

		if ( empty( $_POST['name'] ) ) {
				$ret['m'] = die( json_encode( array( 'r' => $_POST['row'], 'm' => '<p>' . sprintf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ) . '</p>', __( 'database name', 'mainwp-updraftplus-extension' ) ) ) ) ); }

			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_POST['updraftRequestSiteID'];

		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.', 'r' => $_POST['row'] ) ) ); }

			$post_data = array(
		'mwp_action' => 'extradbtestconnection',
				'user_db' => $_POST['user'],
				'host' => $_POST['host'],
				'name' => $_POST['name'],
				'row' => $_POST['row'],
				'pass' => isset( $_POST['pass'] ) ? $_POST['pass'] : '',
				'prefix' => isset( $_POST['prefix'] ) ? $_POST['prefix'] : '',
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );

		if ( is_array( $information ) && ! isset( $information['r'] ) ) {
				$information['r'] = $_POST['row'];
		}

			die( json_encode( $information ) );
	}

	public function database_moredbs_config( $ret ) {
			global $mainwp_updraftplus;
			$ret = '';
			$tp = ''; //$mainwp_updraftplus->get_table_prefix(false);
			$updraft_backupdb_nonwp = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_backupdb_nonwp' );

			$ret .= '<span class="ui toggle checkbox"><input type="checkbox"' . (($updraft_backupdb_nonwp) ? ' checked="checked"' : '') . ' id="updraft_backupdb_nonwp" name="mwp_updraft_backupdb_nonwp" value="1"><label for="updraft_backupdb_nonwp" title="' . sprintf( __( 'This option will cause tables stored in the MySQL database which do not belong to WordPress (identified by their lacking the configured WordPress prefix, %s) to also be backed up.', 'mainwp-updraftplus-extension' ), $tp ) . '">' . __( 'Backup non-WordPress tables contained in the same database as WordPress', 'mainwp-updraftplus-extension' ) . '</label></span><br>';
			//$ret .= '<p><em>' . __( 'If your database includes extra tables that are not part of this WordPress site (you will know if this is the case), then activate this option to also back them up.', 'mainwp-updraftplus-extension' ) . '</em></p>';

			$ret .= '<div id="mwp_updraft_backupextradbs" style="clear:both:float:left;"></div>';

			$ret .= '<div style="clear:both; float:left;"><a href="#" id="mwp_updraft_backupextradb_another">' . __( 'Add an external database to backup...', 'mainwp-updraftplus-extension' ) . '</a></div>';

			add_action( 'admin_footer', array( $this, 'admin_footer' ) );
			return $ret;
	}

	public function admin_footer() {
			?>
			<style type="text/css">
				.updraft_backupextradbs_row {
					border: 1px dotted;
					margin: 4px;
					padding: 4px;
				}
				.updraft_backupextradbs_row h3 {
					margin-top: 0px; padding-top: 0px; margin-bottom: 3px;
					font-size: 90%;
				}
				.updraft_backupextradbs_row .updraft_backupextradbs_testresultarea {
					float: left; clear: both;
					padding-bottom: 4px;
				}
				.updraft_backupextradbs_row .updraft_backupextradbs_row_label {
					float: left; width: 90px;
					padding-top:1px;
				}
				.updraft_backupextradbs_row .updraft_backupextradbs_row_textinput {
					float: left; width: 100px; clear:none; margin-right: 6px;
				}
				.updraft_backupextradbs_row_delete {
					float: right;
					cursor: pointer;
					font-size: 100%;
					padding: 1px 3px;
					margin: 0 6px;
				}
				.updraft_backupextradbs_row_delete:hover {
					cursor: pointer;
				}
			</style>
			<script>
					jQuery(document).ready(function () {
						var updraft_extra_dbs = 0;
						function mainwp_updraft_extradbs_add(host, user, name, pass, prefix) {
							updraft_extra_dbs++;
							var appEl = '<div class="updraft_backupextradbs_row" id="updraft_backupextradbs_row_' + updraft_extra_dbs + '" style="float: left; clear: both">' +
									'<button type="button" title="<?php echo esc_attr( __( 'Remove', 'mainwp-updraftplus-extension' ) ); ?>" class="updraft_backupextradbs_row_delete">X</button>' +
									'<h3><?php echo esc_js( __( 'Backup external database', 'mainwp-updraftplus-extension' ) ); ?></h3>' +
									'<div class="updraft_backupextradbs_testresultarea"></div>' +
									'<div class="updraft_backupextradbs_row_label" style="clear:left;"><?php echo esc_js( __( 'Host', 'mainwp-updraftplus-extension' ) ); ?>:</div><input class="updraft_backupextradbs_row_textinput extradb_host" type="text" name="mwp_updraft_extradbs[' + updraft_extra_dbs + '][host]" value="' + host + '">' +
									'<div class="updraft_backupextradbs_row_label"><?php echo esc_js( __( 'Username', 'mainwp-updraftplus-extension' ) ); ?>:</div><input class="updraft_backupextradbs_row_textinput extradb_user" type="text" name="mwp_updraft_extradbs[' + updraft_extra_dbs + '][user]" value="' + user + '">' +
									'<div class="updraft_backupextradbs_row_label"><?php echo esc_js( __( 'Password', 'mainwp-updraftplus-extension' ) ); ?>:</div><input class="updraft_backupextradbs_row_textinput extradb_pass" type="<?php echo apply_filters( 'mainwp_updraftplus_admin_secret_field_type', 'text' ); ?>" name="mwp_updraft_extradbs[' + updraft_extra_dbs + '][pass]" value="' + pass + '">' +
									'<div class="updraft_backupextradbs_row_label"><?php echo esc_js( __( 'Database', 'mainwp-updraftplus-extension' ) ); ?>:</div><input class="updraft_backupextradbs_row_textinput extradb_name" type="text" name="mwp_updraft_extradbs[' + updraft_extra_dbs + '][name]" value="' + name + '">' +
									'<div class="updraft_backupextradbs_row_label" title="<?php echo esc_attr( 'If you enter a table prefix, then only tables that begin with this prefix will be backed up.', 'mainwp-updraftplus-extension' ); ?>"><?php echo esc_js( __( 'Table prefix', 'mainwp-updraftplus-extension' ) ); ?>:</div><input class="updraft_backupextradbs_row_textinput extradb_prefix" title="<?php echo esc_attr( 'If you enter a table prefix, then only tables that begin with this prefix will be backed up.', 'mainwp-updraftplus-extension' ); ?>" type="text" name="mwp_updraft_extradbs[' + updraft_extra_dbs + '][prefix]" value="' + prefix + '">';

							if (mwp_updraft_individual_siteid)
								appEl += '<div class="updraft_backupextradbs_row_label" style="width: 180px; padding: 6px 0; text-align:right;"><a href="#" class="mwp_updraft_backupextradbs_row_testconnection"><?php echo esc_js( __( 'Test connection...', 'mainwp-updraftplus-extension' ) ); ?></a></div>';

							appEl += '</div>';

							jQuery('#mwp_updraft_backupextradbs').append(appEl);
						}
						jQuery('#mwp_updraft_backupextradb_another').click(function (e) {
							e.preventDefault();
							mainwp_updraft_extradbs_add('', '', '', '', '');
						});
						jQuery('#mwp_updraft_backupextradbs').on('click', '.updraft_backupextradbs_row_delete', function () {
							jQuery(this).parents('.updraft_backupextradbs_row').slideUp('slow').delay(400).remove();
						});
						jQuery('#mwp_updraft_backupextradbs').on('click', '.mwp_updraft_backupextradbs_row_testconnection', function (e) {
							e.preventDefault();
							var row = jQuery(this).parents('.updraft_backupextradbs_row');
							jQuery(row).find('.updraft_backupextradbs_testresultarea').html('<p><em><?php _e( 'Testing...', 'mainwp-updraftplus-extension' ); ?></em></p>');
							var data = {
								action: 'mainwp_updraft_ajax',
								subaction: 'doaction',
								subsubaction: 'mainwp_updraft_extradb_testconnection',
								row: jQuery(row).attr('id'),
								nonce: '<?php echo wp_create_nonce( 'mwp-updraftplus-credentialtest-nonce' ); ?>',
								host: jQuery(row).children('.extradb_host').val(),
								user: jQuery(row).children('.extradb_user').val(),
								pass: jQuery(row).children('.extradb_pass').val(),
								name: jQuery(row).children('.extradb_name').val(),
								prefix: jQuery(row).children('.extradb_prefix').val(),
								updraftRequestSiteID: mwp_updraft_individual_siteid
							};
							jQuery.post(ajaxurl, data, function (data) {
								try {
									resp = jQuery.parseJSON(data);
									if (resp.error) {
										jQuery('#' + resp.r).find('.updraft_backupextradbs_testresultarea').html('<span style="color:red">' + resp.error + '</span>');
									} else if (resp.m && resp.r) {
										jQuery('#' + resp.r).find('.updraft_backupextradbs_testresultarea').html(resp.m);
									} else {
										alert('<?php echo esc_js( __( 'Error: the server sent us a response (JSON) which we did not understand.', 'mainwp-updraftplus-extension' ) ); ?> ' + resp);
									}
								} catch (err) {
									console.log(err);
									console.log(data);
								}
							});
						});
				<?php
				$extradbs = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_extradbs' );
				if ( is_array( $extradbs ) ) {
					foreach ( $extradbs as $db ) {
						if ( is_array( $db ) && ! empty( $db['host'] ) ) {
								echo "mainwp_updraft_extradbs_add('" . esc_js( $db['host'] ) . "', '" . esc_js( $db['user'] ) . "', '" . esc_js( $db['name'] ) . "', '" . esc_js( $db['pass'] ) . "', '" . esc_js( $db['prefix'] ) . "');\n"; }
					}
				}
				?>
					});
				</script>
				<?php
	}

	public function encrypt_file( $result, $file, $encryption, $whichdb, $whichdb_suffix ) {


	}

	public function database_encryption_config( $x ) {
		$updraft_encryptionphrase = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_encryptionphrase' );

		$ret = '';

		$ret .= '<input type="' . apply_filters( 'mainwp_updraftplus_admin_secret_field_type', 'text' ) . '" name="mwp_updraft_encryptionphrase" id="updraft_encryptionphrase" value="' . esc_attr( $updraft_encryptionphrase ) . '" size="54"  />';

		$ret .= '<p>' . __( 'If you enter text here, it is used to encrypt database backups (Rijndael). <strong>Do make a separate record of it and do not lose it, or all your backups <em>will</em> be useless.</strong> This is also the key used to decrypt backups from this admin interface (so if you change it, then automatic decryption will not work until you change it back).', 'mainwp-updraftplus-extension' ) . '</p>';

		return $ret;
	}

	public function backup_databases( $w ) {

		if ( ! is_array( $w ) ) {
				return $w; }

			$extradbs = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_extradbs' );
		if ( empty( $extradbs ) || ! is_array( $extradbs ) ) {
				return $w; }

			$dbnum = 0;
		foreach ( $extradbs as $db ) {
			if ( ! is_array( $db ) || empty( $db['host'] ) ) {
					continue; }
					$dbnum++;
					$w[ $dbnum ] = array( 'dbinfo' => $db, 'status' => 'begun' );
		}

			return $w;
	}

	private function encrypt( $fullpath, $key, $rformat = 'inline' ) {

	}
}

# Needs keeping in sync with the version in backup.php

class MainWP_Updraft_Plus_WPDB_OtherDB_Test extends wpdb {

		// This adjusted bail() does two things: 1) Never dies and 2) logs in the UD log
	public function bail( $message, $error_code = 'updraftplus_default' ) {
		//      global $mainwp_updraftplus_admin;
		//      if ('updraftplus_default' == $error_code) {
		//          $mainwp_updraftplus_admin->logged[] = $message;
		//      } else {
		//          $mainwp_updraftplus_admin->logged[$error_code] = $message;
		//      }
			# Now do the things that would have been done anyway
		if ( class_exists( 'WP_Error' ) ) {
				$this->error = new WP_Error( $error_code, $message ); } else { 					$this->error = $message; }
			return false;
	}
}
