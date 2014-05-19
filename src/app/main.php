<?php

namespace Goat;

set_include_path (dirname (__DIR__) . PATH_SEPARATOR . get_include_path());

require ("initialize.php");

$cli = new CliMain ($argv);
$cli -> run();
