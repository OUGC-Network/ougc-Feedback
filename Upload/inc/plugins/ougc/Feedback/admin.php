<?php

/***************************************************************************
 *
 *    OUGC Feedback plugin (/inc/plugins/ougc/Feedback/admin.php)
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

namespace ougc\Feedback\Admin;

use DirectoryIterator;
use PluginLibrary;
use stdClass;

use function ougc\Feedback\Core\loadLanguage;

use const ougc\Feedback\Core\FIELDS_DATA;
use const ougc\Feedback\Core\TABLES_DATA;
use const ougc\Feedback\ROOT;

function pluginInformation(): array
{
    global $lang;

    loadLanguage();

    return [
        'name' => 'OUGC Feedback',
        'description' => $lang->ougc_feedback_desc,
        'website' => 'https://ougc.network',
        'author' => 'Omar G.',
        'authorsite' => 'https://ougc.network',
        'version' => '1.8.23',
        'versioncode' => 1823,
        'compatibility' => '18*',
        'codename' => 'ougc_feedback',
        'pl' => [
            'version' => 13,
            'url' => 'https://community.mybb.com/mods.php?action=view&pid=573'
        ]
    ];
}

function pluginActivation(): bool
{
    global $PL, $cache, $lang;

    loadLanguage();

    $pluginInfo = pluginInformation();

    loadPluginLibrary();

    // TODO, showin_memberlist & allow_alert_notifications settings
    $settingsContents = file_get_contents(ROOT . '/settings.json');

    $settingsData = json_decode($settingsContents, true);

    foreach ($settingsData as $settingKey => &$settingData) {
        if (empty($lang->{"setting_ougc_feedback_{$settingKey}"})) {
            continue;
        }

        if (in_array($settingData['optionscode'], ['select', 'checkbox'])) {
            foreach ($settingData['options'] as $optionKey) {
                $optionValue = $optionKey;
                if (isset($lang->{"setting_ougc_feedback_{$settingKey}_{$optionKey}"})) {
                    $optionValue = $lang->{"setting_ougc_feedback_{$settingKey}_{$optionKey}"};
                }

                $settingData['optionscode'] .= "\n{$optionKey}={$optionValue}";
            }
        }

        $settingData['title'] = $lang->{"setting_ougc_feedback_{$settingKey}"};
        $settingData['description'] = $lang->{"setting_ougc_feedback_{$settingKey}_desc"};
    }

    $PL->settings(
        'ougc_feedback',
        $lang->setting_group_ougc_feedback,
        $lang->setting_group_ougc_feedback_desc,
        $settingsData
    );

    $templatesList = $stylesheetsList = [];

    /*'memberlist_header'	=> '<td class="tcat" width="10%" align="center"><span class="smalltext"><a href="{$sorturl}&amp;sort=feedbacks&amp;order=descending"><strong>{$lang->ougc_feedback_profile_title}</strong></a> {$orderarrow[\'feedback\']}</span></td>',
    'memberlist_sort'	=> '<option value="positive_feedback"{$sort_selected[\'positive_feedback\']}>{$lang->ougc_feedback_memberlist_sort_positive}</option>
<option value="neutral_feedback"{$sort_selected[\'neutral_feedback\']}>{$lang->ougc_feedback_memberlist_sort_neutral}</option>
<option value="negative_feedback"{$sort_selected[\'negative_feedback\']}>{$lang->ougc_feedback_memberlist_sort_negative}</option>',
    'memberlist_user'	=> '<td class="{$alt_bg}" align="center">{$user[\'uid\']}{$user[\'feedback\']}</td>',*/

    if (file_exists(ROOT . '/templates')) {
        $templatesDirIterator = new DirectoryIterator(ROOT . '/templates');

        foreach ($templatesDirIterator as $template) {
            if (!$template->isFile()) {
                continue;
            }

            $pathName = $template->getPathname();

            $pathInfo = pathinfo($pathName);

            if ($pathInfo['extension'] === 'html') {
                $templatesList[$pathInfo['filename']] = file_get_contents($pathName);
            }

            if ($pathInfo['extension'] === 'css') {
                $stylesheetsList[$pathInfo['filename']] = file_get_contents($pathName);
            }
        }
    }

    if ($templatesList) {
        $PL->templates('ougcfeedback', 'OUGC Feedback', $templatesList);
    }

    if ($stylesheetsList) {
        foreach ($stylesheetsList as $stylesheetName => $stylesheetContents) {
            $PL->stylesheet($stylesheetName, $stylesheetContents, ['feedback.php' => '', 'member.php' => 'profile']);
        }
    }

    // Insert/update version into cache
    $plugins = $cache->read('ougc_plugins');

    if (!$plugins) {
        $plugins = [];
    }

    if (!isset($plugins['feedback'])) {
        $plugins['feedback'] = $pluginInfo['versioncode'];
    }

    dbVerifyTables();

    dbVerifyColumns();

    // TODO:: ip should be stored

    /*~*~* RUN UPDATES START *~*~*/

    /*~*~* RUN UPDATES END *~*~*/

    $cache->update_forums();

    $cache->update_usergroups();

    $plugins['feedback'] = $pluginInfo['versioncode'];

    $cache->update('ougc_plugins', $plugins);

    return true;
}

function pluginDeactivation(): bool
{
    return true;
}

function pluginInstallation(): bool
{
    global $cache, $db;

    dbVerifyTables();

    dbVerifyColumns();

    // Administrators permissions
    $db->update_query(
        'usergroups',
        [
            'ougc_feedback_mod_candelete' => 1,
            'ougc_feedback_ismod' => 1,
            'ougc_feedback_mod_canedit' => 1,
            'ougc_feedback_mod_canremove' => 1
        ],
        "gid='4'"
    );

    // Super moderators permissions
    $db->update_query(
        'usergroups',
        [
            'ougc_feedback_ismod' => 1,
            'ougc_feedback_mod_canedit' => 1,
            'ougc_feedback_mod_canremove' => 1
        ],
        "gid='3'"
    );

    $cache->update_forums();

    $cache->update_usergroups();

    return true;
}

function pluginIsInstalled(): bool
{
    static $isInstalled = null;

    if ($isInstalled === null) {
        global $db;

        $isInstalled = false;

        foreach (dbTables() as $tableName => $tableData) {
            $isInstalled = $db->table_exists($tableName);

            break;
        }
    }

    return $isInstalled;
}

function pluginUninstallation(): bool
{
    global $db, $PL, $cache;

    loadPluginLibrary();

    foreach (TABLES_DATA as $tableName => $tableData) {
        if ($db->table_exists($tableName)) {
            $db->drop_table($tableName);
        }
    }

    foreach (FIELDS_DATA as $tableName => $tableColumns) {
        if ($db->table_exists($tableName)) {
            foreach ($tableColumns as $fieldName => $fieldData) {
                if ($db->field_exists($fieldName, $tableName)) {
                    $db->drop_column($tableName, $fieldName);
                }
            }
        }
    }

    $PL->settings_delete('ougc_feedback');

    $PL->templates_delete('ougcfeedback');

    // Delete version from cache
    $plugins = (array)$cache->read('ougc_plugins');

    if (isset($plugins['feedback'])) {
        unset($plugins['feedback']);
    }

    if (!empty($plugins)) {
        $cache->update('ougc_plugins', $plugins);
    } else {
        $cache->delete('ougc_plugins');
    }

    $cache->update_forums();

    $cache->update_usergroups();

    return true;
}

function dbTables(): array
{
    $tablesData = [];

    foreach (TABLES_DATA as $tableName => $tableColumns) {
        foreach ($tableColumns as $fieldName => $fieldData) {
            if (!isset($fieldData['type'])) {
                continue;
            }

            $tablesData[$tableName][$fieldName] = buildDbFieldDefinition($fieldData);
        }

        foreach ($tableColumns as $fieldName => $fieldData) {
            if (isset($fieldData['primary_key'])) {
                $tablesData[$tableName]['primary_key'] = $fieldName;
            }
            if ($fieldName === 'unique_key') {
                $tablesData[$tableName]['unique_key'] = $fieldData;
            }
        }
    }

    return $tablesData;
}

function dbVerifyTables(): bool
{
    global $db;

    $collation = $db->build_create_table_collation();

    $tablePrefix = $db->table_prefix;

    foreach (dbTables() as $tableName => $tableData) {
        if ($db->table_exists($tableName)) {
            foreach ($tableData as $fieldName => $fieldData) {
                if ($fieldName == 'primary_key' || $fieldName == 'unique_key') {
                    continue;
                }

                if ($db->field_exists($fieldName, $tableName)) {
                    $db->modify_column($tableName, "`{$fieldName}`", $fieldData);
                } else {
                    $db->add_column($tableName, $fieldName, $fieldData);
                }
            }
        } else {
            $query = "CREATE TABLE IF NOT EXISTS `{$tablePrefix}{$tableName}` (";

            foreach ($tableData as $fieldName => $fieldData) {
                if ($fieldName == 'primary_key') {
                    $query .= "PRIMARY KEY (`{$fieldData}`)";
                } elseif ($fieldName != 'unique_key') {
                    $query .= "`{$fieldName}` {$fieldData},";
                }
            }

            $query .= ") ENGINE=MyISAM{$collation};";

            $db->write_query($query);
        }
    }

    dbVerifyIndexes();

    return true;
}

function dbVerifyIndexes(): bool
{
    global $db;

    $tablePrefix = $db->table_prefix;

    foreach (dbTables() as $tableName => $tableData) {
        if (!$db->table_exists($tableName)) {
            continue;
        }

        if (isset($tableData['unique_key'])) {
            foreach ($tableData['unique_key'] as $keyName => $keyValue) {
                if ($db->index_exists($tableName, $keyName)) {
                    continue;
                }

                $db->write_query("ALTER TABLE {$tablePrefix}{$tableName} ADD UNIQUE KEY {$keyName} ({$keyValue})");
            }
        }
    }

    return true;
}

function buildDbFieldDefinition(array $fieldData): string
{
    $fieldDefinition = '';

    $fieldDefinition .= $fieldData['type'];

    if (isset($fieldData['size'])) {
        $fieldDefinition .= "({$fieldData['size']})";
    }

    if (isset($fieldData['unsigned'])) {
        if ($fieldData['unsigned'] === true) {
            $fieldDefinition .= ' UNSIGNED';
        } else {
            $fieldDefinition .= ' SIGNED';
        }
    }

    if (!isset($fieldData['null'])) {
        $fieldDefinition .= ' NOT';
    }

    $fieldDefinition .= ' NULL';

    if (isset($fieldData['auto_increment'])) {
        $fieldDefinition .= ' AUTO_INCREMENT';
    }

    if (isset($fieldData['default'])) {
        $fieldDefinition .= " DEFAULT '{$fieldData['default']}'";
    }

    return $fieldDefinition;
}

function dbVerifyColumns(): bool
{
    global $db;

    foreach (FIELDS_DATA as $tableName => $tableColumns) {
        foreach ($tableColumns as $fieldName => $fieldData) {
            if (!isset($fieldData['type'])) {
                continue;
            }

            if ($db->field_exists($fieldName, $tableName)) {
                $db->modify_column($tableName, "`{$fieldName}`", buildDbFieldDefinition($fieldData));
            } else {
                $db->add_column($tableName, $fieldName, buildDbFieldDefinition($fieldData));
            }
        }
    }

    return true;
}

function pluginLibraryRequirements(): stdClass
{
    return (object)pluginInformation()['pl'];
}

function loadPluginLibrary(): bool
{
    global $PL, $lang;

    loadLanguage();

    $fileExists = file_exists(PLUGINLIBRARY);

    if ($fileExists && !($PL instanceof PluginLibrary)) {
        require_once PLUGINLIBRARY;
    }

    if (!$fileExists || $PL->version < pluginLibraryRequirements()->version) {
        flash_message(
            $lang->sprintf(
                $lang->ougc_feedback_pluginlibrary_required,
                pluginLibraryRequirements()->url,
                pluginLibraryRequirements()->version
            ),
            'error'
        );

        admin_redirect('index.php?module=config-plugins');
    }

    return true;
}