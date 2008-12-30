<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * CGI-related functions
 */

##
# = static class SCGI
# .begin
##
class SCGI
{
	protected static function stripslashes_deep($value)
	{
		return (is_array($value) ? array_map(array('SCGI', 'stripslashes_deep'), $value) : stripslashes($value));
	}

	##
	# = public static void init()
	##
	public static function init()
	{
		global $s_runconf;

		if (!isset($_SESSION)) @session_start();

		if (ini_get('magic_quotes_gpc'))
		{
			$_GET = self::stripslashes_deep($_GET);
			$_POST = self::stripslashes_deep($_POST);
			/* $_COOKIE ? */
		}

		define('ROOT', conf('http.root'));
		define('SSL_ROOT', conf('ssl.root'));

		$s_runconf->set('time.sql.query', 0);
		$s_runconf->set('time.sql.parse', 0);
		$s_runconf->set('time.template', 0);
	}
}
##
# .end
##

##
# = bool inGET($k)
##
function inGET($k)
{
	return array_key_exists($k, $_GET);
}

##
# = bool inPOST($k)
##
function inPOST($k)
{
	return array_key_exists($k, $_POST);
}

##
# = bool inSESSION($k)
##
function inSESSION($k)
{
	return array_key_exists($k, $_SESSION);
}

##
# = bool inCOOKIE($k)
##
function inCOOKIE($k)
{
	return array_key_exists($k, $_COOKIE);
}

##
# = mixed _GET(string $k, mixed $def='')
##
function _GET($k, $def='')
{
	return (array_key_exists($k, $_GET) ? $_GET[$k] : $def);
}

##
# = mixed _POST(string $k, mixed $def='')
##
function _POST($k, $def='')
{
	return (array_key_exists($k, $_POST) ? $_POST[$k] : $def);
}

##
# = mixed _SESSION(string $k, mixed $def='')
##
function _SESSION($k, $def='')
{
	return (array_key_exists($k, $_SESSION) ? $_SESSION[$k] : $def);
}

##
# = mixed _COOKIE(string $k, mixed $def='')
##
function _COOKIE($k, $def='')
{
	return (array_key_exists($k, $_COOKIE) ? $_COOKIE[$k] : $def);
}

##
# = mixed _SERVER(string $k, mixed $def='')
##
function _SERVER($k, $def='')
{
	// begin of code taken from PHPMailer class
	global $HTTP_SERVER_VARS;
	global $HTTP_ENV_VARS;

	if (!isset($_SERVER))
	{
		$_SERVER = $HTTP_SERVER_VARS;

		if (!isset($_SERVER['REMOTE_ADDR'])) {
			$_SERVER = $HTTP_ENV_VARS;	// must be Apache
		}
	}
	// end of code taken from PHPMailer class

	return (array_key_exists($k, $_SERVER) ? $_SERVER[$k] : $def);
}
