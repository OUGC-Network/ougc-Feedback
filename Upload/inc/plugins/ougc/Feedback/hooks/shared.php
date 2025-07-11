<?php

/***************************************************************************
 *
 *    ougc Feedback plugin (/inc/plugins/ougc/Feedback/hooks/shared.php)
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

namespace ougc\Feedback\Hooks\Shared;

use MyBB;

use function MyShowcase\Plugin\Core\sanitizeTableFieldValue;

use const ougc\Feedback\Core\FIELDS_DATA_SHOWCASE;

function myshowcase_system_showcase_insert_update_end(array &$hookArguments): array
{
    $hookArguments['tableFields'] = array_merge_recursive(
        $hookArguments['tableFields'],
        FIELDS_DATA_SHOWCASE['myshowcase_config']
    );

    foreach (FIELDS_DATA_SHOWCASE['myshowcase_config'] as $fieldName => $fieldDefinition) {
        if (isset($hookArguments['showcaseData'][$fieldName])) {
            $hookArguments['insertData'][$fieldName] = sanitizeTableFieldValue(
                $hookArguments['showcaseData'][$fieldName],
                $fieldDefinition['type']
            );
        }
    }

    return $hookArguments;
}

function myshowcase_system_cache_update_start(array &$hookArguments): array
{
    $hookArguments['tableFields'] = array_merge_recursive(
        $hookArguments['tableFields'],
        FIELDS_DATA_SHOWCASE
    );

    return $hookArguments;
}