<?php

namespace Goat;

class ParseVar
{
	private
		$name,
		$type;

	public function __construct ($name, VarType $type, $sourceName=NULL)
	{
		$this -> name = $name;
		$this -> type = $type;
		$this -> sourceName = $sourceName;
	}

	public function getName()
	{
		return $this -> name;
	}

	public function getSourceName()
	{
		if ($this -> sourceName !== NULL)
		{
			return $this -> sourceName;
		}
		else
		{
			return $this -> name;
		}
	}

	public function getVarType()
	{
		return $this -> type;
	}

	public function isBoolVar()
	{
		return $this -> type -> isBool();
	}

	public function isReferenceVar()
	{
		return $this -> type -> isReference();
	}

	public function isStringVar()
	{
		return $this -> type -> isString();
	}

	public function swapType (VarType $varType)
	{
		return new ParseVar ($this -> name, $varType, $this -> sourceName);
	}

	public static function fromPrefix ($prefix, VarType $type)
	{
		return new ParseVar ($prefix.uniqueId(), $type);
	}
}

