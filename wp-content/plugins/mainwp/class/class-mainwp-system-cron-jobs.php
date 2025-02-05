<?php
/**
 * MainWP System Cron Jobs.
 *
 * @package     MainWP/Dashboard
 */

namespace MainWP\Dashboard;

//phpcs:disable WordPress.WP.AlternativeFunctions -- for custom read/write file.

/**
 * Class MainWP_System_Cron_Jobs
 *
 * @package MainWP\Dashboard
 */
class MainWP_System_Cron_Jobs {

	/**
	 * Singleton.
	 *
	 * @var null $instance
	 */
	private static $instance = null;

	/**
	 * MainWP Cron Instance.
	 *
	 * @return self $instance
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * MainWP_System_Cron_Jobs constructor.
	 *
	 * Run each time the class is called.
	 */
	public function __construct() {
	}

	/**
	 * Method init_cron_jobs()
	 *
	 * Instantiate Cron Jobs.
	 */
	public function init_cron_jobs() {

		/**
		 * Action: mainwp_cronload_action
		 *
		 * Hooks MainWP cron jobs actions.
		 *
		 * @since Unknown
		 */
		do_action( 'mainwp_cronload_action' );

		add_action( 'mainwp_cronstats_action', array( $this, 'cron_stats' ) );
		add_action( 'mainwp_cronbackups_action', array( $this, 'cron_backups' ) );
		add_action( 'mainwp_cronbackups_continue_action', array( $this, 'cron_backups_continue' ) );
		add_action( 'mainwp_cronupdatescheck_action', array( $this, 'cron_updates_check' ) );
		add_action( 'mainwp_cronpingchilds_action', array( $this, 'cron_ping_childs' ) );
		add_action( 'mainwp_croncheckstatus_action', array( $this, 'cron_check_websites_status' ) );
		add_action( 'mainwp_cronsitehealthcheck_action', array( $this, 'cron_check_websites_health' ) );

		// phpcs:ignore -- required for dashboard's minutely scheduled jobs.
		add_filter( 'cron_schedules', array( $this, 'get_cron_schedules' ) );

		$this->init_cron();
	}

	/**
	 * Method init_cron()
	 *
	 * Build Cron Jobs Array & initiate via init_mainwp_cron()
	 */
	public function init_cron() {

		// Check wether or not to use MainWP Cron false|1.
		$useWPCron = ( get_option( 'mainwp_wp_cron' ) === false ) || ( get_option( 'mainwp_wp_cron' ) == 1 );

		// Default Cron Jobs.
		$jobs = array(
			'mainwp_cronstats_action'        => 'hourly',
			'mainwp_cronpingchilds_action'   => 'daily',
			'mainwp_cronupdatescheck_action' => 'minutely',
		);

		$disableChecking = get_option( 'mainwp_disableSitesChecking', 1 );
		if ( ! $disableChecking ) {
			$jobs['mainwp_croncheckstatus_action'] = 'minutely';
		} else {
			// disable check sites status cron.
			$sched = wp_next_scheduled( 'mainwp_croncheckstatus_action' );
			if ( false != $sched ) {
				wp_unschedule_event( $sched, 'mainwp_croncheckstatus_action' );
			}
		}

		$disableHealthChecking = get_option( 'mainwp_disableSitesHealthMonitoring', 1 ); // disabled by default.
		if ( ! $disableHealthChecking ) {
			$jobs['mainwp_cronsitehealthcheck_action'] = 'hourly';
		} else {
			// disable check sites health cron.
			$sched = wp_next_scheduled( 'mainwp_cronsitehealthcheck_action' );
			if ( false != $sched ) {
				wp_unschedule_event( $sched, 'mainwp_cronsitehealthcheck_action' );
			}
		}

		// Legacy Backup Cron jobs.
		if ( get_option( 'mainwp_enableLegacyBackupFeature' ) ) {
			$jobs = array_merge(
				$jobs,
				array(
					'mainwp_cronbackups_action'          => 'hourly',
					'mainwp_cronbackups_continue_action' => '5minutely',
				)
			);
		} else {
			// Unset Cron Schedules.
			$sched = wp_next_scheduled( 'mainwp_cronbackups_action' );
			if ( $sched ) {
				wp_unschedule_event( $sched, 'mainwp_cronbackups_action' );
			}
			$sched = wp_next_scheduled( 'mainwp_cronbackups_continue_action' );
			if ( $sched ) {
				wp_unschedule_event( $sched, 'mainwp_cronbackups_continue_action' );
			}
		}

		foreach ( $jobs as $hook => $recur ) {
			$this->init_mainwp_cron( $useWPCron, $hook, $recur );
		}
	}

	/**
	 * Method init_mainwp_cron()
	 *
	 * Schedual Cron Jobs.
	 *
	 * @param mixed $useWPCron Wether or not to use WP_Cron.
	 * @param mixed $cron_hook When cron is going to reoccur.
	 * @param mixed $recurrence Cron job hook.
	 */
	public function init_mainwp_cron( $useWPCron, $cron_hook, $recurrence ) {
		$sched = wp_next_scheduled( $cron_hook );
		if ( false == $sched ) {
			if ( $useWPCron ) {
				wp_schedule_event( time(), $recurrence, $cron_hook );
			}
		} else {
			if ( ! $useWPCron ) {
				wp_unschedule_event( $sched, $cron_hook );
			}
		}
	}

	/**
	 * Method cron_active()
	 *
	 * Check if WP_Cron is active.
	 *
	 * @return void
	 *
	 * @uses \MainWP\Dashboard\MainWP_System::$version
	 */
	public function cron_active() {
		if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON ) {
			return;
		}
		if ( empty( $_GET['mainwp_run'] ) || 'test' !== $_GET['mainwp_run'] ) {
			return;
		}
		session_write_close();
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ), true );
			header( 'X-Robots-Tag: noindex, nofollow', true );
			header( 'X-MainWP-Version: ' . MainWP_System::$version, true );
			nocache_headers();
		}
		wp_die( 'MainWP Test' );
	}

	/**
	 * Method get_cron_schedules()
	 *
	 * Get current Cron Schedual.
	 *
	 * @param array $schedules Array of currently set scheduals.
	 *
	 * @return array $scheduales.
	 */
	public function get_cron_schedules( $schedules ) {
		$schedules['5minutely'] = array(
			'interval' => 5 * 60,
			'display'  => __( 'Once every 5 minutes', 'mainwp' ),
		);
		$schedules['minutely']  = array(
			'interval' => 1 * 60,
			'display'  => __( 'Once every minute', 'mainwp' ),
		);

		return $schedules;
	}

	/**
	 * Method get_timestamp_from_hh_mm()
	 *
	 * Get Time Stamp from $hh_mm.
	 *
	 * @param mixed $hh_mm Global time stamp variable.
	 * @param int   $time Time of day.
	 *
	 * @return time Y-m-d 00:00:59.
	 */
	public static function get_timestamp_from_hh_mm( $hh_mm, $time = false ) {
		$hh_mm = explode( ':', $hh_mm );
		$_hour = isset( $hh_mm[0] ) ? intval( $hh_mm[0] ) : 0;
		$_mins = isset( $hh_mm[1] ) ? intval( $hh_mm[1] ) : 0;
		if ( $_hour < 0 || $_hour > 23 ) {
			$_hour = 0;
		}
		if ( $_mins < 0 || $_mins > 59 ) {
			$_mins = 0;
		}
		if ( ! empty( $time ) ) {
			$str_date = date( 'Y-m-d', $time ); // phpcs:ignore -- check update at given time.
		} else {
			$lctime   = MainWP_Utility::get_timestamp();
			$str_date = date( 'Y-m-d', $lctime ); // phpcs:ignore -- check update at local server time.
		}
		return strtotime( $str_date . ' ' . $_hour . ':' . $_mins . ':59' ); // phpcs:ignore -- check update at given time.
	}

	/**
	 * Method cron_updates_check()
	 *
	 * MainWP Cron Check Update
	 *
	 * This Cron Checks to see if Automatic Daily Updates need to be performed.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Backup_Handler::is_archive()
	 * @uses \MainWP\Dashboard\MainWP_Backup_Handler::backup()
	 * @uses \MainWP\Dashboard\MainWP_Backup_Handler::backup_download_file()
	 * @uses \MainWP\Dashboard\MainWP_Connect::fetch_url_authed()
	 * @uses \MainWP\Dashboard\MainWP_DB_Backup::backup_full_task_running()
	 * @uses \MainWP\Dashboard\MainWP_DB_Common::get_user_extension_by_user_id()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_websites_check_updates()
	 * @uses \MainWP\Dashboard\MainWP_DB::update_website_sync_values()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_websites_count_where_dts_automatic_sync_smaller_then_start()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_website_by_id()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_website_option()
	 * @uses \MainWP\Dashboard\MainWP_DB::update_website_option()
	 * @uses \MainWP\Dashboard\MainWP_Logger::info()
	 * @uses \MainWP\Dashboard\MainWP_Logger::debug()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_general_email_settings()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_site_email_settings()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_default_emails_fields()
	 * @uses \MainWP\Dashboard\MainWP_Sync::sync_site()
	 * @uses \MainWP\Dashboard\MainWP_Sync::sync_site_icon()
	 * @uses \MainWP\Dashboard\MainWP_Sync::sync_information_array()
	 * @uses \MainWP\Dashboard\MainWP_System_Utility::get_wp_file_system()
	 * @uses \MainWP\Dashboard\MainWP_System_Utility::get_mainwp_specific_dir()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::get_timestamp()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function cron_updates_check() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity -- Current complexity is the only way to achieve desired results, pull request solutions appreciated.

		ignore_user_abort( true );
		MainWP_System_Utility::set_time_limit( 0 );
		add_filter(
			'admin_memory_limit',
			function() {
				return '512M';
			}
		);

		$updatecheck_running = ( 'Y' == get_option( 'mainwp_updatescheck_is_running' ) ? true : false );
		$timeDailyUpdate     = get_option( 'mainwp_timeDailyUpdate' );

		$run_timestamp = 0;

		$local_timestamp = MainWP_Utility::get_timestamp();

		$frequencyDailyUpdate = get_option( 'mainwp_frequencyDailyUpdate' );
		if ( $frequencyDailyUpdate <= 0 ) {
			$frequencyDailyUpdate = 1;
		}

		$frequency_second = DAY_IN_SECONDS;
		if ( 1 < $frequencyDailyUpdate ) {
			$frequency_second = ( DAY_IN_SECONDS / intval( $frequencyDailyUpdate ) );
		}

		$frequence_period_in_seconds = DAY_IN_SECONDS / $frequencyDailyUpdate;

		$today_0h = strtotime( date("Y-m-d 00:00:00", $local_timestamp) ); // phpcs:ignore -- to localtime.
		$today_end   = strtotime( date("Y-m-d 23:59:59", $local_timestamp ) ) ; // phpcs:ignore -- to localtime.

		$today_m_y = date( 'd/m/Y', $local_timestamp ); //phpcs:ignore -- local time.

		$lasttimeAutomaticUpdate      = get_option( 'mainwp_updatescheck_last_timestamp' );
		$lasttimeStartAutomaticUpdate = get_option( 'mainwp_updatescheck_start_last_timestamp' );
		$mainwpLastAutomaticUpdate    = get_option( 'mainwp_updatescheck_last' );
		$lasttimeDailyDigest          = get_option( 'mainwp_updatescheck_dailydigest_last_timestamp' );
		$mainwpLastDailydigest        = get_option( 'mainwp_dailydigest_last' );

		$sendmail_for_each_auto_sync = apply_filters( 'mainwp_updatescheck_sendmail_for_each_auto_sync_finished', false );

		$check_ready_sendmail = ( $sendmail_for_each_auto_sync || $today_m_y !== $mainwpLastDailydigest ) ? true : false;

		if ( $check_ready_sendmail && 'Y' == get_option( 'mainwp_updatescheck_ready_sendmail' ) ) {
			/**
			 * Filter: mainwp_updatescheck_sendmail_at_time
			 *
			 * Filters the the time when the Daily Digest email will be sent.
			 *
			 * @since 3.4
			 */
			$send_noti_at   = apply_filters( 'mainwp_updatescheck_sendmail_at_time', false );
			$time_to_noti   = false;
			$time_notice_at = false;

			if ( ! empty( $send_noti_at ) ) {
				$send_timestamp = self::get_timestamp_from_hh_mm( $send_noti_at, $lasttimeDailyDigest );
				$time_notice_at = $local_timestamp > $send_timestamp ? true : false;
			} else {
				if ( 1 < $frequencyDailyUpdate ) {
					$next_time_to_send = $today_end - $frequency_second;
					$time_to_noti      = ( $local_timestamp > $next_time_to_send ) ? true : false;
					// to fix.
					if ( ! $time_to_noti ) {
						$send_timestamp = self::get_timestamp_from_hh_mm( '00:01', $lasttimeDailyDigest ); // to check more than 24 hours from the last send.
						$time_to_noti   = $local_timestamp > $send_timestamp ? true : false;
					}
				}
			}

			if ( 1 == $frequencyDailyUpdate || $time_notice_at || $time_to_noti ) {

				$plain_text = get_option( 'mainwp_daily_digest_plain_text', false );

				MainWP_Utility::update_option( 'mainwp_dailydigest_last', $today_m_y );

				MainWP_Utility::update_option( 'mainwp_updatescheck_dailydigest_last_timestamp', $local_timestamp );

				// send daily digest email one time per day.
				$individual_digestWebsites = get_option( 'mainwp_updatescheck_individual_digest_websites' );

				MainWP_Logger::instance()->debug( 'CRON :: updates check :: got to the mail part' );

				MainWP_Logger::instance()->log_action( 'CRON :: got to the daily digest mail part', MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );

				$gen_email_settings = MainWP_Notification_Settings::get_general_email_settings( 'daily_digest' );
				if ( empty( $gen_email_settings['disable'] ) ) {
					// send general daily digests.
					$this->start_notification_daily_digest( $gen_email_settings, $plain_text ); // general email.
				}

				$to_admin_digestWebsites = array();

				if ( is_array( $individual_digestWebsites ) && 0 < count( $individual_digestWebsites ) ) {
					// send individual site daily digests, one email for one site.
					foreach ( $individual_digestWebsites as $siteid ) {
						$website        = MainWP_DB::instance()->get_website_by_id( $siteid, false, array( 'settings_notification_emails' ) );
						$email_settings = MainWP_Notification_Settings::get_site_email_settings( 'daily_digest', $website );  // get site email settings.
						if ( ! $email_settings['disable'] ) {
							$to_admin_digestWebsites[] = $siteid;
							$sent                      = $this->start_notification_daily_digest( $email_settings, $plain_text, array( $siteid ), $website );
							if ( $sent ) {
								usleep( 100000 );
							}
						}
					}
				}

				if ( 0 < count( $to_admin_digestWebsites ) ) {
					$admin_email_settings               = MainWP_Notification_Settings::get_default_emails_fields( 'daily_digest', '', true ); // get default subject and heading only.
					$admin_email_settings['disable']    = 0;
					$admin_email_settings['recipients'] = MainWP_Notification_Settings::get_general_email(); // sent to general notification email only.
					// send all individual daily digest to admin in one email.
					$this->start_notification_daily_digest( $admin_email_settings, $plain_text, $to_admin_digestWebsites ); // will send email to general notification email.
				}
				$this->refresh_saved_fields();
			}
		}
		$run_synctime = null;
		if ( ! empty( $timeDailyUpdate ) ) {
			$run_synctime  = false;
			$run_timestamp = self::get_timestamp_from_hh_mm( $timeDailyUpdate );
			if ( $local_timestamp > $run_timestamp ) {
				$run_synctime = true; // it is time to run or worked.
			} elseif ( ( $local_timestamp - $lasttimeStartAutomaticUpdate ) > DAY_IN_SECONDS ) {
				$run_synctime = true;
			}

			if ( ! $updatecheck_running && ! $run_synctime ) {
				MainWP_Logger::instance()->info( 'CRON :: updates check :: wait sync time' );
				MainWP_Logger::instance()->log_action( 'CRON :: updates check :: wait sync time :: ' . date( 'Y-m-d H:i:s', $run_timestamp ), MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER ); //phpcs:ignore -- local time.
				// sync time is false.
				return;
			}
		}

		$frequence_today_count = get_option( 'mainwp_updatescheck_frequency_today_count' );
		$frequence_now         = ceil( ( $local_timestamp - $today_0h ) / $frequence_period_in_seconds ); // 0 <= frequence_now <= frequencyDailyUpdate, computes frequence value now.

		$run_frequency = null;
		if ( $frequencyDailyUpdate > 1 ) { // check this if frequency > 1 only.
			$run_frequency = false;
			if ( ( $local_timestamp > $today_0h + $frequence_period_in_seconds * $frequence_today_count ) || ( $local_timestamp > $lasttimeStartAutomaticUpdate + $frequence_period_in_seconds ) ) {
				$run_frequency = true;
				MainWP_Logger::instance()->log_action( 'CRON :: updates check :: running frequency now :: ' . $frequence_now . ' :: ' . date( 'Y-m-d H:i:s', $local_timestamp ), MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER ); //phpcs:ignore -- local time.
			}
			if ( ! $run_frequency ) {
				MainWP_Logger::instance()->log_action( 'CRON :: updates check :: wait frequency today :: ' . $frequence_now . ' / ' . $frequencyDailyUpdate . ' :: ' . date( 'Y-m-d H:i:s', $local_timestamp ), MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER ); //phpcs:ignore -- local time.
			}
		}

		$mainwpAutomaticDailyUpdate = get_option( 'mainwp_automaticDailyUpdate' );

		$plugin_automaticDailyUpdate = get_option( 'mainwp_pluginAutomaticDailyUpdate' );
		$theme_automaticDailyUpdate  = get_option( 'mainwp_themeAutomaticDailyUpdate' );

		/**
		 * Filter: mainwp_updatescheck_hours_interval
		 *
		 * Filters the status check interval.
		 *
		 * @since 3.4
		 */
		$mainwpHoursIntervalAutomaticUpdate = apply_filters( 'mainwp_updatescheck_hours_interval', false );

		if ( ! $updatecheck_running ) {
			$run_hours_interval = null;
			if ( $mainwpHoursIntervalAutomaticUpdate > 0 ) {
				$run_hours_interval = false;
				if ( $lasttimeAutomaticUpdate && ( $lasttimeAutomaticUpdate + $mainwpHoursIntervalAutomaticUpdate * 3600 > $local_timestamp ) ) {
					MainWP_Logger::instance()->debug( 'CRON :: updates check :: already updated hours interval' );
					MainWP_Logger::instance()->log_action( 'CRON :: updates check :: already updated hours interval', MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );
					return;
				} else {
					$run_hours_interval = true;
				}
			}

			$run_valid = false;
			if ( $run_hours_interval ) {
				$run_valid = true;
			} elseif ( ( null === $run_synctime || $run_synctime ) && $run_frequency ) { // if not set sync time, and run frequency.
				$run_valid = true;
			} elseif ( ( null === $run_synctime || $run_synctime ) && $today_m_y != $mainwpLastAutomaticUpdate ) { // if sync time or not set sync time, and other day, to fix.
				$run_valid = true;
			} elseif ( ( null === $run_frequency ) && ( null === $run_synctime || $run_synctime ) && $today_m_y == $mainwpLastAutomaticUpdate ) { // if sync time or not set sync time, and other day, to fix.
				MainWP_Logger::instance()->log_action( 'CRON :: already checked update today', MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );
				$run_valid = false;
				return;
			}

			if ( ! $run_valid ) {
				// $run_hours_interval == false|null.
				// $run_frequency == false|null.
				// $run_synctime == false|null.
				// $today_m_y == $mainwpLastAutomaticUpdate.
				MainWP_Logger::instance()->debug( 'CRON :: updates check :: waiting conditionals to run.' );
				MainWP_Logger::instance()->log_action( 'CRON :: updates check :: waiting conditionals to run.', MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );
				return;
			}
		}

		if ( ! $updatecheck_running ) {
			$lasttimeStartAutomaticUpdate = $local_timestamp;
			MainWP_Utility::update_option( 'mainwp_updatescheck_start_last_timestamp', $local_timestamp ); // start new update checking.
			$this->refresh_saved_fields();
		}

		$websites             = array();
		$checkupdate_websites = MainWP_DB::instance()->get_websites_check_updates( 4, $lasttimeStartAutomaticUpdate );

		foreach ( $checkupdate_websites as $website ) {
			if ( ! MainWP_DB_Backup::instance()->backup_full_task_running( $website->id ) ) {
				$websites[] = $website;
			}
		}

		MainWP_Logger::instance()->debug( 'CRON :: backup task running :: found ' . ( count( $checkupdate_websites ) - count( $websites ) ) . ' websites' );
		MainWP_Logger::instance()->debug( 'CRON :: updates check :: found ' . count( $checkupdate_websites ) . ' websites' );

		MainWP_Logger::instance()->log_action( 'CRON :: updates check found ' . count( $checkupdate_websites ), MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );

		$userid = null;
		foreach ( $websites as $website ) {
			$websiteValues = array(
				'dtsAutomaticSyncStart' => $local_timestamp,
			);
			if ( null === $userid ) {
				$userid = $website->userid;
			}

			MainWP_DB::instance()->update_website_sync_values( $website->id, $websiteValues );
		}

		$plain_text = get_option( 'mainwp_daily_digest_plain_text', false );

		/**
		 * Filter: mainwp_text_format_email
		 *
		 * Filters whether the email shuld bein plain text format.
		 *
		 * @since 3.5
		 */
		$filter_plain_text = apply_filters( 'mainwp_text_format_email', $plain_text );

		if ( $plain_text !== $filter_plain_text ) {
			$plain_text = $filter_plain_text;
			MainWP_Utility::update_option( 'mainwp_daily_digest_plain_text', $plain_text );
		}

		if ( 0 == count( $checkupdate_websites ) ) {
			$busyCounter = MainWP_DB::instance()->get_websites_count_where_dts_automatic_sync_smaller_then_start( $lasttimeStartAutomaticUpdate );
			if ( 0 != $busyCounter ) {
				MainWP_Logger::instance()->debug( 'CRON :: busy counter :: found ' . $busyCounter . ' websites' );
				MainWP_Logger::instance()->log_action( 'CRON :: busy counter :: found ' . $busyCounter . ' websites', MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );
				$lastAutomaticUpdate = MainWP_DB::instance()->get_websites_last_automatic_sync();
				if ( ( time() - $lastAutomaticUpdate ) < HOUR_IN_SECONDS ) {
					MainWP_Logger::instance()->log_action( 'CRON :: last automatic update :: ' . $lastAutomaticUpdate, MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );
				}
			}

			MainWP_Logger::instance()->log_action( 'CRON :: sites to check update empty', MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );

			if ( $updatecheck_running ) {
				MainWP_Utility::update_option( 'mainwp_updatescheck_is_running', '' );
				$updatecheck_running = false;
			}

			update_option( 'mainwp_last_synced_all_sites', time() );
			MainWP_Utility::update_option( 'mainwp_updatescheck_frequency_today_count', $frequence_now );
			MainWP_Utility::update_option( 'mainwp_updatescheck_last_timestamp', $local_timestamp );

			MainWP_Utility::update_option( 'mainwp_updatescheck_last', $today_m_y );

			// send http check notification.
			if ( 1 == get_option( 'mainwp_check_http_response', 0 ) ) {
				$this->start_notification_http_check( $plain_text );
			}

			if ( 'Y' != get_option( 'mainwp_updatescheck_ready_sendmail' ) ) {
				MainWP_Utility::update_option( 'mainwp_updatescheck_ready_sendmail', 'Y' );
			}
			return;
		} else {

			MainWP_Logger::instance()->log_action( 'CRON :: auto updates check is running', MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );

			if ( ! $updatecheck_running ) {
				MainWP_Utility::update_option( 'mainwp_updatescheck_is_running', 'Y' );
			}

			$userExtension = MainWP_DB_Common::instance()->get_user_extension_by_user_id( $userid );

			$decodedIgnoredPlugins = json_decode( $userExtension->ignored_plugins, true );
			if ( ! is_array( $decodedIgnoredPlugins ) ) {
				$decodedIgnoredPlugins = array();
			}

			$trustedPlugins = json_decode( $userExtension->trusted_plugins, true );
			if ( ! is_array( $trustedPlugins ) ) {
				$trustedPlugins = array();
			}

			$decodedIgnoredThemes = json_decode( $userExtension->ignored_themes, true );
			if ( ! is_array( $decodedIgnoredThemes ) ) {
				$decodedIgnoredThemes = array();
			}

			$trustedThemes = json_decode( $userExtension->trusted_themes, true );
			if ( ! is_array( $trustedThemes ) ) {
				$trustedThemes = array();
			}

			$coreToUpdateNow      = array();
			$coreToUpdate         = array();
			$coreNewUpdate        = array();
			$ignoredCoreToUpdate  = array();
			$ignoredCoreNewUpdate = array();

			$pluginsToUpdateNow         = array();
			$pluginsToUpdate            = array();
			$pluginsNewUpdate           = array();
			$notTrustedPluginsToUpdate  = array();
			$notTrustedPluginsNewUpdate = array();

			$themesToUpdateNow         = array();
			$themesToUpdate            = array();
			$themesNewUpdate           = array();
			$notTrustedThemesToUpdate  = array();
			$notTrustedThemesNewUpdate = array();

			$allWebsites = array();

			$individualDailyDigestWebsites = array();

			$updatescheckSitesIcon = get_option( 'mainwp_updatescheck_sites_icon' );
			if ( ! is_array( $updatescheckSitesIcon ) ) {
				$updatescheckSitesIcon = array();
			}

			foreach ( $websites as $website ) {
				$websiteDecodedIgnoredPlugins = json_decode( $website->ignored_plugins, true );
				if ( ! is_array( $websiteDecodedIgnoredPlugins ) ) {
					$websiteDecodedIgnoredPlugins = array();
				}

				$websiteDecodedIgnoredThemes = json_decode( $website->ignored_themes, true );
				if ( ! is_array( $websiteDecodedIgnoredThemes ) ) {
					$websiteDecodedIgnoredThemes = array();
				}

				if ( ! MainWP_Sync::sync_site( $website, false, true ) ) {
					$websiteValues = array(
						'dtsAutomaticSync' => $local_timestamp,
					);

					MainWP_DB::instance()->update_website_sync_values( $website->id, $websiteValues );

					continue;
				}
				$website = MainWP_DB::instance()->get_website_by_id( $website->id );

				$check_individual_digest = false;

				/** Check core updates * */
				$websiteLastCoreUpgrades = json_decode( MainWP_DB::instance()->get_website_option( $website, 'last_wp_upgrades' ), true );
				$websiteCoreUpgrades     = json_decode( MainWP_DB::instance()->get_website_option( $website, 'wp_upgrades' ), true );

				if ( isset( $websiteCoreUpgrades['current'] ) ) {
					$newUpdate = ! ( isset( $websiteLastCoreUpgrades['current'] ) && ( $websiteLastCoreUpgrades['current'] == $websiteCoreUpgrades['current'] ) && ( $websiteLastCoreUpgrades['new'] == $websiteCoreUpgrades['new'] ) );
					if ( ! $website->is_ignoreCoreUpdates ) {
						$check_individual_digest = true;
						$item                    = array(
							'id'          => $website->id,
							'name'        => $website->name,
							'url'         => $website->url,
							'current'     => $websiteCoreUpgrades['current'],
							'new_version' => $websiteCoreUpgrades['new'],
						);
						if ( 1 == $website->automatic_update ) {
							$item['trusted'] = 1;
							if ( $newUpdate ) {
								$item['new']     = 1;
								$coreNewUpdate[] = $item;
							} else {
								$item['new']                 = 0;
								$coreToUpdateNow[]           = $website->id;
								$allWebsites[ $website->id ] = $website;
								$coreToUpdate[]              = $item;
							}
						} else {
							$item['trusted'] = 0;
							if ( $newUpdate ) {
								$item['new']            = 1;
								$ignoredCoreNewUpdate[] = $item;
							} else {
								$item['new']           = 0;
								$ignoredCoreToUpdate[] = $item;
							}
						}
					}
				}

				/** Check plugins * */
				$websiteLastPlugins = json_decode( MainWP_DB::instance()->get_website_option( $website, 'last_plugin_upgrades' ), true );
				$websitePlugins     = json_decode( $website->plugin_upgrades, true );

				/** Check themes * */
				$websiteLastThemes = json_decode( MainWP_DB::instance()->get_website_option( $website, 'last_theme_upgrades' ), true );
				$websiteThemes     = json_decode( $website->theme_upgrades, true );

				$decodedPremiumUpgrades = json_decode( MainWP_DB::instance()->get_website_option( $website, 'premium_upgrades' ), true );
				if ( is_array( $decodedPremiumUpgrades ) ) {
					foreach ( $decodedPremiumUpgrades as $slug => $premiumUpgrade ) {
						if ( 'plugin' === $premiumUpgrade['type'] ) {
							if ( ! is_array( $websitePlugins ) ) {
								$websitePlugins = array();
							}
							$websitePlugins[ $slug ] = $premiumUpgrade;
						} elseif ( 'theme' === $premiumUpgrade['type'] ) {
							if ( ! is_array( $websiteThemes ) ) {
								$websiteThemes = array();
							}
							$websiteThemes[ $slug ] = $premiumUpgrade;
						}
					}
				}

				foreach ( $websitePlugins as $pluginSlug => $pluginInfo ) {
					if ( isset( $decodedIgnoredPlugins[ $pluginSlug ] ) || isset( $websiteDecodedIgnoredPlugins[ $pluginSlug ] ) ) {
						continue;
					}
					if ( $website->is_ignorePluginUpdates ) {
						continue;
					}

					$check_individual_digest = true;

					$change_log = '';
					if ( isset( $pluginInfo['update']['url'] ) && ( false !== strpos( $pluginInfo['update']['url'], 'wordpress.org/plugins' ) ) ) {
						$change_log = $pluginInfo['update']['url'];
						if ( substr( $change_log, - 1 ) != '/' ) {
							$change_log .= '/';
						}
						$change_log .= '#developers';
					}

					$item = array(
						'id'          => $website->id,
						'name'        => $website->name,
						'url'         => $website->url,
						'plugin'      => $pluginInfo['Name'],
						'current'     => $pluginInfo['Version'],
						'new_version' => $pluginInfo['update']['new_version'],
						'change_log'  => $change_log,
					);

					$newUpdate = ! ( isset( $websiteLastPlugins[ $pluginSlug ] ) && ( $pluginInfo['Version'] == $websiteLastPlugins[ $pluginSlug ]['Version'] ) && ( $pluginInfo['update']['new_version'] == $websiteLastPlugins[ $pluginSlug ]['update']['new_version'] ) );
					if ( in_array( $pluginSlug, $trustedPlugins ) ) {
						$item['trusted'] = 1;
						if ( $newUpdate ) {
							$item['new']        = 1;
							$pluginsNewUpdate[] = $item;
						} else {
							$item['new']                          = 0;
							$pluginsToUpdateNow[ $website->id ][] = $pluginSlug;
							$allWebsites[ $website->id ]          = $website;
							$pluginsToUpdate[]                    = $item;
						}
					} else {
						$item['trusted'] = 0;
						if ( $newUpdate ) {
							$item['new']                  = 1;
							$notTrustedPluginsNewUpdate[] = $item;
						} else {
							$item['new']                 = 0;
							$notTrustedPluginsToUpdate[] = $item;
						}
					}
				}

				foreach ( $websiteThemes as $themeSlug => $themeInfo ) {
					if ( isset( $decodedIgnoredThemes[ $themeSlug ] ) || isset( $websiteDecodedIgnoredThemes[ $themeSlug ] ) ) {
						continue;
					}

					if ( $website->is_ignoreThemeUpdates ) {
						continue;
					}

					$check_individual_digest = true;

					$newUpdate = ! ( isset( $websiteLastThemes[ $themeSlug ] ) && ( $themeInfo['Version'] == $websiteLastThemes[ $themeSlug ]['Version'] ) && ( $themeInfo['update']['new_version'] == $websiteLastThemes[ $themeSlug ]['update']['new_version'] ) );

					$item = array(
						'id'          => $website->id,
						'name'        => $website->name,
						'url'         => $website->url,
						'theme'       => $themeInfo['Name'],
						'current'     => $themeInfo['Version'],
						'new_version' => $themeInfo['update']['new_version'],
					);

					if ( in_array( $themeSlug, $trustedThemes ) ) {
						$item['trusted'] = 1;
						if ( $newUpdate ) {
							$item['new']       = 1;
							$themesNewUpdate[] = $item;
						} else {
							$item['new']                         = 0;
							$themesToUpdateNow[ $website->id ][] = $themeSlug;
							$allWebsites[ $website->id ]         = $website;
							$themesToUpdate[]                    = $item;
						}
					} else {
						$item['trusted'] = 0;
						if ( $newUpdate ) {
							$item['new']                 = 1;
							$notTrustedThemesNewUpdate[] = $item;
						} else {
							$item['new']                = 0;
							$notTrustedThemesToUpdate[] = $item;
						}
					}
				}

				/**
				 * Action: mainwp_daily_digest_action
				 *
				 * Hooks the daily digest email notification send process.
				 *
				 * @param object $website    Object conaining child site info.
				 * @param bool   $plain_text Whether plain text email should be sent.
				 *
				 * @since 4.1
				 */
				do_action( 'mainwp_daily_digest_action', $website, $plain_text );
				MainWP_DB::instance()->update_website_sync_values( $website->id, array( 'dtsAutomaticSync' => $local_timestamp ) );
				MainWP_DB::instance()->update_website_option( $website, 'last_wp_upgrades', wp_json_encode( $websiteCoreUpgrades ) );
				MainWP_DB::instance()->update_website_option( $website, 'last_plugin_upgrades', $website->plugin_upgrades );
				MainWP_DB::instance()->update_website_option( $website, 'last_theme_upgrades', $website->theme_upgrades );

				if ( ! in_array( $website->id, $updatescheckSitesIcon ) ) {
					MainWP_Sync::sync_site_icon( $website->id );
					$updatescheckSitesIcon[] = $website->id;
				}

				if ( $check_individual_digest ) {
					$individualDailyDigestWebsites[] = $website->id;
				}
			}

			MainWP_Utility::update_option( 'mainwp_updatescheck_sites_icon', $updatescheckSitesIcon );

			if ( 0 != count( $individualDailyDigestWebsites ) ) {
				$individualDailyDigestWebsitesSaved = get_option( 'mainwp_updatescheck_individual_digest_websites' );
				if ( ! is_array( $individualDailyDigestWebsitesSaved ) ) {
					$individualDailyDigestWebsitesSaved = array();
				}
				foreach ( $individualDailyDigestWebsites as $sid ) {
					if ( ! in_array( $sid, $individualDailyDigestWebsitesSaved ) ) {
						$individualDailyDigestWebsitesSaved[] = $sid;
					}
				}
				MainWP_Utility::update_option( 'mainwp_updatescheck_individual_digest_websites', $individualDailyDigestWebsitesSaved );
			}

			if ( count( $coreNewUpdate ) != 0 ) {
				$coreNewUpdateSaved = get_option( 'mainwp_updatescheck_mail_update_core_new' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_update_core_new', MainWP_Utility::array_merge( $coreNewUpdateSaved, $coreNewUpdate ) );
			}

			if ( count( $pluginsNewUpdate ) != 0 ) {
				$pluginsNewUpdateSaved = get_option( 'mainwp_updatescheck_mail_update_plugins_new' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_update_plugins_new', MainWP_Utility::array_merge( $pluginsNewUpdateSaved, $pluginsNewUpdate ) );
			}

			if ( count( $themesNewUpdate ) != 0 ) {
				$themesNewUpdateSaved = get_option( 'mainwp_updatescheck_mail_update_themes_new' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_update_themes_new', MainWP_Utility::array_merge( $themesNewUpdateSaved, $themesNewUpdate ) );
			}

			if ( count( $coreToUpdate ) != 0 ) {
				$coreToUpdateSaved = get_option( 'mainwp_updatescheck_mail_update_core' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_update_core', MainWP_Utility::array_merge( $coreToUpdateSaved, $coreToUpdate ) );
			}

			if ( count( $pluginsToUpdate ) != 0 ) {
				$pluginsToUpdateSaved = get_option( 'mainwp_updatescheck_mail_update_plugins' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_update_plugins', MainWP_Utility::array_merge( $pluginsToUpdateSaved, $pluginsToUpdate ) );
			}

			if ( count( $themesToUpdate ) != 0 ) {
				$themesToUpdateSaved = get_option( 'mainwp_updatescheck_mail_update_themes' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_update_themes', MainWP_Utility::array_merge( $themesToUpdateSaved, $themesToUpdate ) );
			}

			if ( count( $ignoredCoreToUpdate ) != 0 ) {
				$ignoredCoreToUpdateSaved = get_option( 'mainwp_updatescheck_mail_ignore_core' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_ignore_core', MainWP_Utility::array_merge( $ignoredCoreToUpdateSaved, $ignoredCoreToUpdate ) );
			}

			if ( count( $ignoredCoreNewUpdate ) != 0 ) {
				$ignoredCoreNewUpdateSaved = get_option( 'mainwp_updatescheck_mail_ignore_core_new' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_ignore_core_new', MainWP_Utility::array_merge( $ignoredCoreNewUpdateSaved, $ignoredCoreNewUpdate ) );
			}

			if ( count( $notTrustedPluginsToUpdate ) != 0 ) {
				$notTrustedPluginsToUpdateSaved = get_option( 'mainwp_updatescheck_mail_ignore_plugins' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_ignore_plugins', MainWP_Utility::array_merge( $notTrustedPluginsToUpdateSaved, $notTrustedPluginsToUpdate ) );
			}

			if ( count( $notTrustedPluginsNewUpdate ) != 0 ) {
				$notTrustedPluginsNewUpdateSaved = get_option( 'mainwp_updatescheck_mail_ignore_plugins_new' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_ignore_plugins_new', MainWP_Utility::array_merge( $notTrustedPluginsNewUpdateSaved, $notTrustedPluginsNewUpdate ) );
			}

			if ( count( $notTrustedThemesToUpdate ) != 0 ) {
				$notTrustedThemesToUpdateSaved = get_option( 'mainwp_updatescheck_mail_ignore_themes' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_ignore_themes', MainWP_Utility::array_merge( $notTrustedThemesToUpdateSaved, $notTrustedThemesToUpdate ) );
			}

			if ( count( $notTrustedThemesNewUpdate ) != 0 ) {
				$notTrustedThemesNewUpdateSaved = get_option( 'mainwp_updatescheck_mail_ignore_themes_new' );
				MainWP_Utility::update_option( 'mainwp_updatescheck_mail_ignore_themes_new', MainWP_Utility::array_merge( $notTrustedThemesNewUpdateSaved, $notTrustedThemesNewUpdate ) );
			}

			if ( ( count( $coreToUpdate ) == 0 ) && ( count( $pluginsToUpdate ) == 0 ) && ( count( $themesToUpdate ) == 0 ) && ( count( $ignoredCoreToUpdate ) == 0 ) && ( count( $ignoredCoreNewUpdate ) == 0 ) && ( count( $notTrustedPluginsToUpdate ) == 0 ) && ( count( $notTrustedPluginsNewUpdate ) == 0 ) && ( count( $notTrustedThemesToUpdate ) == 0 ) && ( count( $notTrustedThemesNewUpdate ) == 0 )
			) {
				return;
			}

			if ( 1 != $mainwpAutomaticDailyUpdate && 1 != $plugin_automaticDailyUpdate && 1 != $theme_automaticDailyUpdate ) {
				return;
			}

			// going to retired.
			if ( get_option( 'mainwp_enableLegacyBackupFeature' ) && get_option( 'mainwp_backup_before_upgrade' ) == 1 ) {
				$sitesCheckCompleted = get_option( 'mainwp_automaticUpdate_backupChecks' );
				if ( ! is_array( $sitesCheckCompleted ) ) {
					$sitesCheckCompleted = array();
				}

				$websitesToCheck = array();

				if ( 1 == $plugin_automaticDailyUpdate ) {
					foreach ( $pluginsToUpdateNow as $websiteId => $slugs ) {
						$websitesToCheck[ $websiteId ] = true;
					}
				}

				if ( 1 == $theme_automaticDailyUpdate ) {
					foreach ( $themesToUpdateNow as $websiteId => $slugs ) {
						$websitesToCheck[ $websiteId ] = true;
					}
				}

				if ( 1 == $mainwpAutomaticDailyUpdate ) {
					foreach ( $coreToUpdateNow as $websiteId ) {
						$websitesToCheck[ $websiteId ] = true;
					}
				}

				$hasWPFileSystem = MainWP_System_Utility::get_wp_file_system();

				/**
				 * WordPress files system object.
				 *
				 * @global object
				 */
				global $wp_filesystem;

				if ( $hasWPFileSystem && ! empty( $wp_filesystem ) ) {
					foreach ( $websitesToCheck as $siteId => $bool ) {
						if ( 0 == $allWebsites[ $siteId ]->backup_before_upgrade ) {
							$sitesCheckCompleted[ $siteId ] = true;
						}
						if ( isset( $sitesCheckCompleted[ $siteId ] ) ) {
							continue;
						}

						$dir        = MainWP_System_Utility::get_mainwp_specific_dir( $siteId );
						$dh         = opendir( $dir );
						$lastBackup = - 1;
						if ( $wp_filesystem->exists( $dir ) && $dh ) {
							while ( ( $file = readdir( $dh ) ) !== false ) {
								if ( '.' !== $file && '..' !== $file ) {
									$theFile = $dir . $file;
									if ( MainWP_Backup_Handler::is_archive( $file ) && ! MainWP_Backup_Handler::is_sql_archive( $file ) && ( $wp_filesystem->mtime( $theFile ) > $lastBackup ) ) {
										$lastBackup = $wp_filesystem->mtime( $theFile );
									}
								}
							}
							closedir( $dh );
						}

						$mainwp_backup_before_upgrade_days = get_option( 'mainwp_backup_before_upgrade_days' );
						if ( empty( $mainwp_backup_before_upgrade_days ) || ! ctype_digit( $mainwp_backup_before_upgrade_days ) ) {
							$mainwp_backup_before_upgrade_days = 7;
						}

						$backupRequired = ( $lastBackup < ( time() - ( $mainwp_backup_before_upgrade_days * 24 * 60 * 60 ) ) ? true : false );

						if ( ! $backupRequired ) {
							$sitesCheckCompleted[ $siteId ] = true;
							MainWP_Utility::update_option( 'mainwp_automaticUpdate_backupChecks', $sitesCheckCompleted );
							continue;
						}

						try {
							$result = MainWP_Backup_Handler::backup( $siteId, 'full', '', '', 0, 0, 0, 0 );
							MainWP_Backup_Handler::backup_download_file( $siteId, 'full', $result['url'], $result['local'] );
							$sitesCheckCompleted[ $siteId ] = true;
							MainWP_Utility::update_option( 'mainwp_automaticUpdate_backupChecks', $sitesCheckCompleted );
						} catch ( \Exception $e ) {
							$sitesCheckCompleted[ $siteId ] = false;
							MainWP_Utility::update_option( 'mainwp_automaticUpdate_backupChecks', $sitesCheckCompleted );
						}
					}
				}
			} else {
				$sitesCheckCompleted = null;
			}

			/**  Auto updates part. */
			if ( 1 == $plugin_automaticDailyUpdate ) {
				foreach ( $pluginsToUpdateNow as $websiteId => $slugs ) {
					if ( ( null != $sitesCheckCompleted ) && ( false == $sitesCheckCompleted[ $websiteId ] ) ) {
						continue;
					}

					try {

						MainWP_Logger::instance()->debug( 'CRON :: auto update plugins [websiteid=' . $websiteId . ']' );
						MainWP_Logger::instance()->log_action( 'CRON :: auto update plugins [websiteid=' . $websiteId . '] :: slugs :: ' . urldecode( implode( ',', $slugs ) ), MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );

						/**
						* Action: mainwp_before_plugin_theme_translation_update
						*
						* Fires before plugin/theme/translation update actions.
						*
						* @since 4.1
						*/
						do_action( 'mainwp_before_plugin_theme_translation_update', 'plugin', implode( ',', $slugs ), $allWebsites[ $websiteId ] );

						$information = MainWP_Connect::fetch_url_authed(
							$allWebsites[ $websiteId ],
							'upgradeplugintheme',
							array(
								'type' => 'plugin',
								'list' => urldecode( implode( ',', $slugs ) ),
							)
						);

						/**
						* Action: mainwp_after_plugin_theme_translation_update
						*
						* Fires before plugin/theme/translation update actions.
						*
						* @since 4.1
						*/
						do_action( 'mainwp_after_plugin_theme_translation_update', $information, 'plugin', implode( ',', $slugs ), $allWebsites[ $websiteId ] );

						if ( isset( $information['sync'] ) && ! empty( $information['sync'] ) ) {
							MainWP_Sync::sync_information_array( $allWebsites[ $websiteId ], $information['sync'] );
						}
					} catch ( \Exception $e ) {
						// ok.
					}
				}
			} else {
				$pluginsToUpdateNow = array();
			}

			if ( 1 == $theme_automaticDailyUpdate ) {
				foreach ( $themesToUpdateNow as $websiteId => $slugs ) {
					if ( ( null != $sitesCheckCompleted ) && ( false == $sitesCheckCompleted[ $websiteId ] ) ) {
						continue;
					}

					MainWP_Logger::instance()->log_action( 'CRON :: auto update theme [websiteid=' . $websiteId . '] :: themes :: ' . implode( ',', $slugs ), MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );

					/**
					* Action: mainwp_before_plugin_theme_translation_update
					*
					* Fires before plugin/theme/translation update actions.
					*
					* @since 4.1
					*/
					do_action( 'mainwp_before_plugin_theme_translation_update', 'theme', implode( ',', $slugs ), $allWebsites[ $websiteId ] );

					try {
						$information = MainWP_Connect::fetch_url_authed(
							$allWebsites[ $websiteId ],
							'upgradeplugintheme',
							array(
								'type' => 'theme',
								'list' => urldecode( implode( ',', $slugs ) ),
							)
						);

						if ( isset( $information['sync'] ) && ! empty( $information['sync'] ) ) {
							MainWP_Sync::sync_information_array( $allWebsites[ $websiteId ], $information['sync'] );
						}
					} catch ( \Exception $e ) {
						// ok.
					}

					/**
					* Action: mainwp_after_plugin_theme_translation_update
					*
					* Fires before plugin/theme/translation update actions.
					*
					* @since 4.1
					*/
					do_action( 'mainwp_after_plugin_theme_translation_update', $information, 'theme', implode( ',', $slugs ), $allWebsites[ $websiteId ] );

				}
			} else {
				$themesToUpdateNow = array();
			}

			if ( 1 == $mainwpAutomaticDailyUpdate ) {
				foreach ( $coreToUpdateNow as $websiteId ) {
					if ( ( null != $sitesCheckCompleted ) && ( false == $sitesCheckCompleted[ $websiteId ] ) ) {
						continue;
					}
					MainWP_Logger::Instance()->debug( 'CRON :: auto update core [websiteid=' . $websiteId . ']' );
					MainWP_Logger::instance()->log_action( 'CRON :: auto update core [websiteid=' . $websiteId . ']', MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );
					try {
						MainWP_Connect::fetch_url_authed( $allWebsites[ $websiteId ], 'upgrade' );
					} catch ( \Exception $e ) {
						// ok.
					}
				}
			} else {
				$coreToUpdateNow = array();
			}

			/**
			 * Action: mainwp_cronupdatecheck_action
			 *
			 * Fires upon checking for available updates scheduled action.
			 *
			 * @param array $pluginsNewUpdate   Array of new available plugin updates.
			 * @param array $pluginsToUpdate    Array of new available plugin updates that should be processed.
			 * @param array $pluginsToUpdateNow Array of new available plugin updates that should be processed now.
			 * @param array $themesNewUpdate    Array of new available themes updates.
			 * @param array $themesToUpdate     Array of new available themes updates that should be processed.
			 * @param array $themesToUpdateNow  Array of new available themes updates that should be processed now.
			 * @param array $coreNewUpdate      Array of new available WP updates.
			 * @param array $coreToUpdate       Array of new available WP updates that should be processed.
			 * @param array $coreToUpdateNow    Array of new available WP updates that should be processed now.
			 *
			 * @since Unknown
			 */
			do_action( 'mainwp_cronupdatecheck_action', $pluginsNewUpdate, $pluginsToUpdate, $pluginsToUpdateNow, $themesNewUpdate, $themesToUpdate, $themesToUpdateNow, $coreNewUpdate, $coreToUpdate, $coreToUpdateNow );
		}
	}

	/**
	 * Method start_notification_daily_digest().
	 *
	 * Send email notification.
	 *
	 * @param array  $email_settings Email settings.
	 * @param bool   $plain_text Text format value.
	 * @param array  $sites_ids Array of websites ids (option).
	 * @param object $email_site current report site.
	 *
	 * @return bool True|False
	 *
	 * @uses \MainWP\Dashboard\MainWP_Format::get_site_updates_items()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_disconnected_websites()
	 * @uses \MainWP\Dashboard\MainWP_Logger::debug()
	 * @uses \MainWP\Dashboard\MainWP_Notification
	 */
	public function start_notification_daily_digest( $email_settings, $plain_text, $sites_ids = false, $email_site = false ) {

		$sendMail       = false;
		$updateAvaiable = false;
		$plugin_updates = array();
		$theme_updates  = array();

		$plugin_updates = MainWP_Format::get_site_updates_items( 'plugin', $sites_ids );
		if ( 0 < count( $plugin_updates ) ) {
			$sendMail       = true;
			$updateAvaiable = true;
		}

		$theme_updates = MainWP_Format::get_site_updates_items( 'theme', $sites_ids );
		if ( 0 < count( $theme_updates ) ) {
			$sendMail       = true;
			$updateAvaiable = true;
		}

		$wp_updates = MainWP_Format::get_site_updates_items( 'wpcore', $sites_ids );
		if ( 0 < count( $wp_updates ) ) {
			$sendMail       = true;
			$updateAvaiable = true;
		}

		$sites_disconnected = MainWP_DB::instance()->get_disconnected_websites( $sites_ids );

		if ( 0 < count( $sites_disconnected ) ) {
			$sendMail = true;
		}

		if ( ! $sendMail ) {
			MainWP_Logger::instance()->debug( 'CRON :: updates check :: sendMail is false' );
			MainWP_Logger::instance()->log_action( 'CRON :: updates check :: sendMail is false', MAINWP_UPDATE_CHECK_LOG_PRIORITY_NUMBER );
			return false;
		}

		return MainWP_Notification::send_daily_digest_notification( $email_settings, $updateAvaiable, $wp_updates, $plugin_updates, $theme_updates, $sites_disconnected, $plain_text, $sites_ids, $email_site );
	}

	/**
	 * Method refresh_saved_fields().
	 *
	 * Clear settings field values.
	 *
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function refresh_saved_fields() {
		$empty_fields = array(
			'mainwp_automaticUpdate_backupChecks',
			'mainwp_updatescheck_mail_update_core_new',
			'mainwp_updatescheck_mail_update_plugins_new',
			'mainwp_updatescheck_mail_update_themes_new',
			'mainwp_updatescheck_mail_update_core',
			'mainwp_updatescheck_mail_update_plugins',
			'mainwp_updatescheck_mail_update_themes',
			'mainwp_updatescheck_mail_ignore_core',
			'mainwp_updatescheck_mail_ignore_plugins',
			'mainwp_updatescheck_mail_ignore_themes',
			'mainwp_updatescheck_mail_ignore_core_new',
			'mainwp_updatescheck_mail_ignore_plugins_new',
			'mainwp_updatescheck_mail_ignore_themes_new',
			'mainwp_updatescheck_ready_sendmail',
			'mainwp_updatescheck_individual_digest_websites',
			'mainwp_updatescheck_sites_icon',
		);

		foreach ( $empty_fields as $field ) {
			MainWP_Utility::update_option( $field, '' );
		}
	}

	/**
	 * Method start_notification_http_check().
	 *
	 * Prepare to send http check notification.
	 *
	 * @param bool $plain_text Text format value.
	 *
	 * @return bool True|False
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB::get_websites_offline_check_status()
	 * @uses \MainWP\Dashboard\MainWP_Notification::send_http_check_notification()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_general_email_settings()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_site_email_settings()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_default_emails_fields()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::get_http_codes()
	 */
	public function start_notification_http_check( $plain_text ) {

		$sitesHttpCheck       = array();
		$email_settings_sites = array();

		$sites_offline = MainWP_DB::instance()->get_websites_offline_check_status();
		if ( is_array( $sites_offline ) && count( $sites_offline ) > 0 ) {
			foreach ( $sites_offline as $site ) {
				if ( 200 == $site->http_response_code ) { // to fix: ignored 200 http code.
					continue;
				}
				$email_settings_sites[ $site->id ] = $site->settings_notification_emails; // ok.
				$code                              = $site->http_response_code;
				$code_string                       = MainWP_Utility::get_http_codes( $code );
				if ( ! empty( $code_string ) ) {
					$code .= ' - ' . $code_string;
				}

				$sitesHttpCheck[] = array(
					'id'   => $site->id,
					'name' => $site->name,
					'url'  => $site->url,
					'code' => $code,
				);
			}
		}

		if ( empty( $sitesHttpCheck ) ) {
			return false;
		}

		$gen_settings = MainWP_Notification_Settings::get_general_email_settings( 'http_check' );
		// general http check notificaion, to administrator.
		if ( ! $gen_settings['disable'] ) {
			MainWP_Notification::send_http_check_notification( $gen_settings, $sitesHttpCheck, $plain_text );
			usleep( 100000 );
		}

		$to_admin_HttpCheckWebsites = array();
		// individual http check notification.
		foreach ( $sitesHttpCheck as $site ) {

			$website       = new \stdClass();
			$website->id   = $site['id'];
			$website->url  = $site['url'];
			$website->name = $site['name'];

			$website->settings_notification_emails = $email_settings_sites[ $site['id'] ]; // ok.

			$settings = MainWP_Notification_Settings::get_site_email_settings( 'http_check', $website ); // get site email settings.

			if ( ! $settings['disable'] ) {
				$to_admin_HttpCheckWebsites[] = $site;

				$sent = MainWP_Notification::send_http_check_notification( $settings, array( $site ), $plain_text, false );
				if ( $sent ) {
					usleep( 100000 );
				}
			}
		}

		// send all individual notice to admin in one email.
		if ( 0 < count( $to_admin_HttpCheckWebsites ) ) {
			$admin_email_settings               = MainWP_Notification_Settings::get_default_emails_fields( 'http_check', '', true ); // get default subject and heading only.
			$admin_email_settings['disable']    = 0;
			$admin_email_settings['recipients'] = ''; // sent to admin only.
			MainWP_Notification::send_http_check_notification( $admin_email_settings, $sitesHttpCheck, $plain_text );
		}

		return true;
	}

	/**
	 * Method cron_ping_childs()
	 *
	 * Cron job to ping child sites.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB::query()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_sql_websites()
	 * @uses \MainWP\Dashboard\MainWP_DB::fetch_object()
	 * @uses \MainWP\Dashboard\MainWP_DB::free_result()
	 * @uses \MainWP\Dashboard\MainWP_Logger::info()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::ends_with()
	 */
	public function cron_ping_childs() {
		MainWP_Logger::instance()->info( 'CRON :: ping childs' );

		$lastPing = get_option( 'mainwp_cron_last_ping' );
		if ( false !== $lastPing && ( time() - $lastPing ) < ( 60 * 60 * 23 ) ) {
			return;
		}
		MainWP_Utility::update_option( 'mainwp_cron_last_ping', time() );

		$websites = MainWP_DB::instance()->query( MainWP_DB::instance()->get_sql_websites() );
		while ( $websites && ( $website  = MainWP_DB::fetch_object( $websites ) ) ) {
			try {
				$url = $website->siteurl;
				if ( ! MainWP_Utility::ends_with( $url, '/' ) ) {
					$url .= '/';
				}

				wp_remote_get( $url . 'wp-cron.php' );
			} catch ( \Exception $e ) {
				// ok.
			}
		}
		MainWP_DB::free_result( $websites );
	}

	/**
	 * Method cron_backups_continue()
	 *
	 * Execute remaining backup tasks.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB_Backup::get_backup_tasks_to_complete()
	 * @uses \MainWP\Dashboard\MainWP_DB_Backup::get_backup_task_by_id()
	 * @uses \MainWP\Dashboard\MainWP_Logger::info()
	 * @uses \MainWP\Dashboard\MainWP_Logger::debug()
	 * @uses \MainWP\Dashboard\MainWP_Manage_Backups_Handler::execute_backup_task()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function cron_backups_continue() {

		if ( ! get_option( 'mainwp_enableLegacyBackupFeature' ) ) {
			return;
		}

		MainWP_Logger::instance()->info( 'CRON :: backups continue' );

		ignore_user_abort( true );
		MainWP_System_Utility::set_time_limit( 0 );
		add_filter(
			'admin_memory_limit',
			function() {
				return '512M';
			}
		);

		MainWP_Utility::update_option( 'mainwp_cron_last_backups_continue', time() );

		$tasks = MainWP_DB_Backup::instance()->get_backup_tasks_to_complete();

		MainWP_Logger::instance()->debug( 'CRON :: backups continue :: Found ' . count( $tasks ) . ' to continue.' );

		if ( empty( $tasks ) ) {
			return;
		}

		foreach ( $tasks as $task ) {
			MainWP_Logger::instance()->debug( 'CRON :: backups continue ::    Task: ' . $task->name );
		}

		foreach ( $tasks as $task ) {
			$task = MainWP_DB_Backup::instance()->get_backup_task_by_id( $task->id );
			if ( $task->completed < $task->last_run ) {
				MainWP_Manage_Backups_Handler::execute_backup_task( $task, 5, false );
				break;
			}
		}
	}

	/**
	 * Method cron_backups()
	 *
	 * Execute Backup Tasks.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB_Backup::get_backup_tasks_todo_daily()
	 * @uses \MainWP\Dashboard\MainWP_DB_Backup::get_backup_tasks_todo_weekly()
	 * @uses \MainWP\Dashboard\MainWP_DB_Backup::get_backup_tasks_todo_monthly()
	 * @uses \MainWP\Dashboard\MainWP_DB_Backup::get_backup_task_by_id()
	 * @uses \MainWP\Dashboard\MainWP_Logger::info()
	 * @uses \MainWP\Dashboard\MainWP_Logger::debug()
	 * @uses \MainWP\Dashboard\MainWP_Manage_Backups::validate_backup_tasks()
	 * @uses \MainWP\Dashboard\MainWP_Manage_Backups_Handler::execute_backup_task()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function cron_backups() {
		if ( ! get_option( 'mainwp_enableLegacyBackupFeature' ) ) {
			return;
		}

		MainWP_Logger::instance()->info( 'CRON :: backups' );

		ignore_user_abort( true );
		MainWP_System_Utility::set_time_limit( 0 );
		add_filter(
			'admin_memory_limit',
			function() {
				return '512M';
			}
		);

		MainWP_Utility::update_option( 'mainwp_cron_last_backups', time() );

		$allTasks   = array();
		$dailyTasks = MainWP_DB_Backup::instance()->get_backup_tasks_todo_daily();
		if ( count( $dailyTasks ) > 0 ) {
			$allTasks = $dailyTasks;
		}
		$weeklyTasks = MainWP_DB_Backup::instance()->get_backup_tasks_todo_weekly();
		if ( count( $weeklyTasks ) > 0 ) {
			$allTasks = array_merge( $allTasks, $weeklyTasks );
		}
		$monthlyTasks = MainWP_DB_Backup::instance()->get_backup_tasks_todo_monthly();
		if ( count( $monthlyTasks ) > 0 ) {
			$allTasks = array_merge( $allTasks, $monthlyTasks );
		}

		MainWP_Logger::instance()->debug( 'CRON :: backups :: Found ' . count( $allTasks ) . ' to start.' );

		foreach ( $allTasks as $task ) {
			MainWP_Logger::instance()->debug( 'CRON :: backups ::    Task: ' . $task->name );
		}

		foreach ( $allTasks as $task ) {
			$threshold = 0;
			if ( 'daily' == $task->schedule ) {
				$threshold = ( 60 * 60 * 24 );
			} elseif ( 'weekly' == $task->schedule ) {
				$threshold = ( 60 * 60 * 24 * 7 );
			} elseif ( 'monthly' == $task->schedule ) {
				$threshold = ( 60 * 60 * 24 * 30 );
			}
			$task = MainWP_DB_Backup::instance()->get_backup_task_by_id( $task->id );
			if ( ( time() - $task->last_run ) < $threshold ) {
				continue;
			}

			if ( ! MainWP_Manage_Backups_Handler::validate_backup_tasks( array( $task ) ) ) {
				$task = MainWP_DB_Backup::instance()->get_backup_task_by_id( $task->id );
			}

			$chunkedBackupTasks = get_option( 'mainwp_chunkedBackupTasks' );
			MainWP_Manage_Backups_Handler::execute_backup_task( $task, ( 0 !== $chunkedBackupTasks ? 5 : 0 ) );
		}
	}

	/**
	 * Method cron_stats()
	 *
	 * Grab MainWP Cron Job Statistics.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB::query()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_websites_stats_update_sql()
	 * @uses \MainWP\Dashboard\MainWP_DB::update_website_stats()
	 * @uses \MainWP\Dashboard\MainWP_DB::fetch_object()
	 * @uses \MainWP\Dashboard\MainWP_DB::free_result()
	 * @uses \MainWP\Dashboard\MainWP_Logger::info()
	 * @uses \MainWP\Dashboard\MainWP_Logger::info_for_website()
	 * @uses \MainWP\Dashboard\MainWP_Logger::warning_for_website()
	 * @uses \MainWP\Dashboard\MainWP_Manage_Sites_View::m_reconnect_site()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function cron_stats() {
		MainWP_Logger::instance()->info( 'CRON :: stats' );

		MainWP_Utility::update_option( 'mainwp_cron_last_stats', time() );

		$websites = MainWP_DB::instance()->query( MainWP_DB::instance()->get_websites_stats_update_sql() );

		$start = time();
		while ( $websites && ( $website = MainWP_DB::fetch_object( $websites ) ) ) {
			if ( ( time() - $start ) > ( 60 * 60 * 2 ) ) {
				break;
			}

			MainWP_DB::instance()->update_website_stats( $website->id, time() );

			if ( property_exists( $website, 'sync_errors' ) && '' != $website->sync_errors ) {
				MainWP_Logger::instance()->info_for_website( $website, 'reconnect', 'Trying to reconnect' );
				try {
					if ( MainWP_Manage_Sites_View::m_reconnect_site( $website ) ) {
						MainWP_Logger::instance()->info_for_website( $website, 'reconnect', 'Reconnected successfully' );
					}
				} catch ( \Exception $e ) {
					MainWP_Logger::instance()->warning_for_website( $website, 'reconnect', $e->getMessage() );
				}
			}
			sleep( 3 );
		}
		MainWP_DB::free_result( $websites );
	}

	/**
	 * Method cron_check_websites_status()
	 *
	 * Cron job to check child sites status.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB::query()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_sql_websites_to_check_status()
	 * @uses \MainWP\Dashboard\MainWP_DB::fetch_object()
	 * @uses \MainWP\Dashboard\MainWP_DB::free_result()
	 * @uses \MainWP\Dashboard\MainWP_Logger::info()
	 * @uses \MainWP\Dashboard\MainWP_Monitoring_Handler::check_to_purge_records()
	 * @uses \MainWP\Dashboard\MainWP_Monitoring_Handler::handle_check_website()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function cron_check_websites_status() {

		$disableChecking = get_option( 'mainwp_disableSitesChecking', 1 );
		// to disable if run custom cron.
		if ( $disableChecking ) {
			return;
		}

		$running           = get_option( 'mainwp_cron_checksites_running' );
		$freq_minutes      = get_option( 'mainwp_frequencySitesChecking', 60 );
		$lasttime_to_check = get_option( 'mainwp_cron_checksites_last_timestamp', 0 ); // get last check time to continue.
		if ( ( 'yes' != $running ) && time() < $lasttime_to_check + $freq_minutes * MINUTE_IN_SECONDS ) {
			return;
		}

		if ( 'yes' != $running ) {
			MainWP_Logger::instance()->info( 'CRON :: check sites status :: starting.' );
			MainWP_Utility::update_option( 'mainwp_cron_checksites_running', 'yes' );
			if ( MainWP_Monitoring_Handler::check_to_purge_records() ) {
				return; // to run next time.
			}
		}

		/**
		 * Filter: mainwp_check_sites_status_chunk_size
		 *
		 * Filters the chunk size (number of sites) to process in status check action.
		 *
		 * @since Unknown
		 */
		$chunkSize = apply_filters( 'mainwp_check_sites_status_chunk_size', 10 );

		$websites = MainWP_DB::instance()->query( MainWP_DB::instance()->get_sql_websites_to_check_status( $lasttime_to_check, $chunkSize ) );

		// start notice.
		if ( empty( $websites ) ) {
			$plain_text = get_option( 'mainwp_daily_digest_plain_text', false );
			$this->start_notification_uptime_status( $plain_text );
			MainWP_Logger::instance()->info( 'CRON :: check sites status :: finished.' );
			MainWP_Utility::update_option( 'mainwp_cron_checksites_last_timestamp', time() );
			MainWP_Utility::update_option( 'mainwp_cron_checksites_running', false );
			return;
		}

		while ( $websites && ( $website  = MainWP_DB::fetch_object( $websites ) ) ) {
			MainWP_Monitoring_Handler::handle_check_website( $website );
		}
		MainWP_DB::free_result( $websites );
	}


	/**
	 * Method start_notification_uptime_status().
	 *
	 * Prepare uptime status notification.
	 *
	 * @param bool $plain_text Text format value.
	 *
	 * @return bool True|False
	 *
	 * @uses \MainWP\Dashboard\MainWP_Logger::info()
	 * @uses \MainWP\Dashboard\MainWP_Monitoring_Handler::notice_sites_uptime_monitoring()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_general_email_settings()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_site_email_settings()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_default_emails_fields()
	 */
	public function start_notification_uptime_status( $plain_text ) {

		$offlineSites = MainWP_DB_Common::instance()->get_websites_offline_status_to_send_notice();
		MainWP_Logger::instance()->info( 'CRON :: check sites status :: notice site http :: found ' . ( $offlineSites ? count( $offlineSites ) : 0 ) );

		if ( empty( $offlineSites ) ) {
			return false;
		}

		$admin_email = MainWP_Notification_Settings::get_general_email();

		// general uptime notification, to administrator.
		$email_settings = MainWP_Notification_Settings::get_general_email_settings( 'uptime' );
		if ( ! $email_settings['disable'] ) {
			MainWP_Monitoring_Handler::notice_sites_uptime_monitoring( $offlineSites, $admin_email, $email_settings, $plain_text );
		}

		$individual_admin_uptimeSites = array();
		// individual uptime notification.
		foreach ( $offlineSites as $site ) {
			$email_settings = MainWP_Notification_Settings::get_site_email_settings( 'uptime', $site );
			if ( $email_settings['disable'] ) {
				continue; // disabled send notification for this site.
			}
			$individual_admin_uptimeSites[] = $site;
			MainWP_Monitoring_Handler::notice_sites_uptime_monitoring( array( $site ), $admin_email, $email_settings, $plain_text );
		}

		if ( 0 < count( $individual_admin_uptimeSites ) ) {
			$admin_email_settings               = MainWP_Notification_Settings::get_default_emails_fields( 'uptime', '', true ); // get default subject and heading only.
			$admin_email_settings['disable']    = 0;
			$admin_email_settings['recipients'] = ''; // sent to admin only.
			// send to admin, all individual sites in one email.
			MainWP_Monitoring_Handler::notice_sites_uptime_monitoring( $individual_admin_uptimeSites, $admin_email, $admin_email_settings, $plain_text, true );
		}

		return true;
	}

	/**
	 * Method cron_check_websites_health()
	 *
	 * Cron job to check site health.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Logger::info()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function cron_check_websites_health() {

		// to disable if run custom cron.
		$disableChecking = get_option( 'mainwp_disableSitesHealthMonitoring', 1 );  // disabled by default.
		if ( $disableChecking ) {
			return;
		}

		MainWP_Logger::instance()->info( 'CRON :: check sites health :: starting.' );
		$plain_text = get_option( 'mainwp_daily_digest_plain_text', false );
		$this->start_notification_sites_health( $plain_text );
		MainWP_Logger::instance()->info( 'CRON :: check sites health :: finished.' );
		MainWP_Utility::update_option( 'mainwp_cron_checksiteshealth_last_timestamp', time() );
	}

	/**
	 * Method start_notification_sites_health().
	 *
	 * Prepare sites health notification.
	 *
	 * @param bool $plain_text Text format value.
	 *
	 * @return bool True|False
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB::get_websites_to_notice_health_threshold()
	 * @uses \MainWP\Dashboard\MainWP_Logger::info()
	 * @uses \MainWP\Dashboard\MainWP_Monitoring_Handler::notice_site_health_threshold()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_general_email_settings()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_site_email_settings()
	 * @uses \MainWP\Dashboard\MainWP_Notification_Settings::get_default_emails_fields()
	 */
	public function start_notification_sites_health( $plain_text ) {

		$globalThreshold = get_option( 'mainwp_sitehealthThreshold', 80 );
		$healthSites     = MainWP_DB::instance()->get_websites_to_notice_health_threshold( $globalThreshold );
		MainWP_Logger::instance()->info( 'CRON :: check sites health :: notice site health :: found ' . ( $healthSites ? count( $healthSites ) : 0 ) );

		if ( empty( $healthSites ) ) {
			return false;
		}

		$email = MainWP_Notification_Settings::get_general_email();

		// general site health notifcation.
		$email_settings = MainWP_Notification_Settings::get_general_email_settings( 'site_health' );
		if ( ! $email_settings['disable'] ) {
			// send to general email settings, one site in one email.
			MainWP_Monitoring_Handler::notice_site_health_threshold( $email_settings, $healthSites, $email, $plain_text );
		}

		$to_admin_siteHealthWebsites = array();
		// individual uptime notification.
		foreach ( $healthSites as $site ) {
			$email_settings = MainWP_Notification_Settings::get_site_email_settings( 'site_health', $site );
			if ( $email_settings['disable'] ) {
				continue; // disabled notification for this site.
			}
			$to_admin_siteHealthWebsites[] = $site;
			// send to individual email settings, one site in one email.
			MainWP_Monitoring_Handler::notice_site_health_threshold( $email_settings, array( $site ), '', $plain_text, false ); // do not send to admin for individual sending.
		}

		if ( 0 < count( $to_admin_siteHealthWebsites ) ) {
			$admin_email_settings               = MainWP_Notification_Settings::get_default_emails_fields( 'site_health', '', true ); // get default subject and heading only.
			$admin_email_settings['disable']    = 0;
			$admin_email_settings['recipients'] = ''; // sent to admin only.
			// send to admin, all individual sites in one email.
			MainWP_Monitoring_Handler::notice_site_health_threshold( $admin_email_settings, $to_admin_siteHealthWebsites, $email, $plain_text, true, true );
		}

		return true;
	}
}
