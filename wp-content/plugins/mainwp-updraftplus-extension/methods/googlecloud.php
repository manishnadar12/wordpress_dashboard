<?php

if (!defined('MAINWP_UPDRAFT_PLUS_DIR')) die('No direct access.');

if (version_compare(PHP_VERSION, '5.2.4', '>=')) {
	require_once(MAINWP_UPDRAFT_PLUS_DIR.'/methods/viaaddon-base.php');
	class MainWP_Updraft_Plus_BackupModule_googlecloud extends MainWP_Updraft_Plus_BackupModule_ViaAddon {
		public function __construct() {
			parent::__construct('googlecloud', 'Google Cloud', '5.2.4', 'googlecloud.png');
		}
	}
} else {
	require_once(MAINWP_UPDRAFT_PLUS_DIR.'/methods/insufficient.php');
	class MainWP_Updraft_Plus_BackupModule_googlecloud extends MainWP_Updraft_Plus_BackupModule_insufficientphp {
		public function __construct() {
			parent::__construct('googlecloud', 'Google Cloud', '5.2.4', 'googlecloud.png');
		}
	}
}
