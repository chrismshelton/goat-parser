<?php

namespace Goat;

abstract class Literal
{
	const TYPE_CHARACTER_CLASS = 1;
	const TYPE_DOT = 2;
	const TYPE_STRING = 3;
	const TYPE_REGEXP = 4;
	const TYPE_INTEGER = 5;

	abstract public function getLiteralType();
}

class CharacterClassLiteral extends Literal
{
	public function __construct ($text)
	{
		$this -> text = $text;
	}

	public function getLiteralType()
	{
		return Literal::TYPE_CHARACTER_CLASS;
	}
}

class DotLiteral extends Literal
{
	public function getLiteralType()
	{
		return Literal::TYPE_DOT;
	}
}

class IntegerLiteral extends Literal
{
	public function __construct ($value)
	{
		if (!is_integer ($value))
		{
			incorrect_argument ('integer', 1, $value);
		}

		$this -> value = $value;
	}

	public function getLiteralType()
	{
		return Literal::TYPE_INTEGER;
	}
}

class StringLiteral extends Literal
{
	public function __construct ($text)
	{
		if (!is_string ($text))
		{
			incorrect_argument ('string', 1, $text);
		}

		$this -> text = $text;
	}

	public function getLiteralType()
	{
		return Literal::TYPE_STRING;
	}
}

abstract class RegExp extends Literal
{
	const REGEXP_TYPE_CHOICE = 1;
	const REGEXP_TYPE_LITERAL = 2;
	const REGEXP_TYPE_REPEAT = 3;
	const REGEXP_TYPE_SEQUENCE = 4;
	const REGEXP_TYPE_CAPTURE = 5;
	const REGEXP_TYPE_INLINE_RULE = 6;
	const REGEXP_TYPE_ASSERTION = 7;

	public function getLiteralType()
	{
		return self::TYPE_REGEXP;
	}

	abstract public function getRegExpType();
}

class RegExpChoice extends RegExp
{
	private $regExps;
	private $resultVar;

	public function __construct (array $regExps, $debugLabel=NULL, ParseVar $resultVar=NULL)
	{
		foreach ($regExps as $regExp)
		{
			if (!($regExp instanceof RegExp))
			{
				incorrect_argument ('Goat\\RegExp', 1, $regExp);
			}
		}

		$this -> regExps = $regExps;
		$this -> debugLabel = $debugLabel;
		$this -> resultVar = $resultVar;
	}

	public function getRegExps()
	{
		return $this -> regExps;
	}

	public function getRegExpType()
	{
		return self::REGEXP_TYPE_CHOICE;
	}
}

class RegExpAssertion extends RegExp
{
	public function __construct (RegExp $regExp, $negative, ParseVar $resultVar=NULL)
	{
		$this -> regExp = $regExp;
		$this -> negative = $negative;
		$this -> resultVar = $resultVar;
	}

	public function getRegExp()
	{
		return $this -> regExp;
	}

	public function getRegExpType()
	{
		return self::REGEXP_TYPE_ASSERTION;
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}
}

class RegExpCapture extends RegExp
{
	public function __construct (RegExp $regExp, ParseVar $resultVar=NULL)
	{
		$this -> regExp = $regExp;
		$this -> resultVar = $resultVar;
	}

	public function getRegExp()
	{
		return $this -> regExp;
	}

	public function getRegExpType()
	{
		return self::REGEXP_TYPE_CAPTURE;
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}
}

class RegExpInlineRule extends RegExp
{
	public function __construct ($ruleName, RegExp $regExp, ParseVar $resultVar=NULL)
	{
		$this -> ruleName = $ruleName;
		$this -> regExp = $regExp;
		$this -> resultVar = $ResultVar;
	}

	public function getName()
	{
		return $this -> ruleName;
	}

	public function getRegExpType()
	{
		return self::REGEXP_TYPE_INLINE_RULE;
	}
}

// Really the only differences between this, and a regular literal, is:
// 	a. a RegExpLiteral is part of a larger sequence
//	b. a RegExpLiteral can return a result
class RegExpLiteral extends RegExp
{
	private $literal;
	private $resultVar;

	public function __construct (Literal $literal, $debugLabel=NULL, ParseVar $resultVar=NULL)
	{
		$this -> literal = $literal;
		$this -> debugLabel = $debugLabel;
		$this -> resultVar = $resultVar;
	}

	public function getLiteral()
	{
		return $this -> literal;
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}

	public function getRegExpType()
	{
		return self::REGEXP_TYPE_LITERAL;
	}
}

class RegExpRepeat extends RegExp
{
	private $regExp;
	private $resultVar;

	public function __construct (RegExp $regExp, $minCount, $maxCount, $debugLabel=NULL, ParseVar $resultVar=NULL)
	{
		$this -> regExp = $regExp;
		$this -> minCount = $minCount;
		$this -> maxCount = $maxCount;
		$this -> debugLabel = $debugLabel;
		$this -> resultVar = $resultVar;
	}

	public function getMaxCount()
	{
		return $this -> maxCount;
	}

	public function getMinCount()
	{
		return $this -> minCount;
	}

	public function getRegExp()
	{
		return $this -> regExp;
	}

	public function getRegExpType()
	{
		return self::REGEXP_TYPE_REPEAT;
	}
}

class RegExpSequence extends RegExp
{
	private $regExps;
	private $resultVar;

	public function __construct (array $regExps, $debugLabel=NULL, ParseVar $resultVar=NULL)
	{
		foreach ($regExps as $regExp)
		{
			if (!($regExp instanceof RegExp))
			{
				incorrect_argument ('Goat\\RegExp', 1, $regExp);
			}
		}

		$this -> regExps = $regExps;
		$this -> debugLabel = $debugLabel;
		$this -> resultVar = $resultVar;
	}

	public function getRegExps()
	{
		return $this -> regExps;
	}

	public function getRegExpType()
	{
		return self::REGEXP_TYPE_SEQUENCE;
	}
}
