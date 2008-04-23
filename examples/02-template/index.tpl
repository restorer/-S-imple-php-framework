<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8">
	<title>Templates usage</title>
	<style>
body { font-family: Tahoma, Arial; font-size: 12px; }
table { font-family: Tahoma, Arial; font-size: 12px; }

table.login-box { border: 1px solid #CCC; }
table.login-box th { text-align: left; }

.error { color: red; }
	</style>
</head>
<body>
	<h1>Templates usage</h1>
	<p><a href="?gimmedebug=1">Show debug info</a></p>

	<? if $show_login_box ?>
	<form method="post">
		<table class="login-box" cellspacing="0" cellpadding="5">
			<? if $login_error ?>
			<tr>
				<td colspan="2" align="center" class="error">
					<?# $login_error ?>
				</td>
			</tr>
			<? end ?>
			<tr>
				<th>Login:</th>
				<td><input type="text" name="login" /></td>
			</tr>
			<tr>
				<th>Password:</th>
				<td><input type="password" name="password" /></td>
			</tr>
			<tr>
				<td colspan="2" align="center">
					<input type="submit" name="try_it" value=" Try it " />
				</td>
			</tr>
		</table>
	</form>
	<? else ?>
	<form method="post">
		<p>Hello, <strong><?# $user_name ?></strong>.</p>
		<input type="submit" name="try_again" value=" Try again " />
	</form>
	<? end ?>
</body>
</html>