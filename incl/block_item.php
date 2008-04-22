<?php

class BlockItem extends SDBEntity
{
	var $parent_id = 0;
	var $file_id = 0;
	var $title = '';
	var $text = '';
	var $example = '';

	function __construct()
	{
		parent::__construct(true);

		$this->belongs_to('parent', 'BlockItem', array(':parent_id', '=', 'id'));
		$this->has_many('childs', 'BlockItem', array(':id', '=', 'parent_id'), 'id');
		$this->belongs_to('file', 'FileItem', array(':file_id', '=', 'id'));
		$this->has_many('notes', 'NoteItem', array(':id', '=', 'block_id'), 'id');
	}

	function &parent() { return $this->get_rel('parent'); }
	function &childs() { return $this->get_rel('childs'); }
	function &file() { return $this->get_rel('file'); }
	function &notes() { return $this->get_rel('notes'); }
}

?>