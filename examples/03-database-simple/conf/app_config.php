<?php

conf_set('debug', true);

conf_set('sitename', 'zame-dev.org');
conf_set('domain', 'localhost');
conf_set('http.root', '/simple/examples/03-database-simple');

/* conf_set('http.port', 80); */
/* conf_set('ssl.root', '/php-framework/'); */
/* conf_set('ssl.port', 443); */

/*
conf_set('db.type', 'mysql');
conf_set('db.host', '127.0.0.1:3306');
conf_set('db.user', 'database-user');
conf_set('db.pass', 'database-pass');
conf_set('db.name', 'some-database');
*/

conf_set('db.type', 'sqlite');
conf_set('db.name', '~../common/examples.sqlite');

/* conf_set('db.prefix', 'table_prefix_'); */
/* conf_set('page.show_vars', true); */

conf_set('cookie.domain', '/');
conf_set('mail.send', false);
conf_set('log.path', '~debug.log');

conf_set('modules.autoload', array(
	'db/db'
));

?>