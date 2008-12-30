<?php

require_once('s/s.php');
require_once('../common/init_db.php');

switch (_GET('action'))
{
	case 'insert':
		$cmd = new SDBCommand("INSERT INTO some_items (name, date_created) VALUES (@name, @date_created)");
		$cmd->set('name', 'Name #'.md5(now()), SDB::String);
		$cmd->set('date_created', now(), SDB::DateTime);
		$cmd->execute();

		echo 'ok';
		break;

	case 'list':
		$cmd = new SDBCommand("SELECT * FROM some_items");
		$arr = $cmd->get_all();

		echo '<pre>';
		echo "<b><u>id\tname\t\t\t\t\tdate_created\t   </u></b>\n";

		foreach ($arr as $row) {
			echo $row['id'] . "\t" . $row['name'] . "\t" . $row['date_created'] . "\n";
		}

		echo '</pre>';
		break;

	case 'remove':
		$cmd = new SDBCommand("SELECT id FROM some_items");
		$ids = $cmd->get_all();

		$id = $ids[rand() % count($ids)]['id'];

		$cmd = new SDBCommand("DELETE FROM some_items WHERE id=@id");
		$cmd->set('id', $id, SDB::Int);
		$cmd->execute();

		echo 'ok';
		break;

	case 'clear':
		$cmd = new SDBCommand("DELETE FROM some_items");
		$cmd->execute();

		echo 'ok';
		break;
}

echo_debug();
