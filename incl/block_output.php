<?php

class BlockOutput extends SControl
{
	function BlockOutput()
	{
		$this->__construct();
	}

	function __construct()
	{
		$this->set_template(__FILE__);
	}

	function render($attrs)
	{
		$block = $this->take_attr($attrs, 'block', null);
		if ($block === null) return '';

		$this->vars['block'] = $block;
		return $this->render_template();
	}
}

?>