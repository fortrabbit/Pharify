#!/usr/bin/env php
<?php

Phar::mapPhar("pharify.phar");

include_once "phar://pharify.phar/vendor/autoload.php";

$pharify = new Pharify\Console();

try {
    $pharify->run();
} catch (InvalidArgumentException $e) {
    echo "Invalid argument: ". $e->getMessage(). "\n";
} catch (Exception $e) {
    echo "Error: ". $e->getMessage(). "\n";
}

__HALT_COMPILER();