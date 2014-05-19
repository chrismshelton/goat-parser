<?php

namespace Goat;

class GlobalVar
{
	private $name;
	private $sourceName;
	private $index;
	private $typeHint;

	public function __construct ($sourceName, $index, $typeHint=NULL)
	{
//		$this -> name = $sourceName.uniqueId();
		$this -> sourceName = $sourceName;
		$this -> index = $index;
		$this -> typeHint = $typeHint;
	}

	public function getName()
	{
		return $this -> sourceName;
	}

	public function getSourceName()
	{
		return $this -> sourceName;
	}

	public function getIndex()
	{
		return $this -> index;
	}

	public function getTypeHint()
	{
		return $this -> typeHint;
	}

	public function hasTypeHint()
	{
		return $this -> typeHint;
	}
}
