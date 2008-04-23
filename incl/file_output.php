<?php

class FileOutput extends SControl
{
	function FileOutput()
	{
		$this->__construct();
	}

	function __construct()
	{
		$this->set_template(__FILE__);
	}

	function render($attrs)
	{
		$file = $this->take_attr($attrs, 'file', null);
		if ($file === null) return '';

		$this->vars['file'] = $file;
		return $this->render_template();
	}
}

?>