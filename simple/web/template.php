<?php

/*
 * [S]imple framework
 * 2007-2008 Zame Software Development (http://zame-dev.org)
 * All rights reserved
 *
 * World fastest and simpliest php templates :)
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
	public $vars = array();

	##
	# [$controls] Template controls
	##
	public $controls = array();

	##
	# [$replacers] Macro replaces
	# | $tpl->replacers['h'] = 'htmlspecialchars';
	##
	public $replacers = array(
		'h' => 'htmlspecialchars',
		'u' => 'urlencode',
		'j' => 'js_escape'
	);

	##
	# [$optimize_html] Drop empty lines and spaces at begin and end of each line
	##
	public $optimize_html = true;

	protected function escape($str)
	{
		if (!strlen($str)) return '\'\'';

		$res = '';
		$spl = explode("\n", $str);
		$len = count($spl);

		for ($i = 0; $i < $len; $i++)
		{
			$val = $spl[$i];

			if (strlen($val))
			{
				$res .= '\'' . str_replace('\'', '\\\'', str_replace('\\', '\\\\', $val)) . '\'';
				if ($i < $len-1) $res .= '."\\n".';
			}
			elseif ($i < $len-1)
			{
				if (substr($res, -2) == '".') $res = substr($res, 0, -2) . '\\n".';
				else $res .= '"\\n".';
			}
		}

		if ($res{strlen($res)-1} == '.') $res = substr($res, 0, -1);
		return $res;
	}

	##
	# = public void parse(string $buf, string $funcname)
	# [$buf] String to parse
	# [$funcname] Wrapper function name
	##
	public function parse($buf, $funcname)
	{
		$buf = str_replace(chr(0x0D), chr(0x0A), str_replace(chr(0x0A).chr(0x0D), chr(0x0A), str_replace(chr(0x0D).chr(0x0A), chr(0x0A), $buf)));

		$lbra_sz = strlen(LBRA);
		$rbra_sz = strlen(RBRA);

		if ($this->optimize_html)
		{
			$spl = explode(chr(0x0A), $buf);
			$buf = '';

			foreach ($spl as $line)
			{
				$line = trim($line);

				if (strlen($line))
				{
					$buf .= $line;
					if (substr($line, 0, $lbra_sz)!=LBRA || substr($line, -$rbra_sz)!=RBRA) $buf .= "\n";
				}
			}
		}

		$pos = 0;
		$res = '';
		$text = '';
		$tmp_num = 0;

		if ($buf != '')
		{
			$sz = strlen($buf);

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
				if (strlen($text)) { $res .= '$__s.=' . $this->escape($text) . ";\n"; }
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
							if ($buf{$pos} == '\\')		// '
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

						if ($op=='!' || $op=='=')
						{
							$cnt = trim(substr($cnt, 1));

							if ($cnt != '')
							{
								if ($op == '!') { $stat = $cnt.';'; $post_process = false; }
								elseif ($op == '=') $stat = '$__s.=' . $cnt . ';';
							}
						}
						elseif ($op == '@')
						{
							$cnt = trim(substr($cnt, 1));

							if (preg_match('/^([A-Za-z0-9_])+\s*\(\s*\)/', $cnt, $mt))
							{
								if (array_key_exists($mt[1], $this->replacers)) {
									$stat = '$__s.=' . $this->replacers[$mt[1]] . '();';
								} else {
									$stat = '$__s.=$__t->call(\'' . $mt[1] . '\');';
								}
							}
							elseif (preg_match('/^([A-Za-z0-9_])+\s*\(/', $cnt, $mt))
							{
								$rp = (array_key_exists($mt[1], $this->replacers) ? ($this->replacers[$mt[1]].'(') : "\$__t->call('{$mt[1]}',");
								$stat = preg_replace('/^[A-Za-z0-9_]+\s*\(\s*(.*)\s*$/', "\$__s.={$rp}$1;", $cnt);
							}
							else
							{
								$spl = explode(' ', $cnt, 2);
								$name = $spl[0];

								if (count($spl) > 1)
								{
									$args = trim($spl[1]);
									if ($args{0} == ':') { $args = 'array('.$args.')'; }

									if (array_key_exists($name, $this->replacers)) {
										$stat = '$__s.=' . $this->replacers[$name] . "($args);";
									} else {
										$stat = '$__s.=$__t->call(' . "'$name',$args);";
									}

									$stat = preg_replace('/([ \(,]):([A-Za-z_][A-Za-z0-9_]*)\s*=>/', '$1\'$2\'=>', $stat);
								}
								else
								{
									if (array_key_exists($name, $this->replacers)) {
										$stat = '$__s.=' . $this->replacers[$name] . '();';
									} else {
										$stat = '$__s.=$__t->call(\'' . $name . '\');';
									}
								}
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

								if ($op == 'iterate')
								{
									$spl = explode(',', $expr);

									$arr = trim($spl[0]);
									$item = (count($spl) > 1 ? trim($spl[1]) : '$item');
									$counter = (count($spl) > 2 ? trim($spl[2]) : $item.'_ind');
									$limit = (count($spl) > 3 ? trim($spl[3]) : $item.'_cnt');

									$tmp_num++;

									$stat = "\$__t_${tmp_num}=count(${arr});${limit}=\$__t_${tmp_num};";
									$stat .= "for(${counter}=0;${counter}<\$__t_${tmp_num};${counter}++){";
									$stat .= "${item}=${arr}[${counter}];";
								}
								else if ($op=='for' || $op=='foreach' || $op=='if' || $op=='while' || $op=='elseif' || $op=='elsif' || $op=='each')
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

					/*
					if ($post_process) {}
					*/

					$res .= $stat . "\n";
				}
			}
		}

		if (strlen($text)) $res .= '$__s.=' . $this->escape($text) . ";\n";

		$res = 'function '.$funcname.'($__t,$__v){extract($__v);$__s=\'\';'."\n".$res.'return $__s;'."\n}\n";
		return $res;
	}

	##
	# = public string _echo(...)
	# Example of embedded control. Also useful in debugging.
	##
	public function _echo()
	{
		$args = func_get_args();
		foreach ($args as $val) echo $val;

		return '';
	}

	public function call()
	{
		$args = func_get_args();
		$name = array_shift($args);

		switch ($name)
		{
			case 'echo':
				return call_user_func_array(array($this, '_echo'), $args);

			default:
				if (!array_key_exists($name, $this->controls)) throw new Exception("Control $name not found.");
				return call_user_func_array(array($this->controls[$name], 'render'), $args);
		}
	}

	protected function virt_funcname($virt_path)
	{
		$res = preg_replace("/[^a-z0-9_]/", '', preg_replace("/[\-\.\\\\\/]/", '_', $virt_path));
		return ('__s_tpl_' . $res);
	}

	protected function generate_funcname($filename)
	{
		$res = strtolower(substr($filename, strlen(BASE)));
		return $this->virt_funcname($res);
	}

	public function process_str($content, $virt_path)
	{
		$funcname = $this->virt_funcname($virt_path);

		if (!function_exists($funcname))
		{
			$parsed = $this->parse($content, $funcname);
			eval($parsed);
		}

		return call_user_func($funcname, $this, $this->vars);
	}

	##
	# = string process($filename)
	# [$filename] Path to template file
	# Process template, compile it if necessary
	##
	public function process($filename)
	{
		global $s_runconf;

		if (DEBUG)
		{
			$sql_t = $s_runconf->get('time.sql.parse') + $s_runconf->get('time.sql.query');
			$st = get_microtime();

			$nested = $s_runconf->get('tpl.nested', array());
			$nested[] = 0;
			$s_runconf->set('tpl.nested', $nested);
		}

		$funcname = $this->generate_funcname($filename);

		if (!function_exists($funcname))
		{
			if (!file_exists($filename)) throw new Exception("Template \"$filename\" doesn't exists");

			$dir = substr(dirname($filename), strlen(BASE));
			$rdir = conf('cache.path').'templates/'.$dir;
			if ($rdir!='' && !is_dir($rdir)) make_directory($rdir);

			if (substr($rdir, -1) != '/') $rdir .= '/';
			$rname = $rdir.basename($filename).'.php';
			$mt = filemtime($filename);
			$mk = true;

			if (!file_exists($rname) || filemtime($rname)<$mt)
			{
				dwrite('Parsing template "'.$rname.'"', S_ACCENT);
				$parsed = $this->parse(file_get_contents($filename), $funcname);

				if ($fp = @fopen($rname, 'wb'))
				{
					fwrite($fp, '<'.'?'.'php'."\n" . $parsed);		// closing php tag is not necessary
					fclose($fp);
					chmod($rname, 0555+0111);
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
			$dt = get_microtime() - $st - ($s_runconf->get('time.sql.parse') + $s_runconf->get('time.sql.query') - $sql_t);

			$nested = $s_runconf->get('tpl.nested');

			$curr = array_splice($nested, count($nested)-1);
			$rdt = $dt - $curr[0];

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
