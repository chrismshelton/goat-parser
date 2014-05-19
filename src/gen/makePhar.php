<?php

$srcDir = dirname (__DIR__).DIRECTORY_SEPARATOR;
set_include_path ($srcDir . PATH_SEPARATOR . get_include_path());

$argc = sizeof ($argv);
$i = 1;
$filename = "/tmp/goatp.phar";

while ($i < $argc)
{
	if ($argv[$i] == '-o')
	{
		$filename = $argv[$i + 1];
		$i += 2;
		continue;
	}	
	else
	{
		die ("Unknown argument ".$argv[$i]);
	}
}

if (!preg_match ('~\.phar~i', $filename))
{
	$pharFilename = $filename.'.phar';
}
else
{
	$pharFilename = $filename;
}

$phar = new Phar ($pharFilename);
$header = $phar -> createDefaultStub ("app/main.php");
$phar -> startBuffering();
add_phar_file ($phar, "initialize.php");
add_phar_file ($phar, "app/cliMain.php");
add_phar_file ($phar, "app/compiler.php");
add_phar_file ($phar, "app/config.php");
add_phar_file ($phar, "app/main.php");
add_phar_file ($phar, "app/goat.parser.php");
add_phar_file ($phar, "app/goatParserGenerator.php");
add_phar_file ($phar, "app/state.php");
add_phar_file ($phar, "info/infoBuilder.php");
add_phar_file ($phar, "info/ruleInfo.php");
add_phar_file ($phar, "info/ruleInfoMap.php");
add_phar_file ($phar, "output/logger.php");
add_phar_file ($phar, "output/opFormatter.php");
add_phar_file ($phar, "output/stringHelper.php");
add_phar_file ($phar, "transform/exprBuilder.php");
add_phar_file ($phar, "transform/exprInliner.php");
add_phar_file ($phar, "transform/exprOpBuilder.php");
add_phar_file ($phar, "transform/exprToRegExp.php");
add_phar_file ($phar, "transform/leftRecursionFixer.php");
add_phar_file ($phar, "transform/opCleaner.php");
add_phar_file ($phar, "tree/definitions.php");
add_phar_file ($phar, "tree/expr.php");
add_phar_file ($phar, "tree/globalVar.php");
add_phar_file ($phar, "tree/literal.php");
add_phar_file ($phar, "tree/nodes.php");
add_phar_file ($phar, "tree/ops.php");
add_phar_file ($phar, "tree/parseVar.php");
add_phar_file ($phar, "tree/thunk.php");
add_phar_file ($phar, "tree/varType.php");
$phar -> compressFiles (Phar::GZ);
$phar -> stopBuffering();
$phar -> setStub ("#!/usr/bin/env php\n".$header);

if ($pharFilename !== $filename)
{
	rename ($pharFilename, $filename);
}

// add executable bits
chmod ($filename, (fileperms ($filename) & 0777) | 0111);

function add_phar_file (Phar $phar, $relPath, $alias=NULL)
{
	if ($alias === NULL)
	{
		$aliasPath = $relPath;
	}
	else
	{
		$aliasPath = $alias;
	}

	// Why does this handle relative paths different from EVERYTHING ELSE IN PHP
	//$phar -> addFile (stream_resolve_include_path ($relPath), $aliasPath);
	$phar -> addFromString ($aliasPath, php_strip_whitespace ($relPath));
}
