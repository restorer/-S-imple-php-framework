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
		return (is_array($value) ? array_map(array(self, 'stripslashes_deep'), $value) : stripslashes($value));
	}

	##
	# = public static void init()
	##
	public static function init()
	{
		if (!isset($_SESSION)) @session_start();

		if (ini_get('magic_quotes_gpc'))
		{
			$_GET = self::stripslashes_deep($_GET);
			$_POST = self::stripslashes_deep($_POST);
			/* $_COOKIE ? */
		}
	}

	##
	# = bool inGET($k)
	##
	public static function inGET($k)
	{
		return array_key_exists($k, $_GET);
	}

	##
	# = bool inPOST($k)
	##
	public static function inPOST($k)
	{
		return array_key_exists($k, $_POST);
	}

	##
	# = bool inSESSION($k)
	##
	public static function inSESSION($k)
	{
		return array_key_exists($k, $_SESSION);
	}

	##
	# = bool inCOOKIE($k)
	##
	public static function inCOOKIE($k)
	{
		return array_key_exists($k, $_COOKIE);
	}

	##
	# = mixed GET(string $k, mixed $def='')
	##
	public static function GET($k, $def='')
	{
		return (array_key_exists($k, $_GET) ? $_GET[$k] : $def);
	}

	##
	# = mixed POST(string $k, mixed $def='')
	##
	public static function POST($k, $def='')
	{
		return (array_key_exists($k, $_POST) ? $_POST[$k] : $def);
	}

	##
	# = mixed SESSION(string $k, mixed $def='')
	##
	public static function SESSION($k, $def='')
	{
		return (array_key_exists($k, $_SESSION) ? $_SESSION[$k] : $def);
	}

	##
	# = mixed COOKIE(string $k, mixed $def='')
	##
	public static function COOKIE($k, $def='')
	{
		return (array_key_exists($k, $_COOKIE) ? $_COOKIE[$k] : $def);
	}

	##
	# = mixed SERVER(string $k, mixed $def='')
	##
	public static function SERVER($k, $def='')
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
}
##
# .end
##
