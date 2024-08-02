<?php
/*
  UpdraftPlus Addon: migrator:Migrate a WordPress site to a different location.
  Description: Import a backup into a different site, including database search-and-replace. Ideal for development and testing and cloning of sites.
  Version: 2.7.1
  Shop: /shop/migrator/
  Latest Change: 1.9.53
 */

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

// TODO: single-into-multisite migrations:
// TODO: http://iandunn.name/comprehensive-wordpress-multisite-migrations/
// So far: database prefix is switched; a new site is created. Next: selectively add plugins + themes (don't replace). And network-activate them.
// TODO: Then test path-based multisites too
// TODO: The siteurl at the top of the db dump should actually be what's in the DB, and not any over-rides ... ?
// TODO: Search for other TODOs in the code and in updraft-restorer.php
// TODO: Set all post/comment ownership to importing admin. Or import the users.
// TODO: Don't import extraneous tables (e.g. users)
// TODO: Rewriting of URLs like wp-content/uploads/2011/10/bant-logo.png
// TODO: Document it
// TODO: Use log_e
// See http://lists.automattic.com/pipermail/wp-hackers/2013-May/046089.html
// Search/replace code adapted in according with the licence from https://github.com/interconnectit/Search-Replace-DB

$mainwp_updraft_plus_addons_migrator = new MainWP_Updraft_Plus_Addons_Migrator;

class MainWP_Updraft_Plus_Addons_Migrator {

	private $is_migration;
	private $restored_blogs = false;
	private $restored_sites = false;
	private $wpdb_obj = false;

	public function __construct() {
			add_action( 'mainwp_updraftplus_restore_form_db', array( $this, 'updraftplus_restore_form_db' ) );
			add_action( 'mainwp_updraftplus_restored_db', array( $this, 'updraftplus_restored_db' ), 10, 2 );
			add_action( 'mainwp_updraftplus_restored_db_table', array( $this, 'updraftplus_restored_db_table' ), 10, 3 );
			add_action( 'mainwp_updraftplus_restore_db_pre', array( $this, 'updraftplus_restore_db_pre' ) );
			add_action( 'mainwp_updraftplus_restore_db_record_old_siteurl', array( $this, 'updraftplus_restore_db_record_old_siteurl' ) );
			add_action( 'mainwp_updraftplus_restore_db_record_old_home', array( $this, 'updraftplus_restore_db_record_old_home' ) );
			add_action( 'mainwp_updraftplus_restore_db_record_old_content', array( $this, 'updraftplus_restore_db_record_old_content' ) );
			add_action( 'mainwp_updraftplus_restored_plugins_one', array( $this, 'restored_plugins_one' ) );
			add_action( 'mainwp_updraftplus_restored_themes_one', array( $this, 'restored_themes_one' ) );
			add_action( 'mainwp_updraftplus_debugtools_dashboard', array( $this, 'debugtools_dashboard' ) );
			add_action( 'mainwp_updraftplus_adminaction_searchreplace', array( $this, 'adminaction_searchreplace' ) );
			add_filter( 'mainwp_updraftplus_restore_set_table_prefix', array( $this, 'restore_set_table_prefix' ), 10, 2 );
			add_filter( 'mainwp_updraftplus_dbscan_urlchange', array( $this, 'dbscan_urlchange' ), 10, 3 );
			add_filter( 'mainwp_updraftplus_restorecachefiles', array( $this, 'restorecachefiles' ), 10, 2 );
			add_filter( 'mainwp_updraftplus_restored_plugins', array( $this, 'restored_plugins' ) );
	}

		# Disable W3TC and WP Super Cache, etc.

	public function restored_plugins() {
		if ( true !== $this->is_migration ) {
				return; }
			global $mainwp_updraftplus;
			$active_plugins = maybe_unserialize( $mainwp_updraftplus->option_filter_get( 'active_plugins' ) );
		if ( ! is_array( $active_plugins ) ) {
				return; }
			$disable_plugins = array(
				'w3-total-cache/w3-total-cache.php' => 'W3 Total Cache',
				'wp-super-cache/wp-cache.php' => 'W3 Super Cache',
				'quick-cache/quick-cache.php' => 'Quick Cache',
				'wp-fastest-cache/wpFastestCache.php' => 'WP Fastest Cache',
			);
		foreach ( $disable_plugins as $slug => $desc ) {
				# in_array is case sensitive
				#if (in_array($slug, $active_plugins)) {
			if ( preg_grep( '#' . $slug . '#i', $active_plugins ) ) {
					unset( $active_plugins[ $slug ] );
					echo '<strong>';
					$mainwp_updraftplus->log_e( 'Disabled this plugin: %s: re-activate it manually when you are ready.', $desc );
					echo '</strong>';
			}
		}
			update_option( 'active_plugins', $active_plugins );
	}

	public function restorecachefiles( $val, $file ) {
			# On a migration, we don't want to add cache files if they do not already exist (because usually they won't work until re-installed)
		if ( true !== $this->is_migration || false == $val ) {
				return $val; }
			$val = (is_file( WP_CONTENT_DIR . '/' . $file )) ? $val : false;
		if ( false == $val ) {
				global $mainwp_updraftplus;
				$mainwp_updraftplus->log_e( '%s: Skipping cache file (does not already exist)', $file );
		}
			return $val;
	}

	public function adminaction_searchreplace() {
			echo '<h2>' . __( 'Search / replace database', 'mainwp-updraftplus-extension' ) . '</h2>';
			echo '<strong>' . __( 'Search for', 'mainwp-updraftplus-extension' ) . ':</strong> ' . htmlspecialchars( $_POST['search'] ) . '<br>';
			echo '<strong>' . __( 'Replace with', 'mainwp-updraftplus-extension' ) . ':</strong> ' . htmlspecialchars( $_POST['replace'] ) . '<br>';
			$this->page_size = (empty( $_POST['pagesize'] ) || ! is_numeric( $_POST['pagesize'] )) ? 5000 : $_POST['pagesize'];
			$this->which_tables = (empty( $_POST['whichtables'] )) ? '' : explode( ',', ($_POST['whichtables']) );
		if ( empty( $_POST['search'] ) ) {
				echo sprintf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ), __( 'search term', 'mainwp-updraftplus-extension' ) ) . '<br>';
				echo '<a href="' . MainWP_Updraft_Plus_Options::admin_page_url() . '?page=updraftplus">' . __( 'Return to UpdraftPlus Configuration', 'mainwp-updraftplus-extension' ) . '</a>';
				return;
		}
			global $mainwp_updraftplus_restorer;
		if ( empty( $mainwp_updraftplus_restorer ) || ! is_a( $mainwp_updraftplus_restorer, '' ) ) {
				# Needed for the MainWP_Updraft_Plus_WPDB class and Updraft_Restorer::sql_exec() method
				require_once( MAINWP_UPDRAFT_PLUS_DIR . '/restorer.php' );
				$mainwp_updraftplus_restorer = new Updraft_Restorer( null, null, true );
		}
			$this->updraftplus_restore_db_pre();
			$this->tables_replaced = array();
			$this->updraftplus_restored_db_dosearchreplace( $_POST['search'], $_POST['replace'], $this->base_prefix, false );
			echo '<a href="' . MainWP_Updraft_Plus_Options::admin_page_url() . '?page=updraftplus">' . __( 'Return to UpdraftPlus Configuration', 'mainwp-updraftplus-extension' ) . '</a>';
	}

	public function debugtools_dashboard() {
			global $mainwp_updraftplus_admin;
			?>
			<h3><?php echo __( 'Search / replace database', 'mainwp-updraftplus-extension' ); ?></h3>
				<p><em><?php echo __( 'This can easily destroy your site; so, use it with care!', 'mainwp-updraftplus-extension' ); ?></em></p>
				<form method="post" onsubmit="return(confirm('<?php echo esc_js( __( 'A search/replace cannot be undone - are you sure you want to do this?', 'mainwp-updraftplus-extension' ) ); ?>'))">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'updraftplus-credentialtest-nonce' ); ?>">
				<input type="hidden" name="action" value="updraftplus_broadcastaction">
				<input type="hidden" name="subaction" value="updraftplus_adminaction_searchreplace">
				<table>
					<?php
					echo $mainwp_updraftplus_admin->settings_debugrow( __( 'Search for', 'mainwp-updraftplus-extension' ) . ':', '<input type="text" name="search" value="" style="width:380px;">' );
					echo $mainwp_updraftplus_admin->settings_debugrow( __( 'Replace with', 'mainwp-updraftplus-extension' ) . ':', '<input type="text" name="replace" value="" style="width:380px;">' );
					echo $mainwp_updraftplus_admin->settings_debugrow( __( 'Rows per batch', 'mainwp-updraftplus-extension' ) . ':', '<input type="number" min="1" step="1" name="pagesize" value="5000" style="width:380px;">' );
					echo $mainwp_updraftplus_admin->settings_debugrow( __( 'These tables only', 'mainwp-updraftplus-extension' ) . ':', '<input type="text" name="whichtables" title="' . esc_attr( __( 'Enter a comma-separated list; otherwise, leave blank for all tables.', 'mainwp-updraftplus-extension' ) ) . '" value="" style="width:380px;">' );
					?>
					<?php echo $mainwp_updraftplus_admin->settings_debugrow( '', '<input class="button-primary" type="submit" value="' . esc_attr( __( 'Go', 'mainwp-updraftplus-extension' ) ) . '">' ); ?>
					</table>
				</form>
				<?php
	}

	public function dbscan_urlchange( $output, $old_siteurl, $res ) {
		if ( isset( $res['updraft_restorer_replacesiteurl'] ) && $res['updraft_restorer_replacesiteurl'] ) {
				return ''; }
			return '<strong>' . __( 'Warning:', 'mainwp-updraftplus-extension' ) . '</strong>' . ' ' . __( 'This looks like a migration (the backup is from a site with a different address/URL), but you did not check the option to search-and-replace the database. That is usually a mistake.', 'mainwp-updraftplus-extension' );
	}

	public function restored_plugins_one( $plugin ) {
			echo '<strong>' . __( 'Processed plugin:', 'mainwp-updraftplus-extension' ) . '</strong> ' . $plugin . '<br>';
			global $mainwp_updraftplus;
			$mainwp_updraftplus->log( "Processed plugin: $plugin" );
	}

	public function restored_themes_one( $theme ) {
			// Network-activate
			$allowed_themes = get_site_option( 'allowedthemes' );
			$allowed_themes[ $theme ] = true;
			update_site_option( 'allowedthemes', $allowed_themes );
			global $mainwp_updraftplus;
			echo '<strong>' . __( 'Network activating theme:', 'mainwp-updraftplus-extension' ) . '</strong> ' . $theme . '<br>';
			$mainwp_updraftplus->log( 'Network activating theme: ' . $theme );
	}

	public function restore_set_table_prefix( $import_table_prefix, $backup_is_multisite ) {
		if ( ! is_multisite() || 0 !== $backup_is_multisite ) {
				return $import_table_prefix; }

			$new_blogid = $this->generate_new_blogid();

		if ( ! is_integer( $new_blogid ) ) {
				return $new_blogid; }

			$this->new_blogid = $new_blogid;

			return (string) $import_table_prefix . $new_blogid . '_';
	}

	function dump_form() {
			$form = '';
		foreach ( $_POST as $key => $val ) {
			if ( is_array( $val ) ) {
				foreach ( $val as $ktwo => $valtwo ) {
						$form .= '<input type="hidden" name="' . $key . '[' . $ktwo . ']" value="' . htmlspecialchars( $valtwo ) . '">';
				}
			} else {
					$form .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars( $val ) . '">';
			}
		}
			print $form;
	}

	function getinfo_form( $msg = '', $blogname = '' ) {

			global $current_site;

			echo '<h3>' . __( 'Information needed to continue:', 'mainwp-updraftplus-extension' ) . '</h3>';
			echo '<p><em>' . __( 'Please supply the following information:', 'mainwp-updraftplus-extension' ) . '</em></p>';

			echo '<p>' . __( 'Enter details for where this new site is to live within your multisite install:', 'mainwp-updraftplus-extension' ) . '</p>';

		if ( $msg ) {
				echo '<p>' . $msg . '</p>';
		}

			echo '<form method="POST">';
			// These strings are part of WordPress
		if ( ! is_subdomain_install() ) {
				echo '<label for="blogname">' . __( 'Site Name:' ) . '</label>';
		} else {
				echo '<label for="blogname">' . __( 'Site Domain:' ) . '</label>';
		}
			$this->dump_form();

		if ( ! is_subdomain_install() ) {
				echo '<span class="prefix_address">' . $current_site->domain . $current_site->path . '</span><input name="updraftplus_migrate_blogname" type="text" id="blogname" value="' . esc_attr( $blogname ) . '" maxlength="60" /><br />'; } else { 					echo '<input name="updraftplus_migrate_blogname" type="text" id="blogname" value="' . esc_attr( $blogname ) . '" maxlength="60" /><span class="suffix_address">.' . ( $site_domain = preg_replace( '|^www\.|', '', $current_site->domain ) ) . '</span><br />'; }
			?><p><input type="submit" name="submit" class="button button-primary" value="<?php esc_attr_e( 'Continue' ) ?>" /></p><?php
			echo '</form>';
	}

	function generate_new_blogid() {

			$blog_title = __( 'Migrated site (from UpdraftPlus)', 'mainwp-updraftplus-extension' );

		if ( empty( $_POST['updraftplus_migrate_blogname'] ) ) {
				$this->getinfo_form();
				return false;
		}

			// Verify value given
			$result = wpmu_validate_blog_signup( $_POST['updraftplus_migrate_blogname'], $blog_title );

		if ( count( $result['errors'] ) > 0 && $result['errors']->get_error_code() ) {
			if ( is_wp_error( $result['errors'] ) ) {
					$err_msg = '<ul style="list-style: disc inside;">';
				foreach ( $result['errors']->get_error_messages() as $key => $msg ) {
						$err_msg .= '<li><strong>' . __( 'Error:', 'mainwp-updraftplus-extension' ) . '</strong> ' . htmlspecialchars( $msg ) . '</li>';
				}
					$err_msg .= '</ul>';
			}
			if ( isset( $err_msg ) ) {
					$this->getinfo_form( $err_msg, $_POST['updraftplus_migrate_blogname'] );
					return false;
			}
		}

			$blogname = $_POST['updraftplus_migrate_blogname'];

			global $wpdb;
		if ( domain_exists( $result['domain'], $result['path'], $wpdb->siteid ) ) {
				// A WordPress-native string
				$this->getinfo_form( __( '<strong>ERROR</strong>: Site URL already taken.' ), $_POST['updraftplus_migrate_blogname'] );
				return false;
		}

			$create = create_empty_blog( $result['domain'], $result['path'], $blog_title, $wpdb->siteid );
		if ( is_integer( $create ) ) {
				$url = untrailingslashit( $result['domain'] . $result['path'] );
				echo '<strong>' . __( 'New site:', 'mainwp-updraftplus-extension' ) . '</strong> ' . $url . '<br>';

				// Update record of what we want to rewrite the URLs to in the search/replace operation
				// TODO: How to detect whether http or https???
				$this->siteurl = 'http://' . $url;
				// ???
				$this->home = 'http://' . $url;

				return $create;
		} else {
				$this->getinfo_form( print_r( $create, true ), $_POST['updraftplus_migrate_blogname'] );
				return false;
		}
	}

	public function updraftplus_restore_form_db() {

			echo '<input name="updraft_restorer_replacesiteurl" id="updraft_restorer_replacesiteurl" type="checkbox" value="1"><label for="updraft_restorer_replacesiteurl" title="' . sprintf( __( 'All references to the site location in the database will be replaced with your current site URL, which is: %s', 'mainwp-updraftplus-extension' ), htmlspecialchars( untrailingslashit( site_url() ) ) ) . '"> ' . __( 'Search and replace site location in the database (migrate)', 'mainwp-updraftplus-extension' ) . '</label> <a href="http://updraftplus.com/faqs/tell-me-more-about-the-search-and-replace-site-location-in-the-database-option/">' . __( '(learn more)', 'mainwp-updraftplus-extension' ) . '</a>';
	}

	public function updraftplus_restore_db_record_old_siteurl( $old_siteurl ) {
			// Only record once
		if ( ! empty( $this->old_siteurl ) ) {
				return; }
			$this->old_siteurl = $old_siteurl;
	}

	public function updraftplus_restore_db_record_old_home( $old_home ) {
			// Only record once
		if ( ! empty( $this->old_home ) ) {
				return; }
			$this->old_home = $old_home;
	}

	public function updraftplus_restore_db_record_old_content( $old_content ) {
			// Only record once
		if ( ! empty( $this->old_content ) ) {
				return; }
			$this->old_content = $old_content;
	}

	public function updraftplus_restore_db_pre() {

			global $wpdb, $mainwp_updraftplus;

			$this->siteurl = untrailingslashit( site_url() );
			$this->home = untrailingslashit( home_url() );
			$this->content = untrailingslashit( content_url() );
			$this->use_wpdb = (( ! function_exists( 'mysql_query' ) && ! function_exists( 'mysqli_query' )) || ! $wpdb->is_mysql || ! $wpdb->ready) ? true : false;

			$this->base_prefix = $mainwp_updraftplus->get_table_prefix( false );

			$mysql_dbh = false;

		if ( false == $this->use_wpdb ) {
				// We have our own extension which drops lots of the overhead on the query
				// This class is defined in updraft-restorer.php, which has been included if we get here
				$wpdb_obj = new MainWP_Updraft_Plus_WPDB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );
				// Was that successful?
			if ( ! $wpdb_obj->is_mysql || ! $wpdb_obj->ready ) {
					$this->use_wpdb = true;
			} else {
					$this->wpdb_obj = $wpdb_obj;
						$mysql_dbh = $wpdb_obj->updraftplus_getdbh();
						$use_mysqli = $wpdb_obj->updraftplus_use_mysqli();
			}
		}

			$this->mysql_dbh = $mysql_dbh;
			$this->use_mysqli = $use_mysqli;

		if ( true == $this->use_wpdb ) {
				$mainwp_updraftplus->log_e( 'Database access: Direct MySQL access is not available, so we are falling back to wpdb (this will be considerably slower)' ); }

		if ( is_multisite() ) {
				$sites = $wpdb->get_results( 'SELECT id, domain, path FROM ' . esc_sql( $this->base_prefix ) . 'site', ARRAY_N );
			if ( is_array( $sites ) ) {
					$nsites = array();
				foreach ( $sites as $site ) {
						$nsites[ $site[0] ] = array( $site[1], $site[2] ); }
					$this->original_sites = $nsites;
			}
		}

			$this->report = array(
				'tables' => 0,
				'rows' => 0,
				'change' => 0,
				'updates' => 0,
				'timetaken' => 0,
				'errors' => array(),
			);
	}

	public function updraftplus_restored_db_table( $table, $import_table_prefix ) {

			global $mainwp_updraftplus, $wpdb;

			// Anything to do?
		if ( ! isset( $_POST['updraft_restorer_replacesiteurl'] ) || 1 != $_POST['updraft_restorer_replacesiteurl'] ) {
				return; }

			// Can only do something if the old siteurl is known
			$old_siteurl = isset( $this->old_siteurl ) ? $this->old_siteurl : '';
			$old_home = isset( $this->old_home ) ? $this->old_home : '';
			$old_content = isset( $this->old_content ) ? $this->old_content : $old_siteurl . '/wp-content';

		if ( ! $old_home && ! $old_siteurl ) {
				return; }

		if ( empty( $this->tables_replaced ) ) {
				$this->tables_replaced = array(); }

			// Already done?
		if ( ! empty( $this->tables_replaced[ $table ] ) ) {
				return; }

			# If not done already, then search & replace this table, + record that it is done
			@set_time_limit( 1800 );

			$stripped_table = substr( $table, strlen( $import_table_prefix ) );
			# Remove multisite site number prefix, if relevant
		if ( is_multisite() && preg_match( '/^(\d+)_(.*)$/', $stripped_table, $matches ) ) {
				$stripped_table = $matches[2]; }

			# This array is for tables that a) we know don't need URL search/replacing and b) are likely to be sufficiently big that they could significantly delay the progress of the migrate (and increase the risk of timeouts on hosts that enforce them)
			# The term_relationships table contains 3 columns, all integers. Therefore, we can skip it. It can easily get big, so this is a good time-saver.
			$skip_tables = array( 'slim_stats', 'statpress', 'term_relationships', 'icl_languages_translations', 'icl_string_positions', 'icl_string_translations', 'icl_strings', 'redirection_logs', 'Counterize', 'Counterize_UserAgents', 'Counterize_Referers', 'adrotate_stats', 'login_security_solution_fail', 'wfHits', 'wbz404_logs', 'wbz404_redirects', 'wp_wfFileMods', 'tts_trafficstats', 'tts_referrer_stats', 'dmsguestbook', 'relevanssi', 'wponlinebackup_generations', 'svisitor_stat', 'simple_feed_stats' );

		if ( in_array( $stripped_table, $skip_tables ) ) {
				$this->tables_replaced[ $table ] = true;
				$mainwp_updraftplus->log_e( 'Skipping this table: data in this table (%s) should not be search/replaced', $table );
				return;
		}

			# Blogs table on multisite doesn't contain the full URL
		if ( is_multisite() && ($table == $this->base_prefix . 'blogs' || $table == $this->base_prefix . 'site') && (preg_match( '#^https?://([^/]+)#i', $this->home, $matches ) || preg_match( '#^https?://([^/]+)#i', $this->siteurl, $matches )) && (preg_match( '#^https?://([^/]+)#i', $old_home, $omatches ) || preg_match( '#^https?://([^/]+)#i', $old_siteurl, $omatches )) ) {
				$from_array = strtolower( $omatches[1] );
				$to_array = strtolower( $matches[1] );
				$mainwp_updraftplus->log_e( 'Replacing in blogs/site table: from: %s to: %s', htmlspecialchars( $from_array ), htmlspecialchars( $to_array ) );
				$try_site_blog_replace = true;
		} else {

				list($from_array, $to_array) = $this->build_searchreplace_array( $old_siteurl, $old_home, $old_content );

				# This block is for multisite installs, to do the search/replace of each site's URL individually. We want to try to do it here for efficiency - i.e. so that we don't have to double-pass tables
			if ( ! empty( $this->restored_blogs ) && preg_match( '/^(\d+)_(.*)$/', substr( $table, strlen( $import_table_prefix ) ), $tmatches ) && (preg_match( '#^((https?://)([^/]+))#i', $this->home, $matches ) || preg_match( '#^((https?://)([^/]+))#i', $this->siteurl, $matches )) && (preg_match( '#^((https?://)([^/]+))#i', $old_home, $omatches ) || preg_match( '#^((https?://)([^/]+))#i', $old_siteurl, $omatches )) ) {
					$nfrom = strtolower( $omatches[3] );
					$nto = strtolower( $matches[3] );
					$blognum = $tmatches[1];
				if ( $nfrom == $this->restored_blogs[1][0] && isset( $this->restored_blogs[ $blognum ] ) ) {
						$bdom = $this->restored_blogs[ $blognum ][0];
						$bpath = $this->restored_blogs[ $blognum ][1];
						$sblog = $omatches[2] . $bdom . (('/' != $bpath) ? untrailingslashit( $bpath ) : '');
						$rblog = $omatches[2] . str_replace( $nfrom, $nto, $bdom ) . (('/' != $bpath) ? untrailingslashit( $bpath ) : '');
					if ( ! in_array( $sblog, $from_array ) ) {
							$from_array[] = $sblog;
							$to_array[] = $rblog;
					}
				}
			}
		}

			// The search/replace parameters are allowed to be either strings or arrays
			$report = $this->_migrator_icit_srdb_replacer( $from_array, $to_array, array( $table => $stripped_table ) );

		if ( ! empty( $try_site_blog_replace ) ) {
			if ( $table == $this->base_prefix . 'blogs' ) {
					$blogs = $wpdb->get_results( 'SELECT blog_id, domain, path, site_id FROM ' . esc_sql( $this->base_prefix ) . 'blogs', ARRAY_N );
				if ( is_array( $blogs ) ) {
						$nblogs = array();
					foreach ( $blogs as $blog ) {
							$nblogs[ $blog[0] ] = array( $blog[1], $blog[2], $blog[3] );
					}
						$this->restored_blogs = $nblogs;
				}
			} elseif ( $table == $this->base_prefix . 'site' ) {
					$sites = $wpdb->get_results( 'SELECT id, domain, path FROM ' . esc_sql( $this->base_prefix ) . 'site', ARRAY_N );
				if ( is_array( $sites ) ) {
						$nsites = array();
					foreach ( $sites as $site ) {
							$nsites[ $site[0] ] = array( $site[1], $site[2] );
					}
						$this->restored_sites = $nsites;
				}
			}
			if ( ! empty( $this->restored_sites ) && ! empty( $this->restored_blogs ) && ! empty( $this->original_sites ) ) {
					# Adjust paths
					# Domain, path
					$any_site_changes = false;
				foreach ( $this->original_sites as $oid => $osite ) {
					if ( empty( $this->restored_sites[ $oid ] ) ) {
							continue; }
						$rsite = $this->restored_sites[ $oid ];
						# Task: 1) Replace the site path with the previous site path 2) Replace all the blog path prefixes from the same blog
					if ( $rsite[1] != $osite[1] ) {
							$any_site_changes = true;
							$sitepath = $osite[1];
							$this->restored_sites[ $oid ][1] = $sitepath;
						foreach ( $this->restored_blogs as $bid => $blog ) {
								# From this site?
							if ( $blog[2] != $oid ) {
									continue; }
								# Replace the prefix according to the change in prefix for the site
								$this->restored_blogs[ $bid ] = array( $blog[0], $sitepath . substr( $blog[1], strlen( $rsite[1] ) ), $oid );
						}
					}
				}
				if ( $any_site_changes ) {
						$mainwp_updraftplus->log_e( 'Adjusting multisite paths' );
					foreach ( $this->restored_sites as $oid => $osite ) {
							$wpdb->query( 'UPDATE ' . esc_sql( $this->base_prefix ) . "site SET path='" . esc_sql( $osite[1] ) . "' WHERE id=$oid" );
					}
					foreach ( $this->restored_blogs as $bid => $blog ) {
							$wpdb->query( 'UPDATE ' . esc_sql( $this->base_prefix ) . "blogs SET path='" . esc_sql( $blog[1] ) . "' WHERE blog_id=$bid" );
					}
				}
			}
		}

			// Output any errors encountered during the db work.
		if ( ! empty( $report['errors'] ) && is_array( $report['errors'] ) ) {
				echo '<p><h3>' . __( 'Error:', 'mainwp-updraftplus-extension' ) . '</h3> <ul style="list-style: disc inside;">';
				$processed_errors = array();
			foreach ( $report['errors'] as $error ) {
				if ( in_array( $error, $processed_errors ) ) {
						continue; }
					$processed_errors[] = $error;
					$num = count( array_keys( $report['errors'], $error ) );
					echo '<li>' . htmlspecialchars( $error );
				if ( $num > 1 ) {
						echo ' (x' . $num . ')'; }
					echo '</li>';
			}
				echo '</ul></p>';
		}

		if ( false == $report ) {
				echo sprintf( __( 'Failed: the %s operation was not able to start.', 'mainwp-updraftplus-extension' ), __( 'search and replace', 'mainwp-updraftplus-extension' ) );
		} elseif ( ! is_array( $report ) ) {
				echo sprintf( __( 'Failed: we did not understand the result returned by the %s operation.', 'mainwp-updraftplus-extension' ), __( 'search and replace', 'mainwp-updraftplus-extension' ) );
		} else {

				$this->tables_replaced[ $table ] = true;

				// Calc the time taken.
			foreach ( array( 'tables', 'rows', 'change', 'updates' ) as $key ) {
					$this->report[ $key ] += $report[ $key ];
			}
				$this->report['timetaken'] += $report['end'] - $report['start'];
		}
	}

		# Builds from supplied parameters and $this->(siteurl,home,content)

	private function build_searchreplace_array( $old_siteurl, $old_home, $old_content = false ) {
		if ( false === $old_content ) {
				$old_content = $old_siteurl . '/wp-content'; }
			$from_array = array();
			$to_array = array();
		if ( ! empty( $old_siteurl ) && $old_siteurl == $old_home ) {
				$from_array[] = $old_siteurl;
				$to_array[] = $this->siteurl;
		} elseif ( ! empty( $old_home ) && strpos( $old_siteurl, $old_home ) === 0 ) {
				# strpos: haystack, needle - i.e. old_home is a (proper, since they were not ==) substring of old_siteurl
				$from_array[] = $old_siteurl;
				$to_array[] = $this->siteurl;
				$from_array[] = $old_home;
				$to_array[] = $this->home;
				# If the source home URL is also a proper substring of the destination site URL, then this should be skipped
			if ( $old_home != $this->siteurl && strpos( $this->siteurl, $old_home ) === 0 ) {
					# Not pretty, but the only solution that can cope with content in posts that contains references to both site and home URLs in this case. This extra search URL un-does the adding of an unnecessary duplicate portion to site URLs in the case that is detected here.
					$from_array[] = $this->home . substr( $this->home, strlen( $old_home ) );
					$to_array[] = $this->home;
			}
		} elseif ( ! empty( $old_siteurl ) && strpos( $old_home, $old_siteurl ) === 0 ) {
				# old_siteurl is a substring of old_home (weird!)
				$from_array[] = $old_home;
				$to_array[] = $this->home;
				$from_array[] = $old_siteurl;
				$to_array[] = $this->siteurl;
		} else {
				# neither contains the other
			if ( ! empty( $old_siteurl ) ) {
					$from_array[] = $old_siteurl;
					$to_array[] = $this->siteurl;
			}
			if ( ! empty( $old_home ) ) {
					$from_array[] = $old_home;
					$to_array[] = $this->home;
			}
		}
			# We now have a minimal array based on the site_url and home settings
			# The case we need to detect is: (site_url is a prefix of content_url and new_site_url is a prefix of new_content_url and the remains are the same.
			# We do [0] of the existing array, to handle the weird case where old_siteurl is a substring of old_home (i.e. we get the shortest possible match)
			# We will want to do the content URLs first, since they are likely to be longest
		if ( ! empty( $from_array ) && 0 === strpos( $old_content, $from_array[0] ) && 0 === strpos( $this->content, $to_array[0] ) && substr( $old_content, strlen( $from_array[0] ) ) === substr( $this->content, strlen( $to_array[0] ) ) ) {
				# OK - nothing to do - is already covered
		} else {
				# Search/replace needed
				array_unshift( $from_array, $old_content );
				array_unshift( $to_array, $this->content );
		}
			return array( $from_array, $to_array );
	}

	public function updraftplus_restored_db( $info, $import_table_prefix ) {

			global $wpdb, $mainwp_updraftplus;

			$mainwp_updraftplus->log( 'Begin search and replace (updraftplus_restored_db)' );
			echo '<h3>' . __( 'Database: search and replace site URL', 'mainwp-updraftplus-extension' ) . '</h3>';

		if ( ! isset( $_POST['updraft_restorer_replacesiteurl'] ) || 1 != $_POST['updraft_restorer_replacesiteurl'] ) {
				echo '<p>';
				$mainwp_updraftplus->log_e( 'This option was not selected.' );
				echo '</p>';
				return;
		}

			$replace_this_siteurl = isset( $this->old_siteurl ) ? $this->old_siteurl : '';

			// Don't call site_url() - the result may/will have been cached
			//      if (isset($this->new_blogid)) switch_to_blog($this->new_blogid);
			//      $db_siteurl = $wpdb->get_row("SELECT option_value FROM $wpdb->options WHERE option_name='siteurl'")->option_value;
			//      $db_home = $wpdb->get_row("SELECT option_value FROM $wpdb->options WHERE option_name='home'")->option_value;
			//      if (isset($this->new_blogid)) restore_current_blog();

			$db_siteurl = $wpdb->get_row( 'SELECT option_value FROM ' . esc_sql( $this->base_prefix ) . "options WHERE option_name='siteurl'" )->option_value;
			$db_home = $wpdb->get_row( 'SELECT option_value FROM ' . esc_sql( $this->base_prefix ) . "options WHERE option_name='home'" )->option_value;

		if ( ! $replace_this_siteurl ) {
				$replace_this_siteurl = $db_siteurl;
		}

			$replace_this_home = isset( $this->old_home ) ? $this->old_home : '';
		if ( ! $replace_this_home ) {
				$replace_this_home = $db_home;
		}

			$replace_this_content = isset( $this->old_content ) ? $this->old_content : '';
		if ( ! $replace_this_content ) {
				$replace_this_content = $replace_this_siteurl . '/wp-content';
		}

			// Sanity checks
		if ( empty( $replace_this_siteurl ) ) {
				echo '<p>' . sprintf( __( 'Error: unexpected empty parameter (%s, %s)', 'mainwp-updraftplus-extension' ), 'backup_siteurl', $this->siteurl ) . '</p>';
				return;
		}
		if ( empty( $replace_this_home ) ) {
				echo '<p>' . sprintf( __( 'Error: unexpected empty parameter (%s, %s)', 'mainwp-updraftplus-extension' ), 'backup_home', $this->home ) . '</p>';
				return;
		}
		if ( empty( $replace_this_content ) ) {
				echo '<p>' . sprintf( __( 'Error: unexpected empty parameter (%s, %s)', 'mainwp-updraftplus-extension' ), 'backup_content_url', $this->content ) . '</p>';
				return;
		}

		if ( empty( $this->siteurl ) ) {
				echo '<p>' . sprintf( __( 'Error: unexpected empty parameter (%s, %s)', 'mainwp-updraftplus-extension' ), 'new_siteurl', $replace_this_siteurl ) . '</p>';
				return;
		}
		if ( empty( $this->home ) ) {
				echo '<p>' . sprintf( __( 'Error: unexpected empty parameter (%s, %s)', 'mainwp-updraftplus-extension' ), 'new_home', $replace_this_home ) . '</p>';
				return;
		}
		if ( empty( $this->content ) ) {
				echo '<p>' . sprintf( __( 'Error: unexpected empty parameter (%s, %s)', 'mainwp-updraftplus-extension' ), 'new_contenturl', $replace_this_content ) . '</p>';
				return;
		}

		if ( $replace_this_siteurl == $this->siteurl && $replace_this_home == $this->home && $replace_this_content == $this->content ) {
				$this->is_migration = false;
				echo '<p>' . sprintf( __( 'Nothing to do: the site URL is already: %s', 'mainwp-updraftplus-extension' ), $this->siteurl ) . '</p>';
				return;
		}

			$this->is_migration = true;

			do_action( 'updraftplus_restored_db_is_migration' );

			# Detect situation where the database's siteurl in the header differs from that actual row data in the options table. This can occur if the options table was being over-ridden by a constant. In that case, the search/replace will have failed to set the option table's siteurl; and the result will be that that siteurl is hence wrong, leading to site breakage. The solution is to re-set it.
			# $info['expected_oldsiteurl'] is from the db.gz file header
		if ( isset( $info['expected_oldsiteurl'] ) && $info['expected_oldsiteurl'] != $db_siteurl && $db_siteurl != $this->siteurl ) {
				$mainwp_updraftplus->log_e( sprintf( __( 'Warning: the database\'s site URL (%s) is different to what we expected (%s)', 'mainwp-updraftplus-extension' ), $db_siteurl, $info['expected_oldsiteurl'] ) );
				# Here, we change only the site URL entry; we don't run a full search/replace based on it. In theory, if someone developed using two different URLs, then this might be needed.
			if ( ! empty( $this->base_prefix ) && ! empty( $this->siteurl ) ) {
					$wpdb->query( 'UPDATE ' . esc_sql( $this->base_prefix ) . "options SET option_value='" . esc_sql( $this->siteurl ) . "' WHERE option_name='siteurl'" ); }
		}
		if ( isset( $info['expected_oldhome'] ) && $info['expected_oldhome'] != $db_home && $db_home != $this->home ) {
				$mainwp_updraftplus->log_e( sprintf( __( 'Warning: the database\'s home URL (%s) is different to what we expected (%s)', 'mainwp-updraftplus-extension' ), $db_home, $info['expected_oldhome'] ) );
			if ( ! empty( $this->base_prefix ) && ! empty( $this->home ) ) {
					$wpdb->query( 'UPDATE ' . esc_sql( $this->base_prefix ) . "options SET option_value='" . esc_sql( $this->home ) . "' WHERE option_name='home'" ); }
		}

			@set_time_limit( 1800 );

			echo '<p>';

			list($from_array, $to_array) = $this->build_searchreplace_array( $replace_this_siteurl, $replace_this_home, $replace_this_content );

		foreach ( $from_array as $ind => $from_url ) {
				$mainwp_updraftplus->log_e( 'Database search and replace: replace %s in backup dump with %s', $from_url, $to_array[ $ind ] );
		}

			echo '</p>';

			return $this->updraftplus_restored_db_dosearchreplace( $from_array, $to_array, $import_table_prefix );
	}

	private function updraftplus_restored_db_dosearchreplace( $from_array, $to_array, $import_table_prefix, $examine_siteurls = true ) {

			echo '<p>';

			global $mainwp_updraftplus, $wpdb;

			// Now, get an array of tables and then send it off to _migrator_icit_srdb_replacer()
			// Code modified from searchreplacedb2.php version 2.1.0 from http://www.davidcoveney.com
			// Do we have any tables and if so build the all tables array
			$tables = array();

			// We use $wpdb for non-performance-sensitive operations (e.g. one-time calls)
			$tables_mysql = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );

			$is_multisite = is_multisite();
		if ( $examine_siteurls && $is_multisite ) {
				$blogs = $wpdb->get_results( 'SELECT blog_id, domain, path FROM ' . esc_sql( $import_table_prefix ) . 'blogs', ARRAY_N );
				$nblogs = array();
			foreach ( $blogs as $blog ) {
					$nblogs[ $blog[0] ] = array( $blog[1], $blog[2] ); }
		}

		if ( ! $tables_mysql ) {
				echo __( 'Error:', 'mainwp-updraftplus-extension' ) . ' ' . __( 'Could not get list of tables', 'mainwp-updraftplus-extension' );
				echo '</p>';
				$mainwp_updraftplus->log( 'Could not get list of tables' );
				$this->_migrator_print_error( 'SHOW TABLES' );
				return false;
		} else {
				// Run through the array - each element a numerically-indexed array
			foreach ( $tables_mysql as $table ) {

					// Type equality is necessary, as we don't want to match false
					// "Warning: strpos(): Empty delimiter" means that the second parameter is a zero-length string
				if ( strpos( $table[0], $import_table_prefix ) === 0 ) {
						$tablename = $table[0];

						$stripped_table = substr( $tablename, strlen( $import_table_prefix ) );
						# Remove multisite site number prefix, if relevant
					if ( is_multisite() && preg_match( '/^(\d+)_(.*)$/', $stripped_table, $matches ) ) {
							$stripped_table = $matches[2]; }

					if ( ! empty( $this->which_tables ) && is_array( $this->which_tables ) ) {
						if ( ! in_array( $tablename, $this->which_tables ) ) {
								echo '<strong>' . sprintf( __( 'Search and replacing table:', 'mainwp-updraftplus-extension' ) ) . '</strong> ' . htmlspecialchars( $tablename ) . ': ' . __( 'skipped (not in list)', 'mainwp-updraftplus-extension' ) . '<br>';
								continue;
						}
					}

						$still_needs_doing = empty( $this->tables_replaced[ $tablename ] );

						# Looking for site tables on multisite
					if ( $examine_siteurls && $is_multisite && ! empty( $this->restored_blogs ) && preg_match( '/^(\d+)_(.*)$/', substr( $tablename, strlen( $import_table_prefix ) ), $tmatches ) && is_numeric( $tmatches[1] ) && ! empty( $this->restored_blogs[ $tmatches[1] ] ) && ! empty( $nblogs[ $tmatches[1] ] ) && (preg_match( '#^((https?://)([^/]+))#i', $this->home, $matches ) || preg_match( '#^((https?://)([^/]+))#i', $this->siteurl, $matches )) ) {
							# If the database file was not created by UD, then it may be out of order. Specifically, the 'blogs' table might have come *after* the tables for the individual sites. As a result, the tables for those sites may not have been fully searched + replaced... so we need to check that.
							# What are we expecting the site_url to be?
							$blognum = $tmatches[1];
							$site_url_current = $wpdb->get_var( 'SELECT option_value FROM ' . esc_sql( $import_table_prefix . $blognum ) . "_options WHERE option_name='siteurl'" );
						if ( is_string( $site_url_current ) ) {
								$bpathroot = $this->restored_blogs[1][1];
								$bpath = $this->restored_blogs[ $blognum ][1];
							if ( substr( $bpath, 0, strlen( $bpathroot ) ) == $bpathroot ) {
									$bpath = substr( $bpath, strlen( $bpathroot ) - 1 ); }
								$site_url_target = $matches[2] . $nblogs[ $blognum ][0] . (('/' != $bpath) ? untrailingslashit( $bpath ) : '');
							if ( $site_url_target != $site_url_current ) {
									$still_needs_doing = true;
									$from_array[] = $site_url_current;
									$to_array[] = $site_url_target;
							}
						}
					}

					if ( $still_needs_doing ) {
							$tables[ $tablename ] = $stripped_table;
					} else {
							echo '<strong>' . sprintf( __( 'Search and replacing table:', 'mainwp-updraftplus-extension' ) ) . '</strong> ' . htmlspecialchars( $tablename ) . ': ' . __( 'already done', 'mainwp-updraftplus-extension' ) . '<br>';
							$mainwp_updraftplus->log( 'Search and replacing table: ' . $tablename . ': already done' );
					}
				}
			}
		}

			$final_report = $this->report;

		if ( ! empty( $tables ) ) {

				$report = $this->_migrator_icit_srdb_replacer( $from_array, $to_array, $tables );

				// Output any errors encountered during the db work.
			if ( ! empty( $report['errors'] ) && is_array( $report['errors'] ) ) {
					echo '<h3>' . __( 'Error:', 'mainwp-updraftplus-extension' ) . '</h3> <ul style="list-style: disc inside;">';

					$processed_errors = array();
				foreach ( $report['errors'] as $error ) {
					if ( in_array( $error, $processed_errors ) ) {
							continue; }
						$processed_errors[] = $error;
						$num = count( array_keys( $report['errors'], $error ) );
						echo '<li>' . htmlspecialchars( $error );
					if ( $num > 1 ) {
							echo ' (x' . $num . ')'; }
						echo '</li>';
				}

					echo '</ul>';
			}

			if ( false == $report ) {
					echo sprintf( __( 'Failed: the %s operation was not able to start.', 'mainwp-updraftplus-extension' ), 'search and replace' );
			} elseif ( ! is_array( $report ) ) {
					echo sprintf( __( 'Failed: we did not understand the result returned by the %s operation.', 'mainwp-updraftplus-extension' ), 'search and replace' );
			}

				// Calc the time taken.
			foreach ( array( 'tables', 'rows', 'change', 'updates' ) as $key ) {
					$final_report[ $key ] += $report[ $key ];
			}
				$final_report['timetaken'] += $report['end'] - $report['start'];
			foreach ( $report['errors'] as $error ) {
					$final_report['errors'][] = $error;
			}
		}

			echo '</p><p>';

			echo '<strong>' . __( 'Tables examined:', 'mainwp-updraftplus-extension' ) . '</strong> ' . $final_report['tables'] . '<br>';
			echo '<strong>' . __( 'Rows examined:', 'mainwp-updraftplus-extension' ) . '</strong> ' . $final_report['rows'] . '<br>';
			echo '<strong>' . __( 'Changes made:', 'mainwp-updraftplus-extension' ) . '</strong> ' . $final_report['change'] . '<br>';
			echo '<strong>' . __( 'SQL update commands run:', 'mainwp-updraftplus-extension' ) . '</strong> ' . $final_report['updates'] . '<br>';
			echo '<strong>' . __( 'Errors:', 'mainwp-updraftplus-extension' ) . '</strong> ' . count( $final_report['errors'] ) . '<br>';
			echo '<strong>' . __( 'Time taken (seconds):', 'mainwp-updraftplus-extension' ) . '</strong> ' . round( $final_report['timetaken'], 3 ) . '<br>';

			echo '</p>';
	}

	// Returns either an array of results, or false - we abstract away what the wpdb class does compared to plain mysql_query
	//  private function query($sql_line, $sql_type = 5) {
	//      global $wpdb, $mainwp_updraftplus;
	//      if ($this->use_wpdb) {
	//          $res = $wpdb->get_results($sql_line, ARRAY_A);
	//          if ($wpdb->last_error) return false;
	//          return $res;
	//      } else {
	//          $res = mysql_query($sql_line, $this->mysql_dbh);
	//          if (is_bool($res)) return $res;
	//          $nres = array();
	//          while ($row = mysql_fetch_array($res)) {
	//              $nres[] = $row;
	//          }
	//          return $nres;
	//      }
	//  }

	private function _migrator_print_error( $sql_line ) {
			global $wpdb;
		if ( $this->use_wpdb ) {
				$last_error = $wpdb->last_error;
		} else {
				$last_error = ($this->use_mysqli) ? mysqli_error( $this->mysql_dbh ) : mysql_error( $this->mysql_dbh );
		}
			echo __( 'Error:', 'mainwp-updraftplus-extension' ) . ' ' . htmlspecialchars( $last_error ) . ' - ' . __( 'the database query being run was:', 'mainwp-updraftplus-extension' ) . ' ' . htmlspecialchars( $sql_line ) . '<br>';
			return $last_error;
	}

	private function fetch_sql_result( $table, $on_row, $page_size ) {

			$sql_line = sprintf( 'SELECT * FROM %s LIMIT %d, %d', $table, $on_row, $page_size );

			global $mainwp_updraftplus;
			$mainwp_updraftplus->check_db_connection( $this->wpdb_obj, true );

		if ( $this->use_wpdb ) {
				global $wpdb;
				$data = $wpdb->get_results( $sql_line, ARRAY_A );
			if ( ! $wpdb->last_error ) {
					return array( $data, $page_size ); }
		} else {
			if ( $this->use_mysqli ) {
					$data = mysqli_query( $this->mysql_dbh, $sql_line );
			} else {
					$data = mysql_query( $sql_line, $this->mysql_dbh );
			}
			if ( false !== $data ) {
					return array( $data, $page_size ); }
		}

		if ( 5000 <= $page_size ) {
				return $this->fetch_sql_result( $table, $on_row, 2000 ); }
		if ( 2000 <= $page_size ) {
				return $this->fetch_sql_result( $table, $on_row, 500 ); }

			# At this point, $page_size should be 500; and that failed
			return array( false, $page_size );
	}

		// The engine
	private function _migrator_icit_srdb_replacer( $search, $replace, $tables ) {

		if ( ! is_array( $tables ) ) {
				return false; }

			global $wpdb, $mainwp_updraftplus;

			$report = array(
				'tables' => 0,
				'rows' => 0,
				'change' => 0,
				'updates' => 0,
				'start' => microtime( true ),
				'end' => microtime( true ),
				'errors' => array(),
			);

			$page_size = (empty( $this->page_size ) || ! is_numeric( $this->page_size )) ? 5000 : $this->page_size;

			foreach ( $tables as $table => $stripped_table ) {

					$report['tables'] ++;

				if ( $search === $replace ) {
						$mainwp_updraftplus->log( 'No search/replace required: would-be search and replacement are identical' );
						continue;
				}

					$this->columns = array();

					echo '<strong>' . __( 'Search and replacing table:', 'mainwp-updraftplus-extension' ) . '</strong> ' . htmlspecialchars( $table );

					$mainwp_updraftplus->check_db_connection( $this->wpdb_obj, true );

					// Get a list of columns in this table
					$fields = $wpdb->get_results( 'DESCRIBE ' . $mainwp_updraftplus->backquote( $table ), ARRAY_A );

					$indexkey_field = '';

					$prikey_field = false;
				foreach ( $fields as $column ) {
						$primary_key = ('PRI' == $column['Key']) ? true : false;
						$this->columns[ $column['Field'] ] = $primary_key;
					if ( $primary_key ) {
							$prikey_field = $column['Field']; }
				}

					// Count the number of rows we have in the table if large we'll split into blocks, This is a mod from Simon Wheatley
					# InnoDB does not do count(*) quickly. You can use an index for more speed - see: http://www.cloudspace.com/blog/2009/08/06/fast-mysql-innodb-count-really-fast/

					$where = '';
					# Opportunity to use internal knowledge on tables which may be huge
				if ( 'postmeta' == $stripped_table && ((is_array( $search ) && strpos( $search[0], 'http' ) === 0) || strpos( $search, 'http' ) === 0) ) {
						$where = " WHERE meta_value LIKE '%http%'";
				}

					$count_rows_sql = 'SELECT COUNT(*) FROM ' . $table;
				if ( $prikey_field ) {
						$count_rows_sql .= ' USE INDEX (PRIMARY)'; }
					$count_rows_sql .= $where;

					$row_countr = $wpdb->get_results( $count_rows_sql, ARRAY_N );

					// If that failed, try this
				if ( false !== $prikey_field && $wpdb->last_error ) {
						$row_countr = $wpdb->get_results( "SELECT COUNT(*) FROM $table USE INDEX ($prikey_field)" . $where, ARRAY_N );
					if ( $wpdb->last_error ) {
							$row_countr = $wpdb->get_results( "SELECT COUNT(*) FROM $table", ARRAY_N ); }
				}

					$row_count = $row_countr[0][0];
					echo ': ' . sprintf( __( 'rows: %d', 'mainwp-updraftplus-extension' ), $row_count ) . '<br>';
					$mainwp_updraftplus->log( 'Search and replacing table: ' . $table . ': rows: ' . $row_count );
				if ( 0 == $row_count ) {
						continue; }

				for ( $on_row = 0; $on_row <= $row_count; $on_row = $on_row + $page_size ) {

						$this->current_row = 0;

					if ( $on_row > 0 ) {
							$mainwp_updraftplus->log_e( 'Searching and replacing reached row: %d', $on_row ); }

						// Grab the contents of the table
						list($data, $page_size) = $this->fetch_sql_result( $table, $on_row, $page_size );
						# $sql_line is calculated here only for the purpose of logging errors
						# $where might contain a %, so don't place it inside the main parameter
						$sql_line = sprintf( 'SELECT * FROM %s LIMIT %d, %d', $table . $where, $on_row, $on_row + $page_size );

						# Our strategy here is to minimise memory usage if possible; to process one row at a time if we can, rather than reading everything into memory
					if ( $this->use_wpdb ) {
							global $wpdb;
						if ( $wpdb->last_error ) {
								$report['errors'][] = $this->_migrator_print_error( $sql_line );
						} else {
							foreach ( $data as $row ) {
									$rowrep = $this->process_row( $table, $row, $search, $replace );
									$report['rows'] ++;
									$report['updates'] += $rowrep['updates'];
									$report['change'] += $rowrep['change'];
								foreach ( $rowrep['errors'] as $err ) {
										$report['errors'][] = $err; }
							}
						}
					} else {
						if ( false === $data ) {
								$report['errors'][] = $this->_migrator_print_error( $sql_line );
						} elseif ( true !== $data && null !== $data ) {
							if ( $this->use_mysqli ) {
								while ( $row = mysqli_fetch_array( $data ) ) {
										$rowrep = $this->process_row( $table, $row, $search, $replace );
										$report['rows'] ++;
										$report['updates'] += $rowrep['updates'];
										$report['change'] += $rowrep['change'];
									foreach ( $rowrep['errors'] as $err ) {
											$report['errors'][] = $err; }
								}
									@mysqli_free_result( $data );
							} else {
								while ( $row = mysql_fetch_array( $data ) ) {
										$rowrep = $this->process_row( $table, $row, $search, $replace );
										$report['rows'] ++;
										$report['updates'] += $rowrep['updates'];
										$report['change'] += $rowrep['change'];
									foreach ( $rowrep['errors'] as $err ) {
											$report['errors'][] = $err; }
								}
									@mysql_free_result( $data );
							}
						}
					}
				}
			}

			$report['end'] = microtime( true );

			return $report;
	}

	private function process_row( $table, $row, $search, $replace ) {

			global $mainwp_updraftplus, $wpdb, $mainwp_updraftplus_restorer;

			$report = array( 'change' => 0, 'errors' => array(), 'updates' => 0 );

			$this->current_row++;

			$update_sql = array();
			$where_sql = array();
			$upd = false;

		foreach ( $this->columns as $column => $primary_key ) {

				$edited_data = $data_to_fix = $row[ $column ];

				// Run a search replace on the data that'll respect the serialisation.
				$edited_data = $this->_migrator_recursive_unserialize_replace( $search, $replace, $data_to_fix );

				// Something was changed
			if ( $edited_data != $data_to_fix ) {
					$report['change'] ++;
					$ed = $edited_data;
					$wpdb->escape_by_ref( $ed );
					$update_sql[] = $mainwp_updraftplus->backquote( $column ) . ' = "' . $ed . '"';
					$upd = true;
			}

			if ( $primary_key ) {
					$df = $data_to_fix;
					$wpdb->escape_by_ref( $df );
					$where_sql[] = $mainwp_updraftplus->backquote( $column ) . ' = "' . $df . '"';
			}
		}

		if ( $upd && ! empty( $where_sql ) ) {
				$sql = 'UPDATE ' . $mainwp_updraftplus->backquote( $table ) . ' SET ' . implode( ', ', $update_sql ) . ' WHERE ' . implode( ' AND ', array_filter( $where_sql ) );

				$result = $mainwp_updraftplus_restorer->sql_exec( $sql, 5 );
			if ( false === $result || is_wp_error( $result ) ) {
					$last_error = $this->_migrator_print_error( $sql );
					$report['errors'][] = $last_error;
			} else {
					$report['updates'] ++;
			}
		} elseif ( $upd ) {
				$report['errors'][] = sprintf( '"%s" has no primary key, manual change needed on row %s.', $table, $this->current_row );
				echo __( 'Error:', 'mainwp-updraftplus-extension' ) . ' ' . sprintf( __( '"%s" has no primary key, manual change needed on row %s.', 'mainwp-updraftplus-extension' ), $table, $this->current_row );
		}

			return $report;
	}

		/**
		 * Take a serialised array and unserialise it replacing elements as needed and
		 * unserialising any subordinate arrays and performing the replace on those too.
		 *
		 * @param string $from       String we're looking to replace.
		 * @param string $to         What we want it to be replaced with
		 * @param array  $data       Used to pass any subordinate arrays back to in.
		 * @param bool   $serialised Does the array passed via $data need serialising.
		 *
		 * @return array	The original array with all elements replaced as needed.
		 */
		// N.B. $from and $to can be arrays - they get passed only to str_replace(), which can take an array
	private function _migrator_recursive_unserialize_replace( $from = '', $to = '', $data = '', $serialised = false ) {

			// some unserialised data cannot be re-serialised eg. SimpleXMLElements
		try {

			if ( is_string( $data ) && ( $unserialized = @unserialize( $data ) ) !== false ) {
					$data = $this->_migrator_recursive_unserialize_replace( $from, $to, $unserialized, true );
			} elseif ( is_array( $data ) ) {
					$_tmp = array();
				foreach ( $data as $key => $value ) {
						$_tmp[ $key ] = $this->_migrator_recursive_unserialize_replace( $from, $to, $value, false );
				}

					$data = $_tmp;
					unset( $_tmp );
			} elseif ( is_object( $data ) ) {
					// $data_class = get_class( $data );
					$_tmp = $data; // new $data_class( );
					$props = get_object_vars( $data );
				foreach ( $props as $key => $value ) {
						$_tmp->$key = $this->_migrator_recursive_unserialize_replace( $from, $to, $value, false );
				}

					$data = $_tmp;
					unset( $_tmp );
			} elseif ( is_string( $data ) && (null !== ($_tmp = json_decode( $data, true )) ) ) {

				if ( is_array( $_tmp ) ) {
					foreach ( $_tmp as $key => $value ) {
							$_tmp[ $key ] = $this->_migrator_recursive_unserialize_replace( $from, $to, $value, false );
					}

						$data = json_encode( $_tmp );
						unset( $_tmp );
				}
			} else {
				if ( is_string( $data ) ) {
						$data = str_replace( $from, $to, $data );
					# Below is the wrong approach. In fact, in the problematic case, the resolution is an extra search/replace to undo unnecessary ones
					//                  if (is_string($from)) {
					//                      $data = str_replace( $from, $to, $data );
					//                  } else {
					//                      # Array. We only want a maximum of one replacement to take place. This is only an issue in non-default setups, but in those situations, carrying out all the search/replaces can be wrong. This is also why the most specific URL should be done first.
					//                      foreach ($from as $i => $f) {
					//                          $ndata = str_replace($f, $to[$i], $data);
					//                          if ($ndata != $data) {
					//                              $data = $ndata;
					//                              break;
					//                          }
					//                      }
					//                  }
				}
			}

			if ( $serialised ) {
					return serialize( $data ); }
		} catch (Exception $error) {

		}

			return $data;
	}
}
