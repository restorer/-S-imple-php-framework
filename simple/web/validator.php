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
