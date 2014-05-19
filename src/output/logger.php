<?php

namespace Goat;

class Logger
{
	public function __construct (Config $config)
	{
		$this -> config = $config;
	}

	public function logDebug ($debug)
	{
		if ($this -> config -> enableDebugLogs())
		{
			fprintf (STDOUT, "%s\n", $debug);
		}
	}

	public function logWarning ($warning)
	{
		if ($this -> config -> enableWarnings())
		{
			fprintf (STDERR, "Warning: %s\n", $warning);
		}
	}

}
