<?php

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed.' ); }

require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/viaaddon-base.php' );

class MainWP_Updraft_Plus_BackupModule_sftp extends MainWP_Updraft_Plus_BackupModule_ViaAddon {

	public function __construct() {
			parent::__construct( 'sftp', 'SFTP/SCP' );
	}
}
