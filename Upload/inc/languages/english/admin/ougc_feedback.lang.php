<?php

/***************************************************************************
 *
 *	OUGC Feedback plugin (/inc/languages/english/admin/ougc_feedback.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Adds a powerful feedback system to your forum.
 *
 ***************************************************************************

****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/
 
// Plugin APIC
$l['setting_group_ougc_feedback'] = 'OUGC Feedback';
$l['setting_group_ougc_feedback_desc'] = 'Adds a powerful feedback system to your forum.';

// PluginLibrary
$l['ougc_feedback_pluginlibrary_required'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.';
$l['ougc_feedback_pluginlibrary_old'] = 'This plugin requires PluginLibrary version {2} or later, whereas your current version is {1}. Please do update <a href="{3}">PluginLibrary</a>.';

// Settings
$l['setting_ougc_feedback_allow_profile'] = 'Allow Profile Feedback';
$l['setting_ougc_feedback_allow_profile_desc'] = 'Allow feedback from profiles.';
//$l['setting_ougc_feedback_allow_profile_multiple'] = 'Allow Multiple Profile Feedback';
//$l['setting_ougc_feedback_allow_profile_multiple_desc'] = 'Whether if to allow multiple profile feedback from the same user.';
$l['setting_ougc_feedback_allow_thread'] = 'Allow Thread Feedback';
$l['setting_ougc_feedback_allow_thread_desc'] = 'Allow feedback from threads.';
//$l['setting_ougc_feedback_allow_thread_firstpost'] = 'Allow Thread Feedback Only In First Post';
//$l['setting_ougc_feedback_allow_thread_firstpost_desc'] = 'Whether if to allow only feedback from first post or from all posts.';
$l['setting_ougc_feedback_allow_thread_forums'] = 'Allow Thread Feedback Forums';
$l['setting_ougc_feedback_allow_thread_forums_desc'] = 'Please select the forums where thread feedback is allowed.';
$l['setting_ougc_feedback_allow_comments'] = 'Allow Feedback Comments';
$l['setting_ougc_feedback_allow_comments_desc'] = 'Whether if to allow comments in feedbacks.';
$l['setting_ougc_feedback_comments_minlength'] = 'Comments Minimum Length';
$l['setting_ougc_feedback_comments_minlength_desc'] = 'The minimum comments length for comments.';
$l['setting_ougc_feedback_comments_maxlength'] = 'Comments Maximum Length';
$l['setting_ougc_feedback_comments_maxlength_desc'] = 'The maximum comments length for comments.';
$l['setting_ougc_feedback_allow_enable_center'] = 'Enable The Report Center';
$l['setting_ougc_feedback_allow_enable_center_desc'] = 'Whether if to enable the report center functionality.';
//$l['setting_ougc_feedback_allow_email_notifications'] = 'Allow Email Notification';
//$l['setting_ougc_feedback_allow_email_notifications_desc'] = 'Allow users to be notified via e-mail.';
$l['setting_ougc_feedback_allow_pm_notifications'] = 'Allow PM Notification';
$l['setting_ougc_feedback_allow_pm_notifications_desc'] = 'Allow users to be notified via PM.';
//$l['setting_ougc_feedback_allow_alert_notifications'] = 'Allow Alert Notification';
//$l['setting_ougc_feedback_allow_alert_notifications_desc'] = 'Allow users to be notified via a plugin alert.';
$l['setting_ougc_feedback_showin_profile'] = 'Show In Profile';
$l['setting_ougc_feedback_showin_profile_desc'] = 'Whether if to show feedback status in profiles.';
$l['setting_ougc_feedback_showin_postbit'] = 'Show In Posts';
$l['setting_ougc_feedback_showin_postbit_desc'] = 'Whether if to show feedback status in posts.';
//$l['setting_ougc_feedback_showin_memberlist'] = 'Show In Member List';
//$l['setting_ougc_feedback_showin_memberlist_desc'] = 'Whether if to show feedback status in the member list page.';
$l['setting_ougc_feedback_perpage'] = 'Pagination Per-Page Setting';
$l['setting_ougc_feedback_perpage_desc'] = 'Maximum number of items to show per page in pagination enabled features.';
$l['setting_ougc_feedback_maxperday'] = 'Maximum Feedbacks Per Day';
$l['setting_ougc_feedback_maxperday_desc'] = 'The maximum number of times an user can give feedback per day.';
$l['setting_ougc_feedback_permission_canview'] = 'Permission: Can View';
$l['setting_ougc_feedback_permission_canview_desc'] = 'Allowed groups that can view feedback page.';
$l['setting_ougc_feedback_permission_cangive'] = 'Permission: Can Give';
$l['setting_ougc_feedback_permission_cangive_desc'] = 'Allowed groups that can give feedback.';
$l['setting_ougc_feedback_permission_canreceive'] = 'Permission: Can Receive';
$l['setting_ougc_feedback_permission_canreceive_desc'] = 'Allowed groups that can receive feedback.';
$l['setting_ougc_feedback_permission_canedit'] = 'Permission: Can Edit';
$l['setting_ougc_feedback_permission_canedit_desc'] = 'Allowed groups that can edit their own feedbacks.';
$l['setting_ougc_feedback_permission_canremove'] = 'Permission: Can Remove';
$l['setting_ougc_feedback_permission_canremove_desc'] = 'Allowed groups that can remove their own feedbacks.';
$l['setting_ougc_feedback_permission_moderators'] = 'Moderators Groups';
$l['setting_ougc_feedback_permission_moderators_desc'] = 'Allowed groups that can moderate feedbacks.';
$l['setting_ougc_feedback_permission_moderators_canedit'] = 'Moderators: Can Edit';
$l['setting_ougc_feedback_permission_moderators_canedit_desc'] = 'Are moderators allowed to edit feedbacks?';
$l['setting_ougc_feedback_permission_moderators_canremove'] = 'Moderators: Can Remove';
$l['setting_ougc_feedback_permission_moderators_canremove_desc'] = 'Are moderators allowed to remove feedbacks?';
$l['setting_ougc_feedback_permission_moderators_candelete'] = 'Moderators: Can Delete';
$l['setting_ougc_feedback_permission_moderators_candelete_desc'] = 'Are moderators allowed to delete feedbacks?';