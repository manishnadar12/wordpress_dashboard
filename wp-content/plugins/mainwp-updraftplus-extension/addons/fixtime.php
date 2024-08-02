<?php

/*
  UpdraftPlus Addon: fixtime:Fix Time
  Description: Allows you to specify the exact time at which backups will run
  Version: 1.3
  Shop: /shop/fix-time/
  Latest Change: 1.8.14
 */

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

$mainwp_updraft_plus_addon_fixtime = new MainWP_Updraft_Plus_AddOn_FixTime;

class MainWP_Updraft_Plus_AddOn_FixTime {

	public function __construct() {
		add_filter( 'mainwp_updraftplus_schedule_firsttime_files', array( $this, 'starttime_files' ) );
		add_filter( 'mainwp_updraftplus_schedule_firsttime_db', array( $this, 'starttime_db' ) );
		add_filter( 'mainwp_updraftplus_schedule_showfileopts', array( $this, 'schedule_showfileopts' ), 10, 2 );
		add_filter( 'mainwp_updraftplus_schedule_showdbopts', array( $this, 'schedule_showdbopts' ), 10, 2 );
		add_filter( 'mainwp_updraftplus_fixtime_ftinfo', array( $this, 'ftinfo' ) );

		// Retention rules
		add_action('mainwp_updraftplus_after_filesconfig', array($this, 'after_filesconfig'));
		add_action('mainwp_updraftplus_after_dbconfig', array($this, 'after_dbconfig'));
		//add_filter('mainwp_updraftplus_prune_or_not', array($this, 'prune_or_not'), 10, 3);
	}

	public function starttime_files( $val ) {
			return $this->compute( 'files' );
	}

	public function starttime_db( $val ) {
			return $this->compute( 'db' );
	}

	private function parse($start_time) {
		preg_match("/^(\d+):(\d+)$/", $start_time, $matches);
		if (empty($matches[1]) || !is_numeric($matches[1]) || $matches[1]>23) {
			$start_hour = 0;
		} else {
			$start_hour = (int) $matches[1];
		}
		if (empty($matches[2]) || !is_numeric($matches[2]) || $matches[1]>59) {
			$start_minute = 5;
			if ($start_minute>60) {
				$start_minute = $start_minute-60;
				$start_hour++;
				if ($start_hour>23) $start_hour =0;
			}
		} else {
			$start_minute = (int) $matches[2];
		}
		return array($start_hour, $start_minute);
	}

	private function compute( $whichtime ) {
			// Returned value should be in UNIX time.

			$unixtime_now = time();
			// Convert to date
			$now_timestring_gmt = gmdate( 'Y-m-d H:i:s', $unixtime_now );

			// Convert to blog's timezone
			$now_timestring_blogzone = get_date_from_gmt( $now_timestring_gmt, 'Y-m-d H:i:s' );

			$int_key = ('db' == $whichtime) ? '_database' : '';
			$sched = (isset( $_POST[ 'updraft_interval' . $int_key ] )) ? $_POST[ 'updraft_interval' . $int_key ] : 'manual';

			// Was a particular week-day specified?
		if ( isset( $_POST[ 'updraft_startday_' . $whichtime ] ) && ('weekly' == $sched || 'monthly' == $sched || 'fortnightly' == $sched) ) {
				// Get specified day of week in range 0-6
				$startday = min( absint( $_POST[ 'updraft_startday_' . $whichtime ] ), 6 );
				// Get today's day of week in range 0-6
				$day_today_blogzone = get_date_from_gmt( $now_timestring_gmt, 'w' );
			if ( $day_today_blogzone != $startday ) {
				if ( $startday < $day_today_blogzone ) {
						$startday += 7; }
					$new_startdate_unix = $unixtime_now + ($startday - $day_today_blogzone) * 86400;
					$now_timestring_blogzone = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $new_startdate_unix ), 'Y-m-d H:i:s' );
			}
		}

			// HH:MM, in blog time zone
			// This function is only called from the options validator, so we don't read the current option
			//$start_time = MainWP_Updraft_Plus_Options::get_updraft_option('updraft_starttime_'.$whichtime);
			$start_time = (isset( $_POST[ 'updraft_starttime_' . $whichtime ] )) ? $_POST[ 'updraft_starttime_' . $whichtime ] : '00:00';

			list ($start_hour, $start_minute) = $this->parse( $start_time );

			// Now, convert the start time HH:MM from blog time to UNIX time
			$start_time_unix = get_gmt_from_date( substr( $now_timestring_blogzone, 0, 11 ) . sprintf( '%02d', $start_hour ) . ':' . sprintf( '%02d', $start_minute ) . ':00', 'U' );

			// That may have already passed for today
		if ( $start_time_unix < time() ) {
			if ( 'weekly' == $sched || 'monthly' == $sched || 'fortnightly' == $sched ) {
					$start_time_unix = $start_time_unix + 86400 * 7;
			} else {
					$start_time_unix = $start_time_unix + 86400;
			}
		}

			return $start_time_unix;
	}

//	private function day_selector( $id ) {
//
//        global $wp_locale;
//
//        $opt = MainWP_Updraft_Plus_Options::get_updraft_option( $id, 0 );
//
//        $day_selector = '<select name="mwp_' . $id . '" id="' . $id . '">';
//        for ( $day_index = 0; $day_index <= 6; $day_index++ ) :
//            $selected = ($opt == $day_index) ? 'selected="selected"' : '';
//            $day_selector .= "\n\t<option value='" . esc_attr( $day_index ) . "' $selected>" . $wp_locale->get_weekday( $day_index ) . '</option>';
//        endfor;
//
//        $day_selector .= '</select>';
//
//        return $day_selector;
//
//	}

//	public function config_startfile( $disp, $start_time ) {
//		//                global $mainwp_itsec_globals;
////		    $start_time = MainWP_Updraft_Plus_Options::get_updraft_option('updraft_starttime_files');
//			list ($start_hour, $start_minute) = $this->parse( $start_time );
//			return __( 'starting from next time it is', 'mainwp-updraftplus-extension' ) . ' ' . $this->day_selector( 'updraft_startday_files' ) . '<input title="' . __( 'Enter in format HH:MM (e.g. 14:22).', 'mainwp-updraftplus-extension' ) . ' ' . htmlspecialchars( __( 'The time zone used is that from your WordPress settings, in Settings -> General.', 'mainwp-updraftplus-extension' ) ) . '" type="text" style="width: 48px;" maxlength="5" name="mwp_updraft_starttime_files" value="' . sprintf( '%02d', $start_hour ) . ':' . sprintf( '%02d', $start_minute ) . '">';
//	}
//
//	public function config_startdb() {
//
//			$start_time = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_starttime_db' );
//			list ($start_hour, $start_minute) = $this->parse( $start_time );
//
//			return __( 'starting from next time it is', 'mainwp-updraftplus-extension' ) . ' ' . $this->day_selector( 'updraft_startday_db' ) . '<input title="' . __( 'Enter in format HH:MM (e.g. 14:22).', 'mainwp-updraftplus-extension' ) . ' ' . htmlspecialchars( __( 'The time zone used is that from your WordPress settings, in Settings -> General.', 'mainwp-updraftplus-extension' ) ) . '" type="text" style="width: 48px;" maxlength="5" name="mwp_updraft_starttime_db" value="' . sprintf( '%02d', $start_hour ) . ':' . sprintf( '%02d', $start_minute ) . '">';
//	}


    private function day_selector($id, $selected_interval = 'manual') {
		global $wp_locale;

		$day_selector = '<select name="mwp_'.$id.'" id="'.$id.'">';

		$opt = MainWP_Updraft_Plus_Options::get_updraft_option($id, 0);

		$start_from = ('monthly' == $selected_interval) ? 1 : 0;
		$go_to = ('monthly' == $selected_interval) ? 28 : 6;

		for ($day_index = $start_from; $day_index <= $go_to; $day_index++) :
			$selected = ($opt == $day_index) ? 'selected="selected"' : '';
			$day_selector .= "\n\t<option value='" . $day_index . "' $selected>";
			$day_selector .= ('monthly' == $selected_interval) ? $day_index : $wp_locale->get_weekday($day_index);
			$day_selector .= '</option>';
		endfor;
		$day_selector .= '</select>';
		return $day_selector;
	}


    public function starting_widget($start_hour, $start_minute, $day_selector_id, $time_selector_id, $selected_interval = 'manual') {
		return __('starting from next time it is', 'updraftplus').' '.$this->day_selector($day_selector_id, $selected_interval).'<input title="'.__('Enter in format HH:MM (e.g. 14:22).', 'updraftplus').' '.htmlspecialchars(__('The time zone used is that from your WordPress settings, in Settings -> General.', 'updraftplus')).'" type="text" class="fix-time" maxlength="5" name="mwp_'.$time_selector_id.'" value="'.sprintf('%02d', $start_hour).':'.sprintf('%02d', $start_minute).'">';
	}

	public function schedule_showdbopts($disp, $selected_interval) {
		$start_time = MainWP_Updraft_Plus_Options::get_updraft_option('updraft_starttime_db');
		list ($start_hour, $start_minute) = $this->parse($start_time);
		return $this->starting_widget($start_hour, $start_minute, 'updraft_startday_db', 'updraft_starttime_db', $selected_interval);
	}

	public function schedule_showfileopts($disp, $selected_interval) {
		$start_time = MainWP_Updraft_Plus_Options::get_updraft_option('updraft_starttime_files');
		list ($start_hour, $start_minute) = $this->parse($start_time);
		return $this->starting_widget($start_hour, $start_minute, 'updraft_startday_files', 'updraft_starttime_files', $selected_interval);
	}

	public function ftinfo() {
			return '';
	}

	public function after_dbconfig() {
		echo '<div class="ui clearing hidden divider"></div><div id="updraft_retain_db_rules"></div><div><a href="#" class="ui green basic mini button" id="updraft_retain_db_addnew">'.__('Add Additional Retention Rule', 'mainwp-updraftplus-extension').'</a></div>';
	}

	public function after_filesconfig() {
		add_action('admin_footer', array($this, 'admin_footer_extraretain_js'));
		echo '<div class="ui clearing hidden divider"></div><div id="updraft_retain_files_rules"></div><div><a href="#" id="updraft_retain_files_addnew" class="ui mini green basic button">'.__('Add Additional Retention Rule', 'mainwp-updraftplus-extension').'</a></div>';
	}

	public function soonest_first($a, $b) {
		if (!is_array($a)) {
			if (!is_array($b)) return 0;
			return 1;
		} elseif (!is_array($b)) {
			return -1;
		}
		$after_howmany_a = isset($a['after-howmany']) ? absint($a['after-howmany']) : 0;
		$after_howmany_b = isset($b['after-howmany']) ? absint($b['after-howmany']) : 0;
		$after_period_a = isset($a['after-period']) ? absint($a['after-period']) : 0;
		$after_period_b = isset($b['after-period']) ? absint($b['after-period']) : 0;
		$after_a = $after_howmany_a * $after_period_a;
		$after_b = $after_howmany_b * $after_period_b;
		if ($after_a == $after_b) return 0;
		return ($after_a < $after_b) ? -1 : 1;
	}

	public function admin_footer_extraretain_js() {
		$extra_rules = MainWP_Updraft_Plus_Options::get_updraft_option('updraft_retain_extrarules');
		if (!is_array($extra_rules)) $extra_rules = array();
		?>
		<script>
		jQuery(document).ready(function() {
			var db_index = 0;
			var files_index = 0;
			<?php
				if (isset($extra_rules['files']) && is_array($extra_rules['files'])) {
					$this->javascript_print_retain_rules($extra_rules['files'], 'files');
				}
				if (isset($extra_rules['db']) && is_array($extra_rules['db'])) {
					$this->javascript_print_retain_rules($extra_rules['db'], 'db');
				}
			?>
			jQuery('#updraft_retain_db_addnew').click(function(e) {
				e.preventDefault();
				add_rule('db', db_index, 12, 2419200, 1, 2419200);
			});
			jQuery('#updraft_retain_files_addnew').click(function(e) {
				e.preventDefault();
				add_rule('files', files_index, 12, 2419200, 1, 2419200);
			});
			jQuery('#updraft_retain_db_rules, #updraft_retain_files_rules').on('click', '.maiwnp_updraft_retain_rules_delete', function() {
				jQuery(this).parent('.updraft_retain_rules').slideUp(function() {jQuery(this).remove();});
			});
			function add_rule(type, index, howmany_after, period_after, howmany_every, period_every) {
				var selector = 'updraft_retain_'+type+'_rules';
				if ('db' == type) {
					db_index = index + 1;
				} else {
					files_index = index + 1;
				}
				jQuery('#'+selector).append(
					'<div class="updraft_retain_rules '+selector+'_entry ui secondary segment">'+
					mwp_updraftlion.forbackupsolderthan+'<div class="ui hidden fitted divider"></div>'+rule_period_selector(type, index, 'after', howmany_after, period_after)+' keep no more than 1 backup every '+rule_period_selector(type, index, 'every', howmany_every, period_every)+
					'<div class="ui hidden fitted divider"></div><span title="'+mwp_updraftlion.deletebutton+'" class="maiwnp_updraft_retain_rules_delete ui mini button">Remove Rule</span></div><div class="ui hidden divider"></div>'
				)
			}
			function rule_period_selector(type, index, which, howmany_value, period) {
				var nameprefix = "mwp_updraft_retain_extrarules["+type+"]["+index+"]["+which+"-";
				var ret = '<input type="number" min="1" step="1" name="'+nameprefix+'howmany]" value="'+howmany_value+'"> \
				<select name="'+nameprefix+'period]">\
				<option value="3600"';
				if (period == 3600) { ret += ' selected="selected"'; }
				ret += '>'+mwp_updraftlion.hours+'</option>\
				<option value="86400"';
				if (period == 86400) { ret += ' selected="selected"'; }
				ret += '>'+mwp_updraftlion.days+'</option>\
				<option value="2419200"';
				if (period == 2419200) { ret += ' selected="selected"'; }
				ret += '>'+mwp_updraftlion.weeks+'</option>\
				</select>';
				return ret;
			}
		});
		</script>
		<?php
	}

	private function javascript_print_retain_rules($extra_rules, $type) {
		if (!is_array($extra_rules)) return;
		uasort($extra_rules, array($this, 'soonest_first'));
		foreach ($extra_rules as $i => $rule) {
			if (!is_array($rule) || !isset($rule['after-howmany']) || !isset($rule['after-period']) || !isset($rule['every-howmany']) || !isset($rule['every-period'])) continue;
			$after_howmany = $rule['after-howmany'];
			$after_period = $rule['after-period'];
			// Best not to just drop the rule if it is invalid
			if (!is_numeric($after_howmany) || $after_howmany < 0) continue;
			if ($after_period <3600) $after_period = 3600;
			if ($after_period != 3600 && $after_period != 86400 && $after_period != 2419200) continue;
			$every_howmany = $rule['every-howmany'];
			$every_period = $rule['every-period'];
			// Best not to just drop the rule if it is invalid
			if (!is_numeric($every_howmany) || $every_howmany < 1) continue;
			if ($every_period <3600) $every_period = 3600;
			if ($every_period != 3600 && $every_period != 86400 && $every_period != 2419200) continue;
			echo "add_rule('$type', $i, $after_howmany, $after_period, $every_howmany, $every_period);\n";
		}
	}

}

?>
