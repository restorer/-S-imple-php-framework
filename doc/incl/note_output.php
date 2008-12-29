<?php

class NoteOutput extends SControl
{
	function __construct()
	{
		$this->set_template(__FILE__);
	}

	public function render($attrs)
	{
		$note = $this->take_attr($attrs, 'note', null);
		if ($note === null) return '';

		$this->vars['note'] = $note;
		return $this->render_template();
	}
}
