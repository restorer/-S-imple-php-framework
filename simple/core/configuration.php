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
 * Configuration management
 */

##
# .begin
# = class SConfig
##
class SConfig
{
	protected $conf = array();

	##
	# = public void clear()
	##
	public function clear()
	{
		$this->conf = array();
	}

	##
	# = public void set(string $path, mixed $value)
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
	# = public mixed get(string $path, mixed $def)
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
	# = public bool has(string $path)
	##
	public function has($path)
	{
		$res = $this->get($path);
		return ($res !== null);
	}

	##
	# = public SConfig sub(string $path)
	# Creates sub-config
	##
	public function sub($path)
	{
		$sub = $this->get($path);
		if ($sub === null) return null;

		$res = new SConfig();

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
$s_config = new SConfig();

##
# [$s_runconf] runtime config
##
$s_runconf = new SConfig();

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
