<?php

namespace Goat;

class RuleInfo
{
	public function __construct ($ruleName)
	{
		$this -> ruleName = $ruleName;
		$this -> exportRule = false;
		$this -> rulesCalledFrom = array();
		$this -> ruleVars = array();
		$this -> totalCallCount = 0;
	}

	public function addCallingRule ($otherRuleName)
	{
		if (!array_key_exists ($otherRuleName, $this -> rulesCalledFrom))
		{
			$this -> rulesCalledFrom[$otherRuleName] = 0;
		}

		$this -> rulesCalledFrom[$otherRuleName] += 1;
		$this -> totalCallCount += 1;
	}

	public function addVar ($var)
	{
		if (!($var instanceof ParseVar) && !($var instanceof GlobalVar))
		{
			incorrect_argument ("Goat\\ParseVar|Goat\\GlobalVar", 1, $var);
		}

		$this -> ruleVars[$var -> getName()] = $var;
	}

	public function isCalled()
	{
		return (sizeof ($this -> rulesCalledFrom) > 0);
	}

	public function isExported()
	{
		return $this -> exportRule;
	}

	public function getCallingRules()
	{
		return array_keys ($this -> rulesCalledFrom);
	}

	public function getVars()
	{
		return array_values ($this -> ruleReturnVars);
	}

	public function setExportRule()
	{
		$this -> exportRule = true;
	}
}
