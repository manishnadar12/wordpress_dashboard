<?php

class MainWP_Updraft_Plus_BackupModule_openstack_base {

	protected $chunk_size = 5242880;
	protected $client;
	protected $method;
	protected $desc;
	protected $long_desc;
	protected $img_url;

	public function __construct( $method, $desc, $long_desc = null, $img_url = '' ) {
			$this->method = $method;
			$this->desc = $desc;
			$this->long_desc = (is_string( $long_desc )) ? $long_desc : $desc;
			$this->img_url = $img_url;
	}

	public function backup( $backup_array ) {

	}

	private function get_remote_size( $file ) {
		try {
				$response = $this->container_object->getClient()->head( $this->container_object->getUrl( $file ) )->send();
				$response_object = $this->container_object->dataObject()->populateFromResponse( $response )->setName( $file );
				return $response_object->getContentLength();
		} catch (Exception $e) {
				# Allow caller to distinguish between zero-sized and not-found
				return false;
		}
	}

	public function listfiles( $match = 'backup_' ) {

	}

	public function chunked_upload_finish( $file ) {

			$chunk_path = 'chunk-do-not-delete-' . $file;
		try {

				$headers = array(
					'Content-Length' => 0,
					'X-Object-Manifest' => sprintf('%s/%s', $this->container, $chunk_path . '_'
					),
				);

				$url = $this->container_object->getUrl( $file );
				$this->container_object->getClient()->put( $url, $headers )->send();
				return true;
		} catch (Exception $e) {
				return false;
		}
	}

	public function chunked_upload( $file, $fp, $i, $upload_size, $upload_start, $upload_end ) {

			global $mainwp_updraftplus;

			$upload_remotepath = 'chunk-do-not-delete-' . $file . '_' . $i;

			$remote_size = $this->get_remote_size( $upload_remotepath );

			// Without this, some versions of Curl add Expect: 100-continue, which results in Curl then giving this back: curl error: 55) select/poll returned error
			// Didn't make the difference - instead we just check below for actual success even when Curl reports an error
			// $chunk_object->headers = array('Expect' => '');

		if ( $remote_size >= $upload_size ) {
				$mainwp_updraftplus->log( $this->desc . ": Chunk $i ($upload_start - $upload_end): already uploaded" );
		} else {
				$mainwp_updraftplus->log( $this->desc . ": Chunk $i ($upload_start - $upload_end): begin upload" );
				// Upload the chunk
			try {
					$data = fread( $fp, $upload_size );
					$this->container_object->uploadObject( $upload_remotepath, $data );
			} catch (Exception $e) {
					$mainwp_updraftplus->log( $this->desc . " chunk upload: error: ($file / $i) (" . $e->getMessage() . ') (line: ' . $e->getLine() . ', file: ' . $e->getFile() . ')' );
					// Experience shows that Curl sometimes returns a select/poll error (curl error 55) even when everything succeeded. Google seems to indicate that this is a known bug.

					$remote_size = $this->get_remote_size( $upload_remotepath );

				if ( $remote_size >= $upload_size ) {
						$mainwp_updraftplus->log( "$file: Chunk now exists; ignoring error (presuming it was an apparently known curl bug)" );
				} else {
						$mainwp_updraftplus->log( "$file: " . sprintf( __( '%s Error: Failed to upload', 'mainwp-updraftplus-extension' ), $this->desc ), 'error' );
						return false;
				}
			}
		}
			return true;
	}

	public function delete( $files, $data = false ) {

	}

	public function config_print_javascript_onready( $keys = array() ) {
        return; // disabled
			?>
			jQuery('#updraft-<?php echo $this->method; ?>-test').click(function(){
				jQuery(this).html('<?php echo esc_js( __( 'Testing - Please Wait...', 'mainwp-updraftplus-extension' ) ); ?>');
				var data = {
				action: 'mainwp_updraft_ajax',
				subaction: 'credentials_test',
				method: '<?php echo $this->method; ?>',
				nonce: '<?php echo wp_create_nonce( 'mwp-updraftplus-credentialtest-nonce' ); ?>',
				path: jQuery('#updraft_<?php echo $this->method; ?>_path').val(),
				<?php
				foreach ( $keys as $key ) {
						echo "\t\t\t\t$key: jQuery('#updraft_" . $this->method . "_$key').val(),\n";
				}
				?>
				useservercerts: jQuery('#updraft_ssl_useservercerts').val(),
				disableverify: jQuery('#updraft_ssl_disableverify').val()
				};
				jQuery.post(ajaxurl, data, function(response) {
				jQuery('#updraft-<?php echo $this->method; ?>-test').html('<?php echo esc_js( sprintf( __( 'Test %s Settings', 'mainwp-updraftplus-extension' ), $this->desc ) ); ?>');
				alert('<?php echo esc_js( sprintf( __( '%s settings test result:', 'mainwp-updraftplus-extension' ), $this->desc ) ); ?> ' + response);
				});
				});
				<?php
	}

	public function download( $file ) {

	}

	public function chunked_download( $file, $headers, $container_object ) {

	}

	public function credentials_test_go( $opts, $path, $useservercerts, $disableverify ) {

		if ( preg_match( '#^([^/]+)/(.*)$#', $path, $bmatches ) ) {
				$container = $bmatches[1];
				$path = $bmatches[2];
		} else {
				$container = $path;
				$path = '';
		}

		if ( empty( $container ) ) {
				_e( 'Failure: No container details were given.', 'mainwp-updraftplus-extension' );
				return;
		}

		try {
				$service = $this->get_service( $opts, $useservercerts, $disableverify );
		} catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
				$response = $e->getResponse();
				$code = $response->getStatusCode();
				$reason = $response->getReasonPhrase();
			if ( 401 == $code && 'Unauthorized' == $reason ) {
					echo __( 'Authorisation failed (check your credentials)', 'mainwp-updraftplus-extension' );
			} else {
					echo __( 'Authorisation failed (check your credentials)', 'mainwp-updraftplus-extension' ) . " ($code:$reason)";
			}
				die;
		} catch (AuthenticationError $e) {
				echo sprintf( __( '%s authentication failed', 'mainwp-updraftplus-extension' ), $this->desc ) . ' (' . $e->getMessage() . ')';
				die;
		} catch (Exception $e) {
				echo sprintf( __( '%s authentication failed', 'mainwp-updraftplus-extension' ), $this->desc ) . ' (' . get_class( $e ) . ', ' . $e->getMessage() . ')';
				die;
		}

		try {
				$container_object = $service->getContainer( $container );
		} catch (Guzzle\Http\Exception\ClientErrorResponseException $e) {
				$response = $e->getResponse();
				$code = $response->getStatusCode();
				$reason = $response->getReasonPhrase();
			if ( 404 == $code ) {
					$container_object = $service->createContainer( $container );
			} else {
					echo __( 'Authorisation failed (check your credentials)', 'mainwp-updraftplus-extension' ) . " ($code:$reason)";
					die;
			}
		} catch (Exception $e) {
				echo sprintf( __( '%s authentication failed', 'mainwp-updraftplus-extension' ), $this->desc ) . ' (' . get_class( $e ) . ', ' . $e->getMessage() . ')';
				die;
		}

		if ( ! is_a( $container_object, 'OpenCloud\ObjectStore\Resource\Container' ) && ! is_a( $container_object, 'Container' ) ) {
				echo sprintf( __( '%s authentication failed', 'mainwp-updraftplus-extension' ), $this->desc ) . ' (' . get_class( $container_object ) . ')';
				die;
		}

			$try_file = md5( rand() ) . '.txt';

		try {
				$object = $container_object->uploadObject( $try_file, 'UpdraftPlus test file', array( 'content-type' => 'text/plain' ) );
		} catch (Exception $e) {
				echo sprintf( __( '%s error - we accessed the container, but failed to create a file within it', 'mainwp-updraftplus-extension' ), $this->desc ) . ' (' . get_class( $e ) . ', ' . $e->getMessage() . ')';
			if ( ! empty( $this->region ) ) {
					echo ' ' . sprintf( __( 'Region: %s', 'mainwp-updraftplus-extension' ), $this->region ); }
				return;
		}

			echo __( 'Success', 'mainwp-updraftplus-extension' ) . ': ' . __( 'We accessed the container, and were able to create files within it.', 'mainwp-updraftplus-extension' );
		if ( ! empty( $this->region ) ) {
				echo ' ' . sprintf( __( 'Region: %s', 'mainwp-updraftplus-extension' ), $this->region ); }

		try {
			if ( ! empty( $object ) ) {
					# One OpenStack server we tested on did not delete unless we slept... some kind of race condition at their end
					sleep( 1 );
					$object->delete();
			}
		} catch (Exception $e) {

		}
	}

	public function config_print_middlesection() {

	}

	public function config_print() {
			?>
			<div class="ui grid field mwp_updraftplusmethod <?php echo $this->method; ?>">
                <label class="six wide column middle aligned">

                </label>
                <div class="ui ten wide column">
                    <?php if ( ! empty( $this->img_url ) ) { ?>
                            <img alt="<?php echo $this->long_desc; ?>" src="<?php echo MAINWP_UPDRAFT_PLUS_URL . $this->img_url ?>">
                    <?php } ?>
                </div>
            </div>
            <?php
            $this->config_print_middlesection();
	}
}
