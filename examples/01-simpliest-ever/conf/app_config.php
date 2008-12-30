<?php

conf_set('debug', array_key_exists('gimmedebug', $_GET));
// conf_set('debug', true);

conf_set('sitename', 'zame-dev.org');
conf_set('domain', 'localhost');
conf_set('http.root', '/simple/examples/01-simpliest-ever/');

conf_set('modules.autoload', array(
));
