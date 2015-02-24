<?php

/***************************************************************************
 *
 *	OUGC Feedback plugin (/feedback.php)
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

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'feedback.php');

$templatelist = '';

require_once './global.php';

$feedback->load_language();

if($mybb->get_input('action') == 'add')
{
	$feedback_data = array(
		'uid'		=> $mybb->get_input('uid', 1),
		'fuid'		=> $mybb->user['uid'],
		'pid'		=> $mybb->get_input('pid', 1),
		'type'		=> $mybb->get_input('type', 1),
		'feedback'	=> $mybb->get_input('feedback', 1),
		'comment'	=> $mybb->get_input('comment'),
		'status'	=> $feedback->default_status()
	);

	// This user doesn't have permission to give feedback
	if(!$feedback->permission('can_add'))
	{
		$feedback->set_error($lang->error_nopermission_user_ajax);
	}

	if(!($to_user = get_user($feedback_data['uid'])))
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_user);
	}

	if(!$mybb->user['uid'])
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_user);
	}

	if($to_user['uid'] == $mybb->user['uid'])
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_self_user);
	}

	if($feedback_data['pid'] > 0 && !($post = get_post($feedback_data['pid'])))
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_post);
	}

	if(!in_array($feedback_data['type'], array(1, 2, 3)))
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_type);
	}

	if(!in_array($feedback_data['feedback'], array(-1, 0, 1)))
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_feedback);
	}

	if(!in_array($feedback->default_status(), array(-1, 1)))
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_status);
	}

	// We assume this is a profile feedback
	if(!$feedback_data['pid'])
	{
		if(!$mybb->settings['ougc_feedback_allow_profile'])
		{
			$feedback->set_error($lang->ougc_feedback_error_profile_disabled);
		}
		elseif($mybb->settings['ougc_feedback_allow_profile'] && !$mybb->settings['ougc_feedback_allow_profile_multiple'])
		{
			$where = array("uid='{$feedback_data['uid']}'", "fuid!='0'");

			if(!$feedback->permission('ismod'))
			{
				$where[] = "status='1'";
			}

			$where[] = "fuid='{$feedback_data['fuid']}'";

			$query = $db->simple_select('ougc_feedback', 'fid', implode(' AND ', $where));

			if($db->fetch_field($query, 'fid'))
			{
				$feedback->set_error($lang->ougc_feedback_error_profile_multiple_disabled);
			}
		}
	}

	// Set handler data
	$feedback->set_data($feedback_data);

	// POST request
	if($mybb->request_method == 'post')
	{
		// Verify incoming POST request
		verify_post_check($mybb->get_input('my_post_key'));

		if(!$feedback_data['comment'])
		{
			$feedback->set_error($lang->ougc_feedback_error_invalid_comment);
		}

		// Insert feedback
		$insert_data = $feedback->insert_feedback();

		// Throw success message
		$feedback->set_error($lang->ougc_feedback_success_feedback_added);
		$modal = $feedback->throw_error($lang->ougc_feedback_profile_add, false);

		if($feedback_data['pid'])
		{
			$feedback->hook_postbit($post);
			$content = $post['ougc_feedback'];
		}
		else
		{
			$memprofile = &$to_user;
			$feedback->hook_member_profile_end();
			$content = $ougc_feedback;
		}

		header('Content-type: application/json; charset='.$lang->settings['charset']);
		echo json_encode(array('content' => $content, 'modal' => $modal));
		exit;
	}

	// Validate, throw error if not valid
	if(!$feedback->validate_feedback())
	{
		$feedback->throw_error();
	}

	if($feedback->permission('can_comment'))
	{
		$mybb->input['comment'] = $mybb->get_input('comment');
		eval('$comment_row = "'.$templates->get('ougcfeedback_form_comment', 1, 0).'";');
	}

	eval('$form = "'.$templates->get('ougcfeedback_form', 1, 0).'";');

	exit($form);
}
elseif($mybb->get_input('action') == 'edit')
{
	
}
elseif($mybb->get_input('action') == 'delete')
{
	
}
elseif($mybb->get_input('action') == 'remove')
{
	
}
elseif($mybb->get_input('action') == 'report')
{
	
}

// general listing should allow filtering by type|feeeback|from_user|feedback_gived
exit(json_encode(array('errors' => 'duh!')));
exit;