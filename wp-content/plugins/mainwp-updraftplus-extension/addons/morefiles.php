<?php
/*
  UpdraftPlus Addon: morefiles:Back up more files, including WordPress core
  Description: Creates a backup of WordPress core (including everything in that directory WordPress is in), and any other file/directory you specify too.
  Version: 1.9
  Shop: /shop/more-files/
  Latest Change: 1.9.18
 */

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

$mainwp_updraft_plus_addons_morefiles = new MainWP_Updraft_Plus_Addons_MoreFiles;

class MainWP_Updraft_Plus_Addons_MoreFiles {

	private $wpcore_foundyet = 0;
	private $more_paths = array();

	public function __construct() {
			add_filter( 'mainwp_updraft_backupable_file_entities', array( $this, 'backupable_file_entities' ), 10, 2 );
			add_filter( 'mainwp_updraft_backupable_file_entities_final', array( $this, 'backupable_file_entities_final' ), 10, 2 );

			add_filter( 'mainwp_updraftplus_restore_movein_wpcore', array( $this, 'restore_movein_wpcore' ), 10, 2 );
			add_filter( 'mainwp_updraftplus_backup_makezip_wpcore', array( $this, 'backup_makezip_wpcore' ), 10, 3 );
			add_filter( 'mainwp_updraftplus_backup_makezip_more', array( $this, 'backup_makezip_more' ), 10, 3 );

			add_filter( 'mainwp_updraftplus_defaultoption_include_more', '__return_false' );
			add_filter( 'mainwp_updraftplus_defaultoption_include_wpcore', '__return_false' );

			add_filter( 'mainwp_updraftplus_admin_directories_description', array( $this, 'admin_directories_description' ) );

			add_filter( 'mainwp_updraftplus_fileinfo_more', array( $this, 'fileinfo_more' ), 10, 2 );

			add_action( 'mainwp_updraftplus_config_option_include_more', array( $this, 'config_option_include_more' ) );
			add_action( 'mainwp_updraftplus_config_option_include_wpcore', array( $this, 'config_option_include_wpcore' ) );

			add_action( 'mainwp_updraftplus_restore_form_wpcore', array( $this, 'restore_form_wpcore' ) );

			add_filter( 'mainwp_updraftplus_include_wpcore_exclude', array( $this, 'include_wpcore_exclude' ) );
	}

	public function fileinfo_more( $data, $ind ) {
		
	}

	public function restore_form_wpcore() {
			?>
			<div id="updraft_restorer_wpcoreoptions" style="display:none; padding:12px; margin: 8px 0 4px; border: dashed 1px;"><h4 style="margin: 0px 0px 6px; padding:0px;"><?php echo sprintf( __( '%s restoration options:', 'mainwp-updraftplus-extension' ), __( 'WordPress Core', 'mainwp-updraftplus-extension' ) ); ?></h4>

				<?php
				echo '<input name="updraft_restorer_wpcore_includewpconfig" id="updraft_restorer_wpcore_includewpconfig" type="checkbox" value="1"><label for="updraft_restorer_wpcore_includewpconfig"> ' . __( 'Over-write wp-config.php', 'mainwp-updraftplus-extension' ) . '</label> <a href="http://updraftplus.com/faqs/when-i-restore-wordpress-core-should-i-include-wp-config-php-in-the-restoration/">' . __( '(learn more about this significant option)', 'mainwp-updraftplus-extension' ) . '</a>';
				?>

				<script>
						jQuery('#updraft_restore_wpcore').change(function () {
							if (jQuery('#updraft_restore_wpcore').is(':checked')) {
								jQuery('#updraft_restorer_wpcoreoptions').slideDown();
							} else {
								jQuery('#updraft_restorer_wpcoreoptions').slideUp();
							}
						});
				</script>

				</div>
				<?php
	}

	public function admin_directories_description() {
			return '<div style="float: left; clear: both; padding-top: 3px;">' . __( 'The above files comprise everything in a WordPress installation.', 'mainwp-updraftplus-extension' ) . '</div>';
	}

	public function backupable_file_entities( $arr, $full_info ) {
		if ( $full_info ) {
				$arr['wpcore'] = array(
					'path' => untrailingslashit( ABSPATH ),
					'description' => apply_filters( 'mainwp_updraft_wpcore_description', __( 'WordPress core (including any additions to your WordPress root directory)', 'mainwp-updraftplus-extension' ) ),
					'htmltitle' => sprintf( __( 'WordPress root directory server path: %s', 'mainwp-updraftplus-extension' ), ABSPATH ),
				);
		} else {
				$arr['wpcore'] = untrailingslashit( ABSPATH );
		}
			return $arr;
	}

	public function checkzip_wpcore( $zipfile, &$mess, &$warn, &$err ) {
		
	}

	public function checkzip_end_wpcore( &$mess, &$warn, &$err ) {
		
	}

	public function backupable_file_entities_final( $arr, $full_info ) {
			$path = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_include_more_path' );
		if ( is_array( $path ) ) {
				$path = array_map( 'untrailingslashit', $path );
			if ( 1 == count( $path ) ) {
					$path = array_shift( $path ); }
		} else {
				$path = untrailingslashit( $path );
		}
		if ( $full_info ) {
				$arr['more'] = array(
					'path' => $path,
					'description' => __( 'Any other file/directory on your server that you wish to back up', 'mainwp-updraftplus-extension' ),
					'shortdescription' => __( 'More Files', 'mainwp-updraftplus-extension' ),
					'restorable' => false,
				);
		} else {
				$arr['more'] = $path;
		}
			return $arr;
	}

	public function config_option_include_more() {

			$display = (MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_include_more' )) ? '' : 'style="display:none;"';

			$paths = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_include_more_path' );
		if ( ! is_array( $paths ) ) {
				$paths = array( $paths ); }

			echo "<div id=\"updraft_include_more_options\" $display><p>";

			echo __( 'If you are not sure what this option is for, then you will not want it, and should turn it off.', 'mainwp-updraftplus-extension' ) . ' ' . __( 'If using it, enter an absolute path (it is not relative to your WordPress install).', 'mainwp-updraftplus-extension' );

			echo ' ' . __( 'Be careful what you enter - if you enter / then it really will try to create a zip containing your entire webserver.', 'mainwp-updraftplus-extension' );

			echo '</p>';

			echo '<div id="updraft_include_more_paths">';
			$maxind = 1;
		if ( empty( $paths ) ) {
				$paths = array( '' ); }
		foreach ( $paths as $ind => $path ) {
				$maxind = max( $ind, $maxind );
					echo '<div class="updraftplus-morefiles-row" style="float: left; clear: left;"><label for="updraft_include_more_path_' . $ind . '">' . __( 'Enter the directory:', 'mainwp-updraftplus-extension' ) . '</label>';
					echo '<input type="text" id="updraft_include_more_path_' . $ind . '" name="mwp_updraft_include_more_path[]" size="54" value="' . htmlspecialchars( $path ) . '" /> <span title="' . __( 'Remove', 'mainwp-updraftplus-extension' ) . '" class="updraftplus-morefiles-row-delete">X</span>';
					echo '</div>';
		}

			echo '</div>';
			echo '<div style="clear:left; float:left;"><a id="updraft_include_more_paths_another" href="#updraft_include_more_paths">' . __( 'Add another...', 'mainwp-updraftplus-extension' ) . '</a></div>';

			echo '</div>';

			$maxind++;
			$enter = esc_js( __( 'Enter the directory:', 'mainwp-updraftplus-extension' ) );
			$remove = esc_js( __( 'Remove', 'mainwp-updraftplus-extension' ) );
			echo <<<ENDHERE
		<script>
			jQuery(document).ready(function() {
				var updraftplus_morefiles_lastind = $maxind;
				jQuery('#mwp_updraft_include_more').click(function() {
					if (jQuery('#mwp_updraft_include_more').is(':checked')) {
						jQuery('#updraft_include_more_options').slideDown();
					} else {
						jQuery('#updraft_include_more_options').slideUp();
					}
				});
				jQuery('#updraft_include_more_paths_another').click(function(e) {
					e.preventDefault();
					updraftplus_morefiles_lastind++;
					jQuery('#updraft_include_more_paths').append('<div class="updraftplus-morefiles-row" style="float: left; clear: left;"><label for="updraft_include_more_path_'+updraftplus_morefiles_lastind+'">$enter</label><input type="text" id="updraft_include_more_path_'+updraftplus_morefiles_lastind+'" name="mwp_updraft_include_more_path[]" size="54" value="" /> <span title="$remove" class="updraftplus-morefiles-row-delete">X</span></div>');
				});
				jQuery('#updraft_include_more_options').on('click', '.updraftplus-morefiles-row-delete', function(e) {
					e.preventDefault();
					var prow = jQuery(this).parent('.updraftplus-morefiles-row');
					jQuery(prow).slideUp().delay(400).remove();
				});
			});
		</script>
ENDHERE;
	}

	public function config_option_include_wpcore() {

			$display = (MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_include_wpcore' )) ? '' : 'style="display:none;"';

			echo "<div id=\"updraft_include_wpcore_exclude\" $display>";

			echo '<label for="updraft_include_wpcore_exclude">' . __( 'Exclude these:', 'mainwp-updraftplus-extension' ) . '</label>';

			echo '<input title="' . __( 'If entering multiple files/directories, then separate them with commas. For entities at the top level, you can use a * at the start or end of the entry as a wildcard.', 'mainwp-updraftplus-extension' ) . '" type="text" id="updraft_include_wpcore_exclude" name="mwp_updraft_include_wpcore_exclude" size="54" value="' . htmlspecialchars( MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_include_wpcore_exclude' ) ) . '" />';

			echo '<br>';

			echo '</div>';

			echo <<<ENDHERE
		<script>
			jQuery(document).ready(function() {
				jQuery('#mwp_updraft_include_wpcore').click(function() {
					if (jQuery('#mwp_updraft_include_wpcore').is(':checked')) {
						jQuery('#updraft_include_wpcore_exclude').slideDown();
					} else {
						jQuery('#updraft_include_wpcore_exclude').slideUp();
					}
				});
			});
		</script>
ENDHERE;
	}

	public function backup_more_dirlist( $whichdirs ) {
			
	}

		# $whichdir can be an array

	public function backup_makezip_more( $whichdirs, $backup_file_basename, $index ) {

	}

	public function include_wpcore_exclude( $exclude ) {
			return explode( ',', MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_include_wpcore_exclude', '' ) );
	}

	public function backup_wpcore_dirlist( $whichdir, $logit = false ) {

		
	}

		// $whichdir will equal untrailingslashit(ABSPATH) (is ultimately sourced from our backupable_file_entities filter callback)
	public function backup_makezip_wpcore( $whichdir, $backup_file_basename, $index ) {
			
	}

		// $wp_dir is trailingslashit($wp_filesystem->abspath())
		// Must only use $wp_filesystem methods
		// $working_dir is the directory which contains the backup entity/ies. It is a child of wp-content/upgrade
		// We need to make sure we do not over-write any entities that are restored elsewhere. i.e. Don't touch plugins/themes etc. - but use backupable_file_entities in order to be fully compatible, but with an additional over-ride of touching nothing inside WP_CONTENT_DIR. Can recycle code from the 'others' handling to assist with this.
	public function restore_movein_wpcore( $working_dir, $wp_dir ) {

		
	}
}
