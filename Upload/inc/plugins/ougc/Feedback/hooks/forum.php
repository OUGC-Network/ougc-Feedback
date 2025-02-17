<?php

/***************************************************************************
 *
 *    ougc Feedback plugin (/inc/plugins/ougc/Feedback/hooks/forum.php)
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

use postParser;

use function NewPoints\ContractsSystem\Core\get_contract;
use function NewPoints\Core\language_load;
use function ougc\Feedback\Core\enableContractSystemIntegration;
use function ougc\Feedback\Core\getSetting;
use function ougc\Feedback\Core\getTemplate;
use function ougc\Feedback\Core\getUserStats;
use function ougc\Feedback\Core\isModerator;
use function ougc\Feedback\Core\loadLanguage;

use function ougc\Feedback\Core\set_go_back_button;
use function ougc\Feedback\Core\trow_error;

use const NewPoints\ContractsSystem\Core\CONTRACT_STATUS_ACCEPTED;
use const NewPoints\ContractsSystem\Core\CONTRACT_STATUS_CLOSED;
use const NewPoints\ContractsSystem\Core\CONTRACT_TYPE_AUCTION;
use const NewPoints\ContractsSystem\Core\CONTRACT_TYPE_BUY;
use const NewPoints\ContractsSystem\Core\CONTRACT_TYPE_SELL;
use const ougc\Feedback\Core\FEEDBACK_TYPE_CONTRACTS_SYSTEM;
use const ougc\Feedback\Core\DEBUG;
use const ougc\Feedback\Core\FEEDBACK_TYPE_BUYER;
use const ougc\Feedback\Core\FEEDBACK_TYPE_NEGATIVE;
use const ougc\Feedback\Core\FEEDBACK_TYPE_NEUTRAL;
use const ougc\Feedback\Core\FEEDBACK_TYPE_POSITIVE;
use const ougc\Feedback\Core\FEEDBACK_TYPE_SELLER;
use const ougc\Feedback\Core\PLUGIN_VERSION_CODE;
use const ougc\Feedback\ROOT;

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
            $templatelist .= ', ougcfeedback_profile_latest_row, ougcfeedback_profile_latest_empty, ougcfeedback_profile_latest_view_all, ougcfeedback_profile_latest';
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

    $version = PLUGIN_VERSION_CODE;

    if (DEBUG) {
        $version = TIME_NOW;
    }

    $ougc_feedback_js = eval(getTemplate('js'));

    global $db;
    global $ougcFeedbackCounterPositive, $ougcFeedbackCounterNeutral, $ougcFeedbackCounterNegative;

    $ougcFeedbackCounterPositive = $ougcFeedbackCounterNeutral = $ougcFeedbackCounterNegative = 0;

    $userID = (int)$mybb->user['uid'];

    $whereClauses = [
        "uid='{$userID}'",
        "status='1'"
    ];

    $dbQuery = $db->simple_select('ougc_feedback', 'feedback,uid,fuid', implode(' AND ', $whereClauses));

    while ($feedbackData = $db->fetch_array($dbQuery)) {
        switch ((int)$feedbackData['feedback']) {
            case FEEDBACK_TYPE_POSITIVE:
                ++$ougcFeedbackCounterPositive;
                break;
            case FEEDBACK_TYPE_NEUTRAL:
                ++$ougcFeedbackCounterNeutral;
                break;
            case FEEDBACK_TYPE_NEGATIVE:
                ++$ougcFeedbackCounterNegative;
                break;
        }
    }

    return true;
}

function member_profile_end(): string
{
    global $db, $memprofile, $ougc_feedback, $ougc_feedback_average, $theme, $lang, $mybb;

    loadLanguage();

    $ougc_feedback = $ougc_feedback_average = '';

    if (!getSetting('showin_profile')) {
        return $ougc_feedback;
    }

    $userID = (int)$memprofile['uid'];

    $stats = getUserStats($userID);

    $class = '_neutral';

    if ($stats['average'] > 0) {
        $class = '_positive';
    } elseif ($stats['average'] < 0) {
        $class = '_negative';
    }

    $ougc_feedback_average = eval(getTemplate('profile_average'));

    $add_row = '';

    $trow = 'trow1';

    $memprofile_perms = usergroup_permissions($memprofile['usergroup'] . ',' . $memprofile['additionalgroups']);

    if (
        getSetting('allow_profile') &&
        $mybb->usergroup['ougc_feedback_cangive'] &&
        $memprofile_perms['ougc_feedback_canreceive'] &&
        $mybb->user['uid'] != $memprofile['uid']
    ) {
        $show = true;

        if (!getSetting('allow_profile_multiple') && getSetting('profile_hide_add')) {
            $where = [
                "uid='{$memprofile['uid']}'",
                /*"fuid!='0'", */
                "fuid='{$mybb->user['uid']}'"
            ];

            if (!isModerator()) {
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

function member_profile_end10(): bool
{
    global $db, $memprofile, $theme, $lang, $mybb, $parser;
    global $feedbackLatest;

    $feedbackLimit = getSetting('latest_profile_feedback');

    $feedbackLatest = '';

    if ($feedbackLimit < 1) {
        return false;
    }

    if (!($parser instanceof postParser)) {
        require_once MYBB_ROOT . 'inc/class_parser.php';

        $parser = new postParser();
    }

    loadLanguage();

    $userID = (int)$memprofile['uid'];

    $whereClauses = ["f.uid='{$userID}'"];

    if (!isModerator()) {
        $whereClauses[] = "f.status='1'";
    }

    $dbQuery = $db->simple_select(
        "ougc_feedback f LEFT JOIN {$db->table_prefix}users u ON (u.uid=f.fuid)",
        'f.*, u.username AS userName, u.reputation AS user_reputation, u.usergroup AS userGroup, u.displaygroup AS displayGroup',
        implode(' AND ', $whereClauses),
        ['limit' => $feedbackLimit]
    );

    $profileUserName = htmlspecialchars_uni($memprofile['username']);

    $tableTitle = $lang->sprintf($lang->ougc_feedback_profile_latest_title, $profileUserName);

    if (!$db->num_rows($dbQuery)) {
        $feedback_list = eval(getTemplate('profile_latest_empty'));

        $viewAllLink = '';
    } else {
        $feedback_list = '';

        while ($feedbackData = $db->fetch_array($dbQuery)) {
            $feedbackID = (int)$feedbackData['fid'];

            $feedbackRate = (int)$feedbackData['feedback'];

            if (empty($feedbackData['fuid'])) {
                $userName = $lang->guest;
            } elseif (empty($feedbackData['userName'])) {
                $userName = $lang->na;
            } else {
                $userName = format_name(
                    htmlspecialchars_uni($feedbackData['userName']),
                    $feedbackData['userGroup'],
                    $feedbackData['displayGroup']
                );

                $userName = build_profile_link(
                    $userName,
                    $feedbackData['fuid']
                );
            }

            switch ($feedbackData['feedback']) {
                case FEEDBACK_TYPE_NEGATIVE:
                    $statusClass = 'trow_reputation_negative';

                    $typeClass = 'reputation_negative';

                    $rateType = $lang->ougc_feedback_profile_negative;
                    break;
                case FEEDBACK_TYPE_NEUTRAL:
                    $statusClass = 'trow_reputation_neutral';

                    $typeClass = 'reputation_neutral';

                    $rateType = $lang->ougc_feedback_profile_neutral;
                    break;
                case FEEDBACK_TYPE_POSITIVE:
                    $statusClass = 'trow_reputation_positive';

                    $typeClass = 'reputation_positive';

                    $rateType = $lang->ougc_feedback_profile_positive;
                    break;
            }

            $lastUpdatedDate = my_date('relative', $feedbackData['dateline']);

            $lastUpdated = $lang->sprintf($lang->ougc_feedback_page_last_updated, $lastUpdatedDate);

            $feedbackComment = $lang->ougc_feedback_no_allowed_to_view_comment;

            if (is_member(getSetting('latest_profile_comment_groups'))) {
                if (empty($feedbackData['comment'])) {
                    $feedbackComment = $lang->ougc_feedback_no_comment;
                } else {
                    $feedbackComment = $parser->parse_message($feedbackData['comment'], [
                        'allow_html' => 0,
                        'allow_mycode' => 0,
                        'allow_smilies' => 1,
                        'allow_imgcode' => 0,
                        'filter_badwords' => 1,
                    ]);
                }
            }

            $feedback_list .= eval(getTemplate('profile_latest_row'));
        }

        $viewAllLink = eval(getTemplate('profile_latest_view_all'));
    }

    $feedbackLatest = eval(getTemplate('profile_latest'));

    return true;
}

function postbit(array &$post): array
{
    global $db, $templates, $theme, $lang, $mybb, $pids;

    loadLanguage();

    $post['ougc_feedback'] = $post['ougc_feedback_button'] = $post['ougc_feedback_average'] = '';

    $show = (bool)is_member(getSetting('showin_forums'), ['usergroup' => $post['fid'], 'additionalgroups' => '']);

    if ($show && getSetting('showin_postbit')) {
        static $query_cache;

        if (!isset($query_cache)) {
            global $plugins;

            $where = [
                /*"fuid!='0'", */
                "status='1'"
            ];

            /*if(!isModerator())
            {
                $where[] = "status='1'";
            }*/

            if ($plugins->current_hook == 'postbit' && $mybb->get_input(
                    'mode'
                ) != 'threaded' && !empty($pids) && THIS_SCRIPT != 'newreply.php') {
                $uids = [];

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

        $stats = [
            'total' => 0,
            'positive' => 0,
            'neutral' => 0,
            'negative' => 0,
            'positive_percent' => 0,
            'neutral_percent' => 0,
            'negative_percent' => 0,
            'positive_users' => [],
            'neutral_users' => [],
            'negative_users' => []
        ];

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

        $view_all = '';

        if ($mybb->usergroup['ougc_feedback_canview']) {
            $view_all = eval(getTemplate('postbit_view_all'));
        }

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

    if (empty($forum) || !$forum['ougc_feedback_allow_threads'] && !$forum['ougc_feedback_allow_posts']) {
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

        if (!isset($button_query_cache) && getSetting('postbit_hide_button')) {
            global $plugins;

            $where = ["f.fuid='{$mybb->user['uid']}'"];

            if (!isModerator()) {
                $where[] = "f.status='1'";
            }

            if ($plugins->current_hook == 'postbit' && $mybb->get_input(
                    'mode'
                ) != 'threaded' && !empty($pids) && THIS_SCRIPT != 'newreply.php') {
                $where[] = "p.{$pids}";

                $join = ' LEFT JOIN ' . TABLE_PREFIX . 'posts p ON (p.pid=f.pid)';
            } else {
                $where[] = "f.unique_id='{$post['pid']}'";

                $join = '';
            }

            $query = $db->simple_select('ougc_feedback f' . $join, 'f.unique_id', implode(' AND ', $where));

            while ($unique_id = $db->fetch_field($query, 'unique_id')) {
                $query_cache[$unique_id][] = $unique_id;
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

function memberlist_user(array &$userData): array
{
    $userData['feedback'] = $userData['feedback_average'] = '';

    if (!getSetting('showin_memberlist')) {
        return $userData;
    }

    global $theme, $lang, $mybb;

    loadLanguage();

    $userID = (int)$userData['uid'];

    $stats = getUserStats($userID);

    $class = '_neutral';

    if ($stats['average'] > 0) {
        $class = '_positive';
    } elseif ($stats['average'] < 0) {
        $class = '_negative';
    }

    $userData['feedback_average'] = eval(getTemplate('memberlist_average'));

    $view_all = '';

    if ($mybb->usergroup['ougc_feedback_canview']) {
        $view_all = eval(getTemplate('memberlist_view_all'));
    }

    $userData['feedback'] = eval(getTemplate('memberlist'));

    return $userData;
}

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

    // report pid stores the feedback id
    $feedbackID = $mybb->get_input('pid', MyBB::INPUT_INT);

    // Any member can report a reputation comment but let's make sure it exists first
    $query = $db->simple_select('ougc_feedback', '*', "fid='{$feedbackID}'");

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

function newpoints_contracts_system_parse_start(array $hookArguments): array
{
    if (!enableContractSystemIntegration()) {
        return $hookArguments;
    }

    $hookArguments['vars']['buttons']['feedback'] = $hookArguments['vars']['rows']['feedback'] = '';

    return $hookArguments;
}

function newpoints_contracts_system_parse_intermediate(array $hookArguments): array
{
    if (!enableContractSystemIntegration() || empty($hookArguments['contract_data']['tid'])) {
        return $hookArguments;
    }

    $display_feedback = false;

    if (!empty($hookArguments['contract_data']['fid'])) {
        $display_feedback = !empty(get_forum($hookArguments['contract_data']['fid'])['ougc_feedback_allow_threads']);
    }

    if ($display_feedback) {
        global $mybb, $db;

        $whereClauses = [
            "fuid='{$hookArguments['offeree_user_id']}'",
            "pid='{$hookArguments['contract_data']['firstpost']}'"
        ];

        if (!isModerator()) {
            $whereClauses[] = "status='1'";
        }

        switch ($hookArguments['contract_data']['type']) {
            case CONTRACT_TYPE_SELL:
                $whereClauses[] = "type='1'";
                break;
            case CONTRACT_TYPE_BUY:
                $whereClauses[] = "type='2'";
                break;
        }

        $query = $db->simple_select('ougc_feedback', '*', implode(' AND ', $whereClauses));

        $feedbackData = $db->fetch_array($query);

        if (!empty($feedbackData)) {
            $feedbackData['feedback'] = (int)$feedbackData['feedback'];

            $value = my_number_format($feedbackData['feedback']);

            $comment = '';

            $feedback_class = 'neutral';

            if ($feedbackData['comment']) {
                $parser_options = array(
                    'allow_html' => 0,
                    'allow_mycode' => 0,
                    'allow_smilies' => 1,
                    'allow_imgcode' => 0,
                    'filter_badwords' => 1,
                );

                $comment = $parser->parse_message($feedbackData['comment'], $parser_options);

                $comment = eval(getTemplate('contractSystemParseStatusComment'));
            }

            if ($feedbackData['feedback'] > 0) {
                $feedback_class = 'positive';
            } elseif ($feedbackData['feedback'] < 0) {
                $feedback_class = 'negative';
            }

            $feedback_row = eval(getTemplate('contractSystemParseStatus'));
        }
    }

    return $hookArguments;
}

function newpoints_contracts_system_parse_end(array $hookArguments): array
{
    if (!enableContractSystemIntegration()) {
        return $hookArguments;
    }

    if ($hookArguments['contract_status'] !== CONTRACT_STATUS_ACCEPTED ||
        $hookArguments['status_closed'] !== CONTRACT_STATUS_CLOSED) {
        $hookArguments['vars']['buttons']['feedback'] = eval(getTemplate('contractSystemTableItemRowEmpty'));

        return $hookArguments;
    }

    $contractID = (int)$hookArguments['contract_id'];

    if ($hookArguments['offeror_user_id'] === $hookArguments['current_user_id']) {
        $feedbackUserID = $hookArguments['offeree_user_id'];

        switch ($hookArguments['contract_data']['type']) {
            case CONTRACT_TYPE_AUCTION:
            case CONTRACT_TYPE_SELL:
                $feedbackType = FEEDBACK_TYPE_SELLER;
                break;
            default:
                $feedbackType = FEEDBACK_TYPE_BUYER;
                break;
        }
    } else {
        $feedbackUserID = $hookArguments['offeror_user_id'];

        switch ($hookArguments['contract_data']['contract_type']) {
            case CONTRACT_TYPE_AUCTION:
            case CONTRACT_TYPE_SELL:
                $feedbackType = FEEDBACK_TYPE_BUYER;
                break;
            default:
                $feedbackType = FEEDBACK_TYPE_SELLER;
                break;
        }
    }

    $feedbackPluginCode = FEEDBACK_TYPE_CONTRACTS_SYSTEM;

    $whereClauses = [
        "uid='{$feedbackUserID}'",
        "fuid='{$hookArguments['current_user_id']}'"
    ];

    global $db, $lang;

    loadLanguage();

    if (!isModerator()) {
        $whereClauses[] = "status='1'";
    }

    $dbQuery = $db->simple_select('ougc_feedback', 'fid', implode(' AND ', $whereClauses));

    if ($db->num_rows($dbQuery)) {
        $feedbackID = (int)$db->fetch_field($dbQuery, 'fid');

        $buttonText = $lang->ougcFeedbackContractsSystemButtonEdit;

        $hookArguments['vars']['buttons']['feedback'] = eval(getTemplate('contractSystemTableItemRowEdit'));
    } else {
        $buttonText = $lang->ougcFeedbackContractsSystemButtonAdd;

        $hookArguments['vars']['buttons']['feedback'] = eval(getTemplate('contractSystemTableItemRow'));
    }

    return $hookArguments;
}

function newpoints_contracts_system_page_table_start(array $hookArguments): array
{
    if (!enableContractSystemIntegration()) {
        return $hookArguments;
    }

    global $lang;

    loadLanguage();

    ++$hookArguments['column_span'];

    $hookArguments['extra_table_thead'][] = eval(getTemplate('contractSystemTableItemThead'));

    return $hookArguments;
}

function ougc_feedback_add_edit_intermediate(array &$hookArguments): array
{
    if (
        $hookArguments['processed'] !== false ||
        $hookArguments['feedback_code'] !== FEEDBACK_TYPE_CONTRACTS_SYSTEM
    ) {
        return $hookArguments;
    }

    global $lang;

    loadLanguage();

    language_load('contracts_system');

    if (!enableContractSystemIntegration()) {
        set_go_back_button(false);

        trow_error($lang->ougcContractSystemErrorsFeedbackDisabled);

        return $hookArguments;
    }

    global $mybb;

    $contractID = (int)$hookArguments['feedback_data']['unique_id'];

    $contractData = get_contract($contractID);

    if (empty($contractData['contract_id'])) {
        set_go_back_button(false);

        trow_error($lang->ougcContractSystemErrorsInvalidContract);

        return $hookArguments;
    }

    $userID = (int)$mybb->user['uid'];

    $offerorUserID = (int)$contractData['seller_id'];

    $offereeUserID = (int)$contractData['buyer_id'];

    $feedbackUserID = (int)$hookArguments['feedback_data']['uid'];

    if (!in_array($userID, [$offerorUserID, $offereeUserID], true) || $feedbackUserID === $userID) {
        set_go_back_button(false);

        trow_error($lang->newpoints_contracts_system_errors_invaliduser);

        return $hookArguments;
    }

    $contractType = (int)$hookArguments['feedback_data']['type'];

    if ($offerorUserID === $userID) {
        switch ($contractData['contract_type']) {
            case CONTRACT_TYPE_AUCTION:
            case CONTRACT_TYPE_SELL:
                $feedbackType = FEEDBACK_TYPE_SELLER;
                break;
            default:
                $feedbackType = FEEDBACK_TYPE_BUYER;
                break;
        }
    } else {
        switch ($contractData['contract_type']) {
            case CONTRACT_TYPE_AUCTION:
            case CONTRACT_TYPE_SELL:
                $feedbackType = FEEDBACK_TYPE_BUYER;
                break;
            default:
                $feedbackType = FEEDBACK_TYPE_SELLER;
                break;
        }
    }

    if (
        !in_array($contractType, [FEEDBACK_TYPE_SELLER, FEEDBACK_TYPE_BUYER]) ||
        $contractType !== $feedbackType
    ) {
        trow_error($lang->ougcContractSystemErrorsFeedbackInvalidType);

        return $hookArguments;
    }

    if ($mybb->get_input('action') !== 'edit') {
        global $db;

        $whereClauses = [
            "uid='{$feedbackUserID}'",
            "fuid='{$userID}'"
        ];

        if (!$mybb->usergroup['ougc_feedback_ismod']) {
            $whereClauses[] = "status='1'";
        }

        $dbQuery = $db->simple_select('ougc_feedback', 'fid', implode(' AND ', $whereClauses));

        if ($db->num_rows($dbQuery)) {
            set_go_back_button(false);

            trow_error($lang->ougcContractSystemErrorsFeedbackDuplicated);

            return $hookArguments;
        }
    }

    $hookArguments['processed'] = true;

    return $hookArguments;
}

function ougc_feedback_add_edit_do_start(array &$hookArguments): array
{
    $hookArguments['processed'] = false;

    return ougc_feedback_add_edit_intermediate($hookArguments);
}