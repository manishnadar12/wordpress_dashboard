jQuery( document ).ready(function($) {

	// Trigger the Show/Hide plugin action
	$( '.mwp_rocket_showhide_plugin' ).on( 'click', function() {
		mainwp_rocket_plugin_showhide_start_specific( $( this ), false );
		return false;
	} );

	// Trigger the plugin activation action
	$( '.mwp_rocket_active_plugin' ).on( 'click', function() {
		mainwp_rocket_plugin_active_start_specific( $( this ), false );
		return false;
	} );

	// Trigger the update plugin action
	$( '.mwp_rocket_upgrade_plugin' ).on( 'click', function() {
		mainwp_rocket_plugin_upgrade_start_specific( $( this ), false );
		return false;
	} );

	// Check all checkboxes
	jQuery( '#mainwp-rocket-sites-table th input[type="checkbox"]' ).change( function () {
		var checkboxes = jQuery( '#mainwp-rocket-sites-table' ).find( ':checkbox' );
    if ( jQuery( this ).prop( 'checked' ) ) {
      checkboxes.prop( 'checked', true );
    } else {
      checkboxes.prop( 'checked', false );
    }
	} );

	// Trigger the bulk actions
	$( '#wprocket_plugin_doaction_btn' ).on( 'click', function() {
		var bulk_act = $( '#mwp_rocket_plugin_action' ).val();
		mainwp_rocket_plugin_do_bulk_action( bulk_act );
	} );

	$( '.rocket_individual_settings_save_btn' ).on('click', function() {
		var statusEl = jQuery(this).parent().find( '.status' );
        console.log('ok');
		data = {
			action: 'mainwp_rocket_site_override_settings',
			wprocketRequestSiteID: $( 'input[name=mainwp_rocket_current_site_id]' ).val(),
			override: $( '#mainwp_rocket_override_general_settings' ).is( ":checked" ) ? 1 : 0,
			_wprocketNonce: mainwp_rocket_loc.nonce
		};
        statusEl.html( '<i class="notched circle loading icon"></i> Saving. Please wait...' ).fadeIn();
		jQuery.post( ajaxurl, data, function( response ) {
			if (response) {
				if (response.error) {
					statusEl.html( response.error );
				} else if ( response.result == 'SUCCESS' ) {
					statusEl.html( '<i class="green check icon"></i> Saved successfully.' );
					setTimeout(function() {
						statusEl.fadeOut();
                        window.location.reload();
					}, 1000);

				} else {
					statusEl.html( '<i class="red times icon"></i> Saving failed. Please, try again.' );
				}
			} else {
				statusEl.html( '<i class="red times icon"></i> Saving failed. Please, try again.' );
			}
		}, 'json');

		return false;
	});

    $( '#mainwp-rocket-load-optimize-db-info' ).on('click', function() {
		var statusEl = jQuery( this ).parent().find( '.status' );
        var site_id = $( 'input[name=mainwp_rocket_current_site_id]' ).val();
		data = {
			action: 'mainwp_rocket_reload_optimize_info',
			wprocketRequestSiteID: site_id,
			_wprocketNonce: mainwp_rocket_loc.nonce
		};
        statusEl.html( '<i class="notched circle loading icon"></i>' ).fadeIn();
        jQuery( this ).attr('disabled', true);
        var e = this;
		jQuery.post(ajaxurl, data, function(response) {
            jQuery( e ).attr('disabled', false);
			if (response) {
				if (response.error) {
					statusEl.html( response.error );
				} else if (response.result == 'SUCCESS') {
					statusEl.html( '<i class="green check icon"></i>' );
                    if (response.optimize_info) {
                        jQuery('span#opt-info-total_revisions').show().find('.count-info').html(response.optimize_info.total_revisions);
                        jQuery('span#opt-info-total_auto_draft').show().find('.count-info').html(response.optimize_info.total_auto_draft);
                        jQuery('span#opt-info-total_trashed_posts').show().find('.count-info').html(response.optimize_info.total_trashed_posts);

                        jQuery('span#opt-info-total_spam_comments').show().find('.count-info').html(response.optimize_info.total_spam_comments);
                        jQuery('span#opt-info-total_trashed_comments').show().find('.count-info').html(response.optimize_info.total_trashed_comments);
                        jQuery('span#opt-info-total_expired_transients').show().find('.count-info').html(response.optimize_info.total_expired_transients);
                        jQuery('span#opt-info-total_all_transients').show().find('.count-info').html(response.optimize_info.total_all_transients);
                        jQuery('span#opt-info-total_optimize_tables').show().find('.count-info').html(response.optimize_info.total_optimize_tables);
                    }
                    setTimeout(function() {
                        statusEl.fadeOut();
                    }, 3000);
				} else {
					statusEl.html( '<i class="red times icon"></i>' );
				}
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		}, 'json' );

		return false;
	});

	// Close modal and reload page.
	$( '#mainwp-rocket-sync-data-modal .ui.reload.cancel.button' ).on( 'click', function() {
		window.location.href = "admin.php?page=Extensions-Mainwp-Rocket-Extension";
	} );

	// Manage Child options
	$( '.mainwp-parent-field input[type="checkbox"]' ).change( function() {
		if( this.checked ) {
			$( this ).closest( '.mainwp-parent-field' ).next( '.mainwp-child-field' ).fadeIn();
		} else {
			$( this ).closest( '.mainwp-parent-field' ).next( '.mainwp-child-field' ).fadeOut();
		}
	} );
    // Manage Child options
	$( '.mainwp-parent2-field input[type="checkbox"]' ).change( function() {
		if( this.checked ) {
			$( this ).closest( '.mainwp-parent2-field' ).find( '.mainwp-child2-field' ).fadeIn();
		} else {
			$( this ).closest( '.mainwp-parent2-field' ).find( '.mainwp-child2-field' ).fadeOut();
		}
	} );

    jQuery( document ).on( 'click', '#mainwp-rocket-cname-add', function () {
        var addnew = jQuery('#cdn-cnames-field-creator').html();
        jQuery( '#mainwp-rocket-cname-add' ).before( addnew );
        return false;
    });

    jQuery( document ).on( 'click', '#mainwp-rocket-cname-remove', function () {
        jQuery( this ).closest( '.cdn-cnames-field' ).remove();
        return false;
    } );

} );

// Clear cache on a single site
mainwp_rocket_dashboard_tab_purge_all = function( pSiteId, pObj ) {
	var row = jQuery( 'tr[website-id=' + pSiteId + ']' )

	data = {
		action: 'mainwp_rocket_purge_cache_all',
		wprocketRequestSiteID: pSiteId,
		_wprocketNonce: mainwp_rocket_loc.nonce,
		where: 'dashboard_tab'
	};

	row.append( '<div class="ui active inverted dimmer"><div class="ui text loader">Clearing cache...</div></div>');

	jQuery.post( ajaxurl, data, function( response ) {

		if ( response ) {
			if ( response.error ) {
				row.find( '.ui.text' ).html( response.error );
				setTimeout( function() {
					window.location.reload();
				}, 2000 );
			} else if ( response.message ) {
				row.find( '.ui.text' ).html( response.error );
				setTimeout( function() {
					window.location.reload();
				}, 2000 );
			} else if (response.result == 'SUCCESS') {
				row.find( '.ui.text' ).html( __( 'Cache cleared sucessfully.' ) );
				setTimeout( function() {
					row.find( '.ui.dimmer' ).fadeOut();
				}, 2000 );
			} else {
				row.find( '.ui.text' ).html( __( 'Undefined error occurred.' ) );
				setTimeout( function() {
					window.location.reload();
				}, 2000 );
			}
		} else {
			row.find( '.ui.text' ).html( __( 'Undefined error occurred.' ) );
			setTimeout( function() {
				window.location.reload();
			}, 2000 );
		}
	}, 'json');
}

var rocket_bulkMaxThreads = 3;
var rocket_bulkTotalThreads = 0;
var rocket_bulkCurrentThreads = 0;
var rocket_bulkFinishedThreads = 0;

mainwp_rocket_plugin_do_bulk_action = function( act ) {
	var selector = '';
	switch ( act ) {
		case 'activate-selected':
			selector = 'tbody tr.negative';
			jQuery( selector ).addClass( 'queue' );
			mainwp_rocket_plugin_active_start_next( selector );
			break;
		case 'update-selected':
			selector = 'tbody tr.warning';
			jQuery( selector ).addClass( 'queue' );
			mainwp_rocket_plugin_upgrade_start_next( selector );
			break;
		case 'hide-selected':
			selector = 'tbody tr .mwp_rocket_showhide_plugin[showhide="hide"]';
			jQuery( selector ).addClass( 'queue' );
			mainwp_rocket_plugin_showhide_start_next( selector );
			break;
		case 'show-selected':
			selector = 'tbody tr .mwp_rocket_showhide_plugin[showhide="show"]';
			jQuery( selector ).addClass( 'queue' );
			mainwp_rocket_plugin_showhide_start_next( selector );
			break;
		case 'load-settings':
			selector = 'tbody tr';
			jQuery( selector ).addClass( 'queue' );
			mainwp_rocket_load_settings_start_next( selector );
			break;
	}
}

// Loop through sites to load settings
mainwp_rocket_load_settings_start_next = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) && ( rocket_bulkCurrentThreads < rocket_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		if ( objProcess.closest( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length == 0 ) {
			continue;
		}
		mainwp_rocket_load_settings_start_specific( objProcess, selector );
	}
}

// Load settings from child site
mainwp_rocket_load_settings_start_specific = function( pObj, selector ) {
	var row = pObj.closest( 'tr' );
	rocket_bulkCurrentThreads++;
	var data = {
		action: 'mainwp_rocket_site_load_existing_settings',
		wprocketRequestSiteID: row.attr( 'website-id' ),
		_wprocketNonce: mainwp_rocket_loc.nonce
	}

	row.append( '<div class="ui active inverted dimmer"><div class="ui text loader">Loading...</div></div>');

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response && response['error'] ) {
			row.find( '.ui.text' ).html( response['error'] );
			setTimeout( function() {
				window.location.reload();
			}, 3000 );
		} else if ( response && response['result'] == 'SUCCESS' ) {
			row.find( '.ui.text' ).html( __( 'Settings loaded sucessfully.' ) );
			setTimeout( function() {
				window.location.reload();
			}, 2000 );
		} else {
			row.find( '.ui.text' ).html( __( 'Undefined error occurred.' ) );
			setTimeout( function() {
				window.location.reload();
			}, 2000 );
		}

		rocket_bulkCurrentThreads--;
		rocket_bulkFinishedThreads++;

		mainwp_rocket_load_settings_start_next( selector );

	}, 'json');

	return false;
}

// Loop through sites to Show/Hide the plugin
mainwp_rocket_plugin_showhide_start_next = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) && ( rocket_bulkCurrentThreads < rocket_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		if ( objProcess.closest( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length == 0 ) {
			continue;
		}
		mainwp_rocket_plugin_showhide_start_specific( objProcess, true, selector );
	}
}

// Show/Hide the plugin
mainwp_rocket_plugin_showhide_start_specific = function( pObj, bulk, selector ) {
	var parent = pObj.closest( 'tr' );
	var showhide = pObj.attr( 'showhide' );
	var statusEl = parent.find( '.wp-rocket-visibility' );
	if ( bulk ) {
		rocket_bulkCurrentThreads++;
	}
	var data = {
		action: 'mainwp_wprocket_showhide_plugin',
		wprocketRequestSiteID: parent.attr( 'website-id' ),
		showhide: showhide
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post( ajaxurl, data, function( response ) {
		pObj.removeClass( 'queue' );
		if ( response && response['error'] ) {
			statusEl.html( '<i class="red times icon"></i>' );
		} else if ( response && response['result'] == 'SUCCESS' ) {
			if ( showhide == 'show' ) {
				pObj.text( "Hide plugin" );
				pObj.attr( 'showhide', 'hide' );
				statusEl.html( __( 'No' ) );
			} else {
				pObj.text( "Unhide plugin" );
				pObj.attr( 'showhide', 'show' );
				statusEl.html( __( 'Yes' ) );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}
		if ( bulk ) {
			rocket_bulkCurrentThreads--;
			rocket_bulkFinishedThreads++;
			mainwp_rocket_plugin_showhide_start_next( selector );
		}
	}, 'json');
	return false;
}

// Loop through sites to update the plugin
mainwp_rocket_plugin_upgrade_start_next = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) && ( objProcess.closest( 'tr' ).prev( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length > 0 ) && ( rocket_bulkCurrentThreads < rocket_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		if ( objProcess.closest( 'tr' ).prev( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length == 0 ) {
			continue;
		}
		mainwp_rocket_plugin_upgrade_start_specific( objProcess, true, selector );
	}
}

// Update the plugin
mainwp_rocket_plugin_upgrade_start_specific = function( pObj, bulk, selector ) {
	var row = pObj.closest( 'tr' );
	var slug = row.attr( 'plugin-slug' );
	var data = {
		action: 'mainwp_rocket_upgrade_plugin',
		wprocketRequestSiteID: row.attr( 'website-id' ),
		type: 'plugin',
		'slugs[]': slug
	}

	if ( bulk ) {
		rocket_bulkCurrentThreads++;
	}

	row.append( '<div class="ui active inverted dimmer"><div class="ui text loader">Updating the plugin...</div></div>');

	jQuery.post( ajaxurl, data, function( response ) {
		pObj.removeClass( 'queue' );
		if ( response && response['error'] ) {
			row.find( '.ui.text' ).html( response['error'] );
			setTimeout( function() {
				window.location.reload();
			}, 2000 );
		} else if ( response && response['upgrades'][slug] ) {
			row.find( '.ui.text' ).html( __( 'Plugin updated sucessfully.' ) );
			setTimeout( function() {
				window.location.reload();
			}, 2000 );
		} else {
			row.find( '.ui.text' ).html( __( 'Undefined error occurred.' ) );
			setTimeout( function() {
				window.location.reload();
			}, 2000 );
		}
		if ( bulk ) {
			rocket_bulkCurrentThreads--;
			rocket_bulkFinishedThreads++;
			mainwp_rocket_plugin_upgrade_start_next( selector );
		}
	}, 'json');
	return false;
}

// Loop through sites to activate the plugin
mainwp_rocket_plugin_active_start_next = function( selector ) {
	while ( ( objProcess = jQuery( selector + '.queue:first' ) ) && ( objProcess.length > 0 ) && ( objProcess.closest( 'tr' ).prev( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length > 0 ) && ( rocket_bulkCurrentThreads < rocket_bulkMaxThreads ) ) {
		objProcess.removeClass( 'queue' );
		if ( objProcess.closest( 'tr' ).prev( 'tr' ).find( 'td input[type="checkbox"]:checked' ).length == 0 ) {
			continue;
		}
		mainwp_rocket_plugin_active_start_specific( objProcess, true, selector );
	}
}

// Activate the plugin
mainwp_rocket_plugin_active_start_specific = function( pObj, bulk, selector ) {
	var row = pObj.closest( 'tr' );
	var slug = row.attr( 'plugin-slug' );
	var data = {
		action: 'mainwp_rocket_active_plugin',
		wprocketRequestSiteID: row.attr( 'website-id' ),
		'plugins[]': [slug]
	}

	if ( bulk ) {
		rocket_bulkCurrentThreads++;
	}

	row.append( '<div class="ui active inverted dimmer"><div class="ui text loader">Activating the plugin...</div></div>');

	jQuery.post( ajaxurl, data, function( response ) {
		pObj.removeClass( 'queue' );
		if ( response && response['error'] ) {
			row.find( '.ui.text' ).html( response['error'] );
			setTimeout( function() {
				window.location.reload();
			}, 2000 );
		} else if ( response && response['result'] ) {
			row.find( '.ui.text' ).html( __( 'Plugin activated sucessfully.' ) );
			row.removeClass( 'negative' );
			setTimeout( function() {
				row.find( '.ui.dimmer' ).fadeOut();
			}, 2000 );
		}
		if ( bulk ) {
			rocket_bulkCurrentThreads--;
			rocket_bulkFinishedThreads++;
			mainwp_rocket_plugin_active_start_next( selector );
		}
	}, 'json');
	return false;
}



mainwp_rocket_individual_perform_action = function(pSiteId, action) {
	if (action == 'save_opts_child_sites') {
        mainwp_rocket_individual_save_opts_to_site( pSiteId );
	} else if (action == 'optimize_database') {
        mainwp_rocket_individual_optimize_database_on_site( pSiteId );
    }
}

mainwp_rocket_individual_save_opts_to_site = function(pSiteId) {
    var messageEl = jQuery( '#mainwp-rocket-message-zone' );
    messageEl.html( '<i class="notched circle loading icon"></i> ' + __( 'Saving settings to child site ...' ) ).show();
	data = {
		action: 'mainwp_rocket_save_opts_to_child_site',
		wprocketRequestSiteID: pSiteId,
		_wprocketNonce: mainwp_rocket_loc.nonce,
		individual: 1,
        optimize_db: jQuery('input[name="rocket_do_optimize_db"]').val() ? true : false
	};
	jQuery.post(ajaxurl, data, function(response) {
		if (response) {
			if (response.error) {
				messageEl.html( response.error );
			} else if (response.message) {
				messageEl.html( response.message );
			} else if ( response.result == 'SUCCESS' ) {
				messageEl.html( '<i class="green check icon"></i> ' + __( 'Successful.' ) );
                messageEl.after( __( '<div class="ui message yellow"><strong>Warning</strong>: Since the WP Rocket plugin is making changes in your htaccess file, you should make sure that everything is ok by visiting your site.</div>' ) );

//				setTimeout(function() {
//					messageEl.fadeOut();
//				}, 3000);

			} else {
				messageEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			messageEl.html( '<i class="red times icon"></i>' );
		}
	}, 'json');
}


mainwp_rocket_individual_optimize_database_on_site = function(pSiteId) {
    var messageEl = jQuery( '#mainwp-rocket-message-zone' );
    messageEl.html( '<i class="notched circle loading icon"></i> ' + __( 'Optimize Database on child site ...' ) ).show();

    data = {
		action: 'mainwp_rocket_optimize_data_on_child_site',
		wprocketRequestSiteID: pSiteId,
		_wprocketNonce: mainwp_rocket_loc.nonce,
		individual: 1
	};
	jQuery.post(ajaxurl, data, function(response) {
		if (response) {
			if (response.error) {
				messageEl.html( response.error );
			} else if (response.message) {
				messageEl.html( response.message );
			} else if (response.result == 'SUCCESS') {
				messageEl.html( '<i class="green check icon"></i> ' + __( 'Successful.' ) );
//				setTimeout(function() {
//					messageEl.fadeOut();
//				}, 3000);
			} else {
				messageEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			messageEl.html( '<i class="red times icon"></i>' );
		}
	}, 'json');
}

// not used?
mainwp_rocket_individual_purge_cloudflare = function(pSiteId) {
    var messageEl = jQuery( '#mainwp-rocket-message-zone' );
    messageEl.html( '<i class="notched circle loading icon"></i>' ).show();
    data = {
		action: 'mainwp_rocket_purge_cloudflare',
		wprocketRequestSiteID: pSiteId,
		_wprocketNonce: mainwp_rocket_loc.nonce,
		individual: 1
	};

	jQuery.post(ajaxurl, data, function(response) {
		if (response) {
			if (response.error) {
				messageEl.html( response.error );
			} else if (response.message) {
				messageEl.html( response.message );
			} else if (response.result == 'SUCCESS') {
				messageEl.html( '<i class="green check icon"></i> ' + __( 'Successful' ) );
//				setTimeout(function() {
//					messageEl.fadeOut();
//				}, 3000);
			} else {
				messageEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			messageEl.html( '<i class="red times icon"></i>' );
		}
	}, 'json');
}


mainwp_rocket_individual_purge_opcache = function(pSiteId, pObj) {
	var statusEl = jQuery( pObj ).parent().find( '.status' );

	data = {
		action: 'mainwp_rocket_purge_opcache',
		wprocketRequestSiteID: pSiteId,
		_wprocketNonce: mainwp_rocket_loc.nonce,
		individual: 1
	};

    statusEl.html( '<i class="notched circle loading icon"></i>' ).fadeIn();
	jQuery.post(ajaxurl, data, function(response) {
		if (response) {
			if (response.error) {
				statusEl.html( response.error );
			} else if (response.message) {
				statusEl.html( response.message );
			} else if (response.result == 'SUCCESS') {
				statusEl.html( '<i class="green check icon"></i>' );
				setTimeout(function() {
					statusEl.fadeOut();
				}, 3000);
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}
	}, 'json');
}


mainwp_rocket_individual_purge_all = function(pSiteId, pObj) {
    var statusEl = jQuery( pObj ).parent().find( '.status' );
	data = {
		action: 'mainwp_rocket_purge_cache_all',
		wprocketRequestSiteID: pSiteId,
		_wprocketNonce: mainwp_rocket_loc.nonce,
		individual: 1
	};
    statusEl.html( '<i class="notched circle loading icon"></i>' ).fadeIn();
	jQuery.post(ajaxurl, data, function(response) {

		if (response) {
			if (response.error) {
				statusEl.html( response.error );
			} else if (response.message) {
				statusEl.html( response.message );
			} else if (response.result == 'SUCCESS') {
				statusEl.html( '<i class="green check icon"></i>' );
				setTimeout(function() {
					statusEl.fadeOut();
				}, 3000);
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}
	}, 'json');
}

mainwp_rocket_individual_preload_cache = function(pSiteId, pObj) {
	var statusEl = jQuery( pObj ).parent().find( '.status' );

	data = {
		action: 'mainwp_rocket_preload_cache',
		wprocketRequestSiteID: pSiteId,
		_wprocketNonce: mainwp_rocket_loc.nonce,
		individual: 1
	};
    statusEl.html( '<i class="notched circle loading icon"></i>' ).fadeIn();
	jQuery.post(ajaxurl, data, function(response) {

		if (response) {
			if (response.error) {
				statusEl.html( response.error );
			} else if (response.message) {
				statusEl.html( response.message );
			} else if (response.result == 'SUCCESS') {
				statusEl.html( '<i class="red times icon"></i>' );
				setTimeout(function() {
					statusEl.fadeOut();
				}, 3000);
			} else {
				statusEl.html( '<i class="green check icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="green check icon"></i>' );
		}
	}, 'json');
}

mainwp_rocket_individual_generate_critical_css = function(pSiteId, pObj) {
	var statusEl = jQuery( pObj ).parent().find( '.status' );

	data = {
		action: 'mainwp_rocket_generate_critical_css',
		wprocketRequestSiteID: pSiteId,
		_wprocketNonce: mainwp_rocket_loc.nonce,
		individual: 1
	};
    statusEl.html( '<i class="notched circle loading icon"></i>' ).fadeIn();
	jQuery.post(ajaxurl, data, function(response) {

		if (response) {
			if (response.error) {

				statusEl.html( response.error );
			} else if (response.message) {
				statusEl.html( response.message );
			} else if (response.result == 'SUCCESS') {
				statusEl.html( '<i class="green check icon"></i>' );
				setTimeout(function() {
					statusEl.fadeOut();
				}, 3000);
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}
	}, 'json');
}

// not used?
mainwp_rocket_individual_download_options = function(pSiteId) {
    var messageEl = jQuery( '#mainwp-rocket-message-zone' );
    messageEl.html( '<i class="notched circle loading icon"></i>' ).show();

	data = {
		action: 'mainwp_rocket_download_options',
		wprocketRequestSiteID: pSiteId,
		_wprocketNonce: mainwp_rocket_loc.nonce,
		individual: 1
	};

	jQuery.post(ajaxurl, data, function(response) {
		if (response) {
			if (response.error) {
				messageEl.html( response.error );
			} else if (response.message) {
				messageEl.html( response.message );
			} else if (response.result == 'SUCCESS') {
				var redirectWindow = window.open( 'admin-post.php?action=mainwp_rocket_export&id=' + pSiteId + '&_wpnonce=' + mainwp_rocket_loc.nonce, '_blank' );
				redirectWindow.location;
			} else {
				messageEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			messageEl.html( '<i class="red times icon"></i>' );
		}
	}, 'json');
}

// Execute actions
mainwp_rocket_perform_action_start_next = function( action ) {
	while ( ( objProcess = jQuery( '.processing-item[status=queue]:first' ) ) && ( objProcess.length > 0 ) && ( rocket_bulkCurrentThreads < rocket_bulkMaxThreads ) ) {
		objProcess.attr( 'status', 'processed' );
		if ( action == 'purge_cloudflare' ) {
			mainwp_rocket_purge_cloudflare_start_specific( objProcess );
		} else if ( action == 'purge_cache_all' ) {
			mainwp_rocket_purge_cache_all_start_specific( objProcess, false );
		} else if ( action == 'preload_cache' ) {
			mainwp_rocket_preload_cache_start_specific( objProcess, false );
		} else if (action == 'generate_critical_css') {
			mainwp_rocket_generate_critical_css_start_specific( objProcess, false );
		} else if ( action == 'save_opts_child_sites' ) {
			mainwp_rocket_save_opts_child_sites_start_specific( objProcess );
		} else if ( action == 'optimize_database' ) {
			mainwp_rocket_optimize_child_sites_start_specific( objProcess );
		} else if ( action == 'purge_opcache' ) {
			mainwp_rocket_purge_opcache_start_specific( objProcess );
		}
	}

	if (rocket_bulkFinishedThreads > 0 && rocket_bulkFinishedThreads == rocket_bulkTotalThreads) {
    var msg = __('Performed action completed');
    if (action != 'optimize_database')
      msg += __('<br/><strong>Warning:</strong> Since the WP Rocket plugin is making changes in your htaccess file, you should make sure that everything is ok by visiting your site.');
    jQuery( '#mainwp_rocket_settings #mainwp-rocket-sync-data-modal' ).append( '<div class="mainwp_info-box">' + msg + '</div>' + '<p><a class="button-primary" href="admin.php?page=Extensions-Mainwp-Rocket-Extension&tab=dashboard">Return to Options</a></p>' );
	}
}

// Loop through sites to clear all cache
mainwp_rocket_rightnow_perform_action_start_next = function( action ) {
	while ( ( objProcess = jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .processing-item[status=queue]:first' ) ) && ( objProcess.length > 0 ) && ( rocket_bulkCurrentThreads < rocket_bulkMaxThreads ) ) {
		objProcess.attr( 'status', 'processed' );
		if ( action == 'purge_cache_all' ) {
			mainwp_rocket_purge_cache_all_start_specific( objProcess, true );
		} else if ( action == 'preload_cache' ) {
			mainwp_rocket_preload_cache_start_specific( objProcess, true );
		}  else if (action == 'generate_critical_css') {
			mainwp_rocket_generate_critical_css_start_specific( objProcess, true );
		}
	}

	if ( rocket_bulkCurrentThreads == 0 ) {
		setTimeout( function () {
			jQuery( '#mainwp-rocket-overview-page-clear-cache-modal').modal( 'hide' );
		}, 3000 );
	}
}

// Clear CloudFlare cache
mainwp_rocket_purge_cloudflare_start_specific = function( objProcess ) {
	var statusEl = objProcess.find( '.status' );
	rocket_bulkCurrentThreads++;
	var data = {
		action: 'mainwp_rocket_purge_cloudflare',
		_wprocketNonce: mainwp_rocket_loc.nonce,
		wprocketRequestSiteID: objProcess.attr( 'site-id' ),
		individual: 0
	};

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response ) {
			if ( response.error ) {
				statusEl.html( response.error );
			} else if ( response.message ) {
				statusEl.html( response.message );
			} else if ( response.result == 'SUCCESS' ) {
				statusEl.html( '<i class="green check icon"></i>' );
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		rocket_bulkCurrentThreads--;
		rocket_bulkFinishedThreads++;

		mainwp_rocket_perform_action_start_next( 'purge_cloudflare' );
	}, 'json');
}

// Clear opCache
mainwp_rocket_purge_opcache_start_specific = function(objProcess) {
	var statusEl = objProcess.find( '.status' );
	rocket_bulkCurrentThreads++;
	var data = {
		action: 'mainwp_rocket_purge_opcache',
		_wprocketNonce: mainwp_rocket_loc.nonce,
		wprocketRequestSiteID: objProcess.attr( 'site-id' ),
		individual: 0
	};

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response ) {
			if ( response.error ) {
				statusEl.html( response.error );
			} else if ( response.message ) {
				statusEl.html( response.message );
			} else if ( response.result == 'SUCCESS' ) {
				statusEl.html( '<i class="green check icon"></i>' );
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}
		rocket_bulkCurrentThreads--;
		rocket_bulkFinishedThreads++;
		mainwp_rocket_perform_action_start_next( 'purge_opcache' );
	}, 'json');
}

// Clear all Cache
mainwp_rocket_purge_cache_all_start_specific = function( objProcess, rightNow ) {
	var statusEl = objProcess.find( '.status' );
	rocket_bulkCurrentThreads++;
	var data = {
		action: 'mainwp_rocket_purge_cache_all',
		_wprocketNonce: mainwp_rocket_loc.nonce,
		wprocketRequestSiteID: objProcess.attr( 'site-id' )
	};

	if ( ! rightNow) {
		data['individual'] = 0;
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response ) {
			if ( response.error ) {
				statusEl.html( response.error );
			} else if ( response.message ) {
				statusEl.html( response.message );
			} else if ( response.result == 'SUCCESS' ) {
				statusEl.html( '<i class="green check icon"></i>' );
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		rocket_bulkCurrentThreads--;
		rocket_bulkFinishedThreads++;

		if ( ! rightNow ) {
			mainwp_rocket_perform_action_start_next( 'purge_cache_all' );
		} else {
			mainwp_rocket_rightnow_perform_action_start_next( 'purge_cache_all' );
		}
	}, 'json');
}

// Preload cache
mainwp_rocket_preload_cache_start_specific = function( objProcess, rightNow ) {
	var statusEl = objProcess.find( '.status' );
	rocket_bulkCurrentThreads++;
	var data = {
		action: 'mainwp_rocket_preload_cache',
		_wprocketNonce: mainwp_rocket_loc.nonce,
		wprocketRequestSiteID: objProcess.attr( 'site-id' ),
	};

	if ( ! rightNow ) {
		data['individual'] = 0;
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response ) {
			if ( response.error ) {
				statusEl.html( response.error );
			} else if (response.message) {
				statusEl.html( response.message );
			} else if (response.result == 'SUCCESS') {
				statusEl.html( '<i class="green check icon"></i>' );
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		rocket_bulkCurrentThreads--;
		rocket_bulkFinishedThreads++;

		if ( ! rightNow ) {
			mainwp_rocket_perform_action_start_next( 'purge_cache_all' );
		} else {
			mainwp_rocket_rightnow_perform_action_start_next( 'purge_cache_all' );
		}
	}, 'json');
}


mainwp_rocket_generate_critical_css_start_specific = function(objProcess, rightNow) {
	var statusEl = objProcess.find( '.status' );
	rocket_bulkCurrentThreads++;
	var data = {
		action: 'mainwp_rocket_generate_critical_css',
		_wprocketNonce: mainwp_rocket_loc.nonce,
		wprocketRequestSiteID: objProcess.attr( 'site-id' ),
	};
	if ( ! rightNow) {
		data['individual'] = 0;
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );
	jQuery.post(ajaxurl, data, function(response) {
		if (response) {
			if (response.error) {
				statusEl.html( response.error );
			} else if (response.message) {
				statusEl.html( response.message );
			} else if (response.result == 'SUCCESS') {
				statusEl.html( '<i class="green check icon"></i>' );
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}
		rocket_bulkCurrentThreads--;
		rocket_bulkFinishedThreads++;

		if ( ! rightNow) {
			mainwp_rocket_perform_action_start_next( 'generate_critical_css' );
    } else {
			mainwp_rocket_rightnow_perform_action_start_next( 'generate_critical_css' );
    }
	}, 'json');
}


// Save settings to child sites
mainwp_rocket_save_opts_child_sites_start_specific = function(objProcess) {
	var statusEl = objProcess.find( '.status' );
	rocket_bulkCurrentThreads++;
	var data = {
		action: 'mainwp_rocket_save_opts_to_child_site',
		_wprocketNonce: mainwp_rocket_loc.nonce,
		wprocketRequestSiteID: objProcess.attr( 'site-id' ),
		individual: 0
	};

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response ) {
			if ( response.error ) {
				statusEl.html( response.error );
			} else if ( response.message ) {
				statusEl.html( response.message );
			} else if ( response.result == 'SUCCESS' ) {
				statusEl.html( '<i class="green check icon"></i>' );
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		rocket_bulkCurrentThreads--;
		rocket_bulkFinishedThreads++;

		mainwp_rocket_perform_action_start_next( 'save_opts_child_sites' );
	}, 'json');
}

// Optimize database
mainwp_rocket_optimize_child_sites_start_specific = function(objProcess) {
	var statusEl = objProcess.find( '.status' );
	rocket_bulkCurrentThreads++;
	var data = {
		action: 'mainwp_rocket_optimize_data_on_child_site',
		_wprocketNonce: mainwp_rocket_loc.nonce,
		wprocketRequestSiteID: objProcess.attr( 'site-id' ),
		individual: 0
	};

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response ) {
			if ( response.error ) {
				statusEl.html( response.error );
			} else if ( response.message ) {
				statusEl.html( response.message );
			} else if ( response.result == 'SUCCESS' ) {
				statusEl.html( '<i class="green check icon"></i>' );
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}
		rocket_bulkCurrentThreads--;
		rocket_bulkFinishedThreads++;
		mainwp_rocket_perform_action_start_next( 'optimize_database' );
	}, 'json');
}

// Clear modal on the Individual Overview page
mainwp_rocket_rightnow_clearcache_individual = function(  siteId ) {

	jQuery( '#mainwp-rocket-overview-page-clear-cache-modal').modal( 'show' );
	jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui active inverted dimmer"><div class="ui text loader"></div></div>' );

	data = {
		action: 'mainwp_rocket_purge_cache_all',
		wprocketRequestSiteID: siteId,
		_wprocketNonce: mainwp_rocket_loc.nonce
	};

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response ) {
			if ( response.error ) {
				jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui red message">' + response.error + '</div>' );
			} else if ( response.message ) {
				jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui yellow message">' + response.message + '</div>' );
			} else if ( response.result == 'SUCCESS' ) {
				jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui green message">' + __( 'Process completed successfully!' ) + '</div>' );
				setTimeout( function () {
					jQuery( '#mainwp-rocket-overview-page-clear-cache-modal').modal( 'hide' );
				}, 3000 );
			} else {
				jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui red message">' + __( 'Undefined error occurred. Please try again.' ) + '</div>' );
			}
		} else {
			jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui red message">' + __( 'Undefined error occurred. Please try again.' ) + '</div>' );
		}
	}, 'json');

	return false;
}

//Preload modal on the Individual Overview page
mainwp_rocket_rightnow_preloadcache_individual = function( siteId ) {
	jQuery( '#mainwp-rocket-overview-page-clear-cache-modal').modal( 'show' );
	jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui active inverted dimmer"><div class="ui text loader"></div></div>' );

	data = {
		action: 'mainwp_rocket_preload_cache',
		wprocketRequestSiteID: siteId,
		_wprocketNonce: mainwp_rocket_loc.nonce
	};

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response ) {
			if ( response.error ) {
				jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui red message">' + response.error + '</div>' );
			} else if ( response.message ) {
				jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui yellow message">' + response.message + '</div>' );
			} else if ( response.result == 'SUCCESS' ) {
				jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui green message">' + __( 'Process completed successfully!' ) + '</div>' );
				setTimeout( function () {
					jQuery( '#mainwp-rocket-overview-page-clear-cache-modal').modal( 'hide' );
				}, 3000 );
			} else {
				jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui red message">' + __( 'Undefined error occurred. Please try again.' ) + '</div>' );
			}
		} else {
			jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui red message">' + __( 'Undefined error occurred. Please try again.' ) + '</div>' );
		}
	}, 'json');
	return false;
}

// Clear/Preload modal on the Overview page
mainwp_rocket_rightnow_loadsites = function( action ) {

	if ( action == 'purge_cache_all' ) {
		var msg = 'clear';
	} else {
		var msg = 'preload';
	}
	//confirm
	if ( !confirm( 'You are about to ' + msg + ' WP Rocket cache on all your child sites. Do you want to proceed?' ) ) {
		return false;
	}

	jQuery( '#mainwp-rocket-overview-page-clear-cache-modal').modal( 'show' );
	jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui active inverted dimmer"><div class="ui text loader"></div></div>' );

	data = {
		action: 'mainwp_rocket_rightnow_load_sites',
		_wprocketNonce: mainwp_rocket_loc.nonce,
		rightnow_action: action
	};

	jQuery.post( ajaxurl, data, function( response ) {
		if ( response ) {
			jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( response );
			rocket_bulkCurrentThreads = 0;
			rocket_bulkFinishedThreads = 0;
			rocket_bulkTotalThreads = jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .processing-item[status=queue]' ).length;
			if ( rocket_bulkTotalThreads > 0 ) {
				mainwp_rocket_rightnow_perform_action_start_next( action );
			}
		} else {
			jQuery( '#mainwp-rocket-overview-page-clear-cache-modal .content' ).html( '<div class="ui red message">' + __( 'Undefined error occurred. Please try again.' ) + '</div>' );
		}
	} );
	return false;
}


jQuery( document ).on('click', '#mainwp-do-sites-bulk-actions', function() {
        var action = jQuery( "#mainwp-sites-bulk-actions-menu" ).dropdown( "get value" );
	if ( action == '' )
            return false;

	if (action == 'clear_wprocket_cache' || action == 'preload_wprocket_cache') {
		if (bulkManageSitesTaskRunning) {
                    return false;
                }

                var msg = 'You are about to clear WP Rocket Cache on the selected sites?';
                if (action == 'preload_wprocket_cache')
                    msg = 'You are about to preload WP Rocket Cache on the selected sites?';

                if (!confirm(msg)) {
                    return false;
                }

		managesites_bulk_init();

		bulkManageSitesTotal = jQuery( '#mainwp-manage-sites-body-table .check-column INPUT:checkbox:checked[status="queue"]' ).length;
		bulkManageSitesTaskRunning = true;

		if (action == 'clear_wprocket_cache') {
			mainwp_managesites_bulk_clear_rocket_cache_next();
		} else if (action == 'preload_wprocket_cache') {
			mainwp_managesites_bulk_preload_rocket_cache_next();
		}
		return false;
	}

});

mainwp_managesites_bulk_clear_rocket_cache_next = function() {
	while ((checkedBox = jQuery( '#mainwp-manage-sites-body-table .check-column INPUT:checkbox:checked[status="queue"]:first' )) && (checkedBox.length > 0) && (bulkManageSitesCurrentThreads < bulkManageSitesMaxThreads)) {
		mainwp_managesites_bulk_clear_rocket_cache_specific( checkedBox );
	}
	if ((bulkManageSitesTotal > 0) && (bulkManageSitesFinished == bulkManageSitesTotal)) {
		managesites_bulk_done();
		setHtml( '#mainwp-message-zone', __( "Bulk Clear WP Rocket Cache finished." ) );
	}
}

mainwp_managesites_bulk_clear_rocket_cache_specific = function(pCheckedBox) {
	pCheckedBox.attr( 'status', 'running' );
	var rowObj = pCheckedBox.closest( 'tr' );
	bulkManageSitesCurrentThreads++;
	var loadingEl = rowObj.find( '.column-site-bulk i' );
	var statusEl = rowObj.find( '.column-site-bulk .status' );
	loadingEl.show();
	statusEl.hide();
	var data = mainwp_secure_data({
		action: 'mainwp_rocket_purge_cache_all',
		wprocketRequestSiteID: rowObj.attr( 'siteid' ),
		_wprocketNonce: mainwp_rocket_loc.nonce
	});

	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		data: data,
		success: function(response) {
			bulkManageSitesCurrentThreads--;
			bulkManageSitesFinished++;
			loadingEl.hide();

			if (response) {
				if (response.error) {
					statusEl.html( '<i class="red times icon"></i>' );
				} else if (response.message) {
					statusEl.html( '<i class="red times icon"></i>' );
				} else if (response.result == 'SUCCESS') {
					statusEl.html( '<i class="green check icon"></i>' );
					setTimeout(function() {
                                            statusEl.fadeOut();
					}, 3000);
				} else {
                                    statusEl.html( '<i class="red times icon"></i>' );
				}
			} else {
                            statusEl.html( '<i class="red times icon"></i>' );
			}
			statusEl.show();

			mainwp_managesites_bulk_clear_rocket_cache_next();
		},
		dataType: 'json'
	});
	return false;
};


mainwp_managesites_bulk_preload_rocket_cache_next = function() {
	while ((checkedBox = jQuery( '#mainwp-manage-sites-body-table .check-column INPUT:checkbox:checked[status="queue"]:first' )) && (checkedBox.length > 0) && (bulkManageSitesCurrentThreads < bulkManageSitesMaxThreads)) {
		mainwp_managesites_bulk_preload_rocket_cache_specific( checkedBox );
	}
	if ((bulkManageSitesTotal > 0) && (bulkManageSitesFinished == bulkManageSitesTotal)) {
		managesites_bulk_done();
		setHtml( '#mainwp-message-zone', __( "Bulk Clear WP Rocket Cache finished." ) );
	}
}

mainwp_managesites_bulk_preload_rocket_cache_specific = function(pCheckedBox) {
	pCheckedBox.attr( 'status', 'running' );
	var rowObj = pCheckedBox.closest( 'tr' );
	bulkManageSitesCurrentThreads++;
	var loadingEl = rowObj.find( '.column-site-bulk i' );
	var statusEl = rowObj.find( '.column-site-bulk .status' );
	loadingEl.show();
	statusEl.hide();
	var data = mainwp_secure_data({
		action: 'mainwp_rocket_preload_cache',
		wprocketRequestSiteID: rowObj.attr( 'siteid' ),
		_wprocketNonce: mainwp_rocket_loc.nonce
	});

	jQuery.ajax({
		type: 'POST',
		url: ajaxurl,
		data: data,
		success: function(response) {
			bulkManageSitesCurrentThreads--;
			bulkManageSitesFinished++;
			loadingEl.hide();

			if (response) {
				if (response.error) {
					statusEl.html( '<i class="red times icon"></i>' );
				} else if (response.message) {
					statusEl.html( response.message );
				} else if (response.result == 'SUCCESS') {
					statusEl.html( '<i class="green check icon"></i>' );
					setTimeout(function() {
                                            statusEl.fadeOut();
					}, 3000);
				} else {
					statusEl.html( '<i class="red times icon"></i>' );
				}
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
			statusEl.show();
			mainwp_managesites_bulk_preload_rocket_cache_next();
		},
		dataType: 'json'
	});
	return false;
};

mainwp_rightnow_rocket_show_box = function(action) {
	var rocketBox = jQuery( '#rightnow-rocket-box' );
	var title = (action == 'purge_cache_all' ? __( 'Clearing WP Rocket cache on child sites' ) : __( 'Pre-loading WP Rocket cache on child sites' ));
	rocketBox.attr( 'title', title );
	jQuery( 'div[aria-describedby="rightnow-rocket-box"]' ).find( '.ui-dialog-title' ).html( title );

	rocketBox.dialog({
		resizable: false,
		height: 350,
		width: 500,
		modal: true,
		close: function(event, ui) {
			jQuery( '#rightnow-rocket-box' ).dialog( 'destroy' );
		}
	});

};
