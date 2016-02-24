<?php

/**
*
* @package phpBB Extension - Feed Reader
* @copyright (c) 2016 OXPUS - www.oxpus.net
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace oxpus\feed\acp;

/**
* @package acp
*/
class config_module
{
	var $u_action;

	function main($id, $mode)
	{
		global $db, $config, $user, $request, $auth, $cache, $template;

		$auth->acl($user->data);
		if (!$auth->acl_get('a_ox_feed_reader_config'))
		{
			trigger_error('NO_PERMISSION', E_USER_WARNING);
		}

		$this->tpl_name = 'acp_board';
		$this->page_title = 'ACP_OX_FEED_READER_CONFIG';

		$submit = $request->variable('submit', '');

		if ($submit && !check_form_key('ox_feed_config'))
		{
			trigger_error('FORM_INVALID', E_USER_WARNING);
		}
		
		if (!$submit)
		{
			add_form_key('ox_feed_config');
		}

		$s_hidden_fields = array();

		$display_vars = array(
			'title'	=> 'ACP_OX_FEED_READER_CONFIG',
			'vars'	=> array(
				'legend1'				=> '',
		
				'sfnc_index_init'			=> array('lang' => 'ACP_OX_INIT_ON_INDEX',		'validate' => 'bool',	'type' => 'radio:yes_no',	'explain' => true),
				'sfnc_index_posting'		=> array('lang' => 'ACP_OX_POSTING_ON_INDEX',	'validate' => 'bool',	'type' => 'radio:yes_no',	'explain' => true),
				'sfnc_cron_init'			=> array('lang' => 'ACP_OX_INIT_ON_CRON',		'validate' => 'bool',	'type' => 'radio:yes_no',	'explain' => true),
				'sfnc_cron_posting'			=> array('lang' => 'ACP_OX_POSTING_ON_CRON',	'validate' => 'bool',	'type' => 'radio:yes_no',	'explain' => true),
				'sfnc_download_function'	=> array('lang' => 'ACP_OX_DOWNLOAD_FUNCTION',	'validate' => 'string',	'type' => 'select',			'explain' => true, 	'method' => 'feed_download_type'),

				'legend2'				=> '',
		
				'sfnc_ticker_position'		=> array('lang' => 'ACP_OX_FEED_TICKER_POSITION',	'validate' => 'string',	'type' => 'select',			'explain' => true, 	'method' => 'ticker_position'),
			)
		);

		$this->new_config = $config;
		$cfg_array = (isset($_REQUEST['config'])) ? $request->variable('config', array('' => ''), true) : $this->new_config;
		$error = array();
		
		validate_config_vars($display_vars['vars'], $cfg_array, $error);
		
		foreach ($display_vars['vars'] as $config_name => $null)
		{
			if (!isset($cfg_array[$config_name]) || (strpos($config_name, 'legend') !== false && strpos($config_name, '_legend') === false))
			{
				continue;
			}
		
			$this->new_config[$config_name] = $config_value = $cfg_array[$config_name];
		
			if ($submit)
			{
				set_config($config_name, $config_value, false);
			}
		}
		
		if ($submit)
		{
			add_log('admin', 'ACP_OX_FEED_READER_CONFIG_UPDATED');
			$cache->destroy('config');
		
			$message = $user->lang['ACP_OX_FEED_READER_CONFIG_UPDATED'] . '<br />' . adm_back_link($this->u_action);
			trigger_error($message);
		}

		$user->add_lang('acp/users');
		
		$template->assign_vars(array(
			'L_TITLE'			=> $user->lang['ACP_OX_FEED_READER_CONFIG'],
			'L_TITLE_PAGE'		=> $user->lang[$display_vars['title']],
			'L_TITLE_EXPLAIN'	=> '',
		
			'S_ERROR'			=> (sizeof($error)) ? true : false,
			'ERROR_MSG'			=> implode('<br />', $error),
			'S_HIDDEN_FIELDS'	=> (sizeof($s_hidden_fields)) ? build_hidden_fields($s_hidden_fields) : '',
		
			'U_ACTION'			=> $this->u_action)
		);
		
		// Output relevant page
		foreach ($display_vars['vars'] as $config_key => $vars)
		{
			if (!is_array($vars) && (strpos($config_key, 'legend') === false && strpos($config_key, '_legend') === false))
			{
				continue;
			}
		
			if (strpos($config_key, 'legend') !== false && strpos($config_key, '_legend') === false)
			{
				$template->assign_block_vars('options', array(
					'S_LEGEND'		=> true,
					'LEGEND'		=> (@isset($user->lang[$vars])) ? $user->lang[$vars] : $vars)
				);
		
				continue;
			}
		
			$type = explode(':', $vars['type']);
		
			$l_explain = '';
			if ($vars['explain'])
			{
				$l_explain = (isset($user->lang[$vars['lang'] . '_DESCRIPTION'])) ? $user->lang[$vars['lang'] . '_DESCRIPTION'] : '';
			}
		
			$content = build_cfg_template($type, $config_key, $this->new_config, $config_key, $vars);
		
			if (empty($content))
			{
				continue;
			}
		
			$template->assign_block_vars('options', array(
				'KEY'			=> $config_key,
				'TITLE'			=> (isset($user->lang[$vars['lang']])) ? $user->lang[$vars['lang']] : $vars['lang'],
				'S_EXPLAIN'		=> $vars['explain'],
				'TITLE_EXPLAIN'	=> $l_explain,
				'CONTENT'		=> $content,
				)
			);
		
			unset($display_vars['vars'][$config_key]);
		}
	}

	function feed_download_type($value, $key)
	{
		$s_select = '<option value="simplexml">simplexml</option>';
		$s_select .= '<option value="curl">curl</option>';
		$s_select .= '<option value="fopen">fopen</option>';
	
		return str_replace('value="' . $value . '">', 'value="' . $value . '" selected="selected">', $s_select);
	}

	function ticker_position($value, $key)
	{
		global $user;

		$s_select = '<option value="0">' . $user->lang['ACP_OX_FEED_TICKER_POSITION_0'] . '</option>';
		$s_select .= '<option value="1">' . $user->lang['ACP_OX_FEED_TICKER_POSITION_1'] . '</option>';
		$s_select .= '<option value="2">' . $user->lang['ACP_OX_FEED_TICKER_POSITION_2'] . '</option>';
		$s_select .= '<option value="3">' . $user->lang['ACP_OX_FEED_TICKER_POSITION_3'] . '</option>';
		$s_select .= '<option value="4">' . $user->lang['ACP_OX_FEED_TICKER_POSITION_4'] . '</option>';
		$s_select .= '<option value="5">' . $user->lang['ACP_OX_FEED_TICKER_POSITION_5'] . '</option>';
		$s_select .= '<option value="6">' . $user->lang['ACP_OX_FEED_TICKER_POSITION_6'] . '</option>';
		$s_select .= '<option value="7">' . $user->lang['ACP_OX_FEED_TICKER_POSITION_7'] . '</option>';
	
		return str_replace('value="' . $value . '">', 'value="' . $value . '" selected="selected">', $s_select);
	}
}
