<?php

class BlockItem extends SRecord
{
	public $parent_id = 0;
	public $file_id = 0;
	public $title = '';
	public $text = '';
	public $example = '';

	function __construct()
	{
		parent::__construct();

		$this->belongs_to('parent', 'BlockItem', ':parent_id=id');
		$this->has_many('childs', 'BlockItem', ':id=parent_id', 'id');
		$this->belongs_to('file', 'FileItem', ':file_id=id');
		$this->has_many('notes', 'NoteItem', ':id=block_id', 'id');
	}
}
