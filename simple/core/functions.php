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
 * Based on code from PHP Compat
 */
function i_get_backtrace($funcname='')
{
	$backtrace = debug_backtrace();

	$skip_it = array('i_get_backtrace' => true);
	if (strlen($funcname)) $skip_it[$funcname] = true;

	for ($i = 0; $i<3 && count($backtrace); $i++) {
		if (array_key_exists($backtrace[0]['function'], $skip_it)) {
			array_shift($backtrace);
		}
	}

	$res = array();

	foreach ($backtrace as $ind=>$call)
	{
		$location = (array_key_exists('file', $call) ? $call['file'] : '?');
		$line = (array_key_exists('line', $call) ? $call['line'] : '?');
		$function = (array_key_exists('class', $call) ? ($call['class'] . '.' . $call['function']) : $call['function']);

		$res[] = array('ind' => $ind, 'loc' => $location, 'line' => $line, 'func' => $function);
	}

	return $res;
}

function i_get_backtrace_html()
{
	$calls = i_get_backtrace('i_get_backtrace_html');

	$res = '<table cellpadding="4" cellspacing="0" style="font-family:Tahoma,Arial;font-size:8pt;border-left:1px solid #000;border-top:1px solid #000;">';
	$res .= '<tr>';
	$res .= '<td style="background-color:#000;color:#FFF;border-right:1px solid #888;">#</td>';
	$res .= '<td style="background-color:#000;color:#FFF;border-right:1px solid #888;">Location</td>';
	$res .= '<td style="background-color:#000;color:#FFF;border-right:1px solid #888;">Line</td>';
	$res .= '<td style="background-color:#000;color:#FFF;">Function</td>';
	$res .= '</tr>';

	foreach ($calls as $call)
	{
		$res .= '<tr>';
		$res .= '<td style="border-right:1px solid #000;border-bottom:1px solid #000;">'.$call['ind'].'</td>';
		$res .= '<td style="border-right:1px solid #000;border-bottom:1px solid #000;">'.$call['loc'].'</td>';
		$res .= '<td style="border-right:1px solid #000;border-bottom:1px solid #000;">'.$call['line'].'</td>';
		$res .= '<td style="border-right:1px solid #000;border-bottom:1px solid #000;">'.$call['func'].'</td>';
		$res .= '</tr>';
	}

	return $res . '</table>';
}

function i_get_backtrace_text()
{
	$calls = i_get_backtrace('i_get_backtrace_text');

	$hdr = array('ind' => '#', 'loc' => 'Location', 'line' => 'Line', 'func' => 'Function');
	$sizes = array();

	foreach ($hds as $key=>$str) {
		$sizes[$key] = strlen($str);
	}

	foreach ($calls as $call) {
		foreach ($call as $key=>$str) {
			if (strlen($str) > $sizes[$key]) {
				$sizes[$key] = strlen($str);
			}
		}
	}

	$arr = array();
	$total = 0;

	foreach ($hdr as $key=>$str)
	{
		$arr[] = sprintf('%-' . $sizes[$key] . 's', $str);
		$total += $sizes[$key];
	}

	$res = implode(' | ', $arr) . "\n", sprintf("%'-'-" . ($total + (count($hdr) * 3 - 3)) . 's', '') . "\n";

	foreach ($calls as $call)
	{
		$arr = array();

		foreach ($call as $key=>$str) {
			$arr[] = sprintf('%-' . $sizes[$key] . 's', $str);
		}

		$res .= implode(' | ', $arr) . "\n";
	}

	return $res;
}

##
# = void error(string $str)
# Throw error
##
function error($str)
{
	if (LOG_ERRORS)
	{
		_log('[Error happened]');
		_log($str . "\n");
		_log(i_get_backtrace_text());
		_log('');
		_log(dflush_str());
		_log('');
	}

	if (DEBUG)
	{
		echo "<pre>$str</pre>";
		echo i_get_backtrace_html();
		dflush();
		die;
	}
	else
	{
		die("Error happened");
	}
}

function i_on_php_error($code, $message, $filename='', $linenumber=-1, $context=array())
{
	if (error_reporting() == 0) return true;
	if (intval($code) == 2048) return true;		// E_STRICT

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
function get_microtime()
{
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
function mtime_str()
{
	return microtime_to_str(get_microtime());
}

##
# = void dwrite(string $str, int $type=S_NORMAL)
##
function dwrite($str, $type=S_NORMAL, $msg='')
{
	if (!DEBUG) return;

	global $_debug_log_;

	if (!isset($_debug_log_)) $_debug_log_ = array();
	$_debug_log_[] = array('time'=>mtime_str(), 'type'=>$type, 'str'=>$str, 'msg'=>$msg);
}

##
# = void dwrite_msg(string $str, string $msg, int $type=S_NORMAL)
# Shortcut for dwrite($str, S_NORMAL, $msg)
##
function dwrite_msg($str, $msg, $type=S_NORMAL)
{
	if (!DEBUG) return;
	dwrite($str, $type, $msg);
}

##
# = void dflush()
##
function dflush()
{
	if (!DEBUG) return;
	if (!array_key_exists('_debug_log_', $GLOBALS)) return;

	echo "\n";
	echo '<a style="position:absolute;top:0;left:0;font-family:Tahoma,Arial;font-size:8pt;color:#FFF;background-color:#000;font-weight:bold;text-decoration:none;" href="javascript:document.getElementById(\'__s_debug__\').style.display=(document.getElementById(\'__s_debug__\').style.display==\'\'?\'none\':\'\');void(0);">#</a>';
	echo '<div id="__s_debug__" style="display:none;position:absolute;top:20px;left:10px;z-index:10000;padding:2px 2px 2px 2px;border:1px solid #875;background-color:#FEA;"><code style="color:#000;font-family:monospace;font-size:8pt;">',"\n";

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
			case S_ERROR: $str = '<span style="color:#F00">'.$str.'</span>'; break;
			case S_SUCCESS: $str = '<span style="color:#080">'.$str.'</span>'; break;
			case S_ACCENT: $str = '<span style="color:#F80">'.$str.'</span>'; break;
			case S_NOTICE: $str = '<span style="color:#800">'.$str.'</span>'; break;
		}

		echo '<font color="#080">',$time,':</span> ',$str,"<br />\n";
	}

	echo '</code></div></div>';
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
	return ($val=='true' || $val=='1' || $val=='on' || $val=='yes');
}

##
# = string now()
##
function now()
{
	return date('Y-m-d H:i:s', time());
}

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
	return (InGET($k) ? $_GET[$k] : $def);
}

##
# = mixed _POST(string $k, mixed $def='')
##
function _POST($k, $def='')
{
	return (InPOST($k) ? $_POST[$k] : $def);
}

##
# = mixed _SESSION(string $k, mixed $def='')
##
function _SESSION($k, $def='')
{
	return (InSESSION($k) ? $_SESSION[$k] : $def);
}

##
# = mixed _COOKIE(string $k, mixed $def='')
##
function _COOKIE($k, $def='')
{
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
# = void _log(string $msg, string $path='')
##
function _log($msg, $path='', $supress_errors=false)
{
	if (!strlen($path)) $path = conf('log.path');

	if ($supress_errors)
	{
		$fp = @fopen($path, 'at');

		if ($fp)
		{
			@fwrite($fp, $msg . "\n");
			@fclose($fp);
		}
	}
	else
	{
	 	if (!($fp = @fopen($path, 'at'))) error("Can't open log file");
		if (!@fwrite($fp, $msg . "\n")) error("Can't write to log file");
		fclose($fp);
	}
}

##
# = void __log(string $msg, bool $nl=true)
##
function __log($msg, $nl=true)
{
	$fp = fopen(BASE . '_debuglog_.log', 'at');
	fwrite($fp, $msg);
	if ($nl) fwrite($fp, "\n");
	fclose($fp);
}
