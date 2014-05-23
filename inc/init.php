<?php
/**
 * Copyright Alexander René Sagen, No Rights Reserved.
 *
 * Website: http://arsagen.net
 * License: http://www.gnu.org/copyleft/gpl.html
 */

if(!defined('ROOT_FOLDER'))
{
	define('ROOT_FOLDER', dirname(dirname(__FILE__)).'/'); // Attempt autodetecting the root folder.
}

date_default_timezone_set('UTC');

require_once ROOT_FOLDER."inc/functions.php";

if(!file_exists(ROOT_FOLDER.'inc/config.php'))
{
	trigger_error('Config file is not present, please run the install script to generate one.', E_USER_ERROR); // Trigger error
}

require_once ROOT_FOLDER.'inc/db_mysqli.php'; // Require the database layer
$db = new db_mysqli;

if(!extension_loaded($db->engine))
{
	trigger_error("MySQLi failed to load. Have you installed the PHP mysql module?", E_USER_ERROR); // Trigger a MySQLi loading error.
}

require_once ROOT_FOLDER.'/inc/class_session.php';
$sessions = new sessionClass;

require_once ROOT_FOLDER.'/inc/class_user.php';
$users = new userClass;

require_once ROOT_FOLDER.'inc/config.php'; // Require the website configuration

$db->connect($config['database']); // Connect to the database

unset($config);

?>