<?php
/**
 * Post Handler.
 *
 * @package MainWP/Dashboard
 */

namespace MainWP\Dashboard;

/**
 * Class MainWP_Post_Handler
 *
 * @package MainWP\Dashboard
 *
 * @uses \MainWP\Dashboard\MainWP_Post_Base_Handler
 */
class MainWP_Post_Handler extends MainWP_Post_Base_Handler {
	// phpcs:disable Generic.Metrics.CyclomaticComplexity -- This is the only way to achieve desired results, pull request solutions appreciated.

	/**
	 * Private static variable to hold the single instance of the class.
	 *
	 * @static
	 *
	 * @var mixed Default null
	 */
	private static $instance = null;

	/**
	 * Method instance()
	 *
	 * Create a public static instance.
	 *
	 * @static
	 * @return MainWP_Post_Handler
	 */
	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initiate all actions.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Post_Page_Handler::get_class_name()
	 */
	public function init() {
		// Page: ManageSites.
		$this->add_action( 'mainwp_notes_save', array( &$this, 'mainwp_notes_save' ) );

		// Page: BulkAddUser.
		$this->add_action( 'mainwp_bulkadduser', array( &$this, 'mainwp_bulkadduser' ) );
		$this->add_action( 'mainwp_importuser', array( &$this, 'mainwp_importuser' ) );

		// Widget: RightNow.
		$this->add_action( 'mainwp_syncerrors_dismiss', array( &$this, 'mainwp_syncerrors_dismiss' ) );

		if ( mainwp_current_user_have_right( 'dashboard', 'manage_security_issues' ) ) {
			// Page: SecurityIssues.
			$this->add_action( 'mainwp_security_issues_request', array( &$this, 'mainwp_security_issues_request' ) );
			$this->add_action( 'mainwp_security_issues_fix', array( &$this, 'mainwp_security_issues_fix' ) );
			$this->add_action( 'mainwp_security_issues_unfix', array( &$this, 'mainwp_security_issues_unfix' ) );
		}

		$this->add_action( 'mainwp_notice_status_update', array( &$this, 'mainwp_notice_status_update' ) );
		$this->add_action( 'mainwp_dismiss_twit', array( &$this, 'mainwp_dismiss_twit' ) );
		$this->add_action( 'mainwp_dismiss_activate_notice', array( &$this, 'dismiss_activate_notice' ) );
		$this->add_action( 'mainwp_status_saving', array( &$this, 'mainwp_status_saving' ) );
		$this->add_action( 'mainwp_leftmenu_filter_group', array( &$this, 'mainwp_leftmenu_filter_group' ) );
		$this->add_action( 'mainwp_widgets_order', array( &$this, 'ajax_widgets_order' ) );
		$this->add_action( 'mainwp_save_settings', array( &$this, 'ajax_mainwp_save_settings' ) );

		$this->add_action( 'mainwp_twitter_dashboard_action', array( &$this, 'mainwp_twitter_dashboard_action' ) );

		// Page: Recent Posts.
		if ( mainwp_current_user_have_right( 'dashboard', 'manage_posts' ) ) {
			$this->add_action( 'mainwp_post_unpublish', array( &$this, 'mainwp_post_unpublish' ) );
			$this->add_action( 'mainwp_post_publish', array( &$this, 'mainwp_post_publish' ) );
			$this->add_action( 'mainwp_post_trash', array( &$this, 'mainwp_post_trash' ) );
			$this->add_action( 'mainwp_post_delete', array( &$this, 'mainwp_post_delete' ) );
			$this->add_action( 'mainwp_post_restore', array( &$this, 'mainwp_post_restore' ) );
			$this->add_action( 'mainwp_post_approve', array( &$this, 'mainwp_post_approve' ) );
		}
		$this->add_action( 'mainwp_post_addmeta', array( MainWP_Post_Page_Handler::get_class_name(), 'ajax_add_meta' ) );
		// Page: Pages.
		if ( mainwp_current_user_have_right( 'dashboard', 'manage_pages' ) ) {
			$this->add_action( 'mainwp_page_unpublish', array( &$this, 'mainwp_page_unpublish' ) );
			$this->add_action( 'mainwp_page_publish', array( &$this, 'mainwp_page_publish' ) );
			$this->add_action( 'mainwp_page_trash', array( &$this, 'mainwp_page_trash' ) );
			$this->add_action( 'mainwp_page_delete', array( &$this, 'mainwp_page_delete' ) );
			$this->add_action( 'mainwp_page_restore', array( &$this, 'mainwp_page_restore' ) );
		}
		// Page: Users.
		$this->add_action( 'mainwp_user_delete', array( &$this, 'mainwp_user_delete' ) );
		$this->add_action( 'mainwp_user_edit', array( &$this, 'mainwp_user_edit' ) );
		$this->add_action( 'mainwp_user_update_password', array( &$this, 'mainwp_user_update_password' ) );
		$this->add_action( 'mainwp_user_update_user', array( &$this, 'mainwp_user_update_user' ) );

		// Page: Posts.
		$this->add_action( 'mainwp_posts_search', array( &$this, 'mainwp_posts_search' ) );
		$this->add_action( 'mainwp_get_categories', array( &$this, 'mainwp_get_categories' ) );
		$this->add_action( 'mainwp_post_get_edit', array( &$this, 'mainwp_post_get_edit' ) );
		$this->add_action( 'mainwp_post_postingbulk', array( MainWP_Post_Page_Handler::get_class_name(), 'ajax_posting_posts' ) );
		$this->add_action( 'mainwp_get_sites_of_groups', array( MainWP_Post_Page_Handler::get_class_name(), 'ajax_get_sites_of_groups' ) );

		// Page: Pages.
		$this->add_action( 'mainwp_pages_search', array( &$this, 'mainwp_pages_search' ) );
		// Page: User.
		$this->add_action( 'mainwp_users_search', array( &$this, 'mainwp_users_search' ) );

		$this->add_action( 'mainwp_events_notice_hide', array( &$this, 'mainwp_events_notice_hide' ) );
		$this->add_action( 'mainwp_showhide_sections', array( &$this, 'mainwp_showhide_sections' ) );
		$this->add_action( 'mainwp_saving_status', array( &$this, 'mainwp_saving_status' ) );
		$this->add_action( 'mainwp_autoupdate_and_trust_child', array( &$this, 'mainwp_autoupdate_and_trust_child' ) );
		$this->add_action( 'mainwp_installation_warning_hide', array( &$this, 'mainwp_installation_warning_hide' ) );
		$this->add_action( 'mainwp_force_destroy_sessions', array( &$this, 'mainwp_force_destroy_sessions' ) );
		$this->add_action( 'mainwp_recheck_http', array( &$this, 'ajax_recheck_http' ) );
		$this->add_action( 'mainwp_ignore_http_response', array( &$this, 'mainwp_ignore_http_response' ) );
		$this->add_action( 'mainwp_disconnect_site', array( &$this, 'ajax_disconnect_site' ) );
		$this->add_action( 'mainwp_manage_sites_display_rows', array( &$this, 'ajax_sites_display_rows' ) );
		$this->add_action( 'mainwp_monitoring_sites_display_rows', array( &$this, 'ajax_monitoring_display_rows' ) );

		$this->add_action_nonce( 'mainwp-common-nonce' );
	}

	/**
	 * Method add_post_action()
	 *
	 * Add ajax action.
	 *
	 * @param string $action Action to perform.
	 * @param string $callback Callback to perform.
	 */
	public function add_post_action( $action, $callback ) {
		$this->add_action( $action, $callback );
	}

	/**
	 * Method mainwp_installation_warning_hide()
	 *
	 * Hide the installation warning.
	 */
	public function mainwp_installation_warning_hide() {
		$this->secure_request( 'mainwp_installation_warning_hide' );

		update_option( 'mainwp_installation_warning_hide_the_notice', 'yes' );
		die( 'ok' );
	}

	/**
	 * Method mainwp_users_search()
	 *
	 * Search Post handler,
	 * Page: User.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Cache::init_session()
	 * @uses \MainWP\Dashboard\MainWP_User::render_table()
	 */
	public function mainwp_users_search() {
		$this->secure_request( 'mainwp_users_search' );
		MainWP_Cache::init_session();

		$role   = isset( $_POST['role'] ) ? sanitize_text_field( wp_unslash( $_POST['role'] ) ) : '';
		$groups = isset( $_POST['groups'] ) && is_array( $_POST['groups'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['groups'] ) ) : '';
		$sites  = isset( $_POST['sites'] ) && is_array( $_POST['sites'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['sites'] ) ) : '';
		$search = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';

		MainWP_User::render_table( false, $role, $groups, $sites, $search );
		die();
	}

	/**
	 * Method mainwp_posts_search()
	 *
	 * Search Post handler,
	 * Page: Posts.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Cache::init_session()
	 * @uses \MainWP\Dashboard\MainWP_Post::render_table()
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function mainwp_posts_search() {
		$this->secure_request( 'mainwp_posts_search' );
		$post_type = ( isset( $_POST['post_type'] ) && 0 < strlen( sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) ) ? sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) : 'post' );
		if ( isset( $_POST['maximum'] ) ) {
			MainWP_Utility::update_option( 'mainwp_maximumPosts', isset( $_POST['maximum'] ) ? intval( $_POST['maximum'] ) : 50 );
		}

		$keyword            = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		$dtsstart           = isset( $_POST['dtsstart'] ) ? sanitize_text_field( wp_unslash( trim( $_POST['dtsstart'] ) ) ) : '';
		$dtsstop            = isset( $_POST['dtsstop'] ) ? sanitize_text_field( wp_unslash( trim( $_POST['dtsstop'] ) ) ) : '';
		$status             = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$groups             = isset( $_POST['groups'] ) && is_array( $_POST['groups'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['groups'] ) ) : '';
		$sites              = isset( $_POST['sites'] ) && is_array( $_POST['sites'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['sites'] ) ) : '';
		$postId             = isset( $_POST['postId'] ) ? sanitize_text_field( wp_unslash( $_POST['postId'] ) ) : '';
		$userId             = isset( $_POST['userId'] ) ? sanitize_text_field( wp_unslash( $_POST['userId'] ) ) : '';
		$search_on          = isset( $_POST['search_on'] ) ? sanitize_text_field( wp_unslash( $_POST['search_on'] ) ) : '';
		$table_content_only = isset( $_POST['table_content'] ) && $_POST['table_content'] ? true : false;

		MainWP_Cache::init_session();
		if ( $table_content_only ) {
			MainWP_Post::render_table_body( $keyword, $dtsstart, $dtsstop, $status, $groups, $sites, $postId, $userId, $post_type, $search_on, true );
		} else {
			MainWP_Post::render_table( false, $keyword, $dtsstart, $dtsstop, $status, $groups, $sites, $postId, $userId, $post_type, $search_on );
		}
		die();
	}
	/**
	 * Method mainwp_pages_search()
	 *
	 * Search Post handler,
	 * Page: Pages.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Cache::init_session()
	 * @uses \MainWP\Dashboard\MainWP_Page::render_table()
	 *
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function mainwp_pages_search() {
		$this->secure_request( 'mainwp_pages_search' );
		if ( isset( $_POST['maximum'] ) ) {
			MainWP_Utility::update_option( 'mainwp_maximumPages', intval( $_POST['maximum'] ) ? intval( $_POST['maximum'] ) : 50 );
		}

		$keyword   = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
		$dtsstart  = isset( $_POST['dtsstart'] ) ? sanitize_text_field( wp_unslash( $_POST['dtsstart'] ) ) : '';
		$dtsstop   = isset( $_POST['dtsstop'] ) ? sanitize_text_field( wp_unslash( $_POST['dtsstop'] ) ) : '';
		$status    = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$groups    = isset( $_POST['groups'] ) && is_array( $_POST['groups'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['groups'] ) ) : '';
		$sites     = isset( $_POST['sites'] ) && is_array( $_POST['sites'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['sites'] ) ) : '';
		$search_on = isset( $_POST['search_on'] ) ? sanitize_text_field( wp_unslash( $_POST['search_on'] ) ) : '';

		MainWP_Cache::init_session();
		MainWP_Page::render_table( false, $keyword, $dtsstart, $dtsstop, $status, $groups, $sites, $search_on );
		die();
	}

	/**
	 * Method mainwp_get_categories()
	 *
	 * Get post/page categories.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Post_Page_Handler::get_categories()
	 */
	public function mainwp_get_categories() {
		$this->secure_request( 'mainwp_get_categories' );
		MainWP_Post_Page_Handler::get_categories();
		die();
	}

	/**
	 * Method mainwp_post_get_edit()
	 *
	 * Get post to edit.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Post_Page_Handler::get_post()
	 */
	public function mainwp_post_get_edit() {
		$this->secure_request( 'mainwp_post_get_edit' );
		MainWP_Post_Page_Handler::get_post();
		die();
	}

	/**
	 * Method mainwp_user_delete()
	 *
	 * Delete User from Child Site,
	 * Page: Users.
	 *
	 * @uses \MainWP\Dashboard\MainWP_User::delete()
	 */
	public function mainwp_user_delete() {
		$this->secure_request( 'mainwp_user_delete' );
		MainWP_User::delete();
	}

	/**
	 * Method mainwp_user_edit()
	 *
	 * Edit User from Child Site,
	 * Page: Users.
	 *
	 * @uses \MainWP\Dashboard\MainWP_User::edit()
	 */
	public function mainwp_user_edit() {
		$this->secure_request( 'mainwp_user_edit' );
		MainWP_User::edit();
	}

	/**
	 * Method mainwp_user_update_password(
	 *
	 * Update User password from Child Site,
	 * Page: Users.
	 *
	 * @uses \MainWP\Dashboard\MainWP_User::update_password()
	 */
	public function mainwp_user_update_password() {
		$this->secure_request( 'mainwp_user_update_password' );
		MainWP_User::update_password();
	}

	/**
	 * Method mainwp_user_update_user()
	 *
	 * Update User from Child Site,
	 * Page: Users.
	 *
	 * @uses \MainWP\Dashboard\MainWP_User::update_user()
	 */
	public function mainwp_user_update_user() {
		$this->secure_request( 'mainwp_user_update_user' );
		MainWP_User::update_user();
	}

	/**
	 * Method mainwp_post_unpublish()
	 *
	 * Unpublish post from Child Site,
	 * Page: Recent Posts.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Recent_Posts::unpublish()
	 */
	public function mainwp_post_unpublish() {
		$this->secure_request( 'mainwp_post_unpublish' );
		MainWP_Recent_Posts::unpublish();
	}

	/**
	 * Method mainwp_post_publish()
	 *
	 * Publish post on Child Site,
	 * Page: Recent Posts.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Recent_Posts::publish()
	 */
	public function mainwp_post_publish() {
		$this->secure_request( 'mainwp_post_publish' );
		MainWP_Recent_Posts::publish();
	}

	/**
	 * Method mainwp_post_approve()
	 *
	 * Approve post on Child Site,
	 * Page: Recent Posts.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Recent_Posts::approve()
	 */
	public function mainwp_post_approve() {
		$this->secure_request( 'mainwp_post_approve' );
		MainWP_Recent_Posts::approve();
	}

	/**
	 * Method mainwp_post_trash()
	 *
	 * Trash post on Child Site,
	 * Page: Recent Posts.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Recent_Posts::trash()
	 */
	public function mainwp_post_trash() {
		$this->secure_request( 'mainwp_post_trash' );

		MainWP_Recent_Posts::trash();
	}

	/**
	 * Method mainwp_post_delete()
	 *
	 * Delete post on Child Site,
	 * Page: Recent Posts.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Recent_Posts::delete()
	 */
	public function mainwp_post_delete() {
		$this->secure_request( 'mainwp_post_delete' );

		MainWP_Recent_Posts::delete();
	}

	/**
	 * Method mainwp_post_restore()
	 *
	 * Restore post,
	 * Page: Recent Posts.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Recent_Posts::restore()
	 */
	public function mainwp_post_restore() {
		$this->secure_request( 'mainwp_post_restore' );

		MainWP_Recent_Posts::restore();
	}

	/**
	 * Method mainwp_page_unpublish()
	 *
	 * Unpublish page,
	 * Page: Recent Pages.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Page::unpublish()
	 */
	public function mainwp_page_unpublish() {
		$this->secure_request( 'mainwp_page_unpublish' );
		MainWP_Page::unpublish();
	}

	/**
	 * Method mainwp_page_publish()
	 *
	 * Publish page,
	 * Page: Recent Pages.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Page::publish()
	 */
	public function mainwp_page_publish() {
		$this->secure_request( 'mainwp_page_publish' );
		MainWP_Page::publish();
	}

	/**
	 * Method mainwp_page_trash()
	 *
	 * Trash page,
	 * Page: Recent Pages.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Page::trash()
	 */
	public function mainwp_page_trash() {
		$this->secure_request( 'mainwp_page_trash' );
		MainWP_Page::trash();
	}

	/**
	 * Method mainwp_page_delete()
	 *
	 * Delete page,
	 * Page: Recent Pages.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Page::delete()
	 */
	public function mainwp_page_delete() {
		$this->secure_request( 'mainwp_page_delete' );
		MainWP_Page::delete();
	}

	/**
	 * Method mainwp_page_restor()
	 *
	 * Restore page,
	 * Page: Recent Pages.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Page::restore()
	 */
	public function mainwp_page_restore() {
		$this->secure_request( 'mainwp_page_restore' );
		MainWP_Page::restore();
	}

	/**
	 * Method mainwp_notice_status_update()
	 *
	 * Hide after installation notices,
	 * (PHP version, Trust MainWP Child, Multisite Warning and OpenSSL warning).
	 *
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function mainwp_notice_status_update() {
		$this->secure_request( 'mainwp_notice_status_update' );

		$no_id = isset( $_POST['notice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notice_id'] ) ) : false;
		if ( 'mail_failed' === $no_id ) {
			MainWP_Utility::update_option( 'mainwp_notice_wp_mail_failed', 'hide' );
			die( 'ok' );
		}

		/**
		 * Current user global.
		 *
		 * @global string
		 */
		global $current_user;
		$user_id = $current_user->ID;
		if ( $user_id ) {
			$status = get_user_option( 'mainwp_notice_saved_status' );
			if ( ! is_array( $status ) ) {
				$status = array();
			}
			if ( ! empty( $no_id ) ) {
				$status[ $no_id ] = 1;
				update_user_option( $user_id, 'mainwp_notice_saved_status', $status );
			}
		}
		die( 1 );
	}

	/**
	 * Method mainwp_status_saving()
	 *
	 * Save last_sync_sites time() or mainwp_status_saved_values.
	 */
	public function mainwp_status_saving() {
		$this->secure_request( 'mainwp_status_saving' );
		$values = get_option( 'mainwp_status_saved_values' );

		if ( ! isset( $_POST['status'] ) ) {
			die( -1 );
		}

		if ( 'last_sync_sites' === $_POST['status'] ) {
			if ( isset( $_POST['isGlobalSync'] ) && ! empty( $_POST['isGlobalSync'] ) ) {
				update_option( 'mainwp_last_synced_all_sites', time() );

				/**
				 * Action: mainwp_synced_all_sites
				 *
				 * Fires upon successfull synchronization process.
				 *
				 * @since 3.5.1
				 */
				do_action( 'mainwp_synced_all_sites' );
			}
			die( 'ok' );
		}

		$status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : '';
		$value  = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

		if ( ! empty( $status ) ) {
			if ( empty( $value ) ) {
				if ( isset( $values[ $status ] ) ) {
					unset( $values[ $status ] );
				}
			} else {
					$values[ $status ] = $value;
			}
			update_option( 'mainwp_status_saved_values', $values );
		}

		die( 'ok' );
	}

	/**
	 * Method ajax_widget_order()
	 *
	 * Update saved widget order.
	 */
	public function ajax_widgets_order() {

		$this->secure_request( 'mainwp_widgets_order' );
		$user = wp_get_current_user();
		if ( $user && ! empty( $_POST['page'] ) ) {
			$page  = isset( $_POST['page'] ) ? sanitize_text_field( wp_unslash( $_POST['page'] ) ) : '';
			$order = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : '';
			update_user_option( $user->ID, 'mainwp_widgets_sorted_' . $page, $order, true );
			die( 'ok' );
		}
		die( -1 );
	}

	/**
	 * Method ajax_mainwp_save_settings()
	 *
	 * Update saved MainWP Settings.
	 *
	 * @uses  \MainWP\Dashboard\MainWP_Utility::update_option()
	 */
	public function ajax_mainwp_save_settings() {
		$this->secure_request( 'mainwp_save_settings' );
		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		if ( ! empty( $name ) ) {
			$option_name = 'mainwp_' . $name;
			$val         = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';
			MainWP_Utility::update_option( $option_name, $val );
		}
		die( 'ok' );
	}

	/**
	 * Method mainwp_leftmenu_filter_group()
	 *
	 * MainWP left menu filter by group.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB::query()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_websites_by_group_id()
	 * @uses \MainWP\Dashboard\MainWP_DB::fetch_object()
	 * @uses \MainWP\Dashboard\MainWP_DB::free_result()
	 */
	public function mainwp_leftmenu_filter_group() {
		$this->secure_request( 'mainwp_leftmenu_filter_group' );

		$gid = isset( $_POST['group_id'] ) ? intval( $_POST['group_id'] ) : false;

		if ( ! empty( $gid ) ) {
			$ids      = '';
			$websites = MainWP_DB::instance()->query( MainWP_DB::instance()->get_sql_websites_by_group_id( $gid, true ) );
			while ( $websites && ( $website  = MainWP_DB::fetch_object( $websites ) ) ) {
				$ids .= $website->id . ',';
			}
			MainWP_DB::free_result( $websites );
			$ids = rtrim( $ids, ',' );
			die( $ids );
		}
		die( '' );
	}

	/**
	 * Method mainwp_dismiss_twit()
	 *
	 * Dismiss the twitter bragger.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Twitter::clear_twitter_info()
	 */
	public function mainwp_dismiss_twit() {
		$this->secure_request( 'mainwp_dismiss_twit' );

		/**
		 * Current user global.
		 *
		 * @global string
		 */
		global $current_user;

		$user_id = $current_user->ID;
		if ( $user_id && isset( $_POST['twitId'] ) && ! empty( $_POST['twitId'] ) && isset( $_POST['what'] ) && ! empty( $_POST['what'] ) ) {
			MainWP_Twitter::clear_twitter_info( sanitize_text_field( wp_unslash( $_POST['what'] ) ), sanitize_text_field( wp_unslash( $_POST['twitId'] ) ) );
		}
		die( 1 );
	}

	/**
	 * Method dismiss_activate_notice()
	 *
	 * Dismiss activate notice.
	 */
	public function dismiss_activate_notice() {
		$this->secure_request( 'mainwp_dismiss_activate_notice' );

		/**
		 * Current user global.
		 *
		 * @global string
		 */
		global $current_user;

		$user_id = $current_user->ID;
		$slug    = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		if ( $user_id && ! empty( $slug ) ) {
			$activate_notices = get_user_option( 'mainwp_hide_activate_notices' );
			if ( ! is_array( $activate_notices ) ) {
				$activate_notices = array();
			}

			$activate_notices[ $slug ] = time();
			update_user_option( $user_id, 'mainwp_hide_activate_notices', $activate_notices );
		}
		die( 1 );
	}

	/**
	 * Method mainwp_twitter_dashboard_action()
	 *
	 * Post handler for twitter bragger.
	 *
	 * @return mixed $html|$success
	 *
	 * @uses \MainWP\Dashboard\MainWP_Twitter
	 * @uses \MainWP\Dashboard\MainWP_Twitter::update_twitter_info()
	 * @uses \MainWP\Dashboard\MainWP_Twitter::enabled_twitter_messages()
	 * @uses \MainWP\Dashboard\MainWP_Twitter::get_twitter_notice()
	 * @uses \MainWP\Dashboard\MainWP_Twitter::get_twit_to_send()
	 */
	public function mainwp_twitter_dashboard_action() {
		$this->secure_request( 'mainwp_twitter_dashboard_action' );

		$success        = false;
		$actionName     = isset( $_POST['actionName'] ) ? sanitize_text_field( wp_unslash( $_POST['actionName'] ) ) : '';
		$countSites     = isset( $_POST['countSites'] ) ? intval( $_POST['countSites'] ) : 0;
		$countRealItems = isset( $_POST['countRealItems'] ) ? intval( $_POST['countRealItems'] ) : 0;
		$countItems     = isset( $_POST['countItems'] ) ? intval( $_POST['countItems'] ) : 0;
		$countSeconds   = isset( $_POST['countSeconds'] ) ? intval( $_POST['countSeconds'] ) : 0;

		if ( ! empty( $actionName ) && ! empty( $countSites ) ) {
			$success = MainWP_Twitter::update_twitter_info( $actionName, $countSites, $countSeconds, $countRealItems, time(), $countItems );
		}

		if ( ! empty( $_POST['showNotice'] ) ) {
			if ( MainWP_Twitter::enabled_twitter_messages() ) {
				$twitters = MainWP_Twitter::get_twitter_notice( $actionName );
				$html     = '';
				if ( is_array( $twitters ) ) {
					foreach ( $twitters as $timeid => $twit_mess ) {
						if ( ! empty( $twit_mess ) ) {
							$sendText = MainWP_Twitter::get_twit_to_send( $actionName, $timeid );
							$html    .= '<div class="mainwp-tips mainwp-notice mainwp-notice-blue twitter"><span class="mainwp-tip" twit-what="' . esc_attr( $actionName ) . '" twit-id="' . $timeid . '">' . $twit_mess . '</span>&nbsp;' . MainWP_Twitter::gen_twitter_button( $sendText, false ) . '<span><a href="#" class="mainwp-dismiss-twit mainwp-right" ><i class="fa fa-times-circle"></i> ' . __( 'Dismiss', 'mainwp' ) . '</a></span></div>';
						}
					}
				}
				die( $html );
			}
		} elseif ( $success ) {
			die( 'ok' );
		}

		die( '' );
	}

	/**
	 * Method mainwp_security_issues_request()
	 *
	 * Post handler for,
	 * Page: SecurityIssues.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Exception
	 * @uses \MainWP\Dashboard\MainWP_Security_Issues::fetch_security_issues()
	 */
	public function mainwp_security_issues_request() {
		$this->secure_request( 'mainwp_security_issues_request' );

		try {
			wp_send_json( array( 'result' => MainWP_Security_Issues::fetch_security_issues() ) );
		} catch ( MainWP_Exception $e ) {
			die(
				wp_json_encode(
					array(
						'error' => array(
							'message' => $e->getMessage(),
							'extra'   => $e->get_message_extra(),
						),
					)
				)
			);
		}
	}

	/**
	 * Method  mainwp_security_issues_fix()
	 *
	 * Post handler for 'fix issues',
	 * Page: SecurityIssues.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Exception
	 * @uses \MainWP\Dashboard\MainWP_Security_Issues::fix_security_issue()
	 */
	public function mainwp_security_issues_fix() {
		$this->secure_request( 'mainwp_security_issues_fix' );

		try {
			wp_send_json( array( 'result' => MainWP_Security_Issues::fix_security_issue() ) );
		} catch ( MainWP_Exception $e ) {
			die(
				wp_json_encode(
					array(
						'error' => array(
							'message' => $e->getMessage(),
							'extra'   => $e->get_message_extra(),
						),
					)
				)
			);
		}
	}

	/**
	 * Method  mainwp_security_issues_unfix()
	 *
	 * Post handler for 'unfix issues',
	 * Page: SecurityIssues.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Exception
	 * @uses \MainWP\Dashboard\MainWP_Security_Issues::unfix_security_issue()
	 */
	public function mainwp_security_issues_unfix() {
		$this->secure_request( 'mainwp_security_issues_unfix' );

		try {
			wp_send_json( array( 'result' => MainWP_Security_Issues::unfix_security_issue() ) );
		} catch ( MainWP_Exception $e ) {
			die(
				wp_json_encode(
					array(
						'error' => array(
							'message' => $e->getMessage(),
							'extra'   => $e->get_message_extra(),
						),
					)
				)
			);
		}
	}

	/**
	 * Method ajax_disconnect_site()
	 *
	 * Disconnect Child Site.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Connect::fetch_url_authed()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_website_by_id()
	 */
	public function ajax_disconnect_site() {
		$this->secure_request( 'mainwp_disconnect_site' );

		$siteid = isset( $_POST['wp_id'] ) ? intval( $_POST['wp_id'] ) : 0;

		if ( empty( $siteid ) ) {
			die( wp_json_encode( array( 'error' => 'Error: site id empty' ) ) );
		}

		$website = MainWP_DB::instance()->get_website_by_id( $siteid );

		if ( ! $website ) {
			die( wp_json_encode( array( 'error' => 'Not found site' ) ) );
		}

		try {
			$information = MainWP_Connect::fetch_url_authed( $website, 'disconnect' );
		} catch ( \Exception $e ) {
			$information = array( 'error' => __( 'fetch_url_authed exception', 'mainwp' ) );
		}

		wp_send_json( $information );
	}

	/**
	 * Method ajax_sites_display_rows()
	 *
	 * Display rows via ajax,
	 * Page: Manage Sites.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Manage_Sites::ajax_optimize_display_rows()
	 */
	public function ajax_sites_display_rows() {
		$this->secure_request( 'mainwp_manage_sites_display_rows' );
		MainWP_Manage_Sites::ajax_optimize_display_rows();
	}

	/**
	 * Method ajax_sites_display_rows()
	 *
	 * Display rows via ajax,
	 * Page: Monitoring Sites.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Monitoring::ajax_optimize_display_rows()
	 */
	public function ajax_monitoring_display_rows() {
		$this->secure_request( 'mainwp_monitoring_sites_display_rows' );
		MainWP_Monitoring::ajax_optimize_display_rows();
	}


	/**
	 * Method mainwp_bulkadduser()
	 *
	 * Bulk Add User for,
	 * Page: BulkAddUser.
	 *
	 * @uses \MainWP\Dashboard\MainWP_User::do_bulk_add()
	 */
	public function mainwp_bulkadduser() {
		$this->check_security( 'mainwp_bulkadduser' );
		MainWP_User::do_bulk_add();
		die();
	}

	/**
	 * Method mainwp_importuser()
	 *
	 * Import user.
	 *
	 * @uses \MainWP\Dashboard\MainWP_User::do_import()
	 */
	public function mainwp_importuser() {
		$this->secure_request( 'mainwp_importuser' );
		MainWP_User::do_import();
	}

	/**
	 * Method mainwp_notes_save()
	 *
	 * Post handler for save notes on,
	 * Page: Manage Sites.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Manage_Sites_Handler::save_note()
	 */
	public function mainwp_notes_save() {
		$this->secure_request( 'mainwp_notes_save' );
		MainWP_Manage_Sites_Handler::save_note();
	}

	/**
	 * Method mainwp_syncerrors_dismiss()
	 *
	 * Dismiss Sync errors for,
	 * Widget: RightNow.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Updates_Overview::dismiss_sync_errors()
	 */
	public function mainwp_syncerrors_dismiss() {

		$this->secure_request( 'mainwp_syncerrors_dismiss' );

		try {
			die( wp_json_encode( array( 'result' => MainWP_Updates_Overview::dismiss_sync_errors() ) ) );
		} catch ( \Exception $e ) {
			die( wp_json_encode( array( 'error' => $e->getMessage() ) ) );
		}
	}

	/**
	 * Method mainwp_events_notice_hide()
	 *
	 * Hide events notice.
	 */
	public function mainwp_events_notice_hide() {
		$this->secure_request( 'mainwp_events_notice_hide' );

		if ( isset( $_POST['notice'] ) ) {
			$current_options = get_option( 'mainwp_showhide_events_notice' );
			if ( ! is_array( $current_options ) ) {
				$current_options = array();
			}
			if ( 'first_site' === $_POST['notice'] ) {
				update_option( 'mainwp_first_site_events_notice', '' );
			} elseif ( 'request_reviews1' === $_POST['notice'] ) {
				$current_options['request_reviews1']           = 15;
				$current_options['request_reviews1_starttime'] = time();
			} elseif ( 'request_reviews1_forever' === $_POST['notice'] || 'request_reviews2_forever' === $_POST['notice'] ) {
				$current_options['request_reviews1'] = 'forever';
				$current_options['request_reviews2'] = 'forever';
			} elseif ( 'request_reviews2' === $_POST['notice'] ) {
				$current_options['request_reviews2']           = 15;
				$current_options['request_reviews2_starttime'] = time();
			} elseif ( 'trust_child' === $_POST['notice'] ) {
				$current_options['trust_child'] = 1;
			} elseif ( 'multi_site' === $_POST['notice'] ) {
				$current_options['hide_multi_site_notice'] = 1;
			}
			update_option( 'mainwp_showhide_events_notice', $current_options );
		}
		die( 'ok' );
	}

	/**
	 * Method mainwp_showhide_sections()
	 *
	 * Show/Hide sections.
	 */
	public function mainwp_showhide_sections() {
		if ( isset( $_POST['sec'] ) && isset( $_POST['status'] ) ) {
			$opts = get_option( 'mainwp_opts_showhide_sections' );
			if ( ! is_array( $opts ) ) {
				$opts = array();
			}
			$opts[ sanitize_text_field( wp_unslash( $_POST['sec'] ) ) ] = sanitize_text_field( wp_unslash( $_POST['status'] ) );
			update_option( 'mainwp_opts_showhide_sections', $opts );
			die( 'ok' );
		}
		die( 'failed' );
	}

	/**
	 * Method mainwp_saving_status()
	 *
	 * MainWP Saving Status.
	 */
	public function mainwp_saving_status() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['nonce'] ), 'mainwp_ajax' ) ) {
			die( __( 'WP nonce could not be verified. Please reload the page and try again.', 'mainwp' ) );
		}
		$saving_status = isset( $_POST['saving_status'] ) ? sanitize_text_field( wp_unslash( $_POST['saving_status'] ) ) : false;
		if ( ! empty( $saving_status ) ) {
			$current_options = get_option( 'mainwp_opts_saving_status' );
			if ( ! is_array( $current_options ) ) {
				$current_options = array();
			}
			if ( isset( $_POST['value'] ) ) {
				$current_options[ $saving_status ] = sanitize_text_field( wp_unslash( $_POST['value'] ) );
			}
			update_option( 'mainwp_opts_saving_status', $current_options );
		}
		die( 'ok' );
	}

	/**
	 * Method ajax_recheck_http()
	 *
	 * Recheck Child Site http status code & message.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Connect::check_ignored_http_code()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_website_by_id()
	 * @uses \MainWP\Dashboard\MainWP_Monitoring_Handler::handle_check_website()
	 */
	public function ajax_recheck_http() {
		$this->check_security( 'mainwp_recheck_http' );

		if ( ! isset( $_POST['websiteid'] ) || empty( $_POST['websiteid'] ) ) {
			die( -1 );
		}

		$website = MainWP_DB::instance()->get_website_by_id( intval( $_POST['websiteid'] ) );
		if ( empty( $website ) ) {
			die( -1 );
		}

		$result       = MainWP_Monitoring_Handler::handle_check_website( $website );
		$http_code    = ( is_array( $result ) && isset( $result['httpCode'] ) ) ? $result['httpCode'] : 0;
		$check_result = MainWP_Connect::check_ignored_http_code( $http_code );
		die(
			wp_json_encode(
				array(
					'httpcode' => esc_html( $http_code ),
					'status'   => $check_result ? 1 : 0,
				)
			)
		);
	}

	/**
	 * Method mainwp_ignore_http_response()
	 *
	 * Ignore Child Site https response.
	 *
	 * @uses \MainWP\Dashboard\MainWP_DB::get_website_by_id()
	 * @uses \MainWP\Dashboard\MainWP_DB::update_website_values()
	 */
	public function mainwp_ignore_http_response() {
		$this->check_security( 'mainwp_ignore_http_response' );
		$siteid = isset( $_POST['websiteid'] ) ? intval( $_POST['websiteid'] ) : false;

		if ( empty( $siteid ) ) {
			die( -1 );
		}

		$website = MainWP_DB::instance()->get_website_by_id( $siteid );
		if ( empty( $website ) ) {
			die( -1 );
		}

		MainWP_DB::instance()->update_website_values( $website->id, array( 'http_response_code' => '-1' ) );
		die( wp_json_encode( array( 'ok' => 1 ) ) );
	}

	/**
	 * Method mainwp_autoupdate_and_trust_child()
	 *
	 * Set MainWP Child Plugin to Trusted & AutoUpdate.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Plugins_Handler::trust_plugin()
	 */
	public function mainwp_autoupdate_and_trust_child() {
		$this->secure_request( 'mainwp_autoupdate_and_trust_child' );
		if ( get_option( 'mainwp_automaticDailyUpdate' ) != 1 ) {
			update_option( 'mainwp_automaticDailyUpdate', 1 );
		}
		MainWP_Plugins_Handler::trust_plugin( 'mainwp-child/mainwp-child.php' );
		die( 'ok' );
	}

	/**
	 * Method mainwp_force_destroy_sessions()
	 *
	 * Force destroy sessions.
	 *
	 * @uses \MainWP\Dashboard\MainWP_Connect::fetch_url_authed()
	 * @uses \MainWP\Dashboard\MainWP_DB::get_website_by_id()
	 *
	 * @uses \MainWP\Dashboard\MainWP_System_Utility::can_edit_website()
	 */
	public function mainwp_force_destroy_sessions() {
		$this->secure_request( 'mainwp_force_destroy_sessions' );

		$website_id = ( isset( $_POST['website_id'] ) ? (int) $_POST['website_id'] : 0 );

		if ( ! MainWP_DB::instance()->get_website_by_id( $website_id ) ) {
			die( wp_json_encode( array( 'error' => array( 'message' => __( 'This website does not exist.', 'mainwp' ) ) ) ) );
		}

		$website = MainWP_DB::instance()->get_website_by_id( $website_id );
		if ( ! MainWP_System_Utility::can_edit_website( $website ) ) {
			die( wp_json_encode( array( 'error' => array( 'message' => __( 'You cannot edit this website.', 'mainwp' ) ) ) ) );
		}

		try {
			$information = MainWP_Connect::fetch_url_authed(
				$website,
				'settings_tools',
				array(
					'action' => 'force_destroy_sessions',
				)
			);

			/**
			 * MainWP information array.
			 *
			 * @global object $mainWP
			 */
			global $mainWP;

			if ( ( '2.0.22' === $mainWP->get_version() ) || ( '2.0.23' === $mainWP->get_version() ) ) {
				if ( 1 != get_option( 'mainwp_fixed_security_2022' ) ) {
					update_option( 'mainwp_fixed_security_2022', 1 );
				}
			}
		} catch ( \Exception $e ) {
			$information = array( 'error' => __( 'fetch_url_authed exception', 'mainwp' ) );
		}

		wp_send_json( $information );
	}

}
