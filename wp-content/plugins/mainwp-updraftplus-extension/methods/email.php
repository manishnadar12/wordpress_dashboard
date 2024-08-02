<?php
if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed.' ); }

// Files can easily get too big for this method

class MainWP_Updraft_Plus_BackupModule_email {

	public function backup( $backup_array ) {

	}

	public function config_print() {
			?>
			<div class="ui grid field mwp_updraftplusmethod email">
                    <label class="six wide column middle aligned">
                        <h4 class="ui header">
                         <?php _e( 'Email', 'mainwp-updraftplus-extension' ); ?>
                        </h4>
                    </label>
                    <div class="ui ten wide column">
                        <?php _e( 'Note', 'mainwp-updraftplus-extension' ); ?>:
                        <div class="ui hidden fitted divider"></div>
                        <?php
                        $used = apply_filters( 'mainwp_updraftplus_email_whichaddresses', sprintf( __( "Your site's admin email address (%s) will be used.", 'mainwp-updraftplus-extension' ), get_bloginfo( 'admin_email' ) ) . ' <a href="http://updraftplus.com/shop/reporting/">' . sprintf( __( 'For more options, use the "%s" add-on.', 'mainwp-updraftplus-extension' ), __( 'Reporting', 'mainwp-updraftplus-extension' ) ) . '</a>' );
                        echo str_replace( '&gt;', '>', str_replace( '&lt;', '<', htmlspecialchars( $used . ' ' . sprintf( __( 'Be aware that mail servers tend to have size limits; typically around %s Mb; backups larger than any limits will likely not arrive.', 'mainwp-updraftplus-extension' ), '10-20' ) ) ) );
                        ?>

                    </div>
            </div>
            <?php
	}

	public function delete( $files ) {
			return true;
	}
}
