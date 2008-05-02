<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Database entity. Implements [ more or less ;) ] ActiveRecord pattern (best implementation of ActiveRecord (as I know) is in Ruby On Rails)
 */

require_once(S_BASE.'data/entity.php');
require_once(S_BASE.'db/db.php');

define('SDBENTITY_RELATION_BELONGS_TO', 1);
define('SDBENTITY_RELATION_HAS_MANY', 2);

##
# .begin
# = class SDBEntity
##
class SDBEntity extends SEntity
{
	var $_db_key = 'id';
	var $_db_table = '';
	var $_db_fields = array();
	var $_db_after_filter = '';
	var $_relations = array();
	var $_rel_objects = array();

	function SDBEntity($auto_init = false)
	{
		$this->__construct($auto_init);
	}

	##
	# = void __construct(bool $auto_init=false)
	# [$auto_init] Auto initialize table name and fields
	# Don't forget to call parent constructor in your class
	##
	function __construct($auto_init=false)
	{
		if ($auto_init) $this->i_init();
	}

	function i_load_rel($fieldname)
	{
		if ($this->is_new()) return;

		$rel = $this->_relations[$fieldname];

		$cond = $rel['c'];
		if (!is_array($cond[0])) $cond = array($cond);

		$res_cond = array();

		foreach ($cond as $cn)
		{
			if ($cn[0]{0} == ':')
			{
				$tf = substr($cn[0], 1);
				$res_cond[] = array($cn[2], $cn[1], $this->$tf);
			}
			elseif ($cn[2]{0} == ':')
			{
				$tf = substr($cn[2], 1);
				$res_cond[] = array($cn[0], $cn[1], $this->$tf);
			}
			else { $res_cond[] = $cn; }
		}

		if ($rel['t'] == SDBENTITY_RELATION_BELONGS_TO)
		{
			eval('$tmp =& new '.$rel['o'].'();');

			if ($tmp->find($res_cond)) {
				$this->_rel_objects[$fieldname] =& $tmp;
			} else {
				$this->_rel_objects[$fieldname] = null;
			}
		}
		else
		{
			$tmp = SDBEntity::find_all($rel['o'], $res_cond, $rel['ord']);
			$this->_rel_objects[$fieldname] = $tmp;
		}
	}

	##
	# = SDBEntity &get_rel(string $key)
	# [$key] Field name
	# Returns related object
	# **TODO:** Do it via getter in php5. **WARN:** Getters work incorrectly in PHP prior to 5.0.4
	# **TODO2:** What about some sort of getters/setters or dynamically adding methods to class in PHP4?
	##
	function &get_rel($key)
	{
		if (!array_key_exists($key, $this->_relations)) error('Unknown field '.$key.' in class '.get_class($this));

		if (!array_key_exists($key, $this->_rel_objects)) $this->i_load_rel($key);
		return $this->_rel_objects[$key];
	}

	##
	# **Using search conditions:**
	# 1) Condition string, like **'type=42'**. **Not recommended**
	# 2) Condition array, **array(<field>, <compare>, <value>)**. Example: **array('type', '=', 42)**
	# 3) Several conditions, **array(<condition array 1>, <condition array 2>, ...)**
	##

	##
	# **Using order:**
	# 1) String, like 'name'. Only ASC sorting ('name DESC' will cause error)
	# 2) Order array, **array(<field>, true|false)**. **true** - ASC, **false** - DESC. Example: **array('name', false)**
	# 3) Several orders, **array(<order array 1|string 1>, <order array 2|string 2>, ...)**
	##

	##
	# = void belongs_to(string $fieldname, string $objname, mixed $cond)
	# [$fieldname] Field name
	# [$objname] Name of related class
	# [$cond] Relation conditions. If condition field starts with ':', this object field used, otherwise used field in related object
	# Add belongs_to relation
	# **TODO:** Add has_one relation (almost equal to belongs_to)
	# | $this->belongs_to('item', 'SomeItem', array(':item_id', '=', 'id'));
	##
	function belongs_to($fieldname, $objname, $cond)
	{
		$this->_relations[$fieldname] = array('t'=>SDBENTITY_RELATION_BELONGS_TO, 'o'=>$objname, 'c'=>$cond);
	}

	##
	# = void has_many(string $fieldname, string $objname, mixed $cond, mixed $order=null)
	# [$fieldname] Field name
	# [$objname] Name of related class
	# [$cond] Relation conditions. If condition field starts with ':', this object field used, otherwise used field in related object
	# [$order] Order results
	# Add has_many relation
	# | $this->has_many('another_items', 'AnotherItem', array(':id', '=', 'main_object_id'));
	##
	function has_many($fieldname, $objname, $cond, $order=null)
	{
		$this->_relations[$fieldname] = array('t'=>SDBENTITY_RELATION_HAS_MANY, 'o'=>$objname, 'c'=>$cond, 'ord'=>$order);
	}

	##
	# = void after_filter(string $method_name)
	# Adds "after load" filter
	##
	function after_filter($method_name)
	{
		$this->_db_after_filter = $method_name;
	}

	function i_fuzzy_find_table($classname, $sclsname, $tables)
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

	function i_init_table()
	{
		if ($this->_db_table != '') return;

		$classname = strtolower(get_class($this));
		$tables = SDB::get_tables_list();

		$sclsname = substr($classname, 0, -1);

		if (in_array($classname, $tables)) {
			$this->map_table($classname);
		} elseif (in_array($classname.'s', $tables)) {
			$this->map_table($classname.'s');
		} elseif (in_array($sclsname.'ies', $tables)) {
			$this->map_table($sclsname.'ies');
		} elseif (!$this->i_fuzzy_find_table($classname, $sclsname, $tables)) {
			error("SDBEntity.i_init_table : tables '$classname' ('${classname}s', '${sclsname}ies') doesn't exists. Use map_table to set table manually.");
		}
	}

	##
	# = void map_field(string $field, string $db_field, int $db_type, int $db_size)
	# Map object field to database field. Usually you don't need to call this function manually (use **$auto_init** instead)
	##
	function map_field($field, $db_field, $db_type, $db_size)
	{
		$this->_db_fields[$field] = array('f'=>$db_field, 't'=>$db_type, 's'=>$db_size);
	}

	function i_init_fields()
	{
		if (count($this->_db_fields)) return;
		$this->i_init_table();

		$cols = SDB::get_table_columns($this->_db_table);

		foreach (get_object_vars($this) as $prop => $val)
		{
			if ($prop{0} == '_') continue;
			if ($prop!=$this->_db_key && (is_array($val) || is_object($val) || $val===null)) continue;
			if (!array_key_exists($prop, $cols)) error('SDBEntity.i_init_fields : table \''.$this->_db_table.'\' doesn\' have field \''.$prop.'\'');
			$this->map_field($prop, $prop, $cols[$prop]['t'], $cols[$prop]['s']);
		}
	}

	function i_init()
	{
		$this->i_init_fields();
	}

	function i_process_after_filter()
	{
		if ($this->_db_after_filter != '') {
			call_user_func(array(&$this, $this->_db_after_filter));
		}
	}

	##
	# = void map_table(string $table)
	# Map object to table in database. Usually you don't need to call this function manually (use **$auto_init** instead)
	##
	function map_table($table)
	{
		$this->_db_table = $table;
	}

	##
	# = bool is_new()
	##
	function is_new()
	{
		return ($this->get_id() === null);
	}

	##
	# = bool find_by_cmd(SDBCommand $cmd)
	# Find object by sql command. Return **true** when object is found, **false** otherwise
	##
	function find_by_cmd($cmd)
	{
		$this->i_init();

		$row = $cmd->get_row();
		if ($row === null) return false;

		foreach ($this->_db_fields as $prop=>$dummy) {
			$this->$prop = $row[$prop];
		}

		$this->i_process_after_filter();
		return true;
	}

	##
	# = bool find(mixed $conditions)
	# Find object using conditions. Return **true** when object is found, **false** otherwise
	##
	function find($conditions)
	{
		$this->i_init();

		$cmd =& new SDBCommand("SELECT * FROM @_db_table WHERE " . $this->i_get_where_string($conditions));
		$cmd->Add('@_db_table', DB_TableName, $this->_db_table);
		$this->i_prepare_command($cmd, $conditions);

		return $this->find_by_cmd($cmd);
	}

	##
	# = bool find_by_id(int $id)
	# Find object by id. Return **true** when object is found, **false** otherwise
	##
	function find_by_id($id)
	{
		$this->i_init();

		$cmd =& new SDBCommand("SELECT * FROM @_db_table WHERE @_f_id=@id");
		$cmd->add('@_db_table', DB_TableName, $this->_db_table);
		$cmd->add('@_f_id', DB_FieldName, $this->_db_key);
		$cmd->add('@id', DB_Int, $id);

		return $this->find_by_cmd($cmd);
	}

	##
	# = void save()
	# Save object. Related objects will **not** saved
	# **TODO:** Save related objects automatically (has_many relation)
	##
	function save()
	{
		$this->i_init();

		if ($this->is_new())
		{
			$fields = '';
			$values = '';

			foreach ($this->_db_fields as $k=>$dummy)
			{
				if ($k == $this->_db_key) continue;

				$fields .= ($fields==''?'':',') . '@_f_'.$k;
				$values .= ($values==''?'':',') . '@'.$k;
			}

			$cmd =& new SDBCommand("INSERT INTO @_db_table ($fields) VALUES ($values)");
			$cmd->add('@_db_table', DB_TableName, $this->_db_table);

			foreach ($this->_db_fields as $k=>$ts)
			{
				if ($k == $this->_db_key) continue;

				$cmd->add('@_f_'.$k, DB_FieldName, $ts['f']);
				$cmd->add('@'.$k, $ts['t'], $this->$k, $ts['s']);
			}

			$this->set_id($cmd->execute());
		}
		else
		{
			$fields = '';

			foreach ($this->_db_fields as $k=>$dummy) {
				if ($k == $this->_db_key) continue;
				$fields .= ($fields==''?'':',') . '@_f_'.$k.'=@'.$k;
			}

			$cmd =& new SDBCommand("UPDATE @_db_table SET $fields WHERE @_f_id=@id");
			$cmd->Add('@_db_table', DB_TableName, $this->_db_table);
			$cmd->add('@_f_id', DB_FieldName, $this->_db_key);
			$cmd->Add('@id', DB_Int, $this->get_id());

			foreach ($this->_db_fields as $k=>$ts)
			{
				if ($k == $this->_db_key) continue;

				$cmd->Add('@_f_'.$k, DB_FieldName, $ts['f']);
				$cmd->Add('@'.$k, $ts['t'], $this->$k, $ts['s']);
			}

			$cmd->execute();
		}
	}

	##
	# = void remove()
	# Remove this object from database. Related objects will **not** removed.
	##
	function remove()
	{
		$this->i_init();

		$cmd =& new SDBCommand("DELETE FROM @_db_table WHERE @_f_id=@id");
		$cmd->add('@_db_table', DB_TableName, $this->_db_table);
		$cmd->add('@_f_id', DB_FieldName, $this->_db_key);
		$cmd->add('@id', DB_Int, $this->get_id());

		$cmd->execute();
	}

	function i_get_where_string($conditions)
	{
		$wh = '';

		if (is_array($conditions) && count($conditions))
		{
			if (!is_array($conditions[0])) $conditions = array(array($conditions[0], $conditions[1], $conditions[2]));

			foreach ($conditions as $cond)
			{
				$fld = $cond[0];
				$expr = $cond[1];

				if (!array_key_exists($fld, $this->_db_fields)) error('SDBEntity.i_get_where_string : unknown field \''.$fld.'\'');
				$wh .= ($wh==''?'':' AND ') . '@_f_'.$fld . $expr . '@'.$fld;
			}
		}
		elseif (is_string($conditions)) { $wh = $conditions; }
		else { error('SDBEntity.i_get_where_string : unknown contitions type'); }

		return $wh;
	}

	function i_prepare_command(&$cmd, $conditions)
	{
		if (is_array($conditions) && count($conditions))
		{
			if (!is_array($conditions[0])) $conditions = array(array($conditions[0], $conditions[1], $conditions[2]));

			foreach ($conditions as $cond)
			{
				$fld = $cond[0];
				$val = $cond[2];

				$cmd->add('@_f_'.$fld, DB_FieldName, $this->_db_fields[$fld]['f']);
				$cmd->add('@'.$fld, $this->_db_fields[$fld]['t'], $val, $this->_db_fields[$fld]['s']);
			}
		}
	}

	function i_get_order_string($order)
	{
		if ($order === null) return '';
		if (is_string($order) && $order=='') return '';
		if (is_array($order) && !count($order)) return '';

		if (is_string($order)) return ' ORDER BY @_fo_'.$order;
		if (!is_array($order)) error('SDBEntity.i_get_order_string : unknown order');

		$res = ' ORDER BY ';

		foreach ($order as $val)
		{
			if (is_string($val)) $res .= '@_fo_'.$val.',';
			elseif (is_array($val)) $res .= '@_fo_'.$val[0].' '.($val[1] ? 'ASC' : 'DESC').',';
			else error('SDBEntity.i_get_order_string : unknown order');
		}

		return substr($res, 0, -1);
	}

	function i_prepare_order(&$cmd, $order)
	{
		if ($order === null) return;
		if (is_string($order) && $order=='') return;
		if (is_array($order) && !count($order)) return;

		if (is_string($order))
		{
			$cmd->add('@_fo_'.$order, DB_FieldName, $this->_db_fields[$order]['f']);
		}
		else	/* is_array */
		{
			foreach ($order as $val)
			{
				if (is_string($val)) $cmd->add('@_fo_'.$val, DB_FieldName, $this->_db_fields[$val]['f']);
				else $cmd->add('@_fo_'.$val[0], DB_FieldName, $this->_db_fields[$val[0]]['f']);		/* is_array */
			}
		}
	}

	##
	# = static array find_all_by_cmd(string $classname, SDBCommand $cmd)
	# Find all objects using sql command.
	# | $res = SDBEntity::find_all_by_cmd('SomeItem', $cmd)
	##
	function find_all_by_cmd($classname, $cmd)
	{
		$arr = $cmd->get_all();
		$result = array();

		foreach ($arr as $row)
		{
			eval('$obj =& new '.$classname.'();');

			foreach ($obj->_db_fields as $prop=>$ts) {
				$obj->$prop = $row[$ts['f']];
			}

			$obj->i_process_after_filter();
			$result[] = $obj;
		}

		return $result;
	}

	##
	# = static array find_all(string $classname, mixed $conditions=null, mixed $order=null)
	# Find all objects using search conditions
	# | $res = SDBEntity::find_all('SomeItem', array('nickname', '=', 'fuck'), array('registration_date', false))
	##
	function find_all($classname, $conditions=null, $order=null)
	{
		eval('$tmp =& new '.$classname.'();');

		if ($conditions === null)
		{
			$cmd =& new SDBCommand("SELECT * FROM @_db_table".$tmp->i_get_order_string($order));
			$cmd->add('@_db_table', DB_TableName, $tmp->_db_table);
			$tmp->i_prepare_order($cmd, $order);
		}
		else
		{
			$cmd =& new SDBCommand("SELECT * FROM @_db_table WHERE " . $tmp->i_get_where_string($conditions) . $tmp->i_get_order_string($order));
			$cmd->add('@_db_table', DB_TableName, $tmp->_db_table);
			$tmp->i_prepare_command($cmd, $conditions);
			$tmp->i_prepare_order($cmd, $order);
		}

		return SDBEntity::find_all_by_cmd($classname, $cmd);
	}

	##
	# = static void remove_by_id(string $classname, int $id)
	# Remove object by id (without retrieving it)
	# | SDBEntity::remove_by_id('SomeItem', 42)
	##
	function remove_by_id($classname, $id)
	{
		eval('$tmp =& new '.$classname.'();');
		$tmp->i_init();

		$cmd =& new SDBCommand("DELETE FROM @_db_table WHERE @_f_id=@id");
		$cmd->Add('@_db_table', DB_TableName, $tmp->_db_table);
		$cmd->add('@_f_id', DB_FieldName, $tmp->_db_key);
		$cmd->Add('@id', DB_Int, $id);

		$cmd->execute();
	}
}
##
# .end
##
