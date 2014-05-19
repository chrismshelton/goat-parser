<?php

namespace Goat;

class State
{
	private $config;

	public function __construct (Config $config)
	{
		$this -> config = $config;
		$this -> logger = new Logger ($config);
		$this -> reservedWords = $this -> getReservedWords();
		$this -> usedNames = array();
	}

	public function addGlobalVariable ($global)
	{
		$global = $this -> config -> addGlobalVariable ($global);
		$this -> nameInfoMap -> addGlobalVariable ($global);
	}

	public function getConfig()
	{
		return $this -> config;
	}

	public function getUniqueName ($name)
	{
		$this -> usedNames[$name] = 1;
		$uniqueName = $name.uniqueId();

		while (array_key_exists ($uniqueName, $this -> usedNames) || array_key_exists (strtolower ($uniqueName), $this -> reservedWords))
		{
			$uniqueName .= uniqueId();
		}

		$this -> usedNames[$uniqueName] = 1;
		return $uniqueName;
	}

	private function getReservedWords()
	{
		return array ('__halt_compiler' => 0, 'abstract' => 1, 'and' => 2, 'array' => 3,
			'as' => 4, 'break' => 5, 'callable' => 6, 'case' => 7, 'catch' => 8, 'class' => 9,
			'clone' => 10, 'const' => 11, 'continue' => 12, 'declare' => 13, 'default' => 14, 'die' => 15,
			'do' => 16, 'echo' => 17, 'else' => 18, 'elseif' => 19, 'empty' => 20, 'enddeclare' => 21,
			'endfor' => 22, 'endforeach' => 23, 'endif' => 24, 'endswitch' => 25, 'endwhile' => 26,
			'eval' => 27, 'exit' => 28, 'extends' => 29, 'final' => 30, 'for' => 31, 'foreach' => 32,
			'function' => 33, 'global' => 34, 'goto' => 35, 'if' => 36, 'implements' => 37, 'include' => 38,
			'include_once' => 39, 'instanceof' => 40, 'insteadof' => 41, 'interface' => 42, 'isset' => 43,
			'list' => 44, 'namespace' => 45, 'new' => 46, 'or' => 47, 'print' => 48, 'private' => 49,
			'protected' => 50, 'public' => 51, 'require' => 52, 'require_once' => 53, 'return' => 54,
			'static' => 55, 'switch' => 56, 'throw' => 57, 'trait' => 58, 'try' => 59, 'unset' => 60,
			'use' => 61, 'var' => 62, 'while' => 63, 'xor' => 64);
	}

	public function putUsedName ($name)
	{
		$this -> usedNames[$name] = 1;
	}
}
