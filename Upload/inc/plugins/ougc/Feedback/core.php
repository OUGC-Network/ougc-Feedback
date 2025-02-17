<?php

/***************************************************************************
 *
 *    ougc Feedback plugin (/inc/plugins/ougc/Feedback/core.php)
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

namespace ougc\Feedback\Core;

use MyBB;
use pluginSystem;

use const ougc\Feedback\ROOT;

function loadLanguage(bool $forceLoad = false): bool
{
    global $lang;

    isset($lang->ougc_feedback) && !$forceLoad || $lang->load('ougc_feedback');

    return true;
}

function addHooks(string $namespace): bool
{
    global $plugins;

    $namespaceLowercase = strtolower($namespace);
    $definedUserFunctions = get_defined_functions()['user'];

    foreach ($definedUserFunctions as $callable) {
        $namespaceWithPrefixLength = strlen($namespaceLowercase) + 1;

        if (substr($callable, 0, $namespaceWithPrefixLength) == $namespaceLowercase . '\\') {
            $hookName = substr_replace($callable, '', 0, $namespaceWithPrefixLength);

            $priority = substr($callable, -2);

            if (is_numeric(substr($hookName, -2))) {
                $hookName = substr($hookName, 0, -2);
            } else {
                $priority = 10;
            }

            $plugins->add_hook($hookName, $callable, $priority);
        }
    }

    return true;
}

function run_hooks(string $hook_name = '', array &$hook_arguments = []): array
{
    global $plugins;

    if ($plugins instanceof pluginSystem) {
        $hook_arguments = $plugins->run_hooks('ougc_feedback_' . $hook_name, $hook_arguments);
    }

    return (array)$hook_arguments;
}

function getSetting(string $settingKey = '')
{
    global $mybb;

    return SETTINGS[$settingKey] ?? ($mybb->settings['ougc_feedback_' . $settingKey] ?? false);
}

function getTemplateName(string $templateName = ''): string
{
    $templatePrefix = '';

    if ($templateName) {
        $templatePrefix = '_';
    }

    return "ougcfeedback{$templatePrefix}{$templateName}";
}

function getTemplate(string $templateName = '', bool $enableHTMLComments = true): string
{
    global $templates;

    if (DEBUG) {
        $filePath = ROOT . "/templates/{$templateName}.html";

        $templateContents = file_get_contents($filePath);

        $templates->cache[getTemplateName($templateName)] = $templateContents;
    } elseif (my_strpos($templateName, '/') !== false) {
        $templateName = substr($templateName, strpos($templateName, '/') + 1);
    }

    return $templates->render(getTemplateName($templateName), true, $enableHTMLComments);
}

function default_status(): int
{
    return 1;
}

function trow_error(
    string $message,
    string $title = '',
    bool $success = false,
    string $replacement = '',
    int $hide_add = 1
) {
    global $templates, $lang, $theme, $mybb;

    loadLanguage();

    $title = $title ?: $lang->error;

    $message = $message ?: $lang->message;

    if ($success) {
        header('Content-type: application/json; charset=' . $lang->settings['charset']);

        $data = [
            'replacement' => $replacement,
            'hide_add' => $hide_add,
            'reload' => $mybb->get_input('reload', MyBB::INPUT_INT)
        ];

        $data['modal'] = eval(getTemplate('modal', false));

        echo json_encode($data);
    } else {
        $message = set_error($message);

        $message = eval(getTemplate('modal_error'));

        $tfoot = get_go_back_button();

        echo eval(getTemplate('modal', false));
    }

    exit;
}

function trow_success(string $message, string $title = '', string $replacement = '', int $hide_add = 1)
{
    //set_go_back_button(false);
    trow_error($message, $title, true, $replacement, $hide_add);
}

function set_error(string $message = ''): string
{
    static $error = '';

    if ($message !== '') {
        $error = $message;
    }

    return $error;
}

function get_error(): string
{
    return set_error();
}

function set_go_back_button(bool $set_go_back_button = true): bool
{
    static $go_back_button = true;

    if ($set_go_back_button !== true) {
        $go_back_button = false;
    }

    return $go_back_button;
}

function get_go_back_button(): string
{
    if (!set_go_back_button()) {
        return '';
    }

    global $mybb, $templates, $lang;

    loadLanguage();

    $mybb->input['type'] = $mybb->get_input('type', MyBB::INPUT_INT);

    $mybb->input['feedback'] = $mybb->get_input('feedback', MyBB::INPUT_INT);

    $mybb->input['reload'] = $mybb->get_input('reload', MyBB::INPUT_INT);

    $mybb->input['comment'] = $mybb->get_input('comment', MyBB::INPUT_STRING);

    $uid = set_data()['uid'];

    $unique_id = set_data()['unique_id'];

    $feedbackPluginCode = $mybb->get_input('feedback_code', MyBB::INPUT_INT);

    return eval(getTemplate('modal_tfoot'));
}

function set_data(array $feedback = []): array
{
    static $data;

    !isset($feedback['fid']) || $data['fid'] = (int)$feedback['fid'];

    !isset($feedback['fid']) || $data['fid'] = (int)$feedback['fid'];

    !isset($feedback['uid']) || $data['uid'] = (int)$feedback['uid'];

    !isset($feedback['fuid']) || $data['fuid'] = (int)$feedback['fuid'];

    !isset($feedback['unique_id']) || $data['unique_id'] = (int)$feedback['unique_id'];

    !isset($feedback['type']) || $data['type'] = (int)$feedback['type'];

    !isset($feedback['feedback']) || $data['feedback'] = (int)$feedback['feedback'];

    !isset($feedback['comment']) || $data['comment'] = (string)$feedback['comment'];

    !isset($feedback['status']) || $data['status'] = (int)$feedback['status'];

    !isset($feedback['feedback_code']) || $data['feedback_code'] = (int)$feedback['feedback_code'];

    !isset($feedback['dateline']) || $data['dateline'] = TIME_NOW;

    return $data;
}

function validate_feedback(): bool
{
    if (get_error() !== '') {
        return false;
    }

    return true;
}

function fetch_feedback(int $fid): array
{
    global $db;

    $query = $db->simple_select('ougc_feedback', '*', "fid='{$fid}'");

    if ($db->num_rows($query)) {
        return $db->fetch_array($query);
    }

    return [];
}

function insert_feedback(bool $update = false): int
{
    global $db;

    $feedback = set_data();

    $insert_data = [];

    //!isset($feedback['fid']) || $insert_data['fid'] = (int)$feedback['fid'];

    !isset($feedback['uid']) || $insert_data['uid'] = (int)$feedback['uid'];

    !isset($feedback['fuid']) || $insert_data['fuid'] = (int)$feedback['fuid'];

    !isset($feedback['unique_id']) || $insert_data['unique_id'] = (int)$feedback['unique_id'];

    !isset($feedback['type']) || $insert_data['type'] = (int)$feedback['type'];

    !isset($feedback['feedback']) || $insert_data['feedback'] = (int)$feedback['feedback'];

    !isset($feedback['comment']) || $insert_data['comment'] = $db->escape_string($feedback['comment']);

    !isset($feedback['status']) || $insert_data['status'] = (int)$feedback['status'];

    !isset($feedback['feedback_code']) || $insert_data['feedback_code'] = (int)$feedback['feedback_code'];

    if (!$update) {
        !isset($feedback['dateline']) || $insert_data['dateline'] = (int)$feedback['dateline'];
    }

    if ($update) {
        enums::$feedbackID = $feedback['fid'];

        $db->update_query('ougc_feedback', $insert_data, "fid='{$feedback['fid']}'");
    } else {
        $insert_data['dateline'] = TIME_NOW;

        enums::$feedbackID = (int)$db->insert_query('ougc_feedback', $insert_data);
    }

    sync_user($insert_data['uid']);

    set_data($feedback);

    return enums::$feedbackID;
}

function update_feedback(): int
{
    return insert_feedback(true);
}

function delete_feedback(int $fid): bool
{
    global $db;

    $db->delete_query('ougc_feedback', "fid='{$fid}'");

    return true;
}

function ratingInsert(array $ratingData, bool $isUpdate = false, int $ratingID = 0): int
{
    global $db;

    $replaceData = [];

    if (isset($ratingData['ratingTypeID'])) {
        $replaceData['ratingTypeID'] = (int)$ratingData['ratingTypeID'];
    }

    if (isset($ratingData['feedbackID'])) {
        $replaceData['feedbackID'] = (int)$ratingData['feedbackID'];
    }

    if (isset($ratingData['userID'])) {
        $replaceData['userID'] = (int)$ratingData['userID'];
    }

    if (isset($ratingData['ratedUserID'])) {
        $replaceData['ratedUserID'] = (int)$ratingData['ratedUserID'];
    }

    if (isset($ratingData['uniqueID'])) {
        $replaceData['uniqueID'] = (int)$ratingData['uniqueID'];
    }

    if (isset($ratingData['ratingValue'])) {
        $replaceData['ratingValue'] = (int)$ratingData['ratingValue'];
    }

    if (isset($ratingData['feedbackCode'])) {
        $replaceData['feedbackCode'] = (int)$ratingData['feedbackCode'];
    }

    if ($isUpdate) {
        $db->update_query('ougc_feedback_ratings', $replaceData, "ratingID='{$ratingID}'");

        return 0;
    } else {
        return (int)$db->insert_query('ougc_feedback_ratings', $replaceData);
    }
}

function ratingUpdate(array $ratingData, int $ratingID): int
{
    return ratingInsert($ratingData, true, $ratingID);
}

function ratingGet(array $whereClauses, array $queryFields = [], array $queryOptions = []): array
{
    global $db;

    $query = $db->simple_select(
        'ougc_feedback_ratings',
        implode(',', array_merge(['ratingID'], $queryFields)),
        implode(' AND ', $whereClauses),
        $queryOptions
    );

    if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
        return (array)$db->fetch_array($query);
    }

    $ratingObjects = [];

    while ($ratingData = $db->fetch_array($query)) {
        $ratingObjects[(int)$ratingData['ratingID']] = $ratingData;
    }

    return $ratingObjects;
}

function ratingSyncUser(int $ratedUserID, int $ratingTypeID): bool
{
    global $db;

    $db->delete_query('ougc_feedback_ratings', "feedbackID<'1'");

    $query = $db->simple_select(
        'ougc_feedback_ratings',
        'AVG(ratingValue) AS averageRatingValue',
        "ratedUserID='{$ratedUserID}' AND ratingTypeID='{$ratingTypeID}'"
    );

    $averageRatingValue = (float)$db->fetch_field($query, 'averageRatingValue');

    $db->update_query(
        'users',
        [('ougcFeedbackRatingAverage' . $ratingTypeID) => $averageRatingValue],
        "uid='{$ratedUserID}'"
    );

    return true;
}

function send_pm(array $pm, int $fromid = 0, bool $admin_override = false): bool
{
    global $mybb;

    if (!$mybb->settings['ougc_feedback_allow_pm_notification']) {
        return false;
    }

    global $session;

    $pm['ipaddress'] = $pm['ipaddress'] ?? $session->packedip;

    return \send_pm($pm, $fromid, $admin_override);
}

function send_email(array $email): bool
{
    global $mybb, $db, $lang;

    if (!$mybb->settings['ougc_feedback_allow_email_notifications']) {
        return false;
    }

    // Load language
    if ($email['language'] != $mybb->user['language'] && $lang->language_exists($email['language'])) {
        $reset_lang = true;

        $lang->set_language($email['language']);

        loadLanguage(true);
    }

    foreach (['subject', 'message'] as $key) {
        $lang_string = $email[$key];

        if (is_array($email[$key])) {
            $num_args = count($email[$key]);

            for ($i = 1; $i < $num_args; $i++) {
                $lang->{$email[$key][0]} = str_replace('{' . $i . '}', $email[$key][$i], $lang->{$email[$key][0]});
            }

            $lang_string = $email[$key][0];
        }

        $email[$key] = $lang->{$lang_string};
    }

    if (!$email['subject'] || !$email['message'] || !$email['to']) {
        return false;
    }

    my_mail($email['to'], $email['subject'], $email['message'], $email['from']);

    // Log the message
    if ($mybb->settings['mail_logging']) {
        $entry = [
            'subject' => $db->escape_string($email['subject']),
            'message' => $db->escape_string($email['message']),
            'dateline' => TIME_NOW,
            'fromuid' => 0,
            'fromemail' => $db->escape_string($email['from']),
            'touid' => $email['touid'],
            'toemail' => $db->escape_string($email['to']),
            'tid' => 0,
            'ipaddress' => $db->escape_binary($mybb->session->packedip),
            'type' => 1
        ];

        $db->insert_query('maillogs', $entry);
    }

    // Reset language
    if (isset($reset_lang)) {
        $lang->set_language($mybb->user['language']);

        loadLanguage(true);
    }

    return true;
}

function sync_user(int $uid): bool
{
    global $db;

    $query = $db->simple_select('ougc_feedback', 'SUM(feedback) AS feedback', "uid='{$uid}' AND status='1'");

    $feedback = (int)$db->fetch_field($query, 'feedback');

    $db->update_query('users', ['ougc_feedback' => $feedback], "uid='{$uid}'");

    return true;
}

function getUserStats(int $userID): array
{
    global $db;

    $whereClauses = [
        "uid='{$userID}'",
        /*"fuid!='0'", */
        "status='1'"
    ];
    /*if(!isModerator())
    {
        $whereClauses[] = "status='1'";
    }*/

    $userStats = [
        'total' => 0,
        'positive' => 0,
        'neutral' => 0,
        'negative' => 0,
        'positive_percent' => 0,
        'neutral_percent' => 0,
        'negative_percent' => 0,
        'positive_users' => [],
        'neutral_users' => [],
        'negative_users' => [],
        'average' => 0
    ];

    $dbQuery = $db->simple_select('ougc_feedback', '*', implode(' AND ', $whereClauses));

    while ($feedbackData = $db->fetch_array($dbQuery)) {
        ++$userStats['total'];

        $feedbackData['feedback'] = (int)$feedbackData['feedback'];

        switch ($feedbackData['feedback']) {
            case 1:
                ++$userStats['positive'];

                $userStats['positive_users'][$feedbackData['fuid']] = 1;
                break;
            case 0:
                ++$userStats['neutral'];

                $userStats['neutral_users'][$feedbackData['fuid']] = 1;
                break;
            case -1:
                ++$userStats['negative'];

                $userStats['negative_users'][$feedbackData['fuid']] = 1;
                break;
        }
    }

    if ($userStats['total']) {
        $userStats['positive_percent'] = floor(100 * ($userStats['positive'] / $userStats['total']));

        $userStats['neutral_percent'] = floor(100 * ($userStats['neutral'] / $userStats['total']));

        $userStats['negative_percent'] = floor(100 * ($userStats['negative'] / $userStats['total']));

        $userStats['average'] = $userStats['positive'] - $userStats['negative'];
    }

    $userStats['average'] = my_number_format($userStats['average']);

    $userStats['positive_users'] = count($userStats['positive_users']);

    $userStats['neutral_users'] = count($userStats['neutral_users']);

    $userStats['negative_users'] = count($userStats['negative_users']);

    $userStats = array_map('my_number_format', $userStats);

    return $userStats;
}

function enableContractSystemIntegration(): bool
{
    return (bool)getSetting('enableContractSystemIntegration');
}

function isModerator(): bool
{
    global $mybb;

    return !empty($mybb->usergroup['ougc_feedback_ismod']);
}