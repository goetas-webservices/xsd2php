#!/bin/php
<?php
foreach (array(
    __DIR__ . '/../autoload.php',
    __DIR__ . '/../../autoload.php',
    __DIR__ . '/../../../autoload.php',
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
) as $path) {
    if(is_file($path)){
        include $path;
        break;
    }
}

use Goetas\Xsd\XsdToPhp\Console\ConsoleRunner;

$cli = new ConsoleRunner();
$cli->run();
