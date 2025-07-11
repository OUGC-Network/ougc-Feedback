<?php

/***************************************************************************
 *
 *    ougc Feedback plugin (/inc/plugins/ougc/Feedback/classes.php)
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

use MyShowcase\Plugin\FormTypes;

const PLUGIN_VERSION = '1.8.24';

const PLUGIN_VERSION_CODE = 1824;

const FEEDBACK_STATUS_SOFT_DELETED = -1;

const FEEDBACK_STATUS_UNAPPROVED = 0;

const FEEDBACK_STATUS_ACTIVE = 1;

const FEEDBACK_TYPE_BUYER = 1;

const FEEDBACK_TYPE_SELLER = 2;

const FEEDBACK_TYPE_TRADER = 3;

const FEEDBACK_VALUE_POSITIVE = 1;

const FEEDBACK_VALUE_NEUTRAL = 0;

const FEEDBACK_VALUE_NEGATIVE = -1;

const FEEDBACK_TYPE_POST = 1;

const FEEDBACK_TYPE_PROFILE = 2;

const FEEDBACK_TYPE_CONTRACTS_SYSTEM = 21;

const FEEDBACK_TYPE_SHOWCASE_SYSTEM = 22;

const POST_VISIBILITY_SOFT_DELETED = -1;

const POST_VISIBILITY_UNAPPROVED = 0;

const POST_VISIBILITY_APPROVED = 1;

const TABLES_DATA = [
    'ougc_feedback' => [
        'feedbackID' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'userID' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'feedbackUserID' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'uniqueID' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'feedbackType' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'feedbackValue' => [
            'type' => 'TINYINT',
            'default' => 0
        ],
        'feedbackComment' => [
            'type' => 'TEXT',
            'null' => true
        ],
        'feedbackStatus' => [
            'type' => 'TINYINT',
            'default' => 1
        ],
        'feedbackCode' => [//codeID
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'createStamp' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        // todo, legacy KEY uid (uid) skipped
    ],
    'ougcFeedbackCodes' => [
        'codeID' => [ //feedbackCode
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'codeType' => [//post,profile,contract,showcase
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'showcaseID' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'unique_key' => ['code_type_showcase_id' => 'codeType,showcaseID'],
    ],
    'ougcFeedbackRatings' => [
        'ratingID' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'ratingName' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => ''
        ],
        'ratingDescription' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => ''
        ],
        'ratingClass' => [
            'type' => 'VARCHAR',
            'size' => 255,
            'default' => ''
        ],
        'ratingMaximumValue' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'feedbackCode' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'allowedGroups' => [
            'type' => 'TEXT',
            'null' => true
        ],
        'displayOrder' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
    ],
];

const FIELDS_DATA = [
    'usergroups' => [
        'ougc_feedback_canview' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
        ],
        'ougc_feedback_cangive' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
        ],
        'ougc_feedback_canreceive' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
        ],
        'ougc_feedback_canedit' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
        ],
        'ougc_feedback_canremove' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
        ],
        /*'ougc_feedback_value' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 1
        ],*/
        'ougc_feedback_maxperday' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 5
        ],
        'ougc_feedback_ismod' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'ougc_feedback_mod_canedit' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'ougc_feedback_mod_canremove' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'ougc_feedback_mod_candelete' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ]
    ],
    'forums' => [
        'ougc_feedback_allow_threads' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
        ],
        'ougc_feedback_allow_posts' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 1,
        ]
    ],
    'users' => [
        'ougc_feedback_notification' => [
            'type' => 'VARCHAR',
            'size' => 5,
            'default' => '',
        ],
        'ougc_feedback' => [
            'type' => 'INT',
            'default' => 0
        ]
    ]
];

define('ougc\Feedback\Core\FIELDS_DATA_SHOWCASE', [
    'myshowcase_config' => [
        'display_feedback_entries' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'formCategory' => 'other',
            'formSection' => 'display',
            'formType' => class_exists('\MyShowcase\Plugin\FormTypes') ? FormTypes::CheckBox : '',
        ],
        'display_feedback_comments' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'formCategory' => 'other',
            'formSection' => 'display',
            'formType' => class_exists('\MyShowcase\Plugin\FormTypes') ? FormTypes::CheckBox : '',
        ],
        'ougc_feedback_allow_entries' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0,
            'formCategory' => 'other',
            'formSection' => 'feedback',
            'formType' => class_exists('\MyShowcase\Plugin\FormTypes') ? FormTypes::CheckBox : '',
        ]
    ]
]);

class enums
{
    public static $feedbackID = 0;

    public function __construct()
    {
        backButtonSet();
    }
}