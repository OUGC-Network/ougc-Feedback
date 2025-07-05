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

const URL = 'feedback.php';

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

function urlHandler(string $newUrl = ''): string
{
    static $setUrl = URL;

    if (($newUrl = trim($newUrl))) {
        $setUrl = $newUrl;
    }

    return $setUrl;
}

function urlHandlerSet(string $newUrl)
{
    urlHandler($newUrl);
}

function urlHandlerGet(): string
{
    return urlHandler();
}

function urlHandlerBuild(array $urlAppend = [], bool $fetchImportUrl = false, bool $encode = true): string
{
    global $PL;

    if (!is_object($PL)) {
        $PL || require_once PLUGINLIBRARY;
    }

    if ($fetchImportUrl === false) {
        if ($urlAppend && !is_array($urlAppend)) {
            $urlAppend = explode('=', $urlAppend);
            $urlAppend = [$urlAppend[0] => $urlAppend[1]];
        }
    }

    return $PL->url_append(urlHandlerGet(), $urlAppend, '&amp;', $encode);
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
    string $modalMessage,
    string $modalTitle = '',
    bool $isSuccess = false,
    string $replacement = '',
    int $hide_add = 1
) {
    global $templates, $lang, $theme, $mybb;

    loadLanguage();

    $modalTitle = $modalTitle ?: $lang->error;

    $modalMessage = $modalMessage ?: $lang->message;

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
        $modalMessage = eval(getTemplate('modal_error'));

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
    global $feedbackData;

    loadLanguage();

    $feedbackID = $mybb->get_input('feedbackID', MyBB::INPUT_INT);

    $feedbackType = $mybb->get_input('feedbackType', MyBB::INPUT_INT);

    $feedbackValue = $mybb->get_input('feedbackValue', MyBB::INPUT_INT);

    $reloadModal = $mybb->get_input('reload', MyBB::INPUT_INT);

    $feedbackComment = $mybb->get_input('feedbackComment');

    $userID = $feedbackData['userID'];

    $uniqueID = $feedbackData['uniqueID'];

    $feedbackPluginCode = $mybb->get_input('feedbackCode', MyBB::INPUT_INT);

    if ($mybb->get_input('action') === 'edit') {
        $buttonCode = eval(getTemplate('modalFooterEdit'));
    } else {
        $buttonCode = eval(getTemplate('modalFooterAdd'));
    }

    return eval(getTemplate('modalFooter'));
}

function feedbackGet(array $whereClauses, array $queryFields = [], array $queryOptions = []): array
{
    global $db;

    $query = $db->simple_select(
        'ougc_feedback',
        implode(',', array_merge(['feedbackID'], $queryFields)),
        implode(' AND ', $whereClauses),
        $queryOptions
    );

    if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
        return (array)$db->fetch_array($query);
    }

    $feedbackObjects = [];

    while ($feedbackData = $db->fetch_array($query)) {
        $feedbackObjects[(int)$feedbackData['feedbackID']] = $feedbackData;
    }

    return $feedbackObjects;
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

    foreach (ratingGet() as $ratingID => $ratingData) {
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

function ratingGet(array $whereClauses = [], array $queryFields = [], array $queryOptions = []): array
{
    global $db;

    $queryFields[] = 'ratingID';

    if (!empty($queryOptions['group_by'])) {
        $queryOptions['group_by'] .= ', ratingID';
    }

    $query = $db->simple_select(
        'ougcFeedbackRatings',
        implode(',', $queryFields),
        implode(' AND ', $whereClauses),
        $queryOptions
    );

    if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
        return (array)$db->fetch_array($query);
    }

    $feedbackObjects = [];

    while ($ratingData = $db->fetch_array($query)) {
        $feedbackObjects[(int)$ratingData['ratingID']] = $ratingData;
    }

    return $feedbackObjects;
}

function codeInsert(array $codeData, bool $isUpdate = false, int $codeID = 0): int
{
    global $db;

    $insertData = [];

    if (isset($codeData['codeID'])) {
        $insertData['codeID'] = (int)$codeData['codeID'];
    }

    if (isset($codeData['codeType'])) {
        $insertData['codeType'] = (int)$codeData['codeType'];
    }

    if (isset($codeData['showcaseID'])) {
        $insertData['showcaseID'] = (int)$codeData['showcaseID'];
    }

    if ($isUpdate) {
        $db->update_query('ougcFeedbackCodes', $insertData, "codeID='{$codeID}'");
    } else {
        $codeID = (int)$db->insert_query('ougcFeedbackCodes', $insertData);
    }

    return $codeID;
}

function codeUpdate(array $codeData, int $codeID = 0): int
{
    return codeInsert($codeData, true, $codeID);
}

function codeGet(array $whereClauses = [], array $queryFields = [], array $queryOptions = []): array
{
    global $db;

    $queryFields[] = 'codeID';

    $query = $db->simple_select(
        'ougcFeedbackCodes',
        implode(',', $queryFields),
        implode(' AND ', $whereClauses),
        $queryOptions
    );

    if (isset($queryOptions['limit']) && $queryOptions['limit'] === 1) {
        return (array)$db->fetch_array($query);
    }

    $codeObjects = [];

    while ($codeData = $db->fetch_array($query)) {
        $codeObjects[(int)$codeData['codeID']] = $codeData;
    }

    return $codeObjects;
}

function ratingInsert(array $ratingData, bool $isUpdate = false, int $ratingID = 0): int
{
    global $db;

    $insertData = [];

    if (isset($ratingData['ratingName'])) {
        $insertData['ratingName'] = $db->escape_string($ratingData['ratingName']);
    }

    if (isset($ratingData['ratingDescription'])) {
        $insertData['ratingDescription'] = $db->escape_string($ratingData['ratingDescription']);
    }

    if (isset($ratingData['ratingClass'])) {
        $insertData['ratingClass'] = $db->escape_string($ratingData['ratingClass']);
    }

    if (isset($ratingData['ratingMaximumValue'])) {
        $insertData['ratingMaximumValue'] = (int)$ratingData['ratingMaximumValue'];
    }

    if (isset($ratingData['feedbackCode'])) {
        $insertData['feedbackCode'] = (int)$ratingData['feedbackCode'];
    }

    if (isset($ratingData['allowedGroups'])) {
        $insertData['allowedGroups'] = $db->escape_string($ratingData['allowedGroups']);
    }

    if (isset($ratingData['displayOrder'])) {
        $insertData['displayOrder'] = (int)$ratingData['displayOrder'];
    }

    if ($isUpdate) {
        $db->update_query('ougcFeedbackRatings', $insertData, "ratingID='{$ratingID}'");
    } else {
        $ratingID = (int)$db->insert_query('ougcFeedbackRatings', $insertData);
    }

    return $ratingID;
}

function ratingUpdate(array $ratingData, int $ratingID = 0): int
{
    return ratingInsert($ratingData, true, $ratingID);
}

function ratingDelete(int $ratingID): bool
{
    global $db;

    if ($db->field_exists('ougcFeedbackRatingAverage' . $ratingID, 'users')) {
        $db->drop_column('users', 'ougcFeedbackRatingAverage' . $ratingID);
    }

    $db->delete_query('ougcFeedbackRatings', "ratingID='{$ratingID}'");

    return true;
}

function sendPrivateMessage(array $privateMessageData, int $fromUserID = 0, bool $adminOverride = false): bool
{
    global $mybb;

    if (empty($mybb->settings['ougc_feedback_allow_pm_notification'])) {
        return false;
    }

    global $session;

    $privateMessageData['ipaddress'] = $privateMessageData['ipaddress'] ?? $session->packedip;

    return send_pm($privateMessageData, $fromUserID, $adminOverride);
}

function sendEmail(array $emailData): bool
{
    global $mybb, $db, $lang;

    if (empty($mybb->settings['ougc_feedback_allow_email_notifications'])) {
        return false;
    }

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
        "userID='{$userID}' AND feedbackStatus='1'",
        ['group_by' => 'userID']
    );

    $feedbackData = (int)$db->fetch_field($query, 'totalFeedback');

    $db->update_query('users', ['ougc_feedback' => $feedbackData], "uid='{$userID}'");

    foreach (
        ratingGet(
            [],
            ['feedbackCode']
        ) as $ratingID => $ratingData
    ) {
        $feedbackCode = (int)$ratingData['feedbackCode'];

        $query = $db->simple_select(
            'ougc_feedback',
            "AVG(ratingID{$ratingID}) AS averageRatingValue",
            "userID='{$userID}' AND feedbackCode='{$feedbackCode}' AND feedbackStatus='1'"
        );

        $averageRatingValue = (float)$db->fetch_field($query, 'averageRatingValue');

        $db->update_query(
            'users',
            [('ougcFeedbackRatingAverage' . $ratingID) => $averageRatingValue],
            "uid='{$userID}'"
        );
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

    $feedbackFields = [
        'userID',
        'feedbackUserID',
        'feedbackValue'
    ];

    $feedbackObjects = feedbackGet($whereClauses, $feedbackFields);

    foreach ($feedbackObjects as $feedbackID => $feedbackData) {
        ++$userStats['total'];

        $feedbackValue = (int)$feedbackData['feedbackValue'];

        switch ($feedbackValue) {
            case FEEDBACK_VALUE_POSITIVE:
                ++$userStats['positive'];

                $userStats['positive_users'][$feedbackData['feedbackUserID']] = 1;
                break;
            case FEEDBACK_VALUE_NEUTRAL:
                ++$userStats['neutral'];

                $userStats['neutral_users'][$feedbackData['feedbackUserID']] = 1;
                break;
            case FEEDBACK_VALUE_NEGATIVE:
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

function feedBackCodeIsPost(int $feedbackCode): bool
{
    return $feedbackCode === FEEDBACK_TYPE_POST;
}

function feedBackCodeIsProfile(int $feedbackCode): bool
{
    return $feedbackCode === FEEDBACK_TYPE_PROFILE;
}

function feedBackCodeIsContract(int $feedbackCode): bool
{
    return $feedbackCode === FEEDBACK_TYPE_CONTRACTS_SYSTEM;
}

function feedBackCodeGetPost(): int
{
    return FEEDBACK_TYPE_POST;
}

function feedBackCodeGetProfile(): int
{
    return FEEDBACK_TYPE_PROFILE;
}

function feedBackCodeGetContract(): int
{
    return FEEDBACK_TYPE_CONTRACTS_SYSTEM;
}