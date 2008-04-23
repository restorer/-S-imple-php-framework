<?php

require_once(BASE.'incl/file_item.php');
require_once(BASE.'incl/block_item.php');
require_once(BASE.'incl/note_item.php');

class ParsedDocNote
{
	var $name = '';
	var $description = '';
}

class ParsedDocBlock
{
	var $command = '';
	var $title = '';
	var $notes = array();
	var $text = '';
	var $example = '';
}

class Documenter
{
	var $content = array();
	var $blocks = array();

	function i_load_file($filename)
	{
		$buf = file_get_contents($filename);

		$this->content = array();
		$arr = explode("\n", $buf);

		foreach ($arr as $str)
		{
			$str = trim($str);
			$this->content[] = $str;
		}
	}

	function i_highlight_title($str)
	{
		$delimers = "()[]<> \t=-,";
		$keywords = array('class', 'void', 'string', 'float', 'mixed', 'int', 'bool', 'static', 'date_string',
						'datetime_string', 'array', 'protected', 'private', 'extends', 'abstract');

		$spl = array();
		$buf = '';

		for ($i = 0; $i < strlen($str); $i++)
		{
			$ch = $str{$i};

			if (strpos($delimers, $ch) !== false)
			{
				if (strlen($buf))
				{
					$spl[] = $buf;
					$buf = '';
				}

				$spl[] = $ch;
			}
			else { $buf .= $ch; }
		}

		if (strlen($buf)) $spl[] = $buf;

		$res = '';

		for ($i = 0; $i < count($spl); $i++)
		{
			$val = $spl[$i];

			$nx = '';
			for ($j = $i + 1; $j < count($spl); $j++) {
				if ($spl[$j]!=' ' && $spl[$j]!="\t") {
					$nx = $spl[$j];
					break;
				}
			}

			if ($val=='(' || $val==')') {
				$res .= '<span class="hl-bra">'.$val.'</span>';
			} elseif ($val=='true' || $val=='false') {
				$res .= '<span class="hl-bool">'.$val.'</span>';
			} elseif (preg_match('/^\$[A-Za-z_][A-Za-z0-9_]*$/', $val)) {
				$res .= '<span class="hl-var">'.$val.'</span>';
			} elseif (preg_match('/^0x[0-9A-Za-z]+|[0-9\.]+$/', $val)) {
				$res .= '<span class="hl-num">'.$val.'</span>';
			} elseif (preg_match('/^[A-Z_]+$/', $val) && $nx!='(') {
				$res .= '<span class="hl-def">'.$val.'</span>';
			} elseif (preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $val) && $nx=='(') {
				$res .= '<span class="hl-func">'.$val.'</span>';
			} elseif (in_array($val, $keywords)) {
				$res .= '<span class="hl-type">'.$val.'</span>';
			} else {
				$res .= $val;
			}
		}

		return $res;
	}

	function i_highlight_text($str)
	{
		$str = preg_replace('/\*\*([^\*]+)\*\*/', '<strong>$1</strong>', $str);
		$str = preg_replace('/\#\#([^#]+)\#\#/', '<strong>$1</strong>', $str);
		$str = preg_replace('/\/\/([^\/]+)\/\//', '<em>$1</em>', $str);

		return $str;
	}

	function i_parse_block($block)
	{
		$res =& new ParsedDocBlock();

		foreach ($block as $str)
		{
			$ch = $str{0};
			$rem = trim(substr($str, 1));

			switch ($ch)
			{
				case '.':
					$res->command = $rem;
					break;

				case '=':
					$res->title = $this->i_highlight_title($rem);
					break;

				case '~':
					$res->text .= $rem . (strlen($res->text) ? "\n" : '');
					break;

				case '%':
					$res->text .= (strlen($res->text) ? "\n" : '') . $this->i_highlight_text(htmlspecialchars($rem));
					break;

				case '|':
					$res->example .= (strlen($res->example) ? "\n" : '') . $this->i_highlight_title(htmlspecialchars($rem));
					break;

				case '[':
				case '{':
					$pos = strpos($rem, ($ch=='[' ? ']' : '}'));

					if ($pos!==false && $pos>0)		/* $pos!==false is redudnant, but I like double-checking :) */
					{
						$nt =& new ParsedDocNote();
						$nt->name = trim(substr($rem, 0, $pos));
						$nt->description = $this->i_highlight_text(trim(substr($rem, $pos+1)));
						$res->notes[] = $nt;
					}
					else {
						$res->text .= (strlen($res->text) ? "\n" : '') . htmlspecialchars($str);
					}
					break;

				default:
					$res->text .= (strlen($res->text) ? "\n" : '') . $this->i_highlight_text(htmlspecialchars($str));
			}
		}

		$this->blocks[] = $res;
	}

	function i_parse()
	{
		for ($i = 0; $i < count($this->content); $i++)
		{
			if ($this->content[$i] == '##')
			{
				$block = array();

				for ($i++; $i < count($this->content); $i++)
				{
					if (substr($this->content[$i], 0, 1) != '#') {
						break;
					}

					$str = trim(substr($this->content[$i], 1));
					if (substr($str{0}, 0, 1) != '#') $block[] = $str;
				}

				if (count($block)) $this->i_parse_block($block);
			}
		}
	}

	function parse($filename)
	{
		$this->i_load_file($filename);
		$this->i_parse();
	}

	function save($file)
	{
		$parents = array();

		foreach ($this->blocks as $block)
		{
			if (strlen($block->title) || count($block->notes) || strlen($block->text))
			{
				$bi =& new BlockItem;
				$bi->title = $block->title;
				$bi->text = $block->text;
				$bi->example = $block->example;
				$bi->file_id = $file->get_id();
				$bi->parent_id = (count($parents) ? $parents[count($parents)-1] : 0);
				$bi->save();

				foreach ($block->notes as $nt)
				{
					$ni =& new NoteItem();
					$ni->name = $nt->name;
					$ni->description = $nt->description;
					$ni->block_id = $bi->get_id();
					$ni->save();
				}

				if ($block->command == 'begin') $parents[] = $bi->get_id();
			}
			else
			{
				if ($block->command == 'end') array_shift($parents);
			}
		}
	}
}

?>