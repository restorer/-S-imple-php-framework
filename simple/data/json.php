<?php

/*
 * MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * Copyright (c) 2007, Slava Tretyak (aka restorer)
 * Zame Software Development (http://zame-dev.org)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * [S]imple framework
 * Json serializer
 */

##
# .begin
# = interface SJsonSerializable
##
interface SJsonSerializable
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
# = static class SJson
##
class SJson
{
	// replace '/' -> "\/" removed

	public static $find = array('\\',   "'",  "\b", "\f", "\n", "\r", "\t", "\u");	// "
	public static $repl = array('\\\\', "\'", '\b', '\f', '\n', '\r', '\t', '\u');	// '

	##
	# = public static mixed deserialize(string $json_str)
	# TODO: now it is just wrapper to buggy json_decode (in 5.2.6 version some things decoded really weird)
	##
	public static function deserialize($json_str)
	{
		return json_decode($json_str, true);
	}

	##
	# = public static string serialize(mixed $obj, bool $use_internal=false)
	# [$obj] Object to serialize
	# [$use_internal] Always use internal serializer, even if json_encode function availible
	# Note: internal encoder use single-quoted strings instead of duuble-quoted in strings (sometimes it is better than valid json)
	##
	public static function serialize($obj, $use_internal=false)
	{
		if (!$use_internal && function_exists('json_encode')) {
			return json_encode($obj);
		}

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
			if ($obj instanceof SJsonSerializable) {
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
	return str_replace("</script>", "</'+'script>", str_replace(SJson::$find, SJson::$repl, $str));
}
