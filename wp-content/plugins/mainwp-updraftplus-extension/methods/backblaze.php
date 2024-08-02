<?php

if (!defined('MAINWP_UPDRAFT_PLUS_DIR')) die('No direct access.');

if (version_compare(phpversion(), '5.3.3', '>=')) {
	if (class_exists('MainWP_Updraft_Plus_Addons_RemoteStorage_backblaze')) {
		class MainWP_Updraft_Plus_BackupModule_backblaze extends MainWP_Updraft_Plus_Addons_RemoteStorage_backblaze {
			public function __construct() {
				parent::__construct('backblaze', 'Backblaze', true, true);
			}
		}
    } 
	else {
		class MainWP_Updraft_Plus_BackupModule_backblaze extends MainWP_Updraft_Plus_BackupModule_ViaAddon {
			public function __construct() {
				parent::__construct('backblaze', 'Backblaze', '5.3.3', 'backblaze.png');
			}
		}
	}
} else {
	require_once(MAINWP_UPDRAFT_PLUS_DIR.'/methods/insufficient.php');
	class MainWP_Updraft_Plus_BackupModule_backblaze extends MainWP_Updraft_Plus_BackupModule_insufficientphp {
		public function __construct() {
			parent::__construct('backblaze', 'Backblaze', '5.3.3', 'backblaze.png');
		}
	}    	
}

		
