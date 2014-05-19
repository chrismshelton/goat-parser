<?php

namespace Goat;

class RuleInfoMap
{
	private $ruleInfo;

	public function __construct()
	{
		$this -> ruleInfo = array();
	}

	public function getRuleInfo ($ruleName)
	{
		if (!array_key_exists ($ruleName, $this -> ruleInfo))
		{
			$this -> ruleInfo[$ruleName] = new RuleInfo ($ruleName);
		}

		return $this -> ruleInfo[$ruleName];
	}

	public function hasRuleInfo ($ruleName)
	{
		return array_key_exists ($ruleName, $this -> ruleInfo);
	}
}
