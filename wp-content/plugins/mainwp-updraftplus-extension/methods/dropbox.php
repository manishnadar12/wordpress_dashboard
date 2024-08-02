<?php
// https://www.dropbox.com/developers/apply?cont=/developers/apps

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed.' ); }

# Converted to job_options: yes
# Converted to array options: yes
# Migrate options to new-style storage - May 2014
# appkey, secret, folder, updraft_dropboxtk_request_token, updraft_dropboxtk_access_token

class MainWP_Updraft_Plus_BackupModule_dropbox {

	private $current_file_hash;
	private $current_file_size;
	private $dropbox_object;

	public function chunked_callback( $offset, $uploadid, $fullpath = false ) {

	}

	public function get_credentials() {
			return array( 'updraft_dropbox' );
	}

	public function get_opts() {
		global $mainwp_updraftplus;
		
		$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_dropbox' ); //$mainwp_updraftplus->get_job_option('updraft_dropbox');
		
		if ( ! is_array( $opts ) ) {
				$opts = array(); 				
		}
		
		if ( ! isset( $opts['folder'] ) ) {
				$opts['folder'] = ''; 				
		}
		
		return $opts;
	}

	public function backup( $backup_array ) {
		return null;
	}

	# $match: a substring to require (tested via strpos() !== false)
	public function listfiles( $match = 'backup_' ) {

	}

	public function defaults() {
		return apply_filters( 'mainwp_updraftplus_dropbox_defaults', array( 'Z3Q3ZmkwbnplNHA0Zzlx', 'bTY0bm9iNmY4eWhjODRt' ) );
	}

	public function delete( $files ) {

	}

	public function download( $file ) {

	}

	public function config_print() {
		$opts = $this->get_opts();
		?>
		<div class="ui grid field mwp_updraftplusmethod dropbox">			
			<label class="six wide column middle aligned">
				<h4 class="ui header">
				  <img src="<?php echo esc_attr( MAINWP_UPDRAFT_PLUS_URL.'/images/icons/dropbox.png');?>" alt="Dropbox" class="ui image">
				  Dropbox
				</h4>
			</label>
			<div class="ui ten wide column">				  
				<div class="ui info message"><?php echo MainWP_Updraftplus_Backups::show_notice(); ?></div>
			</div>			
		</div>
		<?php echo apply_filters( 'mainwp_updraftplus_dropbox_extra_config', '' ); ?>
		<?php
	}


	public function show_authed_admin_warning() {

	}

	public function auth_token() {
		//      $opts = $this->get_opts();
		//      $previous_token = empty($opts['tk_request_token']) ? '' : $opts['tk_request_token'];
			$this->bootstrap();
			$opts = $this->get_opts();
			$new_token = empty( $opts['tk_request_token'] ) ? '' : $opts['tk_request_token'];
		if ( $new_token ) {
				add_action( 'all_admin_notices', array( $this, 'show_authed_admin_warning' ) );
		}
	}

		// Acquire single-use authorization code
	public function auth_request() {
			$this->bootstrap();
	}

		// This basically reproduces the relevant bits of bootstrap.php from the SDK
	public function bootstrap() {


	}
}
