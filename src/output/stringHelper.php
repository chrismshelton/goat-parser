<?php

namespace Goat;

class StringHelper
{
	public function __construct()
	{
		$this -> cachePregCharacterClass = array();
	}

	public function escapeDoubleQuotedString ($text)
	{
		if (defined ('BOOT') && BOOT)
		{
			$replacements = array (
				"\n" => "\\n",
				"\r" => "\\r",
				"\t" => "\\t",
				"\\\"" => "\\\""
			);

			$mat = array();
			$rep = array();

			foreach ($replacements as $key => $value)
			{
				$mat[] = $key;
				$rep[] = $value;
			}

			return str_replace ($mat, $rep, $text);
		}
		else
		{
			$cb = function ($m)
			{
				switch ($m[0])
				{
					case "\\":
						return "\\\\";
					break;

					case "\"":
						return "\\\"";
					break;

					case "\a":	
						return "\\a";
					break;

					case "\b":
						return "\\b";
					break;

					case "\e":
						return "\\033";
					break;

					case "\f":
						return "\\f";
					break;

					case "\n":
						return "\\n";
					break;

					case "\r":
						return "\\r";
					break;

					case "\t":
						return "\\t";
					break;

					case "\v":
						return "\\v";
					break;

					default:
						return sprintf ("\\x%02x", ord ($m[0]));
					break;
				}
			};

			return preg_replace_callback ("~[\x01-\x1f\x80-\xff\\\\\"]~m", $cb, $text);
		}
	}

	public function escapePregCharacterClassArray (array $elems)
	{
		if ($elems[0] == '^')
		{
			$negated = true;
			$escapeElems = array_slice ($elems, 1);
		}
		else
		{
			$negated = false;
			$escapeElems = $elems;
		}

		$chars = array();

		foreach ($escapeElems as $escapeElem)
		{
			if (is_string ($escapeElem) && strlen ($escapeElem) === 1)
			{
				$chars[] = $this -> escapePregCharacter ($escapeElem);
			}
			elseif (is_array ($escapeElem) && sizeof ($escapeElem) === 2)
			{
				$chars[] = $this -> escapePregCharacter ($escapeElem[0]);
				$chars[] = "-";
				$chars[] = $this -> escapePregCharacter ($escapeElem[1]);
			}
			else
			{
				throw new \RuntimeException ("Unknown character class element: ".serialize ($escapeElem));
			}
		}

		$neg = ($negated ? '^' : '');
		$pregFormat = "[".$neg.implode ("", $chars)."]";
		return $pregFormat;
	}

	protected function escapePregCharacter ($character)
	{
		if (in_array ($character, array ('^', '\\', '-', ']', '[', '"')))
		{
			return "\\".$character;
		}

		$ord = ord ($character);

		if ($ord > 31 && $ord < 127)
		{
			return $character;
		}

		return sprintf ("\\x%02x", $ord);
	}


	public function escapePregCharacterClass ($text)
	{
		if (array_key_exists ($text, $this -> cachePregCharacterClass))
		{
			return $this -> cachePregCharacterClass[$text];
		}

		if (
			(property_exists ($this, 'value') && $this -> value !== NULL)
			|| (property_exists ($this, 'length') && $this -> length !== NULL)
			|| (property_exists ($this, 'ix') && $this -> ix !== NULL))
		{
			throw new \RuntimeException ("Error occurred (".__FILE__." on line ".__LINE__.")");
		}

		$pat = array();
		$this -> value = $text;
		$this -> length = strlen ($text);
		$negated = false;
		$this -> ix = 0;

		if ($text[0] == '^')
		{
			$negated = true;
			$this -> ix += 1;
		}

		while ($this -> ix < $this -> length)
		{
			if ($this -> ix + 2 < $this -> length && $text[$this -> ix] !== '\\' && $text[$this -> ix + 1] == '-')
			{
				$start = $this -> escapePregCharacterClassGetc();
				$this -> ix += 1;
				$end = $this -> escapePregCharacterClassGetc();

				$pat[] = $start . '-' . $end;
			}
			else
			{
				$pat[] = $this -> escapePregCharacterClassGetc();
			}
		}

		$this -> value = NULL;
		$this -> length = NULL;
		$this -> ix = NULL;

		$neg = ($negated ? '^' : '');
		$pregFormat = "[".$neg.implode ("", $pat)."]";
		$this -> cachePregCharacterClass[$text] = $pregFormat;
		return $pregFormat;
	}

	protected function escapePregCharacterClassGetc()
	{
		if (!preg_match ('~
			  (\\[\\n"\'abefnrtv\-])
			| (\\[0-3][0-7][0-7])
			| (\\x[A-Fa-f0-9]{2})
			| (.)
			~xAS', $this -> value, $matches, 0, $this -> ix))
		{
			throw new \RuntimeException ("Error at string '".$this -> value."' @ position ".$this -> ix);
		}

		$this -> ix += strlen ($matches[0]);

		switch (sizeof ($matches))
		{
			case 2:
				return $this -> escapeChar ($matches[1][1]);
			break;

			case 3:
				return "\\".$matches[2];
			break;

			case 4:
				return "\\".$matches[3];
			break;

			case 5:
				if (in_array ($matches[4], array ('^', '\\', '-', ']', '[', '"')))
				{
					return "\\".$matches[4];
				}

				return $matches[4];
			break;

			default:
				throw new \RuntimeException ("Oops! ".__FILE__.", ".__LINE__);
			break;
		}
	}
}
