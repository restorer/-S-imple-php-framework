<?php

define('FILEITEM_FOLDER', 0);
define('FILEITEM_FILE', 1);

class FileItem extends SRecord
{
	public $parent_id = 0;
	public $name = '';
	public $type = 0;

	function __construct()
	{
		parent::__construct();

		$this->belongs_to('parent', 'FileItem', ':parent_id=id');
		$this->has_many('childs', 'FileItem', ':id=parent_id', array('-type', 'name'));
		$this->has_many('blocks', 'BlockItem', array(':id=file_id', array('parent_id=', 0)), 'id');
	}
}
