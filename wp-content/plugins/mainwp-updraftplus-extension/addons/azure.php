<?php
/*
UpdraftPlus Addon: azure:Microsoft Azure Support
Description: Microsoft Azure Support
Version: 1.2
Shop: /shop/azure/
Include: includes/azure
IncludePHP: methods/addon-base.php
RequiresPHP: 5.3.3
Latest Change: 1.10.3
*/

if (!defined('MAINWP_UPDRAFT_PLUS_DIR')) die('No direct access allowed');

/*
do_upload
chunked_upload
get_pointer
do_download
chunked_download
do_delete
do_listfiles
do_bootstrap
options_exist***
action_auth
show_authed_admin_warning
get_onedrive_perms
get_opts***
auth_request
auth_token
do_config_print**
*/

if (!class_exists('MainWP_Updraft_Plus_RemoteStorage_Addons_Base')) require_once(MAINWP_UPDRAFT_PLUS_DIR.'/methods/addon-base.php');

class MainWP_Updraft_Plus_Addons_RemoteStorage_azure extends MainWP_Updraft_Plus_RemoteStorage_Addons_Base {

	// https://msdn.microsoft.com/en-us/library/azure/ee691964.aspx - maximum block size is 4Mb
	private $chunk_size = 2097152;

	public function __construct() {
		// 3rd parameter: chunking? 4th: Test button?
		parent::__construct('azure', 'Azure', true, true);
		// https://msdn.microsoft.com/en-us/library/azure/ee691964.aspx - maximum block size is 4Mb
		if (defined('UPDRAFTPLUS_UPLOAD_CHUNKSIZE') && UPDRAFTPLUS_UPLOAD_CHUNKSIZE > 0) $this->chunk_size = max(UPDRAFTPLUS_UPLOAD_CHUNKSIZE, 4194304);
	}

	public function do_upload($file, $from) {

		return true;
	}

	public function should_print_test_button() {
		return false;
	}


	// Return: boolean|(int)1
	public function chunked_upload($file, $fp, $chunk_index, $upload_size, $upload_start, $upload_end) {

		return true;
	}

	public function chunked_upload_finish($file) {

		return true;
	}

	public function do_download($file, $fullpath, $start_offset) {

	}

	public function chunked_download($file, $headers, $container_name) {


	}


	public function do_listfiles($match = 'backup_') {

	}

	public function do_credentials_test_parameters() {
		return array(
			'account_name' => 'Account Name',
			'key' => 'Account Key',
			'container' => 'Container',
		);
	}

	protected function do_credentials_test($testfile) {
		global $mainwp_updraftplus;
		$container_name = $_POST['container'];

		$directory = !empty($opts['directory']) ? trailingslashit($opts['directory']) : "";
		try {
			$exists = $this->create_container($container_name);

			if (is_wp_error($exists)) {
				foreach ($exists->get_error_messages() as $key => $msg) { echo "$msg\n"; }
				return false;
			}

		} catch (Exception $e) {
			echo __('Could not access container', 'mainwp-updraftplus-extension').': '.$e->getMessage().' ('.get_class($e).') (line: '.$e->getLine().', file: '.$e->getFile().')';

			return false;
		}
		try {
			$this->storage->createBlockBlob($container_name, $directory.$testfile, "UpdraftPlus temporary test file - you can remove this.");
		} catch (Exception $e) {
			echo 'Azure: '.__('Upload failed', 'mainwp-updraftplus-extension').': '.$e->getMessage().' ('.get_class($e).') (line: '.$e->getLine().', file: '.$e->getFile().')';
			return false;
		}

		return true;

	}

	protected function do_credentials_test_deletefile($testfile) {
		$container_name = $_POST['container'];
		$directory = !empty($opts['directory']) ? trailingslashit($opts['directory']) : "";

		try {
			$deleted_file = $this->storage->deleteBlob($container_name, $directory.$testfile);
		} catch (Exception $e) {
			echo __('Delete failed:', 'mainwp-updraftplus-extension').' '.$e->getMessage().' ('.$e->getCode().', '.get_class($e).') (line: '.$e->getLine().', file: '.$e->getFile().')';
		}

	}

	public function do_bootstrap($opts, $connect = true) {

	}

	// Returns a list of container names
	// Currently unused method
	protected function list_containers() {

	}

	// Check if the container exists (using list_containers above) and if not creates the container. Returns the container properties.
	protected function create_container($container_name, $create_on_404 = true) {
		// Should not be possible to reach this point
		return false;
	}

	protected function options_exist($opts) {
		if (is_array($opts) && !empty($opts['account_name']) && !empty($opts['key'])) return true;
		return false;
	}

	public function get_opts() {
		global $mainwp_updraftplus;
		$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_azure' ); //$opts = $mainwp_updraftplus->get_job_option('updraft_azure');
		if (!is_array($opts)) $opts = array('account_name' => '', 'key' => '', 'container' => '', 'directory' => '');
		return $opts;
	}

	public function do_config_print($opts) {
		//account name***
		//key
		//container
		//directory

		global $mainwp_updraftplus_admin;

		//print("HERE");
		//$this->bootstrap();
		//print_r($this->storage);

		/*$folder = (empty($opts['folder'])) ? '' : untrailingslashit($opts['folder']);
		$clientid = (empty($opts['clientid'])) ? '' : $opts['clientid'];
		$secret = (empty($opts['secret'])) ? '' : $opts['secret'];*/

		$account_name = (empty($opts['account_name'])) ? '' : $opts['account_name'];
		$key = (empty($opts['key'])) ? '' : $opts['key'];
		$container = (empty($opts['container'])) ? '' : $opts['container'];
		$directory = (empty($opts['directory'])) ? '' : $opts['directory'];

		/*$site_host = parse_url(network_site_url(), PHP_URL_HOST);

		/*if ('127.0.0.1' == $site_host || '::1' == $site_host || 'localhost' == $site_host) {
			// Of course, there are other things that are effectively 127.0.0.1. This is just to help.
			$callback_text = '<p><strong>'.htmlspecialchars(sprintf(__('Microsoft Azure is not compatible with sites hosted on a localhost or 127.0.0.1 URL - their developer console forbids these (current URL is: %s).', 'mainwp-updraftplus-extension'), site_url())).'</strong></p>';
		} else {
			$callback_text = '<p>'.htmlspecialchars(__('You must add the following as the authorised redirect URI in your Azure console (under "API Settings") when asked', 'mainwp-updraftplus-extension')).': <kbd>'.MainWP_Updraft_Plus_Options::admin_page_url().'</kbd></p>';
		}*/

		$mainwp_updraftplus_admin->storagemethod_row(
			'azure',
			'',
			'<img width="434" src="'.MAINWP_UPDRAFT_PLUS_URL.'/images/azure.png"><p><a href="https://account.live.com/developers/applications/create">'.__('Create Azure credentials in your Azure developer console.', 'mainwp-updraftplus-extension').'</a></p><p><a href="https://updraftplus.com/faqs/microsoft-azure-setup-guide/">'.__('For longer help, including screenshots, follow this link.', 'mainwp-updraftplus-extension').'</a></p>'
		);
		?>

            <div class="ui grid field mwp_updraftplusmethod azure">
                <label class="six wide column middle aligned"></label>
                <div class="ui ten wide column">
                    <div class="ui info message"><?php echo MainWP_Updraftplus_Backups::show_notice(); ?></div>
                </div>
            </div>
           <?php
    }

	protected function do_config_javascript() {
		?>
		account_name: jQuery('#updraft_<?php echo $this->method; ?>_account_name').val(),
		key: jQuery('#updraft_<?php echo $this->method; ?>_key').val(),
		container: jQuery('#updraft_<?php echo $this->method; ?>_container').val(),
		directory: jQuery('#updraft_<?php echo $this->method; ?>_directory').val(),
		disableverify: (jQuery('#updraft_ssl_disableverify').is(':checked')) ? 1 : 0,
		useservercerts: (jQuery('#updraft_ssl_useservercerts').is(':checked')) ? 1 : 0,
		nossl: (jQuery('#updraft_ssl_nossl').is(':checked')) ? 1 : 0,
		<?php
	}

}

$mainwp_updraftplus_addons_azure = new MainWP_Updraft_Plus_Addons_RemoteStorage_azure;
