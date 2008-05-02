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
# = class JsonBase
##
class JsonBase
{
	var $value;

	##
	# = mixed get()
	##
	function get()
	{
		return $this->value;
	}

	##
	# = abstract void set(mixed $value)
	##
	function set($value) { error('JsonBase.set must be overrided'); }

	##
	# = abstract string serialize()
	##
	function serialize() { error('JsonBase.serialize must be overrided'); }
}
##
# .end
##

##
# .begin
# = class JsonBool extends JsonBase
##
class JsonBool extends JsonBase
{
	function JsonBool($value = false)
	{
		$this->__construct($value);
	}

	##
	# = void __construct(bool $value = false)
	##
	function __construct($value = false)
	{
		$this->set($value);
	}

	##
	# = void set(bool $value)
	##
	function set($value)
	{
		$this->value = cast_bool($value);
	}

	##
	# = string serialize()
	##
	function serialize()
	{
		return ($this->value ? 'true' : 'false');
	}
}
##
# .end
##

##
# .begin
# = class JsonString extends JsonBase
##
class JsonString extends JsonBase
{
	function JsonString($value = '')
	{
		$this->__construct($value);
	}

	##
	# = void __construct(string $value = '')
	##
	function __construct($value = '')
	{
		$this->set($value);
	}

	##
	# = void set(string $value)
	##
	function set($value)
	{
		$this->value = strval($value);
	}

	##
	# = string serialize()
	##
	function serialize()
	{
		$find = array('\\',   '"',  '/',  "\b", "\f", "\n", "\r", "\t", "\u");
		$repl = array('\\\\', '\"', '\/', '\b', '\f', '\n', '\r', '\t', '\u');
		return '"' . str_replace($find, $repl, $this->value) . '"';
	}
}
##
# .end
##

##
# .begin
# = class JsonNumber extends JsonBase
##
class JsonNumber extends JsonBase
{
	function JsonNumber($value = 0)
	{
		$this->__construct($value);
	}

	##
	# = void __construct(float $value = 0)
	##
	function __construct($value = 0)
	{
		$this->set($value);
	}

	##
	# = void set(float $value)
	##
	function set($value)
	{
		$this->value = floatval($value);
	}

	##
	# = string serialize()
	##
	function serialize()
	{
		return strval($this->value);
	}
}
##
# .end
##

##
# .begin
# = class JsonArray extends JsonBase
##
class JsonArray extends JsonBase
{
	function JsonArray($value = array())
	{
		$this->__construct($value);
	}

	##
	# = void __construct(array $value = array())
	##
	function __construct($value = array())
	{
		$this->set($value);
	}

	##
	# = void set(array $value)
	##
	function set($value)
	{
		if (is_array($value)) $this->value = $value;
		else $this->value = array();
	}

	##
	# = void add(JsonBase $item)
	##
	function add($item)
	{
		$this->value[] = $item;
	}

	##
	# = string serialize()
	##
	function serialize()
	{
		$res = '';

		foreach ($this->value as $val)
		{
			if ($res != '') $res .= ',';
			$res .= $val->serialize();
		}

		return '[' . $res . ']';
	}
}
##
# .end
##

##
# .begin
# = class JsonObject extends JsonBase
##
class JsonObject extends JsonBase
{
	function JsonObject($value = array())
	{
		$this->__construct($value);
	}

	##
	# = void __construct(array $value = array())
	##
	function __construct($value = array())
	{
		$this->set($value);
	}

	##
	# = void set(array $value)
	##
	function set($value)
	{
		if (is_array($value)) $this->value = $value;
		else $this->value = array();
	}

	##
	# = void add(string $key, JsonBase $item)
	##
	function add($key, $item)
	{
		$this->value[$key] = $item;
	}

	##
	# = string serialize()
	##
	function serialize()
	{
		$res = '';

		foreach ($this->value as $k=>$v)
		{
			if ($res != '') $res .= ',';
			$res .= $k .':' . $v->serialize();
		}

		return '{' . $res . '}';
	}
}
##
# .end
##
