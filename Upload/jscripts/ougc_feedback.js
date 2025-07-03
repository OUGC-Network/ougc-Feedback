/***************************************************************************
 *
 *    ougc Feedback plugin (/jscripts/ougc_feedback.js)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
 *
 *    Website: https://ougc.network
 *
 *    Adds a powerful feedback system to your forum.
 *
 ***************************************************************************

 ****************************************************************************
 This program is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 3 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

let OUGC_Feedback = {
    Unbind: function () {
        $('#ougcfeedback_form').submit(function (e) {
            e.preventDefault();
            $(e).off();
        });
    },

    Add: function (userID, uniqueID, feedbackType, feedbackValue, reload, feedbackComment, back_button, feedbackCode) {
        let postData = 'action=add&userID=' + parseInt(userID) + '&uniqueID=' + parseInt(uniqueID) + '&feedbackType=' + parseInt(feedbackType) + '&feedbackValue=' + parseInt(feedbackValue) + '&reload=' + parseInt(reload) + '&feedbackComment=' + String(feedbackComment) + '&back_button=' + parseInt(back_button) + '&feedbackCode=' + parseInt(feedbackCode);

        MyBB.popupWindow('/' + feedbackSystemUrl + '?' + postData);
    },

    Edit: function (feedbackID, reload, feedbackCode) {
        let postData = 'action=edit&feedbackID=' + parseInt(feedbackID) + '&reload=' + parseInt(reload) + '&feedbackCode=' + parseInt(feedbackCode);

        MyBB.popupWindow('/' + feedbackSystemUrl + '?' + postData);
    },

    DoAdd: function (userID, uniqueID) {
        let postData = $('.feedback_' + parseInt(userID) + '_' + parseInt(uniqueID)).serialize();

        $.ajax(
            {
                type: 'post',
                dataType: 'json',
                url: feedbackSystemUrl,
                data: postData,
                success: function (request) {
                    if (request.error) {
                        alert(request.error);
                    } else {
                        $.modal.close();
                        $(request.modal).appendTo('body').modal({fadeDuration: 250}).fadeIn('slow');

                        if (request.reload) {
                            location.reload(true);
                            /*$.ajax({
                                url: '',
                                context: document.body,
                                success: function(s, x){
                                    $(this).html(s);
                                }
                            });*/
                        } else {
                            $('.ougcfeedback_info_' + parseInt(userID)).html(request.replacement);

                            if (request.hide_add) {
                                $('.ougcfeedback_add_' + parseInt(userID)).fadeOut('slow');
                            }
                        }
                    }
                },
                error: function (xhr) {
                    $.modal.close();
                    $(xhr.responseText).appendTo('body').modal({fadeDuration: 250}).fadeIn('slow');
                }
            });
    },

    DoEdit: function (userID, uniqueID, feedbackID) {
        let postData = $('.feedback_' + parseInt(userID) + '_' + parseInt(uniqueID)).serialize();

        $.ajax(
            {
                type: 'post',
                dataType: 'json',
                url: feedbackSystemUrl,
                data: postData,
                success: function (request) {
                    if (request.error) {
                        alert(request.error);
                        return true;
                    } else {
                        $.modal.close();
                        $(request.modal).appendTo('body').modal({fadeDuration: 250}).fadeIn('slow');

                        if (request.reload) {
                            location.reload(true);
                            /*$.ajax({
                                url: '',
                                context: document.body,
                                success: function(s, x){
                                    $(this).html(s);
                                }
                            });*/
                        } else {
                            $('.ougcfeedback_info_' + parseInt(userID)).html(request.replacement);

                            if (request.hide_add) {
                                $('.ougcfeedback_add_' + parseInt(userID)).fadeOut('slow');
                            }
                        }
                    }
                },
                error: function (xhr) {
                    $.modal.close();
                    $(xhr.responseText).appendTo('body').modal({fadeDuration: 250}).fadeIn('slow');
                }
            });
    },

    Report: function (feedbackID) {
        MyBB.popupWindow('/report.php?type=feedback&pid=' + parseInt(feedbackID));
    },

    Delete: function (feedbackID, my_post_key, hard) {
        let result = confirm(delete_feedback_confirm);

        if (result) {
            let postData = '';

            if (parseInt(hard)) {
                postData = '&hard=' + parseInt(hard);
            }

            let form = $('<form />',
                {
                    method: 'post',
                    action: feedbackSystemUrl + '?action=delete' + postData,
                    style: 'display: none;'
                });

            form.append(
                $('<input />',
                    {
                        name: 'feedbackID',
                        type: 'hidden',
                        value: feedbackID
                    })
            );

            if (my_post_key) {
                form.append(
                    $('<input />',
                        {
                            name: 'my_post_key',
                            type: 'hidden',
                            value: my_post_key
                        })
                );
            }

            $('body').append(form);
            form.submit();
        }
    },

    Restore: function (feedbackID) {
        let result = confirm(delete_feedback_confirm);

        if (result) {
            let form = $('<form />',
                {
                    method: 'post',
                    action: feedbackSystemUrl + '?action=restore',
                    style: 'display: none;'
                });

            form.append(
                $('<input />',
                    {
                        name: 'feedbackID',
                        type: 'hidden',
                        value: feedbackID
                    })
            );

            if (my_post_key) {
                form.append(
                    $('<input />',
                        {
                            name: 'my_post_key',
                            type: 'hidden',
                            value: my_post_key
                        })
                );
            }

            $('body').append(form);
            form.submit();
        }
    },
}