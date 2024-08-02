<?php
// @codingStandardsIgnoreStart
/*
UpdraftPlus Addon: backblaze:Backblaze Support
Description: Backblaze Support
Version: 1.0
Shop: /shop/backblaze/
Include: includes/backblaze
IncludePHP: methods/addon-base-v2.php
RequiresPHP: 5.3.3
Latest Change: 1.13.9
*/
// @codingStandardsIgnoreEnd

if (!defined('MAINWP_UPDRAFT_PLUS_DIR')) die('No direct access allowed');

if (!class_exists('MainWP_Updraft_Plus_RemoteStorage_Addons_Base')) require_once(MAINWP_UPDRAFT_PLUS_DIR.'/methods/addon-base.php');

/**
 * Possible enhancements:
 * - Investigate porting to WP HTTP API so that curl is not required
 */
class MainWP_Updraft_Plus_Addons_RemoteStorage_backblaze extends MainWP_Updraft_Plus_RemoteStorage_Addons_Base {

    /**
	 * Constructor
	 */
	public function __construct() {

	}

	/**
	 * Retrieve default options for this remote storage module.
	 *
	 * @return Array - an array of options
	 */
	public function get_default_options() {
		return array(
			'account_id' => '',
			'key' => '',
			'bucket_name' => '',
			'backup_path' => '',
		);
	}

    public function get_opts() {
		global $mainwp_updraftplus;
		$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_backblaze' ); //$opts = $mainwp_updraftplus->get_job_option('updraft_azure');
		if (!is_array($opts)) $opts = $this->get_default_options();
		return $opts;
	}

    public function do_config_print($opts) {

		$account_id = empty($opts['account_id']) ? '' : $opts['account_id'];
        $key = empty($opts['key']) ? '' : $opts['key'];
        $bucket_name = empty($opts['bucket_name']) ? '' : $opts['bucket_name'];
        $backup_path = empty($opts['backup_path']) ? '' : $opts['backup_path'];


		ob_start();

		global $mainwp_updraftplus_admin;

		?>

		<div class="ui grid field mwp_updraftplusmethod backblaze">
			<label class="six wide column middle aligned">
				<h4 class="ui header">
				  <img src="<?php echo esc_attr( MAINWP_UPDRAFT_PLUS_URL.'/images/icons/backblaze.png');?>" class="ui image">
				  Backblaze
				</h4>
			</label>
			<div class="ui ten wide column">
				<?php echo _e('Account ID', 'updraftplus'); ?>
				<input type="text" size="40" name="mwp_updraft_backblaze[account_id]" value="<?php  echo $account_id; ?>">
				<div class="ui hidden fitted divider"></div>
				<?php _e('Application key', 'updraftplus'); ?>
				<input type="password" name="mwp_updraft_backblaze[key]" value="<?php echo $key; ?>" />
				<div class="ui hidden fitted divider"></div>
				<?php _e('Backup path', 'updraftplus'); ?>
				<input type="text" size="19" maxlength="50" name="mwp_updraft_backblaze[bucket_name]" placeholder="<?php _e('Bucket name', 'updraftplus');?>" data-updraft_settings_test="bucket_name"  value="<?php echo $bucket_name; ?>" />/<input type="text" size="19" maxlength="200" placeholder="<?php _e('some/path', 'updraftplus');?> " data-updraft_settings_test="backup_path" name="mwp_updraft_backblaze[backup_path]" value="<?php echo $backup_path; ?>" />
			</div>
		</div>

		<?php

		$html = ob_get_clean();
        echo $html;
	}
}
