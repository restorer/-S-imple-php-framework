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

function i_get_backtrace($funcnames=array())
{
	$backtrace = debug_backtrace();

	if (count($backtrace) &&
		array_key_exists('args', $backtrace[count($backtrace)-1]) &&
		(count($backtrace[count($backtrace)-1]['args']) == 1) &&
 		($backtrace[count($backtrace)-1]['args'][0] instanceof Exception))
	{
		$trace = $backtrace[count($backtrace)-1]['args'][0]->getTrace();
		array_pop($backtrace);
		$backtrace = array_merge($backtrace, $trace);
	}

	$skip_it = array('i_get_backtrace' => true);
	foreach ($funcnames as $funcname) $skip_it[$funcname] = true;

	for ($i = 0; $i < (3 + count($funcnames)) && count($backtrace); $i++) {
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

		$res[] = array('ind' => count($backtrace) - $ind, 'loc' => $location, 'line' => $line, 'func' => $function);
	}

	return $res;
}

function i_get_backtrace_text($funcnames=array())
{
	$calls = i_get_backtrace(array_merge(array('i_get_backtrace_text'), $funcnames));

	$hdr = array('ind' => '#', 'loc' => 'Location', 'line' => 'Line', 'func' => 'Function');
	$sizes = array();

	foreach ($hdr as $key=>$str) {
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
	$sep = array();
	$total = 0;

	foreach ($hdr as $key=>$str)
	{
		$arr[] = sprintf('%-' . $sizes[$key] . 's', $str);
		$sep[] = sprintf("%'-'-" . $sizes[$key] . 's', '');
		$total += $sizes[$key];
	}

	$res = rtrim(implode(' | ', $arr)) . "\n" . implode('-+-', $sep) . "\n";

	foreach ($calls as $call)
	{
		$arr = array();

		foreach ($call as $key=>$str) {
			$arr[] = sprintf('%-' . $sizes[$key] . 's', $str);
		}

		$res .= rtrim(implode(' | ', $arr)) . "\n";
	}

	return $res;
}

function get_debuglog_html($debuglog_str)
{
	$debuglog_str = htmlspecialchars($debuglog_str);
	$lines = explode("\n", $debuglog_str);

	foreach ($lines as &$line)
	{
		$line = preg_replace("/\*\*(.+?)\*\*/", '<span style="font-weight:bold">$1</span>', $line);
		$line = preg_replace("/!!(.+?)!!/", '<span style="color:red;font-weight:bold;">$1</span>', $line);

		if (preg_match("/^[^\.]+\.[^:]+:[ ]\((.)\)/", $line, $mt))
		{
			switch ($mt[1])
			{
				case 'E':
					$line = '<span style="color:#F00">' . $line . '</span>';
					break;

				case ' ':
					$line = '<span style="color:#080">' . $line . '</span>';
					break;

				case '*':
					$line = '<span style="color:#A40">' . $line . '</span>';
					break;

				case 'I':
					$line = '<span style="color:#00F">' . $line . '</span>';
					break;
			}
		}
	}

	return implode("\n", $lines);
}

##
# = void error(string $str, bool $rm_from_backtrace=false)
# Throw error
##
function error($message, $rm_from_backtrace=false)
{
	if (LOG_ERRORS || DEBUG)
	{
		$backtrace_str = i_get_backtrace_text($rm_from_backtrace ? array('error') : array());
		$debuglog_str = dflush_str();

		if (LOG_ERRORS) {
			_log("[[ Error happened ]]\n\n$message\n\n$backtrace_str\n\n$debuglog_str\n\n", '', true);
		}

		if (DEBUG)
		{
			$message = htmlspecialchars($message);
			$backtrace_str = htmlspecialchars($backtrace_str);
			$debuglog_str = get_debuglog_html($debuglog_str);

			echo "<pre>$message\n\n$backtrace_str\n\n$debuglog_str</pre>";
		}
	}

	if (!DEBUG) {
		echo "Server is out to lunch. Please wait about 5 minutes and try to reload page. If it doesn't help, please contact administrator.";
	}

	die;
}

function echo_debug()
{
	echo '<pre style="text-align:left;border-top:1px solid #CCC;padding:5px;" class="s-debug">';
	echo get_debuglog_html(dflush_str());
	echo '</pre>';
}

function i_on_php_error($code, $message, $filename='', $linenumber=-1, $context=array())
{
	if (error_reporting() == 0) return true;
	if (intval($code) == 2048) return true;		// E_STRICT

	// TODO: check for E_NOTICE

	error("Error $code ($message) occured in $filename at $linenumber", true);
	return true;
}

set_error_handler('i_on_php_error');

function i_on_uncaught_exception($ex)
{
	error('Uncaught exception (' . $ex->getFile() . ':' . $ex->getLine() . '): ' . $ex->getMessage(), true);
}

set_exception_handler('i_on_uncaught_exception');

##
# = string dump_str(string $var, int $indent=0)
##
function dump_str($var, $indent=0)
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
# = void dwrite(string $str, int $type=S_NORMAL)
##
function dwrite($str, $type=S_NORMAL, $msg='')
{
	if (!DEBUG) return;

	global $_debug_log_;

	if (!isset($_debug_log_)) $_debug_log_ = array();
	$_debug_log_[] = array('time'=>microtime_to_str(get_microtime()), 'type'=>$type, 'str'=>$str, 'msg'=>$msg);
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
# = string dflush_str()
##
function dflush_str()
{
	if (!DEBUG) return '';

	global $_debug_log_;

	if (!isset($_debug_log_)) return '';

	$res = '';

	foreach ($_debug_log_ as $arr)
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
			case S_ERROR: 	$str = "(E) $str"; break;
			case S_SUCCESS:	$str = "( ) $str"; break;
			case S_ACCENT:	$str = "(*) $str"; break;
			case S_NOTICE:	$str = "(I) $str"; break;
			default:		$str = "    $str"; break;
		}

		$res .= "$time: $str\n";
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
# = string capitalize(string $str)
##
function capitalize($str)
{
	return mb_strtoupper(mb_substr($str, 0, 1)) . mb_strtolower(mb_substr($str, 1));
}

##
# = object dynamic_cast(object $object, string $class_name)
# Cast an object to another class, keeping the properties, but changing the methods
# **WARN:** this function breaks OOP model, but sometimes it useful
##
function dynamic_cast($object, $class_name)
{
	// serialize/unserialize idea taken from http://blog.adaniels.nl/articles/a-dark-corner-of-php-class-casting/
	return unserialize(preg_replace('/^O:\d+:"[^"]++"/', 'O:' . strlen($class_name) . ':"' . $class_name . '"', serialize($object)));
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
