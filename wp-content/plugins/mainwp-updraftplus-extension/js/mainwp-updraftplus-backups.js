jQuery( document ).ready(function ($) {
	$( '#mwp_updraftplus_dashboard_tab_lnk' ).on('click', function () {
            var href = $(this).attr('href');
                if (href == "#" || href == '') {
		showUpdraftplusTab( true, false, false, false, false );
		return false;
            }
	});

	$( '#mwp_updraftplus_scheduled_tab_lnk' ).on('click', function () {
            var href = $(this).attr('href');
            if (href == "#" || href == '') {
		showUpdraftplusTab( false, true, false, false, false );
		return false;
            }
	});

	$( '#mwp_updraftplus_status_tab_lnk' ).on('click', function () {
		showUpdraftplusTab( false, false, true, false, false );
		return false;
	});
	$( '#mwp_updraftplus_backup_tab_lnk' ).on( 'click', function () {
    var href = $(this).attr('href');
    if (href == "#" || href == '') {
      mainwp_updraft_openrestorepanel( 1 );
      showUpdraftplusTab( false, false, false, true, false );
      return false;
    }
	});
	$( '#mwp_updraftplus_setting_tab_lnk' ).on('click', function () {
                var href = $(this).attr('href');
                if (href == "#" || href == '') {
                    showUpdraftplusTab( false, false, false, false, true );
                    return false;
                }
	});

	//    $('.updraftplus_tabs_lnk a').on('click', function () {
	//        if (jQuery(this).attr('id') != 'mwp_updraftplus_dashboard_tab_lnk') {
	//            jQuery('.updraftplus_tabs_lnk a').removeClass('mainwp_action_down');
	//            jQuery(this).addClass('mainwp_action_down');
	//        }
	//    });

	//    jQuery('#mwp-updraftplus-backupnow-modal').dialog({
	//        autoOpen: false, height: 355, width: 480, modal: true,
	//        close: function(event, ui) {jQuery('#mwp-updraftplus-backupnow-modal').dialog('destroy');}
	//    });

	$( '#mwp_updraftplus_settings_save_btn' ).on('click', function () {
		var statusEl = jQuery( '#mwp_updraftplus_site_save_settings_status' );
		var loaderEl = jQuery( '#updraftplus_site_settings img.loader' );
		statusEl.hide();
		loaderEl.show();
                var over = $( '#mainwp_updraftplus_override_general_settings' ).is( ":checked" ) ? 1 : 0;
		data = {
			action: 'mainwp_updraftplus_site_override_settings',
			updraftRequestSiteID: $( 'input[name=mainwp_updraftplus_settings_site_id]' ).val(),
			override: over
		};
		jQuery.post(ajaxurl, data, function (response) {
			loaderEl.hide();
			if (response) {
				if (response.error) {
					statusEl.css( 'color', 'red' );
					statusEl.html( response.error );
				} else if (response.result == 'success') {
					statusEl.css( 'color', '#21759B' );
					statusEl.html( __( 'Saved.' ) );
					setTimeout(function ()
                                        {
                                            statusEl.fadeOut();
					}, 3000);
                                        if (over) {
                                            jQuery('input[name=save-general-settings-to-site]').removeAttr('disabled').show();
                                        } else {
                                            jQuery('input[name=save-general-settings-to-site]').attr('disabled', 'true').hide();
                                        }
				} else {
					statusEl.css( 'color', 'red' );
					statusEl.html( 'Undefined error' );
				}
			} else {
				statusEl.css( 'color', 'red' );
				statusEl.html( 'Undefined error' );
			}
			statusEl.fadeIn();
		}, 'json');

		return false;
	});

        jQuery('input[name=save-general-settings-to-site]').on('click', function () {
            var site_id = $( 'input[name=mainwp_updraftplus_settings_site_id]' ).val();
            mainwp_updraftplus_individual_save_settings(site_id, 1);
        });


        jQuery(document).on('click', '.mwp-updraftplus-restore-btn', function(){
            var siteid = jQuery(this).closest('.mwp_updraft_content_wrapper').attr('site-id');
            var loc = 'options-general.php?page=updraftplus#updraft-navtab-backups-content';
            loc = 'admin.php?page=SiteOpen&newWindow=yes&websiteid=' + siteid + '&location=' + mainwp_updraft_utf8_to_b64(loc);
            window.open(loc,'_blank');
        });
        
        jQuery( '#mwp_updraftplus_refresh' ).on('click', function () {
                                
		var messageEl = jQuery( '#mwp_updraft_info' );
		messageEl.hide();
		updraftplus_scheduled_bulkTotalThreads = jQuery( '#the-updraftplus-scheduled-list td.check-column input[type="checkbox"]:checked' ).length;
		if (updraftplus_scheduled_bulkTotalThreads == 0) {
			messageEl.html( '<i class="close icon"></i> You must select at least one item.' ).show();
			return false;
		}

                var selector = '#the-updraftplus-scheduled-list tr';

		jQuery( selector ).find( '.check-column input[type="checkbox"]:checked' ).addClass( 'queue' );

		jQuery( this ).attr( 'disabled', 'true' );

		jQuery( '#the-updraftplus-scheduled-list .check-column input[type="checkbox"]:checked' ).closest( 'tr' ).addClass( 'queue' );

		updraftplus_scheduled_bulkFinishedThreads = 0;

		mainwp_updraftplus_scheduledbackups_start_next( selector );
	});


});

showUpdraftplusTab = function ( dashboard, scheduled, status, backup, setting ) {

	var dashboard_tab_lnk = jQuery( "#mwp_updraftplus_dashboard_tab_lnk" );
	if ( dashboard ) {
		dashboard_tab_lnk.addClass( 'active' );
	} else {
		dashboard_tab_lnk.removeClass( 'active' );
	}

	var scheduled_tab_lnk = jQuery( "#mwp_updraftplus_scheduled_tab_lnk" );
	if ( scheduled ) {
		scheduled_tab_lnk.addClass( 'active' );
	} else {
		scheduled_tab_lnk.removeClass( 'active' );
	}

	var status_tab_lnk = jQuery( "#mwp_updraftplus_status_tab_lnk" );
	if ( status ) {
		status_tab_lnk.addClass( 'active' );
	} else {
		status_tab_lnk.removeClass( 'active' );
	}

	var backup_tab_lnk = jQuery( "#mwp_updraftplus_backup_tab_lnk" );
	if ( backup ) {
		backup_tab_lnk.addClass( 'active' );
	} else {
		backup_tab_lnk.removeClass( 'active' );
	}

	var setting_tab_lnk = jQuery( "#mwp_updraftplus_setting_tab_lnk" );
	if ( setting ) {
		setting_tab_lnk.addClass( 'active' );
	} else {
		setting_tab_lnk.removeClass( 'active' );
	}

	var dashboard_tab = jQuery( "#mwp_updraftplus_dashboard_tab" );
	var scheduled_tab = jQuery( "#mwp_updraftplus_nextscheduled_tab" );
	var status_tab = jQuery( "#mwp_updraftplus_status_tab" );
	var backup_tab = jQuery( "#mwp_updraftplus_backup_tab" );
	var setting_tab = jQuery( "#mwp_updraftplus_setting_tab" );

	if ( dashboard ) {
		dashboard_tab.show();
		scheduled_tab.hide();
		status_tab.hide();
		backup_tab.hide();
		setting_tab.hide();
	} else if ( scheduled ) {
		dashboard_tab.hide();
		scheduled_tab.show();
		status_tab.hide();
		backup_tab.hide();
		setting_tab.hide();
	} else if ( status ) {
		dashboard_tab.hide();
		scheduled_tab.hide();
		status_tab.show();
		backup_tab.hide();
		setting_tab.hide();
	} else if ( backup ) {
		dashboard_tab.hide();
		scheduled_tab.hide();
		status_tab.hide();
		backup_tab.show();
		setting_tab.hide();
	} else if ( setting ) {
		dashboard_tab.hide();
		scheduled_tab.hide();
		status_tab.hide();
		backup_tab.hide();
		setting_tab.show();
	}

};

	jQuery( document ).on ('click', '.mwp_updraftplus_active_plugin', function () {
		mainwp_updraftplus_plugin_active_start_specific( jQuery( this ), false );
		return false;
	});

	jQuery( '.mwp_updraftplus_upgrade_plugin' ).on('click', function () {
		mainwp_updraftplus_plugin_upgrade_start_specific( jQuery( this ), false );
		return false;
	});

	jQuery( document ).on ('click', '.mwp_updraftplus_showhide_plugin', function () {
		mainwp_updraftplus_plugin_showhide_start_specific( jQuery( this ), false );
		return false;
	});

	jQuery( document ).on('click', '#updraftplus_plugin_doaction_btn', function () {
		var bulk_act = jQuery( '#mwp_updraftplus_plugin_action' ).val();
		mainwp_updraftplus_plugin_do_bulk_action( bulk_act );
	});

	
//});

var updraftplus_bulkMaxThreads = 3;
var updraftplus_bulkTotalThreads = 0;
var updraftplus_bulkCurrentThreads = 0;
var updraftplus_bulkFinishedThreads = 0;

function mainwp_updraft_general_updatehistory( pRescan, pRemotescan ) {

	var loadingEl = jQuery( '.mwp_updraft_general_rescan_links .loading' );
	var errorEl = jQuery( '#mwp_updraft_backup_error' );
	errorEl.hide();
	loadingEl.show();
	var data = {
		action: 'mainwp_updraftplus_load_sites',
		what: 'update_history',
		rescan: pRescan,
		remotescan: pRemotescan
	};

	jQuery.post( ajaxurl, data, function ( response ) {
		loadingEl.hide();
		if ( response ) {
			if ( response.error ) {
				errorEl.html( response.error ).show();
			} else {
				jQuery( '#mwp_updraftplus_backup_tab' ).html( response );
				updraftplus_bulkTotalThreads = jQuery( '.siteItemProcess[status=queue]' ).length;
				if ( updraftplus_bulkTotalThreads > 0 ) {
					mainwp_updraft_general_rescan_start_next( pRescan, pRemotescan );
				}
			}
		} else {
			errorEl.html( __( "Undefined error." ) ).show();
		}

		setTimeout(function () {
			errorEl.hide();
		}, 5000);
	})
}

mainwp_updraft_general_rescan_start_next = function (pRescan, pRemotescan) {
	while ((objProcess = jQuery( '.siteItemProcess[status=queue]:first' )) && (objProcess.length > 0) && (updraftplus_bulkCurrentThreads < updraftplus_bulkMaxThreads)) {
		objProcess.attr( 'status', 'processed' );
		mainwp_updraft_general_rescan_start_specific( objProcess, pRescan, pRemotescan );
	}

	if (updraftplus_bulkFinishedThreads > 0 && updraftplus_bulkFinishedThreads == updraftplus_bulkTotalThreads) {
		setTimeout( function () {
			location.href = location.href;
		}, 1500 );
	}

}

mainwp_updraft_general_rescan_start_specific = function (objProcess, pRescan, pRemotescan) {
	var statusEl = objProcess.find( '.status' );

	updraftplus_bulkCurrentThreads++;

	var data = {
		action: 'mainwp_updraft_rescan_history_backups',
		updraftRequestSiteID: objProcess.attr( 'site-id' ),
		rescan: pRescan,
		remotescan: pRemotescan,
		generalscan: 1
	};

	statusEl.html( '<i class="notched circle loading icon"></i>' );
	//call the ajax
	jQuery.post(ajaxurl, data, function (response) {
		if (response) {
			if ( response.error ) {
				statusEl.html( '<i class="red times icon"></i>' );
			} else if (response.result == 'success') {
				statusEl.html( __( '<i class="green check icon"></i>' ) );
			} else if (response.result == 'fail') {
				statusEl.html( '<i class="red times icon"></i>' );
			} else if (response.message) {
				statusEl.html( __( '<i class="green check icon"></i>' ) );
			} else {
				statusEl.html( '<i class="red times icon"></i>' );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		updraftplus_bulkCurrentThreads--;
		updraftplus_bulkFinishedThreads++;
		mainwp_updraft_general_rescan_start_next();
	}, 'json');
}


function mainwp_updraft_backupallnow_go(backupnow_nodb, backupnow_nofiles, backupnow_nocloud) {

    var messageEl = jQuery( '#nextscheduled_tab_notice_box' );

    messageEl.hide();
    updraftplus_scheduled_bulkTotalThreads = jQuery( '#the-updraftplus-scheduled-list td.check-column input[type="checkbox"]:checked' ).length;
    console.log( updraftplus_scheduled_bulkTotalThreads );
    
    if (updraftplus_scheduled_bulkTotalThreads == 0) {
            messageEl.html( 'You must select at least one item.' ).fadeIn();
            return false;
    }

    if (jQuery('#backupnow_runascron').is(':checked')) {
        var selectedIds = jQuery.map(jQuery('#the-updraftplus-scheduled-list .check-column input[type="checkbox"]:checked'), function(el) { var parent = jQuery(el).closest( 'tr' ); return parent.attr( 'website-id' ); });
        mainwp_updraft_general_backupallnow_schedule_requests(selectedIds, backupnow_nodb, backupnow_nofiles, backupnow_nocloud);
        return false;
    }

    var selector = '#the-updraftplus-scheduled-list tr';

    jQuery( selector ).find( '.check-column input[type="checkbox"]:checked' ).addClass( 'queue' );

    jQuery( this ).attr( 'disabled', 'true' );

    jQuery( '#the-updraftplus-scheduled-list .check-column input[type="checkbox"]:checked' ).closest( 'tr' ).addClass( 'queue' );


//    var selector = '#the-updraftplus-scheduled-list tr .its-action-working';
//    jQuery( selector ).closest( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).addClass( 'queue' );
//
//    jQuery( '#the-updraftplus-scheduled-list .check-column input[type="checkbox"]:checked' ).closest( 'tr' ).find( '.its-action-working' ).addClass( 'queue' );

    updraftplus_scheduled_bulkFinishedThreads = 0;
    mainwp_updraft_general_backupallnow_start_next( selector, backupnow_nodb, backupnow_nofiles, backupnow_nocloud );
}

mainwp_updraft_general_backupallnow_start_next = function ( selector, backupnow_nodb, backupnow_nofiles, backupnow_nocloud ) {
        while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (objProcess.closest( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length > 0) && (updraftplus_scheduled_bulkCurrentThreads < updraftplus_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		if (objProcess.closest( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_updraft_general_backupallnow_start_specific( objProcess, selector, backupnow_nodb, backupnow_nofiles, backupnow_nocloud );
	}
	if (updraftplus_scheduled_bulkTotalThreads > 0 && updraftplus_scheduled_bulkFinishedThreads == updraftplus_scheduled_bulkTotalThreads) {
		setTimeout(function ()
			{
			//window.location.href = 'admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=gen_schedules';
		}, 1000);
	}
}

mainwp_updraft_general_backupallnow_start_specific = function (pObj, selector, backupnow_nodb, backupnow_nofiles, backupnow_nocloud) {
        var parent = pObj.closest( 'tr' );
        var loaderDimmer = jQuery( '.ui.dimmer' );
	var loader = parent.find( '.its-action-working .loading' );
	var statusEl = parent.find( '.its-action-working .status' );
	updraftplus_scheduled_bulkCurrentThreads++;

        var data = {
		action: 'mainwp_updraft_ajax',
		subaction: 'backupnow',
		nonce: mwp_updraft_credentialtest_nonce,
		backupnow_nodb: backupnow_nodb,
		backupnow_nofiles: backupnow_nofiles,
		backupnow_nocloud: backupnow_nocloud,
                updraftRequestSiteID: parent.attr( 'website-id' )
	};

        loaderDimmer.addClass( 'active' );
	statusEl.hide();
	loader.show();
	jQuery.post(ajaxurl, data, function (response) {
		updraftplus_scheduled_bulkFinishedThreads++;
                loaderDimmer.removeClass( 'active' );
		loader.hide();
		pObj.removeClass( 'queue' );
                try {
                    resp = jQuery.parseJSON(response);
                    if (resp.error) {
                        statusEl.css( 'color', 'red' );
                        statusEl.html( response.error ).show();
                    } else if (resp.m) {
                        statusEl.html(__( 'Successful' )).show();
                    } else {
                        statusEl.css( 'color', 'red' );
                        statusEl.html( 'Undefined error' ).show();
                    }
		} catch (err) {
                    statusEl.css( 'color', 'red' );
                    statusEl.html( 'Undefined error' ).show();
		}
		updraftplus_scheduled_bulkCurrentThreads--;
		mainwp_updraft_general_backupallnow_start_next( selector, backupnow_nodb, backupnow_nofiles, backupnow_nocloud );

	});

	return false;

}

mainwp_updraft_general_backupallnow_schedule_requests = function (pSelectedIds, backupnow_nodb, backupnow_nofiles, backupnow_nocloud) {
   var statusEl = jQuery( '#nextscheduled_tab_message_box' );
   statusEl.html(__( 'Running...' )).show();

   var data = {
		action: 'mainwp_updraft_ajax',
		subaction: 'backupnow_schedule_requests',
		nonce: mwp_updraft_credentialtest_nonce,
        backupnow_nodb: backupnow_nodb,
		backupnow_nofiles: backupnow_nofiles,
		backupnow_nocloud: backupnow_nocloud,
		ids: pSelectedIds
	};

    jQuery.post(ajaxurl, data, function (resp) {

        jQuery('#the-updraftplus-scheduled-list .check-column input[type="checkbox"]:checked').each(function() {
            jQuery(this).prop('checked', false); // Unchecks it
		});
        if (resp.error) {
            statusEl.html( resp.error ).show();
        } else if (resp.ok) {
            statusEl.html(__( 'Schedule backups request successfully' )).show();
        } else {
            statusEl.html( 'Undefined error' ).show();
        }
	}, 'json');

	return false;

}

mainwp_updraftplus_plugin_do_bulk_action = function (act) {
	var selector = '';
	switch (act) {
		case 'activate-selected':
			selector = '#the-mwp-updraftplus-list  tr.negative';
			jQuery( selector ).addClass( 'queue' );
			mainwp_updraftplus_plugin_active_start_next( selector );
			break;
		case 'update-selected':
			selector = '#the-mwp-updraftplus-list  tr.warning';
			jQuery( selector ).addClass( 'queue' );
			mainwp_updraftplus_plugin_upgrade_start_next( selector );
			break;
		case 'hide-selected':
			selector = '#the-mwp-updraftplus-list tr .mwp_updraftplus_showhide_plugin[showhide="hide"]';
			jQuery( selector ).addClass( 'queue' );
			mainwp_updraftplus_plugin_showhide_start_next( selector );
			break;
		case 'show-selected':
			selector = '#the-mwp-updraftplus-list tr .mwp_updraftplus_showhide_plugin[showhide="show"]';
			jQuery( selector ).addClass( 'queue' );
			mainwp_updraftplus_plugin_showhide_start_next( selector );
			break;
	}
}

var updraftplus_scheduled_bulkCurrentThreads = 0;
var updraftplus_scheduled_bulkTotalThreads = 0;
var updraftplus_scheduled_bulkFinishedThreads = 0;

mainwp_updraftplus_scheduledbackups_start_next = function (selector) {
	while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (objProcess.closest( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length > 0) && (updraftplus_scheduled_bulkCurrentThreads < updraftplus_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		if (objProcess.closest( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_updraftplus_scheduledbackups_start_specific( objProcess, selector );
	}
	if (updraftplus_scheduled_bulkTotalThreads > 0 && updraftplus_scheduled_bulkFinishedThreads == updraftplus_scheduled_bulkTotalThreads) {
		setTimeout(function ()
			{
			window.location.href = window.location.href;
		}, 1000);
	}
}


mainwp_updraftplus_scheduledbackups_start_specific = function ( pObj, selector ) {
	var parent = pObj;
	var loader = jQuery( '.ui.dimmer' );
	var error_box = jQuery( '#nextscheduled_tab_notice_box' ) ;
	var success_box = jQuery( '#nextscheduled_tab_message_box' ) ;

	updraftplus_scheduled_bulkCurrentThreads++;

	var data = {
		action: 'mainwp_updraftplus_data_refresh',
		updraftRequestSiteID: parent.attr( 'website-id' )
	}
	loader.addClass( 'active' );
	jQuery.post(ajaxurl, data, function (response) {
		updraftplus_scheduled_bulkFinishedThreads++;
		loader.removeClass( 'active' );
		pObj.removeClass( 'queue' );
		if (response && response['error']) {
			error_box.html( '<i class="close icon"></i>' + response['error'] ).show();
		} else if (response && response.nextsched_current_timegmt) {
			if (response.nextsched_files_timezone) {
				parent.find( '.mwp-scheduled-files' ).html( response.nextsched_files_timezone ); } else {
				parent.find( '.mwp-scheduled-files' ).html( mwp_updraftlion.nothingscheduled ); }

				if (response.nextsched_database_timezone) {
					parent.find( '.mwp-scheduled-database' ).html( response.nextsched_database_timezone ); } else {
					parent.find( '.mwp-scheduled-database' ).html( mwp_updraftlion.nothingscheduled ); }

					if (response.nextsched_current_timezone) {
						parent.find( '.mwp-scheduled-currenttime' ).html( response.nextsched_current_timezone ); } else {
						parent.find( '.mwp-scheduled-currenttime' ).html( '' ); }
						success_box.html( '<i class="close icon"></i> Data loaded successfully. Reloading page...' ).show();
						setTimeout(function () {
							statusEl.fadeOut();
						}, 1500);
		} else {
			error_box.html( '<i class="close icon"></i> Undefined error occurred. Please try again.' ).show();
		}

		updraftplus_scheduled_bulkCurrentThreads--;
		mainwp_updraftplus_scheduledbackups_start_next( selector );

	}, 'json');

	return false;
}


mainwp_updraftplus_plugin_showhide_start_next = function (selector) {
	while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (updraftplus_bulkCurrentThreads < updraftplus_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		if (objProcess.closest( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_updraftplus_plugin_showhide_start_specific( objProcess, true, selector );
	}
}

mainwp_updraftplus_plugin_showhide_start_specific = function (pObj, bulk, selector) {
	var parent = pObj.closest( 'tr' );
	var showhide = pObj.attr( 'showhide' );
	var statusEl = parent.find( '.wp-updraftplus-visibility' );
	if (bulk) {
		updraftplus_bulkCurrentThreads++;
	}

	var data = {
		action: 'mainwp_updraftplus_showhide_plugin',
		updraftRequestSiteID: parent.attr( 'website-id' ),
		showhide: showhide
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post(ajaxurl, data, function (response) {
		pObj.removeClass( 'queue' );
		if (response && response['error']) {
			statusEl.html( response['error'] );
		} else if (response && response['result'] == 'SUCCESS') {
			if (showhide == 'show') {
				pObj.text( "Hide Plugin" );
				pObj.attr( 'showhide', 'hide' );
				parent.find( '.wp-updraftplus-visibility' ).html( __( 'No' ) );
			} else {
				pObj.text( "Unhide Plugin" );
				pObj.attr( 'showhide', 'show' );
				parent.find( '.wp-updraftplus-visibility' ).html( __( 'Yes' ) );
			}
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		if (bulk) {
			updraftplus_bulkCurrentThreads--;
			updraftplus_bulkFinishedThreads++;
			mainwp_updraftplus_plugin_showhide_start_next( selector );
		}

	}, 'json');
	return false;
}

mainwp_updraftplus_plugin_upgrade_start_next = function (selector) {
	while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (objProcess.closest( 'tr' ).prev( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length > 0) && (updraftplus_bulkCurrentThreads < updraftplus_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		if (objProcess.closest( 'tr' ).prev( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_updraftplus_plugin_upgrade_start_specific( objProcess, true, selector );
	}
}

mainwp_updraftplus_plugin_upgrade_start_specific = function (pObj, bulk, selector) {
	var parent = pObj.closest( 'tr' );
	var statusEl = parent.find( '.updating' );
	var slug = parent.attr( 'plugin-slug' );

	statusEl.html( '' );

	var data = {
		action: 'mainwp_updraftplus_upgrade_plugin',
		updraftRequestSiteID: parent.attr( 'website-id' ),
		type: 'plugin',
		'slugs[]': [slug]
	}

	if ( bulk ) {
		updraftplus_bulkCurrentThreads++;
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post(ajaxurl, data, function (response) {
		statusEl.html( '' );
		pObj.removeClass( 'queue' );
		if (response && response['error']) {
			statusEl.html( '<i class="red times icon"></i>' );
		} else if ( response && response['upgrades'][slug] ) {
			pObj.remove();
			parent.removeClass( 'warning' );
		} else {
			statusEl.html( '<i class="red times icon"></i>' );
		}

		if ( bulk ) {
			updraftplus_bulkCurrentThreads--;
			updraftplus_bulkFinishedThreads++;
			mainwp_updraftplus_plugin_upgrade_start_next( selector );
		}

	}, 'json');
	return false;
}

mainwp_updraftplus_plugin_active_start_next = function (selector) {
	while ((objProcess = jQuery( selector + '.queue:first' )) && (objProcess.length > 0) && (objProcess.closest( 'tr' ).prev( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length > 0) && (updraftplus_bulkCurrentThreads < updraftplus_bulkMaxThreads)) {
		objProcess.removeClass( 'queue' );
		if (objProcess.closest( 'tr' ).prev( 'tr' ).find( '.check-column input[type="checkbox"]:checked' ).length == 0) {
			continue;
		}
		mainwp_updraftplus_plugin_active_start_specific( objProcess, true, selector );
	}
}

mainwp_updraftplus_plugin_active_start_specific = function (pObj, bulk, selector) {
	var parent = pObj.closest( 'tr' );
	var statusEl = parent.find( '.updating' );
	var slug = parent.attr( 'plugin-slug' );
	var data = {
		action: 'mainwp_updraftplus_active_plugin',
		updraftRequestSiteID: parent.attr( 'website-id' ),
		'plugins[]': [slug]
	}

	if (bulk) {
		updraftplus_bulkCurrentThreads++;
	}

	statusEl.html( '<i class="notched circle loading icon"></i>' );

	jQuery.post(ajaxurl, data, function (response) {
		statusEl.html( '' );
		pObj.removeClass( 'queue' );
		if ( response && response['error'] ) {
			statusEl.html( '<i class="red times icon"></i>' );;
		} else if (response && response['result']) {
			parent.removeClass( 'negative' );
			pObj.remove();
		}
		if ( bulk ) {
			updraftplus_bulkCurrentThreads--;
			updraftplus_bulkFinishedThreads++;
			mainwp_updraftplus_plugin_active_start_next( selector );
		}

	}, 'json');
	return false;
}

mainwp_updraftplus_individual_save_settings = function (pSiteId, saveGeneral) {
	var statusEl = jQuery( '#mwp_updraftplus_site_save_settings_status' );
        statusEl.hide();
	var loaderEl = jQuery( '#updraftplus_site_settings img.loader' );
	loaderEl.show();
        scrollToElement('#updraftplus_site_settings');
        if (saveGeneral) {
            jQuery('input[name=save-general-settings-to-site]').attr('disabled', true);
        }
	data = {
		action: 'mainwp_updraftplus_save_settings',
		updraftRequestSiteID: pSiteId,
		individual: true,
                save_general: saveGeneral
	};
	jQuery.post(ajaxurl, data, function (response) {
                if (saveGeneral) {
                    jQuery('input[name=save-general-settings-to-site]').removeAttr('disabled');
                }
		loaderEl.hide();
		var _success = false;
		if (response) {
			if (response.error) {
				statusEl.css( 'color', 'red' );
				statusEl.html( response.error );
			} else if (response.result == 'success') {
				statusEl.css( 'color', '#21759B' );
                                if (saveGeneral) {
                                    statusEl.html(__('General Settings saved on the child site.'));
                                } else
                                    statusEl.html( __( 'Saved.' ) );

				_success = true;
			} else if (response.result == 'noupdate') {
				statusEl.css( 'color', '#21759B' );
				statusEl.html( __( 'No change.' ) );
				_success = true;
			} else if (response.message) {
				statusEl.css( 'color', '#21759B' );
				statusEl.html( response.message );
			} else {
				statusEl.css( 'color', 'red' );
				statusEl.html( 'Undefined error' );
			}
		} else {
			statusEl.css( 'color', 'red' );
			statusEl.html( 'Undefined error' );
		}
		statusEl.fadeIn();
		if (_success) {
			setTimeout(function ()
				{
				//statusEl.fadeOut();
			}, 3000);
		}
	}, 'json');
}

mainwp_updraftplus_save_settings_start_next = function () {
	while ((objProcess = jQuery( '.siteItemProcess[status=queue]:first' )) && (objProcess.length > 0) && (updraftplus_bulkCurrentThreads < updraftplus_bulkMaxThreads)) {
		objProcess.attr( 'status', 'processed' );
		mainwp_updraftplus_save_settings_start_specific( objProcess );
	}

	if (updraftplus_bulkFinishedThreads > 0 && updraftplus_bulkFinishedThreads == updraftplus_bulkTotalThreads) {
//		jQuery( '#mwp_updraftplus_setting_tab' ).append( '<div class="mainwp_info-box">' + __( "Save Settings finished." ) + '</div>' + '<p><a class="button-primary" href="admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=settings">Return to Settings</a></p>' );
//		setTimeout(function ()
//			{
//			//location.href = location.href;
//		}, 1000);
	}

}

mainwp_updraftplus_save_settings_start_specific = function (objProcess) {
//	var loadingEl = objProcess.find( 'img' );
	var statusEl = objProcess.find( '.status' );
	updraftplus_bulkCurrentThreads++;
	var data = {
		action: 'mainwp_updraftplus_save_settings',
		updraftRequestSiteID: objProcess.attr( 'site-id' )
	};

        statusEl.html( '<i class="sync alternate loading icon"></i>' );
//	loadingEl.show();
	//call the ajax
	jQuery.post(ajaxurl, data, function (response) {
//		loadingEl.hide();
		if (response) {
			if (response.error) {
				//statusEl.css( 'color', 'red' );
//				statusEl.html( response.error );
                                statusEl.html( response.error );
			} else if (response.result == 'success') {
				//statusEl.css( 'color', '#21759B' );
//				statusEl.html( __( 'Saved.' ) );
                                statusEl.html( '<i class="check green icon"></i>' );
			} else if (response.result == 'noupdate') {
				//statusEl.css( 'color', '#21759B' );
				statusEl.html( __( 'No change.' ) );
			} else if (response.message) {
				//statusEl.css( 'color', '#21759B' );
				statusEl.html( response.message );
			} else {
				//statusEl.css( 'color', 'red' );
//				statusEl.html( 'Undefined error' );
                                statusEl.html( '<i class="exclamation red icon"></i>' );
			}
		} else {
			//statusEl.css( 'color', 'red' );
//			statusEl.html( 'Undefined error' );
                        statusEl.html( '<i class="exclamation red icon"></i>' );
		}

		updraftplus_bulkCurrentThreads--;
		updraftplus_bulkFinishedThreads++;
		mainwp_updraftplus_save_settings_start_next();
	}, 'json');
}

mainwp_updraftplus_individual_addons_connect = function (pSiteId) {
	var statusEl = jQuery( '#mwp_updraft_site_addons_connect_working .status' );
	var loaderEl = jQuery( '#mwp_updraft_site_addons_connect_working i' );
	statusEl.html( __( 'Connect with your UpdraftPlus.Com account ...' ) );
	loaderEl.show();
	data = {
		action: 'mainwp_updraftplus_addons_connect',
		updraftRequestSiteID: pSiteId,
		individual: true
	};
	jQuery.post(ajaxurl, data, function (response) {
		loaderEl.hide();
		var _success = false;
		if (response) {
			if (response.error == 'NO_PREMIUM') {
				statusEl.css( 'color', 'red' );
				statusEl.html( __( 'No premium version.' ) );
				_success = true;
			} else if (response.error) {
				statusEl.css( 'color', 'red' );
				statusEl.html( response.error );
			} else if (response.result == 'success') {
				statusEl.css( 'color', '#21759B' );
				statusEl.html( __( 'Successful.' ) );
				_success = true;
			} else if (response.message) {
				statusEl.css( 'color', '#21759B' );
				statusEl.html( response.message );
			} else {
				statusEl.css( 'color', 'red' );
				statusEl.html( 'Undefined error' );
			}
		} else {
			statusEl.css( 'color', 'red' );
			statusEl.html( 'Undefined error' );
		}
		statusEl.fadeIn();
		if (_success) {
			setTimeout(function ()
				{
				statusEl.fadeOut();
			}, 3000);
		}
	}, 'json');
}


mainwp_updraftplus_addons_connect_start_next = function () {
	while ((objProcess = jQuery( '.siteItemProcess[status=queue]:first' )) && (objProcess.length > 0) && (updraftplus_bulkCurrentThreads < updraftplus_bulkMaxThreads)) {
		objProcess.attr( 'status', 'processed' );
		mainwp_updraftplus_addons_connect_start_specific( objProcess );
	}

	if (updraftplus_bulkFinishedThreads > 0 && updraftplus_bulkFinishedThreads == updraftplus_bulkTotalThreads) {
		jQuery( '#mwp_updraftplus_setting_tab' ).append( '<div class="mainwp_info-box">' + __( "Save Settings finished." ) + '</div>' + '<p><a class="button-primary" href="admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=settings">Return to Settings</a></p>' );
		setTimeout(function ()
			{
			//location.href = location.href;
		}, 1000);
	}

}

mainwp_updraftplus_addons_connect_start_specific = function (objProcess) {
	var loadingEl = objProcess.find( 'img' );
	var statusEl = objProcess.find( '.status' );
	updraftplus_bulkCurrentThreads++;
	var data = {
		action: 'mainwp_updraftplus_addons_connect',
		updraftRequestSiteID: objProcess.attr( 'site-id' )
	};
	statusEl.html( '' );
	loadingEl.show();
	//call the ajax
	jQuery.post(ajaxurl, data, function (response) {
		loadingEl.hide();
		if (response) {
			if (response.error == 'NO_PREMIUM') {
				statusEl.css( 'color', 'red' );
				statusEl.html( __( 'No premium version.' ) );
				_success = true;
			} else if (response.error) {
				statusEl.css( 'color', 'red' );
				statusEl.html( response.error );
			} else if (response.result == 'success') {
				statusEl.css( 'color', '#21759B' );
				statusEl.html( __( 'Successful.' ) );
			} else if (response.message) {
				statusEl.css( 'color', '#21759B' );
				statusEl.html( response.message );
			} else {
				statusEl.css( 'color', 'red' );
				statusEl.html( 'Undefined error' );
			}
		} else {
			statusEl.css( 'color', 'red' );
			statusEl.html( 'Undefined error' );
		}

		updraftplus_bulkCurrentThreads--;
		updraftplus_bulkFinishedThreads++;
		mainwp_updraftplus_addons_connect_start_next();
	}, 'json');
}

mainwp_updraftplus_vault_connect_start_next = function () {
	while ((objProcess = jQuery( '.siteItemProcess[status=queue]:first' )) && (objProcess.length > 0) && (updraftplus_bulkCurrentThreads < updraftplus_bulkMaxThreads)) {
		objProcess.attr( 'status', 'processed' );
		mainwp_updraftplus_vault_connect_start_specific( objProcess );
	}

	if (updraftplus_bulkFinishedThreads > 0 && updraftplus_bulkFinishedThreads == updraftplus_bulkTotalThreads) {
		jQuery( '#mwp_updraftplus_setting_tab' ).append( '<div class="mainwp_info-box">' + __( "Connect child sites with UpdraftPlus Vault finished." ) + '</div>' + '<p><a class="button-primary" href="admin.php?page=Extensions-Mainwp-Updraftplus-Extension&tab=settings">Return to Settings</a></p>' );
	}

}

mainwp_updraftplus_vault_connect_start_specific = function (objProcess) {
	var loadingEl = objProcess.find( 'img' );
	var statusEl = objProcess.find( '.status' );
	updraftplus_bulkCurrentThreads++;

        var data = {
                action: 'mainwp_updraft_ajax',
                subaction: 'vault_connect',
                nonce: mwp_updraft_credentialtest_nonce,
                email: jQuery('#mainwp_updraftplus_vault_opts').attr('email'),
                pass: jQuery('#mainwp_updraftplus_vault_opts').attr('pass'),
		updraftRequestSiteID: objProcess.attr( 'site-id' )
	};
	statusEl.html( '' );
	loadingEl.show();
	//call the ajax
	jQuery.post(ajaxurl, data, function (response) {
                loadingEl.hide();
                try {
                        resp = jQuery.parseJSON(response);
                } catch(err) {
                        console.log(err);
                        console.log(response);
                        statusEl.css( 'color', 'red' );
                        statusEl.html( mwp_updraftlion.unexpectedresponse+' '+response );
                        return;
                }
                if (resp) {
                    if ( resp.hasOwnProperty('error')) {
                        statusEl.css( 'color', 'red' );
                        statusEl.html( resp.error );
                    } else if ( resp.hasOwnProperty('message')) {
                        statusEl.css( 'color', '#21759B' );
                        statusEl.html( resp.message );
                    } else if (resp.hasOwnProperty('e')) {
                        statusEl.css( 'color', '#21759B' );
                        statusEl.html( resp.e );

                    } else if (resp.hasOwnProperty('connected') && resp.connected) {
                        statusEl.css( 'color', '#21759B' );
                        statusEl.html( __('This site is connected to UpdraftPlus Vault') );
                    } else {
                        statusEl.css( 'color', 'red' );
                        statusEl.html( mwp_updraftlion.unexpectedresponse+' '+response );
                    }
                } else {
                        statusEl.css( 'color', 'red' );
                        statusEl.html( mwp_updraftlion.unexpectedresponse+' '+response );
                }

		updraftplus_bulkCurrentThreads--;
		updraftplus_bulkFinishedThreads++;
		mainwp_updraftplus_vault_connect_start_next();
	});
}
