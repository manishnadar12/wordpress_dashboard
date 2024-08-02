<?php
/*
  UpdraftPlus Addon: lockadmin:Password-protect the UpdraftPlus Settings Screen
  Description: Provides the ability to lock the UpdraftPlus settings with a password
  Version: 1.1
  Shop: /shop/lockadmin/
  Latest Change: 1.9.43
 */

if ( ! defined( 'MAINWP_UPDRAFT_PLUS_DIR' ) ) {
		die( 'No direct access allowed' ); }

if ( defined( 'UPDRAFTPLUS_NOADMINLOCK' ) && UPDRAFTPLUS_NOADMINLOCK ) {
		return; }

$mainwp_updraft_plus_addon_moredatabase = new MainWP_Updraft_Plus_Addon_LockAdmin;

class MainWP_Updraft_Plus_Addon_LockAdmin {

	private $correct_password_supplied = null;
	private $default_support_url = 'https://updraftplus.com/faqs/locked-updraftplus-settings-page-forgotten-password-unlock/';

	public function __construct() {
			add_filter( 'mainwp_updraftplus_settings_page_render', array( $this, 'settings_page_render' ) );
			add_action( 'mainwp_updraftplus_settings_page_render_abort', array( $this, 'settings_page_render_abort' ) );
		if ( ( ! empty( $_POST['updraft_unlockadmin_session_length'] ) || ! empty( $_POST['updraft_unlockadmin_password'] )) && ! empty( $_POST['nonce'] ) ) {
				add_action( 'admin_init', array( $this, 'admin_init' ) ); }
			add_action( 'mainwp_updraftplus_debugtools_dashboard', array( $this, 'debugtools_dashboard' ) );
	}

	private function check_user_cookie( $password ) {
		if ( empty( $password ) ) {
				return true; }
			// Value in seconds
			$session_length = $this->opts['session_length'];
		if ( ! $session_length ) {
				$session_length = 86400; }

			// A lock has been set. Has the user passed the test?
		if ( empty( $_COOKIE['updraft_unlockadmin'] ) ) {
				return false; }

			// Cookie in correct format?
		if ( ! preg_match( '/^(\d+):(.*)$/', $_COOKIE['updraft_unlockadmin'], $matches ) ) {
				return false; }

			$cookie_time = $matches[1]; # The time when the session began
			$cookie_hash = $matches[2];

			$time_now = time();

			# Cookie is older than session length
		if ( $time_now > $cookie_time + $session_length ) {
				return false; }

			$cookie_session_began = $cookie_time - ($cookie_time % $session_length);

			$user = wp_get_current_user();
		if ( ! is_a( $user, 'WP_User' ) ) {
				return false; }

			// The cookie relies on the user ID, password and session time. So, someone stealing the cookie can't use it forever. They need the password to generate valid cookies.
			$correct_hash = hash( 'sha256', $user->ID . '-' . $password . '-' . $cookie_session_began );

		if ( $correct_hash != $cookie_hash ) {
				return false; }

			return true;
	}

	private function get_opts() {
			$this->opts = MainWP_Updraft_Plus_Options::get_updraft_option( 'updraft_adminlocking' );
		if ( ! is_array( $this->opts ) ) {
				$this->opts = array(); }
		if ( ! isset( $this->opts['password'] ) ) {
				$this->opts['password'] = ''; }
		if ( ! isset( $this->opts['session_length'] ) ) {
				$this->opts['session_length'] = 3600; }
		if ( ! isset( $this->opts['support_url'] ) ) {
				$this->opts['support_url'] = ''; }
	}

	public function admin_init() {
		if ( (empty( $_POST['updraft_unlockadmin_session_length'] ) && empty( $_POST['updraft_unlockadmin_password'] )) || empty( $_POST['nonce'] ) ) {
				return; }
		if ( ! wp_verify_nonce( $_POST['nonce'], 'updraftplus-unlockadmin-nonce' ) ) {
				return; }
			$user = wp_get_current_user();
		if ( ! is_a( $user, 'WP_User' ) ) {
				return; }
			$this->get_opts();
		if ( ! empty( $_POST['updraft_unlockadmin_session_length'] ) && isset( $_POST['updraft_unlockadmin_oldpassword'] ) && $_POST['updraft_unlockadmin_oldpassword'] == $this->opts['password'] ) {
				$this->old_password = $this->opts['password'];
				$this->opts['password'] = $_POST['updraft_unlockadmin_password'];
				$this->opts['support_url'] = $_POST['updraft_unlockadmin_support_url'];
				$this->opts['session_length'] = (int) $_POST['updraft_unlockadmin_session_length'];
				MainWP_Updraft_Plus_Options::update_updraft_option( 'updraft_adminlocking', $this->opts );
				$this->password_length = strlen( $this->opts['password'] );
				add_action( 'all_admin_notices', array( $this, 'show_admin_warning_passwordset' ) );
		}
			// Note: this code also fires when the user sets a new password (because we don't want to immediately lock them)
			$password = $this->opts['password'];
		if ( (string) $_POST['updraft_unlockadmin_password'] === $password ) {
				$session_length = (int) $this->opts['session_length'];
			if ( $session_length < 1 ) {
					$session_length = 86400; }
					// The cookie relies on the user ID, password and session time. So, someone stealing the cookie can't use it forever. They need the password to generate valid cookies.
					$time_now = time();
					$expire = $time_now + $session_length;
					$cookie_session_began = $time_now - ($time_now % $session_length);
					$correct_hash = hash( 'sha256', $user->ID . '-' . $password . '-' . $cookie_session_began );
					$secure = apply_filters( 'secure_auth_cookie', is_ssl(), $user->ID );
					setcookie( 'updraft_unlockadmin', $cookie_session_began . ':' . $correct_hash, $expire, ADMIN_COOKIE_PATH, COOKIE_DOMAIN, $secure, true );
					$this->correct_password_supplied = true;
		} else {
				$this->correct_password_supplied = false;
		}
	}

	public function show_admin_warning_passwordset() {
			$msg = '<strong>';
		if ( strlen( $this->old_password ) > 0 && $this->password_length == 0 ) {
				$msg .= __( 'The admin password has now been removed.', 'mainwp-updraftplus-extension' );
		} elseif ( strlen( $this->old_password ) == 0 && $this->password_length > 0 ) {
				$msg .= __( 'An admin password has been set.', 'mainwp-updraftplus-extension' );
		} elseif ( $this->old_password !== $this->opts['password'] ) {
				$msg .= __( 'The admin password has been changed.', 'mainwp-updraftplus-extension' );
		} else {
				$msg .= __( 'Settings saved.' );
		}
			$msg .= '</strong>';
			global $mainwp_updraftplus_admin;
			$mainwp_updraftplus_admin->show_admin_warning( $msg );
	}

	public function settings_page_render( $go ) {
		if ( ! $go ) {
				return $go; }
		if ( $this->correct_password_supplied ) {
				return true; }
			$this->get_opts();
			$password = $this->opts['password'];
		if ( $this->check_user_cookie( $password ) ) {
				return $go; }
			return false;
	}

	public function debugtools_dashboard() {
			global $mainwp_updraftplus_admin;
			$this->get_opts();
			?>
			<h3><?php echo __( 'Lock access to the UpdraftPlus settings page', 'mainwp-updraftplus-extension' ); ?></h3>
				<p><em><a href="https://updraftplus.com/lock-updraftplus-settings/"><?php _e( 'Read more about how this works...', 'mainwp-updraftplus-extension' ); ?></a></em></p>
				<form method="post" onsubmit="if (jQuery('#updraft_unlockadmin_password').val() != '') {
											return(confirm('<?php echo esc_js( __( 'Please make sure that you have made a note of the password!', 'mainwp-updraftplus-extension' ) ); ?>'));
										} else {
											return true;
										}">
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'updraftplus-unlockadmin-nonce' ); ?>">
				<input type="hidden" name="page" value="updraftplus">
				<input type="hidden" name="tab" value="expert">
				<input type="hidden" name="updraft_unlockadmin_oldpassword" value="<?php echo esc_attr( $this->opts['password'] ); ?>">
				<table>
					<?php
					echo $mainwp_updraftplus_admin->settings_debugrow( __( 'Password', 'mainwp-updraftplus-extension' ) . ':', '<input type="text" id="updraft_unlockadmin_password" name="updraft_unlockadmin_password" value="' . esc_attr( $this->opts['password'] ) . '" style="width:230px;">' );

					$session_lengths = array(
						'3600' => __( '1 hour', 'mainwp-updraftplus-extension' ),
						'10800' => sprintf( __( '%s hours', 'mainwp-updraftplus-extension' ), 3 ),
						'86400' => sprintf( __( '%s hours', 'mainwp-updraftplus-extension' ), 24 ),
						'604800' => __( '1 week', 'mainwp-updraftplus-extension' ),
						'2419200' => sprintf( __( '%s weeks', 'mainwp-updraftplus-extension' ), 4 ),
						'31449600' => sprintf( __( '%s weeks', 'mainwp-updraftplus-extension' ), 52 ),
					);

					$session_options = '';
					foreach ( $session_lengths as $length => $text ) {
							$session_options .= "<option value=\"$length\"" . (($this->opts['session_length'] == $length) ? ' selected="selected"' : '') . '>' . htmlspecialchars( $text ) . "</option>\n";
					}

					echo $mainwp_updraftplus_admin->settings_debugrow( __( 'Require password again after', 'mainwp-updraftplus-extension' ) . ':', '<select name="updraft_unlockadmin_session_length" style="width:230px;">' . $session_options . '</select>' );

					echo $mainwp_updraftplus_admin->settings_debugrow( __( 'Support URL', 'mainwp-updraftplus-extension' ) . ':', '<input name="updraft_unlockadmin_support_url" type="' . apply_filters( 'mainwp_updraftplus_admin_secret_field_type', 'text' ) . '" value="' . esc_attr( $this->opts['support_url'] ) . '" style="width:230px;"><br><em>' . __( 'Anyone seeing the lock screen will be shown this URL for support - enter a website address or an email address.', 'mainwp-updraftplus-extension' ) . ' <a href="' . $this->default_support_url . '">' . __( 'Otherwise, the default link will be shown.', 'mainwp-updraftplus-extension' ) . '</a></em>' );
					?>
					<?php echo $mainwp_updraftplus_admin->settings_debugrow( '', '<input class="button-primary" type="submit" value="' . esc_attr( __( 'Change Lock Settings', 'mainwp-updraftplus-extension' ) ) . '">' ); ?>
					</table>
				</form>
				<?php
	}

	public function settings_page_render_abort() {
			global $mainwp_updraftplus_admin;
			$mainwp_updraftplus_admin->settings_header();
			?>
			<style type="text/css">
				#updraft-lock-area {
					border: 4px dashed #ddd;
					height: 320px;
					margin: 36px 0 0 20px;
					width: 650px;
				}
				#updraft-lock-area p {
					font-size: 16px;
					text-align: center;
				}

			</style>
			<div id="updraft-lock-area">
				<p>
					<img width="150" height="150" src="<?php echo MAINWP_UPDRAFT_PLUS_URL; ?>/images/padlock-150.png" alt="<?php echo esc_attr( __( 'Unlock', 'mainwp-updraftplus-extension' ) ); ?>">
				</p>
				<form method="post">
					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'updraftplus-unlockadmin-nonce' ); ?>">
					<p>
						<input type="password" size="16" name="updraft_unlockadmin_password" value="">
						<input type="submit" value="<?php echo esc_attr( __( 'Unlock', 'mainwp-updraftplus-extension' ) ); ?>">
					</p>
				</form>
				<p>
					<?php
					if ( false === $this->correct_password_supplied ) {
							echo '<span style="color:red;">' . __( 'Password incorrect', 'mainwp-updraftplus-extension' ) . '</span><br>';
					}
					?>
					<?php _e( 'To access the UpdraftPlus settings, please enter your unlock password', 'mainwp-updraftplus-extension' ); ?><br>
						<span style="font-size:85%;"><em>
							<?php
							$this->get_opts();
							$url = (empty( $this->opts['support_url'] )) ? $this->default_support_url : $this->opts['support_url'];
							if ( preg_match( '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $url ) ) {
									$url = 'mailto:' . $url; }
							if ( ! empty( $url ) ) {
									echo '<a href="' . esc_attr( $url ) . '">';
							};
							_e( 'For unlocking support, please contact whoever manages UpdraftPlus for you.', 'mainwp-updraftplus-extension' );
							if ( ! empty( $url ) ) {
									echo '</a>';
							};
							?>
							</em></span>
					</p>

				</div>
				<?php
	}
}
