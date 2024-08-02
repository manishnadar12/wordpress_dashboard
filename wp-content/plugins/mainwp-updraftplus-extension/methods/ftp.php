<?php
if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed.' ); }

# Converted to array options: yes
# Converted to job_options: yes
# Migrate options to new-style storage - May 2014

class MainWP_Updraft_Plus_BackupModule_ftp {

		// Get FTP object with parameters set
	private function get_ftp( $server, $user, $pass, $disable_ssl = false, $disable_verify = true, $use_server_certs = false, $passive = true ) {

		if ( empty( $server ) || empty( $user ) || empty( $pass ) ) {
				return new WP_Error( 'no_settings', sprintf( __( 'No %s settings were found', 'mainwp-updraftplus-extension' ), 'FTP' ) ); }

		if ( ! class_exists( 'MainWP_Updraft_Plus_ftp_wrapper' ) ) {
				require_once( MAINWP_UPDRAFT_PLUS_DIR . '/includes/ftp.class.php' ); }

			$port = 21;
		if ( preg_match( '/^(.*):(\d+)$/', $server, $matches ) ) {
				$server = $matches[1];
				$port = $matches[2];
		}

			$ftp = new MainWP_Updraft_Plus_ftp_wrapper( $server, $user, $pass, $port );

		if ( $disable_ssl ) {
				$ftp->ssl = false; }
			$ftp->use_server_certs = $use_server_certs;
			$ftp->disable_verify = $disable_verify;
		if ( $passive ) {
				$ftp->passive = true; }

			return $ftp;
	}

	private function get_opts() {
			global $mainwp_updraftplus;
			$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_ftp' ); // $mainwp_updraftplus->get_job_option('updraft_ftp');
		if ( ! is_array( $opts ) ) {
				$opts = array(); }
		if ( empty( $opts['host'] ) ) {
				$opts['host'] = ''; }
		if ( empty( $opts['user'] ) ) {
				$opts['user'] = ''; }
		if ( empty( $opts['pass'] ) ) {
				$opts['pass'] = ''; }
		if ( empty( $opts['path'] ) ) {
				$opts['path'] = ''; }
			return $opts;
	}

	public function backup( $backup_array ) {

	}

	public function listfiles( $match = 'backup_' ) {

	}

	public function delete( $files, $ftparr = array() ) {

	}

	public function download( $file ) {

	}

	public function config_print_javascript_onready() {
        return; // disabled
			?>
			jQuery('#updraft-ftp-test').click(function(){
			jQuery('#updraft-ftp-test').html('<?php echo esc_js( sprintf( __( 'Testing %s Settings...', 'mainwp-updraftplus-extension' ), 'FTP' ) ); ?>');
				var data = {
				action: 'mainwp_updraft_ajax',
				subaction: 'credentials_test',
				method: 'ftp',
				nonce: '<?php echo wp_create_nonce( 'mwp-updraftplus-credentialtest-nonce' ); ?>',
				server: jQuery('#updraft_ftp_host').val(),
				login: jQuery('#updraft_ftp_user').val(),
				pass: jQuery('#updraft_ftp_pass').val(),
				path: jQuery('#updraft_ftp_path').val(),
				passive: (jQuery('#updraft_ftp_passive').is(':checked')) ? 1 : 0,
				disableverify: (jQuery('#updraft_ssl_disableverify').is(':checked')) ? 1 : 0,
				useservercerts: (jQuery('#updraft_ssl_useservercerts').is(':checked')) ? 1 : 0,
				nossl: (jQuery('#updraft_ssl_nossl').is(':checked')) ? 1 : 0,
				};
				jQuery.post(ajaxurl, data, function(response) {
				jQuery('#updraft-ftp-test').html('<?php echo esc_js( sprintf( __( 'Test %s Settings', 'mainwp-updraftplus-extension' ), 'FTP' ) ); ?>');
				alert('<?php echo esc_js( sprintf( __( '%s settings test result:', 'mainwp-updraftplus-extension' ), 'FTP' ) ); ?> ' + response);

				});
				});
				<?php
	}

	private function ftp_possible() {
			$funcs_disabled = array();
		foreach ( array( 'ftp_connect', 'ftp_login', 'ftp_nb_fput' ) as $func ) {
			if ( ! function_exists( $func ) ) {
					$funcs_disabled['ftp'][] = $func; }
		}
			$funcs_disabled = apply_filters( 'mainwp_updraftplus_ftp_possible', $funcs_disabled );
			return (0 == count( $funcs_disabled )) ? true : $funcs_disabled;
	}

	public function config_print() {
			global $mainwp_updraftplus;

			$possible = $this->ftp_possible();
            $opts = $this->get_opts();
            ?>

                 <div class="ui grid field mwp_updraftplusmethod ftp">
                    <label class="six wide column middle aligned"></label>
                    <div class="ui ten wide column">
                    <?php
                        if ( is_array( $possible ) ) {
                            // Check requirements.
                            global $mainwp_updraftplus_admin;
                            $trans = array(
                                'ftp' => __( 'regular non-encrypted FTP', 'mainwp-updraftplus-extension' ),
                                'ftpsslimplicit' => __( 'encrypted FTP (implicit encryption)', 'mainwp-updraftplus-extension' ),
                                'ftpsslexplicit' => __( 'encrypted FTP (explicit encryption)', 'mainwp-updraftplus-extension' ),
                            );
                            foreach ( $possible as $type => $missing ) {
                                    //$mainwp_updraftplus_admin->show_double_warning('<strong>'.__('Warning','mainwp-updraftplus-extension').':</strong> '. sprintf(__("Your web server's PHP installation has these functions disabled: %s.", 'mainwp-updraftplus-extension'), implode(', ', $missing)).' '.sprintf(__('Your hosting company must enable these functions before %s can work.', 'mainwp-updraftplus-extension'), $trans[$type]), 'ftp');
                            }
                        }

                    ?>

                    <div class="ui hidden fitted divider"></div>
                    <em><?php echo apply_filters( 'mainwp_updraft_sftp_ftps_notice', '<strong>' . htmlspecialchars( __( 'Only non-encrypted FTP is supported by regular UpdraftPlus.' ) ) . '</strong> <a href="http://updraftplus.com/shop/sftp/">' . __( 'If you want encryption (e.g. you are storing sensitive business data), then an add-on is available.', 'mainwp-updraftplus-extension' ) ) . '</a>'; ?></em>
                    <div class="ui hidden fitted divider"></div>
                    <?php _e( 'FTP Server', 'mainwp-updraftplus-extension' ); ?>:
                    <div class="ui hidden fitted divider"></div>
                    <input type="text" size="40" id="updraft_ftp_host" name="mwp_updraft_ftp[host]" value="<?php echo htmlspecialchars( $opts['host'] ); ?>" />
                    <div class="ui hidden divider"></div>
                    <?php _e( 'FTP Login', 'mainwp-updraftplus-extension' ); ?>:
                    <div class="ui hidden fitted divider"></div>
                    <input type="text" size="40" id="updraft_ftp_user" name="mwp_updraft_ftp[user]" value="<?php echo htmlspecialchars( $opts['user'] ) ?>" />
                    <div class="ui hidden divider"></div>
                    <?php _e( 'FTP Password', 'mainwp-updraftplus-extension' ); ?>:
                    <div class="ui hidden fitted divider"></div>
                    <input type="<?php echo apply_filters( 'mainwp_updraftplus_admin_secret_field_type', 'password' ); ?>" size="40" id="updraft_ftp_pass" name="mwp_updraft_ftp[pass]" value="<?php echo htmlspecialchars( $opts['pass'] ); ?>" />
                    <div class="ui hidden divider"></div>
                    <?php _e( 'Remote Path', 'mainwp-updraftplus-extension' ); ?>:
                    <div class="ui hidden fitted divider"></div>
                    <input type="text" size="64" id="updraft_ftp_path" name="mwp_updraft_ftp[path]" value="<?php echo htmlspecialchars( $opts['path'] ); ?>" /> <em><?php _e( 'Needs to already exist', 'mainwp-updraftplus-extension' ); ?></em>
                    <br><em><?php _e('Supported tokens', 'mainwp-updraftplus-extension') ?>: %sitename%, %siteurl%</em>
                    <div class="ui hidden divider"></div>
                    <?php _e( 'Passive mode', 'mainwp-updraftplus-extension' ); ?>:
                    <div class="ui hidden fitted divider"></div>
                    <input type="hidden" name="mwp_updraft_ftp[passive]" value="0" /> <!-- provide an alternating value -->
					<input type="checkbox" id="updraft_ftp_passive" name="mwp_updraft_ftp[passive]" value="1" <?php if ( $opts['passive'] ) { echo 'checked="checked"'; } ?> />
                    <br><em><?php echo __( 'Almost all FTP servers will want passive mode; but if you need active mode, then uncheck this.', 'mainwp-updraftplus-extension' ); ?></em>
                    <div class="ui hidden divider"></div>
                    </div>
                </div>
            <?php

	}

	public function get_credentials() {
			return array( 'updraft_ftp', 'updraft_ssl_disableverify', 'updraft_ssl_nossl', 'updraft_ssl_useservercerts' );
	}

	public function credentials_test() {

			$server = $_POST['server'];
			$login = stripslashes( $_POST['login'] );
			$pass = stripslashes( $_POST['pass'] );
			$path = $_POST['path'];
			$nossl = $_POST['nossl'];
			$passive = empty( $_POST['passive'] ) ? false : true;

			$disable_verify = $_POST['disableverify'];
			$use_server_certs = $_POST['useservercerts'];

		if ( empty( $server ) ) {
				_e( 'Failure: No server details were given.', 'mainwp-updraftplus-extension' );
				return;
		}
		if ( empty( $login ) ) {
				printf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ), 'login' );
				return;
		}
		if ( empty( $pass ) ) {
				printf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ), 'password' );
				return;
		}

		if ( preg_match( '#ftp(es|s)?://(.*)#i', $server, $matches ) ) {
				$server = untrailingslashit( $matches[2] ); }

			$ftp = $this->get_ftp( $server, $login, $pass, $nossl, $disable_verify, $use_server_certs, $passive );

		if ( ! $ftp->connect() ) {
				_e( 'Failure: we did not successfully log in with those credentials.', 'mainwp-updraftplus-extension' );
				return;
		}
			//$ftp->make_dir(); we may need to recursively create dirs? TODO

			$file = md5( rand( 0, 99999999 ) ) . '.tmp';
			$fullpath = trailingslashit( $path ) . $file;
		if ( ! file_exists( ABSPATH . WPINC . '/version.php' ) ) {
				_e( 'Failure: an unexpected internal UpdraftPlus error occurred when testing the credentials - please contact the developer' );
				return;
		}
		if ( $ftp->put( ABSPATH . WPINC . '/version.php', $fullpath, FTP_BINARY, false, true ) ) {
				echo __( 'Success: we successfully logged in, and confirmed our ability to create a file in the given directory (login type:', 'mainwp-updraftplus-extension' ) . ' ' . $ftp->login_type . ')';
				@$ftp->delete( $fullpath );
		} else {
				_e( 'Failure: we successfully logged in, but were not able to create a file in the given directory.' );
		}
	}
}
