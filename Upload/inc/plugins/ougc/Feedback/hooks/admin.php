<?php

/***************************************************************************
 *
 *    OUGC Feedback plugin (/inc/plugins/ougc/Feedback/hooks/admin.php)
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

namespace ougc\Feedback\Hooks\Admin;

use FormContainer;
use MyBB;

use function ougc\Feedback\Core\loadLanguage;

use const ougc\Feedback\Core\FIELDS_DATA;
use const ougc\Feedback\ROOT;

function admin_config_plugins_deactivate(): bool
{
    global $mybb, $page;

    if (
        $mybb->get_input('action') != 'deactivate' ||
        $mybb->get_input('plugin') != 'ougc_feedback' ||
        !$mybb->get_input('uninstall', MyBB::INPUT_INT)
    ) {
        return false;
    }

    if ($mybb->request_method != 'post') {
        $page->output_confirm_action(
            'index.php?module=config-plugins&amp;action=deactivate&amp;uninstall=1&amp;plugin=ougc_feedback'
        );
    }

    if ($mybb->get_input('no')) {
        admin_redirect('index.php?module=config-plugins');
    }

    return true;
}

function admin_config_settings_start(): bool
{
    loadLanguage();

    return true;
}

function admin_style_templates_set(): bool
{
    loadLanguage();

    return true;
}

function admin_config_settings_change(): bool
{
    loadLanguage();

    return true;
}

function admin_formcontainer_end(array &$hook_arguments): array
{
    global $run_module, $lang;

    if ($run_module == 'user' && isset($lang->users_permissions) && $hook_arguments['this']->_title == $lang->users_permissions) {
        global $form, $mybb;

        loadLanguage();

        $perms = [];

        foreach (FIELDS_DATA['usergroups'] as $name => $definition) {
            if ($name == 'ougc_feedback_maxperday') {
                $perms[] = "<br />{$lang->ougc_feedback_permission_maxperday}<br /><small>{$lang->ougc_feedback_permission_maxperday_desc}</small><br />{$form->generate_text_box($name, $mybb->get_input($name, MyBB::INPUT_INT), ['id' => $name, 'class' => 'field50'])}";
            } else {
                $lang_var = 'ougc_feedback_permission_' . str_replace('ougc_feedback_', '', $name);

                $perms[] = $form->generate_check_box(
                    $name,
                    1,
                    $lang->{$lang_var},
                    ['checked' => $mybb->get_input($name, MyBB::INPUT_INT)]
                );
            }
        }

        $hook_arguments['this']->output_row(
            $lang->setting_group_ougc_feedback,
            '',
            '<div class="group_settings_bit">' . implode(
                '</div><div class="group_settings_bit">',
                $perms
            ) . '</div>'
        );
    }

    if (
        $run_module == 'forum' &&
        isset($hook_arguments['this']->_title) &&
        (
            $hook_arguments['this']->_title == $lang->additional_forum_options ||
            $hook_arguments['this']->_title == "<div class=\"float_right\" style=\"font-weight: normal;\"><a href=\"#\" onclick=\"$('#additional_options_link').toggle(); $('#additional_options').fadeToggle('fast'); return false;\">{$lang->hide_additional_options}</a></div>" . $lang->additional_forum_options
        )
    ) {
        global $form, $forum_data;

        loadLanguage();

        $perms = [];

        foreach (FIELDS_DATA['forums'] as $name => $definition) {
            $lang_var = 'ougc_feedback_permission_' . str_replace('ougc_feedback_', '', $name);
            $perms[] = $form->generate_check_box(
                $name,
                1,
                $lang->{$lang_var},
                ['checked' => isset($forum_data[$name]) ? (int)$forum_data[$name] : 1]
            );
        }

        $hook_arguments['this']->output_row(
            $lang->setting_group_ougc_feedback,
            '',
            '<div class="forum_settings_bit">' . implode(
                '</div><div class="forum_settings_bit">',
                $perms
            ) . '</div>'
        );
    }

    return $hook_arguments;
}

function admin_user_groups_edit_commit(): bool
{
    global $updated_group, $mybb;

    $array_data = [];

    foreach (FIELDS_DATA['usergroups'] as $name => $definition) {
        $array_data[$name] = $mybb->get_input($name, MyBB::INPUT_INT);
    }

    $updated_group = array_merge($updated_group, $array_data);

    return true;
}

function admin_forum_management_add_commit(): bool
{
    admin_forum_management_edit_commit();

    return true;
}

function admin_forum_management_edit_commit(): bool
{
    global $db, $mybb, $fid, $plugins;

    $array_data = [];

    foreach (FIELDS_DATA['forums'] as $name => $definition) {
        $array_data[$name] = $mybb->get_input($name, MyBB::INPUT_INT);
    }

    $db->update_query('forums', $array_data, "fid='{$fid}'");

    $mybb->cache->update_forums();

    return true;
}