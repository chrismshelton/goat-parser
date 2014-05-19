<?php

namespace Goat;

class ExprInliner
{
	public function __construct (RuleInfoMap $ruleInfoMap)
	{
		$this -> inlineRuleResult = array();
		$this -> inlineScoreCache = array();
		$this -> ruleInfoMap = $ruleInfoMap;
		$this -> ruleStack = array();
		$this -> inlineResults = new \SplObjectStorage;
	}

	public function inlineDefinitions ($definitions)
	{
		$this -> definitions = $definitions;
		$anyChanged = false;
		$newDefinitions = array();

		foreach ($definitions -> getDefinitions() as $definition)
		{
			$newDefinition = $this -> inlineDefinition ($definition);

			if ($newDefinition !== $definition)
			{
				$anyChanged = true;
			}

			$newDefinitions[] = $newDefinition;
		}

		$this -> definitions = NULL;

		if ($anyChanged)
		{
			return new Definitions ($newDefinitions);
		}
		else
		{
			return $definitions;
		}
	}

	public function inlineDefinition (Definition $definition)
	{
		$newExprs = array();

		foreach ($definition -> getNames() as $ruleName)
		{
			$expr = $definition -> getRule ($ruleName);
			$newExpr = $this -> inlineRule ($ruleName, $definition -> getRule ($ruleName));

			if ($expr !== $newExpr)
			{
				$newExprs[$ruleName] = $newExpr;
			}
		}

		if (sizeof ($newExprs) > 0)
		{
			return $definition -> replaceRules ($newExprs);
		}
		else
		{
			return $definition;
		}
	}

	public function inlineExpr (Expr $expr)
	{
		if ($this -> inlineResults -> contains ($expr))
		{
			return $this -> inlineResults[$expr];
		}

		switch ($expr -> getExprType())
		{
			case Expr::TYPE_CALL:
				$result = $this -> inlineCall ($expr);
			break;

			case Expr::TYPE_ARRAY:
			case Expr::TYPE_ASSERTION:
			case Expr::TYPE_CAPTURE:
			case Expr::TYPE_INLINE_RULE:
			case Expr::TYPE_REPEAT:
				$result = $this -> inlineSingleSubExpression ($expr);
			break;

			case Expr::TYPE_LITERAL:
			case Expr::TYPE_MATCH:
			case Expr::TYPE_MAKE_THUNK:
			case Expr::TYPE_RETURN:
				$result = $expr;
			break;

			case Expr::TYPE_CHOICE:
			case Expr::TYPE_SEQUENCE:
				$result = $this -> inlineMultiSubExpression ($expr);
			break;

			default:
				throw new \InvalidArgumentException (sprintf ("Bad expr type - '%d'", $expr -> getExprType()));
			break;
		}

		$this -> inlineResults[$expr] = $result;
		return $result;
	}

	public function inlineRule ($name, Expr $expr)
	{
		$this -> currentRuleName = $name;
		$this -> alreadyInlined = array();

		if (array_key_exists ($name, $this -> inlineRuleResult))
		{
			return $this -> inlineRuleResult[$name];
		}

		$result = $this -> inlineExpr ($expr);
		$this -> inlineRuleResult[$name] = $result;
		return $result;
	}

	protected function inlineCall (Expr $expr)
	{
		if ($this -> shouldInlineRule ($expr -> getName()))
		{
			$ruleName = $expr -> getName();
			$ruleExpr = $this -> definitions -> getRule ($ruleName);

			if (array_key_exists ($ruleName, $this -> inlineRuleResult))
			{
				$ruleExpr = $this -> inlineRuleResult[$ruleName];
			}

			$inlined = /*$this -> inlineRule ($ruleName, */$ruleExpr/*)*/;
			if ($inlined -> getExprType() === Expr::TYPE_CAPTURE)
			{
				if ($expr -> getResultVar() !== NULL)
				{
					$inlineExpr = $inlined;
				}
				else
				{
					$inlineExpr = $inlined -> getSubExpression();
				}
			}
			else
			{
				$inlineExpr = $inlined;
			}

			$this -> alreadyInlined[$expr -> getName()] = 1;
			return new InlineRuleExpr ($expr -> getName(), $inlineExpr, $expr -> getResultVar());
		}
		else
		{
			return $expr;
		}
	}

	protected function inlineSingleSubExpression (Expr $expr)
	{
		if ($expr -> getExprType() === Expr::TYPE_INLINE_RULE)
		{
			$this -> ruleStack[] = $expr -> getName();
			$this -> alreadyInlined[$expr -> getName()] = 1;
		}

		$subExpression = $expr -> getSubExpression();
		$newSubExpression = $this -> inlineExpr ($subExpression);

		if ($expr -> getExprType() === Expr::TYPE_INLINE_RULE)
		{
			array_pop ($this -> ruleStack);
		}

		if ($newSubExpression !== $subExpression)
		{
			return $expr -> copyWithSubExpressions (array ($newSubExpression));
		}
		else
		{
			return $expr;
		}
	}

	protected function inlineMultiSubExpression (Expr $expr)
	{
		$anyChanged = false;
		$newSubExpressions = array();
		$regExpExpressions = array();

		foreach ($expr -> getSubExpressions() as $subExpr)
		{
			$newSubExpression = $this -> inlineExpr ($subExpr);

			if ($newSubExpression != $subExpr)
			{
				$anyChanged = true;
			}

			$newSubExpressions[] = $newSubExpression;
		}

		switch (sizeof ($newSubExpressions))
		{
			case 0:
				return new NullExpr;
			break;

			case 1:
				return $newSubExpressions[0];
			break;

			default:
				if ($anyChanged)
				{
					return ((sizeof ($newSubExpressions) == 1) ? $newSubExpressions[0] : $expr -> copyWithSubExpressions ($newSubExpressions));
				}
				else
				{
					return $expr;
				}
			break;
		}
	}

	protected function getExprInlineScore (Expr $expr)
	{
		$score = 0;

		switch ($expr -> getExprType())
		{
			case Expr::TYPE_ASSERTION:
				return $this -> getExprInlineScore ($expr -> getSubExpression());
			break;

			case Expr::TYPE_CALL:
				return 0;
			break;

			case Expr::TYPE_CAPTURE:
				return 2 + $this -> getExprInlineScore ($expr -> getSubExpression());
			break;

			case Expr::TYPE_CHOICE:
			case Expr::TYPE_SEQUENCE:
				$score = 0;

				foreach ($expr -> getSubExpressions() as $subExpression)
				{
					$score += $this -> getExprInlineScore ($subExpression);
				}

				return $score;
			break;

			case Expr::TYPE_MAKE_THUNK:
				return -1;
			break;

			case Expr::TYPE_MATCH:
			case Expr::TYPE_LITERAL:
				return 2;
			break;

			case EXPR::TYPE_ARRAY:
			case Expr::TYPE_REPEAT:
				// these are costly^ if we can preg_match them, we should
				return 1 + $this -> getExprInlineScore ($expr -> getSubExpression());
			break;

			case Expr::TYPE_INLINE_RULE:
			case Expr::TYPE_RETURN:
				return 0;
			break;

			default:
				throw new \InvalidArgumentException (sprintf ("Bad expr type - '%d'", $expr -> getExprType()));
			break;
		}
	}

	protected function getRuleInlineScore ($ruleName)
	{
		// +1 for stuff that can regex easy, -1 for thunks, +1 for capture ...?
		if (array_key_exists ($ruleName, $this -> inlineScoreCache))
		{
			return $this -> inlineScoreCache[$ruleName];
		}

		$this -> inlineScoreCache[$ruleName] = $this -> getExprInlineScore ($this -> definitions -> getRule ($ruleName));
		return $this -> inlineScoreCache[$ruleName];
	}

	protected function shouldInlineRule ($ruleName)
	{
//		return false;
		if (!$this -> ruleInfoMap -> hasRuleInfo ($ruleName))
		{
//var_dump ($ruleName." has no info");
			return false;
		}

		if ($ruleName === $this -> currentRuleName)
		{
//var_dump ($ruleName." is current rule name");
			return false;
		}

//		if (array_key_exists ($ruleName, $this -> alreadyInlined))
//		{
//var_dump ($ruleName." already inlined");
//			return false;
//		}

		if (in_array ($ruleName, $this -> ruleStack))
		{
//var_dump ($ruleName." in rule stack");
			return false;
		}

		if ($this -> definitions -> isRecursive ($ruleName))
		{
//var_dump ($ruleName." is recursive");
			return false;
		}

		$info = $this -> ruleInfoMap -> getRuleInfo ($ruleName);
		$rule = $this -> definitions -> getRule ($ruleName);
		$score = $this -> getRuleInlineScore ($ruleName);
		//var_dump (sprintf ("Should I inline it? %s - size %d - called from %d places total (score: %d)", $ruleName, $rule -> getSize(), $info -> totalCallCount, $score));

		//if ($info -> totalCallCount 
		if ($info -> totalCallCount === 1)
		{
			return true;
		}

		if ($rule -> getSize() < 5 && $rule -> getSize() * ($score + 1) >= $info -> totalCallCount)
		{
			return true;
		}

//		var_dump ("Not inlining $ruleName: size: ".$rule -> getSize().", score: $score");

		return false;
	}
}
