<?php

if (!defined('MAINWP_UPDRAFT_PLUS_DIR')) die('No direct access allowed.');

require_once(MAINWP_UPDRAFT_PLUS_DIR.'/methods/s3.php');

class MainWP_Updraft_Plus_BackupModule_updraftvault extends MainWP_Updraft_Plus_BackupModule_s3 {

	private $vault_mothership = 'https://vault.updraftplus.com/plugin-info/';

	private $vault_config;

	// This function makes testing easier, rather than having to change the URLs in multiple places
	private function get_url($which_page = false) {
		$base = 'https://updraftplus.com/shop/';
		switch ($which_page) {
			case 'get_more_quota';
				return $base.'product-category/updraftplus-vault/';
				break;
			case 'more_vault_info_faqs';
				return 'https://updraftplus.com/support/updraftplus-vault-faqs/';
				break;
			case 'more_vault_info_landing';
				return 'https://updraftplus.com/landing/vault';
				break;
			case 'vault_forgotten_credentials_links';
				return 'https://updraftplus.com/my-account/lost-password/';
				break;
			default:
				return $base;
				break;
		}
	}

	public function get_opts() {
		global $mainwp_updraftplus;
		$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_updraftvault' ); // $opts = $mainwp_updraftplus->get_job_option('updraft_updraftvault');
		if (!is_array($opts)) $opts = array('token' => '', 'email' => '', 'quota' => -1);
		return $opts;
	}

	public function get_credentials() {
		return array('updraft_updraftvault');
	}

	protected function vault_set_config($config) {
		$config['whoweare'] = 'Updraft Vault';
		$config['whoweare_long'] = __('Updraft Vault', 'mainwp-updraftplus-extension');
		$config['key'] = 'updraftvault';
		$this->vault_config = $config;
	}

	protected function get_config() {

	}

	public function vault_translate_remote_message($message, $code) {
		switch ($code) {
			case 'premium_overdue':
			return __('Your UpdraftPlus Premium purchase is over a year ago. You should renew immediately to avoid losing the 12 months of free storage allowance that you get for being a current UpdraftPlus Premium customer.', 'mainwp-updraftplus-extension');
			break;
			case 'vault_subscription_overdue':
			return __('You have an UpdraftPlus Vault subscription with overdue payment. You are within the few days of grace period before it will be suspended, and you will lose your quota and access to data stored within it. Please renew as soon as possible!', 'mainwp-updraftplus-extension');
			break;
			case 'vault_subscription_suspended':
			return __("You have an UpdraftPlus Vault subscription that has not been renewed, and the grace period has expired. In a few days' time, your stored data will be permanently removed. If you do not wish this to happen, then you should renew as soon as possible.", 'mainwp-updraftplus-extension');
			break;
		}
		return $message;
	}

	public function config_print() {

		// Used to decide whether we can afford HTTP calls or not, or would prefer to rely on cached data
		$this->vault_in_config_print = true;

		$shop_url_base = $this->get_url();
		$get_more_quota = $this->get_url('get_more_quota');

		$vault_settings = MainWP_Updraft_Plus_Options::get_updraft_option('updraft_updraftvault');

		$connected = false; //(is_array($vault_settings) && !empty($vault_settings['token']) && !empty($vault_settings['email'])) ? true : false;
		$quota_text = "";

		$is_individual_site = MainWP_Updraftplus_Backups::is_managesites_updraftplus();

		if ( $is_individual_site) {
			$quota_text =  MainWP_Updraft_Plus_Options::get_updraft_option('updraftvault_quota_text');
			if ( !empty($quota_text) )
				$connected = true;
		}
		?>

		<div class="ui grid field mwp_updraftplusmethod updraftvault">
			<label class="six wide column middle aligned">
				<h4 class="ui header">
				  <img src="<?php echo esc_attr(MAINWP_UPDRAFT_PLUS_URL.'/images/icons/updraftvault.png');?>" alt="UpdraftPlus Vault" class="ui image">
				  UpdraftPlus Vault
				</h4>
			</label>
			<div class="ui ten wide column" id="updraftvault_settings_cell">
				<div id="mainwp_updraftvault_connect_message_box" style="display: none"></div>
				<div id="updraftvault_settings_default" <?php if ($connected) echo ' style="display:none;"';?>>
					<button id="updraftvault_connect" class="ui button" style="font-size:18px;"><?php _e('Already purchased space? Connect', 'mainwp-updraftplus-extension');?></button>
				</div>
				<div id="updraftvault_settings_connect" style="display:none;">
					<?php _e('Enter your UpdraftPlus.Com email / password here to connect:', 'mainwp-updraftplus-extension');?>
					<input id="updraftvault_email" class="udignorechange" type="text" placeholder="<?php esc_attr_e(__('E-mail', 'mainwp-updraftplus-extension'));?>">
					<div class="ui fitted hidden divider"></div>
					<input id="updraftvault_pass" class="udignorechange" type="password" placeholder="<?php esc_attr_e(__('Password', 'mainwp-updraftplus-extension'));?>">
					<div class="ui fitted hidden divider"></div>
					<button id="<?php echo $is_individual_site ? 'updraftvault_connect_go' : 'updraftvault_bulk_connect_go'; ?>" class="ui green button"><?php _e('Connect', 'mainwp-updraftplus-extension');?></button>

				</div>
				<div class="ui hidden divider"></div>
				<div id="updraftvault_settings_connected"<?php if (!$connected) echo ' style="display:none;"';?>>
					<?php echo $quota_text; ?>
				</div>
			</div>
		</div>
		<?php
		global $mainwp_updraftplus_admin;
		//$mainwp_updraftplus_admin->curl_check('UpdraftPlus Vault', false, 'updraftvault', false);
		$this->vault_in_config_print = false;

	}

	private function connected_html($vault_settings = false) {
		if (!is_array($vault_settings)) {
			$vault_settings = MainWP_Updraft_Plus_Options::get_updraft_option('updraft_updraftvault');
		}
		if (!is_array($vault_settings) || empty($vault_settings['token']) || empty($vault_settings['email'])) return '<p>'.__('You are <strong>not connected</strong> to UpdraftPlus Vault.', 'mainwp-updraftplus-extension').'</p>';

		$ret = '';
		$ret .= '<p style="padding-top: 0px; margin-top: 0px;">';
		$ret .= __('This site is <strong>connected</strong> to UpdraftPlus Vault.', 'mainwp-updraftplus-extension').' '.__("Well done - there's nothing more needed to set up.", 'mainwp-updraftplus-extension').'</p><p><strong>'.__('Vault owner', 'mainwp-updraftplus-extension').':</strong> '.htmlspecialchars($vault_settings['email']);

		$ret .= '<br><strong>'.__('Quota:', 'mainwp-updraftplus-extension').'</strong> ';
		if (!isset($vault_settings['quota']) || !is_numeric($vault_settings['quota']) || $vault_settings['quota'] < 0) {
			$ret .= __('Unknown', 'mainwp-updraftplus-extension');
		} else {
			$ret .= $this->s3_get_quota_info('text', $vault_settings['quota']);
		}
		$ret .= '</p>';
		$ret .= '<p><button id="updraftvault_disconnect" class="button-primary" style="font-size:18px;">'.__('Disconnect', 'mainwp-updraftplus-extension').'</button></p>';

		return $ret;
	}

	protected function s3_out_of_quota($total, $used, $needed) {
		global $mainwp_updraftplus;
		$mainwp_updraftplus->log("UpdraftPlus Vault Error: Quota exhausted (used=$used, total=$total, needed=$needed)");
		$mainwp_updraftplus->log(sprintf(__('%s Error: you have insufficient storage quota available (%s) to upload this archive (%s).', 'mainwp-updraftplus-extension'), 'UpdraftPlus Vault', round(($total-$used)/1048576, 2).' Mb', round($needed/1048576, 2).' Mb').' '.__('You can get more quota here', 'mainwp-updraftplus-extension').': '.$this->get_url('get_more_quota'), 'error');
	}

	protected function s3_record_quota_info($quota_used, $quota) {

		$ret = __('Current use:', 'mainwp-updraftplus-extension').' '.round($quota_used / 1048576, 1).' / '.round($quota / 1048576, 1).' Mb';
		$ret .= ' ('.sprintf('%.1f', 100*$quota_used / max($quota, 1)).' %)';

		$ret_plain = $ret . ' - '.__('Get more quota', 'mainwp-updraftplus-extension').': '.$this->get_url('get_more_quota');

		$ret .= ' - <a href="'.esc_attr($this->get_url('get_more_quota')).'">'.__('Get more quota', 'mainwp-updraftplus-extension').'</a>';

		$ret_dashboard = $ret . ' - <a href="#" id="updraftvault_recountquota">'.__('Refresh current status', 'mainwp-updraftplus-extension').'</a>';

		set_transient('updraftvault_quota_text', $ret_dashboard, 86400*3);

		do_action('updraft_report_remotestorage_extrainfo', 'updraftvault', "($ret)", $ret_plain);
	}

	// Valid formats: text|numeric
	// In numeric, returns an integer or false for an error (never returns an error)
	protected function s3_get_quota_info($format = 'numeric', $quota = 0) {
		$ret = '';

		if ($quota > 0) {

			if (!empty($this->vault_in_config_print) && 'text' == $format) {
				$quota_via_transient = get_transient('updraftvault_quota_text');
				if (is_string($quota) && $quota) return $quota;
			}

			try {
				$current_files = $this->listfiles('');
			} catch (Exception $e) {
				global $mainwp_updraftplus;
				$mainwp_updraftplus->log("Listfiles failed during quota calculation: ".$e->getMessage());
				$current_files = new WP_Error('listfiles_exception', $e->getMessage().' ('.get_class($e).')');
			}

			$ret .= __('Current use:', 'mainwp-updraftplus-extension').' ';

			$counted = false;
			if (is_wp_error($current_files)) {
				$ret .= __('Error:', 'mainwp-updraftplus-extension').' '.$current_files->get_error_message().' ('.$current_files->get_error_code().')';
			} elseif (!is_array($current_files)) {
				$ret .= __('Unknown', 'mainwp-updraftplus-extension');
			} else {
				foreach ($current_files as $file) {
					$counted += $file['size'];
				}
				$ret .= round($counted / 1048576, 1);
				$ret .= ' / '.round($quota / 1048576, 1).' Mb';
				$ret .= ' ('.sprintf('%.1f', 100*$counted / $quota).' %)';
			}
		} else {
			$ret .= '0';
		}

		$ret .= ' - <a href="'.esc_attr($this->get_url('get_more_quota')).'">'.__('Get more quota', 'mainwp-updraftplus-extension').'</a> - <a href="#" id="updraftvault_recountquota">'.__('Refresh current status', 'mainwp-updraftplus-extension').'</a>';

		if ('text' == $format) set_transient('updraftvault_quota_text', $ret, 86400*3);

		return ('text' == $format) ? $ret : $counted;
	}

	public function config_print_javascript_onready() {
		//$this->config_print_javascript_onready_engine('updraftvault', 'Updraft Vault');
	}

	public function credentials_test() {

	}

	public function ajax_vault_recountquota() {

	}

	public function ajax_vault_disconnect() {
		global $mainWPUpdraftPlusBackupsExtensionActivator;
		$siteid = $_REQUEST['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }


		$updraft_plus_site = MainWP_Updraftplus_BackupsDB::get_instance()->get_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		if ( $individual_update ) {
			if ( $updraft_plus_site ) {
				if ( !$updraft_plus_site->override ) {
					die( json_encode( array( 'error' => 'Update Failed: Override General Settings need to be set to Yes.' ) ) );
				}
			}
		} else {
			if ( $updraft_plus_site ) {
				if ( 1 == $updraft_plus_site->override ) {
					die( json_encode( array( 'message' => __( 'Not Updated - Individual site settings are in use.', 'mainwp' ) ) ) );
				}
			}
		}

		$post_data = array(
			'mwp_action' => 'vault_disconnect',
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
		if (is_array($information) && isset($information['disconnected'])) {
			MainWP_Updraft_Plus_Options::update_updraft_option( 'updraftvault_quota_text', '', $siteid);
		}
		die( json_encode( $information ) );
	}


	public function ajax_vault_connect() {

		global $mainWPUpdraftPlusBackupsExtensionActivator;

		$siteid = $_REQUEST['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

		$email = $_POST['email'];
		$password = $_POST['pass'];

		if (empty($email) || empty($password))
			die (json_encode(array( 'error' => __('You need to supply both an email address and a password', 'mainwp-updraftplus-extension'))));

		$updraft_plus_site = MainWP_Updraftplus_BackupsDB::get_instance()->get_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		if ( $individual_update ) {
			if ( $updraft_plus_site ) {
				if ( !$updraft_plus_site->override ) {
					die( json_encode( array( 'error' => 'Update Failed: Override General Settings need to be set to Yes.' ) ) );
				}
			}
		} else {
			if ( $updraft_plus_site ) {
				if ( 1 == $updraft_plus_site->override ) {
					die( json_encode( array( 'message' => __( 'Not Updated - Individual site settings are in use.', 'mainwp' ) ) ) );
				}
			}
		}
		$post_data = array(
			'mwp_action' => 'vault_connect',
			'email' => $email,
			'passwd' => $password,
		);
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
		if (is_array($information) && isset($information['connected'])) {
			$information['html'] = preg_replace( '/ - <a href="#" id="updraftvault_recountquota"\>[^<]+<\/a>/', '', $information['html'] );
			MainWP_Updraft_Plus_Options::update_updraft_option( 'updraftvault_quota_text', $information['html'], $siteid);
		}
		die( json_encode( $information ) );
	}

}
