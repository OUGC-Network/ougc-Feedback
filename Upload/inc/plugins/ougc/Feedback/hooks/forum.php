<?php

/***************************************************************************
 *
 *    OUGC Feedback plugin (/inc/plugins/ougc/Feedback/hooks/forum.php)
 *    Author: Omar Gonzalez
 *    Copyright: © 2012 Omar Gonzalez
 *
 *    Website: https://ougc.network
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

declare(strict_types=1);

namespace ougc\Feedback\Hooks\Forum;

use MyBB;

use function ougc\Feedback\Core\getTemplate;
use function ougc\Feedback\Core\loadLanguage;

function global_start(): bool
{
    global $templatelist, $settings;

    if (isset($templatelist)) {
        $templatelist .= ',';
    } else {
        $templatelist = '';
    }

    $templatelist .= 'ougcfeedback_js';

    switch (THIS_SCRIPT) {
        case 'member.php':
            $templatelist .= ',ougcfeedback_profile,ougcfeedback_profile_add,ougcfeedback_add,ougcfeedback_add_comment,ougcfeedback_profile_average, ougcfeedback_profile_view_all';
            break;
        case 'showthread.php':
        case 'newthread.php':
        case 'newreply.php':
        case 'editpost.php':
        case 'private.php':
            $templatelist .= ',ougcfeedback_postbit, ougcfeedback_postbit_average, ougcfeedback_postbit_button';
            break;
    }

    return true;
}

function global_intermediate(): bool
{
    global $ougc_feedback_js, $mybb;

    $ougc_feedback_js = eval(getTemplate('js'));

    return true;
}

function member_profile_end(): string
{
    global $db, $memprofile, $templates, $ougc_feedback, $ougc_feedback_average, $theme, $lang, $mybb;

    loadLanguage();

    $ougc_feedback = $ougc_feedback_average = '';

    if (!$mybb->settings['ougc_feedback_showin_profile']) {
        return $ougc_feedback;
    }

    $where = array(
        "uid='{$memprofile['uid']}'",
        /*"fuid!='0'", */
        "status='1'"
    );
    /*if(!$mybb->usergroup['ougc_feedback_ismod'])
    {
        $where[] = "status='1'";
    }*/

    $stats = array(
        'total' => 0,
        'positive' => 0,
        'neutral' => 0,
        'negative' => 0,
        'positive_percent' => 0,
        'neutral_percent' => 0,
        'negative_percent' => 0,
        'positive_users' => array(),
        'neutral_users' => array(),
        'negative_users' => array(),
        'average' => 0
    );

    $query = $db->simple_select('ougc_feedback', '*', implode(' AND ', $where));

    while ($feedback = $db->fetch_array($query)) {
        ++$stats['total'];

        $feedback['feedback'] = (int)$feedback['feedback'];

        switch ($feedback['feedback']) {
            case 1:
                ++$stats['positive'];

                $stats['positive_users'][$feedback['fuid']] = 1;
                break;
            case 0:
                ++$stats['neutral'];

                $stats['neutral_users'][$feedback['fuid']] = 1;
                break;
            case -1:
                ++$stats['negative'];

                $stats['negative_users'][$feedback['fuid']] = 1;
                break;
        }
    }

    if ($stats['total']) {
        $stats['positive_percent'] = floor(100 * ($stats['positive'] / $stats['total']));

        $stats['neutral_percent'] = floor(100 * ($stats['neutral'] / $stats['total']));

        $stats['negative_percent'] = floor(100 * ($stats['negative'] / $stats['total']));

        $stats['average'] = $stats['positive'] - $stats['negative'];
    }

    $class = '_neutral';

    if ($stats['average'] > 0) {
        $class = '_positive';
    } elseif ($stats['average'] < 0) {
        $class = '_negative';
    }

    $stats['average'] = my_number_format($stats['average']);

    $ougc_feedback_average = eval(getTemplate('profile_average'));

    $stats['positive_users'] = count($stats['positive_users']);

    $stats['neutral_users'] = count($stats['neutral_users']);

    $stats['negative_users'] = count($stats['negative_users']);

    $stats = array_map('my_number_format', $stats);

    $add_row = '';

    $trow = 'trow1';

    $memprofile_perms = usergroup_permissions($memprofile['usergroup'] . ',' . $memprofile['additionalgroups']);

    if ($mybb->settings['ougc_feedback_allow_profile'] && $mybb->usergroup['ougc_feedback_cangive'] && $memprofile_perms['ougc_feedback_canreceive'] && $mybb->user['uid'] != $memprofile['uid']) {
        $show = true;

        if (!$mybb->settings['ougc_feedback_allow_profile_multiple'] && $mybb->settings['ougc_feedback_profile_hide_add']) {
            $where = array(
                "uid='{$memprofile['uid']}'",
                /*"fuid!='0'", */
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
            $trow = 'trow2';

            $pid = '';

            $uid = $memprofile['uid'];

            $mybb->input['type'] = isset($mybb->input['type']) ? $mybb->get_input('type', MyBB::INPUT_INT) : 1;

            $mybb->input['feedback'] = isset($mybb->input['feedback']) ? $mybb->get_input(
                'feedback',
                MyBB::INPUT_INT
            ) : 1;

            $add_row = eval(getTemplate('profile_add'));
        }
    }

    $view_all = '';

    if ($mybb->usergroup['ougc_feedback_canview']) {
        $view_all = eval(getTemplate('profile_view_all'));
    }

    $ougc_feedback = eval(getTemplate('profile'));

    return $ougc_feedback;
}

function postbit(array &$post): array
{
    global $db, $templates, $theme, $lang, $mybb, $pids;

    loadLanguage();

    $post['ougc_feedback'] = $post['ougc_feedback_button'] = $post['ougc_feedback_average'] = '';

    $show = true;

    if (!empty($post['fid']) && (!$mybb->settings['ougc_feedback_showin_forums'] || ($mybb->settings['ougc_feedback_showin_forums'] != -1 && !in_array(
                    $post['fid'],
                    array_map('intval', explode(',', $mybb->settings['ougc_feedback_showin_forums']))
                )))) {
        $show = false;
    }

    if ($show && $mybb->settings['ougc_feedback_showin_postbit']) {
        static $query_cache;

        if (!isset($query_cache)) {
            global $plugins;

            $where = array(
                /*"fuid!='0'", */
                "status='1'"
            );

            /*if(!$mybb->usergroup['ougc_feedback_ismod'])
            {
                $where[] = "status='1'";
            }*/

            if ($plugins->current_hook == 'postbit' && $mybb->get_input(
                    'mode'
                ) != 'threaded' && !empty($pids) && THIS_SCRIPT != 'newreply.php') {
                $uids = array();

                $query = $db->simple_select(
                    'users u LEFT JOIN ' . TABLE_PREFIX . 'posts p ON (p.uid=u.uid)',
                    'u.uid',
                    "p.{$pids}"
                );

                while ($uid = $db->fetch_field($query, 'uid')) {
                    $uids[$uid] = (int)$uid;
                }

                $where[] = "uid IN ('" . implode("','", $uids) . "')";
            } else {
                $where[] = "uid='{$post['uid']}'";
            }

            $query = $db->simple_select('ougc_feedback', 'feedback,uid,fuid', implode(' AND ', $where));
            while ($feedback = $db->fetch_array($query)) {
                $uid = (int)$feedback['uid'];

                unset($feedback['uid']);

                $query_cache[$uid][] = $feedback;
            }
        }

        $stats = array(
            'total' => 0,
            'positive' => 0,
            'neutral' => 0,
            'negative' => 0,
            'positive_percent' => 0,
            'neutral_percent' => 0,
            'negative_percent' => 0,
            'positive_users' => array(),
            'neutral_users' => array(),
            'negative_users' => array()
        );

        if (!empty($query_cache[$post['uid']])) {
            foreach ($query_cache[$post['uid']] as $feedback) {
                ++$stats['total'];

                $feedback['feedback'] = (int)$feedback['feedback'];
                switch ((int)$feedback['feedback']) {
                    case 1:
                        ++$stats['positive'];
                        $stats['positive_users'][$feedback['fuid']] = 1;
                        break;
                    case 0:
                        ++$stats['neutral'];
                        $stats['neutral_users'][$feedback['fuid']] = 1;
                        break;
                    case -1:
                        ++$stats['negative'];
                        $stats['negative_users'][$feedback['fuid']] = 1;
                        break;
                }
            }
        }

        $average = 0;

        if ($stats['total']) {
            $stats['positive_percent'] = floor(100 * ($stats['positive'] / $stats['total']));

            $stats['neutral_percent'] = floor(100 * ($stats['neutral'] / $stats['total']));

            $stats['negative_percent'] = floor(100 * ($stats['negative'] / $stats['total']));

            $average = $stats['positive'] - $stats['negative'];
        }

        $stats['positive_users'] = count($stats['positive_users']);

        $stats['neutral_users'] = count($stats['neutral_users']);

        $stats['negative_users'] = count($stats['negative_users']);

        $stats = array_map('my_number_format', $stats);

        /*$view_all = '';
        if($mybb->usergroup['ougc_feedback_canview'])
        {
            $view_all = eval(getTemplate('postbit_view_all'));
        }*/

        $class = 'reputation_neutral';

        if ($average > 0) {
            $class = 'reputation_positive';
        } elseif ($average < 0) {
            $class = 'reputation_negative';
        }

        $average = my_number_format($average);

        $post['ougc_feedback_average'] = eval(getTemplate('postbit_average'));

        $post['ougc_feedback'] = eval(getTemplate('postbit'));

        $post['user_details'] = str_replace('<!--OUGC_FEEDBACK-->', $post['ougc_feedback'], $post['user_details']);
    }

    global $plugins, $thread, $forum;

    if (!$forum['ougc_feedback_allow_threads'] && !$forum['ougc_feedback_allow_posts']) {
        return $post;
    }

    if ($forum['ougc_feedback_allow_threads'] && !$forum['ougc_feedback_allow_posts'] && $thread['firstpost'] != $post['pid']) {
        return $post;
    }

    if (!$forum['ougc_feedback_allow_threads'] && $forum['ougc_feedback_allow_posts'] && $thread['firstpost'] == $post['pid']) {
        return $post;
    }

    $post_perms = usergroup_permissions($post['usergroup'] . ',' . $post['additionalgroups']);

    if ($mybb->usergroup['ougc_feedback_cangive'] && $post_perms['ougc_feedback_canreceive'] && $mybb->user['uid'] != $post['uid']) {
        static $button_query_cache;

        if (!isset($button_query_cache) && $mybb->settings['ougc_feedback_postbit_hide_button']) {
            global $plugins;

            $where = array("f.fuid='{$mybb->user['uid']}'");

            if (!$mybb->usergroup['ougc_feedback_ismod']) {
                $where[] = "f.status='1'";
            }

            if ($plugins->current_hook == 'postbit' && $mybb->get_input(
                    'mode'
                ) != 'threaded' && !empty($pids) && THIS_SCRIPT != 'newreply.php') {
                $where[] = "p.{$pids}";

                $join = ' LEFT JOIN ' . TABLE_PREFIX . 'posts p ON (p.pid=f.pid)';
            } else {
                $where[] = "f.pid='{$post['pid']}'";

                $join = '';
            }

            $query = $db->simple_select('ougc_feedback f' . $join, 'f.pid', implode(' AND ', $where));

            while ($pid = $db->fetch_field($query, 'pid')) {
                $query_cache[$pid][] = $pid;
            }
        }

        if (!isset($button_query_cache[$post['pid']])) {
            $post['ougc_feedback_button'] = eval(getTemplate('postbit_button'));
        }
    }
    #$plugins->remove_hook('postbit', array($this, 'hook_postbit'));

    return $post;
}

function postbit_prev(array &$post): array
{
    postbit($post);

    return $post;
}

function postbit_pm(array &$post): array
{
    postbit($post);

    return $post;
}

function postbit_announcement(array &$post): array
{
    postbit($post);

    return $post;
}

/*
function memberlist_end(): bool
{
    global $mybb;

    if (!$mybb->settings['ougc_feedback_showin_memberlist']) {
        return false;
    }

    global $templates, $ougc_feedback_header, $ougc_feedback_sort, $sorturl, $lang, $colspan, $sort_selected;

    loadLanguage();

    ++$colspan;

    $ougc_feedback_header = eval(getTemplate('memberlist_header'));

    $ougc_feedback_sort = eval(getTemplate('memberlist_sort'));

    return true;
}

function memberlist_intermediate(): bool
{
    return true;
}

function memberlist_user(array &$user): array
{
    global $mybb;

    if (!$mybb->settings['ougc_feedback_showin_memberlist']) {
        return $user;
    }

    global $templates, $ougc_feedback_bit, $alt_bg;

    loadLanguage();

    static $done = false;

    if (!$done) {
        global $alttrow;

        $done = true;

        if ($alttrow == 'trow1') {
            $alt_bg = 'trow2';
        } else {
            $alt_bg = 'trow1';
        }
    }

    $ougc_feedback_bit = eval(getTemplate('memberlist_user'));

    return $user;
}
*/

function report_start(): bool
{
    global $mybb;

    if ($mybb->get_input('type') == 'feedback') {
        loadLanguage();
    }

    return true;
}

function report_type(): bool
{
    global $report_type;

    if ($report_type != 'feedback') {
        return false;
    }

    global $db, $mybb, $error, $verified, $id, $id2, $id3, $report_type_db, $lang;

    $fid = $mybb->get_input('pid', MyBB::INPUT_INT);

    // Any member can report a reputation comment but let's make sure it exists first
    $query = $db->simple_select('ougc_feedback', '*', "fid='{$fid}'");

    $feedback = $db->fetch_array($query);

    if (empty($feedback)) {
        $error = $lang->error_invalid_report;
    } else {
        $verified = true;

        $id = $feedback['fid']; // id is the feedback id

        $id2 = $feedback['fuid']; // id2 is the user who gave the feedback

        $id3 = $feedback['uid']; // id3 is the user who received the feedback

        $report_type_db = "type='feedback'";
    }

    return true;
}

function modcp_reports_report(): bool
{
    global $report;

    if ($report['type'] != 'feedback') {
        return false;
    }

    global $reputation_link, $bad_user, $lang, $good_user, $usercache, $report_data;

    loadLanguage();

    $user = get_user($report['id3']);

    $reputation_link = "feedback.php?uid={$user['uid']}&amp;fid={$report['id']}";

    $bad_user = build_profile_link($usercache[$report['id2']]['username'], $usercache[$report['id2']]['uid']);

    $report_data['content'] = $lang->sprintf($lang->ougc_feedback_report_info, $reputation_link, $bad_user);

    $good_user = build_profile_link($user['username'], $user['uid']);

    $report_data['content'] .= $lang->sprintf($lang->ougc_feedback_report_info_profile, $good_user);

    return true;
}

function report_content_types(array &$args): array
{
    loadLanguage();

    $args[] = 'feedback';

    return $args;
}