<?php
function __s_tpl_2e_5f_index_2e_tpl_2e_2f_2e($__t,$__v){foreach($__v as $__k=>$__v) $$__k=$__v;$__s='';
$__s.='<html>'."\n".'<head>'."\n".'	<meta http-equiv="Content-type" content="text/html; charset=utf-8">'."\n".'	<title>Templates usage</title>'."\n".'	<style>'."\n".'body { font-family: Tahoma, Arial; font-size: 12px; }'."\n".'table { font-family: Tahoma, Arial; font-size: 12px; }'."\n".'table.login-box { border: 1px solid #CCC; }'."\n".'table.login-box th { text-align: left; }'."\n".'.error { color: red; }'."\n".'	</style>'."\n".'</head>'."\n".'<body>'."\n".'	<h1>Templates usage</h1>'."\n".'	<p><a href="?gimmedebug=1">Show debug info</a></p>'."\n".'	';
if ($show_login_box) {
$__s.='	<form method="post">'."\n".'		<table class="login-box" cellspacing="0" cellpadding="5">'."\n".'			';
if ($login_error) {
$__s.='			<tr>'."\n".'				<td colspan="2" align="center" class="error">'."\n".'					';
$__s.=htmlspecialchars($login_error);
$__s.='				</td>'."\n".'			</tr>'."\n".'			';
}
$__s.='			<tr>'."\n".'				<th>Login:</th>'."\n".'				<td><input type="text" name="login" /></td>'."\n".'			</tr>'."\n".'			<tr>'."\n".'				<th>Password:</th>'."\n".'				<td><input type="password" name="password" /></td>'."\n".'			</tr>'."\n".'			<tr>'."\n".'				<td colspan="2" align="center">'."\n".'					<input type="submit" name="try_it" value=" Try it " />'."\n".'				</td>'."\n".'			</tr>'."\n".'		</table>'."\n".'	</form>'."\n".'	';
} else {
$__s.='	<form method="post">'."\n".'		<p>Hello, <strong>';
$__s.=htmlspecialchars($user_name);
$__s.='</strong>.</p>'."\n".'		<input type="submit" name="try_again" value=" Try again " />'."\n".'	</form>'."\n".'	';
}
$__s.='</body>'."\n".'</html>';
return $__s;
}
?>