<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Dates operations
 */

##
# .begin
# = class SDates
##
class SDates
{
	##
	# = static string datetime_to_string(datetime_string $value)
	##
	function datetime_to_string($value)
	{
		if (!preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)\s(\d\d):(\d\d):(\d\d)/', $value, $m)) return '';
		return date(conf('format.datetime'), mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]));
	}

	##
	# = static array formatted_datetime(datetime_string $value)
	# {$result[0]} date string
	# {$result[1]} time string
	##
	function formatted_datetime($value)
	{
		$str = SDates::datetime_to_string($value);
		if ($str == '') return array('', '');
		return explode(' ', $str);
	}

	##
	# = static string date_to_string(date_string $value)
	##
	function date_to_string($value)
	{
		if (!preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/', $value, $m)) return '';
		return date(conf('format.date'), mktime(0, 0, 0, $m[2], $m[3], $m[1]));
	}

	##
	# = static int date_to_unix_timestamp(date_string $value)
	##
	function date_to_unix_timestamp($value)
	{
		if (!preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/', $value, $m)) return 0;
		return mktime(0, 0, 0, $m[2], $m[3], $m[1]);
	}

	##
	# = static int datetime_to_unix_timestamp(datetime_string $value)
	##
	function datetime_to_unix_timestamp($value)
	{
		if (!preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)\s(\d\d):(\d\d):(\d\d)/', $value, $m)) return 0;
		return mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]);
	}

	##
	# = static string unix_timestamp_to_datetime(int $value)
	##
	function unix_timestamp_to_datetime($value)
	{
		return date(conf("format.datetime"), $value);
	}

	##
	# = static date_string string_to_date(string $value)
	##
	function string_to_date($value)
	{
		if (!preg_match(conf("regexp.date"), $value, $m)) return '0000-01-01';
		return $m[3].'-'.$m[1].'-'.$m[2];
	}

	##
	# = static datetime_string string_to_datetime(string $value)
	##
	function string_to_datetime($value)
	{
		if (!preg_match(conf("regexp.datetime"), $value, $m)) return '0000-01-01 00:00:00';
		return $m[3].'-'.$m[1].'-'.$m[2].' '.$m[4].':'.$m[5].':'.(isset($m[7]) ? $m[7] : '00');
	}
}
##
# .end
##
