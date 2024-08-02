<?php

class MainWP_Updraftplus_BackupsDB {

	private $mainwp_updraftplus_backups_db_version = '2.0';
	private $table_prefix;
		//Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == MainWP_Updraftplus_BackupsDB::$instance ) {
				MainWP_Updraftplus_BackupsDB::$instance = new MainWP_Updraftplus_BackupsDB();
		}
			return MainWP_Updraftplus_BackupsDB::$instance;
	}

		//Constructor
	function __construct() {
			global $wpdb;
			$this->table_prefix = $wpdb->prefix . 'mainwp_';
	}

	function table_name( $suffix ) {
			return $this->table_prefix . $suffix;
	}

		//Support old & new versions of wordpress (3.9+)
	public static function use_mysqli() {
			/** @var $wpdb wpdb */
		if ( ! function_exists( 'mysqli_connect' ) ) {
				return false; }

			global $wpdb;
			return ($wpdb->dbh instanceof mysqli);
	}

		//Installs new DB
	function install() {
		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_updraftplus_backups_db_version' );

		$rslt = self::get_instance()->query( "SHOW TABLES LIKE '" . $this->table_name( 'updraftplus' ) . "'" );
		if ( @self::num_rows( $rslt ) == 0 ) {
			$currentVersion = false;
		}

		if ( $currentVersion == $this->mainwp_updraftplus_backups_db_version ) {
				return; }

			$charset_collate = $wpdb->get_charset_collate();
			$sql = array();

			$tbl = 'CREATE TABLE `' . $this->table_name( 'updraftplus' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL,
`is_premium` tinyint(1) NOT NULL DEFAULT 0,
`isHidden` tinyint(1) NOT NULL DEFAULT 0,
`lastbackup_gmttime` int(11) NOT NULL,
`settings` longtext NOT NULL DEFAULT "",
`override` tinyint(1) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion ) {
					$tbl .= ',
PRIMARY KEY  (`id`)  '; }
			$tbl .= ') ' . $charset_collate;
			$sql[] = $tbl;

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		foreach ( $sql as $query ) {
				dbDelta( $query );
		}

			//        global $wpdb;
			//        echo $wpdb->last_error;
			//        exit();
			update_option( 'mainwp_updraftplus_backups_db_version', $this->mainwp_updraftplus_backups_db_version );
	}

	public function update_setting_fields_by( $by, $value, $data ) {
		$id = ('id' == $by ) ? $value : 0;
		$site_id = ('site_id' == $by) ? $value : 0;

		if ( $id ) {
			$current = $this->get_setting_by( 'id', $id );
		} else if ( $site_id ) {
			$current = $this->get_setting_by( 'site_id', $site_id );
		} else {
			return false;
		}

		if ( empty( $current ) ) {
			if ( $site_id ) {
				$update = array(
					'site_id' => $site_id,
					'settings' => base64_encode( serialize( $data ) ),
				);
				return $this->update_setting( $update );
			} else {
				return false;
			}
		}

		$new_setting = unserialize( base64_decode( $current->settings ) );

		if ( ! is_array( $new_setting ) ) {
				$new_setting = array(); }

		foreach ( $data as $key => $value ) {
				$new_setting[ $key ] = $value;
		}

		return $this->update_setting(array(
			'site_id' => $current->site_id,
			'settings' => base64_encode( serialize( $new_setting ) ),
		));
	}

	public function delete_setting( $by = 'id', $value = null ) {
			global $wpdb;
		if ( empty( $by ) ) {
				return null; }
			$sql = '';
		if ( 'id' == $by ) {
				$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'updraftplus' ) . ' WHERE `id`=%d ', $value );
		} else if ( 'site_id' == $by ) {
				$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'updraftplus' ) . ' WHERE `site_id` = %d ', $value );
		}

		if ( ! empty( $sql ) ) {
				$wpdb->query( $sql ); }

			return true;
	}

	public function update_setting( $setting ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$id = isset( $setting['id'] ) ? $setting['id'] : 0;
		$site_id = isset( $setting['site_id'] ) ? $setting['site_id'] : 0;

		if ( $id ) {
			if ( $wpdb->update( $this->table_name( 'updraftplus' ), $setting, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_setting_by( 'id', $id ); }
		} else if ( $site_id ) {
			$current = $this->get_setting_by( 'site_id', $site_id );
			if ( $current ) {
				if ( $wpdb->update( $this->table_name( 'updraftplus' ), $setting, array( 'site_id' => intval( $site_id ) ) ) ) {
						return $this->get_setting_by( 'site_id', $site_id );
				}
			} else {
				if ( $wpdb->insert( $this->table_name( 'updraftplus' ), $setting ) ) {
						return $this->get_setting_by( 'id', $wpdb->insert_id ); }
			}
		} else if ( $wpdb->insert( $this->table_name( 'updraftplus' ), $setting ) ) {
				return $this->get_setting_by( 'id', $wpdb->insert_id );
		}
		return false;
	}

	public function get_setting_by( $by = 'id', $value = null, $output = OBJECT ) {
			global $wpdb;

		if ( empty( $by ) || empty( $value ) ) {
				return null; }

			$sql = '';
		if ( 'id' == $by ) {
				$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'updraftplus' ) . ' WHERE `id`=%d ', $value );
		} else if ( 'site_id' == $by ) {
				$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'updraftplus' ) . ' WHERE `site_id` = %d ', $value );
		}

			$setting = null;
		if ( ! empty( $sql ) ) {
				$setting = $wpdb->get_row( $sql, $output ); }
			return $setting;
	}

	public function get_settings( $site_ids = array() ) {
        global $wpdb;
        $where = '1';
        if ($site_ids) {
            $where = ' `site_id` IN (' . implode( ',', $site_ids ) . ') ';
        }
        $sql = 'SELECT * FROM ' . $this->table_name( 'updraftplus' ) . ' WHERE ' . $where;
        return $wpdb->get_results( $sql );
	}

	public function get_settings_field_array() {
		global $wpdb;
		$sql = 'SELECT override, site_id FROM ' . $this->table_name( 'updraftplus' ) . ' WHERE 1';
		$results =  $wpdb->get_results( $sql );
		$return = array();
		if (is_array($results)) {
			foreach($results as $val) {
				$return[$val->site_id] = $val->override;
			}
		}
		return $return;
	}

	public function get_updraft_data_site( $site_ids = array() ) {

        $return = array();
        $settings = $this->get_settings( $site_ids );
		if ( $settings ) {
			foreach ( $settings as $val ) {
					$_setting = unserialize( base64_decode( $val->settings ) );

                    if ( ! is_array( $_setting ) ) {
                            $_setting = array(); }

                    if ( empty( $_setting ) ) {
                            continue;
                    }

					$scheduled = array();
					$scheduled['nextsched_files_gmt'] = isset( $_setting['nextsched_files_gmt'] ) ? $_setting['nextsched_files_gmt'] : 0;
					$scheduled['nextsched_files_timezone'] = isset( $_setting['nextsched_files_timezone'] ) ? $_setting['nextsched_files_timezone'] : '';
					$scheduled['nextsched_database_gmt'] = isset( $_setting['nextsched_database_gmt'] ) ? $_setting['nextsched_database_gmt'] : 0;
					$scheduled['nextsched_database_timezone'] = isset( $_setting['nextsched_database_timezone'] ) ? $_setting['nextsched_database_timezone'] : '';
					$scheduled['nextsched_current_timegmt'] = isset( $_setting['nextsched_current_timegmt'] ) ? $_setting['nextsched_current_timegmt'] : 0;
					$scheduled['nextsched_current_timezone'] = isset( $_setting['nextsched_current_timezone'] ) ? $_setting['nextsched_current_timezone'] : '';
					$scheduled['mainwp_updraft_backup_history_html'] = isset( $_setting['mainwp_updraft_backup_history_html'] ) ? $_setting['mainwp_updraft_backup_history_html'] : '';
					$scheduled['mainwp_updraft_backup_history_count'] = isset( $_setting['mainwp_updraft_backup_history_count'] ) ? $_setting['mainwp_updraft_backup_history_count'] : '';
					$scheduled['updraft_lastbackup_html'] = isset( $_setting['updraft_lastbackup_html'] ) ? $_setting['updraft_lastbackup_html'] : '';
					$scheduled['lastbackup_gmttime'] = $val->lastbackup_gmttime;
					$scheduled['is_premium'] = $val->is_premium;
					$scheduled['override_settings'] = $val->override;
                    $scheduled['isHidden'] = $val->isHidden;
					$return[ $val->site_id ] = $scheduled;
			}
		}
        return $return;
	}

	protected function escape( $data ) {
			/** @var $wpdb wpdb */
			global $wpdb;
		if ( function_exists( 'esc_sql' ) ) {
				return esc_sql( $data ); } else { 					return $wpdb->escape( $data ); }
	}

	public function query( $sql ) {
		if ( null == $sql ) {
				return false; }
			/** @var $wpdb wpdb */
			global $wpdb;
			$result = @self::_query( $sql, $wpdb->dbh );

		if ( ! $result || (@self::num_rows( $result ) == 0) ) {
				return false; }
			return $result;
	}

	public static function _query( $query, $link ) {
		if ( self::use_mysqli() ) {
				return mysqli_query( $link, $query );
		} else {
				return mysql_query( $query, $link );
		}
	}

	public static function fetch_object( $result ) {
		if ( self::use_mysqli() ) {
				return mysqli_fetch_object( $result );
		} else {
				return mysql_fetch_object( $result );
		}
	}

	public static function free_result( $result ) {
		if ( self::use_mysqli() ) {
				return mysqli_free_result( $result );
		} else {
				return mysql_free_result( $result );
		}
	}

	public static function data_seek( $result, $offset ) {
		if ( self::use_mysqli() ) {
				return mysqli_data_seek( $result, $offset );
		} else {
				return mysql_data_seek( $result, $offset );
		}
	}

	public static function fetch_array( $result, $result_type = null ) {
		if ( self::use_mysqli() ) {
				return mysqli_fetch_array( $result, (null == $result_type ? MYSQLI_BOTH : $result_type) );
		} else {
				return mysql_fetch_array( $result, (null == $result_type ? MYSQL_BOTH : $result_type) );
		}
	}

	public static function num_rows( $result ) {
                if ( $result === false ) {
			return 0;
		}
		if ( self::use_mysqli() ) {
				return mysqli_num_rows( $result );
		} else {
				return mysql_num_rows( $result );
		}
	}

	public static function is_result( $result ) {
		if ( self::use_mysqli() ) {
				return ($result instanceof mysqli_result);
		} else {
				return is_resource( $result );
		}
	}

	public function get_results_result( $sql ) {
		if ( null == $sql ) {
				return null; }
			/** @var $wpdb wpdb */
			global $wpdb;
			return $wpdb->get_results( $sql, OBJECT_K );
	}
}
