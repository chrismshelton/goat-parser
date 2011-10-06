<?php

/*	Copyright (c) 2011 by Chris Shelton
 *
 *	Permission is hereby granted, free of charge, to any person obtaining a
 *	copy of this software and associated documentation files (the "Software"),
 *	to deal in the Software without restriction, including without limitation
 *	the rights to use, copy, modify, merge, publish, distribute, sublicense,
 *	and/or sell copies of the Software, and to permit persons to whom the
 *	Software is furnished to do so, subject to the following conditions:
 *
 *	The above copyright notice and this permission notice shall be included
 *	in all copies or substantial portions of the Software.
 *
 *	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 *	OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 *	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 *	IN THE SOFTWARE.
 *
 *	Based off Ian Piumarta's peg/leg C parser generators: http://piumarta.com/software/peg/
 */

class Node
{
	static $num;

	function new_num()
	{
		return self::$num++;
	}

	function isAlternate()
	{
		return false;
	}
	function isSequence()
	{
		return false;
	}
	function isSafe() {
		return false;
	}

	function printLabel($label)
	{
		print "\n\n\t\tl{$label}:";
	}

	function printJump($label)
	{
		print "\n\t\tgoto l{$label};";
	}

	function printSave($label)
	{
		echo "\n\t\t\$pos{$label} = \$this->pos;\n\t\t\$sPos{$label} = \$this->sPos;";
	}

	function printRestore($label)
	{
		echo "\n\t\t\$this->pos = \$pos{$label};\n\t\t\$this->sPos = \$sPos{$label};";
	}

	function printReturn($val)
	{
		if(defined('PARSER_DEBUG'))
			if($val == 1) echo "\n\t\techo \"\\n  ok    @  {$this->name} ({\$this->pos})\";";
			else echo "\n\t\techo \"\\n  fail  @  {$this->name} ({\$this->pos})\";";
		echo "\n\t\treturn {$val};";
	}

	function startFunc($name)
	{
		echo "\n\tfunction yy{$this->name}()\n\t{";
		if(defined('PARSER_DEBUG'))
			echo "\n\t\techo \"\nbegin {$this->name} @ ({\$this->pos})\n\";";
	}

	function endFunc()
	{
		echo "\n\t}\n";
	}
}

class SequenceNode extends Node {
	public $sequence;

	function __construct()
	{
		$this->sequence = array();
	}

	function isSequence()
	{
		return true;
	}

	function push($node) {
		$this->sequence[] = $node;
	}

	function compile($ko)
	{
		$ct = count($this->sequence);
		for($i = 0; $i < $ct; $i++)
			$this->sequence[$i]->compile($ko);
	}
}

class AlternateNode extends Node {
	public $alternates;
	
	function __construct()
	{
		$this->alternates = array();
		$this->lastEmpty = false;
	}

	function isAlternate()
	{
		return true;
	}

	function push($alt)
	{
		$this->alternates[] = $alt;
	}

	function compile($ko)
	{
		$ok = $this->new_num();
		$this->printSave($ok);
		$ct = count($this->alternates);
		for($i = 0; $i < $ct - 1; $i++) {
			$next = node::new_num();
			$this->alternates[$i]->compile($next);
			$this->printJump($ok);
			$this->printLabel($next);
			$this->printRestore($ok);
		}
		$this->alternates[$ct - 1]->compile($ko);
		$this->printLabel($ok);
	}
}

class RuleNode extends Node {
	public $name;

	function __construct($name)
	{
		$this->name = $name;
		$this->id = Node::new_num();
		$this->used = false;
	}

	function compile()
	{
		$this->startFunc($this->name);
		if(!$this->expression->isSafe())
			$this->printSave(0);
		$this->expression->compile(0);
		$this->printReturn(true);

		if(!$this->expression->isSafe()) {
			$this->printLabel(0);
			$this->printRestore(0);
			$this->printReturn(0);
		}
		$this->endFunc();
	}

	function setExpression($expression)
	{
		$this->expression = $expression;
	}
}

class DotNode extends Node
{
	function compile($ko)
	{
		echo "\n\t\t",'if(!$this->matchDot())',"\n\t\t\tgoto l{$ko};";
	}
}

class NameNode extends Node
{
	function __construct($rule)
	{
		$this->rule = $rule;
	}

	function compile($ko)
	{
		echo "\n\t\t",'if(!$this->yy',$this->rule->name,'())',"\n\t\t\tgoto l{$ko};";
		if(isset($this->variable))
			die('variables not implemented yet.');
	}
}



class StringNode extends Node
{
	function __construct($text)
	{
		$this->text = $text;
	}

	function compile($ko)
	{
		$len = strlen($this->text);
		if($len == 1)
			echo "\n\t\t",'if(!$this->matchChar("',$this->text,'"))',
				"\n\t\t\tgoto l{$ko};";
		elseif($len == 2 && substr($this->text, 0, 1) == '\\')
			echo "\n\t\t",'if(!$this->matchChar("',$this->text,'"))',
				"\n\t\t\tgoto l{$ko};";
		else
			echo "\n\t\t",'if(!$this->matchString("',$this->text,'"))',
				"\n\t\t\tgoto l{$ko};";
	}
}

class CharClassNode extends Node
{
	public $cc;

	function __construct($value)
	{
		$this->value = $value;
	}

	function makeCharClass($cc)
	{
		$cclass = str_split($cc);
		$ptr = 0;
		$prev = -1;
		$len = strlen($cc);
		$val = 1;
		$bits = array();
	
		if ($cclass[0] == '^')
		{
			$val = 0;
			for($i = 0; $i < 256; $i++)
				$bits[] = 1;
			$ptr = 1;
		} else {
			for($i = 0; $i < 256; $i++)
				$bits[] = 0;
		}
	
		while ($ptr < $len)
		{
			$c = ord($cclass[$ptr]);
			if ($cclass[$ptr] == '-' && $prev >= 0)
			{
				$to = ord($cclass[++$ptr]);
				for(;  $prev <= $to; ++$prev)
					$bits[$prev] = $val;
				$prev= -1;
			}
			else if ($cclass[$ptr] == '\\')
			{
				switch($c = $cclass[++$ptr])
				{
				case 'a':  $c = ord("\a"); break;  /* bel */
				case 'b':  $c = ord("\b"); break;  /* bs */
				case 'e':  $c = ord("\e"); break;  /* esc */
				case 'f':  $c = ord("\f"); break;  /* ff */
				case 'n':  $c = ord("\n"); break;  /* nl */
				case 'r':  $c = ord("\r"); break;  /* cr */
				case 't':  $c = ord("\t"); break;  /* ht */
				case 'v':  $c = ord("\v"); break;  /* vt */
				case '[':  $c = ord('['); break;
				case ']':  $c = ord(']'); break;
				case '\\': $c = ord('\\'); break;
				default:            break;
				}
				$bits[$prev = $c] = $val;
			} else
				$bits[$prev = $c] = $val;
			$ptr++;
		}
	
		$cc = array_chunk($bits, 8);
	
		$str = '';
	
		for($i = 0; $i < 32; $i++)
			$str .= sprintf("\\%03o", $this->compressChars($cc[$i]));
	
		return $str;
	}

	function compressChars($a)
	{
		$char = 0;
		$ct = count($a);

		for($i = 0; $i < $ct; $i++)
			$char = $char | ($a[$i] << $i);

		return $char;
	}

	function compile($ko)
	{
		if($this->cc == null)
			$this->cc = $this->makeCharClass($this->value);

			echo "\n\t\t",
				'if(!$this->matchClass("',
				$this->cc,
				"\"))\n\t\t\tgoto l{$ko};";
		}
	}

class ActionNode extends Node
{
	static $ids;

	function __construct($name, $text, $rule)
	{
		$this->name = $name;
		$this->text = $text;
		$this->rule = $rule;
	}

	function compile($ko)
	{
		echo "\n\t\t\$this->suspend('$this->name');";
	}

	function define()
	{
		echo "\n\tfunction {$this->name}(\$text)\n\t{\n\t\t",
			$this->text, "\n\t}";
	}

	static function getNumber($name)
	{
		if(!is_array(self::$ids))
		{
			self::$ids = array();
		}
		if(!array_key_exists($name, self::$ids))
			self::$ids[$name] = 1;
		return self::$ids[$name]++;
	}
}

class PredicateNode extends Node {
	function __construct($text)
	{
		$this->text = $text;
	}

	function compile($ko)
	{
		echo "\n\t\t",'$this->Text($this->begin, $this->end);',
			"\n\t\tif(!({$this->text}))\n\t\t\tgoto l{$ko};";
	}
}

class PeekNode extends Node {
	function __construct($e)
	{
		$this->element = $e;
	}

	function compile($ko)
	{
		$ok = $this->new_num();
		$this->printSave($ok);
		$this->element->compile($ko);
		$this->printRestore($ok);
	}
}

class PeekNotNode extends Node
{
	function __construct($e)
	{
		$this->element = $e;
	}

	function compile($ko)
	{
		$ok = $this->new_num();
		$this->printSave($ok);
		$this->element->compile($ok);
		$this->printJump($ko);
		$this->printLabel($ok);
		$this->printRestore($ok);
	}
}

class QuestionNode extends Node
{
	function __construct($e)
	{
		$this->element = $e;
	}

	function compile($ko)
	{
		$qko = $this->new_num();
		$qok = $this->new_num();
		$this->printSave($qko);
		$this->element->compile($qko);
		$this->printJump($qok);
		$this->printLabel($qko);
		$this->printRestore($qko);
		$this->printLabel($qok);
	}

	function safe()
	{
		return true;
	}
}

class StarNode extends Node
{
	function __construct($e)
	{
		$this->element = $e;
	}

	function compile($ko)
	{
		$again = $this->new_num();
		$out = $this->new_num();
		$this->printLabel($again);
		$this->printSave($out);
		$this->element->compile($out);
		$this->printJump($again);
		$this->printLabel($out);
		$this->printRestore($out);
	}	

	function safe()
	{
		return true;
	}
}

class PlusNode extends Node
{
	function __construct($e)
	{
		$this->element = $e;
	}


	function compile($ko)
	{
		$again = $this->new_num();
		$out = $this->new_num();
		$this->printLabel($again);
		$this->element->compile($ko);

		//$this->begin();
		$this->printSave($out);
		$this->element->compile($out);
		$this->printJump($again);
		$this->printLabel($out);
		$this->printRestore($out);
		//$this->end();
	}
}

class NodeList
{
	function __construct()
	{
		$this->nodes = array();
		$this->rules = array();
		$this->actions = array();
	}

	function addAction($text, $rule)
	{
		$number = ActionNode::getNumber($rule->name);
		$name = sprintf('yy_%s_%d', $rule->name, $number);
		$this->push(new ActionNode($name, $text, $rule));
		$this->actions[] = $this->nodes[count($this->nodes) - 1];
	}

	function addEmptyAlternate()
	{
		$alt = new AlternateNode();
		$alt->push($this->pop());
		$alt->lastEmpty = true;
		$this->push($alt);
	}

	function addName($name)
	{
		$this->push(
			new NameNode($this->getRule($name))
		);
	}

	function addToAlternates()
	{
		$a = $this->pop();
		$b = $this->pop();

		if($b->isAlternate()) {
			$b->push($a);
			$this->push($b);
			return;
		}
		$c = new AlternateNode();
		$c->push($b);
		$c->push($a);
		$this->push($c);
		return;
	}

	function addToSequence()
	{
		$a = $this->pop();
		$b = $this->pop();

		if($b->isSequence()) {
			$b->push($a);
			$this->push($b);
			return;
		}
		$c = new SequenceNode();
		$c->push($b);
		$c->push($a);
		$this->push($c);
		return;
	}

	function getRule($name)
	{
		if(array_key_exists($name, $this->rules))
			return $this->rules[$name];

		$this->rules[$name] = new RuleNode($name);
		return $this->rules[$name];
	}

	function push($node)
	{
		array_push($this->nodes, $node);
	}

	function pop()
	{
		return array_pop($this->nodes);
	}
}
