<?php

class MainWP_Updraft_Plus_Admin {

	public function __construct() {
			$this->admin_init();
	}

	private function admin_init() {
		if ( isset( $_GET['page'] ) && (('Extensions-Mainwp-Updraftplus-Extension' == $_GET['page']) || ('ManageSitesUpdraftplus' == $_GET['page'])) ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 99999 );
		}
		add_action( 'wp_ajax_mainwp_updraft_ajax', array( $this, 'updraft_ajax_handler' ) );
		add_action( 'wp_ajax_mainwp_updraft_download_backup', array( $this, 'ajax_updraft_download_backup' ) );
		add_action( 'wp_ajax_mainwp_updraft_rescan_history_backups', array( $this, 'ajax_updraft_historystatus' ) );

		add_action( 'admin_head', array( $this, 'admin_head' ) );
	}

	public function admin_head() {
			$this->render_admin_css();
	}

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

	public function render_admin_css() {
			$images_dir = MAINWP_UPDRAFT_PLUS_URL.'/images/icons';
			?>
			<style type="text/css">
				.updraft_settings_sectionheading { display: none; }

				.mwp-updraft-backupentitybutton-disabled {
					background-color: transparent;
					border: none;
					color: #0074a2;
					text-decoration: underline;
					cursor: pointer;
					clear: none;
					float: left;
				}
				.mwp-updraft-backupentitybutton {
					margin-left: 8px !important;
				}
				.updraft-bigbutton {
					padding: 2px 0px;
					margin-right: 14px !important;
					font-size:22px !important;
					min-height: 32px;
					min-width: 180px;
				}
				.updraft_debugrow th {
					text-align: right;
					font-weight: bold;
					padding-right: 8px;
					min-width: 140px;
				}
				.updraft_debugrow td {
					min-width: 300px;
				}
				.updraftplus-morefiles-row-delete {
					cursor: pointer;
					color: red;
					font-size: 100%;
					font-weight: bold;
					border: 0px;
					border-radius: 3px;
					padding: 2px;
					margin: 0 6px;
				}
				.updraftplus-morefiles-row-delete:hover {
					cursor: pointer;
					color: white;
					background: red;
				}

				#updraft-wrap .form-table th {
					width: 230px;
				}
				#mwp_ud_downloadstatus .button {
					vertical-align: middle !important;

				}
				.mwp-updraftplus-remove {
					background-color: #c00000;
					border: 1px solid #c00000;
					height: 22px;
					padding: 4px 3px 0;
					margin-right: 6px;
				}
				.mwp-updraft-viewlogdiv form {
					margin: 0;
					padding: 0;
				}
				/*      .mwp-updraft-viewlogdiv {
							background-color: #ffffff;
							color: #000000;
							border: 1px solid #000000;
							height: 26px;
							padding: 0px;
							margin: 0 4px 0 0;
							border-radius: 3px;
						}*/
				/*      .mwp-updraft-viewlogdiv input {
							border: none;
							background-color: transparent;
							margin:0px;
							padding: 3px 4px;
						}
						.mwp-updraft-viewlogdiv:hover {
							background-color: #000000;
							color: #ffffff;
							border: 1px solid #ffffff;
							cursor: pointer;
						}
						.mwp-updraft-viewlogdiv input:hover {
							color: #ffffff;
							cursor: pointer;
						}*/
				.mwp-updraftplus-remove a {
					color: white;
					padding: 4px 4px 0px;
				}
				.mwp-updraftplus-remove:hover {
					background-color: white;
					border: 1px solid #c00000;
				}
				.mwp-updraftplus-remove a:hover {
					color: #c00000;
				}
				.drag-drop #drag-drop-area2 {
					border: 4px dashed #ddd;
					height: 200px;
				}
				#drag-drop-area2 .drag-drop-inside {
					margin: 36px auto 0;
					width: 350px;
				}
				#filelist, #filelist2  {
					width: 100%;
				}
				#filelist .file, #filelist2 .file, #mwp_ud_downloadstatus .file, #mwp_ud_downloadstatus2 .file {
					padding: 5px;
					background: #ececec;
					border: solid 1px #ccc;
					margin: 4px 0;
				}

				ul.updraft_premium_description_list {
					list-style: disc inside;
				}
				ul.updraft_premium_description_list li {
					display: inline;
				}
				ul.updraft_premium_description_list li::after {
					content: " | ";
				}
				ul.updraft_premium_description_list li.last::after {
					content: "";
				}
				.updraft_feature_cell{
					background-color: #F7D9C9 !important;
					padding: 5px 10px 5px 10px;
				}
				.updraft_feat_table, .updraft_feat_th, .updraft_feat_table td{
					border: 1px solid black;
					border-collapse: collapse;
					font-size: 120%;
					background-color: white;
				}
				.updraft_tick_cell{
					text-align: center;
				}
				.updraft_tick_cell img{
					margin: 4px 0;
					height: 24px;
				}

				#filelist .fileprogress, #filelist2 .fileprogress, #mwp_ud_downloadstatus .dlfileprogress, #mwp_ud_downloadstatus2 .dlfileprogress {
					width: 0%;
					background: #f6a828;
					height: 5px;
				}
				#mwp_ud_downloadstatus .raw, #mwp_ud_downloadstatus2 .raw {
					margin-top: 8px;
					clear:left;
				}
				#mwp_ud_downloadstatus .file, #mwp_ud_downloadstatus2 .file {
					margin-top: 8px;
				}

				#updraft_retain_db_rules .updraft_retain_rules_delete, #updraft_retain_files_rules .updraft_retain_rules_delete {
					cursor: pointer;
					color: red;
					font-size: 120%;
					font-weight: bold;
					border: 0px;
					border-radius: 3px;
					padding: 2px;
					margin: 0 6px;
				}
				#updraft_retain_db_rules .updraft_retain_rules_delete:hover, #updraft_retain_files_rules .updraft_retain_rules_delete:hover {
					cursor: pointer;
					color: white;
					background: red;
				}

				/* Selectric dropdown styling */
				.selectric-items .ico {
				display: inline-block;
				vertical-align: middle;
				zoom: 1;
				*display: inline;
				height: 40px;
				width: 40px;
				margin: 0 6px 0 0;
				}

				.selectric-wrapper{
					width: 300px;
				}

				.selectric-items .ico-updraftvault{ background: url(<?php echo $images_dir; ?>/updraftvault.png) no-repeat; }
				.selectric-items .ico-dropbox { background: url(<?php echo $images_dir; ?>/dropbox.png) no-repeat; }
				.selectric-items .ico-s3 { background: url(<?php echo $images_dir; ?>/s3.png) no-repeat; }
				.selectric-items .ico-cloudfiles { background: url(<?php echo $images_dir; ?>/cloudfiles.png) no-repeat; }
				.selectric-items .ico-googledrive { background: url(<?php echo $images_dir; ?>/googledrive.png) no-repeat; }
				.selectric-items .ico-onedrive { background: url(<?php echo $images_dir; ?>/onedrive.png) no-repeat; }
				.selectric-items .ico-azure { background: url(<?php echo $images_dir; ?>/azure.png) no-repeat; }
				.selectric-items .ico-ftp { background: url(<?php echo $images_dir; ?>/folder.png) no-repeat; }
				.selectric-items .ico-sftp { background: url(<?php echo $images_dir; ?>/folder.png) no-repeat; }
				.selectric-items .ico-webdav { background: url(<?php echo $images_dir; ?>/webdav.png) no-repeat; }
				.selectric-items .ico-s3generic { background: url(<?php echo $images_dir; ?>/folder.png) no-repeat; }
				.selectric-items .ico-googlecloud { background: url(<?php echo $images_dir; ?>/googlecloud.png) no-repeat; }
				.selectric-items .ico-openstack { background: url(<?php echo $images_dir; ?>/openstack.png) no-repeat; }
				.selectric-items .ico-dreamobjects { background: url(<?php echo $images_dir; ?>/dreamobjects.png) no-repeat; }
				.selectric-items .ico-email { background: url(<?php echo $images_dir; ?>/email.png) no-repeat; }

				div.selectric {
					padding: 2px;
					line-height: 28px;
					height: 28px;
					vertical-align: middle;
					background-color: #fff;
				}

				.selectric .label {
					line-height: 28px;
					height: 28px;
					margin: 0px 0px 0px 4px;
					font-size: 14px;
				}

				.selectric .button {
					width: 22px;
					height: 32px;
					border: none;
				}

				.selectric .button:after {
					border-top-color: #000;
				}

				.selectric-hover .selectric {
					border-color: #DDD;
					cursor: default;
				}

				.selectric-hover .selectric .button {
					cursor: default;
				}

				.selectric-hover .selectric .button:after {
					border-top-color: #000;
				}

			</style>
			<?php
	}

	public function admin_enqueue_scripts() {
            global $wp_locale;

            $day_selector = '';
            for ($day_index = 0; $day_index <= 6; $day_index++) {
                // $selected = ($opt == $day_index) ? 'selected="selected"' : '';
                $selected = '';
                $day_selector .= "\n\t<option value='" . $day_index . "' $selected>" . $wp_locale->get_weekday($day_index) . '</option>';
            }

            $mday_selector = '';
            for ($mday_index = 1; $mday_index <= 28; $mday_index++) {
                // $selected = ($opt == $mday_index) ? 'selected="selected"' : '';
                $selected = '';
                $mday_selector .= "\n\t<option value='" . $mday_index . "' $selected>" . $mday_index . '</option>';
            }

			wp_enqueue_script( 'mainwp-updraftplus-admin-ui', MAINWP_UPDRAFT_PLUS_URL . '/includes/updraft-admin-ui.js', array( 'jquery', 'jquery-ui-dialog', 'plupload-all' ), '53' );
			wp_localize_script('mainwp-updraftplus-admin-ui', 'mwp_updraftlion', array(
				'sendonlyonwarnings' => __( 'Send a report only when there are warnings/errors', 'mainwp-updraftplus-extension' ),
				'wholebackup' => __( 'When the Email storage method is enabled, also send the backup', 'mainwp-updraftplus-extension' ),
				'emailsizelimits' => esc_attr( sprintf( __( 'Be aware that mail servers tend to have size limits; typically around %s Mb; backups larger than any limits will likely not arrive.', 'mainwp-updraftplus-extension' ), '10-20' ) ),
				'rescanning' => __( 'Rescanning (looking for backups that you have uploaded manually into the internal backup store)...', 'mainwp-updraftplus-extension' ),
				'rescanningremote' => __( 'Rescanning remote and local storage for backup sets...', 'mainwp-updraftplus-extension' ),
				'enteremailhere' => esc_attr( __( 'To send to more than one address, separate each address with a comma.', 'mainwp-updraftplus-extension' ) ),
				'excludedeverything' => __( 'If you exclude both the database and the files, then you have excluded everything!', 'mainwp-updraftplus-extension' ),
				'restoreproceeding' => __( 'The restore operation has begun. Do not press stop or close your browser until it reports itself as having finished.', 'mainwp-updraftplus-extension' ),
				'unexpectedresponse' => __( 'Unexpected response:', 'mainwp-updraftplus-extension' ),
				'servererrorcode' => __( 'The web server returned an error code (try again, or check your web server logs)', 'mainwp-updraftplus-extension' ),
				'newuserpass' => __( "The new user's RackSpace console password is (this will not be shown again):", 'mainwp-updraftplus-extension' ),
				'trying' => __( 'Trying...', 'mainwp-updraftplus-extension' ),
				'calculating' => __( 'calculating...', 'mainwp-updraftplus-extension' ),
				'begunlooking' => __( 'Begun looking for this entity', 'mainwp-updraftplus-extension' ),
				'stilldownloading' => __( 'Some files are still downloading or being processed - please wait.', 'mainwp-updraftplus-extension' ),
				'processing' => __( 'Processing files - please wait...', 'mainwp-updraftplus-extension' ),
				//'restoreprocessing' => __('Restoring backup - please wait...', 'mainwp-updraftplus-extension'),
				'deleteolddirprocessing' => __( 'Deleting old directory - please wait...', 'mainwp-updraftplus-extension' ),
				'emptyresponse' => __( 'Error: the server sent an empty response.', 'mainwp-updraftplus-extension' ),
				'warnings' => __( 'Warnings:', 'mainwp-updraftplus-extension' ),
				'errors' => __( 'Errors:', 'mainwp-updraftplus-extension' ),
				'jsonnotunderstood' => __( 'Error: the server sent us a response (JSON) which we did not understand.', 'mainwp-updraftplus-extension' ),
				'errordata' => __( 'Error data:', 'mainwp-updraftplus-extension' ),
				'error' => __( 'Error:', 'mainwp-updraftplus-extension' ),
				'fileready' => __( 'File ready.', 'mainwp-updraftplus-extension' ),
				'youshould' => __( 'You should:', 'mainwp-updraftplus-extension' ),
				'connect' => __('Connect', 'mainwp-updraftplus-extension' ),
				'connecting' => __('Connecting...', 'mainwp-updraftplus-extension' ),
				'running' => __('Running...', 'mainwp-updraftplus-extension' ),
				'deletefromserver' => __( 'Delete from your web server', 'mainwp-updraftplus-extension' ),
				'downloadtocomputer' => __( 'Download to your computer', 'mainwp-updraftplus-extension' ),
				'andthen' => __( 'and then, if you wish,', 'mainwp-updraftplus-extension' ),
				'notunderstood' => __( 'Download error: the server sent us a response which we did not understand.', 'mainwp-updraftplus-extension' ),
				'requeststart' => __( 'Requesting start of backup...', 'mainwp-updraftplus-extension' ),
				'phpinfo' => __( 'PHP information', 'mainwp-updraftplus-extension' ),
				'delete_old_dirs' => __( 'Delete Old Directories', 'mainwp-updraftplus-extension' ),
				'raw' => __( 'Raw backup history', 'mainwp-updraftplus-extension' ),
				'notarchive' => __( 'This file does not appear to be an UpdraftPlus backup archive (such files are .zip or .gz files which have a name like: backup_(time)_(site name)_(code)_(type).(zip|gz)).', 'mainwp-updraftplus-extension' ) . ' ' . __( 'However, UpdraftPlus archives are standard zip/SQL files - so if you are sure that your file has the right format, then you can rename it to match that pattern.', 'mainwp-updraftplus-extension' ),
				'notarchive2' => '<p>' . __( 'This file does not appear to be an UpdraftPlus backup archive (such files are .zip or .gz files which have a name like: backup_(time)_(site name)_(code)_(type).(zip|gz)).', 'mainwp-updraftplus-extension' ) . '</p> ' . apply_filters( 'mainwp_updraftplus_if_foreign_then_premium_message', '<p><a href="http://updraftplus.com/shop/updraftplus-premium/">' . __( 'If this is a backup created by a different backup plugin, then UpdraftPlus Premium may be able to help you.', 'mainwp-updraftplus-extension' ) . '</a></p>' ),
				'makesure' => __( '(make sure that you were trying to upload a zip file previously created by UpdraftPlus)', 'mainwp-updraftplus-extension' ),
				'uploaderror' => __( 'Upload error:', 'mainwp-updraftplus-extension' ),
				'notdba' => __( 'This file does not appear to be an UpdraftPlus encrypted database archive (such files are .gz.crypt files which have a name like: backup_(time)_(site name)_(code)_db.crypt.gz).', 'mainwp-updraftplus-extension' ),
				'uploaderr' => __( 'Upload error', 'mainwp-updraftplus-extension' ),
				'followlink' => __( 'Follow this link to attempt decryption and download the database file to your computer.', 'mainwp-updraftplus-extension' ),
				'thiskey' => __( 'This decryption key will be attempted:', 'mainwp-updraftplus-extension' ),
				'unknownresp' => __( 'Unknown server response:', 'mainwp-updraftplus-extension' ),
				'ukrespstatus' => __( 'Unknown server response status:', 'mainwp-updraftplus-extension' ),
				'uploaded' => __( 'The file was uploaded.', 'mainwp-updraftplus-extension' ),
				'backupnow' => __( 'Backup Now', 'mainwp-updraftplus-extension' ),
				'cancel' => __( 'Cancel', 'mainwp-updraftplus-extension' ),
				'deletebutton' => __( 'Delete', 'mainwp-updraftplus-extension' ),
				'createbutton' => __( 'Create', 'mainwp-updraftplus-extension' ),
				'proceedwithupdate' => __( 'Proceed with update', 'mainwp-updraftplus-extension' ),
				'close' => __( 'Close', 'mainwp-updraftplus-extension' ),
				'restore' => __( 'Restore', 'mainwp-updraftplus-extension' ),
				'download' => __( 'Download log file', 'mainwp-updraftplus-extension' ),
				'automaticbackupbeforeupdate' => __( 'Automatic backup before update', 'mainwp-updraftplus-extension' ),
				'youdidnotselectany', __( 'You did not select any components to restore. Please select at least one, and then try again.', 'mainwp-updraftplus-extension' ),
				'undefinederror' => '<em style="color: red">' . __( 'Undefined Error', 'mainwp' ) . '</em>',
				'disabledbackup' => __( 'This button is disabled because your backup directory is not writable (see the settings).', 'mainwp-updraftplus-extension' ),
				'nothingscheduled' => __( 'Nothing currently scheduled', 'mainwp-updraftplus-extension' ),
				'errornocolon' => __('Error', 'mainwp-updraftplus-extension' ),
				'disconnect' => __('Disconnect', 'mainwp-updraftplus-extension' ),
				'disconnecting' => __('Disconnecting...', 'mainwp-updraftplus-extension' ),
				'days' => __('day(s)', 'mainwp-updraftplus-extension'),
				'hours' => __('hour(s)', 'mainwp-updraftplus-extension'),
				'weeks' => __('week(s)', 'mainwp-updraftplus-extension'),
				'forbackupsolderthan' => __('For backups older than', 'mainwp-updraftplus-extension'),
                'dayselector' => $day_selector,
                'mdayselector' => $mday_selector,
                'day' => __('day', 'mainwp-updraftplus-extension'),
                'inthemonth' => __('in the month', 'mainwp-updraftplus-extension'),
			));

			$selectric_file = (defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ? 'jquery.selectric.js' : 'jquery.selectric.min.js';
			wp_enqueue_script('selectric', MAINWP_UPDRAFT_PLUS_URL."/includes/selectric/$selectric_file", array('jquery'), '1.9.3');
			wp_enqueue_style('selectric', MAINWP_UPDRAFT_PLUS_URL.'/includes/selectric/selectric.css', array(), '1.9.3');

			wp_enqueue_script('jquery-labelauty', MAINWP_UPDRAFT_PLUS_URL.'/includes/labelauty/jquery-labelauty.js', array('jquery'), '20150925');
			wp_enqueue_style('jquery-labelauty', MAINWP_UPDRAFT_PLUS_URL.'/includes/labelauty/jquery-labelauty.css', array(), '20150925');


//			wp_localize_script('mainwp-updraftplus-admin-ui', 'mwp_updraftlion', array(
//					'sendonlyonwarnings' => __('Send a report only when there are warnings/errors', 'updraftplus'),
//					'wholebackup' => __('When the Email storage method is enabled, also send the entire backup', 'updraftplus'),
//					'emailsizelimits' => esc_attr(sprintf(__('Be aware that mail servers tend to have size limits; typically around %s Mb; backups larger than any limits will likely not arrive.','updraftplus'), '10-20')),
//					'rescanning' => __('Rescanning (looking for backups that you have uploaded manually into the internal backup store)...','updraftplus'),
//					'rescanningremote' => __('Rescanning remote and local storage for backup sets...','updraftplus'),
//					'enteremailhere' => esc_attr(__('To send to more than one address, separate each address with a comma.', 'updraftplus')),
//					'excludedeverything' => __('If you exclude both the database and the files, then you have excluded everything!', 'updraftplus'),
//					'nofileschosen' => __('You have chosen to backup files, but no file entities have been selected', 'updraftplus'),
//					'restoreproceeding' => __('The restore operation has begun. Do not press stop or close your browser until it reports itself as having finished.', 'updraftplus'),
//					'unexpectedresponse' => __('Unexpected response:','updraftplus'),
//					'servererrorcode' => __('The web server returned an error code (try again, or check your web server logs)', 'updraftplus'),
//					'newuserpass' => __("The new user's RackSpace console password is (this will not be shown again):", 'updraftplus'),
//					'trying' => __('Trying...', 'updraftplus'),
//					'calculating' => __('calculating...','updraftplus'),
//					'begunlooking' => __('Begun looking for this entity','updraftplus'),
//					'stilldownloading' => __('Some files are still downloading or being processed - please wait.', 'updraftplus'),
//					'processing' => __('Processing files - please wait...', 'updraftplus'),
//					'emptyresponse' => __('Error: the server sent an empty response.', 'updraftplus'),
//					'warnings' => __('Warnings:','updraftplus'),
//					'errors' => __('Errors:','updraftplus'),
//					'jsonnotunderstood' => __('Error: the server sent us a response (JSON) which we did not understand.', 'updraftplus'),
//					'errordata' => __('Error data:', 'updraftplus'),
//					'error' => __('Error:','updraftplus'),
//					'errornocolon' => __('Error','updraftplus'),
//					'fileready' => __('File ready.','updraftplus'),
//					'youshould' => __('You should:','updraftplus'),
//					'deletefromserver' => __('Delete from your web server','updraftplus'),
//					'downloadtocomputer' => __('Download to your computer','updraftplus'),
//					'andthen' => __('and then, if you wish,', 'updraftplus'),
//					'notunderstood' => __('Download error: the server sent us a response which we did not understand.', 'updraftplus'),
//					'requeststart' => __('Requesting start of backup...', 'updraftplus'),
//					'phpinfo' => __('PHP information', 'updraftplus'),
//					'delete_old_dirs' => __('Delete Old Directories', 'updraftplus'),
//					'raw' => __('Raw backup history', 'updraftplus'),
//					'notarchive' => __('This file does not appear to be an UpdraftPlus backup archive (such files are .zip or .gz files which have a name like: backup_(time)_(site name)_(code)_(type).(zip|gz)).', 'updraftplus').' '.__('However, UpdraftPlus archives are standard zip/SQL files - so if you are sure that your file has the right format, then you can rename it to match that pattern.','updraftplus'),
//					'notarchive2' => '<p>'.__('This file does not appear to be an UpdraftPlus backup archive (such files are .zip or .gz files which have a name like: backup_(time)_(site name)_(code)_(type).(zip|gz)).', 'updraftplus').'</p> '.apply_filters('updraftplus_if_foreign_then_premium_message', '<p><a href="https://updraftplus.com/shop/updraftplus-premium/">'.__('If this is a backup created by a different backup plugin, then UpdraftPlus Premium may be able to help you.', 'updraftplus').'</a></p>'),
//					'makesure' => __('(make sure that you were trying to upload a zip file previously created by UpdraftPlus)','updraftplus'),
//					'uploaderror' => __('Upload error:','updraftplus'),
//					'notdba' => __('This file does not appear to be an UpdraftPlus encrypted database archive (such files are .gz.crypt files which have a name like: backup_(time)_(site name)_(code)_db.crypt.gz).','updraftplus'),
//					'uploaderr' => __('Upload error', 'updraftplus'),
//					'followlink' => __('Follow this link to attempt decryption and download the database file to your computer.','updraftplus'),
//					'thiskey' => __('This decryption key will be attempted:','updraftplus'),
//					'unknownresp' => __('Unknown server response:','updraftplus'),
//					'ukrespstatus' => __('Unknown server response status:','updraftplus'),
//					'uploaded' => __('The file was uploaded.','updraftplus'),
//					'backupnow' => __('Backup Now', 'updraftplus'),
//					'cancel' => __('Cancel', 'updraftplus'),
//					'deletebutton' => __('Delete', 'updraftplus'),
//					'createbutton' => __('Create', 'updraftplus'),
//					'youdidnotselectany' => __('You did not select any components to restore. Please select at least one, and then try again.', 'updraftplus'),
//					'proceedwithupdate' => __('Proceed with update', 'updraftplus'),
//					'close' => __('Close', 'updraftplus'),
//					'restore' => __('Restore', 'updraftplus'),
//					'downloadlogfile' => __('Download log file', 'updraftplus'),
//					'automaticbackupbeforeupdate' => __('Automatic backup before update', 'updraftplus'),
//					'unsavedsettings' => __('You have made changes to your settings, and not saved.', 'updraftplus'),
//					'connect' => __('Connect', 'updraftplus'),
//					'connecting' => __('Connecting...', 'updraftplus'),
//					'disconnect' => __('Disconnect', 'updraftplus'),
//					'disconnecting' => __('Disconnecting...', 'updraftplus'),
//					'counting' => __('Counting...', 'updraftplus'),
//					'updatequotacount' => __('Update quota count', 'updraftplus'),
//					'addingsite' => __('Adding...', 'updraftplus'),
//					'addsite' => __('Add site', 'updraftplus'),
//		// 			'resetting' => __('Resetting...', 'updraftplus'),
//					'creating' => __('Creating...', 'updraftplus'),
//					'sendtosite' => __('Send to site:', 'updraftplus'),
//					'checkrpcsetup' => sprintf(__('You should check that the remote site is online, not firewalled, does not have security modules that may be blocking access, has UpdraftPlus version %s or later active and that the keys have been entered correctly.', 'updraftplus'), '2.10.3'),
//					'pleasenamekey' => __('Please give this key a name (e.g. indicate the site it is for):', 'updraftplus'),
//					'key' => __('Key', 'updraftplus'),
//					'nokeynamegiven' => sprintf(__("Failure: No %s was given.",'updraftplus'), __('key name','updraftplus')),
//					'deleting' => __('Deleting...', 'updraftplus'),
//					'testingconnection' => __('Testing connection...', 'updraftplus'),
//					'send' => __('Send', 'updraftplus'),
//					'migratemodalheight' => class_exists('UpdraftPlus_Addons_Migrator') ? 555 : 300,
//					'migratemodalwidth' => class_exists('UpdraftPlus_Addons_Migrator') ? 770 : 500,
//					'download' => _x('Download', '(verb)', 'updraftplus'),
//					'unsavedsettingsbackup' => __('You have made changes to your settings, and not saved.', 'updraftplus')."\n".__('Your backup will use your old settings until you save your changes.','updraftplus'),
//					'dayselector' => $day_selector,
//					'mdayselector' => $mday_selector,
//					'day' => __('day', 'updraftplus'),
//					'inthemonth' => __('in the month', 'updraftplus'),
//					'days' => __('day(s)', 'updraftplus'),
//					'hours' => __('hour(s)', 'updraftplus'),
//					'weeks' => __('week(s)', 'updraftplus'),
//					'forbackupsolderthan' => __('For backups older than', 'updraftplus'),
//					'processing' => __('Processing...', 'updraftplus'),
//			) );

	}

	public function ajax_updraft_download_backup() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'mwp_updraftplus_download' ) ) {
				die( json_encode( array( 'error' => 'Security Error.' ) ) ); }

		if ( ! isset( $_REQUEST['timestamp'] ) || ! is_numeric( $_REQUEST['timestamp'] ) || ! isset( $_REQUEST['type'] ) ) {
				die( json_encode( array( 'error' => 'Data Error.' ) ) ); }

			$siteid = $_REQUEST['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$post_data = array(
		'mwp_action' => 'updraft_download_backup',
				'timestamp' => $_REQUEST['timestamp'],
				'type' => $_REQUEST['type'],
				'stage' => isset( $_REQUEST['stage'] ) ? $_REQUEST['stage'] : '',
				'findex' => (isset( $_REQUEST['findex'] )) ? $_REQUEST['findex'] : 0,
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
			die( $information );
	}

	public function storagemethod_row( $method, $header, $contents ) {
			?>
            <div class="ui grid field mwp_updraftplusmethod <?php echo $method; ?>">
                <label class="six wide column middle aligned">
                    <h4 class="ui header">
                    <?php echo $header; ?>
                    </h4>
                </label>
                <div class="ui ten wide column">
                    <?php echo $contents; ?>
                </div>
            </div>
            <?php
	}

	public function settings_statustab() {
			global $mainwp_updraftplus, $mainwp_updraft_globals;

			$_text = __( 'Nothing currently scheduled', 'mainwp-updraftplus-extension' );
			$next_scheduled_backup = (isset( $mainwp_updraft_globals['all_saved_settings']['nextsched_files_timezone'] ) && ! empty( $mainwp_updraft_globals['all_saved_settings']['nextsched_files_timezone'] )) ? $mainwp_updraft_globals['all_saved_settings']['nextsched_files_timezone'] : $_text;
			$next_scheduled_backup_database = (isset( $mainwp_updraft_globals['all_saved_settings']['nextsched_database_timezone'] ) && ! empty( $mainwp_updraft_globals['all_saved_settings']['nextsched_database_timezone'] )) ? $mainwp_updraft_globals['all_saved_settings']['nextsched_database_timezone'] : $_text;
			$current_time = isset( $mainwp_updraft_globals['all_saved_settings']['nextsched_current_timezone'] ) ? $mainwp_updraft_globals['all_saved_settings']['nextsched_current_timezone'] : '';
			$backup_disabled = isset( $mainwp_updraft_globals['all_saved_settings']['updraft_backup_disabled'] ) ? $mainwp_updraft_globals['all_saved_settings']['updraft_backup_disabled'] : 0;

			$loader_url = plugins_url( 'images/loader.gif', __FILE__ );
			?>

			<div id="updraft-insert-admin-warning"></div>
			<noscript>
				<div class="ui red message"><?php _e( 'This admin interface uses JavaScript heavily. You either need to activate it within your browser, or to use a JavaScript-capable browser.', 'mainwp-updraftplus-extension' ); ?></div>
			</noscript>

			<h3 class="ui dividing header"><?php _e( 'Current Status', 'mainwp-updraftplus-extension' ); ?></h3>

			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Actions', 'mainwp-updraftplus-extension' ); ?></label>
	  		<div class="ui six wide column">
						<?php if ( $backup_disabled ) {
							$unwritable_mess = htmlspecialchars( __( "The 'Backup Now' button is disabled as your backup directory is not writable (go to the 'Settings' tab and find the relevant option).", 'mainwp-updraftplus-extension' ) );
						} ?>
						<button type="button" id="mwp_updraft_backupnow_btn" <?php echo $backup_disabled ?> class="ui big green button" onclick="jQuery('#backupnow_label').val(''); jQuery('#mwp-updraftplus-backupnow-modal').dialog('open');"><?php _e( 'Backup Now', 'mainwp-updraftplus-extension' ); ?></button>
						<button type="button" class="ui big green basic button" onclick="showUpdraftplusTab(false, false, false, true, false); mainwp_updraft_openrestorepanel(); return false;"><?php _e( 'Restore Now', 'mainwp-updraftplus-extension' ); ?></button>
				</div>
			</div>

			<?php $last_backup_html = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_lastbackup_html', '' ); ?>
			<script>var mwp_lastbackup_laststatus = '<?php echo esc_js( $last_backup_html ); ?>';</script>

			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Next scheduled backups', 'mainwp-updraftplus-extension' ); ?></label>
	  		<div class="ui ten wide column" id="mwp_updraft_next_scheduled_backups">
					<table class="ui single line table">
						<thead>
							<tr>
								<th><?php echo __( 'Files', 'mainwp-updraftplus-extension' ); ?></th>
								<th><?php echo __( 'Database', 'mainwp-updraftplus-extension' ); ?></th>
								<th><?php echo __( 'Time now', 'mainwp-updraftplus-extension' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><?php echo $next_scheduled_backup; ?></td>
								<td><?php echo $next_scheduled_backup_database; ?></td>
								<td><?php echo $current_time; ?></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Last backup job run', 'mainwp-updraftplus-extension' ); ?></label>
	  		<div class="ui ten wide column" id="mwp_updraft_last_backup">
					<span id="mwp_updraft_lastbackup_container">
						<?php echo ( ! empty( $last_backup_html ) ? $last_backup_html . '<div class="ui hidden divider"></div>' : ''); ?>
						<i class="notched circle loading icon"></i> <?php _e( ' Loading ...', 'mainwp' ); ?>
					</span>
				</div>
			</div>

			<?php $this->render_active_jobs_and_log_table(); ?>




				<div id="mwp-updraftplus-backupnow-modal" title="UpdraftPlus - <?php _e( 'Perform a one-time backup', 'mainwp-updraftplus-extension' ); ?>" style="display: none;">
					<p><?php _e( "To proceed, press 'Backup Now'. Then, watch the 'Last Log Message' field for activity.", 'mainwp-updraftplus-extension' ); ?></p>

					<p>
						<input type="checkbox" id="backupnow_nodb"> <label for="backupnow_nodb"><?php _e( "Don't include the database in the backup", 'mainwp-updraftplus-extension' ); ?></label><br>
						<input type="checkbox" id="backupnow_nofiles"> <label for="backupnow_nofiles"><?php _e( "Don't include any files in the backup", 'mainwp-updraftplus-extension' ); ?></label><br>
						<input type="checkbox" id="backupnow_nocloud"> <label for="backupnow_nocloud"><?php _e( "Don't send this backup to remote storage", 'mainwp-updraftplus-extension' ); ?></label>
					</p>


					<p><?php _e( 'Does nothing happen when you attempt backups?', 'mainwp-updraftplus-extension' ); ?> <a href="http://updraftplus.com/faqs/my-scheduled-backups-and-pressing-backup-now-does-nothing-however-pressing-debug-backup-does-produce-a-backup/"><?php _e( 'Go here for help.', 'mainwp-updraftplus-extension' ); ?></a></p>
				</div>

				<?php
	}

	public function updraft_ajax_handler() {

        global $mainwp_updraftplus;
        $nonce = (empty( $_REQUEST['nonce'] )) ? '' : $_REQUEST['nonce'];
		if ( ! wp_verify_nonce( $nonce, 'mwp-updraftplus-credentialtest-nonce' ) || empty( $_REQUEST['subaction'] ) ) {
				die( 'Security check' );
        }

        // Mitigation in case the nonce leaked to an unauthorised user
		if ( isset( $_REQUEST['subaction'] ) && 'dismissautobackup' == $_REQUEST['subaction'] ) {
			if ( ! current_user_can( 'update_plugins' ) && ! current_user_can( 'update_themes' ) ) {
					return;
            }
		} elseif ( isset( $_REQUEST['subaction'] ) && 'dismissexpiry' == $_REQUEST['subaction'] ) {
			if ( ! current_user_can( 'update_plugins' ) ) {
					return;
            }
		}

        // Some of this checks that _REQUEST['subaction'] is set, which is redundant (done already in the nonce check)
		if ( isset( $_REQUEST['subaction'] ) && 'lastlog' == $_REQUEST['subaction'] ) {
				echo htmlspecialchars( MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_lastmessage', '(' . __( 'Nothing yet logged', 'mainwp-updraftplus-extension' ) . ')' ) );
		} elseif ( 'forcescheduledresumption' == $_REQUEST['subaction'] && ! empty( $_REQUEST['resumption'] ) && ! empty( $_REQUEST['job_id'] ) && is_numeric( $_REQUEST['resumption'] ) ) {
				$this->ajax_updraft_forcescheduledresumption();
		} elseif ( isset( $_GET['subaction'] ) && 'activejobs_list' == $_GET['subaction'] ) {
				$this->ajax_updraft_activejobs_list();
		} elseif ( isset( $_REQUEST['subaction'] ) && 'callwpaction' == $_REQUEST['subaction'] && ! empty( $_REQUEST['wpaction'] ) ) {
            die;
		} elseif ( isset( $_REQUEST['subaction'] ) && 'httpget' == $_REQUEST['subaction'] ) {
			if ( empty( $_REQUEST['uri'] ) ) {
					echo json_encode( array( 'r' => '' ) );
					die;
			}
				$uri = $_REQUEST['uri'];
			if ( ! empty( $_REQUEST['curl'] ) ) {
				if ( ! function_exists( 'curl_exec' ) ) {
						echo json_encode( array( 'e' => 'No Curl installed' ) );
						die;
				}
					$ch = curl_init();
					curl_setopt( $ch, CURLOPT_URL, $uri );
					curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
					curl_setopt( $ch, CURLOPT_FAILONERROR, true );
					curl_setopt( $ch, CURLOPT_HEADER, true );
					curl_setopt( $ch, CURLOPT_VERBOSE, true );
					curl_setopt( $ch, CURLOPT_STDERR, $output = fopen( 'php://temp', 'w+' ) );
						$response = curl_exec( $ch );
						$error = curl_error( $ch );
						$getinfo = curl_getinfo( $ch );
						curl_close( $ch );
						$resp = array();
				if ( false === $response ) {
						$resp['e'] = htmlspecialchars( $error );
						# json_encode(array('e' => htmlspecialchars($error)));
				}
						$resp['r'] = (empty( $response )) ? '' : htmlspecialchars( substr( $response, 0, 2048 ) );
						rewind( $output );
						$verb = stream_get_contents( $output );
				if ( ! empty( $verb ) ) {
						$resp['r'] = htmlspecialchars( $verb ) . "\n\n" . $resp['r']; }
						echo json_encode( $resp );
						//              echo json_encode(array('r' => htmlspecialchars(substr($response, 0, 2048))));
			} else {
					$response = wp_remote_get( $uri, array( 'timeout' => 10 ) );
				if ( is_wp_error( $response ) ) {
						echo json_encode( array( 'e' => htmlspecialchars( $response->get_error_message() ) ) );
						die;
				}
					echo json_encode( array( 'r' => $response['response']['code'] . ': ' . htmlspecialchars( substr( $response['body'], 0, 2048 ) ) ) );
			}
					die;
		} elseif ( isset( $_REQUEST['subaction'] ) && 'dismissautobackup' == $_REQUEST['subaction'] ) {
				MainWP_Updraft_Plus_Options::update_updraft_option( 'updraftplus_dismissedautobackup', time() + 84 * 86400 );
		} elseif ( isset( $_REQUEST['subaction'] ) && 'dismissexpiry' == $_REQUEST['subaction'] ) {
				MainWP_Updraft_Plus_Options::update_updraft_option( 'updraftplus_dismissedexpiry', time() + 14 * 86400 );
		} elseif ( isset( $_REQUEST['subaction'] ) && 'poplog' == $_REQUEST['subaction'] ) {
				echo json_encode( $this->fetch_updraft_log( $_REQUEST['backup_nonce'] ) );
		} elseif ( isset( $_GET['subaction'] ) && 'restore_alldownloaded' == $_GET['subaction'] && isset( $_GET['restoreopts'] ) && isset( $_GET['timestamp'] ) ) {
				$this->ajax_updraft_restore_alldownloaded();
		} elseif ( isset( $_GET['subaction'] ) && 'restorebackup' == $_GET['subaction'] && isset( $_GET['backup_timestamp'] ) ) {
				$this->ajax_updraft_restorebackup();
		} elseif ( isset( $_POST['backup_timestamp'] ) && 'deleteset' == $_REQUEST['subaction'] ) {
				$this->ajax_updraft_deleteset();
		} elseif ( 'rawbackuphistory' == $_REQUEST['subaction'] ) {

		} elseif ( 'countbackups' == $_REQUEST['subaction'] ) {
				$backup_history = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_backup_history' );
				$backup_history = (is_array( $backup_history )) ? $backup_history : array();
				#echo sprintf(__('%d set(s) available', 'mainwp-updraftplus-extension'), count($backup_history));
				echo __( 'Existing Backups', 'mainwp-updraftplus-extension' ) . ' (' . count( $backup_history ) . ')';
		} elseif ( 'ping' == $_REQUEST['subaction'] ) {
				// The purpose of this is to detect brokenness caused by extra line feeds in plugins/themes - before it breaks other AJAX operations and leads to support requests
				echo 'pong';
		} elseif ( 'delete_old_dirs' == $_REQUEST['subaction'] ) {
				$this->ajax_delete_old_dirs();
		} elseif ( 'doaction' == $_REQUEST['subaction'] && ! empty( $_REQUEST['subsubaction'] ) && 'mainwp_updraft_' == substr( $_REQUEST['subsubaction'], 0, 15 ) ) {
				do_action( $_REQUEST['subsubaction'] );
		} elseif ( 'backupnow' == $_REQUEST['subaction'] ) {
				$this->ajax_updraft_backupnow();
		} elseif ( 'backupnow_schedule_requests' == $_REQUEST['subaction'] ) {
				$this->ajax_updraft_backupnow_schedule_requests();
		} elseif ( isset( $_GET['subaction'] ) && 'lastbackup' == $_GET['subaction'] ) {
				$this->ajax_updraft_lastbackuphtml();
		} elseif ( isset( $_GET['subaction'] ) && 'nextscheduledbackups' == $_GET['subaction'] ) {
				$this->ajax_updraft_nextscheduledbackups();
		} elseif ( isset( $_GET['subaction'] ) && 'activejobs_delete' == $_GET['subaction'] && isset( $_GET['jobid'] ) ) {
				$this->ajax_updraft_activejobs_delete();
		} elseif ( isset( $_GET['subaction'] ) && 'diskspaceused' == $_GET['subaction'] && isset( $_GET['entity'] ) ) {
				$this->ajax_updraft_diskspaceused();
		} elseif ( isset( $_GET['subaction'] ) && 'historystatus' == $_GET['subaction'] ) {
				$this->ajax_updraft_historystatus();
		} elseif ( isset( $_GET['subaction'] ) && 'downloadstatus' == $_GET['subaction'] && isset( $_GET['timestamp'] ) && isset( $_GET['type'] ) ) {

            $findex = (isset( $_GET['findex'] )) ? $_GET['findex'] : '0';
			if ( empty( $findex ) ) {
					$findex = '0';
            }
			$mainwp_updraftplus->nonce = $_GET['timestamp'];
			echo json_encode( $this->download_status( $_GET['timestamp'], $_GET['type'], $findex ) );

		} elseif ( isset( $_POST['subaction'] ) && 'credentials_test' == $_POST['subaction'] ) {

                $method = (preg_match( '/^[a-z0-9]+$/', $_POST['method'] )) ? $_POST['method'] : '';

				require_once( MAINWP_UPDRAFT_PLUS_DIR . "/methods/$method.php" );
				$objname = "MainWP_Updraft_Plus_BackupModule_$method";

				$this->logged = array();
				# TODO: Add action for WP HTTP SSL stuff
				//set_error_handler( array( $this, 'get_php_errors' ), E_ALL & ~E_STRICT );
			if ( method_exists( $objname, 'credentials_test' ) ) {
					$obj = new $objname;
					$obj->credentials_test();
			}
			if ( count( $this->logged ) > 0 ) {
					echo "\n\n" . __( 'Messages:', 'mainwp-updraftplus-extension' ) . "\n";
				foreach ( $this->logged as $err ) {
						echo "* $err\n";
				}
			}
				restore_error_handler();
		} elseif (('vault_connect' == $_REQUEST['subaction'] && isset($_REQUEST['email']) && isset($_REQUEST['pass'])) || 'vault_disconnect' == $_REQUEST['subaction'] || 'vault_recountquota' == $_REQUEST['subaction']) {
			require_once(MAINWP_UPDRAFT_PLUS_DIR.'/methods/updraftvault.php');
			$vault = new MainWP_Updraft_Plus_BackupModule_updraftvault();
			call_user_func(array($vault, 'ajax_'.$_REQUEST['subaction']));

		}

		die;
	}

	private function existing_backup_table_html( $site_id = 0, $websites = array() ) {
			$backup_history_html = '';
			$no_backup = '<div class="ui yellow message">' . __( 'You have not yet made any backups.', 'mainwp-updraftplus-extension' ) . '</div>';
		if ( $site_id ) {
			$backup_history_html = '<div class="mwp_updraft_content_wrapper" site-id="' . $site_id . '">';
			$backup_history_html .= MainWP_Updraft_Plus_Options::get_updraft_option( 'mainwp_updraft_backup_history_html' );
			$backup_history_html .= '</div>';
		} else {
			if ( is_array( $websites ) ) {
				foreach ( $websites as $_site ) {
					if ( ! isset( $_site['updraftplus_active'] ) || empty( $_site['updraftplus_active'] ) ) {
						continue;
					}

					$backup_history_html .= '<div class="mwp_updraft_content_wrapper" site-id="' . $_site['id'] . '">';
					$backup_history_html .= '<h3 class="ui dividing header">Site: ' . $_site['name'] . '</h3>';
					if ( empty( $_site['mainwp_updraft_backup_history_html'] ) ) {
						$backup_history_html .= $no_backup;
          } else {
            $backup_history_html .= $_site['mainwp_updraft_backup_history_html'] . '<br/>';
          }
          $backup_history_html .= '</div>';
				}
			}
		}

		if ( empty( $backup_history_html ) ) {
			$backup_history_html = $no_backup;
		}

		return $backup_history_html;
	}

	public function ajax_updraft_deleteset() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_POST['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$post_data = array(
		'mwp_action' => 'deleteset',
				'backup_timestamp' => $_POST['backup_timestamp'],
				'delete_remote' => $_POST['delete_remote'],
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );

		if ( is_array( $information ) && isset( $information['updraft_historystatus'] ) ) {
				$update = array(
					'mainwp_updraft_backup_history_html' => $information['updraft_historystatus'],
					'mainwp_updraft_backup_history_count' => $information['updraft_count_backups'],
				);
				MainWP_Updraftplus_BackupsDB::get_instance()->update_setting_fields_by( 'site_id', $siteid, $update );
		}

			die( json_encode( $information ) );
	}

	public function ajax_updraft_restore_alldownloaded() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_REQUEST['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$post_data = array(
		'mwp_action' => 'restore_alldownloaded',
				'timestamp' => $_REQUEST['timestamp'],
				'restoreopts' => $_REQUEST['restoreopts'],
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );

			die( json_encode( $information ) );
	}

	public function ajax_updraft_restorebackup() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_REQUEST['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			parse_str( $_REQUEST['restoreopts'], $res );

		if ( ! isset( $res['updraft_restore'] ) ) {
				die( json_encode( array( 'error' => 'Data Error.' ) ) ); }

			$post_data = array(
		'mwp_action' => 'restorebackup',
				'backup_timestamp' => $_REQUEST['backup_timestamp'],
				'updraft_restore' => $res['updraft_restore'],
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );

			die( json_encode( $information ) );
	}

	public function ajax_delete_old_dirs() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_GET['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$post_data = array( 'mwp_action' => 'delete_old_dirs' );

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );

			die( json_encode( $information ) );
	}


	public function ajax_updraft_backupnow() {
        global $mainWPUpdraftPlusBackupsExtensionActivator;
        $siteid = $_POST['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
            die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

        $information = MainWP_Updraftplus_Backups::get_instance()->request_backupnow( $siteid, $_REQUEST );
        die( json_encode( $information ) );
	}

    public function ajax_updraft_backupnow_schedule_requests() {
        global $mainWPUpdraftPlusBackupsExtensionActivator;
        $ids = $_POST['ids'];

		if ( empty( $ids ) ) {
            die( json_encode( array( 'error' => 'Error: Empty site ids.' ) ) );
        }

        $opts = array(
            'backupnow_nocloud' => $_REQUEST['backupnow_nocloud'],
            'backupnow_nofiles' => $_REQUEST['backupnow_nofiles'],
            'backupnow_nodb' => $_REQUEST['backupnow_nodb'],
            'onlythisfileentity' => isset( $_REQUEST['onlythisfileentity'] ) ? $_REQUEST['onlythisfileentity'] : '',
        );

        update_option('mainwp_updraft_backupnow_request_options', array('ids' => $ids, 'opts' => $opts));

        MainWP_Updraftplus_Backups::get_instance()->set_schedule_backup_requests();

        die( json_encode( array('ok' => 1) ) );
	}


	public function ajax_updraft_lastbackuphtml() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_GET['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$post_data = array( 'mwp_action' => 'last_backup_html' );

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
            if ( is_array( $information ) && isset( $information['lasttime_gmt'] ) ) {
                $update = array(
                    'site_id' => $siteid,
                    'lastbackup_gmttime' => $information['lasttime_gmt'],
                );
                MainWP_Updraftplus_BackupsDB::get_instance()->update_setting( $update );
            }
			die( json_encode( $information ) );
	}

	public function ajax_updraft_nextscheduledbackups() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_GET['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$post_data = array( 'mwp_action' => 'next_scheduled_backups' );

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
		if ( is_array( $information ) && isset( $information['nextsched_current_timegmt'] ) ) {
				$update = array(
					'updraft_backup_disabled' => isset( $information['updraft_backup_disabled'] ) ? $information['updraft_backup_disabled'] : 0,
					'nextsched_files_gmt' => isset( $information['nextsched_files_gmt'] ) ? $information['nextsched_files_gmt'] : 0,
					'nextsched_files_timezone' => isset( $information['nextsched_files_timezone'] ) ? $information['nextsched_files_timezone'] : '',
					'nextsched_database_gmt' => isset( $information['nextsched_database_gmt'] ) ? $information['nextsched_database_gmt'] : 0,
					'nextsched_database_timezone' => isset( $information['nextsched_database_timezone'] ) ? $information['nextsched_database_timezone'] : '',
					'nextsched_current_timegmt' => isset( $information['nextsched_current_timegmt'] ) ? $information['nextsched_current_timegmt'] : 0,
					'nextsched_current_timezone' => isset( $information['nextsched_current_timezone'] ) ? $information['nextsched_current_timezone'] : '',
				);
				MainWP_Updraftplus_BackupsDB::get_instance()->update_setting_fields_by( 'site_id', $siteid, $update );
		}
			die( json_encode( $information ) );
	}

	public function ajax_updraft_activejobs_delete() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_GET['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$post_data = array(
		'mwp_action' => 'activejobs_delete',
				'jobid' => $_GET['jobid'],
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
			die( json_encode( $information ) );
	}

	public function ajax_updraft_diskspaceused() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_GET['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$post_data = array(
		'mwp_action' => 'diskspaceused',
				'entity' => $_GET['entity'],
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
			die( json_encode( $information ) );
	}

	public function ajax_updraft_historystatus() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_REQUEST['updraftRequestSiteID'];

            if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$remotescan = (isset( $_REQUEST['remotescan'] ) && 1 == $_REQUEST['remotescan']) ? 1 : 0;
			$rescan = ($remotescan || (isset( $_REQUEST['rescan'] ) && 1 == $_REQUEST['rescan'])) ? 1 : 0;

			$post_data = array(
                'mwp_action' => 'historystatus',
				'remotescan' => $remotescan,
				'rescan' => $rescan,
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );

			$generalScan = (isset( $_POST['generalscan'] ) && $_POST['generalscan']) ? true : false;

			$success = false;

			if ( is_array( $information ) && isset( $information['t'] ) ) {
					$success = true;
					$update = array(
						'mainwp_updraft_backup_history_html' => $information['t'],
						'mainwp_updraft_backup_history_count' => $information['c'],
						'mainwp_updraft_detect_safe_mode' => $information['m'],
					);
					MainWP_Updraftplus_BackupsDB::get_instance()->update_setting_fields_by( 'site_id', $siteid, $update );
			}

			if ( $generalScan ) {
					$output = array();
				if ( $success ) {
						$out['result'] = 'success';
				} else if ( isset( $information['error'] ) ) {
						$output['error'] = $information['error'];
				} else {
						$out['result'] = 'fail';
				}
					die( json_encode( $out ) );
			}

			die( json_encode( $information ) );
	}

	public function ajax_updraft_activejobs_list() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_GET['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$post_data = array(
		'mwp_action' => 'activejobs_list',
				'downloaders' => isset( $_GET['downloaders'] ) ? $_GET['downloaders'] : '',
				'oneshot' => isset( $_GET['oneshot'] ) ? $_GET['oneshot'] : '',
				'thisjobonly' => isset( $_GET['thisjobonly'] ) ? $_GET['thisjobonly'] : '',
				'log_fetch' => isset( $_REQUEST['log_fetch'] ) ? $_REQUEST['log_fetch'] : '',
				'log_nonce' => isset( $_REQUEST['log_nonce'] ) ? $_REQUEST['log_nonce'] : '',
				'log_pointer' => isset( $_REQUEST['log_pointer'] ) ? $_REQUEST['log_pointer'] : '',
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
			die( json_encode( $information ) );
	}

	public function fetch_updraft_log( $backup_nonce, $log_pointer = 0 ) {

			global $mainWPUpdraftPlusBackupsExtensionActivator;
			$siteid = $_GET['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$post_data = array(
		'mwp_action' => 'fetch_updraft_log',
				'backup_nonce' => $backup_nonce,
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
		if ( is_array( $information ) && isset( $information['html'] ) ) {

		}
			die( json_encode( $information ) );
	}

	public function ajax_updraft_forcescheduledresumption() {
			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$siteid = $_GET['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$resumption = (int) $_REQUEST['resumption'];
			$job_id = $_REQUEST['job_id'];

			$post_data = array(
		'mwp_action' => 'forcescheduledresumption',
				'resumption' => $resumption,
				'job_id' => $job_id,
			);

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
			die( json_encode( $information ) );
	}

	public function settings_formcontents($is_individual = false, $override = 0) {
			global $mainwp_updraftplus;

			$updraft_dir = $mainwp_updraftplus->backups_dir_location();

			?>
			<div class="ui hidden divider"></div>
			<h3 class="ui dividing header"><?php _e('Backup Contents And Schedule','mainwp-updraftplus-extension');?></h3>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Files backup schedule', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column">
					<select class="ui fluid dropdown" id="mwp_updraft_interval" name="mwp_updraft_interval" onchange="jQuery(document).trigger('updraftplus_interval_changed'); mainwp_updraft_check_same_times();">
						<?php
						$intervals = $this->get_intervals();
						$selected_interval = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_interval', 'manual' );
						foreach ( $intervals as $cronsched => $descrip ) {
							echo "<option value=\"$cronsched\" ";
							if ( $cronsched == $selected_interval ) {
								echo 'selected="selected"'; }
							echo '>' . htmlspecialchars( $descrip ) . "</option>\n";
						}
						?>
					</select>
					<div class="ui hidden fitted divider"></div>
					<span id="updraft_files_timings"><?php echo apply_filters( 'mainwp_updraftplus_schedule_showfileopts', '<input type="hidden" name="mwp_updraftplus_starttime_files" value="">', $selected_interval ); ?></span>
					<div class="ui hidden fitted divider"></div>
					<?php
					echo __( 'and retain this many scheduled backups', 'mainwp-updraftplus-extension' ) . ': ';
					$updraft_retain = (int) MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_retain', 2 );
					$updraft_retain = ($updraft_retain > 0) ? $updraft_retain : 1;
					?>
					<input type="number" min="1" step="1" name="mwp_updraft_retain" value="<?php echo $updraft_retain ?>" />
					<div class="ui hidden fitted divider"></div>
					<?php do_action( 'mainwp_updraftplus_incremental_cell', $selected_interval ); ?>
					<?php do_action( 'mainwp_updraftplus_after_filesconfig' ); ?>
				</div>
			</div>
			<?php apply_filters( 'mainwp_updraftplus_after_file_intervals', false, $selected_interval ); ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Database backup schedule', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column">
					<?php $selected_interval_db = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_interval_database', MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_interval' ) ); ?>
					<select class="ui fluid dropdown" id="mwp_updraft_interval_database" name="mwp_updraft_interval_database" onchange="mainwp_updraft_check_same_times();">
						<?php
						foreach ( $intervals as $cronsched => $descrip ) {
							echo "<option value=\"$cronsched\" ";
							if ( $selected_interval_db == $cronsched ) {
								echo 'selected="selected"';
							}
							echo ">$descrip</option>\n";
						}
						?>
					</select>
					<div class="ui hidden fitted divider"></div>
					<span id="updraft_db_timings"><?php echo apply_filters( 'mainwp_updraftplus_schedule_showdbopts', '<input type="hidden" name="mwp_updraftplus_starttime_db" value="">', $selected_interval_db ); ?></span>
					<div class="ui hidden fitted divider"></div>
					<?php
					echo __( 'and retain this many scheduled backups', 'mainwp-updraftplus-extension' ) . ': ';
					$updraft_retain_db = (int) MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_retain_db', $updraft_retain );
					$updraft_retain_db = ($updraft_retain_db > 0) ? $updraft_retain_db : 1;
					?>
					<input type="number" min="1" step="1" name="mwp_updraft_retain_db" value="<?php echo $updraft_retain_db ?>" />
					<div class="ui hidden fitted divider"></div>
					<?php do_action('mainwp_updraftplus_after_dbconfig'); ?>
				</div>
			</div>
			<?php
			$debug_mode = ( MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_debug_mode' ) ) ? 'checked="checked"' : '';
			$active_service = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_service' );
			$do_not_save_destinations = MainWP_Updraft_Plus_Options::get_updraft_option( 'do_not_save_destinations_settings', true );
			?>

			<h3 class="ui dividing header"><?php _e( 'Sending Your Backup To Remote Storage', 'mainwp-updraftplus-extension' );?></h3>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Do not override Remote Storage settings', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column ui toggle checkbox">
					<input type="checkbox" <?php echo !empty( $do_not_save_destinations ) ? ' checked="checked"' : ''; ?>  name="mwp_updraft_do_not_save_destinations_settings" id="updraft_do_not_save_destinations_settings" value="1">
					<label for="updraft_do_not_save_destinations_settings"></label>
				</div>				
			</div>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Choose your remote storage', 'mainwp-updraftplus-extension' ); ?></label>
                <div class="ten wide column">
					<div id="remote-storage-container">
						<?php
						if ( is_array( $active_service ) )
							$active_service = $mainwp_updraftplus->just_one( $active_service );

						//Change this to give a class that we can exclude
						$multi = apply_filters( 'maiwp_updraftplus_storage_printoptions_multi', '' );

						foreach ( $mainwp_updraftplus->backup_methods as $method => $description ) {
							echo "<input name=\"mwp_updraft_service[]\" class=\"mwp_updraft_servicecheckbox $method $multi\" id=\"mwp_updraft_servicecheckbox_$method\" type=\"checkbox\" value=\"$method\"";
							if ( $active_service === $method || ( is_array( $active_service ) && in_array( $method, $active_service ) ) ) echo ' checked="checked"';
							echo " data-labelauty=\"".esc_attr($description)."\">";
						}
						?>

                    </div>
                </div>
            </div>

            <div class="ui grid field">
                <div class="sixteen wide column">
                    <?php if ( false === apply_filters('maiwp_updraftplus_storage_printoptions', false, $active_service ) ) :
                         //ok ?>
					<?php endif; ?>

                <?php
                $method_objects = array();
                foreach ( $mainwp_updraftplus->backup_methods as $method => $description ) {
                    //do_action( 'mainwp_updraftplus_config_print_before_storage', $method );
                    require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/' . $method . '.php' );
                    $call_method = 'MainWP_Updraft_Plus_BackupModule_' . $method;
                    $method_objects[ $method ] = new $call_method;
                    $method_objects[ $method ]->config_print();
                }
                ?>

                 </div>
			</div>

			<h3 class="ui dividing header"><?php _e( 'File Options', 'mainwp-updraftplus-extension' );?></h3>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Include in files backup', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column">
					<?php
					$backupable_entities = $mainwp_updraftplus->get_backupable_file_entities( true, true );

					foreach ( $backupable_entities as $key => $info ) {
						$included = ( MainWP_Updraft_Plus_Options::get_updraft_option( "updraft_include_$key", apply_filters( 'mainwp_updraftplus_defaultoption_include_' . $key, true ) ) ) ? 'checked="checked"' : '';
						if ( 'others' == $key || 'uploads' == $key ) {
							$include_exclude = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_include_' . $key . '_exclude', ('others' == $key) ? MAINWP_UPDRAFT_DEFAULT_OTHERS_EXCLUDE : MAINWP_UPDRAFT_DEFAULT_UPLOADS_EXCLUDE );
							?>
							<div class="ui checkbox">
								<input id="mwp_updraft_include_<?php echo $key; ?>" type="checkbox" name="mwp_updraft_include_<?php echo $key; ?>" value="1" <?php echo $included; ?> />
								<label for="mwp_updraft_include_<?php echo $key ?>"><?php echo ('others' == $key) ? __( 'Any other directories found inside wp-content', 'mainwp-updraftplus-extension' ) : htmlspecialchars( $info['description'] ); ?></label>
							</div>
							<div class="ui hidden clearing fitted divider"></div>
							<?php
							$display = ($included) ? '' : 'style="display:none;"';
							echo '<div id="mwp_updraft_include_' . $key . "_exclude\" $display>";
							echo '<label for="mwp_updraft_include_' . $key . '_exclude">' . __( 'Exclude these:', 'mainwp-updraftplus-extension' ) . '</label>';
							echo '<input type="text" id="mwp_updraft_include_' . $key . '_exclude" name="mwp_updraft_include_' . $key . '_exclude" size="54" value="' . htmlspecialchars( $include_exclude ) . '" />';
							echo '<div class="ui hidden clearing fitted divider"></div>';
							echo '</div>';
						} else {
							echo '<div class="ui checkbox">';
							echo "<input id=\"mwp_updraft_include_$key\" type=\"checkbox\" name=\"mwp_updraft_include_$key\" value=\"1\" $included /><label for=\"mwp_updraft_include_$key\"" . ((isset( $info['htmltitle'] )) ? ' title="' . htmlspecialchars( $info['htmltitle'] ) . '"' : '') . '> ' . htmlspecialchars( $info['description'] ) . '</label><div class="ui hidden fitted divider"></div>';
							echo '</div><div class="ui hidden fitted divider"></div>';
							do_action( "mainwp_updraftplus_config_option_include_$key" );
						}
					}
					?>
				</div>
			</div>
			<h3 class="ui dividing header"><?php _e('Database Options','mainwp-updraftplus-extension');?></h3>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Database encryption phrase', 'mainwp-updraftplus-extension' ); ?></label>
				<div class="ten wide column"><?php echo apply_filters( 'mainwp_updraft_database_encryption_config', '<a href="http://updraftplus.com/shop/updraftplus-premium/">' . __( "Don't want to be spied on? UpdraftPlus Premium can encrypt your database backup.", 'mainwp-updraftplus-extension' ) . '</a> ' . __( 'It can also backup external databases.', 'mainwp-updraftplus-extension' ) ); ?></div>
			</div>
			<?php $moredbs_config = apply_filters( 'mainwp_updraft_database_moredbs_config', false ); ?>
			<?php if ( ! empty( $moredbs_config ) ) : ?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Back up more databases', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column"><?php echo $moredbs_config; ?></div>
			</div>
			<?php endif; ?>
			<h3 class="ui dividing header"><?php _e( 'Reporting','mainwp-updraftplus-extension' );?></h3>
			<?php $report_rows = apply_filters( 'mainwp_updraftplus_report_form', false ); ?>
			<?php
			if ( is_string( $report_rows ) ) {
				echo $report_rows;
			} else {
			?>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Email', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column ui toggle checkbox">
					<?php $updraft_email = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_email' ); ?>
					<input type="checkbox" id="updraft_email" name="mwp_updraft_email" value="1"<?php if ( ! empty( $updraft_email ) ) { echo ' checked="checked"'; } ?> > <br><label for="updraft_email"><?php echo __( 'Check this box to have a basic report sent to', 'mainwp-updraftplus-extension' ) . ' ' . __( "your site's admin address", 'mainwp-updraftplus-extension' ) . '.'; ?></label>
				</div>
			</div>
			<?php } ?>
			<script type="text/javascript">
				/* <![CDATA[ */
				<?php echo $this->get_settings_js($method_objects); ?>
				/* ]]> */
			</script>
			<h3 class="ui dividing header"><?php _e( 'Advanced / debugging settings','mainwp-updraftplus-extension' );?></h3>
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Expert settings', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column">
					<a id="mwp_enableexpertmode" href="#enableexpertmode" class="ui mini green button"><?php _e( 'Show Expert Settings', 'mainwp-updraftplus-extension' ); ?></a>
				</div>
			</div>
			<?php
			$delete_local = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_delete_local', 1 );
			$split_every_mb = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_split_every', 500 );
			if ( ! is_numeric( $split_every_mb ) ) {
				$split_every_mb = 500;
			}
			if ( $split_every_mb < MAINWP_UPDRAFTPLUS_SPLIT_MIN ) {
				$split_every_mb = MAINWP_UPDRAFTPLUS_SPLIT_MIN;
			}
			?>
			<div class="ui grid field mwp_expertmode" style="display:none;">
				<label class="six wide column middle aligned"><?php _e( 'Debug mode', 'mainwp-updraftplus-extension' ); ?></label>
				<div class="ten wide column ui toggle checkbox">
					<input type="checkbox" id="updraft_debug_mode" name="mwp_updraft_debug_mode" value="1" <?php echo $debug_mode; ?> />
					<label for="updraft_debug_mode"><?php _e( 'Check this to receive more information and emails on the backup process - useful if something is going wrong.', 'mainwp-updraftplus-extension' ); ?> <?php _e( 'This will also cause debugging output from all plugins to be shown upon this screen - please do not be surprised to see these.', 'mainwp-updraftplus-extension' ); ?></label>
				</div>
			</div>
			<div class="ui grid field mwp_expertmode" style="display:none;">
				<label class="six wide column middle aligned"><?php _e( 'Split archives every', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column">
					<div class="ui right labeled input">
					  <input type="text" name="mwp_updraft_split_every" id="updraft_split_every" value="<?php echo $split_every_mb ?>" size="5" />
						<div class="ui label">MB</div>
					</div>
					<label for=""><?php echo sprintf( __( 'UpdraftPlus will split up backup archives when they exceed this file size. The default value is %s megabytes. Be careful to leave some margin if your web-server has a hard size limit (e.g. the 2 Gb / 2048 Mb limit on some 32-bit servers/file systems).', 'mainwp-updraftplus-extension' ), 500 ); ?></label>
				</div>
			</div>
			<div class="ui grid field mwp_expertmode" style="display:none;">
				<label class="six wide column middle aligned"><?php _e( 'Delete local backup', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column ui toggle checkbox">
					<input type="checkbox" id="updraft_delete_local" name="mwp_updraft_delete_local" value="1" <?php if ( $delete_local ) { echo 'checked="checked"'; } ?>>
					<label for="updraft_delete_local"><?php _e( 'Check this to delete any superfluous backup files from your server after the backup run finishes (i.e. if you uncheck, then any files despatched remotely will also remain locally, and any files being kept locally will not be subject to the retention limits).', 'mainwp-updraftplus-extension' ); ?></label>
				</div>
			</div>
			<div class="ui grid field mwp_expertmode" style="display:none;">
				<label class="six wide column middle aligned"><?php _e( 'Backup directory', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column">
					<input type="text" name="mwp_updraft_dir" id="updraft_dir" value="<?php echo htmlspecialchars( $this->prune_updraft_dir_prefix( $updraft_dir ) ); ?>" />
					<label for="updraft_delete_local"><?php _e( 'This is where UpdraftPlus will write the zip files it creates initially. This directory must be writable by your web server. It is relative to your content directory (which by default is called wp-content). <b>Do not</b> place it inside your uploads or plugins directory, as that will cause recursion (backups of backups of backups of...).', 'mainwp-updraftplus-extension' ); ?></label>
				</div>
			</div>
			<div class="ui grid field mwp_expertmode" style="display:none;">
				<label class="six wide column middle aligned"><?php _e( 'Use the server\'s SSL certificates', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column ui toggle checkbox">
					<input type="checkbox" id="updraft_ssl_useservercerts" name="mwp_updraft_ssl_useservercerts" value="1" <?php if ( MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_ssl_useservercerts' ) ) { echo 'checked="checked"'; } ?>>
					<label for="updraft_ssl_useservercerts"><?php _e( 'By default UpdraftPlus uses its own store of SSL certificates to verify the identity of remote sites (i.e. to make sure it is talking to the real Dropbox, Amazon S3, etc., and not an attacker). We keep these up to date. However, if you get an SSL error, then choosing this option (which causes UpdraftPlus to use your web server\'s collection instead) may help.', 'mainwp-updraftplus-extension' ); ?></label>
				</div>
			</div>
			<div class="ui grid field mwp_expertmode" style="display:none;">
				<label class="six wide column middle aligned"><?php _e( 'Do not verify SSL certificates', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column ui toggle checkbox">
					<input type="checkbox" id="updraft_ssl_disableverify" name="mwp_updraft_ssl_disableverify" value="1" <?php if ( MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_ssl_disableverify' ) ) { echo 'checked="checked"'; } ?>>
					<label for="updraft_ssl_disableverify"><?php _e( 'Choosing this option lowers your security by stopping UpdraftPlus from verifying the identity of encrypted sites that it connects to (e.g. Dropbox, Google Drive). It means that UpdraftPlus will be using SSL only for encryption of traffic, and not for authentication.', 'mainwp-updraftplus-extension' ); ?> <?php _e( 'Note that not all cloud backup methods are necessarily using SSL authentication.', 'mainwp-updraftplus-extension' ); ?></label>
				</div>
			</div>
			<div class="ui grid field mwp_expertmode" style="display:none;">
				<label class="six wide column middle aligned"><?php _e( 'Disable SSL entirely where possible', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="ten wide column ui toggle checkbox">
					<input type="checkbox" id="updraft_ssl_nossl" name="mwp_updraft_ssl_nossl" value="1" <?php if ( MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_ssl_nossl' ) ) { echo 'checked="checked"'; } ?>>
					<label for="updraft_ssl_nossl"><?php _e( 'Choosing this option lowers your security by stopping UpdraftPlus from using SSL for authentication and encrypted transport at all, where possible. Note that some cloud storage providers do not allow this (e.g. Dropbox), so with those providers this setting will have no effect.', 'mainwp-updraftplus-extension' ); ?> <a href="http://updraftplus.com/faqs/i-get-ssl-certificate-errors-when-backing-up-andor-restoring/"><?php _e( 'See this FAQ also.', 'mainwp-updraftplus-extension' ); ?></a></label>
				</div>
			</div>
			<?php do_action( 'mainwp_updraft_configprint_expertoptions' ); ?>
			<div class="ui divider"></div>
			<input type="hidden" name="action" value="update" />
			<input type="submit" name="submit-updraft-settings" class="ui big green right floating button" value="<?php _e( 'Save Changes', 'mainwp-updraftplus-extension' ); ?>" />
			<?php if ( $is_individual ) : ?>
			<input type="button" name="save-general-settings-to-site" class="ui big green basic button" <?php echo $override ? '' : 'disabled="disabled" style="display: none"'; ?> value="<?php _e( 'Save Global Settings to The Child Site', 'mainwp-updraftplus-extension' ); ?>" />
			<?php endif; ?>
		<?php
	}



	private function get_settings_js($method_objects) {

		global $mainwp_updraftplus;

		ob_start();
		?>
			jQuery(document).ready(function () {

				<?php
				if ( ! empty( $active_service ) ) {
					if ( is_array( $active_service ) ) {
						foreach ( $active_service as $serv ) {
								echo "jQuery('.${serv}').show();\n";
						}
					} else {
							echo "jQuery('.${active_service}').show();\n";
					}
				} else {
						echo "jQuery('.none').show();\n";
				}
				foreach ( $mainwp_updraftplus->backup_methods as $method => $description ) {
					// already done: require_once(MAINWP_UPDRAFT_PLUS_DIR.'/methods/'.$method.'.php');
					$call_method = "MainWP_Updraft_Plus_BackupModule_$method";
					if ( method_exists( $call_method, 'config_print_javascript_onready' ) ) {
							$method_objects[ $method ]->config_print_javascript_onready();
					}
				}
				?>
				});

		<?php
		$ret = ob_get_contents();
		ob_end_clean();
		return $ret;
	}

	public function get_intervals() {
			return apply_filters('mainwp_updraftplus_backup_intervals', array(
				'manual' => _x( 'Manual', 'i.e. Non-automatic', 'mainwp-updraftplus-extension' ),
				'every4hours' => sprintf( __( 'Every %s hours', 'mainwp-updraftplus-extension' ), '4' ),
				'every8hours' => sprintf( __( 'Every %s hours', 'mainwp-updraftplus-extension' ), '8' ),
				'twicedaily' => sprintf( __( 'Every %s hours', 'mainwp-updraftplus-extension' ), '12' ),
				'daily' => __( 'Daily', 'mainwp-updraftplus-extension' ),
				'weekly' => __( 'Weekly', 'mainwp-updraftplus-extension' ),
				'fortnightly' => __( 'Fortnightly', 'mainwp-updraftplus-extension' ),
				'monthly' => __( 'Monthly', 'mainwp-updraftplus-extension' ),
			));
	}

	public function render_active_jobs_and_log_table( $wide_format = false ) {
		global $mainwp_updraftplus;
		$active_jobs = '';
		?>

		<div class="ui grid field" id="mwp_updraft_activejobsrow" style="<?php if ( ! $active_jobs && ! $wide_format ) { echo 'display:none;'; } if ( $wide_format ) { echo 'min-height: 100px;'; } ?>">
			<label class="six wide column middle aligned"><?php _e( 'Backups in progress', 'mainwp-updraftplus-extension' ); ?></label>
			<div class="ten wide column">
				<div id="mwp_updraft_activejobs" ><?php echo $active_jobs; ?></div>
			</div>
		</div>

		<div class="ui grid field" id="updraft_lastlogmessagerow">
			<label class="six wide column middle aligned"><?php _e( 'Last log message', 'mainwp-updraftplus-extension' ); ?></label>
			<div class="ten wide column">
				<span id="mwp_updraft_lastlogcontainer"><i class="notched circle loading icon"></i> <?php _e( 'Loading ...', 'mainwp' ); ?></span>
				<div class="ui hidden divider"></div>
				<a href="#" class="updraft-log-link ui mini basic green button" onclick="event.preventDefault(); mainwp_updraft_popuplog('', this);"><?php _e( 'Download most recently modified log file', 'mainwp-updraftplus-extension' ); ?></a>
			</div>
		</div>
		<?php
	}

	public function show_double_warning( $text, $extraclass = '', $echo = true ) {

			$ret = "<div class=\"error mwp_updraftplusmethod $extraclass\"><p>$text</p></div>";
			$ret .= "<p style=\"border:1px solid; padding: 6px;\">$text</p>";

		if ( $echo ) {
				echo $ret; }
			return $ret;
	}

	public function curl_check( $service, $has_fallback = false, $extraclass = '', $echo = true ) {

		//      $ret = '';
		//
		//      // Check requirements
		//      if (!function_exists("curl_init") || !function_exists('curl_exec')) {
		//
		//          $ret .= $this->show_double_warning('<strong>'.__('Warning','mainwp-updraftplus-extension').':</strong> '.sprintf(__('Your web server\'s PHP installation does not included a <strong>required</strong> (for %s) module (%s). Please contact your web hosting provider\'s support and ask for them to enable it.', 'mainwp-updraftplus-extension'), $service, 'Curl').' '.sprintf(__("Your options are 1) Install/enable %s or 2) Change web hosting companies - %s is a standard PHP component, and required by all cloud backup plugins that we know of.",'mainwp-updraftplus-extension'), 'Curl', 'Curl'), $extraclass, false);
		//
		//      } else {
		//          $curl_version = curl_version();
		//          $curl_ssl_supported= ($curl_version['features'] & CURL_VERSION_SSL);
		//          if (!$curl_ssl_supported) {
		//              if ($has_fallback) {
		//                  $ret .= '<p><strong>'.__('Warning','mainwp-updraftplus-extension').':</strong> '.sprintf(__("Your web server's PHP/Curl installation does not support https access. Communications with %s will be unencrypted. ask your web host to install Curl/SSL in order to gain the ability for encryption (via an add-on).",'mainwp-updraftplus-extension'),$service).'</p>';
		//              } else {
		//                  $ret .= $this->show_double_warning('<p><strong>'.__('Warning','mainwp-updraftplus-extension').':</strong> '.sprintf(__("Your web server's PHP/Curl installation does not support https access. We cannot access %s without this support. Please contact your web hosting provider's support. %s <strong>requires</strong> Curl+https. Please do not file any support requests; there is no alternative.",'mainwp-updraftplus-extension'),$service).'</p>', $extraclass, false);
		//              }
		//          } else {
		//              $ret .= '<p><em>'.sprintf(__("Good news: Your site's communications with %s can be encrypted. If you see any errors to do with encryption, then look in the 'Expert Settings' for more help.", 'mainwp-updraftplus-extension'),$service).'</em></p>';
		//          }
		//      }
		//      if ($echo) {
		//          echo $ret;
		//      } else {
		//          return $ret;
		//      }
	}

	public function render_downloading_and_restoring( $site_id = 0, $websites = array(), $total_records = 0 ) {
		global $mainwp_updraftplus;
		$loader_url = plugins_url( 'images/loader.gif', __FILE__ );
		?>
		<div class="mainwp-actions-bar mwp_updraft_general_rescan_links">
			<div class="ui grid">
				<div class="ui two column row">
					<div class="middle aligned column">
						<?php if ( $site_id ) : ?>
							<strong><?php _e( 'Web-server disk space in use by UpdraftPlus ', 'mainwp-updraftplus-extension' ); ?>:</strong> <span id="mwp_updraft_diskspaceused"><em><?php _e('calculating...', 'mainwp-updraftplus-extension'); ?></em></span> <a href="#" onclick="mainwp_updraftplus_diskspace(); return false;"><?php _e('Refresh', 'mainwp-updraftplus-extension'); ?></a>
						<?php endif; ?>
					</div>
					<div class="right aligned column">
						<a href="#" class="ui green button" onclick="<?php echo ( $site_id ? 'mainwp_updraft_updatehistory(1, 0); return false;' : 'mainwp_updraft_general_updatehistory(1, 0); return false;'); ?>"><?php _e( 'Rescan Local Folder', 'mainwp-updraftplus-extension' ); ?></a>
						<a href="#" class="ui basic green button" onclick="<?php echo ( $site_id ? 'mainwp_updraft_updatehistory(1, 1); return false;' : 'mainwp_updraft_general_updatehistory(1,1); return false;' ); ?>"><?php _e( 'Rescan Remote Storage', 'mainwp-updraftplus-extension' ); ?></a>
					</div>
        </div>
			</div>
			<div class="ui active inverted dimmer loading" style="display:none">
		    <div class="ui text loader"><?php _e('Loading ...', 'mainwp-updraftplus-extension'); ?></div>
		  </div>
		</div>
		<div class="ui segment">
			<div id="mwp_ud_downloadstatus"></div>
			<div id="mwp_updraft_existing_backups" class="mwp_updraft_content_wrapper" site-id="<?php echo $site_id; ?>">
				<?php print $this->existing_backup_table_html( $site_id, $websites ); ?>
			</div>

			<div id="mwp-updraft-message-modal" title="UpdraftPlus">
				<div id="mwp-updraft-message-modal-innards" style="padding: 4px;"></div>
			</div>

			<div id="mwp-updraft-delete-modal" title="<?php _e( 'Delete backup set', 'mainwp-updraftplus-extension' ); ?>">
				<form id="updraft_delete_form" method="post">
					<p>
						<?php _e( 'Are you sure that you wish to remove this backup set from UpdraftPlus?', 'mainwp-updraftplus-extension' ); ?>
					</p>
					<fieldset>
						<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'mwp-updraftplus-credentialtest-nonce' ); ?>">
						<input type="hidden" name="action" value="mainwp_updraft_ajax">
						<input type="hidden" name="subaction" value="deleteset">
						<input type="hidden" name="updraftRequestSiteID" value="">

						<input type="hidden" name="backup_timestamp" value="0" id="updraft_delete_timestamp">
						<input type="hidden" name="backup_nonce" value="0" id="updraft_delete_nonce">
						<div id="updraft-delete-remote-section"><input checked="checked" type="checkbox" name="delete_remote" id="updraft_delete_remote" value="1"> <label for="updraft_delete_remote"><?php _e( 'Also delete from remote storage', 'mainwp-updraftplus-extension' ); ?></label><br>
							<p id="mwp-updraft-delete-waitwarning" style="display:none;"><em><?php _e( 'Deleting... please allow time for the communications with the remote storage to complete.', 'mainwp-updraftplus-extension' ); ?></em></p>
						</div>
					</fieldset>
				</form>
			</div>

			<div id="mwp-updraft-restore-modal" title="UpdraftPlus - <?php _e( 'Restore backup', 'mainwp-updraftplus-extension' ); ?>">
				<p><strong><?php _e( 'Restore backup from', 'mainwp-updraftplus-extension' ); ?>:</strong> <span class="updraft_restore_date"></span></p>

				<div id="mwp-updraft-restore-modal-stage2">

					<p><strong><?php _e( 'Retrieving (if necessary) and preparing backup files...', 'mainwp-updraftplus-extension' ); ?></strong></p>
					<div id="mwp_ud_downloadstatus2"></div>

					<div id="mwp-updraft-restore-modal-stage2a"></div>

				</div>

				<div id="mwp-updraft-restore-modal-stage1">
					<p><?php _e( "Restoring will replace this site's themes, plugins, uploads, database and/or other content directories (according to what is contained in the backup set, and your selection).", 'mainwp-updraftplus-extension' ); ?> <?php _e( 'Choose the components to restore', 'mainwp-updraftplus-extension' ); ?>:</p>
					<form id="updraft_restore_form" method="post">
						<fieldset>
							<input type="hidden" name="action" value="mainwp_updraft_restore">
							<input type="hidden" name="backup_timestamp" value="0" id="updraft_restore_timestamp">
							<input type="hidden" name="meta_foreign" value="0" id="updraft_restore_meta_foreign">
							<?php
							# The 'off' check is for badly configured setups - http://wordpress.org/support/topic/plugin-wp-super-cache-warning-php-safe-mode-enabled-but-safe-mode-is-off
							if ( $mainwp_updraftplus->detect_safe_mode() ) {
								echo '<p><em>' . __( 'Your web server has PHP\'s so-called safe_mode active.', 'mainwp-updraftplus-extension' ) . ' ' . __( 'This makes time-outs much more likely. You are recommended to turn safe_mode off, or to restore only one entity at a time, <a href="http://updraftplus.com/faqs/i-want-to-restore-but-have-either-cannot-or-have-failed-to-do-so-from-the-wp-admin-console/">or to restore manually</a>.', 'mainwp-updraftplus-extension' ) . '</em></p><br/>';
							}

							$backupable_entities = $mainwp_updraftplus->get_backupable_file_entities( true, true );
							foreach ( $backupable_entities as $type => $info ) {
								if ( ! isset( $info['restorable'] ) || true == $info['restorable'] ) {
									echo '<div><input id="updraft_restore_' . $type . '" type="checkbox" name="updraft_restore[]" value="' . $type . '"> <label id="updraft_restore_label_' . $type . '" for="updraft_restore_' . $type . '">' . $info['description'] . '</label><br>';
									do_action( "updraftplus_restore_form_$type" );
									echo '</div>';
								} else {
									$sdescrip = isset( $info['shortdescription'] ) ? $info['shortdescription'] : $info['description'];
									echo '<div style="margin: 8px 0;"><em>' . htmlspecialchars( sprintf( __( 'The following entity cannot be restored automatically: "%s".', 'mainwp-updraftplus-extension' ), $sdescrip ) ) . ' ' . __( 'You will need to restore it manually.', 'mainwp-updraftplus-extension' ) . '</em><br>' . '<input id="updraft_restore_' . $type . '" type="hidden" name="updraft_restore[]" value="' . $type . '">';
									echo '</div>';
								}
							}
							?>
							<div><input id="mwp_updraft_restore_db" type="checkbox" name="updraft_restore[]" value="db"> <label for="mwp_updraft_restore_db"><?php _e( 'Database', 'mainwp-updraftplus-extension' ); ?></label><br>
							<div id="updraft_restorer_dboptions" style="display:none; padding:12px; margin: 8px 0 4px; border: dashed 1px;"><h4 style="margin: 0px 0px 6px; padding:0px;"><?php echo sprintf( __( '%s restoration options:', 'mainwp-updraftplus-extension' ), __( 'Database', 'mainwp-updraftplus-extension' ) ); ?></h4>
								<?php do_action( 'mainwp_updraftplus_restore_form_db' ); ?>
							</div>
						</div>
					</fieldset>
				</form>
			</div>
		</div>
	</div>
	<?php
	}

	// not used anymore
	private function settings_expertsettings( $backup_disabled ) {

	}

	public function optionfilter_split_every( $value ) {
			$value = absint( $value );
		if ( ! $value >= MAINWP_UPDRAFTPLUS_SPLIT_MIN ) {
				$value = MAINWP_UPDRAFTPLUS_SPLIT_MIN; }
			return $value;
	}

	public function return_array( $input ) {
		if ( ! is_array( $input ) ) {
				$input = array(); }
			return $input;
	}

								// This options filter removes ABSPATH off the front of updraft_dir, if it is given absolutely and contained within it
	public function prune_updraft_dir_prefix( $updraft_dir ) {
		if ( '/' == substr( $updraft_dir, 0, 1 ) || '\\' == substr( $updraft_dir, 0, 1 ) || preg_match( '/^[a-zA-Z]:/', $updraft_dir ) ) {
				$wcd = trailingslashit( WP_CONTENT_DIR );
			if ( strpos( $updraft_dir, $wcd ) === 0 ) {
					$updraft_dir = substr( $updraft_dir, strlen( $wcd ) );
			}
		}
			return $updraft_dir;
	}

	public function settings_debugrow( $head, $content ) {
			echo "<tr class=\"updraft_debugrow\"><th style=\"vertical-align: top; padding-top: 6px;\">$head</th><td>$content</td></tr>";
	}
}
