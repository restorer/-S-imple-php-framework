<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Base driver
 */

##
# .begin
# = class SDBBase
# Base class for db drivers
##
class SDBBase
{
	##
	# [$database] Current database
	##
	var $database = '';

	##
	# [$prefix] Table prefix
	##
	var $prefix = '';

	##
	# [$tables] Table names cache
	##
	var $tables = null;

	##
	# [$tables_columns] Table column names cache
	##
	var $tables_columns = array();

	##
	# = abstract void set_database(string $name)
	##
	function set_database($name)
	{
		error('SDBBase.set_database must be overrided');
	}

	##
	# = void set_prefix(string $prefix)
	##
	function set_prefix($prefix)
	{
		$this->prefix = $prefix;
	}

	##
	# = string i_parse(SDBCommand &$cmd)
	# [$cmd] Command to parse
	# **TODO:** More flexible limiting (i.e. MSSQL has no LIMIT command, but has SELECT TOP <num>)
	##
	function i_parse(&$cmd)
	{
		global $s_runconf;
		$arr = array();

		if (DEBUG) { $st = get_microtime(); }

		foreach ($cmd->params as $k=>$parm)
		{
			$val = $parm['v'];

			if ($val === null) {
				$arr[$k] = 'NULL';
				continue;
			}

			switch ($parm['t'])
			{
				case DB_String:
					$val = strval($val);

					if (strlen($val) > $parm['s']) {
						if (DEBUG) dwrite("Parameter '$k' size more than ".$parm['s'], S_ERROR);
						$val = substr($val, 0, $parm['s']);
					}

					$val = $this->quote($val);
					break;

				case DB_LikeString:
					$val = strval($val);

					if (strlen($val) > $parm['s']) {
						if (DEBUG) dwrite("Parameter '$k' size more than ".$parm['s'], S_ERROR);
						$val = substr($val, 0, $parm['s']);
					}

					$val = $this->quote_like($val);
					break;

				case DB_Int:
					if (!is_numeric($val)) {if (DEBUG) dwrite("Parameter '$k' is not DB_Int", S_ERROR);}
					$val = intval($val);
					$val = $this->quote($val);
					break;

				case DB_Float:
					if (!is_numeric($val)) {if (DEBUG) dwrite("Parameter '$k' is not DB_Float", S_ERROR);}
					$val = floatval($val);
					$val = $this->quote($val);
					break;

				case DB_Date:
					$val = strval($val);

					if (!preg_match("/^(\d{4})-(\d\d)-(\d{2})$/", $val)) {
						if (DEBUG) dwrite("Parameter '$k' is not DB_Date", S_ERROR);
						$val = '0000-01-01';
					}

					$val = $this->quote($val);
					break;

				case DB_DateTime:
					$val = strval($val);

					if (!preg_match("/^(\d{4})-(\d\d)-(\d{2})( (\d\d):(\d\d):(\d\d))?$/", $val)) {
						if (DEBUG) dwrite("Parameter '$k' is not DB_DateTime", S_ERROR);
						$val = '0000-01-01 00:00:00';
					}

					$val = $this->quote($val);
					break;

				case DB_Blob:
					$val = strval($val);
					$val = $this->quote($val);
					break;

				case DB_StringsList:
					$ar = $val;

					if (!is_array($ar)) {
						$ar = array();
						if (DEBUG) dwrite("Parameter '$k' is not Int_StringsList (not an array)", S_ERROR);
					}

					$val = '';

					foreach ($ar as $vl)
					{
						$rv = strval($vl);

						if (strlen($rv) > $parm['s']) {
							if (DEBUG) dwrite("Some elements in parameter '$k' has size more than ".$parm['s'], S_ERROR);
							$rv = substr($rv, 0, $parm['s']);
						}

						$val .= ($val==''?'':',') . $this->quote($rv);
					}
					break;

				case DB_IntsList:
					$ar = $val;

					if (!is_array($ar)) {
						$ar = array();
						if (DEBUG) dwrite("Parameter '$k' is not DB_IntsList (not an array)", S_ERROR);
					}

					$val = '';

					foreach ($ar as $vl)
					{
						if (!is_numeric($vl)) { if (DEBUG) dwrite("Some elements in parameter '$k' are not DB_Int", S_ERROR); }
						$rv = intval($vl);
						$val .= ($val==''?'':',') . $this->quote($rv);
					}
					break;

				case DB_TableName:
					$val = strval($val);

					if (strlen($val) > $parm['s']) {
						if (DEBUG) dwrite("Parameter '$k' size more than ".$parm['s'], S_ERROR);
						$val = substr($val, 0, $parm['s']);
					}

					$val = $this->quote_table($val);
					break;

				case DB_FieldName:
					$val = strval($val);

					if (strlen($val) > $parm['s']) {
						if (DEBUG) dwrite("Parameter '$k' size more than ".$parm['s'], S_ERROR);
						$val = substr($val, 0, $parm['s']);
					}

					$val = $this->quote_field($val);
					break;

				default: error("Data type '".$parm['t']."' not recognized");
			}

			$arr[$k] = $val;
		}

		$sql = $cmd->command;
		$sql = str_replace('@@', $this->prefix, $sql);

		$l = strlen($sql);
		$str = '';
		$res = '';

		for ($i = 0; $i < $l; $i++)
		{
			$ch = $sql{$i};

			if (($ch>='0'&&$ch<='9')||($ch>='a'&&$ch<='z')||($ch>='A'&&$ch<='Z')||$ch=='_'||$ch=='@') {
				$str .= $ch;
			} elseif ($str != '') {
				$res .= (array_key_exists($str, $arr) ? $arr[$str] : $str) . $ch;
				$str = '';
			} else $res .= $ch;
		}

		$res .= (array_key_exists($str, $arr) ? $arr[$str] : $str);

		if (count($cmd->limit) == 1) $sql .= ' LIMIT '.intval($cmd->limit[0]);
		elseif (count($cmd->limit) == 2) $sql .= ' LIMIT '.intval($cmd->limit[0]).','.intval($cmd->limit[1]);

		if (DEBUG)
		{
			$dt = get_microtime() - $st;
			$s_runconf->set('time.sql.parse', $s_runconf->get('time.sql.parse') + $dt);
		}

		return $res;
	}

	##
	# = array i_run_query(string $sql, bool $is_exec)
	# [$sql] SQL query to execute
	# [$is_exec] false = select command, true = non-select command
	# {$result['result']} Command result (resource or class, depending on db driver)
	# {$result['error']} Empty string - no errors
	# {$result['affected']} Number of affected rows (for exec queries)
	# {$result['selected']} Number of selected rows (for non-exec queries)
	##
	function i_run_query($sql, $is_exec)
	{
		error('SDBBase.i_run_query must be overrided');
	}

	##
	# = mixed i_query(SDBCommand &$cmd, bool $is_exec=false)
	# [$cmd] Command to execute
	# [$is_exec] false = select command, true = non-select command
	##
	function i_query(&$cmd, $is_exec=false)
	{
		global $s_runconf;
		$sql = $this->i_parse($cmd);

		if (DEBUG)
		{
			$t1 = get_microtime();
			$res = $this->i_run_query($sql, $is_exec);
			$t2 = get_microtime();

			if ($res['error'])
			{
				dwrite("<b>Failed [</b>".htmlspecialchars($sql)."<b>] ".$res['error']."</b>", S_ERROR);
			}
			else
			{
				$dt = $t2 - $t1;
				$s_runconf->set('time.sql.query', $s_runconf->get('time.sql.query') + $dt);
				$rows_str = ($is_exec ? $res['affected'].' rows affected' : $res['selected'].' rows selected');
				dwrite("<b>Success [</b>".htmlspecialchars($sql)."<b>] $rows_str</b> (".number_format($dt, 8).")", ($dt<0.1 ? S_SUCCESS : S_ACCENT));
			}
		}
		else { $res = $this->i_run_query($sql, $is_exec); }

		return $res['result'];
	}

	##
	# = abstract string quote(string $str)
	# Quote and escape string
	##
	function quote($str)
	{
		error('SDBBase.quote must be overrided');
	}

	##
	# = abstract string i_quote_names(string $name)
	# Quote table and field names
	##
	function i_quote_names($name)
	{
		error('SDBBase.quote_names must be overrided');
	}

	##
	# = string quote_like(string $str)
	# Quote (and escape) string to use in like expressions (additionally escapes '%' and '_' symbols)
	# **TODO:** Check escaping method in sqlite
	##
	function quote_like($str)
	{
		$str = $this->quote($str);
		$str = str_replace(array('%', '_'), array('\\%', '\\_'), $str);
		return $str;
	}

	##
	# = string quote_table(string $str)
	# Quote and escape table name
	##
	function quote_table($name)
	{
		return $this->i_quote_names($this->prefix.$name);
	}

	##
	# = string quote_field(string $str)
	# Quote and escape field name
	##
	function quote_field($name)
	{
		return $this->i_quote_names($name);
	}

	##
	# = abstract int execute(SDBCommand &$cmd)
	# Execute non-select command. Returns last insert id
	##
	function execute(&$cmd)
	{
		error('SDBBase.execute must be overrided');
	}

	##
	# = abstract array get_all(SDBCommand &$cmd)
	# Execute select command, and return results as array of assoc arrays
	##
	function get_all(&$cmd)
	{
		error('SDBBase.get_all must be overrided');
	}

	##
	# = abstract mixed get_row(SDBCommand &$cmd)
	# Execute select command, and return associative array of first result row or null if no records found
	##
	function get_row(&$cmd)
	{
		error('SDBBase.get_row must be overrided');
	}

	##
	# = abstract mixed get_one(SDBCommand &$cmd)
	# Execute select command, and return first field of first row or null if no records found
	##
	function get_one(&$cmd)
	{
		error('SDBBase.get_one must be overrided');
	}

	##
	# = SDBCommand create_count_cmd(SDBCommand &$cmd)
	# Translate "SELECT a,d,c,d FROM t WHERE x=y" to "SELECT COUNT(*) FROM t WHERE x=y"
	##
	function create_count_cmd(&$cmd)
	{
		$sql = $cmd->command;

		while (strlen($sql) && substr($sql, 0, 1)==' ') $sql = substr($sql, 1);

		if (strtoupper(substr($sql, 0, 7)) != 'SELECT ') error("create_count_cmd - this is not SELECT command ($sql)");

		if (($pos = strpos(strtoupper($sql), ' FROM ')) === false) {
			if (($pos = strpos(strtoupper($sql), "\tFROM ")) === false) {
				error("create_count_cmd - can't find 'FROM' statement ($sql)");
			}
		}

		$res = 'SELECT COUNT(*) FROM ' . substr($sql, $pos+6);

		$cmdx = new SDBCommand($res, $this);
		$cmdx->params = $cmd->params;
		$cmdx->limit = $cmd->limit;
		return $cmdx;
	}

	##
	# = abstract array i_get_tables_list()
	# Must return array of table names
	##
	function i_get_tables_list()
	{
		error('SDBBase.i_get_tables_list must be overrided');
	}

	##
	# = array get_tables_list()
	# Returns array of table names (use cache if possible)
	##
	function get_tables_list()
	{
		if ($this->tables === null) { $this->tables = $this->i_get_tables_list(); }
		return $this->tables;
	}

	##
	# = abstract array i_get_table_columns(string $table)
	# Must return assoc array of table fields.
	# **$key** - field name
	# **$result[$key]['t']** - field type
	# **$result[$key]['s']** - field size
	##
	function i_get_table_columns()
	{
		error('SDBBase.i_get_table_columns must be overrided');
	}

	##
	# = array get_table_columns(string $table)
	# Returns assoc array of table fields (use cache if possible)
	##
	function get_table_columns($table)
	{
		if (!array_key_exists($table, $this->tables_columns)) { $this->tables_columns[$table] = $this->i_get_table_columns($table); }
		return $this->tables_columns[$table];
	}

	##
	# = void reset_cached_data()
	# Clear cached data (tables, table fields)
	##
	function reset_cached_data()
	{
		$this->tables = null;
		$this->tables_columns = array();
	}
}
##
# .end
##
