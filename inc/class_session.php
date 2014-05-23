<?php
/**
 * arsagen.net framework
 * Copyright Alexander René Sagen, All Rights Reserved.
 *
 * Website: http://arsagen.net
 * License: http://www.gnu.org/copyleft/gpl.html
 */

class sessionClass
{
	/**
	 * Determines how long sessions persist (Minutes)
	 *
	 * @var int
	 */
	public $expire = 30;

	/**
	 * Gets the Session ID from cookies
	 *
	 * @return string Session ID
	 */
	function get_id()
	{
		if(isset($_COOKIE['session']))
		{
			if(!$this->is_valid($_COOKIE['session']))
			{
				return false;
			}

			return $_COOKIE['session'];
		}
		
		return false;
	}

	/**
	 * Gets the username stored with the session in the db
	 *
	 * @return string Username
	 */
	function get_user($session_id="")
	{
		global $db;

		if(empty($session_id))
		{
			$session_id = $this->get_id();
		}

		if(!$this->is_valid($session_id))
		{
			return false;
		}

		return $db->fetch_field($db->simple_select("website_sessions", "username", "session = '{$db->escape_string($session_id)}'"), "username");
	}

	/**
	 * Gets the DB timestamp and compares it to the current timestamp, and eventually deletes the cookie.
	 *
	 * @return boolean False if expired.
	 */
	function is_valid($session_id)
	{
		global $db;

		$query = $db->simple_select("website_sessions", "session, expire, ip", "session = '{$db->escape_string($session_id)}'");

		if($db->num_rows($query) <= 0)
		{
			return false;
		}

		while($row = $db->fetch_array($query))
		{
			if($row['expire'] < time() || $row['ip'] != getvisitorip())
			{
				return "session_expired";
			}
		}

		return true;
	}

	/**
	 * Generates a new session key and stores it in the database
	 *
	 * @return string Session ID or boolean False on error
	 */
	function generate($username, $persist=false)
	{
		global $db;

		if($this->get_user() && $this->is_valid($this->get_id()))
		{
			return false;
		}

		$username = $db->fetch_field($db->simple_select("website_users", "username", "LOWER(`username`) = LOWER('{$db->escape_string($username)}')"), "username");

		$session_id = bin2hex(openssl_random_pseudo_bytes(16));

		$expire = strtotime('+'.$this->expire.' minutes');

		if($persist)
		{
			$expire = strtotime('+1 year');
		}

		if(!$db->insert_query('website_sessions', array('username' => $username, 'session' => $session_id, 'ip' => getvisitorip(), 'expire' => $expire)))
		{
			return false;
		}

		setcookie('session', $session_id, $expire);

		return $session_id;
	}

	/**
	 * Unsets the cookie, and deletes the session from the database
	 *
	 * @param string Session ID
	 * @param string Username
	 * @return false when unsuccessful
	 */
	function destroy($session_id="", $username="")
	{
		global $db;

		if(empty($session_id) && empty($username))
		{
			$session_id = $this->get_id();
			$username = $this->get_user();
		}

		$db->query("DELETE FROM `website_sessions` WHERE session = '{$db->escape_string($session_id)}'".($username != false ? " AND username = '{$db->escape_string($username)}'" : "").";");

		setcookie('session', '', 0);
		setcookie('username', '', 0);

		return false;
	}
}

?>