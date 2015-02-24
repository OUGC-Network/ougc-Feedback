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
		MyBB.popupWindow('/feedback.php?action=add&uid=' + parseInt(uid) + '&pid=' + parseInt(pid) + '&type=' + parseInt(type) + '&feedback=' + parseInt(feedback) + '&modal=1');
	},

	DoAdd: function(uid, pid)
	{
		// Get form, serialize it and send it
		var postData = $('.feedback_' + parseInt(uid) + '_' + parseInt(pid)).serialize();

		postData = postData + '&modal=1';

		$.ajax(
		{
			type: 'post',
			dataType: 'json',
			url: 'feedback.php',
			data: postData,
			success: function (request)
			{
				$('.modal_' + parseInt(uid) + '_' + parseInt(pid)).fadeOut('slow', function()
				{
					if(parseInt(pid))
					{
						$('.ougcfeedback_postbit_' + parseInt(uid)).html(request.content);
					}
					else
					{
						$('#ougcfeedback_profile').html(request.content);
					}
					//$('.ougcfeedback_postbit_' + parseInt(uid)).replaceWith(request.content);
					$('.modal_' + parseInt(uid) + '_' + parseInt(pid)).html(request.modal);
					$('.modal_' + parseInt(uid) + '_' + parseInt(pid)).fadeIn('slow');
					$('.modal').fadeIn('slow');
				});
			},
			error: function (xhr)
			{
				alert(xhr.responseText);
			}
		});

		return false;
	},
}

