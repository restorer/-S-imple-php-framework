<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Database record. Implements [ more or less ;) ] ActiveRecord pattern (best implementation of ActiveRecord (as I know) is in Ruby On Rails)
 */

require_once(S_BASE.'data/entity.php');
require_once(S_BASE.'db/db.php');

define('SRECORD_RELATION_BELONGS_TO', 1);
define('SRECORD_RELATION_HAS_MANY', 2);
define('SRECORD_RELATION_HAS_ONE', 3);

define('SRECORD_FILTER_AFTER_LOAD', 1);
define('SRECORD_FILTER_BEFORE_SAVE', 2);
define('SRECORD_FILTER_AFTER_SAVE', 3);
define('SRECORD_FILTER_BEFORE_REMOVE', 4);
define('SRECORD_FILTER_AFTER_REMOVE', 5);

##
# .begin
# = class SRecord
##
class SRecord extends SEntity
{
	protected $_db_key = 'id';
	protected $_db_table = '';
	protected $_db_fields = array();
	protected $_filters = array();
	protected $_relations = array();
	protected $_rel_objects = array();

	##
	# **Using search conditions:**
	# 1) Condition string, like **'name IS NULL'**.
	# 2) Condition array, **array(<condition>, <value>)**. Example: **array('number=', 42)**
	# 3) Several conditions, **array(<condition array 1 | condition string 1>, <condition array 2 | condition string 2>, ...)**
	##

	##
	# **Using order:**
	# 1) String like **'name'** or **'+name'** - ASC sorting
	# 2) String like **'-name'** - DESC sorting
	# 3) Several orders, **array(<string 1>, <string 2>, ...)**
	##

	##
	# = void __construct(bool $auto_init=true)
	# [$auto_init] Auto initialize table name and fields
	# Don't forget to call parent constructor in your class
	##
	function __construct($auto_init=true)
	{
		$this->_filters[SRECORD_FILTER_AFTER_LOAD] = array();
		$this->_filters[SRECORD_FILTER_BEFORE_SAVE] = array();
		$this->_filters[SRECORD_FILTER_AFTER_SAVE] = array();
		$this->_filters[SRECORD_FILTER_BEFORE_REMOVE] = array();
		$this->_filters[SRECORD_FILTER_AFTER_REMOVE] = array();

		if ($auto_init) $this->_init();
	}

	protected function _invert_compare($compare)
	{
		switch ($compare)
		{
			case '>':
				return '<';

			case '<':
				return '>';

			case '>=':
				return '<=';

			case '<=':
				return '>=';

			default:
				return $compare;
		}
	}

	protected function _load_rel($fieldname)
	{
		if ($this->is_new()) throw new Exception("Can't load related object for new record");

		$rel = $this->_relations[$fieldname];
		$res_cond = array();

		$cond = $rel['c'];
		if (!is_array($cond)) $cond = array($cond);

		foreach ($cond as $cn)
		{
			if (is_array($cn))
			{
				$res_cond[] = $cn;
			}
			else
			{
				$cn = trim($cn);

				if (preg_match("/^:\s*([A-Za-z0-9_]+)\s*([^A-Za-z0-9_\s]+)\s*([A-Za-z0-9_]+)$/", $cn, $mt))
				{
					$thisfield = $mt[1];
					$res_cond = array($mt[3] . $this->_invert_compare($mt[2]), $this->$thisfield);
				}
				elseif (preg_match("/^([A-Za-z0-9_]+)\s*([^A-Za-z0-9_\s]+)\s*:\s*([A-Za-z0-9_]+)$/", $cn, $mt))
				{
					$thisfield = $mt[3];
					$res_cond = array($mt[1] . $mt[2], $this->$thisfield);
				}
				else
				{
					$res_cond[] = $cn;
				}
			}
		}

		if ($rel['t']==SRECORD_RELATION_BELONGS_TO || $rel['t']==SRECORD_RELATION_HAS_ONE)
		{
			eval("\$tmp=new {$rel['o']}();");

			if ($tmp->find($res_cond)) {
				$this->_rel_objects[$fieldname] = $tmp;
			} else {
				$this->_rel_objects[$fieldname] = null;
			}
		}
		else
		{
			$tmp = self::find_all($rel['o'], $res_cond, $rel['ord']);
			$this->_rel_objects[$fieldname] = $tmp;
		}
	}

	protected function _get_($name)
	{
		if (array_key_exists($name, $this->_relations))
		{
			if (!array_key_exists($name, $this->_rel_objects)) $this->_load_rel($name);
			return $this->_rel_objects[$name];
		}

		throw new Exception("Property $name not found");
	}

	##
	# = protected void belongs_to(string $fieldname, string $objname, mixed $cond)
	# [$fieldname] Field name
	# [$objname] Name of related class
	# [$cond] Relation conditions. If condition starts with ':', this object field used, otherwise used field in related object
	# Add belongs_to relation
	# | $this->belongs_to('item', 'SomeItem', ':item_id=id');
	##
	protected function belongs_to($fieldname, $objname, $cond)
	{
		$this->_relations[$fieldname] = array('t'=>SRECORD_RELATION_BELONGS_TO, 'o'=>$objname, 'c'=>$cond);
	}

	##
	# = protected void has_many(string $fieldname, string $objname, mixed $cond, mixed $order=null)
	# [$fieldname] Field name
	# [$objname] Name of related class
	# [$cond] Relation conditions. If condition starts with ':', this object field used, otherwise used field in related object
	# [$order] Order results
	# Add has_many relation
	# | $this->has_many('another_items', 'AnotherItem', ':id=main_object_id');
	##
	protected function has_many($fieldname, $objname, $cond, $order=null)
	{
		$this->_relations[$fieldname] = array('t'=>SRECORD_RELATION_HAS_MANY, 'o'=>$objname, 'c'=>$cond, 'ord'=>$order);
	}

	##
	# = protected void has_one(string $fieldname, string $objname, mixed $cond)
	# [$fieldname] Field name
	# [$objname] Name of related class
	# [$cond] Relation conditions. If condition starts with ':', this object field used, otherwise used field in related object
	# Add has_one relation
	# | $this->has_one('item', 'SomeItem', ':item_id=id');
	##
	protected function has_one($fieldname, $objname, $cond)
	{
		$this->_relations[$fieldname] = array('t'=>SRECORD_RELATION_HAS_ONE, 'o'=>$objname, 'c'=>$cond);
	}

	##
	# = protected void after_load_filter(string $method_name)
	# Adds "after load" filter
	##
	protected function after_load_filter($method_name)
	{
		$this->_filters[SRECORD_FILTER_AFTER_LOAD][] = $method_name;
	}

	##
	# = protected void before_save_filter(string $method_name)
	# Adds "before save" filter
	##
	protected function before_save_filter($method_name)
	{
		$this->_filters[SRECORD_FILTER_BEFORE_SAVE][] = $method_name;
	}

	##
	# = protected void after_save_filter(string $method_name)
	# Adds "after save" filter
	##
	protected function after_save_filter($method_name)
	{
		$this->_filters[SRECORD_FILTER_AFTER_SAVE][] = $method_name;
	}

	##
	# = protected void before_remove_filter(string $method_name)
	# Adds "before remove" filter
	##
	protected function before_remove_filter($method_name)
	{
		$this->_filters[SRECORD_FILTER_BEFORE_REMOVE][] = $method_name;
	}

	##
	# = protected void after_remove_filter(string $method_name)
	# Adds "after remove" filter
	##
	protected function after_remove_filter($method_name)
	{
		$this->_filters[SRECORD_FILTER_AFTER_REMOVE][] = $method_name;
	}

	##
	# = protected void map_key(string $keyname)
	# Usually you don't need to call this function manually (use **$auto_init** instead)
	##
	protected function map_key($keyname)
	{
		$this->_db_key = $keyname;
	}

	##
	# = protected void map_table(string $table)
	# Map object to table in database. Usually you don't need to call this function manually (use **$auto_init** instead)
	##
	protected function map_table($table)
	{
		$this->_db_table = $table;
	}

	##
	# = protected void map_field(string $field, string $db_field, int $db_type, int $db_size)
	# Map object field to database field. Usually you don't need to call this function manually (use **$auto_init** instead)
	##
	protected function map_field($field, $db_field, $db_type, $db_size)
	{
		$this->_db_fields[$field] = array('f'=>$db_field, 't'=>$db_type, 's'=>$db_size);
	}

	protected function _fuzzy_find_table($classname, $sclsname, $tables)
	{
		foreach ($tables as $table_name)
		{
			$name = str_replace('-', '', str_replace('_', '', strtolower($table_name)));

			if ($classname==$name || $classname.'s'==$name || $sclsname.'ies'==$name)
			{
				$this->map_table($table_name);
				return true;
			}
		}

		return false;
	}

	protected function _init_table()
	{
		if (strlen($this->_db_table)) return;

		$classname = strtolower(get_class($this));
		$tables = SDB::get_tables_list();

		$sclsname = substr($classname, 0, -1);

		if (in_array($classname, $tables)) {
			$this->map_table($classname);
		} elseif (in_array($classname.'s', $tables)) {
			$this->map_table($classname.'s');
		} elseif (in_array($sclsname.'ies', $tables)) {
			$this->map_table($sclsname.'ies');
		} elseif (!$this->_fuzzy_find_table($classname, $sclsname, $tables)) {
			throw new Exception("Tables '$classname' ('${classname}s', '${sclsname}ies') doesn't exists. Use map_table to set table manually.");
		}
	}

	protected function _init_fields()
	{
		if (count($this->_db_fields)) return;
		$this->_init_table();

		$cols = SDB::get_table_columns($this->_db_table);

		foreach (get_object_vars($this) as $prop=>$val)
		{
			if ($prop{0}=='_' || $prop==$this->_db_key || is_array($val) || is_object($val) || $val===null) continue;
			if (!array_key_exists($prop, $cols)) throw new Exception("Table '{$this->_db_table}' doesn't have field '$prop'");
			$this->map_field($prop, $prop, $cols[$prop]['t'], $cols[$prop]['s']);
		}

		// map _db_key here, because get_object_vars not returns virtual properties
		$this->map_field($this->_db_key, $this->_db_key, $cols[$this->_db_key]['t'], $cols[$this->_db_key]['s']);
	}

	protected function _init()
	{
		$this->_init_fields();
	}

	protected function _process_filters($type)
	{
		foreach ($this->_filters[$type] as $method_name) {
			call_user_func(array($this, $method_name));
		}
	}

	##
	# = public bool is_new()
	##
	public function is_new()
	{
		$keyname = $this->_db_key;
		return ($this->$keyname === null);
	}

	protected function _get_where_string($conditions)
	{
		if (is_array($conditions) && count($conditions))
		{
			$res = array();
			if (preg_match("/[<>=]\s*$/i", $conditions[0])) $conditions = array($conditions);

			foreach ($conditions as $item)
			{
				if (!is_array($item))
				{
					$res[] = trim($item);
				}
				else
				{
					if (!preg_match("/^\s*([A-Za-z0-9_]+)\s*([^A-Za-z0-9_\s]+)/", $item[0], $mt)) {
						throw new Exception("Invalid condition \"{$item[0]}\"");
					}

					$fld = $mt[1];
					if (!array_key_exists($fld, $this->_db_fields)) throw new Exception("Unknown field \"$fld\"");

					$res[] = "@_f_{$fld}{$mt[2]}@{$fld}";
				}

				return (' WHERE ' . join(' AND ', $res));
			}

			return;
		}
		elseif (is_string($conditions))
		{
			return (' WHERE ' . trim($conditions));
		}

		return '';
	}

	protected function _bind_where($cmd, $conditions)
	{
		if (!is_array($conditions) || !count($conditions)) return;
		if (preg_match("/[<>=]\s*$/i", $conditions[0])) $conditions = array($conditions);

		foreach ($conditions as $item)
		{
			if (!is_array($item)) continue;
			if (!preg_match("/^\s*([A-Za-z0-9_]+)/", $item[0], $mt)) throw new Exception("Invalid condition \"{item[0]}\"");

			$fld = $mt[1];
			$val = $item[1];

			$cmd->set("_f_{$fld}", $this->_db_fields[$fld]['f'], SDB::FieldName);
			$cmd->set($fld, $val, $this->_db_fields[$fld]['t'], $this->_db_fields[$fld]['s']);
		}
	}

	protected function _get_order_string($order)
	{
		if (is_string($order)) {
			$order = array($order);
		}

		if (is_array($order) && count($order))
		{
			$res = array();

			foreach ($order as $item)
			{
				$item = trim($item);
				if (!strlen($item)) continue;

				$suff = '';

				if ($item{0} == '+') {
					$item = substr($item, 1);
				} elseif ($item{0} == '-') {
					$item = substr($item, 1);
					$suff = ' DESC';
				}

				if (!array_key_exists($item, $this->_db_fields)) throw new Exception("Unknown field \"$fld\"");
				$res[] = "@_fo_{$item}{$suff}";
			}

			return (count($res) ? (' ORDER BY ' . join(',', $res)) : '');
		}

		return '';
	}

	protected function _bind_order($cmd, $order)
	{
		if (is_string($order)) {
			$order = array($order);
		}

		if (is_array($order) && count($order))
		{
			foreach ($order as $item)
			{
				$item = trim($item);
				if (!strlen($item)) continue;

				if ($item{0}=='-' || $item{0}=='+') {
					$item = substr($item, 1);
				}

				$cmd->set("_fo_{$item}", $this->_db_fields[$item]['f'], SDB::FieldName);
			}
		}
	}

	##
	# = public bool find_by_cmd(SDBCommand $cmd)
	# Find object by sql command. Return **true** when object is found, **false** otherwise
	##
	public function find_by_cmd($cmd)
	{
		$this->_init();

		$row = $cmd->get_row();
		if ($row === null) return false;

		foreach ($this->_db_fields as $prop=>$dummy) {
			$this->$prop = $row[$prop];
		}

		$this->_process_filters(SRECORD_FILTER_AFTER_LOAD);
		return true;
	}

	##
	# = public bool find(mixed $conditions)
	# Find object using conditions. Return **true** when object is found, **false** otherwise
	##
	public function find($conditions)
	{
		$this->_init();

		$cmd = new SDBCommand("SELECT * FROM @_db_table" . $this->_get_where_string($conditions));
		$cmd->set('_db_table', $this->_db_table, SDB::TableName);
		$this->_bind_where($cmd, $conditions);
		$cmd->limit(1);

		return $this->find_by_cmd($cmd);
	}

	##
	# = public bool find_by_id(int $id)
	# Find object by id. Return **true** when object is found, **false** otherwise
	##
	public function find_by_id($id)
	{
		$this->_init();

		$cmd = new SDBCommand("SELECT * FROM @_db_table WHERE @_f_id=@id");
		$cmd->set('_db_table', $this->_db_table, SDB::TableName);
		$cmd->set('_f_id', $this->_db_key, SDB::FieldName);
		$cmd->set('id', $id, SDB::Int);

		return $this->find_by_cmd($cmd);
	}

	##
	# = public void save()
	# Save object. Related objects will **not** saved
	# **TODO:** Save related objects automatically (has_one and has_many relations)
	##
	public function save()
	{
		$this->_init();
		$this->_process_filters(SRECORD_FILTER_BEFORE_SAVE);

		if ($this->is_new())
		{
			$fields = '';
			$values = '';

			foreach ($this->_db_fields as $k=>$dummy)
			{
				if ($k == $this->_db_key) continue;

				$fields .= ($fields=='' ? '' : ',') . "@_f_{$k}";
				$values .= ($values=='' ? '' : ',') . "@{$k}";
			}

			$cmd = new SDBCommand("INSERT INTO @_db_table ($fields) VALUES ($values)");
			$cmd->set('_db_table', $this->_db_table, SDB::TableName);

			foreach ($this->_db_fields as $k=>$ts)
			{
				if ($k == $this->_db_key) continue;

				$cmd->set("_f_{$k}", $ts['f'], SDB::FieldName);
				$cmd->set($k, $this->$k, $ts['t'], $ts['s']);
			}

			$keyname = $this->_db_key;
			$this->$keyname = $cmd->insert();
		}
		else
		{
			$fields = '';
			$keyname = $this->_db_key;

			foreach ($this->_db_fields as $k=>$dummy)
			{
				if ($k == $keyname) continue;
				$fields .= ($fields=='' ? '' : ',') . "@_f_{$k}=@_v_{$k}";
			}

			$cmd = new SDBCommand("UPDATE @_db_table SET $fields WHERE @_fk_id=@_k_id");
			$cmd->set('_db_table', $this->_db_table, SDB::TableName);
			$cmd->set('_fk_id', $this->_db_key, SDB::FieldName);
			$cmd->set('_k_id', $this->$keyname, SDB::Int);

			foreach ($this->_db_fields as $k=>$ts)
			{
				if ($k == $keyname) continue;

				$cmd->set("_f_{$k}", $ts['f'], SDB::FieldName);
				$cmd->set("_v_{$k}", $this->$k, $ts['t'], $ts['s']);
			}

			$cmd->execute();
		}

		$this->_process_filters(SRECORD_FILTER_AFTER_SAVE);
	}

	##
	# = public void remove()
	# Remove this object from database. Related objects will **not** removed.
	##
	public function remove()
	{
		$this->_init();
		$this->_process_filters(SRECORD_FILTER_BEFORE_REMOVE);

		$keyname = $this->_db_key;

		$cmd = new SDBCommand("DELETE FROM @_db_table WHERE @_f_id=@id");
		$cmd->add('_db_table', $this->_db_table, SDB::TableName);
		$cmd->add('_f_id', $this->_db_key, SDB::FieldName);
		$cmd->add('id', $this->$keyname, SDB::Int);

		$cmd->execute();
		$this->_process_filters(SRECORD_FILTER_AFTER_REMOVE);
	}

	##
	# = public static array find_all_by_cmd(string $classname, SDBCommand $cmd)
	# Find all objects using sql command.
	# | $res = SRecord::find_all_by_cmd('SomeItem', $cmd)
	##
	public static function find_all_by_cmd($classname, $cmd)
	{
		$arr = $cmd->get_all();
		$res = array();

		foreach ($arr as $row)
		{
			eval("\$obj=new {$classname}();");
			$obj->_init();

			foreach ($obj->_db_fields as $prop=>$ts) {
				$obj->$prop = $row[$ts['f']];
			}

			$obj->_process_filters(SRECORD_FILTER_AFTER_LOAD);
			$res[] = $obj;
		}

		return $res;
	}

	##
	# = public static array find_all(string $classname, mixed $conditions=null, mixed $order=null, mixed $limit=null)
	# Find all objects using search conditions
	# | $res = SRecord::find_all('SomeItem', array('login=', 'qwert'), '-registration_date')
	##
	public static function find_all($classname, $conditions=null, $order=null, $limit=null)
	{
		eval("\$tmp=new {$classname}();");
		$tmp->_init();

		$cmd = new SDBCommand("SELECT * FROM @_db_table" . $tmp->_get_where_string($conditions) . $tmp->_get_order_string($order));
		$cmd->set('_db_table', $tmp->_db_table, SDB::TableName);
		$tmp->_bind_where($cmd, $conditions);
		$tmp->_bind_order($cmd, $order);

		if (is_numeric($limit)) $cmd->limit($limit);
		elseif (is_array($limit)) $cmd->_limit = $limit;

		return self::find_all_by_cmd($classname, $cmd);
	}

	##
	# = public static int get_count(string $classname, mixed $conditions=null)
	# | $res = SRecord::get_count('SomeItem', array('login=', 'qwert'))
	##
	public static function get_count($classname, $conditions=null)
	{
		eval("\$tmp=new {$classname}();");
		$tmp->_init();

		$cmd = new SDBCommand("SELECT COUNT(*) FROM @_db_table" . $tmp->_get_where_string($conditions));
		$cmd->set('_db_table', $tmp->_db_table, SDB::TableName);
		$tmp->_bind_where($cmd, $conditions);

		return $cmd->get_one();
	}

	##
	# = public static void remove_all(string $classname, midex $conditions=null)
	# Remove objects by conditions (or all objects) without retrieving it. Remove filters was **not** called.
	# **TODO:** call remomve filters with id as parameter.
	# | SRecord::remove_all('SomeItem', array('parent_id=', 42))
	##
	public static function remove_all($classname, $conditions=null)
	{
		eval("\$tmp=new {$classname}();");
		$tmp->_init();

		$cmd = new SDBCommand("DELETE FROM @_db_table" . $tmp->_get_where_string($conditions));
		$cmd->set('_db_table', $tmp->_db_table, SDB::TableName);
		$tmp->_bind_where($cmd, $conditions);

		$cmd->execute();
	}

	##
	# = public static void remove_by_id(string $classname, int $id)
	# Remove object by id (without retrieving it). Remove filters was **not** called.
	# **TODO:** call remomve filters with id as parameter.
	# | SRecord::remove_by_id('SomeItem', 42)
	##
	public static function remove_by_id($classname, $id)
	{
		eval("\$tmp=new {$classname}();");
		$tmp->_init();

		$cmd = new SDBCommand("DELETE FROM @_db_table WHERE @_f_id=@id");
		$cmd->set('_db_table', $tmp->_db_table, SDB::TableName);
		$cmd->set('_f_id', $tmp->_db_key, SDB::FieldName);
		$cmd->set('id', $id, SDB::Int);

		$cmd->execute();
	}
}
##
# .end
##
