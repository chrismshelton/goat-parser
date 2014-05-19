<?php

namespace Goat;

class Thunk
{
	protected $name;
	protected $vars;
	protected $text;
	protected $_hasGlobalVars;

	public function __construct ($name, array $vars, $text)
	{
		$this -> name = $name;

		foreach ($vars as $var)
		{
			if (!($var instanceof ParseVar) && !($var instanceof GlobalVar))
			{
				throw new \InvalidArgumentException ("Thunk arguments must be instances of ParseVar");
			}
		}

		$this -> vars = $vars;
		$this -> text = $text;
	}

	public function getName()
	{
		return $this -> name;
	}

	public function getForceParameters()
	{
		return array();
	}

	public function getText()
	{
		return $this -> text;
	}

	public function getUsedVars()
	{
		$varsByName = array();

		foreach ($this -> vars as $var)
		{
			if (!array_key_exists ($var -> getSourceName(), $varsByName))
			{
				$varsByName[$var -> getSourceName()] = array();
			}

			$varsByName[$var -> getSourceName()][] = $var;
		}

		$usedVars = array();
		preg_match_all ('~\$([a-zA-Z_][a-zA-Z_0-9]*)~', $this -> text, $matches);

		foreach (array_unique ($matches[1]) as $notUniqueName)
		{
			if (!array_key_exists ($notUniqueName, $varsByName))
			{
				continue;
			}

			foreach ($varsByName[$notUniqueName] as $notUniqueVar)
			{
				$usedVars[$notUniqueVar -> getName()] = $notUniqueVar;
			}
		}

		return array_values ($usedVars);
	}

	public function hasGlobalVars()
	{
		if ($this -> _hasGlobalVars !== NULL)
		{
			return $this -> _hasGlobalVars;
		}

		foreach ($this -> vars as $var)
		{
			if ($var instanceof GlobalVar)
			{
				$this -> _hasGlobalVars = true;
				return $this -> _hasGlobalVars;
			}
		}

		$this -> _hasGlobalVars = false;
		return $this -> _hasGlobalVars;
	}

	public function swapVars ($vars)
	{
		return new Thunk ($this -> name, $vars, $this -> text);
	}
}
