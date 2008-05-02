<?php
require_once('s/s.php');
?>
<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">
	<title>Simpliest ever example at <?php echo conf('sitename'); ?></title>
	<style>
body { font-family: Tahoma, Arial; font-size: 0.8em; }
	</style>
</head>
<body>
	<h1>Simpliest ever example at <span style="color:#800;"><?php echo conf('sitename'); ?></span></h1>
	<p>
		This example use only few core functions.<br />
		<a href="?<?php echo InGET('index') ? ('index='._GET('index').'&') : ''; ?>gimmedebug=1">Enable debug info</a>
		|
		<a href="?index=0&amp;gimmedebug=1">Hack A</a>
		|
		<a href="?index=10&amp;gimmedebug=1">Hack B</a>
		|
		<a href="?index=42&amp;gimmedebug=1">Magic error</a><br />
	</p>
	<strong>.:
<?php

$index = _GET('index', 1);
dwrite('[Start]');

if ($index == 42) error('Magic found');

if ($index < 1)
{
	$index = 1;
	dwrite('Index out of bound (less than 1)', S_ERROR);
}

if ($index > 9)
{
	$index = 9;
	dwrite('Index out of bound (more than 9)', S_ERROR);
}

for ($i = 1; $i <= 9; $i++)
{
	if ($i == $index) {
		echo "[$i]";
	} else {
		if (InGET('gimmedebug')) {
			echo '&nbsp;<a href="?index='.$i.'&amp;gimmedebug=1">'.$i.'</a>&nbsp;';
		} else {
			echo '&nbsp;<a href="?index='.$i.'">'.$i.'</a>&nbsp;';
		}
	}
}

?>
	:.</strong>
<?php
dwrite('[End]');
if (DEBUG) dflush();
?>
</body>
</html>