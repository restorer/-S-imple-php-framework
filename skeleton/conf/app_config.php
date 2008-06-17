<?php

conf_set('debug', array_key_exists('gimmedebug', $_GET));
/* conf_set('debug', false); */

conf_set('sitename', 'zame-dev.org');
conf_set('domain', 'localhost');
conf_set('http.root', '/simple/');

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
conf_set('db.name', '~cache/simple.sqlite');

/* conf_set('db.prefix', 'table_prefix_'); */
/* conf_set('page.show_vars', true); */

conf_set('mail.send', true);
/*
conf_set('mail.smtp.enable', true);
conf_set('mail.smtp.host', 'smtp.gmail.com');
conf_set('mail.smtp.port', 465);
conf_set('mail.smtp.ssl', true);
conf_set('mail.smtp.user', 'username');
conf_set('mail.smtp.pass', 'password');
*/

conf_set('cookie.domain', '/');
conf_set('log.path', '~debug.log');

conf_set('modules.autoload', array(
	'db',
	'web'
));
