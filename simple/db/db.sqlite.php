<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * SQLite driver
 */

##
# .begin
# = class SDBSQLite
# SQLite driver. See **db.base.php** for more info
##
class SDBSQLite extends SDBBase
{
	protected $db = null;

	function __construct($conf)
	{
		$this->set_database($conf->get('name'));
	}

	public function set_database($name)
	{
		$this->database = expand_tilde($name);
		$this->db = new SQLiteDatabase($this->database);
	}

	protected function do_set_limit($sql, $limit)
	{
		if (count($limit) == 1) return ($sql . ' LIMIT ' . intval($limit[0]));
		elseif (count($limit) == 2) return ($sql . ' LIMIT ' . intval($limit[0]) . ',' . intval($limit[1]));

		return $sql;
	}

	protected function run_query($sql, $type)
	{
		$res = array();

		if ($type != SDBBase::Select)
		{
			$error_msg = '';

			if (version_compare(phpversion(), '5.1') === -1) {
				$res['result'] = $this->db->queryExec($sql);
			} else {
				$res['result'] = $this->db->queryExec($sql, $error_msg);
			}

			$res['error'] = ($res['result'] ? '' : $error_msg.'. '.sqlite_error_string($this->db->lastError()));
			$res['affected'] = ($res['result']!==false ? $this->db->changes() : 0);
		}
		else
		{
			$error_msg = '';

			if (version_compare(phpversion(), '5.1') === -1) {
				$res['result'] = $this->db->query($sql, SQLITE_ASSOC);
			} else {
				$res['result'] = $this->db->query($sql, SQLITE_ASSOC, $error_msg);
			}

			$res['error'] = ($res['result']!==false ? '' : $error_msg.'. '.sqlite_error_string($this->db->lastError()));
			$res['selected'] = ($res['result']!==false ? max($this->db->changes(), $res['result']->numRows()) : 0);
		}

		return $res;
	}

	public function quote($str)
	{
		return "'".sqlite_escape_string($str)."'";
	}

	public function quote_names($name)
	{
		return '"'.preg_replace("/[^A-Za-z0-9_\-\. ]/", '', $name).'"';
	}

	public function execute($cmd)
	{
		$res = $this->query($cmd, SDBBase::Execute);
	}

	public function insert($cmd)
	{
		$res = $this->query($cmd, SDBBase::Insert);
		return ($res ? $this->db->lastInsertRowid() : 0);
	}

	public function get_all($cmd)
	{
		$res = $this->query($cmd);
		if ($res === false) return array();

		$arr = array();
		while ($res->valid()) $arr[] = $res->fetch();

		unset($res);
		return $arr;
	}

	public function get_row($cmd)
	{
		$res = $this->query($cmd);
		if ($res === false) return null;

		if ($res->valid()) $row = $res->current();
		else $row = null;

		unset($res);
		return $row;
	}

	public function get_one($cmd)
	{
		$res = $this->query($cmd);
		if ($res === false) return null;

		if ($res->valid()) $fld = first_value($res->current());
		else $fld = null;

		unset($res);
		return $fld;
	}

	protected function do_get_tables_list()
	{
		// **TODO**: read about 'sqlite_temp_master'
		$cmd = new SDBCommand("SELECT \"name\" FROM sqlite_master WHERE \"type\"='table'");
		$res = $cmd->get_all();

		$arr = array();

		foreach ($res as $row)
		{
			$name = first_value($row);
			if (strlen($this->prefix) && strpos($name, $this->prefix)===0) $name = substr($name, strlen($this->prefix));
			$arr[] = $name;
		}

		return $arr;
	}

	protected function do_get_table_columns($table)
	{
		$cmd = new SDBCommand("PRAGMA table_info(@tbname)");
		$cmd->set('tbname', $table, SDB::TableName);
		$res = $this->get_all($cmd);

		$fields = array();

		foreach ($res as $row)
		{
			$type = $row['type'];

			if (strpos($type, '(') !== false) {
				$typename = substr($type, 0, strpos($type, '('));
				$type = substr($type, strpos($type, '(')+1);

				if (strpos($type, ')') !== false) {
					$size = intval(substr($type, 0, strpos($type, ')')));
				} else {
					$size = 255;
				}
			} else {
				$typename = $type;
				$size = 255;
			}

			$typename = strtoupper($typename);

			/* real types */
			/* http://www.sqlite.org/datatype3.html */

			if (strpos($typename, 'INT') !== false) $tp = SDB::Int;
			else
			if (strpos($typename, 'CHAR')!==false || strpos($typename, 'CLOB')!==false) $tp = SDB::String;
			else
			if (strpos($typename, 'BLOB')!==false || strpos($typename, 'TEXT')!==false) $tp = SDB::Blob;		/* TEXT is Blob in this db engine */
			else
			if (strpos($typename, 'REAL')!==false || strpos($typename, 'FLOA')!==false || strpos($typename, 'DOUB')!==false) $tp = SDB::Float;
			else
			switch ($typename)
			{
				/* virtual types */

				case 'DATE'       : $tp = SDB::Date;     break;
				case 'DATETIME'   : $tp = SDB::DateTime; break;
				case 'DECIMAL'    : $tp = SDB::Float;    break;

				/* no proper type found */

				default           : error('SDBSQLite.get_table_columns : unknown field type "'.$typename.'"');
			}

			$fields[$row['name']] = array('t' => $tp, 's' => $size);
		}

		return $fields;
	}
}
##
# .end
##
