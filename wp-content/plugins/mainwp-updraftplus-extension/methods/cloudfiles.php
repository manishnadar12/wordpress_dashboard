<?php
if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access.' ); }

# Converted to job_options: yes
# Converted to array options: yes

if ( version_compare( phpversion(), '5.3.3', '>=' ) && ( ! defined( 'UPDRAFTPLUS_CLOUDFILES_USEOLDSDK' ) || UPDRAFTPLUS_CLOUDFILES_USEOLDSDK != true) ) {
		require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/cloudfiles-new.php' );

        class MainWP_Updraft_Plus_BackupModule_cloudfiles extends MainWP_Updraft_Plus_BackupModule_cloudfiles_opencloudsdk {

	}

} else {

	class MainWP_Updraft_Plus_BackupModule_cloudfiles extends MainWP_Updraft_Plus_BackupModule_cloudfiles_oldsdk {

	}

}

# Migrate options to new-style storage - Dec 2013
# Old SDK

class MainWP_Updraft_Plus_BackupModule_cloudfiles_oldsdk {

	private $cloudfiles_object;

		// This function does not catch any exceptions - that should be done by the caller
	private function get_cf( $user, $apikey, $authurl, $useservercerts = false ) {

	}

	public function get_credentials() {
			return array( 'updraft_cloudfiles' );
	}

	public function get_opts() {
			global $mainwp_updraftplus;
			$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_cloudfiles' ); //$mainwp_updraftplus->get_job_option('updraft_cloudfiles');
		if ( ! is_array( $opts ) ) {
				$opts = array( 'user' => '', 'authurl' => 'https://auth.api.rackspacecloud.com', 'apikey' => '', 'path' => '' ); }
		if ( empty( $opts['authurl'] ) ) {
				$opts['authurl'] = 'https://auth.api.rackspacecloud.com'; }
		if ( empty( $opts['region'] ) ) {
				$opts['region'] = null; }
			return $opts;
	}

	public function backup( $backup_array ) {
			return false;
	}

	public function listfiles( $match = 'backup_' ) {


	}

	public function delete( $files, $cloudfilesarr = false ) {
		return false;
	}

	public function download( $file ) {

	}

	public function config_print_javascript_onready() {
        return; // disabled this
			?>
			jQuery('#updraft-cloudfiles-test').click(function(){
			jQuery(this).html('<?php echo esc_js( __( 'Testing - Please Wait...', 'mainwp-updraftplus-extension' ) ); ?>');
				var data = {
				action: 'mainwp_updraft_ajax',
				subaction: 'credentials_test',
				method: 'cloudfiles',
				nonce: '<?php echo wp_create_nonce( 'mwp-updraftplus-credentialtest-nonce' ); ?>',
				apikey: jQuery('#updraft_cloudfiles_apikey').val(),
				user: jQuery('#updraft_cloudfiles_user').val(),
				path: jQuery('#updraft_cloudfiles_path').val(),
				authurl: jQuery('#updraft_cloudfiles_authurl').val(),
				region: jQuery('#updraft_cloudfiles_region').val(),
				useservercerts: jQuery('#updraft_ssl_useservercerts').val(),
				disableverify: jQuery('#updraft_ssl_disableverify').val()
				};
				jQuery.post(ajaxurl, data, function(response) {
				jQuery('#updraft-cloudfiles-test').html('<?php echo esc_js( sprintf( __( 'Test %s Settings', 'mainwp-updraftplus-extension' ), 'Cloud Files' ) ); ?>');
				alert('<?php echo esc_js( sprintf( __( '%s settings test result:', 'mainwp-updraftplus-extension' ), 'Cloud Files' ) ); ?> ' + response);
				});
				});
				<?php
	}

	public function config_print() {

			$opts = $this->get_opts();
			?>
			<div class="ui grid field mwp_updraftplusmethod cloudfiles">
                <label class="six wide column middle aligned">

                </label>
                <div class="ui ten wide column">
                    <img alt="Rackspace Cloud Files" src="<?php echo MAINWP_UPDRAFT_PLUS_URL . '/images/rackspacecloud-logo.png' ?>">
                    <?php
                        // Check requirements.
                        global $mainwp_updraftplus_admin;
                        if ( ! function_exists( 'mb_substr' ) ) {
                                $mainwp_updraftplus_admin->show_double_warning( '<strong>' . __( 'Warning', 'mainwp-updraftplus-extension' ) . ':</strong> ' . sprintf( __( 'Your web server\'s PHP installation does not included a required module (%s). Please contact your web hosting provider\'s support.', 'mainwp-updraftplus-extension' ), 'mbstring' ) . ' ' . sprintf( __( "UpdraftPlus's %s module <strong>requires</strong> %s. Please do not file any support requests; there is no alternative.", 'mainwp-updraftplus-extension' ), 'Cloud Files', 'mbstring' ), 'cloudfiles' );
                        }
                        //$mainwp_updraftplus_admin->curl_check('Rackspace Cloud Files', false, 'cloudfiles');
                        ?>
                    <div class="ui hidden fitted divider"></div>
                    <?php _e( 'Get your API key <a href="https://mycloud.rackspace.com/">from your Rackspace Cloud console</a> (read instructions <a href="http://www.rackspace.com/knowledge_center/article/rackspace-cloud-essentials-1-generating-your-api-key">here</a>), then pick a container name to use for storage. This container will be created for you if it does not already exist.', 'mainwp-updraftplus-extension' ); ?> <a href="http://updraftplus.com/faqs/there-appear-to-be-lots-of-extra-files-in-my-rackspace-cloud-files-container/"><?php _e( 'Also, you should read this important FAQ.', 'mainwp-updraftplus-extension' ); ?></a>
                    <div class="ui hidden fitted divider"></div>
                    <?php _e( 'US or UK Cloud', 'mainwp-updraftplus-extension' ); ?>:
                    <div class="ui hidden fitted divider"></div>
                    <select id="updraft_cloudfiles_authurl" name="mwp_updraft_cloudfiles[authurl]">
                    <option <?php if ( 'https://lon.auth.api.rackspacecloud.com' != $opts['authurl'] ) { echo 'selected="selected"'; } ?> value="https://auth.api.rackspacecloud.com"><?php _e( 'US (default)', 'mainwp-updraftplus-extension' ); ?></option>
                    <option <?php if ( 'https://lon.auth.api.rackspacecloud.com' == $opts['authurl'] ) { echo 'selected="selected"'; } ?> value="https://lon.auth.api.rackspacecloud.com"><?php _e( 'UK', 'mainwp-updraftplus-extension' ); ?></option>
                    </select>
                    <input type="hidden" name="mwp_updraft_cloudfiles[region]" value="">
                    <div class="ui hidden fitted divider"></div>
                    <?php _e( 'Cloud Files username', 'mainwp-updraftplus-extension' ); ?>:
                    <div class="ui hidden fitted divider"></div>
                    <input type="text" autocomplete="off" style="width: 282px" id="updraft_cloudfiles_user" name="mwp_updraft_cloudfiles[user]" value="<?php echo htmlspecialchars( $opts['user'] ) ?>" />
                    <div class="ui hidden fitted divider"></div>
                    <?php _e( 'Cloud Files API key', 'mainwp-updraftplus-extension' ); ?>:
                    <div class="ui hidden fitted divider"></div>
                    <input type="<?php echo apply_filters( 'mainwp_updraftplus_admin_secret_field_type', 'text' ); ?>" autocomplete="off" style="width: 282px" id="updraft_cloudfiles_apikey" name="mwp_updraft_cloudfiles[apikey]" value="<?php echo htmlspecialchars( $opts['apikey'] ); ?>" />
                    <div class="ui hidden fitted divider"></div>
                    <?php echo apply_filters( 'mainwp_updraftplus_cloudfiles_location_description', __( 'Cloud Files container', 'mainwp-updraftplus-extension' ) ); ?>:
                    <div class="ui hidden fitted divider"></div>
                    <input type="text" style="width: 282px" name="mwp_updraft_cloudfiles[path]" id="updraft_cloudfiles_path" value="<?php echo htmlspecialchars( $opts['path'] ); ?>" />

                </div>
            </div>
            <?php
	}

	public function credentials_test() {

	}
}
