<?php
/*
  UpdraftPlus Addon: s3-enhanced:Amazon S3, enhanced
  Description: Adds enhanced capabilities for Amazon S3 users
  Version: 1.1
  Shop: /shop/s3-enhanced/
  Latest Change: 1.9.51
 */

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

$mainwp_updraft_plus_addon_s3_enhanced = new MainWP_Updraft_Plus_Addon_S3_Enhanced;

class MainWP_Updraft_Plus_Addon_S3_Enhanced {

	public function __construct() {
			add_action( 'mainwp_updraft_s3_extra_storage_options', array( $this, 'extra_storage_options' ) );
			add_filter( 'mainwp_updraft_s3_storageclass', array( $this, 'storageclass' ), 10, 3 );
	}

	public function storageclass( $class, $s3, $opts ) {
			return ((is_a( $s3, 'MainWP_Updraft_Plus_S3' ) || is_a( $s3, 'MainWP_Updraft_Plus_S3_Compat' )) && is_array( $opts ) && ! empty( $opts['rrs'] )) ? 'REDUCED_REDUNDANCY' : $class;
	}

	public function extra_storage_options( $opts ) {
            ?>
            <div class="ui grid field mwp_updraftplusmethod s3">
                <label class="six wide column middle aligned">

                </label>
                <div class="ui ten wide column">
                    <?php _e('Storage class', 'mainwp-updraftplus-extension');?>:<br><a href="https://aws.amazon.com/s3/storage-classes/"><em><?php _e('(Read more)', 'updraftplus');?></em></a>
                    <div class="ui hidden fitted divider"></div>
                    <?php
                    $rrs = empty($opts['rrs']) ? 'STANDARD' : $opts['rrs'];
                    if (!empty($rrs) && 'STANDARD' != $rrs && 'STANDARD_IA' != $rrs) $rrs = 'REDUCED_REDUNDANCY';
                    ?>
                    <select name="mwp_updraft_s3[rrs]" data-updraft_settings_test="rrs">
                            <option value="STANDARD" <?php if ('STANDARD' == $rrs) echo ' selected="selected"';?>><?php _e('Standard', 'mainwp-updraftplus-extension');?></option>
                            <option value="STANDARD_IA" <?php if ('STANDARD_IA' == $rrs) echo ' selected="selected"';?>><?php _e('Standard (infrequent access)', 'mainwp-updraftplus-extension');?></option>
                            <option value="REDUCED_REDUNDANCY" <?php if ('REDUCED_REDUNDANCY' == $rrs) echo ' selected="selected"';?>><?php _e('Reduced redundancy', 'mainwp-updraftplus-extension');?></option>
                    </select>
                    <div class="ui hidden fitted divider"></div>
                    <?php _e('Server-side encryption', 'mainwp-updraftplus-extension');?>:<br><a href="https://aws.amazon.com/blogs/aws/new-amazon-s3-server-side-encryption/"><em><?php _e('(Read more)', 'mainwp-updraftplus-extension');?></em></a>
                    <div class="ui hidden fitted divider"></div>
                    <input data-updraft_settings_test="server_side_encryption" title="<?php esc_attr_e(__("Check this box to use Amazon's server-side encryption", 'mainwp-updraftplus-extension')); ?>" type="checkbox" name="mwp_updraft_s3[server_side_encryption]" id="updraft_s3_server_side_encryption" value="1" <?php if (!empty($opts['server_side_encryption'])) echo 'checked="checked"';?>/>

                </div>
            </div>
            <?php
	}
}
