<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Database classes. Provide database operations like .NET SqlClient
 */

require_once(S_BASE.'db/db.base.php');
require_once(S_BASE.'db/db.mysql.php');
require_once(S_BASE.'db/db.sqlite.php');

define('DB_String', 1);
define('DB_LikeString', 2);
define('DB_Int', 3);
define('DB_Float', 4);
define('DB_Date', 5);
define('DB_DateTime', 6);
define('DB_Blob', 7);
define('DB_StringsList', 8);
define('DB_IntsList', 9);
define('DB_TableName', 10);
define('DB_FieldName', 11);

##
# .begin
# = class SDB
##
class SDB
{
	##
	# = static SDBBase &get_current()
	##
	function &get_current()
	{
		static $db = null;
		if ($db == null) { $db = SDB::create(); }
		return $db;
	}

	##
	# = static SDBBase &create(string $type='', SConfig $conf=null)
	# [$type] db type (**mysql** or **sqlite**)
	# [$conf] db driver config
	##
	function &create($type='', $conf=null)
	{
		$db = null;

		if ($type == '') $type = conf('db.type');
		if ($conf == null) $conf = conf_sub('db');

		switch ($type)
		{
			case 'mysql': $db =& new SDBMySql($conf); break;
			case 'sqlite': $db =& new SDBSQLite($conf); break;
			default: error("DataBase type '$type' not recognized");
		}

		return $db;
	}

	##
	# = static string escape(string $str)
	# Wrapper to db driver
	##
	function escape($str) {
		$db =& SDB::get_current();
		return $db->escape($str);
	}

	##
	# = static string like_escape(string $str)
	# Wrapper to db driver
	##
	function like_escape($str) {
		$db =& SDB::get_current();
		return $db->like_escape($str);
	}

	##
	# = static int execute(SDBCommand &$cmd)
	# Wrapper to db driver
	##
	function execute(&$cmd) {
		$db =& SDB::get_current();
		return $db->execute($cmd);
	}

	##
	# = static array get_all(SDBCommand &$cmd)
	# Wrapper to db driver
	##
	function get_all(&$cmd) {
		$db =& SDB::get_current();
		return $db->get_all($cmd);
	}

	##
	# = static mixed get_row(SDBCommand &$cmd)
	# Wrapper to db driver
	##
	function get_row(&$cmd) {
		$db =& SDB::get_current();
		return $db->get_wow($cmd);
	}

	##
	# = static mixed get_one(SDBCommand &$cmd)
	# Wrapper to db driver
	##
	function get_one(&$cmd) {
		$db =& SDB::get_current();
		return $db->get_one($cmd);
	}

	##
	# = static SDBCommand create_count_cmd(SDBCommand &$cmd)
	# Wrapper to db driver
	##
	function create_count_cmd(&$cmd) {
		$db =& SDB::get_current();
		return $db->create_count_cmd($cmd);
	}

	##
	# = static array get_tables_list()
	# Wrapper to db driver
	##
	function get_tables_list() {
		$db =& SDB::get_current();
		return $db->get_tables_list();
	}

	##
	# = static array get_table_columns(string $table)
	# Wrapper to db driver
	##
	function get_table_columns($table) {
		$db =& SDB::get_current();
		return $db->get_table_columns($table);
	}

	##
	# = static void reset_cached_data()
	# Wrapper to db driver
	##
	function reset_cached_data() {
		$db =& SDB::get_current();
		return $db->reset_cached_data();
	}
}
##
# .end
##

##
# .begin
# = class SDBCommand
##
class SDBCommand
{
	var $db = null;

	##
	# [$command] SQL query
	##
	var $command = '';

	var $params = array();
	var $limit = array();

	function SDBCommand($command='', $db=null)
	{
		$this->__construct($command, $db);
	}

	##
	# = void __construct(string $command='', SDBBase $db=null)
	##
	function __construct($command='', $db=null)
	{
		$this->command = $command;
		$this->db = ($db!==null ? $db : SDB::get_current());
	}

	##
	# = void set(string $name, mixed $value)
	# Set existing command parameter
	##
	function set($name, $value)
	{
		if (!array_key_exists($name, $this->params)) error("Parameter '$name' not found");
		$this->params[$name]['v'] = $value;
	}

	##
	# = void add(string $name, int $type=0, mixed $value=null, int $size=255)
	# Add new command parameter
	##
	function add($name, $type=0, $value=null, $size=255)
	{
		if (array_key_exists($name, $this->params)) error("Parameter '$name' already added");

		$pr = array();
		$pr['t'] = $type;
		$pr['s'] = $size;
		$pr['v'] = $value;

		$this->params[$name] = $pr;
	}

	##
	# = void set_limit(int $from, int $count)
	##
	function set_limit($from, $count) {
		$this->limit = array($from, $count);
	}

	##
	# = int execute()
	##
	function execute() { return $this->db->execute($this); }

	##
	# = array get_all()
	##
	function get_all() { return $this->db->get_all($this); }

	##
	# = mixed get_row()
	##
	function get_row() { return $this->db->get_row($this); }

	##
	# = mixed get_one()
	##
	function get_one() { return $this->db->get_one($this); }
}
##
# .end
##

?>