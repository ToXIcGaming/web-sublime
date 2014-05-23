<?php
/**
 * arsagen.net framework
 * Copyright Alexander René Sagen, All Rights Reserved.
 *
 * Website: http://arsagen.net
 * License: http://www.gnu.org/copyleft/gpl.html
 */

class userClass
{
	/**
	 * Gets an array of themes
	 * @param string A comma separated list of table fields to get
	 * @return array The array of themes.
	 */
	function get_array($users="")
	{
		global $db;

		if(!empty($users))
		{
			$sql = "";
			$names = explode(",", $users);
			foreach($names as $key => $name)
			{
				$sql .= " ,'" . trim($name) . "'";
			}

			$query = $db->simple_select("website_users", "username, usergroup, email, website, avatar, signature", "username IN (''$sql)");
		}
		else
		{
			$query = $db->simple_select("website_users", "username, usergroup, email, website, avatar, signature");
		}

		if($db->num_rows($query) == 0)
		{
			return false;
		}

		$users = array();

		while($user = $db->fetch_array($query))
		{
			$users[] = $user;
		}

		return $users;
	}

	/**
	 * Gets the group from the user's profile
	 *
	 * @param string Username
	 * @return string Usergroup
	 */
	function get_group($username="")
	{
		global $db, $sessions;
		
		if(empty($username))
		{
			$username = $sessions->get_user();
		}

		return $db->fetch_field($db->simple_select("website_users", "usergroup", "LOWER(`username`) = LOWER('{$db->escape_string($username)}')"), "usergroup");
	}

	/**
	 * Gets the flags from the usergroup
	 *
	 * @param string Username
	 * @return string Usergroup
	 */
	function get_flags($group="")
	{
		global $db, $sessions;
		
		if(empty($group))
		{
			$group = $sessions->get_user();
		}

		return $db->fetch_field($db->simple_select("website_usergroups", "flags", "name = '{$db->escape_string($group)}'"), "flags");
	}

	/**
	 * Checks if the user or usergroup has a flag
	 *
	 * @param string Flag to check for
	 * @param string Username
	 * @param string Usergroup
	 * @return boolean Has flag?
	 */
	function has_flag($flag, $username="", $usergroup="")
	{
		global $db;

		if(empty($username) && empty($usergroup))
		{
			$username = $this->get_user();
			$usergroup = $this->get_group($username);
		}
		elseif(!empty($username) && empty($usergroup))
		{
			$usergroup = $this->get_group($username);
		}

		return in_array($flag, json_decode($db->fetch_field($db->simple_select('website_usergroups', 'flags', 'usergroup = {$db->escape_string($usergroup)}'), 'flags')));
	}
}

?>