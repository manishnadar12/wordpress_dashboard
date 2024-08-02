<?php
/*
  UpdraftPlus Addon: reporting:Sophisticated reporting options
  Description: Provides various new reporting capabilities
  Version: 1.7
  Shop: /shop/reporting/
  Latest Change: 1.9.32
 */

# Future possibility: more reporting options; e.g. HTTP ping; tweet, etc.

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

$mainwp_updraft_plus_addon_reporting = new MainWP_Updraft_Plus_Addon_Reporting;

class MainWP_Updraft_Plus_Addon_Reporting {

	private $emails;
	private $warningsonly;
	private $history;
	private $syslog;

	public function __construct() {
			add_filter( 'mainwp_updraftplus_showbackup_date', array( $this, 'showbackup_date' ), 10, 2 );
			add_filter( 'mainwp_updraftplus_report_form', array( $this, 'updraftplus_report_form' ) );
			add_filter( 'mainwp_updraftplus_saveemails', array( $this, 'saveemails' ), 10, 2 );
			add_filter( 'mainwp_updraftplus_email_whichaddresses', array( $this, 'email_whichaddresses' ) );
	}

	public function showbackup_date( $date, $backup ) {
		if ( ! is_array( $backup ) || empty( $backup['label'] ) ) {
				return $date; }
			return $date . '<br>' . htmlspecialchars( $backup['label'] );
	}

	public function logline( $line, $nonce, $level ) {
			# See http://php.net/manual/en/function.syslog.php for descriptions of the log level meanings
		if ( 'error' == $level ) {
				$pri = LOG_WARNING;
		} elseif ( 'warning' == $level ) {
				$pri = LOG_NOTICE;
		} else {
				$pri = LOG_INFO;
		}
			@syslog( $pri, "($nonce) $line" );
	}


	public function wp_mail_content_type( $content_type ) {
			// Only convert if the message is text/plain and the template is ok
		if ( 'text/plain' == $content_type && ! empty( $this->html ) ) {
			if ( empty( $this->added_phpmailer_init_action ) ) {
					$this->added_phpmailer_init_action = true;
					add_action( 'phpmailer_init', array( $this, 'phpmailer_init' ) );
			}
				return 'text/html';
		}
			return $content_type;
	}

	public function phpmailer_init( $phpmailer ) {
		if ( empty( $this->html ) ) {
				return; }
			$phpmailer->AltBody = wp_specialchars_decode( $phpmailer->Body, ENT_QUOTES );
			$phpmailer->Body = $this->html;
	}

	public function email_whichaddresses( $blurb ) {
			return __( 'Use the "Reporting" section to configure the email addresses to be used.', 'mainwp-updraftplus-extension' );
	}

	public function admin_footer() {
			?>
			<script>
					jQuery(document).ready(function () {
						jQuery('#mwp_updraft_report_another').click(function (e) {
							e.preventDefault();

							var ind = jQuery('#updraft_report_cell .updraft_reportbox').length + 2;
							var showemail = 1;

							jQuery('#mwp_updraft_report_another_p').before('<div id="updraft_reportbox_' + ind + '" class="updraft_reportbox" style="padding:8px; margin: 8px 0; border: 1px dotted; clear:left;float:left;"><button onclick="jQuery(\'#updraft_reportbox_' + ind + '\').fadeOut().remove();" type="button" style="font-size: 50%; float:right; padding:0 3px; position: relative; top: -4px; left: 4px;">X</button><input type="text" title="' + mwp_updraftlion.enteremailhere + '" style="width:300px" name="mwp_updraft_email[' + ind + ']" value="" /><br><input style="margin-top: 4px;" type="checkbox" id="updraft_report_warningsonly_' + ind + '" name="mwp_updraft_report_warningsonly[' + ind + ']"><label for="updraft_report_warningsonly_' + ind + '">' + mwp_updraftlion.sendonlyonwarnings + '</label><br><div class="updraft_report_wholebackup" style="' + ((showemail) ? '' : 'display:none;') + '">\
		<input style="margin-top: 4px;" type="checkbox" id="updraft_report_wholebackup_' + ind + '" name="mwp_updraft_report_wholebackup[' + ind + ']" title="' + mwp_updraftlion.emailsizelimits + '"><label for="updraft_report_wholebackup_' + ind + '" title="' + mwp_updraftlion.emailsizelimits + '">' + mwp_updraftlion.wholebackup + '</label></div></div>');

						});
					});
			</script>
			<?php
	}

	private function printfile( $description, $history, $entity, $checksums, $jobdata ) {

		if ( empty( $history[ $entity ] ) ) {
				return; }

			echo '<h3>' . $description . ' (' . sprintf( __( 'files: %s', 'mainwp-updraftplus-extension' ), count( $history[ $entity ] ) ) . ")</h3>\n\n";

			$pfiles = '<ul>';
			$files = $history[ $entity ];
		if ( is_string( $files ) ) {
				$files = array( $files ); }

		foreach ( $files as $ind => $file ) {

				$op = htmlspecialchars( $file ) . "\n";
				$skey = $entity . ((0 == $ind) ? '' : $ind + 1) . '-size';
				$meta = '';
			if ( 'db' == substr( $entity, 0, 2 ) && 'db' != $entity ) {
					$dind = substr( $entity, 2 );
				if ( is_array( $jobdata ) && ! empty( $jobdata['backup_database'] ) && is_array( $jobdata['backup_database'] ) && ! empty( $jobdata['backup_database'][ $dind ] ) && is_array( $jobdata['backup_database'][ $dind ]['dbinfo'] ) && ! empty( $jobdata['backup_database'][ $dind ]['dbinfo']['host'] ) ) {
						$dbinfo = $jobdata['backup_database'][ $dind ]['dbinfo'];
						$meta .= sprintf( __( 'External database (%s)', 'mainwp-updraftplus-extension' ), $dbinfo['user'] . '@' . $dbinfo['host'] . '/' . $dbinfo['name'] ) . '<br>';
				}
			}
			if ( isset( $history[ $skey ] ) ) {
					$meta .= sprintf( __( 'Size: %s Mb', 'mainwp-updraftplus-extension' ), round( $history[ $skey ] / 1048576, 1 ) ); }
				$ckey = $entity . $ind;
			foreach ( $checksums as $ck ) {
					$ck_plain = false;
				if ( isset( $history['checksums'][ $ck ][ $ckey ] ) ) {
						$meta .= (($meta) ? ', ' : '') . sprintf( __( '%s checksum: %s', 'mainwp-updraftplus-extension' ), strtoupper( $ck ), $history['checksums'][ $ck ][ $ckey ] );
						$ck_plain = true;
				}
				if ( isset( $history['checksums'][ $ck ][ $ckey . '.crypt' ] ) ) {
					if ( $ck_plain ) {
							$meta .= ' ' . __( '(when decrypted)' ); }
						$meta .= (($meta) ? ', ' : '') . sprintf( __( '%s checksum: %s', 'mainwp-updraftplus-extension' ), strtoupper( $ck ), $history['checksums'][ $ck ][ $ckey . '.crypt' ] );
				}
			}

				$fileinfo = apply_filters( 'mainwp_updraftplus_fileinfo_$entity', array(), $ind );
			if ( is_array( $fileinfo ) && ! empty( $fileinfo ) ) {
				if ( isset( $fileinfo['html'] ) ) {
						$meta .= $fileinfo['html'];
				}
			}

				#if ($meta) $meta = " ($meta)";
			if ( $meta ) {
					$meta = "<br><em>$meta</em>"; }
				$pfiles .= '<li>' . $op . $meta . "\n</li>\n";
		}

			$pfiles .= "</ul>\n";

			return $pfiles;
	}

	public function updraftplus_report_form( $in ) {

			add_action( 'admin_footer', array( $this, 'admin_footer' ) );

			# Columns: Email address | only send if no errors/warnings

			$out = '<div class="ui grid field">
				<label class="six wide column middle aligned">' . __( 'Email reports', 'mainwp-updraftplus-extension' ) . '</label>
				<div class="ten wide column" id="updraft_report_cell">';

			# Could be multiple (separated by commas)
			$updraft_email = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_email' );
			$updraft_report_warningsonly = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_report_warningsonly' );
			$updraft_report_wholebackup = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_report_wholebackup' );

		if ( is_string( $updraft_email ) ) {
				$utmp = $updraft_email;
				$updraft_email = array();
				$updraft_report_warningsonly = array();
				$updraft_report_wholebackup = array();
			foreach ( explode( ',', $utmp ) as $email ) {
					# Whole backup only takes effect if 'Email' is chosen as a storage option
					$updraft_email[] = $email;
					$updraft_report_warningsonly[] = false;
					$updraft_report_wholebackup[] = true;
			}
		} elseif ( ! is_array( $updraft_email ) ) {
				$updraft_email = array();
				$updraft_report_warningsonly = array();
				$updraft_report_wholebackup = array();
		}

			$ind = 0;

			$out .= '<p>' . __( 'Enter addresses here to have a report sent to them when a backup job finishes.', 'mainwp-updraftplus-extension' ) . '</p>';

		foreach ( $updraft_email as $ikey => $destination ) {
				$warningsonly = (empty( $updraft_report_warningsonly[ $ikey ] )) ? false : true;
				$wholebackup = (empty( $updraft_report_wholebackup[ $ikey ] )) ? false : true;
			if ( ! empty( $destination ) ) {
					$ind++;
					$out .= $this->report_box_generator( $destination, $ind, $warningsonly, $wholebackup );
			}
		}

		if ( 0 === $ind ) {
				$out .= $this->report_box_generator( '', 0, false, false ); }

			$out .= '<p id="mwp_updraft_report_another_p" style="clear:left;"><a id="mwp_updraft_report_another" href="#updraft_report_row">' . __( 'Add another address...', 'mainwp-updraftplus-extension' ) . '</a></p>';

			$out .= '</div></div>';

			$out .= '<div class="ui grid field">
				<label class="six wide column middle aligned"></label>
				<div class="ten wide column ui toggle checkbox" id="">';
			$out .= '<input type="checkbox" value="1" ' . ((MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_log_syslog', false )) ? 'checked="checked"' : '') . ' name="mwp_updraft_log_syslog" id="updraft_log_syslog">';
			$out .= '<label for="updraft_log_syslog">' . __( 'Log all messages to syslog (only server admins are likely to want this)', 'mainwp-updraftplus-extension' ) . '</label>';
			
			$out .= '</div></div>';

			return $out;
	}

	public function saveemails( $rinput, $input ) {
			return $input;
	}

	private function report_box_generator( $addr, $ind, $warningsonly, $wholebackup ) {

			$out = '';

			$out .= '<div id="updraft_reportbox_' . $ind . '" class="updraft_reportbox" style="padding:8px; margin: 8px 0; border: 1px dotted; clear:left;float:left;">';

			$out .= '<button onclick="jQuery(\'#updraft_reportbox_' . $ind . '\').fadeOut().remove();" type="button" style="font-size: 50%; float:right; padding:0 3px; position: relative; top: -4px; left: 4px;">X</button>';

			$out .= '<input type="text" title="' . esc_attr( __( 'To send to more than one address, separate each address with a comma.', 'mainwp-updraftplus-extension' ) ) . '" style="width:300px" name="mwp_updraft_email[' . $ind . ']" value="' . esc_attr( $addr ) . '" /><br>';

			$out .= '<input ' . (($warningsonly) ? 'checked="checked" ' : '') . 'style="margin-top: 4px;" type="checkbox" id="updraft_report_warningsonly_' . $ind . '" name="mwp_updraft_report_warningsonly[' . $ind . ']"><label for="updraft_report_warningsonly_' . $ind . '"> ' . __( 'Send a report only when there are warnings/errors', 'mainwp-updraftplus-extension' ) . '</label><br>';

			$out .= '<div class="updraft_report_wholebackup"><input ' . (($wholebackup) ? 'checked="checked" ' : '') . 'style="margin-top: 4px;" type="checkbox" id="updraft_report_wholebackup_' . $ind . '" name="mwp_updraft_report_wholebackup[' . $ind . ']" title="' . esc_attr( sprintf( __( 'Be aware that mail servers tend to have size limits; typically around %s Mb; backups larger than any limits will likely not arrive.', 'mainwp-updraftplus-extension' ), '10-20' ) ) . '"><label for="updraft_report_wholebackup_' . $ind . '" title="' . esc_attr( sprintf( __( 'Be aware that mail servers tend to have size limits; typically around %s Mb; backups larger than any limits will likely not arrive.', 'mainwp-updraftplus-extension' ), '10-20' ) ) . '"> ' . __( 'When the Email storage method is enabled, also send the entire backup', 'mainwp-updraftplus-extension' ) . '</label></div>';

			$out .= '</div>';

			return $out;
	}
}
