<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Page class
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
	var $page = null;

	##
	# [$error_message] Custom error message
	##
	var $error_message = '';

	##
	# = abstract string get_error_message(string $fld, array &$vars)
	# [$fld] Field name
	# [$vars] Page variables
	# Must return validator error message
	##
	function get_error_message($fld, &$vars)
	{
		error('SValidator.get_error_message must be overrided');
	}

	##
	# = abstract bool is_valid(string $str)
	# [$str] Field value
	##
	function is_valid($str)
	{
		error('SValidator.is_valid must be overrided');
	}

	##
	# = string error_message(string $fld, array &$vars)
	# Returns custom error message (if not empty) or validator error message
	##
	function error_message($fld, &$vars)
	{
		return ($this->error_message ? $this->error_message : $this->get_error_message($fld, $vars));
	}

	##
	# = bool validate(string $fld, array &$vars)
	# Returns **true** if field value is valid, **false** otherwise
	##
	function validate($fld, &$vars)
	{
		$val = (array_key_exists($fld, $vars) ? $vars[$fld] : '');
		return $this->is_valid($val);
	}
}
##
# .end
##
