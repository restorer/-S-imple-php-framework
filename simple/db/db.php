<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Database classes. Provide database operations like .NET SqlClient
 */

require_once(S_BASE . 'db/db.base.php');
require_once(S_BASE . 'db/db.mysql.php');
require_once(S_BASE . 'db/db.sqlite.php');

##
# .begin
# = class SDB
##
class SDB
{
	const String = 1;
	const LikeString = 2;
	const Int = 3;
	const Float = 4;
	const Date = 5;
	const DateTime = 6;
	const Blob = 7;
	const StringsList = 8;
	const IntsList = 9;
	const TableName = 10;
	const FieldName = 11;

	const Text = self::Blob;

	##
	# = public static SDBBase get_current()
	##
	public static function get_current()
	{
		static $db = null;
		if ($db == null) { $db = SDB::create(); }
		return $db;
	}

	##
	# = public static SDBBase create(string $type='', SConfig $conf=null)
	# [$type] db type (**mysql** or **sqlite**)
	# [$conf] db driver config
	##
	public static function create($type='', $conf=null)
	{
		$db = null;

		if ($type == '') $type = conf('db.type');
		if ($conf == null) $conf = conf_sub('db');

		switch ($type)
		{
			case 'mysql': $db = new SDBMySql($conf); break;
			case 'sqlite': $db = new SDBSQLite($conf); break;
			default: error("DataBase type '$type' not recognized");
		}

		$db->call_init_hook();
		return $db;
	}

	##
	# = public static string escape(string $str)
	# Wrapper to db driver
	##
	public static function escape($str) {
		$db =& SDB::get_current();
		return $db->escape($str);
	}

	##
	# = public static string like_escape(string $str)
	# Wrapper to db driver
	##
	public static function like_escape($str) {
		$db =& SDB::get_current();
		return $db->like_escape($str);
	}

	##
	# = public static SDBCommand create_count_cmd(SDBCommand $cmd)
	# Wrapper to db driver
	##
	public static function create_count_cmd($cmd) {
		$db =& SDB::get_current();
		return $db->create_count_cmd($cmd);
	}

	##
	# = public static array get_tables_list()
	# Wrapper to db driver
	##
	public static function get_tables_list() {
		$db =& SDB::get_current();
		return $db->get_tables_list();
	}

	##
	# = public static array get_table_columns(string $table)
	# Wrapper to db driver
	##
	public static function get_table_columns($table) {
		$db =& SDB::get_current();
		return $db->get_table_columns($table);
	}

	##
	# = public static void reset_cached_data()
	# Wrapper to db driver
	##
	public static function reset_cached_data() {
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
	protected $db = null;

	##
	# [$command] SQL query
	##
	public $command = '';

	public $_prepared_command = null;

	public $_params = array();
	public $_limit = array();

	##
	# = public void __construct(string $command='', SDBBase $db=null)
	##
	public function __construct($command='', $db=null)
	{
		$this->command = $command;
		$this->db = ($db!==null ? $db : SDB::get_current());
	}

	##
	# = public void set(string $name, mixed $value)
	# Set existing command parameter
	##
	public function set($name, $value)
	{
		if (!array_key_exists($name, $this->_params)) error("Parameter '$name' not found");
		$this->_params[$name]['v'] = $value;
	}

	##
	# = public void add(string $name, int $type=0, mixed $value=null, int $size=255)
	# Add new command parameter
	##
	public function add($name, $type=0, $value=null, $size=255)
	{
		if (array_key_exists($name, $this->_params)) error("Parameter '$name' already added");

		$pr = array();
		$pr['t'] = $type;
		$pr['s'] = $size;
		$pr['v'] = $value;

		$this->_params[$name] = $pr;
	}

	##
	# = public void set_limit(int $from_or_count, int $count=null)
	##
	public function set_limit($from_or_count, $count=null) {
		$this->_limit = ($count!==null ? array($from_or_count, $count) : array($from_or_count));
	}

	##
	# = public void execute()
	##
	public function execute() { return $this->db->execute($this); }

	##
	# = public int insert()
	##
	public function insert() { return $this->db->insert($this); }

	##
	# = public array get_all()
	##
	public function get_all() { return $this->db->get_all($this); }

	##
	# = public mixed get_row()
	##
	public function get_row() { return $this->db->get_row($this); }

	##
	# = public mixed get_one()
	##
	public function get_one() { return $this->db->get_one($this); }
}
##
# .end
##
