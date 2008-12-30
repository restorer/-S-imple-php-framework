<?php

conf_set('debug', array_key_exists('gimmedebug', $_GET));
// conf_set('debug', true);
// conf_set('log_errors', true);

conf_set('sitename', 'zame-dev.org');
conf_set('domain', 'localhost');
conf_set('http.root', '/simple/doc/');

/*
conf_set('db.type', 'mysql');
conf_set('db.host', '127.0.0.1:3306');
conf_set('db.user', 'database-user');
conf_set('db.pass', 'database-pass');
conf_set('db.name', 'some-database');
*/

conf_set('db.type', 'sqlite');
conf_set('db.name', '~cache/simple.sqlite');

/* conf_set('db.prefix', 'table_prefix_'); */
/* conf_set('page.show_vars', true); */

conf_set('modules.autoload', array(
	'db',
	'web'
));
