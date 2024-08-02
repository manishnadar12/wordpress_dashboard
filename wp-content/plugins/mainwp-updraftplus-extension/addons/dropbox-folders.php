<?php
/*
  UpdraftPlus Addon: dropbox-folders:Dropbox folders
  Description: Allows Dropbox to use sub-folders - useful if you are backing up many sites into one Dropbox
  Version: 1.3
  Shop: /shop/dropbox-folders/
  Latest Change: 1.9.14
 */

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

add_action( 'mainwp_updraftplus_dropbox_extra_config', array( 'MainWP_Updraft_Plus_Addon_DropboxFolders', 'config_print' ) );
add_filter( 'mainwp_updraftplus_dropbox_modpath', array( 'MainWP_Updraft_Plus_Addon_DropboxFolders', 'change_path' ) );

class MainWP_Updraft_Plus_Addon_DropboxFolders {

	public static function config_print() {

			$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_dropbox' );

			$folder = empty( $opts['folder'] ) ? '' : htmlspecialchars( $opts['folder'] );
			$key = empty( $opts['appkey'] ) ? '' : $opts['appkey'];
			?>
			<div class="ui grid field mwp_updraftplusmethod dropbox">
                <label class="six wide column middle aligned">
                    <h4 class="ui header">
                      <?php _e( 'Store at', 'mainwp-updraftplus-extension' ); ?>:
                    </h4>
                </label>
                <div class="ui ten wide column">
				<label><?php if ( 'dropbox:' != substr( $key, 0, 8 ) ) { echo 'apps/UpdraftPlus.Com/'; } ?><input type="text" style="width: 292px" id="updraft_dropbox_folder" name="mwp_updraft_dropbox[folder]" value="<?php echo $folder; ?>" /></label>
                <br/><em><?php _e('Supported tokens', 'mainwp-updraftplus-extension') ?>: %sitename%, %siteurl%</em>
                </div>
            </div>
        <?php
	}

	public static function change_path( $file ) {
			$opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_dropbox' );
			$folder = empty( $opts['folder'] ) ? '' : $opts['folder'];
			$dropbox_folder = trailingslashit( $folder );
			return ('/' == $dropbox_folder || './' == $dropbox_folder) ? $file : $dropbox_folder . $file;
	}
}
