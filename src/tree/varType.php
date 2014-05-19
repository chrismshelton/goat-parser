<?php

namespace Goat;

final class VarType
{
	const TYPE_BOOL = 1;
	const TYPE_STRING = 2;
	const TYPE_ARRAY = 3;
	const TYPE_THUNK = 4;
	const TYPE_USER = 5;
	const TYPE_POSITION = 6;
	const TYPE_INTEGER = 7;
	const TYPE_REFERENCE = 8;
	const TYPE_UNKNOWN = 9;
	const TYPE_MATCH_RESULT = 10;
	const TYPEOF_RULE = 17;

	private function __construct ($type, $inner = NULL, $inner2 = NULL)
	{
		$this -> type = $type;

		if ($type === self::TYPEOF_RULE)
		{
			if (!is_string ($inner))
			{
				throw new \InvalidArgumentException ("Expected rule name for RuleType argument");
			}

			$this -> type = $type;
			$this -> ruleName = $inner;
		}
		elseif (in_array ($this -> type, array (self::TYPE_ARRAY, self::TYPE_REFERENCE)))
		{
			if ($inner == NULL)
			{
				throw new \InvalidArgumentException ("Expected vartype for thunk/array argument");
			}

			$this -> type = $type;
			$this -> inner = $inner;
		}
		elseif (in_array ($this -> type, array (self::TYPE_THUNK)))
		{
			if (!($inner instanceof Thunk))
			{
				throw new \InvalidArgumentException ("Expected Thunk for ThunkType argument 1");
			}
			if (!($inner2 instanceof VarType))
			{
				throw new \InvalidArgumentException ("Expected VarType for ThunkType argument 2");
			}

			$this -> type = $type;
			$this -> inner = $inner2;
			$this -> thunk = $inner;
		}
		else
		{
			if ($inner != NULL)
			{
				throw new \InvalidArgumentException ("Unexpected vartype argument");
			}

			$this -> type = $type;
		}
	}

	public function getRuleName()
	{
		if ($this -> type !== self::TYPEOF_RULE)
		{
			throw new \RuntimeException ("Not rule type");
		}

		return $this -> ruleName;
	}

	public function isBool()
	{
		return $this -> type === self::TYPE_BOOL;
	}

	public function isReference()
	{
		return $this -> type === self::TYPE_REFERENCE;
	}

	public function isRuleType()
	{
		return $this -> type === self::TYPEOF_RULE;
	}

	public function isString()
	{
		return $this -> type === self::TYPE_STRING;
	}

	public function isUnknown()
	{
		return $this -> type === self::TYPE_UNKNOWN;
	}

	public function toString()
	{
		switch ($this -> type)
		{
			case self::TYPE_BOOL:
				return 'boolean';
			break;

			case self::TYPE_STRING:
				return 'string';
			break;

			case self::TYPE_ARRAY:
				return 'array ('.$this -> inner -> toString().')';
			break;

			case self::TYPE_THUNK:
				return 'thunk ('.$this -> inner -> toString().')';
			break;

			case self::TYPE_USER:
				return 'user';
			break;

			case self::TYPE_POSITION:
				return 'position';
			break;

			case self::TYPE_INTEGER:
				return 'integer';
			break;

			case self::TYPE_MATCH_RESULT:
				return '(preg_match result array)';
			break;

			case self::TYPE_REFERENCE:
				return '&'.$this -> inner -> toString();
			break;

			case self::TYPE_UNKNOWN:
				return 'unknown';
			break;

			case self::TYPEOF_RULE:
				return '@'.$this -> ruleName;
			break;

			default:
				return new \InvalidArgumentException (sprintf ("Unknown VarType %d in %s line %d", $this -> type, __FILE__, __LINE__));
			break;
		}
	}

	public static function ArrayType (VarType $type)
	{
		return new VarType (self::TYPE_ARRAY, $type);
	}

	public static function BooleanType()
	{
		return new VarType (self::TYPE_BOOL);
	}

	public static function IntegerType()
	{
		return new VarType (self::TYPE_INTEGER);
	}

	public static function MatchResultType()
	{
		return new VarType (self::TYPE_MATCH_RESULT);
	}

	public static function PositionType()
	{
		return new VarType (self::TYPE_POSITION);
	}

	public static function ReferenceType (VarType $type)
	{
		return new VarType (self::TYPE_REFERENCE, $type);
	}

	public static function RuleType ($ruleName)
	{
		return new VarType (self::TYPEOF_RULE, $ruleName);
	}

	public static function StringType()
	{
		return new VarType (self::TYPE_STRING);
	}

	public static function ThunkType (Thunk $thunk, VarType $type)
	{
		return new VarType (self::TYPE_THUNK, $thunk, $type);
	}

	public static function UnknownType()
	{
		return new VarType (self::TYPE_UNKNOWN);
	}

	public static function UserType()
	{
		return new VarType (self::TYPE_USER);
	}
}

