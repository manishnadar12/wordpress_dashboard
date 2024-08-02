<?php

class MainWP_Updraftplus_Backups_Next_Scheduled {

	private static $order = '';
	private static $orderby = '';
		//Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == MainWP_Updraftplus_Backups_Next_Scheduled::$instance ) {
				MainWP_Updraftplus_Backups_Next_Scheduled::$instance = new MainWP_Updraftplus_Backups_Next_Scheduled();
		}
			return MainWP_Updraftplus_Backups_Next_Scheduled::$instance;
	}

	public function __construct() {

	}

	public function admin_init() {
			add_action( 'wp_ajax_mainwp_updraftplus_data_refresh', array( $this, 'ajax_data_refresh' ) );
	}

	public function ajax_data_refresh() {
			@ini_set( 'display_errors', false );
			@error_reporting( 0 );

			$siteid = isset( $_POST['updraftRequestSiteID'] ) ? $_POST['updraftRequestSiteID'] : null;
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			global $mainWPUpdraftPlusBackupsExtensionActivator;
			$post_data = array( 'mwp_action' => 'reload_data' );

			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );

		if ( is_array( $information ) && isset( $information['nextsched_current_timegmt'] ) ) {
				global $mainwp_updraftplus;
				$mainwp_updraftplus->save_reload_data( $information, $siteid );
                // to improved performance
                $update = array(
                    'site_id' => $siteid,
                    'lastbackup_gmttime' => $information['updraft_lastbackup_gmttime'],
                );
                MainWP_Updraftplus_BackupsDB::get_instance()->update_setting( $update );


				unset( $information['updraft_historystatus'] );
				unset( $information['updraft_lastbackup_html'] );
				unset( $information['updraft_lastbackup_gmttime'] );

				die( json_encode( $information ) );
		}
			die();
	}

	public function gen_next_scheduled_backups_tab( $websites, $total_records ) {
		usort( $websites, array( 'MainWP_Updraftplus_Backups_Next_Scheduled', 'updraftplus_data_sort' ) );
		?>
		<table id="mainwp-updraftplug-schedules-table" class="ui stackable single line table">
			<thead>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox" id="cb-select-all-1" ></span></th>
					<th><?php _e( 'Site', 'mainwp-updraftplus-extension' ); ?></th>
					<th><?php _e( 'Files', 'mainwp-updraftplus-extension' ); ?></th>
					<th><?php _e( 'Database', 'mainwp-updraftplus-extension' ); ?></th>
					<th><?php _e( 'Time Now', 'mainwp-updraftplus-extension' ); ?></th>
					<th class="no-sort collapsing right aligned"></th>
				</tr>
			</thead>
			<tbody id="the-updraftplus-scheduled-list">
				<?php if ( is_array( $websites ) && count( $websites ) > 0 ) : ?>
					<?php	self::get_scheduled_table_row( $websites ); ?>
				<?php endif; ?>
			</tbody>

			  <div class="ui inverted dimmer">
			    <div class="ui text loader"><?php _e( 'Loading...', 'mainwp-updraftplus-extension' ); ?></div>
			  </div>
			  <p></p>

			<tfoot>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox" id="cb-select-all-1" ></span></th>
					<th><?php _e( 'Site', 'mainwp-updraftplus-extension' ); ?></th>
					<th><?php _e( 'Files', 'mainwp-updraftplus-extension' ); ?></th>
					<th><?php _e( 'Database', 'mainwp-updraftplus-extension' ); ?></th>
					<th><?php _e( 'Time Now', 'mainwp-updraftplus-extension' ); ?></th>
					<th class="no-sort collapsing"></th>
				</tr>
			</tfoot>
    </table>
		<script type="text/javascript">
		jQuery( '#mainwp-updraftplug-schedules-table' ).DataTable( {
			"columnDefs": [ { "orderable": false, "targets": "no-sort" } ],
			"order": [ [ 1, "asc" ] ],
			"language": { "emptyTable": "No schedules were found." },
			"drawCallback": function( settings ) {
				jQuery('#mainwp-updraftplug-schedules-table .ui.checkbox').checkbox();
				jQuery( '#mainwp-updraftplug-schedules-table .ui.dropdown').dropdown();
				
				if (typeof mainwp_table_check_columns_init === 'function') {
					mainwp_table_check_columns_init();
				};
			},
		} );
		</script>

		<?php
	}

	public static function get_scheduled_table_row( $websites ) {

		foreach ( $websites as $website ) {
			if ( ! isset( $website['updraftplus_active'] ) || empty( $website['updraftplus_active'] ) ) {
				continue;
			}
			$website_id = $website['id'];
			?>
			<tr website-id="<?php echo $website_id; ?>">
				<td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
				<td><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php echo stripslashes( $website['name'] ); ?></a></td>
				<td><span class="mwp-scheduled-files mwp-scheduled-text"><?php echo $website['nextsched_files_timezone']; ?></span></td>
				<td><span class="mwp-scheduled-database mwp-scheduled-text"><?php echo $website['nextsched_database_timezone']; ?></span></td>
				<td><span class="mwp-scheduled-currenttime mwp-scheduled-text"><?php echo $website['nextsched_current_timezone']; ?></span></td>
				<td class="right aligned">
					<div class="ui dropdown">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Overview', 'mainwp-updraftplus-extension' ); ?></a>
							<a class="item" href="admin.php?page=ManageSitesUpdraftplus&id=<?php echo $website_id; ?>"><?php _e( 'Backup Now', 'mainwp-updraftplus-extension' ); ?></a>
							<a class="item" href="admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=settings"><?php _e( 'Edit Global Schedule', 'mainwp-updraftplus-extension' ); ?></a>
							<a class="item" href="admin.php?page=ManageSitesUpdraftplus&id=<?php echo $website_id; ?>&tab=settings"><?php _e( 'Edit Individual Schedule', 'mainwp-updraftplus-extension' ); ?></a>
						</div>
					</div>
				</td>
			</tr>
			<?php
		}
	}

	public static function updraftplus_data_sort( $a, $b ) {
        $cmp = 0;
		if ( 'files' == self::$orderby ) {
				$a = $a['nextsched_files_gmt'];
				$b = $b['nextsched_files_gmt'];
				$cmp = $a - $b;
		} else if ( 'database' == self::$orderby ) {
				$a = $a['nextsched_database_gmt'];
				$b = $b['nextsched_database_gmt'];
				$cmp = $a - $b;
		} else if ( 'time' == self::$orderby ) {
				$a = $a['nextsched_current_timegmt'];
				$b = $b['nextsched_current_timegmt'];
				$cmp = $a - $b;
		}
//        else {
//				$a = $a['name'];
//				$b = $b['name'];
//				$cmp = strcmp( $a, $b );
//		}
		if ( 0 == $cmp ) {
				return 0;
        }

		if ( 'desc' == self::$order ) {
				return ($cmp > 0) ? -1 : 1; } else {
				return ($cmp > 0) ? 1 : -1; }
	}
}
