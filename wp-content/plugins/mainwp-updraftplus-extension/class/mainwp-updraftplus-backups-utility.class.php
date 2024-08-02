<?php

class MainWP_Updraftplus_Backups_Utility {

	public static function get_timestamp( $timestamp ) {
			$gmtOffset = get_option( 'gmt_offset' );

			return ($gmtOffset ? ($gmtOffset * HOUR_IN_SECONDS) + $timestamp : $timestamp);
	}

	public static function format_timestamp( $timestamp ) {
			return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
	}

	static function ctype_digit( $str ) {
			return (is_string( $str ) || is_int( $str ) || is_float( $str )) && preg_match( '/^\d+\z/', $str );
	}

	public static function map_site( &$website, $keys ) {
			$outputSite = array();
		foreach ( $keys as $key ) {
				$outputSite[ $key ] = $website->$key;
		}
			return $outputSite;
	}

	static function get_getdata_authed( $website, $paramValue, $paramName = 'where', $open_location = '' ) {
			$params = array();
		if ( $website && '' != $paramValue ) {
				$nonce = rand( 0, 9999 );
			if ( (0 == $website->nossl) && function_exists( 'openssl_verify' ) ) {
					$nossl = 0;
					openssl_sign( $paramValue . $nonce, $signature, base64_decode( $website->privkey ) );
			} else {
					$nossl = 1;
					$signature = md5( $paramValue . $nonce . $website->nosslkey );
			}
				$signature = base64_encode( $signature );

				$params = array(
					'login_required' => 1,
					'user' => $website->adminname,
					'mainwpsignature' => rawurlencode( $signature ),
					'nonce' => $nonce,
					'nossl' => $nossl,
					'open_location' => $open_location,
					$paramName => rawurlencode( $paramValue ),
				);
		}

			$url = (isset( $website->siteurl ) && $website->siteurl != '' ? $website->siteurl : $website->url);
			$url .= (substr( $url, -1 ) != '/' ? '/' : '');
			$url .= '?';

		foreach ( $params as $key => $value ) {
				$url .= $key . '=' . $value . '&';
		}

			return rtrim( $url, '&' );
	}

    public static function pagination( $total, $which = '') {
        if ($total == 0)
            return; // not compatible
        ?>
        <div class="tablenav bottom">
            <div class="alignright actions">
            <?php

        $per_page = MAINWP_UPDRAFTPLUS_PER_PAGE;
		$total_items     = $total;
		$total_pages     = 	ceil( $total / $per_page );


        $pagenum = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 0;
        if ($pagenum > $total_items)
            $pagenum = $total_items;
        $pagenum = max( 1, $pagenum );


		$output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current              = $pagenum;

		$removable_query_args = wp_removable_query_args();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );

		$current_url = remove_query_arg( $removable_query_args, $current_url );

		$page_links = array();

		$total_pages_before = '<span class="paging-input">';
		$total_pages_after  = '</span></span>';

		$disable_first = $disable_last = $disable_prev = $disable_next = false;

		if ( $current == 1 ) {
			$disable_first = true;
			$disable_prev  = true;
		}
		if ( $current == 2 ) {
			$disable_first = true;
		}
		if ( $current == $total_pages ) {
			$disable_last = true;
			$disable_next = true;
		}
		if ( $current == $total_pages - 1 ) {
			$disable_last = true;
		}

		if ( $disable_first ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='first-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( remove_query_arg( 'paged', $current_url ) ),
				__( 'First page' ),
				'&laquo;'
			);
		}

		if ( $disable_prev ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='prev-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', max( 1, $current - 1 ), $current_url ) ),
				__( 'Previous page' ),
				'&lsaquo;'
			);
		}

		if ( 'bottom' === $which ) {
			$html_current_page  = $current;
			$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		} else {
			$html_current_page = sprintf(
				"%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
				'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
				$current,
				strlen( $total_pages )
			);
		}
		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[]     = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

		if ( $disable_next ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='next-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
				__( 'Next page' ),
				'&rsaquo;'
			);
		}

		if ( $disable_last ) {
			$page_links[] = '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
		} else {
			$page_links[] = sprintf(
				"<a class='last-page button' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
				esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
				__( 'Last page' ),
				'&raquo;'
			);
		}

		$pagination_links_class = 'pagination-links';

		$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages ) {
			$page_class = $total_pages < 2 ? ' one-page' : '';
		} else {
			$page_class = ' no-pages';
		}
		$_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $_pagination;

        ?>
            </div>
        </div>
        <?php
    }
}
