<?php

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access.' ); }

if ( version_compare( phpversion(), '5.3.3', '>=' ) ) {
		require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/viaaddon-base.php' );

	class MainWP_Updraft_Plus_BackupModule_onedrive extends MainWP_Updraft_Plus_BackupModule_ViaAddon {

		public function __construct() {
				parent::__construct( 'onedrive', 'Microsoft OneDrive', '5.3.3', 'onedrive.png' );
		}
	}

} else {
		require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/insufficient.php' );

		class MainWP_Updraft_Plus_BackupModule_onedrive extends MainWP_Updraft_Plus_BackupModule_insufficientphp {

		public function __construct() {
				parent::__construct( 'onedrive', 'Microsoft OneDrive', '5.3.3', 'onedrive.png' );
		}
	}

}
