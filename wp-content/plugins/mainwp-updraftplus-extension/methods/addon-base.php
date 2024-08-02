<?php
if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

/*
  Methods to define when extending this class (can use $this->storage and $this->options where relevant):
  do_bootstrap($possible_options_array) # Return a WP_Error object if something goes wrong
  do_upload($file, $sourcefile) # Return true/false
  do_listfiles($match)
  do_delete($file) - return true/false
  do_download($file, $fullpath, $start_offset) - return true/false
  do_config_print()
  do_config_javascript()
  do_credentials_test_parameters() - return an array: keys = required _POST parameters; values = description of each
  do_credentials_test($testfile) - return true/false
  do_credentials_test_deletefile($testfile)
 */

# Uses job options: Yes
# Uses single-array storage: Yes

class MainWP_Updraft_Plus_RemoteStorage_Addons_Base {

	protected $method;
	protected $description;
	protected $storage;
	protected $options;
	private $chunked;
    private $test_button = false;

	public function __construct( $method, $description, $chunked = true, $test_button = true ) {

			$this->method = $method;
			$this->description = $description;
			$this->chunked = $chunked;
			$this->test_button = $test_button;

			add_action( 'mainwp_updraft_' . $method . '_config_javascript', array( $this, 'config_javascript' ) );
			add_action( 'mainwp_updraft_' . $method . '_credentials_test', array( $this, 'credentials_test' ) );
			add_filter( 'mainwp_updraft_' . $method . '_upload_files', array( $this, 'upload_files' ), 10, 2 );
			add_filter( 'mainwp_updraft_' . $method . '_config_print', array( $this, 'config_print' ) );
		//      add_filter('mainwp_updraft_'.$method."_listfiles", array($this, 'listfiles'), 10, 2);
	}

	protected function required_configuration_keys() {

	}

	public function upload_files( $ret, $backup_array ) {

			global $mainwp_updraftplus, $mainwp_updraftplus_backup;

			$this->options = $this->get_opts();
		if ( ! array( $this->options ) ) {
				$mainwp_updraftplus->log( 'No ' . $this->method . ' settings were found' );
				$mainwp_updraftplus->log( sprintf( __( 'No %s settings were found', 'mainwp-updraftplus-extension' ), $this->description ), 'error' );
				return false;
		}

			$storage = $this->bootstrap();
		if ( is_wp_error( $storage ) ) {
					global $mainwp_updraftplus;
					return $mainwp_updraftplus->log_wp_error( $storage, false, true );
		}

			$this->storage = $storage;

			$updraft_dir = trailingslashit( $mainwp_updraftplus->backups_dir_location() );

		foreach ( $backup_array as $file ) {
				$mainwp_updraftplus->log( $this->method . ' upload ' . (( ! empty( $this->options['ownername'] )) ? '(account owner: ' . $this->options['ownername'] . ')' : '') . ": attempt: $file" );
			try {
				if ( $this->do_upload( $file, $updraft_dir . $file ) ) {
						$mainwp_updraftplus->uploaded_file( $file );
				} else {
						$any_failures = true;
						$mainwp_updraftplus->log( 'ERROR: ' . $this->method . ': Failed to upload file: ' . $file );
						$mainwp_updraftplus->log( __( 'Error', 'mainwp-updraftplus-extension' ) . ': ' . $this->description . ': ' . sprintf( __( 'Failed to upload %s', 'mainwp-updraftplus-extension' ), $file ), 'error' );
				}
			} catch (Exception $e) {
					$any_failures = true;
					$mainwp_updraftplus->log( 'ERROR (' . get_class( $e ) . '): ' . $this->method . ": $file: Failed to upload file: " . $e->getMessage() . ' (code: ' . $e->getCode() . ', line: ' . $e->getLine() . ', file: ' . $e->getFile() . ')' );
					$mainwp_updraftplus->log( __( 'Error', 'mainwp-updraftplus-extension' ) . ': ' . $this->description . ': ' . sprintf( __( 'Failed to upload %s', 'mainwp-updraftplus-extension' ), $file ), 'error' );
			}
		}

			return ( ! empty( $any_failures )) ? null : true;
	}

	public function listfiles( $x, $match = 'backup_' ) {

		try {
				$this->options = $this->get_opts();
			if ( ! $this->options_exist( $this->options ) ) {
					return new WP_Error( 'no_settings', sprintf( __( 'No %s settings were found', 'mainwp-updraftplus-extension' ), $this->description ) ); }

				$this->storage = $this->bootstrap();
			if ( is_wp_error( $this->storage ) ) {
					return $this->storage; }

				return $this->do_listfiles( $match );
		} catch (Exception $e) {
				global $mainwp_updraftplus;
				$mainwp_updraftplus->log( 'ERROR: ' . $this->method . ": $file: Failed to list files: " . $e->getMessage() . ' (code: ' . $e->getCode() . ', line: ' . $e->getLine() . ', file: ' . $e->getFile() . ')' );
				return new WP_Error( 'list_failed', $this->description . ': ' . __( 'failed to list files', 'mainwp-updraftplus-extension' ) );
		}
	}
	protected function get_opts() {
			global $mainwp_updraftplus;
			$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_' . $this->method ); //$mainwp_updraftplus->get_job_option('updraft_'.$this->method);
			return (is_array( $opts )) ? $opts : array();
	}

	public function get_credentials() {
			return array( 'mainwp_updraft_' . $this->method, 'updraft_ssl_disableverify', 'updraft_ssl_nossl', 'updraft_ssl_useservercerts' );
	}

	public function config_print() {

			$this->options = $this->get_opts();
			$method = $this->method;

		if ( $this->chunked ) {
				?>
				<div class="ui grid field  mwp_updraftplusmethod <?php echo $method; ?>">
					<label class="six wide column middle aligned"></label>
                    <div class="ui ten wide column">

                    </div>
                </div>

                <?php
		}
		$this->do_config_print( $this->options );

		if (!$this->test_button || (method_exists($this, 'should_print_test_button') && !$this->should_print_test_button()))
			return;

	}

	protected function do_config_javascript() {

	}

	public function config_javascript() {
        return; // disabled
		if (!$this->test_button || (method_exists($this, 'should_print_test_button') && !$this->should_print_test_button()))
			return;
			?>
			jQuery('#updraft-<?php echo $this->method; ?>-test').click(function(){
				jQuery('#updraft-<?php echo $this->method; ?>-test').html('<?php echo esc_js( sprintf( __( 'Testing %s Settings...', 'mainwp-updraftplus-extension' ), $this->description ) ); ?>');
				var data = {
				action: 'mainwp_updraft_ajax',
				subaction: 'credentials_test',
				method: '<?php echo $this->method; ?>',
				nonce: '<?php echo wp_create_nonce( 'mwp-updraftplus-credentialtest-nonce' ); ?>',
				<?php $this->do_config_javascript(); ?>
				};
				jQuery.post(ajaxurl, data, function(response) {
				jQuery('#updraft-<?php echo $this->method; ?>-test').html('<?php echo esc_js( sprintf( __( 'Test %s Settings', 'mainwp-updraftplus-extension' ), $this->description ) ); ?>');
				alert('<?php echo esc_js( sprintf( __( '%s settings test result:', 'mainwp-updraftplus-extension' ), $this->description ) ); ?> ' + response);
				});
				});
				<?php
	}

	protected function options_exist( $opts ) {
		if ( is_array( $opts ) && ! empty( $opts ) ) {
				return true; }
			return false;
	}

	public function bootstrap( $opts = false, $connect = true ) {
		if ( false === $opts ) {
				$opts = $this->options; }
			#  Be careful of checking empty($opts) here - some storage methods may have no options until the OAuth token has been obtained
		if ( $connect && ! $this->options_exist( $opts ) ) {
				return new WP_Error( 'no_settings', sprintf( __( 'No %s settings were found', 'mainwp-updraftplus-extension' ), $this->description ) ); }
		if ( ! empty( $this->storage ) && ! is_wp_error( $this->storage ) ) {
				return $this->storage; }
			return $this->do_bootstrap( $opts, $connect );
	}

	public function credentials_test() {

		global $mainwp_updraftplus;
		$required_test_parameters = $this->do_credentials_test_parameters();

		foreach ( $required_test_parameters as $param => $descrip ) {
			if ( empty( $_POST[ $param ] ) ) {
					printf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ), $descrip );
					return;
			}
		}

			$this->storage = $this->bootstrap( $_POST );
		if ( is_wp_error( $this->storage ) ) {
				echo __( 'Failed', 'mainwp-updraftplus-extension' ) . ': ';
			foreach ( $this->storage->get_error_messages() as $key => $msg ) {
					echo "$msg\n";
			}
				die;
		}

			$testfile = md5( time() . rand() );
		if ( $this->do_credentials_test( $testfile ) ) {
				_e( 'Success', 'mainwp-updraftplus-extension' );
				$this->do_credentials_test_deletefile( $testfile );
		} else {
				_e( 'Failed: We were not able to place a file in that directory - please check your credentials.', 'mainwp-updraftplus-extension' );
		}

			die;
	}
}
