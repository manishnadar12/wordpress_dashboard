<?php

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access.' ); }

// Necessary to place the code in a separate file, because it uses namespaces, which cause a fatal error in PHP 5.2
if ( version_compare( phpversion(), '5.3.3', '>=' ) ) {
		require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/openstack2.php' );
} else {
		require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/insufficient.php' );

	class MainWP_Updraft_Plus_BackupModule_openstack extends MainWP_Updraft_Plus_BackupModule_insufficientphp {

		public function __construct() {
				parent::__construct( 'openstack', 'OpenStack', '5.3.3' );
		}
	}

}
