<?php

conf_set('debug', true);

conf_set('sitename', 'zame-dev.org');
conf_set('domain', 'localhost');
conf_set('http.root', '/simple/examples/03-database-simple/');

conf_set('db.type', 'sqlite');
conf_set('db.name', '~../common/cache/examples.sqlite');

conf_set('modules.autoload', array(
	'db/db'
));
