<?php

/***************************************************************************
 *
 *	OUGC Feedback plugin (/inc/plugins/ougc_feedback.php)
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

// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('Direct initialization of this file is not allowed.');

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// Plugin API
function ougc_feedback_info()
{
	global $feedback;

	return $feedback->_info();
}

// _activate() routine
function ougc_feedback_activate()
{
	global $feedback;

	return $feedback->_activate();
}

// _deactivate() routine
function ougc_feedback_deactivate()
{
	global $feedback;

	return $feedback->_deactivate();
}

// _install() routine
function ougc_feedback_install()
{
	global $feedback;

	return $feedback->_install();
}

// _is_installed() routine
function ougc_feedback_is_installed()
{
	global $feedback;

	return $feedback->_is_installed();
}

// _uninstall() routine
function ougc_feedback_uninstall()
{
	global $feedback;

	return $feedback->_uninstall();
}

// Plugin class
class OUGC_Feedback
{
	function __construct()
	{
		
	}

	// Plugin API:_info() routine
	function _info()
	{
		global $lang;

		$this->load_language();

		return array(
			'name'					=> 'OUGC Feedback',
			'description'			=> $lang->setting_group_ougc_feedback_desc,
			'website'				=> 'http://omarg.me',
			'author'				=> 'Omar G.',
			'authorsite'			=> 'http://omarg.me',
			'version'				=> '1.0',
			'versioncode'			=> 1000,
			'compatibility'			=> '18*',
			'pl'			=> array(
				'version'	=> 12,
				'url'		=> 'http://mods.mybb.com/view/pluginlibrary'
			)
		);
	}

	// Plugin API:_activate() routine
	function _activate()
	{
		global $PL, $lang, $cache;
		$this->load_pluginlibrary();

		$PL->settings('ougc_feedback', $lang->setting_group_ougc_feedback, $lang->setting_group_ougc_feedback_desc, array(
			'allow_profile'				=> array(
			   'title'			=> $lang->setting_ougc_feedback_allow_profile,
			   'description'	=> $lang->setting_ougc_feedback_allow_profile_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'allow_profile_multiple'	=> array(
			   'title'			=> $lang->setting_ougc_feedback_allow_profile_multiple,
			   'description'	=> $lang->setting_ougc_feedback_allow_profile_multiple_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'allow_thread'				=> array(
			   'title'			=> $lang->setting_ougc_feedback_allow_thread,
			   'description'	=> $lang->setting_ougc_feedback_allow_thread_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'allow_thread_firstpost'				=> array(
			   'title'			=> $lang->setting_ougc_feedback_allow_thread_firstpost,
			   'description'	=> $lang->setting_ougc_feedback_allow_thread_firstpost_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'allow_thread_forums'		=> array(
			   'title'			=> $lang->setting_ougc_feedback_allow_thread_forums,
			   'description'	=> $lang->setting_ougc_feedback_allow_thread_forums_desc,
			   'optionscode'	=> 'forumselect',
			   'value'			=> -1
			),
			'allow_comments'			=> array(
			   'title'			=> $lang->setting_ougc_feedback_allow_comments,
			   'description'	=> $lang->setting_ougc_feedback_allow_comments_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'comments_require'			=> array(
			   'title'			=> $lang->setting_ougc_feedback_comments_require,
			   'description'	=> $lang->setting_ougc_feedback_comments_require_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'comments_minlength'		=> array(
			   'title'			=> $lang->setting_ougc_feedback_comments_minlength,
			   'description'	=> $lang->setting_ougc_feedback_comments_minlength_desc,
			   'optionscode'	=> 'text',
			   'value'			=> 15
			),
			'comments_maxlength'		=> array(
			   'title'			=> $lang->setting_ougc_feedback_comments_maxlength,
			   'description'	=> $lang->setting_ougc_feedback_comments_maxlength_desc,
			   'optionscode'	=> 'text',
			   'value'			=> 100
			),
			'enable_report_center'		=> array(
			   'title'			=> $lang->setting_ougc_feedback_allow_enable_center,
			   'description'	=> $lang->setting_ougc_feedback_allow_enable_center_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'allow_email_notifications'	=> array(
			   'title'			=> $lang->setting_ougc_feedback_allow_email_notifications,
			   'description'	=> $lang->setting_ougc_feedback_allow_email_notifications_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'allow_pm_notifications'	=> array(
			   'title'			=> $lang->setting_ougc_feedback_allow_email_pm,
			   'description'	=> $lang->setting_ougc_feedback_allow_email_pm_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'allow_alert_notifications'	=> array(
			   'title'			=> $lang->setting_ougc_feedback_allow_email_alert,
			   'description'	=> $lang->setting_ougc_feedback_allow_email_alert_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'showin_profile'			=> array(
			   'title'			=> $lang->setting_ougc_feedback_showin_profile,
			   'description'	=> $lang->setting_ougc_feedback_showin_profile_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'showin_postbit'			=> array(
			   'title'			=> $lang->setting_ougc_feedback_showin_postbit,
			   'description'	=> $lang->setting_ougc_feedback_showin_postbit_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'showin_memberlist'			=> array(
			   'title'			=> $lang->setting_ougc_feedback_showin_memberlist,
			   'description'	=> $lang->setting_ougc_feedback_showin_memberlist_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'peerpage'					=> array(
			   'title'			=> $lang->setting_ougc_feedback_peerpage,
			   'description'	=> $lang->setting_ougc_feedback_peerpage_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
		));

		$PL->templates('ougcfeedback', '<lang:setting_group_ougc_feedback>', array(
			'js'	=> '<script type="text/javascript" src="{$mybb->asset_url}/jscripts/ougc_feedback.js?ver=1805"></script>',
			'form'	=> '<div class="modal">
	<div style="overflow-y: auto; max-height: 400px;" class="modal_{$feedback_data[\'uid\']}_{$feedback_data[\'pid\']}">
	<form method="post" action="{$mybb->settings[\'bburl\']}/feedback.php" id="ougcfeedback_form" class="feedback_{$feedback_data[\'uid\']}_{$feedback_data[\'pid\']}" onsubmit="javascript: return OUGC_Feedback.DoAdd(\'{$feedback_data[\'uid\']}\', \'{$feedback_data[\'pid\']}\');">
		<input name="action" type="hidden" value="add" />
		<input name="uid" type="hidden" value="{$feedback_data[\'uid\']}" />
		<input name="pid" type="hidden" value="{$feedback_data[\'pid\']}" />
		<input name="nomodal" type="hidden" value="0" />
		<input name="my_post_key" type="hidden" value="{$mybb->post_code}" />
		<table width="100%" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" border="0" class="tborder">
			<tr>
				<td class="thead" colspan="2"><strong>{$lang->ougc_feedback_profile_add}</strong></td>
			</tr>
			<tr>
				<td class="trow1" width="40%"><strong>{$lang->ougc_feedback_modal_type}</strong></td>
				<td class="trow1"><select name="type">
	<option value="1">{$lang->ougc_feedback_type_buyer}</option>
	<option value="2">{$lang->ougc_feedback_type_seller}</option>
	<option value="3">{$lang->ougc_feedback_type_trader}</option>
</select></td>
			</tr>
			<tr>
				<td class="trow2" width="40%"><strong>{$lang->ougc_feedback_modal_feedback}</strong></td>
				<td class="trow2"><select name="feedback">
	<option value="1">{$lang->ougc_feedback_profile_positibve}</option>
	<option value="0">{$lang->ougc_feedback_profile_neutral}</option>
	<option value="-1">{$lang->ougc_feedback_profile_negative}</option>
</select></td>
			</tr>
			{$comment_row}
			<tr>
				<td class="tfoot" colspan="2" align="center">
					<input name="submit" type="submit" class="button" value="{$lang->ougc_feedback_profile_add}" />
				</td>
			</tr>
		</table>
	</form>
	<script>
		$(\'#ougcfeedback_form\').submit(function(e)
		{
			e.preventDefault();
			e.unbind();
		});
	</script>
  </div>
</div>',
			'form_comment'	=> '<tr>
	<td class="trow1" width="40%"><strong>{$lang->ougc_feedback_modal_comment}</strong></td>
	<td class="trow1"><input name="comment" type="text" value="{$mybb->input[\'comment\']}" class="textbox" /></td>
</tr>',
			'error'	=> '<div class="modal">
	<div style="overflow-y: auto; max-height: 400px;">
		<table width="100%" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" border="0" class="tborder">
			<tr>
				<td class="thead"><strong>{$title}</strong></td>
			</tr>
			<tr>
				<td class="trow1">{$message}</td>
			</tr>
			<tr>
				<td class="tfoot">&nbsp;</td>
			</tr>
		</table>
  </div>
</div>',
			'error_nomodal'	=> '',
			'postbit'	=> '<div class="ougcfeedback_postbit_{$post[\'uid\']}">
	<br />{$lang->ougc_feedback_profile_total} {$lang->ougc_feedback_profile_title}: {$stats[\'total\']} <span class="smalltext">(<a href="{$mybb->settings[\'bburl\']}/feedback.php?uid={$post[\'uid\']}">{$lang->ougc_feedback_profile_view}</a>)</span>
	<br />{$lang->ougc_feedback_profile_positibve} {$lang->ougc_feedback_profile_title}: <span style="color: green;">{$stats[\'positive\']} ({$stats[\'positive_percent\']}% - {$stats[\'positive_users\']} {$lang->ougc_feedback_profile_users})</span>
	<br />{$lang->ougc_feedback_profile_neutral} {$lang->ougc_feedback_profile_title}: <span style="color: gray;">{$stats[\'neutral\']} ({$stats[\'neutral_percent\']}% - {$stats[\'neutral_users\']} {$lang->ougc_feedback_profile_users})</span>
	<br />{$lang->ougc_feedback_profile_negative} {$lang->ougc_feedback_profile_title}: <span style="color: red;">{$stats[\'negative\']} ({$stats[\'negative_percent\']}% - {$stats[\'negative_users\']} {$lang->ougc_feedback_profile_users})</span>
</div>',
			'postbit_button'	=> '<a href="javascript:OUGC_Feedback.Add(\'{$post[\'uid\']}\', \'{$post[\'pid\']}\', \'1\', \'1\');" title="{$lang->ougc_feedback_profile_add}" class="postbit_reputation_add"><span>{$lang->ougc_feedback_profile_add}</span></a>',
			'profile'	=> '<div id="ougcfeedback_profile">
	<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
		<tr>
			<td colspan="2" class="thead"><strong>{$lang->ougc_feedback_profile_title}</strong><span class="float_right smalltext">(<a href="{$mybb->settings[\'bburl\']}/feedback.php?uid={$memprofile[\'uid\']}">{$lang->ougc_feedback_profile_view}</a>)</span></td>
		</tr>
		<tr>
			<td class="trow1" style="width: 30%;"><strong>{$lang->ougc_feedback_profile_total}:</strong></td>
			<td class="trow1">{$stats[\'total\']}</td>
		</tr>
		<tr style="color: green;">
			<td class="trow2" style="width: 30%;"><strong>{$lang->ougc_feedback_profile_positibve}:</strong></td>
			<td class="trow2">{$stats[\'positive\']} ({$stats[\'positive_percent\']}% - {$stats[\'positive_users\']} {$lang->ougc_feedback_profile_users})</td>
		</tr>
		<tr style="color: gray;">
			<td class="trow1" style="width: 30%;"><strong>{$lang->ougc_feedback_profile_neutral}:</strong></td>
			<td class="trow1">{$stats[\'neutral\']} ({$stats[\'neutral_percent\']}% - {$stats[\'neutral_users\']} {$lang->ougc_feedback_profile_users})</td>
		</tr>
		<tr style="color: red;">
			<td class="trow2" style="width: 30%;"><strong>{$lang->ougc_feedback_profile_negative}:</strong></td>
			<td class="trow2">{$stats[\'negative\']} ({$stats[\'negative_percent\']}% - {$stats[\'negative_users\']} {$lang->ougc_feedback_profile_users})</td>
		</tr>
		{$add_row}
	</table><br />
</div>',
			'profile_add'	=> '<tr>
	<td class="trow1" colspan="2" align="right"><strong><a href="javascript:OUGC_Feedback.Add(\'{$memprofile[\'uid\']}\', \'0\', \'1\', \'1\');" title="{$lang->ougc_feedback_profile_add}">{$lang->ougc_feedback_profile_add}</a></strong></td>
</tr>',
			/*''	=> '',
			''	=> '',
			''	=> '',*/
		));

		$this->_deactivate();

		require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
		find_replace_templatesets('member_profile', '#'.preg_quote('{$profilefields}').'#i', '{$profilefields}{$ougc_feedback}');
		find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'button_rep\']}').'#i', '{$post[\'button_rep\']}{$post[\'ougc_feedback_button\']}');
		find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'button_rep\']}').'#i', '{$post[\'button_rep\']}{$post[\'ougc_feedback_button\']}');
		find_replace_templatesets('postbit_author_user', '#'.preg_quote('{$post[\'warninglevel\']}').'#i', '{$post[\'warninglevel\']}<!--OUGC_FEEDBACK-->');
		find_replace_templatesets('memberlist_user', '#'.preg_quote('{$referral_bit}').'#i', '{$referral_bit}{$ougc_feedback_bit}');
		find_replace_templatesets('memberlist', '#'.preg_quote('{$referral_header}').'#i', '{$referral_header}{$ougc_feedback_header}');
		find_replace_templatesets('memberlist', '#'.preg_quote('{$lang->sort_by_referrals}</option>').'#i', '{$lang->sort_by_referrals}</option>{$ougc_feedback_sort}');
		find_replace_templatesets('headerinclude', '#'.preg_quote('{$stylesheets}').'#i', '{$stylesheets}{$ougc_feedback_js}');

		// Insert/update version into cache
		$plugins = $cache->read('ougc_plugins');
		if(!$plugins)
		{
			$plugins = array();
		}

		$info = ougc_feedback_info();

		if(!isset($plugins['feedback']))
		{
			$plugins['feedback'] = $info['versioncode'];
		}

		/*~*~* RUN UPDATES START *~*~*/

		/*~*~* RUN UPDATES END *~*~*/

		$plugins['feedback'] = $info['versioncode'];
		$cache->update('ougc_plugins', $plugins);
	}

	// Plugin API:_deactivate() routine
	function _deactivate()
	{
		require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
		find_replace_templatesets('member_profile', '#'.preg_quote('{$ougc_feedback}').'#i', '', 0);
		find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'ougc_feedback_button\']}').'#i', '', 0);
		find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'ougc_feedback_button\']}').'#i', '', 0);
		find_replace_templatesets('postbit_author_user', '#'.preg_quote('<!--OUGC_FEEDBACK-->').'#i', '', 0);
		find_replace_templatesets('memberlist_user', '#'.preg_quote('{$ougc_feedback_bit}').'#i', '', 0);
		find_replace_templatesets('memberlist', '#'.preg_quote('{$ougc_feedback_header}').'#i', '', 0);
		find_replace_templatesets('memberlist', '#'.preg_quote('{$ougc_feedback_sort}').'#i', '', 0);
		find_replace_templatesets('headerinclude', '#'.preg_quote('{$ougc_feedback_js}').'#i', '', 0);
		find_replace_templatesets('postbit', '#'.preg_quote('{$post[\'ougc_feedback\']}').'#i', '', 0);
		find_replace_templatesets('postbit_classic', '#'.preg_quote('{$post[\'ougc_feedback\']}').'#i', '', 0);
	}

	// Plugin API:_install() routine
	function _install()
	{
		global $db;

		// Create DB table
		switch($db->type)
		{
			case 'pgsql':
				$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_feedback` (
						`fid` serial,
						`uid` int NOT NULL DEFAULT '0',
						`fuid` int NOT NULL DEFAULT '0',
						`pid` int NOT NULL DEFAULT '0',
						`type` int NOT NULL DEFAULT '0',
						`feedback` smallint NOT NULL DEFAULT '0',
						`comment` text NOT NULL DEFAULT '',
						`status` tinyint(1) NOT NULL DEFAULT '1',
						`dateline` int NOT NULL DEFAULT '0',,
						PRIMARY KEY(fid)
					);"
				);
				break;
			case 'sqlite':
				$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_feedback` (
						`fid` INTEGER PRIMARY KEY,
						`uid` int NOT NULL DEFAULT '0',
						`fuid` int NOT NULL DEFAULT '0',
						`pid` int NOT NULL DEFAULT '0',
						`type` int NOT NULL DEFAULT '0',
						`feedback` smallint NOT NULL DEFAULT '0',
						`comment` text NOT NULL,
						`status` tinyint(1) NOT NULL DEFAULT '1',
						`dateline` int UNSIGNED NOT NULL DEFAULT '0'
					);"
				);
				break;
			default:
				$collation = $db->build_create_table_collation();
				$db->write_query("CREATE TABLE `".TABLE_PREFIX."ougc_feedback` (
						`fid` int UNSIGNED NOT NULL AUTO_INCREMENT,
						`uid` int UNSIGNED NOT NULL DEFAULT '0',
						`fuid` int UNSIGNED NOT NULL DEFAULT '0',
						`pid` int UNSIGNED NOT NULL DEFAULT '0',
						`type` int UNSIGNED NOT NULL DEFAULT '0',
						`feedback` smallint NOT NULL default '0',
						`comment` text NOT NULL,
						`status` tinyint(1) NOT NULL DEFAULT '1',
						`dateline` int UNSIGNED NOT NULL DEFAULT '0',
						KEY uid (uid),
						PRIMARY KEY (`fid`)
					) ENGINE=MyISAM{$collation};"
				);
				break;
		}
		// TODO:: ip should be stored

		// Add DB fields
		foreach($this->get_db_fields() as $table => $fields)
		{
			foreach($fields as $name => $definition)
			{
				if(!$db->field_exists($name, $table))
				{
					$db->add_column($table, $name, $definition);
				}
			}
		}
	}

	// Plugin API:_is_installed() routine
	function _is_installed()
	{
		global $db;

		return $db->table_exists('ougc_feedback');
	}

	// Plugin API:_uninstall() routine
	function _uninstall()
	{
		global $db, $PL, $cache;
		$this->load_pluginlibrary();

		// Drop table
		$db->drop_table('ougc_feedback');

		// Remove DB fields
		foreach($this->get_db_fields() as $table => $fields)
		{
			foreach($fields as $name => $definition)
			{
				if($db->field_exists($name, $table))
				{
					$db->drop_column($table, $name);
				}
			}
		}

		// Delete settings
		$PL->settings_delete('ougc_feedback');

		// Delete templates
		$PL->templates_delete('ougcfeedback');

		// Delete version from cache
		$plugins = (array)$cache->read('ougc_plugins');

		if(isset($plugins['feedback']))
		{
			unset($plugins['feedback']);
		}

		if(!empty($plugins))
		{
			$cache->update('ougc_plugins', $plugins);
		}
		else
		{
			$PL->cache_delete('ougc_plugins');
		}

		$cache->update_usergroups();
	}

	// Load language file
	function load_language()
	{
		global $lang;

		isset($lang->setting_group_ougc_feedback) or $lang->load('ougc_feedback');
	}

	// Build plugin info
	function load_plugin_info()
	{
		$this->plugin_info = ougc_feedback_info();
	}

	// PluginLibrary requirement check
	function load_pluginlibrary()
	{
		global $lang;
		$this->load_plugin_info();
		$this->load_language();

		if(!file_exists(PLUGINLIBRARY))
		{
			flash_message($lang->sprintf($lang->ougc_feedback_pluginlibrary_required, $this->plugin_info['pl']['ulr'], $this->plugin_info['pl']['version']), 'error');
			admin_redirect('index.php?module=config-plugins');
		}

		global $PL;
		$PL or require_once PLUGINLIBRARY;

		if($PL->version < $this->plugin_info['pl']['version'])
		{
			global $lang;

			flash_message($lang->sprintf($lang->ougc_feedback_pluginlibrary_old, $PL->version, $this->plugin_info['pl']['version'], $this->plugin_info['pl']['ulr']), 'error');
			admin_redirect('index.php?module=config-plugins');
		}
	}

	// DB Fields
	function get_db_fields()
	{
		global $db;

		// Create DB table
		switch($db->type)
		{
			case 'pgsql':
				$fields = array(
					'usergroups'	=> array(
						'feedback_canuser'			=> "smallint NOT NULL DEFAULT '1'",
						'feedback_canview'			=> "smallint NOT NULL DEFAULT '1'",
						'feedback_canadd'			=> "smallint NOT NULL DEFAULT '1'",
						'feedback_canedit'			=> "smallint NOT NULL DEFAULT '1'",
						'feedback_candelete'		=> "smallint NOT NULL DEFAULT '0'",
						'feedback_cancomment'		=> "smallint NOT NULL DEFAULT '1'",
						'feedback_value'			=> "int NOT NULL DEFAULT '1'",
						'feedback_maxperday'		=> "int NOT NULL DEFAULT '5'",
						'feedback_ismod'			=> "smallint NOT NULL DEFAULT '0'",
						'feedback_mod_canedit'		=> "smallint NOT NULL DEFAULT '0'",
						'feedback_mod_canunapprove'	=> "smallint NOT NULL DEFAULT '0'",
						'feedback_mod_candelete'	=> "smallint NOT NULL DEFAULT '0'",
					),
					'users'			=> array(
						'ougc_feedback'					=> "int NOT NULL DEFAULT '0'",
						'ougc_feedback_notification'	=> "int NOT NULL DEFAULT '0'",
					)
				);
				break;
			default:
				$fields = array(
					'usergroups'	=> array(
						'feedback_canuser'			=> "tinyint(1) NOT NULL DEFAULT '1'",
						'feedback_canview'			=> "tinyint(1) NOT NULL DEFAULT '1'",
						'feedback_canadd'			=> "tinyint(1) NOT NULL DEFAULT '1'",
						'feedback_canedit'			=> "tinyint(1) NOT NULL DEFAULT '1'",
						'feedback_candelete'		=> "tinyint(1) NOT NULL DEFAULT '0'",
						'feedback_cancomment'		=> "tinyint(1) NOT NULL DEFAULT '1'",
						'feedback_value'			=> "int UNSIGNED NOT NULL DEFAULT '1'",
						'feedback_maxperday'		=> "int UNSIGNED NOT NULL DEFAULT '5'",
						'feedback_ismod'			=> "tinyint(1) NOT NULL DEFAULT '0'",
						'feedback_mod_canedit'		=> "tinyint(1) NOT NULL DEFAULT '0'",
						'feedback_mod_canunapprove'	=> "tinyint(1) NOT NULL DEFAULT '0'",
						'feedback_mod_candelete'	=> "tinyint(1) NOT NULL DEFAULT '0'",
					),
					'users'			=> array(
						'ougc_feedback'					=> "int UNSIGNED NOT NULL DEFAULT '0'",
						'ougc_feedback_notification'	=> "int UNSIGNED NOT NULL DEFAULT '0'",
					)
				);
				break;
		}

		return $fields;

		/*'interval_add'				=> array(
		   'title'			=> $lang->setting_ougc_feedback_interval_add,
		   'description'	=> $lang->setting_ougc_feedback_interval_add_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),
		'interval_edit'				=> array(
		   'title'			=> $lang->setting_ougc_feedback_interval_edit,
		   'description'	=> $lang->setting_ougc_feedback_interval_edit_desc,
		   'optionscode'	=> 'yesno',
		   'value'			=> 1
		),*/
	// table scheme ||| rep_type{buyer|seler|trader} feedback{1|0|-1} tid{} comment{}
	// permissions: can_add |  | can_manage | can_receive | can_invisible | can_view_invisible
	}

	// Permissions helper
	function permission($key, $uid=null)
	{
		global $mybb;

		isset($uid) or $uid = $mybb->user['uid'];

		if(isset($this->permissions[$uid][$key]))
		{
			return $this->permissions[$uid][$key];
		}

		return true;
		#$mybb->usergroup['feedback_ismod'];
	}

	// Set a custom permission
	function set_permission($key, $val, $uid=null)
	{
		global $mybb;

		isset($uid) or $uid = $mybb->user['uid'];

		$this->permissions[$uid][$key] = $val;
	}

	// Default status
	function default_status()
	{
		return 1;
	}

	// Send an error to the browser
	function throw_error($title='', $exit=true)
	{
		global $mybb, $templates, $lang, $theme;

		$title = $title ? $title : $lang->error;
		$message = $this->error;

		eval('$error = "'.$templates->get('ougcfeedback_error'.($mybb->get_input('nomodal', 1) ? '_nomodal' : ''), 1, 0).'";');

		if($exit)
		{
			exit($error);
		}

		return $error;
	}

	// Set error
	function set_error($message)
	{
		$this->error = $message;
	}

	// Feedback: Insert
	function set_data($data)
	{
		global $db;

		$this->data = array();

		!isset($data['uid']) or $this->data['uid'] = (int)$data['uid'];
		!isset($data['fuid']) or $this->data['fuid'] = (int)$data['fuid'];
		!isset($data['pid']) or $this->data['pid'] = (int)$data['pid'];
		!isset($data['type']) or $this->data['type'] = (int)$data['type'];
		!isset($data['feedback']) or $this->data['feedback'] = (int)$data['feedback'];
		!isset($data['comment']) or $this->data['comment'] = $db->escape_string($data['comment']);
		!isset($data['status']) or $this->data['status'] = (int)$data['status'];
		!isset($data['dateline']) or $this->data['dateline'] = TIME_NOW;
	}

	// Feedback: Insert
	function validate_feedback()
	{
		if($this->error)
		{
			return false;
		}

		return true;
	}

	// Feedback: Insert
	function insert_feedback($update=false)
	{
		global $db;

		$feedback = &$this->data;

		$insert_data = array();

		!isset($feedback['uid']) or $insert_data['uid'] = (int)$feedback['uid'];
		!isset($feedback['fuid']) or $insert_data['fuid'] = (int)$feedback['fuid'];
		!isset($feedback['pid']) or $insert_data['pid'] = (int)$feedback['pid'];
		!isset($feedback['type']) or $insert_data['type'] = (int)$feedback['type'];
		!isset($feedback['feedback']) or $insert_data['feedback'] = (int)$feedback['feedback'];
		!isset($feedback['comment']) or $insert_data['comment'] = $db->escape_string($feedback['comment']);
		!isset($feedback['status']) or $insert_data['status'] = (int)$feedback['status'];

		if($update)
		{
			
		}
		else
		{
			$insert_data['dateline'] = TIME_NOW;

			$this->fid = $db->insert_query('ougc_feedback', $insert_data);
		}

		return $insert_data;
	}

	// Hook: admin_config_settings_change
	function hook_admin_config_settings_change()
	{
		global $db, $mybb;

		$query = $db->simple_select('settinggroups', 'name', "gid='{$mybb->get_input('gid', 1)}'");

		!($db->fetch_field($query, 'name') == 'ougc_feedback') or $this->load_language();
	}

	// Hook: global_intermediate
	function hook_global_intermediate()
	{
		global $templates, $ougc_feedback_js, $mybb;

        eval('$ougc_feedback_js = "'.$templates->get('ougcfeedback_js').'";');
	}

	// Hook: member_profile_end
	function hook_member_profile_end()
	{
		global $db, $memprofile, $templates, $ougc_feedback, $theme, $lang, $mybb;
		$this->load_language();

		$ougc_feedback = '';
		if(!$mybb->settings['ougc_feedback_showin_profile'])
		{
			return;
		}

		$where = array("uid='{$memprofile['uid']}'", "fuid!='0'");

		if(!$this->permission('ismod'))
		{
			$where[] = "status='1'";
		}

		$stats = array('total' => 0, 'positive' => 0, 'neutral' => 0, 'negative' => 0, 'positive_percent' => 0, 'neutral_percent' => 0, 'negative_percent' => 0, 'positive_users' => array(), 'neutral_users' => array(), 'negative_users' => array());

		$query = $db->simple_select('ougc_feedback', '*', implode(' AND ', $where));
		while($feedback = $db->fetch_array($query))
		{
			++$stats['total'];

			$feedback['feedback'] = (int)$feedback['feedback'];
			switch($feedback['feedback'])
			{
				case 1:
					++$stats['positive'];
					$stats['positive_users'][$feedback['fuid']] = 1;
					break;
				case 0:
					++$stats['neutral'];
					$stats['neutral_users'][$feedback['fuid']] = 1;
					break;
				case -1:
					++$stats['negative'];
					$stats['negative_users'][$feedback['fuid']] = 1;
					break;
			}
		}

		if($stats['total'])
		{
			$stats['positive_percent'] = floor(100*($stats['positive']/$stats['total']));
			$stats['neutral_percent'] = floor(100*($stats['neutral']/$stats['total']));
			$stats['negative_percent'] = floor(100*($stats['negative']/$stats['total']));
		}

		$stats['positive_users'] = count($stats['positive_users']);
		$stats['neutral_users'] = count($stats['neutral_users']);
		$stats['negative_users'] = count($stats['negative_users']);

		$stats = array_map('my_number_format', $stats);

		if($this->permission('can_add') && $mybb->user['uid'] != $memprofile['uid'])
		{
			if(!$mybb->settings['ougc_feedback_allow_profile'])
			{
				$this->set_permission('can_add', false);
			}
			elseif($mybb->settings['ougc_feedback_allow_profile'] && !$mybb->settings['ougc_feedback_allow_profile_multiple'])
			{
				$where[] = "fuid='{$mybb->user['uid']}'";

				$query = $db->simple_select('ougc_feedback', 'fid', implode(' AND ', $where));

				if($db->fetch_field($query, 'fid'))
				{
					$this->set_permission('can_add', false);
				}
			}

			$add_row = '';
			$trow = 'trow1';
			if($this->permission('can_add'))
			{
				$trow = 'trow2';
				$pid = '';

				$uid = $memprofile['uid'];

				$mybb->input['type'] = isset($mybb->input['type']) ? $mybb->get_input('type', 1) : 1 ;
				$mybb->input['feedback'] = isset($mybb->input['feedback']) ? $mybb->get_input('feedback', 1) : 1 ;

				eval('$add_row = "'.$templates->get('ougcfeedback_profile_add').'";');
			}
		}

        eval('$ougc_feedback = "'.$templates->get('ougcfeedback_profile').'";');
	}

	// Hook: postbit
	function hook_postbit(&$post)
	{
		global $db, $templates, $theme, $lang, $mybb, $pids;
		$this->load_language();

		$post['ougc_feedback'] = $post['ougc_feedback_button'] = '';
		if($mybb->settings['ougc_feedback_showin_postbit'])
		{
			static $query_cache;

			if(!isset($query_cache))
			{
				global $plugins;

				$where = array("f.fuid!='0'");

				if(!$this->permission('ismod'))
				{
					$where[] = "f.status='1'";
				}

				if($plugins->current_hook == 'postbit' && $mybb->get_input('mode') != 'threaded')
				{
					$where[] = "p.{$pids}";

					$query = $db->query("
						SELECT f.*, p.uid as post_uid
						FROM ".TABLE_PREFIX."ougc_feedback f
						LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=f.uid)
						LEFT JOIN ".TABLE_PREFIX."posts p ON (p.uid=u.uid)
						WHERE ".implode(' AND ', $where)."
					");
					while($feedback = $db->fetch_array($query))
					{
						$query_cache[$feedback['post_uid']][] = $feedback;
					}
				}
				else
				{
					$where[] = "f.uid='{$post['uid']}'";
					$query = $db->simple_select('ougc_feedback f', 'f.*', implode(' AND ', $where));
					while($feedback = $db->fetch_array($query))
					{
						$query_cache[$post['uid']][] = $feedback;
					}
				}
			}

			$stats = array('total' => 0, 'positive' => 0, 'neutral' => 0, 'negative' => 0, 'positive_percent' => 0, 'neutral_percent' => 0, 'negative_percent' => 0, 'positive_users' => array(), 'neutral_users' => array(), 'negative_users' => array());

			if(!empty($query_cache[$post['uid']]))
			{
				foreach($query_cache[$post['uid']] as $feedback)
				{
					++$stats['total'];

					$feedback['feedback'] = (int)$feedback['feedback'];
					switch($feedback['feedback'])
					{
						case 1:
							++$stats['positive'];
							$stats['positive_users'][$feedback['fuid']] = 1;
							break;
						case 0:
							++$stats['neutral'];
							$stats['neutral_users'][$feedback['fuid']] = 1;
							break;
						case -1:
							++$stats['negative'];
							$stats['negative_users'][$feedback['fuid']] = 1;
							break;
					}
				}
			}

			if($stats['total'])
			{
				$stats['positive_percent'] = floor(100*($stats['positive']/$stats['total']));
				$stats['neutral_percent'] = floor(100*($stats['neutral']/$stats['total']));
				$stats['negative_percent'] = floor(100*($stats['negative']/$stats['total']));
			}

			$stats['positive_users'] = count($stats['positive_users']);
			$stats['neutral_users'] = count($stats['neutral_users']);
			$stats['negative_users'] = count($stats['negative_users']);

			$stats = array_map('my_number_format', $stats);

			eval('$post[\'ougc_feedback\'] = "'.$templates->get('ougcfeedback_postbit').'";');
			$post['user_details'] = str_replace('<!--OUGC_FEEDBACK-->', $post['ougc_feedback'], $post['user_details']);
		}

		if(!($mybb->settings['ougc_feedback_allow_thread'] && $post['pid'] && ($mybb->settings['ougc_feedback_allow_thread_forums'] = -1 || my_strpos(','.$mybb->settings['ougc_feedback_allow_thread_forums'].',', ','.$post['fid'].',') !== false)))
		{
			return;
		}

		global $plugins, $thread;

		if($mybb->settings['ougc_feedback_allow_thread_firstpost'] && $thread['firstpost'] != $post['pid'])
		{
			return;
		}

		if(/*$post_type != 3 && */$post['uid'] != $mybb->user['uid'] && $this->permission('can_add') && $this->permission('can_receive', $post['uid']))
		{
			if(!$post['pid'])
			{
				$post['pid'] = 0;
			}

			eval('$post[\'ougc_feedback_button\'] = "'.$templates->get('ougcfeedback_postbit_button').'";');
		}

		#$plugins->remove_hook('postbit', array($this, 'hook_postbit'));
	}
}

global $feedback;

$feedback = new OUGC_Feedback;

// Tell MyBB when to run the hook
if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_config_settings_start', array($feedback, 'load_language'));
	$plugins->add_hook('admin_style_templates_set', array($feedback, 'load_language'));
	$plugins->add_hook('admin_config_settings_change', array($feedback, 'hook_admin_config_settings_change'));
}
else
{
	$plugins->add_hook('global_intermediate', array($feedback, 'hook_global_intermediate'));
	$plugins->add_hook('member_profile_end', array($feedback, 'hook_member_profile_end'));
	$plugins->add_hook('postbit', array($feedback, 'hook_postbit'));
	$plugins->add_hook('postbit_prev', array($feedback, 'hook_postbit'));
	$plugins->add_hook('postbit_pm', array($feedback, 'hook_postbit'));
	$plugins->add_hook('postbit_announcement', array($feedback, 'hook_postbit'));

	global $templatelist;

	if(isset($templatelist))
	{
		$templatelist .= ',';
	}
	else
	{
		$templatelist = '';
	}

	$templatelist .= 'ougcfeedback_js';

	switch(THIS_SCRIPT)
	{
		case 'member.php':
			$templatelist .= ',ougcfeedback_profile,ougcfeedback_profile_add,ougcfeedback_add,ougcfeedback_add_comment';
			break;
		case 'showthread.php':
			$templatelist .= ',ougcfeedback_postbit';
			break;
	}
}

		/*$db->insert_query('ougc_feedback', array('uid' => (int)$memprofile['uid'], 'fuid' => (int)$mybb->user['uid'], 'type' => 1, 'feedback' => 1, 'comment' => '', 'dateline' => TIME_NOW));
		$db->insert_query('ougc_feedback', array('uid' => (int)$memprofile['uid'], 'fuid' => (int)$mybb->user['uid'], 'type' => 1, 'feedback' => -1, 'comment' => '', 'dateline' => TIME_NOW));
		$db->insert_query('ougc_feedback', array('uid' => (int)$memprofile['uid'], 'fuid' => (int)$mybb->user['uid'], 'type' => 1, 'feedback' => 0, 'comment' => '', 'dateline' => TIME_NOW));

		$db->insert_query('ougc_feedback', array('uid' => (int)$memprofile['uid'], 'fuid' => (int)$mybb->user['uid'], 'type' => 2, 'feedback' => 1, 'comment' => '', 'dateline' => TIME_NOW));
		$db->insert_query('ougc_feedback', array('uid' => (int)$memprofile['uid'], 'fuid' => (int)$mybb->user['uid'], 'type' => 2, 'feedback' => -1, 'comment' => '', 'dateline' => TIME_NOW));
		$db->insert_query('ougc_feedback', array('uid' => (int)$memprofile['uid'], 'fuid' => (int)$mybb->user['uid'], 'type' => 2, 'feedback' => 0, 'comment' => '', 'dateline' => TIME_NOW));

		$db->insert_query('ougc_feedback', array('uid' => (int)$memprofile['uid'], 'fuid' => (int)$mybb->user['uid'], 'type' => 3, 'feedback' => 1, 'comment' => '', 'dateline' => TIME_NOW));
		$db->insert_query('ougc_feedback', array('uid' => (int)$memprofile['uid'], 'fuid' => (int)$mybb->user['uid'], 'type' => 3, 'feedback' => -1, 'comment' => '', 'dateline' => TIME_NOW));
		$db->insert_query('ougc_feedback', array('uid' => (int)$memprofile['uid'], 'fuid' => (int)$mybb->user['uid'], 'type' => 3, 'feedback' => 0, 'comment' => '', 'dateline' => TIME_NOW));*/