<?php
/**
 * arsagen.net framework
 * Copyright Alexander René Sagen, No Rights Reserved.
 *
 * Website: http://arsagen.net
 * License: http://www.gnu.org/copyleft/gpl.html
 */


$tables[] = array(
  "name" => "website_themes",
  "sql" => "CREATE TABLE IF NOT EXISTS `website_themes` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(45) NOT NULL,
  `stylesheets` TEXT NOT NULL,
  `scripts` TEXT NOT NULL,
  UNIQUE (`name`),
  PRIMARY KEY (`id`)
  ) ENGINE = MyISAM;");

$tables[] = array(
  "name" => "website_users",
  "sql" => "CREATE TABLE IF NOT EXISTS `website_users` (
  `id` INT(11) NOT NULL auto_increment,
  `username` VARCHAR(120) NOT NULL,
  `hash` VARCHAR(120) NOT NULL,
  `usergroup` VARCHAR(50) NOT NULL default 'user',
  `email` VARCHAR(220) NOT NULL,
  `website` VARCHAR(200),
  `avatar` VARCHAR(200),
  `signature` TEXT,
  `lastip` VARCHAR(50) NOT NULL,
  UNIQUE KEY `username` (`username`),
  KEY `usergroup` (`usergroup`),
  UNIQUE (`username`),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;");

$tables[] = array(
  "name" => "website_usergroups",
  "sql" => "CREATE TABLE IF NOT EXISTS `website_usergroups` (
  `id` INT(11) NOT NULL auto_increment,
  `name` VARCHAR(120) NOT NULL,
  `description` TEXT NOT NULL,
  `flags` TEXT NOT NULL,
  UNIQUE (`name`),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;");

$tables[] = array(
  "name" => "website_loginattempts",
  "sql" => "CREATE TABLE IF NOT EXISTS `website_loginattempts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `ip` VARCHAR(40) NOT NULL,
  `username` VARCHAR(120) NOT NULL,
  `attempts` INT(1) NOT NULL,
  `timeout` INT(10) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = MyISAM;");

$tables[] = array(
  "name" => "website_sessions",
  "sql" => "CREATE TABLE IF NOT EXISTS `website_sessions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(120) NOT NULL,
  `session` VARCHAR(50) NOT NULL,
  `ip` VARCHAR(40) NOT NULL,
  `expire` BIGINT(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = MyISAM;");

?>