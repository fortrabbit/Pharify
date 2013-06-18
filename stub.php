#!/usr/bin/env php
<?php

Phar::mapPhar("pharify.phar");

include_once "phar://pharify.phar/vendor/autoload.php";

$pharify = new Pharify\Console();


if (file_exists(".pharify")) {
    $newArgv = $argv;
    array_shift($newArgv);
    $newArgv = array_merge($newArgv, preg_split('/\s+/', file_get_contents(".pharify")));
    $input = new Symfony\Component\Console\Input\ArgvInput($newArgv);
    $pharify->run($input);
} else {
    $pharify->run();
}

__HALT_COMPILER();