<?php
/*
  UpdraftPlus Addon: autobackup:Automatic Backups
  Description: Save time and worry by automatically create backups before updating WordPress components
  Version: 1.8
  Shop: /shop/autobackup/
  Latest Change: 1.9.62
 */

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

$mainwp_updraftplus_addon_autobackup = new MainWP_UpdraftPlus_Addon_Autobackup;

class MainWP_UpdraftPlus_Addon_Autobackup {

		// Has to be synced with WP_Automatic_Updater::run()
	private $lock_name = 'auto_updater.lock';
	private $already_backed_up = array();
	private $inpage_restrict = '';

	public function __construct() {
		add_action( 'mainwp_updraft_configprint_expertoptions', array( $this, 'configprint_expertoptions' ) );
	}

	public function wpcore_description( $desc ) {
			return __( 'WordPress core (only)', 'mainwp-updraftplus-extension' );
	}

	public function configprint_expertoptions() {
		?>
		<div class="ui grid field mwp_expertmode">
			<label class="six wide column middle aligned"><?php _e( 'UpdraftPlus automatic backups', 'mainwp-updraftplus-extension' ); ?></label>
			<div class="ten wide column ui toggle checkbox">
				<?php $this->auto_backup_form( false, 'mwp_updraft_autobackup_default', '1' ); ?>
			</div>
		</div>
		<?php
	}

	public function initial_jobdata( $jobdata ) {
		if ( ! is_array( $jobdata ) ) {
					return $jobdata; }
			$jobdata[] = 'reschedule_before_upload';
			$jobdata[] = true;
			return $jobdata;
	}

	public function initial_jobdata2( $jobdata ) {
		if ( ! is_array( $jobdata ) ) {
				return $jobdata; }
			$jobdata[] = 'autobackup';
			$jobdata[] = true;
			$jobdata[] = 'label';
			$jobdata[] = __( 'Automatic backup before update', 'mainwp-updraftplus-extension' );
			return $jobdata;
	}


	public function updraftplus_dirlist_wpcore_override( $l, $whichdir ) {
			// This does not need to include everything - only code
			$possible = array( 'wp-admin', 'wp-includes', 'index.php', 'xmlrpc.php', 'wp-config.php', 'wp-activate.php', 'wp-app.php', 'wp-atom.php', 'wp-blog-header.php', 'wp-comments-post.php', 'wp-commentsrss2.php', 'wp-cron.php', 'wp-feed.php', 'wp-links-opml.php', 'wp-load.php', 'wp-login.php', 'wp-mail.php', 'wp-pass.php', 'wp-rdf.php', 'wp-register.php', 'wp-rss2.php', 'wp-rss.php', 'wp-settings.php', 'wp-signup.php', 'wp-trackback.php' );

			$wpcore_dirlist = array();
			$whichdir = trailingslashit( $whichdir );

		foreach ( $possible as $file ) {
			if ( file_exists( $whichdir . $file ) ) {
					$wpcore_dirlist[] = $whichdir . $file; }
		}

			return ( ! empty( $wpcore_dirlist )) ? $wpcore_dirlist : $l;
	}

	private function reschedule( $how_long ) {
			wp_clear_scheduled_hook( 'ud_wp_maybe_auto_update' );
		if ( ! $how_long ) {
				return; }
			global $mainwp_updraftplus;
			$mainwp_updraftplus->log( "Rescheduling WP's automatic update check for $how_long seconds ahead" );
			$lock_result = get_option( $this->lock_name );
			wp_schedule_single_event( time() + $how_long, 'ud_wp_maybe_auto_update', array( $lock_result ) );
	}

	public function admin_footer_insertintoform() {
			$def = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_autobackup_default', true );
			$godef = ($def) ? 'yes' : 'no';
			// Note - now, in the new-style widgetised setup (Feb 2015), we always set updraftplus_noautobackup=1 - because the actual backup will be done in-page. But that is not done here - it is done when the form is submitted, in updraft_try_inpage();
			echo <<<ENDHERE
		<script>
		jQuery(document).ready(function() {
			jQuery('form.upgrade').append('<input type="hidden" name="updraft_autobackup" class="updraft_autobackup_go" value="$godef">');
			jQuery('form.upgrade').append('<input type="hidden" name="updraft_autobackup_setdefault" class="updraft_autobackup_setdefault" value="yes">');
			jQuery('#updraft_autobackup').click(function() {
				var doauto = jQuery(this).attr('checked');
				if ('checked' == doauto) {
					jQuery('.updraft_autobackup_go').attr('value', 'yes');
				} else {
					jQuery('.updraft_autobackup_go').attr('value', 'no');
				}
			});
			jQuery('#updraft_autobackup_sdefault').click(function() {
				var sdef = jQuery(this).attr('checked');
				if ('checked' == sdef) {
					jQuery('.updraft_autobackup_setdefault').attr('value', 'yes');
				} else {
					jQuery('.updraft_autobackup_setdefault').attr('value', 'no');
				}
			});
		});
		</script>
ENDHERE;
	}

	public function admin_footer() {
		if ( ! current_user_can( 'update_' . $this->internaltype ) ) {
				return; }
			$creating = esc_js( sprintf( __( 'Creating %s and database backup with UpdraftPlus...', 'mainwp-updraftplus-extension' ), $this->type ) . ' ' . __( '(logs can be found in the UpdraftPlus settings page as normal)...', 'mainwp-updraftplus-extension' ) );
			$lastlog = esc_js( __( 'Last log message', 'mainwp-updraftplus-extension' ) ) . ':';
			$updraft_credentialtest_nonce = wp_create_nonce( 'updraftplus-credentialtest-nonce' );
			global $mainwp_updraftplus;
			$mainwp_updraftplus->log( __( 'Starting automatic backup...', 'mainwp-updraftplus-extension' ) );

			$unexpected_response = esc_js( __( 'Unexpected response:', 'mainwp-updraftplus-extension' ) );

			echo <<<ENDHERE
			<script>
				jQuery('h2:first').after('<p>$creating</p><p>$lastlog <span id="updraft_lastlogcontainer"></span></p><div id="updraft_activejobs"></div>');
				var lastlog_sdata = {
					action: 'updraft_ajax',
					subaction: 'activejobs_list',
					oneshot: 'yes'
				};
				setInterval(function(){updraft_autobackup_showlastlog(true);}, 3000);
				function updraft_autobackup_showlastlog(repeat){
					lastlog_sdata.nonce = '$updraft_credentialtest_nonce';
					jQuery.get(ajaxurl, lastlog_sdata, function(response) {
						try {
							resp = jQuery.parseJSON(response);
							if (resp.l != null) { jQuery('#updraft_lastlogcontainer').html(resp.l); }
							if (resp.j != null && resp.j != '') {
								jQuery('#updraft_activejobs').html(resp.j);
							} else {
								if (!jQuery('#updraft_activejobs').is(':hidden')) {
									jQuery('#updraft_activejobs').hide();
								}
							}
						} catch(err) {
							console.log('$unexpected_response '+response);
						}
					});
				}
			</script>
ENDHERE;
	}

	private function process_form() {
		//      # We use 0 instead of false, because false is the default for get_option(), and thus setting an unset value to false with update_option() actually sets nothing (since update_option() first checks for the existing value) - which is unhelpful if you want to call get_option() with a different default (as we do)
		//      $autobackup = (isset($_POST['updraft_autobackup']) && $_POST['updraft_autobackup'] == 'yes') ? 1 : 0;
		//      if (!empty($_POST['updraft_autobackup_setdefault']) && 'yes' == $_POST['updraft_autobackup_setdefault']) MainWP_Updraft_Plus_Options::update_updraft_option('updraft_autobackup_default', $autobackup);
		//
		//      # Having dealt with the saving, now see if we really wanted to do it
		//      if (!empty($_REQUEST['updraftplus_noautobackup'])) $autobackup = 0;
		//      MainWP_Updraft_Plus_Options::update_updraft_option('updraft_autobackup_go', $autobackup);
		//
		//      if ($autobackup) add_action('admin_footer', array($this, 'admin_footer'));
	}

	// This is in WP 3.9 and later as a global function (but we support earlier)
	private function doing_filter( $filter = null ) {
		//      if (function_exists('doing_filter')) return doing_filter($filter);
		//      global $wp_current_filter;
		//      if ( null === $filter ) {
		//          return ! empty( $wp_current_filter );
		//      }
		//      return in_array( $filter, $wp_current_filter );
	}


	private function autobackup_finish( $jquery = false ) {

		//      global $wpdb;
		//      if (method_exists($wpdb, 'check_connection') && !$wpdb->check_connection(false)) {
		//          $mainwp_updraftplus->log("It seems the database went away, and could not be reconnected to");
		//          die;
		//      }
		//
		//      echo "<script>var h = document.getElementById('updraftplus-autobackup-log'); h.style.display='none';</script>";
		//
		//      if ($jquery) {
		//          echo '<p>'.__('Backup succeeded', 'mainwp-updraftplus-extension').' <a href="#updraftplus-autobackup-log" onclick="jQuery(\'#updraftplus-autobackup-log\').slideToggle();">'.__('(view log...)', 'mainwp-updraftplus-extension').'</a> - '.__('now proceeding with the updates...', 'mainwp-updraftplus-extension').'</p>';
		//      } else {
		//          echo '<p>'.__('Backup succeeded', 'mainwp-updraftplus-extension').' <a href="#updraftplus-autobackup-log" onclick="var s = document.getElementById(\'updraftplus-autobackup-log\'); s.style.display = \'block\';">'.__('(view log...)', 'mainwp-updraftplus-extension').'</a> - '.__('now proceeding with the updates...', 'mainwp-updraftplus-extension').'</p>';
		//      }
	}

	public function get_setting_and_check_default_setting_save() {
			# Do not use bools here - conflicts with get_option() with a non-default value
		//      $autobackup = (isset($_REQUEST['updraft_autobackup']) && $_REQUEST['updraft_autobackup'] == 'yes') ? 1 : 0;
		//
		//      if (!empty($_REQUEST['updraft_autobackup_setdefault']) && 'yes' == $_REQUEST['updraft_autobackup_setdefault']) MainWP_Updraft_Plus_Options::update_updraft_option('updraft_autobackup_default', $autobackup);
		//
		//      return $autobackup;
	}

	public function request_filesystem_credentials( $input ) {
			echo <<<ENDHERE
<script>
	jQuery(document).ready(function(){
		jQuery('#upgrade').before('<input type="hidden" name="updraft_autobackup_answer" value="1">');
	});
</script>
ENDHERE;
			return $input;
	}

	private function auto_backup_form( $include_wrapper = true, $id = 'updraft_autobackup', $value = 'yes', $form_tags = true ) {

		if ( $include_wrapper ) {
				?>

				<?php if ( $form_tags ) { ?><h2><?php echo __( 'UpdraftPlus Automatic Backups', 'mainwp-updraftplus-extension' ); ?></h2><?php } ?>
					<?php if ( $form_tags ) { ?><form method="post" id="updraft_autobackup_form" onsubmit="return updraft_try_inpage('#updraft_autobackup_form', '');"><?php } ?>
					<div id="mwp-updraft-autobackup" <?php if ( $form_tags ) { echo 'class="updated"'; } ?> style="<?php
					if ( $form_tags ) {
							echo 'border: 1px dotted; ';
					}
						?>padding: 6px; margin:8px 0px; max-width: 540px;">
						<h3 style="margin-top: 0px;"><?php _e( 'Be safe with an automatic backup', 'mainwp-updraftplus-extension' ); ?></h3>
							<?php
		}
					?>
					<input <?php if ( MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_autobackup_default', true ) ) { echo 'checked="checked"'; } ?> type="checkbox" id="<?php echo $id; ?>" value="<?php echo $value; ?>" name="<?php echo $id; ?>">
						<?php if ( ! $include_wrapper ) { echo '<br>'; } ?>
						<label for="<?php echo $id; ?>"><?php echo __( 'Backup (where relevant) plugins, themes and the WordPress database with UpdraftPlus before updating', 'mainwp-updraftplus-extension' ); ?></label><br>
						<?php
						if ( $include_wrapper ) {
								?>
								<input checked="checked" type="checkbox" value="yes" name="updraft_autobackup_setdefault" id="updraft_autobackup_setdefault"> <label for="updraft_autobackup_setdefault"><?php _e( 'Remember this choice for next time (you will still have the chance to change it)', 'mainwp-updraftplus-extension' ); ?></label><br><em>
									<?php
						}
						?>

							<?php
							if ( $include_wrapper ) {
								?></em>
								<?php if ( $form_tags ) { ?><p><em><?php _e( 'Do not abort after pressing Proceed below - wait for the backup to complete.', 'mainwp-updraftplus-extension' ); ?></em></p><?php } ?>
								<?php if ( $form_tags ) { ?><input style="clear:left; margin-top: 6px;" name="updraft_autobackup_answer" type="submit" value="<?php _e( 'Proceed with update', 'mainwp-updraftplus-extension' ); ?>"><?php } ?>
								</div>
								<?php
								if ( $form_tags ) {
									echo '</form>'; }
							}
	}
}
