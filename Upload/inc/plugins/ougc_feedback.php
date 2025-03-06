<?php

/***************************************************************************
 *
 *    ougc Feedback plugin (/inc/plugins/ougc_feedback.php)
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

use ougc\Feedback\Core\enums;

use function ougc\Feedback\Admin\pluginActivation;
use function ougc\Feedback\Admin\pluginDeactivation;
use function ougc\Feedback\Admin\pluginInformation;
use function ougc\Feedback\Admin\pluginInstallation;
use function ougc\Feedback\Admin\pluginIsInstalled;
use function ougc\Feedback\Admin\pluginUninstallation;
use function ougc\Feedback\Core\addHooks;

use const ougc\Feedback\ROOT;

defined('IN_MYBB') || die('Direct initialization of this file is not allowed.');

// You can uncomment the lines below to avoid storing some settings in the DB
define('ougc\Feedback\Core\SETTINGS', [
    //'key' => '',
]);

define('ougc\Feedback\Core\DEBUG', false);

define('ougc\Feedback\ROOT', constant('MYBB_ROOT') . 'inc/plugins/ougc/Feedback');

defined('PLUGINLIBRARY') || define('PLUGINLIBRARY', MYBB_ROOT . 'inc/plugins/pluginlibrary.php');

require_once ROOT . '/core.php';
require_once ROOT . '/classes.php';

if (defined('IN_ADMINCP')) {
    require_once ROOT . '/admin.php';

    require_once ROOT . '/hooks/admin.php';

    addHooks('ougc\Feedback\Hooks\Admin');
}

require_once ROOT . '/hooks/forum.php';

addHooks('ougc\Feedback\Hooks\Forum');

$GLOBALS['ougcFeedback'] = new enums();

function ougc_feedback_info(): array
{
    return pluginInformation();
}

function ougc_feedback_activate(): bool
{
    return pluginActivation();
}

function ougc_feedback_deactivate(): bool
{
    return pluginDeactivation();
}

function ougc_feedback_install(): bool
{
    return pluginInstallation();
}

function ougc_feedback_is_installed(): bool
{
    return pluginIsInstalled();
}

function ougc_feedback_uninstall(): bool
{
    return pluginUninstallation();
}