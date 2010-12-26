<?php
/**
*
* @package phpBB3
* @version $Id$
* @copyright (c) 2005 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
*/

/**
* @ignore
*/
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_display.' . $phpEx);

// Start session management
$user->session_begin();
$auth->acl($user->data);
$user->setup('viewforum');

display_forums('', $config['load_moderators']);

// Set some stats, get posts count from forums data if we... hum... retrieve all forums data
$total_posts	= $config['num_posts'];
$total_topics	= $config['num_topics'];
$total_users	= $config['num_users'];

$l_total_user_s = ($total_users == 0) ? 'TOTAL_USERS_ZERO' : 'TOTAL_USERS_OTHER';
$l_total_post_s = ($total_posts == 0) ? 'TOTAL_POSTS_ZERO' : 'TOTAL_POSTS_OTHER';
$l_total_topic_s = ($total_topics == 0) ? 'TOTAL_TOPICS_ZERO' : 'TOTAL_TOPICS_OTHER';

// Grab group details for legend display
if ($auth->acl_gets('a_group', 'a_groupadd', 'a_groupdel'))
{
	$sql = 'SELECT group_id, group_name, group_colour, group_type
		FROM ' . GROUPS_TABLE . '
		WHERE group_legend = 1
		ORDER BY group_name ASC';
}
else
{
	$sql = 'SELECT g.group_id, g.group_name, g.group_colour, g.group_type
		FROM ' . GROUPS_TABLE . ' g
		LEFT JOIN ' . USER_GROUP_TABLE . ' ug
			ON (
				g.group_id = ug.group_id
				AND ug.user_id = ' . $user->data['user_id'] . '
				AND ug.user_pending = 0
			)
		WHERE g.group_legend = 1
			AND (g.group_type <> ' . GROUP_HIDDEN . ' OR ug.user_id = ' . $user->data['user_id'] . ')
		ORDER BY g.group_name ASC';
}
$result = $db->sql_query($sql);

$legend = array();
while ($row = $db->sql_fetchrow($result))
{
	$colour_text = ($row['group_colour']) ? ' style="color:#' . $row['group_colour'] . '"' : '';
	$group_name = ($row['group_type'] == GROUP_SPECIAL) ? $user->lang['G_' . $row['group_name']] : $row['group_name'];

	if ($row['group_name'] == 'BOTS' || ($user->data['user_id'] != ANONYMOUS && !$auth->acl_get('u_viewprofile')))
	{
		$legend[] = '<span' . $colour_text . '>' . $group_name . '</span>';
	}
	else
	{
		$legend[] = '<a' . $colour_text . ' href="' . append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=group&amp;g=' . $row['group_id']) . '">' . $group_name . '</a>';
	}
}
$db->sql_freeresult($result);

$legend = implode(', ', $legend);

// Generate birthday list if required ...
$birthday_list = '';
if ($config['load_birthdays'] && $config['allow_birthdays'])
{
	$now = getdate(time() + $user->timezone + $user->dst - date('Z'));
	$sql = 'SELECT u.user_id, u.username, u.user_colour, u.user_birthday
		FROM ' . USERS_TABLE . ' u
		LEFT JOIN ' . BANLIST_TABLE . " b ON (u.user_id = b.ban_userid)
		WHERE (b.ban_id IS NULL
			OR b.ban_exclude = 1)
			AND u.user_birthday LIKE '" . $db->sql_escape(sprintf('%2d-%2d-', $now['mday'], $now['mon'])) . "%'
			AND u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')';
	$result = $db->sql_query($sql);

	while ($row = $db->sql_fetchrow($result))
	{
		$birthday_list .= (($birthday_list != '') ? ', ' : '') . get_username_string('full', $row['user_id'], $row['username'], $row['user_colour']);

		if ($age = (int) substr($row['user_birthday'], -4))
		{
			$birthday_list .= ' (' . ($now['year'] - $age) . ')';
		}
	}
	$db->sql_freeresult($result);
}

if ( isset($config['announcement_enable']))
{
	if ( $config['announcement_show_index'] && ($config['announcement_enable'] || $config['announcement_show_birthdays_always']) )
	{
		if (!function_exists('get_announcement_data'))
		{
			include($phpbb_root_path . 'includes/functions_announcements.' . $phpEx);
		}
		get_announcement_data();
	}
}
// Assign index specific vars
$template->assign_vars(array(
	'TOTAL_POSTS'	=> sprintf($user->lang[$l_total_post_s], $total_posts),
	'TOTAL_TOPICS'	=> sprintf($user->lang[$l_total_topic_s], $total_topics),
	'TOTAL_USERS'	=> sprintf($user->lang[$l_total_user_s], $total_users),
	'NEWEST_USER'	=> sprintf($user->lang['NEWEST_USER'], get_username_string('full', $config['newest_user_id'], $config['newest_username'], $config['newest_user_colour'])),

	'LEGEND'		=> $legend,
	'BIRTHDAY_LIST'	=> $birthday_list,

	'FORUM_IMG'				=> $user->img('forum_read', 'NO_UNREAD_POSTS'),
	'FORUM_UNREAD_IMG'			=> $user->img('forum_unread', 'UNREAD_POSTS'),
	'FORUM_LOCKED_IMG'		=> $user->img('forum_read_locked', 'NO_UNREAD_POSTS_LOCKED'),
	'FORUM_UNREAD_LOCKED_IMG'	=> $user->img('forum_unread_locked', 'UNREAD_POSTS_LOCKED'),

	'S_LOGIN_ACTION'			=> append_sid("{$phpbb_root_path}ucp.$phpEx", 'mode=login'),
	'S_DISPLAY_BIRTHDAY_LIST'	=> ($config['load_birthdays']) ? true : false,
    'S_ANNOUNCE_INDEX'  => (!empty($config['announce_index'])) ? true : false,

	'U_MARK_FORUMS'		=> ($user->data['is_registered'] || $config['load_anon_lastread']) ? append_sid("{$phpbb_root_path}index.$phpEx", 'hash=' . generate_link_hash('global') . '&amp;mark=forums') : '',
	'U_MCP'				=> ($auth->acl_get('m_') || $auth->acl_getf_global('m_')) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=main&amp;mode=front', true, $user->session_id) : '')
);
// START Global Announcements
				$user->add_lang(array('memberlist', 'viewforum'));

				$sql_from = TOPICS_TABLE . ' t ';
				$sql_select = '';

				if ($config['load_db_track'])
				{
					$sql_from .= ' LEFT JOIN ' . TOPICS_POSTED_TABLE . ' tp ON (tp.topic_id = t.topic_id
						AND tp.user_id = ' . $user->data['user_id'] . ')';
					$sql_select .= ', tp.topic_posted';
				}

				if ($config['load_db_lastread'])
				{
					$sql_from .= ' LEFT JOIN ' . TOPICS_TRACK_TABLE . ' tt ON (tt.topic_id = t.topic_id
						AND tt.user_id = ' . $user->data['user_id'] . ')';
					$sql_select .= ', tt.mark_time';
				}

				$topic_type = $user->lang['VIEW_TOPIC_GLOBAL'];
				$folder = 'global_read';
				$folder_new = 'global_unread';

				// Get cleaned up list... return only those forums not having the f_read permission
				$forum_ary = $auth->acl_getf('!f_read', true);
				$forum_ary = array_unique(array_keys($forum_ary));

				// Determine first forum the user is able to read into - for global announcement link
				$sql = 'SELECT forum_id
					FROM ' . FORUMS_TABLE . '
					WHERE forum_type = ' . FORUM_POST;

				if (sizeof($forum_ary))
				{
					$sql .= ' AND ' . $db->sql_in_set('forum_id', $forum_ary, true);
				}
				$result = $db->sql_query_limit($sql, 1);
				$g_forum_id = (int) $db->sql_fetchfield('forum_id');
				$db->sql_freeresult($result);

				$sql = "SELECT t.* $sql_select
					FROM $sql_from
					WHERE t.forum_id = 0
						AND t.topic_type = " . POST_GLOBAL . '
					ORDER BY t.topic_last_post_time DESC';

				$topic_list = $rowset = array();
				// If the user can't see any forums, he can't read any posts because fid of 0 is invalid
				if ($g_forum_id)
				{
					$result = $db->sql_query($sql);

					while ($row = $db->sql_fetchrow($result))
					{
						$topic_list[] = $row['topic_id'];
						$rowset[$row['topic_id']] = $row;
					}
					$db->sql_freeresult($result);
				}

				$topic_tracking_info = array();
				if ($config['load_db_lastread'] && $user->data['is_registered'])
				{
					$topic_tracking_info = get_topic_tracking(0, $topic_list, $rowset, false, $topic_list);
				}
				else
				{
					$topic_tracking_info = get_complete_topic_tracking(0, $topic_list, $topic_list);
				}

				foreach ($topic_list as $topic_id)
				{
					$row = &$rowset[$topic_id];

					$forum_id = $row['forum_id'];
					$topic_id = $row['topic_id'];

					$unread_topic = (isset($topic_tracking_info[$topic_id]) && $row['topic_last_post_time'] > $topic_tracking_info[$topic_id]) ? true : false;
                    // Grab icons
                    $icons = $cache->obtain_icons();

					$folder_img = ($unread_topic) ? $folder_new : $folder;
					$folder_alt = ($unread_topic) ? 'UNREAD_POSTS' : (($row['topic_status'] == ITEM_LOCKED) ? 'TOPIC_LOCKED' : 'NO_UNREAD_POSTS');
                    $topic_link = append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'f=' . $row['forum_id'] . '&amp;t=' . $row['topic_id']);
                    $topic_views = $user->lang($row['topic_views']);
                    $topic_replies = $user->lang($row['topic_replies']);
                    $topic_last_author = get_username_string('full', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']);
      
					if ($row['topic_status'] == ITEM_LOCKED)
					{
						$folder_img .= '_locked';
					}

					// Posted image?
					if (!empty($row['topic_posted']) && $row['topic_posted'])
					{
						$folder_img .= '_mine';
					}

					$template->assign_block_vars('topicrow', array(
						'FORUM_ID'					=> $forum_id,
						'TOPIC_ID'					=> $topic_id,
						'TOPIC_AUTHOR'				=> get_username_string('username', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
						'TOPIC_AUTHOR_COLOUR'		=> get_username_string('colour', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
						'TOPIC_AUTHOR_FULL'			=> get_username_string('full', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
						'FIRST_POST_TIME'			=> $user->format_date($row['topic_time']),
						'LAST_POST_SUBJECT'			=> censor_text($row['topic_last_post_subject']),
						'LAST_POST_TIME'			=> $user->format_date($row['topic_last_post_time']),
						'LAST_VIEW_TIME'			=> $user->format_date($row['topic_last_view_time']),
						'LAST_POST_AUTHOR'			=> get_username_string('username', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
						'LAST_POST_AUTHOR_COLOUR'	=> get_username_string('colour', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
						'LAST_POST_AUTHOR_FULL'		=> get_username_string('full', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
						'TOPIC_TITLE'				=> censor_text($row['topic_title']),
						'TOPIC_TYPE'				=> $topic_type,
                        'TOPIC_LINK'                => $topic_link,
	                    'REPLIES'			        => $topic_replies,
	                    'VIEWS'				        => $topic_views,
		                'TOPIC_LAST_AUTHOR'         => $topic_last_author,
						'TOPIC_FOLDER_IMG'		=> $user->img($folder_img, $folder_alt),
						'TOPIC_FOLDER_IMG_SRC'	=> $user->img($folder_img, $folder_alt, false, '', 'src'),
                        'TOPIC_ICON_IMG'		=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['img'] : '',
			            'TOPIC_ICON_IMG_WIDTH'	=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['width'] : '',
			            'TOPIC_ICON_IMG_HEIGHT'	=> (!empty($icons[$row['icon_id']])) ? $icons[$row['icon_id']]['height'] : '',
			
						'S_USER_POSTED'		=> (!empty($row['topic_posted']) && $row['topic_posted']) ? true : false,
						'S_UNREAD'			=> $unread_topic,

						'U_TOPIC_AUTHOR'		=> get_username_string('profile', $row['topic_poster'], $row['topic_first_poster_name'], $row['topic_first_poster_colour']),
						'U_LAST_POST'			=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$g_forum_id&amp;t=$topic_id&amp;p=" . $row['topic_last_post_id']) . '#p' . $row['topic_last_post_id'],
						'U_LAST_POST_AUTHOR'	=> get_username_string('profile', $row['topic_last_poster_id'], $row['topic_last_poster_name'], $row['topic_last_poster_colour']),
						'U_NEWEST_POST'			=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$g_forum_id&amp;t=$topic_id&amp;view=unread") . '#unread',
						'U_VIEW_TOPIC'			=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$g_forum_id&amp;t=$topic_id"))
					);
				}
				
// END Global Announcements

// Output page
page_header($user->lang['INDEX']);

$template->set_filenames(array(
	'body' => 'index_body.html')
);

page_footer();

?>