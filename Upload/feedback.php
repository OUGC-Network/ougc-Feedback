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
	if(!$feedback->permission('can_add'))
	{
		error_no_permission();
	}

	$feedback_data = array(
		'uid'		=> $mybb->get_input('uid', 1),
		'fuid'		=> $mybb->user['uid'],
		'pid'		=> 0,
		'type'		=> $mybb->get_input('type', 1),
		'feedback'	=> $mybb->get_input('feedback', 1),
		'comment'	=> '',
		'status'	=> $feedback->default_status()
	);

	// We assume this is a post feedback
	if($mybb->get_input('pid', 1))
	{
		$feedback_data['pid'] = $mybb->get_input('pid', 1);
	}
	// We assume this is an profile feedback
	else
	{
		if(!$mybb->settings['ougc_feedback_allow_profile'])
		{
			error($lang->ougc_feedback_error_profile_disabled);
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
				error($lang->ougc_feedback_error_profile_multiple_disabled);
			}
		}
	}

	if($mybb->get_input('comment'))
	{
		$feedback_data['comment'] = $mybb->get_input('comment');
	}

	$feedback->set_data($feedback_data);

	if(!$feedback->validate_feedback())
	{
		$feedback->trow_error();
	}

	$insert_data = $feedback->insert_feedback();

	$memprofile = get_user($mybb->get_input('uid', 1));
	$feedback->hook_member_profile_end();

	header("Content-type: application/json; charset={$lang->settings['charset']}");
	echo json_encode(array(
		'success'			=> 1,
		'pid'				=> $mybb->get_input('pid', 1),
		'content'			=> str_replace('<br />', '', $ougc_feedback),
	));

	exit;
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