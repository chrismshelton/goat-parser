<?php

namespace Goat;

class InfoBuilder
{
	private $currentRuleName;

	public function buildInfo (State $state, Definitions $definitions)
	{
		$this -> state = $state;
		$this -> topLevelRuleNames = $state -> getConfig() -> getExportedRuleNames();
		$this -> ruleInfo = new RuleInfoMap;

		foreach ($state -> getConfig() -> getExportedRuleNames() as $exportRule)
		{
			$this -> ruleInfo -> getRuleInfo ($exportRule) -> setExportRule();
		}

		$this -> visitDefinitions ($definitions);
		return $this -> ruleInfo;
	}

	public function collectExpr (Expr $expr)
	{
		$stack = array ($expr);

		while (sizeof ($stack) > 0)
		{
			$currentExpr = array_pop ($stack);

			switch ($currentExpr -> getExprType())
			{
				case Expr::TYPE_CALL:
					$this -> state -> logger -> logDebug ($currentExpr -> getName()." called by ".$this -> currentRuleName);
					$this -> ruleInfo -> getRuleInfo ($currentExpr -> getName()) -> addCallingRule ($this -> currentRuleName);
				break;

				case Expr::TYPE_ARRAY:
				case Expr::TYPE_ASSERTION:
				case Expr::TYPE_CAPTURE:
				case Expr::TYPE_INLINE_RULE:
				case Expr::TYPE_REPEAT:
					$stack[] = $currentExpr -> getSubExpression();
				break;

				case Expr::TYPE_LITERAL:
				case Expr::TYPE_MATCH:
				case Expr::TYPE_MAKE_THUNK:
				case Expr::TYPE_RETURN:
				break;

				case Expr::TYPE_CHOICE:
				case Expr::TYPE_SEQUENCE:
					$stack = array_merge ($stack, $currentExpr -> getSubExpressions());
					//foreach ($expr -> getSubExpressions() as $subExpr)
					//{
					//	$this -> collectExpr ($subExpr);
					//}
				break;

				default:
					throw new \RuntimeException ("Unknown expression type ".get_class ($expr));
				break;
			}
		}
	}

	public function visitDefinitions (Definitions $definitions)
	{
		foreach ($definitions -> getDefinitions() as $definition)
		{
			foreach ($definition -> getNames() as $defName)
			{
				$expr = $definition -> getRule ($defName);
				$this -> currentRuleName = $defName;
				$this -> collectExpr ($expr);
			}
		}
	}
}
