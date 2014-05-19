<?php

namespace Goat;

class LeftRecursionFixer
{
	public function __construct (State $state, $rules)
	{
		$this -> state = $state;
		$this -> currentRule = NULL;
		$this -> debugDump = false;
		$this -> rules = $rules;
		$this -> nodesCurrentlyChecking = new \SplObjectStorage;
		$this -> nodeRulesVisited = new \SplObjectStorage;
		$this -> nodeUsesInput = new \SplObjectStorage;
		$this -> rulesCurrentlyChecking = array();
		$this -> ruleUsesInput = array();
		$this -> otherRulesVisited = array();
	}

	public function canNodeReachRuleWithoutInput (Node $node, $ruleName)
	{
		$rules = $this -> getNodeRulesVisitedWithoutInput ($node);
		return in_array ($ruleName, $rules);
	}

	public function choiceArray (array $nodes)
	{
		$rest = Node::Nil();

		while (sizeof ($nodes) > 0)
		{
			$node = array_pop ($nodes);
			$rest = Node::Choice ($node, $rest);
		}

		return $rest;
	}

	public function findNodeRulesVisitedWithoutInput (Node $node, &$usesInput)
	{
		switch ($node -> getNodeType())
		{
			case Node::NODE_TYPE_MATCH:
				if ($this -> getMinSuccessLength ($node) > 0)
				{
					$usesInput = true;
				}
				else
				{
					//var_dump ($node -> toString()." only uses ".$node -> getMinSuccessLength()." characters");
					$usesInput = false;
				}

				return array();
			break;

			case Node::NODE_TYPE_ASSERTION:
				$childRules = $this -> getNodeRulesVisitedWithoutInput ($node -> getElement(), $usesInput);
				$usesInput = false;
				return $childRules;
			break;

			case Node::NODE_TYPE_QUANTIFIER:
				$childRules = $this -> getNodeRulesVisitedWithoutInput ($node -> getElement(), $childResult);

				if ($node -> isPlus())
				{
					$usesInput = $childResult;
				}
				else
				{
					$usesInput = false;
				}

				return $childRules;
			break;

			case Node::NODE_TYPE_LIST:
				if ($node -> isAltList())
				{
					$rulesFirst = $this -> getNodeRulesVisitedWithoutInput ($node -> getFirst(), $resultFirst);

					if ($node -> getRest() -> isNil())
					{
						$usesInput = $resultFirst;
						return $rulesFirst;
					}

					$rulesRest = $this -> getNodeRulesVisitedWithoutInput ($node -> getRest(), $resultRest);
					$usesInput = ($resultFirst && $resultRest);
					return array_unique (array_merge ($rulesFirst, $rulesRest));
				}
				elseif ($node -> isSeqList())
				{
					$rulesFirst = $this -> getNodeRulesVisitedWithoutInput ($node -> getFirst(), $resultFirst);

					if ($resultFirst)
					{
						$usesInput = $resultFirst;
						return $rulesFirst;
					}
					else
					{
						return array_unique (array_merge ($rulesFirst, $this -> getNodeRulesVisitedWithoutInput ($node -> getRest(), $usesInput)));
					}
				}
			break;

			case Node::NODE_TYPE_NIL:
				$usesInput = false;
				return array();
			break;

			case Node::NODE_TYPE_NAME:
				$childRules = $this -> getRulesVisitedWithoutInput ($this -> rules[$node -> getName()], $usesInput);
				return array_unique (array_merge (array ($node -> getName()), $childRules));
			break;

			case Node::NODE_TYPE_VARIABLE:
				return $this -> getNodeRulesVisitedWithoutInput ($node -> getElement(), $usesInput);
			break;

			case Node::NODE_TYPE_ACTION:
				$usesInput = false;
				return array();
			break;

			default:
				throw new \RuntimeException ("Unknown node ".$node -> getTypeString());
			break;
		}
	}

	public function getMinSuccessLength (Node $node)
	{
		if ($node -> isDot() || $node -> isCharacterClass())
		{
			return 1;
		}
		elseif ($node -> isString())
		{
			return strlen ($node -> getValue());
		}
		else
		{
			throw new \RuntimeException ("Unknown node ".$node -> getTypeString());
		}
	}

	public function getNodeRulesVisitedWithoutInput (Node $node, &$input = NULL)
	{
//		if (array_key_exists ($node -> getNodeId(), $this -> nodeRulesVisited))
		if ($this -> nodeRulesVisited -> contains ($node))
		{
			$input = $this -> nodeUsesInput[$node];
			return $this -> nodeRulesVisited[$node];
		}

//		if (array_key_exists ($node -> getNodeId(), $this -> nodesCurrentlyChecking))
		if ($this -> nodesCurrentlyChecking -> contains ($node))
		{
			// This is definitely not right
			return array(/*$this -> currentRule -> getName()*/);
		}

		$this -> nodesCurrentlyChecking[$node] = 1;
		$nodesVisited = $this -> findNodeRulesVisitedWithoutInput ($node, $input);
		$this -> nodeRulesVisited[$node] = $nodesVisited;
		$this -> nodeUsesInput[$node] = $input;
		$this -> nodesCurrentlyChecking -> detach ($node);
		return $nodesVisited;
	}

	public function getRulesVisitedWithoutInput (Node $rule, &$input = NULL)
	{
		if (!$rule -> isRule())
		{
			incorrect_argument ("RuleNode", 1, $rule);
		}

		if (array_key_exists ($rule -> getName(), $this -> otherRulesVisited))
		{
			$input = $this -> ruleUsesInput[$rule -> getName()];
			return $this -> otherRulesVisited[$rule -> getName()];
		}

		if (array_key_exists ($rule -> getName(), $this -> rulesCurrentlyChecking))
		{
			// This is definitely not right
			return array();
		}

		if ($rule -> getName() == 'Literal')
		{
			$this -> debugDump = true;
		}

		$previousRule = $this -> currentRule;
		$this -> currentRule = $rule;
		$this -> rulesCurrentlyChecking[$rule -> getName()] = 1;
		$input = false;
		$rulesVisited = $this -> getNodeRulesVisitedWithoutInput ($rule -> getElement(), $input);
		$this -> otherRulesVisited[$rule -> getName()] = $rulesVisited;
		$this -> currentRule = $previousRule;

		//var_dump ("Rules visited by `".$rule -> getName().": [".implode (", ", $rulesVisited)."]");
		//var_dump ("Rule `".$rule -> getName()."` consumes input: ".($input ? 'true' : 'false'));

		$this -> ruleUsesInput[$rule -> getName()] = $input;
		return $rulesVisited;
	}

	public function isRuleLeftRecursive (Node $rule)
	{
		if (!$rule -> isRule())
		{
			incorrect_argument ("RuleNode", 1, $rule);
		}

		$rulesVisited = $this -> getRulesVisitedWithoutInput ($rule);
		return in_array ($rule -> getName(), $rulesVisited);
	}

	public function fixLeftRecursiveRule (Node $rule)
	{
		if (!$rule -> isRule())
		{
			incorrect_argument ("RuleNode", 1, $rule);
		}

		if (!$rule -> getElement() -> isAltList())
		{
			throw new \RuntimeException ("Error: don't know how to factor rule `".$rule -> getName()."`");
		}

		$ruleName = $rule -> getName();
		$recNodes = array();
		$fixNodes = array();

		$scan = $rule -> getElement();

		while (!$scan -> isNil())
		{
			if ($this -> canNodeReachRuleWithoutInput ($scan -> getFirst(), $ruleName))
			{
				$recNodes[] = $scan -> getFirst();
			}
			else
			{
				$fixNodes[] = $scan -> getFirst();
			}

			$scan = $scan -> getRest();
		}

		if (sizeof ($fixNodes) == 0)
		{
			throw new \RuntimeException ("Error: don't know how to factor rule `".$ruleName."` - no non-recursive branches");
		}

		$newFixName = $this -> state -> getUniqueName ($ruleName.'fix');
		$newRecName = $this -> state -> getUniqueName ($ruleName.'rec');

		if (sizeof ($fixNodes) == 1)
		{
			$fixRule = Node::Rule ($newFixName, $fixNodes[0]);
		}
		else
		{
			$fixRule = Node::Rule ($newFixName, $this -> choiceArray ($fixNodes));
		}

		$that = $this;
		$newRecNodes = $that -> expandLeftRecursiveNode ($this -> choiceArray ($recNodes), $ruleName, $newRecName);
		$recRule = Node::Rule ($newRecName, $newRecNodes);

		$newFixVar = $this -> state -> getUniqueName ('f');
		$newRecVar = $this -> state -> getUniqueName ('r');

		$newRule = Node::Rule ($ruleName,
			Node::Then (Node::Variable (Node::Name ($newFixName), $newFixVar),
				Node::Then (Node::Variable (Node::Name ($newRecName), $newRecVar),
					Node::Then (Node::Action (" \$$newRecVar (\$$newFixVar) "),
						Node::Nil()))));

		return array ($ruleName => $newRule, $newFixName => $fixRule, $newRecName => $recRule);
	}

	public function expandLeftRecursiveNode (Node $node, $ruleName, $recName, &$input = false, &$var = NULL, &$vars = array())
	{
		if ($node -> isAltList())
		{
			$inputIn = $input;
			$newFirst = $this -> expandLeftRecursiveNode ($node -> getFirst(), $ruleName, $recName, $input);

			if ($node -> getRest() -> isNil())
			{
				$newAction = Node::Action (" function (\$v) { return \$v; } ");
				$newAction -> isFunctionAction = true;
				$newRest = Node::Choice (Node::Then ($newAction, Node::Nil()), Node::Nil());
			}
			else
			{
				$input = $inputIn;
				$newRest = $this -> expandLeftRecursiveNode ($node -> getRest(), $ruleName, $recName, $input);
			}

			if ($newFirst === NULL)
			{
				return $newRest;
			}
			else
			{
				return Node::Choice ($newFirst, $newRest);
			}
		}
		elseif ($node -> isSeqList())
		{
			if ($node -> getFirst() -> isAction() && $node -> getRest() -> isNil())
			{
				$recVar = $this -> state -> getUniqueName ("rest");
				$vars[] = $recVar;

				$newAction = $this -> expandAction ($node -> getFirst(), $recName, $recVar, $var, $vars);
				$newRest = Node::Then (Node::Variable (Node::Name ($recName), $recVar), Node::Then ($newAction, Node::Nil()));
				return $newRest;
			}
			elseif ($node -> getRest() -> isNil())
			{
				$newFirst = $this -> expandLeftRecursiveNode ($node -> getFirst(), $ruleName, $recName, $input, $var, $vars);
				$newRest = Node::Name ($recName);
				$newAction = Node::Action (" function (\$v) { return \$v; } ");
				return Node::Then ($newFirst, Node::Then ($newRest, Node::Then ($newAction, Node::Nil())));
			}
			else
			{
				$this -> getNodeRulesVisitedWithoutInput ($node -> getFirst(), $firstInput);
				$input |= $firstInput;

				if ($firstInput)
				{
					$newFirst = $node -> getFirst();

					if ($node -> getFirst() -> isVariable())
					{
						$vars[] = $node -> getFirst() -> getVar();
					}
				}
				else
				{
					$newFirst = $this -> expandLeftRecursiveNode ($node -> getFirst(), $ruleName, $recName, $input, $var, $vars);
				}

				$newRest = $this -> expandLeftRecursiveNode ($node -> getRest(), $ruleName, $recName, $input, $var, $vars);

				if ($newFirst === NULL)
				{
					return $newRest;
				}
				else
				{
					return Node::Then ($newFirst, $newRest);
				}
			}
		}
		elseif ($node -> isName() && $node -> getName() == $ruleName)
		{
			//if ($node -> isVar())
			//{
			//	$var = $node -> getVar();
			//}

			return NULL;
		}
		elseif ($node -> isName())
		{
			//if ($node -> isVar())
			//{
			//	$vars[] = $node -> getVar();
			//}

			return $node;
		}
		elseif ($node -> isNil())
		{
			return $node;
		}
		elseif ($node -> isVariable())
		{
			if ($node -> getElement() -> isName() && $node -> getElement() -> getName() == $ruleName)
			{
				$var = $node -> getVar();
				return NULL;
			}
			else
			{
				$vars[] = $node -> getVar();
				$newNode = $this -> expandLeftRecursiveNode ($node -> getElement(), $ruleName, $recName, $input, $var, $vars);

				if ($newNode === NULL)
				{
					return NULL;
				}
				else
				{
					return Node::Variable ($newNode, $node -> getVar());
				}
			}
		}
		else
		{
			throw new \RuntimeException ("Unknown node: ".$node -> getTypeString());
		}
	}

	public function expandAction (Node $action, $ruleName, $recVar, $var, $vars)
	{
		if (!$action -> isAction())
		{
			incorrect_argument ("ActionNode", 1, $rule);
		}

		preg_match_all ('~(?P<name>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)~', $action -> getText(), $matches);

		foreach ($matches['name'] as $match)
		{
			$this -> state -> putUsedName ($match);
		}

		$tempVar = $this -> state -> getUniqueName ('temp');
		$refVars = array_map (function ($v) { return '&$'.$v; }, $vars);
		$newText = "function (\$$var) use (".implode (", ", $refVars).") { \$$tempVar = {$action -> getText()}; return \$$recVar (\$$tempVar); }";
		$newAction = Node::Action ($newText);
		return $newAction;
	}

	public function run()
	{
		$leftRecursive = array();
		$notLeftRecursive = array();

		foreach ($this -> rules as $rule)
		{
			if ($this -> isRuleLeftRecursive ($rule))
			{
				$leftRecursive[] = $rule;
			}
			else
			{
				$notLeftRecursive[] = $rule;
			}
		}

		$newLeftRecursive = array();

		foreach ($leftRecursive as $lr)
		{
			$newLeftRecursive = array_merge ($newLeftRecursive, $this -> fixLeftRecursiveRule ($lr));
		}

		return array_merge ($this -> rules, $newLeftRecursive);
	}
}

