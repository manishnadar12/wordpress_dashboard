<?php
if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

class MainWP_Updraft_Plus_AddonStorage_viastream {

	public function __construct( $method, $desc ) {
			$this->method = $method;
			$this->desc = $desc;
			add_action( 'mainwp_updraft_' . $method . '_config_javascript', array( $this, 'config_javascript' ) );
			add_action( 'mainwp_updraft_' . $method . '_credentials_test', array( $this, 'credentials_test' ) );
			add_filter( 'mainwp_updraft_' . $method . '_upload_files', array( $this, 'upload_files' ), 10, 2 );
			add_filter( 'mainwp_updraft_' . $method . '_config_print', array( $this, 'config_print' ) );
		//      add_filter('mainwp_updraft_'.$method.'_listfiles', array($this, 'listfiles'), 10, 2);
	}

	public function chunked_upload( $file, $url ) {

			global $mainwp_updraftplus;

			$orig_file_size = filesize( $file );

			$start_offset = 0;
		if ( is_file( $url ) ) {
				$url_size = filesize( $url );
			if ( $url_size == $orig_file_size ) {
					$mainwp_updraftplus->log( $this->desc . ': This file has already been successfully uploaded' );
						return true;
			} elseif ( $url_size > $orig_file_size ) {
					$mainwp_updraftplus->log( $this->desc . ": A larger file than expected ($url_size > $orig_file_size) already exists" );
					return false;
			}
				$mainwp_updraftplus->log( $this->desc . ": $url_size bytes already uploaded; resuming" );
				$start_offset = $url_size;
		}

			$chunks = floor( $orig_file_size / 2097152 );
			// There will be a remnant unless the file size was exactly on a 5Mb boundary
		if ( $orig_file_size % 2097152 > 0 ) {
				$chunks++; }

		if ( ! $fh = fopen( $url, 'a' ) ) {
				$mainwp_updraftplus->log( $this->desc . ': Failed to open remote file' );
				return false;
		}
		if ( ! $rh = fopen( $file, 'rb' ) ) {
				$mainwp_updraftplus->log( $this->desc . ': Failed to open local file' );
				return false;
		}

			# A hack, to pass information to a modified version of the PEAR library
		if ( 'webdav' == $this->method ) {
				global $mainwp_updraftplus_webdav_filepath;
				$updraftplus_webdav_filepath = $file;
		}

			$last_time = time();
		for ( $i = 1; $i <= $chunks; $i++ ) {

				$chunk_start = ($i - 1) * 2097152;
				$chunk_end = min( $i * 2097152 - 1, $orig_file_size );

			if ( $start_offset > $chunk_end ) {
					$mainwp_updraftplus->log( $this->desc . ": Chunk $i: Already uploaded" );
			} else {

					fseek( $fh, $chunk_start );
					fseek( $rh, $chunk_start );

					$bytes_left = $chunk_end - $chunk_start;
				while ( $bytes_left > 0 ) {
					if ( $buf = fread( $rh, 131072 ) ) {
						if ( fwrite( $fh, $buf, strlen( $buf ) ) ) {
								$bytes_left = $bytes_left - strlen( $buf );
							if ( time() - $last_time > 15 ) {
									$last_time = time();
									touch( $file );
							}
						} else {
								$mainwp_updraftplus->log( $this->desc . ': ' . sprintf( __( 'Chunk %s: A %s error occurred', 'mainwp-updraftplus-extension' ), $i, 'write' ), 'error' );
								return false;
						}
					} else {
							$mainwp_updraftplus->log( $this->desc . ': ' . sprintf( __( 'Chunk %s: A %s error occurred', 'mainwp-updraftplus-extension' ), $i, 'read' ), 'error' );
							return false;
					}
				}
			}

				$mainwp_updraftplus->record_uploaded_chunk( round( 100 * $i / $chunks, 1 ), "$i", $file );
		}

			// N.B. fclose() always returns true for stream wrappers - stream wrappers' return values are ignored - http://php.net/manual/en/streamwrapper.stream-close.php (29-Jan-2015)
		try {
			if ( ! fclose( $fh ) ) {
					$mainwp_updraftplus->log( $this->desc . ': Upload failed (fclose error)' );
					$mainwp_updraftplus->log( $this->desc . ' ' . __( 'Upload failed', 'mainwp-updraftplus-extension' ), 'error' );
					return false;
			}
		} catch (Exception $e) {
				$mainwp_updraftplus->log( $this->desc . ': Upload failed (fclose exception; class=' . get_class( $e ) . '): ' . $e->getMessage() );
				$mainwp_updraftplus->log( $this->desc . ' ' . __( 'Upload failed', 'mainwp-updraftplus-extension' ), 'error' );
				return false;
		}
			fclose( $rh );

			return true;
	}

	public function listfiles( $x, $match = 'backup_' ) {

			$storage = $this->bootstrap();
		if ( is_wp_error( $storage ) ) {
				return $storage; }

			$options = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_' . $this->method . '_settings' );
		if ( ! array( $options ) || empty( $options['url'] ) ) {
				return new WP_Error( 'no_settings', sprintf( __( 'No %s settings were found', 'mainwp-updraftplus-extension' ), $this->desc ) ); }

			$url = trailingslashit( $options['url'] );

		if ( false == ($handle = opendir( $url )) ) {
				return new WP_Error( 'no_access', sprintf( 'Failed to gain %s access', $this->desc ) ); }

			$results = array();

		while ( false !== ($entry = readdir( $handle )) ) {
			if ( is_file( $url . $entry ) && 0 === strpos( $entry, $match ) ) {
					$results[] = array( 'name' => $entry, 'size' => filesize( $url . $entry ) );
			}
		}

			return $results;
	}

	public function upload_files( $ret, $backup_array ) {

			global $mainwp_updraftplus;

			$storage = $this->bootstrap();

		if ( is_wp_error( $storage ) ) {
			foreach ( $storage->get_error_messages() as $key => $msg ) {
					$mainwp_updraftplus->log( $msg );
					$mainwp_updraftplus->log( $msg, 'error' );
			}
				return false;
		}

			$options = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_' . $this->method . '_settings' );
		if ( ! array( $options ) || ! isset( $options['url'] ) ) {
				$mainwp_updraftplus->log( 'No ' . $this->desc . ' settings were found' );
				$mainwp_updraftplus->log( sprintf( __( 'No %s settings were found', 'mainwp-updraftplus-extension' ), $this->desc ), 'error' );
				return false;
		}

			$any_failures = false;

			$updraft_dir = untrailingslashit( $mainwp_updraftplus->backups_dir_location() );
			$url = untrailingslashit( $options['url'] );

		foreach ( $backup_array as $file ) {
				$mainwp_updraftplus->log( $this->desc . " upload: attempt: $file" );
			if ( $this->chunked_upload( $updraft_dir . '/' . $file, $url . '/' . $file ) ) {
					$mainwp_updraftplus->uploaded_file( $file );
			} else {
					$any_failures = true;
					$mainwp_updraftplus->log( 'ERROR: ' . $this->desc . ': Failed to upload file: ' . $file );
					$mainwp_updraftplus->log( __( 'Error', 'mainwp-updraftplus-extension' ) . ': ' . $this->desc . ': ' . sprintf( __( 'Failed to upload to %s', 'mainwp-updraftplus-extension' ), $file ), 'error' );
			}
		}

			return ($any_failures) ? null : array( 'url' => $url );
	}

	public function config_javascript() {
        return; // disabled
			?>
			jQuery('#updraft-<?php echo $this->method; ?>-test').click(function(){
				jQuery('#updraft-<?php echo $this->method; ?>-test').html('<?php echo esc_js( sprintf( __( 'Testing %s Settings...', 'mainwp-updraftplus-extension' ), $this->desc ) ); ?>');
				var data = {
				action: 'mainwp_updraft_ajax',
				subaction: 'credentials_test',
				method: '<?php echo $this->method; ?>',
				nonce: '<?php echo wp_create_nonce( 'mwp-updraftplus-credentialtest-nonce' ); ?>',
				url: jQuery('#updraft_<?php echo $this->method; ?>_settings_url').val()
				};
				jQuery.post(ajaxurl, data, function(response) {
				jQuery('#updraft-<?php echo $this->method; ?>-test').html('<?php echo esc_js( sprintf( __( 'Test %s Settings', 'mainwp-updraftplus-extension' ), $this->desc ) ); ?>');
				alert('<?php echo esc_js( sprintf( __( '%s settings test result:', 'mainwp-updraftplus-extension' ), $this->desc ) ); ?> ' + response);
				});
				});
				<?php
	}

	public function config_print() {

			$options = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_' . $this->method . '_settings' );
			$url = isset( $options['url'] ) ? htmlspecialchars( $options['url'] ) : '';
			?>
			<div class="ui grid field mwp_updraftplusmethod <?php echo $this->method; ?>">
				<label class="six wide column middle aligned">
                    <h4 class="ui header">
                    </h4>
                </label>
                <div class="ui ten wide column">

                </div>

				</div>

				<?php $this->config_print_middlesection( $url ); ?>

				<?php
	}

	public function credentials_test_go( $url ) {

			$storage = $this->bootstrap();

		if ( is_wp_error( $storage ) || true !== $storage ) {
				echo __( 'Failed', 'mainwp-updraftplus-extension' ) . ': ';
			foreach ( $storage->get_error_messages() as $key => $msg ) {
					echo "$msg\n";
			}
				die;
		}

			$x = @mkdir( $url );

			$testfile = $url . '/' . md5( time() . rand() );
		if ( file_put_contents( $testfile, 'test' ) ) {
				_e( 'Success', 'mainwp-updraftplus-extension' );
				@unlink( $testfile );
		} else {
				_e( 'Failed: We were not able to place a file in that directory - please check your credentials.', 'mainwp-updraftplus-extension' );
		}

			die;
	}
}
