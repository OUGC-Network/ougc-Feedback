<?php

/***************************************************************************
 *
 *    OUGC Feedback plugin (/feedback.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012-2019 Omar Gonzalez
 *
 *    Website: https://omarg.me
 *
 *    Adds a powerful feedback system to your forum.
 *
 ***************************************************************************
 ****************************************************************************
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 ****************************************************************************/

define('IN_MYBB', 1);
define('THIS_SCRIPT', 'feedback.php');

$templatelist = 'ougcfeedback_page_item_edit, ougcfeedback_page_item_delete, ougcfeedback_page_item_delete_hard, ougcfeedback_page_item_report, ougcfeedback_page_item, ougcfeedback_page_addlink, ougcfeedback_page';

require_once './global.php';

$mybb->input['fid'] = $mybb->get_input('fid', MyBB::INPUT_INT);

$PL or require_once PLUGINLIBRARY;

$ougcFeedback->load_language();

$mybb->input['reload'] = $mybb->get_input('reload', 1);

$mybb->input['action'] = $mybb->get_input('action', MyBB::INPUT_STRING);

if ($mybb->get_input('action') == 'add' || $mybb->get_input('action') == 'edit') {
    $edit = $mybb->get_input('action') == 'edit';

    if ($edit) {
        if (!($feedback = $ougcFeedback->fetch_feedback($mybb->input['fid']))) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_feedback_value);
        }

        $method = "Feedback_DoEdit('{$feedback['uid']}', '{$feedback['pid']}', '{$feedback['fid']}')";

        $mybb->input['reload'] = 1;

        // users can add but they can't edit.
        if (!$mybb->user['uid']) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->error_nopermission_user_ajax);
        }
    } else {
        $feedback = array(
            'uid' => $mybb->get_input('uid', MyBB::INPUT_INT),
            'fuid' => $mybb->user['uid'],
            'pid' => $mybb->get_input('pid', MyBB::INPUT_INT),
            'status' => $ougcFeedback->default_status()
        );

        $method = "Feedback_DoAdd('{$feedback['uid']}', '{$feedback['pid']}')";
    }

    if (!$edit || $mybb->request_method == 'post' || $mybb->get_input('backbutton', MyBB::INPUT_INT)) {
        $feedback['type'] = $mybb->get_input('type', MyBB::INPUT_INT);
        $feedback['feedback'] = $mybb->get_input('feedback', MyBB::INPUT_INT);
        $feedback['comment'] = $mybb->get_input('comment', MyBB::INPUT_STRING);
    }

    // Set handler data
    $ougcFeedback->set_data($feedback);

    $type_slected = array(
        'buyer' => $feedback['type'] == 1 ? ' selected="selected"' : '',
        'seller' => $feedback['type'] == 2 ? ' selected="selected"' : '',
        'trader' => $feedback['type'] == 3 ? ' selected="selected"' : ''
    );

    $feedback_slected = array(
        'positibve' => $feedback['feedback'] == 1 ? ' selected="selected"' : '',
        'neutral' => $feedback['feedback'] == 0 ? ' selected="selected"' : '',
        'negative' => $feedback['feedback'] == -1 ? ' selected="selected"' : ''
    );

    if (!($user = get_user($feedback['uid']))) {
        $ougcFeedback->set_go_back_button(false);
        $ougcFeedback->error($lang->ougc_feedback_error_invalid_user);
    }

    $user_perms = usergroup_permissions($user['usergroup'] . ',' . $user['additionalgroups']);

    if ($edit) {
        if (!($mybb->usergroup['ougc_feedback_canedit'] && $mybb->user['uid'] == $feedback['fuid']) && !($mybb->usergroup['ougc_feedback_ismod'] && $mybb->usergroup['ougc_feedback_mod_canedit'])) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->error_nopermission_user_ajax);
        }
    } else {
        if (!$mybb->usergroup['ougc_feedback_cangive'] || !$user_perms['ougc_feedback_canreceive']) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->error_nopermission_user_ajax);
        }

        if ($user['uid'] == $mybb->user['uid']) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_self_user);
        }

        if (!in_array($feedback['type'], array(1, 2, 3))) {
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_type);
        }

        if (!in_array($feedback['feedback'], array(-1, 0, 1))) {
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_feedback_value);
        }

        if (!in_array($feedback['status'], array(-1, 0, 1))) {
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_status);
        }

        if ($mybb->usergroup['ougc_feedback_maxperday']) {
            $timesearch = TIME_NOW - (60 * 60 * 24);
            $query = $db->simple_select(
                'ougc_feedback',
                'COUNT(fid) AS feedbacks',
                "fuid='{$mybb->user['uid']}' AND dateline>'{$timesearch}'"
            );
            $numtoday = $db->fetch_field($query, 'feedbacks');

            if ($numtoday >= $mybb->usergroup['ougc_feedback_maxperday']) {
                $ougcFeedback->set_go_back_button(false);
                $ougcFeedback->error($lang->ougc_feedback_error_invalid_maxperday);
            }
        }
    }

    $hide_add = 0;

    if (!$edit && $feedback['pid']) {
        if (!($post = get_post($feedback['pid']))) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_post);
        }

        if ($post['uid'] != $feedback['uid']) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_post);
        }

        if (($post['visible'] == 0 && !is_moderator(
                    $post['fid'],
                    'canviewunapprove'
                )) || ($post['visible'] == -1 && !is_moderator($post['fid'], 'canviewdeleted'))) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_post);
        }

        if (!($thread = get_thread($post['tid'])) || !($forum = get_forum($post['fid']))) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_post);
        }

        if (!$forum['ougc_feedback_allow_threads'] && !$forum['ougc_feedback_allow_posts']) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_post);
        }

        if ($forum['ougc_feedback_allow_threads'] && !$forum['ougc_feedback_allow_posts'] && $thread['firstpost'] != $post['pid']) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_post);
        }

        if (!$forum['ougc_feedback_allow_threads'] && $forum['ougc_feedback_allow_posts'] && $thread['firstpost'] == $post['pid']) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_post);
        }

        if (substr($thread['closed'], 0, 6) == 'moved|' || $forum['type'] != 'f') {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_post);
        }

        if (($thread['visible'] != 1 && !is_moderator($post['fid'])) || ($thread['visible'] == 0 && !is_moderator(
                    $post['fid'],
                    'canviewunapprove'
                )) || ($thread['visible'] == -1 && !is_moderator($post['fid'], 'canviewdeleted'))) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_invalid_post);
        }

        $forumpermissions = forum_permissions($post['fid']);

        // Does the user have permission to view this thread?
        if (!$forumpermissions['canview'] || !$forumpermissions['canviewthreads']) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->error_nopermission_user_ajax);
        }

        if (isset($forumpermissions['canonlyviewownthreads']) && $forumpermissions['canonlyviewownthreads']) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->error_nopermission_user_ajax);
        }

        check_forum_password($post['fid']); // this should at least stop the script

        $where = array(
            "uid='{$feedback['uid']}'", /*"fuid!='0'", */
            "fuid='{$feedback['fuid']}'",
            "pid='{$feedback['pid']}'",
            "status='1'"
        );

        $query = $db->simple_select('ougc_feedback', 'fid', implode(' AND ', $where));

        if ($db->fetch_field($query, 'fid')) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_post_multiple_disabled);
        }

        if ($mybb->settings['ougc_feedback_postbit_hide_button']) {
            $hide_add = 1;
        }
    } elseif (!$edit) {
        if (!$mybb->settings['ougc_feedback_allow_profile']) {
            $ougcFeedback->set_go_back_button(false);
            $ougcFeedback->error($lang->ougc_feedback_error_profile_disabled);
        }

        if (!$mybb->settings['ougc_feedback_allow_profile_multiple']) {
            $where = array(
                "uid='{$feedback['uid']}'", /*"fuid!='0'", */
                "fuid='{$feedback['fuid']}'"
            );

            if (!$mybb->usergroup['ougc_feedback_ismod']) {
                $where[] = "status='1'";
            }

            $query = $db->simple_select('ougc_feedback', 'fid', implode(' AND ', $where));

            if ($db->fetch_field($query, 'fid')) {
                $ougcFeedback->set_go_back_button(false);
                $ougcFeedback->error($lang->ougc_feedback_error_profile_multiple_disabled);
            }

            $hide_add = (int)$mybb->settings['ougc_feedback_profile_hide_add'];
        }
    }

    $comments_minlength = (int)$mybb->settings['ougc_feedback_comments_minlength'];
    $comments_maxlength = (int)$mybb->settings['ougc_feedback_comments_maxlength'];

    // POST request
    if ($mybb->request_method == 'post') {
        // Verify incoming POST request
        verify_post_check($mybb->get_input('my_post_key'));

        $message_count = my_strlen($feedback['comment']);

        if ($comments_maxlength < 1) {
            $feedback['comment'] = '';
        } elseif ($message_count < $comments_minlength || $message_count > $comments_maxlength) {
            $ougcFeedback->set_go_back_button(true);
            $ougcFeedback->error(
                $lang->sprintf(
                    $lang->ougc_feedback_error_invalid_comment,
                    $comments_minlength,
                    $comments_maxlength,
                    $message_count
                )
            );
        }

        // Validate, throw error if not valid
        if ($ougcFeedback->validate_feedback()) {
            if ($edit) {
                // Insert feedback
                $ougcFeedback->update_feedback();

                $ougcFeedback->sync_user($feedback['uid']);

                $ougcFeedback->success($lang->ougc_feedback_success_feedback_edited);
            } else {
                // Insert feedback
                $ougcFeedback->insert_feedback();

                $ougcFeedback->sync_user((int)$feedback['uid']);

                /*if(strpos(','.$user['ougc_feedback_notification'].',', ',1,'))
                {*/
                $ougcFeedback->send_pm(array(
                    'subject' => $lang->ougc_feedback_notification_pm_subject,
                    'message' => $lang->sprintf(
                        $lang->ougc_feedback_notification_pm_message,
                        $user['username'],
                        $mybb->settings['bbname']
                    ),
                    'touid' => $feedback['uid']
                ), -1, true);
                /*}*/

                /*if(strpos(','.$user['ougc_feedback_notification'].',', ',`2,'))
                {*/
                $ougcFeedback->send_email(array(
                    'to' => $user['email'],
                    'subject' => 'ougc_feedback_notification_mail_subject',
                    'message' => array(
                        'ougc_feedback_notification_mail_message',
                        $user['username'],
                        $mybb->settings['bbname']
                    ),
                    'from' => $mybb->settings['adminemail'],
                    'touid' => $user['uid'],
                    'language' => $user['language']
                ));
                /*}*/

                /*if(strpos(','.$user['ougc_feedback_notification'].',', ',`3,'))
                {
                    $ougcFeedback->send_alert(array());
                }*/

                if ($feedback['pid']) {
                    $ougcFeedback->hook_postbit($post);
                    $replacement = $post['ougc_feedback'];
                } else {
                    $memprofile = &$user;
                    $ougcFeedback->hook_member_profile_end();
                    $replacement = $ougc_feedback;
                }

                $ougcFeedback->success($lang->ougc_feedback_success_feedback_added, '', $replacement, $hide_add);
            }
        } else {
            $ougcFeedback->error($lang->ougc_feedback_error_unknown);
        }
    }

    if ($comments_maxlength > 0) {
        $mybb->input['comment'] = $feedback['comment'];

        $mybb->input['comment'] = htmlspecialchars_uni($mybb->input['comment']);

        eval('$comment_row = "' . $templates->get('ougcfeedback_form_comment', 1, 0) . '";');
    }

    if ($edit) {
        $lang->ougc_feedback_profile_add = $lang->ougc_feedback_profile_edit;
    }

    eval('$form = "' . $templates->get('ougcfeedback_form', 1, 0) . '";');

    exit($form);

    // Send an error to the browser
    //$ougcFeedback->send_form($form);
} elseif ($mybb->get_input('action') == 'delete') {
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

    // users can add but they can't delete.
    if (!$mybb->user['uid']) {
        error_no_permission();
    }

    if (!($feedback = $ougcFeedback->fetch_feedback($mybb->input['fid']))) {
        error($lang->ougc_feedback_error_invalid_feedback);
    }

    if (!($mybb->usergroup['ougc_feedback_canremove'] && $mybb->user['uid'] == $feedback['fuid']) && !($mybb->usergroup['ougc_feedback_ismod'] && $mybb->usergroup['ougc_feedback_mod_canremove'])) {
        error_no_permission();
    }

    if ($mybb->get_input('hard', MyBB::INPUT_INT)) {
        $ougcFeedback->delete_feedback((int)$feedback['fid']);

        $ougcFeedback->sync_user($feedback['uid']);

        $lang_string = 'ougc_feedback_redirect_removed';
    } else {
        $ougcFeedback->set_data(
            array(
                'fid' => $feedback['fid'],
                'status' => 0,
            )
        );

        $lang_string = 'ougc_feedback_redirect_removed';

        $ougcFeedback->update_feedback();
    }

    $ougcFeedback->sync_user($feedback['uid']);

    redirect($mybb->settings['bburl'] . '/feedback.php?uid=' . $feedback['uid'], $lang->{$lang_string});
} elseif ($mybb->get_input('action') == 'restore') {
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

    // users can add but they can't delete.
    if (!$mybb->user['uid']) {
        error_no_permission();
    }

    if (!($feedback = $ougcFeedback->fetch_feedback($mybb->input['fid']))) {
        error($lang->ougc_feedback_error_invalid_feedback);
    }

    if (!($mybb->usergroup['ougc_feedback_canremove'] && $mybb->user['uid'] == $feedback['fuid']) && !($mybb->usergroup['ougc_feedback_ismod'] && $mybb->usergroup['ougc_feedback_mod_canremove'])) {
        error_no_permission();
    }

    $ougcFeedback->set_data(
        array(
            'fid' => $feedback['fid'],
            'status' => 1,
        )
    );

    $ougcFeedback->update_feedback();

    $ougcFeedback->sync_user($feedback['uid']);

    redirect(
        $mybb->settings['bburl'] . '/feedback.php?uid=' . $feedback['uid'],
        $lang->ougc_feedback_redirect_restored
    );
}

require_once MYBB_ROOT . 'inc/class_parser.php';
$parser = new postParser();

if (!$mybb->usergroup['canviewprofiles'] || !$mybb->usergroup['ougc_feedback_canview']) {
    error_no_permission();
}

$uid = $mybb->get_input('uid', 1);

if (!($user = get_user($uid))) {
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
if (trim($user['usertitle'])) {
    $usertitle = $user['usertitle'];
} elseif (trim($display_group['usertitle'])) {
    $usertitle = $display_group['usertitle'];
} else {
    $usertitles = $cache->read('usertitles');
    foreach ($usertitles as $title) {
        if ($title['posts'] <= $user['postnum']) {
            $usertitle = $title['title'];
        }
    }
    unset($usertitles, $title);
}

$usertitle = htmlspecialchars_uni($usertitle);

// Start building a where clause
$where = array("f.uid='{$user['uid']}'");

if (!$mybb->usergroup['ougc_feedback_ismod']) {
    $where[] = "f.status='1'";
}

// Start building the url params
$url_params = array('uid' => $user['uid']);

// Build the show filter selected array
$show_selected = array('all' => '', 'positive' => '', 'neutral' => '', 'negative' => '');
switch ($mybb->get_input('show')) {
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

if ($mybb->input['fid']) {
    $url_params['fid'] = $mybb->input['fid'];
}

// Build the sort filter selected array
$sort_selected = array('username' => '', 'last_ipdated' => '');
switch ($mybb->get_input('sort')) {
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

$query = $db->simple_select(
    'ougc_feedback',
    'SUM(feedback) AS feedback, COUNT(fid) AS total_feedback',
    "uid='{$user['uid']}' AND status='1'"
);
$feedback = $db->fetch_array($query);

$sync_feedback = (int)$feedback['feedback'];
$total_feedback = (int)$feedback['total_feedback'];

if ($sync_feedback != $user['ougc_feedback']) {
    $ougcFeedback->sync_user((int)$user['uid']);
}

$stats = array();

$where_stats = array_merge($where, array("f.status='1'"));

// Get the total amount of feedback
$query = $db->simple_select('ougc_feedback f', 'COUNT(f.fid) AS total_feedback', implode(' AND ', $where_stats));
$stats['total'] = $db->fetch_field($query, 'total_feedback');

// Get the total amount of feedback from posts
$query = $db->simple_select(
    'ougc_feedback f',
    'COUNT(f.fid) AS total_posts_feedback',
    implode(' AND ', array_merge($where_stats, array("f.pid>'0'")))
);
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
$last_week = TIME_NOW - 604800;
$last_month = TIME_NOW - 2678400;
$last_6months = TIME_NOW - 16070400;

// Query reputations for the "reputation card"
$query = $db->simple_select('ougc_feedback f', 'f.feedback, f.dateline', implode(' AND ', $where_stats));
while ($feedback = $db->fetch_array($query)) {
    switch ($feedback['feedback']) {
        case -1:
            $negative_count++;
            if ($feedback['dateline'] >= $last_week) {
                $negative_week++;
            }
            if ($feedback['dateline'] >= $last_month) {
                $negative_month++;
            }
            if ($feedback['dateline'] >= $last_6months) {
                $negative_6months++;
            }
            break;
        case 0:
            $neutral_count++;
            if ($feedback['dateline'] >= $last_week) {
                $neutral_week++;
            }
            if ($feedback['dateline'] >= $last_month) {
                $neutral_month++;
            }
            if ($feedback['dateline'] >= $last_6months) {
                $neutral_6months++;
            }
            break;
        case 1:
            $positive_count++;
            if ($feedback['dateline'] >= $last_week) {
                $positive_week++;
            }
            if ($feedback['dateline'] >= $last_month) {
                $positive_month++;
            }
            if ($feedback['dateline'] >= $last_6months) {
                $positive_6months++;
            }
            break;
    }
}

// Build multipage
$query = $db->simple_select('ougc_feedback f', 'COUNT(f.fid) AS feedback_count', implode(' AND ', $where));
$feedback_count = $db->fetch_field($query, 'feedback_count');

$perpage = (int)$mybb->settings['ougc_feedback_perpage'];
if ($mybb->get_input('page', 1) > 0) {
    $page = $mybb->get_input('page', 1);
    $start = ($page - 1) * $perpage;
    $pages = $feedback_count / $perpage;
    $pages = ceil($pages);
    if ($page > $pages) {
        $start = 0;
        $page = 1;
    }
} else {
    $start = 0;
    $page = 1;
}

$multipage = $feedback_count ? (string)multipage(
    $feedback_count,
    $perpage,
    $page,
    $PL->url_append('feedback.php', $url_params)
) : '';

// Fetch the reputations which will be displayed on this page
$query = $db->query(
    '
	SELECT f.*, u.username AS user_username, u.reputation AS user_reputation, u.usergroup AS user_usergroup, u.displaygroup AS user_displaygroup
	FROM ' . TABLE_PREFIX . 'ougc_feedback f
	LEFT JOIN ' . TABLE_PREFIX . 'users u ON (u.uid=f.fuid)
	WHERE ' . implode(' AND ', $where) . "
	ORDER BY {$order}
	LIMIT {$start}, {$perpage}
"
);

// Gather a list of items that have post reputation
$feedback_cache = $post_cache = $post_feedback = array();

while ($feedback = $db->fetch_array($query)) {
    $feedback_cache[] = $feedback;

    // If this is a post, hold it and gather some information about it
    if ($feedback['pid'] && !isset($post_cache[$feedback['pid']])) {
        $post_cache[$feedback['pid']] = $feedback['pid'];
    }
}

if (!empty($post_cache)) {
    $pids = implode(',', $post_cache);

    $where_post = array("p.pid IN ({$pids})");

    if ($unviewable = get_unviewable_forums(true)) {
        $where_post[] = "p.fid NOT IN ({$unviewable})";
    }

    if ($inactive = get_inactive_forums()) {
        $where_post[] = "p.fid NOT IN ({$inactive})";
    }

    if (!$mybb->user['ismoderator']) {
        $where_post[] = "p.visible='1'";
        $where_post[] = "t.visible='1'";
    }

    $query = $db->query(
        '
		SELECT p.pid, p.uid, p.fid, p.visible, p.message, t.tid, t.subject, t.visible AS thread_visible
		FROM ' . TABLE_PREFIX . 'posts p
		LEFT JOIN ' . TABLE_PREFIX . 'threads t ON (t.tid=p.tid)
		WHERE ' . implode(' AND ', $where_post) . '
	'
    );

    while ($post = $db->fetch_array($query)) {
        if (($post['visible'] == 0 || $post['thread_visible'] == 0) && !is_moderator(
                $post['fid'],
                'canviewunapprove'
            )) {
            continue;
        }

        if (($post['visible'] == -1 || $post['thread_visible'] == -1) && !is_moderator(
                $post['fid'],
                'canviewdeleted'
            )) {
            continue;
        }

        $post_reputation[$post['pid']] = $post;
    }
}

$feedback_list = '';
foreach ($feedback_cache as $feedback) {
    $feedback['fid'] = (int)$feedback['fid'];

    $postfeed_given = '';
    if ($feedback['pid']) {
        $postfeed_given = $lang->sprintf($lang->ougc_feedback_page_post_nolink, $user['username']);
        if ($post = $post_reputation[$feedback['pid']]) {
            $thread_link = get_thread_link($post['tid']);
            $subject = htmlspecialchars_uni($parser->parse_badwords($post['subject']));

            $thread_link = $lang->sprintf($lang->ougc_feedback_page_post_given_thread, $thread_link, $subject);
            $link = get_post_link($feedback['pid']) . '#pid' . $feedback['pid'];

            $postfeed_given = $lang->sprintf(
                $lang->ougc_feedback_page_post_given,
                $link,
                $user['username'],
                $thread_link
            );
        }
    }

    switch ($feedback['type']) {
        case 1:
            $vote_type = $lang->ougc_feedback_page_type_buyer;
            break;
        case 2:
            $vote_type = $lang->ougc_feedback_page_type_seller;
            break;
        case 3:
            $vote_type = $lang->ougc_feedback_page_type_trader;
            break;
    }

    switch ($feedback['feedback']) {
        case -1:
            $class = array('status' => 'trow_reputation_negative', 'type' => 'reputation_negative');
            $vote_type .= $lang->ougc_feedback_profile_negative;
            break;
        case 0:
            $class = array('status' => 'trow_reputation_neutral', 'type' => 'reputation_neutral');
            $vote_type .= $lang->ougc_feedback_profile_neutral;
            break;
        case 1:
            $class = array('status' => 'trow_reputation_positive', 'type' => 'reputation_positive');
            $vote_type .= $lang->ougc_feedback_profile_positive;
            break;
    }

    if ($feedback['status'] != 1) {
        //$class = array('status' => '" style="background-color: #E8DEFF;');
    }

    if (!$feedback['status']) {
        $class['status'] = 'trow_shaded trow_deleted forumdisplay_regular';
    }

    if ($feedback['fid'] == $mybb->input['fid']) {
        $class['status'] = 'inline_row trow_selected';
    }

    $last_updated_date = my_date('relative', $feedback['dateline']);
    $last_updated = $lang->sprintf($lang->ougc_feedback_page_last_updated, $last_updated_date);

    if (!$feedback['fuid']) {
        $feedback['user_username'] = $lang->guest;
    } elseif (!$feedback['user_username']) {
        $feedback['user_username'] = $lang->na;
    } else {
        $feedback['user_username'] = format_name(
            htmlspecialchars_uni($feedback['user_username']),
            $feedback['user_usergroup'],
            $feedback['user_displaygroup']
        );
        $feedback['user_username'] = build_profile_link($feedback['user_username'], $feedback['fuid']);
    }

    if ($feedback['comment']) {
        $parser_options = array(
            'allow_html' => 0,
            'allow_mycode' => 0,
            'allow_smilies' => 1,
            'allow_imgcode' => 0,
            'filter_badwords' => 1,
        );

        $feedback['comment'] = $parser->parse_message($feedback['comment'], $parser_options);
    } else {
        $feedback['comment'] = $lang->ougc_feedback_no_comment;
    }

    $edit_link = $delete_link = $delete_hard_link = $report_link = '';
    if ($mybb->user['uid'] && (($feedback['fuid'] == $mybb->user['uid'] && $mybb->usergroup['ougc_feedback_canedit']) || ($mybb->usergroup['ougc_feedback_ismod'] && $mybb->usergroup['ougc_feedback_canedit']))) {
        eval('$edit_link = "' . $templates->get('ougcfeedback_page_item_edit') . '";');
    }

    if ($mybb->user['uid'] && (($feedback['fuid'] == $mybb->user['uid'] && $mybb->usergroup['ougc_feedback_canremove']) || ($mybb->usergroup['ougc_feedback_ismod'] && $mybb->usergroup['ougc_feedback_mod_canremove']))) {
        if (!$feedback['status']) {
            $delete_link = eval($templates->render('ougcfeedback_page_item_restore'));
        } else {
            $delete_link = eval($templates->render('ougcfeedback_page_item_delete'));
        }
    }

    if ($mybb->user['uid'] && $mybb->usergroup['ougc_feedback_ismod'] && $mybb->usergroup['ougc_feedback_mod_candelete']) {
        eval('$delete_hard_link = "' . $templates->get('ougcfeedback_page_item_delete_hard') . '";');
    }

    if ($mybb->user['uid']) {
        $report_link = eval($templates->render('ougcfeedback_page_item_report'));
    }

    eval('$feedback_list .= "' . $templates->get('ougcfeedback_page_item') . '";');
}

if (!$feedback_list) {
    eval('$feedback_list = "' . $templates->get('ougcfeedback_page_empty') . '";');
}

$add_feedback = '';

$user_perms = usergroup_permissions($user['usergroup'] . ',' . $user['additionalgroups']);

if ($mybb->settings['ougc_feedback_allow_profile'] && $mybb->usergroup['ougc_feedback_cangive'] && $user_perms['ougc_feedback_canreceive'] && $mybb->user['uid'] != $user['uid']) {
    $show = true;

    if (!$mybb->settings['ougc_feedback_allow_profile_multiple'] && $mybb->settings['ougc_feedback_profile_hide_add']) {
        $where = array(
            "uid='{$user['uid']}'", /*"fuid!='0'", */
            "fuid='{$mybb->user['uid']}'"
        );

        if (!$mybb->usergroup['ougc_feedback_ismod']) {
            $where[] = "status='1'";
        }

        $query = $db->simple_select('ougc_feedback', 'fid', implode(' AND ', $where));

        if ($db->fetch_field($query, 'fid')) {
            $show = false;
        }
    }

    if ($show) {
        eval('$add_feedback = "' . $templates->get('ougcfeedback_page_addlink') . '";');
    }
}

if ($user['ougc_feedback'] < 0) {
    $total_class = '_negative';
} elseif ($user['ougc_feedback'] > 0) {
    $total_class = '_positive';
} else {
    $total_class = '_neutral';
}

eval('$page = "' . $templates->get('ougcfeedback_page') . '";');
output_page($page);