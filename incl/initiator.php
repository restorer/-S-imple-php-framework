<?php

require_once('s/s.php');
require_once('incl/file_item.php');
require_once('incl/documenter.php');

class Initiator
{
	/**
	 * @static
	 */
	function check_init()
	{
		if (!count(SDB::get_tables_list()))
		{
			$sql = file_get_contents(BASE.'conf/simple.'.conf('db.type').'.sql');
			$cmd =& new SDBCommand($sql);
	        $cmd->execute();

			SDB::reset_cached_data();

			$fl =& new FileItem();
			$fl->parent_id = 0;
			$fl->name = 's';
			$fl->type = FILEITEM_FOLDER;
			$fl->save();

	        Initiator::fill_db(BASE.'s', $fl->get_id());
		}
	}

	function fill_db($path, $parent_id)
	{
		$dh = opendir($path) or error("Can't open $path");

		while (($file = readdir($dh)) !== false)
		{
			if ($file{0}=='.') continue;

			$rpath = $path . '/' . $file;

			$fl =& new FileItem();
			$fl->parent_id = $parent_id;
			$fl->name = $file;

			if (is_dir($rpath))
			{
				$fl->type = FILEITEM_FOLDER;
				$fl->save();
				Initiator::fill_db($rpath, $fl->get_id());
			}
			else
			{
				if ($file == 'all.php') continue;
				if (substr($file, -4) != '.php') continue;

				$fl->type = FILEITEM_FILE;
				$fl->save();

				$doc =& new Documenter();
				$doc->parse($rpath);
				$doc->save($fl);
			}
		}
	}
}

?>