<?php

/***************************************************************************
 *
 *    OUGC Feedback plugin (/feedback.php)
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

use function NewPoints\ContractsSystem\Core\get_contract;
use function ougc\Feedback\Core\default_status;
use function ougc\Feedback\Core\delete_feedback;
use function ougc\Feedback\Core\fetch_feedback;
use function ougc\Feedback\Core\getTemplate;
use function ougc\Feedback\Core\insert_feedback;
use function ougc\Feedback\Core\isModerator;
use function ougc\Feedback\Core\loadLanguage;
use function ougc\Feedback\Core\ratingGet;
use function ougc\Feedback\Core\ratingInsert;
use function ougc\Feedback\Core\ratingReplace;
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

$mybb->input['fid'] = $mybb->get_input('fid', MyBB::INPUT_INT);

$PL || require_once PLUGINLIBRARY;

loadLanguage();

$mybb->input['reload'] = $mybb->get_input('reload', MyBB::INPUT_INT);

$mybb->input['action'] = $mybb->get_input('action');

$currentUserID = (int)$mybb->user['uid'];

if ($mybb->get_input('action') == 'add' || $mybb->get_input('action') == 'edit') {
    $edit = $mybb->get_input('action') == 'edit';

    $feedback_code = $mybb->get_input('feedback_code', MyBB::INPUT_INT);

    $processed = false;

    $hook_arguments = [
        'processed' => &$processed,
        'feedback_code' => &$feedback_code,
    ];

    if ($edit) {
        if (!($feedback = fetch_feedback($mybb->input['fid']))) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_feedback_value);
        }

        $feedbackUniqueID = (int)$feedback['unique_id'];

        $method = "DoEdit('{$feedback['uid']}', '{$feedbackUniqueID}', '{$feedback['fid']}')";

        $mybb->input['reload'] = 1;

        // users can add but they can't edit.
        if (!$currentUserID) {
            set_go_back_button(false);

            trow_error($lang->error_nopermission_user_ajax);
        }
    } else {
        $feedback = [
            'uid' => $mybb->get_input('uid', MyBB::INPUT_INT),
            'fuid' => $currentUserID,
            'unique_id' => $mybb->get_input('unique_id', MyBB::INPUT_INT),
            'status' => default_status(),
            'feedback_code' => $feedback_code
        ];

        $feedbackUniqueID = (int)$feedback['unique_id'];

        $method = "DoAdd('{$feedback['uid']}', '{$feedbackUniqueID}')";
    }

    if (!$edit || $mybb->request_method == 'post' || $mybb->get_input('back_button', MyBB::INPUT_INT)) {
        $feedback['type'] = $mybb->get_input('type', MyBB::INPUT_INT);

        $feedback['feedback'] = $mybb->get_input('feedback', MyBB::INPUT_INT);

        $feedback['comment'] = $mybb->get_input('comment');
    }

    $hook_arguments['feedback_data'] = &$feedback;

    // Set handler data
    set_data($feedback);

    $type_selected_buyer = $type_selected_seller = $type_selected_trader = '';

    switch ((int)$feedback['type']) {
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

    switch ((int)$feedback['feedback']) {
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

    if (!($user = get_user($feedback['uid']))) {
        set_go_back_button(false);

        trow_error($lang->ougc_feedback_error_invalid_user);
    }

    $user_perms = usergroup_permissions($user['usergroup'] . ',' . $user['additionalgroups']);

    if ($edit) {
        if (!($mybb->usergroup['ougc_feedback_canedit'] && $currentUserID == $feedback['fuid']) &&
            !(isModerator() && $mybb->usergroup['ougc_feedback_mod_canedit'])) {
            set_go_back_button(false);

            trow_error($lang->error_nopermission_user_ajax);
        }
    } else {
        if (!$mybb->usergroup['ougc_feedback_cangive'] || !$user_perms['ougc_feedback_canreceive']) {
            set_go_back_button(false);

            trow_error($lang->error_nopermission_user_ajax);
        }

        if ($user['uid'] == $currentUserID) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_self_user);
        }

        if (!in_array($feedback['type'], [1, 2, 3])) {
            trow_error($lang->ougc_feedback_error_invalid_type);
        }

        if (!in_array($feedback['feedback'], [FEEDBACK_TYPE_NEGATIVE, FEEDBACK_TYPE_NEUTRAL, FEEDBACK_TYPE_POSITIVE])) {
            trow_error($lang->ougc_feedback_error_invalid_feedback_value);
        }

        if (!in_array($feedback['status'], [-1, 0, 1])) {
            trow_error($lang->ougc_feedback_error_invalid_status);
        }

        if ($mybb->usergroup['ougc_feedback_maxperday']) {
            $timesearch = TIME_NOW - (60 * 60 * 24);

            $query = $db->simple_select(
                'ougc_feedback',
                'COUNT(fid) AS feedbacks',
                "fuid='{$currentUserID}' AND dateline>'{$timesearch}'"
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

    set_data($feedback);

    if (!$processed && !$edit && $feedbackUniqueID) {
        $feedback_code = FEEDBACK_TYPE_POST;

        if (!($post = get_post($feedbackUniqueID))) {
            set_go_back_button(false);

            trow_error($lang->ougc_feedback_error_invalid_post);
        }

        if ($post['uid'] != $feedback['uid']) {
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
            "uid='{$feedback['uid']}'", /*"fuid!='0'", */
            "fuid='{$feedback['fuid']}'",
            "unique_id='{$feedbackUniqueID}'",
            "status='1'"
        ];

        $query = $db->simple_select('ougc_feedback', 'fid', implode(' AND ', $where));

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
                "uid='{$feedback['uid']}'", /*"fuid!='0'", */
                "fuid='{$feedback['fuid']}'"
            ];

            if (!isModerator()) {
                $where[] = "status='1'";
            }

            $query = $db->simple_select('ougc_feedback', 'fid', implode(' AND ', $where));

            if ($db->fetch_field($query, 'fid')) {
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

        $message_count = my_strlen($feedback['comment']);

        if ($comments_maxlength < 1) {
            $feedback['comment'] = '';
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
                update_feedback();

                sync_user($feedback['uid']);

                trow_success($lang->ougc_feedback_success_feedback_edited);
            } else {
                // Insert feedback
                insert_feedback();

                sync_user((int)$feedback['uid']);

                /*if(strpos(','.$user['ougc_feedback_notification'].',', ',1,'))
                {*/
                \ougc\Feedback\Core\send_pm([
                    'subject' => $lang->ougc_feedback_notification_pm_subject,
                    'message' => $lang->sprintf(
                        $lang->ougc_feedback_notification_pm_message,
                        $user['username'],
                        $mybb->settings['bbname']
                    ),
                    'touid' => $feedback['uid']
                ], (int)$mybb->settings['ougc_feedback_pm_user_id'], true);
                /*}*/

                /*if(strpos(','.$user['ougc_feedback_notification'].',', ',`2,'))
                {*/
                send_email([
                    'to' => $user['email'],
                    'subject' => 'ougc_feedback_notification_mail_subject',
                    'message' => [
                        'ougc_feedback_notification_mail_message',
                        $user['username'],
                        $mybb->settings['bbname']
                    ],
                    'from' => $mybb->settings['adminemail'],
                    'touid' => $user['uid'],
                    'language' => $user['language']
                ]);
                /*}*/

                /*if(strpos(','.$user['ougc_feedback_notification'].',', ',`3,'))
                {
                    \ougc\Feedback\Core\send_alert(array());
                }*/

                if ($feedbackUniqueID && $feedback_code === FEEDBACK_TYPE_POST) {
                    postbit($post);
                    $replacement = $post['ougc_feedback'];
                } else {
                    $memprofile = &$user;

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
        $mybb->input['comment'] = $feedback['comment'];

        $mybb->input['comment'] = htmlspecialchars_uni($mybb->input['comment']);

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
                "userID='{$currentUserID}'",
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

    if (!($feedback = fetch_feedback($mybb->input['fid']))) {
        error($lang->ougc_feedback_error_invalid_feedback);
    }

    if (!($mybb->usergroup['ougc_feedback_canremove'] && $currentUserID == $feedback['fuid']) && !(isModerator(
            ) && $mybb->usergroup['ougc_feedback_mod_canremove'])) {
        error_no_permission();
    }

    if ($mybb->get_input('hard', MyBB::INPUT_INT)) {
        delete_feedback((int)$feedback['fid']);

        sync_user($feedback['uid']);

        $lang_string = 'ougc_feedback_redirect_removed';
    } else {
        set_data(
            [
                'fid' => $feedback['fid'],
                'status' => 0,
            ]
        );

        $lang_string = 'ougc_feedback_redirect_removed';

        update_feedback();
    }

    sync_user($feedback['uid']);

    redirect($mybb->settings['bburl'] . '/feedback.php?uid=' . $feedback['uid'], $lang->{$lang_string});
} elseif ($mybb->get_input('action') == 'restore') {
    // Verify incoming POST request
    verify_post_check($mybb->get_input('my_post_key'));

    // users can add but they can't delete.
    if (!$currentUserID) {
        error_no_permission();
    }

    if (!($feedback = fetch_feedback($mybb->input['fid']))) {
        error($lang->ougc_feedback_error_invalid_feedback);
    }

    if (!($mybb->usergroup['ougc_feedback_canremove'] && $currentUserID == $feedback['fuid']) && !(isModerator(
            ) && $mybb->usergroup['ougc_feedback_mod_canremove'])) {
        error_no_permission();
    }

    set_data(
        [
            'fid' => $feedback['fid'],
            'status' => 1,
        ]
    );

    update_feedback();

    sync_user($feedback['uid']);

    redirect(
        $mybb->settings['bburl'] . '/feedback.php?uid=' . $feedback['uid'],
        $lang->ougc_feedback_redirect_restored
    );
} elseif ($mybb->get_input('action') === 'rate') {
    header('Content-type: application/json; charset=' . $lang->settings['charset']);

    $ratingTypeID = $mybb->get_input('ratingTypeID', MyBB::INPUT_INT);

    $userID = $mybb->get_input('userID', MyBB::INPUT_INT);

    $uniqueID = $mybb->get_input('uniqueID', MyBB::INPUT_INT);

    $feedbackCode = $mybb->get_input('feedbackCode', MyBB::INPUT_INT);

    $ratingValue = $mybb->get_input('ratingValue', MyBB::INPUT_INT);

    if ($userID !== $currentUserID) {
        echo json_encode(['error' => true]);

        exit;
    }

    $ratingTypeData = RATING_TYPES[$ratingTypeID] ?? [];

    if ((int)$ratingTypeData['feedbackCode'] !== $feedbackCode ||
        !is_member($ratingTypeData['allowedGroups'])) {
        echo json_encode(['error' => true]);

        exit;
    }

    $contentExists = false;

    if ($feedbackCode === \ougc\Feedback\Core\FEEDBACK_TYPE_POST) {
        $postData = get_post($uniqueID);

        $contentExists = !empty($postData['pid']) && (int)$postData['uid'] !== $currentUserID;
    } elseif ($feedbackCode === \ougc\Feedback\Core\FEEDBACK_TYPE_PROFILE) {
        $userData = get_user($uniqueID);

        $contentExists = !empty($userData['uid']) && (int)$userData['uid'] !== $currentUserID;
    } elseif ($feedbackCode === FEEDBACK_TYPE_CONTRACTS_SYSTEM) {
        $contractData = get_contract($uniqueID);

        $contentExists = !empty($contractData['contract_id']) && (int)$contractData['buyer_id'] === $currentUserID;
    }

    if (!$contentExists) {
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
        'userID' => $userID,
        'uniqueID' => $uniqueID,
        'ratingValue' => $ratingValue,
        'feedbackCode' => $feedbackCode,
    ];

    $ratingID = ratingGet(
        [
            "ratingTypeID='{$ratingTypeID}'",
            "userID='{$currentUserID}'",
            "uniqueID='{$uniqueID}'",
            "feedbackCode='{$feedbackCode}'"
        ],
        ['ratingID'],
        ['limit' => 1]
    )['ratingID'] ?? 0;

    $ratingID = (int)$ratingID;

    if ($ratingID) {
        ratingUpdate($ratingData, $ratingID);
    } else {
        $ratingID = ratingInsert($ratingData);
    }

    echo json_encode(['success' => true, 'ratingID' => $ratingID]);

    exit;
}

require_once MYBB_ROOT . 'inc/class_parser.php';

$parser = new postParser();

if (!$mybb->usergroup['canviewprofiles'] || !$mybb->usergroup['ougc_feedback_canview']) {
    error_no_permission();
}

$uid = $mybb->get_input('uid', MyBB::INPUT_INT);

if (!($user = get_user($uid))) {
    error($lang->ougc_feedback_error_invalid_user);
}

$user['username'] = htmlspecialchars_uni($user['username']);

$lang->ougc_feedback_page_profile = $lang->sprintf($lang->ougc_feedback_page_profile, $user['username']);

$lang->ougc_feedback_page_report_for = $lang->sprintf($lang->ougc_feedback_page_report_for, $user['username']);

add_breadcrumb($lang->ougc_feedback_page_profile, get_profile_link($user['uid']));

add_breadcrumb($lang->ougc_feedback_page_title);

$username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);

$user['displaygroup'] = $user['displaygroup'] ?: $user['usergroup'];

$display_group = usergroup_displaygroup($user['displaygroup']);

// Get user title
$usertitle = '';

if (trim($user['usertitle'])) {
    $usertitle = $user['usertitle'];
} elseif (!empty($display_group['usertitle'])) {
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
$where = ["f.uid='{$user['uid']}'"];

if (!isModerator()) {
    $where[] = "f.status='1'";
}

// Start building the url params
$url_params = ['uid' => $user['uid']];

// Build the show filter selected array
$show_selected_all = $show_selected_positive = $show_selected_neutral = $show_selected_negative = $show_selected_given = '';

switch ($mybb->get_input('show')) {
    case 'positive':
        $url_params['show'] = 'positive';

        $where[] = "f.feedback='1'";

        $show_selected_positive = ' selected="selected"';
        break;
    case 'neutral':
        $url_params['show'] = 'neutral';

        $where[] = "f.feedback='0'";

        $show_selected_neutral = ' selected="selected"';
        break;
    case 'negative':
        $url_params['show'] = 'negative';

        $where[] = "f.feedback='-1'";

        $show_selected_negative = ' selected="selected"';
        break;
    case 'gived':
        $url_params['show'] = 'negative';

        $where[] = "f.fuid='{$user['uid']}'";

        unset($where[0]);

        $show_selected_given = ' selected="selected"';
        break;
    default:
        $url_params['show'] = 'all';

        $show_selected_all = ' selected="selected"';
        break;
}

if ($mybb->input['fid']) {
    $url_params['fid'] = $mybb->input['fid'];
}

// Build the sort filter selected array
$sort_selected_username = $sort_selected_last_updated = '';

switch ($mybb->get_input('sort')) {
    case 'username':
        $url_params['sort'] = 'username';

        $order = 'u.username ASC, f.dateline DESC';

        $sort_selected_username = ' selected="selected"';
        break;
    default:
        $url_params['sort'] = 'dateline';

        $order = 'f.dateline DESC, u.username ASC';

        $sort_selected_last_updated = ' selected="selected"';
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
    sync_user((int)$user['uid']);
}

$stats = [];

$where_stats = array_merge($where, ["f.status='1'"]);

// Get the total amount of feedback
$query = $db->simple_select('ougc_feedback f', 'COUNT(f.fid) AS total_feedback', implode(' AND ', $where_stats));

$stats['total'] = $db->fetch_field($query, 'total_feedback');

$feedbackPostCode = FEEDBACK_TYPE_POST;

// Get the total amount of feedback from posts
$query = $db->simple_select(
    'ougc_feedback f',
    'COUNT(f.fid) AS total_posts_feedback',
    implode(' AND ', array_merge($where_stats, ["feedback_code='{$feedbackPostCode}'"]))
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
	LEFT JOIN ' . TABLE_PREFIX . 'users u ON (u.uid=f.fuid)
	WHERE ' . implode(' AND ', $where) . "
	ORDER BY {$order}
	LIMIT {$start}, {$perpage}
"
);

// Gather a list of items that have post reputation
$feedback_cache = $post_cache = $post_feedback = [];

while ($feedback = $db->fetch_array($query)) {
    $feedback_cache[] = $feedback;

    $feedbackUniqueID = (int)$feedback['unique_id'];

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

foreach ($feedback_cache as $feedback) {
    $feedback['fid'] = (int)$feedback['fid'];

    $feedback_post_given = '';

    if (!empty($feedbackUniqueID)) {
        $feedback_post_given = $lang->sprintf($lang->ougc_feedback_page_post_nolink, $user['username']);

        if (isset($post_reputation[$feedbackUniqueID])) {
            $post = $post_reputation[$feedbackUniqueID];

            $thread_link = get_thread_link($post['tid']);

            $subject = htmlspecialchars_uni($parser->parse_badwords($post['subject']));

            $thread_link = $lang->sprintf($lang->ougc_feedback_page_post_given_thread, $thread_link, $subject);

            $link = get_post_link($feedbackUniqueID) . '#pid' . $feedbackUniqueID;

            $feedback_post_given = $lang->sprintf(
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
            $class = ['status' => 'trow_reputation_negative', 'type' => 'reputation_negative'];
            $vote_type .= $lang->ougc_feedback_profile_negative;
            break;
        case 0:
            $class = ['status' => 'trow_reputation_neutral', 'type' => 'reputation_neutral'];
            $vote_type .= $lang->ougc_feedback_profile_neutral;
            break;
        case 1:
            $class = ['status' => 'trow_reputation_positive', 'type' => 'reputation_positive'];
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
        $parser_options = [
            'allow_html' => 0,
            'allow_mycode' => 0,
            'allow_smilies' => 1,
            'allow_imgcode' => 0,
            'filter_badwords' => 1,
        ];

        $feedback['comment'] = $parser->parse_message($feedback['comment'], $parser_options);
    } else {
        $feedback['comment'] = $lang->ougc_feedback_no_comment;
    }

    $edit_link = $delete_link = $delete_hard_link = $report_link = '';

    if ($currentUserID && (($feedback['fuid'] == $currentUserID && $mybb->usergroup['ougc_feedback_canedit']) || (isModerator(
                ) && $mybb->usergroup['ougc_feedback_canedit']))) {
        $edit_link = eval(getTemplate('page_item_edit'));
    }

    if ($currentUserID && (($feedback['fuid'] == $currentUserID && $mybb->usergroup['ougc_feedback_canremove']) || (isModerator(
                ) && $mybb->usergroup['ougc_feedback_mod_canremove']))) {
        if (!$feedback['status']) {
            $delete_link = eval($templates->render('ougcfeedback_page_item_restore'));
        } else {
            $delete_link = eval($templates->render('ougcfeedback_page_item_delete'));
        }
    }

    if ($currentUserID && isModerator() && $mybb->usergroup['ougc_feedback_mod_candelete']) {
        $delete_hard_link = eval(getTemplate('page_item_delete_hard'));
    }

    if ($currentUserID) {
        $report_link = eval($templates->render('ougcfeedback_page_item_report'));
    }

    $feedback_list .= eval(getTemplate('page_item'));
}

if (!$feedback_list) {
    $feedback_list = eval(getTemplate('page_empty'));
}

$add_feedback = '';

$user_perms = usergroup_permissions($user['usergroup'] . ',' . $user['additionalgroups']);

if ($mybb->settings['ougc_feedback_allow_profile'] && $mybb->usergroup['ougc_feedback_cangive'] && $user_perms['ougc_feedback_canreceive'] && $currentUserID != $user['uid']) {
    $show = true;

    if (!$mybb->settings['ougc_feedback_allow_profile_multiple'] && $mybb->settings['ougc_feedback_profile_hide_add']) {
        $where = [
            "uid='{$user['uid']}'", /*"fuid!='0'", */
            "fuid='{$currentUserID}'"
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
        $add_feedback = eval(getTemplate('page_addlink'));
    }
}

if ($user['ougc_feedback'] < 0) {
    $total_class = '_negative';
} elseif ($user['ougc_feedback'] > 0) {
    $total_class = '_positive';
} else {
    $total_class = '_neutral';
}

$page = eval(getTemplate('page'));

output_page($page);