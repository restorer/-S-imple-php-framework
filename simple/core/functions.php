<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * System-wide functions
 */

define('S_NORMAL', 0);
define('S_ERROR', 1);
define('S_SUCCESS', 2);
define('S_ACCENT', 3);
define('S_NOTICE', 4);

/*
 * PHP Compat
 */
if (version_compare(phpversion(), '5.0') === -1) {
	/* Needs to be wrapped in eval as clone is a keyword in PHP5 */
	eval('
		function php_compat_clone($object)
		{
			// Sanity check
			if (!is_object($object)) {
				user_error(\'clone() __clone method called on non-object\', E_USER_WARNING);
				return;
			}

			// Use serialize/unserialize trick to deep copy the object
			$object = unserialize(serialize($object));

			// If there is a __clone method call it on the "new" class
			if (method_exists($object, \'__clone\')) {
				$object->__clone();
			}

			return $object;
		}

		function clone($object) {
			return php_compat_clone($object);
		}
	');
}

/*
 * Based on code from PHP Compat
 */
function i_get_backtrace()
{
	$backtrace = debug_backtrace();
	array_shift($backtrace);
	if (isset($backtrace[0]) && $backtrace[0]['function'] === 'i_get_backtrace') array_shift($backtrace);

	$res = '<table cellpadding="4" cellspacing="0" style="font-family:verdana;font-size:8pt;border-left:1px solid #000;border-top:1px solid #000;">';
	$res .= '<tr>';
	$res .= '<td style="background-color:#000;color:#FFF;border-right:1px solid #888;">#</td>';
	$res .= '<td style="background-color:#000;color:#FFF;border-right:1px solid #888;">Location</td>';
	$res .= '<td style="background-color:#000;color:#FFF;border-right:1px solid #888;">Line</td>';
	$res .= '<td style="background-color:#000;color:#FFF;">Function</td>';
	$res .= '</tr>';

	$calls = array();

	foreach ($backtrace as $i=>$call)
	{
		$location = (array_key_exists('file', $call) ? $call['file'] : '?');
		$line = (array_key_exists('line', $call) ? $call['line'] : '?');
		$function = (isset($call['class'])) ? $call['class'] . '.' . $call['function'] : $call['function'];

		$str = '<tr>';
		$str .= '<td style="border-right:1px solid #000;border-bottom:1px solid #000;">'.$i.'</td>';
		$str .= '<td style="border-right:1px solid #000;border-bottom:1px solid #000;">'.$location.'</td>';
		$str .= '<td style="border-right:1px solid #000;border-bottom:1px solid #000;">'.$line.'</td>';
		$str .= '<td style="border-right:1px solid #000;border-bottom:1px solid #000;">'.$function.'</td>';
		$str .= '</tr>';
		$calls[] = $str;
	}

	$res .= implode('',array_reverse($calls)) . '</table>';
	return $res;
}

##
# = void error(string $str)
# Throw error
##
function error($str)
{
	if (DEBUG)
	{
		$bt = i_get_backtrace();
		echo "<pre>$str</pre>";
		echo $bt;
		dflush();
		die;
	}
	else die("<pre>Server error: $str</pre>");
}

function i_on_php_error($code, $message, $filename='', $linenumber=-1, $context=array())
{
	if (error_reporting() == 0) return true;
	if (intval($code) == 2048) return true;		/* E_STRICT */

	// TODO: check for E_NOTICE

	error('Error '.$code.' ('.$message.') occured in '.$filename.' at '.$linenumber.'');
	return true;
}

set_error_handler('i_on_php_error');
if (!isset($_SESSION)) @session_start();

function i_stripslashes_deep($value)
{
	$value = is_array($value) ? array_map('i_stripslashes_deep', $value) : stripslashes($value);
	return $value;
}

if (ini_get('magic_quotes_gpc'))
{
	$_GET = i_stripslashes_deep($_GET);
	$_POST = i_stripslashes_deep($_POST);
	/* cookie ? */
}

##
# = string dump_str(string $var, int $indent = 0)
##
function dump_str($var, $indent = 0)
{
	$res = '';

	if (is_array($var) || is_object($var))
	{
		$spc = '';
		for ($i = 0; $i < $indent; $i++) $spc .= '  ';

		$sz = 0;
		foreach ($var as $k) $sz++;

		if (is_array($var)) $res .= 'array('. $sz .") {\n";
		else $res .= 'object(' . get_class($var) . ') (' . $sz . ") {\n";

		foreach ($var as $k=>$v) {
			$res .= "${spc}  [\"${k}\"] => " . dump_str($v, $indent+1);
		}

		$res .= $spc . '}';
	}
	elseif (is_null($var)) $res .= 'null';
	elseif (is_string($var)) $res .= 'string(' . strlen($var) .  ') "' . str_replace('"', '\\"', $var) . '"';
	else $res .= $var;

	$res .= "\n";
	return $res;
}

##
# = void make_directory(string $dir, int $mode=0777)
# Recursive make directory
##
function make_directory($dir, $mode=0777)
{
	$parent_dir = dirname($dir);

	if (!file_exists($parent_dir)) {
		make_directory($parent_dir, $mode);
	}

	mkdir($dir);
	chmod($dir, $mode);
}

##
# = float get_microtime()
##
function get_microtime() {
	list($usec, $sec) = explode(' ', microtime());
	return $usec + $sec;
}

##
# = string microtime_to_str(float $tm)
##
function microtime_to_str($tm)
{
	$ls = substr(number_format($tm - floor($tm), 8), 1);
	return (date('Y-m-d H:i:s', floor($tm)).' '.$ls);
}

##
# = string mtime_str()
##
function mtime_str() {
	return microtime_to_str(get_microtime());
}

##
# = void dwrite(string $str, int $type=S_NORMAL)
##
function dwrite($str, $type=S_NORMAL) {
	if (!DEBUG) return;
	if (!array_key_exists('_debug_log_', $GLOBALS)) $GLOBALS['_debug_log_'] = array();
	$GLOBALS["_debug_log_"][] = array('time'=>mtime_str(), 'type'=>$type, 'str'=>$str, 'msg'=>'');
}

##
# = void dwrite_msg(string $str, string $msg, int $type=S_NORMAL)
##
function dwrite_msg($str, $msg, $type=S_NORMAL) {
	if (!DEBUG) return;
	if (!array_key_exists('_debug_log_', $GLOBALS)) $GLOBALS['_debug_log_'] = array();
	$GLOBALS["_debug_log_"][] = array('time'=>mtime_str(), 'type'=>$type, 'str'=>$str, 'msg'=>$msg);
}

##
# = void dflush()
##
function dflush()
{
	if (!DEBUG) return;
	if (!array_key_exists('_debug_log_', $GLOBALS)) return;

	echo "\n",'<div style="margin:8px 2px 2px 2px;padding:2px 2px 2px 2px;border:1px solid #875;background-color:#FEA;"><code style="color:#000;font-family:courier new;font-size:8pt;">',"\n";

	foreach ($GLOBALS['_debug_log_'] as $arr)
	{
		$time = $arr['time'];
		$type = $arr['type'];
		$str = $arr['str'];
		$msg = $arr['msg'];

		if ($msg != '') {
			$str = "<b>$str</b><br />".nl2br(str_replace(' ', '&nbsp;', htmlspecialchars($msg)));
		}

		switch ($type)
		{
			case S_ERROR: $str = '<font color="#FF0000">'.$str.'</font>'; break;
			case S_SUCCESS: $str = '<font color="#008000">'.$str.'</font>'; break;
			case S_ACCENT: $str = '<font color="#F08000">'.$str.'</font>'; break;
			case S_NOTICE: $str = '<font color="#800000">'.$str.'</font>'; break;
		}

		echo '<font color="#008000">',$time,':</font> ',$str,"<br>\n";
	}

	echo '</code></div><br>';
}

##
# = string dflush_str()
##
function dflush_str()
{
	if (!DEBUG) return '';
	if (!array_key_exists('_debug_log_', $GLOBALS)) return '';

	$res = '';

	foreach ($GLOBALS['_debug_log_'] as $arr)
	{
		$time = $arr['time'];
		$type = $arr['type'];
		$str = $arr['str'];
		$msg = $arr['msg'];

		if ($msg != '') {
			$str = "[$str]\n$msg";
		}

		switch ($type)
		{
			case S_ERROR: 	$str = '(E) '.$str; break;
			case S_SUCCESS:	$str = '( ) '.$str; break;
			case S_ACCENT:	$str = '(*) '.$str; break;
			case S_NOTICE:	$str = '(I) '.$str; break;
		}

		$res .= $time.': '.$str."\n";
	}

	return $res;
}

##
# = mixed first_key(array $arr)
##
function first_key($arr)
{
	foreach ($arr as $k=>$v) return $k;
	return null;
}

##
# = mixed first_value(array $arr)
##
function first_value($arr)
{
	foreach ($arr as $k=>$v) return $v;
	return null;
}

##
# = bool cast_bool(mixed $val)
##
function cast_bool($val)
{
	if ($val === true) return true;
	if ($val === false) return false;

	$val = strtolower($val);
	return ($val=="true" || $val=="1" || $val=="on" || $val=="yes");
}

##
# = string now()
##
function now() {
	return date('Y-m-d H:i:s', time());
}

##
# = bool inGET($k)
##
function inGET($k) {
	return array_key_exists($k, $_GET);
}

##
# = bool inPOST($k)
##
function inPOST($k) {
	return array_key_exists($k, $_POST);
}

##
# = bool inSESSION($k)
##
function inSESSION($k) {
	return array_key_exists($k, $_SESSION);
}

##
# = bool inCOOKIE($k)
##
function inCOOKIE($k) {
	return array_key_exists($k, $_COOKIE);
}

##
# = mixed _GET(string $k, mixed $def='')
##
function _GET($k, $def='') {
	return (InGET($k) ? $_GET[$k] : $def);
}

##
# = mixed _POST(string $k, mixed $def='')
##
function _POST($k, $def='') {
	return (InPOST($k) ? $_POST[$k] : $def);
}

##
# = mixed _SESSION(string $k, mixed $def='')
##
function _SESSION($k, $def='') {
	return (InSESSION($k) ? $_SESSION[$k] : $def);
}

##
# = mixed _COOKIE(string $k, mixed $def='')
##
function _COOKIE($k, $def='') {
	return (InCOOKIE($k) ? $_COOKIE[$k] : $def);
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

		if (!isset($_SERVER['REMOTE_ADDR']))
		{
			$_SERVER = $HTTP_ENV_VARS;	// must be Apache
		}
	}
	// end of code taken from PHPMailer class

	return (array_key_exists($k, $_SERVER) ? $_SERVER[$k] : $def);
}

##
# = string jsencode(string $str)
##
function jsencode($str)
{
	$str = str_replace("\\", "\\\\", $str);
	$str = str_replace("'", "\\'", $str);
	$str = str_replace("\r", "\\r", $str);
	$str = str_replace("\n", "\\n", $str);
	$str = str_replace("</script>", "</'+'script>", $str);
	return $str;
}

##
# = void _log(string $msg, string $file='')
##
function _log($msg, $file='')
{
	if ($file == '') $file = conf('log.path');
 	if (!($f = fopen($file, 'at'))) Error('Can\'t open log file');
	if (!fwrite($f, $msg."\n")) Error('Can\'t write to log file');
	fclose($f);
}

##
# = void __log(string $msg, bool $nl=true)
##
function __log($msg, $nl=true)
{
	$fp = fopen(BASE.'_debuglog_.log', 'at');
	fwrite($fp, $msg);
	if ($nl) fwrite($fp, "\n");
	fclose($fp);
}
