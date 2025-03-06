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

use function send_pm;

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

function runHooks(string $hookName = '', array &$hookArguments = []): array
{
    global $plugins;

    if ($plugins instanceof pluginSystem) {
        $hookArguments = $plugins->run_hooks('ougc_feedback_' . $hookName, $hookArguments);
    }

    return (array)$hookArguments;
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

function trowError(
    string $errorMessage,
    string $errorTitle = '',
    bool $isSuccess = false,
    string $replacement = '',
    int $hide_add = 1
) {
    global $templates, $lang, $theme, $mybb;

    loadLanguage();

    $errorTitle = $errorTitle ?: $lang->error;

    $errorMessage = $errorMessage ?: $lang->message;

    if ($isSuccess) {
        header('Content-type: application/json; charset=' . $lang->settings['charset']);

        $data = [
            'replacement' => $replacement,
            'hide_add' => $hide_add,
            'reload' => $mybb->get_input('reload', MyBB::INPUT_INT)
        ];

        $data['modal'] = eval(getTemplate('modal', false));

        echo json_encode($data);
    } else {
        $errorMessage = eval(getTemplate('modal_error'));

        $tfoot = backButtonGet();

        echo eval(getTemplate('modal', false));
    }

    exit;
}

function trowSuccess(string $successMessage, string $successTitle = '', string $replacement = '', int $hide_add = 1)
{
    //set_go_back_button(false);
    trowError($successMessage, $successTitle, true, $replacement, $hide_add);
}

function backButtonSet(bool $backButtonSet = true): bool
{
    static $backButtonShow = true;

    if ($backButtonSet !== true) {
        $backButtonShow = false;
    }

    return $backButtonShow;
}

function backButtonGet(): string
{
    if (!backButtonSet()) {
        return '';
    }

    global $mybb, $templates, $lang;

    loadLanguage();

    $feedbackType = $mybb->get_input('feedbackType', MyBB::INPUT_INT);

    $feedbackValue = $mybb->get_input('feedbackValue', MyBB::INPUT_INT);

    $reloadModal = $mybb->get_input('reload', MyBB::INPUT_INT);

    $feedbackComment = $mybb->get_input('feedbackComment');

    global $feedbackData;

    $uid = $feedbackData['userID'];

    $unique_id = $feedbackData['uniqueID'];

    $feedbackPluginCode = $mybb->get_input('feedbackCode', MyBB::INPUT_INT);

    return eval(getTemplate('modal_tfoot'));
}

function feedbackGet(int $feedbackID): array
{
    global $db;

    $query = $db->simple_select('ougc_feedback', '*', "feedbackID='{$feedbackID}'");

    if ($db->num_rows($query)) {
        return $db->fetch_array($query);
    }

    return [];
}

function feedbackInsert(array $feedbackData, bool $isUpdate = false, int $feedbackID = 0): int
{
    global $db;

    $insert_data = [];

    if (isset($feedbackData['userID'])) {
        $insert_data['userID'] = (int)$feedbackData['userID'];
    }

    if (isset($feedbackData['feedbackUserID'])) {
        $insert_data['feedbackUserID'] = (int)$feedbackData['feedbackUserID'];
    }

    if (isset($feedbackData['uniqueID'])) {
        $insert_data['uniqueID'] = (int)$feedbackData['uniqueID'];
    }

    if (isset($feedbackData['feedbackType'])) {
        $insert_data['feedbackType'] = (int)$feedbackData['feedbackType'];
    }

    if (isset($feedbackData['feedbackValue'])) {
        $insert_data['feedbackValue'] = (int)$feedbackData['feedbackValue'];
    }

    if (isset($feedbackData['feedbackComment'])) {
        $insert_data['feedbackComment'] = $db->escape_string($feedbackData['feedbackComment']);
    }

    if (isset($feedbackData['feedbackStatus'])) {
        $insert_data['feedbackStatus'] = (int)$feedbackData['feedbackStatus'];
    }

    if (isset($feedbackData['feedbackCode'])) {
        $insert_data['feedbackCode'] = (int)$feedbackData['feedbackCode'];
    }

    if (isset($feedbackData['createStamp'])) {
        $insert_data['createStamp'] = (int)$feedbackData['createStamp'];
    } elseif (!$isUpdate) {
        $insert_data['createStamp'] = TIME_NOW;
    }

    foreach (RATING_TYPES as $ratingID => $ratingTypeData) {
        if (isset($feedbackData['ratingID' . $ratingID])) {
            $insert_data['ratingID' . $ratingID] = (int)$feedbackData['ratingID' . $ratingID];
        }
    }

    if ($isUpdate) {
        enums::$feedbackID = $feedbackID;

        $db->update_query('ougc_feedback', $insert_data, "feedbackID='{$feedbackID}'");
    } else {
        enums::$feedbackID = (int)$db->insert_query('ougc_feedback', $insert_data);
    }

    feedbackUserSync($insert_data['userID']);

    return enums::$feedbackID;
}

function feedbackUpdate(array $feedbackData, int $feedbackID): int
{
    return feedbackInsert($feedbackData, true, $feedbackID);
}

function feedbackDelete(int $feedbackID): bool
{
    global $db;

    $db->delete_query('ougc_feedback', "feedbackID='{$feedbackID}'");

    return true;
}

function ratingSyncUser(int $userID, int $ratingID, int $feedbackCode): bool
{
    global $db;

    $query = $db->simple_select(
        'ougc_feedback',
        "AVG(ratingID{$ratingID}) AS averageRatingValue",
        "userID='{$userID}' AND ratingTypeID='{$ratingID}' AND feedbackCode='{$feedbackCode}' AND feedbackStatus='1'"
    );

    $averageRatingValue = (float)$db->fetch_field($query, 'averageRatingValue');

    $db->update_query(
        'users',
        [('ougcFeedbackRatingAverage' . $ratingID) => $averageRatingValue],
        "uid='{$userID}'"
    );

    return true;
}

function sendPrivateMessage(array $privateMessageData, int $fromUserID = 0, bool $adminOverride = false): bool
{
    global $mybb;

    if (!$mybb->settings['ougc_feedback_allow_pm_notification']) {
        return false;
    }

    global $session;

    $privateMessageData['ipaddress'] = $privateMessageData['ipaddress'] ?? $session->packedip;

    return send_pm($privateMessageData, $fromUserID, $adminOverride);
}

function sendEmail(array $emailData): bool
{
    global $mybb, $db, $lang;

    if (!$mybb->settings['ougc_feedback_allow_email_notifications']) {
        return false;
    }

    // Load language
    if ($emailData['language'] != $mybb->user['language'] && $lang->language_exists($emailData['language'])) {
        $reset_lang = true;

        $lang->set_language($emailData['language']);

        loadLanguage(true);
    }

    foreach (['subject', 'message'] as $key) {
        $lang_string = $emailData[$key];

        if (is_array($emailData[$key])) {
            $num_args = count($emailData[$key]);

            for ($i = 1; $i < $num_args; $i++) {
                $lang->{$emailData[$key][0]} = str_replace(
                    '{' . $i . '}',
                    $emailData[$key][$i],
                    $lang->{$emailData[$key][0]}
                );
            }

            $lang_string = $emailData[$key][0];
        }

        $emailData[$key] = $lang->{$lang_string};
    }

    if (!$emailData['subject'] || !$emailData['message'] || !$emailData['to']) {
        return false;
    }

    my_mail($emailData['to'], $emailData['subject'], $emailData['message'], $emailData['from']);

    // Log the message
    if ($mybb->settings['mail_logging']) {
        $entry = [
            'subject' => $db->escape_string($emailData['subject']),
            'message' => $db->escape_string($emailData['message']),
            'createStamp' => TIME_NOW,
            'fromuid' => 0,
            'fromemail' => $db->escape_string($emailData['from']),
            'touid' => $emailData['touid'],
            'toemail' => $db->escape_string($emailData['to']),
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

function feedbackUserSync(int $userID): bool
{
    global $db;

    $query = $db->simple_select(
        'ougc_feedback',
        'SUM(feedbackValue) AS totalFeedback',
        "userID='{$userID}' AND feedbackStatus='1'"
    );

    $feedbackData = (int)$db->fetch_field($query, 'totalFeedback');

    $db->update_query('users', ['ougc_feedback' => $feedbackData], "uid='{$userID}'");

    foreach (RATING_TYPES as $ratingID => $ratingTypeData) {
        ratingSyncUser($userID, $ratingID, (int)$ratingTypeData['feedbackCode']);
    }

    return true;
}

function getUserStats(int $userID): array
{
    global $db;

    $whereClauses = [
        "userID='{$userID}'",
        /*"feedbackUserID!='0'", */
        "feedbackStatus='1'"
    ];
    /*if(!isModerator())
    {
        $whereClauses[] = "feedbackStatus='1'";
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

        $feedbackData['feedbackValue'] = (int)$feedbackData['feedbackValue'];

        switch ($feedbackData['feedbackValue']) {
            case 1:
                ++$userStats['positive'];

                $userStats['positive_users'][$feedbackData['feedbackUserID']] = 1;
                break;
            case 0:
                ++$userStats['neutral'];

                $userStats['neutral_users'][$feedbackData['feedbackUserID']] = 1;
                break;
            case -1:
                ++$userStats['negative'];

                $userStats['negative_users'][$feedbackData['feedbackUserID']] = 1;
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