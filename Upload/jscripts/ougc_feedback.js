/***************************************************************************
 *
 *	OUGC Feedback plugin (/jscripts/ougc_feedback.js)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2019 Omar Gonzalez
 *
 *	Website: https://omarg.me
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

var OUGC_Plugins = OUGC_Plugins || {};

$.extend(true, OUGC_Plugins, {
	Feedback_unBind: function()
	{
		$('#ougcfeedback_form').submit(function(e)
		{
			e.preventDefault();
			e.unbind();
		});
	},
	Feedback_Add: function(uid, pid, type, feedback, reload)
	{
		var postData = 'action=add&uid=' + parseInt(uid) + '&pid=' + parseInt(pid) + '&type=' + parseInt(type) + '&feedback=' + parseInt(feedback) + '&reload=' + parseInt(reload);

		MyBB.popupWindow('/feedback.php?' + postData);
	},

	Feedback_DoAdd: function(uid, pid)
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
				}
				else
				{
					$.modal.close();
					$(request.modal).appendTo('body').modal({ fadeDuration: 250}).fadeIn('slow');

					if(request.reload)
					{
						location.reload(true);
						/*$.ajax({
							url: '',
							context: document.body,
							success: function(s, x){
								$(this).html(s);
							}
						});*/
					}
					else
					{
						$('.ougcfeedback_info_' + parseInt(uid)).html(request.replacement);

						if(request.hide_add)
						{
							$('.ougcfeedback_add_' + parseInt(uid)).fadeOut('slow');
						}
					}
				}
			},
			error: function (xhr)
			{
				$.modal.close();
				$(xhr.responseText).appendTo('body').modal({ fadeDuration: 250}).fadeIn('slow');
			}
		});
	},

	Feedback_Report: function(fid)
	{
		MyBB.popupWindow('/report.php?type=feedback&pid=' + parseInt(fid));
	},

	Feedback_Delete: function(fid)
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
	}
}