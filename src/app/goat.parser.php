<?php

namespace Goat;

class GrammarParser
{
	protected $text;
	protected $length;
	protected $errorId;
	protected $errorPosition;

	protected function runParser ($startFunc, $text, $globalVars=NULL)
	{
		global $argv;
		$this -> text = $text;
		$this -> length = strlen ($text);
		$this -> errorId = -1;
		$this -> errorPosition = -1;
		$this -> globalVars = $globalVars;
		$position = 0;
		$value = NULL;
		if (!$this -> $startFunc ($position, $value))
		{
			return $this -> raiseError();
		}

		return $value -> force();
	}

	public function parseGrammar ($text, Config $config)
	{
		return $this -> runParser ("parseRuleGrammar", $text, array ($config));
	}

	public function parseDirective ($text, Config $config)
	{
		return $this -> runParser ("parseRuleDirective", $text, array ($config));
	}

	public function parseRuleGrammar (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$p7g = $position;

		if (!$this -> parseRulewhitespace ($position, $line, $column))
		{
			$position = $p7g;
			return false;
		}

		$p7m = $position;
		$dz = new ArrEmptyThunk7r ($this -> globalVars);
		$sp7w = $position;
		$n7v = 0;
		$p8z = $position;
		goto ir98;

		ir98:
		{
			$p99 = $position;
			// RegExp
			if (!preg_match ("~(?P<id1i>(?:[a-zA-Z_])(?:(?:(?:[A-Za-z_0-9])*)))~AS", $this -> text, $mpo, 0, $position))
			{
				$position = $p99;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				goto sq90f92;
			}

			$position += strlen ($mpo[0]);
			$id1i = $mpo["id1i"];

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $p99;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				goto sq90f92;
			}

			$n11 = $id1i;
			goto ir93;
		}

		sq90f92:
		{
			$position = $p8z;
			$p7z = $position;
			$p84 = $position;
			goto ir8u;
		}

		ir93:
		{
			$p94 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "=")
			{
				$position = $p94;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 2;
					$this -> errorPosition = $position;
				}
				goto sq90f92;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $p94;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 2;
					$this -> errorPosition = $position;
				}
				goto sq90f92;
			}

			goto sq95s96;
		}

		ir8u:
		{
			$p8v = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "%")
			{
				$position = $p8v;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 3;
					$this -> errorPosition = $position;
				}
				goto sq85f87;
			}

			$position += 1;

			if (!$this -> parseRulehspace ($position, $line, $column))
			{
				$position = $p8v;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 3;
					$this -> errorPosition = $position;
				}
				goto sq85f87;
			}

			goto ir88;
		}

		sq95s96:
		{
			if (!$this -> parseRuleexpression ($position, $e12, $line, $column))
			{
				$position = $p8z;
				$p7z = $position;
				$p84 = $position;
				goto ir8u;
			}

			//  \Goat\Node::Rule ($n, $e) 
			$t14 = new ThunkDefinition13 ($n11, $e12, $this -> globalVars);
			$res7q = $t14;
			$dz = new ArrThunk7s ($dz, $res7q, $this -> globalVars);
			$n7v += 1;
			goto ls7t;
		}

		sq85f87:
		{
			$position = $p84;
			if ($position > $this -> errorPosition)
			{
				$this -> errorId = 4;
				$this -> errorPosition = $position;
			}
			$position = $p7z;
			if ($position > $this -> errorPosition)
			{
				$this -> errorId = 5;
				$this -> errorPosition = $position;
			}
			goto lf7u;
		}

		ir88:
		{
			$p8q = $position;

			if (!$this -> parseRuleglobaldirective ($position, $d5, $line, $column))
			{
				goto sq8rf8t;
			}

			$d3 = $d5;
			$d15 = $d3;
			$res7q = $d15;
			$dz = new ArrThunk7s ($dz, $res7q, $this -> globalVars);
			$n7v += 1;
			goto ls7t;
		}

		ls7t:
		{
			$p8z = $position;
			goto ir98;
		}

		lf7u:
		{
			if (($n7v < 1))
			{
				// dropped set position = sp7w
				$position = $p7m;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 6;
					$this -> errorPosition = $position;
				}
				$position = $p7g;
				return false;
			}

			$d1 = $dz;
			goto ir7k;
		}

		sq8rf8t:
		{
			$position = $p8q;
			$p8m = $position;

			if (!$this -> parseRuleinlinedirective ($position, $d6, $line, $column))
			{
				goto sq8nf8p;
			}

			$d3 = $d6;
			$d15 = $d3;
			goto sq85s86;
		}

		ir7k:
		{
			// RegExp
			if (!preg_match ("~(?:(?:(?:(?!.))))~AS", $this -> text, $mpp, 0, $position))
			{
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 7;
					$this -> errorPosition = $position;
				}
				$position = $p7g;
				return false;
			}

			$position += strlen ($mpp[0]);
			$value = $d1;
			return true;
		}

		sq8nf8p:
		{
			$position = $p8m;
			$p8i = $position;

			if (!$this -> parseRulensdirective ($position, $d7, $line, $column))
			{
				goto sq8jf8l;
			}

			$d3 = $d7;
			$d15 = $d3;
			goto sq85s86;
		}

		sq85s86:
		{
			$res7q = $d15;
			$dz = new ArrThunk7s ($dz, $res7q, $this -> globalVars);
			$n7v += 1;
			goto ls7t;
		}

		sq8jf8l:
		{
			$position = $p8i;
			$p8e = $position;

			if (!$this -> parseRuleclassdirective ($position, $d8, $line, $column))
			{
				goto sq8ff8h;
			}

			$d3 = $d8;
			$d15 = $d3;
			goto sq85s86;
		}

		sq8ff8h:
		{
			$position = $p8e;
			$p8a = $position;

			if (!$this -> parseRuletopdirective ($position, $d9, $line, $column))
			{
				$position = $p8a;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 8;
					$this -> errorPosition = $position;
				}
				$position = $p84;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 4;
					$this -> errorPosition = $position;
				}
				$position = $p7z;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 5;
					$this -> errorPosition = $position;
				}
				goto lf7u;
			}

			$d3 = $d9;
			$d15 = $d3;
			goto sq85s86;
		}
	}

	public function parseRuleglobaldirective (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$p9u = $position;
		goto ira8;

		ira8:
		{
			$pa9 = $position;
			// Literal
			if ($position >= $this -> length || substr_compare ($this -> text, "global", $position, 6) !== 0)
			{
				$position = $pa9;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 9;
					$this -> errorPosition = $position;
				}
				goto sq9vf9x;
			}

			$position += 6;

			if (!$this -> parseRulehspace ($position, $line, $column))
			{
				$position = $pa9;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 9;
					$this -> errorPosition = $position;
				}
				goto sq9vf9x;
			}

			goto ira3;
		}

		sq9vf9x:
		{
			$position = $p9u;
			$p9g = $position;
			goto ir9p;
		}

		ira3:
		{
			$pa4 = $position;
			// RegExp
			if (!preg_match ("~(?P<id1k>(?:[a-zA-Z_])(?:(?:(?:[A-Za-z_0-9])*)))~AS", $this -> text, $mpq, 0, $position))
			{
				$position = $pa4;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 10;
					$this -> errorPosition = $position;
				}
				goto sq9vf9x;
			}

			$position += strlen ($mpq[0]);
			$id1k = $mpq["id1k"];

			if (!$this -> parseRulehspace ($position, $line, $column))
			{
				$position = $pa4;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 10;
					$this -> errorPosition = $position;
				}
				goto sq9vf9x;
			}

			$nameb = $id1k;
			goto ir9y;
		}

		ir9p:
		{
			$p9q = $position;
			// Literal
			if ($position >= $this -> length || substr_compare ($this -> text, "global", $position, 6) !== 0)
			{
				$position = $p9q;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 9;
					$this -> errorPosition = $position;
				}
				goto sq9hf9j;
			}

			$position += 6;

			if (!$this -> parseRulehspace ($position, $line, $column))
			{
				$position = $p9q;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 9;
					$this -> errorPosition = $position;
				}
				goto sq9hf9j;
			}

			goto ir9k;
		}

		ir9y:
		{
			$p9z = $position;
			// RegExp
			if (!preg_match ("~(?P<id1i>(?:[a-zA-Z_])(?:(?:(?:[A-Za-z_0-9])*)))~AS", $this -> text, $mpr, 0, $position))
			{
				$position = $p9z;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $p9u;
				$p9g = $position;
				goto ir9p;
			}

			$position += strlen ($mpr[0]);
			$id1i = $mpr["id1i"];

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $p9z;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $p9u;
				$p9g = $position;
				goto ir9p;
			}

			$typec = $id1i;
			//  \Goat\Node::Option ("global", $name, $type) 
			$te = new Thunkglobaldirectived ($nameb, $typec, $this -> globalVars);
			$value = $te;
			return true;
		}

		sq9hf9j:
		{
			$position = $p9g;
			return false;
		}

		ir9k:
		{
			$p9l = $position;
			// RegExp
			if (!preg_match ("~(?P<id1i>(?:[a-zA-Z_])(?:(?:(?:[A-Za-z_0-9])*)))~AS", $this -> text, $mps, 0, $position))
			{
				$position = $p9l;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $p9g;
				return false;
			}

			$position += strlen ($mps[0]);
			$id1i = $mps["id1i"];

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $p9l;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $p9g;
				return false;
			}

			$namef = $id1i;
			//  \Goat\Node::Option ("global", $name) 
			$th = new Thunkglobaldirectiveg ($namef, $this -> globalVars);
			$value = $th;
			goto s9d;
		}

		s9d:
		{
			return true;
		}
	}

	public function parseRuleinlinedirective (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$paf = $position;
		goto irao;

		irao:
		{
			$pap = $position;
			// Literal
			if ($position >= $this -> length || substr_compare ($this -> text, "inline", $position, 6) !== 0)
			{
				$position = $pap;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 11;
					$this -> errorPosition = $position;
				}
				goto sqagfai;
			}

			$position += 6;

			if (!$this -> parseRulehspace ($position, $line, $column))
			{
				$position = $pap;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 11;
					$this -> errorPosition = $position;
				}
				goto sqagfai;
			}

			goto iraj;
		}

		sqagfai:
		{
			$position = $paf;
			return false;
		}

		iraj:
		{
			$pak = $position;
			// RegExp
			if (!preg_match ("~(?P<id1i>(?:[a-zA-Z_])(?:(?:(?:[A-Za-z_0-9])*)))~AS", $this -> text, $mpt, 0, $position))
			{
				$position = $pak;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $paf;
				return false;
			}

			$position += strlen ($mpt[0]);
			$id1i = $mpt["id1i"];

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pak;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $paf;
				return false;
			}

			$namej = $id1i;
			//  \Goat\Node::Option ("inline", $name) 
			$tl = new Thunkinlinedirectivek ($namej, $this -> globalVars);
			$value = $tl;
			return true;
		}
	}

	public function parseRulensdirective (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$pav = $position;
		goto irb4;

		irb4:
		{
			$pb5 = $position;
			// Literal
			if ($position >= $this -> length || substr_compare ($this -> text, "namespace", $position, 9) !== 0)
			{
				$position = $pb5;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 12;
					$this -> errorPosition = $position;
				}
				goto sqawfay;
			}

			$position += 9;

			if (!$this -> parseRulehspace ($position, $line, $column))
			{
				$position = $pb5;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 12;
					$this -> errorPosition = $position;
				}
				goto sqawfay;
			}

			goto iraz;
		}

		sqawfay:
		{
			$position = $pav;
			return false;
		}

		iraz:
		{
			$pb0 = $position;
			// RegExp
			if (!preg_match ("~(?P<id1i>(?:[a-zA-Z_])(?:(?:(?:[A-Za-z_0-9])*)))~AS", $this -> text, $mpu, 0, $position))
			{
				$position = $pb0;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $pav;
				return false;
			}

			$position += strlen ($mpu[0]);
			$id1i = $mpu["id1i"];

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pb0;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $pav;
				return false;
			}

			$namen = $id1i;
			//  \Goat\Node::Option ("namespace", $name) 
			$tp = new Thunknsdirectiveo ($namen, $this -> globalVars);
			$value = $tp;
			return true;
		}
	}

	public function parseRuleclassdirective (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$pbb = $position;
		goto irbk;

		irbk:
		{
			$pbl = $position;
			// Literal
			if ($position >= $this -> length || substr_compare ($this -> text, "class", $position, 5) !== 0)
			{
				$position = $pbl;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 13;
					$this -> errorPosition = $position;
				}
				goto sqbcfbe;
			}

			$position += 5;

			if (!$this -> parseRulehspace ($position, $line, $column))
			{
				$position = $pbl;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 13;
					$this -> errorPosition = $position;
				}
				goto sqbcfbe;
			}

			goto irbf;
		}

		sqbcfbe:
		{
			$position = $pbb;
			return false;
		}

		irbf:
		{
			$pbg = $position;
			// RegExp
			if (!preg_match ("~(?P<id1i>(?:[a-zA-Z_])(?:(?:(?:[A-Za-z_0-9])*)))~AS", $this -> text, $mpv, 0, $position))
			{
				$position = $pbg;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $pbb;
				return false;
			}

			$position += strlen ($mpv[0]);
			$id1i = $mpv["id1i"];

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pbg;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $pbb;
				return false;
			}

			$namer = $id1i;
			//  \Goat\Node::Option ("class", $name) 
			$tt = new Thunkclassdirectives ($namer, $this -> globalVars);
			$value = $tt;
			return true;
		}
	}

	public function parseRuletopdirective (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$pbr = $position;
		goto irc0;

		irc0:
		{
			$pc1 = $position;
			// Literal
			if ($position >= $this -> length || substr_compare ($this -> text, "top", $position, 3) !== 0)
			{
				$position = $pc1;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 14;
					$this -> errorPosition = $position;
				}
				goto sqbsfbu;
			}

			$position += 3;

			if (!$this -> parseRulehspace ($position, $line, $column))
			{
				$position = $pc1;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 14;
					$this -> errorPosition = $position;
				}
				goto sqbsfbu;
			}

			goto irbv;
		}

		sqbsfbu:
		{
			$position = $pbr;
			return false;
		}

		irbv:
		{
			$pbw = $position;
			// RegExp
			if (!preg_match ("~(?P<id1i>(?:[a-zA-Z_])(?:(?:(?:[A-Za-z_0-9])*)))~AS", $this -> text, $mpw, 0, $position))
			{
				$position = $pbw;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $pbr;
				return false;
			}

			$position += strlen ($mpw[0]);
			$id1i = $mpw["id1i"];

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pbw;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $pbr;
				return false;
			}

			$namev = $id1i;
			//  \Goat\Node::Option ("top", $name) 
			$tx = new Thunktopdirectivew ($namev, $this -> globalVars);
			$value = $tx;
			return true;
		}
	}

	public function parseRuleoctchrs (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		// RegExp
		if (!preg_match ("~(?:[0-3])(?:[0-7])(?:[0-7])~AS", $this -> text, $mpx, 0, $position))
		{
			return false;
		}

		$position += strlen ($mpx[0]);
		return true;
	}

	public function parseRuleChar (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$pce = $position;
		goto irci;

		irci:
		{
			$pcj = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "\\")
			{
				$position = $pcj;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 15;
					$this -> errorPosition = $position;
				}
				goto sqcffch;
			}

			$position += 1;
			$pdu = $position;
			$pdz = $position;
			goto ire3;
		}

		sqcffch:
		{
			$position = $pce;
			$pca = $position;
			// RegExp
			if (!preg_match ("~(?P<c3l>(?:(?:(?:[^\]]))))~AS", $this -> text, $mpy, 0, $position))
			{
				$position = $pca;
				return false;
			}

			$position += strlen ($mpy[0]);
			$c3l = $mpy["c3l"];
			$value = $c3l;
			goto sc7;
		}

		ire3:
		{
			$peh = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "\\")
			{
				goto sqeifek;
			}

			$position += 1;
			//  "\\" 
			$t4h = new Thunkccesc34g ($this -> globalVars);
			$c4e = $t4h;
			$l3q = $c4e;
			$e3o = $l3q;
			$e3k = $e3o;
			$value = $e3k;
			return true;
		}

		sc7:
		{
			return true;
		}

		sqeifek:
		{
			$position = $peh;
			$ped = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "[")
			{
				goto sqeefeg;
			}

			$position += 1;
			//  "[" 
			$t4j = new Thunkccesc34i ($this -> globalVars);
			$c4e = $t4j;
			$l3q = $c4e;
			goto sqe0se1;
		}

		sqeefeg:
		{
			$position = $ped;
			$pe9 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "]")
			{
				goto sqeafec;
			}

			$position += 1;
			//  "]" 
			$t4l = new Thunkccesc34k ($this -> globalVars);
			$c4e = $t4l;
			$l3q = $c4e;
			goto sqe0se1;
		}

		sqe0se1:
		{
			$e3o = $l3q;
			$e3k = $e3o;
			$value = $e3k;
			return true;
		}

		sqeafec:
		{
			$position = $pe9;
			$pe5 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "-")
			{
				$position = $pe5;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 16;
					$this -> errorPosition = $position;
				}
				$position = $pdz;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 17;
					$this -> errorPosition = $position;
				}
				goto sqdvfdx;
			}

			$position += 1;
			//  "-" 
			$t4n = new Thunkccesc34m ($this -> globalVars);
			$c4e = $t4n;
			$l3q = $c4e;
			goto sqe0se1;
		}

		sqdvfdx:
		{
			$position = $pdu;
			$pdq = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "a")
			{
				goto sqdrfdt;
			}

			$position += 1;
			//  "\a" 
			$t3s = new Thunkccesc13r ($this -> globalVars);
			$e3o = $t3s;
			$e3k = $e3o;
			goto sqckscl;
		}

		sqdrfdt:
		{
			$position = $pdq;
			$pdm = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "b")
			{
				goto sqdnfdp;
			}

			$position += 1;
			//  "\b" 
			$t3u = new Thunkccesc13t ($this -> globalVars);
			$e3o = $t3u;
			$e3k = $e3o;
			goto sqckscl;
		}

		sqckscl:
		{
			$value = $e3k;
			return true;
		}

		sqdnfdp:
		{
			$position = $pdm;
			$pdi = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "e")
			{
				goto sqdjfdl;
			}

			$position += 1;
			//  "\033" 
			$t3w = new Thunkccesc13v ($this -> globalVars);
			$e3o = $t3w;
			$e3k = $e3o;
			goto sqckscl;
		}

		sqdjfdl:
		{
			$position = $pdi;
			$pde = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "f")
			{
				goto sqdffdh;
			}

			$position += 1;
			//  "\f" 
			$t3y = new Thunkccesc13x ($this -> globalVars);
			$e3o = $t3y;
			$e3k = $e3o;
			goto sqckscl;
		}

		sqdffdh:
		{
			$position = $pde;
			$pda = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "n")
			{
				goto sqdbfdd;
			}

			$position += 1;
			//  "\n" 
			$t40 = new Thunkccesc13z ($this -> globalVars);
			$e3o = $t40;
			$e3k = $e3o;
			goto sqckscl;
		}

		sqdbfdd:
		{
			$position = $pda;
			$pd6 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "r")
			{
				goto sqd7fd9;
			}

			$position += 1;
			//  "\r" 
			$t42 = new Thunkccesc141 ($this -> globalVars);
			$e3o = $t42;
			$e3k = $e3o;
			goto sqckscl;
		}

		sqd7fd9:
		{
			$position = $pd6;
			$pd2 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "t")
			{
				goto sqd3fd5;
			}

			$position += 1;
			//  "\t" 
			$t44 = new Thunkccesc143 ($this -> globalVars);
			$e3o = $t44;
			$e3k = $e3o;
			goto sqckscl;
		}

		sqd3fd5:
		{
			$position = $pd2;
			$pcy = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "v")
			{
				goto sqczfd1;
			}

			$position += 1;
			//  "\v" 
			$t46 = new Thunkccesc145 ($this -> globalVars);
			$e3o = $t46;
			$e3k = $e3o;
			goto sqckscl;
		}

		sqczfd1:
		{
			$position = $pcy;
			$pcu = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "x")
			{
				goto sqcvfcx;
			}

			$position += 1;
			// RegExp
			if (!preg_match ("~(?P<x47>(?:[A-Fa-f0-9])(?:[A-Fa-f0-9]))~AS", $this -> text, $mpz, 0, $position))
			{
				goto sqcvfcx;
			}

			$position += strlen ($mpz[0]);
			$x47 = $mpz["x47"];
			//  chr(hexdec($x)) 
			$t49 = new Thunkccesc148 ($x47, $this -> globalVars);
			$e3o = $t49;
			$e3k = $e3o;
			goto sqckscl;
		}

		sqcvfcx:
		{
			$position = $pcu;
			$pcp = $position;
			$cpct = $position;

			if (!$this -> parseRuleoctchrs ($position, $line, $column))
			{
				$position = $pcp;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 18;
					$this -> errorPosition = $position;
				}
				$position = $pcj;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 15;
					$this -> errorPosition = $position;
				}
				goto sqcffch;
			}

			$o4a = substr ($this -> text, $cpct, $position - $cpct);
			//  chr(octdec($o)) 
			$t4c = new Thunkccesc14b ($o4a, $this -> globalVars);
			$e3o = $t4c;
			$e3k = $e3o;
			goto sqckscl;
		}
	}

	public function parseRulewhitespace (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		// RegExp
		if (!preg_match ("~(?:(?:(?:(?:(?:(?:(?: ))|(?:(?:\t))|(?:(?:(?:\r\n)|(?:\n)|(?:\r)))))|(?:(?:#)(?:(?:(?:[^\x0d\x0a])*))(?:(?:(?:(?:(?:\r\n)|(?:\n)|(?:\r)))|(?:(?!.)))))))*)~mAS", $this -> text, $mq0, 0, $position))
		{
			return false;
		}

		$position += strlen ($mq0[0]);
		return true;
	}

	public function parseRulehspace (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		// RegExp
		if (!preg_match ("~(?:(?:(?:(?:(?: ))|(?:(?:\t))))*)~AS", $this -> text, $mq1, 0, $position))
		{
			return false;
		}

		$position += strlen ($mq1[0]);
		return true;
	}

	public function parseRuleaction (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$per = $position;
		goto irev;

		irev:
		{
			$pew = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "{")
			{
				$position = $pew;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 19;
					$this -> errorPosition = $position;
				}
				goto sqesfeu;
			}

			$position += 1;
			$cpf0 = $position;

			if (!$this -> parseRulenested_curly_braces ($position, $line, $column))
			{
				$position = $pew;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 19;
					$this -> errorPosition = $position;
				}
				goto sqesfeu;
			}

			$t5j = substr ($this -> text, $cpf0, $position - $cpf0);
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "}")
			{
				$position = $pew;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 19;
					$this -> errorPosition = $position;
				}
				goto sqesfeu;
			}

			$position += 1;
			$act5h = $t5j;
			goto sqexsey;
		}

		sqesfeu:
		{
			$position = $per;
			return false;
		}

		sqexsey:
		{
			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $per;
				return false;
			}

			$value = $act5h;
			return true;
		}
	}

	public function parseRulePrimary (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$pkr = $position;
		goto irl1;

		irl1:
		{
			$pl2 = $position;
			// RegExp
			if (!preg_match ("~(?P<id1i>(?:[a-zA-Z_])(?:(?:(?:[A-Za-z_0-9])*)))~AS", $this -> text, $mq2, 0, $position))
			{
				$position = $pl2;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				goto sqksfku;
			}

			$position += strlen ($mq2[0]);
			$id1i = $mq2["id1i"];

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pl2;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				goto sqksfku;
			}

			$rule6o = $id1i;
			$spkv = $position;
			goto irkw;
		}

		sqksfku:
		{
			$position = $pkr;
			$pkd = $position;
			goto irkm;
		}

		irkw:
		{
			$pkx = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "=")
			{
				$position = $pkx;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 2;
					$this -> errorPosition = $position;
				}
				$position = $spkv;
				//  \Goat\Node::Name ($rule) 
				$t6q = new ThunkPrimary6p ($rule6o, $this -> globalVars);
				$value = $t6q;
				goto sf1;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pkx;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 2;
					$this -> errorPosition = $position;
				}
				$position = $spkv;
				//  \Goat\Node::Name ($rule) 
				$t6q = new ThunkPrimary6p ($rule6o, $this -> globalVars);
				$value = $t6q;
				goto sf1;
			}

			// dropped set position = spkv
			$position = $pkr;
			$pkd = $position;
			goto irkm;
		}

		irkm:
		{
			$pkn = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "(")
			{
				$position = $pkn;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 20;
					$this -> errorPosition = $position;
				}
				goto sqkefkg;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pkn;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 20;
					$this -> errorPosition = $position;
				}
				goto sqkefkg;
			}

			goto sqkoskp;
		}

		sf1:
		{
			return true;
		}

		sqkefkg:
		{
			$position = $pkd;
			$pjz = $position;
			goto irk8;
		}

		sqkoskp:
		{
			if (!$this -> parseRuleexpression ($position, $e6r, $line, $column))
			{
				$position = $pkd;
				$pjz = $position;
				goto irk8;
			}

			goto irkh;
		}

		irk8:
		{
			$pk9 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "<")
			{
				$position = $pk9;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 21;
					$this -> errorPosition = $position;
				}
				goto sqk0fk2;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pk9;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 21;
					$this -> errorPosition = $position;
				}
				goto sqk0fk2;
			}

			goto sqkaskb;
		}

		irkh:
		{
			$pki = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== ")")
			{
				$position = $pki;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 22;
					$this -> errorPosition = $position;
				}
				$position = $pkd;
				$pjz = $position;
				goto irk8;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pki;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 22;
					$this -> errorPosition = $position;
				}
				$position = $pkd;
				$pjz = $position;
				goto irk8;
			}

			$value = $e6r;
			return true;
		}

		sqk0fk2:
		{
			$position = $pjz;
			$pgh = $position;
			$piv = $position;
			goto iriz;
		}

		sqkaskb:
		{
			if (!$this -> parseRuleexpression ($position, $e6s, $line, $column))
			{
				$position = $pjz;
				$pgh = $position;
				$piv = $position;
				goto iriz;
			}

			goto irk3;
		}

		iriz:
		{
			$pj0 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "'")
			{
				$position = $pj0;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 23;
					$this -> errorPosition = $position;
				}
				$position = $piv;
				$pgn = $position;
				goto irgr;
			}

			$position += 1;
			$s1q = new ArrEmptyThunkj5 ($this -> globalVars);
			$pjf = $position;
			$cpjj = $position;
			goto irjk;
		}

		irk3:
		{
			$pk4 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== ">")
			{
				$position = $pk4;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 24;
					$this -> errorPosition = $position;
				}
				$position = $pjz;
				$pgh = $position;
				$piv = $position;
				goto iriz;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pk4;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 24;
					$this -> errorPosition = $position;
				}
				$position = $pjz;
				$pgh = $position;
				$piv = $position;
				goto iriz;
			}

			//  \Goat\Node::Capture ($e) 
			$t6u = new ThunkPrimary6t ($e6s, $this -> globalVars);
			$value = $t6u;
			goto sf1;
		}

		irgr:
		{
			$pgs = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "\"")
			{
				$position = $pgs;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 25;
					$this -> errorPosition = $position;
				}
				$position = $pgn;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 26;
					$this -> errorPosition = $position;
				}
				$position = $pgh;
				$pfh = $position;
				goto irfl;
			}

			$position += 1;
			$d1u = new ArrEmptyThunkgx ($this -> globalVars);
			$ph7 = $position;
			goto irhb;
		}

		irjk:
		{
			$pjl = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "\\")
			{
				$position = $pjl;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 27;
					$this -> errorPosition = $position;
				}
				goto sqjgfji;
			}

			$position += 1;
			goto irjp;
		}

		irfl:
		{
			$pfm = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "[")
			{
				$position = $pfm;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 28;
					$this -> errorPosition = $position;
				}
				$position = $pfh;
				$pf8 = $position;
				goto irfc;
			}

			$position += 1;
			$pfr = $position;
			$pfw = $position;
			$r3c = new ArrEmptyThunkg1 ($this -> globalVars);
			$spg6 = $position;
			$ng5 = 0;
			goto irg7;
		}

		irhb:
		{
			$phc = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "\\")
			{
				$position = $phc;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 29;
					$this -> errorPosition = $position;
				}
				goto sqh8fha;
			}

			$position += 1;
			goto irhg;
		}

		sqjgfji:
		{
			$position = $pjf;
			$pjb = $position;
			// RegExp
			if (!preg_match ("~(?P<ndq1z>(?:(?:(?:[^']))))~AS", $this -> text, $mq3, 0, $position))
			{
				$position = $pjb;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 30;
					$this -> errorPosition = $position;
				}
				goto lfj8;
			}

			$position += strlen ($mq3[0]);
			$ndq1z = $mq3["ndq1z"];
			$resj4 = $ndq1z;
			$s1q = new ArrThunkj6 ($s1q, $resj4, $this -> globalVars);
			goto lsj7;
		}

		irjp:
		{
			$pjv = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "\\")
			{
				goto sqjwfjy;
			}

			$position += 1;
			//  "\\" 
			$t25 = new Thunksqesc124 ($this -> globalVars);
			$e22 = $t25;
			$e1y = substr ($this -> text, $cpjj, $position - $cpjj);
			$resj4 = $e1y;
			$s1q = new ArrThunkj6 ($s1q, $resj4, $this -> globalVars);
			goto lsj7;
		}

		irfc:
		{
			$pfd = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== ".")
			{
				$position = $pfd;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 31;
					$this -> errorPosition = $position;
				}
				goto sqf9ffb;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pfd;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 31;
					$this -> errorPosition = $position;
				}
				goto sqf9ffb;
			}

			//  \Goat\Node::Dot() 
			$t72 = new ThunkPrimary71 ($this -> globalVars);
			$value = $t72;
			goto sf1;
		}

		irg7:
		{
			$pgd = $position;

			if (!$this -> parseRuleChar ($position, $a3e, $line, $column))
			{
				goto sqgefgg;
			}

			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "-")
			{
				goto sqgefgg;
			}

			$position += 1;

			if (!$this -> parseRuleChar ($position, $b3f, $line, $column))
			{
				goto sqgefgg;
			}

			//  array ($a, $b) 
			$t3h = new ThunkRange3g ($a3e, $b3f, $this -> globalVars);
			$resg0 = $t3h;
			$r3c = new ArrThunkg2 ($r3c, $resg0, $this -> globalVars);
			$ng5 += 1;
			goto lsg3;
		}

		sqh8fha:
		{
			$position = $ph7;
			$ph3 = $position;
			// RegExp
			if (!preg_match ("~(?P<ndq2a>(?:(?:(?:[^\"]))))~AS", $this -> text, $mq4, 0, $position))
			{
				$position = $ph3;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 32;
					$this -> errorPosition = $position;
				}
				goto lfh0;
			}

			$position += strlen ($mq4[0]);
			$ndq2a = $mq4["ndq2a"];
			$resgw = $ndq2a;
			$d1u = new ArrThunkgy ($d1u, $resgw, $this -> globalVars);
			goto lsgz;
		}

		irhg:
		{
			$pir = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "a")
			{
				goto sqisfiu;
			}

			$position += 1;
			//  "\a" 
			$t2g = new Thunkdqesc12f ($this -> globalVars);
			$e2d = $t2g;
			$e29 = $e2d;
			$resgw = $e29;
			$d1u = new ArrThunkgy ($d1u, $resgw, $this -> globalVars);
			goto lsgz;
		}

		lfj8:
		{
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "'")
			{
				$position = $pj0;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 23;
					$this -> errorPosition = $position;
				}
				$position = $piv;
				$pgn = $position;
				goto irgr;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pj0;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 23;
					$this -> errorPosition = $position;
				}
				$position = $piv;
				$pgn = $position;
				goto irgr;
			}

			//  implode ("", $s) 
			$t1s = new Thunksq_string1r ($s1q, $this -> globalVars);
			$s1n = $t1s;
			$l6v = $s1n;
			//  \Goat\Node::EscapeStringNode ($l) 
			$t6x = new ThunkPrimary6w ($l6v, $this -> globalVars);
			$value = $t6x;
			goto sf1;
		}

		lsj7:
		{
			$pjf = $position;
			$cpjj = $position;
			goto irjk;
		}

		sqjwfjy:
		{
			$position = $pjv;
			$pjr = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "'")
			{
				$position = $pjr;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 33;
					$this -> errorPosition = $position;
				}
				$position = $pjl;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 27;
					$this -> errorPosition = $position;
				}
				goto sqjgfji;
			}

			$position += 1;
			//  "'" 
			$t27 = new Thunksqesc126 ($this -> globalVars);
			$e22 = $t27;
			goto sqjmsjn;
		}

		sqf9ffb:
		{
			$position = $pf8;
			$pf4 = $position;

			if (!$this -> parseRuleaction ($position, $text73, $line, $column))
			{
				$position = $pf4;
				return false;
			}

			//  \Goat\Node::Action ($text) 
			$t75 = new ThunkPrimary74 ($text73, $this -> globalVars);
			$value = $t75;
			goto sf1;
		}

		sqgefgg:
		{
			$position = $pgd;
			$pg9 = $position;

			if (!$this -> parseRuleChar ($position, $c3i, $line, $column))
			{
				$position = $pg9;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 34;
					$this -> errorPosition = $position;
				}
				goto lfg4;
			}

			$resg0 = $c3i;
			$r3c = new ArrThunkg2 ($r3c, $resg0, $this -> globalVars);
			$ng5 += 1;
			goto lsg3;
		}

		lsg3:
		{
			goto irg7;
		}

		lfh0:
		{
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "\"")
			{
				$position = $pgs;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 25;
					$this -> errorPosition = $position;
				}
				$position = $pgn;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 26;
					$this -> errorPosition = $position;
				}
				$position = $pgh;
				$pfh = $position;
				goto irfl;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pgs;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 25;
					$this -> errorPosition = $position;
				}
				$position = $pgn;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 26;
					$this -> errorPosition = $position;
				}
				$position = $pgh;
				$pfh = $position;
				goto irfl;
			}

			//  implode ("", $d) 
			$t1w = new Thunkdq_string1v ($d1u, $this -> globalVars);
			$d1o = $t1w;
			$l6v = $d1o;
			//  \Goat\Node::EscapeStringNode ($l) 
			$t6x = new ThunkPrimary6w ($l6v, $this -> globalVars);
			$value = $t6x;
			goto sqgisgj;
		}

		lsgz:
		{
			$ph7 = $position;
			goto irhb;
		}

		sqisfiu:
		{
			$position = $pir;
			$pin = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "b")
			{
				goto sqiofiq;
			}

			$position += 1;
			//  "\b" 
			$t2i = new Thunkdqesc12h ($this -> globalVars);
			$e2d = $t2i;
			$e29 = $e2d;
			goto sqhdshe;
		}

		sqjmsjn:
		{
			$e1y = substr ($this -> text, $cpjj, $position - $cpjj);
			$resj4 = $e1y;
			$s1q = new ArrThunkj6 ($s1q, $resj4, $this -> globalVars);
			goto lsj7;
		}

		lfg4:
		{
			if (($ng5 < 1))
			{
				// dropped set position = spg6
				$position = $pfw;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 35;
					$this -> errorPosition = $position;
				}
				$position = $pfr;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 36;
					$this -> errorPosition = $position;
				}
				$position = $pfm;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 28;
					$this -> errorPosition = $position;
				}
				$position = $pfh;
				$pf8 = $position;
				goto irfc;
			}

			$c3a = $r3c;
			$c38 = $c3a;
			goto sqfssft;
		}

		sqgisgj:
		{
			return true;
		}

		sqiofiq:
		{
			$position = $pin;
			$pij = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "e")
			{
				goto sqikfim;
			}

			$position += 1;
			//  "\033" 
			$t2k = new Thunkdqesc12j ($this -> globalVars);
			$e2d = $t2k;
			$e29 = $e2d;
			goto sqhdshe;
		}

		sqhdshe:
		{
			$resgw = $e29;
			$d1u = new ArrThunkgy ($d1u, $resgw, $this -> globalVars);
			goto lsgz;
		}

		sqfssft:
		{
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "]")
			{
				$position = $pfm;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 28;
					$this -> errorPosition = $position;
				}
				$position = $pfh;
				$pf8 = $position;
				goto irfc;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pfm;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 28;
					$this -> errorPosition = $position;
				}
				$position = $pfh;
				$pf8 = $position;
				goto irfc;
			}

			$c6y = $c38;
			//  \Goat\Node::CharacterClassNode ($c) 
			$t70 = new ThunkPrimary6z ($c6y, $this -> globalVars);
			$value = $t70;
			goto sf1;
		}

		sqikfim:
		{
			$position = $pij;
			$pif = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "f")
			{
				goto sqigfii;
			}

			$position += 1;
			//  "\f" 
			$t2m = new Thunkdqesc12l ($this -> globalVars);
			$e2d = $t2m;
			$e29 = $e2d;
			goto sqhdshe;
		}

		sqigfii:
		{
			$position = $pif;
			$pib = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "n")
			{
				goto sqicfie;
			}

			$position += 1;
			//  "\n" 
			$t2o = new Thunkdqesc12n ($this -> globalVars);
			$e2d = $t2o;
			$e29 = $e2d;
			goto sqhdshe;
		}

		sqicfie:
		{
			$position = $pib;
			$pi7 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "r")
			{
				goto sqi8fia;
			}

			$position += 1;
			//  "\r" 
			$t2q = new Thunkdqesc12p ($this -> globalVars);
			$e2d = $t2q;
			$e29 = $e2d;
			goto sqhdshe;
		}

		sqi8fia:
		{
			$position = $pi7;
			$pi3 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "t")
			{
				goto sqi4fi6;
			}

			$position += 1;
			//  "\t" 
			$t2s = new Thunkdqesc12r ($this -> globalVars);
			$e2d = $t2s;
			$e29 = $e2d;
			goto sqhdshe;
		}

		sqi4fi6:
		{
			$position = $pi3;
			$phz = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "v")
			{
				goto sqi0fi2;
			}

			$position += 1;
			//  "\v" 
			$t2u = new Thunkdqesc12t ($this -> globalVars);
			$e2d = $t2u;
			$e29 = $e2d;
			goto sqhdshe;
		}

		sqi0fi2:
		{
			$position = $phz;
			$phv = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "\\")
			{
				goto sqhwfhy;
			}

			$position += 1;
			//  "\\" 
			$t2w = new Thunkdqesc12v ($this -> globalVars);
			$e2d = $t2w;
			$e29 = $e2d;
			goto sqhdshe;
		}

		sqhwfhy:
		{
			$position = $phv;
			$phr = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "\"")
			{
				goto sqhsfhu;
			}

			$position += 1;
			//  "\"" 
			$t2y = new Thunkdqesc12x ($this -> globalVars);
			$e2d = $t2y;
			$e29 = $e2d;
			goto sqhdshe;
		}

		sqhsfhu:
		{
			$position = $phr;
			$phn = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "x")
			{
				goto sqhofhq;
			}

			$position += 1;
			// RegExp
			if (!preg_match ("~(?P<x2z>(?:(?:(?:[A-Fa-f0-9])(?:[A-Fa-f0-9]))))~AS", $this -> text, $mq5, 0, $position))
			{
				goto sqhofhq;
			}

			$position += strlen ($mq5[0]);
			$x2z = $mq5["x2z"];
			//  chr(hexdec($x)) 
			$t31 = new Thunkdqesc130 ($x2z, $this -> globalVars);
			$e2d = $t31;
			$e29 = $e2d;
			goto sqhdshe;
		}

		sqhofhq:
		{
			$position = $phn;
			$phi = $position;
			$cphm = $position;

			if (!$this -> parseRuleoctchrs ($position, $line, $column))
			{
				$position = $phi;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 37;
					$this -> errorPosition = $position;
				}
				$position = $phc;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 29;
					$this -> errorPosition = $position;
				}
				goto sqh8fha;
			}

			$o32 = substr ($this -> text, $cphm, $position - $cphm);
			//  chr(octdec($o)) 
			$t34 = new Thunkdqesc133 ($o32, $this -> globalVars);
			$e2d = $t34;
			$e29 = $e2d;
			goto sqhdshe;
		}
	}

	public function parseRulePrefix (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$pm4 = $position;
		goto irmd;

		irmd:
		{
			$pme = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "$")
			{
				$position = $pme;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 38;
					$this -> errorPosition = $position;
				}
				goto sqm5fm7;
			}

			$position += 1;
			goto irmi;
		}

		sqm5fm7:
		{
			$position = $pm4;
			$plv = $position;
			goto irlz;
		}

		irmi:
		{
			$pmj = $position;
			// RegExp
			if (!preg_match ("~(?P<id1i>(?:[a-zA-Z_])(?:(?:(?:[A-Za-z_0-9])*)))~AS", $this -> text, $mq6, 0, $position))
			{
				$position = $pmj;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $pme;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 38;
					$this -> errorPosition = $position;
				}
				goto sqm5fm7;
			}

			$position += strlen ($mq6[0]);
			$id1i = $mq6["id1i"];

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pmj;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 1;
					$this -> errorPosition = $position;
				}
				$position = $pme;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 38;
					$this -> errorPosition = $position;
				}
				goto sqm5fm7;
			}

			$id1g = $id1i;
			$v64 = $id1g;
			goto irm8;
		}

		irlz:
		{
			$pm0 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "&")
			{
				$position = $pm0;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 39;
					$this -> errorPosition = $position;
				}
				goto sqlwfly;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pm0;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 39;
					$this -> errorPosition = $position;
				}
				goto sqlwfly;
			}

			goto sqm1sm2;
		}

		irm8:
		{
			$pm9 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== ":")
			{
				$position = $pm9;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 40;
					$this -> errorPosition = $position;
				}
				goto sqm5fm7;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pm9;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 40;
					$this -> errorPosition = $position;
				}
				goto sqm5fm7;
			}

			goto sqmasmb;
		}

		sqlwfly:
		{
			$position = $plv;
			$plm = $position;
			goto irlq;
		}

		sqm1sm2:
		{
			if (!$this -> parseRuleaction ($position, $t68, $line, $column))
			{
				$position = $plv;
				$plm = $position;
				goto irlq;
			}

			//  \Goat\Node::Predicate ($t) 
			$t6a = new ThunkPrefix69 ($t68, $this -> globalVars);
			$value = $t6a;
			goto sl6;
		}

		sqmasmb:
		{
			if (!$this -> parseRuleSuffix ($position, $s65, $line, $column))
			{
				$position = $pm4;
				$plv = $position;
				goto irlz;
			}

			//  \Goat\Node::variable ($s, $v) 
			$t67 = new ThunkPrefix66 ($s65, $v64, $this -> globalVars);
			$value = $t67;
			return true;
		}

		irlq:
		{
			$plr = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "&")
			{
				$position = $plr;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 39;
					$this -> errorPosition = $position;
				}
				goto sqlnflp;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $plr;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 39;
					$this -> errorPosition = $position;
				}
				goto sqlnflp;
			}

			goto sqlsslt;
		}

		sl6:
		{
			return true;
		}

		sqlnflp:
		{
			$position = $plm;
			$pld = $position;
			goto irlh;
		}

		sqlsslt:
		{
			if (!$this -> parseRuleSuffix ($position, $s6b, $line, $column))
			{
				$position = $plm;
				$pld = $position;
				goto irlh;
			}

			//  \Goat\Node::Assert ($s) 
			$t6d = new ThunkPrefix6c ($s6b, $this -> globalVars);
			$value = $t6d;
			goto sl6;
		}

		irlh:
		{
			$pli = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "!")
			{
				$position = $pli;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 41;
					$this -> errorPosition = $position;
				}
				goto sqleflg;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pli;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 41;
					$this -> errorPosition = $position;
				}
				goto sqleflg;
			}

			goto sqljslk;
		}

		sqleflg:
		{
			$position = $pld;
			$pl9 = $position;

			if (!$this -> parseRuleSuffix ($position, $s6h, $line, $column))
			{
				$position = $pl9;
				return false;
			}

			$value = $s6h;
			goto sl6;
		}

		sqljslk:
		{
			if (!$this -> parseRuleSuffix ($position, $s6e, $line, $column))
			{
				goto sqleflg;
			}

			//  \Goat\Node::AssertNot ($s) 
			$t6g = new ThunkPrefix6f ($s6e, $this -> globalVars);
			$value = $t6g;
			goto sl6;
		}
	}

	public function parseRuleSuffix (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$pmp = $position;

		if (!$this -> parseRulePrimary ($position, $p6j, $line, $column))
		{
			$position = $pmp;
			return false;
		}

		$pnh = $position;
		goto irnl;

		irnl:
		{
			$pnm = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "?")
			{
				$position = $pnm;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 42;
					$this -> errorPosition = $position;
				}
				$position = $pnh;
				$pn8 = $position;
				goto irnc;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pnm;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 42;
					$this -> errorPosition = $position;
				}
				$position = $pnh;
				$pn8 = $position;
				goto irnc;
			}

			//  function ($x) { return \Goat\Node::Question ($x); } 
			$t18 = new ThunkQuantifier17 ($this -> globalVars);
			$q6k = $t18;
			//  $q ($p) 
			$t6m = new ThunkSuffix6l ($q6k, $p6j, $this -> globalVars);
			$value = $t6m;
			return true;
		}

		irnc:
		{
			$pnd = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "*")
			{
				$position = $pnd;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 43;
					$this -> errorPosition = $position;
				}
				$position = $pn8;
				$pmz = $position;
				goto irn3;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pnd;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 43;
					$this -> errorPosition = $position;
				}
				$position = $pn8;
				$pmz = $position;
				goto irn3;
			}

			//  function ($x) { return \Goat\Node::Star ($x); } 
			$t1a = new ThunkQuantifier19 ($this -> globalVars);
			$q6k = $t1a;
			//  $q ($p) 
			$t6m = new ThunkSuffix6l ($q6k, $p6j, $this -> globalVars);
			$value = $t6m;
			goto sqmqsmr;
		}

		irn3:
		{
			$pn4 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "+")
			{
				$position = $pn4;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 44;
					$this -> errorPosition = $position;
				}
				$position = $pmz;
				$pmv = $position;
				//  function ($x) { return $x; } 
				$t1e = new ThunkQuantifier1d ($this -> globalVars);
				$q6k = $t1e;
				//  $q ($p) 
				$t6m = new ThunkSuffix6l ($q6k, $p6j, $this -> globalVars);
				$value = $t6m;
				goto sqmqsmr;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $pn4;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 44;
					$this -> errorPosition = $position;
				}
				$position = $pmz;
				$pmv = $position;
				//  function ($x) { return $x; } 
				$t1e = new ThunkQuantifier1d ($this -> globalVars);
				$q6k = $t1e;
				//  $q ($p) 
				$t6m = new ThunkSuffix6l ($q6k, $p6j, $this -> globalVars);
				$value = $t6m;
				goto sqmqsmr;
			}

			//  function ($x) { return \Goat\Node::Plus ($x); } 
			$t1c = new ThunkQuantifier1b ($this -> globalVars);
			$q6k = $t1c;
			//  $q ($p) 
			$t6m = new ThunkSuffix6l ($q6k, $p6j, $this -> globalVars);
			$value = $t6m;
			goto sqmqsmr;
		}

		sqmqsmr:
		{
			return true;
		}
	}

	public function parseRuleAlt1 (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$pnx = $position;
		goto iro1;

		iro1:
		{
			$po2 = $position;
			// Literal
			if ($position >= $this -> length || $this -> text[$position] !== "|")
			{
				$position = $po2;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 45;
					$this -> errorPosition = $position;
				}
				goto sqnyfo0;
			}

			$position += 1;

			if (!$this -> parseRulewhitespace ($position, $line, $column))
			{
				$position = $po2;
				if ($position > $this -> errorPosition)
				{
					$this -> errorId = 45;
					$this -> errorPosition = $position;
				}
				goto sqnyfo0;
			}

			goto sqo3so4;
		}

		sqnyfo0:
		{
			$position = $pnx;
			$pnt = $position;
			//  \Goat\Node::Nil() 
			$t5q = new ThunkAlt15p ($this -> globalVars);
			$value = $t5q;
			goto snq;
		}

		sqo3so4:
		{
			if (!$this -> parseRuleSequence ($position, $f5l, $line, $column))
			{
				$position = $pnx;
				$pnt = $position;
				//  \Goat\Node::Nil() 
				$t5q = new ThunkAlt15p ($this -> globalVars);
				$value = $t5q;
				goto snq;
			}

			if (!$this -> parseRuleAlt1 ($position, $r5m, $line, $column))
			{
				$position = $pnx;
				$pnt = $position;
				//  \Goat\Node::Nil() 
				$t5q = new ThunkAlt15p ($this -> globalVars);
				$value = $t5q;
				goto snq;
			}

			//  \Goat\Node::Choice ($f, $r) 
			$t5o = new ThunkAlt15n ($f5l, $r5m, $this -> globalVars);
			$value = $t5o;
			return true;
		}

		snq:
		{
			return true;
		}
	}

	public function parseRuleSequence (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$po8 = $position;

		if (!$this -> parseRulePrefix ($position, $f5s, $line, $column))
		{
			$position = $po8;
			return false;
		}

		if (!$this -> parseRuleSequence1 ($position, $r5t, $line, $column))
		{
			$position = $po8;
			return false;
		}

		//  \Goat\Node::Then ($f, $r) 
		$t5v = new ThunkSequence5u ($f5s, $r5t, $this -> globalVars);
		$value = $t5v;
		return true;
	}

	public function parseRuleexpression (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$poe = $position;

		if (!$this -> parseRuleSequence ($position, $f77, $line, $column))
		{
			$position = $poe;
			return false;
		}

		if (!$this -> parseRuleAlt1 ($position, $r78, $line, $column))
		{
			$position = $poe;
			return false;
		}

		//  \Goat\Node::Choice ($f, $r) 
		$t7a = new Thunkexpression79 ($f77, $r78, $this -> globalVars);
		$value = $t7a;
		return true;
	}

	public function parseRuleSequence1 (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$pop = $position;

		if (!$this -> parseRulePrefix ($position, $f5x, $line, $column))
		{
			$position = $pop;
			$pol = $position;
			//  \Goat\Node::Nil() 
			$t62 = new ThunkSequence161 ($this -> globalVars);
			$value = $t62;
			goto soi;
		}

		if (!$this -> parseRuleSequence1 ($position, $r5y, $line, $column))
		{
			$position = $pop;
			$pol = $position;
			//  \Goat\Node::Nil() 
			$t62 = new ThunkSequence161 ($this -> globalVars);
			$value = $t62;
			goto soi;
		}

		//  \Goat\Node::Then ($f, $r) 
		$t60 = new ThunkSequence15z ($f5x, $r5y, $this -> globalVars);
		$value = $t60;
		return true;

		soi:
		{
			return true;
		}
	}

	public function parseRulenested_curly_braces1 (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		$pow = $position;
		// Literal
		if ($position >= $this -> length || $this -> text[$position] !== "{")
		{
			goto sqoxfoz;
		}

		$position += 1;

		if (!$this -> parseRulenested_curly_braces ($position, $line, $column))
		{
			goto sqoxfoz;
		}

		// Literal
		if ($position >= $this -> length || $this -> text[$position] !== "}")
		{
			goto sqoxfoz;
		}

		$position += 1;
		return true;

		sqoxfoz:
		{
			$position = $pow;
			// Literal
			if (!preg_match ("~[^{}]~AS", $this -> text, $mq7, 0, $position))
			{
				return false;
			}

			$position += 1;
			goto sot;
		}

		sot:
		{
			return true;
		}
	}

	public function parseRulenested_curly_braces (&$position, &$value=NULL, &$line=NULL, &$column=NULL)
	{
		goto lsp2;

		lsp2:
		{
			if (!$this -> parseRulenested_curly_braces1 ($position, $line, $column))
			{
				return true;
			}

			goto lsp2;
		}
	}

	public function raiseError()
	{
		switch ($this -> errorId)
		{
			case 1:
				$errorMessage = "Expected identifier at line @LINE, offset @COLUMN";
			break;

			case 2:
				$errorMessage = "Expected equals at line @LINE, offset @COLUMN";
			break;

			case 3:
				$errorMessage = "Expected percent_no_newline at line @LINE, offset @COLUMN";
			break;

			case 4:
				$errorMessage = "Expected directive at line @LINE, offset @COLUMN";
			break;

			case 5:
				$errorMessage = "Expected Definition at line @LINE, offset @COLUMN";
			break;

			case 6:
				$errorMessage = "Expected Definitions at line @LINE, offset @COLUMN";
			break;

			case 7:
				$errorMessage = "Expected EndOfFile at line @LINE, offset @COLUMN";
			break;

			case 8:
				$errorMessage = "Expected directive1 at line @LINE, offset @COLUMN";
			break;

			case 9:
				$errorMessage = "Expected kwglobal at line @LINE, offset @COLUMN";
			break;

			case 10:
				$errorMessage = "Expected ident_no_newline at line @LINE, offset @COLUMN";
			break;

			case 11:
				$errorMessage = "Expected kwinline at line @LINE, offset @COLUMN";
			break;

			case 12:
				$errorMessage = "Expected kwnamespace at line @LINE, offset @COLUMN";
			break;

			case 13:
				$errorMessage = "Expected kwclass at line @LINE, offset @COLUMN";
			break;

			case 14:
				$errorMessage = "Expected kwtop at line @LINE, offset @COLUMN";
			break;

			case 15:
				$errorMessage = "Expected ccesc at line @LINE, offset @COLUMN";
			break;

			case 16:
				$errorMessage = "Expected ccesc3 at line @LINE, offset @COLUMN";
			break;

			case 17:
				$errorMessage = "Expected ccesc2 at line @LINE, offset @COLUMN";
			break;

			case 18:
				$errorMessage = "Expected ccesc1 at line @LINE, offset @COLUMN";
			break;

			case 19:
				$errorMessage = "Expected curly_braces at line @LINE, offset @COLUMN";
			break;

			case 20:
				$errorMessage = "Expected open_paren at line @LINE, offset @COLUMN";
			break;

			case 21:
				$errorMessage = "Expected oc at line @LINE, offset @COLUMN";
			break;

			case 22:
				$errorMessage = "Expected close_paren at line @LINE, offset @COLUMN";
			break;

			case 23:
				$errorMessage = "Expected sq_string at line @LINE, offset @COLUMN";
			break;

			case 24:
				$errorMessage = "Expected cc at line @LINE, offset @COLUMN";
			break;

			case 25:
				$errorMessage = "Expected dq_string at line @LINE, offset @COLUMN";
			break;

			case 26:
				$errorMessage = "Expected string at line @LINE, offset @COLUMN";
			break;

			case 27:
				$errorMessage = "Expected sqesc at line @LINE, offset @COLUMN";
			break;

			case 28:
				$errorMessage = "Expected Class at line @LINE, offset @COLUMN";
			break;

			case 29:
				$errorMessage = "Expected dqesc at line @LINE, offset @COLUMN";
			break;

			case 30:
				$errorMessage = "Expected sq_string_char at line @LINE, offset @COLUMN";
			break;

			case 31:
				$errorMessage = "Expected dot at line @LINE, offset @COLUMN";
			break;

			case 32:
				$errorMessage = "Expected dq_string_char at line @LINE, offset @COLUMN";
			break;

			case 33:
				$errorMessage = "Expected sqesc1 at line @LINE, offset @COLUMN";
			break;

			case 34:
				$errorMessage = "Expected Range at line @LINE, offset @COLUMN";
			break;

			case 35:
				$errorMessage = "Expected Class2 at line @LINE, offset @COLUMN";
			break;

			case 36:
				$errorMessage = "Expected Class1 at line @LINE, offset @COLUMN";
			break;

			case 37:
				$errorMessage = "Expected dqesc1 at line @LINE, offset @COLUMN";
			break;

			case 38:
				$errorMessage = "Expected variable at line @LINE, offset @COLUMN";
			break;

			case 39:
				$errorMessage = "Expected and at line @LINE, offset @COLUMN";
			break;

			case 40:
				$errorMessage = "Expected colon at line @LINE, offset @COLUMN";
			break;

			case 41:
				$errorMessage = "Expected not at line @LINE, offset @COLUMN";
			break;

			case 42:
				$errorMessage = "Expected question_mark at line @LINE, offset @COLUMN";
			break;

			case 43:
				$errorMessage = "Expected star at line @LINE, offset @COLUMN";
			break;

			case 44:
				$errorMessage = "Expected plus at line @LINE, offset @COLUMN";
			break;

			case 45:
				$errorMessage = "Expected bar at line @LINE, offset @COLUMN";
			break;

			default:
				throw new \RuntimeException ("Parser failed with unknown error code.");
			break;

		}

		$lastPosition = 0;
		$line = 1;
		$newlines = preg_split ("~(\r\n|\n|\r)~m", $this -> text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);

		foreach ($newlines as $ix => $match)
		{
			if ($match[1] > $this -> errorPosition)
			{
				$errorMessage = preg_replace (array ('~@LINE~', '~@COLUMN~'), array ($line, ($this -> errorPosition - $lastPosition)), $errorMessage);
				throw new \RuntimeException ($errorMessage);
			}
			elseif ($ix % 2 == 1)
			{
				$line += 1;
			}

			$lastPosition = $match[1];
		}
	}

}

class Undefinedq8
{
}

abstract class Thunkq9
{
	abstract public function force();

	public static function getUndefined()
	{
		static $undefined;
		if ($undefined === NULL)
		{
			$undefined = new Undefinedq8;
		}
		return $undefined;
	}

}

class ArrEmptyThunk7r extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  array() ;
		return $this -> _forceValue;
	}
}
class ThunkDefinition13 extends Thunkq9
{
	private $_forceValue;
	private $n11;
	private $e12;

	public function __construct ($n11, $e12)
	{
		$this -> n11 = $n11;
		$this -> e12 = $e12;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$n = (($this -> n11 instanceof Thunkq9) ? $this -> n11 -> force() : $this -> n11);
		$e = (($this -> e12 instanceof Thunkq9) ? $this -> e12 -> force() : $this -> e12);
		$this -> _forceValue =  \Goat\Node::Rule ($n, $e) ;
		return $this -> _forceValue;
	}
}
class ArrThunk7s extends Thunkq9
{
	private $_forceValue;
	private $dz;
	private $res7q;

	public function __construct ($dz, $res7q)
	{
		$this -> dz = $dz;
		$this -> res7q = $res7q;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$d = (($this -> dz instanceof Thunkq9) ? $this -> dz -> force() : $this -> dz);
		$res7q = (($this -> res7q instanceof Thunkq9) ? $this -> res7q -> force() : $this -> res7q);
		$this -> _forceValue =  array_merge ($d, array ($res7q)) ;
		return $this -> _forceValue;
	}
}
class Thunkglobaldirectived extends Thunkq9
{
	private $_forceValue;
	private $nameb;
	private $typec;

	public function __construct ($nameb, $typec)
	{
		$this -> nameb = $nameb;
		$this -> typec = $typec;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$name = (($this -> nameb instanceof Thunkq9) ? $this -> nameb -> force() : $this -> nameb);
		$type = (($this -> typec instanceof Thunkq9) ? $this -> typec -> force() : $this -> typec);
		$this -> _forceValue =  \Goat\Node::Option ("global", $name, $type) ;
		return $this -> _forceValue;
	}
}
class Thunkglobaldirectiveg extends Thunkq9
{
	private $_forceValue;
	private $namef;

	public function __construct ($namef)
	{
		$this -> namef = $namef;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$name = (($this -> namef instanceof Thunkq9) ? $this -> namef -> force() : $this -> namef);
		$this -> _forceValue =  \Goat\Node::Option ("global", $name) ;
		return $this -> _forceValue;
	}
}
class Thunkinlinedirectivek extends Thunkq9
{
	private $_forceValue;
	private $namej;

	public function __construct ($namej)
	{
		$this -> namej = $namej;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$name = (($this -> namej instanceof Thunkq9) ? $this -> namej -> force() : $this -> namej);
		$this -> _forceValue =  \Goat\Node::Option ("inline", $name) ;
		return $this -> _forceValue;
	}
}
class Thunknsdirectiveo extends Thunkq9
{
	private $_forceValue;
	private $namen;

	public function __construct ($namen)
	{
		$this -> namen = $namen;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$name = (($this -> namen instanceof Thunkq9) ? $this -> namen -> force() : $this -> namen);
		$this -> _forceValue =  \Goat\Node::Option ("namespace", $name) ;
		return $this -> _forceValue;
	}
}
class Thunkclassdirectives extends Thunkq9
{
	private $_forceValue;
	private $namer;

	public function __construct ($namer)
	{
		$this -> namer = $namer;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$name = (($this -> namer instanceof Thunkq9) ? $this -> namer -> force() : $this -> namer);
		$this -> _forceValue =  \Goat\Node::Option ("class", $name) ;
		return $this -> _forceValue;
	}
}
class Thunktopdirectivew extends Thunkq9
{
	private $_forceValue;
	private $namev;

	public function __construct ($namev)
	{
		$this -> namev = $namev;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$name = (($this -> namev instanceof Thunkq9) ? $this -> namev -> force() : $this -> namev);
		$this -> _forceValue =  \Goat\Node::Option ("top", $name) ;
		return $this -> _forceValue;
	}
}
class Thunkccesc34g extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\\" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc34i extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "[" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc34k extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "]" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc34m extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "-" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc13r extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\a" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc13t extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\b" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc13v extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\033" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc13x extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\f" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc13z extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\n" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc141 extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\r" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc143 extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\t" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc145 extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\v" ;
		return $this -> _forceValue;
	}
}
class Thunkccesc148 extends Thunkq9
{
	private $_forceValue;
	private $x47;

	public function __construct ($x47)
	{
		$this -> x47 = $x47;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$x = (($this -> x47 instanceof Thunkq9) ? $this -> x47 -> force() : $this -> x47);
		$this -> _forceValue =  chr(hexdec($x)) ;
		return $this -> _forceValue;
	}
}
class Thunkccesc14b extends Thunkq9
{
	private $_forceValue;
	private $o4a;

	public function __construct ($o4a)
	{
		$this -> o4a = $o4a;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$o = (($this -> o4a instanceof Thunkq9) ? $this -> o4a -> force() : $this -> o4a);
		$this -> _forceValue =  chr(octdec($o)) ;
		return $this -> _forceValue;
	}
}
class ThunkPrimary6p extends Thunkq9
{
	private $_forceValue;
	private $rule6o;

	public function __construct ($rule6o)
	{
		$this -> rule6o = $rule6o;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$rule = (($this -> rule6o instanceof Thunkq9) ? $this -> rule6o -> force() : $this -> rule6o);
		$this -> _forceValue =  \Goat\Node::Name ($rule) ;
		return $this -> _forceValue;
	}
}
class ArrEmptyThunkj5 extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  array() ;
		return $this -> _forceValue;
	}
}
class ThunkPrimary6t extends Thunkq9
{
	private $_forceValue;
	private $e6s;

	public function __construct ($e6s)
	{
		$this -> e6s = $e6s;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$e = (($this -> e6s instanceof Thunkq9) ? $this -> e6s -> force() : $this -> e6s);
		$this -> _forceValue =  \Goat\Node::Capture ($e) ;
		return $this -> _forceValue;
	}
}
class ArrEmptyThunkgx extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  array() ;
		return $this -> _forceValue;
	}
}
class ArrEmptyThunkg1 extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  array() ;
		return $this -> _forceValue;
	}
}
class ArrThunkj6 extends Thunkq9
{
	private $_forceValue;
	private $s1q;
	private $resj4;

	public function __construct ($s1q, $resj4)
	{
		$this -> s1q = $s1q;
		$this -> resj4 = $resj4;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$s = (($this -> s1q instanceof Thunkq9) ? $this -> s1q -> force() : $this -> s1q);
		$resj4 = (($this -> resj4 instanceof Thunkq9) ? $this -> resj4 -> force() : $this -> resj4);
		$this -> _forceValue =  array_merge ($s, array ($resj4)) ;
		return $this -> _forceValue;
	}
}
class Thunksqesc124 extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\\" ;
		return $this -> _forceValue;
	}
}
class ThunkPrimary71 extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  \Goat\Node::Dot() ;
		return $this -> _forceValue;
	}
}
class ThunkRange3g extends Thunkq9
{
	private $_forceValue;
	private $a3e;
	private $b3f;

	public function __construct ($a3e, $b3f)
	{
		$this -> a3e = $a3e;
		$this -> b3f = $b3f;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$a = (($this -> a3e instanceof Thunkq9) ? $this -> a3e -> force() : $this -> a3e);
		$b = (($this -> b3f instanceof Thunkq9) ? $this -> b3f -> force() : $this -> b3f);
		$this -> _forceValue =  array ($a, $b) ;
		return $this -> _forceValue;
	}
}
class ArrThunkg2 extends Thunkq9
{
	private $_forceValue;
	private $r3c;
	private $resg0;

	public function __construct ($r3c, $resg0)
	{
		$this -> r3c = $r3c;
		$this -> resg0 = $resg0;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$r = (($this -> r3c instanceof Thunkq9) ? $this -> r3c -> force() : $this -> r3c);
		$resg0 = (($this -> resg0 instanceof Thunkq9) ? $this -> resg0 -> force() : $this -> resg0);
		$this -> _forceValue =  array_merge ($r, array ($resg0)) ;
		return $this -> _forceValue;
	}
}
class ArrThunkgy extends Thunkq9
{
	private $_forceValue;
	private $d1u;
	private $resgw;

	public function __construct ($d1u, $resgw)
	{
		$this -> d1u = $d1u;
		$this -> resgw = $resgw;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$d = (($this -> d1u instanceof Thunkq9) ? $this -> d1u -> force() : $this -> d1u);
		$resgw = (($this -> resgw instanceof Thunkq9) ? $this -> resgw -> force() : $this -> resgw);
		$this -> _forceValue =  array_merge ($d, array ($resgw)) ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc12f extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\a" ;
		return $this -> _forceValue;
	}
}
class Thunksq_string1r extends Thunkq9
{
	private $_forceValue;
	private $s1q;

	public function __construct ($s1q)
	{
		$this -> s1q = $s1q;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$s = (($this -> s1q instanceof Thunkq9) ? $this -> s1q -> force() : $this -> s1q);
		$this -> _forceValue =  implode ("", $s) ;
		return $this -> _forceValue;
	}
}
class ThunkPrimary6w extends Thunkq9
{
	private $_forceValue;
	private $l6v;

	public function __construct ($l6v)
	{
		$this -> l6v = $l6v;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$l = (($this -> l6v instanceof Thunkq9) ? $this -> l6v -> force() : $this -> l6v);
		$this -> _forceValue =  \Goat\Node::EscapeStringNode ($l) ;
		return $this -> _forceValue;
	}
}
class Thunksqesc126 extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "'" ;
		return $this -> _forceValue;
	}
}
class ThunkPrimary74 extends Thunkq9
{
	private $_forceValue;
	private $text73;

	public function __construct ($text73)
	{
		$this -> text73 = $text73;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$text = (($this -> text73 instanceof Thunkq9) ? $this -> text73 -> force() : $this -> text73);
		$this -> _forceValue =  \Goat\Node::Action ($text) ;
		return $this -> _forceValue;
	}
}
class Thunkdq_string1v extends Thunkq9
{
	private $_forceValue;
	private $d1u;

	public function __construct ($d1u)
	{
		$this -> d1u = $d1u;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$d = (($this -> d1u instanceof Thunkq9) ? $this -> d1u -> force() : $this -> d1u);
		$this -> _forceValue =  implode ("", $d) ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc12h extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\b" ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc12j extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\033" ;
		return $this -> _forceValue;
	}
}
class ThunkPrimary6z extends Thunkq9
{
	private $_forceValue;
	private $c6y;

	public function __construct ($c6y)
	{
		$this -> c6y = $c6y;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$c = (($this -> c6y instanceof Thunkq9) ? $this -> c6y -> force() : $this -> c6y);
		$this -> _forceValue =  \Goat\Node::CharacterClassNode ($c) ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc12l extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\f" ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc12n extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\n" ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc12p extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\r" ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc12r extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\t" ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc12t extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\v" ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc12v extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\\" ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc12x extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  "\"" ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc130 extends Thunkq9
{
	private $_forceValue;
	private $x2z;

	public function __construct ($x2z)
	{
		$this -> x2z = $x2z;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$x = (($this -> x2z instanceof Thunkq9) ? $this -> x2z -> force() : $this -> x2z);
		$this -> _forceValue =  chr(hexdec($x)) ;
		return $this -> _forceValue;
	}
}
class Thunkdqesc133 extends Thunkq9
{
	private $_forceValue;
	private $o32;

	public function __construct ($o32)
	{
		$this -> o32 = $o32;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$o = (($this -> o32 instanceof Thunkq9) ? $this -> o32 -> force() : $this -> o32);
		$this -> _forceValue =  chr(octdec($o)) ;
		return $this -> _forceValue;
	}
}
class ThunkPrefix69 extends Thunkq9
{
	private $_forceValue;
	private $t68;

	public function __construct ($t68)
	{
		$this -> t68 = $t68;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$t = (($this -> t68 instanceof Thunkq9) ? $this -> t68 -> force() : $this -> t68);
		$this -> _forceValue =  \Goat\Node::Predicate ($t) ;
		return $this -> _forceValue;
	}
}
class ThunkPrefix66 extends Thunkq9
{
	private $_forceValue;
	private $s65;
	private $v64;

	public function __construct ($s65, $v64)
	{
		$this -> s65 = $s65;
		$this -> v64 = $v64;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$s = (($this -> s65 instanceof Thunkq9) ? $this -> s65 -> force() : $this -> s65);
		$v = (($this -> v64 instanceof Thunkq9) ? $this -> v64 -> force() : $this -> v64);
		$this -> _forceValue =  \Goat\Node::variable ($s, $v) ;
		return $this -> _forceValue;
	}
}
class ThunkPrefix6c extends Thunkq9
{
	private $_forceValue;
	private $s6b;

	public function __construct ($s6b)
	{
		$this -> s6b = $s6b;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$s = (($this -> s6b instanceof Thunkq9) ? $this -> s6b -> force() : $this -> s6b);
		$this -> _forceValue =  \Goat\Node::Assert ($s) ;
		return $this -> _forceValue;
	}
}
class ThunkPrefix6f extends Thunkq9
{
	private $_forceValue;
	private $s6e;

	public function __construct ($s6e)
	{
		$this -> s6e = $s6e;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$s = (($this -> s6e instanceof Thunkq9) ? $this -> s6e -> force() : $this -> s6e);
		$this -> _forceValue =  \Goat\Node::AssertNot ($s) ;
		return $this -> _forceValue;
	}
}
class ThunkQuantifier17 extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  function ($x) { return \Goat\Node::Question ($x); } ;
		return $this -> _forceValue;
	}
}
class ThunkSuffix6l extends Thunkq9
{
	private $_forceValue;
	private $q6k;
	private $p6j;

	public function __construct ($q6k, $p6j)
	{
		$this -> q6k = $q6k;
		$this -> p6j = $p6j;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$q = (($this -> q6k instanceof Thunkq9) ? $this -> q6k -> force() : $this -> q6k);
		$p = (($this -> p6j instanceof Thunkq9) ? $this -> p6j -> force() : $this -> p6j);
		$this -> _forceValue =  $q ($p) ;
		return $this -> _forceValue;
	}
}
class ThunkQuantifier19 extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  function ($x) { return \Goat\Node::Star ($x); } ;
		return $this -> _forceValue;
	}
}
class ThunkQuantifier1d extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  function ($x) { return $x; } ;
		return $this -> _forceValue;
	}
}
class ThunkQuantifier1b extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  function ($x) { return \Goat\Node::Plus ($x); } ;
		return $this -> _forceValue;
	}
}
class ThunkAlt15p extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  \Goat\Node::Nil() ;
		return $this -> _forceValue;
	}
}
class ThunkAlt15n extends Thunkq9
{
	private $_forceValue;
	private $f5l;
	private $r5m;

	public function __construct ($f5l, $r5m)
	{
		$this -> f5l = $f5l;
		$this -> r5m = $r5m;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$f = (($this -> f5l instanceof Thunkq9) ? $this -> f5l -> force() : $this -> f5l);
		$r = (($this -> r5m instanceof Thunkq9) ? $this -> r5m -> force() : $this -> r5m);
		$this -> _forceValue =  \Goat\Node::Choice ($f, $r) ;
		return $this -> _forceValue;
	}
}
class ThunkSequence5u extends Thunkq9
{
	private $_forceValue;
	private $f5s;
	private $r5t;

	public function __construct ($f5s, $r5t)
	{
		$this -> f5s = $f5s;
		$this -> r5t = $r5t;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$f = (($this -> f5s instanceof Thunkq9) ? $this -> f5s -> force() : $this -> f5s);
		$r = (($this -> r5t instanceof Thunkq9) ? $this -> r5t -> force() : $this -> r5t);
		$this -> _forceValue =  \Goat\Node::Then ($f, $r) ;
		return $this -> _forceValue;
	}
}
class Thunkexpression79 extends Thunkq9
{
	private $_forceValue;
	private $f77;
	private $r78;

	public function __construct ($f77, $r78)
	{
		$this -> f77 = $f77;
		$this -> r78 = $r78;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$f = (($this -> f77 instanceof Thunkq9) ? $this -> f77 -> force() : $this -> f77);
		$r = (($this -> r78 instanceof Thunkq9) ? $this -> r78 -> force() : $this -> r78);
		$this -> _forceValue =  \Goat\Node::Choice ($f, $r) ;
		return $this -> _forceValue;
	}
}
class ThunkSequence161 extends Thunkq9
{
	private $_forceValue;

	public function __construct ()
	{
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$this -> _forceValue =  \Goat\Node::Nil() ;
		return $this -> _forceValue;
	}
}
class ThunkSequence15z extends Thunkq9
{
	private $_forceValue;
	private $f5x;
	private $r5y;

	public function __construct ($f5x, $r5y)
	{
		$this -> f5x = $f5x;
		$this -> r5y = $r5y;
		$this -> _forceValue = self::getUndefined();
	}

	public function force ()
	{
		if (!($this -> _forceValue instanceof Undefinedq8))
		{
			return $this -> _forceValue;
		}

		$f = (($this -> f5x instanceof Thunkq9) ? $this -> f5x -> force() : $this -> f5x);
		$r = (($this -> r5y instanceof Thunkq9) ? $this -> r5y -> force() : $this -> r5y);
		$this -> _forceValue =  \Goat\Node::Then ($f, $r) ;
		return $this -> _forceValue;
	}
}
