<?php

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed.' ); }

require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/s3.php' );

# Migrate options to new-style storage - Jan 2014

class MainWP_Updraft_Plus_BackupModule_s3generic extends MainWP_Updraft_Plus_BackupModule_s3 {

	protected function set_region( $obj, $region = '' ) {
			$config = $this->get_config();
			$endpoint = ('' != $region && 'n/a' != $region) ? $region : $config['endpoint'];
			global $mainwp_updraftplus;
			$mainwp_updraftplus->log( "Set endpoint: $endpoint" );
			$obj->setEndpoint( $endpoint );
	}

	public function get_credentials() {
			return array( 'updraft_s3generic' );
	}

	function get_config() {
        global $mainwp_updraftplus;
        $opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_s3generic' ); //$mainwp_updraftplus->get_job_option('updraft_s3generic');
		if ( ! is_array( $opts ) ) {
			$opts = array( 'accesskey' => '', 'secretkey' => '', 'path' => '' );                 
        }
        $opts['whoweare'] = 'S3';
        $opts['whoweare_long'] = __( 'S3 (Compatible)', 'mainwp-updraftplus-extension' );
        $opts['key'] = 's3generic';
        return $opts;
	}

	public function config_print() {
			// 5th parameter = control panel URL
			// 6th = image HTML
			$this->config_print_engine( 's3generic', 'S3', __( 'S3 (Compatible)', 'mainwp-updraftplus-extension' ), 'S3', '', '', true );
	}

	public function config_print_javascript_onready() {
			//$this->config_print_javascript_onready_engine( 's3generic', 'S3' );
	}

	public function credentials_test() {
			$this->credentials_test_engine( $this->get_config() );
	}
}
