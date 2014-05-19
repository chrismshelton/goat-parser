<?php

namespace Goat;

abstract class CliArgument
{
	protected $longName;
	protected $shortName;
	protected $argumentName;
	protected $limit;
	protected $description;

	public function assertArgumentEmpty ($argument)
	{
		if (!empty ($argument))
		{
			$args = array();

			if ($this -> longName !== NULL)
			{
				$args[] = "--".$this -> longName;
			}

			if ($this -> shortName !== NULL)
			{
				$args[] = "-".$this -> shortName;
			}

			$argName = implode ("/", $args);
			throw new \RuntimeException ("Unexpected argument for argument $argName");
		}
	}

	public function assertArgumentNotEmpty ($argument)
	{
		if (empty ($argument))
		{
			$args = array();

			if ($this -> longName !== NULL)
			{
				$args[] = "--".$this -> longName;
			}

			if ($this -> shortName !== NULL)
			{
				$args[] = "-".$this -> shortName;
			}

			$argName = implode ("/", $args);
			throw new \RuntimeException ("Expected non-empty argument '{$this -> argumentName}' for argument $argName");
		}
	}

	public function getArgumentName()
	{
		return $this -> argumentName;
	}

	public function getDescription()
	{
		return $this -> description;
	}

	public function getLimit()
	{
		return $this -> limit;
	}

	public function getLongName()
	{
		return $this -> longName;
	}

	public function getShortName()
	{
		return $this -> shortName;
	}

	public function match ($args, &$position, Config $config, CliMain $main)
	{
		$argument = NULL;

		if (($this -> longName !== NULL && $args[$position] === '--'.$this -> longName))
		{
			if ($this -> argumentName !== NULL)
			{
				if ($position + 1 >= sizeof ($args))
				{
					throw new \RuntimeException ("Expected argument '{$this -> argName}' for option --{$this -> longName}");
				}

				$argument = $args[$position + 1];
				$argCount = 2;
			}
			else
			{
				$argument = NULL;
				$argCount = 1;
			}
		}
 		elseif ($this -> shortName !== NULL && $args[$position] === '-'.$this -> shortName)
		{
			if ($this -> argumentName !== NULL)
			{
				if ($position + 1 >= sizeof ($args))
				{
					throw new \RuntimeException ("Expected argument '{$this -> argName}' for option -{$this -> shortName}");
				}

				$argument = $args[$position + 1];
				$argCount = 2;
			}
			else
			{
				$argument = NULL;
				$argCount = 1;
			}
		}
		elseif ($this -> longName !== NULL && preg_match ('~^--'.preg_quote ($this -> longName).'=(.*)$~', $args[$position], $match))
		{
			$argCount = 1;
			$argument = $match[1];
		}
		else
		{
			return false;
		}

		$this -> set ($config, $main, $argument);
		$position += $argCount;
		return true;
	}

	abstract public function set (Config $config, CliMain $main, $argument);
}

class ParseFromArgument extends CliArgument
{
	protected $longName = "parse-from";
	protected $argumentName = "RULE";
	protected $description = "Create a top level, public parse method starting from <rule>";

	public function set (Config $config, CliMain $main, $argument)
	{
		$this -> assertArgumentNotEmpty ($argument);
		$config -> addTopLevelRule ($argument);
	}
}

class ClassNameArgument extends CliArgument
{
	protected $shortName = "c";
	protected $longName = "class-name";
	protected $argumentName = "NAME";
	protected $limit = 1;
	protected $description = "Class name of the generated parser";

	public function set (Config $config, CliMain $main, $argument)
	{
		$this -> assertArgumentNotEmpty ($argument);
		$config -> setParserClassName ($argument);
	}
}

class NamespaceArgument extends CliArgument
{
	protected $longName = "namespace";
	protected $shortName = "n";
	protected $argumentName = "NAME";
	protected $limit = 1;
	protected $description = "Namespace of the generated parser";

	public function set (Config $config, CliMain $main, $argument)
	{
		$this -> assertArgumentNotEmpty ($argument);
		$config -> setParserNamespace ($argument);
	}
}

class HelpArgument extends CliArgument
{
	protected $longName = "help";
	protected $shortName = "h";
	protected $description = "Show the help menu (this menu)";

	public function set (Config $config, CliMain $main, $argument)
	{
		$main -> showHelp (false);
		exit (0);
	}
}

class InlineRuleArgument extends CliArgument
{
	protected $longName = "inline-rule";
	protected $shortName = "i";
	protected $argumentName = "RULE";
	protected $description = "Inline <rule> at each call site";

	public function set (Config $config, CliMain $main, $argument)
	{
		$this -> assertArgumentNotEmpty ($argument);
		$config -> addInlineRule ($argument);
	}
}

class GlobalVariableArgument extends CliArgument
{
	protected $longName = "global-variable";
	protected $shortName = "g";
	protected $argumentName = "NAME[,TYPE]";
	protected $description = "Add \"global\" variable <name> (with optional type hint)";

	public function set (Config $config, CliMain $main, $argument)
	{
		if (strpos ($argument, ",") !== false)
		{
			list ($name, $typeHint) = explode (",", $argument, 2);
		}
		else
		{
			$name = $argument;
			$typeHint = NULL;
		}

		$config -> addGlobalVariable ($name, $typeHint);
	}
}

class DebugParserArgument extends CliArgument
{
	protected $longName = "debug-parser";
	protected $shortName = "D";
	protected $description = "Generate parser with debugging messages";

	public function set (Config $config, CliMain $main, $argument)
	{
		$this -> assertArgumentEmpty ($argument);
		$config -> setGenerateParserDebugMessages (true);
	}
}

class OutputFileArgument extends CliArgument
{
	protected $shortName = "o";
	protected $longName = "output-file";
	protected $limit = 1;
	protected $argumentName = "FILE";
	protected $description = "Write parser code to <file> (default is STDOUT)";

	public function set (Config $config, CliMain $main, $argument)
	{
		$this -> assertArgumentNotEmpty ($argument);
		$config -> setOutputFilename ($argument);
	}
}

class InputFileArgument extends CliArgument
{
	protected $longName = "input-file";
	protected $shortName = "f";
	protected $argumentName = "FILE";
	protected $limit = 1;
	protected $description = "Read grammar from <file>";

	public function match ($args, &$position, Config $config, CliMain $main)
	{
		if (parent::match ($args, $position, $config, $main))
		{
			return true;
		}

		if (!$config -> hasInputFile())
		{
			$config -> setInputFilename ($args[$position]);
			$position += 1;
			return true;
		}
		else
		{
			return false;
		}
	}

	public function set (Config $config, CliMain $main, $argument)
	{
		$this -> assertArgumentNotEmpty ($argument);
		$config -> setInputFilename ($argument);
	}
}

class CliMain
{
	public function __construct ($argv)
	{
		$this -> cliName = $argv[0];
		$this -> argv = array_slice ($argv, 1);
		$this -> config = new Config;
	}

	public function getOptions()
	{
		$arguments = array();
		$arguments[] = new HelpArgument;
		$arguments[] = new ParseFromArgument;
		$arguments[] = new ClassNameArgument;
		$arguments[] = new NamespaceArgument;
		$arguments[] = new InlineRuleArgument;
		$arguments[] = new GlobalVariableArgument;
		$arguments[] = new DebugParserArgument;
		$arguments[] = new OutputFileArgument;
		$arguments[] = new InputFileArgument;

		return $arguments;
	}

	protected function parseArguments()
	{
		$arguments = $this -> getOptions();
		$position = 0;
		$argc = sizeof ($this -> argv);
		$used = array();

		while ($position < $argc)
		{
			foreach ($arguments as $ix => $argument)
			{
				if ($argument === NULL)
				{
					continue;
				}

				if ($argument -> match ($this -> argv, $position, $this -> config, $this))
				{
					if (!array_key_exists ($ix, $used))
					{
						$used[$ix] = 0;
					}

					$used[$ix] += 1;

					if ($argument -> getLimit() !== NULL && $used[$ix] >= $argument -> getLimit())
					{
						$arguments[$ix] = NULL;
					}

					continue 2;
				}
			}

			if ($this -> argv[$position] == '--parser-debug')
			{
				$position += 1;
				continue;
			}

			throw new \RuntimeException ("Unknown argument '".$this -> argv[$position]."' $position");
		}
	}

	public function run()
	{
		$this -> parseArguments();

		$grammarFile = $this -> config -> getInputFilename();

		if ($grammarFile == '' || !stream_resolve_include_path ($grammarFile))
		{
			$this -> showHelp();
			exit (1);
		}

		$gpg = new GoatParserGenerator ($this -> config);
		$gpg -> compileFile ($grammarFile);
//		$grammar = file_get_contents ($grammarFile);
//		$parser = new GrammarParser;
//		$rules = $parser -> parseGrammar ($grammar, $this -> config);
//		$newRules = $this -> applyOptions ($rules);
//		$compiler = new Compiler ($this -> state);
//		$ops = $compiler -> compileRules ($newRules);
//		$formatter = new OpFormatter ($this -> state, new StringHelper);
//		$formatter -> formatParser ($ops);
	}

	public function showHelp ($stdErr=true)
	{
		if ($stdErr)
		{
			$fd = STDERR;
		}
		else
		{
			$fd = STDOUT;
		}

		if (preg_match ('~\.php~', $this -> cliName))
		{
			$cliName = "php ".$this -> cliName;
		}
		else
		{
			$cliName = $this -> cliName;
		}

		fwrite ($fd, "Usage: ".$cliName." [options] <file>\n");
		fwrite ($fd, "Options\n");
		$fmtArguments = array();
		$maxArgumentLength = -1;
		$maxArgNameLength = -1;

		foreach ($this -> getOptions() as $argument)
		{
			$fmtArgument = array();

			$shortArg = $argument -> getShortName();
			$longArg = $argument -> getLongName();
			$argName = $argument -> getArgumentName();
			$description = $argument -> getDescription();

			if ($longArg !== NULL && strlen ($longArg) > $maxArgumentLength)
			{
				$maxArgumentLength = strlen ($longArg);
			}
			if ($argName !== NULL && strlen ($argName) > $maxArgNameLength)
			{
				$maxArgNameLength = strlen ($argName);
			}

			$fmtArguments[] = array ($shortArg, $longArg, $argName, $description);
		}

		$formatString = "%4s%-2s%-".($maxArgumentLength + 3)."s%-".($maxArgNameLength + 6)."s%s\n";

		foreach ($fmtArguments as $fmtArgument)
		{
			$sep = NULL;

			if ($fmtArgument[0] == NULL)
			{
				$short = "";
				$sep = "";
			}
			else
			{
				$short = "-".$fmtArgument[0];
			}

			if ($fmtArgument[1] == NULL)
			{
				$long = "";
				$sep = "";
			}
			else
			{
				$long = "--".$fmtArgument[1];

				if ($sep === NULL)
				{
					$sep = ",";
				}
			}

			if ($fmtArgument[2] == NULL)
			{
				$name = "";
			}
			else
			{
				$name = "<".strtolower ($fmtArgument[2]).">";
			}

			if ($fmtArgument[3] == NULL)
			{
				$desc = "";
			}
			else
			{
				$desc = $fmtArgument[3];
			}

			fprintf ($fd, $formatString, $short, $sep, $long, $name, $desc);
		}
	}
}
