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
# = abstract class SDBBase
# Base class for db drivers
##
abstract class SDBBase
{
	const Select = 1;
	const Insert = 2;
	const Execute = 3;

	##
	# [$database] Current database
	##
	protected $database = '';

	##
	# [$prefix] Table prefix
	##
	protected $prefix = '';

	##
	# [$tables] Table names cache
	##
	protected $tables = null;

	##
	# [$tables_columns] Table column names cache
	##
	protected $tables_columns = array();

	##
	# = public void call_init_hook()
	##
	public function call_init_hook()
	{
		if (conf_has('db.init_hook')) {
			call_user_func(conf('db.init_hook'), $this);
		}
	}

	##
	# = public abstract void set_database(string $name)
	##
	public abstract function set_database($name);

	##
	# = public void set_prefix(string $prefix)
	##
	public function set_prefix($prefix)
	{
		$this->prefix = $prefix;
	}

	##
	# = protected abstract string do_set_limit($sql, $limit)
	##
	protected abstract function do_set_limit($sql, $limit);

	##
	# = void prepare(SDBCommand &$cmd)
	##
	protected function prepare($cmd)
	{
		$sql = $cmd->command;
		if ($cmd->_prepared_command!=null && $cmd->_prepared_command[0]==$sql) return;

		$res = array();
		$len = strlen($sql);
		$buf = '';

		for ($pos = 0; $pos < $len;)
		{
			$ch = $sql{$pos};
			$pos++;

			if ($ch=='\'' || $ch=='`' || $ch=='"')
			{
				$buf .= $ch;
				$chx = $ch;

				while ($pos < $len)
				{
					$ch = $sql{$pos};
					$pos++;

					$buf .= $ch;
					if ($ch == $chx) break;
				}
			}
			else if ($ch == '@')
			{
				if ($pos >= $len) throw new Exception("Can't parse sql command: \"$sql\"");

				if (strlen($buf))
				{
					$res[] = array('s', $buf);
					$buf = '';
				}

				$name = $sql{$pos};
				$pos++;

				if ($name == '@')
				{
					$res[] = array('t');
					continue;
				}

				for (;$pos < $len; $pos++)
				{
					$ch = $sql{$pos};
					if (($ch<'a' || $ch>'z') && ($ch<'A' || $ch>'Z') && $ch!='_') break;

					$name .= $ch;
				}

				$res[] = array('p', $name);
			}
			else
			{
				$buf .= $ch;
			}
		}

		if (strlen($buf)) {
			$res[] = array('s', $buf);
		}

		$cmd->_prepared_command = array($sql, $res);
	}

	##
	# = string bind(SDBCommand &$cmd)
	# **TODO:** More flexible limiting (i.e. MSSQL has no LIMIT command, but has SELECT TOP <num>)
	##
	protected function bind($cmd)
	{
		$prep = $cmd->_prepared_command[1];
		$params = array();
		$sql = '';

		foreach ($prep as $arr)
		{
			switch ($arr[0])
			{
				case 's':
					$sql .= $arr[1];
					break;

				case 't':
					$sql .= $this->prefix;
					break;

				case 'p':
					$name = $arr[1];

					if (!array_key_exists($name, $params))
					{
						if (!array_key_exists($name, $cmd->_params)) throw new Exception("Unknown parameter \"$name\"");
						$params[$name] = $this->get_param_value($name, $cmd->_params[$name]);
					}

					$sql .= $params[$name];
					break;
			}
		}

		if ($cmd->_limit!=null && count($cmd->_limit)) {
			$sql = $this->do_set_limit($sql, $cmd->_limit);
		}

		return $sql;
	}

	##
	# = protected string get_param_value(string $name, array $parm)
	##
	protected function get_param_value($name, $parm)
	{
		if ($parm['c'] !== null) return $parm['c'];

		$val = $parm['v'];
		if ($val === null) return 'NULL';

		switch ($parm['t'])
		{
			case SDB::String:
				$val = strval($val);

				if (strlen($val) > $parm['s']) {
					if (DEBUG) dwrite("Parameter '$name' size more than ".$parm['s'], S_ERROR);
					$val = substr($val, 0, $parm['s']);
				}

				$val = $this->quote($val);
				break;

			case SDB::LikeString:
				$val = strval($val);

				if (strlen($val) > $parm['s']) {
					if (DEBUG) dwrite("Parameter '$name' size more than ".$parm['s'], S_ERROR);
					$val = substr($val, 0, $parm['s']);
				}

				$val = $this->quote_like($val);
				break;

			case SDB::Int:
				if (!is_numeric($val)) {
					if (DEBUG) dwrite("Parameter '$name' is not SDB::Int", S_ERROR);
				}

				$val = $this->quote(intval($val));
				break;

			case SDB::Float:
				if (!is_numeric($val)) {
					if (DEBUG) dwrite("Parameter '$name' is not SDB::Float", S_ERROR);
				}

				$val = $this->quote(floatval($val));
				break;

			case SDB::Date:
				$val = strval($val);

				if (!preg_match("/^(\d{4})-(\d\d)-(\d{2})$/", $val)) {
					if (DEBUG) dwrite("Parameter '$name' is not SDB::Date", S_ERROR);
					$val = '0000-01-01';
				}

				$val = $this->quote($val);
				break;

			case SDB::DateTime:
				$val = strval($val);

				if (!preg_match("/^(\d{4})-(\d\d)-(\d{2})( (\d\d):(\d\d):(\d\d))?$/", $val)) {
					if (DEBUG) dwrite("Parameter '$name' is not SDB::DateTime", S_ERROR);
					$val = '0000-01-01 00:00:00';
				}

				$val = $this->quote($val);
				break;

			case SDB::Blob:
				$val = $this->quote(strval($val));
				break;

			case SDB::StringsList:
				$ar = $val;

				if (!is_array($ar)) {
					$ar = array();
					if (DEBUG) dwrite("Parameter '$name' is not SDB::StringsList (not an array)", S_ERROR);
				}

				foreach ($ar as &$vl)
				{
					$vl = strval($vl);

					if (strlen($vl) > $parm['s']) {
						if (DEBUG) dwrite("Some elements in parameter '$name' has size more than ".$parm['s'], S_ERROR);
						$vl = substr($vl, 0, $parm['s']);
					}

					$vl = $this->quote($vl);
				}

				$val = join(',', $ar);
				break;

			case SDB::IntsList:
				$ar = $val;

				if (!is_array($ar)) {
					$ar = array();
					if (DEBUG) dwrite("Parameter '$name' is not SDB::IntsList (not an array)", S_ERROR);
				}

				foreach ($ar as &$vl)
				{
					if (!is_numeric($vl)) {
						if (DEBUG) dwrite("Some elements in parameter '$name' are not SDB::Int", S_ERROR);
					}

					$vl = $this->quote(intval($vl));
				}

				$val = join(',', $ar);
				break;

			case SDB::TableName:
				$val = strval($val);

				if (strlen($val) > $parm['s']) {
					if (DEBUG) dwrite("Parameter '$name' size more than ".$parm['s'], S_ERROR);
					$val = substr($val, 0, $parm['s']);
				}

				$val = $this->quote_table($val);
				break;

			case SDB::FieldName:
				$val = strval($val);

				if (strlen($val) > $parm['s']) {
					if (DEBUG) dwrite("Parameter '$name' size more than ".$parm['s'], S_ERROR);
					$val = substr($val, 0, $parm['s']);
				}

				$val = $this->quote_field($val);
				break;

			default:
				throw new Exception("Data type '".$parm['t']."' not recognized");
		}

		$parm['c'] = $val;
		return $val;
	}

	##
	# = string parse(SDBCommand &$cmd)
	# [$cmd] Command to parse
	##
	protected function parse($cmd)
	{
		global $s_runconf;

		if (DEBUG) { $st = get_microtime(); }

		$this->prepare($cmd);
		$res = $this->bind($cmd);

		if (DEBUG)
		{
			$dt = get_microtime() - $st;
			$s_runconf->set('time.sql.parse', $s_runconf->get('time.sql.parse') + $dt);
		}

		return $res;
	}

	##
	# = protected abstract array run_query(string $sql, mixed $type)
	# [$sql] SQL query to execute
	# [$type] Command type (SDBBase::Select, SDBBase::Insert or SDBBase::Execute)
	# {$result['result']} Command result (resource or class, depending on db driver)
	# {$result['error']} Empty string - no errors
	# {$result['selected']} Number of selected rows (for select queries)
	# {$result['affected']} Number of affected rows (for non-select queries)
	##
	protected abstract function run_query($sql, $type);

	##
	# = protected mixed query(SDBCommand &$cmd, mixed $is_exec=false)
	# [$cmd] Command to execute
	# [$type] Command type (SDBBase::Select, SDBBase::Insert or SDBBase::Execute)
	##
	protected function query($cmd, $type=SDBBase::Select)
	{
		global $s_runconf;
		$sql = $this->parse($cmd);

		if (DEBUG)
		{
			$t1 = get_microtime();
			$res = $this->run_query($sql, $type);
			$t2 = get_microtime();

			if ($res['error'])
			{
				dwrite("**Failed [** $sql **] {$res['error']}**", S_ERROR);
			}
			else
			{
				$dt = $t2 - $t1;
				$s_runconf->set('time.sql.query', $s_runconf->get('time.sql.query') + $dt);
				$rows_str = ($type!=SDBBase::Select ? $res['affected'].' rows affected' : $res['selected'].' rows selected');
				dwrite("**Success [** $sql **] {$rows_str}** (".number_format($dt, 8).")", ($dt<0.1 ? S_SUCCESS : S_ACCENT));
			}
		}
		else { $res = $this->run_query($sql, $type); }

		return $res['result'];
	}

	##
	# = public abstract string quote(string $str)
	# Quote and escape string
	##
	public abstract function quote($str);

	##
	# = public abstract string quote_names(string $name)
	# Quote table and field names
	##
	public abstract function quote_names($name);

	##
	# = public string quote_like(string $str)
	# Quote (and escape) string to use in like expressions (additionally escapes '%' and '_' symbols)
	# **TODO:** Check escaping method in sqlite
	##
	public function quote_like($str)
	{
		$str = $this->quote($str);
		$str = str_replace(array('%', '_'), array('\\%', '\\_'), $str);
		return $str;
	}

	##
	# = public string quote_table(string $str)
	# Quote and escape table name
	##
	public function quote_table($name)
	{
		return $this->quote_names($this->prefix . $name);
	}

	##
	# = public string quote_field(string $str)
	# Quote and escape field name
	##
	public function quote_field($name)
	{
		return $this->quote_names($name);
	}

	##
	# = public abstract void execute(SDBCommand &$cmd)
	# Execute non-select command.
	##
	public abstract function execute($cmd);

	##
	# = public abstract int insert(SDBCommand &$cmd)
	# Execute insert command. Returns last insert id
	##
	public abstract function insert($cmd);

	##
	# = public abstract array get_all(SDBCommand &$cmd)
	# Execute select command, and return results as array of assoc arrays
	##
	public abstract function get_all($cmd);

	##
	# = public abstract mixed get_row(SDBCommand &$cmd)
	# Execute select command, and return associative array of first result row or null if no records found
	##
	public abstract function get_row($cmd);

	##
	# = public abstract mixed get_one(SDBCommand &$cmd)
	# Execute select command, and return first field of first row or null if no records found
	##
	public abstract function get_one($cmd);

	##
	# = public SDBCommand create_count_cmd(SDBCommand &$cmd)
	# Translate "SELECT a,d,c,d FROM t WHERE x=y" to "SELECT COUNT(*) FROM t WHERE x=y"
	##
	public function create_count_cmd($cmd)
	{
		$sql = $cmd->command;

		while (strlen($sql) && substr($sql, 0, 1)==' ') $sql = substr($sql, 1);

		if (strtoupper(substr($sql, 0, 7)) != 'SELECT ') throw new Exception("\"$sql\" is not SELECT command");

		if (($pos = strpos(strtoupper($sql), ' FROM ')) === false) {
			if (($pos = strpos(strtoupper($sql), "\tFROM ")) === false) {
				throw new Exception("Can't find 'FROM' statement in \"$sql\"");
			}
		}

		$res = 'SELECT COUNT(*) FROM ' . substr($sql, $pos+6);

		$cmdx = new SDBCommand($res, $this);
		$cmdx->_params = $cmd->_params;
		$cmdx->_limit = $cmd->_limit;
		return $cmdx;
	}

	##
	# = protected abstract array do_get_tables_list()
	# Must return array of table names
	##
	protected abstract function do_get_tables_list();

	##
	# = public array get_tables_list()
	# Returns array of table names (use cache if possible)
	##
	public function get_tables_list()
	{
		if ($this->tables === null) { $this->tables = $this->do_get_tables_list(); }
		return $this->tables;
	}

	##
	# = protected abstract array do_get_table_columns(string $table)
	# Must return assoc array of table fields.
	# **$key** - field name
	# **$result[$key]['t']** - field type
	# **$result[$key]['s']** - field size
	##
	protected abstract function do_get_table_columns($table);

	##
	# = public array get_table_columns(string $table)
	# Returns assoc array of table fields (use cache if possible)
	##
	public function get_table_columns($table)
	{
		if (!array_key_exists($table, $this->tables_columns)) { $this->tables_columns[$table] = $this->do_get_table_columns($table); }
		return $this->tables_columns[$table];
	}

	##
	# = public void reset_cached_data()
	# Clear cached data (tables, table fields)
	##
	public function reset_cached_data()
	{
		$this->tables = null;
		$this->tables_columns = array();
	}
}
##
# .end
##
