<?php

class MainWP_Rocket {
	public static $instance = null;
	private $rocket_settings = array();
	public $template_path = null;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->template_path = MAINWP_WP_ROCKET_PATH . 'views/settings';
	}

	public function admin_init() {
		add_action( 'wp_ajax_mainwp_rocket_site_override_settings', array( $this, 'ajax_override_settings' ) );
		add_action( 'wp_ajax_mainwp_rocket_reload_optimize_info', array( $this, 'ajax_reload_optimize_info' ) );
		add_action( 'wp_ajax_mainwp_rocket_purge_cloudflare', array( $this, 'ajax_purge_cloudflare' ) );
        add_action( 'wp_ajax_mainwp_rocket_purge_opcache', array( $this, 'ajax_purge_opcache' ) );
		add_action( 'wp_ajax_mainwp_rocket_purge_cache_all', array( $this, 'ajax_purge_cache_all' ) );
		add_action( 'wp_ajax_mainwp_rocket_preload_cache', array( $this, 'ajax_preload_cache' ) );
        add_action( 'wp_ajax_mainwp_rocket_generate_critical_css', array( $this, 'ajax_generate_critical_css' ) );
		add_action( 'wp_ajax_mainwp_rocket_save_opts_to_child_site', array( $this, 'ajax_save_opts_to_child_site' ) );
		add_action( 'wp_ajax_mainwp_rocket_optimize_data_on_child_site', array( $this, 'ajax_optimize_database_site' ) );
		add_action( 'wp_ajax_mainwp_rocket_site_load_existing_settings', array( $this, 'ajax_load_existing_settings' ) );
		add_action( 'wp_ajax_mainwp_rocket_rightnow_load_sites', array( $this, 'ajax_general_load_sites' ) );
		add_action( 'mainwp_delete_site', array( &$this, 'delete_site_data' ), 10, 1 );
		add_action( 'mainwp_updatesoverview_widget_bottom', array( &$this, 'mainwp_rocket_hook_buttons' ) );
		add_filter( 'mainwp_managesites_bulk_actions', array( &$this, 'managesites_bulk_actions' ), 10, 1 );
		add_action( 'mainwp-site-synced', array( &$this, 'site_synced' ), 10, 2 );
		add_filter( 'mainwp-sync-others-data', array( $this, 'sync_others_data' ), 10, 2 );
	}

	public function site_synced( $pWebsite, $information = array() ) {
		if ( $pWebsite && $pWebsite->plugins != '' ) {
			$plugins = json_decode( $pWebsite->plugins, 1 );
			$is_active = 0;
			if ( is_array( $plugins ) && count( $plugins ) != 0 ) {
				foreach ( $plugins as $plugin ) {
					if ( 'wp-rocket/wp-rocket.php' == $plugin['slug'] ) {
						if ( $plugin['active'] ) {
							$is_active = 1;
						}
						break;
					}
				}
			}
			$update = array(
				'site_id' => $pWebsite->id,
				'is_active' => $is_active,
			);
			MainWP_Rocket_DB::get_instance()->update_wprocket( $update );
		}
		if ( is_array( $information ) && isset( $information['syncWPRocketData'] ) ) {
			$data = $information['syncWPRocketData'];
			if ( is_array( $data ) ) {
				$update = array( 'site_id' => $pWebsite->id );
				$wprocket_data = MainWP_Rocket_DB::get_instance()->get_wprocket_by( 'site_id' , $pWebsite->id, 'others' );
				$others = unserialize( base64_decode( $wprocket_data->others ) );
				if ( ! is_array( $others ) ) {
					$others = array(); }
				if ( isset( $data['rocket_boxes'] ) ) {
					$others['rocket_boxes'] = $data['rocket_boxes'];
				} else if ( isset( $others['rocket_boxes'] ) ) {
					unset( $others['rocket_boxes'] );
				}
				$update['others'] = base64_encode( serialize( $others ) );
				MainWP_Rocket_DB::get_instance()->update_wprocket( $update );
			}
			unset( $information['syncWPRocketData'] );
		}
	}

	public function sync_others_data( $data, $pWebsite = null ) {
		if ( ! is_array( $data ) ) {
			$data = array();
		}
		$data['syncWPRocketData'] = 'yes';
		return $data;
	}

	public function get_options() {
		if ( empty( $this->rocket_settings ) ) {
			if ( self::is_manage_sites_page() ) {
				$site_id = self::get_manage_site_id();
				$wprocket = MainWP_Rocket_DB::get_instance()->get_wprocket_by( 'site_id', $site_id );
				if ( ! empty( $wprocket ) ) {
					$this->rocket_settings = unserialize( base64_decode( $wprocket->settings ) );
				} else {
					$this->rocket_settings = mainwp_get_rocket_default_options();
					$update = array(
						'site_id' => $site_id,
						'settings' => base64_encode( serialize( $this->rocket_settings ) ),
					);
					MainWP_Rocket_DB::get_instance()->update_wprocket( $update );
				}
			} else {
				$this->rocket_settings = get_option( MAINWP_ROCKET_GENERAL_SETTINGS );
			}

			if ( ! is_array( $this->rocket_settings ) ) {
				$this->rocket_settings = mainwp_get_rocket_default_options();
			}
		}
		return $this->rocket_settings;
	}

	public function delete_site_data( $website ) {
		if ( $website ) {
			MainWP_Rocket_DB::get_instance()->delete_wprocket( 'site_id', $website->id );
		}
	}

	public static function render() {
		$website = null;
        $current_site_id = 0;
		if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
            $current_site_id = intval($_GET['id']);
			global $mainWPRocketExtensionActivator;
			$option = array(
				'plugin_upgrades' => true,
				'plugins' 				=> true,
			);
			$dbwebsites = apply_filters( 'mainwp-getdbsites', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), array( $current_site_id ), array(), $option );
			if ( is_array( $dbwebsites ) && ! empty( $dbwebsites ) ) {
				$website = current( $dbwebsites );
			}
		}

		if ( self::is_manage_sites_page() ) {
			$error = '';
			if ( empty( $website ) || empty( $website->id ) ) {
				$error = __( 'Error: Site not found.', 'mainwp' );
			} else {
				$activated = false;
				if ( $website && $website->plugins != '' ) {
					$plugins = json_decode( $website->plugins, 1 );
					if ( is_array( $plugins ) && count( $plugins ) > 0 ) {
						foreach ( $plugins as $plugin ) {
							if ( ( 'wp-rocket/wp-rocket.php' == $plugin['slug'] ) ) {
								if ( $plugin['active'] ) {
									$activated = true;
								}
								break;
							}
						}
					}
				}
				if ( ! $activated ) {
					$error = __( 'WP Rocket plugin is not installed or activated on the site.', 'mainwp-rocket-extension' );
				}
			}

			if ( ! empty( $error ) ) {
				do_action( 'mainwp-pageheader-sites', 'WPRocket' );
				echo '<div class="ui red message">' . $error . '</div>';
				do_action( 'mainwp-pagefooter-sites', 'WPRocket' );
				return;
			}
		}

		self::render_tabs( $website );
	}

	public static function render_tabs( $pWebsite = null ) {
		$message = '';
		if ( isset( $_GET['message'] ) ) {
			switch ( $_GET['message'] ) {
				case 1:
					$message = __( 'Settings saved successfully!', 'mainwp-rocket-extension' );
					break;
				case 2:
					$message = __( 'Settings imported and saved successfully!', 'mainwp-rocket-extension' );
					break;
			}
		}

		global $mainWPRocketExtensionActivator;

		$dbwebsites_wprocket = array();

		if ( ! self::is_manage_sites_page() ) {
             // get sites with the wp-rocket plugin installed only
            $others = array(
                'plugins_slug' => 'wp-rocket/wp-rocket.php'
            );
			$websites = apply_filters( 'mainwp-getsites', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), null, false, $others );
			$sites_ids = array();
			if ( is_array( $websites ) ) {
				foreach ( $websites as $site ) {
					$sites_ids[] = $site['id'];
				}
				unset( $websites );
			}

			$option = array(
				'plugin_upgrades' => true,
				'version' 				=> true,
				'plugins' 				=> true,
			);

			$dbwebsites = apply_filters( 'mainwp-getdbsites', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $sites_ids, array(), $option );

			$selected_group = 0;

			if ( isset( $_POST['mainwp_rocket_plugin_groups_select'] ) ) {
				$selected_group = intval( $_POST['mainwp_rocket_plugin_groups_select'] );
			}

			$pluginDataSites = array();
			if ( count( $sites_ids ) > 0 ) {
				$pluginDataSites = MainWP_Rocket_DB::get_instance()->get_wprockets_data( $sites_ids );
			}

			$dbwebsites_wprocket = MainWP_Rocket_Plugin::get_instance()->get_websites_with_the_plugin( $dbwebsites, $selected_group, $pluginDataSites );

			unset( $dbwebsites );
			unset( $pluginDataSites );
		}

		$perform_action = $action = '';

		if ( isset( $_GET['_perform_action'] ) && ! empty( $_GET['_perform_action'] ) ) {
			$perform_action = $_GET['_perform_action'];
			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], $perform_action ) ) {
				_e( 'WP Nonce not verfied.', 'mainwp-rocket-extension' );
				return;
			}

			$style_tab_rocket_dashboard_opts = '';

            // actions: [ mainwp_rocket_save_opts_child_sites, mainwp_rocket_purge_cache_all, mainwp_rocket_preload_cache, mainwp_rocket_purge_opcache ]
			$action = str_replace( 'mainwp_rocket_', '', $perform_action );
		}

        $action = esc_html($action);

        $current_site_id = 0;

		if ( self::is_manage_sites_page() ) {
			do_action( 'mainwp-pageheader-sites', 'WPRocket' );
			$is_manage_site = true;
            $current_site_id = ! empty( $pWebsite ) ? intval($pWebsite->id) : 0;
		} else {
			$is_manage_site = false;
		}

		$is_override = false;

		if ( $is_manage_site ) {
			$wprocket = MainWP_Rocket_DB::get_instance()->get_wprocket_by( 'site_id' , $current_site_id, 'override' );
			if ( $wprocket ) {
				$is_override = $wprocket->override;
			}
		}

		?>

		<?php if ( ! $is_manage_site && ( ! empty( $action ) ) ) : ?>
                <div class="ui modal" id="mainwp-rocket-sync-data-modal">
                    <div class="header"><?php echo __( 'MainWP Rocket', 'mainwp-rocket-extension' ); ?></div>
                    <div class="scrolling content"><?php MainWP_Rocket::ajax_general_load_sites( $action ); ?></div>
                    <div class="actions">
                        <div class="ui cancel reload button"><?php echo __( 'Close', 'mainwp-rocket-extension' ); ?></div>
                    </div>
                </div>
                <script type="text/javascript">
                        jQuery( '#mainwp-rocket-sync-data-modal' ).modal( 'show' );
                </script>
		<?php endif; ?>


        <form action="options.php" id="mainwp-rocket-settings-form" method="post" enctype="multipart/form-data">
        <input type="hidden" name="mainwp_rocket_current_site_id" value="<?php echo $current_site_id; ?>">
        <?php settings_fields( 'mainwp_wp_rocket' ); ?>

        <div class="ui labeled icon inverted menu mainwp-sub-submenu" id="mainwp-rocket-menu">
			<?php if ( ! $is_manage_site ) : ?>
			<a href="#dashboard" class="item active" data-tab="dashboard"><i class="tasks icon"></i> <?php _e( 'Dashboard', 'mainwp-rocket-extension' ); ?></a>
			<?php endif; ?>
			<a href="#wp-rocket" class="item" data-tab="wp-rocket"><i class="cog icon"></i> <?php _e( 'WP Rocket', 'mainwp-rocket-extension' ); ?></a>
			<a href="#cache" class="item" data-tab="cache"><i class="cog icon"></i> <?php _e( 'Cache', 'mainwp-rocket-extension' ); ?></a>
			<a href="#file-optimization" class="item" data-tab="file-optimization"><i class="cog icon"></i> <?php _e( 'File Optimization', 'mainwp-rocket-extension' ); ?></a>
			<a href="#media" class="item" data-tab="media"><i class="cog icon"></i> <?php _e( 'Media', 'mainwp-rocket-extension' ); ?></a>
			<a href="#preload" class="item" data-tab="preload"><i class="cog icon"></i> <?php _e( 'Preload', 'mainwp-rocket-extension' ); ?></a>
			<a href="#advanced-rules" class="item" data-tab="advanced-rules"><i class="cog icon"></i> <?php _e( 'Advanced Rules', 'mainwp-rocket-extension' ); ?></a>
			<a href="#database" class="item" data-tab="database"><i class="cog icon"></i> <?php _e( 'Database', 'mainwp-rocket-extension' ); ?></a>
            <a href="#cdn" class="item" data-tab="cdn"><i class="cog icon"></i> <?php _e( 'CDN', 'mainwp-rocket-extension' ); ?></a>
            <a href="#heartbeat" class="item" data-tab="heartbeat"><i class="cog icon"></i> <?php _e( 'Heartbeat', 'mainwp-rocket-extension' ); ?></a>
			<a href="#varnish" class="item" data-tab="varnish"><i class="cog icon"></i> <?php _e( 'Varnish', 'mainwp-rocket-extension' ); ?></a>
            <a href="#tools" class="item" data-tab="tools"><i class="cog icon"></i> <?php _e( 'Tools', 'mainwp-rocket-extension' ); ?></a>
		</div>

        <div class="ui yellow message" id="mainwp-rocket-message-zone" style="display:none"></div>

		<?php if ( ! $is_manage_site ) : ?>
		<div class="ui active tab" id="mainwp-rocket-dashboard" data-tab="dashboard">
			<?php MainWP_Rocket_Plugin::render_mainwp_rocket_bulk_actions( $dbwebsites_wprocket ); ?>
			<div class="ui segment">
				<?php if ( ! empty( $message ) ) : ?>
					<div class="ui green message"><i class="close icon"></i> <?php echo $message; ?></div>
				<?php endif; ?>
				<?php MainWP_Rocket_Plugin::gen_dashboard_tab( $dbwebsites_wprocket ); ?>
			</div>
		</div>
		<?php endif; ?>
		<div class="ui alt segment <?php echo $is_manage_site ? "active" : ""; ?> tab" id="mainwp-rocket-wp-rocket-settings" data-tab="wp-rocket">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
					<h3 class="ui dividing header"><?php echo __( 'WP Rocket Dashboard', 'mainwp-rocket-extension' ); ?></h3>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Beta testing', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[do_beta]" <?php checked( mainwp_get_rocket_option( 'do_beta', 0 ), 1 ); ?> id="do_beta">
							<label><em><?php echo __( 'I am part of the WP Rocket Beta Testing Program.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Rocket analytics', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[analytics_enabled]" <?php checked( mainwp_get_rocket_option( 'analytics_enabled', 0 ), 1 ); ?> id="analytics_enabled">
							<label><em><?php echo __( 'I agree to share anonymous data with the development team to help improve WP Rocket.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Remove all cached files', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column">
							<a href="<?php echo ( $is_manage_site ? '' : wp_nonce_url( admin_url( "admin.php?page=Extensions-Mainwp-Rocket-Extension&_perform_action=mainwp_rocket_purge_cache_all" ), 'mainwp_rocket_purge_cache_all' ) ); ?>" onclick="<?php echo ( $is_manage_site ? 'mainwp_rocket_individual_purge_all(' . $current_site_id . ', this); return false;' : '' ); ?>" class="ui green basic button"><?php echo __( 'Clear Cache', 'mainwp-rocket-extension' ); ?></a>
                            <span class="status"></span>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Start cache preloading', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column">
							<a href="<?php echo ( $is_manage_site ? '' : wp_nonce_url( admin_url( "admin.php?page=Extensions-Mainwp-Rocket-Extension&_perform_action=mainwp_rocket_preload_cache" ), 'mainwp_rocket_preload_cache' ) ); ?>" onclick="<?php echo ( $is_manage_site ? 'mainwp_rocket_individual_preload_cache(' . $current_site_id . ', this); return false;' : '' ); ?>" class="ui green basic button"><?php echo __( 'Preload Cache', 'mainwp-rocket-extension' ); ?></a>
                            <span class="status"></span>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Purge OPCache content', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column">
							<a href="<?php echo ( $is_manage_site ? '' : wp_nonce_url( admin_url( "admin.php?page=Extensions-Mainwp-Rocket-Extension&_perform_action=mainwp_rocket_purge_opcache" ), 'mainwp_rocket_purge_opcache' ) ); ?>" onclick="<?php echo ( $is_manage_site ? 'mainwp_rocket_individual_purge_opcache(' . $current_site_id . ', this); return false;' : '' ); ?>" class="ui green basic button"><?php echo __( 'Purge OPcache', 'mainwp-rocket-extension' ); ?></a>
                            <span class="status"></span>
						</div>
					</div>
                    <div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Regenerate Critical CSS', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column">
							<a href="<?php echo ( $is_manage_site ? '' : wp_nonce_url( admin_url( "admin.php?page=Extensions-Mainwp-Rocket-Extension&_perform_action=mainwp_rocket_generate_critical_css" ), 'mainwp_rocket_generate_critical_css' ) ); ?>" onclick="<?php echo ( $is_manage_site ? 'mainwp_rocket_individual_generate_critical_css(' . $current_site_id . ', this); return false;' : '' ); ?>" class="ui green basic button"><?php echo __( 'Regenerate Critical CSS', 'mainwp-rocket-extension' ); ?></a>
                            <span class="status"></span>
						</div>
                    </div>
					<div class="ui divider"></div>
					<input type="submit" name="submit" id="submit" class="ui green right floated button" value="<?php esc_attr_e( 'Save Changes', 'mainwp-rocket-extension' ); ?>" />
			</div>
			<div class="mainwp-side-content">
				<?php
                if ( $is_manage_site ) {
                  self::site_settings_box( $current_site_id );
                }
                ?>
				<h3 class="header"><?php echo __( 'MainWP Rocket Extension', 'mainwp-rocket-extension' ); ?></h3>
				<p><?php echo __( 'With the MainWP Rocket Extension, you can control the WP Rocket settings for all your child sites directly from your MainWP Dashboard. This includes giving you the ability to manage your preferences, clear or preload cache on your child sites.', 'mainwp-rocket-extension' ); ?></p>
                <?php self::render_help_message(); ?>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

		<div class="ui alt segment tab" id="mainwp-rocket-cache-settings" data-tab="cache">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
					<h3 class="ui dividing header"><?php echo __( 'Cache Settings', 'mainwp-rocket-extension' ); ?></h3>
                    <?php
                    $cache_mobile = mainwp_get_rocket_option( 'cache_mobile', 0 );
                    ?>
					<div class="ui grid mainwp-parent-field field">
						<label class="six wide column middle aligned"><?php echo __( 'Mobile cache', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[cache_mobile]" <?php checked( $cache_mobile, 1 ); ?> id="cache_mobile">
							<label><em><?php echo __( 'Enable caching for mobile devices.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid mainwp-child-field field" <?php echo $cache_mobile ? "" : 'style="display:none"'; ?>>
						<label class="six wide column middle aligned"><?php echo __( '', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[do_caching_mobile_files]" <?php checked( mainwp_get_rocket_option( 'do_caching_mobile_files', 0 ), 1 ); ?> id="do_caching_mobile_files">
							<label><em><?php echo __( 'Separate cache files for mobile devices.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'User cache', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[cache_logged_user]" <?php checked( mainwp_get_rocket_option( 'cache_logged_user', 0 ), 1 ); ?> id="cache_logged_user">
							<label><em><?php echo __( 'Enable caching for logged-in WordPress users.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'SSL cache', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[cache_ssl]" <?php checked( mainwp_get_rocket_option( 'cache_ssl', 0 ), 1 ); ?> id="cache_ssl">
							<label><em><?php echo __( 'Enable caching for pages with https://', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
                    <?php
                    $purge_cron_unit = mainwp_get_rocket_option( 'purge_cron_unit', 'HOUR_IN_SECONDS' );
                    ?>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Cache lifespan', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column ui input">
							<input type="number" id="purge_cron_interval" name="mainwp_wp_rocket_settings[purge_cron_interval]" min="0" value="10">
                            <select class="ui dropdown" id="purge_cron_unit" name="mainwp_wp_rocket_settings[purge_cron_unit]">
                                  <option value="SECOND_IN_SECONDS" <?php selected( $purge_cron_unit, 'SECOND_IN_SECONDS' ); ?>><?php echo __( 'second(s)', 'mainwp-rocket-extension' ); ?></option>
                                  <option value="MINUTE_IN_SECONDS" <?php selected( $purge_cron_unit, 'MINUTE_IN_SECONDS' ); ?>><?php echo __( 'minute(s)', 'mainwp-rocket-extension' ); ?></option>
                                  <option value="HOUR_IN_SECONDS" <?php selected( $purge_cron_unit, 'HOUR_IN_SECONDS' ); ?>><?php echo __( 'hour(s)', 'mainwp-rocket-extension' ); ?></option>
                                  <option value="DAY_IN_SECONDS" <?php selected( $purge_cron_unit, 'DAY_IN_SECONDS' ); ?>><?php echo __( 'day(s)', 'mainwp-rocket-extension' ); ?></option>
                            </select>
							<label><em><?php echo __( 'Specify time after which the global cache is cleared (0 = unlimited ).', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui clearing divider"></div>
					<input type="submit" name="submit" id="submit" class="ui green right floated button" value="<?php esc_attr_e( 'Save Changes', 'mainwp-rocket-extension' ); ?>" />
			</div>
			<div class="mainwp-side-content">
				<?php
        if ( $is_manage_site ) {
          self::site_settings_box( $current_site_id );
        }
        ?>
				<h5 class="header"><?php echo __( 'Mobile Cache', 'mainwp-rocket-extension' ); ?></h5>
				<p><?php echo __( 'Makes your website mobile-friendlier.', 'mainwp-rocket-extension' ); ?></p>
				<h5 class="header"><?php echo __( 'User Cache', 'mainwp-rocket-extension' ); ?></h5>
				<p><?php echo __( 'User cache is great when you have user-specific or restricted content on your website.', 'mainwp-rocket-extension' ); ?></p>
				<h5 class="header"><?php echo __( 'SSL Cache', 'mainwp-rocket-extension' ); ?></h5>
				<p><?php echo __( 'SSL cache works best when your entire website runs on HTTPS.', 'mainwp-rocket-extension' ); ?></p>
				<h5 class="header"><?php echo __( 'Cache Lifespan', 'mainwp-rocket-extension' ); ?></h5>
				<p><?php echo __( 'Cache lifespan is the period of time after which all cache files are removed. Enable preloading for the cache to be rebuilt automatically after lifespan expiration. Reduce lifespan to 10 hours or less if you notice issues that seem to appear periodically.', 'mainwp-rocket-extension' ); ?></p>
				<?php self::render_help_message(); ?>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

		<div class="ui alt segment tab" id="mainwp-rocket-file-optimization-settings" data-tab="file-optimization">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
					<h3 class="ui dividing header"><?php echo __( 'File Optimization Settings', 'mainwp-rocket-extension' ); ?></h3>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Minify HTML', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[minify_html]" <?php checked( mainwp_get_rocket_option( 'minify_html', 0 ), 1 ); ?> id="minify_html">
							<label><em><?php echo __( 'Minifying HTML removes whitespace and comments to reduce the size.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Combine Google Fonts files', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[minify_google_fonts]" <?php checked( mainwp_get_rocket_option( 'minify_google_fonts', 0 ), 1 ); ?> id="minify_google_fonts">
							<label><em><?php echo __( 'Combining Google Fonts will reduce the number of HTTP requests.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Remove query strings from static resources', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[remove_query_strings]" <?php checked( mainwp_get_rocket_option( 'remove_query_strings', 0 ), 1 ); ?> id="remove_query_strings">
							<label><em><?php echo __( 'Removes the version query string from static files (e.g. style.css?ver=1.0) and encodes it into the filename instead (e.g. style-1.0.css). Can improve your GTMetrix score.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
                    <?php
                    $minify_css = mainwp_get_rocket_option( 'minify_css', 0 );
                    ?>
					<div class="ui grid mainwp-parent-field field">
						<label class="six wide column middle aligned"><?php echo __( 'Minify CSS files', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[minify_css]" <?php checked( $minify_css, 1 ); ?> id="minify_css">
							<label><em><?php echo __( 'Minify CSS removes whitespace and comments to reduce the file size.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid mainwp-child-field field" <?php echo $minify_css ? '' : 'style="display:none"'; ?>>
						<label class="six wide column middle aligned"><?php echo __( 'Combine CSS files (Enable Minify CSS files to select)', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[minify_concatenate_css]" <?php checked( mainwp_get_rocket_option( 'minify_concatenate_css', 0 ), 1 ); ?> id="minify_concatenate_css">
							<label><em><?php echo __( 'Combine CSS merges all your files into 1, reducing HTTP requests. Not recommended if your site uses HTTP/2.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>

						<label class="six wide column middle aligned"><?php echo __( 'Excluded CSS files', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column">
                            <textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[exclude_css]" placeholder="/wp-content/plugins/some-plugin/(.*).css" id="exclude_css"><?php echo mainwp_rocket_field_value( 'exclude_css', 'textarea' ); ?></textarea><br />
							<label><em><?php echo __( 'Specify URLs of CSS files to be excluded from minification and concatenation.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
                     <?php
                    $async_css = mainwp_get_rocket_option( 'async_css', 0 );
                    ?>
					<div class="ui grid mainwp-parent-field field">
						<label class="six wide column middle aligned"><?php echo __( 'Optimize CSS delivery', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[async_css]" <?php checked( $async_css, 1 ); ?> id="async_css">
							<label><em><?php echo __( 'Optimize CSS delivery eliminates render-blocking CSS on your website for faster perceived load time.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid mainwp-child-field field" <?php echo $async_css ? '' : 'style="display:none"'; ?>>
						<label class="six wide column middle aligned"><?php echo __( 'Fallback critical CSS', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column">
							<textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[critical_css]" id="critical_css"><?php echo mainwp_rocket_field_value( 'critical_css', 'textarea' ); ?></textarea><br />
							<label><em><?php echo __( 'Provides a fallback if auto-generated critical path CSS is incomplete.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>

                     <?php
                    $minify_js = mainwp_get_rocket_option( 'minify_js', 0 );
                    ?>
					<div class="ui grid mainwp-parent-field field">
						<label class="six wide column middle aligned"><?php echo __( 'Minify JavaScript files', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[minify_js]" <?php checked( $minify_js, 1 ); ?> id="minify_js">
							<label><em><?php echo __( 'Minify JavaScript removes whitespace and comments to reduce the file size.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid mainwp-child-field field mainwp-parent2-field" <?php echo $minify_js ? '' : 'style="display:none"'; ?>>
                        <?php
                        $minify_concatenate_js = mainwp_get_rocket_option( 'minify_concatenate_js', 0 );
                        ?>
                        <label class="six wide column middle aligned"><?php echo __( 'Combine JavaScript files (Enable Minify JavaScript files to select)', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column ui toggle checkbox">
                            <input type="checkbox" value="1" name="mainwp_wp_rocket_settings[minify_concatenate_js]" <?php checked( $minify_concatenate_js , 1 ); ?> id="minify_concatenate_js">
                            <label><em><?php echo __( "Combine Javascript files combines your site's JS info fewer files, reducing HTTP requests. Not recommended if your site uses HTTP/2.", 'mainwp-rocket-extension' ); ?></em></label>
                        </div>
                        <label class="six wide column middle aligned mainwp-child2-field " <?php echo $minify_concatenate_js ? "" : 'style="display:none"'; ?>><?php echo __( 'Excluded Inline JavaScript', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column mainwp-child2-field" <?php echo $minify_concatenate_js ? "" : 'style="display:none"'; ?>>
                            <textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[exclude_inline_js]" id="exclude_inline_js"><?php echo mainwp_rocket_field_value( 'exclude_inline_js', 'textarea' ); ?></textarea><br />
                            <label><em><?php echo __( 'Specify patterns of inline JavaScript to be excluded from concatenation (one per line).', 'mainwp-rocket-extension' ); ?></em></label>
                        </div>
						<label class="six wide column middle aligned"><?php echo __( 'Excluded JavaScript Files', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column">
                            <textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[exclude_js]" placeholder="/wp-content/themes/some-theme/(.*).js" id="exclude_js"><?php echo mainwp_rocket_field_value( 'exclude_js', 'textarea' ); ?></textarea><br />
							<label><em><?php echo __( 'Specify URLs of JavaScript files to be excluded from minification and concatenation.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
                    <?php
                    $defer_all_js = mainwp_get_rocket_option( 'defer_all_js', 0 );
                    ?>
					<div class="ui grid mainwp-parent-field field">
						<label class="six wide column middle aligned"><?php echo __( 'Load JavaScript deferred', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[defer_all_js]" <?php checked( $defer_all_js, 1 ); ?> id="defer_all_js">
							<label><em><?php echo __( 'Load JavaScript deferred eliminates render-blocking JS on your site and can improve load time.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid mainwp-child-field field" <?php echo $defer_all_js ? '' : 'style="display:none"'; ?>>
						<label class="six wide column middle aligned"><?php echo __( 'Safe Mode for jQuery (recommended)', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[defer_all_js_safe]" <?php checked( mainwp_get_rocket_option( 'defer_all_js_safe', 1 ), 1 ); ?> id="defer_all_js_safe">
							<label><em><?php echo __( 'Safe mode for jQuery for deferred JS ensures support for inline jQuery references from themes and plugins by loading jQuery at the top of the document as a render-blocking script. Deactivating may result in broken functionality, test thoroughly!', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui clearing divider"></div>
					<input type="submit" name="submit" id="submit" class="ui green right floated button" value="<?php esc_attr_e( 'Save Changes', 'mainwp-rocket-extension' ); ?>" />
			</div>
			<div class="mainwp-side-content">
				<?php
        if ( $is_manage_site ) {
          self::site_settings_box( $current_site_id );
        }
        ?>
				<p><?php echo __( 'Minifying CSS & JavaScript files could break things! If you notice any errors on your website after having activated this setting, just deactivate it again, and your site will be back to normal.', 'mainwp-rocket-extension' ); ?></p>
				<?php self::render_help_message(); ?>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

		<div class="ui alt segment tab" id="mainwp-rocket-media-settings" data-tab="media">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
					<h3 class="ui dividing header"><?php echo __( 'Media Settings', 'mainwp-rocket-extension' ); ?></h3>
					<div class="ui grid field">
						<label class="six wide column"><?php echo __( 'LazyLoad', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[lazyload]" <?php checked( mainwp_get_rocket_option( 'lazyload', 0 ), 1 ); ?> id="lazyload">
							<label><em><?php echo __( 'Enable for images', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
                    <?php
                    $lazyload_iframes = mainwp_get_rocket_option( 'lazyload_iframes', 0 );
                    ?>
					<div class="ui grid mainwp-parent-field field">
						<label class="six wide column"><?php echo __( '', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[lazyload_iframes]" <?php checked( $lazyload_iframes, 1 ); ?> id="lazyload_iframes">
							<label><em><?php echo __( 'Enable for iframes and videos', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid mainwp-child-field field" <?php echo $lazyload_iframes ? "" : 'style="display:none"'; ?>>
						<label class="six wide column"><?php echo __( '', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[lazyload_youtube]" <?php checked( mainwp_get_rocket_option( 'lazyload_youtube', 0 ), 1 ); ?> id="lazyload_youtube">
							<label><em><?php echo __( 'Replace YouTube iframe with preview image', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Disable Emoji', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[emoji]" <?php checked( mainwp_get_rocket_option( 'emoji', 0 ), 1 ); ?> id="emoji">
							<label><em><?php echo __( 'Disable Emoji will reduce the number of external HTTP requests.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Disable WordPress Embeds', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[embeds]" <?php checked( mainwp_get_rocket_option( 'embeds', 0 ), 1 ); ?> id="embeds">
							<label><em><?php echo __( 'Prevents others from embedding content from your site, prevents you from embedding content from other (non-whitelisted) sites, and removes JavaScript requests related to WordPress embeds.', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui clearing divider"></div>
					<input type="submit" name="submit" id="submit" class="ui green right floated button" value="<?php esc_attr_e( 'Save Changes', 'mainwp-rocket-extension' ); ?>" />

			</div>
			<div class="mainwp-side-content">
				<?php
        if ( $is_manage_site ) {
          self::site_settings_box( $current_site_id );
        }
        ?>
				<p><?php echo __( 'LazyLoad can improve actual and perceived loading time as images, iframes, and videos will be loaded only as they enter (or about to enter) the viewport and reduces the number of HTTP requests.', 'mainwp-rocket-extension' ); ?></p>
				<?php self::render_help_message(); ?>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

		<div class="ui alt segment tab" id="mainwp-rocket-preload-settings" data-tab="preload">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
					<h3 class="ui dividing header"><?php echo __( 'Preload Settings', 'mainwp-rocket-extension' ); ?></h3>
                    <?php
                    $manual_preload = mainwp_get_rocket_option( 'manual_preload', 0 );
                    ?>
                    <div class="ui grid mainwp-parent-field field">
						<label class="six wide column middle aligned"><?php echo __( 'Activate Preloading', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[manual_preload]" <?php checked( $manual_preload, 1 ); ?> id="manual_preload">
						</div>
					</div>
                    <?php
                    $sitemap_preload = mainwp_get_rocket_option( 'sitemap_preload', 0 );
                    ?>
					<div class="ui grid mainwp-child-field field mainwp-parent2-field" <?php echo $manual_preload ? '' : 'style="display:none"'; ?> >
						<label class="six wide column middle aligned"><?php echo __( 'Activate sitemap-based cache preloading', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[sitemap_preload]" <?php checked( $sitemap_preload , 1 ); ?> id="sitemap_preload">
						</div>
                        <label class="six wide column middle aligned mainwp-child2-field" <?php echo $sitemap_preload ? '' : 'style="display:none"'; ?>><?php echo __( 'Sitemaps for preloading', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column mainwp-child2-field" <?php echo $sitemap_preload ? '' : 'style="display:none"'; ?>>
                            <textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[sitemaps]" placeholder="http://example.com/sitemap.xml" id="sitemaps"><?php echo mainwp_rocket_field_value( 'sitemaps', 'textarea' ); ?></textarea><br />
                            <label><em><?php echo __( 'Specify XML sitemap(s) to be used for preloading.', 'mainwp-rocket-extension' ); ?></em></label>
                        </div>
                    </div>

					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Prefetch DNS requests', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column">
                            <textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[dns_prefetch]" placeholder="//example.com" id="dns_prefetch"><?php echo mainwp_rocket_field_value( 'dns_prefetch', 'textarea' ); ?></textarea><br />
							<label><em><?php echo __( 'URLs to prefetch Specify external hosts to be prefetched (no http:, one per line)', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui clearing divider"></div>
					<input type="submit" name="submit" id="submit" class="ui green right floated button" value="<?php esc_attr_e( 'Save Changes', 'mainwp-rocket-extension' ); ?>" />
			</div>
			<div class="mainwp-side-content">
				<?php
        if ( $is_manage_site ) {
          self::site_settings_box( $current_site_id );
        }
        ?>
				<p><?php echo __( 'Bot-based preloading should only be used on well-performing servers. Once activated, it gets triggered automatically after you add or update content on your website. You can also launch it manually from the upper toolbar menu, or from Quick Actions on the WP Rocket Dashboard. Deactivate these options in case you notice any overload on your server!', 'mainwp-rocket-extension' ); ?></p>
				<?php self::render_help_message(); ?>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

		<div class="ui alt segment tab" id="mainwp-rocket-advanced-rules-settings" data-tab="advanced-rules">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
				<h3 class="ui dividing header"><?php echo __( 'Advanced Rules Settings', 'mainwp-rocket-extension' ); ?></h3>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Never Cache URL(s)', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column">
                            <textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[cache_reject_uri]" placeholder="/members/(.*)" id="cache_reject_uri"><?php echo mainwp_rocket_field_value( 'cache_reject_uri', 'textarea' ); ?></textarea><br />
							<label><em><?php echo __( 'Specify URLs of pages or posts that should never get cached (one per line)', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Never Cache Cookies', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column">
							<textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[cache_reject_cookies]" id="cache_reject_cookies"><?php echo mainwp_rocket_field_value( 'cache_reject_cookies', 'textarea' ); ?></textarea><br />
							<label><em><?php echo __( 'Specify the IDs of cookies that, when set in the visitor’s browser, should prevent a page from getting cached (one per line)', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Never Cache User Agent(s)', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column">
                            <textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[cache_reject_ua]" placeholder="(.*)Mobile(.*)Safari(.*)" id="cache_reject_ua"><?php echo mainwp_rocket_field_value( 'cache_reject_ua', 'textarea' ); ?></textarea><br />
							<label><em><?php echo __( 'Specify user agent strings that should never see cached pages (one per line)', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Always Purge URL(s)', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column">
							<textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[cache_purge_pages]" id="cache_purge_pages"><?php echo mainwp_rocket_field_value( 'cache_purge_pages', 'textarea' ); ?></textarea><br />
							<label><em><?php echo __( 'Specify URLs you always want purged from cache whenever you update any post or page (one per line)', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Cache Query String(s)', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column">
							<textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[cache_query_strings]" id="cache_query_strings"><?php echo mainwp_rocket_field_value( 'cache_query_strings', 'textarea' ); ?></textarea><br />
							<label><em><?php echo __( 'Specify query strings for caching (one per line)', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui clearing divider"></div>
					<input type="submit" name="submit" id="submit" class="ui green right floated button" value="<?php esc_attr_e( 'Save Changes', 'mainwp-rocket-extension' ); ?>" />

			</div>
			<div class="mainwp-side-content">
				<?php
        if ( $is_manage_site ) {
          self::site_settings_box( $current_site_id );
        }
        ?>
				<h5 class="header"><?php echo __( 'Never Cache URL(s)', 'mainwp-rocket-extension' ); ?></h5>
				<p><?php echo __( 'The domain part of the URL will be stripped automatically. Use (.*) wildcards to address multiple URLs under a given path.', 'mainwp-rocket-extension' ); ?></p>
				<h5 class="header"><?php echo __( 'Always Purge URL(s)', 'mainwp-rocket-extension' ); ?></h5>
				<p><?php echo __( 'The domain part of the URL will be stripped automatically. Use (.*) wildcards to address multiple URLs under a given path.', 'mainwp-rocket-extension' ); ?></p>
				<h5 class="header"><?php echo __( 'Cache Query String(s)', 'mainwp-rocket-extension' ); ?></h5>
				<p><?php echo __( 'Cache for query strings enables you to force caching for specific GET parameters.', 'mainwp-rocket-extension' ); ?></p>
				<?php self::render_help_message(); ?>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

		<div class="ui alt segment tab" id="mainwp-rocket-database-settings" data-tab="database">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
					<h3 class="ui dividing header"><?php echo __( 'Database Settings', 'mainwp-rocket-extension' ); ?></h3>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Posts cleanup', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[database_revisions]" <?php checked( mainwp_get_rocket_option( 'database_revisions', 0 ), 1 ); ?> id="database_revisions">
                            <label><em><?php echo __( 'Revisions', 'mainwp-rocket-extension' ); ?></em></label>
                            <span id="opt-info-total_revisions" style="display: none">
                                <span class="count-info"></span> <?php echo __('revisions in your database.', 'mainwp-rocket-extension'); ?>
                            </span>
						</div>

					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( '', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[database_auto_drafts]" <?php checked( mainwp_get_rocket_option( 'database_auto_drafts', 0 ), 1 ); ?> id="database_auto_drafts">
							<label><em><?php echo __( 'Auto drafts', 'mainwp-rocket-extension' ); ?></em></label>
                             <span id="opt-info-total_auto_draft" style="display: none">
                                 <span class="count-info"></span> <?php echo __('drafts in your database.', 'mainwp-rocket-extension'); ?>
                             </span>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( '', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[database_trashed_posts]" <?php checked( mainwp_get_rocket_option( 'database_trashed_posts', 0 ), 1 ); ?>  id="database_trashed_posts">
							<label><em><?php echo __( 'Trashed posts', 'mainwp-rocket-extension' ); ?></em></label>
                             <span id="opt-info-total_trashed_posts" style="display: none">
                                 <span class="count-info"></span> <?php echo __('trashed posts in your database.', 'mainwp-rocket-extension'); ?>
                             </span>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Comments cleanup', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[database_spam_comments]" <?php checked( mainwp_get_rocket_option( 'database_spam_comments', 0 ), 1 ); ?> id="database_spam_comments">
							<label><em><?php echo __( 'Spam comments', 'mainwp-rocket-extension' ); ?></em></label>
                            <span id="opt-info-total_spam_comments" style="display: none">
                                <span class="count-info"></span> <?php echo __('spam comments in your database.', 'mainwp-rocket-extension'); ?>
                            </span>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( '', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[database_trashed_comments]" <?php checked( mainwp_get_rocket_option( 'database_trashed_comments', 0 ), 1 ); ?> id="database_trashed_comments">
							<label><em><?php echo __( 'Trashed comments', 'mainwp-rocket-extension' ); ?></em></label>
                            <span id="opt-info-total_trashed_comments" style="display: none">
                                <span class="count-info"></span> <?php echo __('trashed comments in your database.', 'mainwp-rocket-extension'); ?>
                            </span>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Transients cleanup', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[database_expired_transients]" <?php checked( mainwp_get_rocket_option( 'database_expired_transients', 0 ), 1 ); ?> id="database_expired_transients">
							<label><em><?php echo __( 'Expired transients', 'mainwp-rocket-extension' ); ?></em></label>
                            <span id="opt-info-total_expired_transients" style="display: none">
                                <span class="count-info"></span> <?php echo __('expired transients in your database.', 'mainwp-rocket-extension'); ?>
                            </span>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( '', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[database_all_transients]" <?php checked( mainwp_get_rocket_option( 'database_all_transients', 0 ), 1 ); ?> id="database_all_transients">
							<label><em><?php echo __( 'All transients', 'mainwp-rocket-extension' ); ?></em></label>
                            <span id="opt-info-total_all_transients" style="display: none">
                                <span class="count-info"></span> <?php echo __('transients in your database.', 'mainwp-rocket-extension'); ?>
                            </span>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Database cleanup', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[database_optimize_tables]" <?php checked( mainwp_get_rocket_option( 'database_optimize_tables', 0 ), 1 ); ?> id="database_optimize_tables">
							<label><em><?php echo __( 'Optimize tables', 'mainwp-rocket-extension' ); ?></em></label>
                            <span id="opt-info-total_optimize_tables" style="display: none">
                                <span class="count-info"></span> <?php echo __('tables to optimize in your database.', 'mainwp-rocket-extension'); ?>
                            </span>
						</div>
					</div>
                    <?php
                    $schedule_automatic_cleanup = mainwp_get_rocket_option( 'schedule_automatic_cleanup', 0 );
                    ?>
					<div class="ui grid mainwp-parent-field field">
						<label class="six wide column middle aligned"><?php echo __( 'Automatic cleanup', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[schedule_automatic_cleanup]" <?php checked( $schedule_automatic_cleanup, 1 ); ?> id="schedule_automatic_cleanup">
							<label><em><?php echo __( 'Schedule automatic cleanup', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
                    <?php
                    $automatic_cleanup_frequency = mainwp_get_rocket_option( 'automatic_cleanup_frequency', '' )
                    ?>
					<div class="ui grid mainwp-child-field field" <?php echo $schedule_automatic_cleanup ? "" : 'style="display:none"'; ?>>
						<label class="six wide column middle aligned"><?php echo __( 'Schedule Automatic Cleanup', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column">
							<select id="automatic_cleanup_frequency" name="mainwp_wp_rocket_settings[automatic_cleanup_frequency]" class="ui dropdown">
								<option value="daily" <?php selected( $automatic_cleanup_frequency, 'daily' ); ?>><?php echo __( 'Daily', 'mainwp-rocket-extension' ); ?></option>
								<option value="weekly" <?php selected( $automatic_cleanup_frequency, 'weekly' ); ?>><?php echo __( 'Weekly', 'mainwp-rocket-extension' ); ?></option>
								<option value="monthly" <?php selected( $automatic_cleanup_frequency, 'monthly' ); ?>><?php echo __( 'Monthly', 'mainwp-rocket-extension' ); ?></option>
							</select>
							<label><em><?php echo __( 'Frequency', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Run cleanup', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column">
							<input type="submit" name="mainwp_wp_rocket_settings[submit_optimize]" id="rocket_submit_optimize" class="ui green button" value="<?php esc_attr_e( 'Save and Optimize', 'mainwp-rocket-extension' ); ?>">
                            <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=mainwp_rocket_optimize_database' ), 'mainwp_rocket_nonce_optimize_database' ); ?>" class="ui button"><?php _e( 'Optimize', 'mainwp-rocket-extension' ); ?></a>
                            <?php if ($current_site_id) { ?>
                            <a href="javascript:void(0)" id="mainwp-rocket-load-optimize-db-info" class="ui button"><?php _e( 'Reload optimize info', 'mainwp-rocket-extension' ); ?></a>
                            <?php } ?>
                            <span class="status"></span>
						</div>
					</div>
					<div class="ui clearing divider"></div>
					<input type="submit" name="submit" id="submit" class="ui green right floated button" value="<?php esc_attr_e( 'Save Changes', 'mainwp-rocket-extension' ); ?>" />

			</div>
			<div class="mainwp-side-content">
				<?php
        if ( $is_manage_site ) {
          self::site_settings_box( $current_site_id );
        }
        ?>
				<h5 class="header"><?php echo __( 'Posts cleanup', 'mainwp-rocket-extension' ); ?></h5>
				<p><?php echo __( 'Post revisions and drafts will be permanently deleted. Do not use this option if you need to retain revisions or drafts.', 'mainwp-rocket-extension' ); ?></p>
				<h5 class="header"><?php echo __( 'Comments cleanup', 'mainwp-rocket-extension' ); ?></h5>
				<p><?php echo __( 'Spam and trashed comments will be permanently deleted.', 'mainwp-rocket-extension' ); ?></p>
				<h5 class="header"><?php echo __( 'Transients cleanup', 'mainwp-rocket-extension' ); ?></h5>
				<p><?php echo __( 'Transients are temporary options; they are safe to remove. They will be automatically regenerated as your plugins require them.', 'mainwp-rocket-extension' ); ?></p>
				<h5 class="header"><?php echo __( 'Database cleanup', 'mainwp-rocket-extension' ); ?></h5>
				<p><?php echo __( 'Reduces overhead of database tables', 'mainwp-rocket-extension' ); ?></p>
				<?php self::render_help_message(); ?>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

		<div class="ui alt segment tab" id="mainwp-rocket-cdn-settings" data-tab="cdn">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
					<h3 class="ui dividing header"><?php echo __( 'CDN Settings', 'mainwp-rocket-extension' ); ?></h3>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'CDN', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[cdn]" <?php checked( mainwp_get_rocket_option( 'cdn', 0 ), 1 ); ?> id="cdn-chk">
							<label><em><?php echo __( 'Enable Content Delivery Network', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
                    <?php
                    $cnames = mainwp_get_rocket_option( 'cdn_cnames' );
                    $cnames_zone = mainwp_get_rocket_option( 'cdn_zone' );
                    ?>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'CDN CNAME', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column">
                            <?php
                            if( $cnames ) {
                                foreach( $cnames as $k => $_url ) {
                                ?>
                                <div class="cdn-cnames-field">
                                <input type="text" placeholder="http://" id="cdn_cnames" name="mainwp_wp_rocket_settings[cdn_cnames][<?php echo $k; ?>]" value="<?php echo esc_attr( $_url ); ?>" /> <?php echo __( ' reserved for ', 'mainwp-rocket-extension' ); ?>
                                <select class="ui dropdown" name="mainwp_wp_rocket_settings[cdn_zone][]" id="cdn_zone">
                                    <option value="all" <?php selected( $cnames_zone[$k], 'all' ); ?>><?php echo __( 'All files', 'mainwp-rocket-extension' ); ?></option>
                                    <option value="images" <?php selected( $cnames_zone[$k], 'images' ); ?>><?php echo __( 'Images', 'mainwp-rocket-extension' ); ?></option>
                                    <option value="css_and_js" <?php selected( $cnames_zone[$k], 'css_and_js' ); ?>><?php echo __( 'CSS & JavaScript', 'mainwp-rocket-extension' ); ?></option>
                                    <option value="js" <?php selected( $cnames_zone[$k], 'js' ); ?>><?php echo __( 'JavaScript', 'mainwp-rocket-extension' ); ?></option>
                                    <option value="css" <?php selected( $cnames_zone[$k], 'css' ); ?>><?php echo __( 'CSS', 'mainwp-rocket-extension' ); ?></option>
                                </select>
                                <a href="javascript:void(0)" id="mainwp-rocket-cname-remove" class="ui button basic red">Remove</a>
                                </div>
                                <?php
                                }
                            }
                            ob_start();
                            ?>
                            <input type="text" placeholder="cdn.example.com" id="cdn_cnames" name="mainwp_wp_rocket_settings[cdn_cnames][]" value="" /> <?php echo __( ' reserved for ', 'mainwp-rocket-extension' ); ?>
                            <select class="ui dropdown" name="mainwp_wp_rocket_settings[cdn_zone][]" id="cdn_zone">
                                <option value="all"><?php echo __( 'All files', 'mainwp-rocket-extension' ); ?></option>
                                <option value="images"><?php echo __( 'Images', 'mainwp-rocket-extension' ); ?></option>
                                <option value="css_and_js"><?php echo __( 'CSS & JavaScript', 'mainwp-rocket-extension' ); ?></option>
                                <option value="js"><?php echo __( 'JavaScript', 'mainwp-rocket-extension' ); ?></option>
                                <option value="css"><?php echo __( 'CSS', 'mainwp-rocket-extension' ); ?></option>
                            </select>
                            <?php
                            $field_creator = ob_get_clean();
                            if (empty($cnames)) {
                                ?>
                                <div class="cdn-cnames-field">
                                <?php
                                echo $field_creator;
                                ?>
                                </div>
                                <?php
                            }
                            ?>
                            <a href="#" id="mainwp-rocket-cname-add" class="ui button basic green" ><?php _e( 'Add CNAME', 'mainwp' ); ?></a>
                            <div id="cdn-cnames-field-creator" style="display: none">
                                <div class="cdn-cnames-field">
                                <?php echo $field_creator; ?>
                                <a href="javascript:void(0)" id="mainwp-rocket-cname-remove" class="ui button basic red">Remove</a>
                                </div>
                            </div>
                        </div>
					</div>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Exclude files from CDN', 'mainwp-rocket-extension' ); ?></label>
						<div class="ten wide column">
                            <textarea rows="8" cols="48" name="mainwp_wp_rocket_settings[cdn_reject_files]" placeholder="/wp-content/plugins/some-plugins/(.*).css" id="cdn_reject_files"><?php echo mainwp_rocket_field_value( 'cdn_reject_files', 'textarea' ); ?></textarea><br />
							<label><em><?php echo __( 'Specify URL(s) of files that should not get served via CDN. The domain part of the URL will be stripped automatically. Use (.*) wildcards to exclude all files of a given file type located at a specific path. ', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui clearing divider"></div>
					<input type="submit" name="submit" id="submit" class="ui green right floated button" value="<?php esc_attr_e( 'Save Changes', 'mainwp-rocket-extension' ); ?>" />

			</div>
			<div class="mainwp-side-content">
				<?php
        if ( $is_manage_site ) {
          self::site_settings_box( $current_site_id );
        }
        ?>
				<p><?php echo __( 'All URLs of static files (CSS, JS, images) will be rewritten to the CNAME(s) entered below.', 'mainwp-rocket-extension' ); ?></p>
				<?php self::render_help_message(); ?>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

        <div class="ui alt segment tab" id="mainwp-rocket-heartbeat-settngs" data-tab="heartbeat">
                <div class="mainwp-main-content">
									<div class="ui hidden divider"></div>
					<h3 class="ui dividing header"><?php echo __( 'Heartbeat Settings', 'mainwp-rocket-extension' ); ?></h3>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Control Heartbeat', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[control_heartbeat]" <?php checked( mainwp_get_rocket_option( 'control_heartbeat', 0 ), 1 ); ?> id="control_heartbeat">
						</div>
					</div>
                    <?php
                    $heartbeat_admin_behavior = mainwp_get_rocket_option( 'heartbeat_admin_behavior', 'reduce_periodicity' );
                    $heartbeat_editor_behavior = mainwp_get_rocket_option( 'heartbeat_editor_behavior', 'reduce_periodicity' );
                    $heartbeat_site_behavior = mainwp_get_rocket_option( 'heartbeat_site_behavior', 'reduce_periodicity' );
                    $empty = " "; // space to fix layout
                    ?>
                    <div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Reduce or disable Heartbeat activity', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column ui">
							 <select id="automatic_cleanup_frequency" name="mainwp_wp_rocket_settings[heartbeat_admin_behavior]" class="ui dropdown">
								<option value="<?php echo $empty; ?>" <?php selected( $heartbeat_admin_behavior, '' ); ?>><?php echo __( 'Do not limit', 'mainwp-rocket-extension' ); ?></option>
								<option value="reduce_periodicity" <?php selected( $heartbeat_admin_behavior, 'reduce_periodicity' ); ?>><?php echo __( 'Reduce activity', 'mainwp-rocket-extension' ); ?></option>
								<option value="disable" <?php selected( $heartbeat_admin_behavior, 'disable' ); ?>><?php echo __( 'Disable', 'mainwp-rocket-extension' ); ?></option>
							</select>
							<label><em><?php echo __( 'Behavior in backend', 'mainwp-rocket-extension' ); ?></em></label>
                        </div>
                    </div>
                    <div class="ui grid field">
                        <label class="six wide column middle aligned"></label>
                        <div class="ten wide column ui">
                            <select id="automatic_cleanup_frequency" name="mainwp_wp_rocket_settings[heartbeat_editor_behavior]" class="ui dropdown">
								<option value="<?php echo $empty; ?>" <?php selected( $heartbeat_editor_behavior, '' ); ?>><?php echo __( 'Do not limit', 'mainwp-rocket-extension' ); ?></option>
								<option value="reduce_periodicity" <?php selected( $heartbeat_editor_behavior, 'reduce_periodicity' ); ?>><?php echo __( 'Reduce activity', 'mainwp-rocket-extension' ); ?></option>
								<option value="disable" <?php selected( $heartbeat_editor_behavior, 'disable' ); ?>><?php echo __( 'Disable', 'mainwp-rocket-extension' ); ?></option>
							</select>
							<label><em><?php echo __( 'Behavior in post editor', 'mainwp-rocket-extension' ); ?></em></label>
                        </div>
                    </div>
                    <div class="ui grid field">
                        <label class="six wide column middle aligned"></label>
                        <div class="ten wide column ui">
                            <select id="automatic_cleanup_frequency" name="mainwp_wp_rocket_settings[heartbeat_site_behavior]" class="ui dropdown">
								<option value="<?php echo $empty; ?>" <?php selected( $heartbeat_site_behavior, '' ); ?>><?php echo __( 'Do not limit', 'mainwp-rocket-extension' ); ?></option>
								<option value="reduce_periodicity" <?php selected( $heartbeat_site_behavior, 'reduce_periodicity' ); ?>><?php echo __( 'Reduce activity', 'mainwp-rocket-extension' ); ?></option>
								<option value="disable" <?php selected( $heartbeat_site_behavior, 'disable' ); ?>><?php echo __( 'Disable', 'mainwp-rocket-extension' ); ?></option>
							</select>
							<label><em><?php echo __( 'Behavior in frontend', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
                    </div>
                <div class="ui clearing divider"></div>
                <input type="submit" name="submit" id="submit" class="ui green right floated button" value="<?php esc_attr_e( 'Save Changes', 'mainwp-rocket-extension' ); ?>" />
			</div>
			<div class="mainwp-side-content">
				<?php
        if ( $is_manage_site ) {
          self::site_settings_box( $current_site_id );
        }
        ?>
                <p><?php echo __('Control Heartbeat: Reducing or disabling the Heartbeat API’s activity can help save some of your server’s resources.', 'mainwp-rocket-extension');?></p>
                <p><?php echo __('Reduce or disable Heartbeat activity: Reducing activity will change Heartbeat frequency from one hit each minute to one hit every 2 minutes.
Disabling Heartbeat entirely may break plugins and themes using this API. ', 'mainwp-rocket-extension');?></p>
				<?php self::render_help_message(); ?>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

		<div class="ui alt segment tab" id="mainwp-rocket-varnish-settngs" data-tab="varnish">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
					<h3 class="ui dividing header"><?php echo __( 'Varnish Settings', 'mainwp-rocket-extension' ); ?></h3>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Sync Varnish cache', 'mainwp-rocket-extension' ); ?></label>
					  <div class="ten wide column ui toggle checkbox">
							<input type="checkbox" value="1" name="mainwp_wp_rocket_settings[varnish_auto_purge]" <?php checked( mainwp_get_rocket_option( 'varnish_auto_purge', 0 ), 1 ); ?> id="varnish_auto_purge">
							<label><em><?php echo __( 'Purge Varnish cache automatically', 'mainwp-rocket-extension' ); ?></em></label>
						</div>
					</div>
					<div class="ui clearing divider"></div>
					<input type="submit" name="submit" id="submit" class="ui green right floated button" value="<?php esc_attr_e( 'Save Changes', 'mainwp-rocket-extension' ); ?>" />
			</div>
			<div class="mainwp-side-content">
				<?php
        if ( $is_manage_site ) {
          self::site_settings_box( $current_site_id );
        }
        ?>
				<p><?php echo __( 'Varnish cache will be purged each time WP Rocket clears its cache to ensure content is always up to date.', 'mainwp-rocket-extension' ); ?></p>
				<?php self::render_help_message(); ?>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

        <div class="ui alt segment tab" id="mainwp-rocket-tools-settngs" data-tab="tools">
			<div class="mainwp-main-content">
				<div class="ui hidden divider"></div>
					<h3 class="ui dividing header"><?php echo __( 'Tools', 'mainwp-rocket-extension' ); ?></h3>
                    <?php
                     if ($current_site_id) {
                        $_extra = "&id=" .$current_site_id;
                    } else {
                        $_extra = "";
                    }
                    $download_url = wp_nonce_url( admin_url( 'admin-post.php?action=mainwp_rocket_export' . $_extra), 'mainwp_rocket_export' );
                    $bytes = apply_filters( 'import_upload_size_limit', wp_max_upload_size() ); // Filter from WP Core
                    $size = size_format( $bytes );
                    $desc = __( 'Choose a file from your computer', 'mainwp-rocket-extension' ) . ' (' . sprintf( __( 'maximum size: %s', 'mainwp-rocket-extension' ), $size ) . ')';
                    ?>
					<div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Export settings', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column ui">
                            <a href="<?php echo $download_url; ?>" id="export" class="ui button"><?php echo __("Download settings"); ?></a>
						</div>
					</div>
                    <div class="ui grid field">
						<label class="six wide column middle aligned"><?php echo __( 'Import settings', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ten wide column ui">
                            <input type="file" accept=".txt,.json" id="upload" name="import" size="25" />
                            <input type="hidden" name="max_file_size" value="<?php echo $bytes; ?>" />
                            <label><em><?php echo $desc; ?></em></label>
                            <div class="ui hidden divider"></div>
                            <input type="submit" name="import" id="import" class="ui button" value="<?php echo __('Upload file and import settings'); ?>">
						</div>
					</div>
					<div class="ui clearing divider"></div>

			</div>
			<div class="mainwp-side-content">
				<?php
        if ( $is_manage_site ) {
          self::site_settings_box( $current_site_id );
        }
        ?>
				<p><?php echo __( 'Download a backup file of your settings.', 'mainwp-rocket-extension' ); ?></p>
				<?php self::render_help_message(); ?>
			</div>
			<div class="ui clearing hidden divider"></div>
		</div>

        </form>

		<script type="text/javascript">
			jQuery( '#mainwp-rocket-menu a.item' ).tab();
            {function s() {
                   var e = this;
                   if (this.$links = document.querySelectorAll("#mainwp-rocket-menu a"), this.$menuItem = null, this.pageId = null, window.onhashchange = function() {
                           e.detectID()
                       }, window.location.hash) this.detectID();
                   else {
                       var i = localStorage.getItem("mainwp-rocket-hash");
                       i ? (window.location.hash = i, this.detectID()) : (localStorage.setItem("mainwp-rocket-hash", "dashboard"), window.location.hash = "#dashboard")
                   }
                   for (var s = 0; s < this.$links.length; s++) this.$links[s].onclick = function() {
                       var t = this.href.split("#")[1];
                       if (null != t) window.location.hash = t
                   };
                   if (this.$menuItem)
                        this.$menuItem.click(); // semantic will show/hide tabs

                    // clicking on other links with clear saved status
                    var r = document.querySelectorAll("#mainwp-page-navigation-wrapper a, #mainwp-main-menu a, #mainwp-sync-sites");
                    for (s = 0; s < r.length; s++) r[s].onclick = function() {
                        localStorage.setItem("mainwp-rocket-hash", "");
                    }
               }
               document.addEventListener("DOMContentLoaded", function() {
                   var t = document.querySelector("#mainwp-rocket-menu");
                   t && new s()
               }),
               s.prototype.detectID = function() {
                   this.pageId = window.location.hash.split("#")[1], localStorage.setItem("mainwp-rocket-hash", this.pageId), this.$menuItem = document.querySelector('a[data-tab="' + this.pageId  +'"]')
               }
           };
		</script>

		<?php if ( $is_manage_site ) : ?>
			<?php if ( ! empty( $action ) ) :

                if ( $action == 'save_opts_child_sites' && isset($_GET['optimize_db']) && $_GET['optimize_db'] ) {
                ?>
                <input type="hidden" name="rocket_do_optimize_db" value="1" />
                <?php
                }
                ?>

				<script type="text/javascript">
                    mainwp_rocket_individual_perform_action( <?php echo $current_site_id; ?>, '<?php echo esc_js($action); ?>' );
                </script>
			<?php endif; ?>
			<?php do_action( 'mainwp-pagefooter-sites', 'WPRocket' ); ?>
		<?php endif; ?>
    <?php
	}

    public static function render_help_message() {
        ?>
        <p class="ui info message"><?php echo sprintf( __( 'If you are having issues with the WP Rocket plugin, help documentation can be %sfound here%s.', 'mainwp-rocket-extension' ), '<a href="http://docs.wp-rocket.me/" target="_blank">', '</a>' ); ?></p>
        <a class="ui green big fluid button" target="_blank" href="https://mainwp.com/help/docs/rocket-extension/"><?php echo __( 'Extension Documentation', 'mainwp-rocket-extension' ); ?></a>
        <?php
    }

	public static function site_settings_box( $site_id ) {
		$override = 0;
		if ( $site_id ) {
			$site_wprocket = MainWP_Rocket_DB::get_instance()->get_wprocket_by( 'site_id' , $site_id, 'override' );
			if ( $site_wprocket ) {
				$override = $site_wprocket->override;
			}
		}
		?>
            <div class="ui secondary segment" id="mainwp-rocket-site-settings">
                <h3 class="ui dividing header"><?php echo __( 'WP Rocket Site Settings', 'mainwp-rocket-extension' ); ?></h3>
                <div class="ui form">
                    <div class="ui field">
                        <label><?php echo __( 'Override General Settings', 'mainwp-rocket-extension' ); ?></label>
                        <div class="ui toggle checkbox">
                            <input type="checkbox" value="1" name="mainwp_rocket_override_general_settings" <?php checked( $override, 1 ); ?> id="mainwp_rocket_override_general_settings">
                        </div>
                    </div>
                    <input class="ui fluid button rocket_individual_settings_save_btn"  type="button" value="<?php echo __( 'Save Option', 'mainwp-rocket-extension' ); ?>" />
                    <div class="ui mini message status" style="display:none"></div>
                </div>
                </div>
        <?php
		if ( get_option( 'mainwp_rocket_perform_individual_settings_update' ) == 1 ) {
			delete_option( 'mainwp_rocket_perform_individual_settings_update' );
			?>
      <script type="text/javascript">mainwp_rocket_individual_save_settings( <?php echo $site_id; ?> );</script>
            <?php
		}
	}

	public static function is_manage_sites_page( $tabs = array() ) {
		if ( isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) && isset( $_POST['wprocketRequestSiteID'] ) && ! empty( $_POST['wprocketRequestSiteID'] ) ) {
			return true;
		} else if ( isset( $_GET['page'] ) && ('ManageSitesWPRocket' == $_GET['page']) ) {
			return true;
		} else if ( isset( $_REQUEST['mainwp_rocket_current_site_id'] ) && ! empty( $_REQUEST['mainwp_rocket_current_site_id'] ) ) {
			return true;
		}
		return false;
	}

	public static function get_manage_site_id() {
		$site_id = 0;
		if ( self::is_manage_sites_page() ) {
			if ( isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) && isset( $_REQUEST['wprocketRequestSiteID'] ) && ! empty( $_REQUEST['wprocketRequestSiteID'] ) ) {
				$site_id = $_REQUEST['wprocketRequestSiteID'];
			} else if ( isset( $_GET['id'] ) && ! empty( $_GET['id'] ) ) {
				$site_id = intval($_GET['id']);
			} else if ( isset( $_REQUEST['mainwp_rocket_current_site_id'] ) && ! empty( $_REQUEST['mainwp_rocket_current_site_id'] ) ) {
				$site_id = $_REQUEST['mainwp_rocket_current_site_id'];
			}
		}
		return $site_id;
	}


	public static function ajax_general_load_sites( $action = null ) {
		global $mainWPRocketExtensionActivator;

		if ( isset( $_POST['rightnow_action'] ) ) {
			$action = $_POST['rightnow_action'];
		}

        // get sites with the wp-rocket plugin installed only
        $others = array(
            'plugins_slug' => 'wp-rocket/wp-rocket.php'
        );
        $websites = apply_filters( 'mainwp-getsites', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), null, false, $others );
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

		$dbwebsites = apply_filters( 'mainwp-getdbsites', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $sites_ids, array(), $option );
		$dbwebsites_wprocket = MainWP_Rocket_Plugin::get_instance()->get_websites_with_the_plugin( $dbwebsites );

		unset( $dbwebsites );

		$have_active = false;

		if ( is_array( $dbwebsites_wprocket ) &&  count( $dbwebsites_wprocket ) > 0 ) {
			echo '<div class="ui relaxed divided list">';
			foreach ( $dbwebsites_wprocket as $website ) {
				if ( ! isset( $website['wprocket_active'] ) || empty( $website['wprocket_active'] ) ) {
					continue;
				}
				$have_active = true;
				echo '<div class="item processing-item" site-id="' . $website['id'] . '" status="queue">';
				echo stripslashes( $website['name'] );
				echo '<span class="status right floated"><i class="clock outline icon"></i></span>';
				echo '</div>';
			}
			echo '</div>';
		}

		if ( ! $have_active ) {
			echo '<div class="ui yellow message">' . __( 'WP Rocket plugin not detected on the child sites. Please, install and activate the plugin first.', 'mainwp-rocket-extension' ) . '</div>';
		} else {
                if ( ! isset( $_POST['rightnow_action'] ) ) {
                    ?>
                    <script type="text/javascript">
                      jQuery( document ).ready( function ($) {
                        rocket_bulkTotalThreads = jQuery( '.processing-item[status=queue]' ).length;
                        if ( rocket_bulkTotalThreads > 0 ) {
                            <?php if ( ! empty( $action ) ) { ?>
                                mainwp_rocket_perform_action_start_next( '<?php echo esc_js( $action ); ?>' );
                            <?php } ?>
                        }
                      } );
                    </script>
                <?php
                }
		}

		if ( isset( $_POST['_wprocketNonce'] ) ) {
			die();
		}
	}

	function ajax_check_data() {
		if ( ! isset( $_REQUEST['_wprocketNonce'] ) || ! wp_verify_nonce( $_REQUEST['_wprocketNonce'], 'mainwp_rocket_nonce' ) ) {
			die( json_encode( array( 'error' => __( 'Nonce authentication failed. Please, try again.', 'mainwp-rocket-extension' ) ) ) );
		}
		if ( empty( $_POST['wprocketRequestSiteID'] ) ) {
			die( json_encode( array( 'error' => __( 'Site ID not found. Please, try again.', 'mainwp-rocket-extension' ) ) ) );
		}
	}

	function ajax_check_individual_setting( $pSiteId ) {
		$site_wprocket = MainWP_Rocket_DB::get_instance()->get_wprocket_by( 'site_id' , $pSiteId, 'override, is_active' );
		if ( isset( $_POST['individual'] ) ) {
			$individual = ! empty( $_POST['individual'] ) ? true : false;
			if ( $individual ) {
				if ( $site_wprocket && ! $site_wprocket->override ) {
					die( json_encode( array( 'error' => __( 'Action aborted. General settings are aplied. To proceed, set the Override general settings option to Yes.', 'mainwp-rocket-extension' ) ) ) );
				}
			} else {
				if ( $site_wprocket && $site_wprocket->override ) {
					die( json_encode( array( 'error' => __( 'Action aborted. General settings are overwritten. To proceed, set the Override general settings option to No.', 'mainwp-rocket-extension' ) ) ) );
				}
			}
		}
	}


	function ajax_override_settings() {
		$this->ajax_check_data();
		$websiteId = $_POST['wprocketRequestSiteID'];
		global $mainWPRocketExtensionActivator;
		$website = apply_filters( 'mainwp-getsites', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $websiteId );

		if ( $website && is_array( $website ) ) {
			$website = current( $website );
		}

		if ( ! $website ) {
			die( json_encode( array( 'error' => __( 'Site data not found.', 'mainwp-rocket-extension' ) ) ) );
		}

		$update = array(
			'site_id' => $website['id'],
			'override' => $_POST['override'],
		);

		MainWP_Rocket_DB::get_instance()->update_wprocket( $update );

		die( json_encode( array( 'result' => 'SUCCESS' ) ) );
	}

	function ajax_reload_optimize_info() {
		$this->ajax_check_data();
		$websiteId = $_POST['wprocketRequestSiteID'];
		global $mainWPRocketExtensionActivator;
		$post_data = array( 'mwp_action' => 'get_optimize_info' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $websiteId, 'wp_rocket', $post_data );
		die( json_encode( $information ) );
	}

	function ajax_purge_cloudflare() {
		$this->ajax_check_data();
		$websiteId = $_POST['wprocketRequestSiteID'];
		$this->ajax_check_individual_setting( $websiteId );

		global $mainWPRocketExtensionActivator;
		$post_data = array( 'mwp_action' => 'purge_cloudflare' );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $websiteId, 'wp_rocket', $post_data );
		die( json_encode( $information ) );
	}

  function ajax_purge_opcache() {

        $this->ajax_check_data();

		$websiteId = $_POST['wprocketRequestSiteID'];

		$individual = ! empty( $_POST['individual'] ) ? true : false;
        if (!$individual)
            $this->ajax_check_individual_setting( $websiteId );

		global $mainWPRocketExtensionActivator;

		$post_data = array( 'mwp_action' => 'purge_opcache' );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $websiteId, 'wp_rocket', $post_data );

		die( json_encode( $information ) );
	}

	function ajax_purge_cache_all() {
		$this->ajax_check_data();
		$websiteId = $_POST['wprocketRequestSiteID'];

        $individual = ! empty( $_POST['individual'] ) ? true : false;
        if (!$individual)
            $this->ajax_check_individual_setting( $websiteId );

		global $mainWPRocketExtensionActivator;
		$post_data = array( 'mwp_action' => 'purge_all' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $websiteId, 'wp_rocket', $post_data );
		if ( isset( $_POST['where'] ) && ('dashboard_tab' == $_POST['where']) ) {
			if ( is_array( $information ) && isset( $information['result'] ) && ('SUCCESS' == $information['result']) ) {
				$update = array( 'site_id' => $websiteId );
				$wprocket_data = MainWP_Rocket_DB::get_instance()->get_wprocket_by( 'site_id' , $websiteId, 'others' );
				$others = unserialize( base64_decode( $wprocket_data->others ) );
				if ( ! is_array( $others ) ) {
					$others = array(); }
				if ( isset( $data['rocket_boxes'] ) ) {
					if ( ! is_array( $data['rocket_boxes'] ) ) {
						$data['rocket_boxes'] = array(); }
					$others['rocket_boxes'][] = 'rocket_warning_plugin_modification';
				} else {
					$others['rocket_boxes'] = array( 'rocket_warning_plugin_modification' );
				}
				$update['others'] = base64_encode( serialize( $others ) );
				MainWP_Rocket_DB::get_instance()->update_wprocket( $update );

			}
		}
		die( json_encode( $information ) );
	}

	function ajax_preload_cache() {
		$this->ajax_check_data();
		$websiteId = $_POST['wprocketRequestSiteID'];

        $individual = ! empty( $_POST['individual'] ) ? true : false;
        if (!$individual)
            $this->ajax_check_individual_setting( $websiteId );

		global $mainWPRocketExtensionActivator;

		$post_data = array( 'mwp_action' => 'preload_cache' );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $websiteId, 'wp_rocket', $post_data );

		die( json_encode( $information ) );
	}


    function ajax_generate_critical_css() {
		$this->ajax_check_data();
		$websiteId = $_POST['wprocketRequestSiteID'];

        $individual = ! empty( $_POST['individual'] ) ? true : false;
        if (!$individual)
            $this->ajax_check_individual_setting( $websiteId );

		global $mainWPRocketExtensionActivator;
		$post_data = array( 'mwp_action' => 'generate_critical_css' );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $websiteId, 'wp_rocket', $post_data );
		die( json_encode( $information ) );
	}


	private function check_override_settings( $override ) {
		if ( 1 == $override ) {
			die( json_encode( array( 'error' => __( 'Action aborted. General settings are overwritten. To proceed, set the Override general settings option to No.', 'mainwp-rocket-extension' ) ) ) );
		}
	}

	function ajax_save_opts_to_child_site() {
		$this->ajax_check_data();
		$websiteId = $_POST['wprocketRequestSiteID'];
		//$this->ajax_check_individual_setting($websiteId);
		$settings = array();
		$settings_site = MainWP_Rocket_DB::get_instance()->get_wprocket_by( 'site_id' , $websiteId );
		$individual_update = isset( $_POST['individual'] ) && ! empty( $_POST['individual'] ) ? true : false;
		$general = false;
		if ( $individual_update ) {
			if ( $settings_site ) {
				if ( $settings_site->override ) {
					$settings = unserialize( base64_decode( $settings_site->settings ) );
				} else {
					die( json_encode( array( 'error' => __( 'Action aborted. General settings are aplied. To proceed, set the Override general settings option to Yes.', 'mainwp-rocket-extension' ) ) ) );
				}
			}
		} else {
			if ( $settings_site ) {
				$this->check_override_settings( $settings_site->override );
			}
			$settings = get_option( MAINWP_ROCKET_GENERAL_SETTINGS );
			$general = true;
		}

		if ( ! is_array( $settings ) || empty( $settings ) ) {
			die( json_encode( array( 'error' => $general ? __( 'Invalid general settigns data.', 'mainwp-rocket-extension' ) : __( 'Invalid individual settings data.', 'mainwp-rocket-extension' ) ) ) );
		}

		$send_fields = array();
		$defaults = mainwp_get_rocket_default_options();
		foreach ( $settings as $field => $value ) {
			if ( isset( $defaults[ $field ] ) ) {
					$send_fields[ $field ] = $value;
			}
		}


        global $mainWPRocketExtensionActivator;
		$website = apply_filters( 'mainwp-getsites', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $websiteId );
		$website = current( $website );
		$send_fields = $this->sanitize_fields( $send_fields, $website['url'], $general );

		if (isset( $send_fields['sitemaps'] )) {
			$url     = $website['url'];
			$search  = array( '%url%' );
			$replace = array( $url );
			$send_fields['sitemaps'] = str_replace( $search, $replace, $send_fields['sitemaps'] );
		}

		$post_data = array(
			'mwp_action' => 'save_settings',
			'settings' => base64_encode( serialize( $send_fields ) ),
            'do_database_optimization' => isset($_POST['optimize_db']) && $_POST['optimize_db'] ? true : false
		);

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $websiteId, 'wp_rocket', $post_data );

		die( json_encode( $information ) );
	}

	function ajax_optimize_database_site() {
		$this->ajax_check_data();
		$websiteId = $_POST['wprocketRequestSiteID'];
		$this->ajax_check_individual_setting( $websiteId );

		global $mainWPRocketExtensionActivator;
		$post_data = array( 'mwp_action' => 'optimize_database' );

		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $websiteId, 'wp_rocket', $post_data );
		die( json_encode( $information ) );
	}

	function ajax_load_existing_settings() {
		$this->ajax_check_data();
		$websiteId = $_POST['wprocketRequestSiteID'];
		global $mainWPRocketExtensionActivator;
		$post_data = array( 'mwp_action' => 'load_existing_settings' );
		$information = apply_filters( 'mainwp_fetchurlauthed', $mainWPRocketExtensionActivator->get_child_file(), $mainWPRocketExtensionActivator->get_child_key(), $websiteId, 'wp_rocket', $post_data );
		if ( is_array( $information ) && isset( $information['result'] ) && 'SUCCESS' == $information['result'] ) {
			if ( isset( $information['options'] ) && is_array( $information['options'] ) ) {
				$options = $information['options'];
				$save_fields = mainwp_get_rocket_default_options();
				$save_options = array();
				foreach ( $options as $field => $value ) {
					if ( isset( $save_fields[ $field ] ) ) {
						$save_options[ $field ] = $value; }
				}
				$update = array(
					'site_id' => $websiteId,
					'settings' => base64_encode( serialize( $save_options ) ),
				);
				MainWP_Rocket_DB::get_instance()->update_wprocket( $update );
				unset( $information['options'] );
			}
		}
		die( json_encode( $information ) );
	}


	function sanitize_fields( $input, $site_url, $isGeneral ) {
		$values = array();
		foreach ( $input['minify_js_in_footer'] as $value ) {
			$pos = strpos( $value, 'http' );
			if ( 0 !== $pos ) {
				$values[] = $site_url . ltrim( $value, '/' );
			} else {
				$values[] = $value;
			}
		}

		$input['minify_js_in_footer'] = $values;
		$values = array();

		foreach ( $input['deferred_js_files'] as $value ) {
			$pos = strpos( $value, 'http' );
			if ( 0 !== $pos ) {
				$values[] = $site_url . ltrim( $value, '/' );
			} else {
				$values[] = $value;
			}
		}

		$input['deferred_js_files'] = $values;

		if ( $isGeneral && isset($input['cloudflare_domain']) && 1 == $input['cloudflare_domain'] ) {
			$input['cloudflare_domain'] = $site_url;
		}

		return $input;
	}

	function mainwp_rocket_hook_buttons() {

		$site_id = 0;

        if ( isset( $_GET['page'] ) && 'managesites' == $_GET['page'] ) {
            $site_id = isset( $_GET['dashboard'] ) ? $_GET['dashboard'] : 0;
            if ( empty( $site_id ) ) {
            return;
            }
        }
		?>

		<div class="ui hidden divider"></div>
		<div class="ui horizontal divider"><?php _e( 'WP Rocket', 'mainwp-vulnerability-checker-extension' ); ?></div>
		<div class="ui hidden divider"></div>
		<div class="ui two column stackable grid">
			<div class="column center aligned">
				<a class="ui basic fluid green button" onclick="return <?php echo ( ! $site_id ? "mainwp_rocket_rightnow_loadsites( 'purge_cache_all' );" : 'mainwp_rocket_rightnow_clearcache_individual( '. $site_id . ' );' ); ?>" href="#"><?php _e( 'Clear WP Rocket Cache', 'mainwp-rocket-extension' ); ?></a>
			</div>
			<div class="column center aligned">
				<a class="ui fluid button" onclick="return <?php echo ( ! $site_id ? "mainwp_rocket_rightnow_loadsites( 'preload_cache' );" : 'mainwp_rocket_rightnow_preloadcache_individual( '. $site_id . ' );' ); ?>" href="#"><?php _e( 'Preload WP Rocket Cache', 'mainwp-rocket-extension' ); ?></a>
			</div>
		</div>
		<div class="ui modal" id="mainwp-rocket-overview-page-clear-cache-modal">
			<div class="header"><?php _e( 'MainWP Rocket', 'mainwp-rocket-extension' ); ?></div>
			<div class="scrolling content"></div>
			<div class="actions">
				<div class="ui cancel reload button"><?php _e( 'Close', 'mainwp-rocket-extension' ); ?></div>
			</div>
		</div>
    <?php
	}

	function managesites_bulk_actions( $actions ) {
		$actions['clear_wprocket_cache'] = __( 'Clear WP Rocket Cache', 'mainwp-rocket-extension' );
		$actions['preload_wprocket_cache'] = __( 'Preload WP Rocket Cache', 'mainwp-rocket-extension' );
		return $actions;
	}

	private function get_template_path( $path ) {
		return $this->template_path . '/' . $path . '.php';
	}

	public function checkbox( $args ) {
		echo $this->generate( 'fields/checkbox', $args );
	}

	public function generate( $template, $data = array() ) {
		$template_path = $this->get_template_path( $template );

		if ( !file_exists( $template_path ) ) {
			return;
		}

		ob_start();

		include $template_path;

		return trim( ob_get_clean() );
	}
}
