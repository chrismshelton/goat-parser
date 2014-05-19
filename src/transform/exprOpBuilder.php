<?php

namespace Goat;

class ExprOpBuilder
{
	public function __construct (State $state, RuleInfoMap $ruleInfoMap)
	{
		$this -> state = $state;
		$this -> ruleInfoMap = $ruleInfoMap;
		$this -> createComments = $this -> state -> getConfig() -> generateParserDebugMessages();
		$this -> createDebugMessages = $this -> state -> getConfig() -> generateParserDebugMessages();
	}

	public function compileDefinitions ($definitions)
	{
		$this -> definitions = $definitions;
		$fns = array();

		foreach ($definitions -> getDefinitions() as $definition)
		{
			foreach ($definition -> getNames() as $ruleName)
			{
				$fns[] = $this -> compileRule ($ruleName, $definition -> getRule ($ruleName));
			}
		}

		$this -> definitions = NULL;
		return $fns;
	}

	public function compileExpr (Expr $expr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		switch ($expr -> getExprType())
		{
			case Expr::TYPE_ARRAY:
				return $this -> compileArray ($expr, $stVars, $successExpr, $failExpr);
			break;

			case Expr::TYPE_ASSERTION:
				return $this -> compileAssertion ($expr, $stVars, $successExpr, $failExpr);
			break;

			case Expr::TYPE_CALL:
				return $this -> compileCall ($expr, $stVars, $successExpr, $failExpr);
			break;

			case Expr::TYPE_CAPTURE:
				return $this -> compileCapture ($expr, $stVars, $successExpr, $failExpr);
			break;

			case Expr::TYPE_CHOICE:
				return $this -> compileChoice ($expr, $stVars, $successExpr, $failExpr);
			break;

			case Expr::TYPE_INLINE_RULE:
				return $this -> compileInlineRule ($expr, $stVars, $successExpr, $failExpr);
			break;

			case Expr::TYPE_MAKE_THUNK:
				return $this -> compileMakeThunk ($expr, $stVars, $successExpr, $failExpr);
			break;

			case Expr::TYPE_MATCH:
				return $this -> compileMatch ($expr, $stVars, $successExpr, $failExpr);
			break;

			case Expr::TYPE_LITERAL:
				return $this -> compileLiteral ($expr, $stVars, $successExpr, $failExpr);
			break;

			case Expr::TYPE_REPEAT:
				return $this -> compileRepeat ($expr, $stVars, $successExpr, $failExpr);
			break;

			case Expr::TYPE_RETURN:
				return $this -> compileReturn ($expr, $stVars, $successExpr, $failExpr);
			break;

			case Expr::TYPE_SEQUENCE:
				return $this -> compileSequence ($expr, $stVars, $successExpr, $failExpr);
			break;

			default:
				throw new \InvalidArgumentException (sprintf ("Bad expr type - '%d'", $expr -> getExprType()));
			break;
		}
	}

	public function compileRule ($name, Expr $expr)
	{
		$position = new ParseVar ("position", VarType::ReferenceType (VarType::PositionType()));
		$value = new ParseVar ("value", VarType::ReferenceType (VarType::UserType()));
		$line = new ParseVar ("line", VarType::ReferenceType (VarType::PositionType()));
		$column = new ParseVar ("column", VarType::ReferenceType (VarType::PositionType()));
		$stVars = new ParseState ($position, $value, $line, $column);

		$successExpr = new OpReturnConst (true);
		$successExpr = new OpLabel (new Label ($this -> state -> getUniqueName ("s")), $this -> createDebug (OpDebug::DEBUG_SUCCESS, array ($name, $position), $successExpr));

		$failExpr = new OpReturnConst (false);
		$failExpr = new OpLabel (new Label ($this -> state -> getUniqueName ("f")), $this -> createDebug (OpDebug::DEBUG_FAIL, array ($name, $position), $failExpr));

		$result = $this -> compileExpr ($expr, $stVars, $successExpr, $failExpr);
		return new ParseMethod ($name, $stVars, new OpDebug (OpDebug::DEBUG_ENTER, array ($name, $position), $result));
	}

	protected function compileArray (ArrayExpr $arrayExpr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		$subExpression = $arrayExpr -> getSubExpression();

		if ($subExpression -> getResultVar() === NULL)
		{
			$subExpression = $subExpression -> swapResultVar (new ParseVar ($this -> state -> getUniqueName ("res"), VarType::UnknownType()));
		}

		$subExprVar = $subExpression -> getResultVar();

		$emptyThunk = new Thunk ($this -> state -> getUniqueName ("ArrEmptyThunk"), array(), " array() ");
		$thunk = new Thunk ($this -> state -> getUniqueName ("ArrThunk"), array ($subExprVar, $arrayExpr -> getResultVar()), " array_merge (\$".$arrayExpr -> getResultVar() -> getSourceName().", array (\$".$subExprVar -> getSourceName().")) ");

//		$loopSuccess = new OpThunk ($arrayExpr -> getResultVar(), $thunk, $thunk -> getUsedVars(), $successExpr);
//		$loopFail = $failExpr;

		$arrayResultVar = $arrayExpr -> getResultVar();
		$that = $this;
		$fnCompileLoopBody = function ($stVars, $successExpr, $failExpr) use (&$subExpression, &$that, &$thunk, &$arrayResultVar) {
			$addElem = new OpThunk ($arrayResultVar, $thunk, $thunk -> getUsedVars(), $successExpr);
			return $that -> compileExpr ($subExpression, $stVars, $addElem, $failExpr);
		};

		$loop = $this -> buildLoop ($fnCompileLoopBody, $stVars, $successExpr, $failExpr, $arrayExpr -> getMinCount(), $arrayExpr -> getMaxCount());

		$beforeLoop = new OpThunk ($arrayExpr -> getResultVar(), $emptyThunk, array(), $loop);
		return $beforeLoop;
/*
		$newStVars = $stVars -> putResultVar ($arrayExpr -> );

		$that = $this;
		$fnCompileLoopBody = function ($stVars, $successExpr, $failExpr) use (&$arrayExpr, &$that) {



			return $that -> compileExpr ($arrayExpr -> getSubExpression(), $stVars, $successExpr, $failExpr);
		};

		$loop = $this -> buildLoop ($fnCompileLoopBody, $stVars, $successExpr, $failExpr, $arrayExpr -> getMinCount(), $arrayExpr -> getMaxCount());

		$beforeLoop = new OpThunk ($tempResultVar, $this -> emptyArrayThunk, array(), $successExpr);

*/
	}

	protected function compileAssertion (Expr $expr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		$savePos = new ParseVar ($this -> state -> getUniqueName ("sp"), VarType::PositionType());

		$thisSuccess = new OpSet ($stVars -> position, $savePos, $successExpr);
		$thisFail = new OpSet ($stVars -> position, $savePos, $failExpr);

		if ($expr -> isNegative())
		{
			$assert = $this -> compileExpr ($expr -> getSubExpression(), $stVars, $thisFail, $thisSuccess);
		}
		else
		{
			$assert = $this -> compileExpr ($expr -> getSubExpression(), $stVars, $thisSuccess, $thisFail);
		}

		return new OpSet ($savePos, $stVars -> position, $assert);
	}

	protected function compileCall (Expr $expr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		$info = $this -> ruleInfoMap -> getRuleInfo ($expr -> getName());
		$ruleArgs = array();
		$ruleArgs[] = $stVars -> position;

		if ($expr -> getResultVar() !== NULL)
		{
			$ruleArgs[] = $expr -> getResultVar();
		}

		return new OpRule ($expr -> getName(), $ruleArgs, $successExpr, $failExpr);
	}

	protected function compileCapture (Expr $expr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		$startPos = new ParseVar ($this -> state -> getUniqueName ("cp"), VarType::PositionType());
		$thisSuccess = new OpSetSubstr ($expr -> getResultVar(), $startPos, $stVars -> position, $successExpr);
		$thisFail = $failExpr;
		return new OpSet ($startPos, $stVars -> position, $this -> compileExpr ($expr -> getSubExpression(), $stVars, $thisSuccess, $thisFail));
	}

	protected function compileChoice (Expr $expr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		$choiceId = $this -> state -> getUniqueName ("ch");
		$ct = sizeof ($expr -> getSubExpressions());

		$thisFail = $this -> createComment ("fail $choiceId ($ct)", $failExpr);

		foreach (array_reverse ($expr -> getSubExpressions()) as $subExpr)
		{
			$resultExpr = $this -> compileExpr ($subExpr, $stVars, $successExpr, $thisFail);
			$thisFail = $this -> createComment ("$choiceId - $ct", $resultExpr);
			$ct -= 1;
		}

		return $this -> createComment ("begin $choiceId", $thisFail);
	}

	protected function compileInlineRule (InlineRuleExpr $inlineRule, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		$ruleName = $inlineRule -> getName();
		$expr = $inlineRule -> getSubExpression();
		$resultVar = $inlineRule -> getResultVar();

		$newStVars = $stVars -> putResultVar ($inlineRule -> getResultVar());
		$commentSuccess = $this -> createComment ("success inline ".$ruleName, $successExpr);
		$thisSuccess = $this -> createDebug (OpDebug::DEBUG_SUCCESS, array ($ruleName, $stVars -> position), $commentSuccess);

		$errorFail = new OpError ("Expected $ruleName at line @LINE, offset @COLUMN", $stVars -> position, $failExpr);
		$commentFail = $this -> createComment ("fail inline ".$ruleName, $errorFail);
		$debugFail = $this -> createDebug (OpDebug::DEBUG_FAIL, array ($ruleName, $stVars -> position), $commentFail);
		$thisFail = $debugFail;

		return new OpLabel (new Label ($this -> state -> getUniqueName ("ir")),
			$this -> createDebug (OpDebug::DEBUG_ENTER, array ($ruleName, $stVars -> position),
				$this -> createComment ("begin inline ".$ruleName,
					$this -> compileExpr ($expr, $newStVars, $thisSuccess, $thisFail))));
	}

	protected function compileMatch (MatchExpr $matchExpr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		return new OpComment ('RegExp', new OpMatch ($matchExpr -> getRegExp(), $stVars -> position, $successExpr, $failExpr));
	}

	protected function compileMakeThunk (Expr $expr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		return new OpComment ($expr -> getThunk() -> getText(), new OpThunk ($expr -> getResultVar(), $expr -> getThunk(), $expr -> getThunk() -> getUsedVars(), $successExpr));
	}

	protected function compileLiteral (Expr $expr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		return new OpComment ("Literal", new OpMatch ($expr -> getLiteral(), $stVars -> position, $successExpr, $failExpr));
	}

	protected function buildLoop ($fnCompileLoopBody, ParseState $stVars, Op $successExpr, Op $failExpr, $minCount=0, $maxCount=0)
	{
		$needsBackupPosition = ($minCount > 0);
		$needsCounter = ($minCount > 0 || $maxCount > 0);
		$enterLoop = new OpLabelLazy (new Label ($this -> state -> getUniqueName ("ls")));
		$exitLoopLabel = new Label ($this -> state -> getUniqueName ("lf"));

		// If there's either a minimum or maximum number of times we should
		// loop, we need to keep a loop counter
		if ($needsCounter)
		{
			$ntimes = new ParseVar ($this -> state -> getUniqueName ("n"), VarType::IntegerType());
			$cont = function ($loopCont) use (&$ntimes) { return new OpIncr ($ntimes, $loopCont); };
		}
		else
		{
			$cont = function ($loopCont) { return $loopCont; };
		}

		// If there's a minimum count, we need to check were >= the minimum count
		// on loop exit and fail if we're not
		if ($needsBackupPosition)
		{
			$successPos = new ParseVar ($this -> state -> getUniqueName ("sp"), VarType::PositionType());
			$restorePositionAndFail = new OpSet ($stVars -> position, $successPos, $failExpr);
			$exitLoop = new OpLabel ($exitLoopLabel, new OpCond (array ($ntimes, '<', $minCount), $restorePositionAndFail, $successExpr));
		}
		else
		{
			$exitLoop = new OpLabel ($exitLoopLabel, $successExpr);
		}

		// If there's a maximum count, we need to check we're still under it each
		// iteration of the loop
		if ($maxCount > 0)
		{
			$checkCountLoopContinue = new OpCond (array ($ntimes, '<', $maxCount), $exitLoop, $enterLoop);
		}
		else
		{
			$checkCountLoopContinue = $enterLoop;
		}

		// If we have a count at all, we need to increment it before we continue the loop
		// (And before we check the loop counter)
		if ($needsCounter)
		{
			$beforeContinueLoop = new OpIncr ($ntimes, $checkCountLoopContinue);
		}
		else
		{
			$beforeContinueLoop = $checkCountLoopContinue;
		}

		$loopBody = $fnCompileLoopBody ($stVars, $beforeContinueLoop, $exitLoop);
		$enterLoop -> setNextOp ($loopBody);

		// If we need a counter, we have to initialize it before the loop begins
		if ($needsCounter)
		{
			$preLoop = new OpSetConst ($ntimes, new IntegerLiteral (0), $enterLoop);
		}
		else
		{
			$preLoop = $enterLoop;
		}

		// If we need a backup position, we have to initialize it as well
		if ($needsBackupPosition)
		{
			$prePreLoop = new OpSet ($successPos, $stVars -> position, $preLoop);
		}
		else
		{
			$prePreLoop = $preLoop;
		}

		return $this -> createComment ("Loop", $prePreLoop);
	}

	protected function compileRepeat (Expr $expr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		$that = $this;
		$fnCompileLoopBody = function ($stVars, $successExpr, $failExpr) use (&$expr, &$that) {
			return $that -> compileExpr ($expr -> getSubExpression(), $stVars, $successExpr, $failExpr);
		};

		return $this -> buildLoop ($fnCompileLoopBody, $stVars, $successExpr, $failExpr, $expr -> getMinCount(), $expr -> getMaxCount());
	}

	protected function compileReturn (Expr $expr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		if ($stVars -> result !== NULL)
		{
			return new OpSet ($stVars -> result, $expr -> getReturnVar(), $successExpr);
		}
		else
		{
			return $successExpr;
		}
	}

	protected function compileSequence (Expr $expr, ParseState $stVars, Op $successExpr, Op $failExpr)
	{
		$ops = array();
		$savePos = new ParseVar ($this -> state -> getUniqueName ("p"), VarType::PositionType());
		$sequenceId = $this -> state -> getUniqueName ('sq');

		if ($expr -> getResultVar() !== NULL)
		{
			$seqStVars = $stVars -> putResultVar ($expr -> getResultVar());
		}
		else
		{
			$seqStVars = $stVars;
		}

		$thisSuccess = new OpLabel (new Label ($this -> state -> getUniqueName ($sequenceId.'s')), $this -> createComment ("end sequence $sequenceId", $successExpr));
		$thisFail = new OpLabel (new Label ($this -> state -> getUniqueName ($sequenceId.'f')), $this -> createComment ("fail sequence $sequenceId", new OpSet ($stVars -> position, $savePos, $failExpr)));

		foreach (array_reverse ($expr -> getSubExpressions()) as $subExpr)
		{
			$thisSuccess = $this -> compileExpr ($subExpr, $seqStVars, $thisSuccess, $thisFail);
		}

		return $this -> createComment ("begin $sequenceId", new OpSet ($savePos, $stVars -> position, $thisSuccess));
	}

	protected function createComment ($comment, Op $thenExpr)
	{
		if ($this -> createComments)
		{
			return new OpComment ($comment, $thenExpr);
		}
		else
		{
			return $thenExpr;
		}
	}

	protected function createDebug ($debugType, $debugArgs, Op $thenExpr)
	{
		if ($this -> createDebugMessages)
		{
			return new OpDebug ($debugType, $debugArgs, $thenExpr);
		}
		else
		{
			return $thenExpr;
		}
	}
}
