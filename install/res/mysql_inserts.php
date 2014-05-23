<?php
/**
 * arsagen.net framework
 * Copyright Alexander René Sagen, No Rights Reserved.
 *
 * Website: http://arsagen.net
 * License: http://www.gnu.org/copyleft/gpl.html
 */

$inserts[] = array(
	'name' => 'website_usergroups',
	'sql' => "INSERT IGNORE INTO `website_usergroups` (`id`, `name`, `description`, `flags`) VALUES('1', 'root', 'The highest ranked usergroup.', '*');"
);

$inserts[] = array(
	'name' => 'website_themes',
	'sql' => "INSERT IGNORE INTO `website_themes` (`id`, `name`, `stylesheets`, `scripts`) VALUES ('1', 'Bootstrap 3', './assets/css/bootstrap.min.css', './assets/js/jquery-1.11.1.min.js,./assets/js/bootstrap.min.js');"
);

?>