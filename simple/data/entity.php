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
 * Base entity class
 */

##
# .begin
# = class SEntity
##
class SEntity
{
	private $_id = null;

	##
	# = protected mixed _get_id()
	# Getter for $id
	##
	protected function _get_id()
	{
		return $this->_id;
	}

	##
	# = protected void _set_id(mixed $value)
	# Setter for $id
	##
	protected function _set_id($value)
	{
		if ($this->_id === null) {
			$this->_id = $value;
		} else {
			throw new Exception("Id can be changed only once");
		}
	}

	##
	# = public void __set(string $name, mixed $value)
	# Tries to call magic methods "_set_$name" or _set_($name, $value)
	# | protected function _set_id($value)
	##
	public function __set($name, $value)
	{
		if (method_exists($this, "_set_$name")) {
			call_user_func(array($this, "_set_$name"), $value);
		} elseif (method_exists($this, '_set_')) {
			call_user_func(array($this, '_set_'), $name, $value);
		} else {
			throw new Exception("Property $name, methods _set_$name() and _set_ not found");
		}
	}

	##
	# = public mixed __get(string $name)
	# Tries to call magic methods "_get_$name" or _get_($name)
	# | protected function _get_id()
	##
	public function __get($name)
	{
		if (method_exists($this, "_get_$name")) {
			return call_user_func(array($this, "_get_$name"));
		} elseif (method_exists($this, '_get_')) {
			return call_user_func(array($this, '_get_'), $name);
		} else {
			throw new Exception("Property $name, methods _get_$name() and _get_ not found");
		}
	}
}
##
# .end
##
