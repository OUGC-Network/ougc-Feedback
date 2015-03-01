/***************************************************************************
 *
 *	OUGC Feedback plugin (/jscripts/ougc_feedback.js)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Adds a powerful feedback system to your forum.
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

var OUGC_Feedback = {
	Add: function(uid, pid, type, feedback)
	{
		var postData = 'action=add&uid=' + parseInt(uid) + '&pid=' + parseInt(pid) + '&type=' + parseInt(type) + '&feedback=' + parseInt(feedback);

		$.ajax(
		{
			type: 'get',
			dataType: 'json',
			url: 'feedback.php',
			data: postData,
			success: function (request)
			{
				if(request.error)
				{
					alert(request.error);
					return false;
				}

				$('.modal').html(request.modal);
				$('.modal').fadeIn('slow');
			},
			error: function (xhr)
			{
				MyBB.popupWindow('/feedback.php?action=add&uid=' + parseInt(uid) + '&pid=' + parseInt(pid) + '&type=' + parseInt(type) + '&feedback=' + parseInt(feedback));
				return false;
			}
		});

		return true;
	},

	DoAdd: function(uid, pid)
	{
		// Get form, serialize it and send it
		var postData = $('.feedback_' + parseInt(uid) + '_' + parseInt(pid)).serialize();

		$.ajax(
		{
			type: 'post',
			dataType: 'json',
			url: 'feedback.php',
			data: postData,
			success: function (request)
			{
				if(request.error)
				{
					alert(request.error);
					return false;
				}

				$('.modal_' + parseInt(uid) + '_' + parseInt(pid)).fadeOut('slow', function()
				{
					if(parseInt(pid))
					{
						$('.ougcfeedback_postbit_' + parseInt(uid)).html(request.replacement);
						$('#ougcfeedback_postbit_button_' + parseInt(pid)).fadeOut('slow');
					}
					else
					{
						$('#ougcfeedback_profile').html(request.replacement);
						$('#ougcfeedback_profile_add').fadeOut('slow');
					}
					//$('.ougcfeedback_postbit_' + parseInt(uid)).replaceWith(request.replacement);
					$('.modal_' + parseInt(uid) + '_' + parseInt(pid)).html(request.modal);
					$('.modal_' + parseInt(uid) + '_' + parseInt(pid)).fadeIn('slow');
					$('.modal').fadeIn('slow');
				});
			},
			error: function (xhr)
			{
				alert(xhr.responseText);
				return false;
			}
		});

		return true;
	},

	Report: function(fid)
	{
		MyBB.popupWindow('/report.php?modal=1&type=feedback&pid=' + parseInt(fid));
	},

	Delete: function(fid)
	{
		$.prompt(delete_feedback_confirm, {
			buttons:[
				{title: yes_confirm, value: true},
				{title: no_confirm, value: false}
			],
			submit: function(e,v,m,f){
				if(v == true)
				{
					var form = $('<form />',
					{
						method: 'post',
						action: 'feedback.php?action=delete',
						style: 'display: none;'
					});

					form.append(
						$('<input />',
						{
							name: 'fid',
							type: 'hidden',
							value: fid
						})
					);

					if(my_post_key)
					{
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
			}
		});

		return false;
	},
}

