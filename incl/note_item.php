<?php

class NoteItem extends SDBEntity
{
	var $block_id = 0;
	var $name = '';
	var $description = '';

	function __construct()
	{
		parent::__construct(true);
		$this->belongs_to('block', 'BlockItem', array(':block_id', '=', 'id'));
	}

	function &block() { return $this->get_rel('block'); }
}

?>