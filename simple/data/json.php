<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Json serializer
 */

##
# .begin
# = interface JsonSerializable
##
interface JsonSerializable
{
	##
	# = string json_serialize()
	##
	function json_serialize();
}
##
# .end
##

##
# .begin
# = static class Json
##
class Json
{
	public static $find = array('\\',   "'",  '/',  "\b", "\f", "\n", "\r", "\t", "\u");
	public static $repl = array('\\\\', "\'", '\/', '\b', '\f', '\n', '\r', '\t', '\u');

	##
	# = public static string serialize(mixed $obj)
	# Note: it use single quote in strings, not doudle quotes as usual.
	##
	public static function serialize($obj)
	{
		if (is_string($obj))
		{
			return "'" . str_replace(self::$find, self::$repl, $obj) . "'";
		}
		elseif (is_bool($obj))
		{
			return ($obj ? 'true' : 'false');
		}
		elseif (is_int($obj) || is_float($obj))
		{
			return strval($obj);
		}
		elseif (is_array($obj))
		{
			$res = array();
			$is_arr = true;
			$cnt = count($obj);

			foreach ($obj as $k=>$v)
			{
				$is_arr = $is_arr && (is_int($k) && $k>=0 && $k<$cnt);
				$res[$k] = self::serialize($v);
			}

			if ($is_arr)
			{
				ksort($res);
				return '[' . join(',', array_values($res)) . ']';
			}
			else
			{
				foreach ($res as $k=>&$v) {
					$v = "'" . str_replace(self::$find, self::$repl, $k) . "':" . $v;
				}

				return '{' . join(',', array_values($res)) . '}';
			}
		}
		elseif (is_object($obj))
		{
			if ($obj instanceof JsonSerializable) {
				return $obj->json_serialize();
			} else {
				throw new Exception("Object doesn't implement JsonSerializable interface");
			}
		}
		else
		{
			throw new Exception('Unknown variable type');
		}
	}
}
##
# .end
##

function js_escape($str)
{
	return str_replace("</script>", "</'+'script>", str_replace(Json::$find, Json::$repl, $str));
}
