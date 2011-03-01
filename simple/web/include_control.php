<?php

class SIncludeControl
{
	public function render($tpl_name)
	{
		$tpl = new STemplate();
		$tpl->vars =& $this->page->vars;
		$tpl->controls =& $this->page->controls;

		return $tpl->process($tpl_name);
	}
}
