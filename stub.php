#!/usr/bin/env php
<?php

Phar::mapPhar("pharify.phar");

include_once "phar://pharify.phar/vendor/autoload.php";

$pharify = new Pharify\Console();
$pharify->run();

__HALT_COMPILER();