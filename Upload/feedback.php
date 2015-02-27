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

$PL or require_once PLUGINLIBRARY;

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

	if(!$mybb->user['uid'])
	{
		$feedback->set_error($lang->error_nopermission_user_ajax);
	}

	if(!$feedback->permission('cangive'))
	{
		$feedback->set_error($lang->error_nopermission_user_ajax);
	}

	if(!($user = get_user($feedback_data['uid'])))
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_user);
	}

	if(!$feedback->permission('canreceive', $user))
	{
		$feedback->set_error($lang->error_nopermission_user_ajax);
	}

	if($user['uid'] == $mybb->user['uid'])
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_self_user);
	}

	if($feedback_data['pid'])
	{
		if(!($post = get_post($feedback_data['pid'])))
		{
			$feedback->set_error($lang->ougc_feedback_error_invalid_post);
		}
		else
		{
			if($post['uid'] != $feedback_data['uid'])
			{
				$feedback->set_error($lang->ougc_feedback_error_invalid_post);
			}

			if(($post['visible'] == 0 && !is_moderator($post['fid'], 'canviewunapprove')) || ($post['visible'] == -1 && !is_moderator($post['fid'], 'canviewdeleted')))
			{
				$feedback->set_error($lang->ougc_feedback_error_invalid_post);
			}

			$thread = get_thread($post['tid']);

			if(!($thread = get_thread($post['tid'])) || !($forum = get_forum($post['fid'])))
			{
				$feedback->set_error($lang->ougc_feedback_error_invalid_post);
			}
			else
			{
				if(substr($thread['closed'], 0, 6) == 'moved|' || $forum['type'] != 'f')
				{
					$feedback->set_error($lang->ougc_feedback_error_invalid_post);
				}

				if($mybb->settings['ougc_feedback_allow_thread_firstpost'] && $thread['firstpost'] != $post['pid'])
				{
					$feedback->set_error($lang->ougc_feedback_error_invalid_post);
				}

				if(($thread['visible'] != 1 && !is_moderator($post['fid'])) || ($thread['visible'] == 0 && !is_moderator($post['fid'], 'canviewunapprove')) || ($thread['visible'] == -1 && !is_moderator($post['fid'], 'canviewdeleted')))
				{
					$feedback->set_error($lang->ougc_feedback_error_invalid_post);
				}

				$forumpermissions = forum_permissions($post['fid']);

				// Does the user have permission to view this thread?
				if(!$forumpermissions['canview'] || !$forumpermissions['canviewthreads'])
				{
					$feedback->set_error($lang->error_nopermission_user_ajax);
				}

				if(isset($forumpermissions['canonlyviewownthreads']) && $forumpermissions['canonlyviewownthreads'] && $thread['uid'] != $mybb->user['uid'])
				{
					$feedback->set_error($lang->error_nopermission_user_ajax);
				}

				check_forum_password($forum['fid']); // this should at least stop the script
			}
		}
	}
	else
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

	if(!in_array($feedback_data['type'], array(1, 2, 3)))
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_type);
	}

	if(!in_array($feedback_data['feedback'], array(-1, 0, 1)))
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_feedback);
	}

	if(!in_array($feedback_data['status'], array(-1, 0, 1)))
	{
		$feedback->set_error($lang->ougc_feedback_error_invalid_status);
	}

	// Set handler data
	$feedback->set_data($feedback_data);

	// POST request
	if($mybb->request_method == 'post')
	{
		// Verify incoming POST request
		verify_post_check($mybb->get_input('my_post_key'));

		if(!$mybb->settings['ougc_feedback_allow_comments'])
		{
			$feedback_data['comment'] = '';
		}
		elseif(my_strlen($feedback_data['comment']) < $mybb->settings['ougc_feedback_comments_minlength'] || my_strlen($feedback_data['comment']) > $mybb->settings['ougc_feedback_comments_maxlength'])
		{
			$feedback->set_error($lang->ougc_feedback_error_invalid_comment);
		}

		header('Content-type: application/json; charset='.$lang->settings['charset']);

		// Validate, throw error if not valid
		if($feedback->validate_feedback())
		{
			// Insert feedback
			$insert_data = $feedback->insert_feedback();

			$feedback->send_pm(array(
				'subject'		=> $lang->sprintf($lang->ougc_feedback_notification_pm_subject, $user['username'], $mybb->settings['bbname']),
				'message'		=> $lang->ougc_feedback_notification_pm_message,
				'touid'			=> $feedback_data['uid']
			), -1, true);

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
				$memprofile = &$user;
				$feedback->hook_member_profile_end();
				$content = $ougc_feedback;
			}

			$data = array('content' => $content, 'modal' => $modal);
		}
		else
		{
			$data = array('modal' => $feedback->throw_error($lang->ougc_feedback_profile_add, false));
		}

		echo json_encode($data);
		exit;
	}

	// Validate, throw error if not valid
	if(!$feedback->validate_feedback())
	{
		$feedback->throw_error();
	}

	if($mybb->settings['ougc_feedback_allow_comments'])
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

require_once MYBB_ROOT.'inc/class_parser.php';
$parser = new postParser;

if(!$mybb->usergroup['canviewprofiles'] || !$feedback->permission('canview'))
{
	error_no_permission();
}

$uid = $mybb->get_input('uid', 1);

if(!($user = get_user($uid)))
{
	error($lang->ougc_feedback_error_invalid_user);
}

$lang->ougc_feedback_page_profile = $lang->sprintf($lang->ougc_feedback_page_profile, $user['username']);
$lang->ougc_feedback_page_report_for = $lang->sprintf($lang->ougc_feedback_page_report_for, $user['username']);

add_breadcrumb($lang->ougc_feedback_page_profile, get_profile_link($user['uid']));
add_breadcrumb($lang->ougc_feedback_page_title);

$username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);

$user['displaygroup'] = $user['displaygroup'] ? $user['displaygroup'] : $user['usergroup'];

// Get user title
$usertitle = '';
if(trim($user['usertitle']))
{
	$usertitle = $user['usertitle'];
}
elseif(trim($display_group['usertitle']))
{
	$usertitle = $display_group['usertitle'];
}
else
{
	$usertitles = $cache->read('usertitles');
	foreach($usertitles as $title)
	{
		if($title['posts'] <= $user['postnum'])
		{
			$usertitle = $title['title'];
		}
	}
	unset($usertitles, $title);
}

$usertitle = htmlspecialchars_uni($usertitle);

// Start building a where clause
$where = array("f.uid='{$user['uid']}'");
if(!$feedback->permission('ismod'))
{
		$where[] = "f.status='1'";
}

// Start building the url params
$url_params = array('uid' => $user['uid']);


// Build the show filter selected array
$show_selected = array('all' => '', 'positive' => '', 'neutral' => '', 'negative' => '');
switch($mybb->get_input('show'))
{
	case 'positive':
		$url_params['show'] = 'positive';
		$where[] = "f.feedback='1'";
		$show_selected['positive'] = ' selected="selected"';
		break;
	case 'neutral':
		$url_params['show'] = 'neutral';
		$where[] = "f.feedback='0'";
		$show_selected['neutral'] = ' selected="selected"';
		break;
	case 'negative':
		$url_params['show'] = 'negative';
		$where[] = "f.feedback='-1'";
		$show_selected['negative'] = ' selected="selected"';
		break;
	case 'gived':
		$url_params['show'] = 'negative';
		$where[] = "f.fuid='{$user['uid']}'";
		unset($where[0]);
		$show_selected['gived'] = ' selected="selected"';
		break;
	default:
		$url_params['show'] = 'all';
		$show_select['all'] = ' selected="selected"';
		break;
}

// Build the sort filter selected array
$sort_selected = array('username' => '', 'last_ipdated' => '');
switch($mybb->get_input('sort'))
{
	case 'username':
		$url_params['sort'] = 'username';
		$order = 'u.username ASC, f.dateline DESC';
		$sort_selected['username'] = ' selected="selected"';
		break;
	default:
		$url_params['sort'] = 'dateline';
		$order = 'f.dateline DESC, u.username ASC';
		$sort_selected['last_updated'] = ' selected="selected"';
		break;
}

$stats = array();

// Get the total amount of feedback
$query = $db->simple_select('ougc_feedback f', 'COUNT(f.fid) AS total_feedback', implode(' AND ', $where));
$stats['total'] = $db->fetch_field($query, 'total_feedback');

// Get the total amount of feedback from posts
$query = $db->simple_select('ougc_feedback f', 'COUNT(f.fid) AS total_posts_feedback', implode(' AND ', array_merge($where, array("f.pid>'0'"))));
$stats['posts'] = $db->fetch_field($query, 'total_posts_feedback');

// Get the total amount of feedback from users
$stats['members'] = ($stats['total'] - $stats['posts']);

$stats = array_map('my_number_format', $stats);

// Set default count variables to 0
$positive_count = $negative_count = $neutral_count = 0;
$positive_week = $negative_week = $neutral_week = 0;
$positive_month = $negative_month = $neutral_month = 0;
$positive_6months = $negative_6months = $neutral_6months = 0;

// Unix timestamps for when this week, month and last 6 months started
$last_week = TIME_NOW-604800;
$last_month = TIME_NOW-2678400;
$last_6months = TIME_NOW-16070400;

// Query reputations for the "reputation card"
$query = $db->simple_select('ougc_feedback f', 'f.feedback, f.dateline', implode(' AND ', $where));
while($feedback_vote = $db->fetch_array($query))
{
	switch($feedback_vote['feedback'])
	{
		case -1:
			$negative_count++;
			if($feedback_vote['dateline'] >= $last_week)
			{
				$negative_week++;
			}
			if($feedback_vote['dateline'] >= $last_month)
			{
				$negative_month++;
			}
			if($feedback_vote['dateline'] >= $last_6months)
			{
				$negative_6months++;
			}
			break;
		case 0:
			$neutral_count++;
			if($feedback_vote['dateline'] >= $last_week)
			{
				$neutral_week++;
			}
			if($feedback_vote['dateline'] >= $last_month)
			{
				$neutral_month++;
			}
			if($feedback_vote['dateline'] >= $last_6months)
			{
				$neutral_6months++;
			}
			break;
		case 1:
			$positive_count++;
			if($feedback_vote['dateline'] >= $last_week)
			{
				$positive_week++;
			}
			if($feedback_vote['dateline'] >= $last_month)
			{
				$positive_month++;
			}
			if($feedback_vote['dateline'] >= $last_6months)
			{
				$positive_6months++;
			}
			break;
	}
}

// Build multipage
$query = $db->simple_select('ougc_feedback f', 'COUNT(f.fid) AS feedback_count', implode(' AND ', $where));
$feedback_count = $db->fetch_field($query, 'feedback_count');

$perpage = (int)$mybb->settings['ougc_feedback_perpage'];
if($mybb->get_input('page', 1) > 0)
{
	$page = $mybb->get_input('page', 1);
	$start = ($page-1)*$perpage;
	$pages = $feedback_count/$perpage;
	$pages = ceil($pages);
	if($page > $pages)
	{
		$start = 0;
		$page = 1;
	}
}
else
{
	$start = 0;
	$page = 1;
}

$multipage = $feedback_count ? (string)multipage($feedback_count, $perpage, $page, $PL->url_append('feedback.php', $url_params)) : '';

// Fetch the reputations which will be displayed on this page
$query = $db->query("
	SELECT f.*, u.username AS user_username, u.reputation AS user_reputation, u.usergroup AS user_usergroup, u.displaygroup AS user_displaygroup
	FROM ".TABLE_PREFIX."ougc_feedback f
	LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=f.fuid)
	WHERE ".implode(' AND ', $where)."
	ORDER BY {$order}
	LIMIT {$start}, {$perpage}
");

// Gather a list of items that have post reputation
$feedback_cache = $post_cache = $post_feedback = array();

while($feedback_vote = $db->fetch_array($query))
{
	$feedback_cache[] = $feedback_vote;

	// If this is a post, hold it and gather some information about it
	if($feedback_vote['pid'] && !isset($post_cache[$feedback_vote['pid']]))
	{
		$post_cache[$feedback_vote['pid']] = $feedback_vote['pid'];
	}
}

if(!empty($post_cache))
{
	$pids = implode(',', $post_cache);

	$where_post = array("p.pid IN ({$pids})");

	if($unviewable = get_unviewable_forums(true))
	{
		$where_post[] = "p.fid NOT IN ({$unviewable})";
	}

	if($inactive = get_inactive_forums())
	{
		$where_post[] = "p.fid NOT IN ({$inactive})";
	}

	if(!$mybb->user['ismoderator'])
	{
		$where_post[] = "p.visible='1'";
		$where_post[] = "t.visible='1'";
	}

	$query = $db->query("
		SELECT p.pid, p.uid, p.fid, p.visible, p.message, t.tid, t.subject, t.visible AS thread_visible
		FROM ".TABLE_PREFIX."posts p
		LEFT JOIN ".TABLE_PREFIX."threads t ON (t.tid=p.tid)
		WHERE ".implode(' AND ', $where_post)."
	");

	while($post = $db->fetch_array($query))
	{
		if(($post['visible'] == 0 || $post['thread_visible'] == 0) && !is_moderator($post['fid'], 'canviewunapprove'))
		{
			continue;
		}

		if(($post['visible'] == -1 || $post['thread_visible'] == -1) && !is_moderator($post['fid'], 'canviewdeleted'))
		{
			continue;
		}

		$post_reputation[$post['pid']] = $post;
	}
}

$feedback_list = '';
foreach($feedback_cache as $feedback_vote)
{
	$postfeed_given = $lang->sprintf($lang->ougc_feedback_page_post_nolink, $user['username']);
	if($feedback_vote['pid'])
	{
		$post = $post_reputation[$feedback_vote['pid']];

		$thread_link = get_thread_link($post['tid']);
		$subject = htmlspecialchars_uni($parser->parse_badwords($post['subject']));

		$thread_link = $lang->sprintf($lang->ougc_feedback_page_post_given_thread, $thread_link, $subject);
		$link = get_post_link($feedback_vote['pid']).'#pid'.$feedback_vote['pid'];

		$postfeed_given = $lang->sprintf($lang->ougc_feedback_page_post_given, $link, $user['username'], $thread_link);
	}

	switch($feedback_vote['feedback'])
	{
		case -1:
			$class = array('status' => 'trow_reputation_negative', 'type' => 'reputation_negative');
			$vote_type = $lang->ougc_feedback_profile_negative;
			break;
		case 0:
			$class = array('status' => 'trow_reputation_neutral', 'type' => 'reputation_neutral');
			$vote_type = $lang->ougc_feedback_profile_neutral;
			break;
		case 1:
			$class = array('status' => 'trow_reputation_positive', 'type' => 'reputation_positive');
			$vote_type = $lang->ougc_feedback_profile_positibve;
			break;
	}

	if($feedback_vote['status'] != 1)
	{
		$class = array('status' => '" style="background-color: #E8DEFF;');
	}

	$last_updated_date = my_date('relative', $feedback_vote['dateline']);
	$last_updated = $lang->sprintf($lang->ougc_feedback_page_last_updated, $last_updated_date);

	if(!$feedback_vote['user_username'])
	{
		$feedback_vote['user_username'] = $lang->na;
	}
	else
	{
		$feedback_vote['user_username'] = format_name(htmlspecialchars_uni($feedback_vote['user_username']), $feedback_vote['user_usergroup'], $feedback_vote['user_displaygroup']);
		$feedback_vote['user_username'] = build_profile_link($feedback_vote['user_username'], $feedback_vote['fuid']);
	}

	if($feedback_vote['comment'])
	{
		$parser_options = array(
			'allow_html'		=> 0,
			'allow_mycode'		=> 0,
			'allow_smilies'		=> 1,
			'allow_imgcode'		=> 0,
			'filter_badwords'	=> 1,
		);

		$feedback_vote['comment'] = $parser->parse_message($feedback_vote['comment'], $parser_options);
	}
	else
	{
		$feedback_vote['comment'] = $lang->ougc_feedback_no_comment;
	}

	$delete_link = '';
	if($feedback_vote['fuid'] == $mybb->user['uid'])
	{
		eval('$delete_link .= "'.$templates->get('ougcfeedback_page_item_delete').'";');
	}

	$report_link = '';
	if($mybb->user['uid'] && $mybb->settings['ougc_feedback_enable_report_center'])
	{
		eval('$report_link .= "'.$templates->get('ougcfeedback_page_item_report').'";');
	}

	eval('$feedback_list .= "'.$templates->get('ougcfeedback_page_item').'";');
}

if(!$feedback_list)
{
	eval('$feedback_list = "'.$templates->get('ougcfeedback_page_empty').'";');
}

$add_feedback = '';
if($feedback->permission('cangive') && $feedback->permission('canreceive', $user) && $mybb->user['uid'] != $user['uid'])
{
	eval('$add_feedback = "'.$templates->get('ougcfeedback_page_addlink').'";');
}

eval('$page = "'.$templates->get('ougcfeedback_page').'";');
output_page($page);