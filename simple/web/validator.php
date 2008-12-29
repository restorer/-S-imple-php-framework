<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Base validator class
 */

##
# .begin
# = class SValidator
##
class SValidator
{
	##
	# [$page] This validator belongs to $page
	##
	public $page = null;

	##
	# [$error_message] Custom error message
	##
	public $error_message = '';

	##
	# = protected abstract string do_get_error_message(string $fld, array &$vars)
	# [$fld] Field name
	# [$vars] Page variables
	# Must return validator error message
	##
	protected abstract function do_get_error_message($fld, &$vars);

	##
	# = protected abstract bool is_valid(string $str)
	# [$str] Field value
	##
	protected abstract function is_valid($str);

	##
	# = public string get_error_message(string $fld, array &$vars)
	# Returns custom error message (if not empty) or validator error message
	##
	function get_error_message($fld, &$vars)
	{
		return ($this->error_message ? $this->error_message : $this->do_get_error_message($fld, $vars));
	}

	##
	# = bool validate(string $fld, array &$vars)
	# Returns **true** if field value is valid, **false** otherwise
	##
	public function validate($fld, &$vars)
	{
		$val = (array_key_exists($fld, $vars) ? $vars[$fld] : '');
		return $this->is_valid($val);
	}
}
##
# .end
##
