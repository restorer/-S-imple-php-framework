<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Configuration management
 */

##
# .begin
# = class SConfig
##
class SConfig
{
	protected var $conf = array();

	##
	# = void clear()
	##
	public function clear()
	{
		$this->conf = array();
	}

	##
	# = void set(string $path, mixed $value)
	##
	public function set($path, $value)
	{
		$arr = explode('.', $path);
		$node =& $this->conf;

		for ($i = 0; $i < count($arr)-1; $i++)
		{
			if (!array_key_exists($arr[$i], $node)) $node[$arr[$i]] = array();

			$node =& $node[$arr[$i]];
			if (!is_array($node)) return false;
		}

		$node[$arr[count($arr)-1]] = $value;
		return true;
	}

	##
	# = mixed get(string $path, mixed $def)
	# [$def] default value which is used when config doesn't have values it **$path**
	##
	public function get($path, $def=null)
	{
		$arr = explode('.', $path);
		$node =& $this->conf;

		foreach ($arr as $val)
		{
			if (!is_array($node)) return ($def!==null ? $def : null);
			if (!array_key_exists($val, $node)) return ($def!==null ? $def : null);
			$node =& $node[$val];
		}

		return $node;
	}

	##
	# = bool has(string $path)
	##
	public function has($path)
	{
		$res = $this->get($path);
		return ($res !== null);
	}

	##
	# = SConfig sub(string $path)
	# Creates sub-config
	##
	public function sub($path)
	{
		$sub = $this->get($path);
		if ($sub === null) return null;

		$res =& new SConfig();

		if (is_array($sub)) {
			$res->conf = $sub;
		} else {
			$arr = explode('.', $path);
			$res->conf[$arr[count($arr)-1]] = $sub;
		}

		return $res;
	}
}
##
# .end
##

##
# [$s_config] global config
##
$s_config =& new SConfig();

##
# [$s_runconf] runtime config
##
$s_runconf =& new SConfig();

##
# = void conf_set(string $path, mixed $value)
# Shortcut to **$s_config->set()**
##
function conf_set($path, $value)
{
	global $s_config;
	$s_config->set($path, $value);
}

##
# = mixed conf_get(string $path, mixed $def)
# Shortcut to **$s_config->get()**
##
function conf_get($path, $def=null)
{
	global $s_config;
	return $s_config->get($path, $def);
}

##
# = mixed conf(string $path, mixed $def)
# Shortcut to **conf_get()**
##
function conf($path, $def=null)
{
	global $s_config;
	return $s_config->get($path, $def);
}

##
# = mixed conf_has(string $path)
# Shortcut to **$s_config->has()**
##
function conf_has($path)
{
	global $s_config;
	return $s_config->has($path);
}

##
# = mixed conf_sub(string $path)
# Shortcut to **$s_config->sub()**
##
function conf_sub($path)
{
	global $s_config;
	return $s_config->sub($path);
}
