
jQuery(document).ready(function($){

    if (mwp_updraft_individual_siteid)
        mainwp_next_scheduledbackups(mwp_updraft_individual_siteid);

    mainwp_updraft_showlastbackup();

	jQuery('#mwp_updraft_include_others').click( function() {
		if ( jQuery('#mwp_updraft_include_others').is( ':checked' ) ) {
			jQuery('#mwp_updraft_include_others_exclude').slideDown();
		} else {
			jQuery('#mwp_updraft_include_others_exclude').slideUp();
		}
	});

	jQuery('#mwp_updraft_include_uploads').click(function() {
		if (jQuery('#mwp_updraft_include_uploads').is(':checked')) {
			jQuery('#mwp_updraft_include_uploads_exclude').slideDown();
		} else {
			jQuery('#mwp_updraft_include_uploads_exclude').slideUp();
		}
	});

        //setTimeout(function(){updraft_showlastlog(true);}, 1200);
        setInterval(function() {mainwp_updraft_activejobs_update(false);}, 1250);

        jQuery('#mwp_updraft_restore_db').change(function(){
//		if (jQuery('#mwp_updraft_restore_db').is(':checked')) {
//			jQuery('#updraft_restorer_dboptions').slideDown();
//		} else {
//			jQuery('#updraft_restorer_dboptions').slideUp();
//		}
	});

        var mwp_updraft_message_modal_buttons = {};
	mwp_updraft_message_modal_buttons[mwp_updraftlion.close] = function() { jQuery(this).dialog("close"); };
	jQuery( "#mwp-updraft-message-modal" ).dialog({
		autoOpen: false, height: 350, width: 520, modal: true,
		buttons: mwp_updraft_message_modal_buttons
	});

	var mwp_updraft_delete_modal_buttons = {};
	mwp_updraft_delete_modal_buttons[mwp_updraftlion.deletebutton] = function() {
		jQuery('#mwp-updraft-delete-waitwarning').slideDown();
		timestamp = jQuery('#updraft_delete_timestamp').val();
                jQuery.post(ajaxurl, jQuery('#updraft_delete_form').serialize(), function(response) {
			jQuery('#mwp-updraft-delete-waitwarning').slideUp();
			var resp;
			try {
				resp = jQuery.parseJSON(response);
			} catch(err) {
				alert(mwp_updraftlion.unexpectedresponse+' '+response);
			}
			if (resp.result != null) {
				if (resp.result == 'error') {
					alert(mwp_updraftlion.error+' '+resp.message);
				} else if (resp.result == 'success') {
					//jQuery('#updraft_showbackups').load(ajaxurl+'?action=updraft_ajax&subaction=countbackups&nonce='+updraft_credentialtest_nonce);
					jQuery('#updraft-navtab-backups').load(ajaxurl+'?action=updraft_ajax&subaction=countbackups&nonce='+mwp_updraft_credentialtest_nonce);
					jQuery('#updraft_existing_backups_row_'+timestamp).slideUp().remove();
					jQuery("#mwp-updraft-delete-modal").dialog('close');
					alert(resp.message);
				}
			}
		});
	};
	mwp_updraft_delete_modal_buttons[mwp_updraftlion.cancel] = function() { jQuery(this).dialog("close"); };
	jQuery( "#mwp-updraft-delete-modal" ).dialog({
		autoOpen: false, height: 262, width: 430, modal: true,
		buttons: mwp_updraft_delete_modal_buttons
	});

	var mwp_updraft_restore_modal_buttons = {};
	mwp_updraft_restore_modal_buttons[mwp_updraftlion.restore] = function() {
		var anyselected = 0;
		var whichselected = [];
		// Make a list of what files we want
		var already_added_wpcore = 0;
		var meta_foreign = jQuery('#updraft_restore_meta_foreign').val();
		jQuery('input[name="updraft_restore[]"]').each(function(x,y){
			if (jQuery(y).is(':checked') && !jQuery(y).is(':disabled')) {
				anyselected = 1;
				var howmany = jQuery(y).data('howmany');
				var type = jQuery(y).val();
				if (1 == meta_foreign || (2 == meta_foreign && 'db' != type)) { type = 'wpcore'; }
				if ('wpcore' != type || already_added_wpcore == 0) {
					var restobj = [ type, howmany ];
					whichselected.push(restobj);
					//alert(jQuery(y).val());
					if ('wpcore' == type) { already_added_wpcore = 1; }
				}
			}
		});
		if (anyselected == 1) {
			// Work out what to download
			if (mwp_updraft_restore_stage == 1) {
				// meta_foreign == 1 : All-in-one format: the only thing to download, always, is wpcore
// 				if ('1' == meta_foreign) {
// 					whichselected = [];
// 					whichselected.push([ 'wpcore', 0 ]);
// 				} else if ('2' == meta_foreign) {
// 					jQuery(whichselected).each(function(x,y) {
// 						restobj = whichselected[x];
// 					});
// 					whichselected = [];
// 					whichselected.push([ 'wpcore', 0 ]);
// 				}
				jQuery('#mwp-updraft-restore-modal-stage1').slideUp('slow');
				jQuery('#mwp-updraft-restore-modal-stage2').show();
				mwp_updraft_restore_stage = 2;
				var pretty_date = jQuery('.updraft_restore_date').first().text();
				// Create the downloader active widgets

				for (var i=0; i<whichselected.length; i++) {
					mainwp_updraft_downloader('udrestoredlstatus_', jQuery('#updraft_restore_timestamp').val(), whichselected[i][0], '#mwp_ud_downloadstatus2', whichselected[i][1], pretty_date, false);
				}

				// Make sure all are downloaded
			} else if (mwp_updraft_restore_stage == 2) {
				mainwp_updraft_restorer_checkstage2(1);
			} else if (mwp_updraft_restore_stage == 3) {
				jQuery('#mwp-updraft-restore-modal-stage2a').html(mwp_updraftlion.restoreproceeding);
                                mainwp_updraft_restorer_checkstage3();
				//jQuery('#updraft_restore_form').submit();
			}
		} else {
			alert(mwp_updraftlion.youdidnotselectany);
		}
	};

	mwp_updraft_restore_modal_buttons[mwp_updraftlion.cancel] = function() { jQuery(this).dialog("close"); };

	jQuery( "#mwp-updraft-restore-modal" ).dialog({
		autoOpen: false, height: 505, width: 590, modal: true,
		buttons: mwp_updraft_restore_modal_buttons
	});

	jQuery("#updraft-iframe-modal" ).dialog({
		autoOpen: false, height: 500, width: 780, modal: true
	});

	jQuery("#mwp-updraft-backupnow-inpage-modal" ).dialog({
		autoOpen: false, height: 345, width: 580, modal: true
	});

    var mainwp_backupnow_modal_buttons = {};
	mainwp_backupnow_modal_buttons[mwp_updraftlion.backupnow] = function() {

		var backupnow_nodb = jQuery('#backupnow_nodb').is(':checked') ? 1 : 0;
		var backupnow_nofiles = jQuery('#backupnow_nofiles').is(':checked') ? 1 : 0;
		var backupnow_nocloud = jQuery('#backupnow_nocloud').is(':checked') ? 1 : 0;
		if (backupnow_nodb && backupnow_nofiles) {
			alert(mwp_updraftlion.excludedeverything);
			return;
		}

		jQuery(this).dialog("close");

        if (jQuery(this).attr('id') == 'mwp-updraftplus-backupallnow-modal') {
            mainwp_updraft_backupallnow_go(backupnow_nodb, backupnow_nofiles, backupnow_nocloud, '');
        } else {
            setTimeout(function() {
                    jQuery('#updraft_lastlogmessagerow').fadeOut('slow', function() {
                            jQuery(this).fadeIn('slow');
                    });
            }, 1700);
            mainwp_updraft_backupnow_go(backupnow_nodb, backupnow_nofiles, backupnow_nocloud, '');
        }
	};
	mainwp_backupnow_modal_buttons[mwp_updraftlion.cancel] = function() { jQuery(this).dialog("close"); };

	jQuery("#mwp-updraftplus-backupnow-modal" ).dialog({
		autoOpen: false, height: 355, width: 480, modal: true,
		buttons: mainwp_backupnow_modal_buttons
	});

        jQuery("#mwp-updraftplus-backupallnow-modal" ).dialog({
		autoOpen: false, height: 355, width: 480, modal: true,
		buttons: mainwp_backupnow_modal_buttons
	});

        jQuery('.mwp_updraftplusmethod').hide();
        jQuery('#mwp-updraft-service').change(function() {
		jQuery('.mwp_updraftplusmethod').hide();
		var active_class = jQuery(this).val();
		jQuery('.'+active_class).show();
	});

        mainwp_updraft_check_same_times();

        jQuery( "#mwp_updraft-poplog" ).dialog({
		autoOpen: false, height: 600, width: '75%', modal: true,
	});

        jQuery('#mwp_enableexpertmode').click(function() {
		jQuery('.mwp_expertmode').fadeIn();
		jQuery('#mwp_enableexpertmode').off('click');
		return false;
	});

	jQuery('.mwp-icon-dropdown').selectric({
		optionsItemBuilder: function(itemData, element, index){
			return element.val().length ? '<span class="ico ico-'+element.val()+'"></span>'+itemData.text : itemData.text;
		},
		inheritOriginalWidth: false
	});

    	// Update WebDAV URL as user edits
	$('#mwp-remote-storage-holder').on('change keyup paste', '.mwp_updraft_webdav_settings', function() {
		var updraft_webdav_settings = [];
		$('.mwp_updraft_webdav_settings').each(function(index, item) {
			var id = $(item).attr('id');
            var instance_id = 0;
			if (id && 'mwp_updraft_webdav_' == id.substring(0, 19)) {
				var which_one = id.substring(19);
				if ('undefined' == typeof updraft_webdav_settings[instance_id]) updraft_webdav_settings[instance_id] = [];
				updraft_webdav_settings[instance_id][which_one] = this.value;
			}
		});

		var updraft_webdav_url = "";
		var host = "@";
		var slash = "/";
		var colon = ":";
		var colon_port = ":";

		for (var instance_id in updraft_webdav_settings) {

			if (updraft_webdav_settings[instance_id]['host'].indexOf("@") >= 0 || "" === updraft_webdav_settings[instance_id]['host']) {
				host = "";
			}
			if (updraft_webdav_settings[instance_id]['host'].indexOf("/") >= 0) {
				$('#updraft_webdav_host_error').show();
			} else {
				$('#updraft_webdav_host_error').hide();
			}

			if (0 == updraft_webdav_settings[instance_id]['path'].indexOf("/") || "" === updraft_webdav_settings[instance_id]['path']) {
				slash = "";
			}

			if ("" === updraft_webdav_settings[instance_id]['user'] || "" === updraft_webdav_settings[instance_id]['pass']) {
				colon = "";
			}

			if ("" === updraft_webdav_settings[instance_id]['host'] || "" === updraft_webdav_settings[instance_id]['port']) {
				colon_port = "";
			}

			updraft_webdav_url = updraft_webdav_settings[instance_id]['webdav'] + updraft_webdav_settings[instance_id]['user'] + colon + updraft_webdav_settings[instance_id]['pass'] + host +encodeURIComponent(updraft_webdav_settings[instance_id]['host']) + colon_port + updraft_webdav_settings[instance_id]['port'] + slash + updraft_webdav_settings[instance_id]['path'];

			$('#mwp_updraft_webdav_url').val(updraft_webdav_url);
		}
	});

});

function mainwp_updraft_utf8_to_b64(str) {
    return window.btoa(str);
    //return window.btoa(encodeURIComponent( escape( str )));
}

function mainwp_updraft_get_donwnloadlink(site_id, location) {
    location = location + '&_mwpNoneName=_wpnonce&_mwpNoneValue=updraftplus_download';
    return 'admin.php?page=Extensions-Mainwp-Updraftplus-Extension&action=mwpUpdraftOpenSite&websiteid=' + site_id + '&open_location=' + mainwp_updraft_utf8_to_b64(location);
}

function mainwp_updraftplus_downloadstage2(timestamp, type, findex, site_id) {
    var loc = 'admin-ajax.php?timestamp='+timestamp+'&type='+type+'&stage=2&findex='+findex+'&action=updraft_download_backup';
    var url =  mainwp_updraft_get_donwnloadlink(site_id, loc);
    window.open(url, '_blank');
    //location.href=ajaxurl+'?_wpnonce='+mwp_updraft_download_nonce+'&timestamp='+timestamp+'&type='+type+'&stage=2&findex='+findex+'&action=updraft_download_backup';
}

function mainwp_updraft_downloader(base, nonce, what, whicharea, set_contents, prettydate, async, pObj) {

        if (pObj) {
            mainwp_updraft_reset_wrapper(pObj);
            mainwp_updraft_set_process_siteid();
                if (!mwp_updraft_process_siteid) return;
        }

	if (typeof set_contents !== "string") set_contents=set_contents.toString();
	var set_contents = set_contents.split(',');
	for (var i=0; i<set_contents.length; i++) {
		// Create somewhere for the status to be found
		var stid = base+nonce+'_'+what+'_'+set_contents[i];
		var show_index = parseInt(set_contents[i]); show_index++;
		var itext = (set_contents[i] == 0) ? '' : ' ('+show_index+')';
		if (!jQuery('#'+stid).length) {
			var prdate = (prettydate) ? prettydate : nonce;
                        //console.log(whicharea);
			jQuery(whicharea).append('<div style="clear:left; border: 1px solid; padding: 8px; margin-top: 4px; max-width:920px; margin-bottom:15px;" id="'+stid+'" class="updraftplus_downloader"><button onclick="jQuery(\'#'+stid+'\').fadeOut().remove();" type="button" style="float:right; margin-bottom: 8px;">X</button><strong>Download '+what+itext+' ('+prdate+')</strong>:<div class="raw">'+mwp_updraftlion.begunlooking+'</div><div class="file" id="'+stid+'_st"><div class="dlfileprogress" style="width: 0;"></div></div>');
			jQuery('#'+stid).data('downloaderfor', { base: base, nonce: nonce, what: what, index: i });
			// Legacy: set up watcher
			//(function(base, nonce, what, i) {
			//	setTimeout(function(){updraft_downloader_status(base, nonce, what, i);}, 300);
			//})(base, nonce, what, set_contents[i]);
                        setTimeout(function() {mainwp_updraft_activejobs_update(true);}, 1500);
		}
		jQuery('#'+stid).data('lasttimebegan', (new Date).getTime());

                var fid = '#uddownloadform_'+what+'_'+nonce+'_'+set_contents[i];
                jQuery(fid + ' #_wpnonce').val(mwp_updraft_download_nonce);
                jQuery(fid + ' #_wpnonce').append('<input type="hidden" value="' + mwp_updraft_process_siteid + '" name="updraftRequestSiteID" >');

		// Now send the actual request to kick it all off
		jQuery.ajax({
			url: ajaxurl,
			timeout: 10000,
			type: 'POST',
			async: async,
			data: jQuery('#uddownloadform_'+what+'_'+nonce+'_'+set_contents[i]).serialize()
		});
	}
	// We don't want the form to submit as that replaces the document
	return false;
}


function mainwp_updraft_delete_old_dirs() {
        if (mwp_updraft_process_siteid == 0)
            return;
	// Allow pressing 'Restore' to proceed
	jQuery('#mwp-updraft-restore-modal-stage2a').html(mwp_updraftlion.deleteolddirprocessing);
	jQuery.get(ajaxurl, {
		action: 'mainwp_updraft_ajax',
		subaction: 'delete_old_dirs',
		nonce: mwp_updraft_credentialtest_nonce,
                updraftRequestSiteID: mwp_updraft_process_siteid
	}, function(data) {
		try {
			var resp = jQuery.parseJSON(data);
			if (null == resp) {
				jQuery('#mwp-updraft-restore-modal-stage2a').html(mwp_updraftlion.emptyresponse);
				return;
			}
			var report = '';
			if (resp.error) {
				report = "<p><strong>" + mwp_updraftlion.errors+'</strong><br>' + resp.error + "</p>";
			} else {
                                report = resp.o;
                                if (resp.d) {
                                    mwp_updraft_restore_stage = 2;
                                }
			}
			jQuery('#mwp-updraft-restore-modal-stage2a').html(report);
		} catch(err) {
			console.log(data);
			console.log(err);
			jQuery('#mwp-updraft-restore-modal-stage2a').text(mwp_updraftlion.jsonnotunderstood+' '+mwp_updraftlion.errordata+": "+data).html();
		}
	});
}


function mainwp_updraft_restorer_checkstage2(doalert) {
        if (!mwp_updraft_process_siteid) return;

	// How many left?
	var stilldownloading = jQuery('#mwp_ud_downloadstatus2 .file').length;
	if (stilldownloading > 0) {
		if (doalert) { alert(mwp_updraftlion.stilldownloading); }
		return;
	}
	// Allow pressing 'Restore' to proceed
	jQuery('#mwp-updraft-restore-modal-stage2a').html(mwp_updraftlion.processing);
	jQuery.get(ajaxurl, {
		action: 'mainwp_updraft_ajax',
		subaction: 'restore_alldownloaded',
		nonce: mwp_updraft_credentialtest_nonce,
		timestamp: jQuery('#updraft_restore_timestamp').val(),
		restoreopts: jQuery('#updraft_restore_form').serialize(),
                updraftRequestSiteID: mwp_updraft_process_siteid
	}, function(data) {
		try {
			var resp = jQuery.parseJSON(data);
			if (null == resp) {
				jQuery('#mwp-updraft-restore-modal-stage2a').html(mwp_updraftlion.emptyresponse);
				return;
			}
			var report = resp.m;
			if (resp.w != '') {
				report = report + "<p><strong>" + mwp_updraftlion.warnings +'</strong><br>' + resp.w + "</p>";
			}
			if (resp.e != '') {
				report = report + "<p><strong>" + mwp_updraftlion.errors+'</strong><br>' + resp.e + "</p>";
			} else {
				mwp_updraft_restore_stage = 3;
			}
			jQuery('#mwp-updraft-restore-modal-stage2a').html(report);
		} catch(err) {
			console.log(data);
			console.log(err);
			jQuery('#mwp-updraft-restore-modal-stage2a').text(mwp_updraftlion.jsonnotunderstood+' '+mwp_updraftlion.errordata+": "+data).html();
		}
	});
}

function mainwp_updraft_restorer_checkstage3() {

        if (mwp_updraft_process_siteid == 0)
            return;

	// Allow pressing 'Restore' to proceed
	//jQuery('#mwp-updraft-restore-modal-stage2a').html(mwp_updraftlion.restoreprocessing);
	jQuery.get(ajaxurl, {
		action: 'mainwp_updraft_ajax',
		subaction: 'restorebackup',
		nonce: mwp_updraft_credentialtest_nonce,
		backup_timestamp: jQuery('#updraft_restore_timestamp').val(),
                restoreopts: jQuery('#updraft_restore_form').serialize(),
                updraftRequestSiteID: mwp_updraft_process_siteid
	}, function(data) {
		try {
			var resp = jQuery.parseJSON(data);
			if (null == resp) {
				jQuery('#mwp-updraft-restore-modal-stage2a').html(mwp_updraftlion.emptyresponse);
				return;
			}
			var report = '';
			if (resp.error) {
				report = "<p><strong>" + mwp_updraftlion.errors+'</strong><br>' + resp.error + "</p>";
			} else {
                                report = resp.o;
				mwp_updraft_restore_stage = 4;
			}
			jQuery('#mwp-updraft-restore-modal-stage2a').html(report);
		} catch(err) {
			console.log(data);
			console.log(err);
			jQuery('#mwp-updraft-restore-modal-stage2a').text(mwp_updraftlion.jsonnotunderstood+' '+mwp_updraftlion.errordata+": "+data).html();
		}
	});
}




function mainwp_updraft_delete(key, nonce, showremote, pObj) {
        mainwp_updraft_reset_wrapper(pObj);
        mainwp_updraft_set_process_siteid();
        if (!mwp_updraft_process_siteid) return;

	jQuery('#updraft_delete_timestamp').val(key);
	jQuery('#updraft_delete_nonce').val(nonce);
	if (showremote) {
		jQuery('#updraft-delete-remote-section, #updraft_delete_remote').removeAttr('disabled').show();
	} else {
		jQuery('#updraft-delete-remote-section, #updraft_delete_remote').hide().attr('disabled','disabled');
	}

        jQuery('#mwp-updraft-delete-modal input[name="updraftRequestSiteID"]').val(mwp_updraft_process_siteid);
	jQuery('#mwp-updraft-delete-modal').dialog('open');
}


function mainwp_updraft_backupnow_go(backupnow_nodb, backupnow_nofiles, backupnow_nocloud, onlythisfileentity) {
        var statusEl = jQuery('#mwp_updraft_backup_started');
        var errorEl = jQuery('#mwp_updraft_backup_error');
	statusEl.html('<em>'+mwp_updraftlion.requeststart+'</em>').slideDown('');
	setTimeout(function() {statusEl.fadeOut('slow');}, 75000);

	var params = {
		action: 'mainwp_updraft_ajax',
		subaction: 'backupnow',
		nonce: mwp_updraft_credentialtest_nonce,
		backupnow_nodb: backupnow_nodb,
		backupnow_nofiles: backupnow_nofiles,
		backupnow_nocloud: backupnow_nocloud,
		backupnow_label: jQuery('#backupnow_label').val(),
                updraftRequestSiteID: mwp_updraft_individual_siteid
	};

	if ('' != onlythisfileentity) {
		params.onlythisfileentity = onlythisfileentity;
		params.backupnow_label = mwp_updraftlion.automaticbackupbeforeupdate;
	}

	jQuery.post(ajaxurl, params, function(response) {
		try {
			resp = jQuery.parseJSON(response);
                        if (resp.error) {
                            statusEl.hide();
                            errorEl.html( resp.error );
                            errorEl.fadeIn();
                        } else if (resp.m) {
                            statusEl.html(resp.m);
                            if (resp.hasOwnProperty('nonce')) {
                                // Can't return it from this context
                                mwp_updraft_backupnow_nonce = resp.nonce;
                            }
                        }

		} catch (err) {
                        statusEl.hide();
                        errorEl.html( 'Undefined error' );
                        errorEl.fadeIn();
			console.log(err);
			console.log(response);
		}
	});
}


var mwp_updraft_restore_stage = 1;
var mwp_lastlog_lastmessage = "";
var mwp_lastlog_lastdata = "";
var mwp_lastlog_jobs = "";
var mwp_lastlog_sdata = { action: 'mainwp_updraft_ajax', subaction: 'lastlog' };
var mwp_updraft_activejobs_nextupdate = (new Date).getTime() + 1000;
// Bits: main tab displayed (1); restore dialog open (uses downloader) (2); tab not visible (4)
var mwp_updraft_page_is_visible = 1;
var mwp_updraft_console_focussed_tab = 1;

// N.B. This function works on both the UD settings page and elsewhere
function mainwp_updraft_check_page_visibility(firstload) {
	if ('hidden' == document["visibilityState"]) {
		mwp_updraft_page_is_visible = 0;
	} else {
		mwp_updraft_page_is_visible = 1;
		if (1 !== firstload) {
                    mainwp_updraft_activejobs_update(true);
                }
	};
}

// See http://caniuse.com/#feat=pagevisibility for compatibility (we don't bother with prefixes)
if (typeof document.hidden !== "undefined") {
	document.addEventListener('visibilitychange', function() {mainwp_updraft_check_page_visibility(0);}, false);
}

mainwp_updraft_check_page_visibility(1);

var mwp_updraft_poplog_log_nonce;
var mwp_updraft_poplog_log_pointer = 0;
var mwp_updraft_poplog_lastscroll = -1;
var mwp_updraft_last_forced_jobid = -1;
var mwp_updraft_last_forced_resumption = -1;
var mwp_updraft_last_forced_when = -1;

var mwp_updraft_backupnow_nonce = '';
var mwp_updraft_activejobslist_backupnownonce_only = 0;
var mwp_updraft_inpage_hasbegun = 0;


function mainwp_updraft_activejobs_update(force) {
        mainwp_updraft_set_process_siteid();
        if (!mwp_updraft_process_siteid) return;

	var timenow = (new Date).getTime();
	if (false == force && timenow < mwp_updraft_activejobs_nextupdate) { return; }
	mwp_updraft_activejobs_nextupdate = timenow + 10500;
	var downloaders = '';
	jQuery('#mwp_ud_downloadstatus .updraftplus_downloader, #mwp_ud_downloadstatus2 .updraftplus_downloader').each(function(x,y){
		var dat = jQuery(y).data('downloaderfor');
		if (typeof dat == 'object') {
			if (downloaders != '') { downloaders = downloaders + ':'; }
			downloaders = downloaders + dat.base + ',' + dat.nonce + ',' + dat.what + ',' + dat.index;
		}
	});

	var gdata = {
		action: 'mainwp_updraft_ajax',
		subaction: 'activejobs_list',
		nonce: mwp_updraft_credentialtest_nonce,
		downloaders: downloaders,
                updraftRequestSiteID: mwp_updraft_process_siteid
	}

	try {
		if (jQuery("#mwp_updraft-poplog").dialog("isOpen")) {
			gdata.log_fetch = 1;
			gdata.log_nonce = mwp_updraft_poplog_log_nonce;
			gdata.log_pointer = mwp_updraft_poplog_log_pointer
		}
	} catch (err) {
		console.log(err);
	}

	if (mwp_updraft_activejobslist_backupnownonce_only && typeof mwp_updraft_backupnow_nonce !== 'undefined' && mwp_updraft_backupnow_nonce != '') {
		gdata.thisjobonly = mwp_updraft_backupnow_nonce;
	}

	jQuery.get(ajaxurl, gdata, function(response) {

 		try {
			resp = jQuery.parseJSON(response);

			//if (repeat) { setTimeout(function(){mainwp_updraft_activejobs_update(true);}, nexttimer);}
			if (resp.l != null) { jQuery('#mwp_updraft_lastlogcontainer').html(resp.l); }

			var lastactivity = -1;

			if (mwp_updraft_individual_siteid) {
                            jQuery('#mwp_updraft_activejobs').html(resp.j);
                            jQuery('#mwp_updraft_activejobs .updraft_jobtimings').each(function(ind, element) {
                                    var $el = jQuery(element);
                                    // lastactivity, nextresumption, nextresumptionafter
                                    if ($el.data('lastactivity') && $el.data('jobid')) {
                                            var jobid = $el.data('jobid');
                                            var new_lastactivity = $el.data('lastactivity');
                                            if (lastactivity == -1 || new_lastactivity < lastactivity) { lastactivity = new_lastactivity; }
                                            var nextresumptionafter = $el.data('nextresumptionafter');
                                            var nextresumption = $el.data('nextresumption');
    // 					console.log("Job ID: "+jobid+", Next resumption: "+nextresumption+", Next resumption after: "+nextresumptionafter+", Last activity: "+new_lastactivity);
                                            // Milliseconds
                                            timenow = (new Date).getTime();
                                            if (new_lastactivity > 50 && nextresumption >0 && nextresumptionafter < -30 && timenow > mwp_updraft_last_forced_when+100000 && (mwp_updraft_last_forced_jobid != jobid || nextresumption != mwp_updraft_last_forced_resumption)) {
                                                    mwp_updraft_last_forced_resumption = nextresumption;
                                                    mwp_updraft_last_forced_jobid = jobid;
                                                    mwp_updraft_last_forced_when = timenow;
                                                    console.log('UpdraftPlus: force resumption: job_id='+jobid+', resumption='+nextresumption);
                                                    jQuery.post(ajaxurl,  {
                                                            action: 'mainwp_updraft_ajax',
                                                            subaction: 'forcescheduledresumption',
                                                            nonce: mwp_updraft_credentialtest_nonce,
                                                            resumption: nextresumption,
                                                            job_id: jobid,
                                                            updraftRequestSiteID: mwp_updraft_process_siteid
                                                    }, function(response) {
                                                            console.log(response);
                                                    });
                                            }
                                    }
                            });
                        }

			timenow = (new Date).getTime();
			mwp_updraft_activejobs_nextupdate = timenow + 180000;
			// More rapid updates needed if a) we are on the main console, or b) a downloader is open (which can only happen on the restore console)
			if (mwp_updraft_page_is_visible == 1 && (1 == mwp_updraft_console_focussed_tab || (2 == mwp_updraft_console_focussed_tab && downloaders != ''))) {
				if (lastactivity > -1) {
					if (lastactivity < 5) {
						mwp_updraft_activejobs_nextupdate = timenow + 1300;
					} else {
						mwp_updraft_activejobs_nextupdate = timenow + 4500;
					}
				} else if (mwp_lastlog_lastdata == response) {
					// This condition is pretty hard to hit
					mwp_updraft_activejobs_nextupdate = timenow + 4500;
				} else {
					mwp_updraft_activejobs_nextupdate = timenow + 1300;
				}
			}

			mwp_lastlog_lastdata = response;

			if (resp.j != null && resp.j != '') {
				jQuery('#mwp_updraft_activejobsrow').show();

			if (gdata.hasOwnProperty('thisjobonly') && !mwp_updraft_inpage_hasbegun && jQuery('#updraft-jobid-'+gdata.thisjobonly).length) {
					mwp_updraft_inpage_hasbegun = 1;
					console.log('UpdraftPlus: the start of the requested backup job has been detected');
				}
				if (mwp_updraft_inpage_hasbegun == 1 && jQuery('#updraft-jobid-'+gdata.thisjobonly+'.updraft_finished').length) {
					// Don't reset to 0 - this will cause the 'began' event to be detected again
					mwp_updraft_inpage_hasbegun = 2;
// 					var updraft_inpage_modal_buttons = {};
// 					updraft_inpage_modal_buttons[updraftlion.close] = function() {
// 						jQuery(this).dialog("close");
// 					};
// 					jQuery('#mwp-updraft-backupnow-inpage-modal').dialog('option', 'buttons', updraft_inpage_modal_buttons);
					jQuery('#mwp-updraft-backupnow-inpage-modal').dialog('close');
					console.log('UpdraftPlus: the end of the requested backup job has been detected');
					if (typeof updraft_inpage_success_callback !== 'undefined' && updraft_inpage_success_callback != '') {
						// Move on to next page
						updraft_inpage_success_callback.call(false);
					}
				}
				if ('' == mwp_lastlog_jobs) {
					setTimeout(function(){jQuery('#mwp_updraft_backup_started').slideUp();}, 3500);
				}
			} else {
				if (!jQuery('#mwp_updraft_activejobsrow').is(':hidden')) {
					// Backup has now apparently finished - hide the row. If using this for detecting a finished job, be aware that it may never have shown in the first place - so you'll need more than this.
					if (typeof mwp_lastbackup_laststatus != 'undefined') { mainwp_updraft_showlastbackup(); }
					jQuery('#mwp_updraft_activejobsrow').hide();
				}
			}
			mwp_lastlog_jobs = resp.j;

			// Download status
			if (resp.ds != null && resp.ds != '') {
				jQuery(resp.ds).each(function(x, dstatus){
					if (dstatus.base != '') {
						mainwp_updraft_downloader_status_update(dstatus.base, dstatus.timestamp, dstatus.what, dstatus.findex, dstatus, response, mwp_updraft_process_siteid);
					}
				});
			}

			if (resp.u != null && resp.u != '' && jQuery("#mwp_updraft-poplog").dialog("isOpen")) {
				var log_append_array = resp.u;
				if (log_append_array.nonce == mwp_updraft_poplog_log_nonce) {
					mwp_updraft_poplog_log_pointer = log_append_array.pointer;
					if (log_append_array.html != null && log_append_array.html != '') {
						var oldscroll = jQuery('#mwp_updraft-poplog').scrollTop();
						jQuery('#mwp_updraft-poplog-content').append(log_append_array.html);
						if (mwp_updraft_poplog_lastscroll == oldscroll || mwp_updraft_poplog_lastscroll == -1) {
							jQuery('#mwp_updraft-poplog').scrollTop(jQuery('#mwp_updraft-poplog-content').prop("scrollHeight"));
							mwp_updraft_poplog_lastscroll = jQuery('#mwp_updraft-poplog').scrollTop();
						}
					}
				}
			}

		} catch(err) {
			console.log(mwp_updraftlion.unexpectedresponse+' '+response);
			console.log(err);
		}
	});
}

function mainwp_updraft_downloader_status_update(base, nonce, what, findex, resp, response, site_ID) {
	var stid = base+nonce+'_'+what+'_'+findex;
	var cancel_repeat = 0;
	if (resp.e != null) {
		jQuery('#'+stid+' .raw').html('<strong>'+mwp_updraftlion.error+'</strong> '+resp.e);
		console.log(resp);
	} else if (resp.p != null) {
		jQuery('#'+stid+'_st .dlfileprogress').width(resp.p+'%');
		//jQuery('#'+stid+'_st .dlsofar').html(Math.round(resp.s/1024));
		//jQuery('#'+stid+'_st .dlsize').html(Math.round(resp.t/1024));

		// Is a restart appropriate?
		// resp.a, if set, indicates that a) the download is incomplete and b) the value is the number of seconds since the file was last modified...
		if (resp.a != null && resp.a > 0) {
			var timenow = (new Date).getTime();
			var lasttimebegan = jQuery('#'+stid).data('lasttimebegan');
			// Remember that this is in milliseconds
			var sincelastrestart = timenow - lasttimebegan;
			if (resp.a > 90 && sincelastrestart > 60000) {
				console.log(nonce+" "+what+" "+findex+": restarting download: file_age="+resp.a+", sincelastrestart_ms="+sincelastrestart);
				jQuery('#'+stid).data('lasttimebegan', (new Date).getTime());
				jQuery.ajax({
					url: ajaxurl,
					timeout: 10000,
					type: 'POST',
					data: jQuery('#uddownloadform_'+what+'_'+nonce+'_'+findex).serialize()
				});
				jQuery('#'+stid).data('lasttimebegan', (new Date).getTime());
			}
		}

		if (resp.m != null) {
			if (resp.p >=100 && base == 'udrestoredlstatus_') {
				jQuery('#'+stid+' .raw').html(resp.m);
				jQuery('#'+stid).fadeOut('slow', function() { jQuery(this).remove(); mainwp_updraft_restorer_checkstage2(0);});
			} else if (resp.p < 100 || base != 'uddlstatus_') {
				jQuery('#'+stid+' .raw').html(resp.m);
			} else {
				jQuery('#'+stid+' .raw').html(mwp_updraftlion.fileready+' '+ mwp_updraftlion.youshould+' <a class="button" href="#" onclick="event.preventDefault();mainwp_updraftplus_downloadstage2(\''+nonce+'\', \''+what+'\', \''+findex+'\',' + site_ID + ')\">'+mwp_updraftlion.downloadtocomputer+'</a> '+mwp_updraftlion.andthen+' <a id="uddownloaddelete_'+nonce+'_'+what+'" class="button" hreft="#" onclick="event.preventDefault();mainwp_updraftplus_deletefromserver(\''+nonce+'\', \''+what+'\', \''+findex+'\',' + site_ID + ')\">'+mwp_updraftlion.deletefromserver+'</button>');
			}
		}
		dlstatus_lastlog = response;
	} else if (resp.m != null) {
			jQuery('#'+stid+' .raw').html(resp.m);
	} else {
		jQuery('#'+stid+' .raw').html(mwp_updraftlion.jsonnotunderstood+' ('+response+')');
		cancel_repeat = 1;
	}
	return cancel_repeat;
}

function mainwp_updraftplus_deletefromserver(timestamp, type, findex, site_id) {
        if (!site_id) return;

	if (!findex) findex=0;
	var pdata = {
		action: 'mainwp_updraft_download_backup',
		stage: 'delete',
		timestamp: timestamp,
		type: type,
		findex: findex,
		_wpnonce: mwp_updraft_download_nonce,
                updraftRequestSiteID: site_id
	};
	jQuery.post(ajaxurl, pdata, function(response) {
		if (response != 'deleted') {
			alert('We requested to delete the file, but could not understand the server\'s response '+response);
		}
	});
}

var mwp_updraft_historytimer = 0;
var mwp_calculated_diskspace = 0;
var mwp_updraft_historytimer_notbefore = 0;

function mainwp_updraft_historytimertoggle(forceon) {
	if (!mwp_updraft_historytimer || forceon == 1) {
		mainwp_updraft_updatehistory(0, 0);
		mwp_updraft_historytimer = setInterval(function(){mainwp_updraft_updatehistory(0, 0);}, 30000);
		if (!mwp_calculated_diskspace) {
			mainwp_updraftplus_diskspace();
			mwp_calculated_diskspace=1;
		}
	} else {
		clearTimeout(mwp_updraft_historytimer);
		mwp_updraft_historytimer = 0;
	}
}


function mainwp_updraft_restore_setoptions(entities, pObj) {
        mainwp_updraft_reset_wrapper(pObj);
        mainwp_updraft_set_process_siteid();
        if (!mwp_updraft_process_siteid) return;

	var howmany = 0;
	jQuery('input[name="updraft_restore[]"]').each(function(x,y){
		var entity = jQuery(y).val();
		var epat = entity+'=([0-9,]+)';
		var eregex = new RegExp(epat);
		var ematch = entities.match(eregex);
		if (ematch) {
			jQuery(y).removeAttr('disabled').data('howmany', ematch[1]).parent().show();
			howmany++;
			if ('db' == entity) { howmany += 4.5;}
			if (jQuery(y).is(':checked')) {
				// This element may or may not exist. The purpose of explicitly calling show() is that Firefox, when reloading (including via forwards/backwards navigation) will remember checkbox states, but not which DOM elements were showing/hidden - which can result in some being hidden when they should be shown, and the user not seeing the options that are/are not checked.
				jQuery('#updraft_restorer_'+entity+'options').show();
			}
		} else {
			jQuery(y).attr('disabled','disabled').parent().hide();
		}
	});
	var cryptmatch = entities.match(/dbcrypted=1/);
	if (cryptmatch) {
		jQuery('.updraft_restore_crypteddb').show();
	} else {
		jQuery('.updraft_restore_crypteddb').hide();
	}
	var dmatch = entities.match(/meta_foreign=([12])/);
	if (dmatch) {
		jQuery('#updraft_restore_meta_foreign').val(dmatch[1]);
	} else {
		jQuery('#updraft_restore_meta_foreign').val('0');
	}
	var height = 336+howmany*20;
	jQuery('#mwp-updraft-restore-modal').dialog("option", "height", height);
}

var mwp_updraft_workingWrapper = null;
var mwp_updraft_process_siteid = 0;

function mainwp_updraft_reset_wrapper(pObj) {
    mwp_updraft_workingWrapper = jQuery(pObj).closest('.mwp_updraft_content_wrapper');
    //console.log(mwp_updraft_workingWrapper);
}

// some case need to call mainwp_updraft_reset_wrapper() before call the function mainwp_updraft_set_process_siteid()
function mainwp_updraft_set_process_siteid() {
    mwp_updraft_process_siteid = 0;
    if (!!mwp_updraft_individual_siteid && mwp_updraft_individual_siteid) // !! is not null
        mwp_updraft_process_siteid = mwp_updraft_individual_siteid;
    else if (mwp_updraft_workingWrapper) {
        mwp_updraft_process_siteid = mwp_updraft_workingWrapper.attr('site-id');
    }
    //console.log(mwp_updraft_process_siteid);
    return mwp_updraft_process_siteid;
}

function mainwp_updraft_updatehistory(rescan, remotescan) {
        if (!mwp_updraft_individual_siteid)
            return;

	var unixtime = Math.round(new Date().getTime() / 1000);

	if (1 == rescan || 1 == remotescan) {
		mwp_updraft_historytimer_notbefore = unixtime + 30;
	} else {
		if (unixtime < mwp_updraft_historytimer_notbefore) {
			console.log("Update history skipped: "+unixtime.toString()+" < "+mwp_updraft_historytimer_notbefore.toString());
			return;
		}
	}

	if (rescan == 1) {
		if (remotescan == 1) {
			jQuery('#mwp_updraft_existing_backups').html('<p style="text-align:center;"><em>'+mwp_updraftlion.rescanningremote+'</em></p>');
		} else {
			jQuery('#mwp_updraft_existing_backups').html('<p style="text-align:center;"><em>'+mwp_updraftlion.rescanning+'</em></p>');
		}
	}
	jQuery.get(ajaxurl,
                    {
                            action: 'mainwp_updraft_ajax',
                            subaction: 'historystatus',
                            nonce: mwp_updraft_credentialtest_nonce,
                            rescan: rescan,
                            remotescan: remotescan,
                            updraftRequestSiteID: mwp_updraft_individual_siteid
                    }, function(response) {
                            try {
                                resp = jQuery.parseJSON(response);
                                if (resp.error) {
                                    jQuery('#mwp_updraft_existing_backups').html('<em style="color: red">' + resp.error + '</em>');
                                } else {
                                    if (resp.n != null) { jQuery('#mwp_updraftplus_backup_tab_lnk').html(resp.n); }
                                    if (resp.t != null) { jQuery('#mwp_updraft_existing_backups').html(resp.t); }
                                }
                            } catch(err) {
                                console.log(mwp_updraftlion.unexpectedresponse+' '+response);
                                console.log(err);
                            }
                    });
}

jQuery(document).ready(function($){
// Section: Plupload
	try {
		if (typeof updraft_plupload_config !== 'undefined') {
			plupload_init();
		}
	} catch (err) {
		console.log(err);
	}

	function plupload_init() {

		// create the uploader and pass the config from above
		var uploader = new plupload.Uploader(updraft_plupload_config);

		// checks if browser supports drag and drop upload, makes some css adjustments if necessary
		uploader.bind('Init', function(up){
			var uploaddiv = $('#plupload-upload-ui');

			if(up.features.dragdrop){
				uploaddiv.addClass('drag-drop');
				$('#drag-drop-area')
				.bind('dragover.wp-uploader', function(){ uploaddiv.addClass('drag-over'); })
				.bind('dragleave.wp-uploader, drop.wp-uploader', function(){ uploaddiv.removeClass('drag-over'); });

			} else {
				uploaddiv.removeClass('drag-drop');
				$('#drag-drop-area').unbind('.wp-uploader');
			}
		});

		uploader.init();

		// a file was added in the queue
		uploader.bind('FilesAdded', function(up, files){
		// 				var hundredmb = 100 * 1024 * 1024, max = parseInt(up.settings.max_file_size, 10);

		plupload.each(files, function(file){

			if (! /^backup_([\-0-9]{15})_.*_([0-9a-f]{12})-[\-a-z]+([0-9]+?)?(\.(zip|gz|gz\.crypt))?$/i.test(file.name) && ! /^log\.([0-9a-f]{12})\.txt$/.test(file.name)) {
				var accepted_file = false;
				for (var i = 0; i<updraft_accept_archivename.length; i++) {
					if (updraft_accept_archivename[i].test(file.name)) {
						var accepted_file = true;
					}
				}
				if (!accepted_file) {
					if (/\.(zip|tar|tar\.gz|tar\.bz2)$/i.test(file.name) || /\.sql(\.gz)?$/i.test(file.name)) {
						jQuery('#mwp-updraft-message-modal-innards').html('<p><strong>'+file.name+"</strong></p> "+mwp_updraftlion.notarchive2);
						jQuery('#mwp-updraft-message-modal').dialog('open');
					} else {
						alert(file.name+": "+mwp_updraftlion.notarchive);
					}
					uploader.removeFile(file);
					return;
				}
			}

			// a file was added, you may want to update your DOM here...
			$('#filelist').append(
				'<div class="file" id="' + file.id + '"><b>' +
				file.name + '</b> (<span>' + plupload.formatSize(0) + '</span>/' + plupload.formatSize(file.size) + ') ' +
				'<div class="fileprogress"></div></div>');
		});

			up.refresh();
			up.start();
		});

		uploader.bind('UploadProgress', function(up, file) {
			$('#' + file.id + " .fileprogress").width(file.percent + "%");
			$('#' + file.id + " span").html(plupload.formatSize(parseInt(file.size * file.percent / 100)));
		});

		uploader.bind('Error', function(up, error) {
			alert(mwp_updraftlion.uploaderr+' (code '+error.code+') : '+error.message+' '+mwp_updraftlion.makesure);
		});


		// a file was uploaded
		uploader.bind('FileUploaded', function(up, file, response) {

			if (response.status == '200') {
				// this is your ajax response, update the DOM with it or something...
				try {
					resp = jQuery.parseJSON(response.response);
					if (resp.e) {
						alert(mwp_updraftlion.uploaderror+" "+resp.e);
					} else if (resp.dm) {
						alert(resp.dm);
						mainwp_updraft_updatehistory(1, 0);
					} else if (resp.m) {
						mainwp_updraft_updatehistory(1, 0);
					} else {
						alert('Unknown server response: '+response.response);
					}

				} catch(err) {
					console.log(response);
					alert(mwp_updraftlion.jsonnotunderstood);
				}

			} else {
				alert('Unknown server response status: '+response.code);
				console.log(response);
			}

		});
	}

})

function mainwp_updraft_openrestorepanel(toggly) {
    mwp_updraft_console_focussed_tab = 2;
    mainwp_updraft_historytimertoggle(toggly);
}

jQuery(document).ready(function($){

        jQuery.get(ajaxurl, { action: 'mainwp_updraft_ajax', subaction: 'ping', nonce: mwp_updraft_credentialtest_nonce }, function(data, response) {
		if ('success' == response && data != 'pong' && data.indexOf('pong')>=0) {
			//jQuery('#ud-whitespace-warning').show();
		}
	});

	try {
		if (typeof updraft_plupload_config2 !== 'undefined') {
			plupload_init();
		}
	} catch (err) {
		console.log(err);
	}

	function plupload_init() {
		// create the uploader and pass the config from above
		var uploader = new plupload.Uploader(updraft_plupload_config2);

		// checks if browser supports drag and drop upload, makes some css adjustments if necessary
		uploader.bind('Init', function(up){
			var uploaddiv = $('#plupload-upload-ui2');

			if(up.features.dragdrop){
				uploaddiv.addClass('drag-drop');
				$('#drag-drop-area2')
				.bind('dragover.wp-uploader', function(){ uploaddiv.addClass('drag-over'); })
				.bind('dragleave.wp-uploader, drop.wp-uploader', function(){ uploaddiv.removeClass('drag-over'); });
			} else {
				uploaddiv.removeClass('drag-drop');
				$('#drag-drop-area2').unbind('.wp-uploader');
			}
		});

		uploader.init();

		// a file was added in the queue
		uploader.bind('FilesAdded', function(up, files){
			// 				var hundredmb = 100 * 1024 * 1024, max = parseInt(up.settings.max_file_size, 10);

			plupload.each(files, function(file){

				if (! /^backup_([\-0-9]{15})_.*_([0-9a-f]{12})-db([0-9]+)?\.(gz\.crypt)$/i.test(file.name)) {
					alert(file.name+': '+mwp_updraftlion.notdba);
					uploader.removeFile(file);
					return;
				}

				// a file was added, you may want to update your DOM here...
				$('#filelist2').append(
					'<div class="file" id="' + file.id + '"><b>' +
					file.name + '</b> (<span>' + plupload.formatSize(0) + '</span>/' + plupload.formatSize(file.size) + ') ' +
					'<div class="fileprogress"></div></div>');
			});

			up.refresh();
			up.start();
		});

		uploader.bind('UploadProgress', function(up, file) {
			$('#' + file.id + " .fileprogress").width(file.percent + "%");
			$('#' + file.id + " span").html(plupload.formatSize(parseInt(file.size * file.percent / 100)));
		});

		uploader.bind('Error', function(up, error) {
			alert(mwp_updraftlion.uploaderr+' (code '+error.code+") : "+error.message+" "+mwp_updraftlion.makesure);
		});

		// a file was uploaded
		uploader.bind('FileUploaded', function(up, file, response) {

			if (response.status == '200') {
				// this is your ajax response, update the DOM with it or something...
				if (response.response.substring(0,6) == 'ERROR:') {
					alert(mwp_updraftlion.uploaderror+" "+response.response.substring(6));
				} else if (response.response.substring(0,3) == 'OK:') {
					bkey = response.response.substring(3);
					$('#' + file.id + " .fileprogress").hide();
					$('#' + file.id).append(mwp_updraftlion.uploaded+' <a href="?page=updraftplus&action=downloadfile&updraftplus_file='+bkey+'&decrypt_key='+$('#updraftplus_db_decrypt').val()+'">'+mwp_updraftlion.followlink+'</a> '+mwp_updraftlion.thiskey+' '+$('#updraftplus_db_decrypt').val().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;"));
				} else {
					alert(mwp_updraftlion.unknownresp+' '+response.response);
				}
			} else {
				alert(mwp_updraftlion.ukrespstatus+' '+response.code);
			}

		});
	}

	jQuery('#updraft-hidethis').remove();

});


function mainwp_updraftplus_diskspace() {

        if (mwp_updraft_individual_siteid == 0)
            return;

        var statusEl = jQuery('#mwp_updraft_diskspaceused');
	statusEl.html('<em>'+mwp_updraftlion.calculating+'</em>');
	jQuery.get(ajaxurl,
                    {   action: 'mainwp_updraft_ajax',
                        entity: 'updraft',
                        subaction: 'diskspaceused',
                        nonce: mwp_updraft_credentialtest_nonce,
                        updraftRequestSiteID: mwp_updraft_individual_siteid
                    },
                    function(response) {
                       if (response) {
                            if (response.error) {
                                statusEl.html('<em style="color: red">' + response.error + '</em>');
                            } else if (response.diskspaceused) {
                                statusEl.html('<em>' + response.diskspaceused + '</em>');
                            } else {
                                statusEl.html(mwp_updraftlion.undefinederror);
                            }
                        } else {
                            statusEl.html(mwp_updraftlion.undefinederror);
                        }
                    }, 'json');
}

function mainwp_updraftplus_diskspace_entity(key) {
        mainwp_updraft_set_process_siteid();
        if (!mwp_updraft_process_siteid) return;

        var statusEl = jQuery('#mwp_updraft_diskspaceused_'+key);
	statusEl.html('<em>'+mwp_updraftlion.calculating+'</em>');

	jQuery.get(ajaxurl,
                { action: 'mainwp_updraft_ajax',
                    subaction: 'diskspaceused',
                    entity: key,
                    nonce: mwp_updraft_credentialtest_nonce,
                    updraftRequestSiteID: mwp_updraft_process_siteid
                },
                function(response) {
                    if (response) {
                        if (response.error) {
                            statusEl.html('<em style="color: red">' + response.error + '</em>');
                        } else if (response.diskspaceused) {
                            statusEl.html('<em>' + response.diskspaceused + '</em>');
                        } else {
                            statusEl.html(mwp_updraftlion.undefinederror);
                        }
                    } else {
                        statusEl.html(mwp_updraftlion.undefinederror);
                    }
                }, 'json');
}

var mwp_lastbackup_sdata = {
    action: 'mainwp_updraft_ajax',
    subaction: 'lastbackup'
};

function mainwp_updraft_showlastbackup(){
        mainwp_updraft_set_process_siteid();
        if (!mwp_updraft_process_siteid) return;

        mwp_lastbackup_sdata.updraftRequestSiteID = mwp_updraft_process_siteid

	mwp_lastbackup_sdata.nonce = mwp_updraft_credentialtest_nonce;
	jQuery.get(ajaxurl, mwp_lastbackup_sdata, function(response) {
                jQuery('#mwp_updraft_lastbackup_lnk').hide();
                if (response) {
                    if (response.error) {
                        jQuery('#mwp_updraft_lastbackup_container').html('<em style="color: red">' + response.error + '</em>');
                    } else if (response.b) {
                        if (mwp_lastbackup_laststatus == response.b) {
                                setTimeout(function(){mainwp_updraft_showlastbackup();}, 7000);
                        } else {
                                jQuery('#mwp_updraft_lastbackup_container').html(response.b);
                        }
                        mwp_lastbackup_laststatus = response.b;
                    } else {
                        jQuery('#mwp_updraft_lastbackup_container').html(mwp_updraftlion.undefinederror);
                    }
                } else {
                    jQuery('#mwp_updraft_lastbackup_container').html(mwp_updraftlion.undefinederror);
                }


	}, 'json');
}


function mainwp_updraft_activejobs_delete(jobid) {
    if (mwp_updraft_individual_siteid == 0)
            return;
    jQuery.get(ajaxurl,
            {   action: 'mainwp_updraft_ajax',
                subaction: 'activejobs_delete',
                jobid: jobid,
                nonce: mwp_updraft_credentialtest_nonce,
                updraftRequestSiteID: mwp_updraft_individual_siteid
            },
            function(response) {
                try {
                        var resp = jQuery.parseJSON(response);
                        if (resp.error) {
                            alert(resp.error);
                        } else if (resp.ok == 'Y') {
                                jQuery('#updraft-jobid-'+jobid).html(resp.m).fadeOut('slow').remove();
                        } else if (resp.ok == 'N') {
                                alert(resp.m);
                        } else {
                                alert(mwp_updraftlion.unexpectedresponse+' '+response);
                        }
                } catch(err) {
                        console.log(err);
                        alert(mwp_updraftlion.unexpectedresponse+' '+response);
                }
            });
}

function mainwp_next_scheduledbackups(site_id){
        jQuery.get( ajaxurl,
        {
            action: 'mainwp_updraft_ajax',
            subaction: 'nextscheduledbackups',
            nonce: mwp_updraft_credentialtest_nonce,
            updraftRequestSiteID: site_id
        },
        function(response) {
            if (response) {
                if (response.error) {
                    jQuery('#mwp_updraft_next_scheduled_backups').html('<em style="color: red">' + response.error + '</em>');
                } else if (response.n) {
                    jQuery('#mwp_updraft_next_scheduled_backups').html(response.n);
                } else {
                    jQuery('#mwp_updraft_next_scheduled_backups').html(mwp_updraftlion.undefinederror);
                }

                if (response.backup_disabled !== 'undefined') {
                    if (response.backup_disabled == 1 ) {
                        jQuery('#mwp_updraft_backupnow_btn').attr('disabled', 'true');
                        jQuery('#mwp_updraft_backupnow_btn').attr('title', mwp_updraftlion.disabledbackup);
                    } else {
                        jQuery('#mwp_updraft_backupnow_btn').removeAttr('disabled');
                        jQuery('#mwp_updraft_backupnow_btn').removeAttr('title');
                    }
                }
            } else {
                jQuery('#mwp_updraft_next_scheduled_backups').html(mwp_updraftlion.undefinederror);
            }

        }, 'json');
}


function mainwp_updraft_popuplog(backup_nonce, pObj) {
        if (pObj) {
            mainwp_updraft_reset_wrapper(pObj);
        }
        mainwp_updraft_set_process_siteid();
        if (!mwp_updraft_process_siteid) return;

        popuplog_sdata = {
                action: 'mainwp_updraft_ajax',
                subaction: 'poplog',
                nonce: mwp_updraft_credentialtest_nonce,
                backup_nonce: backup_nonce,
                updraftRequestSiteID: mwp_updraft_process_siteid
        };

        jQuery('#mwp_updraft-poplog').dialog("option", "title", 'log.'+backup_nonce+'.txt');
        jQuery('#mwp_updraft-poplog-content').html('<em>log.'+backup_nonce+'.txt ...</em>');
        jQuery('#mwp_updraft-poplog').dialog("open");

        jQuery.get(ajaxurl, popuplog_sdata, function(response){

                var resp = jQuery.parseJSON(response);

                mwp_updraft_poplog_log_pointer = resp.pointer;
                mwp_updraft_poplog_log_nonce = resp.nonce;


                var download_url = '?page=updraftplus&action=downloadlog&force_download=1&updraftplus_backup_nonce='+resp.nonce;
                download_url =  mainwp_updraft_get_donwnloadlink(mwp_updraft_process_siteid, download_url);


                jQuery('#mwp_updraft-poplog-content').html(resp.html);

                var log_popup_buttons = {};
                log_popup_buttons[mwp_updraftlion.download] = function() { window.open(download_url, '_blank'); };
                log_popup_buttons[mwp_updraftlion.close] = function() { jQuery(this).dialog("close"); };

                //Set the dialog buttons: Download log, Close log
                jQuery('#mwp_updraft-poplog').dialog("option", "buttons", log_popup_buttons);
                //[
                        //{ text: "Download", click: function() { window.location.href = download_url } },
                        //{ text: "Close", click: function(){ jQuery( this ).dialog("close");} }
                //]
                jQuery('#mwp_updraft-poplog').dialog("option", "title", 'log.'+resp.nonce+'.txt');

                mwp_updraft_poplog_lastscroll = -1;

        });
}

var mwp_updraft_interval_week_val = false;
var mwp_updraft_interval_month_val = false;

function mainwp_updraft_intervals_monthly_or_not(selector_id, now_showing) {
	var selector = '#mwp-updraft-navtab-settings-content #'+selector_id;
	var current_length = jQuery(selector+' option').length;
	var is_monthly = ('monthly' == now_showing) ? true : false;
	var existing_is_monthly = false;
	if (current_length > 10) { existing_is_monthly = true; }
	if (!is_monthly && !existing_is_monthly) {
		return;
	}
	if (is_monthly && existing_is_monthly) {
		if ('monthly' == now_showing) {
			// existing_is_monthly does not mean the same as now_showing=='monthly'. existing_is_monthly refers to the drop-down, not whether the drop-down is being displayed. We may need to add these words back.
			jQuery('.updraft_monthly_extra_words_'+selector_id).remove();
			jQuery(selector).before('<span class="updraft_monthly_extra_words_'+selector_id+'">'+mwp_updraftlion.day+' </span>').after('<span class="updraft_monthly_extra_words_'+selector_id+'"> '+mwp_updraftlion.inthemonth+' </span>');
		}
		return;
	}
	jQuery('.updraft_monthly_extra_words_'+selector_id).remove();
	if (is_monthly) {
		// Save the old value
		mwp_updraft_interval_week_val = jQuery(selector+' option:selected').val();
		jQuery(selector).html(mwp_updraftlion.mdayselector).before('<span class="updraft_monthly_extra_words_'+selector_id+'">'+mwp_updraftlion.day+' </span>').after('<span class="updraft_monthly_extra_words_'+selector_id+'"> '+mwp_updraftlion.inthemonth+' </span>');
		var select_mday = (mwp_updraft_interval_month_val === false) ? 1 : mwp_updraft_interval_month_val;
		// Convert from day of the month (ordinal) to option index (starts at 0)
		select_mday = select_mday - 1;
		jQuery(selector+" option:eq("+select_mday+")").prop('selected', true);
	} else {
		// Save the old value
		mwp_updraft_interval_month_val = jQuery(selector+' option:selected').val();
		jQuery(selector).html(mwp_updraftlion.dayselector);
		var select_day = (mwp_updraft_interval_week_val === false) ? 1 : mwp_updraft_interval_week_val;
		jQuery(selector+" option:eq("+select_day+")").prop('selected', true);
	}
}

function mainwp_updraft_check_same_times() {
	var dbmanual = 0;
	//var file_interval = jQuery('#updraft-navtab-settings-content .updraft_interval').val();
        var file_interval = jQuery('#mwp_updraft_interval').val();
	if (file_interval == 'manual') {

		jQuery('#updraft_files_timings').hide();
	} else {

		jQuery('#updraft_files_timings').show();
	}

	if ('weekly' == file_interval || 'fortnightly' == file_interval || 'monthly' == file_interval) {
		mainwp_updraft_intervals_monthly_or_not('updraft_startday_files', file_interval);
		jQuery('#updraft_startday_files').show();
	} else {
		jQuery('.updraft_monthly_extra_words_updraft_startday_files').remove();
		jQuery('#updraft_startday_files').hide();
	}

	//var db_interval = jQuery('#updraft-navtab-settings-content .updraft_interval_database').val();
        var db_interval = jQuery('#mwp_updraft_interval_database').val();
	if (db_interval == 'manual') {
		dbmanual = 1;

		jQuery('#updraft_db_timings').hide();
	}

	if ('weekly' == db_interval || 'fortnightly' == db_interval || 'monthly' == db_interval) {
		mainwp_updraft_intervals_monthly_or_not('updraft_startday_db', db_interval);
		jQuery('#updraft_startday_db').show();
	} else {
		jQuery('.updraft_monthly_extra_words_updraft_startday_db').remove();
		jQuery('#updraft_startday_db').hide();
	}

	if (db_interval == file_interval) {

		jQuery('#updraft_db_timings').hide();

		if (0 == dbmanual) {
			jQuery('.updraft_same_schedules_message').show();
		} else {
			jQuery('.updraft_same_schedules_message').hide();
		}
	} else {
		jQuery('.updraft_same_schedules_message').hide();
		if (0 == dbmanual) {

			jQuery('#updraft_db_timings').show();
		}
	}
}


function mainwp_updraft_check_same_times_old() {
	var dbmanual = 0;
	var file_interval = jQuery('#mwp_updraft_interval').val();

	if (file_interval == 'manual') {
		jQuery('#updraft_files_timings').css('opacity', '0.25');
	} else {
		jQuery('#updraft_files_timings').css('opacity', 1);
	}

	if ('weekly' == file_interval || 'fortnightly' == file_interval || 'monthly' == file_interval) {
		jQuery('#updraft_startday_files').show();
	} else {
		jQuery('#updraft_startday_files').hide();
	}

	var db_interval = jQuery('#mwp_updraft_interval_database').val();
	if (db_interval == 'manual') {
		dbmanual = 1;
		jQuery('#updraft_db_timings').css('opacity', '0.25');
	}

	if ('weekly' == db_interval || 'fortnightly' == db_interval || 'monthly' == db_interval) {
		jQuery('#updraft_startday_db').show();
	} else {
		jQuery('#updraft_startday_db').hide();
	}

	if (db_interval == file_interval) {
		jQuery('#updraft_db_timings').css('opacity','0.25');
	} else {
		if (0 == dbmanual) jQuery('#updraft_db_timings').css('opacity', '1');
	}
}


jQuery(document).ready(function($){
        jQuery('#updraftvault_settings_cell').on('click', '#updraftvault_disconnect', function(e) {
		e.preventDefault();
                jQuery('#mainwp_updraftvault_connect_message_box').html('').hide();
		jQuery('#updraftvault_disconnect').html(mwp_updraftlion.disconnecting);
		try {
			jQuery.post(ajaxurl,  {
				action: 'mainwp_updraft_ajax',
				subaction: 'vault_disconnect',
				nonce: mwp_updraft_credentialtest_nonce,
                                updraftRequestSiteID: mwp_updraft_process_siteid,
                                individual: 1
			}, function(response) {
				jQuery('#updraftvault_disconnect').html(mwp_updraftlion.disconnect);
				try {
					resp = jQuery.parseJSON(response);
                                        if (resp.hasOwnProperty('error')) {
                                            jQuery('#mainwp_updraftvault_connect_message_box').html('<div class="mainwp_info-box-red">' + resp.error + '</div>').show();
                                        } else if ( resp.hasOwnProperty('message')) {
                                            jQuery('#mainwp_updraftvault_connect_message_box').html('<div class="mainwp_info-box-yellow">' + resp.message + '</div>').show();
                                        } else if (resp.hasOwnProperty('html')) {
                                            jQuery('#updraftvault_settings_connected').html(resp.html).slideUp();
                                            jQuery('#updraftvault_settings_default').slideDown();
					}
				} catch (err) {
					alert(mwp_updraftlion.unexpectedresponse+' '+response);
					console.log(response);
					console.log(err);
				}
			});
		} catch (err) {
			jQuery('#updraftvault_disconnect').html(mwp_updraftlion.disconnect);
			console.log(err);
		}
	});

	jQuery('#updraftvault_connect').click(function(e) {
		e.preventDefault();
                jQuery('#mainwp_updraftvault_connect_message_box').html('').hide();
		jQuery('#updraftvault_settings_default').slideUp();
		jQuery('#updraftvault_settings_connect').slideDown();
	});

        jQuery('#updraftvault_settings_cell').on('click', '.updraftvault_backtostart', function(e) {
		e.preventDefault();
                jQuery('#mainwp_updraftvault_connect_message_box').html('').hide();
		jQuery('#updraftvault_settings_connect').slideUp();
		jQuery('#updraftvault_settings_connected').slideUp();
		jQuery('#updraftvault_settings_default').slideDown();
	});

	jQuery('#updraftvault_connect_go').click(function(e) {
		jQuery('#updraftvault_connect_go').html(mwp_updraftlion.connecting);
                jQuery('#mainwp_updraftvault_connect_message_box').html('').hide();
		jQuery.post(ajaxurl,  {
			action: 'mainwp_updraft_ajax',
			subaction: 'vault_connect',
			nonce: mwp_updraft_credentialtest_nonce,
			email: jQuery('#updraftvault_email').val(),
			pass: jQuery('#updraftvault_pass').val(),
                        updraftRequestSiteID: mwp_updraft_process_siteid,
                        individual: 1
		}, function(response) {
			jQuery('#updraftvault_connect_go').html(mwp_updraftlion.connect);
			try {
				resp = jQuery.parseJSON(response);
			} catch(err) {
				console.log(err);
				console.log(response);
				alert(mwp_updraftlion.unexpectedresponse+' '+response);
				return;
			}
                        if (resp) {
                            if (resp.hasOwnProperty('error')) {
                                    jQuery('#mainwp_updraftvault_connect_message_box').html('<div class="mainwp_info-box-red">' + resp.error + '</div>').show();
                                    if (resp.hasOwnProperty('code') && resp.code == 'no_quota') {
                                            jQuery('#updraftvault_settings_connect').slideUp();
                                            jQuery('#updraftvault_settings_default').slideDown();
                                    }
                            } else if ( resp.hasOwnProperty('message')) {
                                jQuery('#mainwp_updraftvault_connect_message_box').html('<div class="mainwp_info-box-yellow">' + resp.message + '</div>').show();
                            } else if (resp.hasOwnProperty('e')) {
                                    jQuery('#mainwp_updraftvault_connect_message_box').html('<div class="mainwp_info-box-red">' + resp.e + '</div>').show();
                                    if (resp.hasOwnProperty('code') && resp.code == 'no_quota') {
                                            jQuery('#updraftvault_settings_connect').slideUp();
                                            jQuery('#updraftvault_settings_default').slideDown();
                                    }
                            } else if (resp.hasOwnProperty('connected') && resp.connected && resp.hasOwnProperty('html')) {
                                    jQuery('#updraftvault_settings_connect').slideUp();
                                    jQuery('#updraftvault_settings_connected').html(resp.html).slideDown();
                            } else {
				console.log(response);
				console.log(resp);
				alert(mwp_updraftlion.unexpectedresponse+' '+response);
                            }
                        } else {
				console.log(response);
				console.log(resp);
				alert(mwp_updraftlion.unexpectedresponse+' '+response);
			}

		});
		return false;
	});

        jQuery('#updraftvault_bulk_connect_go').click(function(e) {
            	jQuery('#updraftvault_bulk_connect_go').html(mwp_updraftlion.running);
		jQuery.post(ajaxurl,  {
			action: 'mainwp_updraftplus_load_sites',
                        what: 'vault_bulk_connect',
			email: jQuery('#updraftvault_email').val(),
			pass: jQuery('#updraftvault_pass').val()
		}, function(response) {
			jQuery( '#mwp_updraftplus_setting_tab' ).html( response );
		});
		return false;
	});
        mainwp_updraft_remote_storage_tabs_setup();
});


function mainwp_updraft_remote_storage_tabs_setup() {

	var anychecked = 0;
	var set = jQuery('.mwp_updraft_servicecheckbox:checked');

	jQuery(set).each(function(ind, obj) {
		var ser = jQuery(obj).val();

		if(jQuery(obj).attr('id') != 'mwp_updraft_servicecheckbox_none') {
			anychecked++;
		}

		jQuery('.remote-tab-'+ser).show();
		if(ind == jQuery(set).length-1){
			mainwp_updraft_remote_storage_tab_activation(ser);
		}
	});
	if (anychecked > 0) {
		jQuery('.mwp_updraftplusmethod.none').hide();
	}

	jQuery('.mwp_updraft_servicecheckbox').change(function() {
		var sclass = jQuery(this).attr('id');
		if ('mwp_updraft_servicecheckbox_' == sclass.substring(0,28)) {
			var serv = sclass.substring(28);
			if (null != serv && '' != serv) {
				if (jQuery(this).is(':checked')) {
					anychecked++;
					jQuery('.remote-tab-'+serv).fadeIn();
					mainwp_updraft_remote_storage_tab_activation(serv);
				} else {
					anychecked--;
					jQuery('.remote-tab-'+serv).hide();
					//Check if this was the active tab, if yes, switch to another
					if(jQuery('.remote-tab-'+serv).data('active') == true){
						mainwp_updraft_remote_storage_tab_activation(jQuery('.remote-tab:visible').last().attr('name'));
					}
				}
			}
		}

		if (anychecked <= 0) {
			jQuery('.mwp_updraftplusmethod.none').fadeIn();
		} else {
			jQuery('.mwp_updraftplusmethod.none').hide();
		}
	});

	//Add stuff for free version
	jQuery('.mwp_updraft_servicecheckbox:not(.multi)').change(function(){
		var svalue = jQuery(this).attr('value');

		if (jQuery(this).is(':not(:checked)')) {
			jQuery('.mwp_updraftplusmethod.'+svalue).hide();
			jQuery('.mwp_updraftplusmethod.none').fadeIn();
		} else {
			jQuery('.mwp_updraft_servicecheckbox').not(this).prop('checked', false);
		}
	});

	var servicecheckbox = jQuery('.mwp_updraft_servicecheckbox');
	if (typeof servicecheckbox.labelauty === 'function') { servicecheckbox.labelauty(); }

}

function mainwp_updraft_remote_storage_tab_activation(the_method){
	jQuery('.mwp_updraftplusmethod').hide();
	jQuery('.remote-tab').data('active', false);
	jQuery('.remote-tab').removeClass('nav-tab-active');
	jQuery('.mwp_updraftplusmethod.'+the_method).show();
	jQuery('.remote-tab-'+the_method).data('active', true);
	jQuery('.remote-tab-'+the_method).addClass('nav-tab-active');
}
