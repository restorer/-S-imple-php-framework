<?php

if (!count(SDB::get_tables_list()))
{
	$sql = file_get_contents(BASE.'../common/examples.'.conf('db.type').'.sql');

	if (conf('db.type') == 'mysql')
	{
		$spl = explode(';', $sql);

		foreach ($spl as $part)
		{
			$cmd =& new SDBCommand($part);
			$cmd->execute();
		}
	}
	else
	{
		$cmd =& new SDBCommand($sql);
		$cmd->execute();
	}

	SDB::reset_cached_data();
}
