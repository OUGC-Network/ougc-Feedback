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
	Add: function()
	{
		var postData = $('#ougcfeedback_add_form').serialize();

		$.ajax(
		{
			type: 'post',
			dataType: 'json',
			url: 'feedback.php',
			data: postData,
			success: function (request)
			{
				if(request.errors)
				{
					alert(request.errors);
					return false;
				}

				if(request.success == 1)
				{
					$('#ougcfeedback_profile').replaceWith(request.content);

					return true;
				}
			},
			error: function (xhr)
			{
				alert(xhr.responseText);
				return false;
			}
		});

		$.modal.close();

		$('#ougcfeedback_add_form').preventDefault();
		$('#ougcfeedback_add_form').unbind();
	},
	Modal: function()
	{
		var _zIndex = 9999;
		if(typeof modal_zindex !== 'undefined')
		{
			_zIndex = modal_zindex;
		}

		$('#feedback_add').modal({
			keepelement: true,
			zIndex: _zIndex
		});
	}
}

