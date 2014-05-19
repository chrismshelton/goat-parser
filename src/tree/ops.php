<?php

namespace Goat;

class Label
{
	public $name;

	public function __construct ($name)
	{
		$this -> name = $name;
	}

	public function getName()
	{
		return $this -> name;
	}

	public static function fromPrefix ($prefix)
	{
		return new Label (sprintf ("l%s%s", $prefix, uniqueId()));
	}
}

class ParseState
{
	public $position;
	public $result;
	public $line;
	public $col;

	public function __construct (ParseVar $position, ParseVar $result=NULL, ParseVar $line, ParseVar $col)
	{
		$this -> position = $position;
		$this -> result = $result;
		$this -> line = $line;
		$this -> col = $col;
	}

	public function putResultVar (ParseVar $result=NULL)
	{
		return new ParseState ($this -> position, $result, $this -> line, $this -> col);
	}
}

class ParseMethod
{
	public $name;
	public $vars;
	public $opSequence;

	public function __construct ($name, ParseState $vars, Op $opSequence)
	{
		$this -> name = $name;
		$this -> vars = $vars;
		$this -> opSequence = $opSequence;
	}

	public function swapOp (Op $op)
	{
		return new ParseMethod ($this -> name, $this -> vars, $op);
	}
}

abstract class Op
{
	const OP_COND = 1;
	const OP_COMMENT = 2;
	const OP_DEBUG = 3;
	const OP_ERROR = 4;
	const OP_INCR = 5;
	const OP_GET_MATCH_RESULT = 6;
	const OP_LABEL = 7;
	const OP_LOOP = 8;
	const OP_MATCH = 9;
	const OP_NOP = 10;
	const OP_RETURN_CONST = 11;
	const OP_RULE = 12;
	const OP_SET = 13;
	const OP_SET_CONST = 14;
	const OP_SET_SUBSTR = 15;
	const OP_THUNK = 16;

	abstract public function getOpType();
}

class OpCond extends Op
{
	public $expr;
	public $successExpr;
	public $failExpr;

	public function __construct ($expr, Op $successExpr, Op $failExpr)
	{
		if ((!($expr instanceof ParseVar)) && !(is_array ($expr) && sizeof ($expr) == 3))
		{
			$dbg = debug_backtrace(false);
			throw new \InvalidArgumentException ('Argument 1 passed to Goat\OpCond::__construct() must be of type Goat\ParseVar|array, '.gettype ($expr).' given, called in '.$dbg[0]['file'].' on line '.$dbg[0]['line'].' and defined in '.__FILE__.' on line '.__LINE__);
		}

		$this -> expr = $expr;
		$this -> successExpr = $successExpr;
		$this -> failExpr = $failExpr;
	}

	public function getOpType()
	{
		return self::OP_COND;
	}
}

class OpComment extends Op
{
	public $lines;
	public $thenExpr;

	public function __construct ($lines, Op $thenExpr)
	{
		if (is_string ($lines))
		{
			$this -> lines = array ($lines);
		}
		else
		{
			$this -> lines = $lines;
		}

		$this -> thenExpr = $thenExpr;
	}

	public function getOpType()
	{
		return self::OP_COMMENT;
	}
}

class OpDebug extends Op
{
	const DEBUG_ENTER = 1;
	const DEBUG_SUCCESS = 2;
	const DEBUG_FAIL = 3;

	public function __construct ($debugType, $args, $thenExpr)
	{
		$this -> debugType = $debugType;
		$this -> args = $args;
		$this -> thenExpr = $thenExpr;
	}

	public function getOpType()
	{
		return self::OP_DEBUG;
	}
}

class OpError extends Op
{
	public $errorMessage;
	public $position;
	public $thenExpr;

	public function __construct ($errorMessage, ParseVar $position, $thenExpr)
	{
		$this -> errorMessage = $errorMessage;
		$this -> position = $position;
		$this -> thenExpr = $thenExpr;
	}

	public function getOpType()
	{
		return self::OP_ERROR;
	}
}

class OpGetMatchResult extends Op
{
	public function __construct (ParseVar $matchVar, ParseVar $resultVar, Op $thenExpr)
	{
		$this -> matchVar = $matchVar;
		$this -> resultVar = $resultVar;
		$this -> thenExpr = $thenExpr;
	}

	public function getOpType()
	{
		return self::OP_GET_MATCH_RESULT;
	}
}

class OpIncr extends Op
{
	public $incrVar;
	public $thenExpr;

	public function __construct (ParseVar $incrVar, Op $thenExpr)
	{
		$this -> incrVar = $incrVar;
		$this -> thenExpr = $thenExpr;
	}

	public function getOpType()
	{
		return self::OP_INCR;
	}
}

class OpLabel extends Op
{
	public $label;

	public function __construct (Label $label, Op $thenExpr)
	{
		$this -> label = $label;
		$this -> thenExpr = $thenExpr;
	}

	public function getLabelName()
	{
		return $this -> label -> getName();
	}

	public function getOpType()
	{
		return self::OP_LABEL;
	}
}

class OpLabelLazy extends OpLabel
{
	public $label;
	private $thenExpr;

	public function __construct (Label $label)
	{
		$this -> label = $label;
		$debugInfo = debug_backtrace (false);
		$this -> debugInfo = $debugInfo[0];
	}

	public function __get ($name)
	{
		if ($name === 'thenExpr')
		{
			if ($this -> thenExpr === NULL)
			{
				if ($this -> debugInfo['type'] != '')
				{
					$func = $this -> debugInfo['class'].$this -> debugInfo['type'].$this -> debugInfo['function'];
				}
				else
				{
					$func = $this -> debugInfo['function'];
				}

				throw new \RuntimeException ("Lazy label expression never set for label ".$this -> getLabelName()." (created in function ".$func." in file ".$this -> debugInfo['file']." on line ".$this -> debugInfo['line'].")");
			}

			return $this -> thenExpr;
		}

		return parent::__get ($name);
	}

	public function setNextOp (Op $thenOp)
	{
		$this -> thenExpr = $thenOp;
	}
}

class OpLoop extends Op
{
	private $reduced;

	public function __construct ($condition, $fnBody, Op $thenExpr)
	{
		throw new \RuntimeException ("Shouldn't get called anywhere");
//		$loopSuccess = new OpLabel (Label::fromPrefix ("ls"));
		$this -> fnBody = $fnBody;
		$this -> thenExpr = $thenExpr;

		$loopFail = new OpLabel (Label::fromPrefix ("lf"), $thenExpr);

		$loopContinue = new OpNop;

//		$this -> enterLabel = Label::fromPrefix ("ls");
		$this -> condition = $condition;
		$this -> body = $fnBody ($loopContinue, $loopFail);
		$this -> reduced = $this -> reduce();
	}

	public function getOpType()
	{
		return self::OP_LOOP;
	}

	public function reduce()
	{
		if ($this -> reduced !== NULL)
		{
			return $this -> reduced;
		}

		$enter = new OpLabelLazy (Label::fromPrefix ("ls"));
		$loopFail = new OpLabel (Label::fromPrefix ("lf"), $this -> thenExpr);
		$fnBody = $this -> fnBody;
		$op = $fnBody ($enter, $loopFail);
		$enter -> setNextOp ($op);
		//$enter -> thenExpr; // tie
		return $enter;
	}
}

class OpMatch extends Op
{
	public $matchExpr;
	public $position;
	public $successExpr;
	public $failExpr;

	public function __construct (Literal $matchExpr, ParseVar $position, Op $successExpr, Op $failExpr)
	{
		$this -> matchExpr = $matchExpr;
		$this -> position = $position;
		$this -> successExpr = $successExpr;
		$this -> failExpr = $failExpr;
	}

	public function getMatchExpr()
	{
		return $this -> matchExpr;
	}

	public function getMatchType()
	{
		return $this -> matchExpr -> getLiteralType();
	}

	public function getOpType()
	{
		return self::OP_MATCH;
	}
}

class OpNop extends Op
{
	public function __construct()
	{
	}

	public function getOpType()
	{
		return self::OP_NOP;
	}
}

class OpReturnConst extends Op
{
	public $value;

	public function __construct ($value)
	{
		$this -> value = $value;
	}

	public function getOpType()
	{
		return self::OP_RETURN_CONST;
	}
}

class OpRule extends Op
{
	public $ruleName;
	public $ruleArgs;
	public $successExpr;
	public $failExpr;

	public function __construct ($ruleName, array $ruleArgs, Op $successExpr, Op $failExpr)
	{
		$this -> ruleName = $ruleName;
		$this -> ruleArgs = $ruleArgs;
		$this -> successExpr = $successExpr;
		$this -> failExpr = $failExpr;
	}

	public function getOpType()
	{
		return self::OP_RULE;
	}
}

class OpSet extends Op
{
	public $target;
	public $source;
	public $thenExpr;

	public function __construct (ParseVar $target, ParseVar $source, Op $thenExpr)
	{
		$this -> target = $target;
		$this -> source = $source;
		$this -> thenExpr = $thenExpr;
	}

	public function getOpType()
	{
		return self::OP_SET;
	}
}

class OpSetConst extends Op
{
	public $target;
	public $value;
	public $thenExpr;

	public function __construct (ParseVar $target, Literal $value, Op $thenExpr)
	{
		$this -> target = $target;
		$this -> value = $value;
		$this -> thenExpr = $thenExpr;
	}

	public function getOpType()
	{
		return self::OP_SET_CONST;
	}
}

class OpSetSubstr extends Op
{
	public $target;
	public $first;
	public $last;
	public $thenExpr;

	public function __construct (ParseVar $target, ParseVar $first, ParseVar $last, Op $thenExpr)
	{
		$this -> target = $target;
		$this -> first = $first;
		$this -> last = $last;
		$this -> thenExpr = $thenExpr;
	}

	public function getOpType()
	{
		return self::OP_SET_SUBSTR;
	}
}

class OpThunk extends Op
{
	public $target;
	public $thunk;
	public $vars;
	public $thenExpr;

	public function __construct (ParseVar $target, Thunk $thunk, array $vars, Op $thenExpr)
	{
		$this -> target = $target;
		$this -> thunk = $thunk;
		$this -> vars = $vars;
		$this -> thenExpr = $thenExpr;
	}

	public function getClassName()
	{
		return $this -> thunk -> getName();
	}

	public function getOpType()
	{
		return self::OP_THUNK;
	}

	public function getThunk()
	{
		return $this -> thunk;
	}

	public function getVarName()
	{
		return $this -> target -> getName();
	}

}
