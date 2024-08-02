<?php

/*
 *
 * Credits
 *
 * Plugin-Name: WP Rocket
 * Plugin URI: https://wp-rocket.me
 * Description: The best WordPress performance plugin.
 * Version: 3.3.3.1
 * Code Name: Dagobah
 * Author: WP Media
 * Author URI: https://wp-media.me
 * Licence: GPLv2 or later
 *
*/


defined( 'ABSPATH' ) or	die( 'Cheatin&#8217; uh?' );

function mainwp_rocket_clean_exclude_file( $file )
{
	if ( ! $file ) {
		return false;
	}

	$path = parse_url( $file, PHP_URL_PATH );
    return $path;
}

function mainwp_rocket_sanitize_css( $file )
{
	$file = preg_replace( '#\?.*$#', '', $file );
	$ext = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	return $ext=='css' ? trim( $file ) : false;
}


function mainpw_rocket_sanitize_js( $file )
{
	$file = preg_replace( '#\?.*$#', '', $file );
	$ext  = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	return $ext == 'js' ? trim( $file ) : false;
}



function mainwp_get_rocket_option( $option, $default = false )
{
    $options = MainWP_Rocket::get_instance()->get_options();

    $value = isset( $options[ $option ] ) && $options[ $option ] !== '' ? $options[ $option ] : $default;

    return $value;
}


function mainwp_get_rocket_default_options()
{
        return array(
                'cache_mobile'             => 1,
				'do_caching_mobile_files'     => 0,
                'cache_logged_user'        => 0,
                'cache_ssl'                => 0,
				'emoji'					  => 0,
                'embeds'                   => 1,
                'control_heartbeat' => 0,
                'heartbeat_site_behavior'     => 'reduce_periodicity',
				'heartbeat_admin_behavior'    => 'reduce_periodicity',
				'heartbeat_editor_behavior'   => 'reduce_periodicity',
				'varnish_auto_purge' => 0,
				'manual_preload' => 0,
				'automatic_preload' => 0,
				'sitemap_preload' => 0,
				//'sitemap_preload_url_crawl' => 500000,
				'sitemaps' => array(),
				'database_revisions' => 0,
				'database_auto_drafts' => 0,
				'database_trashed_posts' => 0,
				'database_spam_comments' => 0,
				'database_trashed_comments' => 0,
				'database_expired_transients' => 0,
				'database_all_transients' => 0,
				'database_optimize_tables' => 0,
				'schedule_automatic_cleanup' => 0,
				'automatic_cleanup_frequency' => '',
                'cache_reject_uri'         => array(),
                'cache_reject_cookies'     => array(),
                'cache_reject_ua'          => array(),
                'cache_query_strings'      => array(),
                'cache_purge_pages'        => array(),
                'purge_cron_interval'      => 10,
                'purge_cron_unit'          => 'HOUR_IN_SECONDS',
                'exclude_css'              => array(),
                'exclude_js'               => array(),
                'exclude_inline_js'         => array(),
                'async_css'				  => 0,
            	'defer_all_js'				  => 0,
				'defer_all_js_safe'			=> 1,
                'critical_css'		   => '',
                'deferred_js_files'        => array(),
                'lazyload'          	   => 0,
                'lazyload_iframes'         => 0,
				'lazyload_youtube'			=>0,
                'minify_css'               => 0,
//                'minify_css_key'           => $minify_css_key,
                'minify_concatenate_css'	  => 0,
                //'minify_css_combine_all'   => 0,
                'minify_css_legacy'			  => 0,
                'minify_js'                => 0,
//                'minify_js_key'            => $minify_js_key,
                'minify_js_in_footer'      => array(),
                'minify_concatenate_js'		  => 0,
                'minify_js_combine_all'    => 0,
                //'minify_js_legacy'			  => 0,
                'minify_google_fonts'      => 0,
                'minify_html'              => 0,
                'remove_query_strings'     => 0,
                'dns_prefetch'             => 0,
                'cdn'                      => 0,
                'cdn_cnames'               => array(),
                'cdn_zone'                 => array(),
                //'cdn_ssl'                  => 0,
                'cdn_reject_files'         => array(),
                'do_cloudflare'		   	   => 0,
                'cloudflare_email'		   => '',
                'cloudflare_api_key'	   => '',
                'cloudflare_domain'	   	   => '',
                //'cloudflare_zone_id'          => '',
                'cloudflare_devmode'	   => 0,
                'cloudflare_protocol_rewrite' => 0,
                'cloudflare_auto_settings' => 0,
                'cloudflare_old_settings'  => 0,
                'do_beta'                  => 0,
				'analytics_enabled'        => 1,
        );
}


function mainwp_rocket_field_value( $name, $type, $default = '' )
{
    switch( $type ) {
        case 'number' :
        case 'email' :
        case 'text' :
            $value = esc_attr( mainwp_get_rocket_option( $name ) );
            if ( $value === false ) {
                $value = $default;
            }
            echo $value;
        break;
        case 'textarea' :
            $t_temp = mainwp_get_rocket_option( $name, '' );
            if ( is_array( $t_temp ) ) {
                $t_temp = implode( "\n" , $t_temp );
            }
            $value = ! empty( $t_temp ) ? esc_textarea( $t_temp ) : '';
            if ( ! $value ) {
                $value = $default;
            }
            echo $value;
        break;
        default :
            echo ''; // incorrect type
    }


}

function mainwp_rocket_settings_import_redirect( $message, $status ) {
	add_settings_error( 'general', 'settings_updated', $message, $status );

	set_transient( 'settings_errors', get_settings_errors(), 30 );

	$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
	wp_safe_redirect( esc_url_raw( $goback ) );
	die();
}


/**
 * Allow upload of JSON file.
 *
 * @since 2.10.7
 * @author Remy Perona
 *
 * @param array $wp_get_mime_types Array of allowed mime types.
 * @return array Updated array of allowed mime types
 */
function mainwp_rocket_allow_json_mime_type( $wp_get_mime_types ) {
	$wp_get_mime_types['json'] = 'application/json';

	return $wp_get_mime_types;
}

/**
 * Forces the correct file type for JSON file if the WP checks is incorrect
 *
 * @since 3.2.3.1
 * @author Gregory Viguier
 *
 * @param array  $wp_check_filetype_and_ext File data array containing 'ext', 'type', and
 *                                         'proper_filename' keys.
 * @param string $file                     Full path to the file.
 * @param string $filename                 The name of the file (may differ from $file due to
 *                                         $file being in a tmp directory).
 * @param array  $mimes                     Key is the file extension with value as the mime type.
 * @return array
 */
function mainwp_rocket_check_json_filetype( $wp_check_filetype_and_ext, $file, $filename, $mimes ) {
	if ( ! empty( $wp_check_filetype_and_ext['ext'] ) && ! empty( $wp_check_filetype_and_ext['type'] ) ) {
		return $wp_check_filetype_and_ext;
	}

	$wp_filetype = wp_check_filetype( $filename, $mimes );

	if ( 'json' !== $wp_filetype['ext'] ) {
		return $wp_check_filetype_and_ext;
	}

	if ( empty( $wp_filetype['type'] ) ) {
		// In case some other filter messed it up.
		$wp_filetype['type'] = 'application/json';
	}

	if ( ! extension_loaded( 'fileinfo' ) ) {
		return $wp_check_filetype_and_ext;
	}

	$finfo     = finfo_open( FILEINFO_MIME_TYPE );
	$real_mime = finfo_file( $finfo, $file );
	finfo_close( $finfo );

	if ( 'text/plain' !== $real_mime ) {
		return $wp_check_filetype_and_ext;
	}

	$wp_check_filetype_and_ext = array_merge( $wp_check_filetype_and_ext, $wp_filetype );

	return $wp_check_filetype_and_ext;
}


/**
 * File creation based on WordPress Filesystem
 *
 * @since 1.3.5
 *
 * @param string $file    The path of file will be created.
 * @param string $content The content that will be printed in advanced-cache.php.
 * @return bool
 */
function mainwp_rocket_put_content( $file, $content ) {
	$chmod = mainwp_rocket_get_filesystem_perms( 'file' );
	return mainwp_rocket_direct_filesystem()->put_contents( $file, $content, $chmod );
}


/**
 * Get the permissions to apply to files and folders.
 *
 * Reminder:
 * `$perm = fileperms( $file );`
 *
 *  WHAT                                         | TYPE   | FILE   | FOLDER |
 * ----------------------------------------------+--------+--------+--------|
 * `$perm`                                       | int    | 33188  | 16877  |
 * `substr( decoct( $perm ), -4 )`               | string | '0644' | '0755' |
 * `substr( sprintf( '%o', $perm ), -4 )`        | string | '0644' | '0755' |
 * `$perm & 0777`                                | int    | 420    | 493    |
 * `decoct( $perm & 0777 )`                      | string | '644'  | '755'  |
 * `substr( sprintf( '%o', $perm & 0777 ), -4 )` | string | '644'  | '755'  |
 *
 * @since  3.2.4
 * @author Grégory Viguier
 *
 * @param  string $type The type: 'dir' or 'file'.
 * @return int          Octal integer.
 */
function mainwp_rocket_get_filesystem_perms( $type ) {
	static $perms = [];

	// Allow variants.
	switch ( $type ) {
		case 'dir':
		case 'dirs':
		case 'folder':
		case 'folders':
			$type = 'dir';
			break;

		case 'file':
		case 'files':
			$type = 'file';
			break;

		default:
			return 0755;
	}

	if ( isset( $perms[ $type ] ) ) {
		return $perms[ $type ];
	}

	// If the constants are not defined, use fileperms() like WordPress does.
	switch ( $type ) {
		case 'dir':
			if ( defined( 'FS_CHMOD_DIR' ) ) {
				$perms[ $type ] = FS_CHMOD_DIR;
			} else {
				$perms[ $type ] = fileperms( ABSPATH ) & 0777 | 0755;
			}
			break;

		case 'file':
			if ( defined( 'FS_CHMOD_FILE' ) ) {
				$perms[ $type ] = FS_CHMOD_FILE;
			} else {
				$perms[ $type ] = fileperms( ABSPATH . 'index.php' ) & 0777 | 0644;
			}
	}

	return $perms[ $type ];
}


/**
 * Instanciate the filesystem class
 *
 * @since 2.10
 *
 * @return object WP_Filesystem_Direct instance
 */
function mainwp_rocket_direct_filesystem() {
	require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
	require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
	return new WP_Filesystem_Direct( new StdClass() );
}

