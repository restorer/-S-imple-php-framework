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
	var $db = null;

	function SDBSQLite($conf)
	{
		$this->__construct($conf);
	}

	function __construct($conf)
	{
		$this->set_database($conf->get('name'));
	}

	function set_database($name)
	{
		$this->database = expand_tilde($name);
		$this->db =& new SQLiteDatabase($this->database);
	}

	function i_limit($sql, $limit)
	{
		if (count($limit) == 1) $sql .= ' LIMIT '.intval($limit[0]);
		elseif (count($limit) == 2) $sql .= ' LIMIT '.intval($limit[0]).','.intval($limit[1]);

		return $sql;
	}

	function i_parse(&$cmd)
	{
		return $this->i_parse_cmd($cmd, "'", '"', '"');
	}

	function i_run_query($sql, $is_exec)
	{
		$res = array();

		if ($is_exec)
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
			$res['affected'] = ($res['result']!==false ? max($this->db->changes(), $res['result']->numRows()) : 0);
		}

		return $res;
	}

	function escape($str)
	{
		return sqlite_escape_string($str);
	}

	function execute(&$cmd)
	{
		$res = $this->i_query($cmd, true);
		return ($res ? $this->db->lastInsertRowid() : 0);
	}

	function get_all(&$cmd)
	{
		$res = $this->i_query($cmd);

		if ($res === false) return array();

		$arr = array();
		while ($res->valid()) $arr[] = $res->fetch();

		unset($res);
		return $arr;
	}

	function get_row(&$cmd)
	{
		$res = $this->i_query($cmd);

		if ($res === false) return null;

		if ($res->valid()) $row = $res->current();
		else $row = null;

		unset($res);
		return $row;
	}

	function get_one(&$cmd)
	{
		$res = $this->i_query($cmd);

		if ($res === false) return null;

		if ($res->valid()) $fld = first_value($res->current());
		else $fld = null;

		unset($res);
		return $fld;
	}

	function i_get_tables_list()
	{
		// todo: read about 'sqlite_temp_master'
		$cmd =& new SDBCommand("SELECT \"name\" FROM sqlite_master WHERE \"type\"='table'");
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

	function i_get_table_columns($table)
	{
		$cmd =& new SDBCommand("PRAGMA table_info(@tbname)");
		$cmd->add('@tbname', DB_TableName, $table);
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

			if (strpos($typename, 'INT') !== false) $tp = DB_Int;
			else
			if (strpos($typename, 'CHAR')!==false || strpos($typename, 'CLOB')!==false) $tp = DB_String;
			else
			if (strpos($typename, 'BLOB')!==false || strpos($typename, 'TEXT')!==false) $tp = DB_Blob;		/* TEXT is Blob in this db engine */
			else
			if (strpos($typename, 'REAL')!==false || strpos($typename, 'FLOA')!==false || strpos($typename, 'DOUB')!==false) $tp = DB_Float;
			else
			switch ($typename)
			{
				/* virtual types */

				case 'DATE'       : $tp = DB_Date;     break;
				case 'DATETIME'   : $tp = DB_DateTime; break;
				case 'DECIMAL'    : $tp = DB_Float;    break;

				/* no proper type found */

				default           : error('SDBSQLite.get_table_columns : unknown field type \''.$typename.'\'');
			}

			$fields[$row['name']] = array('t' => $tp, 's' => $size);
		}

		return $fields;
	}
}
##
# .end
##
