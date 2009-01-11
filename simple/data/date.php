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
# = class SDate
##
class SDate
{
	public static $ru_months_short = array('дек.', 'янв.', 'фев.', 'мар.', 'апр.', 'мая', 'июня', 'июля', 'авг.', 'сен.', 'окт.', 'ноя.', 'дек.');
	public static $ru_months_full = array('декабря', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
	public static $en_months_short = array('Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
	public static $en_months_full = array('December', 'January', 'Febrary', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

	public static function parse($str_date)
	{
		if (preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)\s(\d\d):(\d\d):(\d\d)/', $str_date, $mt)) {
			return mktime($mt[4], $mt[5], $mt[6], $mt[2], $mt[3], $mt[1]);
		} else if (preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/', $str_date, $mt)) {
			return mktime(0, 0, 0, $mt[2], $mt[3], $mt[1]);
		} else {
			throw new Exception("\"$str_date\" is not valid date");
		}
	}

	public static function format($value, $format)
	{
		$tm = (is_numeric($value) ? $value : self::parse($value));

		$year = intval(date('Y', $tm));
		$mon = intval(date('m', $tm));
		$day = intval(date('d', $tm));

		switch ($format)
		{
			case 'ru-short':
				return ($day . ' ' . self::$ru_months_short[$mon] . ' ' . $year . ' г.');
				break;

			case 'ru-full':
				return ($day . ' ' . self::$ru_months_full[$mon] . ' ' . $year . ' г.');
				break;

			case 'en-short':
				return (self::$en_months_short[$mon] . ' ' . $day . ', ' . $year);
				break;

			case 'en-full':
				return (self::$en_months_full[$mon] . ' ' . $day . ', ' . $year);
				break;

			default:
				return date('Y-m-d H:i:s', $tm);
		}
	}

	##
	# Old functions below.
	# **TODO:** Refactor code.
	##

	##
	# = static string datetime_to_string(datetime_string $value)
	##
	public static function datetime_to_string($value)
	{
		if (!preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)\s(\d\d):(\d\d):(\d\d)/', $value, $m)) return '';
		return date(conf('format.datetime'), mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]));
	}

	##
	# = static array formatted_datetime(datetime_string $value)
	# {$result[0]} date string
	# {$result[1]} time string
	##
	public static function formatted_datetime($value)
	{
		$str = SDate::datetime_to_string($value);
		if ($str == '') return array('', '');
		return explode(' ', $str);
	}

	##
	# = static string date_to_string(date_string $value)
	##
	public static function date_to_string($value)
	{
		if (!preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/', $value, $m)) return '';
		return date(conf('format.date'), mktime(0, 0, 0, $m[2], $m[3], $m[1]));
	}

	##
	# = static int date_to_unix_timestamp(date_string $value)
	##
	public static function date_to_unix_timestamp($value)
	{
		if (!preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/', $value, $m)) return 0;
		return mktime(0, 0, 0, $m[2], $m[3], $m[1]);
	}

	##
	# = static int datetime_to_unix_timestamp(datetime_string $value)
	##
	public static function datetime_to_unix_timestamp($value)
	{
		if (!preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)\s(\d\d):(\d\d):(\d\d)/', $value, $m)) return 0;
		return mktime($m[4], $m[5], $m[6], $m[2], $m[3], $m[1]);
	}

	##
	# = static string unix_timestamp_to_datetime(int $value)
	##
	public static function unix_timestamp_to_datetime($value)
	{
		return date(conf("format.datetime"), $value);
	}

	##
	# = static date_string string_to_date(string $value)
	##
	public static function string_to_date($value)
	{
		if (!preg_match(conf("regexp.date"), $value, $m)) return '0000-01-01';
		return $m[3].'-'.$m[1].'-'.$m[2];
	}

	##
	# = static datetime_string string_to_datetime(string $value)
	##
	public static function string_to_datetime($value)
	{
		if (!preg_match(conf("regexp.datetime"), $value, $m)) return '0000-01-01 00:00:00';
		return $m[3].'-'.$m[1].'-'.$m[2].' '.$m[4].':'.$m[5].':'.(isset($m[7]) ? $m[7] : '00');
	}
}
##
# .end
##
