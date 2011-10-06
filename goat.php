<?php
/*	Copyright (c) 2011 by Chris Shelton
 *
 *	Permission is hereby granted, free of charge, to any person obtaining a
 *	copy of this software and associated documentation files (the "Software"),
 *	to deal in the Software without restriction, including without limitation
 *	the rights to use, copy, modify, merge, publish, distribute, sublicense,
 *	and/or sell copies of the Software, and to permit persons to whom the
 *	Software is furnished to do so, subject to the following conditions:
 *
 *	The above copyright notice and this permission notice shall be included
 *	in all copies or substantial portions of the Software.
 *
 *	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 *	OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 *	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
 *	IN THE SOFTWARE.
 *
 *	Based off Ian Piumarta's peg/leg C parser generators: http://piumarta.com/software/peg/
 */

$dir = dirname(__FILE__);
$dir = $dir . DIRECTORY_SEPARATOR;
require_once $dir.'nodes.php';
require_once $dir.'packrat.php';
require_once $dir.'peg.boot.php';

$cli_help = <<<CLR
	goat.php
	usage: php goat.php [options]... <file> [<outfile>]
	Options:
		-h, --help		shows this menu
		-d, --debug-mode	output parser with debug options

		sample:
		php goat.php php.peg	# generates contents of 'peg.boot.php'

CLR;

if(PHP_SAPI == 'cli')
	cli_init();

function cli_init()
{
	global $argc, $argv, $cli_help;

	$ct = 1;
	$in = null;
	$out = 'php://stdout';
	while($ct < $argc) {
		switch($argv[$ct])
		{
		case('-d'): define('PARSER_DEBUG', 1); break;
		case('-h'): cli_help(false); break;
		default:
			if($in == null) $in = $argv[$ct];
			elseif($out == 'php://stdout') $out = $argv[$ct];
			else cli_help();
		}
		$ct ++;
	}
	if($in == null) cli_help();
	$grammar = file_get_contents($in);
	$comp = goatMain($grammar);
	$outputFile = fopen($out, 'wb');
	fwrite($outputFile, $comp);
	fclose($outputFile);
	exit(0);
}

function goatMain($grammar) {
	$pp = new PackratParserGenerator;
	$pp = parseInput($pp, $grammar);
	$output = compileGrammar($pp);
	return $output;
}

function cli_help($err = true)
{
	global $cli_help;
	if($err == false) {
		print $cli_help;
		exit(0);
	} else {
		fprintf(STDERR, "%s", $cli_help);
		exit(1);
	}
}

function compileGrammar($pp)
{
	ob_start();
	$list = $pp->list;
	$ct = count($list->actions);
	print "<?php\nclass PackratParserGenerator extends Packrat {\n";
	for($i = 0; $i < $ct; $i++)
	{
	        $list->actions[$i]->define();
	}

	$ct = count($list->nodes);
	for($i = 0; $i < $ct; $i++)
	{
	        $list->nodes[$i]->compile();	
	}
	print "\n\tfunction Parse()\n\t{\n\t\treturn \$this->yyParseFrom('yy";
	print $list->nodes[0]->name;
	print "');\n\t}\n";
	print "\n}";
	$comp = ob_get_contents();
	ob_end_clean();
	return $comp;
}

function parseInput($pp, $input)
{
	$pp->setText($input);

	$res = $pp->parse();
	while($res != false) {
	        $res = $pp->parse();
	}
	return $pp;
}
