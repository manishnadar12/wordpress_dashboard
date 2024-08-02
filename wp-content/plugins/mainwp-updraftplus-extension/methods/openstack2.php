<?php
if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed.' ); }

# SDK uses namespacing - requires PHP 5.3 (actually the SDK states its requirements as 5.3.3)

use OpenCloud\OpenStack;

require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/openstack-base.php' );

class MainWP_Updraft_Plus_BackupModule_openstack extends MainWP_Updraft_Plus_BackupModule_openstack_base {

	public function __construct() {
			# 4th parameter is a relative (to MAINWP_UPDRAFT_PLUS_DIR) logo URL, which should begin with /, should we get approved for use of the OpenStack logo in future (have requested info)
			parent::__construct( 'openstack', 'OpenStack', 'OpenStack (Swift)', '' );
	}

		# $opts: 'tenant', 'user', 'password', 'authurl', (optional) 'region'

	public function get_service( $opts, $useservercerts = false, $disablesslverify = null ) {

	}

	public function get_credentials() {
			return array( 'updraft_openstack' );
	}

	public function get_opts() {
			global $mainwp_updraftplus;
			$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_openstack' );  // $mainwp_updraftplus->get_job_option('updraft_openstack');
		if ( ! is_array( $opts ) ) {
				$opts = array( 'user' => '', 'authurl' => '', 'password' => '', 'tenant' => '', 'path' => '', 'region' => '' ); }
			return $opts;
	}

	public function config_print_middlesection() {
			$opts = $this->get_opts();
			?>
			<div class="ui grid field mwp_updraftplusmethod <?php echo $this->method; ?>">
				<label class="six wide column middle aligned"></label>
                <div class="ui ten wide column">
                <?php _e( 'Get your access credentials from your OpenStack Swift provider, and then pick a container name to use for storage. This container will be created for you if it does not already exist.', 'mainwp-updraftplus-extension' ); ?> <a href="http://updraftplus.com/faqs/there-appear-to-be-lots-of-extra-files-in-my-rackspace-cloud-files-container/"><?php _e( 'Also, you should read this important FAQ.', 'mainwp-updraftplus-extension' ); ?></a>
                <div class="ui hidden fitted divider"></div>
                <?php echo ucfirst( __( 'authentication URI', 'mainwp-updraftplus-extension' ) ); ?>:
                <div class="ui hidden fitted divider"></div>
                <input type="text" autocomplete="off" style="width: 364px" id="updraft_openstack_authurl" name="mwp_updraft_openstack[authurl]" value="<?php echo htmlspecialchars( $opts['authurl'] ) ?>" />
                <br>
                <em><?php echo _x( 'This needs to be a v2 (Keystone) authentication URI; v1 (Swauth) is not supported.', 'Keystone and swauth are technical terms which cannot be translated', 'mainwp-updraftplus-extension' ); ?></em>
                <div class="ui hidden fitted divider"></div>
                <a href="http://docs.openstack.org/grizzly/openstack-compute/admin/content/users-and-projects.html" title="<?php _e( 'Follow this link for more information', 'mainwp-updraftplus-extension' ); ?>"><?php _e( 'Tenant', 'mainwp-updraftplus-extension' ); ?></a>:
                <div class="ui hidden fitted divider"></div>
                <input type="text" autocomplete="off" style="width: 364px" id="updraft_openstack_tenant" name="mwp_updraft_openstack[tenant]" value="<?php echo htmlspecialchars( $opts['tenant'] ) ?>" />
                <div class="ui hidden divider"></div>
                <?php _e( 'Region', 'mainwp-updraftplus-extension' ); ?>:
                <div class="ui hidden fitted divider"></div>
                <input type="text" autocomplete="off" style="width: 364px" id="updraft_openstack_region" name="mwp_updraft_openstack[region]" value="<?php echo htmlspecialchars( $opts['region'] ) ?>" />
                <br>
                <em><?php _e( 'Leave this blank, and a default will be chosen.', 'mainwp-updraftplus-extension' ); ?></em>
                <div class="ui hidden divider"></div>
                <?php _e( 'Username', 'mainwp-updraftplus-extension' ); ?>:
                <div class="ui hidden fitted divider"></div>
                <input type="text" autocomplete="off" style="width: 364px" id="updraft_openstack_user" name="mwp_updraft_openstack[user]" value="<?php echo htmlspecialchars( $opts['user'] ) ?>" />
                <div class="ui hidden divider"></div>
                <?php _e( 'Password', 'mainwp-updraftplus-extension' ); ?>:
                <div class="ui hidden fitted divider"></div>
                <input type="<?php echo apply_filters( 'mainwp_updraftplus_admin_secret_field_type', 'text' ); ?>" autocomplete="off" style="width: 364px" id="updraft_openstack_password" name="mwp_updraft_openstack[password]" value="<?php echo htmlspecialchars( $opts['password'] ); ?>" />
                <div class="ui hidden divider"></div>
                <?php echo __( 'Container', 'mainwp-updraftplus-extension' ); ?>:
                <div class="ui hidden fitted divider"></div>
                <input type="text" style="width: 364px" name="mwp_updraft_openstack[path]" id="updraft_openstack_path" value="<?php echo htmlspecialchars( $opts['path'] ); ?>" />
                <div class="ui hidden divider"></div>
                </div>
            </div>
        <?php
	}

		// The default value is needed only to satisfy PHP strict standards
	public function config_print_javascript_onready( $keys = array() ) {
			parent::config_print_javascript_onready( array( 'tenant', 'user', 'password', 'authurl', 'region' ) );
	}

	public function credentials_test() {

		if ( empty( $_POST['user'] ) ) {
				printf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ), __( 'username', 'mainwp-updraftplus-extension' ) );
				die;
		}

		if ( empty( $_POST['password'] ) ) {
				printf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ), __( 'password', 'mainwp-updraftplus-extension' ) );
				die;
		}

		if ( empty( $_POST['tenant'] ) ) {
				printf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ), _x( 'tenant', '"tenant" is a term used with OpenStack storage - Google for "OpenStack tenant" to get more help on its meaning', 'mainwp-updraftplus-extension' ) );
				die;
		}

		if ( empty( $_POST['authurl'] ) ) {
				printf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ), __( 'authentication URI', 'mainwp-updraftplus-extension' ) );
				die;
		}

			$opts = array(
				'user' => stripslashes( $_POST['user'] ),
				'password' => stripslashes( $_POST['password'] ),
				'authurl' => stripslashes( $_POST['authurl'] ),
				'tenant' => stripslashes( $_POST['tenant'] ),
				'region' => ( ! empty( $_POST['region'] )) ? $_POST['region'] : '',
			);

			$this->credentials_test_go( $opts, stripslashes( $_POST['path'] ), $_POST['useservercerts'], $_POST['disableverify'] );
	}
}
