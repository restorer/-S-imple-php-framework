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
# = abstract class JsonBase
##
abstract class JsonBase
{
	protected $value;

	##
	# = public mixed get()
	##
	public function get()
	{
		return $this->value;
	}

	##
	# = public abstract void set(mixed $value)
	##
	public abstract function set($value);

	##
	# = public abstract string serialize()
	##
	public abstract function serialize();
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
	##
	# = public void __construct(bool $value = false)
	##
	public function __construct($value = false)
	{
		$this->set($value);
	}

	##
	# = public void set(bool $value)
	##
	public function set($value)
	{
		$this->value = cast_bool($value);
	}

	##
	# = public string serialize()
	##
	public function serialize()
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
	##
	# = public void __construct(string $value = '')
	##
	public function __construct($value = '')
	{
		$this->set($value);
	}

	##
	# = public void set(string $value)
	##
	public function set($value)
	{
		$this->value = strval($value);
	}

	##
	# = public string serialize()
	##
	public function serialize()
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
	##
	# = public void __construct(float $value = 0)
	##
	public function __construct($value = 0)
	{
		$this->set($value);
	}

	##
	# = public void set(float $value)
	##
	public function set($value)
	{
		$this->value = floatval($value);
	}

	##
	# = public string serialize()
	##
	public function serialize()
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
	##
	# = public void __construct(array $value = array())
	##
	public function __construct($value = array())
	{
		$this->set($value);
	}

	##
	# = public void set(array $value)
	##
	public function set($value)
	{
		if (is_array($value)) $this->value = $value;
		else $this->value = array();
	}

	##
	# = public void add(JsonBase $item)
	##
	public function add($item)
	{
		$this->value[] = $item;
	}

	##
	# = public string serialize()
	##
	public function serialize()
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
	##
	# = public void __construct(array $value = array())
	##
	public function __construct($value = array())
	{
		$this->set($value);
	}

	##
	# = public void set(array $value)
	##
	public function set($value)
	{
		if (is_array($value)) $this->value = $value;
		else $this->value = array();
	}

	##
	# = public void add(string $key, JsonBase $item)
	##
	public function add($key, $item)
	{
		$this->value[$key] = $item;
	}

	##
	# = public string serialize()
	##
	public function serialize()
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
