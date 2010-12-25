<?php
//
//	file: language/en/mods/info_acp_ltt.php
//	author: abdev
//	begin: 12/12/2010
//	version: 0.0.1 - 12/12/2010
//	licence: http://opensource.org/licenses/gpl-license.php GNU Public License
//

// ignore
if ( !defined('IN_PHPBB') )
{
	exit;
}

// init lang ary, if it doesn't !
if ( empty($lang) || !is_array($lang) )
{
	$lang = array();
}

// administration
$lang = array_merge($lang, array(
	'LTT_MAX_CHARS' => 'Maximum characters per displayed title',
	'LTT_MAX_CHARS_EXPLAIN' => 'The supplementary characters will be replaced by “...”.',

	'LTT_URL' => 'Link redirection',
	'FIRST_POST' => 'First post',
));

// umil
$lang = array_merge($lang, array(
	'LTT' => 'Latest Topic Title',

	'INSTALL_LTT' => 'Install Latest Topic Title',
	'INSTALL_LTT_CONFIRM' => 'Are you ready to install Latest Topic Title ?',
	'UPDATE_LTT' => 'Update Latest Topic Title',
	'UPDATE_LTT_CONFIRM' => 'Are you ready to update Latest Topic Title ?',
	'UNINSTALL_LTT' => 'Uninstall Latest Topic Title',
	'UNINSTALL_LTT_CONFIRM' => 'Are you ready to uninstall Latest Topic Title ? All settings for this MOD will be removed !',
));
