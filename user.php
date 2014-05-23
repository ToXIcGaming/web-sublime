<?php
/**
 * arsagen.net framework
 * Copyright Alexander René Sagen, No Rights Reserved.
 *
 * Website: http://arsagen.net
 * License: http://www.gnu.org/copyleft/gpl.html
 */

define('ROOT_FOLDER', dirname(__FILE__).'/');

require_once ROOT_FOLDER.'inc/init.php';

/*
   Note: This file is an absolute spaghetti mess.

   Usage: POST username & password with AJAX, get a session generated.
*/
   
function user_login($username, $password)
{
	global $db;

	$attempts_query = $db->simple_select("website_loginattempts", "attempts, timeout", "LOWER(`username`) = LOWER('{$db->escape_string($username)}') AND ip = '".getvisitorip()."'");
	$attempts = $db->fetch_array($attempts_query);

	if($attempts['attempts'] == 5 && time() <= $attempts['timeout'])
	{
		return false;
	}

	$hash = $db->fetch_field($db->simple_select("website_users", "hash", "LOWER(`username`) = LOWER('{$db->escape_string($username)}')"), "hash");

	if(password_verify($password, $hash))
	{
		if(get_attempts($username, getvisitorip()))
		{
			$db->delete_query("website_loginattempts", "ip = '".getvisitorip()."' AND username = '{$db->escape_string($username)}'");
		}

		return true;
	}

	return false;
}

function get_attempts($username, $ip)
{
	global $db;

	$query = $db->simple_select("website_loginattempts", "attempts", "LOWER(`username`) = LOWER('{$db->escape_string($username)}') AND ip = '{$db->escape_string($ip)}'");

	$attempts = 0;

	while($res = $db->fetch_array($query))
	{
		$attempts += $res['attempts'];
	}

	return $attempts;
}

function get_timeout($username, $ip)
{
	global $db;

	return $db->fetch_field($db->simple_select("website_loginattempts", "timeout", "LOWER(`username`) = LOWER('{$db->escape_string($username)}') AND ip = '{$db->escape_string($ip)}'"), "timeout");
}

if(isset($_GET['action']))
{
	if($_GET['action'] == 'login' && isset($_POST['username'], $_POST['password']))
	{
		if(user_login($_POST['username'], $_POST['password']))
		{
			$persist = false;

			if(isset($_POST['remember_me']))
			{
				$persist = true;
			}

			$session_id = $sessions->generate($_POST['username'], $persist);

			$group = $users->get_group($_POST['username']);

			$redis->publish('node-php', json_encode(array('type' => 'verifySession', 'data' => array('id' => $session_id, 'username' => $_POST['username'], 'usergroup' => $group, 'flags' => $users->get_flags("", $group)))));

			echo json_encode(array('success' => true, 'sessionid' => $session_id));
		}
		else
		{	
			$attempts = get_attempts($_POST['username'], getvisitorip());

			if(!$attempts)
			{
				$attempts = 1;
				$db->insert_query("website_loginattempts", array('ip' => getvisitorip(), 'username' => $db->escape_string($_POST['username']), 'attempts' => $attempts, 'timeout' => strtotime('+30 minutes')));
			}
			else
			{
				if($attempts != 5)
				{
					$attempts++;
					$db->update_query("website_loginattempts", array('attempts' => $attempts, 'timeout' => strtotime('+30 minutes')), "LOWER(`username`) = LOWER('{$db->escape_string($_POST['username'])}') AND ip = '".getvisitorip()."'");
				}
			}

			echo json_encode(($attempts == 5 ? array('success' => false, 'attempts' => $attempts, 'timeout' => get_timeout($_POST['username'], getvisitorip())) : array('success' => false, 'attempts' => $attempts)));
		}
	}
	elseif($_GET['action'] == 'logout')
	{
		$sessions->destroy();
	}
}

?>