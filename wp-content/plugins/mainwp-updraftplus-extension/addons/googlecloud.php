<?php
/*
UpdraftPlus Addon: googlecloud:Google Cloud Support
Description: Google Cloud Support
Version: 1.0
Shop: /shop/googlecloud/
Include: includes/googlecloud
IncludePHP: methods/addon-base.php
RequiresPHP: 5.2.4
Latest Change: 1.11.13
*/

/*
Potential enhancements:
- Implement the permission to not use SSL (we currently always use SSL).
*/

if (!defined('MAINWP_UPDRAFT_PLUS_DIR')) die('No direct access allowed');

if (!class_exists('MainWP_Updraft_Plus_RemoteStorage_Addons_Base')) require_once(MAINWP_UPDRAFT_PLUS_DIR.'/methods/addon-base.php');

class MainWP_Updraft_Plus_Addons_RemoteStorage_googlecloud extends MainWP_Updraft_Plus_RemoteStorage_Addons_Base {

	private $service;
	private $client;
	private $chunk_size = 2097152;

	private $storage_classes;
	private $bucket_locations;

	public function __construct() {
		# 3rd parameter: chunking? 4th: Test button?

		$this->storage_classes = array(
			'STANDARD' => __('Standard', 'mainwp-updraftplus-extension'),
			'DURABLE_REDUCED_AVAILABILITY' => __('Durable reduced availability', 'mainwp-updraftplus-extension'),
			'NEARLINE' => __('Nearline', 'mainwp-updraftplus-extension'),
		);

		$this->bucket_locations = array(
			'US' => __('United States', 'mainwp-updraftplus-extension').' ('.__('multi-region location', 'mainwp-updraftplus-extension').')',
			'ASIA' => __('Asia Pacific', 'mainwp-updraftplus-extension').' ('.__('multi-region location', 'mainwp-updraftplus-extension').')',
			'EU' => __('European Union', 'mainwp-updraftplus-extension').' ('.__('multi-region location', 'mainwp-updraftplus-extension').')',
			'us-central1' => __('Central United States', 'mainwp-updraftplus-extension').' (1)',
			'us-east1' => __(' Eastern United States', 'mainwp-updraftplus-extension').' (1)',
			'us-central2' => __('Central United States', 'mainwp-updraftplus-extension').' (2)',
			'us-east2' => __('Eastern United States', 'mainwp-updraftplus-extension').' (2)',
			'us-east3' => __('Eastern United States', 'mainwp-updraftplus-extension').' (3)',
			'us-west1' => __('Western United States', 'mainwp-updraftplus-extension').' (1)',
			'asia-east1' => __('Eastern Asia-Pacific', 'mainwp-updraftplus-extension').' (1)',
			'europe-west1' => __('Western Europe', 'mainwp-updraftplus-extension').' (1)',
		);

		parent::__construct('googlecloud', 'Google Cloud Storage', true, true);
		if (defined('UPDRAFTPLUS_UPLOAD_CHUNKSIZE') && UPDRAFTPLUS_UPLOAD_CHUNKSIZE>0) $this->chunk_size = max(UPDRAFTPLUS_UPLOAD_CHUNKSIZE, 512*1024);
	}

	protected function options_exist($opts) {
		if (is_array($opts) && !empty($opts['clientid']) && !empty($opts['secret']) && !empty($opts['bucket_path'])) return true;
		return false;
	}

	public function do_upload($file, $from) {

	}

	// The code in this method is basically copied and slightly adjusted from our Google Drive module
	private function do_upload_engine($basename, $from, $try_again = true) {


	}

	public function do_download($file, $fullpath, $start_offset) {

	}

	public function chunked_download($file, $headers, $link) {
	}


	public function do_listfiles($match = 'backup_') {

	}

	// Revoke a Google account refresh token
	// Returns the parameter fed in, so can be used as a WordPress options filter
	// Can be called statically from UpdraftPlus::googlecloud_checkchange()
	public static function gcloud_auth_revoke($unsetopt = true) {

	}

	// Acquire single-use authorization code from Google OAuth 2.0
	public function gcloud_auth_request() {

	}

	// Get a Google account refresh token using the code received from gdrive_auth_request
	public function gcloud_auth_token() {

	}


	private function redirect_uri() {
		return  MainWP_Updraft_Plus_Options::admin_page_url().'?action=updraftmethod-googlecloud-auth';
	}

	// Get a Google account access token using the refresh token
	private function access_token($refresh_token, $client_id, $client_secret) {


	}

	public function do_bootstrap($opts, $connect) {

	}

	public function show_authed_admin_success() {


	}

	// Google require lower-case only; that's not such a hugely obvious one, so we automatically munge it. We also trim slashes.
	private function split_bucket_path($bucket_path){
		if (preg_match("#^/*([^/]+)/(.*)$#", $bucket_path, $bmatches)) {
			$bucket = $bmatches[1];
			$path = trailingslashit($bmatches[2]);
		} else {
			$bucket = trim($bucket_path, " /");
			$path = "";
		}

		return array(strtolower($bucket), $path);
	}

	public function credentials_test() {
		return $this->credentials_test_engine();
	}

	public function credentials_test_engine() {

		die;
	}

	// Requires project ID to actually create
	// Returns a Google_Service_Storage_Bucket if successful
	// Defaults to STANDARD / US, if the options are not passed and if nothing is in the saved settings
	private function create_bucket_if_not_existing($bucket_name, $storage_class = false, $location = false) {

	}

	public function should_print_test_button() {
//		$opts = $this->get_opts();
//		if (!is_array($opts) || empty($opts['token'])) return false;
		return false;
	}

	public function do_config_javascript() {
		?>
		clientid: jQuery('#updraft_<?php echo $this->method; ?>_clientid').val(),
		secret: jQuery('#updraft_<?php echo $this->method; ?>_apisecret').val(),
		bucket_path: jQuery('#updraft_<?php echo $this->method; ?>_bucket_path').val(),
		project_id: jQuery('#updraft_<?php echo $this->method; ?>_project_id').val(),
		bucket_location: jQuery('#updraft_<?php echo $this->method; ?>_bucket_location').val(),
		storage_class: jQuery('#updraft_<?php echo $this->method; ?>_storage_class').val(),
		disableverify: (jQuery('#updraft_ssl_disableverify').is(':checked')) ? 1 : 0,
		useservercerts: (jQuery('#updraft_ssl_useservercerts').is(':checked')) ? 1 : 0,
		nossl: (jQuery('#updraft_ssl_nossl').is(':checked')) ? 1 : 0,
		<?php
	}

	public function do_config_print($opts) {
		global $mainwp_updraftplus_admin;

		$bucket_path = empty($opts['bucket_path']) ? '' : untrailingslashit($opts['bucket_path']);
		$accesskey = empty($opts['accesskey']) ? '' : $opts['accesskey'];
		$secret = empty($opts['secret']) ? '' : $opts['secret'];
		$client_id = empty($opts['clientid']) ? '' : $opts['clientid'];
		$project_id = empty($opts['project_id']) ? '' : $opts['project_id'];
		$storage_class = empty($opts['storage_class']) ? 'STANDARD' : $opts['storage_class'];
		$bucket_location = empty($opts['bucket_location']) ? 'US' : $opts['bucket_location'];
		?>
		<div class="ui grid field mwp_updraftplusmethod googlecloud">
            <label class="six wide column middle aligned">
                <h4 class="ui header">
                    Google Cloud
                </h4>
            </label>
                <div class="ui ten wide column">
                    <img alt="<?php _e(sprintf(__('%s logo', 'mainwp-updraftplus-extension'), 'Google Cloud')); ?>" src="<?php echo esc_attr(MAINWP_UPDRAFT_PLUS_URL.'/images/googlecloud.png'); ?>"><br>
                    <div class="ui hidden fitted divider"></div>
                    <div class="ui info message"><?php echo MainWP_Updraftplus_Backups::show_notice(); ?></div>

                </div>
        </div>
	<?php

	}

}

$mainwp_updraftplus_addons_googlecloud = new MainWP_Updraft_Plus_Addons_RemoteStorage_googlecloud;
