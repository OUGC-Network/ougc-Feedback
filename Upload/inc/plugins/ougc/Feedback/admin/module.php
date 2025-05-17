<?php

/***************************************************************************
 *
 *    ougc Feedback plugin (/inc/plugins/ougc/Feedback/admin/module.php)
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

use function ougc\Feedback\Core\loadLanguage;
use function ougc\Feedback\Core\ratingDelete;
use function ougc\Feedback\Core\ratingGet;
use function ougc\Feedback\Core\ratingInsert;
use function ougc\Feedback\Core\urlHandlerBuild;
use function ougc\Feedback\Core\urlHandlerSet;

use const ougc\Feedback\Core\FEEDBACK_TYPE_CONTRACTS_SYSTEM;
use const ougc\Feedback\Core\FEEDBACK_TYPE_POST;
use const ougc\Feedback\Core\FEEDBACK_TYPE_PROFILE;
use const ougc\Feedback\Core\FEEDBACK_TYPE_SHOWCASE_SYSTEM;

defined('IN_MYBB') || die('Direct initialization of this file is not allowed.');

global $mybb, $db, $lang, $plugins;
global $page;

urlHandlerSet('index.php?module=config-feedback');

loadLanguage();

$sub_tabs['ougcFeedbackView'] = [
    'title' => $lang->ougc_feedback_ratings_module_tab_view,
    'link' => urlHandlerBuild(),
    'description' => $lang->ougc_feedback_ratings_module_tab_view_description
];

$page->add_breadcrumb_item($lang->ougc_feedback, $sub_tabs['ougcFeedbackView']['link']);

if ($mybb->get_input('action') == 'delete') {
    $ratingID = $mybb->get_input('ratingID', MyBB::INPUT_INT);

    $ratingTypeData = ratingGet(["ratingID='{$ratingID}'"], [], ['limit' => 1]);

    if (!$ratingTypeData) {
        _dump(123);
        admin_redirect(urlHandlerBuild());
    }

    if ($mybb->request_method == 'post') {
        if (isset($mybb->input['no']) || !verify_post_check($mybb->get_input('my_post_key'), true)) {
            admin_redirect(urlHandlerBuild());
        }

        ratingDelete($ratingID);

        flash_message($lang->ougc_feedback_rating_modules_success_deleted, 'success');

        admin_redirect(urlHandlerBuild());
    }

    $page->add_breadcrumb_item($lang->delete);

    $page->output_confirm_action(urlHandlerBuild(['action' => 'delete', 'ratingID' => $ratingID])
    );
} else {
    $page->output_header($lang->ougc_feedback);

    $page->output_nav_tabs($sub_tabs, 'ougcFeedbackView');

    $table = new Table();

    $table->construct_header($lang->ougc_feedback_rating_modules_header_id, ['width' => '1%']);

    $table->construct_header(
        $lang->ougc_feedback_rating_modules_header_name,
        ['width' => '10%', 'class' => 'align_center']
    );

    $table->construct_header(
        $lang->ougc_feedback_rating_modules_header_description,
        ['width' => '15%', 'class' => 'align_center']
    );

    $table->construct_header(
        $lang->ougc_feedback_rating_modules_header_class,
        ['width' => '10%', 'class' => 'align_center']
    );

    $table->construct_header(
        $lang->ougc_feedback_rating_modules_header_maximum_value,
        ['width' => '6%', 'class' => 'align_center']
    );

    $table->construct_header(
        $lang->ougc_feedback_rating_modules_header_code,
        ['width' => '10%', 'class' => 'align_center']
    );

    $table->construct_header(
        $lang->ougc_feedback_rating_modules_header_allowed_groups,
        ['width' => '10%', 'class' => 'align_center']
    );

    $table->construct_header(
        $lang->ougc_feedback_rating_modules_header_display_order,
        ['width' => '10%', 'class' => 'align_center']
    );

    $table->construct_header($lang->options, ['width' => '10%', 'class' => 'align_center']);

    // Multi-page support
    $perPage = 10;

    if ($perPage < 1) {
        $perPage = 10;
    } elseif ($perPage > 100) {
        $perPage = 100;
    }

    if ($mybb->get_input('page', MyBB::INPUT_INT) > 0) {
        $start = ($mybb->get_input('page', MyBB::INPUT_INT) - 1) * $perPage;
    } else {
        $start = 0;

        $mybb->input['page'] = 1;
    }

    $totalRatingTypes = ratingGet(
        [],
        ['COUNT(ratingID) AS total_rating_types'],
        ['limit' => 1]
    )['total_rating_types'] ?? 0;

    if ($totalRatingTypes < 1) {
        $table->construct_cell(
            '<div align="center">' . $lang->ougc_feedback_rating_modules_empty . '</div>',
            ['colspan' => 8]
        );

        $table->construct_row();

        $table->output($sub_tabs['ougcFeedbackView']['title']);
    } else {
        if ($mybb->request_method === 'post') {
            if ($mybb->get_input('create', MyBB::INPUT_INT)) {
                if ($mybb->get_input('ratingName')) {
                    ratingInsert([
                        'ratingName' => $mybb->get_input('ratingName'),
                        'ratingClass' => 'yellow',
                        'ratingMaximumValue' => 5,
                        'feedbackCode' => FEEDBACK_TYPE_POST,
                        'displayOrder' => (ratingGet(
                                [],
                                ['MAX(displayOrder) AS max_display_order'],
                                ['limit' => 1]
                            )['max_display_order'] ?? 0) + 1
                    ]);
                }

                admin_redirect(urlHandlerBuild());
            }

            $updateData = [];

            foreach (
                [
                    'ratingName',
                    'ratingDescription',
                    'ratingClass',
                    'ratingMaximumValue',
                    'feedbackCode',
                    'allowedGroups',
                    'displayOrder'
                ] as $fieldName
            ) {
                foreach ($mybb->get_input($fieldName, MyBB::INPUT_ARRAY) as $ratingID => $ratingTypeData) {
                    $updateData[$ratingID] = [
                        'ratingName' => '',
                        'ratingDescription' => '',
                        'ratingClass' => '',
                        'ratingMaximumValue' => '',
                        'feedbackCode' => '',
                        'allowedGroups' => '',
                        'displayOrder' => ''
                    ];
                }
            }

            foreach (
                [
                    'ratingName',
                    'ratingDescription',
                    'ratingClass',
                    'ratingMaximumValue',
                    'feedbackCode',
                    'allowedGroups',
                    'displayOrder'
                ] as $fieldName
            ) {
                foreach ($mybb->get_input($fieldName, MyBB::INPUT_ARRAY) as $ratingID => $ratingTypeData) {
                    $updateData[$ratingID][$fieldName] = is_array($ratingTypeData) ?
                        implode(',', $ratingTypeData) : $ratingTypeData;

                    if (my_strpos(',' . $updateData[$ratingID][$fieldName] . ',', ',-1,') !== false) {
                        $updateData[$ratingID][$fieldName] = -1;
                    }
                }
            }

            foreach ($updateData as $ratingID => $insertData) {
                \ougc\Feedback\Core\ratingUpdate($insertData, $ratingID);
            }

            flash_message($lang->ougc_feedback_rating_modules_success_updated, 'success');

            admin_redirect(urlHandlerBuild());
        }

        $groupObjects = (function () use ($lang): array {
            global $cache;

            $groupList = [
                -1 => $lang->all_groups
            ];

            foreach ((array)$cache->read('usergroups') as $groupData) {
                $groupList[(int)$groupData['gid']] = strip_tags($groupData['title']);
            }

            return $groupList;
        })();

        $form = new Form(urlHandlerBuild(), 'post');

        foreach (
            ratingGet(
                [],
                [
                    'ratingName',
                    'ratingDescription',
                    'ratingClass',
                    'ratingMaximumValue',
                    'feedbackCode',
                    'allowedGroups',
                    'displayOrder',
                ],
                ['limit' => $perPage, 'limit_start' => $start, 'order_by' => 'displayOrder']
            ) as $ratingID => $ratingTypeData
        ) {
            $table->construct_cell(my_number_format($ratingID));

            $table->construct_cell(
                $form->generate_text_box(
                    "ratingName[{$ratingID}]",
                    htmlspecialchars_uni($ratingTypeData['ratingName']),
                    ['id' => 'ratingName', 'class' => 'field150']
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_text_box(
                    "ratingDescription[{$ratingID}]",
                    htmlspecialchars_uni($ratingTypeData['ratingDescription']),
                    ['id' => 'ratingDescription']
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_text_box(
                    "ratingClass[{$ratingID}]",
                    htmlspecialchars_uni($ratingTypeData['ratingClass']),
                    ['id' => 'ratingClass', 'class' => 'field150']
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_numeric_field(
                    "ratingMaximumValue[{$ratingID}]",
                    (int)$ratingTypeData['ratingMaximumValue'],
                    ['id' => 'ratingMaximumValue', 'class' => 'field50']
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_select_box(
                    "feedbackCode[{$ratingID}]",
                    [
                        FEEDBACK_TYPE_POST => $lang->ougc_feedback_rating_modules_feedback_type_post,
                        FEEDBACK_TYPE_PROFILE => $lang->ougc_feedback_rating_modules_feedback_type_profile,
                        FEEDBACK_TYPE_CONTRACTS_SYSTEM => $lang->ougc_feedback_rating_modules_feedback_type_contract,
                        FEEDBACK_TYPE_SHOWCASE_SYSTEM => $lang->ougc_feedback_rating_modules_feedback_type_showcase
                    ],
                    [(int)$ratingTypeData['feedbackCode']],
                    ['id' => 'feedbackCode', 'class' => 'field50']
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_select_box(
                    "allowedGroups[{$ratingID}][]",
                    $groupObjects,
                    is_array($ratingTypeData['allowedGroups']) ? $ratingTypeData['allowedGroups'] :
                        explode(',', $ratingTypeData['allowedGroups']),
                    ['id' => 'allowedGroups', 'class' => 'field50', 'multiple' => true, 'size' => 5]
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_numeric_field(
                    "displayOrder[{$ratingID}]",
                    (int)$ratingTypeData['displayOrder'],
                    ['id' => 'displayOrder', 'class' => 'field50']
                ),
                ['class' => 'align_center']
            );

            $popup = new PopupMenu('ratingID' . $ratingID, $lang->options);

            $popup->add_item(
                $lang->delete,
                urlHandlerBuild(['action' => 'delete', 'ratingID' => $ratingID])
            );

            $table->construct_cell($popup->fetch(), ['class' => 'align_center']);

            $table->construct_row();
        }

        // Multipage
        if (($multipage = trim(
            draw_admin_pagination(
                $mybb->get_input('page', MyBB::INPUT_INT),
                $perPage,
                $totalRatingTypes,
                urlHandlerBuild()
            )
        ))) {
            echo $multipage;
        }

        $table->output($sub_tabs['ougcFeedbackView']['title']);

        $form->output_submit_wrapper(
            [
                $form->generate_submit_button($lang->ougc_feedback_rating_modules_form_button_update),
                $form->generate_reset_button($lang->reset)
            ]
        );

        $form->end();
    }

    echo '<br />';

    $form = new Form(urlHandlerBuild(), 'post');

    $formContainer = new FormContainer($lang->ougc_feedback_rating_modules_form_title);

    $formContainer->output_row_header(
        $lang->ougc_feedback_rating_modules_form_name . ' *',
        ['class' => 'align_center']
    );

    $formContainer->output_cell(
        $form->generate_text_box('ratingName', '', ['id' => 'ratingName', 'class' => 'field150']),
        ['class' => 'align_center']
    );

    echo $form->generate_hidden_field('create', 1);

    $formContainer->construct_row();

    $formContainer->end();

    $form->output_submit_wrapper([$form->generate_submit_button($lang->ougc_feedback_rating_modules_form_button_create)]
    );

    $form->end();

    $page->output_footer();
}

exit;
