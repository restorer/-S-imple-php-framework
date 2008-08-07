<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * World fastest and simpliest php templates :)
 */

/*
 * TODO: Check what faster:
 *       a) aaa <~php echo $__t->call('xxx') ~> bbb
 *       b) $__s.='aaa'; $__s.=$__t->call('xxx'); $__s.='bbb';
 *       If 'a' if faster, revert template to old model (ob_start / ob_get_contents / ob_end_flush)
 */

define('LBRA', '<'.'?');
define('RBRA', '?'.'>');

##
# .begin
# = class STemplate
##
class STemplate
{
	##
	# [$vars] Template vars
	##
	var $vars = array();

	##
	# [$controls] Template controls
	##
	var $controls = array();

	function i_escape($str)
	{
		if (!strlen($str)) return '\'\'';

		$res = '';
		$spl = explode("\n", $str);

		foreach ($spl as $val) {
			if ($val != '') {
				$res .= '\'' . str_replace('\'', '\\\'', str_replace('\\', '\\\\', $val)) . '\'."\\n".';
			}
		}

		$res = substr($res, 0, -1);
		if ($str{strlen($str)-1} != "\n") $res = substr($res, 0, -5);

		return $res;
	}

	##
	# = void parse(string $in, string $funcname)
	# [$in] Parse from file
	# [$funcname] Wrapper function name
	##
	function parse($in, $funcname)
	{
		$buf = file_get_contents($in);
		$buf = str_replace(chr(0x0D), chr(0x0A), str_replace(chr(0x0A).chr(0x0D), chr(0x0A), str_replace(chr(0x0D).chr(0x0A), chr(0x0A), $buf)));

		$pos = 0;
		$res = '';
		$text = '';

		if ($buf != '')
		{
			$sz = strlen($buf);
			$lbra_sz = strlen(LBRA);
			$rbra_sz = strlen(RBRA);

			for (;;)
			{
				$lb = strpos($buf, LBRA, $pos);

				if ($lb === false)
				{
					if ($pos) $text .= substr($buf, $pos);
					else $text = $buf;

					break;
				}

				$text .= substr($buf, $pos, $lb-$pos);
				$text = $this->i_escape($text);
				if (strlen($text)) { $res .= '$__s.=' . $text . ";\n"; }
				$text = '';

				$pos = $lb + $lbra_sz;

				if ($pos<$sz && $buf{$pos}=='*')
				{
					$pos++;
					$rcom = strpos($buf, '*'.RBRA, $pos);

					if ($rcom !== false)
					{
						$res .= '/* ' . str_replace('/*', ' / * ', str_replace('*/', ' * / ', substr($buf, $pos, $rcom-$pos))) . " */\n";
						$pos = $rcom + 3;
					}

					continue;
				}

				$cnt = '';

				while ($pos < $sz)
				{
					if ($buf{$pos}=='"' || $buf{$pos}=='\'')
					{
						$ch = $buf{$pos};
						$cnt .= $ch;
						$pos++;

						while ($pos<$sz && $buf{$pos}!=$ch)
						{
							if ($buf{$pos} == '\\')
							{
								$cnt .= $buf{$pos};
								$pos++;

								if ($pos < $sz)
								{
									$cnt .= $buf{$pos};
									$pos++;
								}
							}
							else
							{
								$cnt .= $buf{$pos};
								$pos++;
							}
						}

						if ($pos < $sz)
						{
							$cnt .= $ch;
							$pos++;
						}
					}
					elseif (($pos + $rbra_sz <= $sz) && (substr($buf, $pos, $rbra_sz) == RBRA))
					{
						$pos += $rbra_sz;
						break;
					}
					else
					{
						$cnt .= $buf{$pos};
						$pos++;
					}
				}

				$cnt = trim($cnt);

				if ($cnt != '')
				{
					$stat = '/* internal error */';
					$post_process = true;

					if (strtolower($cnt) == 'end')
					{
						$stat = '}';
					}
					elseif (strtolower($cnt) == 'else')
					{
						$stat = '} else {';
					}
					else
					{
						$op = $cnt{0};

						if ($op=='!' || $op=='=' || $op=='#' || $op=='+' || $op=='^')
						{
							$cnt = trim(substr($cnt, 1));

							if ($cnt != '')
							{
								if ($op == '!') { $stat = $cnt.';'; $post_process = false; }
								elseif ($op == '=') $stat = '$__s.=(' . $cnt . ');';
								elseif ($op == '#') $stat = '$__s.=htmlspecialchars(' . $cnt . ');';
								elseif ($op == '+') $stat = '$__s.=urlencode(' . $cnt . ');';
								elseif ($op == '^') $stat = '$__s.=jsencode(' . $cnt .');';
							}
						}
						elseif ($op == '@')
						{
							$cnt = trim(substr($cnt, 1));

							if (preg_match('/^[A-Za-z0-9_]+\s*\(/', $cnt))
							{
								$stat = preg_replace('/^@([A-Za-z0-9_]+)\s*\(\s*\)/', '$__t->call(\'$1\');', '@'.$cnt);
								$stat = preg_replace('/^@([A-Za-z0-9_]+)\s*\((.*)/', '$__t->call(\'$1\',$2;', $stat);
							}
							else
							{
								$spl = explode(' ', $cnt, 2);
								$name = $spl[0];

								if (count($spl) > 1)
								{
									$args = trim($spl[1]);
									if ($args{0} == ':') { $args = 'array('.$args.')'; }
									$stat = '$__s.=$__t->call(\'' . $name . '\',' . $args . ');';
								}
								else { $stat = '$__s.=$__t->call(\'' . $name . '\');'; }
							}
						}
						else
						{
							$i = 1;
							$s = strlen($cnt);

							while ($i<$s && (($cnt{$i}>='A' && $cnt{$i}<='Z') || ($cnt{$i}>='a' && $cnt{$i}<='z') || ($cnt{$i}>='0' && $cnt{$i}<='9')))
							{
								$op .= $cnt{$i};
								$i++;
							}

							$expr = trim(substr($cnt, $i));

							if ($expr != '')
							{
								$op = strtolower($op);

								if ($op=='for' || $op=='foreach' || $op=='if' || $op=='while' || $op=='elseif' || $op=='elsif' || $op=='each')
								{
									if ($expr{0}!='(' || $op=='if' || $op=='while' || $op=='elseif' || $op=='elsif') $expr = '('.$expr.')';

									if ($op=='elseif' || $op=='elsif') $stat = '} elseif '.$expr.' {';
									elseif ($op=='each') $stat = 'foreach '.$expr.' {';
									else $stat = $op.' '.$expr.' {';
								}
								else $stat = $cnt.';';
							}
							else $stat = $cnt.';';
						}
					}

					if ($post_process)
					{
						$stat = preg_replace('/@\s*([A-Za-z_][A-Za-z0-9_]*)\s*\(\s*\)/', '$__t->call(\'$1\')', $stat);
						$stat = preg_replace('/@\s*([A-Za-z_][A-Za-z0-9_]*)\s*\(/', '$__t->call(\'$1\',', $stat);
						$stat = preg_replace('/([ \(,]):([A-Za-z_][A-Za-z0-9_]*)\s*=>/', '$1\'$2\'=>', $stat);
					}

					$res .= $stat . "\n";
				}
			}
		}

		$text = $this->i_escape($text);
		if (strlen($text)) $res .= '$__s.=' . $text . ";\n";

		$res = 'function '.$funcname.'($__t,$__v){foreach($__v as $__k=>$__v) $$__k=$__v;$__s=\'\';'."\n".$res.'return $__s;'."\n}\n";
		return $res;
	}

	function _echo()
	{
		$args = func_get_args();
		foreach ($args as $val) echo $val;
	}

	function call()
	{
		$args = func_get_args();
		$name = array_shift($args);

		switch ($name)
		{
			case 'echo':
				return call_user_func_array(array(&$this, '_echo'), $args);

			default:
				if (!array_key_exists($name, $this->controls)) error("Control $name not found.");
				return call_user_func_array(array(&$this->controls[$name], 'render'), $args);
		}
	}

	function i_generate_funcname($filename)
	{
		$str = '2e_' . strtolower(substr($filename, strlen(BASE))) . './.';
		$prev = false;
		$funcname = '';

		for ($i = 0; $i < strlen($str); $i++)
		{
			$ch = $str{$i};

			if (($ch>='0' && $ch<='9') || ($ch>='a' && $ch<='z'))
			{
				$funcname .= $ch;
				$prev = true;
			}
			else
			{
				if ($prev)
				{
					$funcname .= '_';
					$prev = false;
				}

				$funcname .= sprintf('%02x', ord($ch)).'_';
			}
		}

		if (substr($funcname, -1) == '_') $funcname = substr($funcname, 0, -1);

		$funcname = '__s_tpl_'.$funcname;
		return $funcname;
	}

	##
	# = string process($filename)
	# [$filename] Path to template file
	# Process template, compile it if necessary
	##
	function process($filename)
	{
		global $s_runconf;

		if (DEBUG)
		{
			$st = get_microtime();

			$nested = $s_runconf->get('tpl.nested', array());
			$nested[] = 0;
			$s_runconf->set('tpl.nested', $nested);
		}

		$funcname = $this->i_generate_funcname($filename);

		if (!function_exists($funcname))
		{
			$dir = substr(dirname($filename), strlen(BASE));
			$rdir = conf('cache.path').$dir;
			if ($dir!='' && !is_dir($rdir)) make_directory($rdir);

			if (substr($rdir, -1) != '/') $rdir .= '/';
			$rname = $rdir.basename($filename).'.php';
			$mt = filemtime($filename);
			$mk = true;

			if (!file_exists($rname) || filemtime($rname)<$mt)
			{
				dwrite('Parsing template "'.$rname.'"', S_ACCENT);
				$parsed = $this->parse($filename, $funcname);

				if ($fp = @fopen($rname, 'wb'))
				{
					fwrite($fp, '<'.'?'.'php'."\n" . $parsed);		// closing php tag is not necessary
					fclose($fp);
					chmod($rname, 0777);
				}
				else
				{
					dwrite("Can't write template to \"$rname\"", S_ERROR);
				}

				eval($parsed);
			}
			else
			{
				dwrite('Loading template "'.$rname.'"', S_ACCENT);
				require($rname);
			}
		}

		if (DEBUG)
		{
			$res = call_user_func($funcname, $this, $this->vars);
			$dt = get_microtime() - $st;

			$nested = $s_runconf->get('tpl.nested');
			$rdt = $dt - $nested[count($nested)-1];
			array_splice($nested, count($nested)-1);
			if (count($nested)) $nested[count($nested)-1] += $dt;
			$s_runconf->set('tpl.nested', $nested);

			$s_runconf->set('time.template', $s_runconf->get('time.template') + $rdt);
			return $res;
		}
		else
		{
			return call_user_func($funcname, $this, $this->vars);
		}
	}
}
