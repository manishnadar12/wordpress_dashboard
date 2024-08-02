<?php
class MainWP_Rocket_Plugin {
	private $option_handle = 'mainwp_rocket_plugin_option';
	private $option = array();
	private static $order = '';
	private static $orderby = '';

	//Singleton
	private static $instance = null;

	static function get_instance() {
		if ( null == MainWP_Rocket_Plugin::$instance ) {
			MainWP_Rocket_Plugin::$instance = new MainWP_Rocket_Plugin();
		}
		return MainWP_Rocket_Plugin::$instance;
	}

	public function __construct() {
		$this->option = get_option( $this->option_handle );
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_wprocket_upgrade_noti_dismiss', array( $this, 'ajax_dismiss_upgrade_notice' ) );
		add_action( 'wp_ajax_mainwp_wprocket_version_noti_dismiss', array( $this, 'ajax_dismiss_version_notice' ) );
		add_action( 'wp_ajax_mainwp_rocket_active_plugin', array( $this, 'ajax_active_plugin' ) );
		add_action( 'wp_ajax_mainwp_rocket_upgrade_plugin', array( $this, 'ajax_upgrade_plugin' ) );
		add_action( 'wp_ajax_mainwp_wprocket_showhide_plugin', array( $this, 'ajax_showhide_plugin' ) );
	}

	public function get_option( $key = null, $default = '' ) {
		if ( isset( $this->option[ $key ] ) ) {
			return $this->option[ $key ];
		}
		return $default;
	}

	public function set_option( $key, $value ) {
		$this->option[ $key ] = $value;
		return update_option( $this->option_handle, $this->option );
	}

	public static function gen_dashboard_tab( $websites ) {
		$orderby = 'name';
		$_order = 'desc';

		self::$order = $_order;
		self::$orderby = $orderby;

		usort( $websites, array( 'MainWP_Rocket_Plugin', 'wprocket_data_sort' ) );
		?>

		<table class="ui single line table" id="mainwp-rocket-sites-table" style="width:100%">
			<thead>
				<tr>
					<th class="no-sort collapsing check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'mainwp-rocket-extension' ); ?></th>
					<th class="no-sort collapsing"><i class="sign in icon"></i></th>
					<th><?php _e( 'URL', 'mainwp-rocket-extension' ); ?></th>
					<th><?php _e( 'Version', 'mainwp-rocket-extension' ); ?></th>
					<th class="center aligned collapsing"><?php _e( 'Hidden', 'mainwp-rocket-extension' ); ?></th>
					<th><?php _e( 'Settings', 'mainwp-rocket-extension' ); ?></th>
					<th class="no-sort collapsing"><?php _e( '', 'mainwp-rocket-extension' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( is_array( $websites ) && count( $websites ) > 0 ) : ?>
					<?php self::get_plugin_dashboard_table_row( $websites ); ?>
				<?php else : ?>
					<tr>
						<td colspan="7"><?php _e( 'No websites were found with the WP Rocket plugin installed.', 'mainwp-rocket-extension' ); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
			<tfoot>
				<tr>
					<th class="check-column"><span class="ui checkbox"><input type="checkbox"></span></th>
					<th><?php _e( 'Site', 'mainwp-rocket-extension' ); ?></th>
					<th><i class="sign-in icon"></i></th>
					<th><?php _e( 'URL', 'mainwp-rocket-extension' ); ?></th>
					<th><?php _e( 'Version', 'mainwp-rocket-extension' ); ?></th>
					<th><?php _e( 'Visibility', 'mainwp-rocket-extension' ); ?></th>
					<th><?php _e( 'Settings', 'mainwp-rocket-extension' ); ?></th>
					<th><?php _e( '', 'mainwp-rocket-extension' ); ?></th>
				</tr>
			</tfoot>
		</table>
		<script type="text/javascript">
		jQuery( '#mainwp-rocket-sites-table' ).DataTable( {
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
			"language": { "emptyTable": "No websites were found with the WP Rocket plugin installed." },
			"drawCallback": function( settings ) {
				jQuery( '#mainwp-rocket-sites-table .ui.checkbox' ).checkbox();
				jQuery( '#mainwp-rocket-sites-table .ui.dropdown' ).dropdown();
			},
		} );
		</script>
		<?php
	}

	public static function get_plugin_dashboard_table_row( $websites ) {

		$plugin_name = 'WP Rocket';

		foreach ( $websites as $website ) {
			$website_id = intval($website['id']);
			$location = 'options-general.php?page=wprocket';
			$plugin_slug = esc_html($website['plugin_slug']);

			$class_active = ( isset( $website['wprocket_active'] ) && ! empty( $website['wprocket_active'] ) ) ? '' : 'negative';
			$class_update = ( isset( $website['wprocket_upgrade'] ) ) ? 'warning' : '';
			$class_update = ( 'negative' == $class_active ) ? 'negative' : $class_update;

			$version = '';

			if ( isset( $website['wprocket_upgrade'] ) ) {
				if ( isset( $website['wprocket_upgrade']['new_version'] ) ) {
					$version = $website['wprocket_upgrade']['new_version'];
				}
				if ( isset( $website['wprocket_upgrade']['plugin'] ) ) {
					$plugin_slug = esc_html($website['wprocket_upgrade']['plugin']);
				}
			}

			?>
			<tr class="<?php echo $class_active . ' ' . $class_update; ?>" website-id="<?php echo $website_id; ?>" plugin-name="<?php echo $plugin_name; ?>" plugin-slug="<?php echo $plugin_slug; ?>" version="<?php echo $version; ?>">
				<td class="check-column"><span class="ui checkbox"><input type="checkbox" name="checked[]"></span></td>
				<td><a href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php echo stripslashes( $website['name'] ); ?></a></td>
				<td><a target="_blank" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>"><i class="sign in icon"></i></a></td>
				<td><a href="<?php echo $website['url']; ?>" target="_blank"><?php echo $website['url']; ?></a></td>
				<td><?php echo ( isset( $website['wprocket_upgrade'] ) ) ? '<i class="exclamation circle icon"></i>' : ''; ?> <?php echo ( isset( $website['wprocket_plugin_version'] ) ) ? $website['wprocket_plugin_version'] : 'N/A'; ?></td>
				<td class="wp-rocket-visibility"><?php echo ( 1 == $website['hide_plugin'] ) ? __( 'Yes', 'mainwp-rocket-extension' ) : __( 'No', 'mainwp-rocket-extension' ); ?></td>
				<td><?php echo ( $website['isOverride'] ? __( 'Individual', 'mainwp-rocket-extension' ) : __( 'General', 'mainwp-rocket-extension' ) ); ?></td>
				<td>
					<div class="ui left pointing dropdown icon mini basic green button" style="z-index:999" data-tooltip="<?php esc_attr_e( 'See more options', 'mainwp-rocket-extension' ); ?>" data-position="left center" data-inverted="">
						<a href="javascript:void(0)"><i class="ellipsis horizontal icon"></i></a>
						<div class="menu">
							<a class="item" href="admin.php?page=managesites&dashboard=<?php echo $website_id; ?>"><?php _e( 'Dashboard', 'mainwp-rocket-extension' ); ?></a>
							<a class="item" href="admin.php?page=managesites&id=<?php echo $website_id; ?>"><?php _e( 'Edit', 'mainwp-rocket-extension' ); ?></a>
							<a class="item" href="admin.php?page=SiteOpen&newWindow=yes&websiteid=<?php echo $website_id; ?>&location=<?php echo base64_encode( $location ); ?>" target="_blank"><?php _e( 'Open WP Rocket', 'mainwp-rocket-extension' ); ?></a>

							<?php if ( 1 == $website['hide_plugin'] ) : ?>
							<a class="item mwp_rocket_showhide_plugin" href="#" showhide="show"><?php _e( 'Unhide Plugin', 'mainwp-rocket-extension' ); ?></a>
							<?php else : ?>
							<a class="item mwp_rocket_showhide_plugin" href="#" showhide="hide"><?php _e( 'Hide Plugin', 'mainwp-rocket-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['wprocket_active'] ) && empty( $website['wprocket_active'] ) ) : ?>
							<a class="item mwp_rocket_active_plugin" href="#"><?php _e( 'Activate Plugin', 'mainwp-rocket-extension' ); ?></a>
							<?php else : ?>
							<a class="item" href="#" onclick="mainwp_rocket_dashboard_tab_purge_all( <?php echo $website['id']; ?>, this ); return false;"><?php _e( 'Clear Cache', 'mainwp-rocket-extension' ); ?></a>
							<?php endif; ?>
							<?php if ( isset( $website['wprocket_upgrade'] ) ) : ?>
							<a class="item mwp_rocket_upgrade_plugin" href="#"><?php _e( 'Update Plugin', 'mainwp-rocket-extension' ); ?></a>
							<?php endif; ?>
						</div>
					</div>
				</td>
			</tr>
			<?php
		}
	}

	public static function wprocket_data_sort( $a, $b ) {
		if ( 'version' == self::$orderby ) {
			$a = $a['wprocket_plugin_version'];
			$b = $b['wprocket_plugin_version'];
			$cmp = version_compare( $a, $b );
		} else if ( 'url' == self::$orderby ) {
			$a = $a['url'];
			$b = $b['url'];
			$cmp = strcmp( $a, $b );
		} else if ( 'hidden' == self::$orderby ) {
			$a = $a['hide_plugin'];
			$b = $b['hide_plugin'];
			$cmp = $a - $b;
		} else {
			$a = $a['name'];
			$b = $b['name'];
			$cmp = strcmp( $a, $b );
		}
		if ( 0 == $cmp ) {
			return 0;
		}

		if ( 'desc' == self::$order ) {
			return ( $cmp > 0 ) ? -1 : 1;
		} else {
			return ( $cmp > 0 ) ? 1 : -1;
		}
	}

	public function get_websites_with_the_plugin( $websites, $selected_group = 0, $plugin_data_sites = array() ) {
		$websites_wprocket = array();
		$pluginHide = $this->get_option( 'hide_the_plugin' );

		if ( ! is_array( $pluginHide ) ) {
			$pluginHide = array();
		}

		if ( is_array( $websites ) && count( $websites ) ) {
			if ( empty( $selected_group ) ) {
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							foreach ( $plugins as $plugin ) {
								if ( ('wp-rocket/wp-rocket.php' == $plugin['slug']) ) {
									$site = MainWP_Rocket_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
									if ( $plugin['active'] ) {
										$site['wprocket_active'] = 1; } else {
										$site['wprocket_active'] = 0; }
										// get upgrade info
										$site['wprocket_plugin_version'] = $plugin['version'];
										$site['plugin_slug'] = $plugin['slug'];
										$plugin_upgrades = json_decode( $website->plugin_upgrades, 1 );
										if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
											if ( isset( $plugin_upgrades['wp-rocket/wp-rocket.php'] ) ) {
												$upgrade = $plugin_upgrades['wp-rocket/wp-rocket.php'];
												if ( isset( $upgrade['update'] ) ) {
													$site['wprocket_upgrade'] = $upgrade['update'];
												}
											}
										}

										$site['hide_plugin'] = 0;
										if ( isset( $pluginHide[ $website->id ] ) && $pluginHide[ $website->id ] ) {
											$site['hide_plugin'] = 1;
										}

										$plugintDS = isset( $plugin_data_sites[ $site['id'] ] ) ? $plugin_data_sites[ $site['id'] ] : array();
										if ( ! is_array( $plugintDS ) ) {
											$plugintDS = array(); }

										if ( ! isset ( $plugintDS['is_active'] ) || $site['wprocket_active'] != $plugintDS['is_active'] ) {
											//update active status
											$update = array(
											'site_id' => $website->id,
											'is_active' => $site['wprocket_active'],
											);
											MainWP_Rocket_DB::get_instance()->update_wprocket( $update );
										}

										$site['isOverride'] = isset( $plugintDS['override'] ) ? $plugintDS['override'] : 0;

										$others_data = isset( $plugintDS['others'] ) ? $plugintDS['others'] : array();
										if ( is_array( $others_data ) ) {
											if ( isset( $others_data['rocket_boxes'] ) ) {
												$site['rocket_boxes'] = (array) $others_data['rocket_boxes'];
											}
										}
										$site['version'] = property_exists($website, 'version') ? $website->version : '';
										$websites_wprocket[] = $site;
										break;
								}
							}
						}
					}
				}
			} else {
				global $mainWPRocketExtensionActivator;

				$group_websites = apply_filters( 'mainwp-getdbsites', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), array(), array( $selected_group ) );
				$sites = array();
				foreach ( $group_websites as $site ) {
					$sites[] = $site->id;
				}
				foreach ( $websites as $website ) {
					if ( $website && $website->plugins != '' && in_array( $website->id, $sites ) ) {
						$plugins = json_decode( $website->plugins, 1 );
						if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
							foreach ( $plugins as $plugin ) {
								if ( ('wp-rocket/wp-rocket.php' == $plugin['slug']) ) {
									$site = MainWP_Rocket_Utility::map_site( $website, array( 'id', 'name', 'url' ) );
									if ( $plugin['active'] ) {
										$site['wprocket_active'] = 1; } else {
										$site['wprocket_active'] = 0; }

										$site['wprocket_plugin_version'] = $plugin['version'];
										// get upgrade info
										$plugin_upgrades = json_decode( $website->plugin_upgrades, 1 );
										if ( is_array( $plugin_upgrades ) && count( $plugin_upgrades ) > 0 ) {
											if ( isset( $plugin_upgrades['wp-rocket/wp-rocket.php'] ) ) {
												$upgrade = $plugin_upgrades['wp-rocket/wp-rocket.php'];
												if ( isset( $upgrade['update'] ) ) {
													$site['wprocket_upgrade'] = $upgrade['update'];
												}
											}
										}
										$site['hide_plugin'] = 0;
										if ( isset( $pluginHide[ $website->id ] ) && $pluginHide[ $website->id ] ) {
											$site['hide_plugin'] = 1;
										}

										$plugintDS = isset( $plugin_data_sites[ $site['id'] ] ) ? $plugin_data_sites[ $site['id'] ] : array();
										if ( ! is_array( $plugintDS ) ) {
											$plugintDS = array(); }

										if ( $site['wprocket_active'] != $plugintDS['is_active'] ) {
											//update active status
											$update = array(
											'site_id' => $website->id,
											'is_active' => $site['wprocket_active'],
											);
											MainWP_Rocket_DB::get_instance()->update_wprocket( $update );
										}

										$site['isOverride'] = $plugintDS['override'];

										$others_data = isset( $plugintDS['others'] ) ? $plugintDS['others'] : array();
										if ( is_array( $others_data ) ) {
											if ( isset( $others_data['rocket_boxes'] ) ) {
												$site['rocket_boxes'] = (array) $others_data['rocket_boxes'];
											}
										}
										$site['version'] = $website->version;
										$websites_wprocket[] = $site;
										break;
								}
							}
						}
					}
				}
			}
		}

		// if search action
		$search_sites = array();
		if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
			$find = trim( $_GET['s'] );
			foreach ( $websites_wprocket as $website ) {
				if ( stripos( $website['name'], $find ) !== false || stripos( $website['url'], $find ) !== false ) {
					$search_sites[] = $website;
				}
			}
			$websites_wprocket = $search_sites;
		}
		unset( $search_sites );

		return $websites_wprocket;
	}

	public static function render_mainwp_rocket_bulk_actions( $websites ) {
		global $mainWPRocketExtensionActivator;
		?>
		<div class="mainwp-actions-bar">
			<div class="ui grid">
				<div class="ui two column row">
					<div class="column">
						<select class="ui dropdown" id="mwp_rocket_plugin_action">
							<option value="none"><?php _e( 'Bulk Actions', 'mainwp-rocket-extension' ); ?></option>
							<option value="activate-selected"><?php _e( 'Activate Plugin', 'mainwp-rocket-extension' ); ?></option>
							<option value="update-selected"><?php _e( 'Update Plugin', 'mainwp-rocket-extension' ); ?></option>
							<option value="load-settings"><?php _e( 'Load Settings', 'mainwp-rocket-extension' ); ?></option>
							<option value="hide-selected"><?php _e( 'Hide Plugin', 'mainwp-rocket-extension' ); ?></option>
							<option value="show-selected"><?php _e( 'Unhide plugin', 'mainwp-rocket-extension' ); ?></option>
						</select>
						<input type="button" name="" id="wprocket_plugin_doaction_btn" class="ui basic button" value="<?php _e( 'Apply', 'mainwp-rocket-extension' ); ?>"/>
						<?php do_action( 'mainwp_rocket_actions_bar_left' ); ?>
					</div>
					<div class="right aligned column">
						<?php do_action( 'mainwp_rocket_actions_bar_right' ); ?>
					</div>
				</div>
			</div>
		</div>
    <?php
		return;
	}


	public function ajax_dismiss_upgrade_notice() {
		$website_id = $_POST['wprocketRequestSiteID'];
		if ( $website_id ) {
			@session_start();
			$dismiss = isset( $_SESSION['mainwp_wprocket_dismiss_upgrade_plugin_notis'] ) ? $_SESSION['mainwp_wprocket_dismiss_upgrade_plugin_notis'] : false;
			if ( is_array( $dismiss ) && count( $dismiss ) > 0 ) {
				$dismiss[ $website_id ] = 1;
			} else {
				$dismiss = array();
				$dismiss[ $website_id ] = 1;
			}
			$_SESSION['mainwp_wprocket_dismiss_upgrade_plugin_notis'] = $dismiss;
			die( 'updated' );
		}
		die( 'nochange' );
	}


	public function ajax_dismiss_version_notice() {
		$website_id = $_POST['wprocketRequestSiteID'];
		if ( $website_id ) {
			session_start();
			$dismiss = $_SESSION['mainwp_wprocket_dismiss_version_plugin_notis'];
			if ( is_array( $dismiss ) && count( $dismiss ) > 0 ) {
				$dismiss[ $website_id ] = 1;
			} else {
				$dismiss = array();
				$dismiss[ $website_id ] = 1;
			}
			$_SESSION['mainwp_wprocket_dismiss_version_plugin_notis'] = $dismiss;
			die( 'updated' );
		}
		die( 'nochange' );
	}


	public function ajax_active_plugin() {
		$_POST['websiteId'] = $_POST['wprocketRequestSiteID'];
		do_action( 'mainwp_activePlugin' );
		die();
	}

	public function ajax_upgrade_plugin() {
		$_POST['websiteId'] = $_POST['wprocketRequestSiteID'];
		do_action( 'mainwp_upgradePluginTheme' );
		die();
	}

	public function ajax_showhide_plugin() {
		$siteid = isset( $_POST['wprocketRequestSiteID'] ) ? $_POST['wprocketRequestSiteID'] : null;
		$showhide = isset( $_POST['showhide'] ) ? $_POST['showhide'] : null;
		if ( null !== $siteid && null !== $showhide ) {
			global $mainWPRocketExtensionActivator;
			$post_data = array(
				'mwp_action' => 'set_showhide',
				'showhide' 	 => $showhide,
			);
			$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $siteid, 'wp_rocket', $post_data );

			if ( is_array( $information ) && isset( $information['result'] ) && 'SUCCESS' === $information['result'] ) {
				$hide_plugin = $this->get_option( 'hide_the_plugin' );
				if ( ! is_array( $hide_plugin ) ) {
					$hide_plugin = array();
				}
				$hide_plugin[ $siteid ] = ( 'hide' === $showhide ) ? 1 : 0;
				$this->set_option( 'hide_the_plugin', $hide_plugin );
			}
			die( json_encode( $information ) );
		}
		die();
	}
}
