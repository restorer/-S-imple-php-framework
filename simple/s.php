<?php

/*
 * MIT License (http://www.opensource.org/licenses/mit-license.php)
 *
 * Copyright (c) 2007, Slava Tretyak (aka restorer)
 * Zame Software Development (http://zame-dev.org)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * [S]imple framework
 * Main file
 */

##
# % **[S]imple** framework starts here :)
##

if (!defined('BASE')) define('BASE', str_replace('\\', '/', substr(__FILE__, 0, strlen(__FILE__) - strlen('s/s.php'))));	// '
if (!defined('S_BASE')) define('S_BASE', BASE.'s/');

require_once(S_BASE.'core/configuration.php');
require_once(BASE.'conf/app_config.php');

if (!defined('PATH_SEPARATOR')) {
	define('PATH_SEPARATOR', strtoupper(substr(PHP_OS, 0, 3) == 'WIN') ? ';' : ':');
}

function expand_tilde($path) {
	return ($path{0}=='~' ? BASE.substr($path, 1) : $path);
}

if (!conf_has('debug')) conf_set('debug', true);
if (!conf_has('log_errors')) conf_set('log_errors', false);
if (!conf_has('log_debug_info')) conf_set('log_debug_info', false);
if (!conf_has('show_debug_info')) conf_set('show_debug_info', true);
if (!conf_has('modules.autoload')) conf_set('modules.autoload', array());

if (!conf_has('log.path')) conf_set('log.path', '~cache/debug.log');
conf_set('log.path', expand_tilde(conf('log.path')));

define('DEBUG', conf('debug'));
define('LOG_ERRORS', conf('log_errors'));
define('LOG_DEBUG_INFO', conf('log_debug_info'));
define('SHOW_DEBUG_INFO', conf('show_debug_info'));

if (!conf_has('use_cgi')) conf_set('use_cgi', true);
if (!conf_has('http.port')) conf_set('http.port', 80);
if (!conf_has('ssl.port')) conf_set('ssl.port', 443);
if (!conf_has('ssl.root')) conf_set('ssl.root', conf('http.root'));
if (!conf_has('cookie.domain')) conf_set('cookie.domain', '/');
if (!conf_has('db.prefix')) conf_set('db.prefix', '');
if (!conf_has('format.date')) conf_set('format.date', 'd.m.Y');
if (!conf_has('format.datetime')) conf_set('format.datetime', 'd.m.Y H:i');
if (!conf_has('regexp.date')) conf_set('regexp.date', '/^(\d\d).(\d\d).(\d\d\d\d)$/');
if (!conf_has('regexp.datetime')) conf_set('regexp.datetime', '/^(\d\d).(\d\d).(\d\d\d\d).(\d\d):(\d\d)(:(\d\d))?$/');
if (!conf_has('cache.path')) conf_set('cache.path', BASE.'cache/');
if (!conf_has('set_utf8')) conf_set('set_utf8', true);
if (!conf_has('page.tpl.custom')) conf_set('page.tpl.custom', '');
if (!conf_has('page.tpl.base')) conf_set('page.tpl.base', BASE . 'templates/');

if (!conf_has('mail.send')) conf_set('mail.send', false);
if (!conf_has('mail.type')) conf_set('mail.type', 'mail');
if (!conf_has('mail.sendmail.path')) conf_set('mail.sendmail.path', '/var/qmail/bin/sendmail');
if (!conf_has('mail.smtp.port')) conf_set('mail.smtp.port', 25);
if (!conf_has('mail.smtp.ssl')) conf_set('mail.smtp.ssl', true);
if (!conf_has('mail.smtp.timeout')) conf_set('mail.smtp.timeout', 30);

require_once(S_BASE.'core/functions.php');
$modules = conf('modules.autoload');

if (conf('use_cgi'))
{
	require_once(S_BASE . 'web/cgi.php');
	SCGI::init();
}

foreach ($modules as $name)
{
	if (strpos($name, '/') !== false) { require_once(S_BASE . $name . '.php'); }
	else { require_once(S_BASE . $name . '/all.php'); }
}

if (conf('set_utf8')) {
	mb_internal_encoding('UTF-8');
}
