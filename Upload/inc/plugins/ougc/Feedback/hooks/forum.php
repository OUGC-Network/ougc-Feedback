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

use function ougc\Feedback\Core\enableContractSystemIntegration;
use function ougc\Feedback\Core\feedbackGet;
use function ougc\Feedback\Core\getSetting;
use function ougc\Feedback\Core\getTemplate;
use function ougc\Feedback\Core\getUserStats;
use function ougc\Feedback\Core\isModerator;
use function ougc\Feedback\Core\loadLanguage;
use function ougc\Feedback\Core\backButtonSet;
use function ougc\Feedback\Core\ratingGet;
use function ougc\Feedback\Core\trowError;
use function ougc\Feedback\Core\urlHandlerBuild;
use function ougc\Feedback\Core\urlHandlerGet;
use function NewPoints\Core\language_load;
use function Newpoints\Core\post_parser_parse_message;
use function NewPoints\ContractsSystem\Core\get_contract;

use const ougc\Feedback\Core\FEEDBACK_TYPE_CONTRACTS_SYSTEM;
use const ougc\Feedback\Core\DEBUG;
use const ougc\Feedback\Core\FEEDBACK_TYPE_BUYER;
use const ougc\Feedback\Core\FEEDBACK_VALUE_NEGATIVE;
use const ougc\Feedback\Core\FEEDBACK_VALUE_NEUTRAL;
use const ougc\Feedback\Core\FEEDBACK_VALUE_POSITIVE;
use const ougc\Feedback\Core\FEEDBACK_TYPE_SELLER;
use const ougc\Feedback\Core\PLUGIN_VERSION_CODE;
use const NewPoints\ContractsSystem\Core\CONTRACT_STATUS_ACCEPTED;
use const NewPoints\ContractsSystem\Core\CONTRACT_STATUS_CLOSED;
use const NewPoints\ContractsSystem\Core\CONTRACT_TYPE_AUCTION;
use const NewPoints\ContractsSystem\Core\CONTRACT_TYPE_BUY;
use const NewPoints\ContractsSystem\Core\CONTRACT_TYPE_SELL;

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
            $templatelist .= ', ougcfeedback_profile_average, ougcfeedback_profile_add, ougcfeedback_profile_rating, ougcfeedback_profile_view_all, ougcfeedback_profile, ougcfeedback_profile_latest_row_rating, ougcfeedback_profile_latest_row, ougcfeedback_profile_latest_view_all, ougcfeedback_profile_latest';
            break;
        case 'showthread.php':
        case 'newthread.php':
        case 'newreply.php':
        case 'editpost.php':
        case 'private.php':
            $templatelist .= ', ougcfeedback_postbit_view_all, ougcfeedback_postbit_average, ougcfeedback_postbit, ougcfeedback_postbit_button';
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

    $feedbackSystemUrl = urlHandlerGet();

    $ougc_feedback_js = eval(getTemplate('js'));

    global $db;
    global $ougcFeedbackCounterPositive, $ougcFeedbackCounterNeutral, $ougcFeedbackCounterNegative;

    $ougcFeedbackCounterPositive = $ougcFeedbackCounterNeutral = $ougcFeedbackCounterNegative = 0;

    $currentUserID = (int)$mybb->user['uid'];

    $whereClauses = [
        "userID='{$currentUserID}'",
        "feedbackStatus='1'"
    ];

    $dbQuery = $db->simple_select(
        'ougc_feedback',
        'feedbackValue,userID,feedbackUserID',
        implode(' AND ', $whereClauses)
    );

    while ($feedbackData = $db->fetch_array($dbQuery)) {
        $feedbackValue = (int)$feedbackData['feedbackValue'];

        switch ($feedbackValue) {
            case FEEDBACK_VALUE_POSITIVE:
                ++$ougcFeedbackCounterPositive;
                break;
            case FEEDBACK_VALUE_NEUTRAL:
                ++$ougcFeedbackCounterNeutral;
                break;
            case FEEDBACK_VALUE_NEGATIVE:
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

    $feedbackUserLink = urlHandlerBuild(['userID' => $userID]);

    $statsData = getUserStats($userID);

    $stylingClasses = '_neutral';

    if ($statsData['average'] > 0) {
        $stylingClasses = '_positive';
    } elseif ($statsData['average'] < 0) {
        $stylingClasses = '_negative';
    }

    $ougc_feedback_average = eval(getTemplate('profile_average'));

    $add_row = '';

    $trow = 'trow1';

    $userPermissions = usergroup_permissions($memprofile['usergroup'] . ',' . $memprofile['additionalgroups']);

    $alternativeBackground = alt_trow(true);

    $currentUserID = (int)$mybb->user['uid'];

    if (
        getSetting('allow_profile') &&
        $mybb->usergroup['ougc_feedback_cangive'] &&
        $userPermissions['ougc_feedback_canreceive'] &&
        $mybb->user['uid'] != $userID
    ) {
        $show = true;

        if (!getSetting('allow_profile_multiple') && getSetting('profile_hide_add')) {
            $where = [
                "userID='{$userID}'",
                /*"feedbackUserID!='0'", */
                "feedbackUserID='{$currentUserID}'"
            ];

            if (!isModerator()) {
                $where[] = "feedbackStatus='1'";
            }

            if (feedbackGet($where, [], ['limit' => 1])) {
                $show = false;
            }
        }

        if ($show) {
            $trow = 'trow2';

            $pid = '';

            $uid = $userID;

            $mybb->input['feedbackType'] = isset($mybb->input['feedbackType']) ? $mybb->get_input(
                'feedbackType',
                MyBB::INPUT_INT
            ) : 1;

            $mybb->input['feedbackValue'] = isset($mybb->input['feedbackValue']) ? $mybb->get_input(
                'feedbackValue',
                MyBB::INPUT_INT
            ) : 1;

            $add_row = eval(getTemplate('profile_add'));

            $alternativeBackground = alt_trow();
        }
    }

    $rating_rows = '';

    foreach (
        ratingGet(
            [],
            ['ratingName', 'ratingDescription', 'ratingClass', 'ratingMaximumValue']
        ) as $ratingID => $ratingData
    ) {
        $ratingName = $lang->sprintf(
            $lang->ougc_feedback_profile_rating,
            htmlspecialchars_uni($ratingData['ratingName'])
        );

        $ratingDescription = htmlspecialchars_uni($ratingData['ratingDescription']);

        $ratingClass = htmlspecialchars_uni($ratingData['ratingClass']);

        $ratingMaximumValue = max(1, min(5, (int)$ratingData['ratingMaximumValue']));

        $ratingValue = (float)($memprofile['ougcFeedbackRatingAverage' . $ratingID] ?? 0);

        $rating_rows .= eval(getTemplate('profile_rating'));

        $alternativeBackground = alt_trow();
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

    $feedbackUserLink = urlHandlerBuild(['userID' => $userID]);

    $whereClauses = ["f.userID='{$userID}'"];

    if (!isModerator()) {
        $whereClauses[] = "f.feedbackStatus='1'";
    }

    $dbQuery = $db->simple_select(
        "ougc_feedback f LEFT JOIN {$db->table_prefix}users u ON (u.uid=f.feedbackUserID)",
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

        $alternativeBackground = alt_trow(true);

        while ($feedbackData = $db->fetch_array($dbQuery)) {
            $feedbackType = (int)$feedbackData['feedbackType'];

            $feedbackID = (int)$feedbackData['feedbackID'];

            $feedbackValue = (int)$feedbackData['feedbackValue'];

            $uniqueID = (int)$feedbackData['uniqueID'];

            $feedbackCode = (int)$feedbackData['feedbackCode'];

            if (empty($feedbackData['feedbackUserID'])) {
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
                    $feedbackData['feedbackUserID']
                );
            }

            switch ($feedbackValue) {
                case FEEDBACK_VALUE_NEGATIVE:
                    $feedbackStatusClass = 'trow_reputation_negative';

                    $feedbackClass = 'reputation_negative';

                    $feedbackType = $lang->ougc_feedback_profile_negative;
                    break;
                case FEEDBACK_VALUE_NEUTRAL:
                    $feedbackStatusClass = 'trow_reputation_neutral';

                    $feedbackClass = 'reputation_neutral';

                    $feedbackType = $lang->ougc_feedback_profile_neutral;
                    break;
                case FEEDBACK_VALUE_POSITIVE:
                    $feedbackStatusClass = 'trow_reputation_positive';

                    $feedbackClass = 'reputation_positive';

                    $feedbackType = $lang->ougc_feedback_profile_positive;
                    break;
            }

            $lastUpdatedDate = my_date('relative', $feedbackData['createStamp']);

            $lastUpdated = $lang->sprintf($lang->ougc_feedback_page_last_updated, $lastUpdatedDate);

            $feedbackComment = $lang->ougc_feedback_no_allowed_to_view_comment;

            if (is_member(getSetting('latest_profile_comment_groups'))) {
                if (empty($feedbackData['feedbackComment'])) {
                    $feedbackComment = $lang->ougc_feedback_no_comment;
                } else {
                    $feedbackComment = $parser->parse_message($feedbackData['feedbackComment'], [
                        'allow_html' => 0,
                        'allow_mycode' => 0,
                        'allow_smilies' => 1,
                        'allow_imgcode' => 0,
                        'filter_badwords' => 1,
                    ]);
                }
            }

            $rating_rows = '';

            foreach (
                ratingGet(
                    [],
                    ['ratingName', 'ratingDescription', 'ratingClass', 'ratingMaximumValue', 'feedbackCode']
                ) as $ratingID => $ratingData
            ) {
                if ((int)$ratingData['feedbackCode'] !== $feedbackCode) {
                    continue;
                }

                $ratingName = htmlspecialchars_uni($ratingData['ratingName']);

                $ratingDescription = htmlspecialchars_uni($ratingData['ratingDescription']);

                $ratingClass = htmlspecialchars_uni($ratingData['ratingClass']);

                $ratingMaximumValue = max(1, min(5, (int)$ratingData['ratingMaximumValue']));

                $ratingValue = (int)$feedbackData['ratingID' . $ratingID];

                $rating_rows .= eval(getTemplate('profile_latest_row_rating'));
            }

            $feedback_list .= eval(getTemplate('profile_latest_row'));

            $alternativeBackground = alt_trow();
        }

        $viewAllLink = eval(getTemplate('profile_latest_view_all'));
    }

    $feedbackLatest = eval(getTemplate('profile_latest'));

    return true;
}

function postbit(array &$postData): array
{
    global $db, $templates, $theme, $lang, $mybb, $pids;

    loadLanguage();

    $userID = (int)$postData['uid'];

    $postID = (int)$postData['pid'];

    $postData['ougc_feedback'] = $postData['ougc_feedback_button'] = $postData['ougc_feedback_average'] = '';

    $show = (bool)is_member(getSetting('showin_forums'), ['usergroup' => $postData['fid'], 'additionalgroups' => '']);

    $feedbackUserLink = urlHandlerBuild(['userID' => $userID]);

    if ($show && getSetting('showin_postbit')) {
        static $query_cache;

        if (!isset($query_cache)) {
            global $plugins;

            $where = [
                /*"feedbackUserID!='0'", */
                "feedbackStatus='1'"
            ];

            /*if(!isModerator())
            {
                $where[] = "feedbackStatus='1'";
            }*/

            if ($plugins->current_hook == 'postbit' &&
                $mybb->get_input('mode') != 'threaded' &&
                !empty($pids) &&
                THIS_SCRIPT != 'newreply.php') {
                $uids = [];

                $query = $db->simple_select(
                    'users u LEFT JOIN ' . TABLE_PREFIX . 'posts p ON (p.uid=u.uid)',
                    'u.uid',
                    "p.{$pids}"
                );

                while ($uid = $db->fetch_field($query, 'uid')) {
                    $uids[$uid] = (int)$uid;
                }

                $where[] = "userID IN ('" . implode("','", $uids) . "')";
            } else {
                $where[] = "userID='{$postData['uid']}'";
            }

            $query = $db->simple_select(
                'ougc_feedback',
                'feedbackValue,userID,feedbackUserID',
                implode(' AND ', $where)
            );
            while ($feedbackData = $db->fetch_array($query)) {
                $uid = (int)$feedbackData['userID'];

                unset($feedbackData['userID']);

                $query_cache[$uid][] = $feedbackData;
            }
        }

        $statsData = [
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

        if (!empty($query_cache[$postData['uid']])) {
            foreach ($query_cache[$postData['uid']] as $feedbackData) {
                ++$statsData['total'];

                $feedbackValue = (int)$feedbackData['feedbackValue'];

                switch ($feedbackValue) {
                    case FEEDBACK_VALUE_POSITIVE:
                        ++$statsData['positive'];
                        $statsData['positive_users'][$feedbackData['feedbackUserID']] = 1;
                        break;
                    case FEEDBACK_VALUE_NEUTRAL:
                        ++$statsData['neutral'];
                        $statsData['neutral_users'][$feedbackData['feedbackUserID']] = 1;
                        break;
                    case FEEDBACK_VALUE_NEGATIVE:
                        ++$statsData['negative'];
                        $statsData['negative_users'][$feedbackData['feedbackUserID']] = 1;
                        break;
                }
            }
        }

        $average = 0;

        if ($statsData['total']) {
            $statsData['positive_percent'] = floor(100 * ($statsData['positive'] / $statsData['total']));

            $statsData['neutral_percent'] = floor(100 * ($statsData['neutral'] / $statsData['total']));

            $statsData['negative_percent'] = floor(100 * ($statsData['negative'] / $statsData['total']));

            $average = $statsData['positive'] - $statsData['negative'];
        }

        $statsData['positive_users'] = count($statsData['positive_users']);

        $statsData['neutral_users'] = count($statsData['neutral_users']);

        $statsData['negative_users'] = count($statsData['negative_users']);

        $statsData = array_map('my_number_format', $statsData);

        $view_all = '';

        if ($mybb->usergroup['ougc_feedback_canview']) {
            $view_all = eval(getTemplate('postbit_view_all'));
        }

        $stylingClasses = 'reputation_neutral';

        if ($average > 0) {
            $stylingClasses = 'reputation_positive';
        } elseif ($average < 0) {
            $stylingClasses = 'reputation_negative';
        }

        $average = my_number_format($average);

        $postData['ougc_feedback_average'] = eval(getTemplate('postbit_average'));

        $postData['ougc_feedback'] = eval(getTemplate('postbit'));

        $postData['user_details'] = str_replace(
            '<!--OUGC_FEEDBACK-->',
            $postData['ougc_feedback'],
            $postData['user_details']
        );
    }

    global $plugins, $thread, $forum;

    if (empty($forum) || !$forum['ougc_feedback_allow_threads'] && !$forum['ougc_feedback_allow_posts']) {
        return $postData;
    }

    if ($forum['ougc_feedback_allow_threads'] && !$forum['ougc_feedback_allow_posts'] && $thread['firstpost'] != $postData['pid']) {
        return $postData;
    }

    if (!$forum['ougc_feedback_allow_threads'] && $forum['ougc_feedback_allow_posts'] && $thread['firstpost'] == $postData['pid']) {
        return $postData;
    }

    $userPermissions = usergroup_permissions($postData['usergroup'] . ',' . $postData['additionalgroups']);

    $currentUserID = (int)$mybb->user['uid'];

    if ($mybb->usergroup['ougc_feedback_cangive'] && $userPermissions['ougc_feedback_canreceive'] && $currentUserID !== $userID) {
        static $button_query_cache;

        if (!isset($button_query_cache) && getSetting('postbit_hide_button')) {
            global $plugins;

            $where = ["f.feedbackUserID='{$currentUserID}'"];

            if (!isModerator()) {
                $where[] = "f.feedbackStatus='1'";
            }

            if ($plugins->current_hook == 'postbit' &&
                $mybb->get_input('mode') != 'threaded' &&
                !empty($pids) &&
                THIS_SCRIPT != 'newreply.php') {
                $where[] = "p.{$pids}";

                $join = ' LEFT JOIN ' . TABLE_PREFIX . 'posts p ON (p.pid=f.uniqueID)';
            } else {
                $where[] = "f.uniqueID='{$postData['pid']}'";

                $join = '';
            }

            $query = $db->simple_select('ougc_feedback f' . $join, 'f.uniqueID', implode(' AND ', $where));

            while ($unique_id = $db->fetch_field($query, 'uniqueID')) {
                $query_cache[$unique_id][] = $unique_id;
            }
        }

        if (!isset($button_query_cache[$postData['pid']])) {
            $postData['ougc_feedback_button'] = eval(getTemplate('postbit_button'));
        }
    }
    #$plugins->remove_hook('postbit', array($this, 'hook_postbit'));

    return $postData;
}

function postbit_prev(array &$postData): array
{
    postbit($postData);

    return $postData;
}

function postbit_pm(array &$postData): array
{
    postbit($postData);

    return $postData;
}

function postbit_announcement(array &$postData): array
{
    postbit($postData);

    return $postData;
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

    $statsData = getUserStats($userID);

    $stylingClasses = '_neutral';

    if ($statsData['average'] > 0) {
        $stylingClasses = '_positive';
    } elseif ($statsData['average'] < 0) {
        $stylingClasses = '_negative';
    }

    $userData['feedback_average'] = eval(getTemplate('memberlist_average'));

    $feedbackUserLink = urlHandlerBuild(['userID' => $userID]);

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
    $feedbackFields = [
        'userID',
        'feedbackUserID',
    ];

    $feedbackData = feedbackGet(["feedbackID='{$feedbackID}'"], $feedbackFields, ['limit' => 1]);

    if (empty($feedbackData)) {
        $error = $lang->error_invalid_report;
    } else {
        $verified = true;

        $id = $feedbackData['feedbackID']; // id is the feedback id

        $id2 = $feedbackData['feedbackUserID']; // id2 is the user who gave the feedback

        $id3 = $feedbackData['userID']; // id3 is the user who received the feedback

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

    $userData = get_user($report['id3']);

    $reputation_link = urlHandlerBuild(['userID' => $userData['uid'], 'feedbackID' => $report['id']]);

    $bad_user = build_profile_link($usercache[$report['id2']]['username'], $usercache[$report['id2']]['uid']);

    global $mybb;

    $report_data['content'] = $lang->sprintf(
        $lang->ougc_feedback_report_info,
        $reputation_link,
        $bad_user,
        $mybb->settings['bburl']
    );

    $good_user = build_profile_link($userData['username'], $userData['uid']);

    $report_data['content'] .= $lang->sprintf($lang->ougc_feedback_report_info_profile, $good_user);

    return true;
}

function report_content_types(array &$hookArguments): array
{
    loadLanguage();

    $hookArguments[] = 'feedback';

    return $hookArguments;
}

function newpoints_global_start(array &$template_list): array
{
    $template_list['newpoints.php'] = array_merge($template_list['newpoints.php'], [
        'ougcfeedback_contractSystemTableItemThead',
        'ougcfeedback_contractSystemTableItemRow',
        'ougcfeedback_contractSystemTableItemRowEdit',
        'ougcfeedback_contractSystemTableItemRowEmpty',
        'ougcfeedback_contractSystemParseStatus',
        'ougcfeedback_contractSystemParseStatusComment',
    ]);

    return $template_list;
}

function newpoints_contracts_system_parse_start(array &$hookArguments): array
{
    if (!enableContractSystemIntegration()) {
        return $hookArguments;
    }

    $hookArguments['vars']['buttons']['feedback'] = $hookArguments['vars']['rows']['feedback'] = '';

    return $hookArguments;
}

function newpoints_contracts_system_parse_intermediate(array &$hookArguments): array
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
            "feedbackUserID='{$hookArguments['offeree_user_id']}'",
            "pid='{$hookArguments['contract_data']['firstpost']}'"
        ];

        if (!isModerator()) {
            $whereClauses[] = "feedbackStatus='1'";
        }

        switch ($hookArguments['contract_data']['type']) {
            case CONTRACT_TYPE_SELL:
                $whereClauses[] = "feedbackType='1'";
                break;
            case CONTRACT_TYPE_BUY:
                $whereClauses[] = "feedbackType='2'";
                break;
        }

        $feedbackFields = [
            'feedbackComment',
            'feedbackValue',
        ];

        $feedbackData = feedbackGet($whereClauses, $feedbackFields, ['limit' => 1]);

        if (!empty($feedbackData)) {
            $feedbackValue = (int)$feedbackData['feedbackValue'];

            $feedbackComment = '';

            $feedbackClass = 'neutral';

            if (!empty($feedbackData['feedbackComment'])) {
                $parser_options = array(
                    'allow_html' => 0,
                    'allow_mycode' => 0,
                    'allow_smilies' => 1,
                    'allow_imgcode' => 0,
                    'filter_badwords' => 1,
                );

                $feedbackComment = post_parser_parse_message($feedbackData['feedbackComment'], $parser_options);

                $feedbackComment = eval(getTemplate('contractSystemParseStatusComment'));
            }

            if ($feedbackValue === FEEDBACK_VALUE_POSITIVE) {
                $feedbackClass = 'positive';
            } elseif ($feedbackValue === FEEDBACK_VALUE_NEGATIVE) {
                $feedbackClass = 'negative';
            }

            $feedbackValue = my_number_format($feedbackValue);

            $feedback_row = eval(getTemplate('contractSystemParseStatus'));
        }
    }

    return $hookArguments;
}

function newpoints_contracts_system_parse_end(array &$hookArguments): array
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

        switch ($hookArguments['contract_data']['contract_type']) {
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
        "userID='{$feedbackUserID}'",
        "feedbackUserID='{$hookArguments['current_user_id']}'"
    ];

    global $db, $lang;

    loadLanguage();

    if (!isModerator()) {
        $whereClauses[] = "feedbackStatus='1'";
    }

    if ($feedbackData = feedbackGet($whereClauses, [], ['limit' => 1])) {
        $feedbackID = (int)$feedbackData['feedbackID'];

        $buttonText = $lang->ougcFeedbackContractsSystemButtonEdit;

        $hookArguments['vars']['buttons']['feedback'] = eval(getTemplate('contractSystemTableItemRowEdit'));
    } else {
        $buttonText = $lang->ougcFeedbackContractsSystemButtonAdd;

        $hookArguments['vars']['buttons']['feedback'] = eval(getTemplate('contractSystemTableItemRow'));
    }

    return $hookArguments;
}

function newpoints_contracts_system_page_table_start(array &$hookArguments): array
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
        $hookArguments['feedbackProcessed'] !== false ||
        $hookArguments['feedbackCode'] !== FEEDBACK_TYPE_CONTRACTS_SYSTEM
    ) {
        return $hookArguments;
    }

    global $lang;

    loadLanguage();

    language_load('contracts_system');

    if (!enableContractSystemIntegration()) {
        backButtonSet(false);

        trowError($lang->ougcContractSystemErrorsFeedbackDisabled);

        return $hookArguments;
    }

    global $mybb;

    $contractID = (int)$hookArguments['feedback_data']['uniqueID'];

    $contractData = get_contract($contractID);

    if (empty($contractData['contract_id'])) {
        backButtonSet(false);

        trowError($lang->ougcContractSystemErrorsInvalidContract);

        return $hookArguments;
    }

    $currentUserID = (int)$mybb->user['uid'];

    $offerorUserID = (int)$contractData['seller_id'];

    $offereeUserID = (int)$contractData['buyer_id'];

    $feedbackUserID = (int)$hookArguments['feedback_data']['userID'];

    if (!in_array($currentUserID, [$offerorUserID, $offereeUserID]) || $feedbackUserID === $currentUserID) {
        backButtonSet(false);

        trowError($lang->newpoints_contracts_system_errors_invaliduser);

        return $hookArguments;
    }

    $contractType = (int)$hookArguments['feedback_data']['feedbackType'];

    if ($offerorUserID === $currentUserID) {
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
        trowError($lang->ougcContractSystemErrorsFeedbackInvalidType);

        return $hookArguments;
    }

    if ($mybb->get_input('action') !== 'edit') {
        global $db;

        $whereClauses = [
            "userID='{$feedbackUserID}'",
            "feedbackUserID='{$currentUserID}'"
        ];

        if (empty($mybb->usergroup['ougc_feedback_ismod'])) {
            $whereClauses[] = "feedbackStatus='1'";
        }

        if (feedbackGet($whereClauses, [], ['limit' => 1])) {
            backButtonSet(false);

            trowError($lang->ougcContractSystemErrorsFeedbackDuplicated);

            return $hookArguments;
        }
    }

    $hookArguments['feedbackProcessed'] = true;

    return $hookArguments;
}

function ougc_feedback_add_edit_do_start(array &$hookArguments): array
{
    if (
        $hookArguments['feedbackProcessed'] === false &&
        $hookArguments['feedbackCode'] === FEEDBACK_TYPE_CONTRACTS_SYSTEM
    ) {
        $hookArguments['feedbackProcessed'] = false;

        return ougc_feedback_add_edit_intermediate($hookArguments);
    }

    return $hookArguments;
}