<?php

namespace Goat;

class OpFormatter
{
	public function __construct (State $state, $stringHelper)
	{
		$this -> state = $state;
		$this -> config = $state -> getConfig();
		$this -> errorIds = array();
		$this -> file = $this -> config -> getOutputFile();
		$this -> indentStr = "";
		$this -> indentChar = "\t";
		$this -> indentLength = 1;
		$this -> lastLineEmpty = false;
		$this -> opCleaner = new OpCleaner;
		$this -> stringHelper = $stringHelper;
		$this -> thunks = array();
	}

	public function addIndent ($indentCount = 1)
	{
		$this -> indentStr .= str_repeat ($this -> indentChar, $this -> indentLength * $indentCount);
		$this -> lastLineEmpty = true; // not really, but...
	}

	public function clearLabels()
	{
		$this -> labels = array();
	}

	public function formatLabels()
	{
		$alreadyFormatted = array();

		while (sizeof ($this -> labels) > 0)
		{
			$labels = $this -> labels;
			$this -> labels = array();

			foreach ($labels as $labelName => $label)
			{
				if (array_key_exists ($labelName, $alreadyFormatted))
				{
					continue;
				}

				$this -> putLine ("");
				$this -> putLine ("$labelName:");
				$this -> putLine ("{");
				$this -> addIndent();
				$this -> formatOp ($label -> thenExpr);
				$this -> subIndent();
				$this -> putLine ("}");
				$alreadyFormatted[$labelName] = true;
			}
		}
	}

	public function putLine ($text)
	{
		if ($text == '')
		{
			if (!$this -> lastLineEmpty)
			{
				//fwrite ($this -> file, "\n");
				echo "\n";
				$this -> lastLineEmpty = true;
			}
		}
		else
		{
			echo $this -> indentStr, $text, "\n";
			//fwrite ($this -> file, $this -> indentStr . $text . "\n");
			$this -> lastLineEmpty = false;
		}
	}

	public function subIndent ($indentCount = 1)
	{
		$this -> indentStr = substr ($this -> indentStr, 0, strlen ($this -> indentStr) - ($this -> indentLength * $indentCount));
	}

	public function formatParamVars(array $vars)
	{
		$varStrs = array();

		foreach ($vars as $var)
		{
			if ($var -> isReferenceVar())
			{
				$varStrs[] = "&$".$var -> getName();
			}
			else
			{
				$varStrs[] = "$".$var -> getName();
			}
		}

		return implode (", ", $varStrs);
	}

	public function formatParser (array $methods)
	{
		ob_start();

		$this -> formatStackFrames = array();
		$this -> formatStack = array();

		if ($this -> config -> getParserClassName() != NULL)
		{
			$className = $this -> config -> getParserClassName();
		}
		else
		{
			$className = "Parser";
		}

		$namespace = $this -> config -> getParserNamespace();

		$this -> putLine ("<?php");
		$this -> putLine ("");

		if ($namespace != NULL)
		{
			$this -> putLine ("namespace $namespace;");
			$this -> putLine ("");
		}

		$this -> putLine ("class $className");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("protected \$text;");
		$this -> putLine ("protected \$length;");
		$this -> putLine ("protected \$errorId;");
		$this -> putLine ("protected \$errorPosition;");
		$this -> putLine ("");

		$this -> putLine ("protected function runParser (\$startFunc, \$text, \$globalVars=NULL)");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("global \$argv;");
		$this -> putLine ("\$this -> text = \$text;");
		$this -> putLine ("\$this -> length = strlen (\$text);");
		$this -> putLine ("\$this -> errorId = -1;");
		$this -> putLine ("\$this -> errorPosition = -1;");
		$this -> putLine ("\$this -> globalVars = \$globalVars;");

		if ($this -> config -> generateParserDebugMessages())
		{
			$this -> putLine ("\$this -> indent = 0;");
			$this -> putLine ("\$this -> debugLog = (in_array (\"--parser-debug\", \$argv));");
		}

		$this -> putLine ("\$position = 0;");
		$this -> putLine ("\$value = NULL;");
		$this -> putLine ("if (!\$this -> \$startFunc (\$position, \$value))");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("return \$this -> raiseError();");
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");

		if ($this -> config -> generateParserDebugMessages())
		{
			$this -> putLine ("if (\$this -> debugLog)");
			$this -> putLine ("{");
			$this -> addIndent();
			$this -> putLine ("var_dump (\$value);");
			$this -> putLine ("var_dump (\$value -> force());");
			$this -> subIndent();
			$this -> putLine ("}");
			$this -> putLine ("");
		}

		$this -> putLine ("return \$value -> force();");
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");

		$extraVars = array();
		$globalVarArgs = array();

		foreach ($this -> config -> getGlobalVariables() as $global)
		{
			if ($global -> getTypeHint() !== NULL)
			{
				$extraVars[] = $global -> getTypeHint()." \$".$global -> getSourceName();
			}
			else
			{
				$extraVars[] = "\$".$global -> getSourceName();
			}

			$globalVarArgs[] = "\$".$global -> getSourceName();
		}

		if (sizeof ($extraVars) > 0)
		{
			$extraVarString = ", ".implode (", ", $extraVars);
			$globalVarString = "array (".implode (", ", $globalVarArgs).")";
		}
		else
		{
			$extraVarString = "";
			$globalVarString = "NULL";
		}

		foreach ($this -> config -> getExportedRuleNames() as $ruleName)
		{
			$this -> putLine ("public function parse$ruleName (\$text$extraVarString)");
			$this -> putLine ("{");
			$this -> addIndent();
			$this -> putLine ("return \$this -> runParser (\"parseRule$ruleName\", \$text, $globalVarString);");
			$this -> subIndent();
			$this -> putLine ("}");
			$this -> putLine ("");
		}

		$this -> formatParseMethods ($methods);
		$this -> formatErrorMethod();

		// end class
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");

		$this -> formatThunks ($this -> thunks);

		fwrite ($this -> file, ob_get_clean());
	}

	public function formatParseMethods (array $methods)
	{
		foreach ($methods as $method)
		{
			$this -> formatParseMethod ($method);
		}
	}

	public function formatParseMethod (ParseMethod $method)
	{
		$vars = $this -> formatParamVars (array ($method -> vars -> position, $method -> vars -> result));
		$this -> putLine ("public function parseRule{$method -> name} ($vars=NULL, &\$line=NULL, &\$column=NULL)");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> clearLabels();
		$this -> formatOp ($method -> opSequence);
		$this -> formatLabels();
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");
	}

	public function formatErrorMethod()
	{
		$this -> putLine ("public function raiseError()");
		$this -> putLine ("{");
		$this -> addIndent();

		// begin switch
		$this -> putLine ("switch (\$this -> errorId)");
		$this -> putLine ("{");
		$this -> addIndent();

		foreach ($this -> errorIds as $errorMessage => $errorId)
		{
			$this -> putLine ("case $errorId:");
			$this -> addIndent();
			$this -> putLine ("\$errorMessage = \"".$this -> stringHelper -> escapeDoubleQuotedString ($errorMessage)."\";");
			$this -> subIndent();
			$this -> putLine ("break;");
			$this -> putLine ("");
		}

		$this -> putLine ("default:");
		$this -> addIndent();
		$this -> putLine ("throw new \\RuntimeException (\"Parser failed with unknown error code.\");");
		$this -> subIndent();
		$this -> putLine ("break;");
		$this -> putLine ("");

		// end switch
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");

		$this -> putLine ("\$lastPosition = 0;");
		$this -> putLine ("\$line = 1;");
		$this -> putLine ("\$newlines = preg_split (\"~(\\r\\n|\\n|\\r)~m\", \$this -> text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);");
		$this -> putLine ("");

		$this -> putLine ("foreach (\$newlines as \$ix => \$match)");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("if (\$match[1] > \$this -> errorPosition)");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("\$errorMessage = preg_replace (array ('~@LINE~', '~@COLUMN~'), array (\$line, (\$this -> errorPosition - \$lastPosition)), \$errorMessage);");
		$this -> putLine ("throw new \\RuntimeException (\$errorMessage);");
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("elseif (\$ix % 2 == 1)");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("\$line += 1;");
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");
		$this -> putLine ("\$lastPosition = \$match[1];");

		// end foreach
		$this -> subIndent();
		$this -> putLine ("}");

		// end method
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");
	}

	public function formatThunks (array $thunks)
	{
		$undefinedClass = $this -> state -> getUniqueName ("Undefined");

		$this -> putLine ("class $undefinedClass");
		$this -> putLine ("{");
		$this -> putLine ("}");
		$this -> putLine ("");

		$thunkClass = $this -> state -> getUniqueName ("Thunk");

		$this -> putLine ("abstract class $thunkClass");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("abstract public function force();");
		$this -> putLine ("");
		$this -> putLine ("public static function getUndefined()");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("static \$undefined;");
		$this -> putLine ("if (\$undefined === NULL)");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("\$undefined = new $undefinedClass;");
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("return \$undefined;");
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");

		foreach ($thunks as $thunk)
		{
			$this -> formatThunkClass ($thunk, $thunkClass, $undefinedClass);
		}
	}

	public function formatThunkClass (Thunk $thunk, $thunkClass, $undefinedClass)
	{
		$varArgs = array();
		$properties = array();
		$this -> putLine ("class ".$thunk -> getName()." extends $thunkClass");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("private \$_forceValue;");

		if (!property_exists ($this, 'globalName') || $this -> globalName === NULL)
		{
			$this -> globalName = $this -> state -> getUniqueName ("globalVars");
		}

		foreach ($thunk -> getUsedVars() as $thunkVar)
		{
			if (!($thunkVar instanceof GlobalVar))
			{
				$this -> putLine ("private \$".$thunkVar -> getName().";");
				$varArgs[] = "\$".$thunkVar -> getName();
				$properties[] = $thunkVar -> getName();
			}
		}

		if ($thunk -> hasGlobalVars())
		{
			$this -> putLine ("private \${$this -> globalName};");
			$varArgs[] = "\$".$this -> globalName;
			$properties[] = $this -> globalName;
		}

		$this -> putLine ("");
		$this -> putLine ("public function __construct (".implode (", ", $varArgs).")");
		$this -> putLine ("{");
		$this -> addIndent();

		foreach ($properties as $property)
		{
			$this -> putLine ("\$this -> $property = \$$property;");
		}

		$this -> putLine ("\$this -> _forceValue = self::getUndefined();");
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");
		$this -> putLine ("public function force ()");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("if (!(\$this -> _forceValue instanceof $undefinedClass))");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("return \$this -> _forceValue;");
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");

		$thunkNames = array();

		foreach ($thunk -> getUsedVars() as $thunkVar)
		{
			$thunkNames[] = $thunkVar -> getSourceName();

			if ($thunkVar instanceof ParseVar)
			{
				$this -> putLine ("\$".$thunkVar -> getSourceName()." = ((\$this -> ".$thunkVar -> getName()." instanceof $thunkClass) ? \$this -> ".$thunkVar -> getName()." -> force() : \$this -> ".$thunkVar -> getName().");");
			}
			else
			{
				$this -> putLine ("\$".$thunkVar -> getSourceName()." = \$this -> {$this -> globalName}[".$thunkVar -> getIndex()."];");
			}
		}

		/*
		foreach ($this -> config -> getGlobalVariables() as $globalVar)
		{
			if (!in_array ($globalVar -> getSourceName(), $thunkNames) && preg_match ('~'.preg_quote ($globalVar -> getSourceName()).'~i', $thunk -> getText()))
			{
				$this -> putLine ("\$".$globalVar -> getSourceName()." = \$this -> {$this -> globalName}[".$globalVar -> getIndex()."];");
			}
		}
		*/

		$this -> putLine ("\$this -> _forceValue = ".$thunk -> getText().";");
		$this -> putLine ("return \$this -> _forceValue;");
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> subIndent();
		$this -> putLine ("}");
	}

	public function formatOp (Op $op)
	{
		// Especially with nice debugging comments & messages,
		// it's not hard to cause php to stack overflow. A huge
		// percentage of the calls to this function are "tail calls",
		// so we're going to emulate tail calls by manually pushing
		// and popping things-to-be-formatted to/from a stack.

		//echo "Stack depth: ".sizeof (debug_backtrace())."\n";
		$this -> formatStackFrames[] = $this -> formatStack;
		$this -> formatStack = array($op);

		while (sizeof ($this -> formatStack) > 0)
		{
			$currentOp = array_pop ($this -> formatStack);
			$this -> formatSingleOp ($currentOp);
		}

		$this -> formatStack = array_pop ($this -> formatStackFrames);
	}

	public function formatStackPush (Op $op)
	{
		$this -> formatStack[] = $op;
	}

	public function formatSingleOp (Op $op)
	{
		switch ($op -> getOpType())
		{
			case Op::OP_COND:
				return $this -> formatCond ($op);
			break;

			case Op::OP_COMMENT:
				return $this -> formatComment ($op);
			break;

			case Op::OP_DEBUG:
				return $this -> formatDebug ($op);
			break;

			case Op::OP_ERROR:
				return $this -> formatError ($op);
			break;

			case Op::OP_GET_MATCH_RESULT:
				return $this -> formatGetMatchResult ($op);
			break;

			case Op::OP_INCR:
				return $this -> formatIncr ($op);
			break;

			case Op::OP_LABEL:
				return $this -> formatLabel ($op);
			break;

			case Op::OP_LOOP:
				return $this -> formatLoop ($op);
			break;

			case Op::OP_MATCH:
				return $this -> formatMatch ($op);
			break;

			case Op::OP_NOP:
				$this -> putLine ("// NOP");
			break;

			case Op::OP_RETURN_CONST:
				return $this -> formatReturnConst ($op);
			break;

			case Op::OP_RULE:
				return $this -> formatRule ($op);
			break;

			case Op::OP_SET:
				return $this -> formatSet ($op);
			break;

			case Op::OP_SET_CONST:
				return $this -> formatSetConst ($op);
			break;

			case Op::OP_SET_SUBSTR:
				return $this -> formatSetSubstr ($op);
			break;

			case Op::OP_THUNK:
				return $this -> formatThunk ($op);
			break;

			default:
				throw new \InvalidArgumentException (sprintf ("Unknown op type %d (class %s)", $op -> getOpType(), get_class ($op)));
			break;
		}
	}

	protected function formatArgsArray (array $args, $parensIfEmpty=true)
	{
		$argStrs = array();

		foreach ($args as $arg)
		{
			$argStrs[] = "\$".$arg -> getName();
		}

		if (sizeof ($argStrs) == 0 && !$parensIfEmpty)
		{
			return "";
		}
		else
		{
			return " (".implode (", ", $argStrs).")";
		}
	}

	protected function formatConditionArray (array $args)
	{
		return "(\$".$args[0] -> getName()." ".$args[1]." ".$args[2].")";
	}

	protected function formatIf ($stringCond, Op $thenExpr)
	{
		$this -> putLine ("if ($stringCond)");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> formatOp ($thenExpr);
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");
	}

	protected function formatCond (OpCond $cond)
	{
		$this -> formatIf ($this -> formatConditionArray ($cond -> expr), $cond -> successExpr);
//		$this -> formatOp ($cond -> failExpr);
		$this -> formatStackPush ($cond -> failExpr);
	}

	protected function formatComment (OpComment $comment)
	{
		foreach ($comment -> lines as $line)
		{
			$this -> putLine ("// ".$line);
		}
//		$this -> formatOp ($comment -> thenExpr);
		$this -> formatStackPush ($comment -> thenExpr);
	}

	protected function formatDebug (OpDebug $debug)
	{
		if ($this -> config -> generateParserDebugMessages())
		{
			switch ($debug -> debugType)
			{
				case OpDebug::DEBUG_ENTER:
					$this -> putLine ("if (\$this -> debugLog)");
					$this -> putLine ("{");
					$this -> addIndent();
					$this -> putLine ("\$this -> indent += 1;");
					$this -> putLine ("printf (\"%sbegin rule ".$debug->args[0]." (position %d)\\n\", str_repeat (' ', \$this -> indent), \$".$debug->args[1]->getName().");");
					$this -> subIndent();
					$this -> putLine ("}");
					$this -> putLine ("");
				break;
	
				case OpDebug::DEBUG_SUCCESS:
					$this -> putLine ("if (\$this -> debugLog)");
					$this -> putLine ("{");
					$this -> addIndent();
					$this -> putLine ("printf (\"%ssuccess rule ".$debug->args[0]." (position %d)\\n\", str_repeat (' ', \$this -> indent), \$".$debug->args[1]->getName().");");
					$this -> putLine ("\$this -> indent -= 1;");
					$this -> subIndent();
					$this -> putLine ("}");
					$this -> putLine ("");
				break;
	
				case OpDebug::DEBUG_FAIL:
					$this -> putLine ("if (\$this -> debugLog)");
					$this -> putLine ("{");
					$this -> addIndent();
					$this -> putLine ("printf (\"%sfail rule ".$debug->args[0]." (position %d)\\n\", str_repeat (' ', \$this -> indent), \$".$debug->args[1]->getName().");");
					$this -> putLine ("\$this -> indent -= 1;");
					$this -> subIndent();
					$this -> putLine ("}");
					$this -> putLine ("");
				break;
	
				default:
					throw new \InvalidArgumentException (sprintf ("Unknown opdebug type %d (class %s)", $op -> debugType));
				break;
			}
		}

		//$this -> formatOp ($debug -> thenExpr);
		$this -> formatStackPush ($debug -> thenExpr);
	}

	protected function formatError (OpError $error)
	{
		if (!array_key_exists ($error -> errorMessage, $this -> errorIds))
		{
			$this -> errorIds[$error -> errorMessage] = sizeof ($this -> errorIds) + 1;
		}

		$errorId = $this -> errorIds[$error -> errorMessage];
		$posName = $error -> position -> getName();

		$this -> putLine ("if (\$$posName > \$this -> errorPosition)");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> putLine ("\$this -> errorId = $errorId;");
		$this -> putLine ("\$this -> errorPosition = \$$posName;");
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> formatStackPush ($error -> thenExpr);
	}

	protected function formatGetMatchResult (OpGetMatchResult $getMatchResult)
	{
		$resultVarName = $getMatchResult -> resultVar -> getName();
		$matchVarName = $getMatchResult -> matchVar -> getName();

		$this -> putLine ("\${$resultVarName} = \${$matchVarName}[\"$resultVarName\"];");
		$this -> formatStackPush ($getMatchResult -> thenExpr);
	}

	protected function formatIncr (OpIncr $incr)
	{
		$this -> putLine ("\$".$incr -> incrVar -> getName()." += 1;");
		$this -> formatStackPush ($incr -> thenExpr);
	}

	protected function formatLabel (OpLabel $label)
	{
		$this -> labels[$label -> getLabelName()] = $label;
		$this -> putLine ("goto ".$label -> getLabelName().";");
	}

	protected function formatLoop (OpLoop $loop)
	{
		throw new \RuntimeException ("This should never be called ".__FILE__.' '.__LINE__);
		return $this -> formatOp ($loop -> reduce());


		if ($loop -> condition === NULL)
		{
			$whileCond = "(true)";
		}
		else
		{
			$whileCond = $this -> formatConditionArray ($loop -> condition);
		}

		$this -> putLine ("while $whileCond");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> formatOp ($loop -> body);
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");
		//$this -> formatOp ($loop -> thenExpr);
	}

	protected function formatMatch (OpMatch $match)
	{
		switch ($match -> getMatchType())
		{
			case Literal::TYPE_CHARACTER_CLASS:
				return $this -> formatMatchCharacterClass ($match);
			break;

			case Literal::TYPE_DOT:
				return $this -> formatMatchDot ($match);
			break;

			case Literal::TYPE_STRING:
				if (strlen ($match -> getMatchExpr() -> text) == 1)
				{
					return $this -> formatMatchCharacter ($match);
				}
				else
				{
					return $this -> formatMatchString ($match);
				}
			break;

			case Literal::TYPE_REGEXP:
				$this -> formatMatchRegExp ($match);
			break;

			default:
				throw new \InvalidArgumentException (sprintf ("Unknown match type %d / class %s in file %s on line %d", $match -> getMatchType(), get_class ($match -> getMatchExpr()), __FILE__, __LINE__));
			break;
		}
	}

	public function formatMatchCharacter (OpMatch $match)
	{
		$escapedString = $this -> stringHelper -> escapeDoubleQuotedString ($match -> getMatchExpr() -> text);
		$positionName = $match -> position -> getName();
 
		$this -> formatIf (sprintf ("\$%s >= \$this -> length || \$this -> text[\$%s] !== \"%s\"", $positionName, $positionName, $escapedString), $match -> failExpr);
		$this -> putLine ("\$$positionName += 1;");
//		$this -> formatOp ($match -> successExpr);
		$this -> formatStackPush ($match -> successExpr);
	}

	public function formatMatchCharacterClass (OpMatch $match)
	{
		if (is_string ($match -> getMatchExpr() -> text))
		{
			$escapedString = $this -> stringHelper -> escapePregCharacterClass ($match -> getMatchExpr() -> text);
		}
		else
		{
			$escapedString = $this -> stringHelper -> escapePregCharacterClassArray ($match -> getMatchExpr() -> text);
		}

		$positionName = $match -> position -> getName();

		$matches = $this -> state -> getUniqueName ("m");

		$this -> formatIf (sprintf ("!preg_match (\"~%s~AS\", \$this -> text, \$%s, 0, \$%s)", $escapedString, $matches, $positionName), $match -> failExpr);
		$this -> putLine ("\$$positionName += 1;");
//		$this -> formatOp ($match -> successExpr);
		$this -> formatStackPush ($match -> successExpr);
	}

	public function formatMatchDot (OpMatch $match)
	{
		$positionName = $match -> position -> getName();
		$this -> formatIf ("\$$positionName >= \$this -> length", $match -> failExpr);
		$this -> putLine ("\$$positionName += 1;");
//		$this -> formatOp ($match -> successExpr);
		$this -> formatStackPush ($match -> successExpr);
	}

	public function formatMatchString (OpMatch $match)
	{
		$escapedString = $this -> stringHelper -> escapeDoubleQuotedString ($match -> getMatchExpr() -> text);
		$positionName = $match -> position -> getName();

		if (defined ('BOOT') && BOOT)
		{
			$lengthExpr = "strlen (\"%s\")";
			$lengthArg = $escapedString;
		}
		else
		{
			$lengthExpr = "%d";
			$lengthArg = strlen ($match -> getMatchExpr() -> text);
		}

		$this -> putLine (sprintf ("if (\$%s >= \$this -> length || substr_compare (\$this -> text, \"%s\", \$%s, ".$lengthExpr.") !== 0)", $match -> position -> getName(), $escapedString, $match -> position -> getName(), $lengthArg));
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> formatOp ($match -> failExpr);
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");
		$this -> putLine (sprintf ("\$$positionName += $lengthExpr;", $lengthArg));

		$addLine = 0;
		$addCol = 0;
		preg_match_all ('~(?P<Newline>\r\n|\n|\r)|(?P<Tab>\t)|(?P<Char>.)~m', $match -> getMatchExpr() -> text, $matches, PREG_SET_ORDER);

		$this -> formatStackPush ($match -> successExpr);
	}

	protected function formatMatchRegExp (OpMatch $match)
	{
		$matches = $this -> state -> getUniqueName ("m");
		$matchVar = new ParseVar ($matches, VarType::MatchResultType());
		$success = $match -> successExpr;
		$escapedRegExp = $this -> formatRegExp ($match -> getMatchExpr(), $matchVar, $success);
		$positionName = $match -> position -> getName();

		$mflag = (preg_match ("~\\\\[nr]~", $escapedRegExp) ? 'm' : '');

		$this -> formatIf (sprintf ("!preg_match (\"~%s~{$mflag}AS\", \$this -> text, \$%s, 0, \$%s)", $escapedRegExp, $matches, $positionName), $match -> failExpr);
		$this -> putLine ("\$$positionName += strlen (\${$matches}[0]);");
//		$this -> formatOp ($success);
		$this -> formatStackPush ($success);
	}

	protected function formatRegExp (RegExp $expr, ParseVar $matchVar, Op &$success)
	{
		switch ($expr -> getRegExpType())
		{
			case RegExp::REGEXP_TYPE_ASSERTION:
				return $this -> formatRegExpAssertion ($expr, $matchVar, $success);
			break;

			case RegExp::REGEXP_TYPE_CAPTURE:
				return $this -> formatRegExpCapture ($expr, $matchVar, $success);
			break;

			case RegExp::REGEXP_TYPE_CHOICE:
				return $this -> formatRegExpChoice ($expr, $matchVar, $success);
			break;

			case RegExp::REGEXP_TYPE_LITERAL:
				return $this -> formatRegExpLiteral ($expr -> getLiteral(), $matchVar, $success, $expr -> getResultVar());
			break;

			case RegExp::REGEXP_TYPE_REPEAT:
				return $this -> formatRegExpRepeat ($expr, $matchVar, $success);
			break;

			case RegExp::REGEXP_TYPE_SEQUENCE:
				return $this -> formatRegExpSequence ($expr, $matchVar, $success);
			break;

			default:
				throw new \InvalidArgumentException ("Unknown regexp: ".var_export ($expr, true));
			break;
		}
	}

	protected function formatRegExpAssertion (RegExp $expr, ParseVar $matchVar, Op &$success)
	{
		$regExp = $expr -> getRegExp();
//		$success = new OpGetMatchResult ($matchVar, $expr -> getResultVar(), $success);
		$regExpStr = $this -> formatRegExp ($regExp, $matchVar, $success);

		if ($expr -> getResultVar())
		{
			throw new \RuntimeException ("Variables not supported for assertions (yet)");
		}

		return ($expr -> negative ? "(?!$regExpStr)" : "(?=$regExpStr)");
	}

	protected function formatRegExpCapture (RegExp $expr, ParseVar $matchVar, Op &$success)
	{
		$regExp = $expr -> getRegExp();
		$success = new OpGetMatchResult ($matchVar, $expr -> getResultVar(), $success);
		$regExpStr = $this -> formatRegExp ($regExp, $matchVar, $success);
		return "(?P<".$expr -> getResultVar() -> getName().">$regExpStr)";
	}

	protected function formatRegExpChoice (RegExp $expr, ParseVar $matchVar, Op &$success)
	{
		$regExps = $expr -> getRegExps();
		$regExpStrs = array();

		foreach ($regExps as $regExp)
		{
			$regExpStrs[] = "(?:".$this -> formatRegExp ($regExp, $matchVar, $success).")";
		}
		//var_dump ($regExpStrs);

		return "(?:".implode ("|", $regExpStrs).")";
	}

	protected function formatRegExpLiteral (Literal $literal, ParseVar $matchVar, Op &$success, ParseVar $resultVar=NULL)
	{
		switch ($literal -> getLiteralType())
		{
			case Literal::TYPE_CHARACTER_CLASS:
				if (is_string ($literal -> text))
				{
					$escaped = $this -> stringHelper -> escapePregCharacterClass ($literal -> text);
				}
				else
				{
					$escaped = $this -> stringHelper -> escapePregCharacterClassArray ($literal -> text);
				}

				if ($resultVar !== NULL)
				{
					return "(?P<".$resultVar -> getName().">$escaped)";
				}
				else
				{
					return $escaped;
				}
			break;

			case Literal::TYPE_DOT:
				return ".";
			break;

			case Literal::TYPE_STRING:
				//$escaped = $this -> stringHelper -> escapeDoubleQuotedString ($match -> getMatchExpr() -> text);
				$escaped = $this -> stringHelper -> escapeDoubleQuotedString (preg_quote ($literal -> text, "~"));

				if ($resultVar !== NULL)
				{
					return "(?P<".$resultVar -> getName().">$escaped)";
				}
				else
				{
					return $escaped;
				}
			break;

			default:
				throw new \InvalidArgumentException ("Unknown literal ".var_export ($literal, true));
			break;
		}
	}

	protected function formatRegExpRepeat (RegExp $expr, ParseVar $matchVar, Op &$success)
	{
		$min = $expr -> getMinCount();
		$max = $expr -> getMaxCount();

		if ($min === 0 && $max === -1)
		{
			$quant = "*";
		}
		elseif ($min === 1 && $max === -1)
		{
			$quant = "+";
		}
		elseif ($min === 0 && $max === 1)
		{
			$quant = "?";
		}
		else
		{
			$quant = "{{$min}, {$max}}";
		}

		$regExp = $expr -> getRegExp();
		$regExpStr = $this -> formatRegExp ($regExp, $matchVar, $success);
		return "(?:(?:$regExpStr)$quant)";
	}

	protected function formatRegExpSequence (RegExp $expr, ParseVar $matchVar, Op &$success)
	{
		$regExps = $expr -> getRegExps();
		$regExpStrs = array();

		foreach ($regExps as $regExp)
		{
			$regExpStrs[] = "(?:".$this -> formatRegExp ($regExp, $matchVar, $success).")";
		}
		//var_dump ($regExpStrs);

		return implode ("", $regExpStrs);
	}

	protected function formatReturnConst (OpReturnConst $returnConst)
	{
		if (is_bool ($returnConst -> value))
		{
			$constStr = ($returnConst -> value ? 'true' : 'false');
		}
		else
		{
			throw new \InvalidArgumentException ("Don't know how to format type ".gettype ($returnConst -> value));
		}

		$this -> putLine ("return $constStr;");
	}

	protected function formatRule (OpRule $rule)
	{
		$vars = implode (", ", array_map (function ($v) { return "\$".$v -> getName(); }, $rule -> ruleArgs));
		$vars .= ', $line, $column';

		$this -> putLine ("");
		$this -> putLine ("if (!\$this -> parseRule".$rule -> ruleName." ($vars))");
		$this -> putLine ("{");
		$this -> addIndent();
		$this -> formatOp ($rule -> failExpr);
		$this -> subIndent();
		$this -> putLine ("}");
		$this -> putLine ("");
		//$this -> formatOp ($rule -> successExpr);
		$this -> formatStackPush ($rule -> successExpr);
	}

	protected function formatSet (OpSet $set)
	{
		$this -> putLine ("\$".$set -> target -> getName()." = \$".$set -> source -> getName().";");
		//$this -> formatOp ($set -> thenExpr);
		$this -> formatStackPush ($set -> thenExpr);
	}

	protected function formatSetConst (OpSetConst $set)
	{
		switch ($set -> value -> getLiteralType())
		{
			case Literal::TYPE_INTEGER:
				$literalString = strval ($set -> value -> value);
			break;

			default:
				throw new \RuntimeException ("Don't know how to print literal type ".$set -> value -> getLiteralType());
			break;
		}

		$this -> putLine ("\$".$set -> target -> getName()." = ".$literalString.";");
		//$this -> formatOp ($set -> thenExpr);
		$this -> formatStackPush ($set -> thenExpr);
	}

	protected function formatSetSubstr (OpSetSubstr $substr)
	{
		$targetVar = $substr -> target -> getName();
		$firstVar = $substr -> first -> getName();
		$lastVar = $substr -> last -> getName();
		$this -> putLine ("\$$targetVar = substr (\$this -> text, \$$firstVar, \$$lastVar - \$$firstVar);");
		//$this -> formatOp ($substr -> thenExpr);
		$this -> formatStackPush ($substr -> thenExpr);
	}

	protected function formatThunk (OpThunk $thunk)
	{
		$thunkDef = $thunk -> getThunk();
		$this -> thunks[$thunkDef -> getName()] = $thunkDef;

		$argStrs = array();

		foreach ($thunk -> vars as $arg)
		{
			if ($arg instanceof ParseVar)
			{
				$argStrs[] = "\$".$arg -> getName();
			}
		}

		$argStrs[] = "\$this -> globalVars";
		$argString = " (".implode (", ", $argStrs).")";

		$this -> putLine (sprintf ("\$%s = new %s%s;", $thunk -> getVarName(), $thunk -> getClassName(), $argString));
		//$this -> formatOp ($thunk -> thenExpr);
		$this -> formatStackPush ($thunk -> thenExpr);
	}
}
