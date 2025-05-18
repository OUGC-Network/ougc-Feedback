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

use function MyShowcase\Core\showcaseGet;
use function ougc\Feedback\Core\codeGet;
use function ougc\Feedback\Core\codeInsert;
use function ougc\Feedback\Core\loadLanguage;
use function ougc\Feedback\Core\ratingDelete;
use function ougc\Feedback\Core\ratingGet;
use function ougc\Feedback\Core\ratingInsert;
use function ougc\Feedback\Core\ratingUpdate;
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

$sub_tabs['ougcFeedbackRatings'] = [
    'title' => $lang->ougc_feedback_ratings_module_tab_ratings,
    'link' => urlHandlerBuild(),
    'description' => $lang->ougc_feedback_ratings_module_tab_ratings_description
];

$sub_tabs['ougcFeedbackCodes'] = [
    'title' => $lang->ougc_feedback_ratings_module_tab_codes,
    'link' => urlHandlerBuild(['action' => 'manageCodes']),
    'description' => $lang->ougc_feedback_ratings_module_tab_codes_description
];

$page->add_breadcrumb_item($lang->ougc_feedback, $sub_tabs['ougcFeedbackRatings']['link']);

if ($mybb->get_input('action') == 'deleteRating') {
    $ratingID = $mybb->get_input('ratingID', MyBB::INPUT_INT);

    $ratingData = ratingGet(["ratingID='{$ratingID}'"], [], ['limit' => 1]);

    if (!$ratingData) {
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

    $page->output_confirm_action(urlHandlerBuild(['action' => 'deleteRating', 'ratingID' => $ratingID])
    );
} elseif ($mybb->get_input('action') == 'manageCodes') {
    urlHandlerSet(urlHandlerBuild(['action' => 'manageCodes']));

    $page->output_header($lang->ougc_feedback);

    $page->output_nav_tabs($sub_tabs, 'ougcFeedbackCodes');

    $table = new Table();

    $table->construct_header($lang->ougc_feedback_rating_modules_code_header_id, ['width' => '1%']);

    $table->construct_header(
        $lang->ougc_feedback_rating_modules_code_header_type,
        ['width' => '50%', 'class' => 'align_center']
    );

    $table->construct_header(
        $lang->ougc_feedback_rating_modules_code_header_showcase,
        ['width' => '49%', 'class' => 'align_center']
    );

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

    $totalCodeIDs = ratingGet(
        [],
        ['COUNT(ratingID) AS total_code_ids'],
        ['limit' => 1]
    )['total_code_ids'] ?? 0;

    if ($totalCodeIDs < 1) {
        $table->construct_cell(
            '<div align="center">' . $lang->ougc_feedback_rating_modules_code_empty . '</div>',
            ['colspan' => 8]
        );

        $table->construct_row();

        $table->output($sub_tabs['ougcFeedbackCodes']['title']);
    } else {
        if ($mybb->request_method === 'post') {
            if ($mybb->get_input('create', MyBB::INPUT_INT)) {
                if (!$mybb->get_input('codeType', MyBB::INPUT_INT)) {
                    admin_redirect(urlHandlerBuild());
                }

                $codeType = $mybb->get_input('codeType', MyBB::INPUT_INT);

                $showcaseID = $mybb->get_input('showcaseID', MyBB::INPUT_INT);

                if (codeGet(["codeType='{$codeType}'", "showcaseID='{$showcaseID}'"])) {
                    flash_message($lang->ougc_feedback_rating_modules_error_code_duplicated, 'error');

                    admin_redirect(urlHandlerBuild());
                }

                codeInsert([
                    'codeType' => $codeType,
                    'showcaseID' => $showcaseID
                ]);

                flash_message($lang->ougc_feedback_rating_modules_code_success_created, 'success');

                admin_redirect(urlHandlerBuild());
            }
        }

        $form = new Form(urlHandlerBuild(), 'post');

        foreach (
            codeGet(
                [],
                [
                    'codeType',
                    'showcaseID',
                ],
                ['limit' => $perPage, 'limit_start' => $start]
            ) as $codeID => $codeData
        ) {
            $table->construct_cell(my_number_format($codeID));

            $codeType = '';

            switch ($codeData['codeType']) {
                case FEEDBACK_TYPE_POST;
                    $codeType = $lang->ougc_feedback_rating_modules_feedback_type_post;

                    break;
                case FEEDBACK_TYPE_PROFILE;
                    $codeType = $lang->ougc_feedback_rating_modules_feedback_type_profile;

                    break;
                case FEEDBACK_TYPE_CONTRACTS_SYSTEM;
                    $codeType = $lang->ougc_feedback_rating_modules_feedback_type_contract;

                    break;
                case FEEDBACK_TYPE_SHOWCASE_SYSTEM;
                    $codeType = $lang->ougc_feedback_rating_modules_feedback_type_showcase;

                    break;
            }

            $table->construct_cell(
                $codeType,
                ['class' => 'align_center']
            );

            $table->construct_cell(
                (function () use ($codeData): string {
                    if (!function_exists('\MyShowcase\Core\showcaseGet') || empty($codeData['showcaseID'])) {
                        return '';
                    }

                    return showcaseGet(
                        ["showcase_id={$codeData['showcaseID']}"],
                        ['name'],
                        ['limit' => 1]
                    )['name'] ?? (string)$codeData['showcaseID'];
                })(),
                ['class' => 'align_center']
            );

            $table->construct_row();
        }

        // Multipage
        if (($multipage = trim(
            draw_admin_pagination(
                $mybb->get_input('page', MyBB::INPUT_INT),
                $perPage,
                $totalCodeIDs,
                urlHandlerBuild()
            )
        ))) {
            echo $multipage;
        }

        $table->output($sub_tabs['ougcFeedbackCodes']['title']);

        $form->output_submit_wrapper(
            [
                $form->generate_submit_button($lang->ougc_feedback_rating_modules_form_code_button_update),
                $form->generate_reset_button($lang->reset)
            ]
        );

        $form->end();
    }

    echo '<br />';

    $form = new Form(urlHandlerBuild(), 'post');

    $formContainer = new FormContainer($lang->ougc_feedback_rating_modules_form_code_title);

    $formContainer->output_row_header(
        $lang->ougc_feedback_rating_modules_form_code_name . ' *',
        ['class' => 'align_center']
    );

    $formContainer->output_row_header(
        $lang->ougc_feedback_rating_modules_form_code_showcase . ' *',
        ['class' => 'align_center']
    );

    $formContainer->output_cell(
        $form->generate_select_box(
            'codeType',
            [
                FEEDBACK_TYPE_SHOWCASE_SYSTEM => $lang->ougc_feedback_rating_modules_feedback_type_showcase
            ],
            [],
            ['id' => 'codeType', 'class' => 'field150']
        ),
        ['class' => 'align_center']
    );

    $formContainer->output_cell(
        $form->generate_select_box(
            'showcaseID',
            (function (): array {
                if (!function_exists('\MyShowcase\Core\showcaseGet')) {
                    return [];
                }

                return array_map(function ($showcaseData) {
                    return $showcaseData['name'];
                }, showcaseGet([], ['name'], ['order_by' => 'name']));
            })(),
            [],
            ['id' => 'showcaseID', 'class' => 'field150']
        ),
        ['class' => 'align_center']
    );

    echo $form->generate_hidden_field('create', 1);

    $formContainer->construct_row();

    $formContainer->end();

    $form->output_submit_wrapper(
        [$form->generate_submit_button($lang->ougc_feedback_rating_modules_form_code_button_create)]
    );

    $form->end();

    $page->output_footer();
} else {
    $page->output_header($lang->ougc_feedback);

    $page->output_nav_tabs($sub_tabs, 'ougcFeedbackRatings');

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

        $table->output($sub_tabs['ougcFeedbackRatings']['title']);
    } else {
        if ($mybb->request_method === 'post') {
            if ($mybb->get_input('create', MyBB::INPUT_INT)) {
                if ($mybb->get_input('ratingName')) {
                    ratingInsert([
                        'ratingName' => $mybb->get_input('ratingName'),
                        'ratingClass' => 'yellow',
                        'ratingMaximumValue' => 5,
                        'feedbackCode' => FEEDBACK_TYPE_SHOWCASE_SYSTEM,
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
                foreach ($mybb->get_input($fieldName, MyBB::INPUT_ARRAY) as $ratingID => $ratingData) {
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
                foreach ($mybb->get_input($fieldName, MyBB::INPUT_ARRAY) as $ratingID => $ratingData) {
                    $updateData[$ratingID][$fieldName] = is_array($ratingData) ?
                        implode(',', $ratingData) : $ratingData;

                    if (my_strpos(',' . $updateData[$ratingID][$fieldName] . ',', ',-1,') !== false) {
                        $updateData[$ratingID][$fieldName] = -1;
                    }
                }
            }

            foreach ($updateData as $ratingID => $insertData) {
                ratingUpdate($insertData, $ratingID);
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
            ) as $ratingID => $ratingData
        ) {
            $table->construct_cell(my_number_format($ratingID));

            $table->construct_cell(
                $form->generate_text_box(
                    "ratingName[{$ratingID}]",
                    htmlspecialchars_uni($ratingData['ratingName']),
                    ['id' => 'ratingName', 'class' => 'field150']
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_text_box(
                    "ratingDescription[{$ratingID}]",
                    htmlspecialchars_uni($ratingData['ratingDescription']),
                    ['id' => 'ratingDescription']
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_text_box(
                    "ratingClass[{$ratingID}]",
                    htmlspecialchars_uni($ratingData['ratingClass']),
                    ['id' => 'ratingClass', 'class' => 'field150']
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_numeric_field(
                    "ratingMaximumValue[{$ratingID}]",
                    (int)$ratingData['ratingMaximumValue'],
                    ['id' => 'ratingMaximumValue', 'class' => 'field50']
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_select_box(
                    "feedbackCode[{$ratingID}]",
                    (function () use ($lang): array {
                        return array_map(function ($showcaseData) use ($lang) {
                            $codeType = '';

                            switch ($showcaseData['codeType']) {
                                case FEEDBACK_TYPE_POST;
                                    $codeType = $lang->ougc_feedback_rating_modules_feedback_type_post;

                                    break;
                                case FEEDBACK_TYPE_PROFILE;
                                    $codeType = $lang->ougc_feedback_rating_modules_feedback_type_profile;

                                    break;
                                case FEEDBACK_TYPE_CONTRACTS_SYSTEM;
                                    $codeType = $lang->ougc_feedback_rating_modules_feedback_type_contract;

                                    break;
                                case FEEDBACK_TYPE_SHOWCASE_SYSTEM;
                                    $codeType = $lang->ougc_feedback_rating_modules_feedback_type_showcase;

                                    break;
                            }

                            $showcaseName = (function () use ($showcaseData): string {
                                if (!function_exists(
                                        '\MyShowcase\Core\showcaseGet'
                                    ) || empty($showcaseData['showcaseID'])) {
                                    return '';
                                }

                                return showcaseGet(
                                    ["showcase_id={$showcaseData['showcaseID']}"],
                                    ['name'],
                                    ['limit' => 1]
                                )['name'] ?? (string)$showcaseData['showcaseID'];
                            })();

                            return $codeType . (!empty($showcaseData['showcaseID']) ? ' (' . $showcaseName . ')' : '');
                        }, codeGet([], ['codeType', 'showcaseID']));
                    })(),
                    [(int)$ratingData['feedbackCode']],
                    ['id' => 'feedbackCode', 'class' => 'field50']
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_select_box(
                    "allowedGroups[{$ratingID}][]",
                    $groupObjects,
                    is_array($ratingData['allowedGroups']) ? $ratingData['allowedGroups'] :
                        explode(',', $ratingData['allowedGroups']),
                    ['id' => 'allowedGroups', 'class' => 'field50', 'multiple' => true, 'size' => 5]
                ),
                ['class' => 'align_center']
            );

            $table->construct_cell(
                $form->generate_numeric_field(
                    "displayOrder[{$ratingID}]",
                    (int)$ratingData['displayOrder'],
                    ['id' => 'displayOrder', 'class' => 'field50']
                ),
                ['class' => 'align_center']
            );

            $popup = new PopupMenu('ratingID' . $ratingID, $lang->options);

            $popup->add_item(
                $lang->delete,
                urlHandlerBuild(['action' => 'deleteRating', 'ratingID' => $ratingID])
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

        $table->output($sub_tabs['ougcFeedbackRatings']['title']);

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
