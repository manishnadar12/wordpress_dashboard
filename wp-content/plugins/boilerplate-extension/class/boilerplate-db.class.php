<?php
class Boilerplate_DB {

	private $mainwp_boilerplate_db_version = '1.6';
	// Singleton
	private static $instance = null;
	private $table_prefix;
	public $default_tokens;

	static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new Boilerplate_DB();
		}
		return self::$instance;
	}
	// Constructor
	function __construct() {

		global $wpdb;
		$this->table_prefix   = $wpdb->prefix . 'mainwp_';
		$this->default_tokens = array(
			'url.site'     => __( 'Display site URL', 'boilerplate-extension' ),
			'name.site'    => __( 'Display site Name', 'boilerplate-extension' ),
			'state.site'   => __( 'Display site State', 'boilerplate-extension' ),
			'city.site'    => __( 'Display site City', 'boilerplate-extension' ),
			'address.site' => __( 'Display site Address', 'boilerplate-extension' ),
		);
	}

	function table_name( $suffix ) {

		return $this->table_prefix . $suffix;
	}

	// Support old & new versions of WordPress (3.9+)
	public static function use_mysqli() {

		/** @var $wpdb wpdb */
		if ( ! function_exists( 'mysqli_connect' ) ) {
			return false; }

		global $wpdb;
		return ( $wpdb->dbh instanceof mysqli );
	}

	// Installs new DB
	function install() {

		global $wpdb;
		$currentVersion = get_site_option( 'mainwp_boilerplate_db_version' );

		if ( $currentVersion == $this->mainwp_boilerplate_db_version ) {
			return; }

		$charset_collate = $wpdb->get_charset_collate();
		$sql             = array();

		$tbl = 'CREATE TABLE `' . $this->table_name( 'boilerplate_token' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`token_name` varchar(512) NOT NULL DEFAULT "",
`token_description` text NOT NULL,
`type` tinyint(1) NOT NULL DEFAULT 0';
		if ( '' == $currentVersion ) {
				$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		// will drop the site_url field
		$tbl = 'CREATE TABLE `' . $this->table_name( 'boilerplate_site_token' ) . '` (
`id` int(11) NOT NULL AUTO_INCREMENT,
`site_url` varchar(255) NOT NULL,
`site_id` int(11) NOT NULL,
`token_id` int(12) NOT NULL,
`token_value` varchar(512) NOT NULL';
		if ( '' == $currentVersion ) {
					$tbl .= ',
PRIMARY KEY  (`id`)  '; }
		$tbl  .= ') ' . $charset_collate;
		$sql[] = $tbl;

		error_reporting( 0 ); // make sure to disable any error output
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		foreach ( $sql as $query ) {
			dbDelta( $query );
		}
		// global $wpdb;
		// echo $wpdb->last_error;
		// exit();
		foreach ( $this->default_tokens as $token_name => $token_description ) {

			$token = array(
				'type'              => 1,
				'token_name'        => $token_name,
				'token_description' => $token_description,
			);

			if ( $current = $this->get_tokens_by( 'token_name', $token_name ) ) { // ok
				$this->update_token( $current->id, $token );
			} else {
				$this->add_token( $token );
			}
		}
		$this->check_update( $currentVersion );
		update_option( 'mainwp_boilerplate_db_version', $this->mainwp_boilerplate_db_version );
	}

	public function check_update( $currentVersion ) {

		global $wpdb;

		if ( ! empty( $currentVersion ) ) {
			if ( version_compare( $currentVersion, '1.5', '<' ) ) {
				global $mainWPBoilerplateExtensionActivator;
				$qry         = ' SELECT * FROM ' . $this->table_name( 'boilerplate_site_token' ) .
						' WHERE 1 = 1';
				$site_tokens = $wpdb->get_results( $qry );
				// to fix compatible data
				foreach ( $site_tokens as $token ) {
					if ( $token->site_url != '' ) {
						$website = apply_filters( 'mainwp_getwebsitesbyurl', $token->site_url );
						if ( $website ) {
							$website = current( $website );
							$this->try_to_fix_compatible( $website->id, $token->token_id, $website->url );
						}
					}
				}
			}
		}
	}

	public function add_token( $token ) {

		/** @var $wpdb wpdb */
		global $wpdb;
		if ( ! empty( $token['token_name'] ) && ! empty( $token['token_description'] ) ) {
			if ( $current  = $this->get_tokens_by( 'token_name', $token['token_name'] ) ) { // ok
				return false; }
			if ( $wpdb->insert( $this->table_name( 'boilerplate_token' ), $token ) ) {
				return $this->get_tokens_by( 'id', $wpdb->insert_id ); // ok
			}
		}
		return false;
	}

	public function update_token( $id, $token ) {

		 /** @var $wpdb wpdb */
		global $wpdb;
		if ( ! empty( $id ) && ! empty( $token['token_name'] ) && ! empty( $token['token_description'] ) ) {
			if ( $wpdb->update( $this->table_name( 'boilerplate_token' ), $token, array( 'id' => intval( $id ) ) ) ) {
				return $this->get_tokens_by( 'id', $id );  // ok
			}
		}
		return false;
	}

	public function get_tokens_by( $by = 'id', $value = null ) {
		global $wpdb;

		if ( empty( $value ) ) {
			return null;
		}

		$sql = '';

		if ( 'id' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'boilerplate_token' ) . ' WHERE `id`=%d ', $value );
		} elseif ( 'token_name' == $by ) {
			$sql = $wpdb->prepare( 'SELECT * FROM ' . $this->table_name( 'boilerplate_token' ) . " WHERE `token_name` = '%s' ", $value );
		}

		$token = null;
		if ( ! empty( $sql ) ) {
			$token = $wpdb->get_row( $sql );
		}
		return $token;
	}

	public function get_tokens() {
		global $wpdb;
		return $wpdb->get_results( 'SELECT * FROM ' . $this->table_name( 'boilerplate_token' ) . ' WHERE 1 = 1 ORDER BY type DESC, token_name ASC' );
	}

	public function get_token_of_site( $site_id, $token_id ) {
		global $wpdb;

		$query = ' SELECT * FROM ' . $this->table_name( 'boilerplate_site_token' ) .
				' WHERE site_id = ' . intval( $site_id ) .
				' AND token_id = ' . intval( $token_id );

		return $wpdb->get_results( $query );

	}

	public function get_indexed_site_tokens( $website ) {

		global $wpdb;

		if ( empty( $website ) ) {
			return array();
		}

		if ( is_object( $website ) ) {
			$site_id   = $website->id;
			$url       = $website->url;
			$site_name = $website->name;

			$default   = array(
				'url.site'  => $url,
				'name.site' => $site_name,
			);
			
		} else {
			$site_id = $website;
			$default = array();
		}

		// to compatible data.
		$qry = 'SELECT * FROM ' .
				$this->table_name( 'boilerplate_site_token' ) . " 
				WHERE site_url = '" . $url . "' 
				OR site_id = " . intval( $site_id );

		$site_tokens = $wpdb->get_results( $qry );

		$return = array();

		if ( is_array( $site_tokens ) ) {
			foreach ( $site_tokens as $token ) {
				$return[ $token->token_id ] = $token;
			}
		}
		// get default token value if empty.
		$tokens = $this->get_tokens();
		if ( is_array( $tokens ) ) {
			foreach ( $tokens as $token ) {
				// check default tokens if it is empty.
				if ( ! empty( $token ) && ( $token->type == 1 ) && ( ! isset( $return[ $token->id ] ) || empty( $return[ $token->id ] ) ) ) {
					if ( ! isset( $return[ $token->id ] ) ) {
						$return[ $token->id ] = new stdClass();
					}
					$return[ $token->id ]->token_value = isset( $default[ $token->token_name ] ) ? $default[ $token->token_name ] : '';
				}
			}
		}
		return $return;
	}

	public function update_token_site( $site_id, $token_id, $token_value ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		if ( empty( $token_id ) || empty( $site_id ) ) {
			return false;
		}

		// fixed to compatible data
		$current = self::get_instance()->get_token_of_site( $site_id, $token_id );

		if ( $current ) {
			// update token value
			$sql = 'UPDATE ' . $this->table_name( 'boilerplate_site_token' ) .
					" SET token_value = '" . $this->escape( $token_value ) . "' " .
					' WHERE token_id = ' . intval( $token_id ) .
					' AND site_id = ' . intval( $site_id );
			if ( $wpdb->query( $sql ) ) {
				return $this->get_tokens_by( 'id', $token_id ); // ok
			}
		} else {
			if ( $wpdb->insert(
				$this->table_name( 'boilerplate_site_token' ),
				array(
					'token_id'    => $token_id,
					'token_value' => $token_value,
					'site_url'    => '', // deprecated
					'site_id'     => $site_id,
				)
			) ) {
				return true;
			}
		}

		return false;
	}


	public function try_to_fix_compatible( $site_id, $token_id, $siteUrl ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		if ( empty( $token_id ) || empty( $site_id ) || empty( $siteUrl ) ) {
			return false;
		}

		$sql = 'SELECT * FROM ' . $this->table_name( 'boilerplate_site_token' ) .
				" WHERE site_url = '" . $this->escape( $siteUrl ) . "' AND token_id = " . intval( $token_id );

		$value_by_url_of_site = $wpdb->get_row( $sql );

		// found token with site-url, so fix it.
		if ( $value_by_url_of_site ) {
			$update_id        = false;
			$sql              = 'SELECT * FROM ' . $this->table_name( 'boilerplate_site_token' ) .
				" WHERE site_id = '" . $this->escape( $site_id ) . "' AND token_id = " . intval( $token_id );
			$value_by_site_id = $wpdb->get_row( $sql );

			if ( $value_by_site_id ) {
				if ( $value_by_url_of_site->id != $value_by_site_id->id ) {
					// delete this site token
					$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'boilerplate_site_token' ) . ' WHERE id = %d ', $value_by_url_of_site->id ) );
					$update_id = $value_by_site_id->id;
				} else {
					$update_id = $value_by_url_of_site->id;
				}
			} else {
				$update_id = $value_by_url_of_site->id;
			}

			if ( $update_id ) {
				$sql = 'UPDATE ' . $this->table_name( 'boilerplate_site_token' ) .
					' SET site_id = ' . intval( $site_id ) . // update this field
					", site_url = '' " . // clear this, not used any more
					' WHERE id = ' . intval( $update_id );
				$wpdb->query( $sql );
			}
		} else {
			// ok corrected data
		}
		return true;
	}


	public function delete_tokens_of_site_by( $by, $val ) {
		global $wpdb;

		if ( ! empty( $val ) ) {
			if ( $by == 'site_id' ) {
				return $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'boilerplate_site_token' ) . ' WHERE site_id = %d ', $val ) );
			} elseif ( $by == 'token_id' ) {
				return $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'boilerplate_site_token' ) . ' WHERE token_id = %d ', $val ) );
			}
		}

		return false;
	}

	public function delete_token_by_id( $token_id ) {
		global $wpdb;

		if ( $token_id ) {
			$return = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->table_name( 'boilerplate_token' ) . ' WHERE id = %d ', $token_id ) );
			$return = $this->delete_tokens_of_site_by( 'token_id', $token_id ) || $return; // ok, delete token with token_id so delete all the tokens of sites with the token_id
			return $return;
		}

		return false;
	}

	protected function escape( $data ) {

		/** @var $wpdb wpdb */
		global $wpdb;

		if ( function_exists( 'esc_sql' ) ) {
			return esc_sql( $data );
		} else {
			return $wpdb->escape( $data ); }
	}

	public function query( $sql ) {

		if ( null == $sql ) {
			return false; }

		/** @var $wpdb wpdb */
		global $wpdb;
		$result = @self::_query( $sql, $wpdb->dbh );

		if ( ! $result || ( @self::num_rows( $result ) == 0 ) ) {
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
			return mysqli_fetch_array( $result, ( null == $result_type ? MYSQLI_BOTH : $result_type ) );
		} else {
			return mysql_fetch_array( $result, ( null == $result_type ? MYSQL_BOTH : $result_type ) );
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

	public function get_results_result( $sql ) {

		if ( null == $sql ) {
			return null; }
		/** @var $wpdb wpdb */
		global $wpdb;
		return $wpdb->get_results( $sql, OBJECT_K );
	}
}
