<?php

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed.' ); }

require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/s3.php' );

# Migrate options to new-style storage - Jan 2014

class MainWP_Updraft_Plus_BackupModule_dreamobjects extends MainWP_Updraft_Plus_BackupModule_s3 {

	protected function set_region( $obj, $region ) {
			$config = $this->get_config();
			global $mainwp_updraftplus;
			$mainwp_updraftplus->log( 'Set endpoint: ' . $config['endpoint'] );
			$obj->setEndpoint( $config['endpoint'] );
	}

	public function get_credentials() {
			return array( 'updraft_dreamobjects' );
	}

	protected function get_config() {
			global $mainwp_updraftplus;
			$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_dreamobjects' ); // $mainwp_updraftplus->get_job_option('updraft_dreamobjects');
            if ( ! is_array( $opts ) ) {
                    $opts = array( 'accesskey' => '', 'secretkey' => '', 'path' => '' );

            }
			$opts['whoweare'] = 'DreamObjects';
			$opts['whoweare_long'] = 'DreamObjects';
			$opts['key'] = 'dreamobjects';
            if (empty($opts['endpoint'])) {
                $opts['endpoint'] = 'objects-us-west-1.dream.io';
            }
			return $opts;
	}

	public function config_print() {
			$this->config_print_engine( 'dreamobjects', 'DreamObjects', 'DreamObjects', 'DreamObjects', 'https://panel.dreamhost.com/index.cgi?tree=storage.dreamhostobjects', '<a href="http://dreamhost.com/cloud/dreamobjects/"><img alt="DreamObjects" src="' . MAINWP_UPDRAFT_PLUS_URL . '/images/dreamobjects_logo-horiz-2013.png"></a>' );
	}

	public function config_print_javascript_onready() {
			//$this->config_print_javascript_onready_engine( 'dreamobjects', 'DreamObjects' );
	}

    protected function get_partial_configuration_template_for_endpoint( $opts = array()) {
        $endpoint = $opts['endpoint'];
        ?>
        <div class="ui grid field mwp_updraftplusmethod dreamobjects" style="display: flex;">
                <label class="six wide column middle aligned"></label>
                <div class="ui ten wide column">
                    DreamObjects end-point
                    <div class="ui hidden divider"></div>
                    <select id="updraft_dreamobjects_endpoint" name="mwp_updraft_dreamobjects[endpoint]" style="width: 360px">
                        <option value="objects-us-west-1.dream.io" <?php echo $endpoint == 'objects-us-west-1.dream.io' ? 'selected="selected"' : ''; ?> >objects-us-west-1.dream.io</option>
                        <option value="objects-us-east-1.dream.io" <?php echo $endpoint == 'objects-us-east-1.dream.io' ? 'selected="selected"' : ''; ?>>objects-us-east-1.dream.io (launching some time in 2018)</option>
                    </select>
                </div>
            </div>
        <?php
	}

	public function credentials_test() {
			$this->credentials_test_engine( $this->get_config() );
	}
}

?>
