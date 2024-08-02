<?php
if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed.' ); }

# SDK uses namespacing - requires PHP 5.3 (actually the SDK states its requirements as 5.3.3)

use OpenCloud\Rackspace;

# New SDK - https://github.com/rackspace/php-opencloud and http://docs.rackspace.com/sdks/guide/content/php.html
# Uploading: https://github.com/rackspace/php-opencloud/blob/master/docs/userguide/ObjectStore/Storage/Object.md

require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/openstack-base.php' );

class MainWP_Updraft_Plus_BackupModule_cloudfiles_opencloudsdk extends MainWP_Updraft_Plus_BackupModule_openstack_base {

	public function __construct() {
			parent::__construct( 'cloudfiles', 'Cloud Files', 'Rackspace Cloud Files', '/images/rackspacecloud-logo.png' );
	}

	public function get_client() {
			return $this->client;
	}

	public function get_service( $opts, $useservercerts = false, $disablesslverify = null ) {

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

	public function config_print_middlesection() {
			$opts = $this->get_opts();
			?>
            <div class="ui grid field mwp_updraftplusmethod <?php echo $this->method; ?>">
                <label class="six wide column middle aligned">

                </label>
                 <div class="ui ten wide column">
                     <div class="ui info message"><?php echo MainWP_Updraftplus_Backups::show_notice(); ?></div>
                 </div>
            </div>
            <?php

    }

		# The default parameter here is only to satisfy Strict Standards

	public function config_print_javascript_onready( $keys = array() ) {
			parent::config_print_javascript_onready( array( 'apikey', 'user', 'region', 'authurl' ) );
	}

	public function credentials_test() {

		if ( empty( $_POST['apikey'] ) ) {
				printf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ), __( 'API key', 'mainwp-updraftplus-extension' ) );
				die;
		}

		if ( empty( $_POST['user'] ) ) {
				printf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ), __( 'Username', 'mainwp-updraftplus-extension' ) );
				die;
		}

			$opts = array(
				'user' => $_POST['user'],
				'apikey' => stripslashes( $_POST['apikey'] ),
				'authurl' => $_POST['authurl'],
				'region' => (empty( $_POST['region'] )) ? null : $_POST['region'],
			);

			$this->credentials_test_go( $opts, $_POST['path'], $_POST['useservercerts'], $_POST['disableverify'] );
	}
}
