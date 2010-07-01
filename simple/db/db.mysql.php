<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * MySql driver
 */

##
# .begin
# = class SDBMySql
# MySql driver. See **db.base.php** for more info
##
class SDBMySql extends SDBBase
{
	protected $conn = null;

	function __construct($conf)
	{
		$this->conn = mysql_connect($conf->get('host'), $conf->get('user'), $conf->get('pass'));
		if (!$this->conn) throw new Exception(mysql_error());

		$this->set_database($conf->get('name'));
		$this->set_prefix($conf->get('prefix'));
	}

	public function set_database($name)
	{
		$this->database = $name;

		if ($name != '') {
			if (!mysql_select_db($name, $this->conn)) {
				throw new Exception(mysql_error());
			}
		}
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
		$res['result'] = @mysql_query($sql, $this->conn);
		$res['error'] = ($res['result'] ? '' : mysql_error($this->conn));

		if ($type == SDBBase::Select) {
			$res['selected'] = ($res['result'] ? mysql_num_rows($res['result']) : 0);
		} else {
			$res['affected'] = ($res['result'] ? mysql_affected_rows($this->conn) : 0);
		}

		return $res;
	}

	public function quote($str)
	{
		return "'".(function_exists('mysql_real_escape_string') ? mysql_real_escape_string($str, $this->conn) : mysql_escape_string($str))."'";
	}

	public function quote_names($name)
	{
		return '`'.preg_replace("/[^A-Za-z0-9_\-\. ]/", '', $name).'`';
	}

	public function execute($cmd)
	{
		$res = $this->query($cmd, SDBBase::Execute);
		if ($res === false) return;

		if ($res !== true)
		{
			if (DEBUG_ENABLE) dwrite("**\"{$cmd->command}\"** is not a non-query", S_ERROR);
			mysql_free_result($res);
		}
	}

	public function insert($cmd)
	{
		$res = $this->query($cmd, SDBBase::Insert);
		if ($res === false) return 0;

		if ($res !== true)
		{
			if (DEBUG_ENABLE) dwrite("**\"{$cmd->command}\"** is not a non-query", S_ERROR);
			mysql_free_result($res);
			return 0;
		}

		return mysql_insert_id($this->conn);
	}

	public function get_all($cmd)
	{
		$res = $this->query($cmd);
		if ($res === false) return array();

		if ($res === true) {
			if (DEBUG) dwrite("**\"{$cmd->command}\"** is not a SELECT query", S_ERROR);
			return array();
		}

		$arr = array();
		while ($row = mysql_fetch_assoc($res)) $arr[] = $row;
		mysql_free_result($res);
		return $arr;
	}

	public function get_row($cmd)
	{
		$res = $this->query($cmd);
		if ($res === false) return null;

		if ($res === true) {
			if (DEBUG) dwrite("**\"{$cmd->command}\"** is not a SELECT query", S_ERROR);
			return null;
		}

		if (!($row = mysql_fetch_assoc($res))) $row = null;
		mysql_free_result($res);
		return $row;
	}

	public function get_one($cmd)
	{
		$res = $this->query($cmd);
		if ($res === false) return null;

		if ($res === true) {
			if (DEBUG) dwrite("**\"{$cmd->command}\"** is not a SELECT query", S_ERROR);
			return null;
		}

		if ($row = mysql_fetch_assoc($res)) $fld = first_value($row);
		else $fld = null;

		mysql_free_result($res);
		return $fld;
	}

	protected function do_get_tables_list()
	{
		$cmd = new SDBCommand("SHOW TABLES FROM @dbname");
		$cmd->set('dbname', conf('db.name'), SDB::TableName);
		$res = $this->get_all($cmd);

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
		$cmd = new SDBCommand("SHOW COLUMNS FROM @tbname");
		$cmd->set('tbname', $table, SDB::TableName);
		$res = $this->get_all($cmd);

		$fields = array();

		foreach ($res as $row)
		{
			$type = $row['Type'];

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

			switch ($typename)
			{
				case 'varchar'    : $tp = SDB::String;   break;
				case 'tinyint'    : $tp = SDB::Int;      break;
				case 'text'       : $tp = SDB::Blob;     break;
				case 'date'       : $tp = SDB::Date;     break;
				case 'smallint'   : $tp = SDB::Int;      break;
				case 'mediumint'  : $tp = SDB::Int;      break;
				case 'int'        : $tp = SDB::Int;      break;
				case 'bigint'     : $tp = SDB::Int;      break;
				case 'float'      : $tp = SDB::Float;    break;
				case 'double'     : $tp = SDB::Float;    break;
				case 'decimal'    : $tp = SDB::Float;    break;
				case 'datetime'   : $tp = SDB::DateTime; break;
				case 'timestamp'  : throw new Exception('TODO: check mysql manual for "timestamp"'); break;
				case 'time'       : throw new Exception('TODO: check mysql manual for "time"'); break;
				case 'year'       : throw new Exception('TODO: check mysql manual for "year"'); break;
				case 'char'       : $tp = SDB::String;   break;
				case 'tinyblob'   : $tp = SDB::Blob;     break;
				case 'tinytext'   : $tp = SDB::Blob;     break;
				case 'blob'       : $tp = SDB::Blob;     break;
				case 'mediumblob' : $tp = SDB::Blob;     break;
				case 'mediumtext' : $tp = SDB::Blob;     break;
				case 'longblob'   : $tp = SDB::Blob;     break;
				case 'longtext'   : $tp = SDB::Blob;     break;
				case 'enum'       : throw new Exception('Unsupported type "enum"'); break;
				case 'set'        : throw new Exception('TODO: check mysql manual for "set"'); break;
				default           : throw new Exception("Unknown field type \"$typename\"");
			}

			$fields[$row['Field']] = array('t' => $tp, 's' => $size);
		}

		return $fields;
	}
}
##
# .end
##
