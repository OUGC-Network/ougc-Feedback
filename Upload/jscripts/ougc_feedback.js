/***************************************************************************
 *
 *	OUGC Feedback plugin (/jscripts/ougc_feedback.js)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012 Omar Gonzalez
 *
 *	Website: https://ougc.network
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
	Unbind: function()
	{
		$('#ougcfeedback_form').submit(function(e)
		{
			e.preventDefault();
			$(e).off();
		});
	},

	Add: function(uid, unique_id, type, feedback, reload, comment, back_button, feedback_code)
	{
		var postData = 'action=add&uid=' + parseInt(uid) + '&unique_id=' + parseInt(unique_id) + '&type=' + parseInt(type) + '&feedback=' + parseInt(feedback) + '&reload=' + parseInt(reload) + '&comment=' + String(comment) + '&back_button=' + parseInt(back_button) + '&feedback_code=' + String(feedback_code);

		MyBB.popupWindow('/feedback.php?' + postData);
	},

	Edit: function(fid, reload, feedback_code)
	{
		var postData = 'action=edit&fid=' + parseInt(fid) + '&reload=' + parseInt(reload) + '&feedback_code=' + parseInt(feedback_code);

		MyBB.popupWindow('/feedback.php?' + postData);
	},

	DoAdd: function(uid, unique_id)
	{
		// Get form, serialize it and send it
		var postData = $('.feedback_' + parseInt(uid) + '_' + parseInt(unique_id)).serialize();

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

	DoEdit: function(uid, unique_id, fid)
	{
		// Get form, serialize it and send it
		var postData = $('.feedback_' + parseInt(uid) + '_' + parseInt(unique_id)).serialize();

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
					return true;
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

	Report: function(fid)
	{
		MyBB.popupWindow('/report.php?type=feedback&pid=' + parseInt(fid));
	},

	Delete: function(fid, my_post_key, hard)
	{
		var result = confirm(delete_feedback_confirm);

		if(result)
		{
			var postData = '';

			if(parseInt(hard))
			{
				postData = '&hard=' + parseInt(hard);
			}

			var form = $('<form />',
			{
				method: 'post',
				action: 'feedback.php?action=delete' + postData,
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
	},

	Restore: function(fid)
	{
		var result = confirm(delete_feedback_confirm);

		if(result)
		{
			var form = $('<form />',
			{
				method: 'post',
				action: 'feedback.php?action=restore',
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
	},
}