<?php

class NoteItem extends SRecord
{
	public $block_id = 0;
	public $name = '';
	public $description = '';

	function __construct()
	{
		parent::__construct(true);
		$this->belongs_to('block', 'BlockItem', ':block_id=id');
	}
}
