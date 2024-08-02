<?php
/*
  UpdraftPlus Addon: webdav:WebDAV Support
  Description: Allows UpdraftPlus to back up to WebDAV servers
  Version: 2.0
  Shop: /shop/webdav/
  Include: includes/PEAR
  IncludePHP: methods/stream-base.php
  Latest Change: 1.9.1
 */

/*
  To look at:
  http://sabre.io/dav/http-patch/
  http://sabre.io/dav/davclient/
  https://blog.sphere.chronosempire.org.uk/2012/11/21/webdav-and-the-http-patch-nightmare
 */

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

# In PHP 5.2, the instantiation of the class has to be after it is defined, if the class is extending a class from another file. Hence, that has been moved to the end of this file.

if ( ! class_exists( 'MainWP_Updraft_Plus_AddonStorage_viastream' ) ) {
		require_once( MAINWP_UPDRAFT_PLUS_DIR . '/methods/stream-base.php' ); }

class MainWP_Updraft_Plus_Addons_RemoteStorage_webdav extends MainWP_Updraft_Plus_AddonStorage_viastream {

	public function __construct() {
			parent::__construct( 'webdav', 'WebDAV' );
	}

	public function bootstrap() {
		return true;
	}

    public function transform_options_for_template( $url ) {
        $opts = array('url' => $url);
		$parse_url = @parse_url($url);
		if (false === $parse_url) $url = '';
		$opts['url'] = $url;
		$url_scheme = @parse_url($url, PHP_URL_SCHEME);
		if ('webdav' == $url_scheme) {
			$opts['is_webdav_protocol'] = true;
		} elseif ('webdavs' == $url_scheme) {
			$opts['is_webdavs_protocol'] = true;
		}
		$opts['user'] = urldecode(@parse_url($url, PHP_URL_USER));
		$opts['pass'] = urldecode(@parse_url($url, PHP_URL_PASS));
		$opts['host'] = urldecode(@parse_url($url, PHP_URL_HOST));
		$opts['port'] = @parse_url($url, PHP_URL_PORT);
		$opts['path'] = @parse_url($url, PHP_URL_PATH);
        return $opts;
    }

	public function config_print_middlesection( $url ) {
            $options = $this->transform_options_for_template( $url );

			?>
			<div class="ui grid field mwp_updraftplusmethod webdav">
                <label class="six wide column middle aligned">
                    <h4 class="ui header">WebDAV</h4>
                    </label>
                    <div class="ui ten wide column">
                        <?php _e( 'WebDAV URL', 'mainwp-updraftplus-extension' ); ?>:
                        <div class="ui hidden fitted divider"></div>
                        <input type="text" style="width: 432px" id="mwp_updraft_webdav_url" name="mwp_updraft_webdav_settings[url]" value="<?php echo isset($options['url']) ? esc_attr($options['url']) : ''; ?>" readonly />
                        <br>
                        <?php printf( __( 'Enter a complete URL, beginning with webdav:// or webdavs:// and including path, username, password and port as required - e.g.%s', 'mainwp-updraftplus-extension' ), 'webdavs://myuser:password@example.com/dav' ); ?>
                        <div class="ui hidden fitted divider"></div>
                        <?php _e('Protocol (SSL or not)', 'mainwp-updraftplus-extension');?>:
                        <select name="mwp_updraft_webdav_settings[webdav]"  id="mwp_updraft_webdav_webdav" class="mwp_updraft_webdav_settings" >
                            <option value="webdav://" <?php echo (isset($options['is_webdav_protocol'])) ? 'selected="selected"' : ''; ?>>webdav://</option>
                            <option value="webdavs://" <?php echo (isset($options['is_webdavs_protocol'])) ? 'selected="selected"' : ''; ?>>webdavs://</option>
                        </select>
                        <div class="ui hidden fitted divider"></div>
                        <?php _e('Username', 'mainwp-updraftplus-extension');?>:
                        <div class="ui hidden fitted divider"></div>
                        <input type="text" name="mwp_updraft_webdav_settings[user]"   id="mwp_updraft_webdav_user"  class="mwp_updraft_webdav_settings" value="<?php echo isset($options['user']) ? esc_attr($options['user']) : ''; ?>"/>
                        <div class="ui hidden fitted divider"></div>
                        <?php _e('Password', 'mainwp-updraftplus-extension');?>:
                        <div class="ui hidden fitted divider"></div>
                        <input type="password" name="mwp_updraft_webdav_settings[pass]" id="mwp_updraft_webdav_pass" class="mwp_updraft_webdav_settings"  value="<?php echo isset($options['pass']) ? esc_attr($options['pass']) : ''; ?>" />
                        <div class="ui hidden fitted divider"></div>
                        <?php _e('Host', 'mainwp-updraftplus-extension');?>:
                        <div class="ui hidden fitted divider"></div>
                        <input type="text" name="mwp_updraft_webdav_settings[host]" id="mwp_updraft_webdav_host" class="mwp_updraft_webdav_settings"  value="<?php echo isset($options['host']) ? esc_attr($options['host']) : ''; ?>"/>
                        <br>
                        <em id="updraft_webdav_host_error" style="display: none;"><?php echo __('Error:', 'mainwp-updraftplus-extension').' '.__('A host name cannot contain a slash.', 'mainwp-updraftplus-extension').' '.__('Enter any path in the field below.', 'mainwp-updraftplus-extension'); ?></em>
                        <div class="ui hidden fitted divider"></div>
                        <?php _e('Port', 'mainwp-updraftplus-extension');?>:
                        <div class="ui hidden fitted divider"></div>
                        <input type="number" step="1" min="1" max="65535" name="mwp_updraft_webdav_settings[port]" id="mwp_updraft_webdav_port" class="mwp_updraft_webdav_settings"  value="<?php echo isset($options['port']) ? esc_attr($options['port']) : ''; ?>" />
                        <br>
                        <em><?php _e('Leave this blank to use the default (80 for webdav, 443 for webdavs)', 'mainwp-updraftplus-extension');?></em>
                        <div class="ui hidden fitted divider"></div>
                        <?php _e('Path', 'mainwp-updraftplus-extension');?>:
                        <div class="ui hidden fitted divider"></div>
                        <input type="text" name="mwp_updraft_webdav_settings[path]"id="mwp_updraft_webdav_path"  class="mwp_updraft_webdav_settings" value="<?php echo isset($options['path']) ? esc_attr($options['path']) : ''; ?>"/>
                        <br/><em><?php _e('Supported tokens', 'mainwp-updraftplus-extension'); ?> %sitename%, %siteurl%</em>
                        <div class="ui hidden fitted divider"></div>
                    </div>
                </div>
        <?php
	}

	public function credentials_test() {
		if ( empty( $_POST['url'] ) ) {
				printf( __( 'Failure: No %s was given.', 'mainwp-updraftplus-extension' ), 'URL' );
				return;
		}

			$url = preg_replace( '/^http/', 'webdav', untrailingslashit( $_POST['url'] ) );
			$this->credentials_test_go( $url );
	}
}

$mainwp_updraft_plus_addons_webdav = new MainWP_Updraft_Plus_Addons_RemoteStorage_webdav;
