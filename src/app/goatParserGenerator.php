<?php

namespace Goat;

class GoatParserGenerator
{
	public function __construct (Config $config)
	{
		$this -> config = $config;;
		$this -> state = new State ($this -> config);
		$this -> parser = new GrammarParser;
		$this -> opCleaner = new OpCleaner;
	}

	public function applyOptions (array $definitions)
	{
		$rules = array();

		foreach ($definitions as $definition)
		{
			if ($definition -> isRule())
			{
				$rules[] = $definition;
			}
			else
			{
				$this -> applyOptionNode ($definition);
			}
		}

		return $rules;
	}

	public function applyOptionNode (Node $node)
	{
		$arguments = $node -> getOptionArguments();

		switch ($node -> getOptionName())
		{
			case 'class':
				$this -> config -> setParserClassName ($arguments[0]);
			break;

			case 'namespace':
				$this -> config -> setParserNamespace ($arguments[0]);
			break;

			case 'inline':
				$this -> config -> addInlineRule ($arguments[0]);
			break;

			case 'global':
				if (sizeof ($arguments) == 2)
				{
					$this -> config -> addGlobalVariable ($arguments[0], $arguments[1]);
				}
				else
				{
					$this -> config -> addGlobalVariable ($arguments[0]);
				}
			break;

			case 'top':
				$this -> config -> addTopLevelRule ($arguments[0]);
			break;

			default:
				throw new \InvalidArgumentException ("Don't know how to set option '".$node -> getOptionName()."'");
			break;
		}
	}

	public function compileFile ($grammarFile)
	{
		return $this -> compileString (file_get_contents ($grammarFile));
	}

	public function compileString ($grammar)
	{
		$rules = $this -> parser -> parseGrammar ($grammar, $this -> config);
		$newRules = $this -> applyOptions ($rules);

		$compiler = new Compiler ($this -> state);
		$parseMethods = $compiler -> compileRules ($newRules);
		$newParseMethods = array();

		foreach ($parseMethods as $parseMethod)
		{
			$newParseMethods[] = $parseMethod -> swapOp ($this -> opCleaner -> cleanupOps ($this -> state, $parseMethod -> opSequence));
		}

		$formatter = new OpFormatter ($this -> state, new StringHelper);
		$formatter -> formatParser ($newParseMethods);
	}
}
