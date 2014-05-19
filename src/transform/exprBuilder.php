<?php

namespace Goat;

class ExprBuilder
{
	public function __construct (State $state, array $rules)
	{
		$this -> state = $state;
		$this -> rules = $rules;
		$this -> currentRule = NULL;
		$this -> capturedNodes = new \SplObjectStorage;
		$this -> capturedRules = array();
		$this -> actionRules = array();
		$this -> calledRules = array();
		$this -> recursiveRules = array();
		$this -> thunks = array();
	}

	public function run()
	{
		foreach ($this -> rules as $rule)
		{
			$this -> actionRules[$rule -> getName()] = false;
			$this -> capturedRules[$rule -> getName()] = false;
			$this -> calledRules[$rule -> getName()] = array();
		}

		foreach ($this -> rules as $rule)
		{
			$this -> currentRule = $rule;
			$this -> visitNode ($rule -> getElement());

			if ($this -> capturedNodes -> contains ($rule -> getElement()))
			{
				$this -> capturedRules[$rule -> getName()] = $this -> capturedNodes[$rule -> getElement()];
			}
		}

		$recCalledRules = array();

		foreach ($this -> calledRules as $ruleName => $calledRules)
		{
			$recCalledRules[$ruleName] = array();

			foreach ($calledRules as $calledRule)
			{
				$recCalledRules[$ruleName][$calledRule] = 1;
			}
		}

		$recChanged = true;

		while ($recChanged)
		{
			$recChanged = false;

			foreach ($this -> rules as $rule)
			{
				foreach ($recCalledRules[$rule -> getName()] as $calledRuleName => $dontCare)
				{
					$merged = array_merge ($recCalledRules[$rule -> getName()], $recCalledRules[$calledRuleName]);

					if (sizeof ($merged) > sizeof ($recCalledRules[$rule -> getName()]))
					{
						$recCalledRules[$rule -> getName()] = $merged;
						$recChanged = true;
					}
				}
			}

			foreach ($this -> rules as $rule)
			{
				if (array_key_exists ($rule -> getName(), $this -> recursiveRules))
				{
					continue;
				}

				if (array_key_exists ($rule -> getName(), $recCalledRules[$rule -> getName()]))
				{
					$this -> recursiveRules[$rule -> getName()] = true;
					$recChanged = true;
				}
			}
		}

		$recGroups = $this -> makeRecGroups (array_keys ($this -> recursiveRules));

		$newRules = array();

		foreach ($this -> rules as $rule)
		{
			if (!array_key_exists ($rule -> getName(), $this -> recursiveRules))
			{
				$newRules[] = new NonRecDefinition ($rule -> getName(), $this -> expandRule ($rule));
			}
		}

		foreach ($recGroups as $group)
		{
			$groupArray = array();

			foreach ($group as $ruleName)
			{
				$groupArray[$ruleName] = $this -> expandRule ($this -> rules[$ruleName]);
			}

			$newRules[] = new RecDefinition ($groupArray);
		}

		return new Definitions ($newRules);
	}

	public function getCalledRuleNames ($node)
	{
		$rules = array();
		$nodes = array ($node);

		while (sizeof ($nodes) > 0)
		{
			$currentNode = array_pop ($nodes);

			switch ($currentNode -> getNodeType())
			{
				case Node::NODE_TYPE_ACTION:
				case Node::NODE_TYPE_MATCH:
				case Node::NODE_TYPE_NIL:
					continue;
				break;

				case Node::NODE_TYPE_ASSERTION:
				case Node::NODE_TYPE_QUANTIFIER:
				case Node::NODE_TYPE_VARIABLE:
					array_push ($nodes, $currentNode -> getElement());
				break;

				case Node::NODE_TYPE_LIST:
					array_push ($nodes, $currentNode -> getFirst());
					array_push ($nodes, $currentNode -> getRest());
				break;

				case Node::NODE_TYPE_NAME:
					$rules[$currentNode -> getName()] = 1;
				break;

				default:
					throw new \RuntimeException ("Unknown node type ".$currentNode -> getTypeString());
				break;
			}
		}

		return array_keys ($rules);
	}

	public function makeRecGroups ($ruleNames)
	{
		$groups = array();
		$groupCt = 1;

		$calledRules = array();

		foreach ($ruleNames as $ruleName)
		{
			$calledRules[$ruleName] = $this -> getCalledRuleNames ($this -> rules[$ruleName] -> getElement());
		}

		foreach ($ruleNames as $ruleName)
		{
			if (array_key_exists ($ruleName, $groups))
			{
				$groupNums = array ($groups[$ruleName]);
			}
			else
			{
				$groupNums = array();
			}

			foreach ($calledRules[$ruleName] as $calledRule)
			{
				if (!array_key_exists ($calledRule, $calledRules))
				{
					continue;
				}

				if (in_array ($ruleName, $calledRules[$calledRule]) || in_array ($calledRule, $calledRules[$ruleName]))
				{
					if (!array_key_exists ($calledRule, $groups))
					{
						$groups[$calledRule] = $groupCt++;
					}

					$groupNums[] = $groups[$calledRule];
				}
			}

			if (sizeof ($groupNums) == 0)
			{
				$groups[$ruleName] = $groupCt++;
			}
			elseif (sizeof ($groupNums) == 1)
			{
				$groups[$ruleName] = $groupNums[0];
			}
			else
			{
				$useGroup = $groupNums[0];

				foreach ($groups as $groupMember => $groupNumber)
				{
					if (in_array ($groupNumber, $groupNums))
					{
						$groups[$groupMember] = $useGroup;
					}
				}
			}
		}

		$recGroups = array();

		foreach ($groups as $groupMember => $groupNumber)
		{
			if (!array_key_exists ($groupNumber, $recGroups))
			{
				$recGroups[$groupNumber] = array();
			}

			$recGroups[$groupNumber][] = $groupMember;
		}

		return array_values ($recGroups);
	}

	public function visitNode (Node $node)
	{
		switch ($node -> getNodeType())
		{
			case Node::NODE_TYPE_LIST:
				$this -> visitNode ($node -> getFirst());
				$this -> visitNode ($node -> getRest());
			break;

			case Node::NODE_TYPE_NAME:
				$this -> calledRules[$this -> currentRule -> getName()][] = $node -> getName();

				if ($node -> getName() == $this -> currentRule -> getName())
				{
					$this -> recursiveRules[$node -> getName()] = true;
				}
			break;

			case Node::NODE_TYPE_ACTION:
				$this -> actionRules[$this -> currentRule -> getName()] = true;
			break;

			case Node::NODE_TYPE_ASSERTION:
			case Node::NODE_TYPE_QUANTIFIER:
				$this -> visitNode ($node -> getElement());
			break;

			case Node::NODE_TYPE_MATCH:
			case Node::NODE_TYPE_NIL:
				return;
			break;

			case Node::NODE_TYPE_VARIABLE:
				$element = $node -> getElement();
				$this -> capturedNodes[$element] = true;

				if ($element -> isName())
				{
					$this -> capturedRules[$element -> getName()] = true;
				}

				$this -> visitNode ($element);
			break;

			default:
				throw new \RuntimeException ("Unknown node: ".$node -> getTypeString());
			break;
		}
	}

	public function nodeHasReturnVal (Node $node)
	{
		switch ($node -> getNodeType())
		{
			case Node::NODE_TYPE_ACTION:
				return true;
			break;

			case Node::NODE_TYPE_LIST:
				if ($node -> isSeqList())
				{
					return $this -> sequenceHasReturnVal ($node);
				}
				elseif ($node -> isAltList())
				{
					return $this -> alternateHasReturnVal ($node);
				}
			break;

			case Node::NODE_TYPE_NAME:
				return $this -> actionRules[$node -> getName()];
			break;

			case Node::NODE_TYPE_MATCH:
			case Node::NODE_TYPE_NIL:
				return false;
			break;

			case Node::NODE_TYPE_ASSERTION:
			case Node::NODE_TYPE_QUANTIFIER:
			case Node::NODE_TYPE_VARIABLE:
				return $this -> nodeHasReturnVal ($node -> getElement());
			break;

			default:
				throw new \RuntimeException ("Unknown node type ".$node -> getTypeString());
			break;
		}
	}

	public function alternateHasReturnVal ($list)
	{
		if (!$list -> isAltList())
		{
			throw new \InvalidArgumentException ("List is not an alternate: ".$list -> toString());
		}

		$scan = $list;

		while (!$scan -> isNil())
		{
			if ($this -> nodeHasReturnVal ($scan -> getFirst()))
			{
				return true;
			}

			$scan = $scan -> getRest();
		}

		return false;
	}

	public function sequenceHasReturnVal (Node $list)
	{
		if (!$list -> isSeqList())
		{
			throw new \InvalidArgumentException ("List is not a sequence: ".$list -> toString());
		}

		$scan = $list;

		while (!$scan -> getRest() -> isNil())
		{
			$scan = $scan -> getRest();
		}

		if ($scan -> getFirst() -> isAction())
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	public function expandExpression (Node $node, array &$vars, $ruleName, $returnValue, $varName=NULL)
	{
		switch ($node -> getNodeType())
		{
			case Node::NODE_TYPE_LIST:
				if ($node -> isAltList())
				{
					return $this -> expandAltList ($node, $vars, $ruleName, $returnValue, $varName);
				}
				elseif ($node -> isSeqList())
				{
					return $this -> expandSeqList ($node, $vars, $ruleName, $returnValue, $varName);
				}
				else
				{
					throw new \InvalidArgumentException ("Unknown node type: " . $node -> getTypeString());
				}
			break;

			case Node::NODE_TYPE_ASSERTION:
				return $this -> expandAssertion ($node, $vars, $ruleName, $returnValue, $varName);
			break;

			case Node::NODE_TYPE_NAME:
				if ($varName !== NULL)
				{
					$globalVars = $this -> state -> getConfig() -> getGlobalVariables();

					foreach ($globalVars as $globalVar)
					{
						if ($varName == $globalVar -> getName())
						{
							$this -> state -> logger -> logWarning ("variable name '$varName' (in rule '$ruleName') conflicts with global variable '".$globalVar -> getName()."'");
						}
					}

					if (!array_key_exists ($node -> getName(), $this -> actionRules) || !$this -> actionRules[$node -> getName()])
					{
						$var = new ParseVar ($this -> state -> getUniqueName ($varName), VarType::StringType(), $varName);
						$vars[] = $var;
						return new CaptureExpr (new CallExpr ($node -> getName()), $var);
					}
					else
					{
						$var = new ParseVar ($this -> state -> getUniqueName ($varName), VarType::RuleType ($node -> getName()), $varName);
						$vars[] = $var;
					}
				}
				else
				{
					$var = NULL;
				}

				return new CallExpr ($node -> getName(), $var);
			break;

			case Node::NODE_TYPE_QUANTIFIER:
				$expr = $this -> expandExpression ($node -> getElement(), $vars, $ruleName, $returnValue);

				if ($node -> isStar())
				{
					$minCount = 0;
					$maxCount = -1;
				}
				elseif ($node -> isPlus())
				{
					$minCount = 1;
					$maxCount = -1;
				}
				elseif ($node -> isQuestion())
				{
					$minCount = 0;	
					$maxCount = 1;
				}
				else
				{
					throw new \InvalidArgumentException ("Unknown node ".$node -> toString()." ".__FILE__.", line ".__LINE__);
				}

				if ($varName !== NULL)
				{
					$var = new ParseVar ($this -> state -> getUniqueName ($varName), VarType::ArrayType (VarType::UnknownType()), $varName);
					$vars[] = $var;
				}
				else
				{
					$var = NULL;
				}

				if ($this -> nodeHasReturnVal ($node -> getElement()) && $var !== NULL)
				{
					return new ArrayExpr ($expr, $minCount, $maxCount, $var);
				}
				else
				{
					return new RepeatExpr ($expr, $minCount, $maxCount, $var);
				}
			break;

			case Node::NODE_TYPE_MATCH:
				if ($varName !== NULL)
				{
					$var = new ParseVar ($this -> state -> getUniqueName ($varName), VarType::StringType(), $varName);
					$vars[] = $var;
				}
				else
				{
					$var = NULL;
				}

				if ($node -> isString())
				{
					return new MatchLiteral (new StringLiteral ($node -> getValue()), $var);
				}
				elseif ($node -> isCharacterClass())
				{
					return new MatchLiteral (new CharacterClassLiteral ($node -> getValue()), $var);
				}
				elseif ($node -> isDot())
				{
					return new MatchLiteral (new DotLiteral, $var);
				}
				else
				{
					throw new \InvalidArgumentException ("Unknown node ".$node -> getTypeString()." ".__FILE__.", line ".__LINE__);
				}
			break;

			case Node::NODE_TYPE_VARIABLE:
				//var_dump ($node -> getVar().' '.$node -> getElement() -> getTypeString());
				return $this -> expandExpression ($node -> getElement(), $vars, $ruleName, $returnValue, $node -> getVar());
			break;

			default:
				throw new \InvalidArgumentException ("Unknown node ".$node -> getTypeString()." ".__FILE__.", line ".__LINE__);
			break;
		}
	}

	public function expandRule (Node $rule)
	{
		$vars = array();
		$returnValue = $this -> capturedRules[$rule -> getName()];

		if ($rule -> getElement() -> isAltList())
		{
			return $this -> expandAltList ($rule -> getElement(), $vars, $rule -> getName(), $returnValue);
		}
		elseif ($rule -> getElement() -> isSeqList())
		{
			return $this -> expandSeqList ($rule -> getElement(), $vars, $rule -> getName(), $returnValue);
		}
		else
		{
			if ($returnValue)
			{
				$vars = array();
				$expr = $this -> expandExpression ($rule -> getElement(), $vars, $rule -> getName());
				$var = ParseVar::fromPrefix ('t', VarType::StringType());
				$capture = new CaptureExpr ($expr, $var);
				return new SequenceExpr (array ($capture, new ReturnVar ($var)));
			}
			else
			{
				$expr = $this -> expandExpression ($rule -> getElement(), $vars, $rule -> getName(), $returnValue);
				return $expr;
			}
		}
	}

	public function expandAltList (Node $list, array &$vars, $ruleName, $returnValue, $varName=NULL)
	{
		if (!$list -> isAltList())
		{
			throw new \InvalidArgumentException ("List is not a list of alternates: ".$list -> toString());
		}

		$alts = array();
		$scan = $list;

		while (!$scan -> isNil())
		{
			$altVars = $vars;

			if ($scan -> getFirst() -> isAction())
			{
				$sequence = array();
				$this -> expandReturnAction ($scan -> getFirst(), $altVars, $ruleName, $sequence, $returnValue);
				$alts[] = new SequenceExpr ($sequence);
			}
			else
			{
				$alts[] = $this -> expandExpression ($scan -> getFirst(), $altVars, $ruleName, $returnValue);
			}

			$scan = $scan -> getRest();
		}

		if ($varName === NULL)
		{
			$var = new ParseVar ($this -> state -> getUniqueName ($varName), VarType::UnknownType());
			$vars[] = $var;
		}
		else
		{
			$var = NULL;
		}

		return new ChoiceExpr ($alts, $var);
	}

	public function expandAssertion (Node $assertion, array &$vars, $ruleName, $returnValue, $varName=NULL)
	{
		if ($assertion -> isAssert())
		{
			return new AssertExpr ($this -> expandExpression ($assertion -> getElement(), $vars, $ruleName, $returnValue), false);
		}
		elseif ($assertion -> isAssertNot())
		{
			return new AssertExpr ($this -> expandExpression ($assertion -> getElement(), $vars, $ruleName, $returnValue), true);
		}
		else
		{
			throw new \InvalidArgumentException ("Unknown node ".get_class ($assertion)." ".__FILE__." ".__LINE__);
		}
	}

	public function expandSeqList (Node $list, array &$vars, $ruleName, $returnValue, $varName=NULL)
	{
		$sequence = array();
		$this -> expandSeqListInner ($list, $vars, $ruleName, $sequence, $returnValue);

		if ($varName !== NULL && !$this -> sequenceHasReturnVal ($list))
		{
			//var_dump ($varName);
			$var = new ParseVar ($this -> state -> getUniqueName ($varName), VarType::StringType(), $varName);
			$capture = new CaptureExpr (new SequenceExpr ($sequence), $var);
			return new SequenceExpr (array ($capture, new ReturnVar ($var)));
		}
		else
		{
			if ($varName !== NULL)
			{
				//var_dump ($varName);
				$prevVar = $sequence[sizeof ($sequence) - 1] -> getResultVar();
				$newVar = new ParseVar ($this -> state -> getUniqueName ($varName), $prevVar -> getVarType(), $varName);
				$vars[] = $newVar;
			}
			else
			{
				$newVar = NULL;
			}

			return new SequenceExpr ($sequence, $newVar);
		}
	}

	public function expandReturnAction (Node $action, $vars, $ruleName, &$sequence, $returnValue, $varName=NULL)
	{
		$thunkVars = array();
		$testVars = array_merge ($vars, $this -> state -> getConfig() -> getGlobalVariables());

		if (preg_match ("~\\$\\$~", $action -> getText()) || preg_match ("~\beval\b~i", $action -> getText()))
		{
			$thunkVars = $testVars;
		}
		else
		{
			foreach ($testVars as $var)
			{
				if (preg_match ("~^\s*".preg_quote ("\$".$var -> getSourceName())."\s*$~", $action -> getText()))
				{
					$sequence[] = new ReturnVar ($var);
					return;
				}

				if (preg_match ("~".preg_quote ("\$".$var -> getSourceName())."~i", $action -> getText()))
				{
					$thunkVars[] = $var;
				}
			}
		}

		/*
		$thunkHash = sha1 ($action -> getText());
		if (!array_key_exists ($thunkHash))
		{
			
		}
		*/

		$thunk = new Thunk ($this -> state -> getUniqueName ('Thunk'.$ruleName), $thunkVars, $action -> getText());
		$varType = VarType::ThunkType ($thunk, VarType::UserType());

		if ($varName !== NULL)
		{
			$var = new ParseVar ($this -> state -> getUniqueName ($varName), $varType, $varName);
		}
		else
		{
			$var = new ParseVar ($this -> state -> getUniqueName ('t'), $varType);
		}

		$ret = new ReturnVar ($var);
		$stmt = new MakeThunk ($thunk, $var);
		$sequence[] = $stmt;
		$sequence[] = $ret;
	}

	public function expandSeqListInner (Node $list, &$vars, $ruleName, &$sequence, $returnValue)
	{
		if ($list -> getRest() -> isNil())
		{
			if ($list -> getFirst() -> isAction())
			{
				$this -> expandReturnAction ($list -> getFirst(), $vars, $ruleName, $sequence, $returnValue);
			}
			else
			{
				$sequence[] = $this -> expandExpression ($list -> getFirst(), $vars, $ruleName, $returnValue);
			}
		}
		else
		{
			$sequence[] = $this -> expandExpression ($list -> getFirst(), $vars, $ruleName, $returnValue);
			$this -> expandSeqListInner ($list -> getRest(), $vars, $ruleName, $sequence, $returnValue);
		}
	}
}
