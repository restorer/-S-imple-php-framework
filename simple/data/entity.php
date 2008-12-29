<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
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
