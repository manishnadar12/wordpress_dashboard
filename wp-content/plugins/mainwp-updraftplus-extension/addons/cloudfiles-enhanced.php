<?php
/*
  UpdraftPlus Addon: cloudfiles-enhanced:Rackspace Cloud Files, enhanced
  Description: Adds enhanced capabilities for Rackspace Cloud Files users
  Version: 1.1
  RequiresPHP: 5.3.3
  Shop: /shop/cloudfiles-enhanced/
  Latest Change: 1.9.17
 */

# Future possibility: sub-folders

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

# The new Rackspace SDK is PHP 5.3.3 or later
if ( version_compare( phpversion(), '5.3.3', '<' ) ) {
		return; }
if ( defined( 'UPDRAFTPLUS_CLOUDFILES_USEOLDSDK' ) && UPDRAFTPLUS_CLOUDFILES_USEOLDSDK == true ) {
		return; }

use OpenCloud\Rackspace;

$mainwp_updraft_plus_addon_cloudfilesenhanced = new MainWP_Updraft_Plus_Addon_CloudFilesEnhanced;

class MainWP_Updraft_Plus_Addon_CloudFilesEnhanced {

	public function __construct() {
			$this->title = __( 'Rackspace Cloud Files, enhanced', 'mainwp-updraftplus-extension' );
			$this->description = __( 'Adds enhanced capabilities for Rackspace Cloud Files users', 'mainwp-updraftplus-extension' );
			add_action( 'mainwp_updraftplus_settings_page_init', array( $this, 'updraftplus_settings_page_init' ) );
			add_action( 'mainwp_updraft_cloudfiles_newuser', array( $this, 'newuser' ) );
	}

	public function updraftplus_settings_page_init() {
			add_action( 'admin_footer', array( $this, 'admin_footer' ) );
			add_filter( 'mainwp_updraft_cloudfiles_apikeysetting', array( $this, 'apikeysettings' ) );
	}

	public function apikeysettings( $msg ) {
			$msg = '<a href="#" id="updraft_cloudfiles_newapiuser">' . __( 'Create a new API user with access to only this container (rather than your whole account)', 'mainwp-updraftplus-extension' ) . '</a>';
			return $msg;
	}

	public function newuser() {
		
	}

	public function admin_footer() {
			?>
			<style type="text/css">
				#updraft_cfnewapiuser_form label { float: left; clear:left; width: 200px;}
				#updraft_cfnewapiuser_form input[type="text"], #updraft_cfnewapiuser_form select { float: left; width: 230px; }
			</style>
			<div id="updraft-cfnewapiuser-modal" title="<?php _e( 'Create new API user and container', 'mainwp-updraftplus-extension' ); ?>">
				<div id="updraft_cfnewapiuser_form">
					<p style="margin:1px; padding-top:0; clear: left; float: left;">
						<em><?php _e( 'Enter your Rackspace admin username/API key (so that Rackspace can authenticate your permission to create new users), and enter a new (unique) username and email address for the new user and a container name.', 'mainwp-updraftplus-extension' ); ?></em>
					</p>
					<div id="updraft-cfnewapiuser-results" style="clear: left; float: left;"><p><p></div>

					<p style="margin-top:3px; padding-top:0; clear: left; float: left;">

						<label for="updraft_cfnewapiuser_accountlocation"><?php _e( 'US or UK Rackspace Account', 'mainwp-updraftplus-extension' ); ?></label>
						<select title="<?php _e( 'Accounts created at rackspacecloud.com are US accounts; accounts created at rackspace.co.uk are UK accounts.', 'mainwp-updraftplus-extension' ); ?>" id="updraft_cfnewapiuser_accountlocation">
							<?php
							$accounts = array(
								'us' => __( 'US (default)', 'mainwp-updraftplus-extension' ),
								'uk' => __( 'UK', 'mainwp-updraftplus-extension' ),
							);
							$selaccount = 'us';
							foreach ( $accounts as $acc => $desc ) {
									?><option <?php if ( $selaccount == $acc ) { echo 'selected="selected"'; } ?> value="<?php echo $acc; ?>"><?php echo htmlspecialchars( $desc ); ?></option><?php
							}
							?>
							</select>

							<label for="updraft_cfnewapiuser_adminusername"><?php _e( 'Admin Username', 'mainwp-updraftplus-extension' ); ?></label> <input type="text" id="updraft_cfnewapiuser_adminusername" value="">
							<label for="updraft_cfnewapiuser_adminapikey"><?php _e( 'Admin API Key', 'mainwp-updraftplus-extension' ); ?></label> <input type="text" id="updraft_cfnewapiuser_adminapikey" value="">
							<label for="updraft_cfnewapiuser_newuser"><?php _e( "New User's Username", 'mainwp-updraftplus-extension' ); ?></label> <input type="text" id="updraft_cfnewapiuser_newuser" value="">
							<label for="updraft_cfnewapiuser_newemail"><?php _e( "New User's Email Address", 'mainwp-updraftplus-extension' ); ?></label> <input type="text" id="updraft_cfnewapiuser_newemail" value="">

							<label for="updraft_cfnewapiuser_region"><?php _e( 'Cloud Files Storage Region', 'mainwp-updraftplus-extension' ); ?>:</label>
							<select id="updraft_cfnewapiuser_region">
								<?php
								$regions = array(
								'DFW' => __( 'Dallas (DFW) (default)', 'mainwp-updraftplus-extension' ),
								'SYD' => __( 'Sydney (SYD)', 'mainwp-updraftplus-extension' ),
								'ORD' => __( 'Chicago (ORD)', 'mainwp-updraftplus-extension' ),
								'IAD' => __( 'Northern Virginia (IAD)', 'mainwp-updraftplus-extension' ),
								'HKG' => __( 'Hong Kong (HKG)', 'mainwp-updraftplus-extension' ),
								);
								// 'LON' => __('London (LON)', 'mainwp-updraftplus-extension')
								$selregion = 'DFW';
								foreach ( $regions as $reg => $desc ) {
										?>
										<option <?php if ( $selregion == $reg ) { echo 'selected="selected"'; } ?> value="<?php echo $reg; ?>"><?php echo htmlspecialchars( $desc ); ?></option>
										<?php
								}
								?>
							</select>
							<label for="updraft_cfnewapiuser_container"><?php _e( 'Cloud Files Container', 'mainwp-updraftplus-extension' ); ?></label> <input type="text" id="updraft_cfnewapiuser_container" value="">

						</p>
						<fieldset>
							<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'updraftplus-credentialtest-nonce' ); ?>">
							<input type="hidden" name="action" value="updraft_ajax">
							<input type="hidden" name="subaction" value="cloudfiles_newuser">
							</div>
						</fieldset>
					</div>
				</div>

				<script>
						jQuery(document).ready(function () {
							jQuery('#updraft_cloudfiles_newapiuser').click(function (e) {
								e.preventDefault();
								jQuery('#updraft-cfnewapiuser-modal').dialog('open');
							});

							var updraft_cfnewapiuser_modal_buttons = {};

							updraft_cfnewapiuser_modal_buttons[mwp_updraftlion.cancel] = function () {
								jQuery(this).dialog("close");
							};
							updraft_cfnewapiuser_modal_buttons[mwp_updraftlion.createbutton] = function () {
								jQuery('#updraft-cfnewapiuser-results').html('<p style="color:green">' + mwp_updraftlion.trying + '</p>');
								var data = {
								action: 'mainwp_updraft_ajax',
								subaction: 'doaction',
								subsubaction: 'mainwp_updraft_cloudfiles_newuser',
								nonce: '<?php echo wp_create_nonce( 'mwp-updraftplus-credentialtest-nonce' ); ?>',
								adminuser: jQuery('#updraft_cfnewapiuser_adminusername').val(),
								adminapikey: jQuery('#updraft_cfnewapiuser_adminapikey').val(),
								newuser: jQuery('#updraft_cfnewapiuser_newuser').val(),
								newemail: jQuery('#updraft_cfnewapiuser_newemail').val(),
								container: jQuery('#updraft_cfnewapiuser_container').val(),
								location: jQuery('#updraft_cfnewapiuser_accountlocation').val(),
								region: jQuery('#updraft_cfnewapiuser_region').val(),
								useservercerts: jQuery('#updraft_ssl_useservercerts').val(),
								disableverify: jQuery('#updraft_ssl_disableverify').val()
								};
								jQuery.post(ajaxurl, data, function (response) {
								try {
									resp = jQuery.parseJSON(response);
								} catch (err) {
									jQuery('#updraft-cfnewapiuser-results').html('<p style="color:red;">' + mwp_updraftlion.servererrorcode + '</p>');
									alert(mwp_updraftlion.unexpectedresponse + ' ' + response);
									return;
								}
								if (resp.e == 1) {
									jQuery('#updraft-cfnewapiuser-results').html('<p style="color:red;">' + resp.m + '</p>');
								} else if (resp.e == 0) {
									jQuery('#updraft-cfnewapiuser-results').html('<p style="color:green;">' + resp.m + '</p>');
									jQuery('#updraft_cloudfiles_user').val(resp.u);
									jQuery('#updraft_cloudfiles_apikey').val(resp.k);
									jQuery('#updraft_cloudfiles_authurl').val(resp.a);
									jQuery('#updraft_cloudfiles_region').val(resp.r);
									jQuery('#updraft_cloudfiles_path').val(resp.c);
									jQuery('#updraft_cloudfiles_newapiuser').after('<br><strong>' + mwp_updraftlion.newuserpass + '</strong> ' + resp.p);
									jQuery('#updraft-cfnewapiuser-modal').dialog('close');
								}
								});
							};
							jQuery("#updraft-cfnewapiuser-modal").dialog({
								autoOpen: false, height: 465, width: 555, modal: true,
								buttons: updraft_cfnewapiuser_modal_buttons
							});

						});
				</script>
				<?php
	}
}
