<?php

require_once('s/s.php');
require_once('incl/file_item.php');
require_once('incl/initiator.php');
require_once('incl/file_output.php');
require_once('incl/block_output.php');
require_once('incl/note_output.php');

class IndexPage extends SPage
{
	function IndexPage()
	{
		$this->__construct();
	}

	function __construct()
	{
		parent::__construct();
		$this->add_event(PAGE_INIT, 'on_init');
	}

	function on_init()
	{
		Initiator::check_init();

/*
$dc =& new Documenter();
$dc->parse(S_BASE.'data/json.php');
dwrite_msg('', dump_str($dc->blocks));
*/

		$this->add_control('FileOutput', new FileOutput());
		$this->add_control('BlockOutput', new BlockOutput());
		$this->add_control('NoteOutput', new NoteOutput());

		$root =& new FileItem();
		$root->find(array('parent_id', '=', 0));

		$this->vars['files'] = $root->childs();
	}
}

$page =& new IndexPage();
$page->process();
