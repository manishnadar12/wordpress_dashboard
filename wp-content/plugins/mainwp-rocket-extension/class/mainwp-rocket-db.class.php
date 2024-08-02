<?php
class MainWP_Rocket_DB {
	private $mainwp_wp_rocket_db_version = '1.5';
	private $table_prefix;

	//Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null === MainWP_Rocket_DB::$instance ) {
			MainWP_Rocket_DB::$instance = new MainWP_Rocket_DB();
		}
		return MainWP_Rocket_DB::$instance;
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
			return false;
		}

		global $wpdb;
		return ( $wpdb->dbh instanceof mysqli );
	}

	//Installs new DB
	function install() {
		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_wp_rocket_db_version' );
		if ( $currentVersion == $this->mainwp_wp_rocket_db_version ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
    $result_wprocket = $this->query( "SHOW TABLES LIKE '" . $this->table_name( 'wprocket' ) . "'" );

		$sql = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'wprocket' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_id` int(11) NOT NULL,
`others` text NOT NULL DEFAULT "",
`settings` longtext NOT NULL DEFAULT "",
`is_active` tinyint(1) NOT NULL DEFAULT 0,
`override` tinyint(1) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion || empty($result_wprocket) ) {
					$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl .= ') ' . $charset_collate;
		$sql[] = $tbl;

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}
		update_option( 'mainwp_wp_rocket_db_version', $this->mainwp_wp_rocket_db_version );
	}


	public function delete_wprocket( $by = 'id', $value = null ) {
		global $wpdb;
		if ( empty( $by ) ) {
			return null;
		}
		$sql = '';
		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'wprocket' ) . ' WHERE `id`=%d ', $value );
		} else if ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'wprocket' ) . ' WHERE `site_id` = %d ', $value );
		}

		if ( ! empty( $sql ) ) {
			$wpdb->query( $sql );
		}

		return true;
	}

	public function update_wprocket( $setting ) {

		 /** @var $wpdb wpdb */
		global $wpdb;
		$id = isset( $setting['id'] ) ? $setting['id'] : 0;
		$site_id = isset( $setting['site_id'] ) ? $setting['site_id'] : 0;

		if ( $id ) {
			if ( $wpdb->update( $this->table_name( 'wprocket' ), $setting, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_wprocket_by( 'id', $id );
			}
		} else if ( $site_id ) {
			$current = $this->get_wprocket_by( 'site_id', $site_id );
			if ( $current ) {
				if ( $wpdb->update( $this->table_name( 'wprocket' ), $setting, array( 'site_id' => intval( $site_id ) ) ) ) {
					return $this->get_wprocket_by( 'site_id', $site_id );
				}
			} else {
				if ( $wpdb->insert( $this->table_name( 'wprocket' ), $setting ) ) {
					return $this->get_wprocket_by( 'id', $wpdb->insert_id );
				}
			}
		} else if ( $wpdb->insert( $this->table_name( 'wprocket' ), $setting ) ) {
			return $this->get_wprocket_by( 'id', $wpdb->insert_id );
		}
		return false;
	}

	public function get_wprocket_settings_by( $by = 'id', $value = null ) {
		if ( empty( $by ) || empty( $value ) ) {
			return null; }
		$wp_rockets = $this->get_wprocket_by( $by , $value );
		if ( $wp_rockets ) {
			return unserialize( base64_decode( $wp_rockets->settings ) );
		}
		return null;
	}

	public function get_wprocket_by( $by = 'id', $value = null, $fields = '', $output = OBJECT ) {
		global $wpdb;
		if ( empty( $by ) || empty( $value ) ) {
			return null;
		}
		if ( empty( $fields ) ) {
			$fields = '*';
		}
		$sql = '';
		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( "SELECT {$fields} FROM " . $this->table_name( 'wprocket' ) . ' WHERE `id`=%d ', $value );
		} else if ( 'site_id' == $by ) {
			$sql = $wpdb->prepare( "SELECT {$fields} FROM " . $this->table_name( 'wprocket' ) . ' WHERE `site_id` = %d ', $value );
		}
		if ( ! empty( $sql ) ) {
			return $wpdb->get_row( $sql, $output );
		}
		return null;
	}

	public function get_wprockets( $site_ids = array() ) {
		global $wpdb;
		$str_ids = '';
		if ( is_array( $site_ids ) && count( $site_ids ) > 0 ) {
			$str_ids = implode( ',', $site_ids );
		}
		if ( ! empty( $str_ids ) ) {
			$sql = 'SELECT * FROM ' . $this->table_name( 'wprocket' ) . ' WHERE `site_id` IN (' . $str_ids . ') ';
		} else {
			$sql = 'SELECT * FROM ' . $this->table_name( 'wprocket' ) . ' WHERE 1 ';
		}
		return  $wpdb->get_results( $sql );
	}

	public function get_wprockets_data( $site_ids = array() ) {
		$wp_rockets = $this->get_wprockets( $site_ids );
		if ( count( $wp_rockets ) > 0 ) {
			$return = array();
			foreach ( $wp_rockets as $wp_rocket ) {
				$data = array();
				$data['override'] = $wp_rocket->override;
				$data['is_active'] = $wp_rocket->is_active;
				$data['others'] = unserialize( base64_decode( $wp_rocket->others ) );
				$return[ $wp_rocket->site_id ] = $data;
			}
			return $return;
		}
		return array();
	}

	protected function escape( $data ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		if ( function_exists( 'esc_sql' ) ) {
			return esc_sql( $data );
		} else {
			return $wpdb->escape( $data );
		}
	}

	public function query( $sql ) {
		if ( null == $sql ) {
			return false;
		}
		/** @var $wpdb wpdb */
		global $wpdb;
		$result = @self::_query( $sql, $wpdb->dbh );

		if ( ! $result || ( @self::num_rows( $result ) == 0 ) ) {
			return false;
		}
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
			return mysqli_fetch_array( $result, ( null == $result_type ? MYSQLI_BOTH : $result_type ) );
		} else {
			return mysql_fetch_array( $result, ( null == $result_type  ? MYSQL_BOTH : $result_type ) );
		}
	}

	public static function num_rows( $result ) {
		if ( self::use_mysqli() ) {
			return mysqli_num_rows( $result );
		} else {
			return mysql_num_rows( $result );
		}
	}

	public static function is_result( $result ) {
		if ( self::use_mysqli() ) {
			return ( $result instanceof mysqli_result );
		} else {
			return is_resource( $result );
		}
	}
}
