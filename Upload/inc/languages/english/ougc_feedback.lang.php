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

// Profile
$l['ougc_feedback_profile_title'] = 'Feedback';
$l['ougc_feedback_profile_view'] = 'View All';
$l['ougc_feedback_profile_total'] = 'Total';
$l['ougc_feedback_profile_positibve'] = 'Positive';
$l['ougc_feedback_profile_neutral'] = 'Neutral';
$l['ougc_feedback_profile_negative'] = 'Negative';
$l['ougc_feedback_profile_users'] = 'users';
$l['ougc_feedback_profile_add'] = 'Add Feedback';

// Modal
$l['ougc_feedback_modal_type'] = 'Transaction Type:';
$l['ougc_feedback_modal_feedback'] = 'Feedback:';
$l['ougc_feedback_modal_comment'] = 'Comment:';
$l['ougc_feedback_type_buyer'] = 'Buyer';
$l['ougc_feedback_type_seller'] = 'Seller';
$l['ougc_feedback_type_trader'] = 'Trader';

// Page
$l['ougc_feedback_page_title'] = 'Feedback Report';
$l['ougc_feedback_page_report_for'] = 'Feedback Report for {1}';
$l['ougc_feedback_page_summary'] = 'Summary';
$l['ougc_feedback_page_profile'] = 'Profile of {1}';
$l['ougc_feedback_confirm_delete'] = 'Are you sure you wish to delete this feedback?';
$l['ougc_feedback_page_empty'] = 'This user currently does not have any feedback with the specified criteria below.';
$l['ougc_feedback_page_report'] = 'Report';
$l['ougc_feedback_page_edit'] = 'Edit';
$l['ougc_feedback_page_delete'] = 'Remove';
$l['ougc_feedback_page_delete_hard'] = 'Delete';
$l['ougc_feedback_page_last_updated'] = 'Last updated {1}';
$l['ougc_feedback_page_post_given'] = 'Feedback given for <a href="{1}">{2}\'s post</a> {3}<br />';
$l['ougc_feedback_page_post_nolink'] = 'Feedback given for {1}\'s post<br />';
$l['ougc_feedback_page_post_given_thread'] = 'in <a href="{1}">{2}</a>';
$l['ougc_feedback_no_comment'] = '[No comment]';
$l['ougc_feedback_page_stats_total'] = 'Total Feedback';
$l['ougc_feedback_page_stats_members'] = 'Feedback from members';
$l['ougc_feedback_page_stats_posts'] = 'Feedback from posts';
$l['ougc_feedback_page_type_buyer'] = '[Buyer] ';
$l['ougc_feedback_page_type_seller'] = '[Seller] ';
$l['ougc_feedback_page_type_trader'] = '[Trader] ';

// Took from reputation feature
$l['show_all'] = 'Show: All Votes';
$l['show_positive'] = 'Show: Positive Feedback';
$l['show_neutral'] = 'Show: Neutral Feedback';
$l['show_negative'] = 'Show: Negative Feedback';
$l['show_gived'] = 'Show: Gived Feedback';
$l['sort_updated'] = 'Sort by: Last Updated';
$l['sort_username'] = 'Sort by: Username';
$l['positive_count'] = 'Positives';
$l['neutral_count'] = 'Neutrals';
$l['negative_count'] = 'Negatives';
$l['last_week'] = 'Last week';
$l['last_month'] = 'Last month';
$l['last_6months'] = 'Last 6 months';
$l['all_time'] = 'All Time';
$l['comments'] = 'Comments';

// Notifications
$l['ougc_feedback_notification_pm_subject'] = 'You have received new feedback!';
$l['ougc_feedback_notification_pm_message'] = 'Hi {1}! This is a automatic message to notify you that your profile has been updated with a new user feedback.

Cheers, {2}.';
$l['ougc_feedback_notification_mail_subject'] = 'You have received new feedback!';
$l['ougc_feedback_notification_mail_message'] = 'Hi {1}! This is a automatic message to notify you that your profile has been updated with a new user feedback.

Cheers, {2}.';

// Member list
//$l['ougc_feedback_memberlist_sort_positive'] = 'Sort by: Positive Feedback';
//$l['ougc_feedback_memberlist_sort_neutral'] = 'Sort by: Neutral Feedback';
//$l['ougc_feedback_memberlist_sort_negative'] = 'Sort by: Negative Feedback';

// Report center
$l['ougc_feedback_report_info'] = '<a href="{1}">Feedback</a> from {2}';
$l['ougc_feedback_report_info_profile'] = '<br /><span class="smalltext">On {1}\'s profile</span';

// Errors
$l['ougc_feedback_error_profile_disabled'] = 'Profile feedback is disabled.';
$l['ougc_feedback_error_profile_thread'] = 'Thread feedback is disabled.';
$l['ougc_feedback_error_profile_multiple_disabled'] = 'Multiple profile feedback is disabled.';
$l['ougc_feedback_error_invalid_user'] = 'Invalid user.';
$l['ougc_feedback_error_invalid_self_user'] = 'You cannot add to your own feedback.';
$l['ougc_feedback_error_invalid_post'] = 'Invalid post.';
$l['ougc_feedback_error_invalid_type'] = 'Invalid feedback type selected.';
$l['ougc_feedback_error_invalid_feedback'] = 'Invalid feedback value selected.';
$l['ougc_feedback_error_invalid_status'] = 'Invalid feedback status selected.';
$l['ougc_feedback_error_invalid_comment'] = 'Invalid feedback comment entered.';
$l['ougc_feedback_error_invalid_maxperday'] = 'You have already given as many feedback  as you are allowed to for today.';

// Success
$l['ougc_feedback_success_feedback_added'] = 'Your feedback was successfully added.';