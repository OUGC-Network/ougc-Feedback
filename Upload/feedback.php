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

use ougc\Feedback\Core\enums;

use function ougc\Feedback\Core\default_status;
use function ougc\Feedback\Core\delete_feedback;
use function ougc\Feedback\Core\fetch_feedback;
use function ougc\Feedback\Core\getTemplate;
use function ougc\Feedback\Core\insert_feedback;
use function ougc\Feedback\Core\isModerator;
use function ougc\Feedback\Core\loadLanguage;
use function ougc\Feedback\Core\ratingGet;
use function ougc\Feedback\Core\ratingInsert;
use function ougc\Feedback\Core\ratingSyncUser;
use function ougc\Feedback\Core\ratingUpdate;
use function ougc\Feedback\Core\run_hooks;
use function ougc\Feedback\Core\send_email;
use function ougc\Feedback\Core\set_data;
use function ougc\Feedback\Core\set_go_back_button;
use function ougc\Feedback\Core\sync_user;
use function ougc\Feedback\Core\trow_error;
use function ougc\Feedback\Core\trow_success;
use function ougc\Feedback\Core\update_feedback;
use function ougc\Feedback\Core\validate_feedback;
use function ougc\Feedback\Hooks\Forum\member_profile_end;
use function ougc\Feedback\Hooks\Forum\postbit;
use function NewPoints\ContractsSystem\Core\get_contract;

use const ougc\Feedback\Core\FEEDBACK_TYPE_CONTRACTS_SYSTEM;
use const ougc\Feedback\Core\FEEDBACK_TYPE_BUYER;
use const ougc\Feedback\Core\FEEDBACK_TYPE_NEGATIVE;
use const ougc\Feedback\Core\FEEDBACK_TYPE_NEUTRAL;
use const ougc\Feedback\Core\FEEDBACK_TYPE_POSITIVE;
use const ougc\Feedback\Core\FEEDBACK_TYPE_POST;
use const ougc\Feedback\Core\FEEDBACK_TYPE_PROFILE;
use const ougc\Feedback\Core\FEEDBACK_TYPE_SELLER;
use const ougc\Feedback\Core\FEEDBACK_TYPE_TRADER;
use const ougc\Feedback\Core\RATING_TYPES;

const IN_MYBB = 1;

const THIS_SCRIPT = 'feedback.php';

$templatelist = 'ougcfeedback_page_item_edit, ougcfeedback_page_item_delete, ougcfeedback_page_item_delete_hard, ougcfeedback_page_item_report, ougcfeedback_page_item, ougcfeedback_page_addlink, ougcfeedback_page';

require_once './global.php';

global $mybb, $lang, $db, $templates, $cache;
global $PL;

$mybb->input['feedbackID'] = $mybb->get_input('feedbackID', MyBB::INPUT_INT);

$PL || require_once PLUGINLIBRARY;

loadLanguage();

$currentUserID = (int)$mybb->user['uid'];

if ($mybb->get_input('action') === 'add' || $mybb->get_input('action') === 'edit') {
    $edit = $mybb->get_input('action') == 'edit';

    $feedback_code = $mybb->get_input('feedbackCode', MyBB::INPUT_INT);

    $processed = false;

    $hook_arguments = [
        'processed' => &$processed,
        'feedbackCode' => &$feedback_code,
    ];

    $mybb->input['reload'] = $mybb->get_input('reload', MyBB::INPUT_INT);

    if ($edit) {
        if (!($feedbackData = fetch_feedback($mybb->input['feedbackID']))) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_feedback_value);
        }

        $feedbackUniqueID = (int)$feedbackData['uniqueID'];

        $feedbackID = (int)$feedbackData['feedbackID'];

        $method = "DoEdit('{$feedbackData['userID']}', '{$feedbackUniqueID}', '{$feedbackID}')";

        $mybb->input['reload'] = 1;

        // users can add but they can't edit.
        if (!$currentUserID) {
            set_go_back_button(false);

            trow_error($lang->error_nopermission_user_ajax);
        }
    } else {
        $feedbackData = [
            'userID' => $mybb->get_input('userID', MyBB::INPUT_INT),
            'feedbackUserID' => $currentUserID,
            'uniqueID' => $mybb->get_input('uniqueID', MyBB::INPUT_INT),
            'feedbackStatus' => default_status(),
            'feedbackCode' => $feedback_code
        ];

        $feedbackUniqueID = (int)$feedbackData['uniqueID'];

        $feedbackID = 0;

        $method = "DoAdd('{$feedbackData['userID']}', '{$feedbackUniqueID}')";
    }

    $UserID = (int)$feedbackData['userID'];

    $feedbackUserID = (int)$feedbackData['feedbackUserID'];

    $feedbackUniqueID = (int)$feedbackData['uniqueID'];

    switch ($feedback_code) {
        case FEEDBACK_TYPE_POST:
            $feedbackUniqueID = 1;
            break;
        case FEEDBACK_TYPE_PROFILE:
            $feedbackUniqueID = 1;
            break;
    }

    if (!$edit || $mybb->request_method == 'post' || $mybb->get_input('back_button', MyBB::INPUT_INT)) {
        $feedbackData['feedbackType'] = $mybb->get_input('feedbackType', MyBB::INPUT_INT);

        $feedbackData['feedbackValue'] = $mybb->get_input('feedbackValue', MyBB::INPUT_INT);

        $feedbackData['feedbackComment'] = $mybb->get_input('feedbackComment');
    }

    $hook_arguments['feedback_data'] = &$feedbackData;

    // Set handler data
    set_data($feedbackData);

    $type_selected_buyer = $type_selected_seller = $type_selected_trader = '';

    switch ((int)$feedbackData['feedbackType']) {
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

    switch ((int)$feedbackData['feedbackValue']) {
        case FEEDBACK_TYPE_POSITIVE:
            $feedback_selected_positive = ' selected="selected"';
            break;
        case FEEDBACK_TYPE_NEUTRAL:
            $feedback_selected_neutral = ' selected="selected"';
            break;
        case FEEDBACK_TYPE_NEGATIVE:
            $feedback_selected_negative = ' selected="selected"';
            break;
    }

    if (!($userData = get_user($UserID))) {
        set_go_back_button(false);

        trow_error($lang->ougc_feedback_error_invalid_user);
    }

    $user_perms = usergroup_permissions($userData['usergroup'] . ',' . $userData['additionalgroups']);

    if ($edit) {
        if (!($mybb->usergroup['ougc_feedback_canedit'] && $currentUserID == $feedbackUserID) &&
            !(isModerator() && $mybb->usergroup['ougc_feedback_mod_canedit'])) {
            set_go_back_button(false);

            trow_error($lang->error_nopermission_user_ajax);
        }
    } else {
        if (!$mybb->usergroup['ougc_feedback_cangive'] || !$user_perms['ougc_feedback_canreceive']) {
            set_go_back_button(false);

            trow_error($lang->error_nopermission_user_ajax);
        }

        if ($userData['uid'] == $currentUserID) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_self_user);
        }

        if (!in_array($feedbackData['feedbackType'], [1, 2, 3])) {
            trow_error($lang->ougc_feedback_error_invalid_type);
        }

        if (!in_array(
            $feedbackData['feedbackValue'],
            [FEEDBACK_TYPE_NEGATIVE, FEEDBACK_TYPE_NEUTRAL, FEEDBACK_TYPE_POSITIVE]
        )) {
            trow_error($lang->ougc_feedback_error_invalid_feedback_value);
        }

        if (!in_array($feedbackData['feedbackStatus'], [-1, 0, 1])) {
            trow_error($lang->ougc_feedback_error_invalid_status);
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
                set_go_back_button(false);

                trow_error($lang->ougc_feedback_error_invalid_maxperday);
            }
        }
    }

    $hide_add = 0;

    $hook_arguments = run_hooks('add_edit_intermediate', $hook_arguments);

    set_data($feedbackData);

    if (!$processed && !$edit && $feedbackUniqueID) {
        $feedback_code = FEEDBACK_TYPE_POST;

        if (!($post = get_post($feedbackUniqueID))) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_post);
        }

        if ($post['uid'] != $UserID) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_post);
        }

        if (($post['visible'] == 0 && !is_moderator(
                    $post['fid'],
                    'canviewunapprove'
                )) || ($post['visible'] == -1 && !is_moderator($post['fid'], 'canviewdeleted'))) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_post);
        }

        if (!($thread = get_thread($post['tid'])) || !($forum = get_forum($post['fid']))) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_post);
        }

        if (!$forum['ougc_feedback_allow_threads'] && !$forum['ougc_feedback_allow_posts']) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_post);
        }

        if ($forum['ougc_feedback_allow_threads'] && !$forum['ougc_feedback_allow_posts'] && $thread['firstpost'] != $post['pid']) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_post);
        }

        if (!$forum['ougc_feedback_allow_threads'] && $forum['ougc_feedback_allow_posts'] && $thread['firstpost'] == $post['pid']) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_post);
        }

        if (substr($thread['closed'], 0, 6) == 'moved|' || $forum['type'] != 'f') {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_post);
        }

        if (($thread['visible'] != 1 && !is_moderator($post['fid'])) || ($thread['visible'] == 0 && !is_moderator(
                    $post['fid'],
                    'canviewunapprove'
                )) || ($thread['visible'] == -1 && !is_moderator($post['fid'], 'canviewdeleted'))) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_post);
        }

        $forumpermissions = forum_permissions($post['fid']);

        // Does the user have permission to view this thread?
        if (!$forumpermissions['canview'] || !$forumpermissions['canviewthreads']) {
            set_go_back_button(false);

            trow_error($lang->error_nopermission_user_ajax);
        }

        if (isset($forumpermissions['canonlyviewownthreads']) && $forumpermissions['canonlyviewownthreads']) {
            set_go_back_button(false);

            trow_error($lang->error_nopermission_user_ajax);
        }

        check_forum_password($post['fid']); // this should at least stop the script

        $where = [
            "userID='{$UserID}'", /*"feedbackUserID!='0'", */
            "feedbackUserID='{$feedbackUserID}'",
            "uniqueID='{$feedbackUniqueID}'",
            "feedbackStatus='1'"
        ];

        $query = $db->simple_select('ougc_feedback', 'feedbackID', implode(' AND ', $where));

        if ($db->num_rows($query)) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_post_multiple_disabled);
        }

        if ($mybb->settings['ougc_feedback_postbit_hide_button']) {
            $hide_add = 1;
        }

        $processed = true;
    } elseif (!$processed && !$edit) {
        $feedback_code = FEEDBACK_TYPE_PROFILE;

        if (!$mybb->settings['ougc_feedback_allow_profile']) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_profile_disabled);
        }

        if (!$mybb->settings['ougc_feedback_allow_profile_multiple']) {
            $where = [
                "userID='{$UserID}'", /*"feedbackUserID!='0'", */
                "feedbackUserID='{$feedbackUserID}'"
            ];

            if (!isModerator()) {
                $where[] = "feedbackStatus='1'";
            }

            $query = $db->simple_select('ougc_feedback', 'feedbackID', implode(' AND ', $where));

            if ($db->fetch_field($query, 'feedbackID')) {
                set_go_back_button(false);

                trow_error($lang->ougc_feedback_error_profile_multiple_disabled);
            }

            $hide_add = (int)$mybb->settings['ougc_feedback_profile_hide_add'];
        }

        $processed = true;
    }

    $comments_minlength = (int)$mybb->settings['ougc_feedback_comments_minlength'];

    $comments_maxlength = (int)$mybb->settings['ougc_feedback_comments_maxlength'];

    // POST request
    if ($mybb->request_method == 'post') {
        // Verify incoming POST request
        verify_post_check($mybb->get_input('my_post_key'));

        $message_count = my_strlen($feedbackData['feedbackComment']);

        if ($comments_maxlength < 1) {
            $feedbackData['feedbackComment'] = '';
        } elseif ($message_count < $comments_minlength || $message_count > $comments_maxlength) {
            set_go_back_button();

            trow_error(
                $lang->sprintf(
                    $lang->ougc_feedback_error_invalid_comment,
                    $comments_minlength,
                    $comments_maxlength,
                    $message_count
                )
            );
        }

        $hook_arguments = run_hooks('add_edit_do_start', $hook_arguments);

        if ($processed && validate_feedback()) {
            if ($edit) {
                // Insert feedback
                $feedbackID = update_feedback();

                $db->update_query(
                    'ougc_feedback_ratings',
                    ['feedbackID' => $feedbackID],
                    "ratedUserID='{$feedbackUserID}' AND uniqueID='{$feedbackUniqueID}' AND feedbackCode='{$feedback_code}'"
                );

                sync_user($UserID);

                foreach (RATING_TYPES as $ratingTypeID => $ratingTypeData) {
                    ratingSyncUser($UserID, $ratingTypeID);

                    ratingSyncUser($feedbackUserID, $ratingTypeID);
                }

                trow_success($lang->ougc_feedback_success_feedback_edited);
            } else {
                // Insert feedback
                $feedbackID = insert_feedback();

                $db->update_query(
                    'ougc_feedback_ratings',
                    ['feedbackID' => $feedbackID],
                    "userID='{$feedbackID}' AND ratedUserID='{$feedbackUserID}' AND uniqueID='{$feedbackUniqueID}' AND feedbackCode='{$feedback_code}'"
                );

                sync_user($UserID);

                foreach (RATING_TYPES as $ratingTypeID => $ratingTypeData) {
                    ratingSyncUser($UserID, $ratingTypeID);

                    ratingSyncUser($feedbackUserID, $ratingTypeID);
                }

                /*if(strpos(','.$userData['ougc_feedback_notification'].',', ',1,'))
                {*/
                \ougc\Feedback\Core\send_pm([
                    'subject' => $lang->ougc_feedback_notification_pm_subject,
                    'message' => $lang->sprintf(
                        $lang->ougc_feedback_notification_pm_message,
                        $userData['username'],
                        $mybb->settings['bbname']
                    ),
                    'touid' => $UserID
                ], (int)$mybb->settings['ougc_feedback_pm_user_id'], true);
                /*}*/

                /*if(strpos(','.$userData['ougc_feedback_notification'].',', ',`2,'))
                {*/
                send_email([
                    'to' => $userData['email'],
                    'subject' => 'ougc_feedback_notification_mail_subject',
                    'message' => [
                        'ougc_feedback_notification_mail_message',
                        $userData['username'],
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

                if ($feedbackUniqueID && $feedback_code === FEEDBACK_TYPE_POST) {
                    postbit($post);
                    $replacement = $post['ougc_feedback'];
                } else {
                    $memprofile = &$userData;

                    $replacement = member_profile_end();
                }

                trow_success(
                    $lang->ougc_feedback_success_feedback_added,
                    '',
                    $replacement,
                    $hide_add
                );
            }
        } else {
            trow_error($lang->ougc_feedback_error_unknown);
        }
    }

    $alternativeBackground = alt_trow(true);

    if ($comments_maxlength > 0) {
        $feedbackComment = htmlspecialchars_uni($mybb->input['feedbackComment']);

        $comment_row = eval(getTemplate('form_comment', false));

        $alternativeBackground = alt_trow();
    }

    $rating_rows = '';

    foreach (RATING_TYPES as $ratingTypeID => $ratingTypeData) {
        if ((int)$ratingTypeData['feedbackCode'] !== $feedback_code ||
            !is_member($ratingTypeData['allowedGroups'])) {
            continue;
        }

        $ratingTypeName = $lang->sprintf(
            $lang->ougc_feedback_modal_rating,
            htmlspecialchars_uni($ratingTypeData['ratingTypeName'])
        );

        $ratingTypeDescription = htmlspecialchars_uni($ratingTypeData['ratingTypeDescription']);

        $ratingTypeClass = htmlspecialchars_uni($ratingTypeData['ratingTypeClass']);

        $ratingTypeMaximumRating = max(1, min(5, (int)$ratingTypeData['ratingTypeMaximumRating']));

        $feedbackRatingValue = (int)(ratingGet(
            [
                "ratingTypeID='{$ratingTypeID}'",
                "feedbackID='{$feedbackID}'",
                "userID='{$currentUserID}'",
                "ratedUserID='{$feedbackUserID}'",
                "uniqueID='{$feedbackUniqueID}'",
                "feedbackCode='{$feedback_code}'",
            ],
            ['ratingValue'],
            ['limit' => 1]
        )['ratingValue'] ?? 0);

        $rating_rows .= eval(getTemplate('form_rating', false));

        $alternativeBackground = alt_trow();
    }

    if ($edit) {
        $lang->ougc_feedback_profile_add = $lang->ougc_feedback_profile_edit;
    }

    $modalTitle = $lang->sprintf($lang->ougcFeedbackModalTitleProfileAdd, htmlspecialchars_uni($userData['username']));

    $feedbackCode = htmlspecialchars_uni($mybb->input['feedbackCode']);

    echo eval(getTemplate('form', false));

    exit;

    // Send an error to the browser
    //$ougcFeedback->send_form($form);
} elseif ($mybb->get_input('action') == 'delete') {
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

    // users can add but they can't delete.
    if (!$currentUserID) {
        error_no_permission();
    }

    if (!($feedbackData = fetch_feedback($mybb->input['feedbackID']))) {
        error($lang->ougc_feedback_error_invalid_feedback);
    }

    if (!($mybb->usergroup['ougc_feedback_canremove'] && $currentUserID == $feedbackData['feedbackUserID']) && !(isModerator(
            ) && $mybb->usergroup['ougc_feedback_mod_canremove'])) {
        error_no_permission();
    }

    if ($mybb->get_input('hard', MyBB::INPUT_INT)) {
        delete_feedback((int)$feedbackData['feedbackID']);

        sync_user($feedbackData['userID']);

        $lang_string = 'ougc_feedback_redirect_removed';
    } else {
        set_data(
            [
                'feedbackID' => $feedbackData['feedbackID'],
                'feedbackStatus' => 0,
            ]
        );

        $lang_string = 'ougc_feedback_redirect_removed';

        update_feedback();
    }

    sync_user($feedbackData['userID']);

    redirect($mybb->settings['bburl'] . '/feedback.php?userID=' . $feedbackData['userID'], $lang->{$lang_string});
} elseif ($mybb->get_input('action') == 'restore') {
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

    // users can add but they can't delete.
    if (!$currentUserID) {
        error_no_permission();
    }

    if (!($feedbackData = fetch_feedback($mybb->input['feedbackID']))) {
        error($lang->ougc_feedback_error_invalid_feedback);
    }

    if (!($mybb->usergroup['ougc_feedback_canremove'] && $currentUserID == $feedbackData['feedbackUserID']) && !(isModerator(
            ) && $mybb->usergroup['ougc_feedback_mod_canremove'])) {
        error_no_permission();
    }

    set_data(
        [
            'feedbackID' => $feedbackData['feedbackID'],
            'feedbackStatus' => 1,
        ]
    );

    update_feedback();

    sync_user($feedbackData['userID']);

    redirect(
        $mybb->settings['bburl'] . '/feedback.php?userID=' . $feedbackData['userID'],
        $lang->ougc_feedback_redirect_restored
    );
} elseif ($mybb->get_input('action') === 'rate') {
    header('Content-type: application/json; charset=' . $lang->settings['charset']);

    $ratingTypeID = $mybb->get_input('ratingTypeID', MyBB::INPUT_INT);

    $feedbackID = $mybb->get_input('feedbackID', MyBB::INPUT_INT);

    $userID = $mybb->get_input('userID', MyBB::INPUT_INT);

    $uniqueID = $mybb->get_input('uniqueID', MyBB::INPUT_INT);

    $feedback_code = $mybb->get_input('feedbackCode', MyBB::INPUT_INT);

    $ratingValue = $mybb->get_input('ratingValue', MyBB::INPUT_INT);

    if ($userID !== $currentUserID) {
        echo json_encode(['error' => true]);

        exit;
    }

    $ratingTypeData = RATING_TYPES[$ratingTypeID] ?? [];

    if ((int)$ratingTypeData['feedbackCode'] !== $feedback_code ||
        !is_member($ratingTypeData['allowedGroups'])) {
        echo json_encode(['error' => true]);

        exit;
    }

    $contentExists = false;

    if ($feedback_code === FEEDBACK_TYPE_POST) {
        $postData = get_post($uniqueID);

        $ratedUserID = (int)($postData['uid'] ?? 0);

        $contentExists = !empty($postData['pid']) && $ratedUserID !== $currentUserID;
    } elseif ($feedback_code === FEEDBACK_TYPE_PROFILE) {
        $userData = get_user($uniqueID);

        $ratedUserID = (int)($userData['uid'] ?? 0);

        $contentExists = !empty($userData['uid']) && $ratedUserID !== $currentUserID;
    } elseif ($feedback_code === FEEDBACK_TYPE_CONTRACTS_SYSTEM) {
        $contractData = get_contract($uniqueID);

        $sellerUserID = (int)$contractData['seller_id'];

        $buyerUserID = (int)$contractData['buyer_id'];

        $ratedUserID = $currentUserID === $sellerUserID ? $buyerUserID : $sellerUserID;

        $contentExists = !empty($contractData['contract_id']) &&
            in_array($currentUserID, [$sellerUserID, $buyerUserID]);
    }

    if (!$contentExists || empty($ratedUserID)) {
        echo json_encode(['error' => true]);

        exit;
    }

    if ($ratingValue < 1) {
        $ratingValue = 1;
    }

    if ($ratingValue > $ratingTypeData['ratingTypeMaximumRating']) {
        $ratingValue = (int)$ratingTypeData['ratingTypeMaximumRating'];
    }

    $ratingData = [
        'ratingTypeID' => $ratingTypeID,
        'feedbackID' => $feedbackID,
        'userID' => $userID,
        'ratedUserID' => $ratedUserID,
        'uniqueID' => $uniqueID,
        'ratingValue' => $ratingValue,
        'feedbackCode' => $feedback_code,
    ];

    $ratingID = ratingGet(
        [
            "ratingTypeID='{$ratingTypeID}'",
            'feedbackID' => $feedbackID,
            "userID='{$currentUserID}'",
            "ratedUserID='{$ratedUserID}'",
            "uniqueID='{$uniqueID}'",
            "feedbackCode='{$feedback_code}'"
        ],
        [],
        ['limit' => 1]
    )['ratingID'] ?? 0;

    $ratingID = (int)$ratingID;

    if ($ratingID) {
        ratingUpdate($ratingData, $ratingID);
    } else {
        $ratingID = ratingInsert($ratingData);
    }

    //ratingSyncUser($ratedUserID, $ratingTypeID);

    echo json_encode(['success' => true]);

    exit;
}

require_once MYBB_ROOT . 'inc/class_parser.php';

$parser = new postParser();

if (!$mybb->usergroup['canviewprofiles'] || !$mybb->usergroup['ougc_feedback_canview']) {
    error_no_permission();
}

$uid = $mybb->get_input('userID', MyBB::INPUT_INT);

if (!($userData = get_user($uid))) {
    error($lang->ougc_feedback_error_invalid_user);
}

$userID = (int)$userData['uid'];

$userData['username'] = htmlspecialchars_uni($userData['username']);

$lang->ougc_feedback_page_profile = $lang->sprintf($lang->ougc_feedback_page_profile, $userData['username']);

$lang->ougc_feedback_page_report_for = $lang->sprintf($lang->ougc_feedback_page_report_for, $userData['username']);

add_breadcrumb($lang->ougc_feedback_page_profile, get_profile_link($userID));

add_breadcrumb($lang->ougc_feedback_page_title);

$username = format_name($userData['username'], $userData['usergroup'], $userData['displaygroup']);

$userData['displaygroup'] = $userData['displaygroup'] ?: $userData['usergroup'];

$display_group = usergroup_displaygroup($userData['displaygroup']);

// Get user title
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

// Start building a where clause
$where = ["f.userID='{$userID}'"];

if (!isModerator()) {
    $where[] = "f.feedbackStatus='1'";
}

// Start building the url params
$url_params = ['userID' => $userID];

// Build the show filter selected array
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

if ($mybb->input['feedbackID']) {
    $url_params['feedbackID'] = $mybb->input['feedbackID'];
}

// Build the sort filter selected array
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
    sync_user($userID);
}

$statsData = [];

$where_stats = array_merge($where, ["f.feedbackStatus='1'"]);

// Get the total amount of feedbackValue
$query = $db->simple_select(
    'ougc_feedback f',
    'COUNT(f.feedbackID) AS totalFeedbackCount',
    implode(' AND ', $where_stats)
);

$statsData['total'] = $db->fetch_field($query, 'totalFeedbackCount');

$feedbackPostCode = FEEDBACK_TYPE_POST;

// Get the total amount of feedbackValue from posts
$query = $db->simple_select(
    'ougc_feedback f',
    'COUNT(f.feedbackID) AS total_posts_feedback',
    implode(' AND ', array_merge($where_stats, ["feedbackCode='{$feedbackPostCode}'"]))
);

$statsData['posts'] = $db->fetch_field($query, 'total_posts_feedback');

// Get the total amount of feedbackValue from users
$statsData['members'] = ($statsData['total'] - $statsData['posts']);

$statsData = array_map('my_number_format', $statsData);

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
$query = $db->simple_select('ougc_feedback f', 'f.feedbackValue, f.createStamp', implode(' AND ', $where_stats));

while ($feedbackData = $db->fetch_array($query)) {
    switch ($feedbackData['feedbackValue']) {
        case -1:
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
        case 0:
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
        case 1:
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

// Build multipage
$query = $db->simple_select('ougc_feedback f', 'COUNT(f.feedbackID) AS feedback_count', implode(' AND ', $where));

$feedback_count = $db->fetch_field($query, 'feedback_count');

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
    $PL->url_append('feedback.php', $url_params)
) : '';

// Fetch the reputations which will be displayed on this page
$query = $db->query(
    '
	SELECT f.*, u.username AS user_username, u.usergroup AS user_usergroup, u.displaygroup AS user_displaygroup
	FROM ' . TABLE_PREFIX . 'ougc_feedback f
	LEFT JOIN ' . TABLE_PREFIX . 'users u ON (u.uid=f.feedbackUserID)
	WHERE ' . implode(' AND ', $where) . "
	ORDER BY {$order}
	LIMIT {$start}, {$perpage}
"
);

// Gather a list of items that have post reputation
$feedback_cache = $post_cache = $post_feedback = [];

while ($feedbackData = $db->fetch_array($query)) {
    $feedback_cache[] = $feedbackData;

    $feedbackUniqueID = (int)$feedbackData['uniqueID'];

    // If this is a post, hold it and gather some information about it
    if ($feedbackUniqueID && !isset($post_cache[$feedbackUniqueID])) {
        $post_cache[$feedbackUniqueID] = $feedbackUniqueID;
    }
}

$post_reputation = [];

if (!empty($post_cache)) {
    $pids = implode(',', $post_cache);

    $where_post = ["p.pid IN ({$pids})"];

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

foreach ($feedback_cache as $feedbackData) {
    $feedbackID = $feedbackData['feedbackID'];

    $feedback_post_given = '';

    if (!empty($feedbackUniqueID)) {
        $feedback_post_given = $lang->sprintf($lang->ougc_feedback_page_post_nolink, $userData['username']);

        if (isset($post_reputation[$feedbackUniqueID])) {
            $post = $post_reputation[$feedbackUniqueID];

            $thread_link = get_thread_link($post['tid']);

            $subject = htmlspecialchars_uni($parser->parse_badwords($post['subject']));

            $thread_link = $lang->sprintf($lang->ougc_feedback_page_post_given_thread, $thread_link, $subject);

            $link = get_post_link($feedbackUniqueID) . '#pid' . $feedbackUniqueID;

            $feedback_post_given = $lang->sprintf(
                $lang->ougc_feedback_page_post_given,
                $link,
                $userData['username'],
                $thread_link
            );
        }
    }

    switch ($feedbackData['feedbackType']) {
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

    switch ($feedbackData['feedbackValue']) {
        case -1:
            $stylingClasses = ['status' => 'trow_reputation_negative', 'type' => 'reputation_negative'];
            $vote_type .= $lang->ougc_feedback_profile_negative;
            break;
        case 0:
            $stylingClasses = ['status' => 'trow_reputation_neutral', 'type' => 'reputation_neutral'];
            $vote_type .= $lang->ougc_feedback_profile_neutral;
            break;
        case 1:
            $stylingClasses = ['status' => 'trow_reputation_positive', 'type' => 'reputation_positive'];
            $vote_type .= $lang->ougc_feedback_profile_positive;
            break;
    }

    if ($feedbackData['feedbackStatus'] != 1) {
        //$stylingClasses = array('status' => '" style="background-color: #E8DEFF;');
    }

    if (!$feedbackData['feedbackStatus']) {
        $stylingClasses['status'] = 'trow_shaded trow_deleted forumdisplay_regular';
    }

    if ($feedbackID == $mybb->input['feedbackID']) {
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

    if ($feedbackData['feedbackComment']) {
        $parser_options = [
            'allow_html' => 0,
            'allow_mycode' => 0,
            'allow_smilies' => 1,
            'allow_imgcode' => 0,
            'filter_badwords' => 1,
        ];

        $feedbackData['feedbackComment'] = $parser->parse_message($feedbackData['feedbackComment'], $parser_options);
    } else {
        $feedbackData['feedbackComment'] = $lang->ougc_feedback_no_comment;
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

    $feedback_list .= eval(getTemplate('page_item'));
}

if (!$feedback_list) {
    $feedback_list = eval(getTemplate('page_empty'));
}

$add_feedback = '';

$user_perms = usergroup_permissions($userData['usergroup'] . ',' . $userData['additionalgroups']);

if ($mybb->settings['ougc_feedback_allow_profile'] && $mybb->usergroup['ougc_feedback_cangive'] && $user_perms['ougc_feedback_canreceive'] && $currentUserID !== $userID) {
    $show = true;

    if (!$mybb->settings['ougc_feedback_allow_profile_multiple'] && $mybb->settings['ougc_feedback_profile_hide_add']) {
        $where = [
            "userID='{$userID}'", /*"feedbackUserID!='0'", */
            "feedbackUserID='{$currentUserID}'"
        ];

        if (!isModerator()) {
            $where[] = "feedbackStatus='1'";
        }

        $query = $db->simple_select('ougc_feedback', 'feedbackID', implode(' AND ', $where));

        if ($db->fetch_field($query, 'feedbackID')) {
            $show = false;
        }
    }

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

$page = eval(getTemplate('page'));

output_page($page);