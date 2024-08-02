/**
*Administration Javascript for Boilerplate extension
*/
jQuery(document).ready(function ($) {

    $('#mainwp-bl-token-form form').live('submit', function () {
        $(this).find('input[type=submit]').attr('disabled', 'disabled');
        $(this).find('.link-loading').css('visibility', 'visible');
        $(this).find('.field-error-msg').prev('br').remove();
        $(this).find('.error, .field-error-msg').remove();
        $(this).find('.field-error').removeClass('field-error');
        var fields = {};
        fields.token_id = ($(this).find('input[name=token_id]').size() > 0) ? $('input[name=token_id]').val() : '';
        fields.token_name = $(this).find('input[name=token_name]').val();
        fields.token_description = $(this).find('input[name=token_description]').val();
        fields.action = 'mainwp_boilerplate_save_token';
        var form_o = $(this);
        $.post(ajaxurl, fields, function (data) {
            if (data['success'] == true) {
                //mainwp_boilerplate_load_tokens();
            } else {
                if (data['error']) {
                    form_o.prepend('<div class="error"><p>' + data['error'] + '</p></div>');
                }
                if (typeof (data['field_error']['token_name']) != 'undefined') {
                    form_o.find('input[name=token_name]').addClass('field-error').after('<br /><span class="field-error-msg">' + data['field_error']['token_name'] + '</span>');
                }
            }
            form_o.find('input[type=submit]').removeAttr('disabled');
            form_o.find('.link-loading').css('visibility', 'hidden');
        }, 'json');
        return false;
    });

    // Create new token
    jQuery(document).on('click', '#mainwp-boilerplate-create-new-token', function (e) {
        var parent = jQuery(this).parents('#mainwp-boilerplate-new-token-modal');

        var errors = [];

        if (parent.find('input[name="token-name"]').val().trim() == '') {
            errors.push('Token name is required.');
        }

        if (parent.find('input[name="token-description"]').val().trim() == '') {
            errors.push('Token description is required.');
        }

        if (errors.length > 0) {
            parent.find('.ui.message').html(errors.join('<br />')).show();
            return false;
        }

        var fields = {
            token_name: parent.find('input[name="token-name"]').val(),
            token_description: parent.find('input[name="token-description"]').val(),
            action: 'mainwp_boilerplate_save_token'
        };

        parent.find('.ui.message').html('<i class="notched circle loading icon"></i> Saving token. Please wait...').show().removeClass('yellow');

        $.post(ajaxurl, fields, function (response) {
            if (response) {
                if (response['success']) {
                    window.location.reload();
                } else {
                    if (response['error']) {
                        parent.find('.ui.message').html(response['error']).show().removeClass('yellow').addClass('red');
                    } else {
                        parent.find('.ui.message').html('Undefined error occurred. Please try again.').show().removeClass('yellow').addClass('red');
                    }
                }
            } else {
                parent.find('.ui.message').html('Undefined error occurred. Please try again.').show().removeClass('yellow').addClass('red');
            }

        }, 'json');
        return false;
    });

    // Edit custom token
    jQuery(document).on('click', '#mainwp-boilerplate-edit-token', function (e) {
        var parent = jQuery(this).closest('.mainwp-token');
        var token_name = parent.find('td.token-name').html();
        var token_description = parent.find('td.token-description').html();
        var token_id = parent.attr('token_id');

        token_name = token_name.replace(/\[|\]/gi, "");

        jQuery('#mainwp-boilerplate-update-token-modal').modal('show');

        jQuery('input[name="token-name"]').val(token_name);
        jQuery('input[name="token-description"]').val(token_description);
        jQuery('input[name="token-id"]').val(token_id);

        return false;
    });

    // Update token
    jQuery(document).on('click', '#mainwp-save-boilerplate-token', function (e) {
        var parent = jQuery(this).parents('#mainwp-boilerplate-update-token-modal');

        var errors = [];

        if (parent.find('input[name="token-name"]').val().trim() == '') {
            errors.push('Token name is required.');
        }

        if (parent.find('input[name="token-description"]').val().trim() == '') {
            errors.push('Token description is required.');
        }

        if (errors.length > 0) {
            parent.find('.ui.message').html(errors.join('<br />')).show();
            return false;
        }

        var fields = {
            token_name: parent.find('input[name="token-name"]').val(),
            token_description: parent.find('input[name="token-description"]').val(),
            token_id: parent.find('input[name="token-id"]').val(),
            action: 'mainwp_boilerplate_save_token'
        };

        parent.find('.ui.message').html('<i class="notched circle loading icon"></i> Saving token. Please wait...').show().removeClass('yellow');

        $.post(ajaxurl, fields, function (response) {
            if (response) {
                if (response['success']) {
                    window.location.reload();
                } else {
                    if (response['error']) {
                        parent.find('.ui.message').html(response['error']).show().removeClass('yellow').addClass('red');
                    } else {
                        parent.find('.ui.message').html('Undefined error occurred. Please try again.').show().removeClass('yellow').addClass('red');
                    }
                }
            } else {
                parent.find('.ui.message').html('Undefined error occurred. Please try again.').show().removeClass('yellow').addClass('red');
            }
        }, 'json');
        return false;
    });

    $('.bpl_posts_list_delete_post').bind('click', function () {
        if (!confirm(__('Are you sure?'))) {
            return false;
        }
        var post_id = $(this).attr('bpl-post-id');
        $('tr#post-' + post_id).find('.bpl-row-ajax-info').html('<i class="fa fa-spinner fa-pulse"></i>').show();
        $.ajax({
            url: ajaxurl,
            data: {
                action: 'mainwp_boilerplate_delete_post',
                post_id: post_id,
            },
            success: function (data) {
                data = $.parseJSON(data);
                if (data && data.success === true) {
                    $('tr#post-' + post_id).html('<td colspan="3"><i class="fa fa-check-circle"></i> Item has been removed.</td>');
                    setTimeout(function () {
                        $('tr#post-' + post_id).fadeOut(1000);
                    }, 2000);
                } else {
                    $('tr#post-' + post_id).find('.bpl-row-ajax-info').html('<i class="fa fa-exclamation-circle"></i> ' + 'Can\'t delete the item.').show();
                }
            }, type: 'POST'
        });
        return false;
    });

    // Delete token
    jQuery(document).on('click', '#mainwp-boilerplate-delete-token', function (e) {
        if (confirm(__('Are you sure?'))) {
            var parent = $(this).closest('.mainwp-token');
            jQuery.post(ajaxurl, {
                action: 'mainwp_boilerplate_delete_token',
                token_id: parent.attr('token_id')
            }, function (data) {
                if (data && data.success) {
                    parent.html('<td colspan="3">' + __('Token has been deleted successfully.') + '</td>').fadeOut(2000);
                } else {
                    jQuery('#mainwp-message-zone').html(__('Token can not be deleted.')).addClass('red').show();
                }
            }, 'json');
            return false;
        }
        return false;
    });
});
