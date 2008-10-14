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
	protected $_methods = null;

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
			$this->_id = $id;
		} else {
			throw new Exception("Id can be changed only once");
		}
	}

	##
	# = protected bool _has_method(string $name)
	##
	protected function _has_method($name)
	{
		if ($this->_methods === null)
		{
			$arr = get_class_methods($this);
			$this->_methods = array();

			for ($i = 0; $i < count($arr); $i++) {
				$_methods[$arr[$i]] = true;
			}
		}

		return array_key_exists($name, $this->_methods);
	}

	##
	# = public void __set(string $name, mixed $value)
	# Tries to call magic methods "_set_$name"
	# | protected function _set_id($value)
	##
	public function __set($name, $value)
	{
		if ($this->_has_method("_set_$name")) {
			call_user_func(array($this, "_set_$name"), $value);
		} else {
			throw new Exception("Property $name and method _set_$name() not found");
		}
	}

	##
	# = public mixed __get(string $name)
	# Tries to call magic methods "_get_$name"
	# | protected function _get_id()
	##
	public function __get($name)
	{
		if ($this->_has_method("_get_$name")) {
			return call_user_func(array($this, "_get_$name"));
		} else {
			throw new Exception("Property $name and method _get_$name() not found");
		}
	}
}
##
# .end
##
