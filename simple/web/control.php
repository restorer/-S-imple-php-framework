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
 * Base control class
 */

##
# .begin
# = abstract class SControl
##
abstract class SControl
{
	##
	# [$page] This control belongs to $page
	##
	public $page = null;

	##
	# [$vars] Template variables
	##
	public $vars = array();

	##
	# [$template_name] Full path to template
	##
	public $template_name = '';

	##
	# = public void set_template(string $path_to_control)
	# Usage: **$this->set_template(__FILE__)**
	##
	public function set_template($path_to_control)
	{
		$this->template_name = dirname($path_to_control) . '/' . basename($path_to_control, '.php') . '.tpl';
	}

	##
	# = protected mixed get_var(string $name, mixed $def='')
	##
	protected function get_var($name, $def='')
	{
		return (array_key_exists($name, $this->vars) ? $this->vars[$name] : $def);
	}

	##
	# = protected string attrs_str(array $attrs)
	# Encode array of attributes to html string
	##
	protected function attrs_str($attrs)
	{
		$res = '';
		foreach ($attrs as $k=>$v) $res .= ' '.$k.'="'.htmlspecialchars($v).'"';
		return $res;
	}

	##
	# = protected mixed take_attr(array &$attrs, string $name, mixed $def='')
	# Get attribute from **$attrs** array (attribute will removed from array)
	##
	protected function take_attr(&$attrs, $name, $def='')
	{
		if (array_key_exists($name, $attrs))
		{
			$res = $attrs[$name];
			unset($attrs[$name]);
			return $res;
		}

		return $def;
	}

	##
	# = protected string render_template()
	##
	protected function render_template()
	{
		if (!strlen($this->template_name)) throw new Exception('Please set $template_name variable');

		$tpl = new STemplate();
		$tpl->vars =& $this->vars;
		$tpl->controls =& $this->page->controls;

		return $tpl->process($this->template_name);
	}

	##
	# = public abstract string render(array $attrs)
	##
	public abstract function render($attrs);
}
##
# .end
##
