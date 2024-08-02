<?php

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed.' ); }

class MainWP_Updraft_Plus_BackupModule_ViaAddon {

	private $method;
	private $description;

	public function __construct( $method, $description, $required_php = false, $image = null ) {
			$this->method = $method;
			$this->description = $description;
			$this->required_php = $required_php;
			$this->image = $image;
			$this->error_msg = 'This remote storage method (' . $this->description . ') requires PHP ' . $this->required_php . ' or later';
			$this->error_msg_trans = sprintf( __( 'This remote storage method (%s) requires PHP %s or later.', 'mainwp-updraftplus-extension' ), $this->description, $this->required_php );
	}

	public function action_handler( $action = '' ) {
			return apply_filters( 'mainwp_updraft_' . $this->method . '_action_' . $action, null );
	}

	public function backup( $backup_array ) {

	}

	public function delete( $files, $method_obj = false ) {

	}

	public function listfiles( $match = 'backup_' ) {
		//      return apply_filters('mainwp_updraft_'.$this->method.'_listfiles', new WP_Error('no_addon', sprintf(__('You do not have the UpdraftPlus %s add-on installed - get it from %s','mainwp-updraftplus-extension'), $this->description, 'http://updraftplus.com/shop/')), $match);
	}

		// download method: takes a file name (base name), and removes it from the cloud storage
	public function download( $file ) {

	}

	public function config_print() {

			$link = sprintf( __( '%s support is available as an add-on', 'mainwp-updraftplus-extension' ), $this->description ) . ' - <a href="http://updraftplus.com/shop/' . $this->method . '/">' . __( 'follow this link to get it', 'mainwp-updraftplus-extension' );

			$default = '
		<div class="ui grid field mwp_updraftplusmethod ' . $this->method . '">
            <label class="six wide column middle aligned">
				<h4 class="ui header">' . $this->description . ':</h4>
			</label>
			<div class="ui ten wide column">
			' . (( ! empty( $this->image )) ? '<p><img src="' . MAINWP_UPDRAFT_PLUS_URL . '/images/' . $this->image . '"></p>' : '') . $link . '</a>
            </div>
			</div>';

		if ( version_compare( phpversion(), $this->required_php, '<' ) ) {
				$default .= '<div class="ui grid field mwp_updraftplusmethod ' . $this->method . '">
			<label class="six wide column middle aligned"></label>
			<div class="ui ten wide column">
				<em>
					' . htmlspecialchars( $this->error_msg_trans ) . '
					' . htmlspecialchars( __( 'You will need to ask your web hosting company to upgrade.', 'mainwp-updraftplus-extension' ) ) . '
					' . sprintf( __( 'Your %s version: %s.', 'mainwp-updraftplus-extension' ), 'PHP', phpversion() ) . '
				</em>
                </div>
			</div>';
		}

			echo apply_filters( 'mainwp_updraft_' . $this->method . '_config_print', $default );
	}

	public function config_print_javascript_onready() {
			do_action( 'mainwp_updraft_' . $this->method . '_config_javascript' );
	}

	public function credentials_test() {
			do_action( 'mainwp_updraft_' . $this->method . '_credentials_test' );
			die;
	}
}
