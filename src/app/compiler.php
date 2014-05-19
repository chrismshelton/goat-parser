<?php

namespace Goat;

class NameNotDefinedException extends \Exception {}

class Compiler
{
	public function __construct (State $state)
	{
		$this -> state = $state;
		$this -> infoBuilder = new InfoBuilder;
	}

	public function compileRules (array $nodes)
	{
		$rules = array();

		foreach ($nodes as $node)
		{
			if (!$node -> isRule())
			{
				throw new \RuntimeException ("Expected array of RuleNodes, got ".get_class ($node));
			}

			$rules[$node -> getName()] = $node;
		}

		$exprs = $this -> makeExpr ($rules);
		$this -> ruleInfoMap = $this -> infoBuilder -> buildInfo ($this -> state, $exprs);

		for ($i = 1; $i <= 3; $i += 1)
		{
			$exprs = $this -> cleanExpr ($exprs);
		//$exprs = $this -> cleanExpr ($this -> cleanExpr ($this -> cleanExpr ($exprs)));
		}

		$opMaker = new ExprOpBuilder ($this -> state, $this -> ruleInfoMap);
		$ops = $opMaker -> compileDefinitions ($exprs);
		return $ops;
	}

	public function cleanExpr (Definitions $definitions)
	{
		$newDefinitions = $this -> dropUnusedRules ($this -> ruleInfoMap, $definitions);
		$inliner = new ExprInliner ($this -> ruleInfoMap);
		$newDefinitions1 = $inliner -> inlineDefinitions ($definitions);

		$this -> ruleInfoMap = $this -> infoBuilder -> buildInfo ($this -> state, $newDefinitions1);
		$newDefinitions2 = $this -> dropUnusedRules ($this -> ruleInfoMap, $newDefinitions1);

		$combiner = new ExprToRegExp ($this -> ruleInfoMap);
		$newDefinitions3 = $combiner -> combineDefinitions ($newDefinitions2);

		return $newDefinitions3;
	}

	public function dropUnusedRules (RuleInfoMap $ruleInfoMap, Definitions $definitions)
	{
		$newDefinitions = array();

		foreach ($definitions -> getDefinitions() as $definition)
		{
			$newRules = array();

			foreach ($definition -> getNames() as $defName)
			{
				$ruleInfo = $ruleInfoMap -> getRuleInfo ($defName);

				if ($ruleInfo -> isExported())
				{
					continue;
				}

				if (!$ruleInfo -> isCalled())
				{
					$this -> state -> logger -> logDebug ("Deleting rule '$defName'");
					$newRules[$defName] = NULL;
				}
				else
				{
					$callingRules = $ruleInfo -> getCallingRules();

					if (sizeof ($callingRules) == 1 && $callingRules[0] === $defName)
					{
						$this -> state -> logger -> logDebug ("Deleting rule '$defName'");
						$newRules[$defName] = NULL;
					}
				}
			}

			if (sizeof ($newRules) > 0)
			{
				$newDefinition = $definition -> replaceRules ($newRules);

				if ($newDefinition !== NULL)
				{
					$newDefinitions[] = $newDefinition;
				}
			}
			else
			{
				$newDefinitions[] = $definition;
			}
		}

		return new Definitions ($newDefinitions);
	}

	public function makeExpr ($rules)
	{
		$fixer = new LeftRecursionFixer ($this -> state, $rules);
		$nonLeftRecRules = $fixer -> run();
		$analyzer = new ExprBuilder ($this -> state, $nonLeftRecRules);
		$exprs = $analyzer -> run();
		return $exprs;
	}
}
