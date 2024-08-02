<?php
if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed.' ); }

# Converted to job_options: yes
# Converted to array options: yes
# Migrate options to new-style storage - Apr 2014
# clientid, secret, remotepath

class MainWP_Updraft_Plus_BackupModule_googledrive {

	private $service;
	private $client;
	private $ids_from_paths;



	public function get_credentials() {
			return array( 'updraft_googledrive' );
	}

	public function get_opts() {
			# parentid is deprecated since April 2014; it should not be in the default options (its presence is used to detect an upgraded-from-previous-SDK situation). For the same reason, 'folder' is also unset; which enables us to know whether new-style settings have ever been set.
			global $mainwp_updraftplus;
			$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_googledrive' ); // $mainwp_updraftplus->get_job_option('updraft_googledrive');
		if ( ! is_array( $opts ) ) {
				$opts = array( 'clientid' => '', 'secret' => '' ); }
			return $opts;
	}

	private function root_id() {
		if ( empty( $this->root_id ) ) {
				$this->root_id = $this->service->about->get()->getRootFolderId(); }
			return $this->root_id;
	}

	public function id_from_path( $path, $retry = true ) {
			global $mainwp_updraftplus;

		try {
			while ( '/' == substr( $path, 0, 1 ) ) {
					$path = substr( $path, 1 );
			}

				$cache_key = (empty( $path )) ? '/' : $path;
			if ( ! empty( $this->ids_from_paths ) && isset( $this->ids_from_paths[ $cache_key ] ) ) {
					return $this->ids_from_paths[ $cache_key ]; }

				$current_parent = $this->root_id();
				$current_path = '/';

			if ( ! empty( $path ) ) {
				foreach ( explode( '/', $path ) as $element ) {
						$found = false;
						$sub_items = $this->get_subitems( $current_parent, 'dir', $element );

					foreach ( $sub_items as $item ) {
						try {
							if ( $item->getTitle() == $element ) {
									$found = true;
									$current_path .= $element . '/';
									$current_parent = $item->getId();
									break;
							}
						} catch (Exception $e) {
									$mainwp_updraftplus->log( 'Google Drive id_from_path: exception: ' . $e->getMessage() . ' (line: ' . $e->getLine() . ', file: ' . $e->getFile() . ')' );
						}
					}

					if ( ! $found ) {
							$ref = new Google_Service_Drive_ParentReference;
							$ref->setId( $current_parent );
							$dir = new Google_Service_Drive_DriveFile();
							$dir->setMimeType( 'application/vnd.google-apps.folder' );
							$dir->setParents( array( $ref ) );
							$dir->setTitle( $element );
							$mainwp_updraftplus->log( 'Google Drive: creating path: ' . $current_path . $element );
							$dir = $this->service->files->insert(
								$dir, array( 'mimeType' => 'application/vnd.google-apps.folder' )
							);
							$current_path .= $element . '/';
							$current_parent = $dir->getId();
					}
				}
			}

			if ( empty( $this->ids_from_paths ) ) {
					$this->ids_from_paths = array(); }
					$this->ids_from_paths[ $cache_key ] = $current_parent;

					return $current_parent;
		} catch (Exception $e) {
				$mainwp_updraftplus->log( 'Google Drive id_from_path failure: exception: ' . $e->getMessage() . ' (line: ' . $e->getLine() . ', file: ' . $e->getFile() . ')' );
				# One retry
				return ($retry) ? $this->id_from_path( $path, false ) : false;
		}
	}

	private function get_parent_id( $opts ) {
			$filtered = apply_filters( 'mainwp_updraftplus_googledrive_parent_id', false, $opts, $this->service, $this );
		if ( ! empty( $filtered ) ) {
				return $filtered; }
		if ( isset( $opts['parentid'] ) ) {
			if ( empty( $opts['parentid'] ) ) {
					return $this->root_id();
			} else {
					$parent = (is_array( $opts['parentid'] )) ? $opts['parentid']['id'] : $opts['parentid'];
			}
		} else {
				$parent = $this->id_from_path( 'UpdraftPlus' );
		}
			return (empty( $parent )) ? $this->root_id() : $parent;
	}

	public function listfiles( $match = 'backup_' ) {

	}

		// Get a Google account access token using the refresh token
	private function access_token( $refresh_token, $client_id, $client_secret ) {

			global $mainwp_updraftplus;
			$mainwp_updraftplus->log( "Google Drive: requesting access token: client_id=$client_id" );

			$query_body = array(
				'refresh_token' => $refresh_token,
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'grant_type' => 'refresh_token',
			);

			$result = wp_remote_post('https://accounts.google.com/o/oauth2/token', array(
				'timeout' => '15',
				'method' => 'POST',
				'body' => $query_body,
					)
			);

			if ( is_wp_error( $result ) ) {
					$mainwp_updraftplus->log( 'Google Drive error when requesting access token' );
				foreach ( $result->get_error_messages() as $msg ) {
						$mainwp_updraftplus->log( "Error message: $msg" ); }
					return false;
			} else {
					$json_values = json_decode( $result['body'], true );
				if ( isset( $json_values['access_token'] ) ) {
						$mainwp_updraftplus->log( 'Google Drive: successfully obtained access token' );
						return $json_values['access_token'];
				} else {
						$mainwp_updraftplus->log( 'Google Drive error when requesting access token: response does not contain access_token' );
						return false;
				}
			}
	}

	private function redirect_uri() {
			return MainWP_Updraft_Plus_Options::googledrive_page_url(); //.'?action=updraftmethod-googledrive-auth';
	}

		// Acquire single-use authorization code from Google OAuth 2.0
	public function gdrive_auth_request() {

	}

		// Revoke a Google account refresh token
		// Returns the parameter fed in, so can be used as a WordPress options filter
		// Can be called statically from UpdraftPlus::googledrive_clientid_checkchange()
	public static function gdrive_auth_revoke( $unsetopt = true ) {

	}

		// Get a Google account refresh token using the code received from gdrive_auth_request
	public function gdrive_auth_token() {

	}

	public function show_authed_admin_success() {

	}

		// This function just does the formalities, and off-loads the main work to upload_file
	public function backup( $backup_array ) {

			return null;
	}

	public function bootstrap( $access_token = false ) {

	}

		# Returns Google_Service_Drive_DriveFile object

	private function get_subitems( $parent_id, $type = 'any', $match = 'backup_' ) {

	}

	public function delete( $files ) {

	}

	private function upload_file( $file, $parent_id, $try_again = true ) {


	}

	public function download( $file ) {
		return true;
	}

	public function config_print() {
		$opts = $this->get_opts();
		?>
		<div class="ui grid field mwp_updraftplusmethod googledrive">
			<label class="six wide column middle aligned">
				<h4 class="ui header">
					<img src="<?php echo esc_attr( MAINWP_UPDRAFT_PLUS_URL.'/images/icons/googledrive.png');?>" alt="Google Drive" class="ui image">
					Google Drive
				</h4>
			</label>
			<div class="ui ten wide column">
				<div class="ui info message"><?php echo MainWP_Updraftplus_Backups::show_notice(); ?></div>
			</div>
		</div>
		<?php
	}
}
