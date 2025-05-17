<?php

/***************************************************************************
 *
 *    ougc Feedback plugin (/feedback.php)
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

use function ougc\Feedback\Core\feedbackDelete;
use function ougc\Feedback\Core\feedbackGet;
use function ougc\Feedback\Core\getTemplate;
use function ougc\Feedback\Core\feedbackInsert;
use function ougc\Feedback\Core\isModerator;
use function ougc\Feedback\Core\loadLanguage;
use function ougc\Feedback\Core\ratingGet;
use function ougc\Feedback\Core\runHooks;
use function ougc\Feedback\Core\sendEmail;
use function ougc\Feedback\Core\backButtonSet;
use function ougc\Feedback\Core\feedbackUserSync;
use function ougc\Feedback\Core\sendPrivateMessage;
use function ougc\Feedback\Core\trowError;
use function ougc\Feedback\Core\trowSuccess;
use function ougc\Feedback\Core\feedbackUpdate;
use function ougc\Feedback\Core\urlHandlerBuild;
use function ougc\Feedback\Core\urlHandlerGet;
use function ougc\Feedback\Hooks\Forum\member_profile_end;
use function ougc\Feedback\Hooks\Forum\postbit;

use const ougc\Feedback\Core\FEEDBACK_STATUS_ACTIVE;
use const ougc\Feedback\Core\FEEDBACK_STATUS_SOFT_DELETED;
use const ougc\Feedback\Core\FEEDBACK_STATUS_UNAPPROVED;
use const ougc\Feedback\Core\FEEDBACK_TYPE_BUYER;
use const ougc\Feedback\Core\FEEDBACK_VALUE_NEGATIVE;
use const ougc\Feedback\Core\FEEDBACK_VALUE_NEUTRAL;
use const ougc\Feedback\Core\FEEDBACK_VALUE_POSITIVE;
use const ougc\Feedback\Core\FEEDBACK_TYPE_POST;
use const ougc\Feedback\Core\FEEDBACK_TYPE_PROFILE;
use const ougc\Feedback\Core\FEEDBACK_TYPE_SELLER;
use const ougc\Feedback\Core\FEEDBACK_TYPE_TRADER;
use const ougc\Feedback\Core\POST_VISIBILITY_APPROVED;
use const ougc\Feedback\Core\POST_VISIBILITY_SOFT_DELETED;
use const ougc\Feedback\Core\POST_VISIBILITY_UNAPPROVED;

const IN_MYBB = 1;

const THIS_SCRIPT = 'feedback.php';

$templatelist = 'ougcfeedback_' . implode(',ougcfeedback_', [
        'form',
        'form_comment',
        'form_rating',
        'modal',
        'modal_error',
        'modalFooter',
        'modalFooterAdd',
        'modalFooterEdit.html',
        'page',
        'page_addlink',
        'page_empty',
        'page_item',
        'page_item_delete',
        'page_item_delete_hard',
        'page_item_edit',
        'page_item_rating',
        'page_item_report',
        'page_item_restore.html'
    ]);

require_once './global.php';

global $mybb, $lang, $db, $templates, $cache;

loadLanguage();

$currentUserID = (int)$mybb->user['uid'];

if ($mybb->get_input('action') === 'add' || $mybb->get_input('action') === 'edit') {
    $edit = $mybb->get_input('action') == 'edit';

    $feedbackProcessed = false;

    $hookArguments = [
        'feedbackProcessed' => &$feedbackProcessed,
        'feedbackCode' => &$feedbackCode,
    ];

    $mybb->input['reload'] = $mybb->get_input('reload', MyBB::INPUT_INT);

    $feedbackID = 0;

    $userID = $mybb->get_input('userID', MyBB::INPUT_INT);

    $uniqueID = $mybb->get_input('uniqueID', MyBB::INPUT_INT);

    $feedbackCode = $mybb->get_input('feedbackCode', MyBB::INPUT_INT);

    $feedbackUserID = $currentUserID;

    $feedbackValue = 0;

    $feedbackFields = [
        'feedbackComment',
        'feedbackStatus',
        'feedbackType',
        'userID',
        'feedbackUserID',
        'uniqueID',
        'feedbackCode',
        'feedbackValue'
    ];

    foreach (ratingGet() as $ratingID => $ratingData) {
        $feedbackFields[] = 'ratingID' . $ratingID;
    }

    if ($edit) {
        $feedbackID = $mybb->get_input('feedbackID', MyBB::INPUT_INT);

        if (!($feedbackData = feedbackGet(["feedbackID='{$feedbackID}'"], $feedbackFields, ['limit' => 1]))) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_invalid_feedback_value);
        }

        $uniqueID = (int)$feedbackData['uniqueID'];

        $feedbackValue = (int)$feedbackData['feedbackValue'];

        $feedbackCode = (int)$feedbackData['feedbackCode'];

        $method = "DoEdit('{$feedbackData['userID']}', '{$uniqueID}', '{$feedbackID}')";

        $mybb->input['reload'] = 1;

        $userID = (int)$feedbackData['userID'];

        $feedbackUserID = (int)$feedbackData['feedbackUserID'];

        if (!$currentUserID) {
            backButtonSet(false);

            trowError($lang->error_nopermission_user_ajax);
        }

        $feedbackData['feedbackType'] = $feedbackType = (int)$feedbackData['feedbackType'];

        if ($mybb->request_method == 'post') {
            $feedbackData = array_merge($feedbackData, $mybb->input);
        }
    } else {
        $feedbackData = [
            'userID' => $userID,
            'feedbackUserID' => $feedbackUserID,
            'uniqueID' => $uniqueID,
            'feedbackCode' => $feedbackCode
        ];

        foreach (ratingGet() as $ratingID => $ratingData) {
            $feedbackData['ratingID' . $ratingID] = 0;
        }

        $method = "DoAdd('{$feedbackData['userID']}', '{$uniqueID}')";

        $feedbackData['feedbackType'] = $feedbackType = $mybb->get_input('feedbackType', MyBB::INPUT_INT);
    }

    switch ($feedbackCode) {
        case FEEDBACK_TYPE_PROFILE:
            $uniqueID = 0;
            break;
        case FEEDBACK_TYPE_POST:
            //$uniqueID = 1; // todo, seems like a placeholder
            break;
    }

    $feedbackComment = $feedbackData['feedbackComment'];

    if (!$edit || $mybb->request_method == 'post' || $mybb->get_input('back_button', MyBB::INPUT_INT)) {
        $feedbackType = $mybb->get_input('feedbackType', MyBB::INPUT_INT);

        $feedbackValue = $mybb->get_input('feedbackValue', MyBB::INPUT_INT);

        $feedbackComment = $mybb->get_input('feedbackComment');

        foreach (ratingGet() as $ratingID => $ratingData) {
            $feedbackData['ratingID' . $ratingID] = $mybb->get_input('ratingID' . $ratingID, MyBB::INPUT_INT);
        }
    }

    $hookArguments['feedbackData'] = &$feedbackData;

    $type_selected_buyer = $type_selected_seller = $type_selected_trader = '';

    switch ($feedbackType) {
        case FEEDBACK_TYPE_BUYER:
            $type_selected_buyer = ' selected="selected"';
            break;
        case FEEDBACK_TYPE_SELLER:
            $type_selected_seller = ' selected="selected"';
            break;
        case FEEDBACK_TYPE_TRADER:
            $type_selected_trader = ' selected="selected"';
            break;
    }

    $feedback_selected_positive = $feedback_selected_neutral = $feedback_selected_negative = '';

    switch ($feedbackValue) {
        case FEEDBACK_VALUE_POSITIVE:
            $feedback_selected_positive = ' selected="selected"';
            break;
        case FEEDBACK_VALUE_NEUTRAL:
            $feedback_selected_neutral = ' selected="selected"';
            break;
        case FEEDBACK_VALUE_NEGATIVE:
            $feedback_selected_negative = ' selected="selected"';
            break;
    }

    if (!($userData = get_user($userID))) {
        backButtonSet(false);

        trowError($lang->ougc_feedback_error_invalid_user);
    }

    $userName = htmlspecialchars_uni($userData['username']);

    $userPermissions = usergroup_permissions($userData['usergroup'] . ',' . $userData['additionalgroups']);

    if ($edit) {
        if (!($mybb->usergroup['ougc_feedback_canedit'] && $currentUserID == $feedbackUserID) &&
            !(isModerator() && $mybb->usergroup['ougc_feedback_mod_canedit'])) {
            backButtonSet(false);

            trowError($lang->error_nopermission_user_ajax);
        }
    } else {
        if (empty($mybb->usergroup['ougc_feedback_cangive']) || empty($userPermissions['ougc_feedback_canreceive'])) {
            backButtonSet(false);

            trowError($lang->error_nopermission_user_ajax);
        }

        if ($userData['uid'] == $currentUserID) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_invalid_self_user);
        }

        if (!in_array($feedbackType, [FEEDBACK_TYPE_BUYER, FEEDBACK_TYPE_SELLER, FEEDBACK_TYPE_TRADER])) {
            trowError($lang->ougc_feedback_error_invalid_type);
        }

        if (!in_array(
            $feedbackValue,
            [FEEDBACK_VALUE_NEGATIVE, FEEDBACK_VALUE_NEUTRAL, FEEDBACK_VALUE_POSITIVE]
        )) {
            trowError($lang->ougc_feedback_error_invalid_feedback_value);
        }

        if (!in_array(
            $feedbackData['feedbackStatus'],
            [FEEDBACK_STATUS_SOFT_DELETED, FEEDBACK_STATUS_UNAPPROVED, FEEDBACK_STATUS_ACTIVE]
        )) {
            trowError($lang->ougc_feedback_error_invalid_status);
        }

        if ($mybb->usergroup['ougc_feedback_maxperday']) {
            $timesearch = TIME_NOW - (60 * 60 * 24);

            $query = $db->simple_select(
                'ougc_feedback',
                'COUNT(feedbackID) AS feedbacks',
                "feedbackUserID='{$currentUserID}' AND createStamp>'{$timesearch}'"
            );

            $numtoday = $db->fetch_field($query, 'feedbacks');

            if ($numtoday >= $mybb->usergroup['ougc_feedback_maxperday']) {
                backButtonSet(false);

                trowError($lang->ougc_feedback_error_invalid_maxperday);
            }
        }
    }

    $hide_add = 0;

    $hookArguments = runHooks('add_edit_intermediate', $hookArguments);

    if (!$feedbackProcessed && $feedbackCode === FEEDBACK_TYPE_POST) {
        if (!($post = get_post($uniqueID))) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_invalid_post);
        }

        if ($post['uid'] != $userID) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_invalid_post);
        }

        $postVisibleStatus = (int)$post['visible'];

        if (($postVisibleStatus === POST_VISIBILITY_UNAPPROVED && !is_moderator($post['fid'], 'canviewunapprove')) ||
            ($postVisibleStatus === POST_VISIBILITY_SOFT_DELETED && !is_moderator($post['fid'], 'canviewdeleted'))) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_invalid_post);
        }

        $forum = get_forum($post['fid']);

        if (!($thread = get_thread($post['tid'])) || empty($forum['fid'])) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_invalid_post);
        }

        if (!$forum['ougc_feedback_allow_threads'] && !$forum['ougc_feedback_allow_posts']) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_invalid_post);
        }

        if ($forum['ougc_feedback_allow_threads'] && !$forum['ougc_feedback_allow_posts'] && $thread['firstpost'] != $post['pid']) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_invalid_post);
        }

        if (!$forum['ougc_feedback_allow_threads'] && $forum['ougc_feedback_allow_posts'] && $thread['firstpost'] == $post['pid']) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_invalid_post);
        }

        if (substr($thread['closed'], 0, 6) == 'moved|' || $forum['type'] != 'f') {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_invalid_post);
        }

        $threadVisibleStatus = (int)$thread['visible'];

        if (($threadVisibleStatus !== POST_VISIBILITY_APPROVED && !is_moderator($post['fid'])) ||
            ($threadVisibleStatus === POST_VISIBILITY_UNAPPROVED && !is_moderator($post['fid'], 'canviewunapprove')) ||
            ($threadVisibleStatus === POST_VISIBILITY_SOFT_DELETED && !is_moderator($post['fid'], 'canviewdeleted'))) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_invalid_post);
        }

        $forumPermissions = forum_permissions($post['fid']);

        if (!$forumPermissions['canview'] || !$forumPermissions['canviewthreads']) {
            backButtonSet(false);

            trowError($lang->error_nopermission_user_ajax);
        }

        if (isset($forumPermissions['canonlyviewownthreads']) && $forumPermissions['canonlyviewownthreads']) {
            backButtonSet(false);

            trowError($lang->error_nopermission_user_ajax);
        }

        check_forum_password($post['fid']);

        $where = [
            "userID='{$userID}'", /*"feedbackUserID!='0'", */
            "feedbackUserID='{$feedbackUserID}'",
            "uniqueID='{$uniqueID}'",
            "feedbackStatus='1'"
        ];

        if (feedbackGet($where, [], ['limit' => 1])) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_post_multiple_disabled);
        }

        if ($mybb->settings['ougc_feedback_postbit_hide_button']) {
            $hide_add = 1;
        }

        $feedbackProcessed = true;
    } elseif (!$feedbackProcessed && $feedbackCode === FEEDBACK_TYPE_PROFILE) {
        if (empty($mybb->settings['ougc_feedback_allow_profile'])) {
            backButtonSet(false);

            trowError($lang->ougc_feedback_error_profile_disabled);
        }

        if ($mybb->get_input('action') !== 'edit' && empty($mybb->settings['ougc_feedback_allow_profile_multiple'])) {
            $where = [
                "userID='{$userID}'", /*"feedbackUserID!='0'", */
                "feedbackUserID='{$feedbackUserID}'",
                "uniqueID='{$uniqueID}'",
                "feedbackCode='{$feedbackCode}'",
            ];

            if (!isModerator()) {
                $where[] = "feedbackStatus='1'";
            }

            if (feedbackGet($where, [], ['limit' => 1])) {
                backButtonSet(false);

                trowError($lang->ougc_feedback_error_profile_multiple_disabled);
            }

            $hide_add = (int)$mybb->settings['ougc_feedback_profile_hide_add'];
        }

        $feedbackProcessed = true;
    }

    $comments_minlength = (int)$mybb->settings['ougc_feedback_comments_minlength'];

    $comments_maxlength = (int)$mybb->settings['ougc_feedback_comments_maxlength'];

    if ($mybb->request_method == 'post') {
        verify_post_check($mybb->get_input('my_post_key'));

        $message_count = my_strlen($feedbackComment);

        if ($comments_maxlength < 1) {
            $feedbackComment = '';
        } elseif ($message_count < $comments_minlength || $message_count > $comments_maxlength) {
            backButtonSet();

            trowError(
                $lang->sprintf(
                    $lang->ougc_feedback_error_invalid_comment,
                    $comments_minlength,
                    $comments_maxlength,
                    $message_count
                )
            );
        }

        $hookArguments = runHooks('add_edit_do_start', $hookArguments);

        if ($feedbackProcessed) {
            $insertData = [
                'feedbackComment' => $feedbackComment,
                'feedbackStatus' => FEEDBACK_STATUS_ACTIVE,
                'feedbackType' => $feedbackType,
                'userID' => $userID,
                'feedbackUserID' => $feedbackUserID,
                'uniqueID' => $uniqueID,
                'feedbackCode' => $feedbackCode,
                'feedbackValue' => $feedbackValue
            ];

            foreach (
                ratingGet(
                    [],
                    ['feedbackCode', 'allowedGroups']
                ) as $ratingID => $ratingData
            ) {
                if ((int)$ratingData['feedbackCode'] !== $feedbackCode ||
                    !is_member($ratingData['allowedGroups'])) {
                    continue;
                }

                if (isset($mybb->input['ratingID' . $ratingID])) {
                    $insertData['ratingID' . $ratingID] = $mybb->get_input('ratingID' . $ratingID, MyBB::INPUT_INT);
                }
            }

            if ($edit) {
                feedbackUpdate($insertData, $feedbackID);

                feedbackUserSync($userID);

                feedbackUserSync($feedbackUserID);

                trowSuccess($lang->ougc_feedback_success_feedback_edited);
            } else {
                $feedbackID = feedbackInsert($insertData);

                feedbackUserSync($userID);

                feedbackUserSync($feedbackUserID);

                /*if(strpos(','.$userData['ougc_feedback_notification'].',', ',1,'))
                {*/
                sendPrivateMessage([
                    'subject' => $lang->ougc_feedback_notification_pm_subject,
                    'message' => $lang->sprintf(
                        $lang->ougc_feedback_notification_pm_message,
                        $userName,
                        $mybb->settings['bbname']
                    ),
                    'touid' => $userID
                ], (int)$mybb->settings['ougc_feedback_pm_user_id'], true);
                /*}*/

                /*if(strpos(','.$userData['ougc_feedback_notification'].',', ',`2,'))
                {*/
                sendEmail([
                    'to' => $userData['email'],
                    'subject' => 'ougc_feedback_notification_mail_subject',
                    'message' => [
                        'ougc_feedback_notification_mail_message',
                        $userName,
                        $mybb->settings['bbname']
                    ],
                    'from' => $mybb->settings['adminemail'],
                    'touid' => $userData['uid'],
                    'language' => $userData['language']
                ]);
                /*}*/

                /*if(strpos(','.$userData['ougc_feedback_notification'].',', ',`3,'))
                {
                    \ougc\Feedback\Core\send_alert(array());
                }*/

                if ($uniqueID && $feedbackCode === FEEDBACK_TYPE_POST) {
                    postbit($post);
                    $replacement = $post['ougc_feedback'];
                } else {
                    $memprofile = &$userData;

                    $replacement = member_profile_end();
                }

                trowSuccess(
                    $lang->ougc_feedback_success_feedback_added,
                    '',
                    $replacement,
                    $hide_add
                );
            }
        } else {
            trowError($lang->ougc_feedback_error_unknown);
        }
    }

    $alternativeBackground = alt_trow(true);

    if ($comments_maxlength > 0) {
        $feedbackComment = htmlspecialchars_uni($feedbackComment);

        $comment_row = eval(getTemplate('form_comment'));

        $alternativeBackground = alt_trow();
    }

    $ratingObjects = ratingGet(
        [],
        ['feedbackCode', 'allowedGroups', 'ratingName', 'ratingDescription', 'ratingClass', 'ratingMaximumValue']
    );

    $ratingRows = '';

    foreach ($ratingObjects as $ratingID => $ratingData) {
        if ((int)$ratingData['feedbackCode'] !== $feedbackCode ||
            !is_member($ratingData['allowedGroups'])) {
            continue;
        }

        $ratingName = htmlspecialchars_uni($ratingData['ratingName']);

        $ratingNameText = $lang->sprintf(
            $lang->ougc_feedback_modal_rating,
            $ratingName
        );

        $ratingDescription = htmlspecialchars_uni($ratingData['ratingDescription']);

        $ratingClass = htmlspecialchars_uni($ratingData['ratingClass']);

        $ratingMaximumValue = max(1, min(5, (int)$ratingData['ratingMaximumValue']));

        $ratingValue = (int)$feedbackData['ratingID' . $ratingID];

        $ratingRows .= eval(getTemplate('form_rating'));

        $alternativeBackground = alt_trow();
    }

    if ($edit) {
        $lang->ougc_feedback_profile_add = $lang->ougc_feedback_profile_edit;
    }

    if ($edit) {
        $modalTitle = $lang->sprintf(
            $lang->ougcFeedbackModalTitleProfileUpdate,
            htmlspecialchars_uni($userName)
        );
    } else {
        $modalTitle = $lang->sprintf(
            $lang->ougcFeedbackModalTitleProfileAdd,
            htmlspecialchars_uni($userName)
        );
    }

    $formUrl = urlHandlerGet();

    echo eval(getTemplate('form', false));

    exit;
} elseif ($mybb->get_input('action') == 'delete') {
    verify_post_check($mybb->get_input('my_post_key'));

    if (!$currentUserID) {
        error_no_permission();
    }

    $feedbackID = $mybb->get_input('feedbackID', MyBB::INPUT_INT);

    $feedbackFields = [
        'userID',
        'feedbackUserID'
    ];

    if (!($feedbackData = feedbackGet(["feedbackID='{$feedbackID}'"], $feedbackFields, ['limit' => 1]))) {
        error($lang->ougc_feedback_error_invalid_feedback);
    }

    if (!($mybb->usergroup['ougc_feedback_canremove'] && $currentUserID == $feedbackData['feedbackUserID']) && !(isModerator(
            ) && $mybb->usergroup['ougc_feedback_mod_canremove'])) {
        error_no_permission();
    }

    if ($mybb->get_input('hard', MyBB::INPUT_INT)) {
        feedbackDelete((int)$feedbackData['feedbackID']);

        feedbackUserSync($feedbackData['userID']);

        $lang_string = 'ougc_feedback_redirect_removed';
    } else {
        $lang_string = 'ougc_feedback_redirect_removed';

        feedbackUpdate([
            'feedbackID' => $feedbackData['feedbackID'],
            'feedbackStatus' => 0,
        ], (int)$feedbackData['feedbackID']);
    }

    feedbackUserSync($feedbackData['userID']);

    redirect(
        $mybb->settings['bburl'] . '/' . urlHandlerBuild(['userID' => $feedbackData['userID']]),
        $lang->{$lang_string}
    );
} elseif ($mybb->get_input('action') == 'restore') {
    verify_post_check($mybb->get_input('my_post_key'));

    if (!$currentUserID) {
        error_no_permission();
    }

    $feedbackID = $mybb->get_input('feedbackID', MyBB::INPUT_INT);

    $feedbackFields = [
        'userID',
        'feedbackUserID'
    ];

    if (!($feedbackData = feedbackGet(["feedbackID='{$feedbackID}'"], $feedbackFields, ['limit' => 1]))) {
        error($lang->ougc_feedback_error_invalid_feedback);
    }

    if (!($mybb->usergroup['ougc_feedback_canremove'] && $currentUserID == $feedbackData['feedbackUserID']) && !(isModerator(
            ) && $mybb->usergroup['ougc_feedback_mod_canremove'])) {
        error_no_permission();
    }

    feedbackUpdate([
        'feedbackID' => $feedbackData['feedbackID'],
        'feedbackStatus' => 1,
    ], (int)$feedbackData['feedbackID']);

    feedbackUserSync($feedbackData['userID']);

    redirect(
        $mybb->settings['bburl'] . '/' . urlHandlerBuild(['userID' => $feedbackData['userID']]),
        $lang->ougc_feedback_redirect_restored
    );
}

require_once MYBB_ROOT . 'inc/class_parser.php';

$parser = new postParser();

if (empty($mybb->usergroup['canviewprofiles']) || empty($mybb->usergroup['ougc_feedback_canview'])) {
    error_no_permission();
}

$userID = $mybb->get_input('userID', MyBB::INPUT_INT);

if (!($userData = get_user($userID))) {
    error($lang->ougc_feedback_error_invalid_user);
}

$userName = htmlspecialchars_uni($userData['username']);

$userID = (int)$userData['uid'];

$userName = htmlspecialchars_uni($userName);

$lang->ougc_feedback_page_profile = $lang->sprintf($lang->ougc_feedback_page_profile, $userName);

$lang->ougc_feedback_page_report_for = $lang->sprintf($lang->ougc_feedback_page_report_for, $userName);

add_breadcrumb($lang->ougc_feedback_page_profile, get_profile_link($userID));

add_breadcrumb($lang->ougc_feedback_page_title);

$username = format_name($userName, $userData['usergroup'], $userData['displaygroup']);

$userData['displaygroup'] = $userData['displaygroup'] ?: $userData['usergroup'];

$display_group = usergroup_displaygroup($userData['displaygroup']);

$usertitle = '';

if (trim($userData['usertitle'])) {
    $usertitle = $userData['usertitle'];
} elseif (!empty($display_group['usertitle'])) {
    $usertitle = $display_group['usertitle'];
} else {
    $usertitles = $cache->read('usertitles');

    foreach ($usertitles as $title) {
        if ($title['posts'] <= $userData['postnum']) {
            $usertitle = $title['title'];
        }
    }

    unset($usertitles, $title);
}

$usertitle = htmlspecialchars_uni($usertitle);

$where = ["f.userID='{$userID}'"];

if (!isModerator()) {
    $where[] = "f.feedbackStatus='1'";
}

$url_params = ['userID' => $userID];

$show_selected_all = $show_selected_positive = $show_selected_neutral = $show_selected_negative = $show_selected_given = '';

switch ($mybb->get_input('show')) {
    case 'positive':
        $url_params['show'] = 'positive';

        $where[] = "f.feedbackValue='1'";

        $show_selected_positive = ' selected="selected"';
        break;
    case 'neutral':
        $url_params['show'] = 'neutral';

        $where[] = "f.feedbackValue='0'";

        $show_selected_neutral = ' selected="selected"';
        break;
    case 'negative':
        $url_params['show'] = 'negative';

        $where[] = "f.feedbackValue='-1'";

        $show_selected_negative = ' selected="selected"';
        break;
    case 'gived':
        $url_params['show'] = 'negative';

        $where[] = "f.feedbackUserID='{$userID}'";

        unset($where[0]);

        $show_selected_given = ' selected="selected"';
        break;
    default:
        $url_params['show'] = 'all';

        $show_selected_all = ' selected="selected"';
        break;
}

if ($mybb->get_input('feedbackID', MyBB::INPUT_INT)) {
    $url_params['feedbackID'] = $mybb->get_input('feedbackID', MyBB::INPUT_INT);
}

$sort_selected_username = $sort_selected_last_updated = '';

switch ($mybb->get_input('sort')) {
    case 'username':
        $url_params['sort'] = 'username';

        $order = 'u.username ASC, f.createStamp DESC';

        $sort_selected_username = ' selected="selected"';
        break;
    default:
        $url_params['sort'] = 'createStamp';

        $order = 'f.createStamp DESC, u.username ASC';

        $sort_selected_last_updated = ' selected="selected"';
        break;
}

$query = $db->simple_select(
    'ougc_feedback',
    'SUM(feedbackValue) AS totalFeedback, COUNT(feedbackID) AS totalFeedbackCount',
    "userID='{$userID}' AND feedbackStatus='1'"
);

$feedbackData = $db->fetch_array($query);

$sync_feedback = (int)$feedbackData['totalFeedback'];

$total_feedback = (int)$feedbackData['totalFeedbackCount'];

if ($sync_feedback != $userData['ougc_feedback']) {
    feedbackUserSync($userID);
}

$statsData = [];

$where_stats = array_merge($where, ["f.feedbackStatus='1'"]);

$query = $db->simple_select(
    'ougc_feedback f',
    'COUNT(f.feedbackID) AS totalFeedbackCount',
    implode(' AND ', $where_stats)
);

$statsData['total'] = $db->fetch_field($query, 'totalFeedbackCount');

$feedbackPostCode = FEEDBACK_TYPE_POST;

$query = $db->simple_select(
    'ougc_feedback f',
    'COUNT(f.feedbackID) AS total_posts_feedback',
    implode(' AND ', array_merge($where_stats, ["feedbackCode='{$feedbackPostCode}'"]))
);

$statsData['posts'] = $db->fetch_field($query, 'total_posts_feedback');

$statsData['members'] = ($statsData['total'] - $statsData['posts']);

$statsData = array_map('my_number_format', $statsData);

$positive_count = $negative_count = $neutral_count = 0;

$positive_week = $negative_week = $neutral_week = 0;

$positive_month = $negative_month = $neutral_month = 0;

$positive_6months = $negative_6months = $neutral_6months = 0;

$last_week = TIME_NOW - 604800;

$last_month = TIME_NOW - 2678400;

$last_6months = TIME_NOW - 16070400;

// Query reputations for the "reputation card" table
foreach (
    feedbackGet(
        array_map(function (string $whereClause): string {
            return str_replace('f.', '', $whereClause);
        }, $where_stats),
        ['feedbackValue, createStamp']
    ) as $feedbackData
) {
    $feedbackValue = (int)$feedbackData['feedbackValue'];

    switch ($feedbackValue) {
        case FEEDBACK_VALUE_NEGATIVE:
            $negative_count++;

            if ($feedbackData['createStamp'] >= $last_week) {
                $negative_week++;
            }
            if ($feedbackData['createStamp'] >= $last_month) {
                $negative_month++;
            }
            if ($feedbackData['createStamp'] >= $last_6months) {
                $negative_6months++;
            }
            break;
        case FEEDBACK_VALUE_NEUTRAL:
            $neutral_count++;

            if ($feedbackData['createStamp'] >= $last_week) {
                $neutral_week++;
            }
            if ($feedbackData['createStamp'] >= $last_month) {
                $neutral_month++;
            }
            if ($feedbackData['createStamp'] >= $last_6months) {
                $neutral_6months++;
            }
            break;
        case FEEDBACK_VALUE_POSITIVE:
            $positive_count++;

            if ($feedbackData['createStamp'] >= $last_week) {
                $positive_week++;
            }
            if ($feedbackData['createStamp'] >= $last_month) {
                $positive_month++;
            }
            if ($feedbackData['createStamp'] >= $last_6months) {
                $positive_6months++;
            }
            break;
    }
}

$feedback_count = feedbackGet(
    array_map(function (string $whereClause): string {
        return str_replace('f.', '', $whereClause);
    }, $where),
    ['COUNT(feedbackID) AS feedback_count'],
    ['limit' => 1]
)['feedback_count'] ?? 0;

$perpage = (int)$mybb->settings['ougc_feedback_perpage'];

if ($mybb->get_input('page', MyBB::INPUT_INT) > 0) {
    $page = $mybb->get_input('page', MyBB::INPUT_INT);

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
    urlHandlerBuild($url_params)
) : '';

$query = $db->simple_select(
    "ougc_feedback f LEFT JOIN {$db->table_prefix}users u ON (u.uid=f.feedbackUserID)",
    'f.*, u.username AS user_username, u.usergroup AS user_usergroup, u.displaygroup AS user_displaygroup',
    implode(' AND ', $where),
    [
        'limit_start' => $start,
        'limit' => $perpage,
        'order_by' => $order
    ]
);

$feedback_cache = $post_cache = $post_feedback = [];

while ($feedbackData = $db->fetch_array($query)) {
    $feedback_cache[] = $feedbackData;

    $uniqueID = (int)$feedbackData['uniqueID'];

    if ($uniqueID && !isset($post_cache[$uniqueID])) {
        $post_cache[$uniqueID] = $uniqueID;
    }
}

$post_reputation = [];

if (!empty($post_cache)) {
    $postIDs = implode(',', $post_cache);

    $where_post = ["p.pid IN ({$postIDs})"];

    if ($unviewable = get_unviewable_forums(true)) {
        $where_post[] = "p.fid NOT IN ({$unviewable})";
    }

    if ($inactive = get_inactive_forums()) {
        $where_post[] = "p.fid NOT IN ({$inactive})";
    }

    if (!is_moderator(0, '', $currentUserID)) {
        $where_post[] = "p.visible='1'";

        $where_post[] = "t.visible='1'";
    }

    $query = $db->simple_select(
        "posts p LEFT JOIN {$db->table_prefix}threads t ON (t.tid=p.tid)",
        'p.pid, p.uid, p.fid, p.visible, p.message, t.tid, t.subject, t.visible AS thread_visible',
        implode(' AND ', $where_post)
    );

    while ($post = $db->fetch_array($query)) {
        $postVisibleStatus = (int)$post['visible'];

        $threadVisibleStatus = (int)$post['thread_visible'];

        if (($postVisibleStatus === POST_VISIBILITY_UNAPPROVED || $threadVisibleStatus === POST_VISIBILITY_UNAPPROVED) &&
            !is_moderator($post['fid'], 'canviewunapprove')) {
            continue;
        }

        if (($postVisibleStatus === POST_VISIBILITY_SOFT_DELETED || $threadVisibleStatus === POST_VISIBILITY_SOFT_DELETED) &&
            !is_moderator($post['fid'], 'canviewdeleted')) {
            continue;
        }

        $post_reputation[$post['pid']] = $post;
    }
}

$alternativeBackground = alt_trow(true);

$feedback_list = '';

foreach ($feedback_cache as $feedbackData) {
    $uniqueID = (int)$feedbackData['uniqueID'];

    $feedbackID = (int)$feedbackData['feedbackID'];

    $feedbackGivenDescription = '';

    $feedbackCode = (int)$feedbackData['feedbackCode'];

    switch ($feedbackCode) {
        case FEEDBACK_TYPE_POST:
            if (!empty($uniqueID)) {
                $feedbackGivenDescription = $lang->sprintf(
                    $lang->ougc_feedback_page_post_nolink,
                    $userName
                );

                if (isset($post_reputation[$uniqueID])) {
                    $post = $post_reputation[$uniqueID];

                    $thread_link = get_thread_link($post['tid']);

                    $subject = htmlspecialchars_uni($parser->parse_badwords($post['subject']));

                    $thread_link = $lang->sprintf(
                        $lang->ougc_feedback_page_post_given_thread,
                        $thread_link,
                        $subject,
                        $mybb->settings['bburl']
                    );

                    $link = get_post_link($uniqueID) . '#pid' . $uniqueID;

                    $feedbackGivenDescription = $lang->sprintf(
                        $lang->ougc_feedback_page_post_given,
                        $link,
                        $userName,
                        $thread_link,
                        $mybb->settings['bburl']
                    );
                }
            }

            break;
        case FEEDBACK_TYPE_PROFILE:
            $feedbackGivenDescription = $lang->ougc_feedback_page_given_profile;
            break;
    }

    runHooks('page_feedback_start');

    $feedbackType = (int)$feedbackData['feedbackType'];

    $voteType = '';

    switch ($feedbackType) {
        case FEEDBACK_TYPE_BUYER:
            $voteType .= $lang->ougc_feedback_page_type_buyer;
            break;
        case FEEDBACK_TYPE_SELLER:
            $voteType .= $lang->ougc_feedback_page_type_seller;
            break;
        case FEEDBACK_TYPE_TRADER:
            $voteType .= $lang->ougc_feedback_page_type_trader;
            break;
    }

    $feedbackValue = (int)$feedbackData['feedbackValue'];

    switch ($feedbackValue) {
        case FEEDBACK_VALUE_NEGATIVE:
            $stylingClasses = ['status' => 'trow_reputation_negative', 'type' => 'reputation_negative'];
            $voteType .= $lang->ougc_feedback_profile_negative;
            break;
        case FEEDBACK_VALUE_NEUTRAL:
            $stylingClasses = ['status' => 'trow_reputation_neutral', 'type' => 'reputation_neutral'];
            $voteType .= $lang->ougc_feedback_profile_neutral;
            break;
        case FEEDBACK_VALUE_POSITIVE:
            $stylingClasses = ['status' => 'trow_reputation_positive', 'type' => 'reputation_positive'];
            $voteType .= $lang->ougc_feedback_profile_positive;
            break;
    }

    if ($feedbackData['feedbackStatus'] != 1) {
        //$stylingClasses = array('status' => '" style="background-color: #E8DEFF;');
    }

    if (!$feedbackData['feedbackStatus']) {
        $stylingClasses['status'] = 'trow_shaded trow_deleted forumdisplay_regular';
    }

    if ($feedbackID === $mybb->get_input('feedbackID', MyBB::INPUT_INT)) {
        $stylingClasses['status'] = 'inline_row trow_selected';
    }

    $last_updated_date = my_date('relative', $feedbackData['createStamp']);

    $last_updated = $lang->sprintf($lang->ougc_feedback_page_last_updated, $last_updated_date);

    if (!$feedbackData['feedbackUserID']) {
        $feedbackData['user_username'] = $lang->guest;
    } elseif (!$feedbackData['user_username']) {
        $feedbackData['user_username'] = $lang->na;
    } else {
        $feedbackData['user_username'] = format_name(
            htmlspecialchars_uni($feedbackData['user_username']),
            $feedbackData['user_usergroup'],
            $feedbackData['user_displaygroup']
        );

        $feedbackData['user_username'] = build_profile_link(
            $feedbackData['user_username'],
            $feedbackData['feedbackUserID']
        );
    }

    $feedbackComment = $feedbackData['feedbackComment'];

    if ($feedbackComment) {
        $parser_options = [
            'allow_html' => 0,
            'allow_mycode' => 0,
            'allow_smilies' => 1,
            'allow_imgcode' => 0,
            'filter_badwords' => 1,
        ];

        $feedbackComment = $parser->parse_message($feedbackComment, $parser_options);
    } else {
        $feedbackComment = $lang->ougc_feedback_no_comment;
    }

    $edit_link = $delete_link = $delete_hard_link = $report_link = '';

    if ($currentUserID && (($feedbackData['feedbackUserID'] == $currentUserID && $mybb->usergroup['ougc_feedback_canedit']) || (isModerator(
                ) && $mybb->usergroup['ougc_feedback_canedit']))) {
        $edit_link = eval(getTemplate('page_item_edit'));
    }

    if ($currentUserID && (($feedbackData['feedbackUserID'] == $currentUserID && $mybb->usergroup['ougc_feedback_canremove']) || (isModerator(
                ) && $mybb->usergroup['ougc_feedback_mod_canremove']))) {
        if (!$feedbackData['feedbackStatus']) {
            $delete_link = eval(getTemplate('page_item_restore'));
        } else {
            $delete_link = eval(getTemplate('page_item_delete'));
        }
    }

    if ($currentUserID && isModerator() && $mybb->usergroup['ougc_feedback_mod_candelete']) {
        $delete_hard_link = eval(getTemplate('page_item_delete_hard'));
    }

    if ($currentUserID) {
        $report_link = eval(getTemplate('page_item_report'));
    }

    $ratingRows = '';

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

        $ratingRows .= eval(getTemplate('page_item_rating'));
    }

    $feedback_list .= eval(getTemplate('page_item'));

    $alternativeBackground = alt_trow();
}

if (!$feedback_list) {
    $feedback_list = eval(getTemplate('page_empty'));
}

$add_feedback = '';

$userPermissions = usergroup_permissions($userData['usergroup'] . ',' . $userData['additionalgroups']);

if ($mybb->settings['ougc_feedback_allow_profile'] && $mybb->usergroup['ougc_feedback_cangive'] && $userPermissions['ougc_feedback_canreceive'] && $currentUserID !== $userID) {
    $show = true;

    if (empty($mybb->settings['ougc_feedback_allow_profile_multiple']) && !empty($mybb->settings['ougc_feedback_profile_hide_add'])) {
        $where = [
            "userID='{$userID}'", /*"feedbackUserID!='0'", */
            "feedbackUserID='{$currentUserID}'"
        ];

        if (!isModerator()) {
            $where[] = "feedbackStatus='1'";
        }

        if (feedbackGet($where, [], ['limit' => 1])) {
            $show = false;
        }
    }

    $feedbackCode = FEEDBACK_TYPE_PROFILE;

    if ($show) {
        $add_feedback = eval(getTemplate('page_addlink'));
    }
}

if ($userData['ougc_feedback'] < 0) {
    $total_class = '_negative';
} elseif ($userData['ougc_feedback'] > 0) {
    $total_class = '_positive';
} else {
    $total_class = '_neutral';
}

$formUrl = urlHandlerGet();

$page = eval(getTemplate('page'));

output_page($page);