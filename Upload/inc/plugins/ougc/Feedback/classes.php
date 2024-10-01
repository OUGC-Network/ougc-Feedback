<?php

/***************************************************************************
 *
 *    OUGC Feedback plugin (/inc/plugins/ougc/Feedback/classes.php)
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

const PLUGIN_VERSION = '1.8.24';

const PLUGIN_VERSION_CODE = 1824;

const FEEDBACK_TYPE_BUYER = 1;

const FEEDBACK_TYPE_SELLER = 2;

const FEEDBACK_TYPE_TRADER = 3;

const FEEDBACK_TYPE_POSITIVE = 1;

const FEEDBACK_TYPE_NEUTRAL = 0;

const FEEDBACK_TYPE_NEGATIVE = -1;

const FEEDBACK_TYPE_POST = 1;

const FEEDBACK_TYPE_PROFILE = 2;

const TABLES_DATA = [
    'ougc_feedback' => [
        'fid' => [
            'type' => 'INT',
            'unsigned' => true,
            'auto_increment' => true,
            'primary_key' => true
        ],
        'uid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'fuid' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'unique_id' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'type' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        'feedback' => [
            'type' => 'INT',
            'default' => 0
        ],
        'comment' => [
            'type' => 'TEXT',
            'null' => true
        ],
        'status' => [
            'type' => 'TINYINT',
            'default' => 1
        ],
        'feedback_code' => [
            'type' => 'TINYINT',
            'unsigned' => true,
            'default' => 0
        ],
        'dateline' => [
            'type' => 'INT',
            'unsigned' => true,
            'default' => 0
        ],
        // todo, legacy KEY uid (uid) skipped
    ]
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
            'unsigned' => true,
            'default' => 0
        ]
    ]
];

class enums
{
    public static $fid = 0;

    public function __construct()
    {
        set_go_back_button();
    }
}