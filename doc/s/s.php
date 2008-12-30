<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * Main file for apps.
 * When many apps use one framework, place framework into separate folder and rename s.php.apps to s.php
 */

define('BASE', str_replace('\\', '/', substr(__FILE__, 0, strlen(__FILE__) - strlen('s/s.php'))));
define('S_BASE', BASE.'../simple/');
require_once(S_BASE.'s.php');
