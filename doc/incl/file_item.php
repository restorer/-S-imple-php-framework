<?php

define('FILEITEM_FOLDER', 0);
define('FILEITEM_FILE', 1);

class FileItem extends SDBEntity
{
	var $parent_id = 0;
	var $name = '';
	var $type = 0;

	function FileItem()
	{
		$this->__construct();
	}

	function __construct()
	{
		parent::__construct(true);

		$this->belongs_to('parent', 'FileItem', array(':parent_id', '=', 'id'));
		$this->has_many('childs', 'FileItem', array(':id', '=', 'parent_id'), array(array('type', false), 'name'));
		$this->has_many('blocks', 'BlockItem', array(array(':id', '=', 'file_id'), array('parent_id', '=', 0)), 'id');
	}

	function &parent() { return $this->get_rel('parent'); }
	function &childs() { return $this->get_rel('childs'); }
	function &blocks() { return $this->get_rel('blocks'); }
}
