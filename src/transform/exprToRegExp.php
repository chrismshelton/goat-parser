<?php

namespace Goat;

class ExprToRegExp
{
	public function __construct (RuleInfoMap $ruleInfoMap)
	{
		$this -> combineRuleResult = array();
		$this -> combineScoreCache = array();
		$this -> ruleInfoMap = $ruleInfoMap;
	}

	public function combineDefinitions ($definitions)
	{
		$this -> definitions = $definitions;
		$anyChanged = false;
		$newDefinitions = array();

		foreach ($definitions -> getDefinitions() as $definition)
		{
			$newDefinition = $this -> combineDefinition ($definition);

			if ($newDefinition !== $definition)
			{
				$anyChanged = true;
			}

			if ($newDefinition !== NULL)
			{
				$newDefinitions[] = $newDefinition;
			}
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

	public function combineDefinition (Definition $definition)
	{
		$newExprs = array();

		foreach ($definition -> getNames() as $ruleName)
		{
			$expr = $definition -> getRule ($ruleName);
			$regExpr = $this -> combineRule ($ruleName, $definition -> getRule ($ruleName));

			if ($regExpr !== $expr)
			{
				//echo "Using regexp definition for rule $ruleName\n";
				$newExprs[$ruleName] = $regExpr;
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

	public function combineExpr (Expr $expr)
	{
		switch ($expr -> getExprType())
		{
			case Expr::TYPE_CALL:
				return $expr;
			break;

			case Expr::TYPE_REPEAT:
				return $this -> combineRepeatExpression ($expr);
			break;

			case Expr::TYPE_ASSERTION:
			case Expr::TYPE_CAPTURE:
			case Expr::TYPE_INLINE_RULE:
				return $this -> combineSingleSubExpression ($expr);
			break;

			case Expr::TYPE_LITERAL:
				return $expr;
			break;

			case Expr::TYPE_MATCH:
				return $expr;
			break;

			case EXPR::TYPE_ARRAY:
			case Expr::TYPE_MAKE_THUNK:
			case Expr::TYPE_RETURN:
				return $expr;
			break;

			case Expr::TYPE_CHOICE:
			case Expr::TYPE_SEQUENCE:
				return $this -> combineMultiSubExpression ($expr);
			break;

			default:
				throw new \InvalidArgumentException (sprintf ("Bad expr type - '%d'", $expr -> getExprType()));
			break;
		}
	}

	public function combineRule ($name, Expr $expr)
	{
		return $this -> combineExpr ($expr);
	}

	protected function combineCall (Expr $expr)
	{
		return $expr;
	}

	protected function combineLiteral (MatchLiteral $matchLiteral)
	{
		return $matchLiteral;
	}

	protected function combineRepeatExpression (RepeatExpr $expr)
	{
		$newExpr = $this -> combineExpr ($expr -> getSubExpression());

		if ($newExpr instanceof InlineRuleExpr)
		{
			$matchExpr = $newExpr -> getSubExpression();
		}
		else
		{
			$matchExpr = $newExpr;
		}

		if ($matchExpr instanceof MatchExpr || $matchExpr instanceof MatchLiteral)
		{
			return new MatchExpr (new RegExpRepeat ($matchExpr -> getRegExp(), $expr -> getMinCount(), $expr -> getMaxCount(), $expr -> getResultVar()));
		}

		return $expr -> copyWithSubExpressions (array ($newExpr));

		if ($expr -> containsExprType (Expr::TYPE_CAPTURE))
		{
			return NULL;
		}

		if ($expr -> containsExprType (Expr::TYPE_MAKE_THUNK))
		{
			return NULL;
		}

		if ($expr -> containsExprType (Expr::TYPE_RETURN))
		{
			return NULL;
		}

		return NULL;
	}

	protected function combineSingleSubExpression (Expr $expr)
	{
		$subExpression = $expr -> getSubExpression();
		$regExpSubExpr = $this -> combineExpr ($subExpression);

		if ($regExpSubExpr instanceof InlineRuleExpr)
		{
			$matchExpr = $regExpSubExpr -> getSubExpression();
		}
		else
		{
			$matchExpr = $regExpSubExpr;
		}

		if ($matchExpr instanceof MatchExpr || $matchExpr instanceof MatchLiteral)
		{
			if ($expr instanceof CaptureExpr)
			{
				return new MatchExpr (new RegExpCapture ($matchExpr -> getRegExp(), $expr -> getResultVar()));
			}
			elseif ($expr instanceof AssertExpr)
			{
				return new MatchExpr (new RegExpAssertion ($matchExpr -> getRegExp(), $expr -> isNegative(), $expr -> getResultVar()));
			}
		}

		return $expr -> copyWithSubExpressions (array ($regExpSubExpr));
	}

	protected function combineMultiSubExpression (Expr $expr)
	{
		$regExpSubExpressions = array();

		$subExpressions = $expr -> getSubExpressions();

		$newExprs = array();
		$regExps = array();
		$allRegExp = true;

		foreach ($subExpressions as $subExpr)
		{
			$regExp = $this -> combineExpr ($subExpr);
			$newExprs[] = $regExp;

			if ($regExp instanceof InlineRuleExpr)
			{
				$matchExpr = $regExp -> getSubExpression();
			}
			else
			{
				$matchExpr = $regExp;
			}

			if (!($matchExpr instanceof MatchLiteral) && !($matchExpr instanceof MatchExpr))
			{
				$allRegExp = false;
			}
			else
			{
				$regExps[] = $matchExpr -> getRegExp();
			}
		}

		if ($allRegExp)
		{
			if ($expr instanceof ChoiceExpr)
			{
				return new MatchExpr (new RegExpChoice ($regExps, $expr -> getResultVar()));
			}
			else
			{
				if ($expr -> getResultVar() !== NULL)
				{
			var_dump ($expr -> geResultVar());
					return new MatchExpr (new RegExpCapture (new RegExpSequence ($regExps), $expr -> getResultVar()));

				}
				else
				{
					return new MatchExpr (new RegExpSequence ($regExps));
				}
			}
		}
		else
		{
			$smallerExprs = array();

			/*
			for ($i = 0; $i < sizeof ($newExprs); $i += 1)
			{
				if ($newExprs[$i] -> getSize() + 2 <= $subExpressions[$i] -> getSize())
				{
					$smallerExprs[] = $newExprs[$i];
				}
				else
				{
					$smallerExprs[] = $subExpressions[$i];
				}
			}
			*/

			return $expr -> copyWithSubExpressions ($newExprs);
		}
	}
}
