<?php

namespace Goat;

require ("app/cliMain.php");
require ("app/compiler.php");
require ("app/config.php");
require ("app/goatParserGenerator.php");
require ("app/goat.parser.php");
require ("app/state.php");
require ("info/infoBuilder.php");
require ("info/ruleInfo.php");
require ("info/ruleInfoMap.php");
require ("output/logger.php");
require ("output/opFormatter.php");
require ("output/stringHelper.php");
require ("transform/exprBuilder.php");
require ("transform/exprInliner.php");
require ("transform/exprOpBuilder.php");
require ("transform/exprToRegExp.php");
require ("transform/leftRecursionFixer.php");
require ("transform/opCleaner.php");
require ('tree/definitions.php');
require ('tree/expr.php');
require ('tree/globalVar.php');
require ('tree/literal.php');
require ("tree/nodes.php");
require ('tree/ops.php');
require ('tree/parseVar.php');
require ('tree/thunk.php');
require ('tree/varType.php');
