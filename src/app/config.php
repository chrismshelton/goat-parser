<?php

namespace Goat;

class Config
{
	public function __construct()
	{
		$this -> exportRules = array();
		$this -> globalVariables = array();
		$this -> inlineRules = array();
		$this -> parserClassName = NULL;
		$this -> parserNamespace = NULL;
		$this -> inputFile = NULL;
		$this -> inputFilename = NULL;
		$this -> outputFile = NULL;
		$this -> outputFilename = NULL;
		$this -> outputParserDebugging = false;
	}

	public function addGlobalVariable ($name, $typeHint=NULL)
	{
		$global = new GlobalVar ($name, sizeof ($this -> globalVariables), $typeHint);
		$this -> globalVariables[] = $global;
		return $global;
	}

	public function addInlineRule ($ruleName)
	{
		$this -> inlineRules[] = $ruleName;
	}

	public function addTopLevelRule ($ruleName)
	{
		$this -> exportRules[] = $ruleName;
	}

	public function enableDebugLogs()
	{
		return false;
	}

	public function enableWarningLogs()
	{
		return true;
	}

	public function generateParserDebugMessages()
	{
		return $this -> outputParserDebugging;
	}

	public function getExportedRuleNames()
	{
		return $this -> exportRules;
	}

	public function getGlobalVariables()
	{
		return $this -> globalVariables;
	}

	public function getParserClassName()
	{
		return $this -> parserClassName;
	}

	public function getParserNamespace()
	{
		return $this -> parserNamespace;
	}

	public function getInputFilename()
	{
		return $this -> inputFilename;
	}

	public function getOutputFile()
	{
		if ($this -> outputFile === NULL)
		{
			if ($this -> outputFilename === NULL)
			{
				$this -> outputFile = STDOUT;
			}
			else
			{
				$this -> outputFile = fopen ($this -> outputFilename, "w");
			}
		}

		return $this -> outputFile;
	}

	public function hasInputFile()
	{
		return $this -> inputFilename !== NULL;
	}

	public function setGenerateParserDebugMessages ($outputParserDebugging)
	{
		$this -> outputParserDebugging = $outputParserDebugging;
	}

	public function setInputFilename ($inputFile)
	{
		$this -> inputFilename = $inputFile;
	}

	public function setOutputFilename ($outputFile)
	{
		$this -> outputFilename = $outputFile;
	}

	public function setParserClassName ($className)
	{
		$this -> parserClassName = $className;
	}

	public function setParserNamespace ($namespace)
	{
		$this -> parserNamespace = $namespace;
	}
}
