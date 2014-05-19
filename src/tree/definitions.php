<?php

namespace Goat;

interface Definition
{
	public function getNames();
	public function getRule ($ruleName);
	public function replaceRules ($newRules);
}

class NonRecDefinition implements Definition
{
	public function __construct ($name, Expr $expr)
	{
		$this -> name = $name;
		$this -> expr = $expr;
	}

	public function getNames()
	{
		return array ($this -> name);
	}

	public function getRule ($ruleName)
	{
		if ($this -> name !== $ruleName)
		{
			throw new \InvalidArgumentException ("Rule '{$this -> name}' has no rule named '$ruleName'");
		}

		return $this -> expr;
	}

	public function replaceRules ($newRules)
	{
		if (sizeof ($newRules) !== 1)
		{
			throw new \InvalidArgumentException ("Expected array of 1 key/value pair for argument to NonRecDefinition->replaceRules, got ".sizeof ($newRules)." arguments");
		}

		$ruleName = key ($newRules);
		$ruleExpr = $newRules[$ruleName];

		if ($this -> name !== $ruleName)
		{
			throw new \InvalidArgumentException ("Can't replace rule '{$this -> name}' with '$ruleName'");
		}

		if ($ruleExpr === NULL)
		{
			return NULL;
		}

		return new NonRecDefinition ($ruleName, $ruleExpr);
	}
}

class RecDefinition implements Definition
{
	public function __construct ($bindings)
	{
		foreach ($bindings as $key => $value)
		{
			if (!is_string ($key) || !($value instanceof Expr))
			{
				throw new \InvalidArgumentException ("RecDefinition expects array of (string => Expr)");
			}
		}

		$this -> bindings = $bindings;
	}

	public function getNames()
	{
		return array_keys ($this -> bindings);
	}

	public function getRule ($ruleName)
	{
		foreach ($this -> bindings as $name => $expr)
		{
			if ($name === $ruleName)
			{
				return $expr;
			}
		}

		throw new \InvalidArgumentException ("Rule '{$this -> name}' has no rule named '$ruleName'");
	}

	public function replaceRules ($newRules)
	{
		$combinedRules = array();

		foreach ($newRules as $ruleName => $ruleExpr)
		{
			if (!array_key_exists ($ruleName, $this -> bindings))
			{
				throw new \InvalidArgumentException ("No rule named '$ruleName'");
			}

			if ($ruleExpr !== NULL)
			{
				$combinedRules[$ruleName] = $ruleExpr;
			}
		}

		foreach ($this -> bindings as $ruleName => $ruleExpr)
		{
			if (!array_key_exists ($ruleName, $combinedRules) && !array_key_exists ($ruleName, $newRules))
			{
				$combinedRules[$ruleName] = $ruleExpr;
			}
		}

		if (sizeof ($combinedRules) === 0)
		{
			return NULL;
		}

		return new RecDefinition ($combinedRules);
	}
}

class Definitions
{
	public function __construct (array $definitions = NULL)
	{
		if ($definitions === NULL)
		{
			$this -> definitions = array();
		}
		else
		{
			$this -> definitions = $definitions;
		}

		$this -> setNameBindings();
	}

	public function getDefinitions()
	{
		return $this -> definitions;
	}

	public function getRule ($ruleName)
	{
		return $this -> nameBindings[$ruleName] -> getRule ($ruleName);
	}

	public function isRecursive ($ruleName)
	{
		return ($this -> nameBindings[$ruleName] instanceof RecDefinition);
	}

	private function setNameBindings()
	{
		$this -> nameBindings = array();

		foreach ($this -> definitions as $definition)
		{
			foreach ($definition -> getNames() as $name)
			{
				$this -> nameBindings[$name] = $definition;
			}
		}
	}
}
