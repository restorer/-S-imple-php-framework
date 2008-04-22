<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Main file
 */

##
# % **[S]imple** framework starts here :)
##

if (!defined('BASE')) define('BASE', str_replace('\\', '/', substr(__FILE__, 0, strlen(__FILE__) - strlen('s/s.php'))));
if (!defined('S_BASE')) define('S_BASE', BASE.'s/');

require_once(S_BASE.'core/configuration.php');
require_once(BASE.'conf/app_config.php');

if (!defined('PATH_SEPARATOR')) {
	define('PATH_SEPARATOR', strtoupper(substr(PHP_OS, 0, 3) == 'WIN') ? ';' : ':');
}

function expand_tilde($path) {
	return ($path{0}=='~' ? BASE.substr($path, 1) : $path);
}

if (!conf_has('http.port')) conf_set('http.port', 80);
if (!conf_has('ssl.port')) conf_set('ssl.port', 443);
if (!conf_has('ssl.root')) conf_set('ssl.root', conf('http.root'));
if (!conf_has('db.prefix')) conf_set('db.prefix', '');
if (!conf_has('format.date')) conf_set('format.date', 'd.m.Y');
if (!conf_has('format.datetime')) conf_set('format.datetime', 'd.m.Y H:i');
if (!conf_has('regexp.date')) conf_set('regexp.date', '/^(\d\d).(\d\d).(\d\d\d\d)$/');
if (!conf_has('regexp.datetime')) conf_set('regexp.datetime', '/^(\d\d).(\d\d).(\d\d\d\d).(\d\d):(\d\d)(:(\d\d))?$/');

conf_set('log.path', expand_tilde(conf('log.path')));

define('ROOT', conf('http.root'));
define('SSL_ROOT', conf('ssl.root'));
define('DEBUG', conf('debug'));

$s_runconf->set('time.sql.query', 0);
$s_runconf->set('time.sql.parse', 0);
$s_runconf->set('time.template', 0);

require_once(S_BASE.'core/functions.php');

if (conf_has('modules.autoload'))
{
	$arr = conf('modules.autoload');

	foreach ($arr as $name)
	{
		if (strpos($name, '/') !== false) { require_once(S_BASE.$name.'.php'); }
		else { require_once(S_BASE.$name.'/all.php'); }
	}
}

?>