<?php

class MainWP_Updraftplus_Backups {

	public static $instance = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
				self::$instance = new self(); }
			return self::$instance;
	}

	public function __construct() {
        add_action( 'mainwp_updraft_backupnow_schedule_requests', array($this, 'run_schedule_requests'));
	}

	public function init() {

	}

	public function init_updraft() {
			$this->handle_settings_post();
			$this->load_updraft_classes();
	}

	public function admin_init() {
			add_action( 'wp_ajax_mainwp_updraftplus_load_sites', array( 'MainWP_Updraftplus_Backups', 'ajax_load_sites' ) );
			add_action( 'wp_ajax_mainwp_updraftplus_save_settings', array( $this, 'ajax_save_settings' ) );
			add_action( 'wp_ajax_mainwp_updraftplus_site_override_settings', array( $this, 'ajax_override_settings' ) );
			add_action( 'wp_ajax_mainwp_updraftplus_addons_connect', array( $this, 'ajax_addons_connect' ) );
			add_action( 'mainwp-site-synced', array( &$this, 'site_synced' ), 10, 1 );
			add_action( 'mainwp_delete_site', array( &$this, 'delete_site_data' ), 10, 1 );
	}


    public function request_backupnow($siteid, $opts) {
        global $mainWPUpdraftPlusBackupsExtensionActivator;
        $post_data = array(
            'mwp_action' => 'backup_now',
            'backupnow_nocloud' => $opts['backupnow_nocloud'],
            'backupnow_nofiles' => $opts['backupnow_nofiles'],
            'backupnow_nodb' => $opts['backupnow_nodb'],
            'onlythisfileentity' => isset( $opts['onlythisfileentity'] ) ? $opts['onlythisfileentity'] : '',
        );
        $information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );                return $information;
	}

    public function run_schedule_requests() {
        $schedule_opts = get_option('mainwp_updraft_backupnow_request_options');
        if (is_array($schedule_opts)) {

            $siteIds = isset($schedule_opts['ids']) ? $schedule_opts['ids'] : false;
            $otps = isset($schedule_opts['opts']) ? $schedule_opts['opts'] : false;

            if (is_array($siteIds) && !empty($siteIds) && is_array( $otps )) {
                $current_id = array_shift( $siteIds ); // get and remove first item
                if ( $siteIds ) {
                    update_option('mainwp_updraft_backupnow_request_options', array('ids' => $siteIds, 'opts' => $otps));
                    $this->set_schedule_backup_requests(); // set for next schedule request
                } else {
                    delete_option('mainwp_updraft_backupnow_request_options');
                }
                $information = $this->request_backupnow($current_id, $otps);
            }
        }

	}

    public function set_schedule_backup_requests() {
        $previous_time = wp_next_scheduled('mainwp_updraft_backupnow_schedule_requests');
		// Clear schedule so that we don't stack up scheduled backups
		wp_clear_scheduled_hook('mainwp_updraft_backupnow_schedule_requests');
		// Try to avoid changing the time is one was already scheduled. This is fairly conservative - we could do more, e.g. check if a backup already happened today.
		$schedule_for = ($previous_time>0) ? $previous_time : time()+ 5 * MINUTE_IN_SECONDS;
        wp_schedule_single_event($schedule_for, 'mainwp_updraft_backupnow_schedule_requests', array());
    }


  public static function show_notice() {
    return 'To complete the setup, all Cloud Storage Apps need to be authenticated directly on child sites';
  }

	public function delete_site_data( $website ) {
		if ( $website ) {
			MainWP_Updraftplus_BackupsDB::get_instance()->delete_setting( 'site_id', $website->id );
		}
	}

	public function site_synced( $website ) {
		if ( $website && $website->plugins != '' ) {
				$plugins = json_decode( $website->plugins, 1 );
				$status = 0;
			if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
				foreach ( $plugins as $plugin ) {
					if ( ('updraftplus/updraftplus.php' == $plugin['slug']) ) {
						if ( $plugin['active'] ) {
								$status = 1;
						}
						break;
					}
				}
			}
		}
	}

	public static function render() {
		$website = null;

		if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
			global $mainWPUpdraftPlusBackupsExtensionActivator;
			$option = array(
				'plugin_upgrades' => true,
				'plugins' => true,
			);
			$dbwebsites = apply_filters( 'mainwp-getdbsites', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), array( $_GET['id'] ), array(), $option );

			if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
				$website = current( $dbwebsites );
			}
		}

		if ( self::is_managesites_updraftplus() ) {
			$error = '';
			if ( empty( $website ) ) {
				$error = __( 'Invalid child site ID.', 'mainwp' );
			} else {
				$activated = false;
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) > 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ('updraftplus/updraftplus.php' == $plugin['slug']) ) {
								if ( $plugin['active'] ) {
									$activated = true;
								}
								break;
							}
						}
					}
				}
				if ( ! $activated ) {
					$error = __( 'UpdraftPlus - Backup/Restore plugin is not installed or activated on the site.', 'mainwp' );
				}
			}

			if ( ! empty( $error ) ) {
				do_action( 'mainwp-pageheader-sites', 'Updraftplus' );
				echo '<div class="ui yellow message">' . $error . '</div>';
				do_action( 'mainwp-pagefooter-sites', 'Updraftplus' );
				return;
			}
		}

		self::render_tabs( $website );
	}

	public static function render_tabs( $website = null ) {
    if ( isset( $_GET['action'] ) && 'mwpUpdraftOpenSite' == $_GET['action'] ) {
      self::open_site();
      return;
    }
  	global $mainWPUpdraftPlusBackupsExtensionActivator;
    $dbwebsites_updraftplus = array();
    $total_records = 0;

    if ( ! self::is_managesites_updraftplus() ) {
        // get sites with the wp-rocket plugin installed only
        $others = array(
            'plugins_slug' => 'updraftplus/updraftplus.php'
        );
        $websites = apply_filters( 'mainwp-getsites', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), null, false, $others ); // to fix overload all sites data
        $sites_ids = array();
        if ( is_array( $websites ) ) {
            $first = current($websites);
            $total_records = is_array($first) && isset($first['totalRecords']) ? $first['totalRecords'] : 0;

            foreach ( $websites as $site ) {
              $sites_ids[] = $site['id'];
            }
            unset( $websites );
    	}

	    $option = array(
        'plugin_upgrades' => true,
        'plugins' => true,
	    );

	    $dbwebsites = apply_filters( 'mainwp-getdbsites', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $sites_ids, array(), $option );
	    //print_r($dbwebsites);
	    $selected_group = 0;

      if ( isset( $_POST['mainwp_updraftplus_plugin_groups_select'] ) ) {
        $selected_group = intval( $_POST['mainwp_updraftplus_plugin_groups_select'] );
      }

    	$sites_updraftplus = MainWP_Updraftplus_Backups_Plugin::get_instance()->get_websites_with_the_plugin( $dbwebsites, true );

      $updraftDataSites = array();
      $sites_ids = array();
      if ( count( $sites_updraftplus ) > 0 ) {
        foreach($sites_updraftplus as $siteid => $val) {
          $sites_ids[] = $siteid;
        }
        $updraftDataSites = MainWP_Updraftplus_BackupsDB::get_instance()->get_updraft_data_site( $sites_ids );
      }

      $dbwebsites_updraftplus = MainWP_Updraftplus_Backups_Plugin::get_instance()->get_websites_with_the_data( $sites_updraftplus, $selected_group, $updraftDataSites );

      unset( $dbwebsites );
      unset( $updraftDataSites );
    }

    $style_tab_dashboard = $style_tab_status = $style_tab_backup = $style_tab_settings = $style_tab_debug = $style_tab_nextscheduled = ' style="display: none" ';

		if ( self::is_managesites_updraftplus() ) {
			$is_individual = true;
		} else {
			$is_individual = false;
		}

		$perform_settings_update = false;

		if ( get_option( 'mainwp_updraft_perform_settings_update' ) == 1 ) {
			delete_option( 'mainwp_updraft_perform_settings_update' );
			$perform_settings_update = true;
			$style_tab_settings = '';
			$performWhat = 'save_settings';
		} else if ( get_option( 'mainwp_updraft_general_addons_connect' ) == 1 ) {
			delete_option( 'mainwp_updraft_general_addons_connect' );
			$perform_settings_update = true;
			$style_tab_settings = '';
			$performWhat = 'addons_connect';
		}

		$current_tab = 'dashboard';

		if ( ! $perform_settings_update ) {
			if ( (isset( $_GET['tab'] ) && ( 'settings' == $_GET['tab'] )) || isset( $_POST['mainwp_premium_updraft_site_id'] ) || isset( $_POST['mainwp_updraft_addons_site_id'] ) ) {
					$style_tab_settings = '';
					$current_tab = 'settings';
			} else if ( isset( $_GET['tab'] ) && ('backups' == $_GET['tab']) ) {
					$style_tab_backup = '';
					$current_tab = 'backups';
			} else if ( isset( $_GET['tab'] ) && ('gen_schedules' == $_GET['tab']) ) {
					$style_tab_nextscheduled = '';
					$current_tab = 'gen_schedules';
			}  else {
				if ( $is_individual ) {
					if ( isset( $_POST['submit-updraft-settings'] ) ) {
							$style_tab_settings = '';
							$current_tab = 'settings';
					} else {
							$style_tab_status = '';
							$current_tab = 'status';
					}
				} else {
					if ( isset( $_GET['updraftplus_scheduled_orderby'] ) ) {
							$style_tab_nextscheduled = '';
							$current_tab = 'gen_schedules';
					} else {
							$style_tab_dashboard = '';
							$current_tab = 'dashboard';
					}
				}
			}
		}

		if ( $is_individual ) {
			do_action( 'mainwp-pageheader-sites', 'Updraftplus' );
			$count_backups = MainWP_Updraft_Plus_Options::get_updraft_option( 'mainwp_updraft_backup_history_count' );
			if ( $count_backups > 0 ) {
				$count_backups = ' (' . $count_backups . ')';
			} else {
				$count_backups = '';
			}
		} else {
			$count_backups = '';
		}

		global $mainwp_updraftplus_admin;

		$site_id = ! empty( $website ) ? $website->id : 0;
		$primary_backup = get_option( 'mainwp_primaryBackup', null );

    $next_sch = wp_next_scheduled('mainwp_updraft_backupnow_schedule_requests');
    $cancelled_sch = false;
    if (isset($_GET['_cancelnonce']) && wp_verify_nonce($_GET['_cancelnonce'], 'cancelscheduledrequests')) {
      if ( $next_sch ) {
        wp_clear_scheduled_hook('mainwp_updraft_backupnow_schedule_requests');
        $cancelled_sch = true;
      }
    }
		?>
		<script type="text/javascript"> var mwp_updraft_individual_siteid = <?php echo $site_id ?>; </script>
		<style type="text/css">.ui-dialog { width: 75% !important; }</style>
		<div id="mwp_updraft-poplog" ><pre id="mwp_updraft-poplog-content" style="white-space: pre-wrap;"></pre></div>

    <?php if ( $cancelled_sch ) : ?>
      <div class="ui yellow message"><?php echo esc_html_e('Schedule backup requests has been canceled successful.'); ?></div>
    <?php endif; ?>

    <?php if ( $next_sch && !$cancelled_sch ) : ?>
      <?php echo '<div class="ui green message">' . esc_html__('Scheduled requests backup', 'mainwp-updraftplus-extension') . ' ' . '<a href="admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=gen_schedules&calcel_requests=yes&_cancelnonce=' . wp_create_nonce('cancelscheduledrequests') . '">' . __('Cancel scheduled', 'mainwp-updraftplus-extension') . '</a></div>'; ?>
    <?php endif; ?>


		<div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-updraftplus-menu">
			<?php if ( ! $is_individual ) : ?>
			<a href="admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=dashboard" class="item <?php echo ( $current_tab == 'dashboard' ? 'active' : '' ); ?>"><i class="tasks icon"></i> <?php _e( 'UpdraftPlus Dashboard', 'mainwp' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=gen_schedules" class="item <?php echo ( $current_tab == 'gen_schedules' ? 'active' : '' ); ?>"><i class="clock icon"></i> <?php _e( 'Scheduled Backups', 'mainwp' ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=backups" class="item <?php echo ( $current_tab == 'backups' ? 'active' : '' ); ?>"><i class="hdd outline icon"></i> <?php _e( 'Existing Backups' . $count_backups ); ?></a>
			<a href="admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=settings" class="item <?php echo ( $current_tab == 'settings' ? 'active' : '' ); ?>"><i class="cog icon"></i> <?php _e( 'Settings', 'mainwp' ); ?></a>
			<?php else : ?>
			<a href="#" id="mwp_updraftplus_status_tab_lnk" class="item <?php echo ( empty( $style_tab_status ) ? 'active' : '' ); ?>"><i class="check icon"></i> <?php _e( 'Status', 'mainwp' ); ?></a>
			<a href="#" id="mwp_updraftplus_backup_tab_lnk" class="item <?php echo ( empty( $style_tab_backup ) ? 'active' : ''); ?>"><i class="hdd outline icon"></i> <?php _e( 'Existing Backups' . $count_backups ); ?></a>
			<a href="#" id="mwp_updraftplus_setting_tab_lnk" class="item <?php echo ( empty( $style_tab_settings ) ? 'active' : ''); ?>"><i class="cog icon"></i> <?php _e( 'Settings', 'mainwp' ); ?></a>
			<?php endif; ?>
		</div>

		<div id="mainwp-updraftplus-extension">

			<?php if ( ! $is_individual && $current_tab == 'dashboard') : ?>
			<div id="mwp_updraftplus_dashboard_tab" <?php echo $style_tab_dashboard; ?>>
				<?php MainWP_Updraftplus_Backups_Plugin::gen_select_sites(); ?>
				<div class="ui segment">
					<?php MainWP_Updraftplus_Backups_Plugin::gen_plugin_dashboard_tab( $dbwebsites_updraftplus, $total_records ); ?>
				</div>
			</div>
			<?php endif; ?>

			<script type="text/javascript">
				var mwp_updraft_credentialtest_nonce = '<?php echo wp_create_nonce( 'mwp-updraftplus-credentialtest-nonce' ); ?>';
				var mwp_updraft_download_nonce = '<?php echo wp_create_nonce( 'mwp_updraftplus_download' ); ?>';
			</script>

			<?php
			$backup_history = array();
			if ( $is_individual ) {
				?>
				<div id="mwp_updraftplus_status_tab" <?php echo $style_tab_status; ?>>
					<div class="ui segment">
						<?php $mainwp_updraftplus_admin->settings_statustab(); ?>
					</div>
				</div>
				<?php
			}
			?>

			<div id="mwp_updraftplus_backup_tab" <?php echo $style_tab_backup; ?>>
				<?php
				if ( $current_tab == 'backups' || $is_individual ) {
					$mainwp_updraftplus_admin->render_downloading_and_restoring( $site_id, $dbwebsites_updraftplus, $total_records);
				}
				?>

			</div>

			<?php if ( ! $is_individual && $current_tab == 'gen_schedules' ) : ?>
				<div id="mwp_updraftplus_nextscheduled_tab" <?php echo $style_tab_nextscheduled; ?>>
					<div class="mainwp-actions-bar">
						<div class="ui grid">
							<div class="ui two column row">
								<div class="column"></div>
								<div class="right aligned column">
									<input type="button" class="ui green basic button" value="<?php esc_html_e( 'Reload Data', 'mainwp-updraftplus-extension' ); ?>" id="mwp_updraftplus_refresh">
									<input type="button" class="ui green button" onclick="jQuery('#mwp-updraftplus-backupallnow-modal').dialog('open');" value="<?php _e( 'Backup Sites', 'mainwp-updraftplus-extension' ); ?>" id="mwp_updraftplus_backup_all_now">
								</div>
			        </div>
						</div>
					</div>
					<div class="ui segment">
						<div id="mwp_updraft_backup_started" class="ui blue message" style="display:none;"></div>
						<div id="mwp_updraft_backup_error" class="ui message red" style="display:none;"></div>
						<div id="mwp_updraft_info" class="ui yellow message" style="display:none;"></div>
						<div id="nextscheduled_tab_notice_box" class="ui yellow message" style="display:none;"></div>
						<div id="nextscheduled_tab_message_box" class="ui green message" style="display:none;"></div>
						<?php MainWP_Updraftplus_Backups_Next_Scheduled::get_instance()->gen_next_scheduled_backups_tab( $dbwebsites_updraftplus, $total_records ); ?>
						<div id="mwp-updraftplus-backupallnow-modal" title="UpdraftPlus - <?php _e( 'Perform a one-time backup', 'mainwp-updraftplus-extension' ); ?>" style="display: none;">
							<p><?php _e( "To proceed, press 'Backup Now'.", 'mainwp-updraftplus-extension' ); ?></p>
							<p>
								<input type="checkbox" id="backupnow_nodb"> <label for="backupnow_nodb"><?php _e( "Don't include the database in the backup", 'mainwp-updraftplus-extension' ); ?></label><br>
								<input type="checkbox" id="backupnow_nofiles"> <label for="backupnow_nofiles"><?php _e( "Don't include any files in the backup", 'mainwp-updraftplus-extension' ); ?></label><br>
								<input type="checkbox" id="backupnow_nocloud"> <label for="backupnow_nocloud"><?php _e( "Don't send this backup to remote storage", 'mainwp-updraftplus-extension' ); ?></label>
							</p>
							<p><?php _e( 'Does nothing happen when you attempt backups?', 'mainwp-updraftplus-extension' ); ?> <a href="http://updraftplus.com/faqs/my-scheduled-backups-and-pressing-backup-now-does-nothing-however-pressing-debug-backup-does-produce-a-backup/"><?php _e( 'Go here for help.', 'mainwp-updraftplus-extension' ); ?></a></p>
							<p>
								<input type="checkbox" id="backupnow_runascron"> <label for="backupnow_runascron"><?php _e( "Schedule backup requests (5 minutes for each request)", 'mainwp-updraftplus-extension' ); ?></label>
							</p>
						</div>
					</div>
				</div>
			<?php endif; ?>

			<form method="post" id="mwp_updraftplus_form_settings" class="ui form" action="" >
				<div id="mwp_updraftplus_setting_tab" <?php echo $style_tab_settings; ?>>
					<div class="ui alt segment">
						<div class="mainwp-main-content">
							<?php
							if ( $perform_settings_update ) {
								MainWP_Updraftplus_Backups::ajax_load_sites( $performWhat, false );
							} else {
								if ( $current_tab == 'settings' || $is_individual ) {
									$is_premium = MainWP_Updraftplus_Backups_Extension::is_updraft_premium();
									if ( $is_premium ) {
										self::box_connect_updraft( $site_id );
									}
								?>
								<div class="ui red message" id="updraftplus_error_zone" style="display:none"></div>

								<?php
										$override = 0;
										if ( $is_individual ) {
											$site_updraftplus = MainWP_Updraftplus_BackupsDB::get_instance()->get_setting_by( 'site_id', $site_id );
											if ( $site_updraftplus ) {
												$override = $site_updraftplus->override;
											}
											self::site_settings_box($override);
										}
										self::box_premium_setting( $site_id );
										?>
										<input type="hidden" name="mainwp_updraft_site_id" value="<?php echo $site_id; ?>">
										<?php $mainwp_updraftplus_admin->settings_formcontents( $is_individual, $override ); ?>

								<?php
								}
								}
								?>
							<?php if ( $is_individual ) { ?>
							</div>
							<?php } ?>
						</div>
						<div class="mainwp-side-content">
							<p>
								<?php echo __( 'All UpdraftPlus Settings can be set for separately for different child sites. To do this go to the MainWP > Sites page, and in the sites table, under the child site url, you can find the UpdraftPlus Backup/Restore link. This link will open Individual site UpdraftPlus Options.', 'mainwp-updraftplus-extension' ); ?>
							</p>
							<p>
                <?php echo __( 'The Settings tab will show you all plugin options where you can set custom settings for the child site. In order to override global options, set the Override General Settings to YES and click the Save button.', 'mainwp-updraftplus-extension' ); ?>
							</p>
							<p class="ui info message"><?php echo sprintf( __( 'If you are having issues with the UpdraftPlus Backup/Restore plugin, help documentation can be %sfound here%s.', 'mainwp-updraftplus-extension' ), '<a href="https://updraftplus.com/support/" target="_blank">', '</a>' ); ?></p>
              <a class="ui green big fluid button" target="_blank" href="https://mainwp.com/help/docs/category/mainwp-extensions/updraftplus-backups/"><?php echo __( 'Extension Documentation', 'mainwp-updraftplus-extension' ); ?></a>
						</div>
						<div class="ui clearing hidden divider"></div>
					</div>
				</div>
			</form>

		</div>

		<?php if ( $is_individual ) : ?>
			<?php do_action( 'mainwp-pagefooter-sites', 'Updraftplus' ); ?>
		<?php endif; ?>
		<?php
	}

	public static function site_settings_box( $override ) {
		$site_id = isset( $_GET['id'] ) ? $_GET['id'] : 0;
		?>
		<div class="ui hidden divider"></div>
		<div id="updraftplus_site_settings">
			<h3 class="ui dividing header"><?php _e( 'UpdraftPlus Backups Site Settings', 'mainwp-updraftplus-extension' ); ?></h3>
			<input type="hidden" name="mainwp_updraftplus_settings_site_id" value="<?php echo $site_id; ?>">
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Override general settings', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="two wide column ui toggle checkbox">
					<input type="checkbox" id="mainwp_updraftplus_override_general_settings" name="mainwp_updraftplus_override_general_settings"  <?php echo ( 0 == $override ? '' : 'checked="checked"'); ?> value="1"/>
					<label for="mainwp_updraftplus_override_general_settings"></label>
				</div>
				<div class="two wide column">
					<input class="ui green mini button" id="mwp_updraftplus_settings_save_btn" type="button" value="<?php echo __( 'Save', 'mainwp-updraftplus-extension' ); ?>" />
				</div>
				<div class="six wide column">
					<span id="mwp_updraftplus_site_save_settings_status" class="hidden"></span>
				</div>
			</div>
			<script type="text/javascript">
			<?php
			if ( get_option( 'mainwp_updraft_perform_individual_settings_update' ) == 1 ) {
				delete_option( 'mainwp_updraft_perform_individual_settings_update' );
				?>
				mainwp_updraftplus_individual_save_settings(<?php echo $site_id; ?>);
				<?php
			} else if ( get_option( 'mainwp_updraft_individual_addons_connect' ) == 1 ) {
				delete_option( 'mainwp_updraft_individual_addons_connect' );
				?>
				mainwp_updraftplus_individual_addons_connect(<?php echo $site_id; ?>);
				<?php
			}
			?>
			</script>
		<?php
	}

	public static function box_connect_updraft( $site_id = 0 ) {
		$addonsOptions = MainWP_Updraft_Plus_Options::get_updraft_option( 'addons_options', array() );
		if ( ! is_array( $addonsOptions ) ) {
			$addonsOptions = array();
		}

		$user_email = isset( $addonsOptions['email'] ) ? $addonsOptions['email'] : '';
		$user_password = isset( $addonsOptions['password'] ) ? $addonsOptions['password'] : '';
		?>

		<div class="ui hidden divider"></div>
		<h3 class="ui dividing header"><?php _e( 'Connect with UpdraftPlus account', 'mainwp-updraftplus-extension' ); ?></h3>
		<form method="post" action="">
			<input type="hidden" name="mainwp_updraft_addons_site_id" value="<?php echo $site_id; ?>">
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'UpdraftPlus Email & Password', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="four wide column">
					<input type="text" value="<?php echo $user_email; ?>" placeholder="Email" name="mainwp_updraftplus-addons_options[email]" autocomplete="off">
				</div>
				<div class="four wide column">
					<input type="password" value="<?php echo $user_password; ?>" placeholder="Password" name="mainwp_updraftplus-addons_options[password]" autocomplete="off">
				</div>
				<div class="two wide column">
					<input class="ui green button" type="submit" name="submit" value="<?php echo __( 'Connect', 'mainwp-updraftplus-extension' ); ?>" />
				</div>
			</div>
			<div id="mwp_updraft_site_addons_connect_working" class="ui field">
				<i class="notched circle loading icon" style="display: none;"></i>
				<span class="status"></span>
			</div>
		</form>
		<?php
		global $current_user;
	}

	public static function box_premium_setting( $site_id = 0 ) {
		$is_premium = MainWP_Updraftplus_Backups_Extension::is_updraft_premium();
		?>
		<div class="ui hidden divider"></div>
		<h3 class="ui dividing header">
			<?php _e( 'UpdraftPlus plugin version settings', 'mainwp-updraftplus-extension' ); ?>
			<div class="sub header">
				<?php _e( 'Premium version requires you to purchase the Premium Upgrade from <a href="https://updraftplus.com/shop/updraftplus-premium/" title="UpdraftPlus" target="_blank">UpdraftPlus</a>.', 'mainwp-updraftplus-extension' ); ?>
			</div>
		</h3>
		<form method="post" action="" class="ui form">
			<input type="hidden" name="mainwp_premium_updraft_site_id" value="<?php echo $site_id; ?>">
			<div class="ui grid field">
				<label class="six wide column middle aligned"><?php _e( 'Use premium version', 'mainwp-updraftplus-extension' ); ?></label>
			  <div class="two wide column ui toggle checkbox">
					<input type="checkbox" id="mwp_updraft_is_premium" name="mwp_updraft_is_premium"  <?php echo ( $is_premium ? 'checked="checked"' : '' ); ?> value="yes" />
					<label for="mwp_updraft_is_premium"></label>
				</div>
				<div class="eight wide column">
					<input class="ui green mini button" type="submit" name="submit" value="<?php echo __( 'Save', 'mainwp-updraftplus-extension' ); ?>" />
				</div>
			</div>
		</form>
		<?php
		global $current_user;
	}

	public static function open_site() {
			$id = $_GET['websiteid'];
			global $mainWPUpdraftPlusBackupsExtensionActivator;
			$websites = apply_filters( 'mainwp-getdbsites', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), array( $id ), array() );
			$website = null;
		if ( $websites && is_array( $websites ) ) {
				$website = current( $websites );
		}

			$open_location = '';
		if ( isset( $_GET['open_location'] ) ) {
				$open_location = $_GET['open_location']; }
			?>
			<div id="mainwp_background-box">
				<?php
				if ( function_exists( 'mainwp_current_user_can' ) && ! mainwp_current_user_can( 'dashboard', 'access_wpadmin_on_child_sites' ) ) {
						mainwp_do_not_have_permissions( 'WP-Admin on child sites' );
				} else {
						?>
						<div style="font-size: 30px; text-align: center; margin-top: 5em;"><?php _e( 'You will be redirected to your website immediately.', 'mainwp' ); ?></div>
							<form method="POST" action="<?php echo MainWP_Updraftplus_Backups_Utility::get_getdata_authed( $website, 'index.php', 'where', $open_location ); ?>" id="redirectForm">
                            </form>
					<?php } ?>
                </div>
				<?php
	}

	public function load_updraft_classes() {
			global $mainwp_updraft_globals;

		if ( ! class_exists( 'MainWP_Updraft_Plus_Options' ) ) {
				require_once( MAINWP_UPDRAFT_PLUS_DIR . '/options.php' ); }

			$is_premium = MainWP_Updraftplus_Backups_Extension::is_updraft_premium();

			$updraftplus_have_addons = 0;
		if ( $is_premium ) {
			if ( is_dir( MAINWP_UPDRAFT_PLUS_DIR . '/addons' ) && $dir_handle = opendir( MAINWP_UPDRAFT_PLUS_DIR . '/addons' ) ) {
				while ( false !== ($e = readdir( $dir_handle )) ) {
					if ( is_file( MAINWP_UPDRAFT_PLUS_DIR . '/addons/' . $e ) && preg_match( '/\.php$/', $e ) ) {
							# We used to have 1024 bytes here - but this meant that if someone's site was hacked and a lot of code added at the top, and if they were running a too-low PHP version, then they might just see the symptom rather than the cause - and raise the support request with us.
							$header = file_get_contents( MAINWP_UPDRAFT_PLUS_DIR . '/addons/' . $e, false, null, -1, 16384 );
							$phprequires = (preg_match( '/RequiresPHP: (\d[\d\.]+)/', $header, $matches )) ? $matches[1] : false;
							$phpinclude = (preg_match( '/IncludePHP: (\S+)/', $header, $matches )) ? $matches[1] : false;
						if ( false === $phprequires || version_compare( PHP_VERSION, $phprequires, '>=' ) ) {
								$updraftplus_have_addons++;
							if ( $phpinclude && file_exists( MAINWP_UPDRAFT_PLUS_DIR . '/' . $phpinclude )) {
									require_once( MAINWP_UPDRAFT_PLUS_DIR . '/' . $phpinclude );
							}
								include_once( MAINWP_UPDRAFT_PLUS_DIR . '/addons/' . $e );
						}
					}
				}
					@closedir( $dir_handle );
			}

				//if (is_file(MAINWP_UPDRAFT_PLUS_DIR.'/udaddons/updraftplus-addons.php')) include_once(MAINWP_UPDRAFT_PLUS_DIR.'/udaddons/updraftplus-addons.php');
		}

			global $mainwp_updraftplus;

		if ( empty( $mainwp_updraftplus ) ) {
				require_once MAINWP_UPDRAFT_PLUS_DIR . '/class-updraftplus.php';
				$mainwp_updraftplus = new MainWP_UpdraftPlus();
				$mainwp_updraftplus->have_addons = $updraftplus_have_addons;
		}
	}

	public static function is_updraftplus_page( $tabs = array() ) {
		if ( isset( $_GET['page'] ) && ('Extensions-Mainwp-Updraftplus-Extension' == $_GET['page'] || 'ManageSitesUpdraftplus' == $_GET['page'] ) ) {
			if ( 'ManageSitesUpdraftplus' == $_GET['page'] ) {
				if ( ! isset( $_GET['tab'] ) || empty( $_GET['tab'] ) ) {
						$_GET['tab'] = 'settings';
				}
			}
			if ( empty( $tabs ) ) {
					return true;
			} else if ( is_array( $tabs ) && isset( $_GET['tab'] ) && in_array( $_GET['tab'], $tabs ) ) {
					return true;
			} else if ( isset( $_GET['tab'] ) && $_GET['tab'] == $tabs ) {
					return true;
			}
		}
			return false;
	}

	public static function is_managesites_updraftplus( $tabs = array() ) {
			// to fix bug
		if ( isset( $_REQUEST['updraftRequestSiteID'] ) && ! empty( $_REQUEST['updraftRequestSiteID'] ) ) {
				return true;
		} else if ( isset( $_GET['page'] ) && ('ManageSitesUpdraftplus' == $_GET['page']) ) {
				return true;
		}
			return false;
	}

	public static function get_site_id_managesites_updraftplus() {
			$site_id = 0;
		if ( self::is_managesites_updraftplus() ) {
			if ( isset( $_REQUEST['updraftRequestSiteID'] ) && ! empty( $_REQUEST['updraftRequestSiteID'] ) ) {
					$site_id = $_REQUEST['updraftRequestSiteID']; } else if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
						$site_id = $_GET['id']; } else if ( isset( $_POST['mainwp_updraft_site_id'] ) && ! empty( $_POST['mainwp_updraft_site_id'] ) ) {
						$site_id = $_POST['mainwp_updraft_site_id']; }
		}
			return $site_id;
	}

	// save_settings()
	public function handle_settings_post() {

		if ( isset( $_POST['submit-updraft-settings'] ) ) {
				$is_individual_settings = false;
			if ( isset( $_POST['mainwp_updraft_site_id'] ) && ! empty( $_POST['mainwp_updraft_site_id'] ) ) {
					$is_individual_settings = true;
			}


//			$add_to_post_keys = array('updraft_interval', 'updraft_interval_database', 'updraft_starttime_files', 'updraft_starttime_db', 'updraft_startday_files', 'updraft_startday_db');
//
//			// If database and files are on same schedule, override the db day/time settings
//			if (isset($settings['updraft_interval_database']) && isset($settings['updraft_interval_database']) && $settings['updraft_interval_database'] == $settings['updraft_interval'] && isset($settings['updraft_starttime_files'])) {
//				$settings['updraft_starttime_db'] = $settings['updraft_starttime_files'];
//				$settings['updraft_startday_db'] = $settings['updraft_startday_files'];
//			}
//			foreach ($add_to_post_keys as $key) {
//				// For add-ons that look at $_POST to find saved settings, add the relevant keys to $_POST so that they find them there
//				if (isset($settings[$key])) {
//					$_POST[$key] = $settings[$key];
//				}
//			}


            $settings = array();
            $settingKeys = self::get_settings_keys();
			foreach ( $settingKeys as $key ) {
					$pos_key = 'mwp_' . $key;
				if ( isset( $_POST[ $pos_key ] ) ) {
						$settings[ $key ] = $_POST[ $pos_key ];
				} else {
						$settings[ $key ] = '';
				}
			}

            $settings[ 'do_not_save_destinations_settings' ] = isset( $_POST[ 'mwp_updraft_do_not_save_destinations_settings' ] ) ? intval($_POST[ 'mwp_updraft_do_not_save_destinations_settings' ]) : 0;

			// to fix bug
			$settings = $this->sanitize_fields( $settings );

			if ( $is_individual_settings ) {
					$sid = isset( $_GET['id'] ) ? $_GET['id'] : (isset( $_POST['mainwp_updraft_site_id'] ) ? $_POST['mainwp_updraft_site_id'] : 0 );
					MainWP_Updraftplus_BackupsDB::get_instance()->update_setting_fields_by( 'site_id', $sid, $settings );
					update_option( 'mainwp_updraft_perform_individual_settings_update', 1 );
			} else {
					self::update_general_settings( $settings );
					update_option( 'mainwp_updraft_perform_settings_update', 1 );
			}
		}
	}

	public function sanitize_fields( $settings ) {
			$data = $settings;
			$san_emails = $san_warningsonly = $san_wholebackup = array();
		if ( isset( $data['updraft_email'] ) ) {
				$value_emails = $data['updraft_email'];
				$value_warningsonly = $data['updraft_report_warningsonly'];
				$value_wholebackup = $data['updraft_report_wholebackup'];
				// premium version
			if ( is_array( $value_emails ) ) {
				foreach ( $value_emails as $key => $val ) {
						$val = $this->just_one( $val );
					if ( ! empty( $val ) ) {
							$san_emails[] = $val;
							$san_warningsonly[] = isset( $value_warningsonly[ $key ] ) ? $value_warningsonly[ $key ] : 0;
							$san_wholebackup[] = isset( $value_wholebackup[ $key ] ) ? $value_wholebackup[ $key ] : 0;
					}
				}
					$data['updraft_email'] = $san_emails;
					$data['updraft_report_warningsonly'] = $san_warningsonly;
					$data['updraft_report_wholebackup'] = $san_wholebackup;
			}
		}

		if ( isset( $data['updraft_s3'] ) ) {
				$data['updraft_s3'] = $this->s3_sanitise( $data['updraft_s3'] );
		}

			return $data;
	}

	public function s3_sanitise( $s3 ) {
		if ( is_array( $s3 ) && ! empty( $s3['path'] ) && '/' == substr( $s3['path'], 0, 1 ) ) {
				$s3['path'] = substr( $s3['path'], 1 );
		}
			return $s3;
	}

	public function just_one_email( $input, $required = false ) {
			$x = $this->just_one( $input, 'saveemails', (empty( $input ) && false === $required) ? '' : get_bloginfo( 'admin_email' ) );
		if ( is_array( $x ) ) {
			foreach ( $x as $ind => $val ) {
				if ( empty( $val ) ) {
						unset( $x[ $ind ] ); }
			}
			if ( empty( $x ) ) {
					$x = ''; }
		}
			return $x;
	}

	public function just_one( $input, $filter = 'savestorage', $rinput = false ) {
		if ( false === $rinput ) {
				$rinput = (is_array( $input )) ? array_pop( $input ) : $input; }
		if ( is_string( $rinput ) && false !== strpos( $rinput, ',' ) ) {
				$rinput = substr( $rinput, 0, strpos( $rinput, ',' ) ); }
			return apply_filters( 'mainwp_updraftplus_' . $filter, $rinput, $oinput );
	}

	public function return_array( $input ) {
		if ( ! is_array( $input ) ) {
				$input = array(); }
			return $input;
	}

	public static function update_general_settings( $settings ) {
			$curgen_settings = get_site_option( 'mainwp_updraftplus_generalSettings' );
		if ( ! is_array( $curgen_settings ) ) {
				$curgen_settings = array(); }

		foreach ( $settings as $key => $value ) {
				$curgen_settings[ $key ] = $value;
		}
			return update_site_option( 'mainwp_updraftplus_generalSettings', $curgen_settings );
	}

	public static function delete_updraftplus_settings( $option, $site_id = false ) {
		if ( $site_id ) {
				return MainWP_Updraftplus_BackupsDB::get_instance()->update_setting_fields_by( 'site_id', $site_id, array( $option => '' ) );
		} else {
				return self::update_general_settings( array( $option => '' ) );
		}
			return false;
	}

	public static function update_updraftplus_settings( $settings, $site_id = false ) {
		if ( $site_id ) {
				return MainWP_Updraftplus_BackupsDB::get_instance()->update_setting_fields_by( 'site_id', $site_id, $settings );
		} else {
				return self::update_general_settings( $settings );
		}
			return false;
	}

	public static function ajax_load_sites( $what = null, $ajax_call = true ) {
		global $mainWPUpdraftPlusBackupsExtensionActivator;
		$websites = apply_filters( 'mainwp-getsites', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), null, false, array('plugins_slug' => 'updraftplus/updraftplus.php') );
		$sites_ids = array();
		if ( is_array( $websites ) ) {
			foreach ( $websites as $website ) {
				$sites_ids[] = $website['id'];
			}
      unset( $websites );
		}
		$option = array(
			'plugin_upgrades' => true,
			'plugins' => true,
		);

		$dbwebsites = apply_filters( 'mainwp-getdbsites', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $sites_ids, array(), $option );
		$dbwebsites_updraftplus = MainWP_Updraftplus_Backups_Plugin::get_instance()->get_websites_with_the_plugin( $dbwebsites );
		unset( $dbwebsites );


		$what = ( empty( $what ) && isset( $_POST['what'] )) ? $_POST['what'] : $what;

		?>
		<div class="ui modal" id="mainwp-updraft-sync">
			<div class="header">
				<?php
				if ( 'save_settings' == $what ) {
					echo  __( 'Saving Settings to child sites ...', 'mainwp' );
				} else if ( 'update_history' == $what ) {
					if ( $_POST['remotescan'] ) {
							$msg = __( 'Rescanning...', 'mainwp-updraftplus-extension' );
					} else {
							$msg = __( 'Rescanning...', 'mainwp-updraftplus-extension' );
					}
						echo $msg;
				} else if ( 'addons_connect' == $what ) {
					echo __( 'Connect with your UpdraftPlus.Com account ...', 'mainwp' );
				} else if ( 'vault_bulk_connect' == $what ) {
					echo __( 'Connecting child sites with your UpdraftPlus Vault account ...', 'mainwp' );
				} else if ( 'backup_all' == $what ) {
					echo __( 'Requesting Backup on Child Sites ... ...', 'mainwp' );
				}
				?>
			</div>
			<div class="scrolling content">
				<?php

				if ( 'vault_bulk_connect' == $what ) {
					$email = $_POST['email'];
					$password = $_POST['pass'];

					if (empty($email) || empty($password)) {
						echo '<div class="ui message red">' . __( 'You need to supply both an email address and a password.', 'mainwp-updraftplus-extension' ) . '</div>';
						if ( $ajax_call ) {
							die();
						} else {
							return;
						}
					}
					?>
					<input type="hidden" id="mainwp_updraftplus_vault_opts" name="mainwp_updraftplus_vault_opts" email="<?php echo esc_attr($email); ?>" pass="<?php echo esc_attr($password); ?>"/>
					<?php
				}

				$have_active = false;
				if ( is_array( $dbwebsites_updraftplus ) && count( $dbwebsites_updraftplus ) > 0 ) {
					?>
					<div class="ui relaxed divided list">
						<?php
						foreach ( $dbwebsites_updraftplus as $website ) {
							$have_active = true;
							echo '<div class="item">' . stripslashes( $website['name'] );
							echo '<span class="right floated siteItemProcess" site-id="' . $website['id'] . '" status="queue"><span class="status"><i class="clock outline icon"></i></span></span>';
							echo '</div>';
						}
						?>
					</div>
					<?php
				}

				if ( ! $have_active ) {
					echo '<div class="ui yellow message">' . __( 'No websites were found with the Updraftplus Backups plugin installed.', 'mainwp' ) . '</div>';
					if ( $ajax_call ) {
						die();
					} else {
						return;
					}
				}
				?>
			</div>
			<div class="actions">
				<div class="ui cancel button"><?php echo __( 'Close', 'mainwp-updraftplus-extension' ); ?></div>
				<a class="ui button green" href="admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=settings"><?php echo __('Return to Settings', 'mainwp-updraftplus-extension' ); ?></a>
			</div>
		</div>
		<script type="text/javascript">
		jQuery( '#mainwp-updraft-sync' ).modal( 'show' );
		</script>
		<?php

		if ( 'save_settings' == $what ) {
				?>
				<script type="text/javascript">
						jQuery(document).ready(function ($) {
							updraftplus_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
							if (updraftplus_bulkTotalThreads > 0) {
								mainwp_updraftplus_save_settings_start_next();
							}
						});
				</script>
				<?php
		} else if ( 'addons_connect' == $what ) {
				?>
				<script type="text/javascript">
						jQuery(document).ready(function ($) {
							updraftplus_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
							if (updraftplus_bulkTotalThreads > 0) {
								mainwp_updraftplus_addons_connect_start_next();
							}
						});
				</script>
				<?php
		} else if ( 'vault_bulk_connect' == $what ) {
				?>
				<script type="text/javascript">
						jQuery(document).ready(function ($) {
							updraftplus_bulkTotalThreads = jQuery('.siteItemProcess[status=queue]').length;
							if (updraftplus_bulkTotalThreads > 0) {
								mainwp_updraftplus_vault_connect_start_next();
							}
						});
				</script>
				<?php
		}

		if ( $ajax_call ) {
			die();
		}
	}

	function ajax_save_settings() {
		@ini_set( 'display_errors', false );
		@error_reporting( 0 );
		$siteid = $_POST['updraftRequestSiteID'];
		$save_general = isset( $_POST['save_general'] ) && !empty( $_POST['save_general'] )  ? true : false;
		if ( empty( $siteid ) ) {
			die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

		$information = $this->perform_save_settings($siteid, $check_override = true, $save_general);
		die( json_encode( $information ) );
	}

	function mainwp_apply_plugin_settings($siteid) {
		$information = $this->perform_save_settings($siteid, false);
		$result = array();
		if (is_array($information)) {
			if ( 'success' == $information['result'] || 'noupdate' == $information['result'] ) {
				$result = array('result' => 'success');
			} else if (isset($information['message'])) {
				$result = array('result' => 'success', 'message' => $information['message']);
			} else if (isset($information['error'])) {
				$result = array('error' => $information['error']);
			} else {
				$result = array('result' => 'failed');
			}
		} else {
			$result = array('error' => __('Undefined error', 'mainwp-updraftplus-extension'));
		}
		die( json_encode( $result ) );
	}

	public function perform_save_settings($siteid, $check_override = true, $save_general = false ) {
		$settings = array();
		$updraft_plus_site = MainWP_Updraftplus_BackupsDB::get_instance()->get_setting_by( 'site_id', $siteid );
		$individual_update = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		$general = false;
		if ( $individual_update ) {
			if ($save_general) {
				$settings = get_site_option( 'mainwp_updraftplus_generalSettings' );
				$general = true;
			} else if ( $updraft_plus_site ) {
				if ( $updraft_plus_site->override ) {
						$settings = unserialize( base64_decode( $updraft_plus_site->settings ) );
				} else {
						die( json_encode( array( 'error' => 'Update Failed: Override General Settings need to be set to Yes.' ) ) );
				}
			}
		} else {
			if ( $updraft_plus_site && $check_override) {
				$this->check_override_settings( $updraft_plus_site->override );
			}
			$settings = get_site_option( 'mainwp_updraftplus_generalSettings' );
			$general = true;
		}

		if ( ! is_array( $settings ) || empty( $settings ) ) {
				die( json_encode( array( 'error' => $general ? 'Error: Empty General Settings.' : 'Error: Empty Individual Settings.' ) ) );
		}

		$send_fields = array();
		$settingKeys = self::get_settings_keys();
		foreach ( $settingKeys as $field ) {
			$send_fields[ $field ] = $settings[ $field ];
		}

		if ( $general ) {
			// do not save
			//unset( $send_fields['updraft_googledrive'] );
			//unset( $send_fields['updraft_dropbox'] );
			//unset( $send_fields['updraft_onedrive'] );
			//unset( $send_fields['updraft_azure'] );
			//unset( $send_fields['updraft_googlecloud'] );
            $send_fields['is_general'] = 1;
		}

		if (isset($send_fields['updraft_onedrive'])) {
            unset( $send_fields['updraft_onedrive'] );
        }
        if (isset($send_fields['updraft_azure'])) {
            unset( $send_fields['updraft_azure'] );
        }

        if (isset($settings['do_not_save_destinations_settings']) && $settings['do_not_save_destinations_settings']) {
            $destination = array(
                'updraft_dropbox',
                'updraft_googledrive',
                'updraft_googlecloud',
                'updraft_backblaze',
                'updraft_onedrive',
                //'updraft_email', //this is email reports config
                'updraft_s3',
                'updraft_s3generic',
                'updraft_dreamobjects',
                'updraft_ftp',
                'updraft_sftp_settings'
            );
            foreach($destination as $dest) {
                if (isset($send_fields[$dest]))
                    unset($send_fields[$dest]);
            }
            $send_fields['do_not_save_remote_settings'] = 1;
        }

		global $mainWPUpdraftPlusBackupsExtensionActivator;

		$post_data = array(
			'mwp_action' => 'save_settings',
			'settings' => base64_encode( serialize( $send_fields ) ),
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
		if ( is_array( $information ) ) {
			if ( isset( $information['sync_updraft_status'] ) ) {
					$syncStatus = $information['sync_updraft_status'];
				if ( is_array( $syncStatus ) ) {
						MainWP_Updraftplus_BackupsDB::get_instance()->update_setting_fields_by( 'site_id', $siteid, $syncStatus );
				}
			}
		}
		return $information;
	}

	function ajax_addons_connect() {
			@ini_set( 'display_errors', false );
			@error_reporting( 0 );

			$siteid = $_POST['updraftRequestSiteID'];
		if ( empty( $siteid ) ) {
				die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

			$addonsOptions = array();
			$updraft_plus_site = MainWP_Updraftplus_BackupsDB::get_instance()->get_setting_by( 'site_id', $siteid );
			$individual = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		if ( $individual ) {
			if ( $updraft_plus_site ) {
				if ( $updraft_plus_site->override ) {
						$settings = unserialize( base64_decode( $updraft_plus_site->settings ) );
						$addonsOptions = isset( $settings['addons_options'] ) ? $settings['addons_options'] : array();
				} else {
						die( json_encode( array( 'error' => 'Update Failed: Override General Settings need to be set to Yes.' ) ) );
				}
			}
		} else {
			if ( $updraft_plus_site ) {
					$this->check_override_settings( $updraft_plus_site->override );
			}
				$settings = get_site_option( 'mainwp_updraftplus_generalSettings' );
				$addonsOptions = isset( $settings['addons_options'] ) ? $settings['addons_options'] : array();
		}
		if ( ! is_array( $addonsOptions ) ) {
				$addonsOptions = array(); }
			$send_fields = array(
				'email' => isset( $addonsOptions['email'] ) ? $addonsOptions['email'] : '',
				'password' => isset( $addonsOptions['password'] ) ? $addonsOptions['password'] : '',
			);

			global $mainWPUpdraftPlusBackupsExtensionActivator;

			$post_data = array(
		'mwp_action' => 'addons_connect',
				'addons_options' => base64_encode( serialize( $send_fields ) ),
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $siteid, 'updraftplus', $post_data );
			die( json_encode( $information ) );
	}

		# TODO: Remove legacy storage setting keys from here

	public static function get_settings_keys() {
			return array(
				'updraft_autobackup_default',
				'updraftplus_dismissedautobackup',
				'updraftplus_dismissedexpiry',
				'updraft_interval',
				'updraft_interval_increments',
				'updraft_interval_database',
				'updraft_retain',
				'updraft_retain_db',
				'updraft_encryptionphrase',
				'updraft_service',
				'updraft_dir',
				'updraft_email',
				'updraft_delete_local',
				'updraft_include_plugins',
				'updraft_include_themes',
				'updraft_include_uploads',
				'updraft_include_others',
				'updraft_include_wpcore',
				'updraft_include_wpcore_exclude',
				'updraft_include_more',
				'updraft_include_blogs',
				'updraft_include_mu-plugins',
				'updraft_include_others_exclude',
				'updraft_include_uploads_exclude',
//				'updraft_adminlocking',
				'updraft_starttime_files',
				'updraft_starttime_db',
				'updraft_startday_db',
				'updraft_startday_files',
				'updraft_googledrive',
				'updraft_s3',
				'updraft_s3generic',
				'updraft_dreamhost',
				'updraft_disable_ping',
				'updraft_openstack',
				'updraft_bitcasa',
				'updraft_cloudfiles',
				'updraft_ssl_useservercerts',
				'updraft_ssl_disableverify',
				'updraft_report_warningsonly',
				'updraft_report_wholebackup',
				'updraft_log_syslog',
				'updraft_extradatabases',
				'updraft_split_every',
				'updraft_ssl_nossl',
				'updraft_backupdb_nonwp',
				'updraft_extradbs',
				'updraft_include_more_path',
				'updraft_dropbox',
				'updraft_ftp',
				'updraft_sftp_settings',
				'updraft_webdav_settings',
				'updraft_dreamobjects',
				'updraft_onedrive',
				'updraft_azure',
				'updraft_googlecloud',
				//'updraft_updraftvault',
				'updraft_retain_extrarules',
                'updraft_backblaze',
			);
	}

	public static function get_open_location_link( $site_id, $loc ) {
			$loc = base64_encode( $loc );
			return 'admin.php?page=Extensions-Mainwp-Updraftplus-Extension&action=mwpUpdraftOpenSite&websiteid=' . $site_id . '&open_location=' . $loc;
	}

	function ajax_override_settings() {
        $websiteId = $_POST['updraftRequestSiteID'];
		if ( empty( $websiteId ) ) {
            die( json_encode( array( 'error' => 'Empty site id.' ) ) ); }

        global $mainWPUpdraftPlusBackupsExtensionActivator;

        $website = apply_filters( 'mainwp-getsites', $mainWPUpdraftPlusBackupsExtensionActivator->get_child_file(), $mainWPUpdraftPlusBackupsExtensionActivator->get_child_key(), $websiteId );
		if ( $website && is_array( $website ) ) {
				$website = current( $website );
		}
		if ( ! $website ) {
				return; }

			$update = array(
				'site_id' => $website['id'],
				'override' => $_POST['override'],
			);

			MainWP_Updraftplus_BackupsDB::get_instance()->update_setting( $update );
			die( json_encode( array( 'result' => 'success' ) ) );
	}

	private function check_override_settings( $override ) {
		if ( 1 == $override ) {
				die( json_encode( array( 'message' => __( 'Not Updated - Individual site settings are in use.', 'mainwp' ) ) ) );
		}
	}
}
