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
	var $conn = null;

	function SDBMySql($conf)
	{
		$this->__construct($conf);
	}

	function __construct($conf)
	{
		$this->conn = mysql_connect($conf->get('host'), $conf->get('user'), $conf->get('pass')) or error(mysql_error());
		$this->set_database($conf->get('name'));
		$this->set_prefix($conf->get('prefix'));
	}

	function set_database($name)
	{
		$this->database = $name;
		if ($name != '') mysql_select_db($name, $this->conn) or error(mysql_error());
	}

	function i_run_query($sql, $is_exec)
	{
		$res = array();
		$res['result'] = @mysql_query($sql, $this->conn);
		$res['error'] = ($res['result'] ? '' : mysql_error($this->conn));

		if ($is_exec) {
			$res['affected'] = ($res['result'] ? 0 : mysql_affected_rows($this->conn));
		} else {
			$res['selected'] = ($res['result'] ? 0 : mysql_num_rows($this->conn));
		}

		return $res;
	}

	function quote($str)
	{
		return "'".(function_exists('mysql_real_escape_string') ? mysql_real_escape_string($str, $this->conn) : mysql_escape_string($str))."'";
	}

	function i_quote_names($name)
	{
		return '`'.(function_exists('mysql_real_escape_string') ? mysql_real_escape_string($str, $this->conn) : mysql_escape_string($name)).'`';
	}

	function execute(&$cmd)
	{
		$res = $this->i_query($cmd, true);

		if ($res === false) return 0;

		if ($res !== true) {
			if (DEBUG_ENABLE) dwrite("'".htmlspecialchars($cmd->command)."' is not a non-query", S_ERROR);
			mysql_free_result($res);
			return 0;
		}

		return mysql_insert_id($this->conn);
	}

	function get_all(&$cmd)
	{
		$res = $this->i_query($cmd);

		if ($res === false) return array();

		if ($res === true) {
			if (DEBUG) dwrite("'".htmlspecialchars($cmd->command)."' is not a SELECT query", S_ERROR);
			return array();
		}

		$arr = array();
		while ($row = mysql_fetch_assoc($res)) $arr[] = $row;
		mysql_free_result($res);
		return $arr;
	}

	function get_row(&$cmd)
	{
		$res = $this->i_query($cmd);

		if ($res === false) return null;

		if ($res === true) {
			if (DEBUG) dwrite("'".htmlspecialchars($cmd->command)."' is not a SELECT query", S_ERROR);
			return null;
		}

		if (!($row = mysql_fetch_assoc($res))) $row = null;
		mysql_free_result($res);
		return $row;
	}

	function get_one(&$cmd)
	{
		$res = $this->i_query($cmd);

		if ($res === false) return null;

		if ($res === true) {
			if (DEBUG) dwrite("'".htmlspecialchars($cmd->command)."' is not a SELECT query", S_ERROR);
			return array();
		}

		if ($row = mysql_fetch_assoc($res)) $fld = first_value($row);
		else $fld = null;

		mysql_free_result($res);
		return $fld;
	}

	function i_get_tables_list()
	{
		$cmd =& new SDBCommand("SHOW TABLES FROM @dbname");
		$cmd->add('@dbname', DB_TableName, conf('db.name'));
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

	function i_get_table_columns($table)
	{
		$cmd =& new SDBCommand("SHOW COLUMNS FROM @tbname");
		$cmd->add('@tbname', DB_TableName, $table);
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
				case 'varchar'    : $tp = DB_String;   break;
				case 'tinyint'    : $tp = DB_Int;      break;
				case 'text'       : $tp = DB_Blob;     break;
				case 'date'       : $tp = DB_Date;     break;
				case 'smallint'   : $tp = DB_Int;      break;
				case 'mediumint'  : $tp = DB_Int;      break;
				case 'int'        : $tp = DB_Int;      break;
				case 'bigint'     : $tp = DB_Int;      break;
				case 'float'      : $tp = DB_Float;    break;
				case 'double'     : $tp = DB_Float;    break;
				case 'decimal'    : $tp = DB_Float;    break;
				case 'datetime'   : $tp = DB_DateTime; break;
				case 'timestamp'  : error('SDBMySql.get_table_columns : TODO: check mysql manual for \'timestamp\''); break;
				case 'time'       : error('SDBMySql.get_table_columns : TODO: check mysql manual for \'time\''); break;
				case 'year'       : error('SDBMySql.get_table_columns : TODO: check mysql manual for \'year\''); break;
				case 'char'       : $tp = DB_String;   break;
				case 'tinyblob'   : $tp = DB_Blob;     break;
				case 'tinytext'   : $tp = DB_Blob;     break;
				case 'blob'       : $tp = DB_Blob;     break;
				case 'mediumblob' : $tp = DB_Blob;     break;
				case 'mediumtext' : $tp = DB_Blob;     break;
				case 'longblob'   : $tp = DB_Blob;     break;
				case 'longtext'   : $tp = DB_Blob;     break;
				case 'enum'       : error('SDBMySql.get_table_columns : unsupported type \'enum\''); break;
				case 'set'        : error('SDBMySql.get_table_columns : TODO: check mysql manual for \'set\''); break;
				default           : error('SDBMySql.get_table_columns : unknown field type \''.$typename.'\'');
			}

			$fields[$row['Field']] = array('t' => $tp, 's' => $size);
		}

		return $fields;
	}
}
##
# .end
##
