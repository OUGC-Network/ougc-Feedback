<?php

/***************************************************************************
 *
 *    OUGC Feedback plugin (/inc/languages/english/admin/ougc_feedback.lang.php)
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

// Plugin API
$l['ougc_feedback'] = 'OUGC Feedback';
$l['ougc_feedback_desc'] = 'Adds a powerful feedback system to your forum.';

// PluginLibrary
$l['ougc_feedback_pluginlibrary_required'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum.';

// Settings
$l['setting_group_ougc_feedback'] = 'Feedback System';
$l['setting_group_ougc_feedback_desc'] = 'Modify the settings for the feedback system, a powerful reputation system for market traders.';
$l['setting_ougc_feedback_allow_profile'] = 'Allow Profile Feedback';
$l['setting_ougc_feedback_allow_profile_desc'] = 'Allow feedback from profiles.';
$l['setting_ougc_feedback_allow_profile_multiple'] = 'Allow Multiple Profile Feedback';
$l['setting_ougc_feedback_allow_profile_multiple_desc'] = 'Allow users to leave multiple profile feedbacks to the same user.';
$l['setting_ougc_feedback_comments_minlength'] = 'Comments Minimum Length';
$l['setting_ougc_feedback_comments_minlength_desc'] = 'The minimum amount of characters for comments.';
$l['setting_ougc_feedback_comments_maxlength'] = 'Comments Maximum Length';
$l['setting_ougc_feedback_comments_maxlength_desc'] = 'The maximum amount of characters for comments.';
$l['setting_ougc_feedback_allow_email_notifications'] = 'Allow Email Notification';
$l['setting_ougc_feedback_allow_email_notifications_desc'] = 'Allow users to be notified via e-mail.';
$l['setting_ougc_feedback_pm_user_id'] = 'PM Author User ID';
$l['setting_ougc_feedback_pm_user_id_desc'] = 'Select a set user to be marked as the sender of PM notifications. Default is <code>-1</code> for "MyBB Engine".';

$l['setting_ougc_feedback_allow_pm_notifications'] = 'Allow PM Notification';
$l['setting_ougc_feedback_allow_pm_notifications_desc'] = 'Allow users to be notified via PM.';
//$l['setting_ougc_feedback_allow_alert_notifications'] = 'Allow Alert Notification';
//$l['setting_ougc_feedback_allow_alert_notifications_desc'] = 'Allow users to be notified via a plugin alert.';
$l['setting_ougc_feedback_showin_profile'] = 'Show In Profile';
$l['setting_ougc_feedback_showin_profile_desc'] = 'Show feedback status in profiles.';
$l['setting_ougc_feedback_latest_profile_feedback'] = 'Latest Feedback Count';
$l['setting_ougc_feedback_latest_profile_feedback_desc'] = 'Set amount of latest feedback to show in user profiles.';
$l['setting_ougc_feedback_latest_profile_comment_groups'] = 'Latest Feedback Comments Permission';
$l['setting_ougc_feedback_latest_profile_comment_groups_desc'] = 'Select which groups are allowed to see the latest feedback comment in user profiles.';
$l['setting_ougc_feedback_showin_postbit'] = 'Show In Posts';
$l['setting_ougc_feedback_showin_postbit_desc'] = 'Show feedback status in posts.';
$l['setting_ougc_feedback_showin_forums'] = 'Show In Forums';
$l['setting_ougc_feedback_showin_forums_desc'] = 'Show feedback user information within posts.';
$l['setting_ougc_feedback_postbit_hide_button'] = 'Hide Post-bit Button';
$l['setting_ougc_feedback_postbit_hide_button_desc'] = 'Turn this on to hide the post-bit feedback button if not allowed to use it. Save one query turning this off.';
$l['setting_ougc_feedback_profile_hide_add'] = 'Hide Profile Add Link';
$l['setting_ougc_feedback_profile_hide_add_desc'] = 'Turn this on to hide the profile feedback add link if not allowed to use it. Save one query turning this off.';
$l['setting_ougc_feedback_showin_memberlist'] = 'Show In Member List';
$l['setting_ougc_feedback_showin_memberlist_desc'] = 'Whether if to show feedback status in the member list page.';
$l['setting_ougc_feedback_perpage'] = 'Pagination Per-Page Setting';
$l['setting_ougc_feedback_perpage_desc'] = 'Maximum number of items to show per page in pagination enabled features.';

// Permissions
$l['ougc_feedback_permission_canview'] = 'Can view feedback page?';
$l['ougc_feedback_permission_cangive'] = 'Can give feedback to users?';
$l['ougc_feedback_permission_canreceive'] = 'Can receive feedback from users?';
$l['ougc_feedback_permission_canedit'] = 'Can edit his/her own sent feedback?';
$l['ougc_feedback_permission_canremove'] = 'Can (soft) delete his/her own sent feedback?';
$l['ougc_feedback_permission_maxperday'] = 'Maximum Feedback Allowed Per Day:';
$l['ougc_feedback_permission_maxperday_desc'] = 'Here you can enter the maximum number of feedbacks that users in this group can give per day. To allow unlimited feedbacks per day, enter 0.';
$l['ougc_feedback_permission_ismod'] = 'Can moderate feedback?';
$l['ougc_feedback_permission_mod_canedit'] = 'Can this moderator edit feedbacks?';
$l['ougc_feedback_permission_mod_canremove'] = 'Can this moderator soft delete feedbacks?';
$l['ougc_feedback_permission_mod_candelete'] = 'Can this moderator hard delete feedbacks?';

// Reort system
$l['report_content_feedback'] = 'OUGC Feedback';

// Forums
$l['ougc_feedback_permission_allow_threads'] = 'Yes, allow feedback for threads';
$l['ougc_feedback_permission_allow_posts'] = 'Yes, allow feedback for posts';