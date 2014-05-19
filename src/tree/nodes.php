<?php

namespace Goat;

function uniqueId()
{
	static $num = 1;
	return base_convert ($num++, 10, 36);
}

function incorrect_argument ($expected, $number, $argument, $arrayIndex=NULL)
{
	$dbg = debug_backtrace(false);

	if (array_key_exists ('type', $dbg[1]))
	{
		$errorFunc = $dbg[1]['class'].$dbg[1]['type'].$dbg[1]['function'];
	}
	else
	{
		$errorFunc = $dbg[1]['function'];
	}

	if (gettype ($argument) == 'object')
	{
		$typeGiven = get_class ($argument);
	}
	else
	{
		$typeGiven = gettype ($argument);
	}

	if ($arrayIndex !== NULL)
	{
		$expected = "array($expected)";
		$indexString = " (at array index '$arrayIndex')";
	}
	else
	{
		$indexString = "";
	}

	throw new \InvalidArgumentException ("Argument $number passed to $errorFunc must be of type $expected, $typeGiven$indexString given, called in {$dbg[2]['file']} on line {$dbg[2]['line']} and defined in {$dbg[1]['file']} on line {$dbg[1]['line']}");
}

/**
 * Node
 *
 * This class is what the parser constructs. It gets turned into \Goat\Exprs almost immediately, so
 * it doesn't have to be complicated or super extendable. It's purpose is to be simple and easy to add
 * to without writing a ton of extra code.
 */
class Node
{
	const NODE_TYPE_ACTION = 1;
	const NODE_TYPE_ASSERTION = 2;
	const NODE_TYPE_LIST = 3;
	const NODE_TYPE_MATCH = 4;
	const NODE_TYPE_NAME = 5;
	const NODE_TYPE_NIL = 6;
	const NODE_TYPE_OPTION = 7;
	const NODE_TYPE_QUANTIFIER = 8;
	const NODE_TYPE_RULE = 9;
	const NODE_TYPE_VARIABLE = 10;
	const ASSERTION_TYPE_NEGATIVE = 1;
	const ASSERTION_TYPE_POSITIVE = 2;
	const LIST_TYPE_CHOICE = 1;
	const LIST_TYPE_SEQUENCE = 2;
	const MATCH_TYPE_CLASS = 1;
	const MATCH_TYPE_STRING = 2;
	const MATCH_TYPE_DOT = 3;
	const QUANTIFIER_TYPE_PLUS = 1;
	const QUANTIFIER_TYPE_QUESTION = 2;
	const QUANTIFIER_TYPE_STAR = 3;

	private $nodeId;
	private $type;
	private $subtype;
	private $properties;

	private function __construct ($type, $subtype=0, array $properties=array())
	{
		$this -> type = $type;
		$this -> subtype = $subtype;
		$this -> properties = $properties;
	}

	public function __get ($key)
	{
		throw new \RuntimeException ("Trying to get non-existent property '$key' of node type '".$this -> getTypeString()."'");
	}

	public function getNodeId()
	{
		if ($this -> nodeId === NULL)
		{
			$this -> nodeId = uniqueId();
		}

		return $this -> nodeId;
	}

	public function getElement()
	{
		switch ($this -> type)
		{
			case self::NODE_TYPE_ASSERTION:
			case self::NODE_TYPE_QUANTIFIER:
			case self::NODE_TYPE_RULE:
			case self::NODE_TYPE_VARIABLE:
				return $this -> properties['element'];
			break;

			default:
				throw new \InvalidArgumentException ("Node type ".$this -> getTypeString()." has no property 'element'");
			break;
		}
	}

	public function getFirst()
	{
		switch ($this -> type)
		{
			case self::NODE_TYPE_LIST:
				return $this -> properties['first'];
			break;

			default:
				throw new \InvalidArgumentException ("Node type ".$this -> getTypeString()." has no property 'first'");
			break;
		}
	}

	public function getName()
	{
		switch ($this -> type)
		{
			case self::NODE_TYPE_RULE:
			case self::NODE_TYPE_NAME:
				return $this -> properties['name'];
			break;

			default:
				throw new \InvalidArgumentException ("Node type ".$this -> getTypeString()." has no property 'name'");
			break;
		}
	}

	public function getOptionArguments()
	{
		if (array_key_exists ('optionArgs', $this -> properties))
		{
			return $this -> properties['optionArgs'];
		}

		throw new \InvalidArgumentException ("Node type ".$this -> getTypeString()." has no property 'optionArgs'");
	}

	public function getOptionName()
	{
		if (array_key_exists ('optionName', $this -> properties))
		{
			return $this -> properties['optionName'];
		}

		throw new \InvalidArgumentException ("Node type ".$this -> getTypeString()." has no property 'optionName'");
	}

	public function getRest()
	{
		switch ($this -> type)
		{
			case self::NODE_TYPE_LIST:
				return $this -> properties['rest'];
			break;

			default:
				throw new \InvalidArgumentException ("Node type ".$this -> getTypeString()." has no property 'rest'");
			break;
		}
	}

	public function getText()
	{
		if (array_key_exists ('text', $this -> properties))
		{
			return $this -> properties['text'];
		}

		throw new \InvalidArgumentException ("Node type ".$this -> getTypeString()." has no property 'text'");
	}

	public function getVar()
	{
		if (array_key_exists ('varName', $this -> properties))
		{
			return $this -> properties['varName'];
		}

		throw new \InvalidArgumentException ("Node type ".$this -> getTypeString()." has no property 'var'");
	}

	public function getValue()
	{
		switch ($this -> type)
		{
			case self::NODE_TYPE_MATCH:
				if (array_key_exists ('value', $this -> properties))
				{
					return $this -> properties['value'];
				}
			break;
		}

		throw new \InvalidArgumentException ("Node type ".$this -> getTypeString()." has no property 'value'");
	}

	public function getNodeType()
	{
		return $this -> type;
	}

	public function getTypeString()
	{
		$subtypes = self::getSubtypeConstStrings ($this -> type);

		if (!is_array ($subtypes))
		{
			$types = self::getTypeConstStrings();

			if (!array_key_exists ($this -> type, $types))
			{
				throw new \RuntimeException ("Unknown node type ".strval ($this -> type));
			}

			return $types[$this -> type];
		}

		if (!array_key_exists ($this -> subtype, $subtypes))
		{
			$types = self::getTypeConstStrings();

			if (array_key_exists ($this -> type, $types))
			{
				throw new \RuntimeException ("Unknown ".$types[$this -> type]." type ".strval ($this -> subtype));
			}
			else
			{
				throw new \RuntimeException ("Unknown node type/subtype ".strval ($this -> type)." / ".strval ($this -> subtype));
			}
		}

		return $subtypes[$this -> subtype];
	}

	public function isAction()
	{
		return ($this -> type === self::NODE_TYPE_ACTION);
	}

	public function isAltList()
	{
		return (($this -> type === self::NODE_TYPE_LIST) && ($this -> subtype === self::LIST_TYPE_CHOICE));
	}

	public function isAssert()
	{
		return (($this -> type === self::NODE_TYPE_ASSERTION) && ($this -> subtype === self::ASSERTION_TYPE_POSITIVE));
	}

	public function isAssertNot()
	{
		return (($this -> type === self::NODE_TYPE_ASSERTION) && ($this -> subtype === self::ASSERTION_TYPE_NEGATIVE));
	}

	public function isAssertion()
	{
		return ($this -> type === self::NODE_TYPE_ASSERTION);
	}

	public function isCharacterClass()
	{
		return (($this -> type === self::NODE_TYPE_MATCH) && ($this -> subtype === self::MATCH_TYPE_CLASS));
	}

	public function isDot()
	{
		return (($this -> type === self::NODE_TYPE_MATCH) && ($this -> subtype === self::MATCH_TYPE_DOT));
	}

	public function isList()
	{
		return ($this -> type === self::NODE_TYPE_LIST);
	}

	public function isName()
	{
		return ($this -> type === self::NODE_TYPE_NAME);
	}

	public function isNil()
	{
		return ($this -> type === self::NODE_TYPE_NIL);
	}

	public function isPlus()
	{
		return (($this -> type === self::NODE_TYPE_QUANTIFIER) && ($this -> subtype === self::QUANTIFIER_TYPE_PLUS));
	}

	public function isQuantifier()
	{
		return ($this -> type === self::NODE_TYPE_QUANTIFIER);
	}

	public function isQuestion()
	{
		return (($this -> type === self::NODE_TYPE_QUANTIFIER) && ($this -> subtype === self::QUANTIFIER_TYPE_QUESTION));
	}

	public function isRegExp()
	{
		return ($this -> type === self::NODE_TYPE_MATCH);
	}

	public function isRule()
	{
		return ($this -> type === self::NODE_TYPE_RULE);
	}

	public function isSeqList()
	{
		return (($this -> type === self::NODE_TYPE_LIST) && ($this -> subtype === self::LIST_TYPE_SEQUENCE));
	}

	public function isStar()
	{
		return (($this -> type === self::NODE_TYPE_QUANTIFIER) && ($this -> subtype === self::QUANTIFIER_TYPE_STAR));
	}

	public function isString()
	{
		return (($this -> type === self::NODE_TYPE_MATCH) && ($this -> subtype === self::MATCH_TYPE_STRING));
	}

	public function isVar()
	{
		return array_key_exists ('varName', $this -> properties);
	}

	public function isVariable()
	{
		return ($this -> type === self::NODE_TYPE_VARIABLE);
	}

	public static function unescapeCharacterClass ($text)
	{
		$newText = '';
		$ix = 0;
		$len = strlen ($text);

		while ($ix < $len)
		{
			if ($text[$ix] == '\\')
			{
				if (preg_match ('~x([A-Fa-f0-9]{2})~A', $text, $matches, 0, $ix + 1))
				{
					$newText .= chr (hexdec ($matches[1]));
					$ix += strlen ($matches[0]) + 1;
				}
				elseif (preg_match ("~x([0-3][0-7][0-7])~A", $text, $matches, 0, $ix + 1))
				{
					$newText .= chr (octdec ($matches[1]));
					$ix += strlen ($matches[0]) + 1;
				}
				elseif (preg_match ("~([\\abefnrtv\[\]])~A", $text, $matches, 0, $ix + 1))
				{
					$unescaped = array ("a" => "\a", "b" => "\b", "e" => "\e", "f" => "\f", "n" => "\n", "r" => "\r", "t" => "\t", "v" => "\v", '[' => '[', ']' => ']');
					if (!array_key_exists ($matches[1], $unescaped))
					{
						throw new \RuntimeException ("Unknown escape character: '".$matches[1]."'");
					}

					$newText .= $unescaped[$matches[1]];
					$ix += strlen ($matches[0]) + 1;
				}
				else
				{
					$newText .= '\\';
					$ix += 1;
				}
			}
			else
			{
				$newText .= $text[$ix];
				$ix += 1;
			}
		}

		return $newText;
	}

	public static function unescapeString ($text)
	{
		$newText = '';
		$ix = 0;
		$len = strlen ($text);

		while ($ix < $len)
		{
			if ($text[$ix] == '\\')
			{
				if (preg_match ('~\\\\x([A-Fa-f0-9]{2})~A', $text, $matches, 0, $ix))
				{
					$newText .= chr (hexdec ($matches[1]));
					$ix += strlen ($matches[0]);
				}
				elseif (preg_match ('~\\\\x([0-3][0-7][0-7])~A', $text, $matches, 0, $ix))
				{
					$newText .= chr (octdec ($matches[1]));
					$ix += strlen ($matches[0]);
				}
				elseif (preg_match ("~\\\\([\\\"'abefnrtv])~A", $text, $matches, 0, $ix))
				{
					$unescaped = array ("a" => "\a", "b" => "\b", "e" => "\e", "f" => "\f", "n" => "\n", "r" => "\r", "t" => "\t", "v" => "\v", "'" => '\\\'', '"' => '\"', "\\" => "\\");
					if (!array_key_exists ($matches[1], $unescaped))
					{
						throw new \RuntimeException ("Unknown escape character: '".$matches[1]."'");
					}

					$newText .= $unescaped[$matches[1]];
					$ix += strlen ($matches[0]);
				}
				else
				{
					$newText .= '\\';
					$ix += 1;
				}
			}
			else
			{
				$newText .= $text[$ix];
				$ix += 1;
			}
		}

		return $newText;
	}

	public static function getTypeConstStrings()
	{
		return array
			( self::NODE_TYPE_ACTION		=> 'Action'
			, self::NODE_TYPE_ASSERTION		=> 'Assertion'
			, self::NODE_TYPE_LIST			=> 'List'
			, self::NODE_TYPE_MATCH			=> 'Match'
			, self::NODE_TYPE_NAME			=> 'Name'
			, self::NODE_TYPE_NIL			=> 'Nil'
			, self::NODE_TYPE_OPTION		=> 'Option'
			, self::NODE_TYPE_QUANTIFIER	=> 'Quantifier'
			, self::NODE_TYPE_RULE			=> 'Rule'
			, self::NODE_TYPE_VARIABLE		=> 'Variable'
			);
	}

	public static function getSubtypeConstStrings ($type)
	{
		switch ($type)
		{
			case self::NODE_TYPE_ACTION:
			case self::NODE_TYPE_NAME:
			case self::NODE_TYPE_NIL:
			case self::NODE_TYPE_OPTION:
			case self::NODE_TYPE_RULE:
			case self::NODE_TYPE_VARIABLE:
				return NULL;
			break;

			case self::NODE_TYPE_ASSERTION:
				return array
					( self::ASSERTION_TYPE_NEGATIVE => 'AssertNot'
					, self::ASSERTION_TYPE_POSITIVE => 'Assert'
					);
			break;

			case self::NODE_TYPE_LIST:
				return array
					( self::LIST_TYPE_CHOICE	=> 'Choice'
					, self::LIST_TYPE_SEQUENCE	=> 'Sequence'
					);
			break;

			case self::NODE_TYPE_MATCH:
				return array
					( self::MATCH_TYPE_CLASS	=> 'Class'
					, self::MATCH_TYPE_STRING	=> 'String'
					, self::MATCH_TYPE_DOT		=> 'Dot'
					);
			break;

			case self::NODE_TYPE_QUANTIFIER:
				return array
					( self::QUANTIFIER_TYPE_PLUS		=> 'Plus'
					, self::QUANTIFIER_TYPE_QUESTION	=> 'Question'
					, self::QUANTIFIER_TYPE_STAR		=> 'Star'
					);
			break;

			default:
				throw new \RuntimeException ("Unknown node type ".strval ($type));
			break;
		}
	}

	public static function Assert (Node $node)
	{
		return new Node (self::NODE_TYPE_ASSERTION, self::ASSERTION_TYPE_POSITIVE, array ('element' => $node));
	}

	public static function AssertNot (Node $node)
	{
		return new Node (self::NODE_TYPE_ASSERTION, self::ASSERTION_TYPE_NEGATIVE, array ('element' => $node));
	}

	public static function Action ($text)
	{
		return new Node (self::NODE_TYPE_ACTION, 0, array ('text' => $text));
	}

	public static function CharacterClassNode ($value)
	{
		return new Node (self::NODE_TYPE_MATCH, self::MATCH_TYPE_CLASS, array ('value' => $value));
	}

	public static function Choice (Node $first, Node $rest)
	{
		if (!$rest -> isAltList() && !$rest -> isNil())
		{
			incorrect_argument ("AltListNode|NilNode", 2, $rest);
		}

		return new Node (self::NODE_TYPE_LIST, self::LIST_TYPE_CHOICE, array ('first' => $first, 'rest' => $rest));
	}

	public static function Option ($type, $args)
	{
		$args = array_slice (func_get_args(), 1);
		return new Node (self::NODE_TYPE_OPTION, 0, array ('optionName' => $type, 'optionArgs' => $args));
	}

	public static function Dot()
	{
		return new Node (self::NODE_TYPE_MATCH, self::MATCH_TYPE_DOT);
	}

	public static function EscapeCharacterClassNode ($str)
	{
		return self::CharacterClassNode (self::unescapeCharacterClass ($str));//new CharClassNode (self::unescapeCharacterClass ($str));
	}

	public static function EscapeStringNode ($str)
	{
		return self::StringNode (self::unescapeString ($str));
	}

	public static function Name ($name)
	{
		return new Node (self::NODE_TYPE_NAME, 0, array ('name' => $name));
	}

	public static function Nil()
	{
		return new Node (self::NODE_TYPE_NIL);
	}

	public static function Plus (Node $node)
	{
		return new Node (self::NODE_TYPE_QUANTIFIER, self::QUANTIFIER_TYPE_PLUS, array ('element' => $node));
	}

	public static function Question (Node $node)
	{
		return new Node (self::NODE_TYPE_QUANTIFIER, self::QUANTIFIER_TYPE_QUESTION, array ('element' => $node));
	}

	public static function Rule ($name, Node $node)
	{
		return new Node (self::NODE_TYPE_RULE, 0, array ('name' => $name, 'element' => $node));
	}

	public static function Star (Node $node)
	{
		return new Node (self::NODE_TYPE_QUANTIFIER, self::QUANTIFIER_TYPE_STAR, array ('element' => $node));
	}

	public static function StringNode ($value)
	{
		if (!is_string ($value))
		{
			incorrect_argument ("string", 1, $value);
		}

		return new Node (self::NODE_TYPE_MATCH, self::MATCH_TYPE_STRING, array ('value' => $value));
	}

	public static function Then (Node $first, Node $rest)
	{
		if (!$rest -> isSeqList() && !$rest -> isNil())
		{
			incorrect_argument ("SeqListNode|NilNode", 2, $rest);
		}

		return new Node (self::NODE_TYPE_LIST, self::LIST_TYPE_SEQUENCE, array ('first' => $first, 'rest' => $rest));
	}

	public static function VarName ($name, $varName)
	{
		return new Node (self::NODE_TYPE_NAME, 0, array ('name' => $name, 'varName' => $varName));
	}

	public static function Variable (Node $node, $varName)
	{
		return new Node (self::NODE_TYPE_VARIABLE, 0, array ('element' => $node, 'varName' => $varName));
	}
}
