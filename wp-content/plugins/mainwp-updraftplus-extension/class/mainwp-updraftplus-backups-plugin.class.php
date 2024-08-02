<?php

class MainWP_Updraftplus_Backups_Plugin {

	private static $order = '';
	private static $orderby = '';
	//Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == MainWP_Updraftplus_Backups_Plugin::$instance ) {
				MainWP_Updraftplus_Backups_Plugin::$instance = new MainWP_Updraftplus_Backups_Plugin();
		}
			return MainWP_Updraftplus_Backups_Plugin::$instance;
	}

	public function __construct() {

	}

	public function admin_init() {
			add_action( 'wp_ajax_mainwp_updraftplus_upgrade_noti_dismiss', array( $this, 'dismiss_notice' ) );
			add_action( 'wp_ajax_mainwp_updraftplus_active_plugin', array( $this, 'active_plugin' ) );
			add_action( 'wp_ajax_mainwp_updraftplus_upgrade_plugin', array( $this, 'upgrade_plugin' ) );
			add_action( 'wp_ajax_mainwp_updraftplus_showhide_plugin', array( $this, 'showhide_plugin' ) );
	}


	public static function gen_plugin_dashboard_tab( $websites, $totalRecords = 0 ) {
			?>
			<table id="mainwp-updraftplus-sites-table" class="ui single line table" style="width:100%">

				<thead>
			    <tr>
				    <th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
						<th><?php _e( 'Site', 'mainwp-updraftplus-extension' ); ?></th>
						<th class="no-sort collapsing"><i class="sign in icon"></i></th>
						<th><?php _e( 'URL', 'mainwp-updraftplus-extension' ); ?></th>
						<th><?php _e( 'Version', 'mainwp-updraftplus-extension' ); ?></th>
						<th><?php _e( 'Hidden', 'mainwp-updraftplus-extension' ); ?></th>
						<th><?php _e( 'Settings', 'mainwp-updraftplus-extension' ); ?></th>
						<th class="no-sort collapsing"><?php _e( '', 'mainwp-updraftplus-extension' ); ?></th>
			    </tr>
		    </thead>
				<tbody id="the-mwp-updraftplus-list">
					<?php
					if ( is_array( $websites ) && count( $websites ) > 0 ) {
						self::get_plugin_dashboard_table_row( $websites );
					}
					?>
				</tbody>
				<tfoot>
			    <tr>
				    <th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
						<th><?php _e( 'Site', 'mainwp-updraftplus-extension' ); ?></th>
						<th class="no-sort collapsing"><i class="sign in icon"></i></th>
						<th><?php _e( 'URL', 'mainwp-updraftplus-extension' ); ?></th>
						<th><?php _e( 'Version', 'mainwp-updraftplus-extension' ); ?></th>
						<th><?php _e( 'Hidden', 'mainwp-updraftplus-extension' ); ?></th>
						<th><?php _e( 'Settings', 'mainwp-updraftplus-extension' ); ?></th>
						<th class="no-sort collapsing"><?php _e( '', 'mainwp-updraftplus-extension' ); ?></th>
			    </tr>
		    </tfoot>
      </table>
			<script type="text/javascript">
			jQuery( '#mainwp-updraftplus-sites-table' ).DataTable( {
				"stateSave": true,
				"stateDuration": 0, // forever
				"scrollX": true,
				"colReorder" : {
					fixedColumnsLeft: 1,
					fixedColumnsRight: 1
				},
				"lengthMenu": [ [10, 25, 50, 100, -1], [10, 25, 50, 100, "All"] ],
				"columnDefs": [ { "targets": 'no-sort', "orderable": false } ],
				"order": [ [ 1, "asc" ] ],
				"language": { "emptyTable": "No websites were found with the UpdraftPlus Backup/Restore plugin installed." },
				"drawCallback": function( settings ) {
					jQuery('#mainwp-updraftplus-sites-table .ui.checkbox').checkbox();
					jQuery( '#mainwp-updraftplus-sites-table .ui.dropdown').dropdown();

					if (typeof mainwp_table_check_columns_init === 'function') {
						mainwp_table_check_columns_init();
					};
				},
			} );
			</script>
    <?php
	}

	public static function get_plugin_dashboard_table_row( $websites ) {
		$plugin_name = 'UpdraftPlus - Backups';
		$location = 'options-general.php?page=updraftplus';
		$globalPremium = get_option( 'mainwp_updraft_general_is_premium', 0 );

		foreach ( $websites as $website ) {
			$website_id = $website['id'];

			$class_active = ( isset( $website['updraftplus_active'] ) && ! empty( $website['updraftplus_active'] ) ) ? '' : 'negative';
			$class_update = ( isset( $website['updraftplus_upgrade'] ) ) ? 'warning' : '';
			$class_update = ( 'negative' == $class_active ) ? 'negative' : $class_update;

			$version = "";
			$plugin_slug = ( isset( $website['plugin_slug'] ) ) ? $website['plugin_slug'] : '';

			if ( isset( $website['updraftplus_upgrade'] ) ) {
				if ( isset( $website['updraftplus_upgrade']['new_version'] ) ) {
					$version = $website['updraftplus_upgrade']['new_version'];
				}
				if ( isset( $website['updraftplus_upgrade']['plugin'] ) ) {
					$plugin_slug = $website['updraftplus_upgrade']['plugin'];
				}
			}

			//echo var_dump( $website );

			?>
			<tr class="<?php echo $class_active . ' ' . $class_update; ?>" website-id="<?php echo $website_id; ?>" plugin-name="<?php echo $plugin_name; ?>" plugin-slug="<?php echo $plugin_slug; ?>" version="<?php echo ( isset( $website['updraftplus_plugin_version'] ) ) ? $website['updraftplus_plugin_version'] : 'N/A'; ?>">
        <td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
				<td class="website-name"><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php echo stripslashes( $website['name'] ); ?></a></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>"><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank"><?php echo $website['url']; ?></a></td>
				<td><span class="updating"></span> <?php echo ( isset( $website['updraftplus_upgrade'] ) ) ? '<i class="exclamation circle icon"></i>' : ''; ?> <?php echo ( isset( $website['updraftplus_plugin_version'] ) ) ? $website['updraftplus_plugin_version'] : 'N/A'; ?></td>
				<td class="wp-updraftplus-visibility"><?php echo ( 1 == $website['hide_updraftplus'] ) ? __( 'Yes', 'mainwp-updraftplus-extension' ) : __( 'No', 'mainwp-updraftplus-extension' ); ?></td>
				<td><?php echo ( $website['isOverride'] ? __( 'Individual', 'mainwp-updraftplus-extension' ) : __( 'General', 'mainwp-updraftplus-extension' ) ); ?></td>
				<td>
					<div class="ui left pointing dropdown icon mini basic green button">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Overview', 'mainwp-updraftplus-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php _e( 'Edit', 'mainwp-updraftplus-extension' ); ?></a>
							<a class="item" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&location=<?php echo base64_encode( $location ); ?>" target="_blank"><?php _e( 'Open UpdraftPlus', 'mainwp-updraftplus-extension' ); ?></a>
							<?php if ( 1 == $website['hide_updraftplus'] ) : ?>
							<a class="item mwp_updraftplus_showhide_plugin" href="#" showhide="show"><?php _e( 'Unhide Plugin', 'mainwp-updraftplus-extension' ); ?></a>
							<?php else : ?>
							<a class="item mwp_updraftplus_showhide_plugin" href="#" showhide="hide"><?php _e( 'Hide Plugin', 'mainwp-updraftplus-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['updraftplus_active'] ) && empty( $website['updraftplus_active'] ) ) : ?>
							<a class="item mwp_updraftplus_active_plugin" href="#"><?php _e( 'Activate Plugin', 'mainwp-updraftplus-extension' ); ?></a>
							<?php else : ?>
							<a class="item" href="admin.php?page=ManageSitesUpdraftplus&id=<?php echo $website_id; ?>"><?php _e( 'Backup Now', 'mainwp-updraftplus-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['the_plugin_upgrade'] ) ) : ?>
							<a class="item mwp_updraftplus_update_plugin" href="#"><?php _e( 'Update Plugin', 'mainwp-updraftplus-extension' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
        </td>
      </tr>
		<?php
		}
	}

	public function get_updraft_info( $site_ids = array() ) {
        $settings = MainWP_Updraftplus_BackupsDB::get_instance()->get_settings( $site_ids );
        $results = array();
        if ($settings) {
            foreach($settings as $val) {
                $results[$val->site_id] = array('lastbackup_gmttime' => $val->lastbackup_gmttime);
            }
        }
        return $results;
	}

	public function get_websites_with_some_updraftdata( $websites, $updraft_data_sites = array() ) {
			$sites_updraft = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				if ( $website && $website->plugins != '' ) {
						$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ('updraftplus/updraftplus.php' == $plugin['slug']) ) {
									$site = MainWP_Updraftplus_Backups_Utility::map_site( $website, array( 'id' ) );
								if ( ! $plugin['active'] ) {
										continue; }

									$data = array( 'id' => $website->id );
									$updraftDS = isset( $updraft_data_sites[ $site['id'] ] ) ? $updraft_data_sites[ $site['id'] ] : array();
								if ( ! is_array( $updraftDS ) ) {
										$updraftDS = array(); }
									$data['lastbackup_gmttime'] = isset( $updraftDS['lastbackup_gmttime'] ) ? $updraftDS['lastbackup_gmttime'] : 0;
									//$data['mwp_updraft_is_premium'] = isset($updraftDS['mwp_updraft_is_premium']) ? $updraftDS['mwp_updraft_is_premium'] : "";
									$sites_updraft[ $website->id ] = $data;
									break;
							}
						}
					}
				}
			}
		}

			return $sites_updraft;
	}

    public function get_websites_with_the_plugin( $websites, $getPluginInfo = false ) {
		$websites_updraftplus = array();
		if ( is_array( $websites ) ) {
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' ) {
                        $plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins )) {
							foreach ( $plugins as $plugin ) {
								if ( ('updraftplus/updraftplus.php' == $plugin['slug']) ) {
                                        if ($getPluginInfo) {
                                            $site = MainWP_Updraftplus_Backups_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
                                            if ( $plugin['active'] ) {
                                                $site['updraftplus_active'] = 1;
                                            } else {
                                                $site['updraftplus_active'] = 0;
                                            }
                                            $site['updraftplus_plugin_version'] = $plugin['version'];
                                            $site['plugin_slug'] = $plugin['slug'];
                                            $plugin_upgrades = json_decode( $website->plugin_upgrades, 1 );
											if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
												if ( isset( $plugin_upgrades['updraftplus/updraftplus.php'] ) ) {
                                                    $upgrade = $plugin_upgrades['updraftplus/updraftplus.php'];
													if ( isset( $upgrade['update'] ) ) {
                                                        $site['updraftplus_upgrade'] = $upgrade['update'];
													}
												}
											}
                                            $websites_updraftplus[$website->id] = $site;
                                        } else if ($plugin['active']) {
                                            // get sites with activated plugin only
                                            $site = MainWP_Updraftplus_Backups_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
                                            $websites_updraftplus[$website->id] = $site;
                                        }
                                        break;
                                }
							}
						}
					}
				}

		}
		return $websites_updraftplus;
	}


	public function get_websites_with_the_data( $websites, $selected_group = 0, $updraft_data_sites = array() ) {
		$websites_updraftplus = array();

//        $updraftHide = $this->get_option( 'hide_the_plugin' );
//		if ( ! is_array( $updraftHide ) ) {
//				$updraftHide = array();
//		}


		$_text = __( 'Nothing currently scheduled', 'mainwp-updraftplus-extension' );
		$in_use = MainWP_Updraftplus_BackupsDB::get_instance()->get_settings_field_array();

		if ( is_array( $websites ) && count( $websites ) ) {
			if ( empty( $selected_group ) ) {
				foreach ( $websites as $siteid => $website ) {
                    $site = $website;
                    $updraftDS = isset( $updraft_data_sites[ $site['id'] ] ) ? $updraft_data_sites[ $site['id'] ] : array();
                    if ( ! is_array( $updraftDS ) ) {
                        $updraftDS = array();
                    }
                    $site['hide_updraftplus'] = $updraftDS['isHidden'];
                    $site['nextsched_files_gmt'] = isset( $updraftDS['nextsched_files_gmt'] ) ? $updraftDS['nextsched_files_gmt'] : 0;
                    $site['nextsched_files_timezone'] = (isset( $updraftDS['nextsched_files_timezone'] ) && ! empty( $updraftDS['nextsched_files_timezone'] )) ? $updraftDS['nextsched_files_timezone'] : $_text;
                    $site['nextsched_database_gmt'] = isset( $updraftDS['nextsched_database_gmt'] ) ? $updraftDS['nextsched_database_gmt'] : 0;
                    $site['nextsched_database_timezone'] = (isset( $updraftDS['nextsched_database_timezone'] ) && ! empty( $updraftDS['nextsched_database_timezone'] )) ? $updraftDS['nextsched_database_timezone'] : $_text;
                    $site['nextsched_current_timegmt'] = isset( $updraftDS['nextsched_current_timegmt'] ) ? $updraftDS['nextsched_current_timegmt'] : 0;
                    $site['nextsched_current_timezone'] = isset( $updraftDS['nextsched_current_timezone'] ) ? $updraftDS['nextsched_current_timezone'] : '';
                    $site['mainwp_updraft_backup_history_html'] = isset( $updraftDS['mainwp_updraft_backup_history_html'] ) ? $updraftDS['mainwp_updraft_backup_history_html'] : '';
                    $site['mainwp_updraft_backup_history_count'] = isset( $updraftDS['mainwp_updraft_backup_history_count'] ) ? $updraftDS['mainwp_updraft_backup_history_count'] : '';
                    $site['isPremium'] = isset( $updraftDS['is_premium'] ) ? $updraftDS['is_premium'] : 0;
                    $site['isOverride'] = isset( $updraftDS['override_settings'] ) ? $updraftDS['override_settings'] : 0;
                    $site['individual_in_use'] = isset( $in_use[$siteid] ) ? $in_use[$siteid]  : 0 ;
                    $websites_updraftplus[] = $site;
				}
			} else {
					global $mainWPUpdraftPlusBackupsExtensionActivator;
					$group_websites = apply_filters( 'mainwp-getdbsites', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), array(), array( $selected_group ) );
					$sites = array();
                    foreach ( $group_websites as $site ) {
                            $sites[] = $site->id;
                    }
                    foreach ( $websites as $siteid => $website ) {
                        if ( in_array( $siteid, $sites ) ) {
                            $site = $website;
                            $updraftDS = isset( $updraft_data_sites[ $siteid ] ) ? $updraft_data_sites[ $siteid ] : array();
                            if ( ! is_array( $updraftDS ) ) {
                                $updraftDS = array();
                            }
                            $site['hide_updraftplus'] = $updraftDS['isHidden'];
                            $site['nextsched_files_gmt'] = isset( $updraftDS['nextsched_files_gmt'] ) ? $updraftDS['nextsched_files_gmt'] : 0;
                            $site['nextsched_files_timezone'] = isset( $updraftDS['nextsched_files_timezone'] ) ? $updraftDS['nextsched_files_timezone'] : $_text;
                            $site['nextsched_database_gmt'] = isset( $updraftDS['nextsched_database_gmt'] ) ? $updraftDS['nextsched_database_gmt'] : 0;
                            $site['nextsched_database_timezone'] = isset( $updraftDS['nextsched_database_timezone'] ) ? $updraftDS['nextsched_database_timezone'] : $_text;
                            $site['nextsched_current_timegmt'] = isset( $updraftDS['nextsched_current_timegmt'] ) ? $updraftDS['nextsched_current_timegmt'] : 0;
                            $site['nextsched_current_timezone'] = isset( $updraftDS['nextsched_current_timezone'] ) ? $updraftDS['nextsched_current_timezone'] : '';
                            $site['mainwp_updraft_backup_history_html'] = isset( $updraftDS['mainwp_updraft_backup_history_html'] ) ? $updraftDS['mainwp_updraft_backup_history_html'] : '';
                            $site['mainwp_updraft_backup_history_count'] = isset( $updraftDS['mainwp_updraft_backup_history_count'] ) ? $updraftDS['mainwp_updraft_backup_history_count'] : '';
                            $site['isPremium'] = $updraftDS['is_premium'];
                            $site['isOverride'] = $updraftDS['override_settings'];
                            $site['individual_in_use'] = isset( $in_use[$siteid] ) ? $in_use[$siteid]  : 0 ;
                            $websites_updraftplus[] = $site;

                        }
                    }
            }
        }

		return $websites_updraftplus;
	}

	public static function gen_select_sites() {
		?>
		<div class="mainwp-actions-bar">
			<div class="ui grid">
				<div class="ui two column row">
					<div class="column">
						<select class="ui dropdown" id="mwp_updraftplus_plugin_action">
							<option value="-1"><?php _e( 'Bulk Actions', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="activate-selected"><?php _e( 'Active', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="update-selected"><?php _e( 'Update', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="hide-selected"><?php _e( 'Hide Plugin', 'mainwp-pagespeed-extension' ); ?></option>
							<option value="show-selected"><?php _e( 'Unhide Plugin', 'mainwp-pagespeed-extension' ); ?></option>
                        </select>
						<input type="button" value="<?php _e( 'Apply' ); ?>" class="ui basic button action" id="updraftplus_plugin_doaction_btn" name="">
						<?php do_action( 'mainwp_updraftplus_actions_bar_right' ); ?>
	        </div>
					<div class="right aligned column">
						<?php do_action( 'mainwp_updraftplus_actions_bar_right' ); ?>
	        </div>
        </div>
			</div>
		</div>
    <?php
	}

	public function dismiss_notice() {
			$website_id = $_POST['updraftRequestSiteID'];
			$version = $_POST['new_version'];
		if ( $website_id ) {
				session_start();
				$dismiss = $_SESSION['mainwp_updraftplus_dismiss_upgrade_plugin_notis'];
			if ( is_array( $dismiss ) && count( $dismiss ) > 0 ) {
					$dismiss[ $website_id ] = 1;
			} else {
					$dismiss = array();
					$dismiss[ $website_id ] = 1;
			}
				$_SESSION['mainwp_updraftplus_dismiss_upgrade_plugin_notis'] = $dismiss;
				die( 'updated' );
		}
			die( 'nochange' );
	}

	public function active_plugin() {
			$_POST['websiteId'] = $_POST['updraftRequestSiteID'];
			do_action( 'mainwp_activePlugin' );
			die();
	}

	public function upgrade_plugin() {
			$_POST['websiteId'] = $_POST['updraftRequestSiteID'];
			do_action( 'mainwp_upgradePluginTheme' );
			die();
	}

	public function showhide_plugin() {
        $siteid = isset( $_POST['updraftRequestSiteID'] ) ? $_POST['updraftRequestSiteID'] : null;

        $showhide = isset( $_POST['showhide'] ) ? $_POST['showhide'] : null;
		if ( null !== $siteid && null !== $showhide ) {
				global $mainWPUpdraftPlusBackupsExtensionActivator;
				$post_data = array(
                    'mwp_action' => 'set_showhide',
					'showhide' => $showhide,
				);
				$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );

				if ( is_array( $information ) && isset( $information['result'] ) && 'SUCCESS' === $information['result'] ) {
//                    $hide_updraftplus = $this->get_option( 'hide_the_plugin' );
//					if ( ! is_array( $hide_updraftplus ) ) {
//							$hide_updraftplus = array(); }
//                    $hide_updraftplus[ $siteid ] = ('hide' === $showhide) ? 1 : 0;
//                    $this->set_option( 'hide_the_plugin', $hide_updraftplus );

                    $update = array(
                        'site_id' => $siteid,
                        'isHidden' => ('hide' === $showhide) ? 1 : 0,
                    );
                    MainWP_Updraftplus_BackupsDB::get_instance()->update_setting( $update );
				}
				die( json_encode( $information ) );
		}
        die();
	}
}
