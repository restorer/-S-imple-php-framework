<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Base entity class
 */

##
# .begin
# = class SEntity
##
class SEntity
{
	##
	# = protected $id
	# [$id] **don't use it!** use **set_id()** and **get_id()** instead.
	##
	var $id = null;

	##
	# = void set_id(mixed $id)
	##
	function set_id($id) {
		if ($this->id === null) {
			$this->id = $id;
		}
		else error('Readonly field');
	}

	##
	# = mixed get_id()
	##
	function get_id() {
		return $this->id;
	}
}
##
# .end
##

?>