<?php

namespace Goat;

abstract class Expr
{
	const TYPE_ARRAY = 1;
	const TYPE_ASSERTION = 2;
	const TYPE_CALL = 3;
	const TYPE_CAPTURE = 4;
	const TYPE_CHOICE = 5;
	const TYPE_INLINE_RULE = 6;
	const TYPE_MAKE_THUNK = 7;
	const TYPE_MATCH = 8;
	const TYPE_NULL = 9;
	const TYPE_LITERAL = 10;
	const TYPE_REPEAT = 11;
	const TYPE_RETURN = 12;
	const TYPE_SEQUENCE = 13;

	public function copyWithSubExpressions (array $newExprs)
	{
		throw new \RuntimeException ("Error - Expr (".get_class ($this).") has no subexpressions");
	}

	public function hasResultVar()
	{
		if (!property_exists ($this, 'resultVar') || $this -> resultVar === NULL)
		{
			return false;
		}

		return true;
	}

	abstract public function getExprType();
	abstract public function getSize();
	abstract public function getSubExpressions();
}

class AssertExpr extends Expr
{
	protected $expr;
	protected $negate;
	protected $resultVar;
	protected $_getSize;

	public function __construct (Expr $expr, $negate, ParseVar $resultVar=NULL)
	{
		if ($resultVar !== NULL && !$resultVar -> isBoolVar())
		{
			throw new \InvalidArgumentException ("Assertion may only return Boolean values");
		}

		$this -> expr = $expr;
		$this -> negate = $negate;
		$this -> resultVar = $resultVar;
	}

	public function copyWithSubExpressions (array $exprs)
	{
		if (sizeof ($exprs) !== 1)
		{
			throw new \RuntimeException ("Expected exactly 1 subexpression for type AssertExpr");
		}

		return new AssertExpr ($exprs[0], $this -> negate, $this -> resultVar);
	}

	public function isNegative()
	{
		return $this -> negate;
	}

	public function getExprType()
	{
		return self::TYPE_ASSERTION;
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}

	public function getSize()
	{
		if ($this -> _getSize !== NULL)
		{
			return $this -> _getSize;
		}

		$this -> _getSize = 1 + $this -> expr -> getSize();
		return $this -> _getSize;
	}

	public function getSubExpression()
	{
		return $this -> expr;
	}

	public function getSubExpressions()
	{
		return array ($this -> expr);
	}

	public function swapResultVar(ParseVar $resultVar=NULL)
	{
		return new AssertExpr ($this -> expr, $this -> negate, $resultVar);
	}
}

class CallExpr extends Expr
{
	protected $name;
	protected $resultVar;

	public function __construct ($name, ParseVar $resultVar=NULL)
	{
		$this -> name = $name;
		$this -> resultVar = $resultVar;
	}

	public function getExprType()
	{
		return self::TYPE_CALL;
	}

	public function getName()
	{
		return $this -> name;
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}

	public function getSize()
	{
		return 1;
	}

	public function getSubExpressions()
	{
		return array();
	}

	public function swapResultVar(ParseVar $resultVar=NULL)
	{
		return new CallExpr ($this -> name, $resultVar);
	}
}

class CaptureExpr extends Expr
{
	protected $expr;
	protected $resultVar;
	protected $_getSize;

	public function __construct (Expr $expr, ParseVar $resultVar)
	{
		if (!$resultVar -> isStringVar())
		{
			throw new \InvalidArgumentException ("CaptureExpr may only return String value");
		}

		$this -> expr = $expr;
		$this -> resultVar = $resultVar;
	}
	public function copyWithSubExpressions (array $exprs)
	{
		if (sizeof ($exprs) !== 1)
		{
			throw new \RuntimeException ("Expected exactly 1 subexpression for type CaptureExpr");
		}

		return new CaptureExpr ($exprs[0], $this -> resultVar);
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}

	public function getSize()
	{
		if ($this -> _getSize !== NULL)
		{
			return $this -> _getSize;
		}

		$this -> _getSize = 1 + $this -> expr -> getSize();
		return $this -> _getSize;
	}

	public function getSubExpression()
	{
		return $this -> expr;
	}

	public function getSubExpressions()
	{
		return array ($this -> expr);
	}

	public function getExprType()
	{
		return self::TYPE_CAPTURE;
	}

	public function swapResultVar(ParseVar $resultVar=NULL)
	{
		if ($resultVar === NULL)
		{
			return $this -> expr;
		}
		else
		{
			return new CaptureExpr ($this -> expr, $resultVar);
		}
	}
}

class ChoiceExpr extends Expr
{
	protected $_getSize;
	protected $exprs;
	protected $resultVar;

	public function __construct (array $exprs, ParseVar $resultVar = NULL)
	{
		foreach ($exprs as $ix => $expr)
		{
			if (!($expr instanceof Expr))
			{
				incorrect_argument ('Expr', 1, $expr, $ix);
			}

			$this -> exprs = $exprs;
		}
	}

	public function copyWithSubExpressions (array $exprs)
	{
		return new ChoiceExpr ($exprs, $this -> resultVar);
	}

	public function getExprType()
	{
		return self::TYPE_CHOICE;
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}

	public function getSize()
	{
		if ($this -> _getSize !== NULL)
		{
			return $this -> _getSize;
		}

		$size = 1;

		foreach ($this -> exprs as $expr)
		{
			$size += $expr -> getSize();
		}

		$this -> _getSize = $size;
		return $this -> _getSize;
	}

	public function getSubExpressions()
	{
		return $this -> exprs;
	}
}

class InlineRuleExpr extends Expr
{
	protected $ruleName;
	protected $expr;
	protected $resultVar;

	public function __construct ($ruleName, Expr $expr, ParseVar $resultVar=NULL)
	{
		$this -> ruleName = $ruleName;
		$this -> expr = $expr;
		$this -> resultVar = $resultVar;
	}

	public function copyWithSubExpressions (array $exprs)
	{
		if (sizeof ($exprs) !== 1)
		{
			throw new \RuntimeException ("Expected exactly 1 subexpression for type InlineRuleExpr");
		}

		if ($exprs[0] instanceof InlineRuleExpr && $exprs[0] -> ruleName === $this -> ruleName)
		{
			throw new \RuntimeException ("HERE");
		}


		return new InlineRuleExpr ($this -> ruleName, $exprs[0], $this -> resultVar);
	}

	public function getName()
	{
		return $this -> ruleName;
	}

	public function getExprType()
	{
		return self::TYPE_INLINE_RULE;
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}

	public function getSize()
	{
		return $this -> expr -> getSize();
	}

	public function getSubExpression()
	{
		return $this -> expr;
	}

	public function getSubExpressions()
	{
		return array ($this -> expr);
	}

	public function swapResultVar (ParseVar $resultVar=NULL)
	{
		return new InlineRuleExpr ($this -> ruleName, $this -> expr, $resultVar);
	}
}

class MatchExpr extends Expr
{
	protected $regExp;

	public function __construct (RegExp $regExp)
	{
		$this -> regExp = $regExp;
	}

	public function getExprType()
	{
		return self::TYPE_MATCH;
	}

	public function getRegExp()
	{
		return $this -> regExp;
	}

	public function getSize()
	{
		return 1;
	}

	public function getSubExpressions()
	{
		return array();
	}
}

class MatchLiteral extends Expr
{
	protected $literal;
	protected $resultVar;

	public function __construct (Literal $literal, ParseVar $resultVar=NULL)
	{
		if ($resultVar !== NULL && !$resultVar -> isStringVar())
		{
			throw new \InvalidArgumentException ("MatchLit may only return type String");
		}

		$this -> literal = $literal;
		$this -> resultVar = $resultVar;
	}

	public function getExprType()
	{
		return self::TYPE_LITERAL;
	}

	public function getLiteral()
	{
		return $this -> literal;
	}

	public function getRegExp()
	{
		return new RegExpLiteral ($this -> literal, $this -> resultVar);
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}

	public function getSize()
	{
		return 1;
	}

	public function getSubExpressions()
	{
		return array();
	}

	public function swapResultVar (ParseVar $resultVar=NULL)
	{
		return new MatchLiteral ($this -> literal, $resultVar);
	}
}

class MakeThunk extends Expr
{
	protected $thunk;
	protected $resultVar;

	public function __construct (Thunk $thunk, ParseVar $resultVar)
	{
		$this -> resultVar = $resultVar;
		$this -> thunk = $thunk;
	}

	public function getExprType()
	{
		return self::TYPE_MAKE_THUNK;
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}

	public function getSize()
	{
		return 1;
	}

	public function getSubExpressions()
	{
		return array();
	}

	public function getThunk()
	{
		return $this -> thunk;
	}

	public function swapResultVar(ParseVar $resultVar=NULL)
	{
		if ($resultVar === NULL)
		{
			return new NullExpr;
		}
		else
		{
			return new MakeThunk ($this -> thunk, $resultVar);
		}
	}
}

class NullExpr extends Expr
{
	public function getExprType()
	{
		return self::TYPE_NULL;
	}

	public function getSize()
	{
		return 0;
	}

	public function getSubExpressions()
	{
		return array();
	}
}

class RepeatExpr extends Expr
{
	protected $expr;
	protected $minCount;
	protected $maxCount;
	protected $resultVar;
	protected $_getSize;

	public function __construct (Expr $expr, $minCount, $maxCount, ParseVar $resultVar=NULL)
	{
		$this -> expr = $expr;
		$this -> minCount = $minCount;
		$this -> maxCount = $maxCount;
		$this -> resultVar = $resultVar;
	}

	public function copyWithSubExpressions (array $exprs)
	{
		if (sizeof ($exprs) !== 1)
		{
			throw new \RuntimeException ("Expected exactly 1 subexpression for type RepeatExpr");
		}

		return new static ($exprs[0], $this -> minCount, $this -> maxCount, $this -> resultVar);
	}

	public function getExprType()
	{
		return self::TYPE_REPEAT;
	}

	public function getMaxCount()
	{
		return $this -> maxCount;
	}

	public function getMinCount()
	{
		return $this -> minCount;
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}

	public function getSize()
	{
		if ($this -> _getSize !== NULL)
		{
			return $this -> _getSize;
		}

		$this -> _getSize = 1 + $this -> expr -> getSize();
		return $this -> _getSize;
	}

	public function getSubExpression()
	{
		return $this -> expr;
	}

	public function getSubExpressions()
	{
		return array ($this -> expr);
	}

	public function swapResultVar (ParseVar $resultVar=NULL)
	{
		return new static ($this -> expr, $this -> minCount, $this -> maxCount, $resultVar);
	}
}

class ArrayExpr extends RepeatExpr
{
	public function getExprType()
	{
		return self::TYPE_ARRAY;
	}
}

class ReturnVar extends Expr
{
	protected $resultVar;

	public function __construct (ParseVar $returnVar)
	{
		$this -> resultVar = $returnVar;
	}

	public function getExprType()
	{
		return self::TYPE_RETURN;
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}

	public function getReturnVar()
	{
		return $this -> resultVar;
	}

	public function getSize()
	{
		return 1;
	}

	public function getSubExpressions()
	{
		return array();
	}

	public function swapResultVar (ParseVar $resultVar=NULL)
	{
		if ($resultVar === NULL)
		{
			return new NullExpr;
		}
		else
		{
			return new ReturnVar ($resultVar);
		}
	}
}

class SequenceExpr extends Expr
{
	protected $_getSize;
	protected $exprs;
	protected $resultVar;

	public function __construct (array $exprs, ParseVar $resultVar = NULL)
	{
		foreach ($exprs as $expr)
		{
			if (!($expr instanceof Expr))
			{
				throw new \InvalidArgumentException ("SequenceExpr expects array of Expr, got ".(gettype ($expr) == 'object' ? get_class ($expr) : gettype ($expr)));
			}

			$this -> exprs = $exprs;
		}

		$this -> resultVar = $resultVar;
	}

	public function getExprType()
	{
		return self::TYPE_SEQUENCE;
	}

	public function copyWithSubExpressions (array $newExprs)
	{
		return new SequenceExpr ($newExprs, $this -> resultVar);
	}

	public function getResultVar()
	{
		return $this -> resultVar;
	}

	public function getSize()
	{
		if ($this -> _getSize !== NULL)
		{
			return $this -> _getSize;
		}

		$size = 1;

		foreach ($this -> exprs as $expr)
		{
			$size += $expr -> getSize();
		}

		$this -> _getSize = $size;
		return $this -> _getSize;
	}

	public function getSubExpressions()
	{
		return $this -> exprs;
	}
}
