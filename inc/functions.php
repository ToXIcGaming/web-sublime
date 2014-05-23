<?php
/**
 * arsagen.net framework
 * Copyright Alexander René Sagen, All Rights Reserved.
 *
 * Website: http://arsagen.net
 * License: http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Custom function for htmlspecialchars which takes in to account unicode
 * 
 * This function was shamelessly stolen from MyBB :|
 *
 * @param string The string to format
 * @return string The string with htmlspecialchars applied
 */
function htmlspecialchars_uni($message)
{
  $message = preg_replace("#&(?!\#[0-9]+;)#si", "&amp;", $message); // Fix & but allow unicode
  $message = str_replace(array("<", ">", "\""), array("&lt;", "&gt;", "&quot;"), $message);

  return $message;
}

/**
 * Gets the IP address of the person currently viewing the site.
 *
 * @return string The IP address.
 */
function getvisitorip()
{
    $ip = '0.0.0.0';
    if ($_SERVER) {
        if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] ) {
            $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif ( isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER["HTTP_CLIENT_IP"] ) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            $ip = $_SERVER["REMOTE_ADDR"];
        }
    } else {
        if ( getenv('HTTP_X_FORWARDED_FOR') ) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif ( getenv('HTTP_CLIENT_IP') ) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else {
            $ip = getenv('REMOTE_ADDR');
        }
    }
    return $ip;
}

/**
 * Gets the status of a given IP and port.
 *
 * @param string The IP address of the server.
 * @param integer The port of the server.
 * @param integer The timeout of the function.
 * @param string The function to use.
 * @return boolean Returns true if the server responds, false if not.
 */
function get_status($ip, $port, $timeout=0.2, $function='fsockopen')
{
  switch($function)
  {
    case 'fsockopen':
      $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
      break;
    case 'pfsockopen':
      $fp = @pfsockopen($ip, $port, $errno, $errstr, $timeout);
      break;
    default: 
      $fp = @fsockopen($ip, $port, $errno, $errstr, $timeout);
      break;
  }

  if($fp !== false && is_resource($fp)) 
  {
    fclose($fp);
    unset($fp);
    return true;
  }

  unset($fp);
  return false;
}

/**
 * Minifies stuff
 *
 * @param string The string to minify.
 * @return string The minified string.
 */
function minify($contents)
{
  $contents = str_replace(array("\t", "\n", "  "), NULL, $contents);

  return $contents;
}

?>