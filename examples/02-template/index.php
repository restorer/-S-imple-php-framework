<?php

require_once('s/s.php');

$tpl =& new STemplate();

$tpl->vars['show_login_box'] = _SESSION('show_login_box', true);
$tpl->vars['login_error'] = '';
$tpl->vars['user_name'] = _SESSION('user_name');

if (InPOST('try_it'))
{
	if (_POST('login')!='test' || _POST('password')!='12345')
	{
		$tpl->vars['login_error'] = 'Invalid credentials. Try test/12345';
	}
	else
	{
		$_SESSION['user_name'] = 'Test test';
		$_SESSION['show_login_box'] = false;

		header('Location: ?' . (InGET('gimmedebug') ? 'gimmedebug=1' : ''));
		return;
	}
}

if (InPOST('try_again'))
{
	$_SESSION['show_login_box'] = true;

	header('Location: ?' . (InGET('gimmedebug') ? 'gimmedebug=1' : ''));
	return;
}

echo $tpl->process(BASE.'index.tpl');
dflush();
